<?php
/**
 * BuddyPress XProfile Loader.
 *
 * An extended profile component for users. This allows site admins to create
 * groups of fields for users to enter information about themselves.
 *
 * @package BuddyPress
 * @subpackage XProfileLoader
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates our XProfile component.
 *
 * @since 1.5.0
 */
#[AllowDynamicProperties]
class BP_XProfile_Component extends BP_Component {

	/**
	 * Profile field types.
	 *
	 * @since 1.5.0
	 * @var array
	 */
	public $field_types;

	/**
	 * The acceptable visibility levels for xprofile fields.
	 *
	 * @see bp_xprofile_get_visibility_levels()
	 *
	 * @since 1.6.0
	 * @var array
	 */
	public $visibility_levels = array();

	/**
	 * Start the xprofile component creation process.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		parent::start(
			'xprofile',
			_x( 'Extended Profiles', 'Component page <title>', 'buddypress' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 20,
			)
		);

		$this->setup_hooks();
	}

	/**
	 * Include files.
	 *
	 * @since 1.5.0
	 *
	 * @param array $includes Array of files to include.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'cssjs',
			'cache',
			'caps',
			'filters',
			'template',
			'functions',
		);

		// Conditional includes.
		if ( bp_is_active( 'activity' ) ) {
			$includes[] = 'activity';
		}
		if ( bp_is_active( 'notifications' ) ) {
			$includes[] = 'notifications';
		}
		if ( bp_is_active( 'settings' ) ) {
			$includes[] = 'settings';
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

		// Bail if not on a user page.
		if ( ! bp_is_user() ) {
			return;
		}

		// User nav.
		if ( bp_is_profile_component() ) {
			require_once $this->path . 'bp-xprofile/screens/public.php';

			// Sub-nav items.
			if ( is_user_logged_in() && 'edit' === bp_current_action() ) {
				require_once $this->path . 'bp-xprofile/screens/edit.php';
			}
		}

		// Settings.
		if ( is_user_logged_in() && bp_is_user_settings_profile() ) {
			require_once $this->path . 'bp-xprofile/screens/settings-profile.php';
		}
	}

	/**
	 * Setup globals.
	 *
	 * The BP_XPROFILE_SLUG constant is deprecated.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args Array of globals to set up.
	 */
	public function setup_globals( $args = array() ) {
		$bp           = buddypress();
		$default_slug = 'profile';

		// @deprecated.
		if ( defined( 'BP_XPROFILE_SLUG' ) ) {
			_doing_it_wrong( 'BP_XPROFILE_SLUG', esc_html__( 'Slug constants are deprecated.', 'buddypress' ), 'BuddyPress 12.0.0' );
			$default_slug = BP_XPROFILE_SLUG;
		}

		// Assign the base group and fullname field names to constants
		// to use in SQL statements.
		// Defined conditionally to accommodate unit tests.
		if ( ! defined( 'BP_XPROFILE_BASE_GROUP_NAME' ) ) {
			define( 'BP_XPROFILE_BASE_GROUP_NAME', stripslashes( bp_core_get_root_option( 'avatar_default' ) ) );
		}

		if ( ! defined( 'BP_XPROFILE_FULLNAME_FIELD_NAME' ) ) {
			define( 'BP_XPROFILE_FULLNAME_FIELD_NAME', stripslashes( bp_core_get_root_option( 'bp-xprofile-fullname-field-name' ) ) );
		}

		/**
		 * Filters the supported field type IDs.
		 *
		 * @since 1.1.0
		 *
		 * @param array $value Array of IDs for the supported field types.
		 */
		$this->field_types = apply_filters( 'xprofile_field_types', array_keys( bp_xprofile_get_field_types() ) );

		/*
		 * 'option' is a special case. It is not a top-level field, so
		 * does not have an associated BP_XProfile_Field_Type class,
		 * but it must be explicitly allowed.
		 */
		$this->field_types[] = 'option';

		// Register the visibility levels. See bp_xprofile_get_visibility_levels() to filter.
		$this->visibility_levels = array(
			'public' => array(
				'id'	  => 'public',
				'label' => _x( 'Everyone', 'Visibility level setting', 'buddypress' ),
			),
			'adminsonly' => array(
				'id'	  => 'adminsonly',
				'label' => _x( 'Only Me', 'Visibility level setting', 'buddypress' ),
			),
			'loggedin' => array(
				'id'	  => 'loggedin',
				'label' => _x( 'All Members', 'Visibility level setting', 'buddypress' ),
			),
		);

		if ( bp_is_active( 'friends' ) ) {
			$this->visibility_levels['friends'] = array(
				'id'	=> 'friends',
				'label'	=> _x( 'My Friends', 'Visibility level setting', 'buddypress' ),
			);
		}

		// Tables.
		$global_tables = array(
			'table_name_data'   => $bp->table_prefix . 'bp_xprofile_data',
			'table_name_groups' => $bp->table_prefix . 'bp_xprofile_groups',
			'table_name_fields' => $bp->table_prefix . 'bp_xprofile_fields',
			'table_name_meta'   => $bp->table_prefix . 'bp_xprofile_meta',
		);

		$meta_tables = array(
			'xprofile_group' => $bp->table_prefix . 'bp_xprofile_meta',
			'xprofile_field' => $bp->table_prefix . 'bp_xprofile_meta',
			'xprofile_data'  => $bp->table_prefix . 'bp_xprofile_meta',
		);

		$globals = array(
			'slug'                  => $default_slug,
			'has_directory'         => false,
			'notification_callback' => 'xprofile_format_notifications',
			'global_tables'         => $global_tables,
			'meta_tables'           => $meta_tables,
		);

		parent::setup_globals( $globals );
	}

	/**
	 * Register component navigation.
	 *
	 * @since 12.0.0
	 *
	 * @param array $main_nav See `BP_Component::register_nav()` for details.
	 * @param array $sub_nav  See `BP_Component::register_nav()` for details.
	 */
	public function register_nav( $main_nav = array(), $sub_nav = array() ) {
		$slug = bp_get_profile_slug();

		// Add 'Profile' to the main navigation.
		$main_nav = array(
			'name'                => _x( 'Profile', 'Profile header menu', 'buddypress' ),
			'slug'                => $slug,
			'position'            => 20,
			'screen_function'     => 'xprofile_screen_display_profile',
			'default_subnav_slug' => 'public',
			'item_css_id'         => $this->id,
		);

		// Add the subnav items to the profile.
		$sub_nav[] = array(
			'name'            => _x( 'View', 'Profile header sub menu', 'buddypress' ),
			'slug'            => 'public',
			'parent_slug'     => $slug,
			'screen_function' => 'xprofile_screen_display_profile',
			'position'        => 10,
		);

		// Edit Profile.
		$sub_nav[] = array(
			'name'                     => _x( 'Edit','Profile header sub menu', 'buddypress' ),
			'slug'                     => 'edit',
			'parent_slug'              => $slug,
			'screen_function'          => 'xprofile_screen_edit_profile',
			'position'                 => 20,
			'user_has_access'          => false,
			'user_has_access_callback' => 'bp_core_can_edit_settings',
		);

		parent::register_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the Settings > Profile nav item.
	 *
	 * Loaded in a separate method because the Settings component may not
	 * be loaded in time for BP_XProfile_Component::setup_nav().
	 *
	 * @since 2.1.0
	 * @deprecated 12.0.0
	 */
	public function setup_settings_nav() {
		_deprecated_function( __METHOD__, '12.0.0' );
	}

	/**
	 * Set up the Admin Bar.
	 *
	 * @since 1.5.0
	 *
	 * @param array $wp_admin_nav Admin Bar items.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {
			$profile_slug = bp_get_profile_slug();

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

			// Edit Profile.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-edit',
				'title'    => _x( 'Edit', 'My Account Profile sub nav', 'buddypress' ),
				'href'     => bp_loggedin_user_url( bp_members_get_path_chunks( array( $profile_slug, 'edit' ) ) ),
				'position' => 20,
			);
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Add custom hooks.
	 *
	 * @since 2.0.0
	 */
	public function setup_hooks() {
		add_filter( 'bp_settings_admin_nav', array( $this, 'setup_settings_admin_nav' ), 2 );
	}

	/**
	 * Sets up the title for pages and <title>.
	 *
	 * @since 1.5.0
	 */
	public function setup_title() {

		if ( bp_is_profile_component() ) {
			$bp = buddypress();

			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = _x( 'My Profile', 'Page title', 'buddypress' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',

					/* translators: %s: member name */
					'alt'	  => sprintf( _x( 'Profile picture of %s', 'Avatar alt', 'buddypress' ), bp_get_displayed_user_fullname() ),
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();
			}
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
			'bp_xprofile',
			'bp_xprofile_data',
			'bp_xprofile_fields',
			'bp_xprofile_groups',
			'xprofile_meta',
			'bp_user_mid',
		) );

		parent::setup_cache_groups();
	}

	/**
	 * Adds "Settings > Profile" subnav item under the "Settings" adminbar menu.
	 *
	 * @since 2.0.0
	 *
	 * @param array $wp_admin_nav The settings adminbar nav array.
	 * @return array
	 */
	public function setup_settings_admin_nav( $wp_admin_nav ) {
		// Add the "Profile" subnav item.
		$wp_admin_nav[] = array(
			'parent' => 'my-account-' . buddypress()->settings->id,
			'id'     => 'my-account-' . buddypress()->settings->id . '-profile',
			'title'  => _x( 'Profile', 'My Account Settings sub nav', 'buddypress' ),
			'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( bp_get_settings_slug(), 'profile' ) ) ),
		);

		return $wp_admin_nav;
	}

	/**
	 * Init the BP REST API.
	 *
	 * @since 5.0.0
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for
	 *                           description.
	 */
	public function rest_api_init( $controllers = array() ) {
		parent::rest_api_init( array(
			'BP_REST_XProfile_Fields_Endpoint',
			'BP_REST_XProfile_Field_Groups_Endpoint',
			'BP_REST_XProfile_Data_Endpoint',
		) );
	}

	/**
	 * Register the BP xProfile Blocks.
	 *
	 * @since 9.0.0
	 *
	 * @param array $blocks Optional. See BP_Component::blocks_init() for
	 *                      description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init( array() );
	}
}
