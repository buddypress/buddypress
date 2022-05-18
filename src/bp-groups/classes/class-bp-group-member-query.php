<?php
/**
 * BuddyPress Groups Classes.
 *
 * @package BuddyPress
 * @subpackage GroupsClasses
 * @since 1.8.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Query for the members of a group.
 *
 * Special notes about the group members data schema:
 * - *Members* are entries with is_confirmed = 1.
 * - *Pending requests* are entries with is_confirmed = 0 and inviter_id = 0.
 * - *Pending and sent invitations* are entries with is_confirmed = 0 and
 *   inviter_id != 0 and invite_sent = 1.
 * - *Pending and unsent invitations* are entries with is_confirmed = 0 and
 *   inviter_id != 0 and invite_sent = 0.
 * - *Membership requests* are entries with is_confirmed = 0 and
 *   inviter_id = 0 (and invite_sent = 0).
 *
 * @since 1.8.0
 * @since 3.0.0 $group_id now supports multiple values.
 *
 * @param array $args  {
 *     Array of arguments. Accepts all arguments from
 *     {@link BP_User_Query}, with the following additions:
 *
 *     @type int|array|string $group_id     ID of the group to limit results to. Also accepts multiple values
 *                                          either as an array or as a comma-delimited string.
 *     @type array            $group_role   Array of group roles to match ('member', 'mod', 'admin', 'banned').
 *                                          Default: array( 'member' ).
 *     @type bool             $is_confirmed Whether to limit to confirmed members. Default: true.
 *     @type string           $type         Sort order. Accepts any value supported by {@link BP_User_Query}, in
 *                                          addition to 'last_joined' and 'first_joined'. Default: 'last_joined'.
 * }
 */
class BP_Group_Member_Query extends BP_User_Query {

	/**
	 * Array of group member ids, cached to prevent redundant lookups.
	 *
	 * @since 1.8.1
	 * @var null|array Null if not yet defined, otherwise an array of ints.
	 */
	protected $group_member_ids;

	/**
	 * Constructor.
	 *
	 * @since 10.3.0
	 *
	 * @param string|array|null $query See {@link BP_User_Query}.
	 */
	public function __construct( $query = null ) {
		$qv = bp_parse_args(
			$query,
			array(
				'count' => false, // True to perform a count query. False otherwise.
			)
		);

		parent::__construct( $qv );
	}

	/**
	 * Set up action hooks.
	 *
	 * @since 1.8.0
	 */
	public function setup_hooks() {
		// Take this early opportunity to set the default 'type' param
		// to 'last_joined', which will ensure that BP_User_Query
		// trusts our order and does not try to apply its own.
		if ( empty( $this->query_vars_raw['type'] ) ) {
			$this->query_vars_raw['type'] = 'last_joined';
		}

		if ( ! $this->query_vars_raw['count'] ) {
			// Set the sort order.
			add_action( 'bp_pre_user_query', array( $this, 'set_orderby' ) );

			// Set up our populate_extras method.
			add_action( 'bp_user_query_populate_extras', array( $this, 'populate_group_member_extras' ), 10, 2 );
		} else {
			$this->query_vars_raw['orderby'] = 'ID';
		}
	}

	/**
	 * Use WP_User_Query() to pull data for the user IDs retrieved in the main query.
	 *
	 * If a `count` query is performed, the function is used to validate existing users.
	 *
	 * @since 10.3.0
	 */
	public function do_wp_user_query() {
		if ( ! $this->query_vars_raw['count'] ) {
			return parent::do_wp_user_query();
		}

		/**
		 * Filters the WP User Query arguments before passing into the class.
		 *
		 * @since 10.3.0
		 *
		 * @param array         $value Array of arguments for the user query.
		 * @param BP_User_Query $this  Current BP_User_Query instance.
		 */
		$wp_user_query = new WP_User_Query(
			apply_filters(
				'bp_group_members_count_query_args',
				array(
					// Relevant.
					'fields'      => 'ID',
					'include'     => $this->user_ids,

					// Overrides
					'blog_id'     => 0,    // BP does not require blog roles.
					'count_total' => false // We already have a count.

				),
				$this
			)
		);

		// Validate existing user IDs.
		$this->user_ids = array_map( 'intval', $wp_user_query->results );
		$this->results  = $this->user_ids;

		// Set the total existing users.
		$this->total_users = count( $this->user_ids );
	}

	/**
	 * Get a list of user_ids to include in the IN clause of the main query.
	 *
	 * Overrides BP_User_Query::get_include_ids(), adding our additional
	 * group-member logic.
	 *
	 * @since 1.8.0
	 *
	 * @param array $include Existing group IDs in the $include parameter,
	 *                       as calculated in BP_User_Query.
	 * @return array
	 */
	public function get_include_ids( $include = array() ) {
		// The following args are specific to group member queries, and
		// are not present in the query_vars of a normal BP_User_Query.
		// We loop through to make sure that defaults are set (though
		// values passed to the constructor will, as usual, override
		// these defaults).
		$this->query_vars = bp_parse_args(
			$this->query_vars,
			array(
				'group_id'     => 0,
				'group_role'   => array( 'member' ),
				'is_confirmed' => true,
				'invite_sent'  => null,
				'inviter_id'   => null,
				'type'         => 'last_joined',
			),
			'bp_group_member_query_get_include_ids'
		);

		$group_member_ids = $this->get_group_member_ids();

		// If the group member query returned no users, bail with an
		// array that will guarantee no matches for BP_User_Query.
		if ( empty( $group_member_ids ) ) {
			return array( 0 );
		}

		if ( ! empty( $include ) ) {
			$group_member_ids = array_intersect( $include, $group_member_ids );
		}

		return $group_member_ids;
	}

	/**
	 * Get the members of the queried group.
	 *
	 * @since 1.8.0
	 *
	 * @return array $ids User IDs of relevant group member ids.
	 */
	protected function get_group_member_ids() {
		global $wpdb;

		if ( is_array( $this->group_member_ids ) ) {
			return $this->group_member_ids;
		}

		$bp  = buddypress();
		$sql = array(
			'select'  => "SELECT user_id FROM {$bp->groups->table_name_members}",
			'where'   => array(),
			'orderby' => '',
			'order'   => '',
		);

		/* WHERE clauses *****************************************************/

		// Group id.
		$group_ids = wp_parse_id_list( $this->query_vars['group_id'] );
		$group_ids = implode( ',', $group_ids );
		$sql['where'][] = "group_id IN ({$group_ids})";

		// If is_confirmed.
		$is_confirmed = ! empty( $this->query_vars['is_confirmed'] ) ? 1 : 0;
		$sql['where'][] = $wpdb->prepare( "is_confirmed = %d", $is_confirmed );

		// If invite_sent.
		if ( ! is_null( $this->query_vars['invite_sent'] ) ) {
			$invite_sent = ! empty( $this->query_vars['invite_sent'] ) ? 1 : 0;
			$sql['where'][] = $wpdb->prepare( "invite_sent = %d", $invite_sent );
		}

		// If inviter_id.
		if ( ! is_null( $this->query_vars['inviter_id'] ) ) {
			$inviter_id = $this->query_vars['inviter_id'];

			// Empty: inviter_id = 0. (pass false, 0, or empty array).
			if ( empty( $inviter_id ) ) {
				$sql['where'][] = "inviter_id = 0";

			// The string 'any' matches any non-zero value (inviter_id != 0).
			} elseif ( 'any' === $inviter_id ) {
				$sql['where'][] = "inviter_id != 0";

			// Assume that a list of inviter IDs has been passed.
			} else {
				// Parse and sanitize.
				$inviter_ids = wp_parse_id_list( $inviter_id );
				if ( ! empty( $inviter_ids ) ) {
					$inviter_ids_sql = implode( ',', $inviter_ids );
					$sql['where'][] = "inviter_id IN ({$inviter_ids_sql})";
				}
			}
		}

		// Role information is stored as follows: admins have
		// is_admin = 1, mods have is_mod = 1, banned have is_banned =
		// 1, and members have all three set to 0.
		$roles = !empty( $this->query_vars['group_role'] ) ? $this->query_vars['group_role'] : array();
		if ( is_string( $roles ) ) {
			$roles = explode( ',', $roles );
		}

		// Sanitize: Only 'admin', 'mod', 'member', and 'banned' are valid.
		$allowed_roles = array( 'admin', 'mod', 'member', 'banned' );
		foreach ( $roles as $role_key => $role_value ) {
			if ( ! in_array( $role_value, $allowed_roles ) ) {
				unset( $roles[ $role_key ] );
			}
		}

		$roles = array_unique( $roles );

		// When querying for a set of roles containing 'member' (for
		// which there is no dedicated is_ column), figure out a list
		// of columns *not* to match.
		$roles_sql = '';
		if ( in_array( 'member', $roles ) ) {
			$role_columns = array();
			foreach ( array_diff( $allowed_roles, $roles ) as $excluded_role ) {
				$role_columns[] = 'is_' . $excluded_role . ' = 0';
			}

			if ( ! empty( $role_columns ) ) {
				$roles_sql = '(' . implode( ' AND ', $role_columns ) . ')';
			}

		// When querying for a set of roles *not* containing 'member',
		// simply construct a list of is_* = 1 clauses.
		} else {
			$role_columns = array();
			foreach ( $roles as $role ) {
				$role_columns[] = 'is_' . $role . ' = 1';
			}

			if ( ! empty( $role_columns ) ) {
				$roles_sql = '(' . implode( ' OR ', $role_columns ) . ')';
			}
		}

		if ( ! empty( $roles_sql ) ) {
			$sql['where'][] = $roles_sql;
		}

		$sql['where'] = ! empty( $sql['where'] ) ? 'WHERE ' . implode( ' AND ', $sql['where'] ) : '';

		// We fetch group members in order of last_joined, regardless
		// of 'type'. If the 'type' value is not 'last_joined' or
		// 'first_joined', the order will be overridden in
		// BP_Group_Member_Query::set_orderby().
		$sql['orderby'] = "ORDER BY date_modified";
		$sql['order']   = 'first_joined' === $this->query_vars['type'] ? 'ASC' : 'DESC';

		$group_member_ids = $wpdb->get_col( "{$sql['select']} {$sql['where']} {$sql['orderby']} {$sql['order']}" );

		$invited_member_ids = array();

		// If appropriate, fetch invitations and add them to the results.
		if ( ! $is_confirmed || ! is_null( $this->query_vars['invite_sent'] ) || ! is_null( $this->query_vars['inviter_id'] ) ) {
			$invite_args = array(
				'item_id' => $this->query_vars['group_id'],
				'fields'  => 'user_ids',
				'type'    => 'all',
			);

			if ( ! is_null( $this->query_vars['invite_sent'] ) ) {
				$invite_args['invite_sent'] = ! empty( $this->query_vars['invite_sent'] ) ? 'sent' : 'draft';
			}

			// If inviter_id.
			if ( ! is_null( $this->query_vars['inviter_id'] ) ) {
				$inviter_id = $this->query_vars['inviter_id'];

				// Empty: inviter_id = 0. (pass false, 0, or empty array).
				if ( empty( $inviter_id ) ) {
					$invite_args['type'] = 'request';

				/*
				* The string 'any' matches any non-zero value (inviter_id != 0).
				* These are invitations, not requests.
				*/
				} elseif ( 'any' === $inviter_id ) {
					$invite_args['type'] = 'invite';

				// Assume that a list of inviter IDs has been passed.
				} else {
					$invite_args['type'] = 'invite';
					// Parse and sanitize.
					$inviter_ids = wp_parse_id_list( $inviter_id );
					if ( ! empty( $inviter_ids ) ) {
						$invite_args['inviter_id'] = $inviter_ids;
					}
				}
			}

			/*
			 * If first_joined is the "type" of query, sort the oldest
			 * requests and invitations to the top.
			 */
			if ( 'first_joined' === $this->query_vars['type'] ) {
				$invite_args['order_by']   = 'date_modified';
				$invite_args['sort_order'] = 'ASC';
			}

			$invited_member_ids = groups_get_invites( $invite_args );
		}

		$this->group_member_ids = array_merge( $group_member_ids, $invited_member_ids );

		/**
		 * Filters the member IDs for the current group member query.
		 *
		 * Use this filter to build a custom query (such as when you've
		 * defined a custom 'type').
		 *
		 * @since 2.0.0
		 *
		 * @param array                 $group_member_ids Array of associated member IDs.
		 * @param BP_Group_Member_Query $this             Current BP_Group_Member_Query instance.
		 */
		$this->group_member_ids = apply_filters( 'bp_group_member_query_group_member_ids', $this->group_member_ids, $this );

		return $this->group_member_ids;
	}

	/**
	 * Tell BP_User_Query to order by the order of our query results.
	 *
	 * We only override BP_User_Query's native ordering in case of the
	 * 'last_joined' and 'first_joined' $type parameters.
	 *
	 * @since 1.8.1
	 *
	 * @param BP_User_Query $query BP_User_Query object.
	 */
	public function set_orderby( $query ) {
		$gm_ids = $this->get_group_member_ids();
		if ( empty( $gm_ids ) ) {
			$gm_ids = array( 0 );
		}

		// For 'last_joined', 'first_joined', and 'group_activity'
		// types, we override the default orderby clause of
		// BP_User_Query. In the case of 'group_activity', we perform
		// a separate query to get the necessary order. In the case of
		// 'last_joined' and 'first_joined', we can trust the order of
		// results from  BP_Group_Member_Query::get_group_members().
		// In all other cases, we fall through and let BP_User_Query
		// do its own (non-group-specific) ordering.
		if ( in_array( $query->query_vars['type'], array( 'last_joined', 'first_joined', 'group_activity' ) ) ) {

			// Group Activity DESC.
			if ( 'group_activity' == $query->query_vars['type'] ) {
				$gm_ids = $this->get_gm_ids_ordered_by_activity( $query, $gm_ids );
			}

			// The first param in the FIELD() clause is the sort column id.
			$gm_ids = array_merge( array( 'u.id' ), wp_parse_id_list( $gm_ids ) );
			$gm_ids_sql = implode( ',', $gm_ids );

			$query->uid_clauses['orderby'] = "ORDER BY FIELD(" . $gm_ids_sql . ")";
		}

		// Prevent this filter from running on future BP_User_Query
		// instances on the same page.
		remove_action( 'bp_pre_user_query', array( $this, 'set_orderby' ) );
	}

	/**
	 * Fetch additional data required in bp_group_has_members() loops.
	 *
	 * Additional data fetched:
	 *      - is_banned
	 *      - date_modified
	 *
	 * @since 1.8.0
	 *
	 * @param BP_User_Query $query        BP_User_Query object. Because we're
	 *                                    filtering the current object, we use
	 *                                    $this inside of the method instead.
	 * @param string        $user_ids_sql Sanitized, comma-separated string of
	 *                                    the user ids returned by the main query.
	 */
	public function populate_group_member_extras( $query, $user_ids_sql ) {
		global $wpdb;

		$bp     = buddypress();
		$extras = $wpdb->get_results( $wpdb->prepare( "SELECT id, user_id, date_modified, is_admin, is_mod, comments, user_title, invite_sent, is_confirmed, inviter_id, is_banned FROM {$bp->groups->table_name_members} WHERE user_id IN ({$user_ids_sql}) AND group_id = %d", $this->query_vars['group_id'] ) );

		foreach ( (array) $extras as $extra ) {
			if ( isset( $this->results[ $extra->user_id ] ) ) {
				// The user_id is provided for backward compatibility.
				$this->results[ $extra->user_id ]->user_id       = (int) $extra->user_id;
				$this->results[ $extra->user_id ]->is_admin      = (int) $extra->is_admin;
				$this->results[ $extra->user_id ]->is_mod        = (int) $extra->is_mod;
				$this->results[ $extra->user_id ]->is_banned     = (int) $extra->is_banned;
				$this->results[ $extra->user_id ]->date_modified = $extra->date_modified;
				$this->results[ $extra->user_id ]->user_title    = $extra->user_title;
				$this->results[ $extra->user_id ]->comments      = $extra->comments;
				$this->results[ $extra->user_id ]->invite_sent   = (int) $extra->invite_sent;
				$this->results[ $extra->user_id ]->inviter_id    = (int) $extra->inviter_id;
				$this->results[ $extra->user_id ]->is_confirmed  = (int) $extra->is_confirmed;
				$this->results[ $extra->user_id ]->membership_id = (int) $extra->id;
			}
		}

		// Add accurate invitation info from the invitations table.
		$invites = groups_get_invites( array(
			'user_id' => $user_ids_sql,
			'item_id' => $this->query_vars['group_id'],
			'type'    => 'all',
		) );
		foreach ( $invites as $invite ) {
			if ( isset( $this->results[ $invite->user_id ] ) ) {
				$this->results[ $invite->user_id ]->comments      = $invite->content;
				$this->results[ $invite->user_id ]->is_confirmed  = 0;
				$this->results[ $invite->user_id ]->invitation_id = $invite->id;
				$this->results[ $invite->user_id ]->invite_sent   = (int) $invite->invite_sent;
				$this->results[ $invite->user_id ]->inviter_id    = $invite->inviter_id;

				// Backfill properties that are not being set above.
				if ( ! isset( $this->results[ $invite->user_id ]->user_id ) ) {
					$this->results[ $invite->user_id ]->user_id = $invite->user_id;
				}
				if ( ! isset( $this->results[ $invite->user_id ]->is_admin ) ) {
					$this->results[ $invite->user_id ]->is_admin = 0;
				}
				if ( ! isset( $this->results[ $invite->user_id ]->is_mod ) ) {
					$this->results[ $invite->user_id ]->is_mod = 0;
				}
				if ( ! isset( $this->results[ $invite->user_id ]->is_banned ) ) {
					$this->results[ $invite->user_id ]->is_banned = 0;
				}
				if ( ! isset( $this->results[ $invite->user_id ]->date_modified ) ) {
					$this->results[ $invite->user_id ]->date_modified = $invite->date_modified;
				}
				if ( ! isset( $this->results[ $invite->user_id ]->user_title ) ) {
					$this->results[ $invite->user_id ]->user_title = '';
				}
				if ( ! isset( $this->results[ $invite->user_id ]->membership_id ) ) {
					$this->results[ $invite->user_id ]->membership_id = 0;
				}
			}
		}

		// Don't filter other BP_User_Query objects on the same page.
		remove_action( 'bp_user_query_populate_extras', array( $this, 'populate_group_member_extras' ), 10 );
	}

	/**
	 * Sort user IDs by how recently they have generated activity within a given group.
	 *
	 * @since 2.1.0
	 *
	 * @param BP_User_Query $query  BP_User_Query object.
	 * @param array         $gm_ids array of group member ids.
	 * @return array
	 */
	public function get_gm_ids_ordered_by_activity( $query, $gm_ids = array() ) {
		global $wpdb;

		if ( empty( $gm_ids ) ) {
			return $gm_ids;
		}

		if ( ! bp_is_active( 'activity' ) ) {
			return $gm_ids;
		}

		$activity_table = buddypress()->activity->table_name;

		$sql = array(
			'select'  => "SELECT user_id, max( date_recorded ) as date_recorded FROM {$activity_table}",
			'where'   => array(),
			'groupby' => 'GROUP BY user_id',
			'orderby' => 'ORDER BY date_recorded',
			'order'   => 'DESC',
		);

		$sql['where'] = array(
			'user_id IN (' . implode( ',', wp_parse_id_list( $gm_ids ) ) . ')',
			'item_id = ' . absint( $query->query_vars['group_id'] ),
			$wpdb->prepare( "component = %s", buddypress()->groups->id ),
		);

		$sql['where'] = 'WHERE ' . implode( ' AND ', $sql['where'] );

		$group_user_ids = $wpdb->get_results( "{$sql['select']} {$sql['where']} {$sql['groupby']} {$sql['orderby']} {$sql['order']}" );

		return wp_list_pluck( $group_user_ids, 'user_id' );
	}

	/**
	 * Perform a database query to populate any extra metadata we might need.
	 *
	 * If a `count` query is performed, the function is used to validate active users.
	 *
	 * @since 10.3.0
	 */
	public function populate_extras() {
		if ( ! $this->query_vars_raw['count'] ) {
			return parent::populate_extras();
		}

		// Validate active users.
		$active_users    = array_filter( BP_Core_User::get_last_activity( $this->user_ids ) );
		$active_user_ids = array_keys( $active_users );
		$this->results   = array_intersect( $this->user_ids, $active_user_ids );

		// Set the total active users.
		$this->total_users = count( $this->results );
	}
}
