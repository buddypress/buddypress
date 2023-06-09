<?php
/**
 * Signups Management class.
 *
 * @package BuddyPress
 * @subpackage coreClasses
 * @since 2.0.0
 */

/**
 * Class used to handle Signups.
 *
 * @since 2.0.0
 */
#[AllowDynamicProperties]
class BP_Signup {

	/**
	 * ID of the signup which the object relates to.
	 *
	 * @since 2.0.0
	 * @var integer
	 */
	public $id;

	/**
	 * ID of the signup which the object relates to.
	 *
	 * @since 2.0.0
	 * @var integer
	 */
	public $signup_id;

	/**
	 * The URL to the full size of the avatar for the user.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $avatar;

	/**
	 * The username for the user.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $user_login;

	/**
	 * The email for the user.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $user_email;

	/**
	 * The full name of the user.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $user_name;

	/**
	 * Metadata associated with the signup.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	public $meta;

	/**
	 * The registered date for the user.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $registered;

	/**
	 * The activation key for the user.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $activation_key;

	/**
	 * The activated date for the user.
	 *
	 * @since 10.0.0
	 * @var string
	 */
	public $activated;

	/**
	 * Whether the user account is activated or not.
	 *
	 * @since 10.0.0
	 * @var bool
	 */
	public $active;

	/**
	 * The date that the last activation email was sent.
	 *
	 * @since 10.0.0
	 * @var string
	 */
	public $date_sent;

	/**
	 * Was the last activation email sent in the last 24 hours?
	 *
	 * @since 10.0.0
	 * @var bool
	 */
	public $recently_sent;

	/**
	 * The number of activation emails sent to this user.
	 *
	 * @since 10.0.0
	 * @var int
	 */
	public $count_sent;

	/**
	 * The domain for the signup.
	 *
	 * @since 10.0.0
	 * @var string
	 */
	public $domain;

	/**
	 * The path for the signup.
	 *
	 * @since 10.0.0
	 * @var string
	 */
	public $path;

	/**
	 * The title for the signup.
	 *
	 * @since 10.0.0
	 * @var string
	 */
	public $title;


	/** Public Methods *******************************************************/

	/**
	 * Class constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param integer $signup_id The ID for the signup being queried.
	 */
	public function __construct( $signup_id = 0 ) {
		if ( ! empty( $signup_id ) ) {
			$this->id = $signup_id;
			$this->populate();
		}
	}

	/**
	 * Populate the instantiated class with data based on the signup_id provided.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The WordPress database object.
	 */
	public function populate() {
		global $wpdb;

		// Get BuddyPress.
		$bp = buddypress();

		// Check cache for signup data.
		$signup = wp_cache_get( $this->id, 'bp_signups' );

		// Cache missed, so query the DB.
		if ( false === $signup ) {
			$signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->members->table_name_signups} WHERE signup_id = %d", $this->id ) );

			wp_cache_set( $this->id, $signup, 'bp_signups' );
		}

		// No signup found so set the ID and bail.
		if ( empty( $signup ) || is_wp_error( $signup ) ) {
			$this->id = 0;
			return;
		}

		/*
		 * Add every db column to the object.
		 */
		$this->signup_id      = $this->id;
		$this->domain         = $signup->domain;
		$this->path           = $signup->path;
		$this->title          = $signup->title;
		$this->user_login     = $signup->user_login;
		$this->user_email     = $signup->user_email;
		$this->registered     = $signup->registered;
		$this->activated      = $signup->activated;
		$this->active         = (bool) $signup->active;
		$this->activation_key = $signup->activation_key;
		$this->meta           = maybe_unserialize( $signup->meta );

		// Add richness.
		$this->avatar    = get_avatar( $signup->user_email, 32 );
		$this->user_name = ! empty( $this->meta['field_1'] ) ? wp_unslash( $this->meta['field_1'] ) : '';

		// When was the activation email sent?
		if ( isset( $this->meta['sent_date'] ) && '0000-00-00 00:00:00' !== $this->meta['sent_date'] ) {
			$this->date_sent = $this->meta['sent_date'];

			// Sent date defaults to date of registration.
		} else {
			$this->date_sent = $signup->registered;
		}

		// How many times has the activation email been sent?
		if ( isset( $this->meta['count_sent'] ) ) {
			$this->count_sent = absint( $this->meta['count_sent'] );
		} else {
			/**
			 * Meta will not be set if this is a pre-10.0 signup.
			 * In this case, we assume that the count is 1.
			 */
			$this->count_sent = 1;
		}

		/**
		 * Calculate a diff between now & last time
		 * an activation link has been resent.
		 */
		$sent_at = mysql2date( 'U', $this->date_sent );
		$now     = current_time( 'timestamp', true );
		$diff    = $now - $sent_at;

		/**
		 * Set a boolean to track whether an activation link
		 * was sent in the last day.
		 */
		$this->recently_sent = $this->count_sent && ( $diff < 1 * DAY_IN_SECONDS );

	}

	/** Static Methods *******************************************************/

	/**
	 * Fetch signups based on parameters.
	 *
	 * @since 2.0.0
	 * @since 6.0.0 Added a list of allowed orderby parameters.
	 *
	 * @global wpdb $wpdb The WordPress database object.
	 *
	 * @param array $args {
	 *     The argument to retrieve desired signups.
	 *     @type int         $offset         Offset amount. Default 0.
	 *     @type int         $number         How many to fetch. Pass -1 to fetch all. Default 1.
	 *     @type bool|string $usersearch     Whether or not to search for a username. Default false.
	 *     @type string      $orderby        Order By parameter. Possible values are `signup_id`, `login`, `email`,
	 *                                       `registered`, `activated`. Default `signup_id`.
	 *     @type string      $order          Order direction. Default 'DESC'.
	 *     @type bool        $include        Whether or not to include more specific query params.
	 *     @type string      $activation_key Activation key to search for. If specified, all other
	 *                                       parameters will be ignored.
	 *     @type int|bool    $active         Pass 0 for inactive signups, 1 for active signups,
	 *                                       and `false` to ignore.
	 *     @type string      $user_login     Specific user login to return.
	 *     @type string      $fields         Which fields to return. Specify 'ids' to fetch a list of signups IDs.
	 *                                       Default: 'all' (return BP_Signup objects).
	 * }
	 * @return array {
	 *     @type array $signups Located signups. (IDs only if `fields` is set to `ids`.)
	 *     @type int   $total   Total number of signups matching params.
	 * }
	 */
	public static function get( $args = array() ) {
		global $wpdb;

		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'offset'         => 0,
				'number'         => 1,
				'usersearch'     => false,
				'orderby'        => 'signup_id',
				'order'          => 'DESC',
				'include'        => false,
				'activation_key' => '',
				'active'         => 0,
				'user_email'     => '',
				'user_login'     => '',
				'fields'         => 'all',
			),
			'bp_core_signups_get_args'
		);

		// Make sure the orderby clause is allowed.
		if ( ! in_array( $r['orderby'], array( 'login', 'email', 'registered', 'activated' ), true ) ) {
			$r['orderby'] = 'signup_id';
		}

		if ( 'login' === $r['orderby'] || 'email' === $r['orderby'] ) {
			$r['orderby'] = 'user_' . $r['orderby'];
		}

		$r['orderby'] = sanitize_title( $r['orderby'] );

		$sql = array(
			'select'     => "SELECT DISTINCT signup_id",
			'from'       => "{$bp->members->table_name_signups}",
			'where'      => array(),
			'orderby'    => '',
			'limit'      => '',
		);

		// Activation key trumps other parameters because it should be unique.
		if ( ! empty( $r['activation_key'] ) ) {
			$sql['where'][] = $wpdb->prepare( "activation_key = %s", $r['activation_key'] );

			// `Include` finds signups by ID.
		} else if ( ! empty( $r['include'] ) ) {

			$in             = implode( ',', wp_parse_id_list( $r['include'] ) );
			$sql['where'][] = "signup_id IN ({$in})";

			/**
			 * Finally, the general case where a variety of parameters
			 * can be used in combination to find signups.
			 */
		} else {
			// Active.
			if ( false !== $r['active'] ) {
				$sql['where'][] = $wpdb->prepare( "active = %d", absint( $r['active'] ) );
			}

			// Search terms.
			if ( ! empty( $r['usersearch'] ) ) {
				$search_terms_like = '%' . bp_esc_like( $r['usersearch'] ) . '%';
				$sql['where'][]    = $wpdb->prepare( "( user_login LIKE %s OR user_email LIKE %s OR meta LIKE %s )", $search_terms_like, $search_terms_like, $search_terms_like );
			}

			// User email.
			if ( ! empty( $r['user_email'] ) ) {
				$sql['where'][] = $wpdb->prepare( "user_email = %s", $r['user_email'] );
			}

			// User login.
			if ( ! empty( $r['user_login'] ) ) {
				$sql['where'][] = $wpdb->prepare( "user_login = %s", $r['user_login'] );
			}

			$order	        = bp_esc_sql_order( $r['order'] );
			$sql['orderby'] = "ORDER BY {$r['orderby']} {$order}";

			$number = intval( $r['number'] );
			if ( -1 !== $number ) {
				$sql['limit'] = $wpdb->prepare( "LIMIT %d, %d", absint( $r['offset'] ), $number );
			}
		}

		// Implode WHERE clauses.
		$sql['where'] = 'WHERE ' . implode( ' AND ', $sql['where'] );

		$paged_signups_sql = "{$sql['select']} FROM {$sql['from']} {$sql['where']} {$sql['orderby']} {$sql['limit']}";

		/**
		 * Filters the Signups paged query.
		 *
		 * @since 2.0.0
		 *
		 * @param string $value SQL statement.
		 * @param array  $sql   Array of SQL statement parts.
		 * @param array  $args  Array of original arguments for get() method.
		 * @param array  $r     Array of parsed arguments for get() method.
		 */
		$paged_signups_sql = apply_filters( 'bp_members_signups_paged_query', $paged_signups_sql, $sql, $args, $r );

		$cached = bp_core_get_incremented_cache( $paged_signups_sql, 'bp_signups' );
		if ( false === $cached ) {
			$paged_signup_ids = $wpdb->get_col( $paged_signups_sql );
			bp_core_set_incremented_cache( $paged_signups_sql, 'bp_signups', $paged_signup_ids );
		} else {
			$paged_signup_ids = $cached;
		}

		// We only want the IDs.
		if ( 'ids' === $r['fields'] ) {
			$paged_signups = array_map( 'intval', $paged_signup_ids );

		} else {
			$uncached_signup_ids = bp_get_non_cached_ids( $paged_signup_ids, 'bp_signups' );
			if ( $uncached_signup_ids ) {
				$signup_ids_sql      = implode( ',', array_map( 'intval', $uncached_signup_ids ) );
				$signup_data_objects = $wpdb->get_results( "SELECT * FROM {$bp->members->table_name_signups} WHERE signup_id IN ({$signup_ids_sql})" );
				foreach ( $signup_data_objects as $signup_data_object ) {
					wp_cache_set( $signup_data_object->signup_id, $signup_data_object, 'bp_signups' );
				}
			}

			$paged_signups = array();
			foreach ( $paged_signup_ids as $paged_signup_id ) {
				$paged_signups[] = new BP_Signup( $paged_signup_id );
			}
		}

		// Find the total number of signups in the results set.
		$total_signups_sql = "SELECT COUNT(DISTINCT signup_id) FROM {$sql['from']} {$sql['where']}";

		/**
		 * Filters the Signups count query.
		 *
		 * @since 2.0.0
		 *
		 * @param string $value SQL statement.
		 * @param array  $sql   Array of SQL statement parts.
		 * @param array  $args  Array of original arguments for get() method.
		 * @param array  $r     Array of parsed arguments for get() method.
		 */
		$total_signups_sql = apply_filters( 'bp_members_signups_count_query', $total_signups_sql, $sql, $args, $r );

		$cached = bp_core_get_incremented_cache( $total_signups_sql, 'bp_signups' );
		if ( false === $cached ) {
			$total_signups = (int) $wpdb->get_var( $total_signups_sql );
			bp_core_set_incremented_cache( $total_signups_sql, 'bp_signups', $total_signups );
		} else {
			$total_signups = (int) $cached;
		}

		return array( 'signups' => $paged_signups, 'total' => $total_signups );
	}

	/**
	 * Add a signup.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The WordPress database object.
	 *
	 * @param array $args {
	 *     Array of arguments for signup addition.
	 *     @type string     $domain         New user's domain.
	 *     @type string     $path           New user's path.
	 *     @type string     $title          New user's title.
	 *     @type string     $user_login     New user's user_login.
	 *     @type string     $user_email     New user's email address.
	 *     @type int|string $registered     Time the user registered.
	 *     @type string     $activation_key New user's activation key.
	 *     @type string     $meta           New user's user meta.
	 * }
	 * @return int|bool ID of newly created signup on success, false on failure.
	 */
	public static function add( $args = array() ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'domain'         => '',
				'path'           => '',
				'title'          => '',
				'user_login'     => '',
				'user_email'     => '',
				'registered'     => current_time( 'mysql', true ),
				'activation_key' => wp_generate_password( 32, false ),
				'meta'           => array(),
			),
			'bp_core_signups_add_args'
		);

		// Ensure that sent_date and count_sent are set in meta.
		if ( ! isset( $r['meta']['sent_date'] ) ) {
			$r['meta']['sent_date'] = '0000-00-00 00:00:00';
		}
		if ( ! isset( $r['meta']['count_sent'] ) ) {
			$r['meta']['count_sent'] = 0;
		}

		$r['meta'] = maybe_serialize( $r['meta'] );

		$inserted = $wpdb->insert(
			buddypress()->members->table_name_signups,
			$r,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $inserted ) {
			$retval = $wpdb->insert_id;
		} else {
			$retval = false;
		}

		/**
		 * Fires after adding a new BP_Signup.
		 *
		 * @since 10.0.0
		 *
		 * @param int|bool $retval ID of the BP_Signup just added.
		 * @param array    $r      Array of parsed arguments for add() method.
		 * @param array    $args   Array of original arguments for add() method.
		 */
		do_action( 'bp_core_signups_after_add', $retval, $r, $args );

		/**
		 * Filters the result of a signup addition.
		 *
		 * @since 2.0.0
		 *
		 * @param int|bool $retval Newly added signup ID on success, false on failure.
		 */
		return apply_filters( 'bp_core_signups_add', $retval );
	}

	/**
	 * Create a WP user at signup.
	 *
	 * Since BP 2.0, non-multisite configurations have stored signups in
	 * the same way as Multisite configs traditionally have: in the
	 * wp_signups table. However, because some plugins may be looking
	 * directly in the wp_users table for non-activated signups, we
	 * mirror signups there by creating "phantom" users, mimicking WP's
	 * default behavior.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The WordPress database object.
	 *
	 * @param string $user_login    User login string.
	 * @param string $user_password User password.
	 * @param string $user_email    User email address.
	 * @param array  $usermeta      Metadata associated with the signup.
	 * @return int User id.
	 */
	public static function add_backcompat( $user_login = '', $user_password = '', $user_email = '', $usermeta = array() ) {
		global $wpdb;

		$user_id = wp_insert_user(
			array(
				'user_login'   => $user_login,
				'user_pass'    => $user_password,
				'display_name' => sanitize_title( $user_login ),
				'user_email'   => $user_email
			)
		);

		if ( is_wp_error( $user_id ) || empty( $user_id ) ) {
			return $user_id;
		}

		// Update the user status to '2', ie "not activated"
		// (0 = active, 1 = spam, 2 = not active).
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET user_status = 2 WHERE ID = %d", $user_id ) );

		// WordPress creates these options automatically on
		// wp_insert_user(), but we delete them so that inactive
		// signups don't appear in various user counts.
		delete_user_option( $user_id, 'capabilities' );
		delete_user_option( $user_id, 'user_level'   );

		// Set any profile data.
		if ( bp_is_active( 'xprofile' ) ) {
			if ( ! empty( $usermeta['profile_field_ids'] ) ) {
				$profile_field_ids = explode( ',', $usermeta['profile_field_ids'] );

				foreach ( (array) $profile_field_ids as $field_id ) {
					if ( empty( $usermeta["field_{$field_id}"] ) ) {
						continue;
					}

					$current_field = $usermeta["field_{$field_id}"];
					xprofile_set_field_data( $field_id, $user_id, $current_field );

					/*
					 * Save the visibility level.
					 *
					 * Use the field's default visibility if not present, and 'public' if a
					 * default visibility is not defined.
					 */
					$key = "field_{$field_id}_visibility";
					if ( isset( $usermeta[ $key ] ) ) {
						$visibility_level = $usermeta[ $key ];
					} else {
						$vfield           = xprofile_get_field( $field_id, null, false );
						$visibility_level = isset( $vfield->default_visibility ) ? $vfield->default_visibility : 'public';
					}
					xprofile_set_field_visibility_level( $field_id, $user_id, $visibility_level );
				}
			}
		}

		/**
		 * Fires after adding a new WP User (backcompat).
		 *
		 * @since 10.0.0
		 *
		 * @param int $user_id ID of the WP_User just added.
		 */
		do_action( 'bp_core_signups_after_add_backcompat', $user_id );

		/**
		 * Filters the user ID for the backcompat functionality.
		 *
		 * @since 2.0.0
		 *
		 * @param int $user_id User ID being registered.
		 */
		return apply_filters( 'bp_core_signups_add_backcompat', $user_id );
	}

	/**
	 * Check a user status (from wp_users) on a non-multisite config.
	 *
	 * @since 2.0.0
	 *
	 * @param  int      $user_id ID of the user being checked.
	 * @return int|bool          The status if found, otherwise false.
	 */
	public static function check_user_status( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			return false;
		}

		$user        = get_user_by( 'id', $user_id );
		$user_status = $user->user_status;

		/**
		 * Filters the user status of a provided user ID.
		 *
		 * @since 2.0.0
		 *
		 * @param int $value User status of the provided user ID.
		 */
		return apply_filters( 'bp_core_signups_check_user_status', intval( $user_status ) );
	}

	/**
	 * Activate a signup.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The WordPress database object.
	 *
	 * @param string $key Activation key.
	 * @return bool True on success, false on failure.
	 */
	public static function validate( $key = '' ) {
		global $wpdb;

		if ( empty( $key ) ) {
			return;
		}

		$activated = $wpdb->update(
			// Signups table.
			buddypress()->members->table_name_signups,
			array(
				'active' => 1,
				'activated' => current_time( 'mysql', true ),
			),
			array(
				'activation_key' => $key,
			),
			// Data sanitization format.
			array(
				'%d',
				'%s',
			),
			// WHERE sanitization format.
			array(
				'%s',
			)
		);

		/**
		 * Filters the status of the activated user.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $activated Whether or not the activation was successful.
		 */
		return apply_filters( 'bp_core_signups_validate', $activated );
	}

	/**
	 * How many inactive signups do we have?
	 *
	 * @since 2.0.0
	 *
	 * @return int The number of signups.
	 */
	public static function count_signups() {
		$all_signups   = self::get(
			array(
				'fields' => 'ids',
			)
		);
		$count_signups = $all_signups['total'];

		/**
		 * Filters the total inactive signups.
		 *
		 * @since 2.0.0
		 *
		 * @param int $count_signups How many total signups there are.
		 */
		return apply_filters( 'bp_core_signups_count', (int) $count_signups );
	}

	/**
	 * Update the meta for a signup.
	 *
	 * This is the way we use to "trace" the last date an activation
	 * email was sent and how many times activation was sent.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The WordPress database object.
	 *
	 * @param array $args {
	 *     Array of arguments for the signup update.
	 *     @type int $signup_id User signup ID.
	 *     @type array $meta Meta to update.
	 * }
	 * @return int The signup id.
	 */
	public static function update( $args = array() ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'signup_id'  => 0,
				'meta'       => array(),
			),
			'bp_core_signups_update_args'
		);

		if ( empty( $r['signup_id'] ) || empty( $r['meta'] ) ) {
			return false;
		}

		$signup_id = absint( $r['signup_id'] );

		// Figure out which meta keys should be updated.
		$signup       = new BP_Signup( $signup_id );
		$blended_meta = bp_parse_args(
			$r['meta'],
			$signup->meta
		);

		$wpdb->update(
			// Signups table.
			buddypress()->members->table_name_signups,
			// Data to update.
			array(
				'meta' => serialize( $blended_meta ),
			),
			// WHERE.
			array(
				'signup_id' => $signup_id,
			),
			// Data sanitization format.
			array(
				'%s',
			),
			// WHERE sanitization format.
			array(
				'%d',
			)
		);

		/**
		 * Fires after updating the meta of a new BP_Signup.
		 *
		 * @since 10.0.0
		 *
		 * @param int|bool $signup_id    ID of the BP_Signup updated.
		 * @param array    $r            Array of parsed arguments to update() method.
		 * @param array    $args         Array of original arguments to update() method.
		 * @param array    $blended_meta The complete set of meta to save.
		 */
		do_action( 'bp_core_signups_after_update_meta', $signup_id, $r, $args, $blended_meta );

		/**
		 * Filters the signup ID which received a meta update.
		 *
		 * @since 2.0.0
		 *
		 * @param int $value The signup ID.
		 */
		return apply_filters( 'bp_core_signups_update', $r['signup_id'] );
	}

	/**
	 * Resend an activation email.
	 *
	 * @since 2.0.0
	 *
	 * @param array $signup_ids Single ID or list of IDs to resend.
	 * @return array
	 */
	public static function resend( $signup_ids = array() ) {
		if ( empty( $signup_ids ) || ! is_array( $signup_ids ) ) {
			return false;
		}

		$to_resend = self::get(
			array(
				'include' => $signup_ids,
			)
		);

		if ( ! $signups = $to_resend['signups'] ) {
			return false;
		}

		$result = array();

		/**
		 * Fires before activation emails are resent.
		 *
		 * @since 2.0.0
		 *
		 * @param array $signup_ids Array of IDs to resend activation emails to.
		 */
		do_action( 'bp_core_signup_before_resend', $signup_ids );

		foreach ( $signups as $signup ) {

			$meta               = $signup->meta;
			$meta['sent_date']  = current_time( 'mysql', true );
			$meta['count_sent'] = $signup->count_sent + 1;

			// Send activation email.
			if ( is_multisite() ) {
				// Should we send the user or blog activation email?
				if ( ! empty( $signup->domain ) || ! empty( $signup->path ) ) {
					wpmu_signup_blog_notification( $signup->domain, $signup->path, $signup->title, $signup->user_login, $signup->user_email, $signup->activation_key, $meta );
				} else {
					wpmu_signup_user_notification( $signup->user_login, $signup->user_email, $signup->activation_key, $meta );
				}
			} else {

				// Check user status before sending email.
				$user_id = email_exists( $signup->user_email );

				if ( ! empty( $user_id ) && 2 != self::check_user_status( $user_id ) ) {

					// Status is not 2, so user's account has been activated.
					$result['errors'][ $signup->signup_id ] = array( $signup->user_login, esc_html__( 'the sign-up has already been activated.', 'buddypress' ) );

					// Repair signups table.
					self::validate( $signup->activation_key );

					continue;

				// Send the validation email.
				} else {
					$salutation = $signup->user_login;
					if ( bp_is_active( 'xprofile' ) && isset( $meta[ 'field_' . bp_xprofile_fullname_field_id() ] ) ) {
						$salutation = $meta[ 'field_' . bp_xprofile_fullname_field_id() ];
					}

					bp_core_signup_send_validation_email( false, $signup->user_email, $signup->activation_key, $salutation );
				}
			}

			// Update metas.
			$result['resent'][] = self::update(
				array(
					'signup_id' => $signup->signup_id,
					'meta'      => $meta,
				)
			);
		}

		/**
		 * Fires after activation emails are resent.
		 *
		 * @since 2.0.0
		 *
		 * @param array $signup_ids Array of IDs to resend activation emails to.
		 * @param array $result     Updated metadata related to activation emails.
		 */
		do_action( 'bp_core_signup_after_resend', $signup_ids, $result );

		/**
		 * Filters the result of the metadata for signup activation email resends.
		 *
		 * @since 2.0.0
		 *
		 * @param array $result Updated metadata related to activation emails.
		 */
		return apply_filters( 'bp_core_signup_resend', $result );
	}

	/**
	 * Activate a pending account.
	 *
	 * @since 2.0.0
	 *
	 * @param array $signup_ids Single ID or list of IDs to activate.
	 * @return array
	 */
	public static function activate( $signup_ids = array() ) {
		if ( empty( $signup_ids ) || ! is_array( $signup_ids ) ) {
			return false;
		}

		$to_activate = self::get(
			array(
				'include' => $signup_ids,
			)
		);

		if ( ! $signups = $to_activate['signups'] ) {
			return false;
		}

		$result = array();

		/**
		 * Fires before activation of user accounts.
		 *
		 * @since 2.0.0
		 *
		 * @param array $signup_ids Array of IDs to activate.
		 */
		do_action( 'bp_core_signup_before_activate', $signup_ids );

		foreach ( $signups as $signup ) {

			$user = bp_core_activate_signup( $signup->activation_key );

			if ( ! empty( $user->errors ) ) {

				$user_id = username_exists( $signup->user_login );

				if ( 2 !== self::check_user_status( $user_id ) ) {
					$user_id = false;
				}

				if ( empty( $user_id ) ) {

					// Status is not 2, so user's account has been activated.
					$result['errors'][ $signup->signup_id ] = array( $signup->user_login, esc_html__( 'the sign-up has already been activated.', 'buddypress' ) );

					// Repair signups table.
					self::validate( $signup->activation_key );

				// We have a user id, account is not active, let's delete it.
				} else {
					$result['errors'][ $signup->signup_id ] = array( $signup->user_login, $user->get_error_message() );
				}

			} else {
				$result['activated'][] = $user;
			}
		}

		/**
		 * Fires after activation of user accounts.
		 *
		 * @since 2.0.0
		 *
		 * @param array $signup_ids Array of IDs activated activate.
		 * @param array $result     Array of data for activated accounts.
		 */
		do_action( 'bp_core_signup_after_activate', $signup_ids, $result );

		/**
		 * Filters the result of the metadata after user activation.
		 *
		 * @since 2.0.0
		 *
		 * @param array $result Updated metadata related to user activation.
		 */
		return apply_filters( 'bp_core_signup_activate', $result );
	}

	/**
	 * Delete a pending account.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The WordPress database object.
	 *
	 * @param array $signup_ids Single ID or list of IDs to delete.
	 * @return array
	 */
	public static function delete( $signup_ids = array() ) {
		global $wpdb;

		if ( empty( $signup_ids ) || ! is_array( $signup_ids ) ) {
			return false;
		}

		$to_delete = self::get(
			array(
				'include' => $signup_ids,
			)
		);

		if ( ! $signups = $to_delete['signups'] ) {
			return false;
		}

		$result = array();

		/**
		 * Fires before deletion of pending accounts.
		 *
		 * @since 2.0.0
		 *
		 * @param array $signup_ids Array of pending IDs to delete.
		 */
		do_action( 'bp_core_signup_before_delete', $signup_ids );

		foreach ( $signups as $signup ) {
			$user_id = username_exists( $signup->user_login );

			if ( ! empty( $user_id ) && $signup->activation_key === bp_get_user_meta( $user_id, 'activation_key', true ) ) {

				if ( 2 != self::check_user_status( $user_id ) ) {

					// Status is not 2, so user's account has been activated.
					$result['errors'][ $signup->signup_id ] = array( $signup->user_login, esc_html__( 'the sign-up has already been activated.', 'buddypress' ) );

					// Repair signups table.
					self::validate( $signup->activation_key );

				// We have a user id, account is not active, let's delete it.
				} else {
					bp_core_delete_account( $user_id );
				}
			}

			if ( empty( $result['errors'][ $signup->signup_id ] ) ) {
				$wpdb->delete(
					// Signups table.
					buddypress()->members->table_name_signups,
					// Where.
					array( 'signup_id' => $signup->signup_id, ),
					// WHERE sanitization format.
					array( '%d', )
				);

				$result['deleted'][] = $signup->signup_id;
			}
		}

		/**
		 * Fires after deletion of pending accounts.
		 *
		 * @since 2.0.0
		 *
		 * @param array $signup_ids Array of pending IDs to delete.
		 * @param array $result     Array of data for deleted accounts.
		 */
		do_action( 'bp_core_signup_after_delete', $signup_ids, $result );

		/**
		 * Filters the result of the metadata for deleted pending accounts.
		 *
		 * @since 2.0.0
		 *
		 * @param array $result Updated metadata related to deleted pending accounts.
		 */
		return apply_filters( 'bp_core_signup_delete', $result );
	}
}
