<?php
/**
 * BuddyPress Activity Classes
 *
 * @package BuddyPress
 * @subpackage Activity
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Database interaction class for the BuddyPress activity component.
 *
 * Instance methods are available for creating/editing an activity,
 * static methods for querying activities.
 *
 * @since BuddyPress (1.0)
 */
class BP_Activity_Activity {

	/** Properties ************************************************************/

	/**
	 * ID of the activity item.
	 *
	 * @var int
	 */
	var $id;

	/**
	 * ID of the associated item.
	 *
	 * @var int
	 */
	var $item_id;

	/**
	 * ID of the associated secondary item.
	 *
	 * @var int
	 */
	var $secondary_item_id;

	/**
	 * ID of user associated with the activity item.
	 *
	 * @var int
	 */
	var $user_id;

	/**
	 * The primary URL for the activity in RSS feeds.
	 *
	 * @var string
	 */
	var $primary_link;

	/**
	 * BuddyPress component the activity item relates to.
	 *
	 * @var string
	 */
	var $component;

	/**
	 * Activity type, eg 'new_blog_post'.
	 *
	 * @var string
	 */
	var $type;

	/**
	 * Description of the activity, eg 'Alex updated his profile.'
	 *
	 * @var string
	 */
	var $action;

	/**
	 * The content of the activity item.
	 *
	 * @var string
	 */
	var $content;

	/**
	 * The date the activity item was recorded, in 'Y-m-d h:i:s' format.
	 *
	 * @var string
	 */
	var $date_recorded;

	/**
	 * Whether the item should be hidden in sitewide streams.
	 *
	 * @var int
	 */
	var $hide_sitewide = false;

	/**
	 * Node boundary start for activity or activity comment.
	 *
	 * @var int
	 */
	var $mptt_left;

	/**
	 * Node boundary end for activity or activity comment.
	 *
	 * @var int
	 */
	var $mptt_right;

	/**
	 * Whether this item is marked as spam.
	 *
	 * @var int
	 */
	var $is_spam;

	/**
	 * Constructor method.
	 *
	 * @param int $id Optional. The ID of a specific activity item.
	 */
	public function __construct( $id = false ) {
		if ( !empty( $id ) ) {
			$this->id = $id;
			$this->populate();
		}
	}

	/**
	 * Populate the object with data about the specific activity item.
	 */
	public function populate() {
		global $wpdb, $bp;

		$row = wp_cache_get( $this->id, 'bp_activity' );

		if ( false === $row ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->activity->table_name} WHERE id = %d", $this->id ) );

			wp_cache_set( $this->id, $row, 'bp_activity' );
		}

		if ( ! empty( $row ) ) {
			$this->id                = (int) $row->id;
			$this->item_id           = (int) $row->item_id;
			$this->secondary_item_id = (int) $row->secondary_item_id;
			$this->user_id           = (int) $row->user_id;
			$this->primary_link      = $row->primary_link;
			$this->component         = $row->component;
			$this->type              = $row->type;
			$this->action            = $row->action;
			$this->content           = $row->content;
			$this->date_recorded     = $row->date_recorded;
			$this->hide_sitewide     = $row->hide_sitewide;
			$this->mptt_left         = (int) $row->mptt_left;
			$this->mptt_right        = (int) $row->mptt_right;
			$this->is_spam           = $row->is_spam;
		}

		// Generate dynamic 'action' when possible
		$action = bp_activity_generate_action_string( $this );
		if ( false !== $action ) {
			$this->action = $action;

		// If no callback is available, use the literal string from
		// the database row
		} else if ( ! empty( $row->action ) ) {
			$this->action = $row->action;

		// Provide a fallback to avoid PHP notices
		} else {
			$this->action = '';
		}
	}

	/**
	 * Save the activity item to the database.
	 *
	 * @return bool True on success.
	 */
	public function save() {
		global $wpdb, $bp;

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

	/** Static Methods ***************************************************/

	/**
	 * Get activity items, as specified by parameters
	 *
	 * @see BP_Activity_Activity::get_filter_sql() for a description of the
	 *      'filter' parameter.
	 * @see WP_Meta_Query::queries for a description of the 'meta_query'
	 *      parameter format.
	 *
	 * @param array $args {
	 *     An array of arguments. All items are optional.
	 *     @type int $page Which page of results to fetch. Using page=1
	 *                     without per_page will result in no pagination.
	 *                     Default: 1.
	 *     @type int|bool $per_page Number of results per page. Default: 25.
	 *     @type int|bool $max Maximum number of results to return.
	 *                         Default: false (unlimited).
	 *     @type string $sort ASC or DESC. Default: 'DESC'.
	 *     @type array $exclude Array of activity IDs to exclude.
	 *                          Default: false.
	 *     @type array $in Array of ids to limit query by (IN).
	 *                     Default: false.
	 *     @type array $meta_query An array of meta_query conditions.
	 *                             See WP_Meta_Query::queries for description.
	 *     @type array $date_query An array of date_query conditions.
	 *                             See first parameter of WP_Date_Query::__construct()
	 *                             for description.
	 *     @type array $filter See BP_Activity_Activity::get_filter_sql().
	 *     @type string $search_terms Limit results by a search term.
	 *                                Default: false.
	 *     @type bool $display_comments Whether to include activity comments.
	 *                                  Default: false.
	 *     @type bool $show_hidden Whether to show items marked hide_sitewide.
	 *                             Default: false.
	 *     @type string $spam Spam status. Default: 'ham_only'.
	 *     @type bool $update_meta_cache Whether to pre-fetch metadata for
	 *           queried activity items. Default: true.
	 *     @type string|bool $count_total If true, an additional DB query
	 *           is run to count the total activity items for the query.
	 *           Default: false.
	 * }
	 * @return array The array returned has two keys:
	 *     - 'total' is the count of located activities
	 *     - 'activities' is an array of the located activities
	 */
	public static function get( $args = array() ) {
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
			'page'              => 1,          // The current page
			'per_page'          => 25,         // Activity items per page
			'max'               => false,      // Max number of items to return
			'sort'              => 'DESC',     // ASC or DESC
			'exclude'           => false,      // Array of ids to exclude
			'in'                => false,      // Array of ids to limit query by (IN)
			'meta_query'        => false,      // Filter by activitymeta
			'date_query'        => false,      // Filter by date
			'filter'            => false,      // See self::get_filter_sql()
			'search_terms'      => false,      // Terms to search by
			'display_comments'  => false,      // Whether to include activity comments
			'show_hidden'       => false,      // Show items marked hide_sitewide
			'spam'              => 'ham_only', // Spam status
			'update_meta_cache' => true,
			'count_total'       => false,
		);
		$r = wp_parse_args( $args, $defaults );
		extract( $r );

		// Select conditions
		$select_sql = "SELECT DISTINCT a.id";

		$from_sql   = " FROM {$bp->activity->table_name} a";

		$join_sql   = '';

		// Where conditions
		$where_conditions = array();

		// Excluded types
		$excluded_types = array();

		// Spam
		if ( 'ham_only' == $spam )
			$where_conditions['spam_sql'] = 'a.is_spam = 0';
		elseif ( 'spam_only' == $spam )
			$where_conditions['spam_sql'] = 'a.is_spam = 1';

		// Searching
		if ( $search_terms ) {
			$search_terms_like = '%' . bp_esc_like( $search_terms ) . '%';
			$where_conditions['search_sql'] = $wpdb->prepare( 'a.content LIKE %s', $search_terms_like );
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

		// Process date_query into SQL
		$date_query_sql = self::get_date_query_sql( $date_query );

		if ( ! empty( $date_query_sql ) ) {
			$where_conditions['date'] = $date_query_sql;
		}

		// Alter the query based on whether we want to show activity item
		// comments in the stream like normal comments or threaded below
		// the activity.
		if ( false === $display_comments || 'threaded' === $display_comments ) {
			$excluded_types[] = 'activity_comment';
		}

		// Exclude 'last_activity' items unless the 'action' filter has
		// been explicitly set
		if ( empty( $filter['object'] ) ) {
			$excluded_types[] = 'last_activity';
		}

		// Exclude 'new_member' items if xprofile component is not active
		if ( ! bp_is_active( 'xprofile' ) ) {
			$excluded_types[] = 'new_member';
		}

		// Build the excluded type sql part
		if ( ! empty( $excluded_types ) ) {
			$not_in = "'" . implode( "', '", esc_sql( $excluded_types ) ) . "'";
			$where_conditions['excluded_types'] = "a.type NOT IN ({$not_in})";
		}

		// Filter the where conditions
		$where_conditions = apply_filters( 'bp_activity_get_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

		// Join the where conditions together
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

		// Sanitize page and per_page parameters
		$page     = absint( $page     );
		$per_page = absint( $per_page );

		$retval = array(
			'activities'     => null,
			'total'          => null,
			'has_more_items' => null,
		);

		// Filter and return true to use the legacy query structure (not recommended)
		if ( apply_filters( 'bp_use_legacy_activity_query', false, __METHOD__, $r ) ) {

			// Legacy queries joined against the user table
			$select_sql = "SELECT DISTINCT a.*, u.user_email, u.user_nicename, u.user_login, u.display_name";
			$from_sql   = " FROM {$bp->activity->table_name} a LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID";

			if ( ! empty( $page ) && ! empty( $per_page ) ) {
				$pag_sql    = $wpdb->prepare( "LIMIT %d, %d", absint( ( $page - 1 ) * $per_page ), $per_page );
				$activities = $wpdb->get_results( apply_filters( 'bp_activity_get_user_join_filter', "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY a.date_recorded {$sort} {$pag_sql}", $select_sql, $from_sql, $where_sql, $sort, $pag_sql ) );
			} else {
				$activities = $wpdb->get_results( apply_filters( 'bp_activity_get_user_join_filter', "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY a.date_recorded {$sort}", $select_sql, $from_sql, $where_sql, $sort ) );
			}

		} else {

			// Query first for activity IDs
			$activity_ids_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY a.date_recorded {$sort}";

			if ( ! empty( $per_page ) && ! empty( $page ) ) {
				// We query for $per_page + 1 items in order to
				// populate the has_more_items flag
				$activity_ids_sql .= $wpdb->prepare( " LIMIT %d, %d", absint( ( $page - 1 ) * $per_page ), $per_page + 1 );
			}

			$activity_ids_sql = apply_filters( 'bp_activity_paged_activities_sql', $activity_ids_sql, $r );

			$activity_ids = $wpdb->get_col( $activity_ids_sql );

			$retval['has_more_items'] = ! empty( $per_page ) && count( $activity_ids ) > $per_page;

			// If we've fetched more than the $per_page value, we
			// can discard the extra now
			if ( ! empty( $per_page ) && count( $activity_ids ) === $per_page + 1 ) {
				array_pop( $activity_ids );
			}

			$activities = self::get_activity_data( $activity_ids );
		}

		// Get the fullnames of users so we don't have to query in the loop
		$activities = self::append_user_fullnames( $activities );

		// Get activity meta
		$activity_ids = array();
		foreach ( (array) $activities as $activity ) {
			$activity_ids[] = $activity->id;
		}

		if ( ! empty( $activity_ids ) && $update_meta_cache ) {
			bp_activity_update_meta_cache( $activity_ids );
		}

		if ( $activities && $display_comments )
			$activities = BP_Activity_Activity::append_comments( $activities, $spam );

		// Pre-fetch data associated with activity users and other objects
		BP_Activity_Activity::prefetch_object_data( $activities );

		// Generate action strings
		$activities = BP_Activity_Activity::generate_action_strings( $activities );

		$retval['activities'] = $activities;

		// If $max is set, only return up to the max results
		if ( ! empty( $r['count_total'] ) ) {

			$total_activities_sql = apply_filters( 'bp_activity_total_activities_sql', "SELECT count(DISTINCT a.id) FROM {$bp->activity->table_name} a {$join_sql} {$where_sql}", $where_sql, $sort );
			$total_activities     = $wpdb->get_var( $total_activities_sql );

			if ( !empty( $max ) ) {
				if ( (int) $total_activities > (int) $max )
					$total_activities = $max;
			}

			$retval['total'] = $total_activities;
		}

		return $retval;
	}

	/**
	 * Convert activity IDs to activity objects, as expected in template loop.
	 *
	 * @since 2.0
	 *
	 * @param array $activity_ids Array of activity IDs.
	 * @return array
	 */
	protected static function get_activity_data( $activity_ids = array() ) {
		global $wpdb;

		// Bail if no activity ID's passed
		if ( empty( $activity_ids ) ) {
			return array();
		}

		// Get BuddyPress
		$bp = buddypress();

		$activities   = array();
		$uncached_ids = bp_get_non_cached_ids( $activity_ids, 'bp_activity' );

		// Prime caches as necessary
		if ( ! empty( $uncached_ids ) ) {
			// Format the activity ID's for use in the query below
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

			// Fetch data from activity table, preserving order
			$queried_adata = $wpdb->get_results( "SELECT * FROM {$bp->activity->table_name} WHERE id IN ({$uncached_ids_sql})");

			// Put that data into the placeholders created earlier,
			// and add it to the cache
			foreach ( (array) $queried_adata as $adata ) {
				wp_cache_set( $adata->id, $adata, 'bp_activity' );
			}
		}

		// Now fetch data from the cache
		foreach ( $activity_ids as $activity_id ) {
			$activities[] = wp_cache_get( $activity_id, 'bp_activity' );
		}

		// Then fetch user data
		$user_query = new BP_User_Query( array(
			'user_ids'        => wp_list_pluck( $activities, 'user_id' ),
			'populate_extras' => false,
		) );

		// Associated located user data with activity items
		foreach ( $activities as $a_index => $a_item ) {
			$a_user_id = intval( $a_item->user_id );
			$a_user    = isset( $user_query->results[ $a_user_id ] ) ? $user_query->results[ $a_user_id ] : '';

			if ( !empty( $a_user ) ) {
				$activities[ $a_index ]->user_email    = $a_user->user_email;
				$activities[ $a_index ]->user_nicename = $a_user->user_nicename;
				$activities[ $a_index ]->user_login    = $a_user->user_login;
				$activities[ $a_index ]->display_name  = $a_user->display_name;
			}
		}

		return $activities;
	}

	/**
	 * Append xProfile fullnames to an activity array.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param array $activities Activities array.
	 * @return array
	 */
	protected static function append_user_fullnames( $activities ) {

		if ( bp_is_active( 'xprofile' ) && ! empty( $activities ) ) {
			$activity_user_ids = wp_list_pluck( $activities, 'user_id' );

			if ( ! empty( $activity_user_ids ) ) {
				$fullnames = bp_core_get_user_displaynames( $activity_user_ids );
				if ( ! empty( $fullnames ) ) {
					foreach ( (array) $activities as $i => $activity ) {
						if ( ! empty( $fullnames[ $activity->user_id ] ) ) {
							$activities[ $i ]->user_fullname = $fullnames[ $activity->user_id ];
						}
					}
				}
			}
		}

		return $activities;
	}

	/**
	 * Pre-fetch data for objects associated with activity items.
	 *
	 * Activity items are associated with users, and often with other
	 * BuddyPress data objects. Here, we pre-fetch data about these
	 * associated objects, so that inline lookups - done primarily when
	 * building action strings - do not result in excess database queries.
	 *
	 * The only object data required for activity component activity types
	 * (activity_update and activity_comment) is related to users, and that
	 * info is fetched separately in BP_Activity_Activity::get_activity_data().
	 * So this method contains nothing but a filter that allows other
	 * components, such as bp-friends and bp-groups, to hook in and prime
	 * their own caches at the beginning of an activity loop.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param array $activities Array of activities.
	 */
	protected static function prefetch_object_data( $activities ) {
		return apply_filters( 'bp_activity_prefetch_object_data', $activities );
	}

	/**
	 * Generate action strings for the activities located in BP_Activity_Activity::get().
	 *
	 * If no string can be dynamically generated for a given item
	 * (typically because the activity type has not been properly
	 * registered), the static 'action' value pulled from the database will
	 * be left in place.
	 *
	 * @since BuddyPress (2.0.0)
	 *
	 * @param array $activities Array of activities.
	 * @return array
	 */
	protected static function generate_action_strings( $activities ) {
		foreach ( $activities as $key => $activity ) {
			$generated_action = bp_activity_generate_action_string( $activity );
			if ( false !== $generated_action ) {
				$activity->action = $generated_action;
			}

			$activities[ $key ] = $activity;
		}

		return $activities;
	}

	/**
	 * Get the SQL for the 'meta_query' param in BP_Activity_Activity::get().
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
	 * @return array $sql_array 'join' and 'where' clauses.
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
	 * Get the SQL for the 'date_query' param in BP_Activity_Activity::get().
	 *
	 * We use BP_Date_Query, which extends WP_Date_Query, to do the heavy lifting
	 * of parsing the date_query array and creating the necessary SQL clauses.
	 * However, since BP_Activity_Activity::get() builds its SQL differently than
	 * WP_Query, we have to alter the return value (stripping the leading AND
	 * keyword from the query).
	 *
	 * @since BuddyPress (2.1.0)
	 *
	 * @param array $date_query An array of date_query parameters. See the
	 *        documentation for the first parameter of WP_Date_Query.
	 * @return string
	 */
	public static function get_date_query_sql( $date_query = array() ) {
		$sql = '';

		// Date query
		if ( ! empty( $date_query ) && is_array( $date_query ) && class_exists( 'BP_Date_Query' ) ) {
			$date_query = new BP_Date_Query( $date_query, 'date_recorded' );
			$sql = preg_replace( '/^\sAND/', '', $date_query->get_sql() );
		}

		return $sql;
	}

	/**
	 * In BuddyPress 1.2.x, this was used to retrieve specific activity stream items (for example, on an activity's permalink page).
	 *
	 * As of 1.5.x, use BP_Activity_Activity::get() with an 'in' parameter instead.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @deprecated 1.5
	 * @deprecated Use BP_Activity_Activity::get() with an 'in' parameter instead.
	 *
	 * @param mixed $activity_ids Array or comma-separated string of activity IDs to retrieve
	 * @param int $max Maximum number of results to return. (Optional; default is no maximum)
	 * @param int $page The set of results that the user is viewing. Used in pagination. (Optional; default is 1)
	 * @param int $per_page Specifies how many results per page. Used in pagination. (Optional; default is 25)
	 * @param string MySQL column sort; ASC or DESC. (Optional; default is DESC)
	 * @param bool $display_comments Retrieve an activity item's associated comments or not. (Optional; default is false)
	 * @return array
	 */
	public static function get_specific( $activity_ids, $max = false, $page = 1, $per_page = 25, $sort = 'DESC', $display_comments = false ) {
		_deprecated_function( __FUNCTION__, '1.5', 'Use BP_Activity_Activity::get() with the "in" parameter instead.' );
		return BP_Activity_Activity::get( $max, $page, $per_page, $sort, false, false, $display_comments, false, false, $activity_ids );
	}

	/**
	 * Get the first activity ID that matches a set of criteria.
	 *
	 * @param int $user_id The user ID to filter by.
	 * @param string $component The component to filter by.
	 * @param string $type The activity type to filter by.
	 * @param int $item_id The associated item to filter by.
	 * @param int $secondary_item_id The secondary associated item to filter by.
	 * @param string $action The action to filter by.
	 * @param string $content The content to filter by.
	 * @param string $date_recorded The date to filter by.
	 * @return int|bool Activity ID on success, false if none is found.
	 */
	public static function get_id( $user_id, $component, $type, $item_id, $secondary_item_id, $action, $content, $date_recorded ) {
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

	/**
	 * Delete activity items from the database.
	 *
	 * To delete a specific activity item, pass an 'id' parameter.
	 * Otherwise use the filters.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @param array $args {
	 *     @int $id Optional. The ID of a specific item to delete.
	 *     @string $action Optional. The action to filter by.
	 *     @string $content Optional. The content to filter by.
	 *     @string $component Optional. The component name to filter by.
	 *     @string $type Optional. The activity type to filter by.
	 *     @string $primary_link Optional. The primary URL to filter by.
	 *     @int $user_id Optional. The user ID to filter by.
	 *     @int $item_id Optional. The associated item ID to filter by.
	 *     @int $secondary_item_id Optional. The secondary associated item ID to filter by.
	 *     @string $date_recorded Optional. The date to filter by.
	 *     @int $hide_sitewide Optional. Default: false.
	 * }
	 * @return array|bool An array of deleted activity IDs on success, false on failure.
	 */
	public static function delete( $args = array() ) {
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

		if ( ! $wpdb->query( "DELETE FROM {$bp->activity->table_name} {$where_sql}" ) ) {
			return false;
		}

		// Handle accompanying activity comments and meta deletion
		if ( $activity_ids ) {
			$activity_ids_comma          = implode( ',', wp_parse_id_list( $activity_ids ) );
			$activity_comments_where_sql = "WHERE type = 'activity_comment' AND item_id IN ({$activity_ids_comma})";

			// Fetch the activity comment IDs for our deleted activity items
			$activity_comment_ids = $wpdb->get_col( "SELECT id FROM {$bp->activity->table_name} {$activity_comments_where_sql}" );

			// We have activity comments!
			if ( ! empty( $activity_comment_ids ) ) {
				// Delete activity comments
				$wpdb->query( "DELETE FROM {$bp->activity->table_name} {$activity_comments_where_sql}" );

				// Merge activity IDs with activity comment IDs
				$activity_ids = array_merge( $activity_ids, $activity_comment_ids );
			}

			// Delete all activity meta entries for activity items and activity comments
			BP_Activity_Activity::delete_activity_meta_entries( $activity_ids );
		}

		return $activity_ids;
	}

	/**
	 * Delete the comments associated with a set of activity items.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @todo Mark as deprecated?  Method is no longer used internally.
	 *
	 * @param array $activity_ids Activity IDs whose comments should be deleted.
	 * @param bool $delete_meta Should we delete the activity meta items for these comments?
	 * @return bool True on success.
	 */
	public static function delete_activity_item_comments( $activity_ids = array(), $delete_meta = true ) {
		global $bp, $wpdb;

		$delete_meta = (bool) $delete_meta;

		$activity_ids = implode( ',', wp_parse_id_list( $activity_ids ) );

		if ( $delete_meta ) {
			// Fetch the activity comment IDs for our deleted activity items
			$activity_comment_ids = $wpdb->get_col( "SELECT id FROM {$bp->activity->table_name} WHERE type = 'activity_comment' AND item_id IN ({$activity_ids})" );

			if ( ! empty( $activity_comment_ids ) ) {
				self::delete_activity_meta_entries( $activity_comment_ids );
			}
		}

		return $wpdb->query( "DELETE FROM {$bp->activity->table_name} WHERE type = 'activity_comment' AND item_id IN ({$activity_ids})" );
	}

	/**
	 * Delete the meta entries associated with a set of activity items.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @param array $activity_ids Activity IDs whose meta should be deleted.
	 * @return bool True on success.
	 */
	public static function delete_activity_meta_entries( $activity_ids = array() ) {
		$activity_ids = wp_parse_id_list( $activity_ids );

		foreach ( $activity_ids as $activity_id ) {
			bp_activity_delete_meta( $activity_id );
		}

		return true;
	}

	/**
	 * Append activity comments to their associated activity items.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global wpdb $wpdb WordPress database object
	 *
	 * @param array $activities Activities to fetch comments for.
	 * @param bool $spam Optional. 'ham_only' (default), 'spam_only' or 'all'.
	 * @return array The updated activities with nested comments.
	 */
	public static function append_comments( $activities, $spam = 'ham_only' ) {
		$activity_comments = array();

		// Now fetch the activity comments and parse them into the correct position in the activities array.
		foreach ( (array) $activities as $activity ) {
			$top_level_parent_id = 'activity_comment' == $activity->type ? $activity->item_id : 0;
			$activity_comments[$activity->id] = BP_Activity_Activity::get_activity_comments( $activity->id, $activity->mptt_left, $activity->mptt_right, $spam, $top_level_parent_id );
		}

		// Merge the comments with the activity items
		foreach ( (array) $activities as $key => $activity ) {
			if ( isset( $activity_comments[$activity->id] ) ) {
				$activities[$key]->children = $activity_comments[$activity->id];
			}
		}

		return $activities;
	}

	/**
	 * Get activity comments that are associated with a specific activity ID.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $activity_id Activity ID to fetch comments for.
	 * @param int $left Left-most node boundary.
	 * @param into $right Right-most node boundary.
	 * @param bool $spam Optional. 'ham_only' (default), 'spam_only' or 'all'.
	 * @param int $top_level_parent_id Optional. The id of the root-level parent activity item.
	 * @return array The updated activities with nested comments.
	 */
	public static function get_activity_comments( $activity_id, $left, $right, $spam = 'ham_only', $top_level_parent_id = 0 ) {
		global $wpdb, $bp;

		if ( empty( $top_level_parent_id ) ) {
			$top_level_parent_id = $activity_id;
		}

		$comments = wp_cache_get( $activity_id, 'bp_activity_comments' );

		// We store the string 'none' to cache the fact that the
		// activity item has no comments
		if ( 'none' === $comments ) {
			$comments = false;

		// A true cache miss
		} else if ( empty( $comments ) ) {

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

			// Legacy query - not recommended
			$func_args = func_get_args();
			if ( apply_filters( 'bp_use_legacy_activity_query', false, __METHOD__, $func_args ) ) {
				$sql = apply_filters( 'bp_activity_comments_user_join_filter', $wpdb->prepare( "SELECT a.*, u.user_email, u.user_nicename, u.user_login, u.display_name{$fullname_select} FROM {$bp->activity->table_name} a, {$wpdb->users} u{$fullname_from} WHERE u.ID = a.user_id {$fullname_where} AND a.type = 'activity_comment' {$spam_sql} AND a.item_id = %d AND a.mptt_left > %d AND a.mptt_left < %d ORDER BY a.date_recorded ASC", $top_level_parent_id, $left, $right ), $activity_id, $left, $right, $spam_sql );

				$descendants = $wpdb->get_results( $sql );

			// We use the mptt BETWEEN clause to limit returned
			// descendants to the correct part of the tree.
			} else {
				$sql = $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} a WHERE a.type = 'activity_comment' {$spam_sql} AND a.item_id = %d and a.mptt_left > %d AND a.mptt_left < %d ORDER BY a.date_recorded ASC", $top_level_parent_id, $left, $right );

				$descendant_ids = $wpdb->get_col( $sql );
				$descendants    = self::get_activity_data( $descendant_ids );
				$descendants    = self::append_user_fullnames( $descendants );
			}

			$ref = array();

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

			// Calculate depth for each item
			foreach ( $ref as &$r ) {
				$depth = 1;
				$parent_id = $r->secondary_item_id;
				while ( $parent_id !== $r->item_id ) {
					$depth++;

					// When display_comments=stream, the
					// parent comment may not be part of
					// the returned results, so we manually
					// fetch it
					if ( empty( $ref[ $parent_id ] ) ) {
						$direct_parent = new BP_Activity_Activity( $parent_id );
						if ( isset( $direct_parent->secondary_item_id ) ) {
							$parent_id = $direct_parent->secondary_item_id;
						} else {
							// Something went wrong
							// Short-circuit the
							// depth calculation
							$parent_id = $r->item_id;
						}
					} else {
						$parent_id = $ref[ $parent_id ]->secondary_item_id;
					}
				}
				$r->depth = $depth;
			}

			// If we cache a value of false, it'll count as a cache
			// miss the next time the activity comments are fetched.
			// Storing the string 'none' is a hack workaround to
			// avoid unnecessary queries.
			if ( false === $comments ) {
				$cache_value = 'none';
			} else {
				$cache_value = $comments;
			}

			wp_cache_set( $activity_id, $cache_value, 'bp_activity_comments' );
		}

		return $comments;
	}

	/**
	 * Rebuild nested comment tree under an activity or activity comment.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $parent_id ID of an activty or activity comment.
	 * @param int $left Node boundary start for activity or activity comment.
	 * @return int Right node boundary of activity or activity comment.
	 */
	public static function rebuild_activity_comment_tree( $parent_id, $left = 1 ) {
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

	/**
	 * Get child comments of an activity or activity comment.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $parent_id ID of an activty or activity comment.
	 * @return object Numerically indexed array of child comments.
	 */
	public static function get_child_comments( $parent_id ) {
		global $bp, $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE type = 'activity_comment' AND secondary_item_id = %d", $parent_id ) );
	}

	/**
	 * Get a list of components that have recorded activity associated with them.
	 *
	 * @param bool $skip_last_activity If true, components will not be
	 *        included if the only activity type associated with them is
	 *        'last_activity'. (Since 2.0.0, 'last_activity' is stored in
	 *        the activity table, but these items are not full-fledged
	 *        activity items.) Default: true.
	 * @return array List of component names.
	 */
	public static function get_recorded_components( $skip_last_activity = true ) {
		global $wpdb, $bp;

		if ( $skip_last_activity ) {
			$components = $wpdb->get_col( "SELECT DISTINCT component FROM {$bp->activity->table_name} WHERE action != '' AND action != 'last_activity' ORDER BY component ASC" );
		} else {
			$components = $wpdb->get_col( "SELECT DISTINCT component FROM {$bp->activity->table_name} ORDER BY component ASC" );
		}

		return $components;
	}

	/**
	 * Get sitewide activity items for use in an RSS feed.
	 *
	 * @param int $limit Optional. Number of items to fetch. Default: 35.
	 * @return array $activity_feed List of activity items, with RSS data added.
	 */
	public static function get_sitewide_items_for_feed( $limit = 35 ) {
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

	/**
	 * Create SQL IN clause for filter queries.
	 *
	 * @since BuddyPress (1.5)
	 *
	 * @see BP_Activity_Activity::get_filter_sql()
	 *
	 * @param string $field The database field.
	 * @param array|bool $items The values for the IN clause, or false when none are found.
	 */
	public static function get_in_operator_sql( $field, $items ) {
		global $wpdb;

		// split items at the comma
		if ( ! is_array( $items ) ) {
			$items = explode( ',', $items );
		}

		// array of prepared integers or quoted strings
		$items_prepared = array();

		// clean up and format each item
		foreach ( $items as $item ) {
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

	/**
	 * Create filter SQL clauses.
	 *
	 * @since BuddyPress (1.5.0)
	 *
	 * @param array $filter_array {
	 *     Fields and values to filter by.
	 *     @type array|string|id $user_id User ID(s).
	 *     @type array|string $object Corresponds to the 'component'
	 *           column in the database.
	 *     @type array|string $action Corresponds to the 'type' column
	 *           in the database.
	 *     @type array|string|int $primary_id Corresponds to the 'item_id'
	 *           column in the database.
	 *     @type array|string|int $secondary_id Corresponds to the
	 *           'secondary_item_id' column in the database.
	 *     @type int $offset Return only those items with an ID greater
	 *           than the offset value.
	 *     @type string $since Return only those items that have a
	 *           date_recorded value greater than a given MySQL-formatted
	 *           date.
	 * }
	 * @return string The filter clause, for use in a SQL query.
	 */
	public static function get_filter_sql( $filter_array ) {

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
			if ( ! empty( $action_sql ) )
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

		if ( ! empty( $filter_array['offset'] ) ) {
			$sid_sql = absint( $filter_array['offset'] );
			$filter_sql[] = "a.id >= {$sid_sql}";
		}

		if ( ! empty( $filter_array['since'] ) ) {
			// Validate that this is a proper Y-m-d H:i:s date
			// Trick: parse to UNIX date then translate back
			$translated_date = date( 'Y-m-d H:i:s', strtotime( $filter_array['since'] ) );
			if ( $translated_date === $filter_array['since'] ) {
				$filter_sql[] = "a.date_recorded > '{$translated_date}'";
			}
		}

		if ( empty( $filter_sql ) )
			return false;

		return join( ' AND ', $filter_sql );
	}

	/**
	 * Get the date/time of last recorded activity.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @return string ISO timestamp.
	 */
	public static function get_last_updated() {
		global $bp, $wpdb;

		return $wpdb->get_var( "SELECT date_recorded FROM {$bp->activity->table_name} ORDER BY date_recorded DESC LIMIT 1" );
	}

	/**
	 * Get favorite count for a given user.
	 *
	 * @since BuddyPress (1.2)
	 *
	 * @param int The ID of the user whose favorites you're counting.
	 * @return int A count of the user's favorites.
	 */
	public static function total_favorite_count( $user_id ) {
		if ( !$favorite_activity_entries = bp_get_user_meta( $user_id, 'bp_favorite_activities', true ) )
			return 0;

		return count( maybe_unserialize( $favorite_activity_entries ) );
	}

	/**
	 * Check whether an activity item exists with a given string content.
	 *
	 * @param string $content The content to filter by.
	 * @return int|bool The ID of the first matching item if found, otherwise false.
	 */
	public static function check_exists_by_content( $content ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE content = %s", $content ) );
	}

	/**
	 * Hide all activity for a given user.
	 *
	 * @param int $user_id The ID of the user whose activity you want to mark hidden.
	 * @param int
	 */
	public static function hide_all_for_user( $user_id ) {
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
	 *
	 * @access protected
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
			'per_page'         => $this->max,
			'display_comments' => 'stream'
		) );

	}

	/**
	 * Setup some hooks that are used in the feed.
	 *
	 * Currently, these hooks are used to maintain backwards compatibility with
	 * the RSS feeds previous to BP 1.8.
	 *
	 * @access protected
	 */
	protected function setup_hooks() {
		add_action( 'bp_activity_feed_rss_attributes',   array( $this, 'backpat_rss_attributes' ) );
		add_action( 'bp_activity_feed_channel_elements', array( $this, 'backpat_channel_elements' ) );
		add_action( 'bp_activity_feed_item_elements',    array( $this, 'backpat_item_elements' ) );
	}

	/** BACKPAT HOOKS ********************************************************/

	/**
	 * Fire a hook to ensure backward compatibility for RSS attributes.
	 */
	public function backpat_rss_attributes() {
		do_action( 'bp_activity_' . $this->id . '_feed' );
	}

	/**
	 * Fire a hook to ensure backward compatibility for channel elements.
	 */
	public function backpat_channel_elements() {
		do_action( 'bp_activity_' . $this->id . '_feed_head' );
	}

	/**
	 * Fire a hook to ensure backward compatibility for item elements.
	 */
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
	 *
	 * @access protected
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

	/**
	 * Sets various HTTP headers related to Content-Type and browser caching.
	 *
	 * Most of this class method is derived from {@link WP::send_headers()}.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @access protected
	 */
	protected function http_headers() {
		// set up some additional headers if not on a directory page
		// this is done b/c BP uses pseudo-pages
		if ( ! bp_is_directory() ) {
			global $wp_query;

			$wp_query->is_404 = false;
			status_header( 200 );
		}

		// Set content-type
		@header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );

		// Cache-related variables
		$last_modified      = mysql2date( 'D, d M Y H:i:s O', bp_activity_get_last_updated(), false );
		$modified_timestamp = strtotime( $last_modified );
		$etag               = md5( $last_modified );

		// Set cache-related headers
		@header( 'Last-Modified: ' . $last_modified );
		@header( 'Pragma: no-cache' );
		@header( 'ETag: ' . '"' . $etag . '"' );

		// First commit of BuddyPress! (Easter egg)
		@header( 'Expires: Tue, 25 Mar 2008 17:13:55 GMT');

		// Get ETag from supported user agents
		if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) {
			$client_etag = wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] );

			// Remove quotes from ETag
			$client_etag = trim( $client_etag, '"' );

			// Strip suffixes from ETag if they exist (eg. "-gzip")
			if ( $etag_suffix_pos = strpos( $client_etag, '-' ) ) {
				$client_etag = substr( $client_etag, 0, $etag_suffix_pos );
			}

		// No ETag found
		} else {
			$client_etag = false;
		}

		// Get client last modified timestamp from supported user agents
		$client_last_modified      = empty( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ? '' : trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
		$client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;

		// Set 304 status if feed hasn't been updated since last fetch
		if ( ( $client_last_modified && $client_etag ) ?
				 ( ( $client_modified_timestamp >= $modified_timestamp ) && ( $client_etag == $etag ) ) :
				 ( ( $client_modified_timestamp >= $modified_timestamp ) || ( $client_etag == $etag ) ) ) {
			$status = 304;
		} else {
			$status = false;
		}

		// If feed hasn't changed as reported by the user agent, set 304 status header
		if ( ! empty( $status ) ) {
			status_header( $status );

			// cached response, so stop now!
			if ( $status == 304 ) {
				exit();
			}
		}
	}

	/** OUTPUT ***************************************************************/

	/**
	 * Output the RSS feed.
	 *
	 * @access protected
	 */
	protected function output() {
		$this->http_headers();
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
