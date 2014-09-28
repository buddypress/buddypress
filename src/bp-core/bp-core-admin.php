<?php

/**
 * Main BuddyPress Admin Class.
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'BP_Admin' ) ) :
/**
 * Load BuddyPress plugin admin area.
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 *
 * @since BuddyPress (1.6.0)
 */
class BP_Admin {

	/** Directory *************************************************************/

	/**
	 * Path to the BuddyPress admin directory.
	 *
	 * @var string $admin_dir
	 */
	public $admin_dir = '';

	/** URLs ******************************************************************/

	/**
	 * URL to the BuddyPress admin directory.
	 *
	 * @var string $admin_url
	 */
	public $admin_url = '';

	/**
	 * URL to the BuddyPress images directory.
	 *
	 * @var string $images_url
	 */
	public $images_url = '';

	/**
	 * URL to the BuddyPress admin CSS directory.
	 *
	 * @var string $css_url
	 */
	public $css_url = '';

	/**
	 * URL to the BuddyPress admin JS directory.
	 *
	 * @var string
	 */
	public $js_url = '';

	/** Other *****************************************************************/

	/**
	 * Notices used for user feedback, like saving settings.
	 *
	 * @var array()
	 */
	public $notices = array();

	/** Methods ***************************************************************/

	/**
	 * The main BuddyPress admin loader.
	 *
	 * @since BuddyPress (1.6.0)
	 *
	 * @uses BP_Admin::setup_globals() Setup the globals needed.
	 * @uses BP_Admin::includes() Include the required files.
	 * @uses BP_Admin::setup_actions() Setup the hooks and actions.
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Set admin-related globals.
	 *
	 * @access private
	 * @since BuddyPress (1.6.0)
	 */
	private function setup_globals() {
		$bp = buddypress();

		// Paths and URLs
		$this->admin_dir  = trailingslashit( $bp->plugin_dir  . 'bp-core/admin' ); // Admin path
		$this->admin_url  = trailingslashit( $bp->plugin_url  . 'bp-core/admin' ); // Admin url
		$this->images_url = trailingslashit( $this->admin_url . 'images'        ); // Admin images URL
		$this->css_url    = trailingslashit( $this->admin_url . 'css'           ); // Admin css URL
		$this->js_url     = trailingslashit( $this->admin_url . 'js'            ); // Admin css URL

		// Main settings page
		$this->settings_page = bp_core_do_network_admin() ? 'settings.php' : 'options-general.php';

		// Main capability
		$this->capability = bp_core_do_network_admin() ? 'manage_network_options' : 'manage_options';
	}

	/**
	 * Include required files.
	 *
	 * @since BuddyPress (1.6.0)
	 * @access private
	 */
	private function includes() {
		require( $this->admin_dir . 'bp-core-actions.php'    );
		require( $this->admin_dir . 'bp-core-settings.php'   );
		require( $this->admin_dir . 'bp-core-functions.php'  );
		require( $this->admin_dir . 'bp-core-components.php' );
		require( $this->admin_dir . 'bp-core-slugs.php'      );
		require( $this->admin_dir . 'bp-core-tools.php'      );
	}

	/**
	 * Set up the admin hooks, actions, and filters.
	 *
	 * @access private
	 * @since BuddyPress (1.6.0)
	 *
	 * @uses add_action() To add various actions.
	 * @uses add_filter() To add various filters.
	 */
	private function setup_actions() {

		/** General Actions ***************************************************/

		// Add some page specific output to the <head>
		add_action( 'bp_admin_head',            array( $this, 'admin_head'  ), 999 );

		// Add menu item to settings menu
		add_action( bp_core_admin_hook(),       array( $this, 'admin_menus' ), 5 );

		// Enqueue all admin JS and CSS
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/** BuddyPress Actions ************************************************/

		// Load the BuddyPress metabox in the WP Nav Menu Admin UI
		add_action( 'load-nav-menus.php', 'bp_admin_wp_nav_menu_meta_box' );

		// Add settings
		add_action( 'bp_register_admin_settings', array( $this, 'register_admin_settings' ) );

		// Add a link to BuddyPress About page to the admin bar
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_about_link' ), 15 );

		// Add a description of new BuddyPress tools in the available tools page
		add_action( 'tool_box', 'bp_core_admin_available_tools_intro' );
		add_action( 'bp_network_tool_box', 'bp_core_admin_available_tools_intro' );

		// On non-multisite, catch
		add_action( 'load-users.php', 'bp_core_admin_user_manage_spammers' );

		/** Filters ***********************************************************/

		// Add link to settings page
		add_filter( 'plugin_action_links',               array( $this, 'modify_plugin_action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );

		// Add "Mark as Spam" row actions on users.php
		add_filter( 'ms_user_row_actions', 'bp_core_admin_user_row_actions', 10, 2 );
		add_filter( 'user_row_actions',    'bp_core_admin_user_row_actions', 10, 2 );
	}

	/**
	 * Add the navigational menu elements.
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @uses add_management_page() To add the Recount page in Tools section.
	 * @uses add_options_page() To add the Forums settings page in Settings
	 *       section.
	 */
	public function admin_menus() {

		// Bail if user cannot moderate
		if ( ! bp_current_user_can( 'manage_options' ) )
			return;

		// About
		add_dashboard_page(
			__( 'Welcome to BuddyPress',  'buddypress' ),
			__( 'Welcome to BuddyPress',  'buddypress' ),
			'manage_options',
			'bp-about',
			array( $this, 'about_screen' )
		);

		// Credits
		add_dashboard_page(
			__( 'Welcome to BuddyPress',  'buddypress' ),
			__( 'Welcome to BuddyPress',  'buddypress' ),
			'manage_options',
			'bp-credits',
			array( $this, 'credits_screen' )
		);

		$hooks = array();

		// Changed in BP 1.6 . See bp_core_admin_backpat_menu()
		$hooks[] = add_menu_page(
			__( 'BuddyPress', 'buddypress' ),
			__( 'BuddyPress', 'buddypress' ),
			$this->capability,
			'bp-general-settings',
			'bp_core_admin_backpat_menu',
			'div'
		);

		$hooks[] = add_submenu_page(
			'bp-general-settings',
			__( 'BuddyPress Help', 'buddypress' ),
			__( 'Help', 'buddypress' ),
			$this->capability,
			'bp-general-settings',
			'bp_core_admin_backpat_page'
		);

		// Add the option pages
		$hooks[] = add_submenu_page(
			$this->settings_page,
			__( 'BuddyPress Components', 'buddypress' ),
			__( 'BuddyPress', 'buddypress' ),
			$this->capability,
			'bp-components',
			'bp_core_admin_components_settings'
		);

		$hooks[] = add_submenu_page(
			$this->settings_page,
			__( 'BuddyPress Pages', 'buddypress' ),
			__( 'BuddyPress Pages', 'buddypress' ),
			$this->capability,
			'bp-page-settings',
			'bp_core_admin_slugs_settings'
		);

		$hooks[] = add_submenu_page(
			$this->settings_page,
			__( 'BuddyPress Settings', 'buddypress' ),
			__( 'BuddyPress Settings', 'buddypress' ),
			$this->capability,
			'bp-settings',
			'bp_core_admin_settings'
		);

		// For consistency with non-Multisite, we add a Tools menu in
		// the Network Admin as a home for our Tools panel
		if ( is_multisite() && bp_core_do_network_admin() ) {
			$tools_parent = 'network-tools';

			$hooks[] = add_menu_page(
				__( 'Tools', 'buddypress' ),
				__( 'Tools', 'buddypress' ),
				$this->capability,
				$tools_parent,
				'bp_core_tools_top_level_item',
				'',
				24 // just above Settings
			);

			$hooks[] = add_submenu_page(
				$tools_parent,
				__( 'Available Tools', 'buddypress' ),
				__( 'Available Tools', 'buddypress' ),
				$this->capability,
				'available-tools',
				'bp_core_admin_available_tools_page'
			);
		} else {
			$tools_parent = 'tools.php';
		}

		$hooks[] = add_submenu_page(
			$tools_parent,
			__( 'BuddyPress Tools', 'buddypress' ),
			__( 'BuddyPress', 'buddypress' ),
			$this->capability,
			'bp-tools',
			'bp_core_admin_tools'
		);

		// Fudge the highlighted subnav item when on a BuddyPress admin page
		foreach( $hooks as $hook ) {
			add_action( "admin_head-$hook", 'bp_core_modify_admin_menu_highlight' );
		}
	}

	/**
	 * Register the settings.
	 *
	 * @since BuddyPress (1.6.0)
	 *
	 * @uses add_settings_section() To add our own settings section.
	 * @uses add_settings_field() To add various settings fields.
	 * @uses register_setting() To register various settings.
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
			add_settings_section( 'bp_xprofile', _x( 'Profile Settings', 'BuddyPress setting tab', 'buddypress' ), 'bp_admin_setting_callback_xprofile_section', 'buddypress' );

			$avatar_setting = 'bp_xprofile';

			// Profile sync setting
			add_settings_field( 'bp-disable-profile-sync',   __( 'Profile Syncing',  'buddypress' ), 'bp_admin_setting_callback_profile_sync',     'buddypress', 'bp_xprofile' );
			register_setting  ( 'buddypress',         'bp-disable-profile-sync',     'intval'                                                                                  );
		}

		/** Groups Section ****************************************************/

		if ( bp_is_active( 'groups' ) ) {

			// Add the main section
			add_settings_section( 'bp_groups',        __( 'Groups Settings',  'buddypress' ), 'bp_admin_setting_callback_groups_section',   'buddypress'              );

			if ( empty( $avatar_setting ) )
				$avatar_setting = 'bp_groups';

			// Allow subscriptions setting
			add_settings_field( 'bp_restrict_group_creation', __( 'Group Creation',   'buddypress' ), 'bp_admin_setting_callback_group_creation',   'buddypress', 'bp_groups' );
			register_setting  ( 'buddypress',         'bp_restrict_group_creation',   'intval'                                                                                );
		}

		/** Forums ************************************************************/

		if ( bp_is_active( 'forums' ) ) {

			// Add the main section
			add_settings_section( 'bp_forums',        __( 'Legacy Group Forums',       'buddypress' ), 'bp_admin_setting_callback_bbpress_section',       'buddypress'              );

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

			// Activity Heartbeat refresh
			add_settings_field( '_bp_enable_heartbeat_refresh', __( 'Activity auto-refresh', 'buddypress' ), 'bp_admin_setting_callback_heartbeat', 'buddypress', 'bp_activity' );
			register_setting( 'buddypress', '_bp_enable_heartbeat_refresh', 'intval' );

			// Allow activity akismet
			if ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) {
				add_settings_field( '_bp_enable_akismet', __( 'Akismet',          'buddypress' ), 'bp_admin_setting_callback_activity_akismet', 'buddypress', 'bp_activity' );
				register_setting  ( 'buddypress',         '_bp_enable_akismet',   'intval'                                                                                  );
			}
		}

		/** Avatar upload for users or groups ************************************/

		if ( ! empty( $avatar_setting ) ) {
		    // Allow avatar uploads
		    add_settings_field( 'bp-disable-avatar-uploads', __( 'Profile Photo Uploads',   'buddypress' ), 'bp_admin_setting_callback_avatar_uploads',   'buddypress', $avatar_setting );
		    register_setting  ( 'buddypress',         'bp-disable-avatar-uploads',   'intval'                                                                                    );
		}
	}

	/**
	 * Add a link to BuddyPress About page to the admin bar.
	 *
	 * @since BuddyPress (1.9.0)
	 *
	 * @param WP_Admin_Bar $wp_admin_bar As passed to 'admin_bar_menu'.
	 */
	public function admin_bar_about_link( $wp_admin_bar ) {
		if ( is_user_logged_in() ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'wp-logo',
				'id'     => 'bp-about',
				'title'  => esc_html__( 'About BuddyPress', 'buddypress' ),
				'href'   => add_query_arg( array( 'page' => 'bp-about' ), bp_get_admin_url( 'index.php' ) ),
			) );
		}
	}

	/**
	 * Add Settings link to plugins area.
	 *
	 * @since BuddyPress (1.6.0)
	 *
	 * @param array $links Links array in which we would prepend our link.
	 * @param string $file Current plugin basename.
	 * @return array Processed links.
	 */
	public function modify_plugin_action_links( $links, $file ) {

		// Return normal links if not BuddyPress
		if ( plugin_basename( buddypress()->basename ) != $file )
			return $links;

		// Add a few links to the existing links array
		return array_merge( $links, array(
			'settings' => '<a href="' . add_query_arg( array( 'page' => 'bp-components' ), bp_get_admin_url( $this->settings_page ) ) . '">' . esc_html__( 'Settings', 'buddypress' ) . '</a>',
			'about'    => '<a href="' . add_query_arg( array( 'page' => 'bp-about'      ), bp_get_admin_url( 'index.php'          ) ) . '">' . esc_html__( 'About',    'buddypress' ) . '</a>'
		) );
	}

	/**
	 * Add some general styling to the admin area.
	 *
	 * @since BuddyPress (1.6.0)
	 */
	public function admin_head() {

		// Settings pages
		remove_submenu_page( $this->settings_page, 'bp-page-settings' );
		remove_submenu_page( $this->settings_page, 'bp-settings'      );

		// Network Admin Tools
		remove_submenu_page( 'network-tools', 'network-tools' );

		// About and Credits pages
		remove_submenu_page( 'index.php', 'bp-about'   );
		remove_submenu_page( 'index.php', 'bp-credits' );
	}

	/**
	 * Add some general styling to the admin area.
	 *
	 * @since BuddyPress (1.6.0)
	 */
	public function enqueue_scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$file = $this->css_url . "common{$min}.css";
		$file = apply_filters( 'bp_core_admin_common_css', $file );
		wp_enqueue_style( 'bp-admin-common-css', $file, array(), bp_get_version() );

		wp_style_add_data( 'bp-admin-common-css', 'rtl', true );
		if ( $min ) {
			wp_style_add_data( 'bp-admin-common-css', 'suffix', $min );
		}
	}

	/** About *****************************************************************/

	/**
	 * Output the about screen.
	 *
	 * @since BuddyPress (1.7.0)
	 */
	public function about_screen() {
		global $wp_rewrite;

		$is_new_install            = ! empty( $_GET['is_new_install'] );
		$pretty_permalinks_enabled = ! empty( $wp_rewrite->permalink_structure );
		list( $display_version )   = explode( '-', bp_get_version() ); ?>

		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to BuddyPress %s', 'buddypress' ), $display_version ); ?></h1>
			<div class="about-text">
				<?php if ( $is_new_install ) : ?>
					<?php printf( __( 'Thank you for installing BuddyPress! BuddyPress %s is our most streamlined and easy-to-use release to date, and we think you&#8217;re going to love it.', 'buddypress' ), $display_version ); ?>
				<?php else : ?>
					<?php printf( __( 'Howdy. BuddyPress %s is our most streamlined and easy-to-use release to date, and we think you&#8217;re going to love it.', 'buddypress' ), $display_version ); ?>
				<?php endif; ?>
			</div>

			<div class="bp-badge"></div>

			<h2 class="nav-tab-wrapper">
				<a class="nav-tab nav-tab-active" href="<?php echo esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-about' ), 'index.php' ) ) ); ?>">
					<?php _e( 'What&#8217;s New', 'buddypress' ); ?>
				</a><a class="nav-tab" href="<?php echo esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-credits' ), 'index.php' ) ) ); ?>">
					<?php _e( 'Credits', 'buddypress' ); ?>
				</a>
			</h2>

			<?php if ( $is_new_install ) : ?> 

				<div id="welcome-panel" class="welcome-panel">
					<div class="welcome-panel-content">
						<h3 style="margin:0"><?php _e( 'Getting Started with BuddyPress', 'buddypress' ); ?></h3>
						<div class="welcome-panel-column-container">
							<div class="welcome-panel-column">
								<h4><?php _e( 'Configure Buddypress', 'buddypress' ); ?></h4>
								<ul>
									<li><?php printf( 
									'<a href="%s" class="welcome-icon welcome-edit-page">' . __( 'Set Up Components', 'buddypress' ) . '</a>', bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), $this->settings_page ) )
									); ?></li>
									<li><?php printf( 
									'<a href="%s" class="welcome-icon welcome-edit-page">' . __( 'Assign Components to Pages', 'buddypress' ) . '</a>', bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings' ), $this->settings_page ) )
									); ?></li>
									<li><?php printf(
									'<a href="%s" class="welcome-icon welcome-edit-page">' . __( 'Customize Settings', 'buddypress' ) . '</a>', bp_get_admin_url( add_query_arg( array( 'page' => 'bp-settings' ), $this->settings_page ) )
									); ?></li>
								</ul>
								<a class="button button-primary button-hero" style="margin-bottom:20px;margin-top:0;" href="<?php echo esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), $this->settings_page ) ) ); ?>"><?php _e( 'Get Started', 'buddypress' ); ?></a>
							</div>
							<div class="welcome-panel-column">
								<h4><?php _e( 'Administration Tools', 'buddypress' ); ?></h4>
								<ul>
									<?php if ( bp_is_active( 'members' ) ) : ?>
										<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Add User Profile Fields', 'buddypress' ) . '</a>', bp_get_admin_url( add_query_arg( array( 'page' => 'bp-profile-setup' ), 'users.php' ) ) ); ?></li>
									<?php endif; ?>
									<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Manage User Signups', 'buddypress' ) . '</a>', bp_get_admin_url( add_query_arg( array( 'page' => 'bp-signups' ), 'users.php' ) ) ); ?></li>
									<?php if ( bp_is_active( 'activity' ) ) : ?>
										<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Moderate Activity Streams', 'buddypress' ) . '</a>', bp_get_admin_url( add_query_arg( array( 'page' => 'bp-activity' ), 'admin.php' ) ) ); ?></li>
									<?php endif; ?>
									<?php if ( bp_is_active( 'groups' ) ) : ?>
										<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Manage Groups', 'buddypress' ) . '</a>', bp_get_admin_url( add_query_arg( array( 'page' => 'bp-groups' ), 'admin.php' ) ) ); ?></li>
									<?php endif; ?>
									<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Repair Data', 'buddypress' ) . '</a>', bp_get_admin_url( add_query_arg( array( 'page' => 'bp-tools' ), 'tools.php' ) ) ); ?>
									</li>
								</ul>
							</div>
							<div class="welcome-panel-column welcome-panel-last">
								<h4><?php _e( 'Community and Support', 'buddypress'  ); ?></h4>
								<p class="welcome-icon welcome-learn-more" style="margin-right:10px"><?php _e( 'Looking for help? The <a href="http://codex.buddypress.org/">BuddyPress Codex</a> has you covered.', 'buddypress' ) ?></p> 
								<p class="welcome-icon welcome-learn-more" style="margin-right:10px"><?php _e( 'Can&#8217;t find what you need? Stop by <a href="http://buddypress.org/support/">our support forums</a>, where active BuddyPress users and developers are waiting to share tips and more.', 'buddypress' ) ?></p>
							</div>
						</div>
					</div>
				</div>

			<?php endif; ?>

			<hr />

			<div class="changelog">
				<h2 class="about-headline-callout"><?php _e( 'Revamped @mentions Interface', 'buddypress' ); ?></h2>
				<p><?php _e( 'Forget the old days of trying to remember someone&#8217;s username when you want to @mention them in a conversation! With BuddyPress 2.1, type a <code>@</code> when leaving a status update or commenting on an activity item or blog post, and the new suggestions panel will open.', 'buddypress' ) ?></p>
				<p style="text-align: center"><img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/admin/images/mentions.gif' ); ?>" alt="<?php esc_attr_e( 'Demo of at-mentions feature', 'buddypress' ); ?>" style="margin-bottom: 20px"></p>
 			</div>

			<hr />

			<div class="changelog">
				<h2 class="about-headline-callout"><?php _e( 'Continuous Improvement', 'buddypress' ); ?></h2>

				<div class="feature-section col three-col">
					<div class="col-1">
						<h4><?php esc_html_e( 'New Profile Field Type: URL', 'buddypress' ); ?></h4>
						<p><?php esc_html_e( 'Built to hold the address of another website, this new field type automatically creates a link to that site.', 'buddypress' ); ?></p>
					</div>

					<div class="col-2">
						<h4><?php esc_html_e( 'Awesome Translations', 'buddypress' ); ?></h4>
						<p><?php esc_html_e( 'BuddyPress supports high-quality translations that are automatically fetched by WordPress. Many thanks to our translation volunteers for making this possible.', 'buddypress' ); ?></p>
					</div>

					<div class="col-3 last-feature">
						<h4><?php esc_html_e( 'Performance Improvements', 'buddypress' ); ?></h4>
						<p><?php _e( 'Like we do with every release, we&#8217ve made further optimizations to increase BuddyPress&#8217 performance and reduce its query overhead.', 'buddypress' ); ?></p>
					</div>
				</div>
			</div>

			<hr />

			<div class="changelog">
				<h2 class="about-headline-callout"><?php esc_html_e( 'Enhancements for Plugin &amp; Theme Developers', 'buddypress' ); ?></h2>
				<div class="feature-section col two-col">
					<div class="col-1">
						<p><?php _e( 'If you&#8217re a plugin developer, or make custom themes, or want to contribute back to the BuddyPress project, here&#8217s what you should know about this release:', 'buddypress' ); ?></p>
						<p><?php _e( 'If you&#8217ve used BuddyPress for a very long time, you might remember the <em>BuddyBar</em>; it was our toolbar before WordPress had its own toolbar. We started to deprecate it in BuddyPress 1.6. It is now formally deprecated, which means you should not use it for new sites.', 'buddypress' ); ?></p>
						<p>
							<?php printf(
								__( 'The classic <a href="%s">BP Default theme has moved to Github</a>. We moved it because BuddyPress development is now focused on our <a href="%s">theme compatibility</a> templates, which were introduced in BuddyPress 1.7. Don&#8217t worry, BP-Default is still bundled with BuddyPress releases.', 'buddypress' ),
								esc_url( 'https://github.com/buddypress/BP-Default' ),
								esc_url( 'http://codex.buddypress.org/themes/theme-compatibility-1-7/a-quick-look-at-1-7-theme-compatibility/' )
							); ?>
						</p>
						<p>
							<?php
							/* translators: don't translate the insides of the <code> block */
							_e( 'In BuddyPress 2.0, we added a new <code>BP_XProfile_Field_Type</code> API for managing profile field types. In this release, we&#8217ve added a new <code>bp_core_get_suggestions</code> API which powers our new @mentions interface. Both are cool, and are worth checking out.', 'buddypress' );
							?>
						</p>
					</div>

					<div class="col-2 last-feature">
						<p><?php esc_html_e( 'Other interesting changes:', 'buddypress' ); ?>

						<ul>
							<li>
								<?php
								/* translators: don't translate the insides of the <code> block */
								_e( 'In <code>BP_Group_Extension</code>, the <code>visibility</code> and <code>enable_nav_item</code> properties have been phased out in favor of new <code>access</code> and <code>show_tab</code> parameters.', 'buddypress' );
								?>
							</li>
							<li>
								<?php
								/* translators: don't translate the insides of the <code> block */
								_e( 'A new <code>group_activity</code> sort order has been added for Groups queries, to let you query for recently active members.', 'buddypress' );
								?>
							</li>
							<li>
								<?php
								/* translators: don't translate the insides of the <code> block */
								_e( 'Extra CSS classes have been added to Profile Field visibility field elements, allowing greater CSS customization.', 'buddypress' );
								?>
							</li>
							<li>
								<?php
								/* translators: don't translate the insides of the <code> block */
								_e( 'A <code>no_access_url</code> parameter has been added to <code>bp_core_new_subnav_item()</code>. This allows you to set the URL that users are redirected to when they do not have permission to access a sub-navigation item.', 'buddypress' );
								?>
							</li>
							<li>
								<?php
								/* translators: don't translate the insides of the <code> block */
								_e( 'When making searches with <code>BP_User_Query</code>, a new <code>search_wildcard</code> parameter gives you finer control over how the search SQL is constructed.', 'buddypress' );
								?>
							</li>
	

							<li><?php printf( __( '<a href="%s">&hellip;and lots more!</a>', 'buddypress' ), 'https://codex.buddypress.org/releases/version-2-1' ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		<?php
	}

	/**
	 * Output the credits screen.
	 *
	 * Hardcoding this in here is pretty janky. It's fine for now, but we'll
	 * want to leverage api.wordpress.org eventually.
	 *
	 * @since BuddyPress (1.7.0)
	 */
	public function credits_screen() {

		$is_new_install = ! empty( $_GET['is_new_install'] );

		list( $display_version ) = explode( '-', bp_get_version() ); ?>

		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to BuddyPress %s', 'buddypress' ), $display_version ); ?></h1>
			<div class="about-text">
				<?php if ( $is_new_install ) : ?>
					<?php printf( __( 'Thank you for installing BuddyPress! BuddyPress %s is our most streamlined and easy-to-use release to date, and we think you&#8217;re going to love it.', 'buddypress' ), $display_version ); ?>
				<?php else : ?>
					<?php printf( __( 'Howdy. BuddyPress %s is our most streamlined and easy-to-use release to date, and we think you&#8217;re going to love it.', 'buddypress' ), $display_version ); ?>
				<?php endif; ?>
			</div>

			<div class="bp-badge"></div>

			<h2 class="nav-tab-wrapper">
				<a class="nav-tab" href="<?php echo esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-about' ), 'index.php' ) ) ); ?>">
					<?php _e( 'What&#8217;s New', 'buddypress' ); ?>
				</a><a class="nav-tab nav-tab-active" href="<?php echo esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-credits' ), 'index.php' ) ) ); ?>">
					<?php _e( 'Credits', 'buddypress' ); ?>
				</a>
			</h2>

			<p class="about-description"><?php _e( 'BuddyPress is created by a worldwide network of friendly folks.', 'buddypress' ); ?></p>

			<h4 class="wp-people-group"><?php _e( 'Project Leaders', 'buddypress' ); ?></h4>
			<ul class="wp-people-group " id="wp-people-group-project-leaders">
				<li class="wp-person" id="wp-person-johnjamesjacoby">
					<a href="http://profiles.wordpress.org/johnjamesjacoby"><img src="http://0.gravatar.com/avatar/81ec16063d89b162d55efe72165c105f?s=60" class="gravatar" alt="John James Jacoby" /></a>
					<a class="web" href="http://profiles.wordpress.org/johnjamesjacoby">John James Jacoby</a>
					<span class="title"><?php _e( 'Project Lead', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-boonebgorges">
					<a href="http://profiles.wordpress.org/boonebgorges"><img src="http://0.gravatar.com/avatar/9cf7c4541a582729a5fc7ae484786c0c?s=60" class="gravatar" alt="Boone B. Gorges" /></a>
					<a class="web" href="http://profiles.wordpress.org/boonebgorges">Boone B. Gorges</a>
					<span class="title"><?php _e( 'Lead Developer', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-djpaul">
					<a href="http://profiles.wordpress.org/djpaul"><img src="http://0.gravatar.com/avatar/3bc9ab796299d67ce83dceb9554f75df?s=60" class="gravatar" alt="Paul Gibbs" /></a>
					<a class="web" href="http://profiles.wordpress.org/djpaul">Paul Gibbs</a>
					<span class="title"><?php _e( 'Lead Developer', 'buddypress' ); ?></span>
				</li>
			</ul>

			<h4 class="wp-people-group"><?php _e( 'Core Team', 'buddypress' ); ?></h4>
			<ul class="wp-people-group " id="wp-people-group-core-team">
				<li class="wp-person" id="wp-person-r-a-y">
					<a href="http://profiles.wordpress.org/r-a-y"><img src="http://0.gravatar.com/avatar/3bfa556a62b5bfac1012b6ba5f42ebfa?s=60" class="gravatar" alt="Ray" /></a>
					<a class="web" href="http://profiles.wordpress.org/r-a-y">Ray</a>
					<span class="title"><?php _e( 'Core Developer', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-imath">
					<a href="http://profiles.wordpress.org/imath"><img src="http://0.gravatar.com/avatar/8b208ca408dad63888253ee1800d6a03?s=60" class="gravatar" alt="Mathieu Viet" /></a>
					<a class="web" href="http://profiles.wordpress.org/imath">Mathieu Viet</a>
					<span class="title"><?php _e( 'Core Developer', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-mercime">
					<a href="http://profiles.wordpress.org/mercime"><img src="http://0.gravatar.com/avatar/fae451be6708241627983570a1a1817a?s=60" class="gravatar" alt="Mercime" /></a>
					<a class="web" href="http://profiles.wordpress.org/mercime">Mercime</a>
					<span class="title"><?php _e( 'Navigator', 'buddypress' ); ?></span>
				</li>
			</ul>

			<h4 class="wp-people-group"><?php _e( 'Recent Rockstars', 'buddypress' ); ?></h4>
			<ul class="wp-people-group " id="wp-people-group-rockstars">
				<li class="wp-person" id="wp-person-dcavins">
					<a href="http://profiles.wordpress.org/dcavins"><img src="http://0.gravatar.com/avatar/a5fa7e83d59cb45ebb616235a176595a?s=60" class="gravatar" alt="David Cavins" /></a>
					<a class="web" href="http://profiles.wordpress.org/dcavins">David Cavins</a>
				</li>
				<li class="wp-person" id="wp-person-henry-wright">
					<a href="http://profiles.wordpress.org/henry.wright"><img src="http://0.gravatar.com/avatar/0da2f1a9340d6af196b870f6c107a248?s=60" class="gravatar" alt="Henry Wright" /></a>
					<a class="web" href="http://profiles.wordpress.org/henry.wright">Henry Wright</a>
				</li>
				<li class="wp-person" id="wp-person-danbp">
					<a href="http://profiles.wordpress.org/danbp"><img src="http://0.gravatar.com/avatar/0deae2e7003027fbf153500cd3fa5501?s=60" class="gravatar" alt="danbp" /></a>
					<a class="web" href="http://profiles.wordpress.org/danbp">danbp</a>
				</li>
				<li class="wp-person" id="wp-person-shanebp">
					<a href="http://profiles.wordpress.org/shanebp"><img src="http://0.gravatar.com/avatar/ffd294ab5833ba14aaf175f9acc71cc4?s=60" class="gravatar" alt="shanebp" /></a>
					<a class="web" href="http://profiles.wordpress.org/shanebp">shanebp</a>
				</li>
				<li class="wp-person" id="wp-person-netweb">
					<a href="http://profiles.wordpress.org/netweb"><img src="http://0.gravatar.com/avatar/97e1620b501da675315ba7cfb740e80f?s=60" class="gravatar" alt="Stephen Edgar" /></a>
					<a class="web" href="http://profiles.wordpress.org/netweb">Stephen Edgar</a>
				</li>
			</ul>

			<h4 class="wp-people-group"><?php printf( __( 'Contributors to BuddyPress %s', 'buddypress' ), $display_version ); ?></h4>
			<p class="wp-credits-list">
				<a href="https://profiles.wordpress.org/adamt19/">adamt19</a>,
				<a href="https://profiles.wordpress.org/Viper007Bond/">Alex Mills (Viper007Bond)</a>,
				<a href="https://profiles.wordpress.org/allendav/">allendav</a>,
				<a href="https://profiles.wordpress.org/alternatekev/">alternatekev</a>,
				<a href="https://profiles.wordpress.org/automattic/">Automattic</a>,
				<a href="https://profiles.wordpress.org/beaulebens/">Beau Lebens (beaulebens)</a>,
				<a href="https://profiles.wordpress.org/boonebgorges/">Boone B Gorges (boonebgorges)</a>,
				<a href="https://profiles.wordpress.org/williamsba1/">Brad Williams (williamsba1)</a>,
				<a href="https://profiles.wordpress.org/sbrajesh/">Brajesh Singh (sbrajesh)</a>,
				<a href="https://profiles.wordpress.org/danbp/">danbp</a>,
				<a href="https://profiles.wordpress.org/dcavins/">David Cavins (dcavins)</a>,
				<a href="https://profiles.wordpress.org/ebellempire/">Erin B. (ebellempire)</a>,
				<a href="https://profiles.wordpress.org/esroyo/">esroyo</a>,
				<a href="https://profiles.wordpress.org/godavid33">godavid33</a>,
				<a href="http://profiles.wordpress.org/henry.wright">Henry Wright (henry.wright)</a>,
				<a href="https://profiles.wordpress.org/hnla/">Hugo (hnla)</a>,
				<a href="https://profiles.wordpress.org/imath/">Mathieu Viet (imath)</a>,
				<a href="https://profiles.wordpress.org/johnjamesjacoby/">John James Jacoby (johnjamesjacoby)</a>,
				<a href="https://profiles.wordpress.org/jconti/">Jose Conti (jconti)</a>,
				<a href="https://profiles.wordpress.org/jreeve/">jreeve</a>,
				<a href="https://profiles.wordpress.org/Offereins">Laurens Offereins (Offereins)</a>
				<a href="https://profiles.wordpress.org/lenasterg/">lenasterg</a>,
				<a href="https://profiles.wordpress.org/mercime/">mercime</a>,
				<a href="https://profiles.wordpress.org/tw2113/">Michael Beckwith (tw2113)</a>,
				<a href="https://profiles.wordpress.org/milesstewart88/">Miles Stewart (milesstewart88)</a>,
				<a href="https://profiles.wordpress.org/needle/">needle</a>,
				<a href="https://profiles.wordpress.org/sooskriszta/">OC2PS (sooskriszta)</a>,
				<a href="https://profiles.wordpress.org/DJPaul/">Paul Gibbs (DJPaul)</a>,
				<a href="https://profiles.wordpress.org/r-a-y/">r-a-y</a>,
				<a href="https://profiles.wordpress.org/rogercoathup/">Roger Coathup (rogercoathup)</a>,
				<a href="https://profiles.wordpress.org/pollyplummer/">Sarah Gooding (pollyplummer)</a>,
				<a href="https://profiles.wordpress.org/SGr33n/">Sergio De Falco (SGr33n)</a>,
				<a href="https://profiles.wordpress.org/shanebp/">shanebp</a>,
				<a href="https://profiles.wordpress.org/slaFFik/">Slava UA (slaFFik)</a>,
				<a href="https://profiles.wordpress.org/netweb/">Stephen Edgar (netweb)</a>,
				<a href="https://profiles.wordpress.org/karmatosed/">Tammie (karmatosed)</a>,
				<a href="https://profiles.wordpress.org/tomdxw/">tomdxw</a>,
				<a href="https://profiles.wordpress.org/treyhunner/">treyhunner</a>,
				<a href="https://profiles.wordpress.org/ubernaut/">ubernaut</a>,
				<a href="https://profiles.wordpress.org/wbajzek/">wbajzek</a>,
				<a href="https://profiles.wordpress.org/WCUADD/">WCUADD</a>,
				<a href="https://profiles.wordpress.org/wpdennis/">wpdennis</a>,
				<a href="https://profiles.wordpress.org/wolfhoundjesse/">wolfhoundjesse</a>.
			</p>

			<h4 class="wp-people-group"><?php _e( 'External Libraries', 'buddypress' ); ?></h4>
			<p class="wp-credits-list">
				<a href="https://github.com/ichord/At.js">At.js</a>,
				<a href="https://github.com/ichord/Caret.js">Caret.js</a>,
				<a href="https://github.com/carhartl/jquery-cookie">jquery.cookie</a>.
			</p>

		</div>

		<?php
	}
}
endif; // class_exists check

/**
 * Setup BuddyPress Admin.
 *
 * @since BuddyPress (1.6.0)
 *
 * @uses BP_Admin
 */
function bp_admin() {
	buddypress()->admin = new BP_Admin();
	return;


	// These are strings we may use to describe maintenance/security releases, where we aim for no new strings.

	_n_noop( 'Maintenance Release', 'Maintenance Releases', 'buddypress' );
	_n_noop( 'Security Release', 'Security Releases', 'buddypress' );
	_n_noop( 'Maintenance and Security Release', 'Maintenance and Security Releases', 'buddypress' );

	/* translators: 1: WordPress version number. */
	_n_noop( '<strong>Version %1$s</strong> addressed a security issue.',
	         '<strong>Version %1$s</strong> addressed some security issues.',
	         'buddypress' );

	/* translators: 1: WordPress version number, 2: plural number of bugs. */
	_n_noop( '<strong>Version %1$s</strong> addressed %2$s bug.',
	         '<strong>Version %1$s</strong> addressed %2$s bugs.',
	         'buddypress' );

	/* translators: 1: WordPress version number, 2: plural number of bugs. Singular security issue. */
	_n_noop( '<strong>Version %1$s</strong> addressed a security issue and fixed %2$s bug.',
	         '<strong>Version %1$s</strong> addressed a security issue and fixed %2$s bugs.',
	         'buddypress' );

	/* translators: 1: WordPress version number, 2: plural number of bugs. More than one security issue. */
	_n_noop( '<strong>Version %1$s</strong> addressed some security issues and fixed %2$s bug.',
	         '<strong>Version %1$s</strong> addressed some security issues and fixed %2$s bugs.',
	         'buddypress' );

	__( 'For more information, see <a href="%s">the release notes</a>.', 'buddypress' );
}
