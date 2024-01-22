<?php
/**
 * BuddyPress Member Loader.
 *
 * @package BuddyPress
 * @subpackage Members
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Defines the BuddyPress Members Component.
 *
 * @since 1.5.0
 */
#[AllowDynamicProperties]
class BP_Members_Component extends BP_Component {

	/**
	 * Member types.
	 *
	 * @see bp_register_member_type()
	 *
	 * @since 2.2.0
	 * @var array
	 */
	public $types = array();

	/**
	 * Main nav arguments.
	 *
	 * @since 2.2.0
	 * @var array
	 */
	public $main_nav = array();

	/**
	 * Main nav arguments.
	 *
	 * @since 2.2.0
	 * @var array
	 */
	public $sub_nav = array();

	/**
	 * Nav for the members component.
	 *
	 * @since 2.2.0
	 * @var BP_Core_Nav
	 */
	public $nav;

	/**
	 * Member admin.
	 *
	 * @since 2.0.0
	 * @var BP_Members_Admin
	 */
	public $admin;

	/**
	 * Invitations.
	 *
	 * @var stdClass
	 */
	public $invitations;

	/**
	 * Start the members component creation process.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		parent::start(
			'members',
			__( 'Members', 'buddypress' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 20,
				'search_query_arg'         => 'members_search',
				'features'                 => array( 'invitations', 'membership_requests' ),
			)
		);
	}

	/**
	 * Include bp-members files.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component::includes()}.
	 */
	public function includes( $includes = array() ) {

		// Always include these files.
		$includes = array(
			'cssjs',
			'filters',
			'template',
			'adminbar',
			'functions',
			'blocks',
			'cache',
			'invitations',
			'notifications',
		);

		if ( bp_is_active( 'activity' ) ) {
			$includes[] = 'activity';
		}

		/**
		 * Duplicate bp_get_membership_requests_required() and
		 * bp_get_signup_allowed() logic here,
		 * because those functions are not available yet.
		 * The `bp_get_signup_allowed` filter is documented in
		 * bp-members/bp-members-template.php.
		 */
		$signup_allowed = apply_filters( 'bp_get_signup_allowed', (bool) bp_get_option( 'users_can_register' ) );
		$membership_requests_enabled = (bool) bp_get_option( 'bp-enable-membership-requests' );
		if ( bp_is_active( 'members', 'membership_requests' ) && ! $signup_allowed && $membership_requests_enabled ) {
			$includes[] = 'membership-requests';
		}

		// Include these only if in admin.
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

		// Members.
		if ( bp_is_members_component() ) {
			// Actions - Random member handler.
			if ( isset( $_GET['random-member'] ) ) {
				require_once $this->path . 'bp-members/actions/random.php';
			}

			// Screens - Directory.
			if ( bp_is_members_directory() ) {
				require_once $this->path . 'bp-members/screens/directory.php';
			}
		}

		// Members - User main nav screen.
		if ( bp_is_user() ) {
			require_once $this->path . 'bp-members/screens/profile.php';

			// Action - Delete avatar.
			if ( is_user_logged_in()&& bp_is_user_change_avatar() && bp_is_action_variable( 'delete-avatar', 0 ) ) {
				require_once $this->path . 'bp-members/actions/delete-avatar.php';
			}

			// Sub-nav items.
			if ( is_user_logged_in() &&
				in_array( bp_current_action(), array( 'change-avatar', 'change-cover-image' ), true )
			) {
				require_once $this->path . 'bp-members/screens/' . bp_current_action() . '.php';
			}
		}

		// Members - Theme compatibility.
		if ( bp_is_members_component() || bp_is_user() ) {
			new BP_Members_Theme_Compat();
		}

		// Registration / Activation.
		if ( bp_is_register_page() || bp_is_activation_page() ) {
			if ( bp_is_register_page() ) {
				require_once $this->path . 'bp-members/screens/register.php';
			} else {
				require_once $this->path . 'bp-members/screens/activate.php';
			}

			// Theme compatibility.
			new BP_Registration_Theme_Compat();
		}

		// Invitations.
		if ( is_user_logged_in() && bp_is_user_members_invitations() ) {
			// Actions.
			if ( isset( $_POST['members_invitations'] ) ) {
				require_once $this->path . 'bp-members/actions/invitations-bulk-manage.php';
			}

			// Screens.
			require_once $this->path . 'bp-members/screens/invitations.php';
		}
	}

	/**
	 * Set up the component actions.
	 *
	 * @since 12.0.0
	 */
	public function setup_actions() {
		parent::setup_actions();

		// Check the parsed query is consistent with the Members navigation.
		add_action( 'bp_parse_query',  array( $this, 'check_parsed_query' ), 999, 0 );
	}

	/**
	 * Set up additional globals for the component.
	 *
	 * @since 10.0.0
	 */
	public function setup_additional_globals() {
		$bp = buddypress();

		// Set-up Extra permastructs for the register and activate pages.
		$this->register_permastruct = bp_get_signup_slug() . '/%' . $this->rewrite_ids['member_register'] . '%';
		$this->activate_permastruct = bp_get_activate_slug() . '/%' . $this->rewrite_ids['member_activate'] . '%';

		// Init the User's ID to use to build the Nav for.
		$user_id = bp_loggedin_user_id();

		/** Logged in user ***************************************************
		 */

		// The core userdata of the user who is currently logged in.
		$bp->loggedin_user->userdata = bp_core_get_core_userdata( $user_id );

		// Fetch the full name for the logged in user.
		$bp->loggedin_user->fullname = isset( $bp->loggedin_user->userdata->display_name ) ? $bp->loggedin_user->userdata->display_name : '';

		// Hits the DB on single WP installs so get this separately.
		$bp->loggedin_user->is_super_admin = $bp->loggedin_user->is_site_admin = is_super_admin( $user_id );

		// The domain for the user currently logged in. eg: http://example.com/members/andy.
		$bp->loggedin_user->domain = bp_members_get_user_url( $user_id );

		/**
		 * Set the Displayed user for the classic BuddyPress. This should only be the case when the
		 * legacy parser is on. When BP Rewrites are on, the displayed user is set in
		 * `BP_Members_Component::parse_query()`.
		 */
		if ( bp_displayed_user_id() ) {
			// We're viewing a speciific user, switch the ID to use for the Nav to this one.
			$user_id = bp_displayed_user_id();

			// The core userdata of the user who is currently being displayed.
			$bp->displayed_user->userdata = bp_core_get_core_userdata( $user_id );

			// Fetch the full name displayed user.
			$bp->displayed_user->fullname = isset( $bp->displayed_user->userdata->display_name ) ? $bp->displayed_user->userdata->display_name : '';

			// The domain for the user currently being displayed.
			$bp->displayed_user->domain = bp_members_get_user_url( $user_id );

			// If A user is displayed, check if there is a front template
			if ( bp_get_displayed_user() ) {
				$bp->displayed_user->front_template = bp_displayed_user_get_front_template();
			}
		}

		/** Initialize the nav for the members component *********************
		 */

		$this->nav = new BP_Core_Nav( $user_id );

		/** Signup ***********************************************************
		 */

		$bp->signup = new stdClass;

		/** Profiles Fallback ************************************************
		 */

		if ( ! bp_is_active( 'xprofile' ) ) {
			$bp->profile       = new stdClass;
			$bp->profile->slug = 'profile';
			$bp->profile->id   = 'profile';
		}

		/** Network Invitations **************************************************
		 */

		$bp->members->invitations = new stdClass;
	}

	/**
	 * Set up bp-members global settings.
	 *
	 * The BP_MEMBERS_SLUG constant is deprecated.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 *
	 * @global wpdb $wpdb The WordPress database object.
	 *
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
		global $wpdb;

		$bp           = buddypress();
		$default_slug = $this->id;

		/** Component Globals ************************************************
		 */

		// @deprecated.
		if ( defined( 'BP_MEMBERS_SLUG' ) ) {
			_doing_it_wrong( 'BP_MEMBERS_SLUG', esc_html__( 'Slug constants are deprecated.', 'buddypress' ), 'BuddyPress 12.0.0' );
			$default_slug = BP_MEMBERS_SLUG;
		}

		// Fetch the default directory title.
		$default_directory_titles = bp_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[$this->id];

		// Override any passed args.
		$args = array(
			'slug'            => $default_slug,
			'root_slug'       => isset( $bp->pages->members->slug ) ? $bp->pages->members->slug : $default_slug,
			'has_directory'   => true,
			'rewrite_ids'     => array(
				'directory'                    => 'members',
				'directory_type'               => 'members_type',
				'single_item'                  => 'member',
				'single_item_component'        => 'member_component',
				'single_item_action'           => 'member_action',
				'single_item_action_variables' => 'member_action_variables',
				'member_register'              => 'register',
				'member_activate'              => 'activate',
				'member_activate_key'          => 'activate_key',
			),
			'directory_title' => isset( $bp->pages->members->title ) ? $bp->pages->members->title : $default_directory_title,
			'search_string'   => __( 'Search Members...', 'buddypress' ),
			'global_tables'   => array(
				'table_name_invitations'   => bp_core_get_table_prefix() . 'bp_invitations',
				'table_name_last_activity' => bp_core_get_table_prefix() . 'bp_activity',
				'table_name_optouts'       => bp_core_get_table_prefix() . 'bp_optouts',
				'table_name_signups'       => $wpdb->base_prefix . 'signups', // Signups is a global WordPress table.
			),
			'notification_callback' => 'members_format_notifications',
			'block_globals'         => array(
				'bp/dynamic-members' => array(
					'widget_classnames' => array( 'widget_bp_core_members_widget', 'buddypress' ),
				),
				'bp/online-members' => array(
					'widget_classnames' => array( 'widget_bp_core_whos_online_widget', 'buddypress' ),
				),
				'bp/active-members' => array(
					'widget_classnames' => array( 'widget_bp_core_recently_active_widget', 'buddypress' ),
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
		$bp = buddypress();

		/** Default Profile Component ****************************************
		 */
		if ( bp_displayed_user_has_front_template() ) {
			$bp->default_component = 'front';
		} elseif ( bp_is_active( 'activity' ) && isset( $bp->pages->activity ) ) {
			$bp->default_component = bp_get_activity_slug();
		} else {
			$bp->default_component = ( 'xprofile' === $bp->profile->id ) ? 'profile' : $bp->profile->id;
		}

		if ( defined( 'BP_DEFAULT_COMPONENT' ) && BP_DEFAULT_COMPONENT ) {
			$default_component = BP_DEFAULT_COMPONENT;
			if ( 'profile' === $default_component ) {
				$default_component = 'xprofile';
			}

			if ( bp_is_active( $default_component ) ) {
				$bp->default_component = BP_DEFAULT_COMPONENT;
			}
		}

		/** Canonical Component Stack ****************************************
		 */

		if ( bp_displayed_user_id() ) {
			$bp->canonical_stack['base_url'] = bp_displayed_user_url();
			$action_variables                = (array) bp_action_variables();
			$path_chunks                     = bp_members_get_path_chunks(
				array_merge(
					array( bp_current_component(), bp_current_action() ),
					array_filter( $action_variables )
				)
			);

			if ( isset( $path_chunks['single_item_component'] ) ) {
				$bp->canonical_stack['component'] = $path_chunks['single_item_component'];

				// The canonical URL will not contain the default component.
				if ( bp_is_current_component( $bp->default_component ) && ! bp_current_action() ) {
					unset( $bp->canonical_stack['component'] );
				} elseif ( isset( $path_chunks['single_item_action'] ) ) {
					$bp->canonical_stack['action'] = $path_chunks['single_item_action'];

					if ( isset( $path_chunks['single_item_action_variables'] ) ) {
						$bp->canonical_stack['action_variables'] = $path_chunks['single_item_action_variables'];
					}
				}

				// Looking at the single member root/home, so assume the default.
			} else {
				$bp->current_component = $bp->default_component;
			}

			/*
			 * If we're on a spammer's profile page, only users with the 'bp_moderate' cap
			 * can view subpages on the spammer's profile.
			 *
			 * users without the cap trying to access a spammer's subnav page will get
			 * redirected to the root of the spammer's profile page.  this occurs by
			 * by removing the component in the canonical stack.
			 */
			if ( bp_is_user_spammer( bp_displayed_user_id() ) && ! bp_current_user_can( 'bp_moderate' ) ) {
				unset( $bp->canonical_stack['component'] );
			}
		}
	}

	/**
	 * Get the Avatar and Cover image subnavs.
	 *
	 * @since 6.0.0
	 * @deprecated 12.0.0
	 *
	 * @return array The Avatar and Cover image subnavs.
	 */
	public function get_avatar_cover_image_subnavs() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Register component navigation.
	 *
	 * @since 12.0.0
	 *
	 * @see `BP_Component::register_nav()` for a description of arguments.
	 *
	 * @param array $main_nav Optional. See `BP_Component::register_nav()` for
	 *                        description.
	 * @param array $sub_nav  Optional. See `BP_Component::register_nav()` for
	 *                        description.
	 */
	public function register_nav( $main_nav = array(), $sub_nav = array() ) {
		// Set slug to profile in case the xProfile component is not active
		$slug = bp_get_profile_slug();

		$main_nav = array(
			'name'                => _x( 'Profile', 'Member profile main navigation', 'buddypress' ),
			'slug'                => $slug,
			'position'            => 20,
			'screen_function'     => 'bp_members_screen_display_profile',
			'default_subnav_slug' => 'public',
			'item_css_id'         => buddypress()->profile->id,
			'generate'            => ! bp_is_active( 'xprofile' ),
		);

		$sub_nav[] = array(
			'name'            => _x( 'View', 'Member profile view', 'buddypress' ),
			'slug'            => 'public',
			'parent_slug'     => $slug,
			'screen_function' => 'bp_members_screen_display_profile',
			'position'        => 10,
			'generate'        => ! bp_is_active( 'xprofile' ),
		);

		$sub_nav[] = array(
			'name'                     => _x( 'Change Profile Photo', 'Profile header sub menu', 'buddypress' ),
			'slug'                     => 'change-avatar',
			'parent_slug'              => $slug,
			'screen_function'          => 'bp_members_screen_change_avatar',
			'position'                 => 30,
			'user_has_access'          => false,
			'user_has_access_callback' => 'bp_core_can_edit_settings',
			'generate'                 => buddypress()->avatar->show_avatars,
		);

		$sub_nav[] = array(
			'name'                     => _x( 'Change Cover Image', 'Profile header sub menu', 'buddypress' ),
			'slug'                     => 'change-cover-image',
			'parent_slug'              => $slug,
			'screen_function'          => 'bp_members_screen_change_cover_image',
			'position'                 => 40,
			'user_has_access'          => false,
			'user_has_access_callback' => 'bp_core_can_edit_settings',
			'generate'                 => bp_displayed_user_use_cover_image_header(),
		);

		parent::register_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up a profile nav in case the xProfile
	 * component is not active and a front template is
	 * used.
	 *
	 * @since 2.6.0
	 * @deprecated 12.0.0
	 */
	public function setup_profile_nav() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Set up the xProfile nav.
	 *
	 * @since 6.0.0
	 * @deprecated 12.0.0
	 */
	public function setup_xprofile_nav() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Get the Avatar and Cover image admin navs.
	 *
	 * @since 6.0.0
	 *
	 * @param  string $admin_bar_menu_id The Admin bar menu ID to attach sub items to.
	 * @return array                     The Avatar and Cover image admin navs.
	 */
	public function get_avatar_cover_image_admin_navs( $admin_bar_menu_id = '' ) {
		$wp_admin_nav = array();
		$profile_slug = bp_get_profile_slug();

		if ( ! $admin_bar_menu_id ) {
			$admin_bar_menu_id = $this->id;
		}

		// Edit Avatar.
		if ( buddypress()->avatar->show_avatars ) {
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $admin_bar_menu_id,
				'id'       => 'my-account-' . $admin_bar_menu_id . '-change-avatar',
				'title'    => _x( 'Change Profile Photo', 'My Account Profile sub nav', 'buddypress' ),
				'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $profile_slug, 'change-avatar' ) ) ),
				'position' => 30,
			);
		}

		// Edit Cover Image
		if ( bp_displayed_user_use_cover_image_header() ) {
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $admin_bar_menu_id,
				'id'       => 'my-account-' . $admin_bar_menu_id . '-change-cover-image',
				'title'    => _x( 'Change Cover Image', 'My Account Profile sub nav', 'buddypress' ),
				'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $profile_slug, 'change-cover-image' ) ) ),
				'position' => 40,
			);
		}

		return $wp_admin_nav;
	}

	/**
	 * Set up the Admin Bar.
	 *
	 * @since 6.0.0
	 *
	 * @param array $wp_admin_nav Admin Bar items.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		// Menus for logged in user.
		if ( is_user_logged_in() ) {
			$profile_slug = bp_get_profile_slug();

			if ( ! bp_is_active( 'xprofile' ) ) {
				// Add the "Profile" sub menu.
				$wp_admin_nav[] = array(
					'parent' => buddypress()->my_account_menu_id,
					'id'     => 'my-account-' . $this->id,
					'title'  => _x( 'Profile', 'My Account Profile', 'buddypress' ),
					'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( $profile_slug ) ) ),
				);

				// View Profile.
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-public',
					'title'    => _x( 'View', 'My Account Profile sub nav', 'buddypress' ),
					'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $profile_slug, 'public' ) ) ),
					'position' => 10,
				);

				$wp_admin_nav = array_merge( $wp_admin_nav, $this->get_avatar_cover_image_admin_navs() );

			/**
			 * The xProfile is active.
			 *
			 * Add the Change Avatar and Change Cover Image Admin Bar items
			 * to the xProfile Admin Bar Menu.
			 */
			} else {
				add_filter( 'bp_xprofile_admin_nav', array( $this, 'setup_xprofile_admin_nav' ), 2 );
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Adds "Profile > Change Avatar" & "Profile > Change Cover Image" subnav item
	 * under the "Profile" adminbar menu.
	 *
	 * @since 6.0.0
	 *
	 * @param array $wp_admin_nav The Profile adminbar nav array.
	 * @return array
	 */
	public function setup_xprofile_admin_nav( $wp_admin_nav ) {
		$items = $this->get_avatar_cover_image_admin_navs( buddypress()->profile->id );

		if ( $items ) {
			$wp_admin_nav = array_merge( $wp_admin_nav, $items );
		}

		return $wp_admin_nav;
	}

	/**
	 * Set up the title for pages and <title>.
	 *
	 * @since 1.5.0
	 */
	public function setup_title() {
		$bp = buddypress();

		if ( bp_is_my_profile() ) {
			$bp->bp_options_title = __( 'You', 'buddypress' );
		} elseif ( bp_is_user() ) {
			$bp->bp_options_title  = bp_get_displayed_user_fullname();
			$bp->bp_options_avatar = bp_core_fetch_avatar( array(
				'item_id' => bp_displayed_user_id(),
				'type'    => 'thumb',
				/* translators: %s: member name */
				'alt'     => sprintf( __( 'Profile picture of %s', 'buddypress' ), $bp->bp_options_title )
			) );
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups.
	 *
	 * @since 2.2.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups( array(
			'bp_last_activity',
			'bp_member_member_type',
		) );

		parent::setup_cache_groups();
	}

	/**
	 * Adds the Members directory type, Registration and Activation rewrite tags.
	 *
	 * @since 12.0.0
	 *
	 * @param array $rewrite_tags Optional. See BP_Component::add_rewrite_tags() for
	 *                            description.
	 */
	public function add_rewrite_tags( $rewrite_tags = array() ) {
		$rewrite_tags = array(
			'directory_type'      => '([^/]+)',
			'member_register'     => '([1]{1,})',
			'member_activate'     => '([1]{1,})',
			'member_activate_key' => '([^/]+)',
		);

		parent::add_rewrite_tags( $rewrite_tags );
	}

	/**
	 * Adds the Registration and Activation rewrite rules.
	 *
	 * @since 12.0.0
	 *
	 * @param array $rewrite_rules Optional. See BP_Component::add_rewrite_rules() for
	 *                             description.
	 */
	public function add_rewrite_rules( $rewrite_rules = array() ) {
		$rewrite_rules = array(
			'directory_type'      => array(
				'regex' => $this->root_slug . '/' . bp_get_members_member_type_base() . '/([^/]+)/?$',
				'order' => 50,
				'query' => 'index.php?' . $this->rewrite_ids['directory'] . '=1&' . $this->rewrite_ids['directory_type'] . '=$matches[1]',
			),
			'member_activate'     => array(
				'regex' => bp_get_activate_slug() . '/?$',
				'order' => 40,
				'query' => 'index.php?' . $this->rewrite_ids['member_activate'] . '=1',
			),
			'member_activate_key' => array(
				'regex' => bp_get_activate_slug() . '/([^/]+)/?$',
				'order' => 30,
				'query' => 'index.php?' . $this->rewrite_ids['member_activate'] . '=1&' . $this->rewrite_ids['member_activate_key'] . '=$matches[1]',
			),
			'member_register'     => array(
				'regex' => bp_get_signup_slug() . '/?$',
				'order' => 20,
				'query' => 'index.php?' . $this->rewrite_ids['member_register'] . '=1',
			),
		);

		parent::add_rewrite_rules( $rewrite_rules );
	}

	/**
	 * Adds the Registration and Activation permastructs.
	 *
	 * @since 12.0.0
	 *
	 * @param array $permastructs Optional. See BP_Component::add_permastructs() for
	 *                            description.
	 */
	public function add_permastructs( $permastructs = array() ) {
		$permastructs = array(
			// Register permastruct.
			$this->rewrite_ids['member_register'] => array(
				'permastruct' => $this->register_permastruct,
				'args'        => array(),
			),
			// Activate permastruct.
			$this->rewrite_ids['member_activate'] => array(
				'permastruct' => $this->activate_permastruct,
				'args'        => array(),
			),
		);

		parent::add_permastructs( $permastructs );
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

		// Init the current member and member type.
		$member      = false;
		$member_type = false;
		$member_data = bp_rewrites_get_member_data();

		if ( isset( $member_data['object'] ) && $member_data['object'] ) {
			bp_reset_query( trailingslashit( $this->root_slug ) . $GLOBALS['wp']->request, $query );
			$member = $member_data['object'];

			// Make sure the Member's screen is fired.
			add_action( 'bp_screens', 'bp_members_screen_display_profile', 3 );
		}

		if ( bp_is_site_home() && bp_is_directory_homepage( $this->id ) ) {
			$query->set( $this->rewrite_ids['directory'], 1 );
		}

		// Which component are we displaying?
		$is_members_component  = 1 === (int) $query->get( $this->rewrite_ids['directory'] );
		$is_register_component = 1 === (int) $query->get( $this->rewrite_ids['member_register'] );
		$is_activate_component = 1 === (int) $query->get( $this->rewrite_ids['member_activate'] );

		// Get BuddyPress main instance.
		$bp = buddypress();

		if ( $is_members_component ) {
			$bp->current_component = 'members';
			$member_slug           = $query->get( $this->rewrite_ids['single_item'] );
			$member_type_slug      = $query->get( $this->rewrite_ids['directory_type'] );

			if ( $member_slug ) {
				/**
				 * Filter the portion of the URI that is the displayed user's slug.
				 *
				 * Eg. example.com/ADMIN (when root profiles is enabled)
				 *     example.com/members/ADMIN (when root profiles isn't enabled)
				 *
				 * ADMIN would be the displayed user's slug.
				 *
				 * @since 2.6.0
				 *
				 * @param string $member_slug
				 */
				$member_slug           = apply_filters( 'bp_core_set_uri_globals_member_slug', $member_slug );
				$bp->current_component = '';

				// Unless root profiles are on, the member shouldn't be set yet.
				if ( ! $member ) {
					$member = get_user_by( $member_data['field'], $member_slug );

					if ( ! $member ) {
						bp_do_404();
						return;
					}
				}

				// If the member is marked as a spammer, 404 (unless logged-in user is a super admin).
				if ( bp_is_user_spammer( $member->ID ) ) {
					if ( bp_current_user_can( 'bp_moderate' ) ) {
						bp_core_add_message( __( 'This user has been marked as a spammer. Only site admins can view this profile.', 'buddypress' ), 'warning' );
					} else {
						bp_do_404();
						return;
					}
				}

				// Set the displayed user and the current item.
				$bp->displayed_user->id = $member->ID;
				$bp->current_item       = $member_slug;

				// The core userdata of the user who is currently being displayed.
				if ( ! isset( $bp->displayed_user->userdata ) || ! $bp->displayed_user->userdata ) {
					$bp->displayed_user->userdata = bp_core_get_core_userdata( bp_displayed_user_id() );
				}

				// Fetch the full name displayed user.
				if ( ! isset( $bp->displayed_user->fullname ) || ! $bp->displayed_user->fullname ) {
					$bp->displayed_user->fullname = '';
					if ( isset( $bp->displayed_user->userdata->display_name ) ) {
						$bp->displayed_user->fullname = $bp->displayed_user->userdata->display_name;
					}
				}

				// The domain for the user currently being displayed.
				if ( ! isset( $bp->displayed_user->domain ) || ! $bp->displayed_user->domain ) {
					$bp->displayed_user->domain = bp_members_get_user_url( bp_displayed_user_id() );
				}

				// If a user is displayed, check if there is a front template and reset navigation.
				if ( bp_get_displayed_user() ) {
					$bp->displayed_user->front_template = bp_displayed_user_get_front_template();

					// Reset the nav for the members component.
					$this->nav = new BP_Core_Nav();
				}

				$member_component = $query->get( $this->rewrite_ids['single_item_component'] );
				if ( $member_component ) {
					// Check if the member's component slug has been customized.
					$item_component_rewrite_id = bp_rewrites_get_custom_slug_rewrite_id( 'members', $member_component );
					if ( $item_component_rewrite_id ) {
						$member_component = str_replace( 'bp_member_', '', $item_component_rewrite_id );
					}

					$bp->current_component = $member_component;
				}

				$current_action = $query->get( $this->rewrite_ids['single_item_action'] );
				if ( $current_action ) {
					$context = sprintf( 'bp_member_%s_', $bp->current_component );

					// Check if the member's component action slug has been customized.
					$item_component_action_rewrite_id = bp_rewrites_get_custom_slug_rewrite_id( 'members', $current_action, $context );
					if ( $item_component_action_rewrite_id ) {
						$custom_action_slug = str_replace( $context, '', $item_component_action_rewrite_id );

						// Make sure the action is stored as a slug: underscores need to be replaced by dashes.
						$current_action = str_replace( '_', '-', $custom_action_slug );
					}

					$bp->current_action = $current_action;
				}

				$action_variables = $query->get( $this->rewrite_ids['single_item_action_variables'] );
				if ( $action_variables ) {
					$context = sprintf( 'bp_member_%1$s_%2$s_', $bp->current_component, $bp->current_action );

					if ( ! is_array( $action_variables ) ) {
						$action_variables = explode( '/', ltrim( $action_variables, '/' ) );
					}

					foreach ( $action_variables as $key_variable => $action_variable ) {
						$item_component_action_variable_rewrite_id = bp_rewrites_get_custom_slug_rewrite_id( 'members', $action_variable, $context );

						if ( $item_component_action_variable_rewrite_id ) {
							$action_variables[ $key_variable ] = str_replace( $context, '', $item_component_action_variable_rewrite_id );
						}
					}

					$bp->action_variables = $action_variables;
				}

				// Is this a member type query?
			} elseif ( $member_type_slug ) {
				$member_type = bp_get_member_types(
					array(
						'has_directory'  => true,
						'directory_slug' => $member_type_slug,
					)
				);

				if ( $member_type ) {
					$member_type             = reset( $member_type );
					$bp->current_member_type = $member_type;
				} else {
					$bp->current_component = '';
					bp_do_404();
					return;
				}
			}

			// Set the BuddyPress queried object.
			if ( isset( $bp->pages->members->id ) ) {
				$query->queried_object    = get_post( $bp->pages->members->id );
				$query->queried_object_id = $query->queried_object->ID;

				if ( $member ) {
					$query->queried_object->single_item_name = $member->display_name;
				} elseif ( $member_type ) {
					$query->queried_object->directory_type_name = $member_type;
				}
			}

			// Handle the custom registration page.
		} elseif ( $is_register_component ) {
			$bp->current_component = 'register';

			// Handle the custom activation page.
		} elseif ( $is_activate_component ) {
			$bp->current_component = 'activate';

			$current_action = $query->get( $this->rewrite_ids['member_activate_key'] );
			if ( $current_action ) {
				$bp->current_action = $current_action;
			}
		}

		parent::parse_query( $query );
	}

	/**
	 * Check the parsed query is consistent with Members navigation.
	 *
	 * As the members’ component pages need a valid screen function to load the right BP Template,
	 * we need to make sure the current single item action exists inside the Members navigation and
	 * that the corresponding screen function is a valid callback.
	 *
	 * @since 12.0.0
	 */
	public function check_parsed_query() {
		if ( bp_is_user() ) {
			$single_item_component = bp_current_component();

			$single_item_action = '';
			if ( $single_item_component ) {
				$single_item_action = bp_current_action();
			}

			// Viewing a single activity.
			if ( 'activity' === $single_item_component && is_numeric( $single_item_action ) ) {
				return;
			}

			$bp = buddypress();
			if ( ! bp_is_active( $single_item_component ) && ! $bp->members->nav->get_primary( array( 'slug' => $single_item_component ), false ) ) {
				bp_do_404();
				return;
			}

			// Navigation is generated by a component.
			if ( isset( $bp->{$single_item_component} ) ) {
				$screen_function = '';

				if ( isset( $bp->{$single_item_component}->sub_nav ) ) {
					$screen_functions = wp_list_pluck( $bp->{$single_item_component}->sub_nav, 'screen_function', 'slug' );

					if ( isset( $screen_functions[ $single_item_action ] ) ) {
						$screen_function = $screen_functions[ $single_item_action ];
					}
				}

				// Check if this nav item has been added from outside the Component's class.
				if ( ! $screen_function ) {
					$sub_nav = $this->nav->get( $single_item_component . '/' . $single_item_action );

					if ( isset( $sub_nav->screen_function ) && $sub_nav->screen_function ) {
						$screen_function = $sub_nav->screen_function;
					}
				}

				if ( ! $single_item_action || ! $screen_function || ! is_callable( $screen_function ) ) {
					bp_do_404();
					return;
				}

				// Navigation is not generated by a component.
			} else {
				$sub_nav = $bp->members->nav->get_secondary(
					array(
						'parent_slug' => $single_item_component,
						'slug'        => $single_item_action,
					),
					false
				);

				if ( ! $sub_nav ) {
					bp_do_404();
					return;
				}
			}
		}
	}

	/**
	 * Init the BP REST API.
	 *
	 * @since 5.0.0
	 * @since 6.0.0 Adds the Member Cover and Signup REST endpoints.
	 * @since 9.0.0 Moves the `BP_REST_Components_Endpoint` controller in `BP_Core` component.
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for
	 *                           description.
	 */
	public function rest_api_init( $controllers = array() ) {
		$controllers = array(
			'BP_REST_Members_Endpoint',
			'BP_REST_Attachments_Member_Avatar_Endpoint',
		);

		if ( bp_is_active( 'members', 'cover_image' ) ) {
			$controllers[] = 'BP_REST_Attachments_Member_Cover_Endpoint';
		}

		if ( bp_get_signup_allowed() ) {
			$controllers[] = 'BP_REST_Signup_Endpoint';
		}

		parent::rest_api_init( $controllers );
	}

	/**
	 * Register the BP Members Blocks.
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
				'bp/member' => array(
					'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-members/blocks/member',
					'render_callback' => 'bp_members_render_member_block',
				),
				'bp/members' => array(
					'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-members/blocks/members',
					'render_callback' => 'bp_members_render_members_block',
				),
				'bp/dynamic-members' => array(
					'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-members/blocks/dynamic-members',
					'render_callback' => 'bp_members_render_dynamic_members_block',
				),
				'bp/online-members'  => array(
					'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-members/blocks/online-members',
					'render_callback' => 'bp_members_render_online_members_block',
				),
				'bp/active-members'  => array(
					'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-members/blocks/active-members',
					'render_callback' => 'bp_members_render_active_members_block',
				),
			)
		);
	}
}
