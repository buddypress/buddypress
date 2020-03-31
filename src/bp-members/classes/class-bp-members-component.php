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
				'search_query_arg' => 'members_search',
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
			'filters',
			'template',
			'adminbar',
			'functions',
			'blocks',
			'widgets',
			'cache',
		);

		if ( bp_is_active( 'activity' ) ) {
			$includes[] = 'activity';
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
				require $this->path . 'bp-members/actions/random.php';
			}

			// Screens - Directory.
			if ( bp_is_members_directory() ) {
				require $this->path . 'bp-members/screens/directory.php';
			}
		}

		// Members - User main nav screen.
		if ( bp_is_user() ) {
			require $this->path . 'bp-members/screens/profile.php';

			// Action - Delete avatar.
			if ( is_user_logged_in()&& bp_is_user_change_avatar() && bp_is_action_variable( 'delete-avatar', 0 ) ) {
				require $this->path . 'bp-members/actions/delete-avatar.php';
			}

			// Sub-nav items.
			if ( is_user_logged_in() &&
				in_array( bp_current_action(), array( 'change-avatar', 'change-cover-image' ), true )
			) {
				require $this->path . 'bp-members/screens/' . bp_current_action() . '.php';
			}
		}

		// Members - Theme compatibility.
		if ( bp_is_members_component() || bp_is_user() ) {
			new BP_Members_Theme_Compat();
		}

		// Registration / Activation.
		if ( bp_is_register_page() || bp_is_activation_page() ) {
			if ( bp_is_register_page() ) {
				require $this->path . 'bp-members/screens/register.php';
			} else {
				require $this->path . 'bp-members/screens/activate.php';
			}

			// Theme compatibility.
			new BP_Registration_Theme_Compat();
		}
	}

	/**
	 * Set up bp-members global settings.
	 *
	 * The BP_MEMBERS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 *
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
		global $wpdb;

		$bp = buddypress();

		/** Component Globals ************************************************
		 */

		// Define a slug, as a fallback for backpat.
		if ( !defined( 'BP_MEMBERS_SLUG' ) ) {
			define( 'BP_MEMBERS_SLUG', $this->id );
		}

		// Fetch the default directory title.
		$default_directory_titles = bp_core_get_directory_page_default_titles();
		$default_directory_title  = $default_directory_titles[$this->id];

		// Override any passed args.
		$args = array(
			'slug'            => BP_MEMBERS_SLUG,
			'root_slug'       => isset( $bp->pages->members->slug ) ? $bp->pages->members->slug : BP_MEMBERS_SLUG,
			'has_directory'   => true,
			'directory_title' => isset( $bp->pages->members->title ) ? $bp->pages->members->title : $default_directory_title,
			'search_string'   => __( 'Search Members...', 'buddypress' ),
			'global_tables'   => array(
				'table_name_last_activity' => bp_core_get_table_prefix() . 'bp_activity',
				'table_name_signups'       => $wpdb->base_prefix . 'signups', // Signups is a global WordPress table.
			)
		);

		parent::setup_globals( $args );

		/** Logged in user ***************************************************
		 */

		// The core userdata of the user who is currently logged in.
		$bp->loggedin_user->userdata       = bp_core_get_core_userdata( bp_loggedin_user_id() );

		// Fetch the full name for the logged in user.
		$bp->loggedin_user->fullname       = isset( $bp->loggedin_user->userdata->display_name ) ? $bp->loggedin_user->userdata->display_name : '';

		// Hits the DB on single WP installs so get this separately.
		$bp->loggedin_user->is_super_admin = $bp->loggedin_user->is_site_admin = is_super_admin( bp_loggedin_user_id() );

		// The domain for the user currently logged in. eg: http://example.com/members/andy.
		$bp->loggedin_user->domain         = bp_core_get_user_domain( bp_loggedin_user_id() );

		/** Displayed user ***************************************************
		 */

		// The core userdata of the user who is currently being displayed.
		$bp->displayed_user->userdata = bp_core_get_core_userdata( bp_displayed_user_id() );

		// Fetch the full name displayed user.
		$bp->displayed_user->fullname = isset( $bp->displayed_user->userdata->display_name ) ? $bp->displayed_user->userdata->display_name : '';

		// The domain for the user currently being displayed.
		$bp->displayed_user->domain   = bp_core_get_user_domain( bp_displayed_user_id() );

		// Initialize the nav for the members component.
		$this->nav = new BP_Core_Nav();

		// If A user is displayed, check if there is a front template
		if ( bp_get_displayed_user() ) {
			$bp->displayed_user->front_template = bp_displayed_user_get_front_template();
		}

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
			$bp->canonical_stack['base_url'] = bp_displayed_user_domain();

			if ( bp_current_component() ) {
				$bp->canonical_stack['component'] = bp_current_component();
			}

			if ( bp_current_action() ) {
				$bp->canonical_stack['action'] = bp_current_action();
			}

			if ( !empty( $bp->action_variables ) ) {
				$bp->canonical_stack['action_variables'] = bp_action_variables();
			}

			// Looking at the single member root/home, so assume the default.
			if ( ! bp_current_component() ) {
				$bp->current_component = $bp->default_component;

			// The canonical URL will not contain the default component.
			} elseif ( bp_is_current_component( $bp->default_component ) && ! bp_current_action() ) {
				unset( $bp->canonical_stack['component'] );
			}

			// If we're on a spammer's profile page, only users with the 'bp_moderate' cap
			// can view subpages on the spammer's profile.
			//
			// users without the cap trying to access a spammer's subnav page will get
			// redirected to the root of the spammer's profile page.  this occurs by
			// by removing the component in the canonical stack.
			if ( bp_is_user_spammer( bp_displayed_user_id() ) && ! bp_current_user_can( 'bp_moderate' ) ) {
				unset( $bp->canonical_stack['component'] );
			}
		}
	}

	/**
	 * Get the Avatar and Cover image subnavs.
	 *
	 * @since 6.0.0
	 *
	 * @return array The Avatar and Cover image subnavs.
	 */
	public function get_avatar_cover_image_subnavs() {
		$subnavs = array();

		$access       = bp_core_can_edit_settings();
		$slug         = bp_get_profile_slug();
		$profile_link = bp_get_members_component_link( $slug );

		// Change Avatar.
		if ( buddypress()->avatar->show_avatars ) {
			$subnavs[] = array(
				'name'            => _x( 'Change Profile Photo', 'Profile header sub menu', 'buddypress' ),
				'slug'            => 'change-avatar',
				'parent_url'      => $profile_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bp_members_screen_change_avatar',
				'position'        => 30,
				'user_has_access' => $access
			);
		}

		// Change Cover image.
		if ( bp_displayed_user_use_cover_image_header() ) {
			$subnavs[] = array(
				'name'            => _x( 'Change Cover Image', 'Profile header sub menu', 'buddypress' ),
				'slug'            => 'change-cover-image',
				'parent_url'      => $profile_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bp_members_screen_change_cover_image',
				'position'        => 40,
				'user_has_access' => $access
			);
		}

		return $subnavs;
	}

	/**
	 * Set up fall-back component navigation if XProfile is inactive.
	 *
	 * @since 1.5.0
	 *
	 * @see BP_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for
	 *                        description.
	 * @param array $sub_nav  Optional. See BP_Component::setup_nav() for
	 *                        description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Don't set up navigation if there's no member.
		if ( ! is_user_logged_in() && ! bp_is_user() ) {
			return;
		}

		$is_xprofile_active = bp_is_active( 'xprofile' );

		// Bail if XProfile component is active and there's no custom front page for the user.
		if ( ! bp_displayed_user_has_front_template() && $is_xprofile_active ) {
			add_action( 'bp_xprofile_setup_nav', array( $this, 'setup_xprofile_nav' ) );
			return;
		}

		// Determine user to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		// Set slug to profile in case the xProfile component is not active
		$slug = bp_get_profile_slug();

		// Defaults to empty navs
		$this->main_nav = array();
		$this->sub_nav  = array();

		if ( ! $is_xprofile_active ) {
			$this->main_nav = array(
				'name'                => _x( 'Profile', 'Member profile main navigation', 'buddypress' ),
				'slug'                => $slug,
				'position'            => 20,
				'screen_function'     => 'bp_members_screen_display_profile',
				'default_subnav_slug' => 'public',
				'item_css_id'         => buddypress()->profile->id
			);

		/**
		 * The xProfile component is active.
		 *
		 * We need to make sure the Change Avatar and Change Cover Image subnavs are
		 * added just like it was the case before.
		 */
		} else {
			add_action( 'bp_xprofile_setup_nav', array( $this, 'setup_xprofile_nav' ) );
		}

		/**
		 * Setup the subnav items for the member profile.
		 *
		 * This is required in case there's a custom front or in case the xprofile component
		 * is not active.
		 */
		$this->sub_nav = array(
			'name'            => _x( 'View', 'Member profile view', 'buddypress' ),
			'slug'            => 'public',
			'parent_url'      => trailingslashit( $user_domain . $slug ),
			'parent_slug'     => $slug,
			'screen_function' => 'bp_members_screen_display_profile',
			'position'        => 10
		);

		/**
		 * If there's a front template the members component nav
		 * will be there to display the user's front page.
		 */
		if ( bp_displayed_user_has_front_template() ) {
			$main_nav = array(
				'name'                => _x( 'Home', 'Member Home page', 'buddypress' ),
				'slug'                => 'front',
				'position'            => 5,
				'screen_function'     => 'bp_members_screen_display_profile',
				'default_subnav_slug' => 'public',
			);

			// We need a dummy subnav for the front page to load.
			$front_subnav = $this->sub_nav;
			$front_subnav['parent_slug'] = 'front';

			// In case the subnav is displayed in the front template
			$front_subnav['parent_url'] = trailingslashit( $user_domain . 'front' );

			// Set the subnav
			$sub_nav[] = $front_subnav;

			/**
			 * If the profile component is not active, we need to create a new
			 * nav to display the WordPress profile.
			 */
			if ( ! $is_xprofile_active ) {
				add_action( 'bp_members_setup_nav', array( $this, 'setup_profile_nav' ) );
			}

		/**
		 * If there's no front template and xProfile is not active, the members
		 * component nav will be there to display the WordPress profile
		 */
		} else {
			$main_nav  = $this->main_nav;
			$sub_nav   = array( $this->sub_nav );

			if ( ! $is_xprofile_active ) {
				$sub_nav = array_merge( $sub_nav, $this->get_avatar_cover_image_subnavs() );
			}
		}


		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up a profile nav in case the xProfile
	 * component is not active and a front template is
	 * used.
	 *
	 * @since 2.6.0
	 */
	public function setup_profile_nav() {
		if ( empty( $this->main_nav ) || empty( $this->sub_nav ) ) {
			return;
		}

		// Add the main nav
		bp_core_new_nav_item( $this->main_nav, 'members' );

		// Add the sub nav item.
		bp_core_new_subnav_item( $this->sub_nav, 'members' );

		// Get the Avatar and cover image subnavs.
		$this->setup_xprofile_nav();
	}

	/**
	 * Set up the xProfile nav.
	 *
	 * @since 6.0.0
	 */
	public function setup_xprofile_nav() {
		// Get the Avatar and cover image subnavs.
		$items = $this->get_avatar_cover_image_subnavs();

		foreach ( $items as $item ) {
			bp_core_new_subnav_item( $item, 'members' );
		}
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
		$profile_link = trailingslashit( bp_loggedin_user_domain() . bp_get_profile_slug() );

		if ( ! $admin_bar_menu_id ) {
			$admin_bar_menu_id = $this->id;
		}

		// Edit Avatar.
		if ( buddypress()->avatar->show_avatars ) {
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $admin_bar_menu_id,
				'id'       => 'my-account-' . $admin_bar_menu_id . '-change-avatar',
				'title'    => _x( 'Change Profile Photo', 'My Account Profile sub nav', 'buddypress' ),
				'href'     => trailingslashit( $profile_link . 'change-avatar' ),
				'position' => 30
			);
		}

		// Edit Cover Image
		if ( bp_displayed_user_use_cover_image_header() ) {
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $admin_bar_menu_id,
				'id'       => 'my-account-' . $admin_bar_menu_id . '-change-cover-image',
				'title'    => _x( 'Change Cover Image', 'My Account Profile sub nav', 'buddypress' ),
				'href'     => trailingslashit( $profile_link . 'change-cover-image' ),
				'position' => 40
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
			$profile_link = trailingslashit( bp_loggedin_user_domain() . bp_get_profile_slug() );

			if ( ! bp_is_active( 'xprofile' ) ) {
				// Add the "Profile" sub menu.
				$wp_admin_nav[] = array(
					'parent' => buddypress()->my_account_menu_id,
					'id'     => 'my-account-' . $this->id,
					'title'  => _x( 'Profile', 'My Account Profile', 'buddypress' ),
					'href'   => $profile_link
				);

				// View Profile.
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-public',
					'title'    => _x( 'View', 'My Account Profile sub nav', 'buddypress' ),
					'href'     => $profile_link,
					'position' => 10
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
			'bp_member_type'
		) );

		parent::setup_cache_groups();
	}

	/**
	 * Init the BP REST API.
	 *
	 * @since 5.0.0
	 * @since 6.0.0 Adds the Member Cover and Signup REST endpoints.
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for
	 *                           description.
	 */
	public function rest_api_init( $controllers = array() ) {
		$controllers = array(
			/**
			 * As the Members component is always loaded,
			 * let's register the Components endpoint here.
			 */
			'BP_REST_Components_Endpoint',
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
	 *
	 * @param array $blocks Optional. See BP_Component::blocks_init() for
	 *                      description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init(
			array(
				'bp/member' => array(
					'name'               => 'bp/member',
					'editor_script'      => 'bp-member-block',
					'editor_script_url'  => plugins_url( 'js/blocks/member.js', dirname( __FILE__ ) ),
					'editor_script_deps' => array(
						'wp-blocks',
						'wp-element',
						'wp-components',
						'wp-i18n',
						'wp-editor',
						'wp-compose',
						'wp-data',
						'wp-block-editor',
						'bp-block-components',
					),
					'style'              => 'bp-member-block',
					'style_url'          => plugins_url( 'css/blocks/member.css', dirname( __FILE__ ) ),
					'render_callback'    => 'bp_members_render_member_block',
					'attributes'         => array(
						'itemID'              => array(
							'type'    => 'integer',
							'default' => 0,
						),
						'avatarSize'          => array(
							'type'    => 'string',
							'default' => 'full',
						),
						'displayMentionSlug'  => array(
							'type'    => 'boolean',
							'default' => true,
						),
						'displayActionButton' => array(
							'type'    => 'boolean',
							'default' => true,
						),
						'displayCoverImage'   => array(
							'type'    => 'boolean',
							'default' => true,
						),
					),
				),
			)
		);
	}
}
