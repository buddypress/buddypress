<?php

/**
 * Main BuddyPress Admin Class
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'BP_Admin' ) ) :
/**
 * Loads BuddyPress plugin admin area
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 * @since BuddyPress (1.6)
 */
class BP_Admin {

	/**
	 * Instance of the setup wizard
	 *
	 * @since BuddyPress (1.6)
	 * @var BP_Core_Setup_Wizard
	 */
	public $wizard;

	/** Directory *************************************************************/

	/**
	 * @var string Path to the BuddyPress admin directory
	 */
	public $admin_dir = '';

	/** URLs ******************************************************************/

	/**
	 * @var string URL to the BuddyPress admin directory
	 */
	public $admin_url = '';

	/**
	 * @var string URL to the BuddyPress images directory
	 */
	public $images_url = '';

	/**
	 * @var string URL to the BuddyPress admin CSS directory
	 */
	public $css_url = '';

	/**
	 * @var string URL to the BuddyPress admin JS directory
	 */
	public $js_url = '';


	/** Methods ***************************************************************/

	/**
	 * The main BuddyPress admin loader
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @uses BBP_Admin::setup_globals() Setup the globals needed
	 * @uses BBP_Admin::includes() Include the required files
	 * @uses BBP_Admin::setup_actions() Setup the hooks and actions
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Admin globals
	 *
	 * @since BuddyPress (1.6)
	 * @access private
	 */
	private function setup_globals() {
		global $bp;

		// Admin url
		$this->admin_dir  = trailingslashit( $bp->plugin_dir . 'bp-core/admin' );

		// Admin url
		$this->admin_url  = trailingslashit( $bp->plugin_url . 'bp-core/admin' );

		// Admin images URL
		$this->images_url = trailingslashit( $this->admin_url . 'images' );

		// Admin css URL
		$this->css_url    = trailingslashit( $this->admin_url . 'css'    );

		// Admin css URL
		$this->js_url     = trailingslashit( $this->admin_url . 'js'     );
	}

	/**
	 * Include required files
	 *
	 * @since BuddyPress (1.6)
	 * @access private
	 */
	private function includes() {

		// If in maintenance mode, only include updater and schema
		if ( bp_get_maintenance_mode() ) {
			require( $this->admin_dir . 'bp-core-schema.php' );
			require( $this->admin_dir . 'bp-core-update.php' );

		// No update needed so proceed with loading everything
		} else {
			require( $this->admin_dir . 'bp-core-settings.php'   );
			require( $this->admin_dir . 'bp-core-functions.php'  );
			require( $this->admin_dir . 'bp-core-components.php' );
			require( $this->admin_dir . 'bp-core-slugs.php'      );
		}
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @since BuddyPress (1.6)
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 * @uses add_filter() To add various filters
	 */
	private function setup_actions() {

		// Start the wizard if in maintenance mode
		if ( bp_get_maintenance_mode() ) {
			add_action( bp_core_admin_hook(), array( $this, 'start_wizard' ), 2 );
		}

		/** General Actions ***************************************************/

		// Attach the BuddyPress admin_init action to the WordPress admin_init action.
		add_action( 'admin_init',            array( $this, 'admin_init'  ) );

		// Add some page specific output to the <head>
		add_action( 'admin_head',            array( $this, 'admin_head'  ) );

		// Add menu item to settings menu
		add_action( bp_core_admin_hook(),    array( $this, 'admin_menus' ), 5 );

		// Add notice if not using a BuddyPress theme
		add_action( 'admin_notices',         array( $this, 'admin_notices' ) );
		add_action( 'network_admin_notices', array( $this, 'admin_notices' ) );

		// Enqueue all admin JS and CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts'   ) );

		/** BuddyPress Actions ************************************************/

		// Add settings
		add_action( 'bp_admin_init',      array( $this, 'register_admin_settings' ) );

		/** Filters ***********************************************************/

		// Add link to settings page
		add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ), 10, 2 );
	}

	public function start_wizard() {
		$this->wizard = new BP_Core_Setup_Wizard;
	}

	/**
	 * Add the navigational menu elements
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @uses add_management_page() To add the Recount page in Tools section
	 * @uses add_options_page() To add the Forums settings page in Settings
	 *                           section
	 */
	public function admin_menus() {

		// In maintenance mode
		if ( bp_get_maintenance_mode() ) {

			if ( !current_user_can( 'manage_options' ) )
				return;

			if ( bp_get_maintenance_mode() == 'install' )
				$status = __( 'BuddyPress Setup', 'buddypress' );
			else
				$status = __( 'Update BuddyPress',  'buddypress' );

			if ( bp_get_wizard() ) {
				if ( ! is_multisite() || bp_is_multiblog_mode() ) {
					$hook = add_dashboard_page( $status, $status, 'manage_options', 'bp-wizard', array( bp_get_wizard(), 'html' ) );
				} else {
					$hook = add_submenu_page( 'update-core.php', $status, $status, 'manage_options', 'bp-wizard', array( bp_get_wizard(), 'html' ) );
				}
			}

		// Not in maintenance mode
		} else {

			// Bail if user cannot moderate
			if ( ! bp_current_user_can( 'manage_options' ) )
				return;

			$hooks = array();
			$page  = bp_core_do_network_admin()  ? 'settings.php' : 'options-general.php';

			// Changed in BP 1.6 . See bp_core_admin_backpat_menu()
			$hooks[] = add_menu_page(
				__( 'BuddyPress', 'buddypress' ),
				__( 'BuddyPress', 'buddypress' ),
				'manage_options',
				'bp-general-settings',
				'bp_core_admin_backpat_menu',
				''
			);

			$hooks[] = add_submenu_page(
				'bp-general-settings',
				__( 'BuddyPress Help', 'buddypress' ),
				__( 'Help', 'buddypress' ),
				'manage_options',
				'bp-general-settings',
				'bp_core_admin_backpat_page'
			);

			// Add the option pages
			$hooks[] = add_submenu_page(
				$page,
				__( 'BuddyPress Components', 'buddypress' ),
				__( 'BuddyPress', 'buddypress' ),
				'manage_options',
				'bp-components',
				'bp_core_admin_components_settings'
			);

			$hooks[] = add_submenu_page(
				$page,
				__( 'BuddyPress Pages', 'buddypress' ),
				__( 'BuddyPress Pages', 'buddypress' ),
				'manage_options',
				'bp-page-settings',
				'bp_core_admin_slugs_settings'
			);

			$hooks[] = add_submenu_page(
				$page,
				__( 'BuddyPress Settings', 'buddypress' ),
				__( 'BuddyPress Settings', 'buddypress' ),
				'manage_options',
				'bp-settings',
				'bp_core_admin_settings'
			);

			// Fudge the highlighted subnav item when on a BuddyPress admin page
			foreach( $hooks as $hook ) {
				add_action( "admin_head-$hook", 'bp_core_modify_admin_menu_highlight' );
			}
		}
	}

	/**
	 * Register the settings
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @uses add_settings_section() To add our own settings section
	 * @uses add_settings_field() To add various settings fields
	 * @uses register_setting() To register various settings
	 * @uses do_action() Calls 'bp_register_admin_settings'
	 */
	public function register_admin_settings() {

		/** Main Section ******************************************************/

		// Add the main section
		add_settings_section( 'bp_main',            __( 'Main Settings',    'buddypress' ), 'bp_admin_setting_callback_main_section',     'buddypress'            );

		// Hide toolbar for logged out users setting
		add_settings_field( 'hide-loggedout-adminbar',        __( 'Toolbar',        'buddypress' ), 'bp_admin_setting_callback_admin_bar',        'buddypress', 'bp_main' );
	 	register_setting  ( 'buddypress',           'hide-loggedout-adminbar',        'intval'                                                                              );

		// Only show 'switch to Toolbar' option if the user chose to retain the BuddyBar during the 1.6 upgrade
		if ( (bool) bp_get_option( '_bp_force_buddybar', false ) ) {
			add_settings_field( '_bp_force_buddybar', __( 'Toolbar', 'buddypress' ), 'bp_admin_setting_callback_force_buddybar', 'buddypress', 'bp_main' );
		 	register_setting( 'buddypress', '_bp_force_buddybar', 'bp_admin_sanitize_callback_force_buddybar' );
		}

		// Allow account deletion
		add_settings_field( 'bp-disable-account-deletion', __( 'Account Deletion', 'buddypress' ), 'bp_admin_setting_callback_account_deletion', 'buddypress', 'bp_main' );
	 	register_setting  ( 'buddypress',           'bp-disable-account-deletion', 'intval'                                                                              );

		/** XProfile Section **************************************************/

		if ( bp_is_active( 'xprofile' ) ) {

			// Add the main section
			add_settings_section( 'bp_xprofile',      __( 'Profile Settings', 'buddypress' ), 'bp_admin_setting_callback_xprofile_section', 'buddypress'                );

			// Allow avatar uploads
			add_settings_field( 'bp-disable-avatar-uploads', __( 'Avatar Uploads',   'buddypress' ), 'bp_admin_setting_callback_avatar_uploads',   'buddypress', 'bp_xprofile' );
			register_setting  ( 'buddypress',         'bp-disable-avatar-uploads',   'intval'                                                                                  );

			// Profile sync setting
			add_settings_field( 'bp-disable-profile-sync',   __( 'Profile Syncing',  'buddypress' ), 'bp_admin_setting_callback_profile_sync',     'buddypress', 'bp_xprofile' );
			register_setting  ( 'buddypress',         'bp-disable-profile-sync',     'intval'                                                                                  );
		}

		/** Groups Section ****************************************************/

		if ( bp_is_active( 'groups' ) ) {

			// Add the main section
			add_settings_section( 'bp_groups',        __( 'Groups Settings',  'buddypress' ), 'bp_admin_setting_callback_groups_section',   'buddypress'              );

			// Allow subscriptions setting
			add_settings_field( 'bp_restrict_group_creation', __( 'Group Creation',   'buddypress' ), 'bp_admin_setting_callback_group_creation',   'buddypress', 'bp_groups' );
			register_setting  ( 'buddypress',         'bp_restrict_group_creation',   'intval'                                                                                );
		}

		/** Forums ************************************************************/

		if ( bp_is_active( 'forums' ) && bp_forums_is_installed_correctly() ) {

			// Add the main section
			add_settings_section( 'bp_forums',        __( 'Forums Settings',       'buddypress' ), 'bp_admin_setting_callback_bbpress_section',       'buddypress'              );

			// Allow subscriptions setting
			add_settings_field( 'bb-config-location', __( 'bbPress Configuration', 'buddypress' ), 'bp_admin_setting_callback_bbpress_configuration', 'buddypress', 'bp_forums' );
			register_setting  ( 'buddypress',         'bb-config-location',        ''                                                                                           );
		}

		/** Activity Section **************************************************/

		if ( bp_is_active( 'activity' ) ) {

			// Add the main section
			add_settings_section( 'bp_activity',      __( 'Activity Settings', 'buddypress' ), 'bp_admin_setting_callback_activity_section', 'buddypress'                );

			// Activity commenting on blog and forum posts
			add_settings_field( 'bp-disable-blogforum-comments', __( 'Blog &amp; Forum Comments', 'buddypress' ), 'bp_admin_setting_callback_blogforum_comments', 'buddypress', 'bp_activity' );
			register_setting( 'buddypress', 'bp-disable-blogforum-comments', 'bp_admin_sanitize_callback_blogforum_comments' );

			// Allow activity akismet
			if ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) {
				add_settings_field( '_bp_enable_akismet', __( 'Akismet',          'buddypress' ), 'bp_admin_setting_callback_activity_akismet', 'buddypress', 'bp_activity' );
				register_setting  ( 'buddypress',         '_bp_enable_akismet',   'intval'                                                                                  );
			}
		}

		do_action( 'bp_register_admin_settings' );
	}

	/**
	 * Add Settings link to plugins area
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @param array $links Links array in which we would prepend our link
	 * @param string $file Current plugin basename
	 * @return array Processed links
	 */
	public function add_settings_link( $links, $file ) {
		global $bp;

		if ( plugin_basename( $bp->file ) == $file ) {
			$url           = bp_core_do_network_admin() ? network_admin_url( 'settings.php' ) : admin_url( 'options-general.php' );
			$settings_link = '<a href="' . add_query_arg( array( 'page' => 'bp-components' ), $url ) . '">' . __( 'Settings', 'buddypress' ) . '</a>';
			array_unshift( $links, $settings_link );
		}

		return $links;
	}

	/**
	 * BuddyPress's dedicated admin init action
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @uses do_action() Calls 'bp_admin_init'
	 */
	public function admin_init() {
		do_action( 'bp_admin_init' );
	}

	/**
	 * Add some general styling to the admin area
	 *
	 * @since BuddyPress (1.6)
	 */
	public function admin_head() { }

	/**
	 * Add some general styling to the admin area
	 *
	 * @since BuddyPress (1.6)
	 */
	public function enqueue_scripts() {

		$maybe_dev = '';
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			$maybe_dev = '.dev';

		$file = $this->css_url . "common{$maybe_dev}.css";
		$file = apply_filters( 'bp_core_admin_common_css', $file );
		wp_enqueue_style( 'bp-admin-common-css', $file, array(), bp_get_version() );

		// Extra bits for the installation wizard
		if ( bp_get_maintenance_mode() ) {

			// Styling
			$file = $this->css_url . "wizard{$maybe_dev}.css";
			$file = apply_filters( 'bp_core_admin_wizard_css', $file );
			wp_enqueue_style( 'bp-admin-wizard-css', $file, array(), bp_get_version() );

			// JS
			$file = $this->js_url . "wizard{$maybe_dev}.js";
			$file = apply_filters( 'bp_core_admin_wizard_js', $file );
			wp_enqueue_script( 'bp-admin-wizard-js', $file, array(), bp_get_version() );

			// We'll need the thickbox too
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );
		}

		do_action( 'bp_admin_head' );
	}

	/**
	 * Add any admin notices we might need, mostly for update or new installs
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @global string $pagenow
	 * @return If no notice is needed
	 */
	public function admin_notices() {
		global $pagenow;

		// Bail if not in maintenance mode
		if ( ! bp_get_maintenance_mode() )
			return;

		// Bail if user cannot manage options
		if ( ! current_user_can( 'manage_options' ) )
			return;

		// Are we looking at a network?
		if ( bp_core_do_network_admin() ) {

			// Bail if looking at wizard page
			if ( ( 'admin.php' == $pagenow ) && ( !empty( $_GET['page'] ) && ( 'bp-wizard' == $_GET['page'] ) ) ) {
				return;
			}

			// Set the url for the nag
			$url = network_admin_url( 'admin.php?page=bp-wizard' );

		// Single site
		} else {

			// Bail if looking at wizard page
			if ( ( 'index.php' == $pagenow ) && ( !empty( $_GET['page'] ) && ( 'bp-wizard' == $_GET['page'] ) ) ) {
				return;
			}

			// Set the url for the nag
			$url = admin_url( 'index.php?page=bp-wizard' );
		}

		// What does the nag say?
		switch ( bp_get_maintenance_mode() ) {

			// Update text
			case 'update' :
				$msg = sprintf( __( 'BuddyPress has been updated! Please run the <a href="%s">update wizard</a>.', 'buddypress' ), $url );
				break;

			// First install text
			case 'install' : default :
				$msg = sprintf( __( 'BuddyPress was successfully activated! Please run the <a href="%s">installation wizard</a>.', 'buddypress' ), $url );
				break;
		} ?>

		<div class="update-nag"><?php echo $msg; ?></div>

		<?php
	}
}
endif; // class_exists check

/**
 * Setup BuddyPress Admin
 *
 * @since BuddyPress (1.6)
 *
 * @uses BP_Admin
 */
function bp_admin() {
	global $bp;

	$bp->admin = new BP_Admin();
}

?>
