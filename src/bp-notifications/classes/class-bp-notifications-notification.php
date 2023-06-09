<?php
/**
 * BuddyPress Notifications Classes.
 *
 * Classes used for the Notifications component.
 *
 * @package BuddyPress
 * @subpackage NotificationsClasses
 * @since 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Notification items.
 *
 * Use this class to create, access, edit, or delete BuddyPress Notifications.
 *
 * @since 1.9.0
 */
#[AllowDynamicProperties]
class BP_Notifications_Notification {

	/**
	 * The notification ID.
	 *
	 * @since 1.9.0
	 * @var int
	 */
	public $id;

	/**
	 * The ID of the item associated with the notification.
	 *
	 * @since 1.9.0
	 * @var int
	 */
	public $item_id;

	/**
	 * The ID of the secondary item associated with the notification.
	 *
	 * @since 1.9.0
	 * @var int|null
	 */
	public $secondary_item_id = null;

	/**
	 * The ID of the user the notification is associated with.
	 *
	 * @since 1.9.0
	 * @var int
	 */
	public $user_id;

	/**
	 * The name of the component that the notification is for.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	public $component_name;

	/**
	 * The component action which the notification is related to.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	public $component_action;

	/**
	 * The date the notification was created.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	public $date_notified;

	/**
	 * Is the notification new, or has it already been read.
	 *
	 * @since 1.9.0
	 * @var bool
	 */
	public $is_new;

	/**
	 * Columns in the notifications table.
	 *
	 * @since 9.1.0
	 * @var array
	 */
	public static $columns = array(
		'id',
		'user_id',
		'item_id',
		'secondary_item_id',
		'component_name',
		'component_action',
		'date_notified',
		'is_new',
	);

	/** Public Methods ********************************************************/

	/**
	 * Constructor method.
	 *
	 * @since 1.9.0
	 *
	 * @param int $id Optional. Provide an ID to access an existing
	 *                notification item.
	 */
	public function __construct( $id = 0 ) {
		if ( ! empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Update or insert notification details into the database.
	 *
	 * @since 1.9.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save() {
		$retval = false;

		/**
		 * Fires before the current notification item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since 2.0.0
		 *
		 * @param BP_Notifications_Notification $value Current instance of the notification item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_notification_before_save', array( &$this ) );

		$data = array(
			'user_id'           => $this->user_id,
			'item_id'           => $this->item_id,
			'secondary_item_id' => $this->secondary_item_id,
			'component_name'    => $this->component_name,
			'component_action'  => $this->component_action,
			'date_notified'     => $this->date_notified,
			'is_new'            => $this->is_new,
		);
		$data_format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%d' );

		// Update.
		if ( ! empty( $this->id ) ) {
			$result = self::_update( $data, array( 'ID' => $this->id ), $data_format, array( '%d' ) );

		// Insert.
		} else {
			$result = self::_insert( $data, $data_format );
		}

		// Set the notification ID if successful.
		if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
			global $wpdb;

			$this->id = $wpdb->insert_id;
			$retval   = $wpdb->insert_id;
		}

		/**
		 * Fires after the current notification item gets saved.
		 *
		 * @since 2.0.0
		 *
		 * @param BP_Notifications_Notification $value Current instance of the notification item being saved.
		 *                                             Passed by reference.
		 */
		do_action_ref_array( 'bp_notification_after_save', array( &$this ) );

		// Return the result.
		return $retval;
	}

	/**
	 * Fetch data for an existing notification from the database.
	 *
	 * @since 1.9.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 */
	public function populate() {
		global $wpdb;

		$bp = buddypress();

		// Look for a notification.
		$notification = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->notifications->table_name} WHERE id = %d", $this->id ) );

		// Setup the notification data.
		if ( ! empty( $notification ) && ! is_wp_error( $notification ) ) {
			$this->item_id           = (int) $notification->item_id;
			$this->secondary_item_id = (int) $notification->secondary_item_id;
			$this->user_id           = (int) $notification->user_id;
			$this->component_name    = $notification->component_name;
			$this->component_action  = $notification->component_action;
			$this->date_notified     = $notification->date_notified;
			$this->is_new            = (int) $notification->is_new;
		}
	}

	/** Protected Static Methods **********************************************/

	/**
	 * Create a notification entry.
	 *
	 * @since 1.9.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @see wpdb::insert() for further description of paramater formats.
	 *
	 * @param array $data {
	 *     Array of notification data, passed to {@link wpdb::insert()}.
	 *     @type int    $user_id           ID of the associated user.
	 *     @type int    $item_id           ID of the associated item.
	 *     @type int    $secondary_item_id ID of the secondary associated item.
	 *     @type string $component_name    Name of the associated component.
	 *     @type string $component_action  Name of the associated component
	 *                                     action.
	 *     @type string $date_notified     Timestamp of the notification.
	 *     @type bool   $is_new            True if the notification is unread, otherwise false.
	 * }
	 * @param array $data_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	protected static function _insert( $data = array(), $data_format = array() ) {
		global $wpdb;
		return $wpdb->insert( buddypress()->notifications->table_name, $data, $data_format );
	}

	/**
	 * Update notifications.
	 *
	 * @since 1.9.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $data         Array of notification data to update, passed to
	 *                            {@link wpdb::update()}. Accepts any property of a
	 *                            BP_Notification_Notification object.
	 * @param array $where        The WHERE params as passed to wpdb::update().
	 *                            Typically consists of array( 'ID' => $id ) to specify the ID
	 *                            of the item being updated. See {@link wpdb::update()}.
	 * @param array $data_format  See {@link wpdb::insert()}.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _update( $data = array(), $where = array(), $data_format = array(), $where_format = array() ) {
		global $wpdb;
		return $wpdb->update( buddypress()->notifications->table_name, $data, $where, $data_format, $where_format );
	}

	/**
	 * Delete notifications.
	 *
	 * @since 1.9.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @see wpdb::delete() for further description of paramater formats.
	 *
	 * @param array $where        Array of WHERE clauses to filter by, passed to
	 *                            {@link wpdb::delete()}. Accepts any property of a
	 *                            BP_Notification_Notification object.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _delete( $where = array(), $where_format = array() ) {
		global $wpdb;
		return $wpdb->delete( buddypress()->notifications->table_name, $where, $where_format );
	}

	/**
	 * Assemble the WHERE clause of a get() SQL statement.
	 *
	 * Used by BP_Notifications_Notification::get() to create its WHERE
	 * clause.
	 *
	 * @since 1.9.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param array  $args           See {@link BP_Notifications_Notification::get()}
	 *                               for more details.
	 * @param string $select_sql     SQL SELECT fragment.
	 * @param string $from_sql       SQL FROM fragment.
	 * @param string $join_sql       SQL JOIN fragment.
	 * @param string $meta_query_sql SQL meta query fragment.
	 * @return string WHERE clause.
	 */
	protected static function get_where_sql( $args = array(), $select_sql = '', $from_sql = '', $join_sql = '', $meta_query_sql = '' ) {
		global $wpdb;

		$where_conditions = array();
		$where            = '';

		// The id.
		if ( ! empty( $args['id'] ) ) {
			$id_in = implode( ',', wp_parse_id_list( $args['id'] ) );
			$where_conditions['id'] = "id IN ({$id_in})";
		}

		// The user_id.
		if ( ! empty( $args['user_id'] ) ) {
			$user_id_in = implode( ',', wp_parse_id_list( $args['user_id'] ) );
			$where_conditions['user_id'] = "user_id IN ({$user_id_in})";
		}

		// The item_id.
		if ( ! empty( $args['item_id'] ) ) {
			$item_id_in = implode( ',', wp_parse_id_list( $args['item_id'] ) );
			$where_conditions['item_id'] = "item_id IN ({$item_id_in})";
		}

		// The secondary_item_id.
		if ( ! empty( $args['secondary_item_id'] ) ) {
			$secondary_item_id_in = implode( ',', wp_parse_id_list( $args['secondary_item_id'] ) );
			$where_conditions['secondary_item_id'] = "secondary_item_id IN ({$secondary_item_id_in})";
		}

		// The component_name.
		if ( ! empty( $args['component_name'] ) ) {
			if ( ! is_array( $args['component_name'] ) ) {
				$component_names = explode( ',', $args['component_name'] );
			} else {
				$component_names = $args['component_name'];
			}

			$cn_clean = array();
			foreach ( $component_names as $cn ) {
				$cn_clean[] = $wpdb->prepare( '%s', $cn );
			}

			$cn_in = implode( ',', $cn_clean );
			$where_conditions['component_name'] = "component_name IN ({$cn_in})";
		}

		// The component_action.
		if ( ! empty( $args['component_action'] ) ) {
			if ( ! is_array( $args['component_action'] ) ) {
				$component_actions = explode( ',', $args['component_action'] );
			} else {
				$component_actions = $args['component_action'];
			}

			$ca_clean = array();
			foreach ( $component_actions as $ca ) {
				$ca_clean[] = $wpdb->prepare( '%s', $ca );
			}

			$ca_in = implode( ',', $ca_clean );
			$where_conditions['component_action'] = "component_action IN ({$ca_in})";
		}

		// If is_new.
		if ( ! empty( $args['is_new'] ) && 'both' !== $args['is_new'] ) {
			$where_conditions['is_new'] = "is_new = 1";
		} elseif ( isset( $args['is_new'] ) && ( 0 === $args['is_new'] || false === $args['is_new'] ) ) {
			$where_conditions['is_new'] = "is_new = 0";
		}

		// The search_terms.
		if ( ! empty( $args['search_terms'] ) ) {
			$search_terms_like = '%' . bp_esc_like( $args['search_terms'] ) . '%';
			$where_conditions['search_terms'] = $wpdb->prepare( "( component_name LIKE %s OR component_action LIKE %s )", $search_terms_like, $search_terms_like );
		}

		// The date query.
		if ( ! empty( $args['date_query'] ) ) {
			$where_conditions['date_query'] = self::get_date_query_sql( $args['date_query'] );
		}

		// The meta query.
		if ( ! empty( $meta_query_sql['where'] ) ) {
			$where_conditions['meta_query'] = $meta_query_sql['where'];
		}

		/**
		 * Filters the MySQL WHERE conditions for the Notifications items get method.
		 *
		 * @since 2.3.0
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $args             Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 * @param string $meta_query_sql   Current meta query WHERE statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bp_notifications_get_where_conditions', $where_conditions, $args, $select_sql, $from_sql, $join_sql, $meta_query_sql );

		// Custom WHERE.
		if ( ! empty( $where_conditions ) ) {
			$where = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		return $where;
	}

	/**
	 * Assemble the ORDER BY clause of a get() SQL statement.
	 *
	 * Used by BP_Notifications_Notification::get() to create its ORDER BY
	 * clause.
	 *
	 * @since 1.9.0
	 *
	 * @param array $args See {@link BP_Notifications_Notification::get()}
	 *                    for more details.
	 * @return string ORDER BY clause.
	 */
	protected static function get_order_by_sql( $args = array() ) {

		// Setup local variable.
		$conditions = array();
		$retval     = '';

		// Order by.
		if ( ! empty( $args['order_by'] ) ) {
			$order_by_clean = array();
			foreach ( (array) $args['order_by'] as $key => $value ) {
				if ( in_array( $value, self::$columns, true ) ) {
					$order_by_clean[] = $value;
				}
			}
			if ( ! empty( $order_by_clean ) ) {
				$order_by               = implode( ', ', $order_by_clean );
				$conditions['order_by'] = "{$order_by}";
			}
		}

		// Sort order direction.
		if ( ! empty( $args['sort_order'] ) ) {
			$sort_order               = bp_esc_sql_order( $args['sort_order'] );
			$conditions['sort_order'] = "{$sort_order}";
		}

		// Custom ORDER BY.
		if ( ! empty( $conditions ) ) {
			$retval = 'ORDER BY ' . implode( ' ', $conditions );
		}

		return $retval;
	}

	/**
	 * Assemble the LIMIT clause of a get() SQL statement.
	 *
	 * Used by BP_Notifications_Notification::get() to create its LIMIT clause.
	 *
	 * @since 1.9.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param array $args See {@link BP_Notifications_Notification::get()}
	 *                    for more details.
	 * @return string $retval LIMIT clause.
	 */
	protected static function get_paged_sql( $args = array() ) {
		global $wpdb;

		// Setup local variable.
		$retval = '';

		// Custom LIMIT.
		if ( ! empty( $args['page'] ) && ! empty( $args['per_page'] ) ) {
			$page     = absint( $args['page']     );
			$per_page = absint( $args['per_page'] );
			$offset   = $per_page * ( $page - 1 );
			$retval   = $wpdb->prepare( "LIMIT %d, %d", $offset, $per_page );
		}

		return $retval;
	}

	/**
	 * Assemble query clauses, based on arguments, to pass to $wpdb methods.
	 *
	 * The insert(), update(), and delete() methods of {@link wpdb} expect
	 * arguments of the following forms:
	 *
	 * - associative arrays whose key/value pairs are column => value, to
	 *   be used in WHERE, SET, or VALUES clauses.
	 * - arrays of "formats", which tell $wpdb->prepare() which type of
	 *   value to expect when sanitizing (eg, array( '%s', '%d' ))
	 *
	 * This utility method can be used to assemble both kinds of params,
	 * out of a single set of associative array arguments, such as:
	 *
	 *     $args = array(
	 *         'user_id' => 4,
	 *         'component_name' => 'groups',
	 *     );
	 *
	 * This will be converted to:
	 *
	 *     array(
	 *         'data' => array(
	 *             'user_id' => 4,
	 *             'component_name' => 'groups',
	 *         ),
	 *         'format' => array(
	 *             '%d',
	 *             '%s',
	 *         ),
	 *     )
	 *
	 * which can easily be passed as arguments to the $wpdb methods.
	 *
	 * @since 1.9.0
	 *
	 * @param array $args Associative array of filter arguments.
	 *                    See {@BP_Notifications_Notification::get()}
	 *                    for a breakdown.
	 * @return array Associative array of 'data' and 'format' args.
	 */
	protected static function get_query_clauses( $args = array() ) {
		$where_clauses = array(
			'data'   => array(),
			'format' => array(),
		);

		// The id.
		if ( ! empty( $args['id'] ) ) {
			$where_clauses['data']['id'] = absint( $args['id'] );
			$where_clauses['format'][] = '%d';
		}

		// The user_id.
		if ( ! empty( $args['user_id'] ) ) {
			$where_clauses['data']['user_id'] = absint( $args['user_id'] );
			$where_clauses['format'][] = '%d';
		}

		// The item_id.
		if ( ! empty( $args['item_id'] ) ) {
			$where_clauses['data']['item_id'] = absint( $args['item_id'] );
			$where_clauses['format'][] = '%d';
		}

		// The secondary_item_id.
		if ( ! empty( $args['secondary_item_id'] ) ) {
			$where_clauses['data']['secondary_item_id'] = absint( $args['secondary_item_id'] );
			$where_clauses['format'][] = '%d';
		}

		// The component_name.
		if ( ! empty( $args['component_name'] ) ) {
			$where_clauses['data']['component_name'] = $args['component_name'];
			$where_clauses['format'][] = '%s';
		}

		// The component_action.
		if ( ! empty( $args['component_action'] ) ) {
			$where_clauses['data']['component_action'] = $args['component_action'];
			$where_clauses['format'][] = '%s';
		}

		// If is_new.
		if ( isset( $args['is_new'] ) ) {
			$where_clauses['data']['is_new'] = ! empty( $args['is_new'] ) ? 1 : 0;
			$where_clauses['format'][] = '%d';
		}

		return $where_clauses;
	}

	/** Public Static Methods *************************************************/

	/**
	 * Check that a specific notification is for a specific user.
	 *
	 * @since 1.9.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $user_id         ID of the user being checked.
	 * @param int $notification_id ID of the notification being checked.
	 * @return bool True if the notification belongs to the user, otherwise false.
	 */
	public static function check_access( $user_id = 0, $notification_id = 0 ) {
		global $wpdb;

		$bp = buddypress();

		$query   = "SELECT COUNT(id) FROM {$bp->notifications->table_name} WHERE id = %d AND user_id = %d";
		$prepare = $wpdb->prepare( $query, $notification_id, $user_id );
		$result  = $wpdb->get_var( $prepare );

		return $result;
	}

	/**
	 * Parse notifications query arguments.
	 *
	 * @since 2.3.0
	 *
	 * @param array|string $args Args to parse.
	 * @return array
	 */
	public static function parse_args( $args = '' ) {
		return bp_parse_args(
			$args,
			array(
				'id'                => false,
				'user_id'           => false,
				'item_id'           => false,
				'secondary_item_id' => false,
				'component_name'    => bp_notifications_get_registered_components(),
				'component_action'  => false,
				'is_new'            => true,
				'search_terms'      => '',
				'order_by'          => false,
				'sort_order'        => false,
				'page'              => false,
				'per_page'          => false,
				'meta_query'        => false,
				'date_query'        => false,
				'update_meta_cache' => true,
			)
		);
	}

	/**
	 * Get notifications, based on provided filter parameters.
	 *
	 * @since 1.9.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param array $args {
	 *     Associative array of arguments. All arguments but $page and
	 *     $per_page can be treated as filter values for get_where_sql()
	 *     and get_query_clauses(). All items are optional.
	 *     @type int|array    $id                ID of notification being updated. Can be an
	 *                                           array of IDs.
	 *     @type int|array    $user_id           ID of user being queried. Can be an
	 *                                           array of user IDs.
	 *     @type int|array    $item_id           ID of associated item. Can be an array
	 *                                           of multiple item IDs.
	 *     @type int|array    $secondary_item_id ID of secondary associated
	 *                                           item. Can be an array of multiple IDs.
	 *     @type string|array $component_name    Name of the component to
	 *                                           filter by. Can be an array of component names.
	 *     @type string|array $component_action  Name of the action to
	 *                                           filter by. Can be an array of actions.
	 *     @type bool         $is_new            Whether to limit to new notifications. True
	 *                                           returns only new notifications, false returns only non-new
	 *                                           notifications. 'both' returns all. Default: true.
	 *     @type string       $search_terms      Term to match against component_name
	 *                                           or component_action fields.
	 *     @type string       $order_by          Database column to order notifications by.
	 *     @type string       $sort_order        Either 'ASC' or 'DESC'.
	 *     @type int          $page              Number of the current page of results. Default:
	 *                                           false (no pagination - all items).
	 *     @type int          $per_page          Number of items to show per page. Default:
	 *                                           false (no pagination - all items).
	 *     @type array        $meta_query        Array of meta_query conditions. See WP_Meta_Query::queries.
	 *     @type array        $date_query        Array of date_query conditions. See first parameter of
	 *                                           WP_Date_Query::__construct().
	 *     @type bool         $update_meta_cache Whether to update meta cache. Default: true.
	 * }
	 * @return array Located notifications.
	 */
	public static function get( $args = array() ) {
		global $wpdb;

		// Parse the arguments.
		$r = self::parse_args( $args );

		// Get BuddyPress.
		$bp = buddypress();

		// METADATA.
		$meta_query_sql = self::get_meta_query_sql( $r['meta_query'] );

		// SELECT.
		$select_sql = "SELECT n.*";

		// FROM.
		$from_sql = "FROM {$bp->notifications->table_name} n ";

		// Append meta data to the results.
		if ( isset( $r['meta_query'][0]['compare'] ) && 'EXISTS' === $r['meta_query'][0]['compare'] ) {
			$meta_table = $bp->notifications->table_name_meta;
			$select_sql = "SELECT n.*, {$meta_table}.id as meta_id, {$meta_table}.meta_key, {$meta_table}.meta_value";
		}

		// JOIN.
		$join_sql = $meta_query_sql['join'];

		// WHERE.
		$where_sql = self::get_where_sql( array(
			'id'                => $r['id'],
			'user_id'           => $r['user_id'],
			'item_id'           => $r['item_id'],
			'secondary_item_id' => $r['secondary_item_id'],
			'component_name'    => $r['component_name'],
			'component_action'  => $r['component_action'],
			'is_new'            => $r['is_new'],
			'search_terms'      => $r['search_terms'],
			'date_query'        => $r['date_query']
		), $select_sql, $from_sql, $join_sql, $meta_query_sql );

		// ORDER BY.
		$order_sql  = self::get_order_by_sql( array(
			'order_by'   => $r['order_by'],
			'sort_order' => $r['sort_order']
		) );

		// LIMIT %d, %d.
		$pag_sql    = self::get_paged_sql( array(
			'page'     => $r['page'],
			'per_page' => $r['per_page']
		) );

		// Concatenate query parts.
		$sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} {$order_sql} {$pag_sql}";

		// Perform query.
		$results = $wpdb->get_results( $sql );

		// Integer casting.
		foreach ( $results as $key => $result ) {
			$results[$key]->id                = (int) $results[$key]->id;
			$results[$key]->user_id           = (int) $results[$key]->user_id;
			$results[$key]->item_id           = (int) $results[$key]->item_id;
			$results[$key]->secondary_item_id = (int) $results[$key]->secondary_item_id;
			$results[$key]->is_new            = (int) $results[$key]->is_new;
		}

		// Update meta cache.
		if ( true === $r['update_meta_cache'] ) {
			bp_notifications_update_meta_cache( wp_list_pluck( $results, 'id' ) );
		}

		return $results;
	}

	/**
	 * Get a count of total notifications matching a set of arguments.
	 *
	 * @since 1.9.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param array|string $args See {@link BP_Notifications_Notification::get()}.
	 * @return int Count of located items.
	 */
	public static function get_total_count( $args ) {
		global $wpdb;

		// Parse the arguments.
		$r = self::parse_args( $args );

		// Load BuddyPress.
		$bp = buddypress();

		// METADATA.
		$meta_query_sql = self::get_meta_query_sql( $r['meta_query'] );

		// SELECT.
		$select_sql = "SELECT COUNT(*)";

		// FROM.
		$from_sql   = "FROM {$bp->notifications->table_name} n ";

		// JOIN.
		$join_sql   = $meta_query_sql['join'];

		// WHERE.
		$where_sql  = self::get_where_sql( array(
			'id'                => $r['id'],
			'user_id'           => $r['user_id'],
			'item_id'           => $r['item_id'],
			'secondary_item_id' => $r['secondary_item_id'],
			'component_name'    => $r['component_name'],
			'component_action'  => $r['component_action'],
			'is_new'            => $r['is_new'],
			'search_terms'      => $r['search_terms'],
			'date_query'        => $r['date_query']
		), $select_sql, $from_sql, $join_sql, $meta_query_sql );

		// Concatenate query parts.
		$sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql}";

		// Return the queried results.
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Get the SQL for the 'meta_query' param in BP_Notifications_Notification::get().
	 *
	 * We use WP_Meta_Query to do the heavy lifting of parsing the
	 * meta_query array and creating the necessary SQL clauses. However,
	 * since BP_Notifications_Notification::get() builds its SQL differently than
	 * WP_Query, we have to alter the return value (stripping the leading
	 * AND keyword from the 'where' clause).
	 *
	 * @since 2.3.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param  array $meta_query An array of meta_query filters. See the
	 *                           documentation for WP_Meta_Query for details.
	 * @return array $sql_array 'join' and 'where' clauses.
	 */
	public static function get_meta_query_sql( $meta_query = array() ) {
		global $wpdb;

		// Default array keys & empty values.
		$sql_array = array(
			'join'  => '',
			'where' => '',
		);

		// Bail if no meta query.
		if ( empty( $meta_query ) ) {
			return $sql_array;
		}

		$bp       = buddypress();
		$meta_sql = new WP_Meta_Query( $meta_query );

		// WP_Meta_Query expects the table name at $wpdb->notificationmeta.
		$wpdb->notificationmeta = $bp->notifications->table_name_meta;

		$meta_sql = $meta_sql->get_sql( 'notification', 'n', 'id' );

		// Strip the leading AND - it's handled in get().
		$sql_array['where'] = preg_replace( '/^\sAND/', '', $meta_sql['where'] );
		$sql_array['join']  = $meta_sql['join'];

		return $sql_array;
	}

	/**
	 * Get the SQL for the 'date_query' param in BP_Notifications_Notification::get().
	 *
	 * We use BP_Date_Query, which extends WP_Date_Query, to do the heavy lifting
	 * of parsing the date_query array and creating the necessary SQL clauses.
	 *
	 * @since 2.3.0
	 *
	 * @param array $date_query An array of date_query parameters. See the
	 *                          documentation for the first parameter of WP_Date_Query.
	 * @return string
	 */
	public static function get_date_query_sql( $date_query = array() ) {
		return BP_Date_Query::get_where_sql( $date_query, 'n.date_notified' );
	}

	/**
	 * Update notifications.
	 *
	 * @since 1.9.0
	 *
	 * @see BP_Notifications_Notification::get() for a description of
	 *      accepted update/where arguments.
	 *
	 * @param array $update_args Associative array of fields to update,
	 *                           and the values to update them to. Of the format
	 *                           array( 'user_id' => 4, 'component_name' => 'groups', ).
	 * @param array $where_args  Associative array of columns/values, to
	 *                           determine which rows should be updated. Of the format
	 *                           array( 'item_id' => 7, 'component_action' => 'members', ).
	 * @return int|false Number of rows updated on success, false on failure.
	 */
	public static function update( $update_args = array(), $where_args = array() ) {
		$update = self::get_query_clauses( $update_args );
		$where  = self::get_query_clauses( $where_args );

		/**
		 * Fires before the update of a notification item.
		 *
		 * @since 2.3.0
		 *
		 * @param array $update_args See BP_Notifications_Notification::update().
		 * @param array $where_args  See BP_Notifications_Notification::update().
		 */
		do_action( 'bp_notification_before_update', $update_args, $where_args );

		return self::_update(
			$update['data'],
			$where['data'],
			$update['format'],
			$where['format']
		);
	}

	/**
	 * Update notifications using a list of ids/items_ids.
	 *
	 * @since 10.0.0
	 *
	 * @global wpdb $wpdb The WordPress database object.
	 *
	 * @param string $field The name of the db field of the items to update.
	 *                      Possible values are `id` or `item_id`.
	 * @param int[]  $items The list of items to update.
	 * @param array  $data  Array of notification data to update.
	 * @param array  $where The WHERE params to use to specify the item IDs to update.
	 * @return int|false    The number of updated rows. False on error.
	 */
	public static function update_id_list( $field, $items = array(), $data = array(), $where = array() ) {
		global $wpdb;
		$bp = buddypress();

		$supported_fields = array( 'id', 'item_id' );

		if ( false === in_array( $field, $supported_fields, true ) ) {
			return false;
		}

		if ( ! is_array( $items ) || ! is_array( $data ) || ! is_array( $where ) ) {
			return false;
		}

		$update_args = self::get_query_clauses( $data );
		$where_args  = self::get_query_clauses( $where );

		$fields     = array();
		$conditions = array();
		$values     = array();

		$_items       = implode( ',', wp_parse_id_list( $items ) );
		$conditions[] = "{$field} IN ({$_items})";

		foreach ( $update_args['data'] as $update_field => $value ) {
			$index  = array_search( $update_field, array_keys( $update_args['data'] ) );
			$format = $update_args['format'][ $index ];

			$fields[] = "{$update_field} = {$format}";
			$values[] = $value;
		}

		foreach ( $where_args['data'] as $where_field => $value ) {
			$index  = array_search( $where_field, array_keys( $where_args['data'] ) );
			$format = $where_args['format'][ $index ];

			$conditions[] = "{$where_field} = {$format}";
			$values[]     = $value;
		}

		$fields     = implode( ', ', $fields );
		$conditions = implode( ' AND ', $conditions );

		if ( 'item_id' === $field && isset( $where_args['data']['user_id'] ) ) {
			$where_args['item_ids'] = $items;
			$where_args['user_id']  = $where_args['data']['user_id'];
		} elseif ( 'id' === $field ) {
			$where_args['ids'] = $items;
		}

		/** This action is documented in bp-notifications/classes/class-bp-notifications-notification.php */
		do_action( 'bp_notification_before_update', $update_args, $where_args );

		return $wpdb->query( $wpdb->prepare( "UPDATE {$bp->notifications->table_name} SET {$fields} WHERE {$conditions}", $values ) );
	}

	/**
	 * Delete notifications.
	 *
	 * @since 1.9.0
	 *
	 * @see BP_Notifications_Notification::get() for a description of
	 *      accepted where arguments.
	 *
	 * @param array $args Associative array of columns/values, to determine
	 *                    which rows should be deleted.  Of the format
	 *                    array( 'item_id' => 7, 'component_action' => 'members', ).
	 * @return int|false Number of rows deleted on success, false on failure.
	 */
	public static function delete( $args = array() ) {
		$where = self::get_query_clauses( $args );

		/**
		 * Fires before the deletion of a notification item.
		 *
		 * @since 2.0.0
		 *
		 * @param array $args Associative array of columns/values, to determine
		 *                    which rows should be deleted. Of the format
		 *                    array( 'item_id' => 7, 'component_action' => 'members' ).
		 */
		do_action( 'bp_notification_before_delete', $args );

		return self::_delete( $where['data'], $where['format'] );
	}

	/**
	 * Delete notifications using a list of ids/items_ids.
	 *
	 * @since 10.0.0
	 *
	 * @global wpdb $wpdb The WordPress database object.
	 *
	 * @param string $field The name of the db field of the items to delete.
	 *                      Possible values are `id` or `item_id`.
	 * @param int[]  $items The list of items to delete.
	 * @param array  $args  The WHERE arguments to use to specify the item IDs to delete.
	 * @return int|false    The number of deleted rows. False on error.
	 */
	public static function delete_by_id_list( $field, $items = array(), $args = array() ) {
		global $wpdb;
		$bp = buddypress();

		$supported_fields = array( 'id', 'item_id' );

		if ( false === in_array( $field, $supported_fields, true ) ) {
			return false;
		}

		if ( ! is_array( $items ) || ! is_array( $args ) ) {
			return false;
		}

		$where = self::get_query_clauses( $args );

		$conditions = array();
		$values     = array();

		$_items       = implode( ',', wp_parse_id_list( $items ) );
		$conditions[] = "{$field} IN ({$_items})";

		foreach ( $where['data'] as $where_field => $value ) {
			$index  = array_search( $where_field, array_keys( $where['data'] ) );
			$format = $where['format'][ $index ];

			$conditions[] = "{$where_field} = {$format}";
			$values[]     = $value;
		}

		$conditions = implode( ' AND ', $conditions );

		if ( 'id' === $field ) {
			$args['id'] = $items;
		}

		/** This action is documented in bp-notifications/classes/class-bp-notifications-notification.php */
		do_action( 'bp_notification_before_delete', $args );

		if ( ! $values ) {
			return $wpdb->query( "DELETE FROM {$bp->notifications->table_name} WHERE {$conditions}" );
		}

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->notifications->table_name} WHERE {$conditions}", $values ) );
	}

	/** Convenience methods ***************************************************/

	/**
	 * Delete a single notification by ID.
	 *
	 * @since 1.9.0
	 *
	 * @see BP_Notifications_Notification::delete() for explanation of
	 *      return value.
	 *
	 * @param int $id ID of the notification item to be deleted.
	 * @return int|false True on success, false on failure.
	 */
	public static function delete_by_id( $id ) {
		return self::delete( array(
			'id' => $id,
		) );
	}

	/**
	 * Fetch all the notifications in the database for a specific user.
	 *
	 * @since 1.9.0
	 *
	 * @param int    $user_id ID of the user whose notifications are being
	 *                        fetched.
	 * @param string $status  Optional. Status of notifications to fetch.
	 *                        'is_new' to get only unread items, 'all' to get all.
	 * @return array Associative array of notification items.
	 */
	public static function get_all_for_user( $user_id, $status = 'is_new' ) {
		return self::get( array(
			'user_id' => $user_id,
			'is_new'  => 'is_new' === $status,
		) );
	}

	/**
	 * Fetch all the unread notifications in the database for a specific user.
	 *
	 * @since 1.9.0
	 *
	 * @param int $user_id ID of the user whose notifications are being
	 *                     fetched.
	 * @return array Associative array of unread notification items.
	 */
	public static function get_unread_for_user( $user_id = 0 ) {
		return self::get( array(
			'user_id' => $user_id,
			'is_new'  => true,
		) );
	}

	/**
	 * Fetch all the read notifications in the database for a specific user.
	 *
	 * @since 1.9.0
	 *
	 * @param int $user_id ID of the user whose notifications are being
	 *                     fetched.
	 * @return array Associative array of unread notification items.
	 */
	public static function get_read_for_user( $user_id = 0 ) {
		return self::get( array(
			'user_id' => $user_id,
			'is_new'  => false,
		) );
	}

	/**
	 * Get unread notifications for a user, in a pagination-friendly format.
	 *
	 * @since 1.9.0
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *     @type int    $user_id      ID of the user for whom the notifications are
	 *                                being fetched. Default: logged-in user ID.
	 *     @type bool   $is_new       Whether to limit the query to unread
	 *                                notifications. Default: true.
	 *     @type int    $page         Number of the page to return. Default: 1.
	 *     @type int    $per_page     Number of results to display per page.
	 *                                Default: 10.
	 *     @type string $search_terms Optional. A term to search against in
	 *                                the 'component_name' and 'component_action' columns.
	 * }
	 * @return array {
	 *     @type array $notifications Array of notification results.
	 *     @type int   $total         Count of all located notifications matching
	 *                                the query params.
	 * }
	 */
	public static function get_current_notifications_for_user( $args = array() ) {
		$r = bp_parse_args(
			$args,
			array(
				'user_id'      => bp_loggedin_user_id(),
				'is_new'       => true,
				'page'         => 1,
				'per_page'     => 25,
				'search_terms' => '',
			)
		);

		$notifications = self::get( $r );

		// Bail if no notifications.
		if ( empty( $notifications ) ) {
			return false;
		}

		$total_count = self::get_total_count( $r );

		return array( 'notifications' => &$notifications, 'total' => $total_count );
	}

	/** Mark ******************************************************************/

	/**
	 * Mark all user notifications as read.
	 *
	 * @since 1.9.0
	 *
	 * @param int    $user_id           The ID of the user who the notifications are for.
	 * @param int    $is_new            Mark as read (1) or unread (0).
	 * @param int    $item_id           Item ID being acted on.
	 * @param string $component_name    Name of component the notifications are for.
	 * @param string $component_action  Name of the component action.
	 * @param int    $secondary_item_id The ID of the secondary item.
	 * @return int|false False on failure to update. ID on success.
	 */
	public static function mark_all_for_user( $user_id, $is_new = 0, $item_id = 0, $component_name = '', $component_action = '', $secondary_item_id = 0 ) {

		// Values to be updated.
		$update_args = array(
			'is_new' => $is_new,
		);

		// WHERE clauses.
		$where_args = array(
			'user_id' => $user_id,
		);

		if ( ! empty( $item_id ) ) {
			$where_args['item_id'] = $item_id;
		}

		if ( ! empty( $component_name ) ) {
			$where_args['component_name'] = $component_name;
		}

		if ( ! empty( $component_action ) ) {
			$where_args['component_action'] = $component_action;
		}

		if ( ! empty( $secondary_item_id ) ) {
			$where_args['secondary_item_id'] = $secondary_item_id;
		}

		return self::update( $update_args, $where_args );
	}

	/**
	 * Mark all notifications from a user as read.
	 *
	 * @since 1.9.0
	 *
	 * @param int    $user_id           The ID of the user who the notifications are from.
	 * @param int    $is_new            Mark as read (1) or unread (0).
	 * @param string $component_name    Name of component the notifications are for.
	 * @param string $component_action  Name of the component action.
	 * @param int    $secondary_item_id The ID of the secondary item.
	 * @return int|false
	 */
	public static function mark_all_from_user( $user_id, $is_new = 0, $component_name = '', $component_action = '', $secondary_item_id = 0 ) {

		// Values to be updated.
		$update_args = array(
			'is_new' => $is_new,
		);

		// WHERE clauses.
		$where_args = array(
			'item_id' => $user_id,
		);

		if ( ! empty( $component_name ) ) {
			$where_args['component_name'] = $component_name;
		}

		if ( ! empty( $component_action ) ) {
			$where_args['component_action'] = $component_action;
		}

		if ( ! empty( $secondary_item_id ) ) {
			$where_args['secondary_item_id'] = $secondary_item_id;
		}

		return self::update( $update_args, $where_args );
	}

	/**
	 * Mark all notifications for all users as read by item id, and optional
	 * secondary item id, and component name and action.
	 *
	 * @since 1.9.0
	 *
	 * @param int    $item_id           The ID of the item associated with the
	 *                                  notifications.
	 * @param int    $is_new            Mark as read (1) or unread (0).
	 * @param string $component_name    The component that the notifications
	 *                                  are associated with.
	 * @param string $component_action  The action that the notifications
	 *                                  are associated with.
	 * @param int    $secondary_item_id Optional. ID of the secondary
	 *                                  associated item.
	 * @return int|false
	 */
	public static function mark_all_by_type( $item_id, $is_new = 0, $component_name = '', $component_action = '', $secondary_item_id = 0 ) {

		// Values to be updated.
		$update_args = array(
			'is_new' => $is_new,
		);

		// WHERE clauses.
		$where_args = array(
			'item_id' => $item_id,
		);

		if ( ! empty( $component_name ) ) {
			$where_args['component_name'] = $component_name;
		}

		if ( ! empty( $component_action ) ) {
			$where_args['component_action'] = $component_action;
		}

		if ( ! empty( $secondary_item_id ) ) {
			$where_args['secondary_item_id'] = $secondary_item_id;
		}

		return self::update( $update_args, $where_args );
	}

	/**
	 * Get a user's unread notifications, grouped by component and action.
	 *
	 * Multiple notifications of the same type (those that share the same component_name
	 * and component_action) are collapsed for formatting as "You have 5 pending
	 * friendship requests", etc. See bp_notifications_get_notifications_for_user().
	 * For a full-fidelity list of user notifications, use
	 * bp_notifications_get_all_notifications_for_user().
	 *
	 * @since 3.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $user_id ID of the user whose notifications are being fetched.
	 * @return array Notifications items for formatting into a list.
	 */
	public static function get_grouped_notifications_for_user( $user_id ) {
		global $wpdb;

		// Load BuddyPress.
		$bp = buddypress();

		// SELECT.
		$select_sql = "SELECT id, user_id, item_id, secondary_item_id, component_name, component_action, date_notified, is_new, COUNT(id) as total_count ";

		// FROM.
		$from_sql = "FROM {$bp->notifications->table_name} n ";

		// WHERE.
		$where_sql = self::get_where_sql( array(
			'user_id'        => $user_id,
			'is_new'         => 1,
			'component_name' => bp_notifications_get_registered_components(),
		), $select_sql, $from_sql );

		// GROUP
		$group_sql = "GROUP BY user_id, component_name, component_action";

		// SORT
		$order_sql = "ORDER BY date_notified desc";

		// Concatenate query parts.
		$sql = "{$select_sql} {$from_sql} {$where_sql} {$group_sql} {$order_sql}";

		// Return the queried results.
		return $wpdb->get_results( $sql );
	}
}
