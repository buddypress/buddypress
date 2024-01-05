<?php
/**
 * BuddyPress Groups Component Class.
 *
 * @package BuddyPress
 * @subpackage GroupsLoader
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates our Groups component.
 *
 * @since 1.5.0
 */
#[AllowDynamicProperties]
class BP_Groups_Component extends BP_Component {

	/**
	 * Auto-join group when non group member performs group activity.
	 *
	 * @since 1.5.0
	 * @var bool
	 */
	public $auto_join;

	/**
	 * The group being currently accessed.
	 *
	 * @since 1.5.0
	 * @var BP_Groups_Group
	 */
	public $current_group;

	/**
	 * Default group extension.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public $default_extension;

	/**
	 * Illegal group names/slugs.
	 *
	 * @since 1.5.0
	 * @var array
	 */
	public $forbidden_names;

	/**
	 * Group creation/edit steps (e.g. Details, Settings, Avatar, Invites).
	 *
	 * @since 1.5.0
	 * @var array
	 */
	public $group_creation_steps;

	/**
	 * Types of group statuses (Public, Private, Hidden).
	 *
	 * @since 1.5.0
	 * @var array
	 */
	public $valid_status;

	/**
	 * Group types.
	 *
	 * @see bp_groups_register_group_type()
	 *
	 * @since 2.6.0
	 * @var array
	 */
	public $types = array();

	/**
	 * Nav for the Group component.
	 *
	 * @since 2.6.0
	 * @var BP_Core_Nav
	 */
	public $nav;

	/**
	 * Current directory group type.
	 *
	 * @see groups_directory_groups_setup()
	 *
	 * @since 2.7.0
	 * @var string
	 */
	public $current_directory_type = '';

	/**
	 * List of registered Group extensions.
	 *
	 * @see bp_register_group_extension()
	 *
	 * @since 10.0.0
	 * @var array
	 */
	public $group_extensions = array();

	/**
	 * Start the groups component creation process.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		$features = array();
		if ( bp_is_active( 'friends' ) ) {
			$features[] = 'invitations';
		}

		parent::start(
			'groups',
			_x( 'User Groups', 'Group screen page <title>', 'buddypress' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 70,
				'search_query_arg'         => 'groups_search',
				'features'                 => $features,
			)
		);
	}

	/**
	 * Include Groups component files.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::includes() for a description of arguments.
	 *
	 * @param array $includes See BP_Component::includes() for a description.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'cache',
			'filters',
			'template',
			'adminbar',
			'functions',
			'notifications',
			'cssjs',
			'blocks',
		);

		// Conditional includes.
		if ( bp_is_active( 'activity' ) ) {
			$includes[] = 'activity';
		}
		if ( is_admin() ) {
			$includes[] = 'admin';
		}

		parent::includes( $includes );
	}

	/**
	 * Late includes method.
	 *
	 * Only load up certain code when on specific pages.
	 *
	 * @since 3.0.0
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		if ( bp_is_groups_component() ) {
			// Authenticated actions.
			if ( is_user_logged_in() &&
				in_array( bp_current_action(), array( 'create', 'join', 'leave-group' ), true )
			) {
				require_once $this->path . 'bp-groups/actions/' . bp_current_action() . '.php';
			}

			// Actions - RSS feed handler.
			if ( bp_is_active( 'activity' ) && bp_is_current_action( 'feed' ) ) {
				require_once $this->path . 'bp-groups/actions/feed.php';
			}

			// Actions - Random group handler.
			if ( isset( $_GET['random-group'] ) ) {
				require_once $this->path . 'bp-groups/actions/random.php';
			}

			// Screens - Directory.
			if ( bp_is_groups_directory() ) {
				require_once $this->path . 'bp-groups/screens/directory.php';
			}

			// Screens - User profile integration.
			if ( bp_is_user() ) {
				require_once $this->path . 'bp-groups/screens/user/my-groups.php';

				if ( bp_is_current_action( 'invites' ) ) {
					require_once $this->path . 'bp-groups/screens/user/invites.php';
				}
			}

			// Single group.
			if ( bp_is_group() ) {
				// Actions - Access protection.
				require_once $this->path . 'bp-groups/actions/access.php';

				// Public nav items.
				if ( in_array( bp_current_action(), array( 'home', 'request-membership', 'activity', 'members', 'send-invites' ), true ) ) {
					require_once $this->path . 'bp-groups/screens/single/' . bp_current_action() . '.php';
				}

				// Admin nav items.
				if ( bp_is_item_admin() && is_user_logged_in() ) {
					require_once $this->path . 'bp-groups/screens/single/admin.php';

					if ( in_array( bp_get_group_current_admin_tab(), array( 'edit-details', 'group-settings', 'group-avatar', 'group-cover-image', 'manage-members', 'membership-requests', 'delete-group' ), true ) ) {
						require_once $this->path . 'bp-groups/screens/single/admin/' . bp_get_group_current_admin_tab() . '.php';
					}
				}
			}

			// Theme compatibility.
			new BP_Groups_Theme_Compat();
		}
	}

	/**
	 * Sets up the current (displayed) group it it exists.
	 *
	 * @since 12.0.0
	 *
	 * @param string $group_slug The current action which is possibly a group slug.
	 * @return BP_Groups_Group|Object|integer A group's object or 0 if no groups were found.
	 */
	public function setup_current_group( $group_slug = '' ) {
		if ( ! bp_is_groups_component() || ! $group_slug ) {
			return 0;
		}

		// Get the BuddyPress main instance.
		$bp = buddypress();

		// Try to find a group ID matching the requested slug.
		$group_id = BP_Groups_Group::group_exists( $group_slug );
		if ( ! $group_id ) {
			$group_id = BP_Groups_Group::get_id_by_previous_slug( $group_slug );
		}

		// The Group was not found.
		if ( ! $group_id ) {
			return 0;
		}

		// Set BP single item's global.
		$bp->is_single_item = true;

		/**
		 * Filters the current PHP Class being used.
		 *
		 * @since 1.5.0
		 *
		 * @param string $value Name of the class being used.
		 */
		$current_group_class = apply_filters( 'bp_groups_current_group_class', 'BP_Groups_Group' );

		if ( $current_group_class == 'BP_Groups_Group' ) {
			$current_group = groups_get_group( $group_id );

		} else {
			/**
			 * Filters the current group object being instantiated from previous filter.
			 *
			 * @since 1.5.0
			 *
			 * @param object $value Newly instantiated object for the group.
			 */
			$current_group = apply_filters( 'bp_groups_current_group_object', new $current_group_class( $group_id ) );
		}

		if ( ! isset( $current_group->id ) || ! $current_group->id ) {
			return 0;
		}

		// Make sure the Group ID is an integer.
		$current_group->id = (int) $current_group->id;

		/**
		 * When in a single group, the first action is bumped down one because of the
		 * group name, so we need to adjust this and set the group name to current_item.
		 */
		$bp->current_item   = bp_current_action();
		$bp->current_action = bp_action_variable( 0 );
		array_shift( $bp->action_variables );

		// Using "item" not "group" for generic support in other components.
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			bp_update_is_item_admin( true, 'groups' );
		} else {
			bp_update_is_item_admin( groups_is_user_admin( bp_loggedin_user_id(), $current_group->id ), 'groups' );
		}

		// If the user is not an admin, check if they are a moderator.
		if ( ! bp_is_item_admin() ) {
			bp_update_is_item_mod( groups_is_user_mod( bp_loggedin_user_id(), $current_group->id ), 'groups' );
		}

		// Check once if the current group has a custom front template.
		$current_group->front_template = bp_groups_get_front_template( $current_group );

		/**
		 * Fires once the `current_group` global is fully set.
		 *
		 * @since 10.0.0
		 *
		 * @param BP_Groups_Group|object $current_group The current group object.
		 */
		do_action_ref_array( 'bp_groups_set_current_group', array( $current_group ) );

		// Initialize the nav for the groups component.
		$this->nav = new BP_Core_Nav( $current_group->id, $this->id );

		// Finally return the current group.
		return $current_group;
	}

	/**
	 * Set up the component actions.
	 *
	 * @since 12.0.0
	 */
	public function setup_actions() {
		parent::setup_actions();

		// Check the parsed query is consistent with the Group’s registered screens.
		add_action( 'bp_parse_query',  array( $this, 'check_parsed_query' ), 999, 0 );
	}

	/**
	 * Set up additional globals for the component.
	 *
	 * @since 10.0.0
	 */
	public function setup_additional_globals() {
		$bp = buddypress();

		// Are we viewing a single group?
		$this->current_group = $this->setup_current_group( bp_current_action() );

		// Set up variables specific to the group creation process.
		if ( bp_is_groups_component() && bp_is_current_action( 'create' ) && bp_user_can_create_groups() && isset( $_COOKIE['bp_new_group_id'] ) ) {
			$bp->groups->new_group_id = (int) $_COOKIE['bp_new_group_id'];
		}

		// The base slug to filter the groups directory according to a group type.
		$group_type_base = bp_get_groups_group_type_base();

		/**
		 * Filters the list of illegal groups names/slugs.
		 *
		 * @since 1.0.0
		 *
		 * @param array $value Array of illegal group names/slugs.
		 */
		$this->forbidden_names = apply_filters(
			'groups_forbidden_names',
			array(
				'my-groups',
				'create',
				'invites',
				'send-invites',
				'forum',
				'delete',
				'add',
				'admin',
				'request-membership',
				'members',
				'settings',
				'avatar',
				$this->slug,
				$this->root_slug,
				$group_type_base,
			)
		);

		// If the user was attempting to access a group, but no group by that name was found, 404.
		if ( bp_is_groups_component() && empty( $this->current_group ) && bp_current_action() ) {

			// Set group type if available.
			if ( bp_is_current_action( bp_get_groups_group_type_base() ) && bp_action_variable() ) {
				$matched_type  = '';
				$matched_types = bp_groups_get_group_types(
					array(
						'has_directory'  => true,
						'directory_slug' => bp_action_variable(),
					)
				);

				// Set our directory type marker.
				if ( ! empty( $matched_types ) ) {
					$this->current_directory_type = reset( $matched_types );
				}
			}

			if ( ! $this->current_directory_type && ! in_array( bp_current_action(), $this->forbidden_names, true ) ) {
				bp_do_404();
				return;
			}
		}

		// Set default Group creation steps.
		$group_creation_steps = bp_get_group_screens( 'create', true );

		// If avatar uploads are disabled, remove avatar view.
		$disabled_avatar_uploads = (int) bp_disable_group_avatar_uploads();
		if ( $disabled_avatar_uploads || empty( $bp->avatar->show_avatars ) ) {
			unset( $group_creation_steps['group-avatar'] );
		}

		// If cover images are disabled, remove its view.
		if ( ! bp_group_use_cover_image_header() ) {
			unset( $group_creation_steps['group-cover-image'] );
		}

		// If the invitations feature is not active, remove the corresponding view.
		if ( ! bp_is_active( 'groups', 'invitations' ) ) {
			unset( $group_creation_steps['group-invites'] );
		}

		/**
		 * Filters the preconfigured groups creation steps.
		 *
		 * @since 1.1.0
		 *
		 * @param array $group_creation_steps Array of preconfigured group creation steps.
		 */
		$this->group_creation_steps = apply_filters( 'groups_create_group_steps', $group_creation_steps );

		/**
		 * Filters the list of valid groups statuses.
		 *
		 * @since 1.1.0
		 *
		 * @param array $value Array of valid group statuses.
		 */
		$this->valid_status = apply_filters( 'groups_valid_status', array(
			'public',
			'private',
			'hidden'
		) );

		// Auto join group when non group member performs group activity.
		$this->auto_join = defined( 'BP_DISABLE_AUTO_GROUP_JOIN' ) && BP_DISABLE_AUTO_GROUP_JOIN ? false : true;
	}

	/**
	 * Set up component global data.
	 *
	 * The BP_GROUPS_SLUG constant is deprecated.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {
		$bp           = buddypress();
		$default_slug = $this->id;

		// @deprecated.
		if ( defined( 'BP_GROUPS_SLUG' ) ) {
			_doing_it_wrong( 'BP_GROUPS_SLUG', esc_html__( 'Slug constants are deprecated.', 'buddypress' ), 'BuddyPress 12.0.0' );
			$default_slug = BP_GROUPS_SLUG;
		}

		// Global tables for groups component.
		$global_tables = array(
			'table_name'           => $bp->table_prefix . 'bp_groups',
			'table_name_members'   => $bp->table_prefix . 'bp_groups_members',
			'table_name_groupmeta' => $bp->table_prefix . 'bp_groups_groupmeta'
		);

		// Metadata tables for groups component.
		$meta_tables = array(
			'group' => $bp->table_prefix . 'bp_groups_groupmeta',
		);

		// Fetch the default directory title.
		$default_directory_titles = bp_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[$this->id];

		// All globals for groups component.
		// Note that global_tables is included in this array.
		$args = array(
			'slug'                  => $default_slug,
			'root_slug'             => isset( $bp->pages->groups->slug ) ? $bp->pages->groups->slug : $default_slug,
			'has_directory'         => true,
			'rewrite_ids'           => array(
				'directory'                    => 'groups',
				'directory_type'               => 'groups_type',
				'create_single_item'           => 'group_create',
				'create_single_item_variables' => 'group_create_variables',
				'single_item'                  => 'group',
				'single_item_action'           => 'group_action',
				'single_item_action_variables' => 'group_action_variables',
			),
			'directory_title'       => isset( $bp->pages->groups->title ) ? $bp->pages->groups->title : $default_directory_title,
			'notification_callback' => 'groups_format_notifications',
			'search_string'         => _x( 'Search Groups...', 'Component directory search', 'buddypress' ),
			'global_tables'         => $global_tables,
			'meta_tables'           => $meta_tables,
			'block_globals'         => array(
				'bp/dynamic-groups' => array(
					'widget_classnames' => array( 'widget_bp_groups_widget', 'buddypress' ),
				),
			),
		);

		parent::setup_globals( $args );

		// Additional globals.
		$this->setup_additional_globals();
	}

	/**
	 * Set up canonical stack for this component.
	 *
	 * @since 2.1.0
	 */
	public function setup_canonical_stack() {
		if ( ! bp_is_groups_component() ) {
			return;
		}

		if ( empty( $this->current_group ) ) {
			return;
		}

		/**
		 * Filters the default groups extension.
		 *
		 * @since 1.6.0
		 *
		 * @param string $value BP_GROUPS_DEFAULT_EXTENSION constant if defined,
		 *                      else 'home'.
		 */
		$this->default_extension = apply_filters( 'bp_groups_default_extension', defined( 'BP_GROUPS_DEFAULT_EXTENSION' ) ? BP_GROUPS_DEFAULT_EXTENSION : 'home' );

		$bp = buddypress();

		// If the activity component is not active and the current group has no custom front, members are displayed in the home nav.
		if ( 'members' === $this->default_extension && ! bp_is_active( 'activity' ) && ! $this->current_group->front_template ) {
			$this->default_extension = 'home';
		}

		if ( ! bp_current_action() ) {
			$bp->current_action = $this->default_extension;
		}

		// Prepare for a redirect to the canonical URL.
		$bp->canonical_stack['base_url'] = bp_get_group_url( $this->current_group );
		$current_action                  = bp_current_action();

		/**
		 * If there's no custom front.php template for the group, we need to make sure the canonical stack action
		 * is set to 'home' in these 2 cases:
		 *
		 * - the current action is 'activity' (eg: site.url/groups/single/activity) and the Activity component is active
		 * - the current action is 'members' (eg: site.url/groups/single/members) and the Activity component is *not* active.
		 */
		if ( ! $this->current_group->front_template && ( bp_is_current_action( 'activity' ) || ( ! bp_is_active( 'activity' ) && bp_is_current_action( 'members' ) ) ) ) {
			$current_action = 'home';
		}

		if ( $current_action ) {
			$context                       = 'read';
			$path_chunks                   = bp_groups_get_path_chunks( array( $current_action ), $context );
			$bp->canonical_stack['action'] = $current_action;

			if ( isset( $path_chunks['single_item_action'] ) ) {
				$bp->canonical_stack['action'] = $path_chunks['single_item_action'];
			}

			if ( ! empty( $bp->action_variables ) ) {
				$chunks               = array( $current_action, $bp->action_variables );
				$key_action_variables = 'single_item_action_variables';

				if ( bp_is_group_admin_page() ) {
					$context = 'manage';
					array_shift( $chunks );
				}

				$path_chunks                             = bp_groups_get_path_chunks( $chunks, $context );
				$bp->canonical_stack['action_variables'] = bp_action_variables();

				if ( isset( $path_chunks[ $key_action_variables ] ) ) {
					$bp->canonical_stack['action_variables'] = $path_chunks[ $key_action_variables ];
				}
			}
		}

		/*
		 * When viewing the default extension, the canonical URL should not have
		 * that extension's slug, unless more has been tacked onto the URL via
		 * action variables.
		 */
		if ( bp_is_current_action( $this->default_extension ) && empty( $bp->action_variables ) )  {
			unset( $bp->canonical_stack['action'] );
		}
	}

	/**
	 * Register component navigation.
	 *
	 * @since 12.0.0
	 *
	 * @see `BP_Component::register_nav()` for a description of arguments.
	 *
	 * @param array $main_nav Optional. See `BP_Component::register_nav()` for description.
	 * @param array $sub_nav  Optional. See `BP_Component::register_nav()` for description.
	 */
	public function register_nav( $main_nav = array(), $sub_nav = array() ) {
		$slug = bp_get_groups_slug();

		// Add 'Groups' to the main navigation.
		$main_nav = array(
			'name'                => _x( 'Groups', 'Group screen nav without counter', 'buddypress' ),
			'slug'                => $slug,
			'position'            => 70,
			'screen_function'     => 'groups_screen_my_groups',
			'default_subnav_slug' => 'my-groups',
			'item_css_id'         => $this->id
		);

		// Add the My Groups nav item.
		$sub_nav[] = array(
			'name'            => __( 'Memberships', 'buddypress' ),
			'slug'            => 'my-groups',
			'parent_slug'     => $slug,
			'screen_function' => 'groups_screen_my_groups',
			'position'        => 10,
			'item_css_id'     => 'groups-my-groups'
		);

		if ( bp_is_active( 'groups', 'invitations' ) ) {
			// Add the Group Invites nav item.
			$sub_nav[] = array(
				'name'                     => __( 'Invitations', 'buddypress' ),
				'slug'                     => 'invites',
				'parent_slug'              => $slug,
				'screen_function'          => 'groups_screen_group_invites',
				'position'                 => 30,
				'user_has_access'          => false,
				'user_has_access_callback' => 'bp_core_can_edit_settings',
			);
		}

		parent::register_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up component navigation.
	 *
	 * @since 1.5.0
	 * @since 12.0.0 Used to customize the main navigation name and set
	 *               a Groups single item navigation.
	 *
	 * @see `BP_Component::setup_nav()` for a description of arguments.
	 *
	 * @param array $main_nav Optional. See `BP_Component::setup_nav()` for
	 *                        description.
	 * @param array $sub_nav  Optional. See `BP_Component::setup_nav()` for
	 *                        description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		// Only grab count if we're on a user page.
		if ( isset( $this->main_nav['name'] ) && bp_is_user() ) {
			$class                  = ( 0 === groups_total_groups_for_user( bp_displayed_user_id() ) ) ? 'no-count' : 'count';
			$this->main_nav['name'] = sprintf(
				/* translators: %s: Group count for the current user */
				_x( 'Groups %s', 'Group screen nav with counter', 'buddypress' ),
				sprintf(
					'<span class="%s">%s</span>',
					esc_attr( $class ),
					bp_get_total_group_count_for_user()
				)
			);
		}

		// Generate the displayed User navigation for the Groupe compnent.
		parent::setup_nav( $main_nav, $sub_nav );

		// Generate the displayed Group navigation.
		if ( bp_is_groups_component() && bp_is_single_item() ) {
			/*
			 * The top-level Groups item is called 'Memberships' for legacy reasons.
			 * It does not appear in the interface.
			 */
			bp_core_new_nav_item(
				array(
					'name'                => __( 'Memberships', 'buddypress' ),
					'slug'                => $this->current_group->slug,
					'position'            => -1, // Do not show into the navigation.
					'screen_function'     => 'groups_screen_group_home',
					'default_subnav_slug' => $this->default_extension,
					'item_css_id'         => $this->id
				),
				'groups'
			);

			// Get the "read" screens.
			$screens    = bp_get_group_screens( 'read', true );
			$group_link = bp_get_group_url( $this->current_group );
			$sub_nav    = array();

			/*
			 * If this is a private group, and the user is not a member and does not
			 * have an outstanding invitation, only generate the request membership
			 * nav item if the user can request this membership.
			 */
			if ( ! bp_current_user_can( 'groups_request_membership', array( 'group_id' => $this->current_group->id ) ) ) {
				unset( $screens['request-membership'] );
			}

			// If the invitations feature is not active remove the corresponding nav item.
			if ( ! bp_is_active( 'groups', 'invitations' ) ) {
				unset( $screens['send-invites'] );
			}

			/*
			 * By default activity is group's home, only keep an activity sub nab if there's
			 * a custom group's front page and the activity component is active.
			 */
			if ( ! $this->current_group->front_template || ! bp_is_active( 'activity' ) ) {
				unset( $screens['activity'] );
			}

			/*
			 * If there's a custom group's front page and the activity component is not active,
			 * The members screen is use as the group's home page. If it's not the case, remove
			 * the corresponding nav item.
			 */
			if ( ! $this->current_group->front_template && ! bp_is_active( 'activity' ) ) {
				unset( $screens['members'] );
			}

			foreach ( $screens as $screen_id => $sub_nav_item ) {
				$sub_nav_item['parent_slug'] = $this->current_group->slug;

				if ( 'members' === $screen_id ) {
					$sub_nav_item['name'] = sprintf(
						$sub_nav_item['name'],
						'<span>' . number_format( $this->current_group->total_member_count ) . '</span>'
					);
				}

				if ( isset( $sub_nav_item['no_access_url'] ) ) {
					$sub_nav_item['no_access_url'] = $group_link;
				}

				if ( isset( $sub_nav_item['user_has_access_callback'] ) && is_callable( $sub_nav_item['user_has_access_callback'] ) ) {
					$sub_nav_item['user_has_access'] = call_user_func( $sub_nav_item['user_has_access_callback'] );
					unset( $sub_nav_item['user_has_access_callback'] );
				}

				// Add the sub nav item.
				$sub_nav[] = $sub_nav_item;
			}

			// If the user is a group admin, then show the group admin nav item.
			if ( bp_is_item_admin() ) {
				// Get the "manage" screens.
				$manage_screens = bp_get_group_screens( 'manage', true );

				// Common params to all nav items.
				$default_params = array(
					'parent_slug'       => $this->current_group->slug . '_manage',
					'screen_function'   => 'groups_screen_group_admin',
					'user_has_access'   => bp_is_item_admin(),
					'show_in_admin_bar' => true,
				);

				// Only keep the Group's profile photo screen if avatars are enabled.
				if ( bp_disable_group_avatar_uploads() || ! buddypress()->avatar->show_avatars ) {
					unset( $manage_screens['group-avatar'] );
				}

				// Only keep the Group's cover image screen if cover images are enabled.
				if ( ! bp_group_use_cover_image_header() ) {
					unset( $manage_screens['group-cover-image'] );
				}

				// Only keep the membership requests screen for private groups.
				if ( 'private' !== $this->current_group->status ) {
					unset( $manage_screens['membership-requests'] );
				}

				foreach ( $manage_screens as $manage_screen_id => $manage_sub_nav_item ) {
					$sub_nav[] = array_merge( $manage_sub_nav_item, $default_params );
				}
			}

			// Finally generate read/manage nav items.
			foreach ( $sub_nav as $nav ) {
				bp_core_new_subnav_item( $nav, 'groups' );
			}

			if ( isset( $this->current_group->user_has_access ) ) {

				/**
				 * Fires at the end of the groups navigation setup if user has access.
				 *
				 * @since 1.0.2
				 *
				 * @param bool $user_has_access Whether or not user has access.
				 */
				do_action( 'groups_setup_nav', $this->current_group->user_has_access );
			} else {

				/** This action is documented in bp-groups/bp-groups-loader.php */
				do_action( 'groups_setup_nav');
			}
		}
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$groups_slug = bp_get_groups_slug();

			$title   = _x( 'Groups', 'My Account Groups', 'buddypress' );
			$pending = _x( 'No Pending Invites', 'My Account Groups sub nav', 'buddypress' );

			if ( bp_is_active( 'groups', 'invitations' ) ) {
				// Pending group invites.
				$count   = groups_get_invite_count_for_user();
				if ( $count ) {
					$title = sprintf(
						/* translators: %s: Group invitation count for the current user */
						_x( 'Groups %s', 'My Account Groups nav', 'buddypress' ),
						'<span class="count">' . bp_core_number_format( $count ) . '</span>'
					);

					$pending = sprintf(
						/* translators: %s: Group invitation count for the current user */
						_x( 'Pending Invites %s', 'My Account Groups sub nav', 'buddypress' ),
						'<span class="count">' . bp_core_number_format( $count ) . '</span>'
					);
				}
			}

			// Add the "My Account" sub menus.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( $groups_slug ) ) ),
			);

			// My Groups.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-memberships',
				'title'    => _x( 'Memberships', 'My Account Groups sub nav', 'buddypress' ),
				'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $groups_slug, 'my-groups' ) ) ),
				'position' => 10,
			);

			// Invitations.
			if ( bp_is_active( 'groups', 'invitations' ) ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-invites',
					'title'    => $pending,
					'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $groups_slug, 'invites' ) ) ),
					'position' => 30,
				);
			}

			// Create a Group.
			if ( bp_user_can_create_groups() ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-create',
					'title'    => _x( 'Create a Group', 'My Account Groups sub nav', 'buddypress' ),
					'href'     => bp_get_groups_directory_url(
						array(
							'create_single_item' => 1,
						)
					),
					'position' => 90
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since 1.5.0
	 */
	public function setup_title() {

		if ( bp_is_groups_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() && !bp_is_single_item() ) {
				$bp->bp_options_title = _x( 'Memberships', 'My Groups page <title>', 'buddypress' );

			} elseif ( !bp_is_my_profile() && !bp_is_single_item() ) {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					'alt'     => sprintf(
						/* translators: %s: member name */
						__( 'Profile picture of %s', 'buddypress' ),
						bp_get_displayed_user_fullname()
					),
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();

			// We are viewing a single group, so set up the
			// group navigation menu using the $this->current_group global.
			} elseif ( bp_is_single_item() ) {
				$bp->bp_options_title  = $this->current_group->name;
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id'    => $this->current_group->id,
					'object'     => 'group',
					'type'       => 'thumb',
					'avatar_dir' => 'group-avatars',
					'alt'        => __( 'Group Profile Photo', 'buddypress' )
				) );

				if ( empty( $bp->bp_options_avatar ) ) {
					$bp->bp_options_avatar = '<img loading="lazy" src="' . esc_url( bp_core_avatar_default_thumb() ) . '" alt="' . esc_attr__( 'No Group Profile Photo', 'buddypress' ) . '" class="avatar" />';
				}
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups
	 *
	 * @since 2.2.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bp_groups',
				'bp_group_admins',
				'bp_group_invite_count',
				'group_meta',
				'bp_groups_memberships',
				'bp_groups_memberships_for_user',
				'bp_group_mods',
				'bp_groups_invitations_as_memberships',
				'bp_groups_group_type',
			)
		);

		parent::setup_cache_groups();
	}

	/**
	 * Set up taxonomies.
	 *
	 * @since 2.6.0
	 * @since 7.0.0 The Group Type taxonomy is registered using the `bp_groups_register_group_type_taxonomy()` function.
	 */
	public function register_taxonomies() {

		// Just let BP Component fire 'bp_groups_register_taxonomies'.
		return parent::register_taxonomies();
	}

	/**
	 * Adds the Groups directory type & Group create rewrite tags.
	 *
	 * @since 12.0.0
	 *
	 * @param array $rewrite_tags Optional. See BP_Component::add_rewrite_tags() for
	 *                            description.
	 */
	public function add_rewrite_tags( $rewrite_tags = array() ) {
		$rewrite_tags = array(
			'directory_type'               => '([^/]+)',
			'create_single_item'           => '([1]{1,})',
			'create_single_item_variables' => '(.+?)',
		);

		parent::add_rewrite_tags( $rewrite_tags );
	}

	/**
	 * Adds the Groups directory type & Group create rewrite rules.
	 *
	 * @since 12.0.0
	 *
	 * @param array $rewrite_rules Optional. See BP_Component::add_rewrite_rules() for
	 *                             description.
	 */
	public function add_rewrite_rules( $rewrite_rules = array() ) {
		$create_slug = bp_rewrites_get_slug( 'groups', 'group_create', 'create' );

		$rewrite_rules = array(
			'directory_type'      => array(
				'regex' => $this->root_slug . '/' . bp_get_groups_group_type_base() . '/([^/]+)/?$',
				'order' => 50,
				'query' => 'index.php?' . $this->rewrite_ids['directory'] . '=1&' . $this->rewrite_ids['directory_type'] . '=$matches[1]',
			),
			'create_single_item' => array(
				'regex' => $this->root_slug . '/' . $create_slug . '/?$',
				'order' => 40,
				'query' => 'index.php?' . $this->rewrite_ids['directory'] . '=1&' . $this->rewrite_ids['create_single_item'] . '=1',
			),
			'create_single_item_variables' => array(
				'regex' => $this->root_slug . '/' . $create_slug . '/(.+?)/?$',
				'order' =>30,
				'query' => 'index.php?' . $this->rewrite_ids['directory'] . '=1&' . $this->rewrite_ids['create_single_item'] . '=1&' . $this->rewrite_ids['create_single_item_variables'] . '=$matches[1]',
			),
		);

		parent::add_rewrite_rules( $rewrite_rules );
	}

	/**
	 * Parse the WP_Query and eventually display the component's directory or single item.
	 *
	 * @since 12.0.0
	 *
	 * @param WP_Query $query Required. See BP_Component::parse_query() for
	 *                        description.
	 */
	public function parse_query( $query ) {
		/*
		 * If BP Rewrites are not in use, no need to parse BP URI globals another time.
		 * Legacy Parser should have already set these.
		 */
		if ( 'rewrites' !== bp_core_get_query_parser() ) {
			return parent::parse_query( $query );
		}

		if ( bp_is_site_home() && bp_is_directory_homepage( $this->id ) ) {
			$query->set( $this->rewrite_ids['directory'], 1 );
		}

		if ( 1 === (int) $query->get( $this->rewrite_ids['directory'] ) ) {
			$bp                    = buddypress();
			$group_type            = false;
			$bp->current_component = 'groups';
			$group_slug            = $query->get( $this->rewrite_ids['single_item'] );
			$group_type_slug       = $query->get( $this->rewrite_ids['directory_type'] );
			$is_group_create       = 1 === (int) $query->get( $this->rewrite_ids['create_single_item'] );

			if ( $group_slug ) {
				$this->current_group = $this->setup_current_group( $group_slug );

				if ( ! $this->current_group ) {
					$bp->current_component = false;
					bp_do_404();
					return;
				}

				// Set the current item using the group slug.
				$bp->current_item = $group_slug;

				$current_action = $query->get( $this->rewrite_ids['single_item_action'] );
				if ( $current_action ) {
					$context = 'bp_group_read_';

					// Get the rewrite ID corresponfing to the custom slug.
					$current_action_rewrite_id = bp_rewrites_get_custom_slug_rewrite_id( 'groups', $current_action, $context );

					if ( $current_action_rewrite_id ) {
						$current_action = str_replace( $context, '', $current_action_rewrite_id );

						// Make sure the action is stored as a slug: underscores need to be replaced by dashes.
						$current_action = str_replace( '_', '-', $current_action );
					}

					// Set the BuddyPress global.
					$bp->current_action = $current_action;
				}

				$action_variables = $query->get( $this->rewrite_ids['single_item_action_variables'] );
				if ( $action_variables ) {
					if ( ! is_array( $action_variables ) ) {
						$action_variables = explode( '/', ltrim( $action_variables, '/' ) );
					}

					// In the Manage context, we need to translate custom slugs to BP Expected variables.
					if ( 'admin' === $bp->current_action ) {
						$context = 'bp_group_manage_';

						// Get the rewrite ID corresponfing to the custom slug.
						$first_action_variable_rewrite_id = bp_rewrites_get_custom_slug_rewrite_id( 'groups', $action_variables[0], $context );

						if ( $first_action_variable_rewrite_id ) {
							$first_action_variable = str_replace( $context, '', $first_action_variable_rewrite_id );

							// Make sure the action is stored as a slug: underscores need to be replaced by dashes.
							$action_variables[0] = str_replace( '_', '-', $first_action_variable );
						}
					}

					// Set the BuddyPress global.
					$bp->action_variables = $action_variables;
				}
			} elseif ( $group_type_slug ) {
				$group_type = bp_groups_get_group_types(
					array(
						'has_directory'  => true,
						'directory_slug' => $group_type_slug,
					)
				);

				if ( $group_type ) {
					$group_type                   = reset( $group_type );
					$this->current_directory_type = $group_type;
					$bp->current_action           = bp_get_groups_group_type_base();
					$bp->action_variables         = array( $group_type_slug );
				} else {
					$bp->current_component        = false;
					$this->current_directory_type = '';
					bp_do_404();
					return;
				}
			} elseif ( $is_group_create ) {
				$bp->current_action = 'create';

				if ( bp_user_can_create_groups() && isset( $_COOKIE['bp_new_group_id'] ) ) {
					$bp->groups->new_group_id = (int) $_COOKIE['bp_new_group_id'];
				}

				$create_variables = $query->get( $this->rewrite_ids['create_single_item_variables'] );
				if ( $create_variables ) {
					$context          = 'bp_group_create_';
					$action_variables = array();

					if ( ! is_array( $create_variables ) ) {
						$action_variables = explode( '/', ltrim( $create_variables, '/' ) );
					} else {
						$action_variables = $create_variables;
					}

					// The slug of the step is the second action variable.
					if ( isset( $action_variables[1] ) && $action_variables[1] ) {
						// Get the rewrite ID corresponfing to the custom slug.
						$second_action_variable_rewrite_id = bp_rewrites_get_custom_slug_rewrite_id( 'groups', $action_variables[1], $context );

						// Reset the action variable with BP Default create step slug.
						if ( $second_action_variable_rewrite_id ) {
							$second_action_variable = str_replace( $context, '', $second_action_variable_rewrite_id );

							// Make sure the action is stored as a slug: underscores need to be replaced by dashes.
							$action_variables[1] = str_replace( '_', '-', $second_action_variable );
						}
					}

					$bp->action_variables = $action_variables;
				}
			}

			/**
			 * Set the BuddyPress queried object.
			 */
			if ( isset( $bp->pages->groups->id ) ) {
				$query->queried_object    = get_post( $bp->pages->groups->id );
				$query->queried_object_id = $query->queried_object->ID;

				if ( $this->current_group ) {
					$query->queried_object->single_item_name = $this->current_group->name;
				} elseif ( $group_type ) {
					$query->queried_object->directory_type_name = $group_type;
				}
			}
		}

		parent::parse_query( $query );
	}

	/**
	 * Check the parsed query is consistent with Group’s registered screens.
	 *
	 * @since 12.0.0
	 */
	public function check_parsed_query() {
		if ( bp_is_group() ) {
			$slug    = bp_current_action();
			$context = 'read';

			if ( 'admin' === $slug ) {
				$slug    = bp_action_variable( 0 );
				$context = 'manage';
			}

			$registered_group_screens = bp_get_group_screens( $context );

			if ( ! isset( $registered_group_screens[ $slug ] ) ) {
				bp_do_404();
				return;
			}
		}
	}

	/**
	 * Init the BP REST API.
	 *
	 * @since 5.0.0
	 * @since 6.0.0 Adds the Group Cover REST endpoint.
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for
	 *                           description.
	 */
	public function rest_api_init( $controllers = array() ) {
		$controllers = array(
			'BP_REST_Groups_Endpoint',
			'BP_REST_Group_Membership_Endpoint',
			'BP_REST_Group_Invites_Endpoint',
			'BP_REST_Group_Membership_Request_Endpoint',
			'BP_REST_Attachments_Group_Avatar_Endpoint',
		);

		// Support to Group Cover.
		if ( bp_is_active( 'groups', 'cover_image' ) ) {
			$controllers[] = 'BP_REST_Attachments_Group_Cover_Endpoint';
		}

		parent::rest_api_init( $controllers );
	}

	/**
	 * Register the BP Groups Blocks.
	 *
	 * @since 6.0.0
	 * @since 12.0.0 Use the WP Blocks API v2.
	 *
	 * @param array $blocks Optional. See BP_Component::blocks_init() for
	 *                      description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init(
			array(
				'bp/group' => array(
					'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-groups/blocks/group',
					'render_callback' => 'bp_groups_render_group_block',
				),
				'bp/groups' => array(
					'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-groups/blocks/groups',
					'render_callback' => 'bp_groups_render_groups_block',
				),
				'bp/dynamic-groups' => array(
					'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-groups/blocks/dynamic-groups',
					'render_callback' => 'bp_groups_render_dynamic_groups_block',
				),
			)
		);
	}
}
