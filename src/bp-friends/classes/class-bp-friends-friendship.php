<?php
/**
 * BuddyPress Friends Classes.
 *
 * @package BuddyPress
 * @subpackage FriendsFriendship
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Friendship object.
 *
 * @since 1.0.0
 */
class BP_Friends_Friendship {

	/**
	 * ID of the friendship.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $id;

	/**
	 * User ID of the friendship initiator.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $initiator_user_id;

	/**
	 * User ID of the 'friend' - the one invited to the friendship.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $friend_user_id;

	/**
	 * Has the friendship been confirmed/accepted?
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $is_confirmed;

	/**
	 * Is this a "limited" friendship?
	 *
	 * Not currently used by BuddyPress.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $is_limited;

	/**
	 * Date the friendship was created.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $date_created;

	/**
	 * Is this a request?
	 *
	 * Not currently used in BuddyPress.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $is_request;

	/**
	 * Should additional friend details be queried?
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $populate_friend_details;

	/**
	 * Details about the friend.
	 *
	 * @since 1.0.0
	 * @var BP_Core_User
	 */
	public $friend;

	/**
	 * Constructor method.
	 *
	 * @since 1.5.0
	 * @since 10.0.0 Updated to add deprecated notice for `$is_request`.
	 *
	 * @param int|null $id                      Optional. The ID of an existing friendship.
	 * @param bool     $is_request              Deprecated.
	 * @param bool     $populate_friend_details Optional. True if friend details should be queried.
	 */
	public function __construct( $id = null, $is_request = false, $populate_friend_details = true ) {

		if ( false !== $is_request ) {
			_deprecated_argument(
				__METHOD__,
				'1.5.0',
				sprintf(
					/* translators: 1: the name of the method. 2: the name of the file. */
					esc_html__( '%1$s no longer accepts $is_request. See the inline documentation at %2$s for more details.', 'buddypress' ),
					__METHOD__,
					__FILE__
				)
			);
		}

		$this->is_request = $is_request;

		if ( ! empty( $id ) ) {
			$this->id                      = (int) $id;
			$this->populate_friend_details = $populate_friend_details;
			$this->populate( $this->id );
		}
	}

	/**
	 * Set up data about the current friendship.
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 */
	public function populate() {
		global $wpdb;

		$bp = buddypress();

		// Check cache for friendship data.
		$friendship = wp_cache_get( $this->id, 'bp_friends_friendships' );

		// Cache missed, so query the DB.
		if ( false === $friendship ) {
			$friendship = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->friends->table_name} WHERE id = %d", $this->id ) );

			wp_cache_set( $this->id, $friendship, 'bp_friends_friendships' );
		}

		// No friendship found so set the ID and bail.
		if ( empty( $friendship ) || is_wp_error( $friendship ) ) {
			$this->id = 0;
			return;
		}

		$this->initiator_user_id = (int) $friendship->initiator_user_id;
		$this->friend_user_id    = (int) $friendship->friend_user_id;
		$this->is_confirmed      = (int) $friendship->is_confirmed;
		$this->is_limited        = (int) $friendship->is_limited;
		$this->date_created      = $friendship->date_created;

		if ( ! empty( $this->populate_friend_details ) ) {
			if ( bp_displayed_user_id() === $this->friend_user_id ) {
				$this->friend = new BP_Core_User( $this->initiator_user_id );
			} else {
				$this->friend = new BP_Core_User( $this->friend_user_id );
			}
		}
	}

	/**
	 * Save the current friendship to the database.
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save() {
		global $wpdb;

		$bp = buddypress();

		$this->initiator_user_id = apply_filters( 'friends_friendship_initiator_user_id_before_save', $this->initiator_user_id, $this->id );
		$this->friend_user_id    = apply_filters( 'friends_friendship_friend_user_id_before_save', $this->friend_user_id, $this->id );
		$this->is_confirmed      = apply_filters( 'friends_friendship_is_confirmed_before_save', $this->is_confirmed, $this->id );
		$this->is_limited        = apply_filters( 'friends_friendship_is_limited_before_save', $this->is_limited, $this->id );
		$this->date_created      = apply_filters( 'friends_friendship_date_created_before_save', $this->date_created, $this->id );

		/**
		 * Fires before processing and saving the current friendship request.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Friends_Friendship $value Current friendship object. Passed by reference.
		 */
		do_action_ref_array( 'friends_friendship_before_save', array( &$this ) );

		// Update.
		if ( ! empty( $this->id ) ) {
			$result = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->friends->table_name} SET initiator_user_id = %d, friend_user_id = %d, is_confirmed = %d, is_limited = %d, date_created = %s WHERE id = %d", $this->initiator_user_id, $this->friend_user_id, $this->is_confirmed, $this->is_limited, $this->date_created, $this->id ) );

		// Save.
		} else {
			$result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->friends->table_name} ( initiator_user_id, friend_user_id, is_confirmed, is_limited, date_created ) VALUES ( %d, %d, %d, %d, %s )", $this->initiator_user_id, $this->friend_user_id, $this->is_confirmed, $this->is_limited, $this->date_created ) );
			$this->id = $wpdb->insert_id;
		}

		/**
		 * Fires after processing and saving the current friendship request.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Friends_Friendship $value Current friendship object. Passed by reference.
		 */
		do_action_ref_array( 'friends_friendship_after_save', array( &$this ) );

		return $result;
	}

	/**
	 * Delete the current friendship from the database.
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return bool|int
	 */
	public function delete() {
		global $wpdb;

		$bp = buddypress();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->friends->table_name} WHERE id = %d", $this->id ) );
	}

	/** Static Methods ********************************************************/

	/**
	 * Get the friendships for a given user.
	 *
	 * @since 2.6.0
	 *
	 * @param int    $user_id  ID of the user whose friends are being retrieved.
	 * @param array  $args     {
	 *        Optional. Filter parameters.
	 *        @type int    $id                ID of specific friendship to retrieve.
	 *        @type int    $initiator_user_id ID of friendship initiator.
	 *        @type int    $friend_user_id    ID of specific friendship to retrieve.
	 *        @type int    $is_confirmed      Whether the friendship has been accepted.
	 *        @type int    $is_limited        Whether the friendship is limited.
	 *        @type string $order_by          Column name to order by.
	 *        @type string $sort_order        Optional. ASC or DESC. Default: 'DESC'.
	 * }
	 * @param string $operator Optional. Operator to use in `wp_list_filter()`.
	 *
	 * @return array $friendships Array of friendship objects.
	 */
	public static function get_friendships( $user_id, $args = array(), $operator = 'AND' ) {

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		$friendships = array();
		$operator    = strtoupper( $operator );

		if ( ! in_array( $operator, array( 'AND', 'OR', 'NOT' ), true ) ) {
			return $friendships;
		}

		$r = bp_parse_args(
			$args,
			array(
				'id'                => null,
				'initiator_user_id' => null,
				'friend_user_id'    => null,
				'is_confirmed'      => null,
				'is_limited'        => null,
				'order_by'          => 'date_created',
				'sort_order'        => 'DESC',
				'page'              => null,
				'per_page'          => null,
			),
			'bp_get_user_friendships'
		);

		// First, we get all friendships that involve the user.
		$friendship_ids = wp_cache_get( $user_id, 'bp_friends_friendships_for_user' );
		if ( false === $friendship_ids ) {
			$friendship_ids = self::get_friendship_ids_for_user( $user_id );
			wp_cache_set( $user_id, $friendship_ids, 'bp_friends_friendships_for_user' );
		}

		// Prime the membership cache.
		$uncached_friendship_ids = bp_get_non_cached_ids( $friendship_ids, 'bp_friends_friendships' );
		if ( ! empty( $uncached_friendship_ids ) ) {
			$uncached_friendships = self::get_friendships_by_id( $uncached_friendship_ids );

			foreach ( $uncached_friendships as $uncached_friendship ) {
				wp_cache_set( $uncached_friendship->id, $uncached_friendship, 'bp_friends_friendships' );
			}
		}

		$int_keys  = array( 'id', 'initiator_user_id', 'friend_user_id' );
		$bool_keys = array( 'is_confirmed', 'is_limited' );

		// Assemble filter array.
		$filters = wp_array_slice_assoc( $r, array( 'id', 'initiator_user_id', 'friend_user_id', 'is_confirmed', 'is_limited' ) );
		foreach ( $filters as $filter_name => $filter_value ) {
			if ( is_null( $filter_value ) ) {
				unset( $filters[ $filter_name ] );
			} elseif ( in_array( $filter_name, $int_keys, true ) ) {
				$filters[ $filter_name ] = (int) $filter_value;
			} else {
				$filters[ $filter_name ] = (bool) $filter_value;
			}
		}

		// Populate friendship array from cache, and normalize.
		foreach ( $friendship_ids as $friendship_id ) {
			// Create a limited BP_Friends_Friendship object (don't fetch the user details).
			$friendship = new BP_Friends_Friendship( $friendship_id, false, false );

			// Sanity check.
			if ( ! isset( $friendship->id ) ) {
				continue;
			}

			// Integer values.
			foreach ( $int_keys as $index ) {
				$friendship->{$index} = intval( $friendship->{$index} );
			}

			// Boolean values.
			foreach ( $bool_keys as $index ) {
				$friendship->{$index} = (bool) $friendship->{$index};
			}

			// We need to support the same operators as wp_list_filter().
			if ( 'OR' === $operator || 'NOT' === $operator ) {
				$matched = 0;

				foreach ( $filters as $filter_name => $filter_value ) {
					if ( isset( $friendship->{$filter_name} ) && $filter_value === $friendship->{$filter_name} ) {
						$matched++;
					}
				}

				if ( ( 'OR' === $operator && $matched > 0 )
				  || ( 'NOT' === $operator && 0 === $matched ) ) {
					$friendships[ $friendship->id ] = $friendship;
				}

			} else {
				/*
				 * This is the more typical 'AND' style of filter.
				 * If any of the filters miss, we move on.
				 */
				foreach ( $filters as $filter_name => $filter_value ) {
					if ( ! isset( $friendship->{$filter_name} ) || $filter_value !== $friendship->{$filter_name} ) {
						continue 2;
					}
				}
				$friendships[ $friendship->id ] = $friendship;
			}

		}

		// Sort the results on a column name.
		if ( in_array( $r['order_by'], array( 'id', 'initiator_user_id', 'friend_user_id' ) ) ) {
			$friendships = bp_sort_by_key( $friendships, $r['order_by'], 'num', true );
		}

		// Adjust the sort direction of the results.
		if ( 'ASC' === bp_esc_sql_order( $r['sort_order'] ) ) {
			// `true` to preserve keys.
			$friendships = array_reverse( $friendships, true );
		}

		// Paginate the results.
		if ( $r['per_page'] && $r['page'] ) {
			$start       = ( $r['page'] - 1 ) * ( $r['per_page'] );
			$friendships = array_slice( $friendships, $start, $r['per_page'] );
		}

		return $friendships;
	}

	/**
	 * Get all friendship IDs for a user.
	 *
	 * @since 2.7.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $user_id ID of the user.
	 * @return array
	 */
	public static function get_friendship_ids_for_user( $user_id ) {
		global $wpdb;

		$bp = buddypress();

		$friendship_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->friends->table_name} WHERE (initiator_user_id = %d OR friend_user_id = %d) ORDER BY date_created DESC", $user_id, $user_id ) );

		return $friendship_ids;
	}

	/**
	 * Get the IDs of a given user's friends.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $user_id              ID of the user whose friends are being retrieved.
	 * @param bool $friend_requests_only Optional. Whether to fetch
	 *                                   unaccepted requests only. Default: false.
	 * @param bool $assoc_arr            Optional. True to receive an array of arrays
	 *                                   keyed as 'user_id' => $user_id; false to get a one-dimensional
	 *                                   array of user IDs. Default: false.
	 * @return array $fids IDs of friends for provided user.
	 */
	public static function get_friend_user_ids( $user_id, $friend_requests_only = false, $assoc_arr = false ) {

		if ( ! empty( $friend_requests_only ) ) {
			$args = array(
				'is_confirmed'   => 0,
				'friend_user_id' => $user_id,
			);
		} else {
			$args = array(
				'is_confirmed' => 1,
			);
		}

		$friendships = self::get_friendships( $user_id, $args );
		$user_id     = (int) $user_id;

		$fids = array();
		foreach ( $friendships as $friendship ) {
			$friend_id = $friendship->friend_user_id;
			if ( $friendship->friend_user_id === $user_id ) {
				$friend_id = $friendship->initiator_user_id;
			}

			if ( ! empty( $assoc_arr ) ) {
				$fids[] = array( 'user_id' => $friend_id );
			} else {
				$fids[] = $friend_id;
			}
		}

		return array_map( 'intval', $fids );
	}

	/**
	 * Get the ID of the friendship object, if any, between a pair of users.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id   The ID of the first user.
	 * @param int $friend_id The ID of the second user.
	 * @return int|null The ID of the friendship object if found, otherwise null.
	 */
	public static function get_friendship_id( $user_id, $friend_id ) {
		$friendship_id = null;

		// Can't friend yourself.
		if ( $user_id === $friend_id ) {
			return $friendship_id;
		}

		/*
		 * Find friendships where the possible_friend_userid is the
		 * initiator or friend.
		 */
		$args = array(
			'initiator_user_id' => $friend_id,
			'friend_user_id'    => $friend_id,
		);

		$result = self::get_friendships( $user_id, $args, 'OR' );
		if ( $result ) {
			$friendship_id = current( $result )->id;
		}
		return $friendship_id;
	}

	/**
	 * Get a list of IDs of users who have requested friendship of a given user.
	 *
	 * @since 1.2.0
	 *
	 * @param int $user_id The ID of the user who has received the
	 *                     friendship requests.
	 * @return array|bool An array of user IDs or false if none are found.
	 */
	public static function get_friendship_request_user_ids( $user_id ) {
		$friend_requests = wp_cache_get( $user_id, 'bp_friends_requests' );

		if ( false === $friend_requests ) {
			$friend_requests = self::get_friend_user_ids( $user_id, true );

			wp_cache_set( $user_id, $friend_requests, 'bp_friends_requests' );
		}

		// Integer casting.
		if ( ! empty( $friend_requests ) ) {
			$friend_requests = array_map( 'intval', $friend_requests );
		}

		return $friend_requests;
	}

	/**
	 * Get a total friend count for a given user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id Optional. ID of the user whose friendships you
	 *                     are counting. Default: displayed user (if any), otherwise
	 *                     logged-in user.
	 * @return int Friend count for the user.
	 */
	public static function total_friend_count( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
		}

		/*
		 * This is stored in 'total_friend_count' usermeta.
		 * This function will recalculate, update and return.
		 */

		$args        = array( 'is_confirmed' => 1 );
		$friendships = self::get_friendships( $user_id, $args );
		$count       = count( $friendships );

		// Do not update meta if user has never had friends.
		if ( ! $count && ! bp_get_user_meta( $user_id, 'total_friend_count', true ) ) {
			return 0;
		}

		bp_update_user_meta( $user_id, 'total_friend_count', (int) $count );

		return absint( $count );
	}

	/**
	 * Search the friends of a user by a search string.
	 *
	 * @todo Optimize this function.
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param string   $filter  The search string, matched against xprofile
	 *                        fields (if available), or usermeta 'nickname' field.
	 * @param int      $user_id ID of the user whose friends are being searched.
	 * @param int|null $limit   Optional. Max number of friends to return.
	 * @param int|null $page    Optional. The page of results to return. Default:
	 *                          null (no pagination - return all results).
	 * @return array|bool On success, an array: {
	 *     @type array $friends IDs of friends returned by the query.
	 *     @type int   $count   Total number of friends (disregarding
	 *                          pagination) who match the search.
	 * }. Returns false on failure.
	 */
	public static function search_friends( $filter, $user_id, $limit = null, $page = null ) {
		global $wpdb;

		$bp = buddypress();

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		// Only search for matching strings at the beginning of the
		// name (@todo - figure out why this restriction).
		$search_terms_like = bp_esc_like( $filter ) . '%';

		$pag_sql = '';
		if ( ! empty( $limit ) && ! empty( $page ) ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		$friend_ids = self::get_friend_user_ids( $user_id );
		if ( ! $friend_ids ) {
			return false;
		}

		// Get all the user ids for the current user's friends.
		$fids = implode( ',', wp_parse_id_list( $friend_ids ) );

		if ( empty( $fids ) ) {
			return false;
		}

		// Filter the user_ids based on the search criteria.
		if ( bp_is_active( 'xprofile' ) ) {
			$sql       = $wpdb->prepare( "SELECT DISTINCT user_id FROM {$bp->profile->table_name_data} WHERE user_id IN ({$fids}) AND value LIKE %s {$pag_sql}", $search_terms_like );
			$total_sql = $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM {$bp->profile->table_name_data} WHERE user_id IN ({$fids}) AND value LIKE %s", $search_terms_like );
		} else {
			$sql       = $wpdb->prepare( "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE user_id IN ({$fids}) AND meta_key = 'nickname' AND meta_value LIKE %s {$pag_sql}", $search_terms_like );
			$total_sql = $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE user_id IN ({$fids}) AND meta_key = 'nickname' AND meta_value LIKE %s", $search_terms_like );
		}

		$filtered_friend_ids = $wpdb->get_col( $sql );
		$total_friend_ids    = $wpdb->get_var( $total_sql );

		if ( empty( $filtered_friend_ids ) ) {
			return false;
		}

		return array(
			'friends' => array_map( 'intval', $filtered_friend_ids ),
			'total'   => (int) $total_friend_ids,
		);
	}

	/**
	 * Check friendship status between two users.
	 *
	 * Note that 'pending' means that $initiator_userid has sent a friend
	 * request to $possible_friend_userid that has not yet been approved,
	 * while 'awaiting_response' is the other way around ($possible_friend_userid
	 * sent the initial request).
	 *
	 * @since 1.0.0
	 *
	 * @param int $initiator_userid       The ID of the user who is the initiator
	 *                                    of the potential friendship/request.
	 * @param int $possible_friend_userid The ID of the user who is the
	 *                                    recipient of the potential friendship/request.
	 * @return string|false $value The friendship status, from among 'not_friends',
	 *                             'is_friend', 'pending', and 'awaiting_response'.
	 */
	public static function check_is_friend( $initiator_userid, $possible_friend_userid ) {

		if ( empty( $initiator_userid ) || empty( $possible_friend_userid ) ) {
			return false;
		}

		// Can't friend yourself.
		if ( (int) $initiator_userid === (int) $possible_friend_userid ) {
			return 'not_friends';
		}

		self::update_bp_friends_cache( $initiator_userid, $possible_friend_userid );

		return bp_core_get_incremented_cache( $initiator_userid . ':' . $possible_friend_userid, 'bp_friends' );
	}

	/**
	 * Find uncached friendships between a user and one or more other users and cache them.
	 *
	 * @since 3.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int              $user_id             The ID of the primary user for whom we want
	 *                                              to check friendships statuses.
	 * @param int|array|string $possible_friend_ids The IDs of the one or more users
	 *                                              to check friendship status with primary user.
	 */
	public static function update_bp_friends_cache( $user_id, $possible_friend_ids ) {
		global $wpdb;

		$bp                  = buddypress();
		$user_id             = (int) $user_id;
		$possible_friend_ids = wp_parse_id_list( $possible_friend_ids );

		$fetch = array();
		foreach ( $possible_friend_ids as $friend_id ) {
			// Check for cached items in both friendship directions.
			if ( false === bp_core_get_incremented_cache( $user_id . ':' . $friend_id, 'bp_friends' )
				|| false === bp_core_get_incremented_cache( $friend_id . ':' . $user_id, 'bp_friends' ) ) {
				$fetch[] = $friend_id;
			}
		}

		if ( empty( $fetch ) ) {
			return;
		}

		$friend_ids_sql = implode( ',', array_unique( $fetch ) );
		$sql = $wpdb->prepare( "SELECT initiator_user_id, friend_user_id, is_confirmed FROM {$bp->friends->table_name} WHERE (initiator_user_id = %d AND friend_user_id IN ({$friend_ids_sql}) ) OR (initiator_user_id IN ({$friend_ids_sql}) AND friend_user_id = %d )", $user_id, $user_id );
		$friendships = $wpdb->get_results( $sql );

		// Use $handled to keep track of all of the $possible_friend_ids we've matched.
		$handled = array();
		foreach ( $friendships as $friendship ) {
			$initiator_user_id = (int) $friendship->initiator_user_id;
			$friend_user_id    = (int) $friendship->friend_user_id;
			if ( 1 === (int) $friendship->is_confirmed ) {
				$status_initiator = $status_friend = 'is_friend';
			} else {
				$status_initiator = 'pending';
				$status_friend    = 'awaiting_response';
			}
			bp_core_set_incremented_cache( $initiator_user_id . ':' . $friend_user_id, 'bp_friends', $status_initiator );
			bp_core_set_incremented_cache( $friend_user_id . ':' . $initiator_user_id, 'bp_friends', $status_friend );

			$handled[] = ( $initiator_user_id === $user_id ) ? $friend_user_id : $initiator_user_id;
		}

		// Set all those with no matching entry to "not friends" status.
		$not_friends = array_diff( $fetch, $handled );

		foreach ( $not_friends as $not_friend_id ) {
			bp_core_set_incremented_cache( $user_id . ':' . $not_friend_id, 'bp_friends', 'not_friends' );
			bp_core_set_incremented_cache( $not_friend_id . ':' . $user_id, 'bp_friends', 'not_friends' );
		}
	}

	/**
	 * Get the last active date of many users at once.
	 *
	 * @todo Why is this in the Friends component?
	 *
	 * @since 1.0.0
	 *
	 * @param array $user_ids IDs of users whose last_active meta is
	 *                        being queried.
	 * @return array $retval Array of last_active values + user_ids.
	 */
	public static function get_bulk_last_active( $user_ids ) {
		$last_activities = BP_Core_User::get_last_activity( $user_ids );

		// Sort and structure as expected in legacy function.
		usort( $last_activities, function( $a, $b ) {
			if ( $a['date_recorded'] === $b['date_recorded'] ) {
				return 0;
			}

			return ( strtotime( $a['date_recorded'] ) < strtotime( $b['date_recorded'] ) ) ? 1 : -1;
		} );

		$retval = array();
		foreach ( $last_activities as $last_activity ) {
			$u                = new stdClass();
			$u->last_activity = $last_activity['date_recorded'];
			$u->user_id       = $last_activity['user_id'];

			$retval[] = $u;
		}

		return $retval;
	}

	/**
	 * Mark a friendship as accepted.
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $friendship_id ID of the friendship to be accepted.
	 * @return int Number of database rows updated.
	 */
	public static function accept( $friendship_id ) {
		global $wpdb;

		$bp = buddypress();

		return $wpdb->query( $wpdb->prepare( "UPDATE {$bp->friends->table_name} SET is_confirmed = 1, date_created = %s WHERE id = %d AND friend_user_id = %d", bp_core_current_time(), $friendship_id, bp_loggedin_user_id() ) );
	}

	/**
	 * Remove a friendship or a friendship request INITIATED BY the logged-in user.
	 *
	 * @since 1.6.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $friendship_id ID of the friendship to be withdrawn.
	 * @return int Number of database rows deleted.
	 */
	public static function withdraw( $friendship_id ) {
		global $wpdb;

		$bp = buddypress();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->friends->table_name} WHERE id = %d AND initiator_user_id = %d", $friendship_id, bp_loggedin_user_id() ) );
	}

	/**
	 * Remove a friendship or a friendship request MADE OF the logged-in user.
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $friendship_id ID of the friendship to be rejected.
	 * @return int Number of database rows deleted.
	 */
	public static function reject( $friendship_id ) {
		global $wpdb;

		$bp = buddypress();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->friends->table_name} WHERE id = %d AND friend_user_id = %d", $friendship_id, bp_loggedin_user_id() ) );
	}

	/**
	 * Search users.
	 *
	 * @todo Why does this exist, and why is it in bp-friends?
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param string   $filter  String to search by.
	 * @param int      $user_id A user ID param that is unused.
	 * @param int|null $limit   Optional. Max number of records to return.
	 * @param int|null $page    Optional. Number of the page to return. Default:
	 *                          false (no pagination - return all results).
	 * @return array $filtered_ids IDs of users who match the query.
	 */
	public static function search_users( $filter, $user_id, $limit = null, $page = null ) {
		global $wpdb;

		// Only search for matching strings at the beginning of the
		// name (@todo - figure out why this restriction).
		$search_terms_like = bp_esc_like( $filter ) . '%';

		$usermeta_table = $wpdb->base_prefix . 'usermeta';
		$users_table    = $wpdb->base_prefix . 'users';

		$pag_sql = '';
		if ( ! empty( $limit ) && ! empty( $page ) ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * intval( $limit ) ), intval( $limit ) );
		}

		$bp = buddypress();

		// Filter the user_ids based on the search criteria.
		if ( bp_is_active( 'xprofile' ) ) {
			$sql = $wpdb->prepare( "SELECT DISTINCT d.user_id as id FROM {$bp->profile->table_name_data} d, {$users_table} u WHERE d.user_id = u.id AND d.value LIKE %s ORDER BY d.value DESC {$pag_sql}", $search_terms_like );
		} else {
			$sql = $wpdb->prepare( "SELECT DISTINCT user_id as id FROM {$usermeta_table} WHERE meta_value LIKE %s ORDER BY d.value DESC {$pag_sql}", $search_terms_like );
		}

		$filtered_fids = $wpdb->get_col( $sql );

		if ( empty( $filtered_fids ) ) {
			return false;
		}

		return $filtered_fids;
	}

	/**
	 * Get a count of users who match a search term.
	 *
	 * @todo Why does this exist, and why is it in bp-friends?
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param string $filter Search term.
	 * @return int Count of users matching the search term.
	 */
	public static function search_users_count( $filter ) {
		global $wpdb;

		// Only search for matching strings at the beginning of the
		// name (@todo - figure out why this restriction).
		$search_terms_like = bp_esc_like( $filter ) . '%';

		$usermeta_table = $wpdb->prefix . 'usermeta';
		$users_table    = $wpdb->base_prefix . 'users';

		$bp = buddypress();

		// Filter the user_ids based on the search criteria.
		if ( bp_is_active( 'xprofile' ) ) {
			$sql = $wpdb->prepare( "SELECT COUNT(DISTINCT d.user_id) FROM {$bp->profile->table_name_data} d, {$users_table} u WHERE d.user_id = u.id AND d.value LIKE %s", $search_terms_like );
		} else {
			$sql = $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM {$usermeta_table} WHERE meta_value LIKE %s", $search_terms_like );
		}

		$user_count = $wpdb->get_col( $sql );

		if ( empty( $user_count ) ) {
			return false;
		}

		return $user_count[0];
	}

	/**
	 * Sort a list of user IDs by their display names.
	 *
	 * @todo Why does this exist, and why is it in bp-friends?
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param array $user_ids Array of user IDs.
	 * @return array|bool User IDs, sorted by the associated display names.
	 *                    False if XProfile component is not active.
	 */
	public static function sort_by_name( $user_ids ) {
		global $wpdb;

		if ( ! bp_is_active( 'xprofile' ) ) {
			return false;
		}

		$bp = buddypress();

		$user_ids = implode( ',', wp_parse_id_list( $user_ids ) );

		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$bp->profile->table_name_data} pd, {$bp->profile->table_name_fields} pf WHERE pf.id = pd.field_id AND pf.name = %s AND pd.user_id IN ( {$user_ids} ) ORDER BY pd.value ASC", bp_xprofile_fullname_field_name() ) );
	}

	/**
	 * Get a list of random friend IDs.
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $user_id       ID of the user whose friends are being retrieved.
	 * @param int $total_friends Optional. Number of random friends to get.
	 *                           Default: 5.
	 * @return array|false An array of random friend user IDs on success;
	 *                     false if none are found.
	 */
	public static function get_random_friends( $user_id, $total_friends = 5 ) {
		global $wpdb;

		$bp      = buddypress();
		$fids    = array();
		$sql     = $wpdb->prepare( "SELECT friend_user_id, initiator_user_id FROM {$bp->friends->table_name} WHERE (friend_user_id = %d || initiator_user_id = %d) && is_confirmed = 1 ORDER BY rand() LIMIT %d", $user_id, $user_id, $total_friends );
		$results = $wpdb->get_results( $sql );
		$user_id = (int) $user_id;

		for ( $i = 0, $count = count( $results ); $i < $count; ++$i ) {
			$friend_user_id    = (int) $results[ $i ]->friend_user_id;
			$initiator_user_id = (int) $results[ $i ]->initiator_user_id;

			if ( $friend_user_id === $user_id ) {
				$fids[] = $initiator_user_id;
			} else {
				$fids[] = $friend_user_id;
			}
		}

		// Remove duplicates.
		if ( count( $fids ) > 0 ) {
			return array_flip( array_flip( $fids ) );
		}

		return false;
	}

	/**
	 * Get a count of a user's friends who can be invited to a given group.
	 *
	 * Users can invite any of their friends except:
	 *
	 * - users who are already in the group
	 * - users who have a pending invite to the group
	 * - users who have been banned from the group
	 *
	 * @todo Need to do a group component check before using group functions.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id  ID of the user whose friends are being counted.
	 * @param int $group_id ID of the group friends are being invited to.
	 * @return bool|int False if group component is not active, and friend count.
	 */
	public static function get_invitable_friend_count( $user_id, $group_id ) {

		if ( ! bp_is_active( 'group' ) ) {
			return false;
		}

		// Setup some data we'll use below.
		$is_group_admin  = groups_is_user_admin( $user_id, $group_id );
		$friend_ids      = self::get_friend_user_ids( $user_id );
		$invitable_count = 0;

		for ( $i = 0, $count = count( $friend_ids ); $i < $count; ++$i ) {

			// If already a member, they cannot be invited again.
			if ( groups_is_user_member( (int) $friend_ids[ $i ], $group_id ) ) {
				continue;
			}

			// If user already has invite, they cannot be added.
			if ( groups_check_user_has_invite( (int) $friend_ids[ $i ], $group_id ) ) {
				continue;
			}

			// If user is not group admin and friend is banned, they cannot be invited.
			if ( ( false === $is_group_admin ) && groups_is_user_banned( (int) $friend_ids[ $i ], $group_id ) ) {
				continue;
			}

			$invitable_count++;
		}

		return $invitable_count;
	}

	/**
	 * Get friendship objects by ID (or an array of IDs).
	 *
	 * @since 2.7.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int|string|array $friendship_ids Single friendship ID or comma-separated/array list of friendship IDs.
	 * @return array
	 */
	public static function get_friendships_by_id( $friendship_ids ) {
		global $wpdb;

		$bp = buddypress();

		$friendship_ids = implode( ',', wp_parse_id_list( $friendship_ids ) );
		return $wpdb->get_results( "SELECT * FROM {$bp->friends->table_name} WHERE id IN ({$friendship_ids})" );
	}

	/**
	 * Get the friend user IDs for a given friendship.
	 *
	 * @since 1.0.0
	 *
	 * @param int $friendship_id ID of the friendship.
	 * @return null|stdClass
	 */
	public static function get_user_ids_for_friendship( $friendship_id ) {
		$friendship = new BP_Friends_Friendship( $friendship_id, false, false );

		if ( empty( $friendship->id ) ) {
			return null;
		}

		$retval                    = new StdClass();
		$retval->friend_user_id    = $friendship->friend_user_id;
		$retval->initiator_user_id = $friendship->initiator_user_id;

		return $retval;
	}

	/**
	 * Delete all friendships and friend notifications related to a user.
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $user_id ID of the user being expunged.
	 */
	public static function delete_all_for_user( $user_id ) {
		global $wpdb;

		$bp      = buddypress();
		$user_id = (int) $user_id;

		// Get all friendships, of any status, for the user.
		$friendships    = self::get_friendships( $user_id );
		$friend_ids     = array();
		$friendship_ids = array();
		foreach ( $friendships as $friendship ) {
			$friendship_ids[] = $friendship->id;
			if ( $friendship->is_confirmed ) {
				if ( $friendship->friend_user_id === $user_id ) {
					$friend_ids[] = $friendship->initiator_user_id;
				} else {
					$friend_ids[] = $friendship->friend_user_id;
				}
			}
		}

		// Delete the friendships from the database.
		if ( $friendship_ids ) {
			$friendship_ids_sql = implode( ',', wp_parse_id_list( $friendship_ids ) );
			$wpdb->query( "DELETE FROM {$bp->friends->table_name} WHERE id IN ({$friendship_ids_sql})" );
		}

		// Delete friend request notifications for members who have a
		// notification from this user.
		if ( bp_is_active( 'notifications' ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->notifications->table_name} WHERE component_name = 'friends' AND ( component_action = 'friendship_request' OR component_action = 'friendship_accepted' ) AND item_id = %d", $user_id ) );
		}

		// Clean up the friendships cache.
		foreach ( $friendship_ids as $friendship_id ) {
			wp_cache_delete( $friendship_id, 'bp_friends_friendships' );
		}

		// Loop through friend_ids to scrub user caches and update total count metas.
		foreach ( (array) $friend_ids as $friend_id ) {
			// Delete cached friendships.
			wp_cache_delete( $friend_id, 'bp_friends_friendships_for_user' );

			self::total_friend_count( $friend_id );
		}

		// Delete cached friendships.
		wp_cache_delete( $user_id, 'bp_friends_friendships_for_user' );
	}
}
