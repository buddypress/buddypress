<?php
/**
 * BuddyPress Activity Classes
 *
 * @package BuddyPress
 * @subpackage Activity
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Activity_Activity {
	var $id;
	var $item_id;
	var $secondary_item_id;
	var $user_id;
	var $primary_link;
	var $component;
	var $type;
	var $action;
	var $content;
	var $date_recorded;
	var $hide_sitewide = false;
	var $mptt_left;
	var $mptt_right;
	var $is_spam;

	function __construct( $id = false ) {
		if ( !empty( $id ) ) {
			$this->id = $id;
			$this->populate();
		}
	}

	function populate() {
		global $wpdb, $bp;

		if ( $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE id = %d", $this->id ) ) ) {
			$this->id                = $row->id;
			$this->item_id           = $row->item_id;
			$this->secondary_item_id = $row->secondary_item_id;
			$this->user_id           = $row->user_id;
			$this->primary_link      = $row->primary_link;
			$this->component         = $row->component;
			$this->type              = $row->type;
			$this->action            = $row->action;
			$this->content           = $row->content;
			$this->date_recorded     = $row->date_recorded;
			$this->hide_sitewide     = $row->hide_sitewide;
			$this->mptt_left         = $row->mptt_left;
			$this->mptt_right        = $row->mptt_right;
			$this->is_spam           = $row->is_spam;

			bp_activity_update_meta_cache( $this->id );
		}
	}

	function save() {
		global $wpdb, $bp, $current_user;

		$this->id                = apply_filters_ref_array( 'bp_activity_id_before_save',                array( $this->id,                &$this ) );
		$this->item_id           = apply_filters_ref_array( 'bp_activity_item_id_before_save',           array( $this->item_id,           &$this ) );
		$this->secondary_item_id = apply_filters_ref_array( 'bp_activity_secondary_item_id_before_save', array( $this->secondary_item_id, &$this ) );
		$this->user_id           = apply_filters_ref_array( 'bp_activity_user_id_before_save',           array( $this->user_id,           &$this ) );
		$this->primary_link      = apply_filters_ref_array( 'bp_activity_primary_link_before_save',      array( $this->primary_link,      &$this ) );
		$this->component         = apply_filters_ref_array( 'bp_activity_component_before_save',         array( $this->component,         &$this ) );
		$this->type              = apply_filters_ref_array( 'bp_activity_type_before_save',              array( $this->type,              &$this ) );
		$this->action            = apply_filters_ref_array( 'bp_activity_action_before_save',            array( $this->action,            &$this ) );
		$this->content           = apply_filters_ref_array( 'bp_activity_content_before_save',           array( $this->content,           &$this ) );
		$this->date_recorded     = apply_filters_ref_array( 'bp_activity_date_recorded_before_save',     array( $this->date_recorded,     &$this ) );
		$this->hide_sitewide     = apply_filters_ref_array( 'bp_activity_hide_sitewide_before_save',     array( $this->hide_sitewide,     &$this ) );
		$this->mptt_left         = apply_filters_ref_array( 'bp_activity_mptt_left_before_save',         array( $this->mptt_left,         &$this ) );
		$this->mptt_right        = apply_filters_ref_array( 'bp_activity_mptt_right_before_save',        array( $this->mptt_right,        &$this ) );
		$this->is_spam           = apply_filters_ref_array( 'bp_activity_is_spam_before_save',           array( $this->is_spam,           &$this ) );

		// Use this, not the filters above
		do_action_ref_array( 'bp_activity_before_save', array( &$this ) );

		if ( !$this->component || !$this->type )
			return false;

		if ( !$this->primary_link )
			$this->primary_link = bp_loggedin_user_domain();

		// If we have an existing ID, update the activity item, otherwise insert it.
		if ( $this->id )
			$q = $wpdb->prepare( "UPDATE {$bp->activity->table_name} SET user_id = %d, component = %s, type = %s, action = %s, content = %s, primary_link = %s, date_recorded = %s, item_id = %d, secondary_item_id = %d, hide_sitewide = %d, is_spam = %d WHERE id = %d", $this->user_id, $this->component, $this->type, $this->action, $this->content, $this->primary_link, $this->date_recorded, $this->item_id, $this->secondary_item_id, $this->hide_sitewide, $this->is_spam, $this->id );
		else
			$q = $wpdb->prepare( "INSERT INTO {$bp->activity->table_name} ( user_id, component, type, action, content, primary_link, date_recorded, item_id, secondary_item_id, hide_sitewide, is_spam ) VALUES ( %d, %s, %s, %s, %s, %s, %s, %d, %d, %d, %d )", $this->user_id, $this->component, $this->type, $this->action, $this->content, $this->primary_link, $this->date_recorded, $this->item_id, $this->secondary_item_id, $this->hide_sitewide, $this->is_spam );

		if ( false === $wpdb->query( $q ) )
			return false;

		// If this is a new activity item, set the $id property
		if ( empty( $this->id ) )
			$this->id = $wpdb->insert_id;

		// If an existing activity item, prevent any changes to the content generating new @mention notifications.
		else
			add_filter( 'bp_activity_at_name_do_notifications', '__return_false' );

		do_action_ref_array( 'bp_activity_after_save', array( &$this ) );

		return true;
	}

	// Static Functions

	/**
	 * Get activity items, as specified by parameters
	 *
	 * @param array $args See $defaults for explanation of arguments
	 * @return array
	 */
	function get( $args = array() ) {
		global $wpdb, $bp;

		// Backward compatibility with old method of passing arguments
		if ( !is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '1.6', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'max',
				1 => 'page',
				2 => 'per_page',
				3 => 'sort',
				4 => 'search_terms',
				5 => 'filter',
				6 => 'display_comments',
				7 => 'show_hidden',
				8 => 'exclude',
				9 => 'in',
				10 => 'spam'
			);

			$func_args = func_get_args();
			$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$defaults = array(
			'page'             => 1,          // The current page
			'per_page'         => 25,         // Activity items per page
			'max'              => false,      // Max number of items to return
			'sort'             => 'DESC',     // ASC or DESC
			'exclude'          => false,      // Array of ids to exclude
			'in'               => false,      // Array of ids to limit query by (IN)
			'meta_query'       => false,      // Filter by activitymeta
			'filter'           => false,      // See self::get_filter_sql()
			'search_terms'     => false,      // Terms to search by
			'display_comments' => false,      // Whether to include activity comments
			'show_hidden'      => false,      // Show items marked hide_sitewide
			'spam'             => 'ham_only', // Spam status
		);
		$r = wp_parse_args( $args, $defaults );
		extract( $r );

		// Select conditions
		$select_sql = "SELECT DISTINCT a.*, u.user_email, u.user_nicename, u.user_login, u.display_name";

		$from_sql = " FROM {$bp->activity->table_name} a LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID";

		$join_sql = '';

		// Where conditions
		$where_conditions = array();

		// Spam
		if ( 'ham_only' == $spam )
			$where_conditions['spam_sql'] = 'a.is_spam = 0';
		elseif ( 'spam_only' == $spam )
			$where_conditions['spam_sql'] = 'a.is_spam = 1';

		// Searching
		if ( $search_terms ) {
			$search_terms = $wpdb->escape( $search_terms );
			$where_conditions['search_sql'] = "a.content LIKE '%%" . esc_sql( like_escape( $search_terms ) ) . "%%'";
		}

		// Filtering
		if ( $filter && $filter_sql = BP_Activity_Activity::get_filter_sql( $filter ) )
			$where_conditions['filter_sql'] = $filter_sql;

		// Sorting
		if ( $sort != 'ASC' && $sort != 'DESC' )
			$sort = 'DESC';

		// Hide Hidden Items?
		if ( !$show_hidden )
			$where_conditions['hidden_sql'] = "a.hide_sitewide = 0";

		// Exclude specified items
		if ( !empty( $exclude ) ) {
			$exclude = implode( ',', wp_parse_id_list( $exclude ) );
			$where_conditions['exclude'] = "a.id NOT IN ({$exclude})";
		}

		// The specific ids to which you want to limit the query
		if ( !empty( $in ) ) {
			$in = implode( ',', wp_parse_id_list( $in ) );
			$where_conditions['in'] = "a.id IN ({$in})";
		}

		// Process meta_query into SQL
		$meta_query_sql = self::get_meta_query_sql( $meta_query );

		if ( ! empty( $meta_query_sql['join'] ) ) {
			$join_sql .= $meta_query_sql['join'];
		}

		if ( ! empty( $meta_query_sql['where'] ) ) {
			$where_conditions[] = $meta_query_sql['where'];
		}

		// Alter the query based on whether we want to show activity item
		// comments in the stream like normal comments or threaded below
		// the activity.
		if ( false === $display_comments || 'threaded' === $display_comments )
			$where_conditions[] = "a.type != 'activity_comment'";

		$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

		// Define the preferred order for indexes
		$indexes = apply_filters( 'bp_activity_preferred_index_order', array( 'user_id', 'item_id', 'secondary_item_id', 'date_recorded', 'component', 'type', 'hide_sitewide', 'is_spam' ) );

		foreach( $indexes as $key => $index ) {
			if ( false !== strpos( $where_sql, $index ) ) {
				$the_index = $index;
				break; // Take the first one we find
			}
		}

		if ( !empty( $the_index ) ) {
			$index_hint_sql = "USE INDEX ({$the_index})";
		} else {
			$index_hint_sql = '';
		}

		if ( !empty( $per_page ) && !empty( $page ) ) {

			// Make sure page values are absolute integers
			$page     = absint( $page     );
			$per_page = absint( $per_page );

			$pag_sql    = $wpdb->prepare( "LIMIT %d, %d", absint( ( $page - 1 ) * $per_page ), $per_page );
			$activities = $wpdb->get_results( apply_filters( 'bp_activity_get_user_join_filter', "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY a.date_recorded {$sort} {$pag_sql}", $select_sql, $from_sql, $where_sql, $sort, $pag_sql ) );
		} else {
			$activities = $wpdb->get_results( apply_filters( 'bp_activity_get_user_join_filter', "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY a.date_recorded {$sort}", $select_sql, $from_sql, $where_sql, $sort ) );
		}

		$total_activities_sql = apply_filters( 'bp_activity_total_activities_sql', "SELECT count(DISTINCT a.id) FROM {$bp->activity->table_name} a {$index_hint_sql} {$join_sql} {$where_sql} ORDER BY a.date_recorded {$sort}", $where_sql, $sort );

		$total_activities = $wpdb->get_var( $total_activities_sql );

		// Get the fullnames of users so we don't have to query in the loop
		if ( bp_is_active( 'xprofile' ) && !empty( $activities ) ) {
			$activity_user_ids = wp_list_pluck( $activities, 'user_id' );
			$activity_user_ids = implode( ',', wp_parse_id_list( $activity_user_ids ) );

			if ( !empty( $activity_user_ids ) ) {
				if ( $names = $wpdb->get_results( "SELECT user_id, value AS user_fullname FROM {$bp->profile->table_name_data} WHERE field_id = 1 AND user_id IN ({$activity_user_ids})" ) ) {
					foreach ( (array) $names as $name )
						$tmp_names[$name->user_id] = $name->user_fullname;

					foreach ( (array) $activities as $i => $activity ) {
						if ( !empty( $tmp_names[$activity->user_id] ) )
							$activities[$i]->user_fullname = $tmp_names[$activity->user_id];
					}

					unset( $names );
					unset( $tmp_names );
				}
			}
		}

		// Get activity meta
		$activity_ids = array();
		foreach ( (array) $activities as $activity ) {
			$activity_ids[] = $activity->id;
		}

		if ( !empty( $activity_ids ) ) {
			bp_activity_update_meta_cache( $activity_ids );
		}

		if ( $activities && $display_comments )
			$activities = BP_Activity_Activity::append_comments( $activities, $spam );

		// If $max is set, only return up to the max results
		if ( !empty( $max ) ) {
			if ( (int) $total_activities > (int) $max )
				$total_activities = $max;
		}

		return array( 'activities' => $activities, 'total' => (int) $total_activities );
	}

	/**
	 * Get the SQL for the 'meta_query' param in BP_Activity_Activity::get()
	 *
	 * We use WP_Meta_Query to do the heavy lifting of parsing the
	 * meta_query array and creating the necessary SQL clauses. However,
	 * since BP_Activity_Activity::get() builds its SQL differently than
	 * WP_Query, we have to alter the return value (stripping the leading
	 * AND keyword from the 'where' clause).
	 *
	 * @since BuddyPress (1.8)
	 *
	 * @param array $meta_query An array of meta_query filters. See the
	 *   documentation for WP_Meta_Query for details.
	 * @return array $sql_array 'join' and 'where' clauses
	 */
	public static function get_meta_query_sql( $meta_query = array() ) {
		global $wpdb;

		$sql_array = array(
			'join'  => '',
			'where' => '',
		);

		if ( ! empty( $meta_query ) ) {
			$activity_meta_query = new WP_Meta_Query( $meta_query );

			// WP_Meta_Query expects the table name at
			// $wpdb->activitymeta
			$wpdb->activitymeta = buddypress()->activity->table_name_meta;

			$meta_sql = $activity_meta_query->get_sql( 'activity', 'a', 'id' );

			// Strip the leading AND - BP handles it in get()
			$sql_array['where'] = preg_replace( '/^\sAND/', '', $meta_sql['where'] );
			$sql_array['join']  = $meta_sql['join'];
		}

		return $sql_array;
	}

	/**
	 * In BuddyPress 1.2.x, this was used to retrieve specific activity stream items (for example, on an activity's permalink page).
	 * As of 1.5.x, use BP_Activity_Activity::get() with an 'in' parameter instead.
	 *
	 * @deprecated 1.5
	 * @deprecated Use BP_Activity_Activity::get() with an 'in' parameter instead.
	 * @param mixed $activity_ids Array or comma-separated string of activity IDs to retrieve
	 * @param int $max Maximum number of results to return. (Optional; default is no maximum)
	 * @param int $page The set of results that the user is viewing. Used in pagination. (Optional; default is 1)
	 * @param int $per_page Specifies how many results per page. Used in pagination. (Optional; default is 25)
	 * @param string MySQL column sort; ASC or DESC. (Optional; default is DESC)
	 * @param bool $display_comments Retrieve an activity item's associated comments or not. (Optional; default is false)
	 * @return array
	 * @since BuddyPress (1.2)
	 */
	function get_specific( $activity_ids, $max = false, $page = 1, $per_page = 25, $sort = 'DESC', $display_comments = false ) {
		_deprecated_function( __FUNCTION__, '1.5', 'Use BP_Activity_Activity::get() with the "in" parameter instead.' );
		return BP_Activity_Activity::get( $max, $page, $per_page, $sort, false, false, $display_comments, false, false, $activity_ids );
	}

	function get_id( $user_id, $component, $type, $item_id, $secondary_item_id, $action, $content, $date_recorded ) {
		global $bp, $wpdb;

		$where_args = false;

		if ( !empty( $user_id ) )
			$where_args[] = $wpdb->prepare( "user_id = %d", $user_id );

		if ( !empty( $component ) )
			$where_args[] = $wpdb->prepare( "component = %s", $component );

		if ( !empty( $type ) )
			$where_args[] = $wpdb->prepare( "type = %s", $type );

		if ( !empty( $item_id ) )
			$where_args[] = $wpdb->prepare( "item_id = %d", $item_id );

		if ( !empty( $secondary_item_id ) )
			$where_args[] = $wpdb->prepare( "secondary_item_id = %d", $secondary_item_id );

		if ( !empty( $action ) )
			$where_args[] = $wpdb->prepare( "action = %s", $action );

		if ( !empty( $content ) )
			$where_args[] = $wpdb->prepare( "content = %s", $content );

		if ( !empty( $date_recorded ) )
			$where_args[] = $wpdb->prepare( "date_recorded = %s", $date_recorded );

		if ( !empty( $where_args ) )
			$where_sql = 'WHERE ' . join( ' AND ', $where_args );
		else
			return false;

		return $wpdb->get_var( "SELECT id FROM {$bp->activity->table_name} {$where_sql}" );
	}

	function delete( $args ) {
		global $wpdb, $bp;

		$defaults = array(
			'id'                => false,
			'action'            => false,
			'content'           => false,
			'component'         => false,
			'type'              => false,
			'primary_link'      => false,
			'user_id'           => false,
			'item_id'           => false,
			'secondary_item_id' => false,
			'date_recorded'     => false,
			'hide_sitewide'     => false
		);
		$params = wp_parse_args( $args, $defaults );
		extract( $params );

		$where_args = false;

		if ( !empty( $id ) )
			$where_args[] = $wpdb->prepare( "id = %d", $id );

		if ( !empty( $user_id ) )
			$where_args[] = $wpdb->prepare( "user_id = %d", $user_id );

		if ( !empty( $action ) )
			$where_args[] = $wpdb->prepare( "action = %s", $action );

		if ( !empty( $content ) )
			$where_args[] = $wpdb->prepare( "content = %s", $content );

		if ( !empty( $component ) )
			$where_args[] = $wpdb->prepare( "component = %s", $component );

		if ( !empty( $type ) )
			$where_args[] = $wpdb->prepare( "type = %s", $type );

		if ( !empty( $primary_link ) )
			$where_args[] = $wpdb->prepare( "primary_link = %s", $primary_link );

		if ( !empty( $item_id ) )
			$where_args[] = $wpdb->prepare( "item_id = %d", $item_id );

		if ( !empty( $secondary_item_id ) )
			$where_args[] = $wpdb->prepare( "secondary_item_id = %d", $secondary_item_id );

		if ( !empty( $date_recorded ) )
			$where_args[] = $wpdb->prepare( "date_recorded = %s", $date_recorded );

		if ( !empty( $hide_sitewide ) )
			$where_args[] = $wpdb->prepare( "hide_sitewide = %d", $hide_sitewide );

		if ( !empty( $where_args ) )
			$where_sql = 'WHERE ' . join( ' AND ', $where_args );
		else
			return false;

		// Fetch the activity IDs so we can delete any comments for this activity item
		$activity_ids = $wpdb->get_col( "SELECT id FROM {$bp->activity->table_name} {$where_sql}" );

		if ( !$wpdb->query( "DELETE FROM {$bp->activity->table_name} {$where_sql}" ) )
			return false;

		if ( $activity_ids ) {
			BP_Activity_Activity::delete_activity_item_comments( $activity_ids );
			BP_Activity_Activity::delete_activity_meta_entries( $activity_ids );

			return $activity_ids;
		}

		return $activity_ids;
	}

	function delete_activity_item_comments( $activity_ids = array() ) {
		global $bp, $wpdb;

		$activity_ids = implode( ',', wp_parse_id_list( $activity_ids ) );

		return $wpdb->query( "DELETE FROM {$bp->activity->table_name} WHERE type = 'activity_comment' AND item_id IN ({$activity_ids})" );
	}

	function delete_activity_meta_entries( $activity_ids = array() ) {
		global $bp, $wpdb;

		$activity_ids = implode( ',', wp_parse_id_list( $activity_ids ) );

		foreach ( (array) $activity_ids as $activity_id ) {
			bp_activity_clear_meta_cache_for_activity( $activity_id );
		}

		return $wpdb->query( "DELETE FROM {$bp->activity->table_name_meta} WHERE activity_id IN ({$activity_ids})" );
	}

	/**
	 * Append activity comments to their associated activity items
	 *
	 * @global wpdb $wpdb WordPress database object
	 * @param array $activities
	 * @param bool $spam Optional; 'ham_only' (default), 'spam_only' or 'all'.
	 * @return array The updated activities with nested comments
	 * @since BuddyPress (1.2)
	 */
	function append_comments( $activities, $spam = 'ham_only' ) {
		global $wpdb;

		$activity_comments = array();

		// Now fetch the activity comments and parse them into the correct position in the activities array.
		foreach( (array) $activities as $activity ) {
			$top_level_parent_id = 'activity_comment' == $activity->type ? $activity->item_id : 0;
			$activity_comments[$activity->id] = BP_Activity_Activity::get_activity_comments( $activity->id, $activity->mptt_left, $activity->mptt_right, $spam, $top_level_parent_id );
		}

		// Merge the comments with the activity items
		foreach( (array) $activities as $key => $activity )
			if ( isset( $activity_comments[$activity->id] ) )
				$activities[$key]->children = $activity_comments[$activity->id];

		return $activities;
	}

	/**
	 * Get activity comments that are associated with a specific activity ID
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 * @global wpdb $wpdb WordPress database object
	 * @param int $activity_id Activity ID to fetch comments for
	 * @param int $left Left-most node boundary
	 * @param into $right Right-most node boundary
	 * @param bool $spam Optional; 'ham_only' (default), 'spam_only' or 'all'.
	 * @param int $top_level_parent_id The id of the root-level parent activity item
	 * @return array The updated activities with nested comments
	 * @since BuddyPress (1.2)
	 */
	function get_activity_comments( $activity_id, $left, $right, $spam = 'ham_only', $top_level_parent_id = 0 ) {
		global $wpdb, $bp;

		if ( empty( $top_level_parent_id ) ) {
			$top_level_parent_id = $activity_id;
		}

		if ( !$comments = wp_cache_get( 'bp_activity_comments_' . $activity_id ) ) {

			// Select the user's fullname with the query
			if ( bp_is_active( 'xprofile' ) ) {
				$fullname_select = ", pd.value as user_fullname";
				$fullname_from = ", {$bp->profile->table_name_data} pd ";
				$fullname_where = "AND pd.user_id = a.user_id AND pd.field_id = 1";

			// Prevent debug errors
			} else {
				$fullname_select = $fullname_from = $fullname_where = '';
			}

			// Don't retrieve activity comments marked as spam
			if ( 'ham_only' == $spam ) {
				$spam_sql = 'AND a.is_spam = 0';
			} elseif ( 'spam_only' == $spam ) {
				$spam_sql = 'AND a.is_spam = 1';
			} else {
				$spam_sql = '';
			}

			// The mptt BETWEEN clause allows us to limit returned descendants to the right part of the tree
			$sql = apply_filters( 'bp_activity_comments_user_join_filter', $wpdb->prepare( "SELECT a.*, u.user_email, u.user_nicename, u.user_login, u.display_name{$fullname_select} FROM {$bp->activity->table_name} a, {$wpdb->users} u{$fullname_from} WHERE u.ID = a.user_id {$fullname_where} AND a.type = 'activity_comment' {$spam_sql} AND a.item_id = %d AND a.mptt_left > %d AND a.mptt_left < %d ORDER BY a.date_recorded ASC", $top_level_parent_id, $left, $right ), $activity_id, $left, $right, $spam_sql );

			// Retrieve all descendants of the $root node
			$descendants = $wpdb->get_results( $sql );
			$ref         = array();

			// Loop descendants and build an assoc array
			foreach ( (array) $descendants as $d ) {
				$d->children = array();

				// If we have a reference on the parent
				if ( isset( $ref[ $d->secondary_item_id ] ) ) {
					$ref[ $d->secondary_item_id ]->children[ $d->id ] = $d;
					$ref[ $d->id ] =& $ref[ $d->secondary_item_id ]->children[ $d->id ];

				// If we don't have a reference on the parent, put in the root level
				} else {
					$comments[ $d->id ] = $d;
					$ref[ $d->id ] =& $comments[ $d->id ];
				}
			}
			wp_cache_set( 'bp_activity_comments_' . $activity_id, $comments, 'bp' );
		}

		return $comments;
	}

	function rebuild_activity_comment_tree( $parent_id, $left = 1 ) {
		global $wpdb, $bp;

		// The right value of this node is the left value + 1
		$right = $left + 1;

		// Get all descendants of this node
		$descendants = BP_Activity_Activity::get_child_comments( $parent_id );

		// Loop the descendants and recalculate the left and right values
		foreach ( (array) $descendants as $descendant )
			$right = BP_Activity_Activity::rebuild_activity_comment_tree( $descendant->id, $right );

		// We've got the left value, and now that we've processed the children
		// of this node we also know the right value
		if ( 1 == $left )
			$wpdb->query( $wpdb->prepare( "UPDATE {$bp->activity->table_name} SET mptt_left = %d, mptt_right = %d WHERE id = %d", $left, $right, $parent_id ) );
		else
			$wpdb->query( $wpdb->prepare( "UPDATE {$bp->activity->table_name} SET mptt_left = %d, mptt_right = %d WHERE type = 'activity_comment' AND id = %d", $left, $right, $parent_id ) );

		// Return the right value of this node + 1
		return $right + 1;
	}

	function get_child_comments( $parent_id ) {
		global $bp, $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE type = 'activity_comment' AND secondary_item_id = %d", $parent_id ) );
	}

	/**
	 * Fetch a list of all components that have activity items recorded
	 *
	 * @return array
	 */
	function get_recorded_components() {
		global $wpdb, $bp;
		return $wpdb->get_col( "SELECT DISTINCT component FROM {$bp->activity->table_name} ORDER BY component ASC" );
	}

	function get_sitewide_items_for_feed( $limit = 35 ) {
		global $bp;

		$activities    = bp_activity_get_sitewide( array( 'max' => $limit ) );
		$activity_feed = array();

		for ( $i = 0, $count = count( $activities ); $i < $count; ++$i ) {
				$title                            = explode( '<span', $activities[$i]['content'] );
				$activity_feed[$i]['title']       = trim( strip_tags( $title[0] ) );
				$activity_feed[$i]['link']        = $activities[$i]['primary_link'];
				$activity_feed[$i]['description'] = @sprintf( $activities[$i]['content'], '' );
				$activity_feed[$i]['pubdate']     = $activities[$i]['date_recorded'];
		}

		return $activity_feed;
	}

	function get_in_operator_sql( $field, $items ) {
		global $wpdb;

		// split items at the comma
		$items_dirty = explode( ',', $items );

		// array of prepared integers or quoted strings
		$items_prepared = array();

		// clean up and format each item
		foreach ( $items_dirty as $item ) {
			// clean up the string
			$item = trim( $item );
			// pass everything through prepare for security and to safely quote strings
			$items_prepared[] = ( is_numeric( $item ) ) ? $wpdb->prepare( '%d', $item ) : $wpdb->prepare( '%s', $item );
		}

		// build IN operator sql syntax
		if ( count( $items_prepared ) )
			return sprintf( '%s IN ( %s )', trim( $field ), implode( ',', $items_prepared ) );
		else
			return false;
	}

	function get_filter_sql( $filter_array ) {

		$filter_sql = array();

		if ( !empty( $filter_array['user_id'] ) ) {
			$user_sql = BP_Activity_Activity::get_in_operator_sql( 'a.user_id', $filter_array['user_id'] );
			if ( !empty( $user_sql ) )
				$filter_sql[] = $user_sql;
		}

		if ( !empty( $filter_array['object'] ) ) {
			$object_sql = BP_Activity_Activity::get_in_operator_sql( 'a.component', $filter_array['object'] );
			if ( !empty( $object_sql ) )
				$filter_sql[] = $object_sql;
		}

		if ( !empty( $filter_array['action'] ) ) {
			$action_sql = BP_Activity_Activity::get_in_operator_sql( 'a.type', $filter_array['action'] );
			if ( !empty( $action_sql ) )
				$filter_sql[] = $action_sql;
		}

		if ( !empty( $filter_array['primary_id'] ) ) {
			$pid_sql = BP_Activity_Activity::get_in_operator_sql( 'a.item_id', $filter_array['primary_id'] );
			if ( !empty( $pid_sql ) )
				$filter_sql[] = $pid_sql;
		}

		if ( !empty( $filter_array['secondary_id'] ) ) {
			$sid_sql = BP_Activity_Activity::get_in_operator_sql( 'a.secondary_item_id', $filter_array['secondary_id'] );
			if ( !empty( $sid_sql ) )
				$filter_sql[] = $sid_sql;
		}

		if ( empty( $filter_sql ) )
			return false;

		return join( ' AND ', $filter_sql );
	}

	function get_last_updated() {
		global $bp, $wpdb;

		return $wpdb->get_var( "SELECT date_recorded FROM {$bp->activity->table_name} ORDER BY date_recorded DESC LIMIT 1" );
	}

	function total_favorite_count( $user_id ) {
		if ( !$favorite_activity_entries = bp_get_user_meta( $user_id, 'bp_favorite_activities', true ) )
			return 0;

		return count( maybe_unserialize( $favorite_activity_entries ) );
	}

	function check_exists_by_content( $content ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE content = %s", $content ) );
	}

	function hide_all_for_user( $user_id ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "UPDATE {$bp->activity->table_name} SET hide_sitewide = 1 WHERE user_id = %d", $user_id ) );
	}
}

/**
 * Create a RSS feed using the activity component.
 *
 * You should only construct a new feed when you've validated that you're on
 * the appropriate screen.
 *
 * See {@link bp_activity_action_sitewide_feed()} as an example.
 *
 * Accepted parameters:
 *   id	              - internal id for the feed; should be alphanumeric only
 *                      (required)
 *   title            - RSS feed title
 *   link             - Relevant link for the RSS feed
 *   description      - RSS feed description
 *   ttl              - Time-to-live (see inline doc in constructor)
 *   update_period    - Part of the syndication module (see inline doc in
 *                      constructor for more info)
 *   update_frequency - Part of the syndication module (see inline doc in
 *                      constructor for more info)
 *   max              - Number of feed items to display
 *   activity_args    - Arguments passed to {@link bp_has_activities()}
 *
 * @since BuddyPress (1.8)
 */
class BP_Activity_Feed {
	/**
	 * Holds our custom class properties.
	 *
	 * These variables are stored in a protected array that is magically
	 * updated using PHP 5.2+ methods.
	 *
	 * @see BP_Feed::__construct() This is where $data is added
	 * @var array
	 */
	protected $data;

	/**
	 * Magic method for checking the existence of a certain data variable.
	 *
	 * @param string $key
	 */
	public function __isset( $key ) { return isset( $this->data[$key] ); }

	/**
	 * Magic method for getting a certain data variable.
	 *
	 * @param string $key
	 */
	public function __get( $key ) { return isset( $this->data[$key] ) ? $this->data[$key] : null; }

	/**
	 * Constructor.
	 *
	 * @param array $args Optional
	 */
	public function __construct( $args = array() ) {
		// If feeds are disabled, stop now!
		if ( false === (bool) apply_filters( 'bp_activity_enable_feeds', true ) ) {
			global $wp_query;

			// set feed flag to false
			$wp_query->is_feed = false;

			return false;
		}

		// Setup data
		$this->data = wp_parse_args( $args, array(
			// Internal identifier for the RSS feed - should be alphanumeric only
			'id'               => '',

			// RSS title - should be plain-text
			'title'            => '',

			// relevant link for the RSS feed
			'link'             => '',

			// RSS description - should be plain-text
			'description'      => '',

			// Time-to-live - number of minutes to cache the data before an aggregator
			// requests it again.  This is only acknowledged if the RSS client supports it
			//
			// See: http://www.rssboard.org/rss-profile#element-channel-ttl
			//      http://www.kbcafe.com/rss/rssfeedstate.html#ttl
			'ttl'              => '30',

			// Syndication module - similar to ttl, but not really supported by RSS
			// clients
			//
			// See: http://web.resource.org/rss/1.0/modules/syndication/#description
			//      http://www.kbcafe.com/rss/rssfeedstate.html#syndicationmodule
			'update_period'    => 'hourly',
			'update_frequency' => 2,

			// Number of items to display
			'max'              => 50,

			// Activity arguments passed to bp_has_activities()
			'activity_args'    => array()
		) );

		// Plugins can use this filter to modify the feed before it is setup
		do_action_ref_array( 'bp_activity_feed_prefetch', array( &$this ) );

		// Setup class properties
		$this->setup_properties();

		// Check if id is valid
		if ( empty( $this->id ) ) {
			_doing_it_wrong( 'BP_Activity_Feed', __( "RSS feed 'id' must be defined", 'buddypress' ), 'BP 1.8' );
			return false;
		}

		// Plugins can use this filter to modify the feed after it's setup
		do_action_ref_array( 'bp_activity_feed_postfetch', array( &$this ) );

		// Setup feed hooks
		$this->setup_hooks();

		// Output the feed
		$this->output();

		// Kill the rest of the output
		die();
	}

	/** SETUP ****************************************************************/

	/**
	 * Setup and validate the class properties.
	 */
	protected function setup_properties() {
		$this->id               = sanitize_title( $this->id );
		$this->title            = strip_tags( $this->title );
		$this->link             = esc_url_raw( $this->link );
		$this->description      = strip_tags( $this->description );
		$this->ttl              = (int) $this->ttl;
		$this->update_period    = strip_tags( $this->update_period );
		$this->update_frequency = (int) $this->update_frequency;

		$this->activity_args    = wp_parse_args( $this->activity_args, array(
			'max'              => $this->max,
			'display_comments' => 'stream'
		) );

	}

	/**
	 * Setup some hooks that are used in the feed.
	 *
	 * Currently, these hooks are used to maintain backwards compatibility with
	 * the RSS feeds previous to BP 1.8.
	 */
	protected function setup_hooks() {
		add_action( 'bp_activity_feed_rss_attributes',   array( $this, 'backpat_rss_attributes' ) );
		add_action( 'bp_activity_feed_channel_elements', array( $this, 'backpat_channel_elements' ) );
		add_action( 'bp_activity_feed_item_elements',    array( $this, 'backpat_item_elements' ) );
	}

	/** BACKPAT HOOKS ********************************************************/

	public function backpat_rss_attributes() {
		do_action( 'bp_activity_' . $this->id . '_feed' );
	}

	public function backpat_channel_elements() {
		do_action( 'bp_activity_' . $this->id . '_feed_head' );
	}

	public function backpat_item_elements() {
		switch ( $this->id ) {

			// sitewide and friends feeds use the 'personal' hook
			case 'sitewide' :
			case 'friends' :
				$id = 'personal';

				break;

			default :
				$id = $this->id;

				break;
		}

		do_action( 'bp_activity_' . $id . '_feed_item' );
	}

	/** HELPERS **************************************************************/

	/**
	 * Output the feed's item content.
	 */
	protected function feed_content() {
		bp_activity_content_body();

		switch ( $this->id ) {

			// also output parent activity item if we're on a specific feed
			case 'favorites' :
			case 'friends' :
			case 'mentions' :
			case 'personal' :

				if ( 'activity_comment' == bp_get_activity_action_name() ) :
			?>
				<strong><?php _e( 'In reply to', 'buddypress' ) ?></strong> -
				<?php bp_activity_parent_content() ?>
			<?php
				endif;

				break;
		}
	}

	/** OUTPUT ***************************************************************/

	/**
	 * Output the RSS feed.
	 */
	protected function output() {
		// set up some additional headers if not on a directory page
		// this is done b/c BP uses pseudo-pages
		if ( ! bp_is_directory() ) {
			global $wp_query;

			$wp_query->is_404 = false;
			status_header( 200 );
		}

		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
		echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?'.'>';
	?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	<?php do_action( 'bp_activity_feed_rss_attributes' ); ?>
>

<channel>
	<title><?php echo $this->title; ?></title>
	<link><?php echo $this->link; ?></link>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<description><?php echo $this->description ?></description>
	<lastBuildDate><?php echo mysql2date( 'D, d M Y H:i:s O', bp_activity_get_last_updated(), false ); ?></lastBuildDate>
	<generator>http://buddypress.org/?v=<?php bp_version(); ?></generator>
	<language><?php bloginfo_rss( 'language' ); ?></language>
	<ttl><?php echo $this->ttl; ?></ttl>
	<sy:updatePeriod><?php echo $this->update_period; ?></sy:updatePeriod>
 	<sy:updateFrequency><?php echo $this->update_frequency; ?></sy:updateFrequency>
	<?php do_action( 'bp_activity_feed_channel_elements' ); ?>

	<?php if ( bp_has_activities( $this->activity_args ) ) : ?>
		<?php while ( bp_activities() ) : bp_the_activity(); ?>
			<item>
				<guid isPermaLink="false"><?php bp_activity_feed_item_guid(); ?></guid>
				<title><?php echo stripslashes( bp_get_activity_feed_item_title() ); ?></title>
				<link><?php bp_activity_thread_permalink() ?></link>
				<pubDate><?php echo mysql2date( 'D, d M Y H:i:s O', bp_get_activity_feed_item_date(), false ); ?></pubDate>

				<?php if ( bp_get_activity_feed_item_description() ) : ?>
					<content:encoded><![CDATA[<?php $this->feed_content(); ?>]]></content:encoded>
				<?php endif; ?>

				<?php if ( bp_activity_can_comment() ) : ?>
					<slash:comments><?php bp_activity_comment_count(); ?></slash:comments>
				<?php endif; ?>

				<?php do_action( 'bp_activity_feed_item_elements' ); ?>
			</item>
		<?php endwhile; ?>

	<?php endif; ?>
</channel>
</rss><?php
	}
}
