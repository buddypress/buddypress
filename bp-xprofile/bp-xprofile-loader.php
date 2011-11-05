<?php
/**
 * BuddyPress XProfile Loader
 *
 * An extended profile component for users. This allows site admins to create
 * groups of fields for users to enter information about themselves.
 *
 * @package BuddyPress
 * @subpackage XProfile Core
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_XProfile_Component extends BP_Component {

	/**
	 * Start the xprofile component creation process
	 *
	 * @since 1.5
	 */
	function __construct() {
		parent::start(
			'xprofile',
			__( 'Extended Profiles', 'buddypress' ),
			BP_PLUGIN_DIR
		);
	}

	/**
	 * Include files
	 */
	function includes() {
		$includes = array(
			'cssjs',
			'cache',
			'actions',
			'activity',
			'screens',
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
	 * @since 1.5
	 * @global obj $bp
	 */
	function setup_globals() {
		global $bp;

		// Define a slug, if necessary
		if ( !defined( 'BP_XPROFILE_SLUG' ) )
			define( 'BP_XPROFILE_SLUG', 'profile' );

		// Assign the base group and fullname field names to constants to use
		// in SQL statements
		define ( 'BP_XPROFILE_BASE_GROUP_NAME',     stripslashes( $bp->site_options['bp-xprofile-base-group-name']     ) );
		define ( 'BP_XPROFILE_FULLNAME_FIELD_NAME', stripslashes( $bp->site_options['bp-xprofile-fullname-field-name'] ) );

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
	 * @global obj $bp
	 */
	function setup_nav() {
		global $bp;

		// Add 'Profile' to the main navigation
		$main_nav = array(
			'name'                => __( 'Profile', 'buddypress' ),
			'slug'                => $this->slug,
			'position'            => 20,
			'screen_function'     => 'xprofile_screen_display_profile',
			'default_subnav_slug' => 'public',
			'item_css_id'         => $this->id
		);

		$profile_link = trailingslashit( $bp->loggedin_user->domain . $this->slug );

		// Add the subnav items to the profile
		$sub_nav[] = array(
			'name'            => __( 'Public', 'buddypress' ),
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
			'position'        => 20
		);

		// Change Avatar
		$sub_nav[] = array(
			'name'            => __( 'Change Avatar', 'buddypress' ),
			'slug'            => 'change-avatar',
			'parent_url'      => $profile_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'xprofile_screen_change_avatar',
			'position'        => 30
		);

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the admin bar
	 *
	 * @global obj $bp
	 */
	function setup_admin_bar() {
		global $bp;

		// Prevent debug notices
		$wp_admin_nav = array();

		// Menus for logged in user
		if ( is_user_logged_in() ) {

			// Profile link
			$profile_link = trailingslashit( $bp->loggedin_user->domain . $this->slug );

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
				'id'     => 'my-account-' . $this->id . '-view',
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
	 *
	 * @global obj $bp
	 */
	function setup_title() {
		global $bp;

		if ( bp_is_profile_component() ) {
			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'My Profile', 'buddypress' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => $bp->displayed_user->id,
					'type'    => 'thumb'
				) );
				$bp->bp_options_title = $bp->displayed_user->fullname;
			}
		}

		parent::setup_title();
	}
}
// Create the xprofile component
if ( !isset( $bp->profile->id ) )
	$bp->profile = new BP_XProfile_Component();

?>
