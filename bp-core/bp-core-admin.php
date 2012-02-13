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
	 * @var string URL to the BuddyPress admin styles directory
	 */
	public $styles_url = '';

	/**
	 * @var string URL to the BuddyPress admin CSS directory
	 */
	public $css_url = '';

	/**
	 * @var string URL to the BuddyPress admin JS directory
	 */
	public $js_url = '';

	/** Recounts **************************************************************/

	/**
	 * @var bool Enable recounts in Tools area
	 */
	public $enable_recounts = false;

	/** Admin Scheme **********************************************************/

	/**
	 * @var int Depth of custom WP_CONTENT_DIR difference
	 */
	public $content_depth = 0;

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

		// Admin images URL
		$this->styles_url = trailingslashit( $this->admin_url . 'styles' );
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

		// Enqueue all admin JS and CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts'   ) );

		// Forums 'Right now' Dashboard widget
		//add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget_right_now' ) );

		/** BuddyPress Actions ************************************************/

		// Add importers
		//add_action( 'bp_admin_init',      array( $this, 'register_importers'      ) );

		// Add red admin style
		//add_action( 'bp_admin_init',      array( $this, 'register_admin_style'    ) );

		// Add settings
		add_action( 'bp_admin_init',      array( $this, 'register_admin_settings' ) );

		/** Filters ***********************************************************/

		// Add link to settings page
		add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ), 10, 2 );

		// Add sample permalink filter
		//add_filter( 'post_type_link',     'bp_filter_sample_permalink',         10, 4 );
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

		// Edit lock setting
		add_settings_field( '_bp_profile_sync',     __( 'Profile Syncing',  'buddypress' ), 'bp_admin_setting_callback_profile_sync',     'buddypress', 'bp_main' );
	 	register_setting  ( 'buddypress',           '_bp_profile_sync',     'intval'                                                                              );

		// Throttle setting
		add_settings_field( '_bp_admin_bar',        __( 'Admin Bar',        'buddypress' ), 'bp_admin_setting_callback_admin_bar',        'buddypress', 'bp_main' );
	 	register_setting  ( 'buddypress',           '_bp_admin_bar',        'intval'                                                                              );

		// Allow topic and reply revisions
		add_settings_field( '_bp_avatar_uploads',   __( 'Avatar Uploads',   'buddypress' ), 'bp_admin_setting_callback_avatar_uploads',   'buddypress', 'bp_main' );
	 	register_setting  ( 'buddypress',           '_bp_avatar_uploads',   'intval'                                                                              );

		// Allow favorites setting
		add_settings_field( '_bp_account_deletion', __( 'Account Deletion', 'buddypress' ), 'bp_admin_setting_callback_account_deletion', 'buddypress', 'bp_main' );
	 	register_setting  ( 'buddypress',           '_bp_account_deletion', 'intval'                                                                              );

		// Allow global access setting
		if ( function_exists( 'wp_editor' ) ) {
			add_settings_field( '_bp_use_wp_editor', __( 'Fancy Editor',    'buddypress' ), 'bp_admin_setting_callback_use_wp_editor',    'buddypress', 'bp_main' );
		 	register_setting  ( 'buddypress',        '_bp_use_wp_editor',   'intval'                                                                              );
		}

		/** Groups Section ****************************************************/

		// @todo move into groups component
		if ( bp_is_active( 'groups' ) ) {

			// Add the main section
			add_settings_section( 'bp_groups',        __( 'Groups Settings',  'buddypress' ), 'bp_admin_setting_callback_groups_section',   'buddypress'              );

			// Allow subscriptions setting
			add_settings_field( '_bp_group_creation', __( 'Group Creation',   'buddypress' ), 'bp_admin_setting_callback_group_creation',   'buddypress', 'bp_groups' );
			register_setting  ( 'buddypress',         '_bp_group_creation',   'intval'                                                                                );
		}

		/** Activity Section **************************************************/

		// @todo move into activity component
		if ( bp_is_active( 'activity' ) && ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) ) {

			// Add the main section
			add_settings_section( 'bp_activity',      __( 'Activity Settings', 'buddypress' ), 'bp_admin_setting_callback_activity_section', 'buddypress'                );

			// Allow subscriptions setting
			add_settings_field( '_bp_enable_akismet', __( 'Akismet',          'buddypress' ), 'bp_admin_setting_callback_activity_akismet', 'buddypress', 'bp_activity' );
			register_setting  ( 'buddypress',         '_bp_group_creation',   'intval'                                                                                  );
		}

		/** Front Slugs *******************************************************/

		// Add the per page section
		//add_settings_section( 'bp_root_slug',       __( 'Archive Slugs', 'buddypress' ), 'bp_admin_setting_callback_root_slug_section',   'buddypress'                  );

		// Root slug setting
		//add_settings_field  ( '_bp_root_slug',          __( 'Forums base',   'buddypress' ), 'bp_admin_setting_callback_root_slug',           'buddypress', 'bp_root_slug' );
	 	//register_setting    ( 'buddypress',                '_bp_root_slug',                  'esc_sql'                                                                    );

		// Topic archive setting
		//add_settings_field  ( '_bp_topic_archive_slug', __( 'Topics base',   'buddypress' ), 'bp_admin_setting_callback_topic_archive_slug',  'buddypress', 'bp_root_slug' );
	 	//register_setting    ( 'buddypress',                 '_bp_topic_archive_slug',        'esc_sql'                                                                    );

		/** Single slugs ******************************************************/

		// Add the per page section
		//add_settings_section( 'bp_single_slugs',   __( 'Single Slugs',  'buddypress' ), 'bp_admin_setting_callback_single_slug_section', 'buddypress'                     );

		// Include root setting
		//add_settings_field( '_bp_include_root',    __( 'Forum Prefix', 'buddypress' ),  'bp_admin_setting_callback_include_root',        'buddypress', 'bp_single_slugs' );
	 	//register_setting  ( 'buddypress',              '_bp_include_root',              'intval'                                                                        );

		// Forum slug setting
		//add_settings_field( '_bp_forum_slug',      __( 'Forum slug',    'buddypress' ), 'bp_admin_setting_callback_forum_slug',          'buddypress', 'bp_single_slugs' );
	 	//register_setting  ( 'buddypress',             '_bp_forum_slug',                 'sanitize_title'                                                                );

		// Topic slug setting
		//add_settings_field( '_bp_topic_slug',      __( 'Topic slug',    'buddypress' ), 'bp_admin_setting_callback_topic_slug',          'buddypress', 'bp_single_slugs' );
	 	//register_setting  ( 'buddypress',             '_bp_topic_slug',                 'sanitize_title'                                                                );

		// Topic tag slug setting
		//add_settings_field( '_bp_topic_tag_slug', __( 'Topic tag slug', 'buddypress' ), 'bp_admin_setting_callback_topic_tag_slug',      'buddypress', 'bp_single_slugs' );
	 	//register_setting  ( 'buddypress',             '_bp_topic_tag_slug',             'sanitize_title'                                                                );

		// Reply slug setting
		//add_settings_field( '_bp_reply_slug',      __( 'Reply slug',    'buddypress' ), 'bp_admin_setting_callback_reply_slug',          'buddypress', 'bp_single_slugs' );
	 	//register_setting  ( 'buddypress',             '_bp_reply_slug',                 'sanitize_title'                                                                );

		/** Other slugs *******************************************************/

		// User slug setting
		//add_settings_field( '_bp_user_slug',       __( 'User base',     'buddypress' ), 'bp_admin_setting_callback_user_slug',           'buddypress', 'bp_single_slugs' );
	 	//register_setting  ( 'buddypress',              '_bp_user_slug',                 'sanitize_title'                                                                );

		// View slug setting
		//add_settings_field( '_bp_view_slug',       __( 'View base',     'buddypress' ), 'bp_admin_setting_callback_view_slug',           'buddypress', 'bp_single_slugs' );
	 	//register_setting  ( 'buddypress',              '_bp_view_slug',                 'sanitize_title'                                                                );

		/** Akismet ***********************************************************/

		do_action( 'bp_register_admin_settings' );
	}

	/**
	 * Register the importers
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @uses do_action() Calls 'bp_register_importers'
	 * @uses apply_filters() Calls 'bp_importer_path' filter to allow plugins
	 *                        to customize the importer script locations.
	 */
	public function register_importers() {

		// Leave if we're not in the import section
		if ( !defined( 'WP_LOAD_IMPORTERS' ) )
			return;

		// Load Importer API
		require_once( ABSPATH . 'wp-admin/includes/import.php' );

		// Load our importers
		$importers = apply_filters( 'bp_importers', array( 'buddypress' ) );

		// Loop through included importers
		foreach ( $importers as $importer ) {

			// Allow custom importer directory
			$import_dir  = apply_filters( 'bp_importer_path', $this->admin_dir . 'importers', $importer );

			// Compile the importer path
			$import_file = trailingslashit( $import_dir ) . $importer . '.php';

			// If the file exists, include it
			if ( file_exists( $import_file ) ) {
				require( $import_file );
			}
		}

		// Don't do anything we wouldn't do
		do_action( 'bp_register_importers' );
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
	 * Add the 'Right now in Forums' dashboard widget
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @uses wp_add_dashboard_widget() To add the dashboard widget
	 */
	public function dashboard_widget_right_now() {
		//wp_add_dashboard_widget( 'bp-dashboard-right-now', __( 'Right Now in Forums', 'buddypress' ), 'bp_dashboard_widget_right_now' );
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
	 * Registers the BuddyPress admin color scheme
	 *
	 * Because wp-content can exist outside of the WordPress root there is no
	 * way to be certain what the relative path of the admin images is.
	 * We are including the two most common configurations here, just in case.
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @uses wp_admin_css_color() To register the color scheme
	 */
	public function register_admin_style () {

		// Normal wp-content dir
		if ( 0 === $this->content_depth )
			$css_file = $this->styles_url . 'admin.css';

		// Custom wp-content dir is 1 level away
		elseif ( 1 === $this->content_depth )
			$css_file = $this->styles_url . 'admin-1.css';

		// Custom wp-content dir is 1 level away
		elseif ( 2 === $this->content_depth )
			$css_file = $this->styles_url . 'admin-2.css';

		// Load the admin CSS styling
		//wp_admin_css_color( 'buddypress', __( 'Green', 'buddypress' ), $css_file, array( '#222222', '#006600', '#deece1', '#6eb469' ) );
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
