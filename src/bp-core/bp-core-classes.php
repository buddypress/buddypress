<?php
/**
 * Core component classes.
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress User Query class.
 *
 * Used for querying users in a BuddyPress context, in situations where WP_User_Query won't do the trick:
 * Member directories, the Friends component, etc.
 *
 * @since BuddyPress (1.7.0)
 *
 * @param array $query {
 *     Query arguments. All items are optional.
 *     @type string            $type            Determines sort order. Select from 'newest', 'active', 'online',
 *                                              'random', 'popular', 'alphabetical'. Default: 'newest'.
 *     @type int               $per_page Number of results to return. Default: 0 (no limit).
 *     @type int               $page            Page offset (together with $per_page). Default: 1.
 *     @type int               $user_id         ID of a user. If present, and if the friends component is activated,
 *                                              results will be limited to the friends of that user. Default: 0.
 *     @type string|bool       $search_terms    Terms to search by. Search happens across xprofile fields. Requires
 *                                              XProfile component. Default: false.
 *     @type string            $search_wildcard When searching with $search_terms, set where wildcards around the term
 *                                              should be positioned. Accepts 'both', 'left', 'right'. Default: 'both'.
 *     @type array|string|bool $include         An array or comma-separated list of user IDs to which query should
 *                                              be limited. Default: false.
 *     @type array|string|bool $exclude         An array or comma-separated list of user IDs that will be excluded from
 *                                              query results. Default: false.
 *     @type array|string|bool $user_ids        An array or comma-separated list of IDs corresponding to the users
 *                                              that should be returned. When this parameter is passed, it will
 *                                              override all others; BP User objects will be constructed using these
 *                                              IDs only. Default: false.
 *     @type array|string      $member_type     Array or comma-separated list of member types to limit results to.
 *     @type string|bool       $meta_key        Limit results to users that have usermeta associated with this meta_key.
 *                                              Usually used with $meta_value. Default: false.
 *     @type string|bool       $meta_value      When used with $meta_key, limits results to users whose usermeta value
 *                                              associated with $meta_key matches $meta_value. Default: false.
 *     @type array             $xprofile_query  Filter results by xprofile data. Requires the xprofile component. See
 *                                              {@see BP_XProfile_Query} for details.
 *     @type bool              $populate_extras True if you want to fetch extra metadata
 *                                              about returned users, such as total group and friend counts.
 *     @type string            $count_total     Determines how BP_User_Query will do a count of total users matching
 *                                              the other filter criteria. Default value is 'count_query', which does
 *                                              a separate SELECT COUNT query to determine the total.
 *                                              'sql_count_found_rows' uses SQL_COUNT_FOUND_ROWS and
 *                                              SELECT FOUND_ROWS(). Pass an empty string to skip the total user
 *                                              count query.
 * }
 */
class BP_User_Query {

	/** Variables *************************************************************/

	/**
	 * Unaltered params as passed to the constructor.
	 *
	 * @since BuddyPress (1.8.0)
	 * @var array
	 */
	public $query_vars_raw = array();

	/**
	 * Array of variables to query with.
	 *
	 * @since BuddyPress (1.7.0)
	 * @var array
	 */
	public $query_vars = array();

	/**
	 * List of found users and their respective data.
	 *
	 * @access public To allow components to manipulate them.
	 * @since BuddyPress (1.7.0)
	 * @var array
	 */
	public $results = array();

	/**
	 * Total number of found users for the current query.
	 *
	 * @access public To allow components to manipulate it.
	 * @since BuddyPress (1.7.0)
	 * @var int
	 */
	public $total_users = 0;

	/**
	 * List of found user IDs.
	 *
	 * @access public To allow components to manipulate it.
	 * @since BuddyPress (1.7.0)
	 * @var array
	 */
	public $user_ids = array();

	/**
	 * SQL clauses for the user ID query.
	 *
	 * @access public To allow components to manipulate it.
	 * @since BuddyPress (1.7.0)
	 * @var array
	 */
	public $uid_clauses = array();

	/**
	 * SQL table where the user ID is being fetched from.
	 *
	 * @since BuddyPress (2.2.0)
	 * @access public
	 * @var string
	 */
	public $uid_table = '';

	/**
	 * SQL database column name to order by.
	 *
	 * @since BuddyPress (1.7.0)
	 * @var string
	 */
	public $uid_name = '';

	/**
	 * Standard response when the query should not return any rows.
	 *
	 * @access protected
	 * @since BuddyPress (1.7.0)
	 * @var string
	 */
	protected $no_results = array( 'join' => '', 'where' => '0 = 1' );


	/** Methods ***************************************************************/

	/**
	 * Constructor.
	 *
	 * @since BuddyPress (1.7.0)
	 *
	 * @param string|array $query See {@link BP_User_Query}.
	 */
	public function __construct( $query = null ) {

		// Store the raw query vars for later access
		$this->query_vars_raw = $query;

		// Allow extending classes to register action/filter hooks
		$this->setup_hooks();

		if ( ! empty( $this->query_vars_raw ) ) {
			$this->query_vars = wp_parse_args( $this->query_vars_raw, array(
				'type'            => 'newest',
				'per_page'        => 0,
				'page'            => 1,
				'user_id'         => 0,
				'search_terms'    => false,
				'search_wildcard' => 'both',
				'include'         => false,
				'exclude'         => false,
				'user_ids'        => false,
				'member_type'     => '',
				'meta_key'        => false,
				'meta_value'      => false,
				'xprofile_query'  => false,
				'populate_extras' => true,
				'count_total'     => 'count_query'
			) );

			// Plugins can use this filter to modify query args
			// before the query is constructed
			do_action_ref_array( 'bp_pre_user_query_construct', array( &$this ) );

			// Get user ids
			// If the user_ids param is present, we skip the query
			if ( false !== $this->query_vars['user_ids'] ) {
				$this->user_ids = wp_parse_id_list( $this->query_vars['user_ids'] );
			} else {
				$this->prepare_user_ids_query();
				$this->do_user_ids_query();
			}
		}

		// Bail if no user IDs were found
		if ( empty( $this->user_ids ) ) {
			return;
		}

		// Fetch additional data. First, using WP_User_Query
		$this->do_wp_user_query();

		// Get BuddyPress specific user data
		$this->populate_extras();
	}

	/**
	 * Allow extending classes to set up action/filter hooks.
	 *
	 * When extending BP_User_Query, you may need to use some of its
	 * internal hooks to modify the output. It's not convenient to call
	 * add_action() or add_filter() in your class constructor, because
	 * BP_User_Query::__construct() contains a fair amount of logic that
	 * you may not want to override in your class. Define this method in
	 * your own class if you need a place where your extending class can
	 * add its hooks early in the query-building process. See
	 * {@link BP_Group_Member_Query::setup_hooks()} for an example.
	 *
	 * @since BuddyPress (1.8.0)
	 */
	public function setup_hooks() {}

	/**
	 * Prepare the query for user_ids.
	 *
	 * @since BuddyPress (1.7.0)
	 */
	public function prepare_user_ids_query() {
		global $wpdb, $bp;

		// Default query variables used here
		$type         = '';
		$per_page     = 0;
		$page         = 1;
		$user_id      = 0;
		$include      = false;
		$search_terms = false;
		$exclude      = false;
		$meta_key     = false;
		$meta_value   = false;

		extract( $this->query_vars );

		// Setup the main SQL query container
		$sql = array(
			'select'  => '',
			'where'   => array(),
			'orderby' => '',
			'order'   => '',
			'limit'   => ''
		);

		/** TYPE **************************************************************/

		// Determines the sort order, which means it also determines where the
		// user IDs are drawn from (the SELECT and WHERE statements)
		switch ( $type ) {

			// 'online' query happens against the last_activity usermeta key
			// Filter 'bp_user_query_online_interval' to modify the
			// number of minutes used as an interval
			case 'online' :
				$this->uid_name = 'user_id';
				$this->uid_table = $bp->members->table_name_last_activity;
				$sql['select']  = "SELECT u.{$this->uid_name} as id FROM {$this->uid_table} u";
				$sql['where'][] = $wpdb->prepare( "u.component = %s AND u.type = 'last_activity'", buddypress()->members->id );
				$sql['where'][] = $wpdb->prepare( "u.date_recorded >= DATE_SUB( UTC_TIMESTAMP(), INTERVAL %d MINUTE )", apply_filters( 'bp_user_query_online_interval', 15 ) );
				$sql['orderby'] = "ORDER BY u.date_recorded";
				$sql['order']   = "DESC";

				break;

			// 'active', 'newest', and 'random' queries
			// all happen against the last_activity usermeta key
			case 'active' :
			case 'newest' :
			case 'random' :
				$this->uid_name = 'user_id';
				$this->uid_table = $bp->members->table_name_last_activity;
				$sql['select']  = "SELECT u.{$this->uid_name} as id FROM {$this->uid_table} u";
				$sql['where'][] = $wpdb->prepare( "u.component = %s AND u.type = 'last_activity'", buddypress()->members->id );

				if ( 'newest' == $type ) {
					$sql['orderby'] = "ORDER BY u.user_id";
					$sql['order'] = "DESC";
				} elseif ( 'random' == $type ) {
					$sql['orderby'] = "ORDER BY rand()";
				} else {
					$sql['orderby'] = "ORDER BY u.date_recorded";
					$sql['order'] = "DESC";
				}

				break;

			// 'popular' sorts by the 'total_friend_count' usermeta
			case 'popular' :
				$this->uid_name = 'user_id';
				$this->uid_table = $wpdb->usermeta;
				$sql['select']  = "SELECT u.{$this->uid_name} as id FROM {$this->uid_table} u";
				$sql['where'][] = $wpdb->prepare( "u.meta_key = %s", bp_get_user_meta_key( 'total_friend_count' ) );
				$sql['orderby'] = "ORDER BY CONVERT(u.meta_value, SIGNED)";
				$sql['order']   = "DESC";

				break;

			// 'alphabetical' sorts depend on the xprofile setup
			case 'alphabetical' :

				// We prefer to do alphabetical sorts against the display_name field
				// of wp_users, because the table is smaller and better indexed. We
				// can do so if xprofile sync is enabled, or if xprofile is inactive.
				//
				// @todo remove need for bp_is_active() check
				if ( ! bp_disable_profile_sync() || ! bp_is_active( 'xprofile' ) ) {
					$this->uid_name = 'ID';
					$this->uid_table = $wpdb->users;
					$sql['select']  = "SELECT u.{$this->uid_name} as id FROM {$this->uid_table} u";
					$sql['orderby'] = "ORDER BY u.display_name";
					$sql['order']   = "ASC";

				// When profile sync is disabled, alphabetical sorts must happen against
				// the xprofile table
				} else {
					$this->uid_name = 'user_id';
					$this->uid_table = $bp->profile->table_name_data;
					$sql['select']  = "SELECT u.{$this->uid_name} as id FROM {$this->uid_table} u";
					$sql['where'][] = $wpdb->prepare( "u.field_id = %d", bp_xprofile_fullname_field_id() );
					$sql['orderby'] = "ORDER BY u.value";
					$sql['order']   = "ASC";
				}

				// Alphabetical queries ignore last_activity, while BP uses last_activity
				// to infer spam/deleted/non-activated users. To ensure that these users
				// are filtered out, we add an appropriate sub-query.
				$sql['where'][] = "u.{$this->uid_name} IN ( SELECT ID FROM {$wpdb->users} WHERE " . bp_core_get_status_sql( '' ) . " )";

				break;

			// Any other 'type' falls through
			default :
				$this->uid_name = 'ID';
				$this->uid_table = $wpdb->users;
				$sql['select']  = "SELECT u.{$this->uid_name} as id FROM {$this->uid_table} u";

				// In this case, we assume that a plugin is
				// handling order, so we leave those clauses
				// blank

				break;
		}

		/** WHERE *************************************************************/

		// 'include' - User ids to include in the results
		$include     = false !== $include ? wp_parse_id_list( $include ) : array();
		$include_ids = $this->get_include_ids( $include );
		if ( ! empty( $include_ids ) ) {
			$include_ids    = implode( ',', wp_parse_id_list( $include_ids ) );
			$sql['where'][] = "u.{$this->uid_name} IN ({$include_ids})";
		}

		// 'exclude' - User ids to exclude from the results
		if ( false !== $exclude ) {
			$exclude_ids    = implode( ',', wp_parse_id_list( $exclude ) );
			$sql['where'][] = "u.{$this->uid_name} NOT IN ({$exclude_ids})";
		}

		// 'user_id' - When a user id is passed, limit to the friends of the user
		// @todo remove need for bp_is_active() check
		if ( ! empty( $user_id ) && bp_is_active( 'friends' ) ) {
			$friend_ids = friends_get_friend_user_ids( $user_id );
			$friend_ids = implode( ',', wp_parse_id_list( $friend_ids ) );

			if ( ! empty( $friend_ids ) ) {
				$sql['where'][] = "u.{$this->uid_name} IN ({$friend_ids})";

			// If the user has no friends, the query should always
			// return no users
			} else {
				$sql['where'][] = $this->no_results['where'];
			}
		}

		/** Search Terms ******************************************************/

		// 'search_terms' searches user_login and user_nicename
		// xprofile field matches happen in bp_xprofile_bp_user_query_search()
		if ( false !== $search_terms ) {
			$search_terms = bp_esc_like( wp_kses_normalize_entities( $search_terms ) );

			if ( $search_wildcard === 'left' ) {
				$search_terms_nospace = '%' . $search_terms;
				$search_terms_space   = '%' . $search_terms . ' %';
			} elseif ( $search_wildcard === 'right' ) {
				$search_terms_nospace =        $search_terms . '%';
				$search_terms_space   = '% ' . $search_terms . '%';
			} else {
				$search_terms_nospace = '%' . $search_terms . '%';
				$search_terms_space   = '%' . $search_terms . '%';
			}

			$sql['where']['search'] = $wpdb->prepare(
				"u.{$this->uid_name} IN ( SELECT ID FROM {$wpdb->users} WHERE ( user_login LIKE %s OR user_login LIKE %s OR user_nicename LIKE %s OR user_nicename LIKE %s ) )",
				$search_terms_nospace,
				$search_terms_space,
				$search_terms_nospace,
				$search_terms_space
			);
		}

		// Member type.
		if ( ! empty( $member_type ) ) {
			$member_types = array();

			if ( ! is_array( $member_type ) ) {
				$member_type = preg_split( '/[,\s+]/', $member_type );
			}

			foreach ( $member_type as $mt ) {
				if ( ! bp_get_member_type_object( $mt ) ) {
					continue;
				}

				$member_types[] = $mt;
			}

			if ( ! empty( $member_types ) ) {
				$member_type_tq = new WP_Tax_Query( array(
					array(
						'taxonomy' => 'bp_member_type',
						'field'    => 'name',
						'operator' => 'IN',
						'terms'    => $member_types,
					),
				) );

				// Switch to the root blog, where member type taxonomies live.
				switch_to_blog( bp_get_root_blog_id() );

				$member_type_sql_clauses = $member_type_tq->get_sql( 'u', $this->uid_name );
				restore_current_blog();

				// Grab the first term_relationships clause and convert to a subquery.
				if ( preg_match( '/' . $wpdb->term_relationships . '\.term_taxonomy_id IN \([0-9, ]+\)/', $member_type_sql_clauses['where'], $matches ) ) {
					$sql['where']['member_type'] = "u.{$this->uid_name} IN ( SELECT object_id FROM $wpdb->term_relationships WHERE {$matches[0]} )";
				} elseif ( false !== strpos( $member_type_sql_clauses['where'], '0 = 1' ) ) {
					$sql['where']['member_type'] = $this->no_results['where'];
				}
			}
		}

		// 'meta_key', 'meta_value' allow usermeta search
		// To avoid global joins, do a separate query
		if ( false !== $meta_key ) {
			$meta_sql = $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s", $meta_key );

			if ( false !== $meta_value ) {
				$meta_sql .= $wpdb->prepare( " AND meta_value = %s", $meta_value );
			}

			$found_user_ids = $wpdb->get_col( $meta_sql );

			if ( ! empty( $found_user_ids ) ) {
				$sql['where'][] = "u.{$this->uid_name} IN (" . implode( ',', wp_parse_id_list( $found_user_ids ) ) . ")";
			} else {
				$sql['where'][] = '1 = 0';
			}
		}

		// 'per_page', 'page' - handles LIMIT
		if ( !empty( $per_page ) && !empty( $page ) ) {
			$sql['limit'] = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );
		} else {
			$sql['limit'] = '';
		}

		// Allow custom filters
		$sql = apply_filters_ref_array( 'bp_user_query_uid_clauses', array( $sql, &$this ) );

		// Assemble the query chunks
		$this->uid_clauses['select']  = $sql['select'];
		$this->uid_clauses['where']   = ! empty( $sql['where'] ) ? 'WHERE ' . implode( ' AND ', $sql['where'] ) : '';
		$this->uid_clauses['orderby'] = $sql['orderby'];
		$this->uid_clauses['order']   = $sql['order'];
		$this->uid_clauses['limit']   = $sql['limit'];

		do_action_ref_array( 'bp_pre_user_query', array( &$this ) );
	}

	/**
	 * Query for IDs of users that match the query parameters.
	 *
	 * Perform a database query to specifically get only user IDs, using
	 * existing query variables set previously in the constructor.
	 *
	 * Also used to quickly perform user total counts.
	 *
	 * @since BuddyPress (1.7.0)
	 */
	public function do_user_ids_query() {
		global $wpdb;

		// If counting using SQL_CALC_FOUND_ROWS, set it up here
		if ( 'sql_calc_found_rows' == $this->query_vars['count_total'] ) {
			$this->uid_clauses['select'] = str_replace( 'SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $this->uid_clauses['select'] );
		}

		// Get the specific user ids
		$this->user_ids = $wpdb->get_col( "{$this->uid_clauses['select']} {$this->uid_clauses['where']} {$this->uid_clauses['orderby']} {$this->uid_clauses['order']} {$this->uid_clauses['limit']}" );

		// Get the total user count
		if ( 'sql_calc_found_rows' == $this->query_vars['count_total'] ) {
			$this->total_users = $wpdb->get_var( apply_filters( 'bp_found_user_query', "SELECT FOUND_ROWS()", $this ) );
		} elseif ( 'count_query' == $this->query_vars['count_total'] ) {
			$count_select      = preg_replace( '/^SELECT.*?FROM (\S+) u/', "SELECT COUNT(u.{$this->uid_name}) FROM $1 u", $this->uid_clauses['select'] );
			$this->total_users = $wpdb->get_var( apply_filters( 'bp_found_user_query', "{$count_select} {$this->uid_clauses['where']}", $this ) );
		}
	}

	/**
	 * Use WP_User_Query() to pull data for the user IDs retrieved in the main query.
	 *
	 * @since BuddyPress (1.7.0)
	 */
	public function do_wp_user_query() {
		$fields = array( 'ID', 'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_activation_key', 'user_status', 'display_name' );

		if ( is_multisite() ) {
			$fields[] = 'spam';
			$fields[] = 'deleted';
		}

		$wp_user_query = new WP_User_Query( apply_filters( 'bp_wp_user_query_args', array(

			// Relevant
			'fields'      => $fields,
			'include'     => $this->user_ids,

			// Overrides
			'blog_id'     => 0,    // BP does not require blog roles
			'count_total' => false // We already have a count

		), $this ) );

		// WP_User_Query doesn't cache the data it pulls from wp_users,
		// and it does not give us a way to save queries by fetching
		// only uncached users. However, BP does cache this data, so
		// we set it here.
		foreach ( $wp_user_query->results as $u ) {
			wp_cache_set( 'bp_core_userdata_' . $u->ID, $u, 'bp' );
		}

		// We calculate total_users using a standalone query, except
		// when a whitelist of user_ids is passed to the constructor.
		// This clause covers the latter situation, and ensures that
		// pagination works when querying by $user_ids.
		if ( empty( $this->total_users ) ) {
			$this->total_users = count( $wp_user_query->results );
		}

		// Reindex for easier matching
		$r = array();
		foreach ( $wp_user_query->results as $u ) {
			$r[ $u->ID ] = $u;
		}

		// Match up to the user ids from the main query
		foreach ( $this->user_ids as $uid ) {
			if ( isset( $r[ $uid ] ) ) {
				$this->results[ $uid ] = $r[ $uid ];

				// The BP template functions expect an 'id'
				// (as opposed to 'ID') property
				$this->results[ $uid ]->id = $uid;
			}
		}
	}

	/**
	 * Fetch the IDs of users to put in the IN clause of the main query.
	 *
	 * By default, returns the value passed to it
	 * ($this->query_vars['include']). Having this abstracted into a
	 * standalone method means that extending classes can override the
	 * logic, parsing together their own user_id limits with the 'include'
	 * ids passed to the class constructor. See {@link BP_Group_Member_Query}
	 * for an example.
	 *
	 * @since BuddyPress (1.8.0)
	 *
	 * @param array Sanitized array of user IDs, as passed to the 'include'
	 *        parameter of the class constructor.
	 * @return array The list of users to which the main query should be
	 *         limited.
	 */
	public function get_include_ids( $include = array() ) {
		return $include;
	}

	/**
	 * Perform a database query to populate any extra metadata we might need.
	 *
	 * Different components will hook into the 'bp_user_query_populate_extras'
	 * action to loop in the things they want.
	 *
	 * @since BuddyPress (1.7.0)
	 *
	 * @global WPDB $wpdb Global WordPress database access object.
	 */
	public function populate_extras() {
		global $wpdb;

		// Bail if no users
		if ( empty( $this->user_ids ) || empty( $this->results ) ) {
			return;
		}

		// Bail if the populate_extras flag is set to false
		// In the case of the 'popular' sort type, we force
		// populate_extras to true, because we need the friend counts
		if ( 'popular' == $this->query_vars['type'] ) {
			$this->query_vars['populate_extras'] = 1;
		}

		if ( ! (bool) $this->query_vars['populate_extras'] ) {
			return;
		}

		// Turn user ID's into a query-usable, comma separated value
		$user_ids_sql = implode( ',', wp_parse_id_list( $this->user_ids ) );

		/**
		 * Use this action to independently populate your own custom extras.
		 *
		 * Note that anything you add here should query using $user_ids_sql, to
		 * avoid running multiple queries per user in the loop.
		 *
		 * Two BuddyPress components currently do this:
		 * - XProfile: To override display names
		 * - Friends:  To set whether or not a user is the current users friend
		 *
		 * @see bp_xprofile_filter_user_query_populate_extras()
		 * @see bp_friends_filter_user_query_populate_extras()
		 */
		do_action_ref_array( 'bp_user_query_populate_extras', array( $this, $user_ids_sql ) );

		// Fetch last_active data from the activity table
		$last_activities = BP_Core_User::get_last_activity( $this->user_ids );

		// Set a last_activity value for each user, even if it's empty
		foreach ( $this->results as $user_id => $user ) {
			$user_last_activity = isset( $last_activities[ $user_id ] ) ? $last_activities[ $user_id ]['date_recorded'] : '';
			$this->results[ $user_id ]->last_activity = $user_last_activity;
		}

		// Fetch usermeta data
		// We want the three following pieces of info from usermeta:
		// - friend count
		// - latest update
		$total_friend_count_key = bp_get_user_meta_key( 'total_friend_count' );
		$bp_latest_update_key   = bp_get_user_meta_key( 'bp_latest_update'   );

		// total_friend_count must be set for each user, even if its
		// value is 0
		foreach ( $this->results as $uindex => $user ) {
			$this->results[$uindex]->total_friend_count = 0;
		}

		// Create, prepare, and run the separate usermeta query
		$user_metas = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key IN (%s,%s) AND user_id IN ({$user_ids_sql})", $total_friend_count_key, $bp_latest_update_key ) );

		// The $members_template global expects the index key to be different
		// from the meta_key in some cases, so we rejig things here.
		foreach ( $user_metas as $user_meta ) {
			switch ( $user_meta->meta_key ) {
				case $total_friend_count_key :
					$key = 'total_friend_count';
					break;

				case $bp_latest_update_key :
					$key = 'latest_update';
					break;
			}

			if ( isset( $this->results[ $user_meta->user_id ] ) ) {
				$this->results[ $user_meta->user_id ]->{$key} = $user_meta->meta_value;
			}
		}

		// When meta_key or meta_value have been passed to the query,
		// fetch the resulting values for use in the template functions
		if ( ! empty( $this->query_vars['meta_key'] ) ) {
			$meta_sql = array(
				'select' => "SELECT user_id, meta_key, meta_value",
				'from'   => "FROM $wpdb->usermeta",
				'where'  => $wpdb->prepare( "WHERE meta_key = %s", $this->query_vars['meta_key'] )
			);

			if ( false !== $this->query_vars['meta_value'] ) {
				$meta_sql['where'] .= $wpdb->prepare( " AND meta_value = %s", $this->query_vars['meta_value'] );
			}

			$metas = $wpdb->get_results( "{$meta_sql['select']} {$meta_sql['from']} {$meta_sql['where']}" );

			if ( ! empty( $metas ) ) {
				foreach ( $metas as $meta ) {
					if ( isset( $this->results[ $meta->user_id ] ) ) {
						$this->results[ $meta->user_id ]->meta_key = $meta->meta_key;

						if ( ! empty( $meta->meta_value ) ) {
							$this->results[ $meta->user_id ]->meta_value = $meta->meta_value;
						}
					}
				}
			}
		}
	}
}

/**
 * Fetch data about a BuddyPress user.
 *
 * BP_Core_User class can be used by any component. It will fetch useful
 * details for any user when provided with a user_id.
 *
 * Example:
 *    $user = new BP_Core_User( $user_id );
 *    $user_avatar = $user->avatar;
 *	  $user_email = $user->email;
 *    $user_status = $user->status;
 *    etc.
 */
class BP_Core_User {

	/**
	 * ID of the user which the object relates to.
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The URL to the full size of the avatar for the user.
	 *
	 * @var string
	 */
	public $avatar;

	/**
	 * The URL to the thumb size of the avatar for the user.
	 *
	 * @var string
	 */
	public $avatar_thumb;

	/**
	 * The URL to the mini size of the avatar for the user.
	 *
	 * @var string
	 */
	public $avatar_mini;

	/**
	 * The full name of the user
	 *
	 * @var string
	 */
	public $fullname;

	/**
	 * The email for the user.
	 *
	 * @var string
	 */
	public $email;

	/**
	 * The absolute url for the user's profile.
	 *
	 * @var string
	 */
	public $user_url;

	/**
	 * The HTML for the user link, with the link text being the user's full name.
	 *
	 * @var string
	 */
	public $user_link;

	/**
	 * Contains a formatted string when the last time the user was active.
	 *
	 * Example: "active 2 hours and 50 minutes ago"
	 *
	 * @var string
	 */
	public $last_active;

	/* Extras */

	/**
	 * The total number of "Friends" the user has on site.
	 *
	 * @var integer
	 */
	public $total_friends;

	/**
	 * The total number of blog posts posted by the user
	 *
	 * @var integer
	 * @deprecated No longer used
	 */
	public $total_blogs;

	/**
	 * The total number of groups the user is a part of.
	 *
	 * Example: "1 group", "2 groups"
	 *
	 * @var string
	 */
	public $total_groups;

	/**
	 * Profile information for the specific user.
	 *
	 * @since BuddyPress (1.2.0)
	 * @var array
	 */
	public $profile_data;

	/** Public Methods *******************************************************/

	/**
	 * Class constructor.
	 *
	 * @param integer $user_id The ID for the user being queried.
	 * @param bool $populate_extras Whether to fetch extra information
	 *        such as group/friendship counts or not. Default: false.
	 */
	public function __construct( $user_id, $populate_extras = false ) {
		if ( !empty( $user_id ) ) {
			$this->id = $user_id;
			$this->populate();

			if ( !empty( $populate_extras ) ) {
				$this->populate_extras();
			}
		}
	}

	/**
	 * Populate the instantiated class with data based on the User ID provided.
	 *
	 * @uses bp_core_get_userurl() Returns the URL with no HTML markup for
	 *       a user based on their user id.
	 * @uses bp_core_get_userlink() Returns a HTML formatted link for a
	 *       user with the user's full name as the link text.
	 * @uses bp_core_get_user_email() Returns the email address for the
	 *       user based on user ID.
	 * @uses bp_get_user_meta() BP function returns the value of passed
	 *       usermeta name from usermeta table.
	 * @uses bp_core_fetch_avatar() Returns HTML formatted avatar for a user
	 * @uses bp_profile_last_updated_date() Returns the last updated date
	 *       for a user.
	 */
	public function populate() {

		if ( bp_is_active( 'xprofile' ) )
			$this->profile_data = $this->get_profile_data();

		if ( !empty( $this->profile_data ) ) {
			$full_name_field_name = bp_xprofile_fullname_field_name();

			$this->user_url  = bp_core_get_user_domain( $this->id, $this->profile_data['user_nicename'], $this->profile_data['user_login'] );
			$this->fullname  = esc_attr( $this->profile_data[$full_name_field_name]['field_data'] );
			$this->user_link = "<a href='{$this->user_url}' title='{$this->fullname}'>{$this->fullname}</a>";
			$this->email     = esc_attr( $this->profile_data['user_email'] );
		} else {
			$this->user_url  = bp_core_get_user_domain( $this->id );
			$this->user_link = bp_core_get_userlink( $this->id );
			$this->fullname  = esc_attr( bp_core_get_user_displayname( $this->id ) );
			$this->email     = esc_attr( bp_core_get_user_email( $this->id ) );
		}

		// Cache a few things that are fetched often
		wp_cache_set( 'bp_user_fullname_' . $this->id, $this->fullname, 'bp' );
		wp_cache_set( 'bp_user_email_' . $this->id, $this->email, 'bp' );
		wp_cache_set( 'bp_user_url_' . $this->id, $this->user_url, 'bp' );

		$this->avatar       = bp_core_fetch_avatar( array( 'item_id' => $this->id, 'type' => 'full', 'alt' => sprintf( __( 'Profile photo of %s', 'buddypress' ), $this->fullname ) ) );
		$this->avatar_thumb = bp_core_fetch_avatar( array( 'item_id' => $this->id, 'type' => 'thumb', 'alt' => sprintf( __( 'Profile photo of %s', 'buddypress' ), $this->fullname ) ) );
		$this->avatar_mini  = bp_core_fetch_avatar( array( 'item_id' => $this->id, 'type' => 'thumb', 'alt' => sprintf( __( 'Profile photo of %s', 'buddypress' ), $this->fullname ), 'width' => 30, 'height' => 30 ) );
		$this->last_active  = bp_core_get_last_activity( bp_get_user_last_activity( $this->id ), __( 'active %s', 'buddypress' ) );
	}

	/**
	 * Populates extra fields such as group and friendship counts.
	 */
	public function populate_extras() {

		if ( bp_is_active( 'friends' ) ) {
			$this->total_friends = BP_Friends_Friendship::total_friend_count( $this->id );
		}

		if ( bp_is_active( 'groups' ) ) {
			$this->total_groups = BP_Groups_Member::total_group_count( $this->id );
			$this->total_groups = sprintf( _n( '%d group', '%d groups', $this->total_groups, 'buddypress' ), $this->total_groups );
		}
	}

	/**
	 * Fetch xprofile data for the current user.
	 *
	 * @see BP_XProfile_ProfileData::get_all_for_user() for description of
	 *      return value.
	 *
	 * @return array See {@link BP_XProfile_Profile_Data::get_all_for_user()}.
	 */
	public function get_profile_data() {
		return BP_XProfile_ProfileData::get_all_for_user( $this->id );
	}

	/** Static Methods ********************************************************/

	/**
	 * Get a list of users that match the query parameters.
	 *
	 * Since BuddyPress 1.7, use {@link BP_User_Query} instead.
	 *
	 * @deprecated 1.7.0 Use {@link BP_User_Query}.
	 *
	 * @see BP_User_Query for a description of parameters, most of which
	 *      are used there in the same way.
	 *
	 * @param string $type See {@link BP_User_Query}.
	 * @param int $limit See {@link BP_User_Query}. Default: 0.
	 * @param int $page See {@link BP_User_Query}. Default: 1.
	 * @param int $user_id See {@link BP_User_Query}. Default: 0.
	 * @param mixed $include See {@link BP_User_Query}. Default: false.
	 * @param string|bool $search_terms See {@link BP_User_Query}.
	 *        Default: false.
	 * @param bool $populate_extras See {@link BP_User_Query}.
	 *        Default: true.
	 * @param mixed $exclude See {@link BP_User_Query}. Default: false.
	 * @param string|bool $meta_key See {@link BP_User_Query}.
	 *        Default: false.
	 * @param string|bool $meta_value See {@link BP_User_Query}.
	 *        Default: false.
	 * @return array {
	 *     @type int $total_users Total number of users matched by query
	 *           params.
	 *     @type array $paged_users The current page of users matched by
	 *           query params.
	 * }
	 */
	public static function get_users( $type, $limit = 0, $page = 1, $user_id = 0, $include = false, $search_terms = false, $populate_extras = true, $exclude = false, $meta_key = false, $meta_value = false ) {
		global $wpdb, $bp;

		_deprecated_function( __METHOD__, '1.7', 'BP_User_Query' );

		$sql = array();

		$sql['select_main'] = "SELECT DISTINCT u.ID as id, u.user_registered, u.user_nicename, u.user_login, u.display_name, u.user_email";

		if ( 'active' == $type || 'online' == $type || 'newest' == $type  ) {
			$sql['select_active'] = ", um.meta_value as last_activity";
		}

		if ( 'popular' == $type ) {
			$sql['select_popular'] = ", um.meta_value as total_friend_count";
		}

		if ( 'alphabetical' == $type ) {
			$sql['select_alpha'] = ", pd.value as fullname";
		}

		if ( $meta_key ) {
			$sql['select_meta'] = ", umm.meta_key";

			if ( $meta_value ) {
				$sql['select_meta'] .= ", umm.meta_value";
			}
		}

		$sql['from'] = "FROM {$wpdb->users} u LEFT JOIN {$wpdb->usermeta} um ON um.user_id = u.ID";

		// We search against xprofile fields, so we must join the table
		if ( $search_terms && bp_is_active( 'xprofile' ) ) {
			$sql['join_profiledata_search'] = "LEFT JOIN {$bp->profile->table_name_data} spd ON u.ID = spd.user_id";
		}

		// Alphabetical sorting is done by the xprofile Full Name field
		if ( 'alphabetical' == $type ) {
			$sql['join_profiledata_alpha'] = "LEFT JOIN {$bp->profile->table_name_data} pd ON u.ID = pd.user_id";
		}

		if ( $meta_key ) {
			$sql['join_meta'] = "LEFT JOIN {$wpdb->usermeta} umm ON umm.user_id = u.ID";
		}

		$sql['where'] = 'WHERE ' . bp_core_get_status_sql( 'u.' );

		if ( 'active' == $type || 'online' == $type || 'newest' == $type ) {
			$sql['where_active'] = $wpdb->prepare( "AND um.meta_key = %s", bp_get_user_meta_key( 'last_activity' ) );
		}

		if ( 'popular' == $type ) {
			$sql['where_popular'] = $wpdb->prepare( "AND um.meta_key = %s", bp_get_user_meta_key( 'total_friend_count' ) );
		}

		if ( 'online' == $type ) {
			$sql['where_online'] = "AND DATE_ADD( um.meta_value, INTERVAL 5 MINUTE ) >= UTC_TIMESTAMP()";
		}

		if ( 'alphabetical' == $type ) {
			$sql['where_alpha'] = "AND pd.field_id = 1";
		}

		if ( !empty( $exclude ) ) {
			$exclude              = implode( ',', wp_parse_id_list( $exclude ) );
			$sql['where_exclude'] = "AND u.ID NOT IN ({$exclude})";
		}

		// Passing an $include value of 0 or '0' will necessarily result in an empty set
		// returned. The default value of false will hit the 'else' clause.
		if ( 0 === $include || '0' === $include ) {
			$sql['where_users'] = "AND 0 = 1";
		} else {
			if ( !empty( $include ) ) {
				$include = implode( ',',  wp_parse_id_list( $include ) );
				$sql['where_users'] = "AND u.ID IN ({$include})";
			} elseif ( !empty( $user_id ) && bp_is_active( 'friends' ) ) {
				$friend_ids = friends_get_friend_user_ids( $user_id );

				if ( !empty( $friend_ids ) ) {
					$friend_ids = implode( ',', wp_parse_id_list( $friend_ids ) );
					$sql['where_friends'] = "AND u.ID IN ({$friend_ids})";

				// User has no friends, return false since there will be no users to fetch.
				} else {
					return false;
				}
			}
		}

		if ( !empty( $search_terms ) && bp_is_active( 'xprofile' ) ) {
			$search_terms_like        = '%' . bp_esc_like( $search_terms ) . '%';
			$sql['where_searchterms'] = $wpdb->prepare( "AND spd.value LIKE %s", $search_terms_like );
		}

		if ( !empty( $meta_key ) ) {
			$sql['where_meta'] = $wpdb->prepare( " AND umm.meta_key = %s", $meta_key );

			// If a meta value is provided, match it
			if ( $meta_value ) {
				$sql['where_meta'] .= $wpdb->prepare( " AND umm.meta_value = %s", $meta_value );
			}
		}

		switch ( $type ) {
			case 'active': case 'online': default:
				$sql[] = "ORDER BY um.meta_value DESC";
				break;
			case 'newest':
				$sql[] = "ORDER BY u.ID DESC";
				break;
			case 'alphabetical':
				$sql[] = "ORDER BY pd.value ASC";
				break;
			case 'random':
				$sql[] = "ORDER BY rand()";
				break;
			case 'popular':
				$sql[] = "ORDER BY CONVERT(um.meta_value, SIGNED) DESC";
				break;
		}

		if ( !empty( $limit ) && !empty( $page ) ) {
			$sql['pagination'] = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		// Get paginated results
		$paged_users_sql = apply_filters( 'bp_core_get_paged_users_sql', join( ' ', (array) $sql ), $sql );
		$paged_users     = $wpdb->get_results( $paged_users_sql );

		// Re-jig the SQL so we can get the total user count
		unset( $sql['select_main'] );

		if ( !empty( $sql['select_active'] ) ) {
			unset( $sql['select_active'] );
		}

		if ( !empty( $sql['select_popular'] ) ) {
			unset( $sql['select_popular'] );
		}

		if ( !empty( $sql['select_alpha'] ) ) {
			unset( $sql['select_alpha'] );
		}

		if ( !empty( $sql['pagination'] ) ) {
			unset( $sql['pagination'] );
		}

		array_unshift( $sql, "SELECT COUNT(u.ID)" );

		// Get total user results
		$total_users_sql = apply_filters( 'bp_core_get_total_users_sql', join( ' ', (array) $sql ), $sql );
		$total_users     = $wpdb->get_var( $total_users_sql );

		/***
		 * Lets fetch some other useful data in a separate queries, this will be faster than querying the data for every user in a list.
		 * We can't add these to the main query above since only users who have this information will be returned (since the much of the data is in usermeta and won't support any type of directional join)
		 */
		if ( !empty( $populate_extras ) ) {
			$user_ids = array();

			foreach ( (array) $paged_users as $user ) {
				$user_ids[] = $user->id;
			}

			// Add additional data to the returned results
			$paged_users = BP_Core_User::get_user_extras( $paged_users, $user_ids, $type );
		}

		return array( 'users' => $paged_users, 'total' => $total_users );
	}


	/**
	 * Fetch the details for all users whose usernames start with the given letter.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param string $letter The letter the users names are to start with.
	 * @param int $limit The number of users we wish to retrive.
	 * @param int $page The page number we are currently on, used in
	 *        conjunction with $limit to get the start position for the
	 *        limit.
	 * @param bool $populate_extras Populate extra user fields?
	 * @param string $exclude Comma-separated IDs of users whose results
	 *        aren't to be fetched.
	 * @return mixed False on error, otherwise associative array of results.
	 */
	public static function get_users_by_letter( $letter, $limit = null, $page = 1, $populate_extras = true, $exclude = '' ) {
		global $bp, $wpdb;

		$pag_sql = '';
		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		// Multibyte compliance
		if ( function_exists( 'mb_strlen' ) ) {
			if ( mb_strlen( $letter, 'UTF-8' ) > 1 || is_numeric( $letter ) || !$letter ) {
				return false;
			}
		} else {
			if ( strlen( $letter ) > 1 || is_numeric( $letter ) || !$letter ) {
				return false;
			}
		}

		$letter_like = bp_esc_like( $letter ) . '%';
		$status_sql  = bp_core_get_status_sql( 'u.' );

		if ( !empty( $exclude ) ) {
			$exclude     = implode( ',', wp_parse_id_list( $exclude ) );
			$exclude_sql = " AND u.id NOT IN ({$exclude})";
		} else {
			$exclude_sql = '';
		}

		$total_users_sql = apply_filters( 'bp_core_users_by_letter_count_sql', $wpdb->prepare( "SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u LEFT JOIN {$bp->profile->table_name_data} pd ON u.ID = pd.user_id LEFT JOIN {$bp->profile->table_name_fields} pf ON pd.field_id = pf.id WHERE {$status_sql} AND pf.name = %s {$exclude_sql} AND pd.value LIKE %s ORDER BY pd.value ASC", bp_xprofile_fullname_field_name(), $letter_like ) );
		$paged_users_sql = apply_filters( 'bp_core_users_by_letter_sql',       $wpdb->prepare( "SELECT DISTINCT u.ID as id, u.user_registered, u.user_nicename, u.user_login, u.user_email FROM {$wpdb->users} u LEFT JOIN {$bp->profile->table_name_data} pd ON u.ID = pd.user_id LEFT JOIN {$bp->profile->table_name_fields} pf ON pd.field_id = pf.id WHERE {$status_sql} AND pf.name = %s {$exclude_sql} AND pd.value LIKE %s ORDER BY pd.value ASC{$pag_sql}", bp_xprofile_fullname_field_name(), $letter_like ) );

		$total_users = $wpdb->get_var( $total_users_sql );
		$paged_users = $wpdb->get_results( $paged_users_sql );

		/***
		 * Lets fetch some other useful data in a separate queries, this will be
		 * faster than querying the data for every user in a list. We can't add
		 * these to the main query above since only users who have this
		 * information will be returned (since the much of the data is in
		 * usermeta and won't support any type of directional join)
		 */
		$user_ids = array();
		foreach ( (array) $paged_users as $user )
			$user_ids[] = (int) $user->id;

		// Add additional data to the returned results
		if ( $populate_extras ) {
			$paged_users = BP_Core_User::get_user_extras( $paged_users, $user_ids );
		}

		return array( 'users' => $paged_users, 'total' => $total_users );
	}

	/**
	 * Get details of specific users from the database.
	 *
	 * Use {@link BP_User_Query} with the 'user_ids' param instead.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 * @param array $user_ids The user IDs of the users who we wish to
	 *        fetch information on.
	 * @param int $limit The limit of results we want.
	 * @param int $page The page we are on for pagination.
	 * @param bool $populate_extras Populate extra user fields?
	 * @return array Associative array.
	 */
	public static function get_specific_users( $user_ids, $limit = null, $page = 1, $populate_extras = true ) {
		global $wpdb;

		$pag_sql = '';
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		$user_ids   = implode( ',', wp_parse_id_list( $user_ids ) );
		$status_sql = bp_core_get_status_sql();

		$total_users_sql = apply_filters( 'bp_core_get_specific_users_count_sql', "SELECT COUNT(ID) FROM {$wpdb->users} WHERE {$status_sql} AND ID IN ({$user_ids})" );
		$paged_users_sql = apply_filters( 'bp_core_get_specific_users_count_sql', "SELECT ID as id, user_registered, user_nicename, user_login, user_email FROM {$wpdb->users} WHERE {$status_sql} AND ID IN ({$user_ids}) {$pag_sql}" );

		$total_users = $wpdb->get_var( $total_users_sql );
		$paged_users = $wpdb->get_results( $paged_users_sql );

		/***
		 * Lets fetch some other useful data in a separate queries, this will be
		 * faster than querying the data for every user in a list. We can't add
		 * these to the main query above since only users who have this
		 * information will be returned (since the much of the data is in
		 * usermeta and won't support any type of directional join)
		 */

		// Add additional data to the returned results
		if ( !empty( $populate_extras ) ) {
			$paged_users = BP_Core_User::get_user_extras( $paged_users, $user_ids );
		}

		return array( 'users' => $paged_users, 'total' => $total_users );
	}

	/**
	 * Find users who match on the value of an xprofile data.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param string $search_terms The terms to search the profile table
	 *        value column for.
	 * @param integer $limit The limit of results we want.
	 * @param integer $page The page we are on for pagination.
	 * @param boolean $populate_extras Populate extra user fields?
	 * @return array Associative array.
	 */
	public static function search_users( $search_terms, $limit = null, $page = 1, $populate_extras = true ) {
		global $bp, $wpdb;

		$user_ids = array();
		$pag_sql  = $limit && $page ? $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * intval( $limit ) ), intval( $limit ) ) : '';

		$search_terms_like = '%' . bp_esc_like( $search_terms ) . '%';
		$status_sql        = bp_core_get_status_sql( 'u.' );

		$total_users_sql = apply_filters( 'bp_core_search_users_count_sql', $wpdb->prepare( "SELECT COUNT(DISTINCT u.ID) as id FROM {$wpdb->users} u LEFT JOIN {$bp->profile->table_name_data} pd ON u.ID = pd.user_id WHERE {$status_sql} AND pd.value LIKE %s ORDER BY pd.value ASC", $search_terms_like ), $search_terms );
		$paged_users_sql = apply_filters( 'bp_core_search_users_sql',       $wpdb->prepare( "SELECT DISTINCT u.ID as id, u.user_registered, u.user_nicename, u.user_login, u.user_email FROM {$wpdb->users} u LEFT JOIN {$bp->profile->table_name_data} pd ON u.ID = pd.user_id WHERE {$status_sql} AND pd.value LIKE %s ORDER BY pd.value ASC{$pag_sql}", $search_terms_like ), $search_terms, $pag_sql );

		$total_users = $wpdb->get_var( $total_users_sql );
		$paged_users = $wpdb->get_results( $paged_users_sql );

		/***
		 * Lets fetch some other useful data in a separate queries, this will be faster than querying the data for every user in a list.
		 * We can't add these to the main query above since only users who have this information will be returned (since the much of the data is in usermeta and won't support any type of directional join)
		 */
		foreach ( (array) $paged_users as $user )
			$user_ids[] = $user->id;

		// Add additional data to the returned results
		if ( $populate_extras )
			$paged_users = BP_Core_User::get_user_extras( $paged_users, $user_ids );

		return array( 'users' => $paged_users, 'total' => $total_users );
	}

	/**
	 * Fetch extra user information, such as friend count and last profile update message.
	 *
	 * Accepts multiple user IDs to fetch data for.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param array $paged_users An array of stdClass containing the users.
	 * @param string $user_ids The user ids to select information about.
	 * @param string $type The type of fields we wish to get.
	 * @return mixed False on error, otherwise associative array of results.
	 */
	public static function get_user_extras( &$paged_users, &$user_ids, $type = false ) {
		global $bp, $wpdb;

		if ( empty( $user_ids ) )
			return $paged_users;

		// Sanitize user IDs
		$user_ids = implode( ',', wp_parse_id_list( $user_ids ) );

		// Fetch the user's full name
		if ( bp_is_active( 'xprofile' ) && 'alphabetical' != $type ) {
			$names = $wpdb->get_results( $wpdb->prepare( "SELECT pd.user_id as id, pd.value as fullname FROM {$bp->profile->table_name_fields} pf, {$bp->profile->table_name_data} pd WHERE pf.id = pd.field_id AND pf.name = %s AND pd.user_id IN ( {$user_ids} )", bp_xprofile_fullname_field_name() ) );
			for ( $i = 0, $count = count( $paged_users ); $i < $count; ++$i ) {
				foreach ( (array) $names as $name ) {
					if ( $name->id == $paged_users[$i]->id )
						$paged_users[$i]->fullname = $name->fullname;
				}
			}
		}

		// Fetch the user's total friend count
		if ( 'popular' != $type ) {
			$friend_count = $wpdb->get_results( $wpdb->prepare( "SELECT user_id as id, meta_value as total_friend_count FROM {$wpdb->usermeta} WHERE meta_key = %s AND user_id IN ( {$user_ids} )", bp_get_user_meta_key( 'total_friend_count' ) ) );
			for ( $i = 0, $count = count( $paged_users ); $i < $count; ++$i ) {
				foreach ( (array) $friend_count as $fcount ) {
					if ( $fcount->id == $paged_users[$i]->id )
						$paged_users[$i]->total_friend_count = (int) $fcount->total_friend_count;
				}
			}
		}

		// Fetch whether or not the user is a friend
		if ( bp_is_active( 'friends' ) ) {
			$friend_status = $wpdb->get_results( $wpdb->prepare( "SELECT initiator_user_id, friend_user_id, is_confirmed FROM {$bp->friends->table_name} WHERE (initiator_user_id = %d AND friend_user_id IN ( {$user_ids} ) ) OR (initiator_user_id IN ( {$user_ids} ) AND friend_user_id = %d )", bp_loggedin_user_id(), bp_loggedin_user_id() ) );
			for ( $i = 0, $count = count( $paged_users ); $i < $count; ++$i ) {
				foreach ( (array) $friend_status as $status ) {
					if ( $status->initiator_user_id == $paged_users[$i]->id || $status->friend_user_id == $paged_users[$i]->id )
						$paged_users[$i]->is_friend = $status->is_confirmed;
				}
			}
		}

		if ( 'active' != $type ) {
			$user_activity = $wpdb->get_results( $wpdb->prepare( "SELECT user_id as id, meta_value as last_activity FROM {$wpdb->usermeta} WHERE meta_key = %s AND user_id IN ( {$user_ids} )", bp_get_user_meta_key( 'last_activity' ) ) );
			for ( $i = 0, $count = count( $paged_users ); $i < $count; ++$i ) {
				foreach ( (array) $user_activity as $activity ) {
					if ( $activity->id == $paged_users[$i]->id )
						$paged_users[$i]->last_activity = $activity->last_activity;
				}
			}
		}

		// Fetch the user's last_activity
		if ( 'active' != $type ) {
			$user_activity = $wpdb->get_results( $wpdb->prepare( "SELECT user_id as id, meta_value as last_activity FROM {$wpdb->usermeta} WHERE meta_key = %s AND user_id IN ( {$user_ids} )", bp_get_user_meta_key( 'last_activity' ) ) );
			for ( $i = 0, $count = count( $paged_users ); $i < $count; ++$i ) {
				foreach ( (array) $user_activity as $activity ) {
					if ( $activity->id == $paged_users[$i]->id )
						$paged_users[$i]->last_activity = $activity->last_activity;
				}
			}
		}

		// Fetch the user's latest update
		$user_update = $wpdb->get_results( $wpdb->prepare( "SELECT user_id as id, meta_value as latest_update FROM {$wpdb->usermeta} WHERE meta_key = %s AND user_id IN ( {$user_ids} )", bp_get_user_meta_key( 'bp_latest_update' ) ) );
		for ( $i = 0, $count = count( $paged_users ); $i < $count; ++$i ) {
			foreach ( (array) $user_update as $update ) {
				if ( $update->id == $paged_users[$i]->id )
					$paged_users[$i]->latest_update = $update->latest_update;
			}
		}

		return $paged_users;
	}

	/**
	 * Get WordPress user details for a specified user.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param integer $user_id User ID.
	 * @return array Associative array.
	 */
	public static function get_core_userdata( $user_id ) {
		global $wpdb;

		if ( !$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->users} WHERE ID = %d LIMIT 1", $user_id ) ) )
			return false;

		return $user;
	}

	/**
	 * Get last activity data for a user or set of users.
	 *
	 * @param int|array User IDs or multiple user IDs.
	 * @return array
	 */
	public static function get_last_activity( $user_id ) {
		global $wpdb;

		// Sanitize and remove empty values
		$user_ids = array_filter( wp_parse_id_list( $user_id ) );

		if ( empty( $user_ids ) ) {
			return false;
		}

		$uncached_user_ids = bp_get_non_cached_ids( $user_ids, 'bp_last_activity' );
		if ( ! empty( $uncached_user_ids ) ) {
			$bp = buddypress();

			$user_ids_sql = implode( ',', $uncached_user_ids );
			$user_count   = count( $uncached_user_ids );

			$last_activities = $wpdb->get_results( $wpdb->prepare( "SELECT id, user_id, date_recorded FROM {$bp->members->table_name_last_activity} WHERE component = %s AND type = 'last_activity' AND user_id IN ({$user_ids_sql}) LIMIT {$user_count}", $bp->members->id ) );

			foreach ( $last_activities as $last_activity ) {
				wp_cache_set( $last_activity->user_id, array(
					'user_id'       => $last_activity->user_id,
					'date_recorded' => $last_activity->date_recorded,
					'activity_id'   => $last_activity->id,
				), 'bp_last_activity' );
			}
		}

		// Fetch all user data from the cache
		$retval = array();
		foreach ( $user_ids as $user_id ) {
			$retval[ $user_id ] = wp_cache_get( $user_id, 'bp_last_activity' );
		}

		return $retval;
	}

	/**
	 * Set a user's last_activity value.
	 *
	 * Will create a new entry if it does not exist. Otherwise updates the
	 * existing entry.
	 *
	 * @since 2.0
	 *
	 * @param int $user_id ID of the user whose last_activity you are updating.
	 * @param string $time MySQL-formatted time string.
	 * @return bool True on success, false on failure.
	 */
	public static function update_last_activity( $user_id, $time ) {
		global $wpdb;

		$table_name = buddypress()->members->table_name_last_activity;

		$activity = self::get_last_activity( $user_id );

		if ( ! empty( $activity[ $user_id ] ) ) {
			$updated = $wpdb->update(
				$table_name,

				// Data to update
				array(
					'date_recorded' => $time,
				),

				// WHERE
				array(
					'id' => $activity[ $user_id ]['activity_id'],
				),

				// Data sanitization format
				array(
					'%s',
				),

				// WHERE sanitization format
				array(
					'%d',
				)
			);

			// add new date to existing activity entry for caching
			$activity[ $user_id ]['date_recorded'] = $time;

		} else {
			$updated = $wpdb->insert(
				$table_name,

				// Data
				array(
					'user_id'       => $user_id,
					'component'     => buddypress()->members->id,
					'type'          => 'last_activity',
					'action'        => '',
					'content'       => '',
					'primary_link'  => '',
					'item_id'       => 0,
					'date_recorded' => $time,
				),

				// Data sanitization format
				array(
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					'%s',
				)
			);

			// set up activity array for caching
			// view the foreach loop in the get_last_activity() method for format
			$activity = array();
			$activity[ $user_id ] = array(
				'user_id'       => $user_id,
				'date_recorded' => $time,
				'activity_id'   => $wpdb->insert_id,
			);
		}

		// set cache
		wp_cache_set( $user_id, $activity[ $user_id ], 'bp_last_activity' );

		return $updated;
	}

	/**
	 * Delete a user's last_activity value.
	 *
	 * @since 2.0
	 *
	 * @param int $user_id
	 * @return bool True on success, false on failure or if no last_activity
	 *         is found for the user.
	 */
	public static function delete_last_activity( $user_id ) {
		global $wpdb;

		$existing = self::get_last_activity( $user_id );

		if ( empty( $existing ) ) {
			return false;
		}

		$deleted = $wpdb->delete(
			buddypress()->members->table_name_last_activity,

			// WHERE
			array(
				'id' => $existing[ $user_id ]['activity_id'],
			),

			// WHERE sanitization format
			array(
				'%s',
			)
		);

		wp_cache_delete( $user_id, 'bp_last_activity' );

		return $deleted;
	}
}

if ( class_exists( 'WP_Date_Query' ) ) :
/**
 * BuddyPress date query class.
 *
 * Extends the {@link WP_Date_Query} class for use with BuddyPress.
 *
 * @since BuddyPress (2.1.0)
 *
 * @param array $date_query {
 *     Date query arguments.  See first parameter of {@link WP_Date_Query::__construct()}.
 * }
 * @param string $column The DB column to query against.
 */
class BP_Date_Query extends WP_Date_Query {
	/**
	 * The column to query against. Can be changed via the query arguments.
	 *
	 * @var string
	 */
	public $column;

	/**
	 * Constructor.
	 *
	 * @see WP_Date_Query::__construct()
	 */
	public function __construct( $date_query, $column = '' ) {
		if ( ! empty( $column ) ) {
			$this->column = $column;
			add_filter( 'date_query_valid_columns', array( $this, 'register_date_column' ) );
		}

		parent::__construct( $date_query, $column );
	}

	/**
	 * Destructor.
	 */
	public function __destruct() {
		remove_filter( 'date_query_valid_columns', array( $this, 'register_date_column' ) );
	}

	/**
	 * Registers our date column with WP Date Query to pass validation.
	 *
	 * @param array $retval Current DB columns
	 * @return array
	 */
	public function register_date_column( $retval = array() ) {
		$retval[] = $this->column;
		return $retval;
	}
}
endif;

/**
 * BP_Core_Notification is deprecated.
 *
 * Use BP_Notifications_Notification instead.
 *
 * @package BuddyPress Core
 * @deprecated since BuddyPress (1.9)
 */
class BP_Core_Notification {

	/**
	 * The notification id
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The ID to which the notification relates to within the component.
	 *
	 * @var integer
	 */
	public $item_id;

	/**
	 * The secondary ID to which the notification relates to within the component.
	 *
	 * @var integer
	 */
	public $secondary_item_id = null;

	/**
	 * The user ID for who the notification is for.
	 *
	 * @var integer
	 */
	public $user_id;

	/**
	 * The name of the component that the notification is for.
	 *
	 * @var string
	 */
	public $component_name;

	/**
	 * The action within the component which the notification is related to.
	 *
	 * @var string
	 */
	public $component_action;

	/**
	 * The date the notification was created.
	 *
	 * @var string
	 */
	public $date_notified;

	/**
	 * Is the notification new or has it already been read.
	 *
	 * @var boolean
	 */
	public $is_new;

	/** Public Methods ********************************************************/

	/**
	 * Constructor
	 *
	 * @param integer $id
	 */
	public function __construct( $id = 0 ) {
		if ( !empty( $id ) ) {
			$this->id = $id;
			$this->populate();
		}
	}

	/**
	 * Update or insert notification details into the database.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 * @global wpdb $wpdb WordPress database object
	 * @return bool Success or failure
	 */
	public function save() {
		global $bp, $wpdb;

		// Update
		if ( !empty( $this->id ) ) {
			$sql = $wpdb->prepare( "UPDATE {$bp->core->table_name_notifications} SET item_id = %d, secondary_item_id = %d, user_id = %d, component_name = %s, component_action = %d, date_notified = %s, is_new = %d ) WHERE id = %d", $this->item_id, $this->secondary_item_id, $this->user_id, $this->component_name, $this->component_action, $this->date_notified, $this->is_new, $this->id );

		// Save
		} else {
			$sql = $wpdb->prepare( "INSERT INTO {$bp->core->table_name_notifications} ( item_id, secondary_item_id, user_id, component_name, component_action, date_notified, is_new ) VALUES ( %d, %d, %d, %s, %s, %s, %d )", $this->item_id, $this->secondary_item_id, $this->user_id, $this->component_name, $this->component_action, $this->date_notified, $this->is_new );
		}

		if ( !$result = $wpdb->query( $sql ) )
			return false;

		$this->id = $wpdb->insert_id;

		return true;
	}

	/** Private Methods *******************************************************/

	/**
	 * Fetches the notification data from the database.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 * @global wpdb $wpdb WordPress database object
	 */
	public function populate() {
		global $bp, $wpdb;

		if ( $notification = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->core->table_name_notifications} WHERE id = %d", $this->id ) ) ) {
			$this->item_id = $notification->item_id;
			$this->secondary_item_id = $notification->secondary_item_id;
			$this->user_id           = $notification->user_id;
			$this->component_name    = $notification->component_name;
			$this->component_action  = $notification->component_action;
			$this->date_notified     = $notification->date_notified;
			$this->is_new            = $notification->is_new;
		}
	}

	/** Static Methods ********************************************************/

	public static function check_access( $user_id, $notification_id ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->core->table_name_notifications} WHERE id = %d AND user_id = %d", $notification_id, $user_id ) );
	}

	/**
	 * Fetches all the notifications in the database for a specific user.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 * @global wpdb $wpdb WordPress database object
	 * @param integer $user_id User ID
	 * @param string $status 'is_new' or 'all'
	 * @return array Associative array
	 * @static
	 */
	public static function get_all_for_user( $user_id, $status = 'is_new' ) {
		global $bp, $wpdb;

		$is_new = ( 'is_new' == $status ) ? ' AND is_new = 1 ' : '';

 		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->core->table_name_notifications} WHERE user_id = %d {$is_new}", $user_id ) );
	}

	/**
	 * Delete all the notifications for a user based on the component name and action.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 * @global wpdb $wpdb WordPress database object
	 * @param integer $user_id
	 * @param string $component_name
	 * @param string $component_action
	 * @static
	 */
	public static function delete_for_user_by_type( $user_id, $component_name, $component_action ) {
		global $bp, $wpdb;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->core->table_name_notifications} WHERE user_id = %d AND component_name = %s AND component_action = %s", $user_id, $component_name, $component_action ) );
	}

	/**
	 * Delete all the notifications that have a specific item id, component name and action.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 * @global wpdb $wpdb WordPress database object
	 * @param integer $user_id The ID of the user who the notifications are for.
	 * @param integer $item_id The item ID of the notifications we wish to delete.
	 * @param string $component_name The name of the component that the notifications we wish to delete.
	 * @param string $component_action The action of the component that the notifications we wish to delete.
	 * @param integer $secondary_item_id (optional) The secondary item id of the notifications that we wish to use to delete.
	 * @static
	 */
	public static function delete_for_user_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false ) {
		global $bp, $wpdb;

		$secondary_item_sql = !empty( $secondary_item_id ) ? $wpdb->prepare( " AND secondary_item_id = %d", $secondary_item_id ) : '';

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->core->table_name_notifications} WHERE user_id = %d AND item_id = %d AND component_name = %s AND component_action = %s{$secondary_item_sql}", $user_id, $item_id, $component_name, $component_action ) );
	}

	/**
	 * Deletes all the notifications sent by a specific user, by component and action.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 * @global wpdb $wpdb WordPress database object
	 * @param integer $user_id The ID of the user whose sent notifications we wish to delete.
	 * @param string $component_name The name of the component the notification was sent from.
	 * @param string $component_action The action of the component the notification was sent from.
	 * @static
	 */
	public static function delete_from_user_by_type( $user_id, $component_name, $component_action ) {
		global $bp, $wpdb;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->core->table_name_notifications} WHERE item_id = %d AND component_name = %s AND component_action = %s", $user_id, $component_name, $component_action ) );
	}

	/**
	 * Deletes all the notifications for all users by item id, and optional secondary item id, and component name and action.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 * @global wpdb $wpdb WordPress database object
	 * @param string $item_id The item id that they notifications are to be for.
	 * @param string $component_name The component that the notifications are to be from.
	 * @param string $component_action The action that the notifications are to be from.
	 * @param string $secondary_item_id Optional secondary item id that the notifications are to have.
	 * @static
	 */
	public static function delete_all_by_type( $item_id, $component_name, $component_action, $secondary_item_id ) {
		global $bp, $wpdb;

		if ( $component_action )
			$component_action_sql = $wpdb->prepare( "AND component_action = %s", $component_action );
		else
			$component_action_sql = '';

		if ( $secondary_item_id )
			$secondary_item_sql = $wpdb->prepare( "AND secondary_item_id = %d", $secondary_item_id );
		else
			$secondary_item_sql = '';

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->core->table_name_notifications} WHERE item_id = %d AND component_name = %s {$component_action_sql} {$secondary_item_sql}", $item_id, $component_name ) );
	}
}

/**
 * API to create BuddyPress buttons.
 *
 * @since BuddyPress (1.2.6)
 *
 * @param array $args {
 *     Array of arguments.
 *     @type string $id String describing the button type.
 *     @type string $component The name of the component the button belongs to.
 *           Default: 'core'.
 *     @type bool $must_be_logged_in Optional. Does the user need to be logged
 *           in to see this button? Default: true.
 *     @type bool $block_self Optional. True if the button should be hidden
 *           when a user is viewing his own profile. Default: true.
 *     @type string|bool $wrapper Optional. HTML element type that should wrap
 *           the button: 'div', 'span', 'p', or 'li'. False for no wrapper at
 *           all. Default: 'div'.
 *     @type string $wrapper_id Optional. DOM ID of the button wrapper element.
 *           Default: ''.
 *     @type string $wrapper_class Optional. DOM class of the button wrapper
 *           element. Default: ''.
 *     @type string $link_href Optional. Destination link of the button.
 *           Default: ''.
 *     @type string $link_class Optional. DOM class of the button. Default: ''.
 *     @type string $link_id Optional. DOM ID of the button. Default: ''.
 *     @type string $link_rel Optional. DOM 'rel' attribute of the button.
 *           Default: ''.
 *     @type string $link_title Optional. Title attribute of the button.
 *           Default: ''.
 *     @type string $link_text Optional. Text to appear on the button.
 *           Default: ''.
 * }
 */
class BP_Button {

	/** Button properties *****************************************************/

	/**
	 * The button ID.
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * The name of the component that the button belongs to.
	 *
	 * @var string
	 */
	public $component = 'core';

	/**
	 * Does the user need to be logged in to see this button?
	 *
	 * @var bool
	 */
	public $must_be_logged_in = true;

	/**
	 * Whether the button should be hidden when viewing your own profile.
	 *
	 * @var bool
	 */
	public $block_self = true;

	/** Wrapper ***************************************************************/

	/**
	 * The type of DOM element to use for a wrapper.
	 *
	 * @var string|bool 'div', 'span', 'p', 'li', or false for no wrapper.
	 */
	public $wrapper = 'div';

	/**
	 * The DOM class of the button wrapper.
	 *
	 * @var string
	 */
	public $wrapper_class = '';

	/**
	 * The DOM ID of the button wrapper.
	 *
	 * @var string
	 */
	public $wrapper_id = '';

	/** Button ****************************************************************/

	/**
	 * The destination link of the button.
	 *
	 * @var string
	 */
	public $link_href = '';

	/**
	 * The DOM class of the button link.
	 *
	 * @var string
	 */
	public $link_class = '';

	/**
	 * The DOM ID of the button link.
	 *
	 * @var string
	 */
	public $link_id = '';

	/**
	 * The DOM rel value of the button link.
	 *
	 * @var string
	 */
	public $link_rel = '';

	/**
	 * Title of the button link.
	 *
	 * @var string
	 */
	public $link_title = '';

	/**
	 * The contents of the button link.
	 *
	 * @var string
	 */
	public $link_text = '';

	/** HTML result ***********************************************************/

	public $contents = '';

	/** Methods ***************************************************************/

	/**
	 * Builds the button based on class parameters.
	 *
	 * @since BuddyPress (1.2.6)
	 *
	 * @param array $args See {@BP_Button}.
	 * @return bool|null Returns false when the button is not allowed for
	 *         the current context.
	 */
	public function __construct( $args = '' ) {

		$r = wp_parse_args( $args, get_class_vars( __CLASS__ ) );

		// Required button properties
		$this->id                = $r['id'];
		$this->component         = $r['component'];
		$this->must_be_logged_in = (bool) $r['must_be_logged_in'];
		$this->block_self        = (bool) $r['block_self'];
		$this->wrapper           = $r['wrapper'];

		// $id and $component are required
		if ( empty( $r['id'] ) || empty( $r['component'] ) )
			return false;

		// No button if component is not active
		if ( ! bp_is_active( $this->component ) )
			return false;

		// No button for guests if must be logged in
		if ( true == $this->must_be_logged_in && ! is_user_logged_in() )
			return false;

		// block_self
		if ( true == $this->block_self ) {
			// No button if you are the current user in a members loop
			// This condition takes precedence, because members loops
			// can be found on user profiles
			if ( bp_get_member_user_id() ) {
				if ( is_user_logged_in() && bp_loggedin_user_id() == bp_get_member_user_id() ) {
					return false;
				}

			// No button if viewing your own profile (and not in
			// a members loop)
			} elseif ( bp_is_my_profile() ) {
				return false;
			}
		}

		// Wrapper properties
		if ( false !== $this->wrapper ) {

			// Wrapper ID
			if ( !empty( $r['wrapper_id'] ) ) {
				$this->wrapper_id    = ' id="' . $r['wrapper_id'] . '"';
			}

			// Wrapper class
			if ( !empty( $r['wrapper_class'] ) ) {
				$this->wrapper_class = ' class="generic-button ' . $r['wrapper_class'] . '"';
			} else {
				$this->wrapper_class = ' class="generic-button"';
			}

			// Set before and after
			$before = '<' . $r['wrapper'] . $this->wrapper_class . $this->wrapper_id . '>';
			$after  = '</' . $r['wrapper'] . '>';

		// No wrapper
		} else {
			$before = $after = '';
		}

		// Link properties
		if ( !empty( $r['link_id']    ) ) $this->link_id    = ' id="' .    $r['link_id']    . '"';
		if ( !empty( $r['link_href']  ) ) $this->link_href  = ' href="' .  $r['link_href']  . '"';
		if ( !empty( $r['link_title'] ) ) $this->link_title = ' title="' . $r['link_title'] . '"';
		if ( !empty( $r['link_rel']   ) ) $this->link_rel   = ' rel="' .   $r['link_rel']   . '"';
		if ( !empty( $r['link_class'] ) ) $this->link_class = ' class="' . $r['link_class'] . '"';
		if ( !empty( $r['link_text']  ) ) $this->link_text  =              $r['link_text'];

		// Build the button
		$this->contents = $before . '<a'. $this->link_href . $this->link_title . $this->link_id . $this->link_rel . $this->link_class . '>' . $this->link_text . '</a>' . $after;

		// Allow button to be manipulated externally
		$this->contents = apply_filters( 'bp_button_' . $this->component . '_' . $this->id, $this->contents, $this, $before, $after );
	}

	/**
	 * Return the markup for the generated button.
	 *
	 * @since BuddyPress (1.2.6)
	 *
	 * @return string Button markup.
	 */
	public function contents() {
		return $this->contents;
	}

	/**
	 * Output the markup of button.
	 *
	 * @since BuddyPress (1.2.6)
	 */
	public function display() {
		if ( !empty( $this->contents ) )
			echo $this->contents;
	}
}

/**
 * Enable oEmbeds in BuddyPress contexts.
 *
 * Extends WP_Embed class for use with BuddyPress.
 *
 * @since BuddyPress (1.5.0)
 *
 * @see WP_Embed
 */
class BP_Embed extends WP_Embed {

	/**
	 * Constructor
	 *
	 * @global WP_Embed $wp_embed
	 */
	public function __construct() {
		global $wp_embed;

		// Make sure we populate the WP_Embed handlers array.
		// These are providers that use a regex callback on the URL in question.
		// Do not confuse with oEmbed providers, which require an external ping.
		// Used in WP_Embed::shortcode()
		$this->handlers = $wp_embed->handlers;

		if ( bp_use_embed_in_activity() ) {
			add_filter( 'bp_get_activity_content_body', array( &$this, 'autoembed' ), 8 );
			add_filter( 'bp_get_activity_content_body', array( &$this, 'run_shortcode' ), 7 );
		}

		if ( bp_use_embed_in_activity_replies() ) {
			add_filter( 'bp_get_activity_content', array( &$this, 'autoembed' ), 8 );
			add_filter( 'bp_get_activity_content', array( &$this, 'run_shortcode' ), 7 );
		}

		if ( bp_use_embed_in_forum_posts() ) {
			add_filter( 'bp_get_the_topic_post_content', array( &$this, 'autoembed' ), 8 );
			add_filter( 'bp_get_the_topic_post_content', array( &$this, 'run_shortcode' ), 7 );
		}

		if ( bp_use_embed_in_private_messages() ) {
			add_filter( 'bp_get_the_thread_message_content', array( &$this, 'autoembed' ), 8 );
			add_filter( 'bp_get_the_thread_message_content', array( &$this, 'run_shortcode' ), 7 );
		}

		do_action_ref_array( 'bp_core_setup_oembed', array( &$this ) );
	}

	/**
	 * The {@link do_shortcode()} callback function.
	 *
	 * Attempts to convert a URL into embed HTML. Starts by checking the
	 * URL against the regex of the registered embed handlers. Next, checks
	 * the URL against the regex of registered {@link WP_oEmbed} providers
	 * if oEmbed discovery is false. If none of the regex matches and it's
	 * enabled, then the URL will be passed to {@link BP_Embed::parse_oembed()}
	 * for oEmbed parsing.
	 *
	 * @uses wp_parse_args()
	 * @uses wp_embed_defaults()
	 * @uses current_user_can()
	 * @uses _wp_oembed_get_object()
	 * @uses WP_Embed::maybe_make_link()
	 *
	 * @param array $attr Shortcode attributes.
	 * @param string $url The URL attempting to be embeded.
	 * @return string The embed HTML on success, otherwise the original URL.
	 */
	public function shortcode( $attr, $url = '' ) {
		if ( empty( $url ) )
			return '';

		$rawattr = $attr;
		$attr = wp_parse_args( $attr, wp_embed_defaults() );

		// kses converts & into &amp; and we need to undo this
		// See http://core.trac.wordpress.org/ticket/11311
		$url = str_replace( '&amp;', '&', $url );

		// Look for known internal handlers
		ksort( $this->handlers );
		foreach ( $this->handlers as $priority => $handlers ) {
			foreach ( $handlers as $hid => $handler ) {
				if ( preg_match( $handler['regex'], $url, $matches ) && is_callable( $handler['callback'] ) ) {
					if ( false !== $return = call_user_func( $handler['callback'], $matches, $attr, $url, $rawattr ) ) {
						return apply_filters( 'embed_handler_html', $return, $url, $attr );
					}
				}
			}
		}

		// Get object ID
		$id = apply_filters( 'embed_post_id', 0 );

		// Is oEmbed discovery on?
		$attr['discover'] = ( apply_filters( 'bp_embed_oembed_discover', false ) && current_user_can( 'unfiltered_html' ) );

		// Set up a new WP oEmbed object to check URL with registered oEmbed providers
		require_once( ABSPATH . WPINC . '/class-oembed.php' );
		$oembed_obj = _wp_oembed_get_object();

		// If oEmbed discovery is true, skip oEmbed provider check
		$is_oembed_link = false;
		if ( !$attr['discover'] ) {
			foreach ( (array) $oembed_obj->providers as $provider_matchmask => $provider ) {
				$regex = ( $is_regex = $provider[1] ) ? $provider_matchmask : '#' . str_replace( '___wildcard___', '(.+)', preg_quote( str_replace( '*', '___wildcard___', $provider_matchmask ), '#' ) ) . '#i';

				if ( preg_match( $regex, $url ) )
					$is_oembed_link = true;
			}

			// If url doesn't match a WP oEmbed provider, stop parsing
			if ( !$is_oembed_link )
				return $this->maybe_make_link( $url );
		}

		return $this->parse_oembed( $id, $url, $attr, $rawattr );
	}

	/**
	 * Base function so BP components/plugins can parse links to be embedded.
	 *
	 * View an example to add support in {@link bp_activity_embed()}.
	 *
	 * @uses apply_filters() Filters cache.
	 * @uses do_action() To save cache.
	 * @uses wp_oembed_get() Connects to oEmbed provider and returns HTML
	 *       on success.
	 * @uses WP_Embed::maybe_make_link() Process URL for hyperlinking on
	 *       oEmbed failure.
	 *
	 * @param int $id ID to do the caching for.
	 * @param string $url The URL attempting to be embedded.
	 * @param array $attr Shortcode attributes from {@link WP_Embed::shortcode()}.
	 * @param array $rawattr Untouched shortcode attributes from
	 *        {@link WP_Embed::shortcode()}.
	 * @return string The embed HTML on success, otherwise the original URL.
	 */
	public function parse_oembed( $id, $url, $attr, $rawattr ) {
		$id = intval( $id );

		if ( $id ) {
			// Setup the cachekey
			$cachekey = '_oembed_' . md5( $url . serialize( $attr ) );

			// Let components / plugins grab their cache
			$cache = '';
			$cache = apply_filters( 'bp_embed_get_cache', $cache, $id, $cachekey, $url, $attr, $rawattr );

			// Grab cache and return it if available
			if ( !empty( $cache ) ) {
				return apply_filters( 'bp_embed_oembed_html', $cache, $url, $attr, $rawattr );

			// If no cache, ping the oEmbed provider and cache the result
			} else {
				$html = wp_oembed_get( $url, $attr );
				$cache = ( $html ) ? $html : $url;

				// Let components / plugins save their cache
				do_action( 'bp_embed_update_cache', $cache, $cachekey, $id );

				// If there was a result, return it
				if ( $html )
					return apply_filters( 'bp_embed_oembed_html', $html, $url, $attr, $rawattr );
			}
		}

		// Still unknown
		return $this->maybe_make_link( $url );
	}
}

/**
 * Create HTML list of BP nav items.
 *
 * @since BuddyPress (1.7.0)
 */
class BP_Walker_Nav_Menu extends Walker_Nav_Menu {

	/**
	 * Description of fields indexes for building markup.
	 *
	 * @since BuddyPress (1.7.0)
	 * @var array
	 */
	var $db_fields = array( 'id' => 'css_id', 'parent' => 'parent' );

	/**
	 * Tree type.
	 *
	 * @since BuddyPress (1.7.0)
	 * @var string
	 */
	var $tree_type = array();

	/**
	 * Display array of elements hierarchically.
	 *
	 * This method is almost identical to the version in {@link Walker::walk()}.
	 * The only change is on one line which has been commented. An IF was
	 * comparing 0 to a non-empty string which was preventing child elements
	 * being grouped under their parent menu element.
	 *
	 * This caused a problem for BuddyPress because our primary/secondary
	 * navigations don't have a unique numerical ID that describes a
	 * hierarchy (we use a slug). Obviously, WordPress Menus use Posts, and
	 * those have ID/post_parent.
	 *
	 * @since BuddyPress (1.7.0)
	 *
	 * @see Walker::walk()
	 *
	 * @param array $elements See {@link Walker::walk()}.
	 * @param int $max_depth See {@link Walker::walk()}.
	 * @return string See {@link Walker::walk()}.
	 */
	public function walk( $elements, $max_depth ) {
		$func_args = func_get_args();

		$args   = array_slice( $func_args, 2 );
		$output = '';

		if ( $max_depth < -1 ) // invalid parameter
			return $output;

		if ( empty( $elements ) ) // nothing to walk
			return $output;

		$parent_field = $this->db_fields['parent'];

		// flat display
		if ( -1 == $max_depth ) {

			$empty_array = array();
			foreach ( $elements as $e )
				$this->display_element( $e, $empty_array, 1, 0, $args, $output );

			return $output;
		}

		/*
		 * need to display in hierarchical order
		 * separate elements into two buckets: top level and children elements
		 * children_elements is two dimensional array, eg.
		 * children_elements[10][] contains all sub-elements whose parent is 10.
		 */
		$top_level_elements = array();
		$children_elements  = array();

		foreach ( $elements as $e ) {
			// BuddyPress: changed '==' to '==='. This is the only change from version in Walker::walk().
			if ( 0 === $e->$parent_field )
				$top_level_elements[] = $e;
			else
				$children_elements[$e->$parent_field][] = $e;
		}

		/*
		 * when none of the elements is top level
		 * assume the first one must be root of the sub elements
		 */
		if ( empty( $top_level_elements ) ) {

			$first              = array_slice( $elements, 0, 1 );
			$root               = $first[0];
			$top_level_elements = array();
			$children_elements  = array();

			foreach ( $elements as $e ) {
				if ( $root->$parent_field == $e->$parent_field )
					$top_level_elements[] = $e;
				else
					$children_elements[$e->$parent_field][] = $e;
			}
		}

		foreach ( $top_level_elements as $e )
			$this->display_element( $e, $children_elements, $max_depth, 0, $args, $output );

		/*
		 * if we are displaying all levels, and remaining children_elements is not empty,
		 * then we got orphans, which should be displayed regardless
		 */
		if ( ( $max_depth == 0 ) && count( $children_elements ) > 0 ) {
			$empty_array = array();

			foreach ( $children_elements as $orphans )
				foreach ( $orphans as $op )
					$this->display_element( $op, $empty_array, 1, 0, $args, $output );
		 }

		 return $output;
	}

	/**
	 * Display the current <li> that we are on.
	 *
	 * @see Walker::start_el() for complete description of parameters .
	 *
	 * @since BuddyPress (1.7.0)
	 *
	 * @param string $output Passed by reference. Used to append
	 *        additional content.
	 * @param object $item Menu item data object.
	 * @param int $depth Depth of menu item. Used for padding. Optional,
	 *        defaults to 0.
	 * @param array $args Optional. See {@link Walker::start_el()}.
	 * @param int $current_page Menu item ID. Optional.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		// If we're someway down the tree, indent the HTML with the appropriate number of tabs
		$indent = $depth ? str_repeat( "\t", $depth ) : '';

		// Add HTML classes
		$class_names = join( ' ', apply_filters( 'bp_nav_menu_css_class', array_filter( $item->class ), $item, $args ) );
		$class_names = ! empty( $class_names ) ? ' class="' . esc_attr( $class_names ) . '"' : '';

		// Add HTML ID
		$id = sanitize_html_class( $item->css_id . '-personal-li' );  // Backpat with BP pre-1.7
		$id = apply_filters( 'bp_nav_menu_item_id', $id, $item, $args );
		$id = ! empty( $id ) ? ' id="' . esc_attr( $id ) . '"' : '';

		// Opening tag; closing tag is handled in Walker_Nav_Menu::end_el().
		$output .= $indent . '<li' . $id . $class_names . '>';

		// Add href attribute
		$attributes = ! empty( $item->link ) ? ' href="' . esc_attr( esc_url( $item->link ) ) . '"' : '';

		// Construct the link
		$item_output = $args->before;
		$item_output .= '<a' . $attributes . '>';
		$item_output .= $args->link_before . apply_filters( 'the_title', $item->name, 0 ) . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		// $output is byref
		$output .= apply_filters( 'bp_walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
}

/**
 * Create a set of BuddyPress-specific links for use in the Menus admin UI.
 *
 * Borrowed heavily from {@link Walker_Nav_Menu_Checklist}, but modified so as not
 * to require an actual post type or taxonomy, and to force certain CSS classes
 *
 * @since BuddyPress (1.9.0)
 */
class BP_Walker_Nav_Menu_Checklist extends Walker_Nav_Menu {

	/**
	 * Constructor.
	 *
	 * @see Walker_Nav_Menu::__construct() for a description of parameters.
	 *
	 * @param array $fields See {@link Walker_Nav_Menu::__construct()}.
	 */
	public function __construct( $fields = false ) {
		if ( $fields ) {
			$this->db_fields = $fields;
		}
	}

	/**
	 * Create the markup to start a tree level.
	 *
	 * @see Walker_Nav_Menu::start_lvl() for description of parameters.
	 *
	 * @param string $output See {@Walker_Nav_Menu::start_lvl()}.
	 * @param int $depth See {@Walker_Nav_Menu::start_lvl()}.
	 * @param array $args See {@Walker_Nav_Menu::start_lvl()}.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "\n$indent<ul class='children'>\n";
	}

	/**
	 * Create the markup to end a tree level.
	 *
	 * @see Walker_Nav_Menu::end_lvl() for description of parameters.
	 *
	 * @param string $output See {@Walker_Nav_Menu::end_lvl()}.
	 * @param int $depth See {@Walker_Nav_Menu::end_lvl()}.
	 * @param array $args See {@Walker_Nav_Menu::end_lvl()}.
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "\n$indent</ul>";
	}

	/**
	 * Create the markup to start an element.
	 *
	 * @see Walker::start_el() for description of parameters.
	 *
	 * @param string $output Passed by reference. Used to append additional
	 *        content.
	 * @param object $item Menu item data object.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param object $args See {@Walker::start_el()}.
	 * @param int $id See {@Walker::start_el()}.
	 */
	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		global $_nav_menu_placeholder;

		$_nav_menu_placeholder = ( 0 > $_nav_menu_placeholder ) ? intval($_nav_menu_placeholder) - 1 : -1;
		$possible_object_id = isset( $item->post_type ) && 'nav_menu_item' == $item->post_type ? $item->object_id : $_nav_menu_placeholder;
		$possible_db_id = ( ! empty( $item->ID ) ) && ( 0 < $possible_object_id ) ? (int) $item->ID : 0;

		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$output .= $indent . '<li>';
		$output .= '<label class="menu-item-title">';
		$output .= '<input type="checkbox" class="menu-item-checkbox';

		if ( property_exists( $item, 'label' ) ) {
			$title = $item->label;
		}

		$output .= '" name="menu-item[' . $possible_object_id . '][menu-item-object-id]" value="'. esc_attr( $item->object_id ) .'" /> ';
		$output .= isset( $title ) ? esc_html( $title ) : esc_html( $item->title );
		$output .= '</label>';

		if ( empty( $item->url ) ) {
			$item->url = $item->guid;
		}

		if ( ! in_array( array( 'bp-menu', 'bp-'. $item->post_excerpt .'-nav' ), $item->classes ) ) {
			$item->classes[] = 'bp-menu';
			$item->classes[] = 'bp-'. $item->post_excerpt .'-nav';
		}

		// Menu item hidden fields
		$output .= '<input type="hidden" class="menu-item-db-id" name="menu-item[' . $possible_object_id . '][menu-item-db-id]" value="' . $possible_db_id . '" />';
		$output .= '<input type="hidden" class="menu-item-object" name="menu-item[' . $possible_object_id . '][menu-item-object]" value="'. esc_attr( $item->object ) .'" />';
		$output .= '<input type="hidden" class="menu-item-parent-id" name="menu-item[' . $possible_object_id . '][menu-item-parent-id]" value="'. esc_attr( $item->menu_item_parent ) .'" />';
		$output .= '<input type="hidden" class="menu-item-type" name="menu-item[' . $possible_object_id . '][menu-item-type]" value="custom" />';
		$output .= '<input type="hidden" class="menu-item-title" name="menu-item[' . $possible_object_id . '][menu-item-title]" value="'. esc_attr( $item->title ) .'" />';
		$output .= '<input type="hidden" class="menu-item-url" name="menu-item[' . $possible_object_id . '][menu-item-url]" value="'. esc_attr( $item->url ) .'" />';
		$output .= '<input type="hidden" class="menu-item-target" name="menu-item[' . $possible_object_id . '][menu-item-target]" value="'. esc_attr( $item->target ) .'" />';
		$output .= '<input type="hidden" class="menu-item-attr_title" name="menu-item[' . $possible_object_id . '][menu-item-attr_title]" value="'. esc_attr( $item->attr_title ) .'" />';
		$output .= '<input type="hidden" class="menu-item-classes" name="menu-item[' . $possible_object_id . '][menu-item-classes]" value="'. esc_attr( implode( ' ', $item->classes ) ) .'" />';
		$output .= '<input type="hidden" class="menu-item-xfn" name="menu-item[' . $possible_object_id . '][menu-item-xfn]" value="'. esc_attr( $item->xfn ) .'" />';
	}
}

/**
 * Base class for the BuddyPress Suggestions API.
 *
 * Originally built to power BuddyPress' at-mentions suggestions, it's flexible enough to be used
 * for similar kinds of future core requirements, or those desired by third-party developers.
 *
 * To implement a new suggestions service, create a new class that extends this one, and update
 * the list of default services in {@link bp_core_get_suggestions()}. If you're building a plugin,
 * it's recommend that you use the `bp_suggestions_services` filter to do this. :)
 *
 * While the implementation of the query logic is left to you, it should be as quick and efficient
 * as possible. When implementing the abstract methods in this class, pay close attention to the
 * recommendations provided in the phpDoc blocks, particularly the expected return types.
 *
 * @since BuddyPress (2.1.0)
 */
abstract class BP_Suggestions {

	/**
	 * Default arguments common to all suggestions services.
	 *
	 * If your custom service requires further defaults, add them here.
	 *
	 * @since BuddyPress (2.1.0)
	 * @var array
	 */
	protected $default_args = array(
		'limit' => 16,
		'term'  => '',
		'type'  => '',
	);

	/**
	 * Holds the arguments for the query (about to made to the suggestions service).
	 *
	 * This includes `$default_args`, as well as the user-supplied values.
	 *
	 * @since BuddyPress (2.1.0)
	 * @var array
	 */
	protected $args = array(
	);


	/**
	 * Constructor.
	 *
	 * @param array $args Optional. If set, used as the parameters for the suggestions service query.
	 * @since BuddyPress (2.1.0)
	 */
	public function __construct( array $args = array() ) {
		if ( ! empty( $args ) ) {
			$this->set_query( $args );
		}
	}

	/**
	 * Set the parameters for the suggestions service query.
	 *
	 * @param array $args {
	 *     @type int $limit Maximum number of results to display. Optional, default: 16.
	 *     @type string $type The name of the suggestion service to use for the request. Mandatory.
	 *     @type string $term The suggestion service will try to find results that contain this string.
	 *           Mandatory.
	 * }
	 * @since BuddyPress (2.1.0)
	 */
	public function set_query( array $args = array() ) {
		$this->args = wp_parse_args( $args, $this->default_args );
	}

	/**
	 * Validate and sanitise the parameters for the suggestion service query.
	 *
	 * Be sure to call this class' version of this method when implementing it in your own service.
	 * If validation fails, you must return a WP_Error object.
	 *
	 * @return true|WP_Error If validation fails, return a WP_Error object. On success, return true (bool).
	 * @since BuddyPress (2.1.0)
	 */
	public function validate() {
		$this->args['limit'] = absint( $this->args['limit'] );
		$this->args['term']  = trim( sanitize_text_field( $this->args['term'] ) );
		$this->args          = apply_filters( 'bp_suggestions_args', $this->args, $this );


		// Check for invalid or missing mandatory parameters.
		if ( ! $this->args['limit'] || ! $this->args['term'] ) {
			return new WP_Error( 'missing_parameter' );
		}

		// Check for blocked users (e.g. deleted accounts, or spammers).
		if ( is_user_logged_in() && ! bp_is_user_active( get_current_user_id() ) ) {
			return new WP_Error( 'invalid_user' );
		}

		return apply_filters( 'bp_suggestions_validate_args', true, $this );
	}

	/**
	 * Find and return a list of suggestions that match the query.
	 *
	 * The return type is important. If no matches are found, an empty array must be returned.
	 * Matches must be returned as objects in an array.
	 *
	 * The object format for each match must be: { 'ID': string, 'image': string, 'name': string }
	 * For example: { 'ID': 'admin', 'image': 'http://example.com/logo.png', 'name': 'Name Surname' }
	 *
	 * @return array|WP_Error Array of results. If there were problems, returns a WP_Error object.
	 * @since BuddyPress (2.1.0)
	 */
	abstract public function get_suggestions();
}

/**
 * Adds support for user at-mentions to the Suggestions API.
 *
 * This class is in the Core component because it's required by a class in the Groups component,
 * and Groups is loaded before Members (alphabetical order).
 *
 * @since BuddyPress (2.1.0)
 */
class BP_Members_Suggestions extends BP_Suggestions {

	/**
	 * Default arguments for this suggestions service.
	 *
	 * @since BuddyPress (2.1.0)
	 * @var array $args {
	 *     @type int $limit Maximum number of results to display. Default: 16.
	 *     @type bool $only_friends If true, only match the current user's friends. Default: false.
	 *     @type string $term The suggestion service will try to find results that contain this string.
	 *           Mandatory.
	 * }
	 */
	protected $default_args = array(
		'limit'        => 10,
		'only_friends' => false,
		'term'         => '',
		'type'         => '',
	);


	/**
	 * Validate and sanitise the parameters for the suggestion service query.
	 *
	 * @return true|WP_Error If validation fails, return a WP_Error object. On success, return true (bool).
	 * @since BuddyPress (2.1.0)
	 */
	public function validate() {
		$this->args['only_friends'] = (bool) $this->args['only_friends'];
		$this->args                 = apply_filters( 'bp_members_suggestions_args', $this->args, $this );

		// Check for invalid or missing mandatory parameters.
		if ( $this->args['only_friends'] && ( ! bp_is_active( 'friends' ) || ! is_user_logged_in() ) ) {
			return new WP_Error( 'missing_requirement' );
		}

		return apply_filters( 'bp_members_suggestions_validate_args', parent::validate(), $this );
	}

	/**
	 * Find and return a list of username suggestions that match the query.
	 *
	 * @return array|WP_Error Array of results. If there were problems, returns a WP_Error object.
	 * @since BuddyPress (2.1.0)
	 */
	public function get_suggestions() {
		$user_query = array(
			'count_total'     => '',  // Prevents total count
			'populate_extras' => false,
			'type'            => 'alphabetical',

			'page'            => 1,
			'per_page'        => $this->args['limit'],
			'search_terms'    => $this->args['term'],
			'search_wildcard' => 'right',
		);

		// Only return matches of friends of this user.
		if ( $this->args['only_friends'] && is_user_logged_in() ) {
			$user_query['user_id'] = get_current_user_id();
		}

		$user_query = apply_filters( 'bp_members_suggestions_query_args', $user_query, $this );
		if ( is_wp_error( $user_query ) ) {
			return $user_query;
		}


		$user_query = new BP_User_Query( $user_query );
		$results    = array();

		foreach ( $user_query->results as $user ) {
			$result        = new stdClass();
			$result->ID    = $user->user_nicename;
			$result->image = bp_core_fetch_avatar( array( 'html' => false, 'item_id' => $user->ID ) );
			$result->name  = bp_core_get_user_displayname( $user->ID );

			$results[] = $result;
		}

		return apply_filters( 'bp_members_suggestions_get_suggestions', $results, $this );
	}
}

/**
 * Base class for creating query classes that generate SQL fragments for filtering results based on recursive query params.
 *
 * @since BuddyPress (2.2.0)
 */
abstract class BP_Recursive_Query {

	/**
	 * Query arguments passed to the constructor.
	 *
	 * @since BuddyPress (2.2.0)
	 * @access public
	 * @var array
	 */
	public $queries = array();

	/**
	 * Generate SQL clauses to be appended to a main query.
	 *
	 * Extending classes should call this method from within a publicly
	 * accessible get_sql() method, and manipulate the SQL as necessary.
	 * For example, {@link BP_XProfile_Query::get_sql()} is merely a wrapper for
	 * get_sql_clauses(), while {@link BP_Activity_Query::get_sql()} discards
	 * the empty 'join' clause, and only passes the 'where' clause.
	 *
	 * @since BuddyPress (2.2.0)
	 * @access protected
	 *
	 * @param  string $primary_table
	 * @param  string $primary_id_column
	 * @return array
	 */
	protected function get_sql_clauses() {
		$sql = $this->get_sql_for_query( $this->queries );

		if ( ! empty( $sql['where'] ) ) {
			$sql['where'] = ' AND ' . "\n" . $sql['where'] . "\n";
		}

		return $sql;
	}

	/**
	 * Generate SQL clauses for a single query array.
	 *
	 * If nested subqueries are found, this method recurses the tree to
	 * produce the properly nested SQL.
	 *
	 * Subclasses generally do not need to call this method. It is invoked
	 * automatically from get_sql_clauses().
	 *
	 * @since BuddyPress (2.2.0)
	 * @access protected
	 *
	 * @param  array $query Query to parse.
	 * @param  int   $depth Optional. Number of tree levels deep we
	 *                      currently are. Used to calculate indentation.
	 * @return array
	 */
	protected function get_sql_for_query( $query, $depth = 0 ) {
		$sql_chunks = array(
			'join'  => array(),
			'where' => array(),
		);

		$sql = array(
			'join'  => '',
			'where' => '',
		);

		$indent = '';
		for ( $i = 0; $i < $depth; $i++ ) {
			$indent .= "\t";
		}

		foreach ( $query as $key => $clause ) {
			if ( 'relation' === $key ) {
				$relation = $query['relation'];
			} elseif ( is_array( $clause ) ) {
				// This is a first-order clause
				if ( $this->is_first_order_clause( $clause ) ) {
					$clause_sql = $this->get_sql_for_clause( $clause, $query );

					$where_count = count( $clause_sql['where'] );
					if ( ! $where_count ) {
						$sql_chunks['where'][] = '';
					} elseif ( 1 === $where_count ) {
						$sql_chunks['where'][] = $clause_sql['where'][0];
					} else {
						$sql_chunks['where'][] = '( ' . implode( ' AND ', $clause_sql['where'] ) . ' )';
					}

					$sql_chunks['join'] = array_merge( $sql_chunks['join'], $clause_sql['join'] );
				// This is a subquery
				} else {
					$clause_sql = $this->get_sql_for_query( $clause, $depth + 1 );

					$sql_chunks['where'][] = $clause_sql['where'];
					$sql_chunks['join'][]  = $clause_sql['join'];
				}
			}
		}

		// Filter empties
		$sql_chunks['join']  = array_filter( $sql_chunks['join'] );
		$sql_chunks['where'] = array_filter( $sql_chunks['where'] );

		if ( empty( $relation ) ) {
			$relation = 'AND';
		}

		if ( ! empty( $sql_chunks['join'] ) ) {
			$sql['join'] = implode( ' ', array_unique( $sql_chunks['join'] ) );
		}

		if ( ! empty( $sql_chunks['where'] ) ) {
			$sql['where'] = '( ' . "\n\t" . $indent . implode( ' ' . "\n\t" . $indent . $relation . ' ' . "\n\t" . $indent, $sql_chunks['where'] ) . "\n" . $indent . ')' . "\n";
		}

		return $sql;
	}

	/**
	 * Recursive-friendly query sanitizer.
	 *
	 * Ensures that each query-level clause has a 'relation' key, and that
	 * each first-order clause contains all the necessary keys from
	 * $defaults.
	 *
	 * Extend this method if your class uses different sanitizing logic.
	 *
	 * @since BuddyPress (2.2.0)
	 * @access public
	 *
	 * @param  array $queries Array of query clauses.
	 * @return array Sanitized array of query clauses.
	 */
	protected function sanitize_query( $queries ) {
		$clean_queries = array();

		if ( ! is_array( $queries ) ) {
			return $clean_queries;
		}

		foreach ( $queries as $key => $query ) {
			if ( 'relation' === $key ) {
				$relation = $query;

			} elseif ( ! is_array( $query ) ) {
				continue;

			// First-order clause.
			} elseif ( $this->is_first_order_clause( $query ) ) {
				if ( isset( $query['value'] ) && array() === $query['value'] ) {
					unset( $query['value'] );
				}

				$clean_queries[] = $query;

			// Otherwise, it's a nested query, so we recurse.
			} else {
				$cleaned_query = $this->sanitize_query( $query );

				if ( ! empty( $cleaned_query ) ) {
					$clean_queries[] = $cleaned_query;
				}
			}
		}

		if ( empty( $clean_queries ) ) {
			return $clean_queries;
		}

		// Sanitize the 'relation' key provided in the query.
		if ( isset( $relation ) && 'OR' === strtoupper( $relation ) ) {
			$clean_queries['relation'] = 'OR';

		/*
		 * If there is only a single clause, call the relation 'OR'.
		 * This value will not actually be used to join clauses, but it
		 * simplifies the logic around combining key-only queries.
		 */
		} elseif ( 1 === count( $clean_queries ) ) {
			$clean_queries['relation'] = 'OR';

		// Default to AND.
		} else {
			$clean_queries['relation'] = 'AND';
		}

		return $clean_queries;
	}

	/**
	 * Generate JOIN and WHERE clauses for a first-order clause.
	 *
	 * Must be overridden in a subclass.
	 *
	 * @since BuddyPress (2.2.0)
	 * @access protected
	 *
	 * @param  array $clause       Array of arguments belonging to the clause.
	 * @param  array $parent_query Parent query to which the clause belongs.
	 * @return array {
	 *     @type array $join  Array of subclauses for the JOIN statement.
	 *     @type array $where Array of subclauses for the WHERE statement.
	 * }
	 */
	abstract protected function get_sql_for_clause( $clause, $parent_query );

	/**
	 * Determine whether a clause is first-order.
	 *
	 * Must be overridden in a subclass.
	 *
	 * @since BuddyPress (2.2.0)
	 * @access protected
	 *
	 * @param  array $q Clause to check.
	 * @return bool
	 */
	abstract protected function is_first_order_clause( $query );
}
