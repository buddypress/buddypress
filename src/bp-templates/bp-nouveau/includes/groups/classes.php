<?php
/**
 * Groups classes
 *
 * @since 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Query to get members that are not already members of the group
 *
 * @since 3.0.0
 */
class BP_Nouveau_Group_Invite_Query extends BP_User_Query {
	/**
	 * Array of group member ids, cached to prevent redundant lookups
	 *
	 * @var null|array Null if not yet defined, otherwise an array of ints
	 * @since 3.0.0
	 */
	protected $group_member_ids;

	/**
	 * Set up action hooks
	 *
	 * @since 3.0.0
	 */
	public function setup_hooks() {
		add_action( 'bp_pre_user_query_construct', array( $this, 'build_exclude_args' ) );
		add_action( 'bp_pre_user_query', array( $this, 'build_meta_query' ) );
	}

	/**
	 * Exclude group members from the user query as it's not needed to invite members to join the group.
	 *
	 * @since 3.0.0
	 */
	public function build_exclude_args() {
		$this->query_vars = bp_parse_args(
			$this->query_vars,
			array(
				'group_id'     => 0,
				'is_confirmed' => true,
			),
			'nouveau_group_invite_query_exlude_args'
		);

		$group_member_ids = $this->get_group_member_ids();

		/**
		 * We want to exclude users who are already members or who have been
		 * invited by **any** of the group members to join it.
		 */
		$type = 'exclude';

		// We want to get the invited users who did not confirmed yet.
		if ( false === $this->query_vars['is_confirmed'] ) {
			$type = 'include';
		}

		if ( ! empty( $group_member_ids ) ) {
			$this->query_vars[ $type ] = $group_member_ids;
		}
	}

	/**
	 * Get the members of the queried group
	 *
	 * @since 3.0.0
	 *
	 * @return array $ids User IDs of relevant group member ids
	 */
	protected function get_group_member_ids() {
		global $wpdb;

		if ( is_array( $this->group_member_ids ) ) {
			return $this->group_member_ids;
		}

		// Fetch **all** invited users.
		$pending_invites = groups_get_invites( array(
			'item_id'     => $this->query_vars['group_id'],
			'invite_sent' => 'sent',
			'fields'      => 'user_ids'
		) );

		// This is a clue that we only want the invitations.
		if ( false === $this->query_vars['is_confirmed'] ) {
			return $pending_invites;
		}

		/**
		 * Otherwise, we want group members _and_ users with outstanding invitations,
		 * because we're doing an "exclude" query.
		 */
		$bp  = buddypress();
		$sql = array(
			'select'  => "SELECT user_id FROM {$bp->groups->table_name_members}",
			'where'   => array(),
			'orderby' => '',
			'order'   => '',
			'limit'   => '',
		);

		/** WHERE clauses *****************************************************/

		// Group id
		$sql['where'][] = $wpdb->prepare( 'group_id = %d', $this->query_vars['group_id'] );

		// Join the query part
		$sql['where'] = ! empty( $sql['where'] ) ? 'WHERE ' . implode( ' AND ', $sql['where'] ) : '';

		/** ORDER BY clause ***************************************************/
		$sql['orderby'] = 'ORDER BY date_modified';
		$sql['order']   = 'DESC';

		/** LIMIT clause ******************************************************/
		$this->group_member_ids = $wpdb->get_col( "{$sql['select']} {$sql['where']} {$sql['orderby']} {$sql['order']} {$sql['limit']}" );

		return array_merge( $this->group_member_ids, $pending_invites );
	}

	/**
	 * @since 3.0.0
	 */
	public function build_meta_query( BP_User_Query $bp_user_query ) {
		if ( isset( $this->query_vars['scope'] ) && 'members' === $this->query_vars['scope'] && isset( $this->query_vars['meta_query'] ) ) {

			$invites_meta_query = new WP_Meta_Query( $this->query_vars['meta_query'] );
			$meta_sql           = $invites_meta_query->get_sql( 'user', 'u', 'ID' );

			if ( empty( $meta_sql['join'] ) || empty( $meta_sql['where'] ) ) {
				return;
			}

			$bp_user_query->uid_clauses['select'] .= ' ' . $meta_sql['join'];
			$bp_user_query->uid_clauses['where']  .= ' ' . $meta_sql['where'];
		}
	}

	/**
	 * @since 3.0.0
	 */
	public static function get_inviter_ids( $user_id = 0, $group_id = 0 ) {
		global $wpdb;

		if ( empty( $group_id ) || empty( $user_id ) ) {
			return array();
		}

		return groups_get_invites( array(
			'user_id'     => $user_id,
			'item_id'     => $group_id,
			'invite_sent' => 'sent',
			'fields'      => 'inviter_ids'
		) );
	}
}

/**
 * A specific Group Nav class to make it possible to set new positions for
 * buddypress()->groups->nav.
 *
 * @since 3.0.0
 */
class BP_Nouveau_Customizer_Group_Nav extends BP_Core_Nav {
	/**
	 * Constructor
	 *
	 * @param int $object_id Optional. The random group ID used to generate the nav.
	 */
	public function __construct( $object_id = 0 ) {
		$error = new WP_Error( 'missing_parameter' );

		if ( empty( $object_id ) || ! bp_current_user_can( 'bp_moderate' ) || ! did_action( 'admin_init' ) ) {
			return $error;
		}

		$group = groups_get_group( array( 'group_id' => $object_id ) );
		if ( empty( $group->id ) ) {
			return $error;
		}

		$this->group = $group;

		parent::__construct( $group->id );
		$this->setup_nav();
	}

	/**
	 * Checks whether a property is set.
	 *
	 * Overrides BP_Core_Nav::__isset() to avoid looking into its nav property.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key The property.
	 *
	 * @return bool True if the property is set, false otherwise.
	 */
	public function __isset( $key ) {
		return isset( $this->{$key} );
	}

	/**
	 * Gets a property.
	 *
	 * Overrides BP_Core_Nav::__isset() to avoid looking into its nav property.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key The property.
	 *
	 * @return mixed The value corresponding to the property.
	 */
	public function __get( $key ) {
		if ( ! isset( $this->{$key} ) ) {
			$this->{$key} = null;
		}

		return $this->{$key};
	}

	/**
	 * Sets a property.
	 *
	 * Overrides BP_Core_Nav::__isset() to avoid adding a value to its nav property.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key The property.
	 *
	 * @param mixed $value The value of the property.
	 */
	public function __set( $key, $value ) {
		$this->{$key} = $value;
	}

	/**
	 * Setup a temporary nav with only the needed parameters.
	 *
	 * @since 3.0.0
	 */
	protected function setup_nav() {
		$nav_items = array(
			'root'    => array(
				'name'                => __( 'Memberships', 'buddypress' ),
				'slug'                => $this->group->slug,
				'position'            => -1,
				/** This filter is documented in bp-groups/classes/class-bp-groups-component.php. */
				'default_subnav_slug' => apply_filters( 'bp_groups_default_extension', defined( 'BP_GROUPS_DEFAULT_EXTENSION' ) ? BP_GROUPS_DEFAULT_EXTENSION : 'home' ),
			),
			'home'    => array(
				'name'        => _x( 'Home', 'Group screen navigation title', 'buddypress' ),
				'slug'        => 'home',
				'parent_slug' => $this->group->slug,
				'position'    => 10,
			),
			'invites' => array(
				'name'        => _x( 'Invite', 'My Group screen nav', 'buddypress' ),
				'slug'        => 'send-invites',
				'parent_slug' => $this->group->slug,
				'position'    => 70,
			),
			'manage'  => array(
				'name'        => _x( 'Manage', 'My Group screen nav', 'buddypress' ),
				'slug'        => 'admin',
				'parent_slug' => $this->group->slug,
				'position'    => 1000,
			),
		);

		// Make sure only global front.php will be checked.
		add_filter( '_bp_nouveau_group_reset_front_template', array( $this, 'all_groups_fronts' ), 10, 1 );

		$front_template = bp_groups_get_front_template( $this->group );

		remove_filter( '_bp_nouveau_group_reset_front_template', array( $this, 'all_groups_fronts' ), 10, 1 );

		if ( ! $front_template ) {
			if ( bp_is_active( 'activity' ) ) {
				$nav_items['home']['name'] = _x( 'Home (Activity)', 'Group screen navigation title', 'buddypress' );
			} else {
				$nav_items['home']['name'] = _x( 'Home (Members)', 'Group screen navigation title', 'buddypress' );
			}
		} else {
			if ( bp_is_active( 'activity' ) ) {
				$nav_items['activity'] = array(
					'name'        => _x( 'Activity', 'My Group screen nav', 'buddypress' ),
					'slug'        => 'activity',
					'parent_slug' => $this->group->slug,
					'position'    => 11,
				);
			}

			// Add the members one
			$nav_items['members'] = array(
				'name'        => _x( 'Members', 'My Group screen nav', 'buddypress' ),
				'slug'        => 'members',
				'parent_slug' => $this->group->slug,
				'position'    => 60,
			);
		}

		// Required params
		$required_params = array(
			'slug'              => true,
			'name'              => true,
			'nav_item_position' => true,
		);

		// Now find nav items plugins are creating within their Group extensions!
		foreach ( get_declared_classes() as $class ) {
			if ( is_subclass_of( $class, 'BP_Group_Extension' ) ) {
				$extension = new $class;

				if ( ! empty( $extension->params ) && ! array_diff_key( $required_params, $extension->params ) ) {
					$nav_items[ $extension->params['slug'] ] = array(
						'name'        => $extension->params['name'],
						'slug'        => $extension->params['slug'],
						'parent_slug' => $this->group->slug,
						'position'    => $extension->params['nav_item_position'],
					);
				}
			}
		}

		// Now we got all, create the temporary nav.
		foreach ( $nav_items as $nav_item ) {
			$this->add_nav( $nav_item );
		}
	}

	/**
	 * Front template: do not look into group's template hierarchy.
	 *
	 * @since 3.0.0
	 *
	 * @param array $templates The list of possible group front templates.
	 *
	 * @return array The list of "global" group front templates.
	 */
	public function all_groups_fronts( $templates = array() ) {
		return array_intersect( array(
			'groups/single/front.php',
			'groups/single/default-front.php',
		), $templates );
	}

	/**
	 * Get the original order for the group navigation.
	 *
	 * @since 3.0.0
	 *
	 * @return array a list of nav items slugs ordered.
	 */
	public function get_default_value() {
		$default_nav = $this->get_secondary( array( 'parent_slug' => $this->group->slug ) );
		return wp_list_pluck( $default_nav, 'slug' );
	}

	/**
	 * Get the list of nav items ordered according to the Site owner preferences.
	 *
	 * @since 3.0.0
	 *
	 * @return array the nav items ordered.
	 */
	public function get_group_nav() {
		// Eventually reset the order
		bp_nouveau_set_nav_item_order( $this, bp_nouveau_get_appearance_settings( 'group_nav_order' ), $this->group->slug );

		return $this->get_secondary( array( 'parent_slug' => $this->group->slug ) );
	}
}
