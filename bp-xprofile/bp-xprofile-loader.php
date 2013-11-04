<?php

/**
 * BuddyPress XProfile Loader
 *
 * An extended profile component for users. This allows site admins to create
 * groups of fields for users to enter information about themselves.
 *
 * @package BuddyPress
 * @subpackage XProfileLoader
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_XProfile_Component extends BP_Component {
	/**
	 * Profile field types
	 *
	 * @since BuddyPress (1.5)
	 * @var array
	 */
	public $field_types;

	/**
	 * The acceptable visibility levels for xprofile fields.
	 *
	 * @see bp_xprofile_get_visibility_levels()
	 * @since BuddyPress (1.6)
	 */
	var $visibility_levels = array();

	/**
	 * Start the xprofile component creation process
	 *
	 * @since BuddyPress (1.5)
	 */
	function __construct() {
		parent::start(
			'xprofile',
			__( 'Extended Profiles', 'buddypress' ),
			BP_PLUGIN_DIR,
			array(
				'adminbar_myaccount_order' => 20
			)
		);
	}

	/**
	 * Include files
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'cssjs',
			'cache',
			'actions',
			'activity',
			'screens',
			'caps',
			'classes',
			'filters',
			'template',
			'buddybar',
			'functions',
		);

		if ( is_admin() )
			$includes[] = 'admin';

		parent::includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * The BP_XPROFILE_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress (1.5)
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Define a slug, if necessary
		if ( !defined( 'BP_XPROFILE_SLUG' ) )
			define( 'BP_XPROFILE_SLUG', 'profile' );

		// Assign the base group and fullname field names to constants
		// to use in SQL statements.
		// Defined conditionally to accommodate unit tests
		if ( ! defined( 'BP_XPROFILE_BASE_GROUP_NAME' ) ) {
			define( 'BP_XPROFILE_BASE_GROUP_NAME', stripslashes( $bp->site_options['bp-xprofile-base-group-name'] ) );
		}

		if ( ! defined( 'BP_XPROFILE_FULLNAME_FIELD_NAME' ) ) {
			define( 'BP_XPROFILE_FULLNAME_FIELD_NAME', stripslashes( $bp->site_options['bp-xprofile-fullname-field-name'] ) );
		}

		// Set the support field type ids
		$this->field_types = apply_filters( 'xprofile_field_types', array(
			'textbox',
			'textarea',
			'radio',
			'checkbox',
			'selectbox',
			'multiselectbox',
			'datebox'
		) );

		// Register the visibility levels. See bp_xprofile_get_visibility_levels() to filter
		$this->visibility_levels = array(
			'public' => array(
				'id'	  => 'public',
				'label' => __( 'Everyone', 'buddypress' )
			),
			'adminsonly' => array(
				'id'	  => 'adminsonly',
				'label' => __( 'Only Me', 'buddypress' )
			),
			'loggedin' => array(
				'id'	  => 'loggedin',
				'label' => __( 'All Members', 'buddypress' )
			)
		);

		if ( bp_is_active( 'friends' ) ) {
			$this->visibility_levels['friends'] = array(
				'id'	=> 'friends',
				'label'	=> __( 'My Friends', 'buddypress' )
			);
		}

		// Tables
		$global_tables = array(
			'table_name_data'   => $bp->table_prefix . 'bp_xprofile_data',
			'table_name_groups' => $bp->table_prefix . 'bp_xprofile_groups',
			'table_name_fields' => $bp->table_prefix . 'bp_xprofile_fields',
			'table_name_meta'   => $bp->table_prefix . 'bp_xprofile_meta',
		);

		$globals = array(
			'slug'                  => BP_XPROFILE_SLUG,
			'has_directory'         => false,
			'notification_callback' => 'xprofile_format_notifications',
			'global_tables'         => $global_tables
		);

		parent::setup_globals( $globals );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		$sub_nav = array();

		// Add 'Profile' to the main navigation
		$main_nav = array(
			'name'                => __( 'Profile', 'buddypress' ),
			'slug'                => $this->slug,
			'position'            => 20,
			'screen_function'     => 'xprofile_screen_display_profile',
			'default_subnav_slug' => 'public',
			'item_css_id'         => $this->id
		);

		// Determine user to use
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$profile_link = trailingslashit( $user_domain . $this->slug );

		// Add the subnav items to the profile
		$sub_nav[] = array(
			'name'            => __( 'View', 'buddypress' ),
			'slug'            => 'public',
			'parent_url'      => $profile_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'xprofile_screen_display_profile',
			'position'        => 10
		);

		// Edit Profile
		$sub_nav[] = array(
			'name'            => __( 'Edit', 'buddypress' ),
			'slug'            => 'edit',
			'parent_url'      => $profile_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'xprofile_screen_edit_profile',
			'position'        => 20,
			'user_has_access' => bp_core_can_edit_settings()
		);

		// Change Avatar
		$sub_nav[] = array(
			'name'            => __( 'Change Avatar', 'buddypress' ),
			'slug'            => 'change-avatar',
			'parent_url'      => $profile_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'xprofile_screen_change_avatar',
			'position'        => 30,
			'user_has_access' => bp_core_can_edit_settings()
		);

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the Toolbar
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		$bp = buddypress();

		// Prevent debug notices
		$wp_admin_nav = array();

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			// Profile link
			$profile_link = trailingslashit( bp_loggedin_user_domain() . $this->slug );

			// Add the "Profile" sub menu
			$wp_admin_nav[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Profile', 'buddypress' ),
				'href'   => trailingslashit( $profile_link )
			);

			// View Profile
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-public',
				'title'  => __( 'View', 'buddypress' ),
				'href'   => trailingslashit( $profile_link . 'public' )
			);

			// Edit Profile
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-edit',
				'title'  => __( 'Edit', 'buddypress' ),
				'href'   => trailingslashit( $profile_link . 'edit' )
			);

			// Edit Profile
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-change-avatar',
				'title'  => __( 'Change Avatar', 'buddypress' ),
				'href'   => trailingslashit( $profile_link . 'change-avatar' )
			);

		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Sets up the title for pages and <title>
	 */
	function setup_title() {
		$bp = buddypress();

		if ( bp_is_profile_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'My Profile', 'buddypress' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					'alt'	  => sprintf( __( 'Profile picture of %s', 'buddypress' ), bp_get_displayed_user_fullname() )
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}
}

function bp_setup_xprofile() {
	$bp = buddypress();

	if ( !isset( $bp->profile->id ) )
		$bp->profile = new BP_XProfile_Component();
}
add_action( 'bp_setup_components', 'bp_setup_xprofile', 6 );
