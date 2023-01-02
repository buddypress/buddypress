<?php
/**
 * BuddyPress Nonmember Opt-out Class
 *
 * @package BuddyPress
 * @subpackage Nonmember Opt-outs
 *
 * @since 8.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress opt-outs.
 *
 * Use this class to create, access, edit, or delete BuddyPress Nonmember Opt-outs.
 *
 * @since 8.0.0
 */
class BP_Optout {

	/**
	 * The opt-out ID.
	 *
	 * @since 8.0.0
	 * @access public
	 * @var int
	 */
	public $id;

	/**
	 * The hashed email address of the user that wishes to opt out of
	 * communications from this site.
	 *
	 * @since 8.0.0
	 * @access public
	 * @var string
	 */
	public $email_address;

	/**
	 * The ID of the user that generated the contact that resulted in the opt-out.
	 *
	 * @since 8.0.0
	 * @access public
	 * @var int
	 */
	public $user_id;

	/**
	 * The type of email contact that resulted in the opt-out.
	 * This should be one of the known BP_Email types.
	 *
	 * @since 8.0.0
	 * @access public
	 * @var string
	 */
	public $email_type;

	/**
	 * The date the opt-out was last modified.
	 *
	 * @since 8.0.0
	 * @access public
	 * @var string
	 */
	public $date_modified;

	/** Public Methods ****************************************************/

	/**
	 * Constructor method.
	 *
	 * @since 8.0.0
	 *
	 * @param int $id Optional. Provide an ID to access an existing
	 *        optout item.
	 */
	public function __construct( $id = 0 ) {
		if ( ! empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Get the opt-outs table name.
	 *
	 * @since 8.0.0
	 * @access public
	 * @return string
	 */
	public static function get_table_name() {
		return buddypress()->members->table_name_optouts;
	}

	/**
	 * Update or insert opt-out details into the database.
	 *
	 * @since 8.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save() {

		// Return value
		$retval = false;

		// Default data and format
		$data = array(
			'email_address_hash' => $this->email_address,
			'user_id'            => $this->user_id,
			'email_type'         => sanitize_key( $this->email_type ),
			'date_modified'      => $this->date_modified,
		);
		$data_format = array( '%s', '%d', '%s', '%s' );

		/**
		 * Fires before an opt-out is saved.
		 *
		 * @since 8.0.0
		 *
		 * @param BP_Optout object $this Characteristics of the opt-out to be saved.
		 */
		do_action_ref_array( 'bp_optout_before_save', array( &$this ) );

		// Update.
		if ( ! empty( $this->id ) ) {
			$result = self::_update( $data, array( 'ID' => $this->id ), $data_format, array( '%d' ) );
		// Insert.
		} else {
			$result = self::_insert( $data, $data_format );
		}

		// Set the opt-out ID if successful.
		if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
			global $wpdb;

			$this->id = $wpdb->insert_id;
			$retval   = $wpdb->insert_id;
		}

		wp_cache_delete( $this->id, 'bp_optouts' );

		/**
		 * Fires after an optout is saved.
		 *
		 * @since 8.0.0
		 *
		 * @param BP_optout object $this Characteristics of the opt-out just saved.
		 */
		do_action_ref_array( 'bp_optout_after_save', array( &$this ) );

		// Return the result.
		return $retval;
	}

	/**
	 * Fetch data for an existing opt-out from the database.
	 *
	 * @since 8.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 */
	public function populate() {
		global $wpdb;
		$optouts_table_name = $this->get_table_name();

		// Check cache for optout data.
		$optout = wp_cache_get( $this->id, 'bp_optouts' );

		// Cache missed, so query the DB.
		if ( false === $optout ) {
			$optout = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$optouts_table_name} WHERE id = %d", $this->id ) );
			wp_cache_set( $this->id, $optout, 'bp_optouts' );
		}

		// No optout found so set the ID and bail.
		if ( empty( $optout ) || is_wp_error( $optout ) ) {
			$this->id = 0;
			return;
		}

		$this->email_address = $optout->email_address_hash;
		$this->user_id       = (int) $optout->user_id;
		$this->email_type    = sanitize_key( $optout->email_type );
		$this->date_modified = $optout->date_modified;

	}

	/** Protected Static Methods ******************************************/

	/**
	 * Create an opt-out entry.
	 *
	 * @since 8.0.0
	 *
	 * @param array $data {
	 *     Array of optout data, passed to {@link wpdb::insert()}.
	 *	   @type string $email_address     The hashed email address of the user that wishes to opt out of
	 *                                     communications from this site.
	 *	   @type int    $user_id           The ID of the user that generated the contact that resulted in the opt-out.
	 * 	   @type string $email_type        The type of email contact that resulted in the opt-out.
	 * 	   @type string $date_modified     Date the opt-out was last modified.
	 * }
	 * @param array $data_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	protected static function _insert( $data = array(), $data_format = array() ) {
		global $wpdb;
		// We must lowercase and hash the email address at insert.
		$email                      = strtolower( $data['email_address_hash'] );
		$data['email_address_hash'] = wp_hash( $email );
		return $wpdb->insert( BP_Optout::get_table_name(), $data, $data_format );
	}

	/**
	 * Update opt-outs.
	 *
	 * @since 8.0.0
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $data         Array of opt-out data to update, passed to
	 *                            {@link wpdb::update()}. Accepts any property of a
	 *                            BP_optout object.
	 * @param array $where        The WHERE params as passed to wpdb::update().
	 *                            Typically consists of array( 'ID' => $id ) to specify the ID
	 *                            of the item being updated. See {@link wpdb::update()}.
	 * @param array $data_format  See {@link wpdb::insert()}.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _update( $data = array(), $where = array(), $data_format = array(), $where_format = array() ) {
		global $wpdb;

		// Ensure that a passed email address is lowercased and hashed.
		if ( ! empty( $data['email_address_hash'] ) && is_email( $data['email_address_hash'] ) ) {
			$email                      = strtolower( $data['email_address_hash'] );
			$data['email_address_hash'] = wp_hash( $email );
		}

		return $wpdb->update( BP_Optout::get_table_name(), $data, $where, $data_format, $where_format );
	}

	/**
	 * Delete opt-outs.
	 *
	 * @since 8.0.0
	 *
	 * @see wpdb::update() for further description of paramater formats.
	 *
	 * @param array $where        Array of WHERE clauses to filter by, passed to
	 *                            {@link wpdb::delete()}. Accepts any property of a
	 *                            BP_optout object.
	 * @param array $where_format See {@link wpdb::insert()}.
	 * @return int|false The number of rows updated, or false on error.
	 */
	protected static function _delete( $where = array(), $where_format = array() ) {
		global $wpdb;
		return $wpdb->delete( BP_Optout::get_table_name(), $where, $where_format );
	}

	/**
	 * Assemble the WHERE clause of a get() SQL statement.
	 *
	 * Used by BP_optout::get() to create its WHERE
	 * clause.
	 *
	 * @since 8.0.0
	 *
	 * @param array $args See {@link BP_optout::get()} for more details.
	 * @return string WHERE clause.
	 */
	protected static function get_where_sql( $args = array() ) {
		global $wpdb;

		$where_conditions = array();
		$where            = '';

		// id.
		if ( false !== $args['id'] ) {
			$id_in                  = implode( ',', wp_parse_id_list( $args['id'] ) );
			$where_conditions['id'] = "id IN ({$id_in})";
		}

		// email_address.
		if ( ! empty( $args['email_address'] ) ) {
			if ( ! is_array( $args['email_address'] ) ) {
				$emails = explode( ',', $args['email_address'] );
			} else {
				$emails = $args['email_address'];
			}

			$email_clean = array();
			foreach ( $emails as $email ) {
				$email         = strtolower( $email );
				$email_hash    = wp_hash( $email );
				$email_clean[] = $wpdb->prepare( '%s', $email_hash );
			}

			$email_in                          = implode( ',', $email_clean );
			$where_conditions['email_address'] = "email_address_hash IN ({$email_in})";
		}

		// user_id.
		if ( ! empty( $args['user_id'] ) ) {
			$user_id_in                  = implode( ',', wp_parse_id_list( $args['user_id'] ) );
			$where_conditions['user_id'] = "user_id IN ({$user_id_in})";
		}

		// email_type.
		if ( ! empty( $args['email_type'] ) ) {
			if ( ! is_array( $args['email_type'] ) ) {
				$email_types = explode( ',', $args['email_type'] );
			} else {
				$email_types = $args['email_type'];
			}

			$et_clean = array();
			foreach ( $email_types as $et ) {
				$et_clean[] = $wpdb->prepare( '%s', sanitize_key( $et ) );
			}

			$et_in                          = implode( ',', $et_clean );
			$where_conditions['email_type'] = "email_type IN ({$et_in})";
		}

		// search_terms.
		if ( ! empty( $args['search_terms'] ) ) {
			// Matching email_address is an exact match because of the hashing.
			$args['search_terms']             = strtolower( $args['search_terms'] );
			$search_terms_like                = wp_hash( $args['search_terms'] );
			$where_conditions['search_terms'] = $wpdb->prepare( '( email_address_hash LIKE %s )', $search_terms_like );
		}

		// Custom WHERE.
		if ( ! empty( $where_conditions ) ) {
			$where = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		return $where;
	}

	/**
	 * Assemble the ORDER BY clause of a get() SQL statement.
	 *
	 * Used by BP_Optout::get() to create its ORDER BY
	 * clause.
	 *
	 * @since 8.0.0
	 *
	 * @param array $args See {@link BP_optout::get()} for more details.
	 * @return string ORDER BY clause.
	 */
	protected static function get_order_by_sql( $args = array() ) {

		$conditions = array();
		$retval     = '';

		// Order by.
		if ( ! empty( $args['order_by'] ) ) {
			$order_by_clean = array();
			$columns        = array( 'id', 'email_address_hash', 'user_id', 'email_type', 'date_modified' );
			foreach ( (array) $args['order_by'] as $key => $value ) {
				if ( in_array( $value, $columns, true ) ) {
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
		if ( ! empty( $conditions['order_by'] ) ) {
			$retval = 'ORDER BY ' . implode( ' ', $conditions );
		}

		return $retval;
	}

	/**
	 * Assemble the LIMIT clause of a get() SQL statement.
	 *
	 * Used by BP_Optout::get() to create its LIMIT clause.
	 *
	 * @since 8.0.0
	 *
	 * @param array $args See {@link BP_optout::get()} for more details.
	 * @return string LIMIT clause.
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
	 *   be used in WHERE, SET, or VALUES clauses
	 * - arrays of "formats", which tell $wpdb->prepare() which type of
	 *   value to expect when sanitizing (eg, array( '%s', '%d' ))
	 *
	 * This utility method can be used to assemble both kinds of params,
	 * out of a single set of associative array arguments, such as:
	 *
	 *     $args = array(
	 *         'user_id'    => 4,
	 *         'email_type' => 'type_string',
	 *     );
	 *
	 * This will be converted to:
	 *
	 *     array(
	 *         'data' => array(
	 *             'user_id' => 4,
	 *             'email_type'   => 'type_string',
	 *         ),
	 *         'format' => array(
	 *             '%d',
	 *             '%s',
	 *         ),
	 *     )
	 *
	 * which can easily be passed as arguments to the $wpdb methods.
	 *
	 * @since 8.0.0
	 *
	 * @param array $args Associative array of filter arguments.
	 *                    See {@BP_optout::get()} for a breakdown.
	 * @return array Associative array of 'data' and 'format' args.
	 */
	protected static function get_query_clauses( $args = array() ) {
		$where_clauses = array(
			'data'   => array(),
			'format' => array(),
		);

		// id.
		if ( ! empty( $args['id'] ) ) {
			$where_clauses['data']['id'] = absint( $args['id'] );
			$where_clauses['format'][]   = '%d';
		}

		// email_address.
		if ( ! empty( $args['email_address'] ) ) {
			$where_clauses['data']['email_address_hash'] = $args['email_address'];
			$where_clauses['format'][]                   = '%s';
		}

		// user_id.
		if ( ! empty( $args['user_id'] ) ) {
			$where_clauses['data']['user_id'] = absint( $args['user_id'] );
			$where_clauses['format'][]        = '%d';
		}

		// email_type.
		if ( ! empty( $args['email_type'] ) ) {
			$where_clauses['data']['email_type'] = $args['email_type'];
			$where_clauses['format'][]           = '%s';
		}

		return $where_clauses;
	}

	/** Public Static Methods *********************************************/

	/**
	 * Get opt-outs, based on provided filter parameters.
	 *
	 * @since 8.0.0
	 *
	 * @param array $args {
	 *     Associative array of arguments. All arguments but $page and
	 *     $per_page can be treated as filter values for get_where_sql()
	 *     and get_query_clauses(). All items are optional.
	 *     @type int|array    $id                ID of opt-out.
	 *                                           Can be an array of IDs.
	 *     @type string|array $email_address     Email address of users who have opted out
	 *			                                 being queried. Can be an array of addresses.
	 *     @type int|array    $user_id           ID of user whose communication prompted the
	 *                                           opt-out. Can be an array of IDs.
	 *     @type string|array $email_type        Name of the emil type to filter by.
	 *                                           Can be an array of email types.
	 *     @type string       $search_terms      Term to match against email_address field.
	 *     @type string       $order_by          Database column to order by.
	 *     @type string       $sort_order        Either 'ASC' or 'DESC'.
	 *     @type int          $page              Number of the current page of results.
	 *                                           Default: false (no pagination,
	 *                                           all items).
	 *     @type int          $per_page          Number of items to show per page.
	 *                                           Default: false (no pagination,
	 *                                           all items).
  	 *     @type string       $fields            Which fields to return. Specify 'email_addresses' to
  	 *                                           fetch a list of opt-out email_addresses.
  	 *                                           Specify 'user_ids' to
  	 *                                           fetch a list of opt-out user_ids.
  	 *                                           Specify 'ids' to fetch a list of opt-out IDs.
 	 *                                           Default: 'all' (return BP_Optout objects).
	 * }
	 *
	 * @return array BP_Optout objects | IDs of found opt-outs | Email addresses of matches.
	 */
	public static function get( $args = array() ) {
		global $wpdb;
		$optouts_table_name = BP_Optout::get_table_name();

		// Parse the arguments.
		$r = bp_parse_args(
			$args,
			array(
				'id'            => false,
				'email_address' => false,
				'user_id'       => false,
				'email_type'    => false,
				'search_terms'  => '',
				'order_by'      => false,
				'sort_order'    => false,
				'page'          => false,
				'per_page'      => false,
				'fields'        => 'all',
			),
			'bp_optout_get'
		);

		$sql = array(
			'select'     => "SELECT",
			'fields'     => '',
			'from'       => "FROM {$optouts_table_name} o",
			'where'      => '',
			'orderby'    => '',
			'pagination' => '',
		);

		if ( 'user_ids' === $r['fields'] ) {
			$sql['fields'] = "DISTINCT o.user_id";
		} else if ( 'email_addresses' === $r['fields'] ) {
			$sql['fields'] = "DISTINCT o.email_address_hash";
		} else {
			$sql['fields'] = 'DISTINCT o.id';
		}

		// WHERE.
		$sql['where'] = self::get_where_sql(
			array(
				'id'            => $r['id'],
				'email_address' => $r['email_address'],
				'user_id'       => $r['user_id'],
				'email_type'    => $r['email_type'],
				'search_terms'  => $r['search_terms'],
			)
		);

		// ORDER BY.
		$sql['orderby'] = self::get_order_by_sql(
			array(
				'order_by'   => $r['order_by'],
				'sort_order' => $r['sort_order']
			)
		);

		// LIMIT %d, %d.
		$sql['pagination'] = self::get_paged_sql(
			array(
				'page'     => $r['page'],
				'per_page' => $r['per_page'],
			)
		);

		$paged_optouts_sql = "{$sql['select']} {$sql['fields']} {$sql['from']} {$sql['where']} {$sql['orderby']} {$sql['pagination']}";

		/**
		 * Filters the pagination SQL statement.
		 *
		 * @since 8.0.0
		 *
		 * @param string $value Concatenated SQL statement.
		 * @param array  $sql   Array of SQL parts before concatenation.
		 * @param array  $r     Array of parsed arguments for the get method.
		 */
		$paged_optouts_sql = apply_filters( 'bp_optouts_get_paged_optouts_sql', $paged_optouts_sql, $sql, $r );

		$cached = bp_core_get_incremented_cache( $paged_optouts_sql, 'bp_optouts' );
		if ( false === $cached ) {
			$paged_optout_ids = $wpdb->get_col( $paged_optouts_sql );
			bp_core_set_incremented_cache( $paged_optouts_sql, 'bp_optouts', $paged_optout_ids );
		} else {
			$paged_optout_ids = $cached;
		}

		// Special return format cases.
		if ( in_array( $r['fields'], array( 'ids', 'user_ids' ), true ) ) {
			// We only want the field that was found.
			return array_map( 'intval', $paged_optout_ids );
		} else if ( 'email_addresses' === $r['fields'] ) {
			return $paged_optout_ids;
		}

		$uncached_ids = bp_get_non_cached_ids( $paged_optout_ids, 'bp_optouts' );
		if ( $uncached_ids ) {
			$ids_sql = implode( ',', array_map( 'intval', $uncached_ids ) );
			$data_objects = $wpdb->get_results( "SELECT o.* FROM {$optouts_table_name} o WHERE o.id IN ({$ids_sql})" );
			foreach ( $data_objects as $data_object ) {
				wp_cache_set( $data_object->id, $data_object, 'bp_optouts' );
			}
		}

		$paged_optouts = array();
		foreach ( $paged_optout_ids as $paged_optout_id ) {
			$paged_optouts[] = new BP_optout( $paged_optout_id );
		}

		return $paged_optouts;
	}

	/**
	 * Get a count of total optouts matching a set of arguments.
	 *
	 * @since 8.0.0
	 *
	 * @see BP_optout::get() for a description of
	 *      arguments.
	 *
	 * @param array $args See {@link BP_optout::get()}.
	 * @return int Count of located items.
	 */
	public static function get_total_count( $args ) {
		global $wpdb;
		$optouts_table_name = BP_Optout::get_table_name();

		// Parse the arguments.
		$r  = bp_parse_args(
			$args,
			array(
				'id'            => false,
				'email_address' => false,
				'user_id'       => false,
				'email_type'    => false,
				'search_terms'  => '',
				'order_by'      => false,
				'sort_order'    => false,
				'page'          => false,
				'per_page'      => false,
				'fields'        => 'all',
			),
			'bp_optout_get_total_count'
		);

		// Build the query
		$select_sql = "SELECT COUNT(*)";
		$from_sql   = "FROM {$optouts_table_name}";
		$where_sql  = self::get_where_sql( $r );
		$sql        = "{$select_sql} {$from_sql} {$where_sql}";

		// Return the queried results
		return $wpdb->get_var( $sql );
	}

	/**
	 * Update optouts.
	 *
	 * @since 8.0.0
	 *
	 * @see BP_optout::get() for a description of
	 *      accepted update/where arguments.
	 *
	 * @param array $update_args Associative array of fields to update,
	 *                           and the values to update them to. Of the format
	 *                           array( 'user_id' => 4, 'email_address' => 'bar@foo.com', ).
	 * @param array $where_args  Associative array of columns/values, to
	 *                           determine which rows should be updated. Of the format
	 *                           array( 'user_id' => 7, 'email_address' => 'bar@foo.com', ).
	 * @return int|bool Number of rows updated on success, false on failure.
	 */
	public static function update( $update_args = array(), $where_args = array() ) {
		$update = self::get_query_clauses( $update_args );
		$where  = self::get_query_clauses( $where_args  );

		/**
		 * Fires before an opt-out is updated.
		 *
		 * @since 8.0.0
		 *
		 * @param array $where_args  Associative array of columns/values describing
		 *                           opt-outs about to be deleted.
		 * @param array $update_args Array of new values.
		 */
		do_action( 'bp_optout_before_update', $where_args, $update_args );

		$retval = self::_update( $update['data'], $where['data'], $update['format'], $where['format'] );

		// Clear matching items from the cache.
		$cache_args           = $where_args;
		$cache_args['fields'] = 'ids';
		$maybe_cached_ids     = self::get( $cache_args );
		foreach ( $maybe_cached_ids as $invite_id ) {
			wp_cache_delete( $invite_id, 'bp_optouts' );
		}

		/**
		 * Fires after an opt-out is updated.
		 *
		 * @since 8.0.0
		 *
		 * @param array $where_args  Associative array of columns/values describing
		 *                           opt-outs about to be deleted.
		 * @param array $update_args Array of new values.
		 */
		do_action( 'bp_optout_after_update', $where_args, $update_args );

  		return $retval;
	}

	/**
	 * Delete opt-outs.
	 *
	 * @since 8.0.0
	 *
	 * @see BP_optout::get() for a description of
	 *      accepted where arguments.
	 *
	 * @param array $args Associative array of columns/values, to determine
	 *                    which rows should be deleted.  Of the format
	 *                    array( 'user_id' => 7, 'email_address' => 'bar@foo.com', ).
	 * @return int|bool Number of rows deleted on success, false on failure.
	 */
	public static function delete( $args = array() ) {
		$where = self::get_query_clauses( $args );

		/**
		 * Fires before an opt-out is deleted.
		 *
		 * @since 8.0.0
		 *
		 * @param array $args Characteristics of the opt-outs to be deleted.
		 */
		do_action( 'bp_optout_before_delete', $args );

		// Clear matching items from the cache.
		$cache_args           = $args;
		$cache_args['fields'] = 'ids';
		$maybe_cached_ids     = self::get( $cache_args );
		foreach ( $maybe_cached_ids as $invite_id ) {
			wp_cache_delete( $invite_id, 'bp_optouts' );
		}

		$retval = self::_delete( $where['data'], $where['format'] );

		/**
		 * Fires after an opt-out is deleted.
		 *
		 * @since 8.0.0
		 *
		 * @param array $args Characteristics of the opt-outs just deleted.
		 */
		do_action( 'bp_optout_after_delete', $args );

		return $retval;
	}

	/** Convenience methods ***********************************************/

	/**
	 * Check whether an invitation exists matching the passed arguments.
	 *
	 * @since 5.0.0
	 *
	 * @see BP_Optout::get() for a description of accepted parameters.
	 *
	 * @return int|bool ID of first found invitation or false if none found.
	 */
	public function optout_exists( $args = array() ) {
		$exists = false;

		$args['fields'] = 'ids';
		$optouts        = BP_Optout::get( $args );
		if ( $optouts ) {
			$exists = current( $optouts );
		}

		return $exists;
	}

	/**
	 * Delete a single opt-out by ID.
	 *
	 * @since 8.0.0
	 *
	 * @see BP_optout::delete() for explanation of
	 *      return value.
	 *
	 * @param int $id ID of the opt-out item to be deleted.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_by_id( $id ) {
		return self::delete( array(
			'id' => $id,
		) );
	}
}
