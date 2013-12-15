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

		/** Filters ***********************************************************/

		// Add link to settings page
		add_filter( 'plugin_action_links',               array( $this, 'modify_plugin_action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );
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
			'manage_options',
			'bp-general-settings',
			'bp_core_admin_backpat_menu',
			'div'
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
			$this->settings_page,
			__( 'BuddyPress Components', 'buddypress' ),
			__( 'BuddyPress', 'buddypress' ),
			'manage_options',
			'bp-components',
			'bp_core_admin_components_settings'
		);

		$hooks[] = add_submenu_page(
			$this->settings_page,
			__( 'BuddyPress Pages', 'buddypress' ),
			__( 'BuddyPress Pages', 'buddypress' ),
			'manage_options',
			'bp-page-settings',
			'bp_core_admin_slugs_settings'
		);

		$hooks[] = add_submenu_page(
			$this->settings_page,
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

			// Allow activity akismet
			if ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) {
				add_settings_field( '_bp_enable_akismet', __( 'Akismet',          'buddypress' ), 'bp_admin_setting_callback_activity_akismet', 'buddypress', 'bp_activity' );
				register_setting  ( 'buddypress',         '_bp_enable_akismet',   'intval'                                                                                  );
			}
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
		if ( plugin_basename( buddypress()->file ) != $file )
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
	}

	/** About *****************************************************************/

	/**
	 * Output the about screen.
	 *
	 * @since BuddyPress (1.7.0)
	 */
	public function about_screen() {
		global $wp_rewrite;

		$is_new_install = ! empty( $_GET['is_new_install'] );

		$pretty_permalinks_enabled = ! empty( $wp_rewrite->permalink_structure );

		list( $display_version ) = explode( '-', bp_get_version() ); ?>

		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to BuddyPress %s', 'buddypress' ), $display_version ); ?></h1>
			<div class="about-text">
				<?php if ( $is_new_install ) : ?>
				<?php printf( __( 'It&#8217;s a great time to use BuddyPress! %s is our first version with a new component in over two years. Not only that, there are plenty of new features, enhancements, and bug fixes.', 'buddypress' ), $display_version ); ?>
				<?php else : ?>
					<?php printf( __( 'Thanks for updating! BuddyPress %s is our first version with a new component in over two years. Not only that, there are plenty of new features, enhancements, and bug fixes.', 'buddypress' ), $display_version ); ?>
				<?php endif; ?>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Check out the highlights:', 'buddypress' ); ?></h3>

				<ul>
					<li><strong><?php _e( 'You can now add dynamic BuddyPress links to custom navigation menus.', 'buddypress' ); ?></strong></li>
					<li><strong><?php _e( 'Notifications have been moved into their own component.', 'buddypress' ); ?></strong></li>
					<li><strong><?php _e( 'Three new widgets, allowing easier site customization.', 'buddypress' ); ?></strong></li>
				<ul>
			</div>

			<div class="bp-badge"><?php printf( __( 'Version %s', 'buddypress' ), $display_version ); ?></div>

			<h2 class="nav-tab-wrapper">
				<a class="nav-tab nav-tab-active" href="<?php echo esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-about' ), 'index.php' ) ) ); ?>">
					<?php _e( 'What&#8217;s New', 'buddypress' ); ?>
				</a><a class="nav-tab" href="<?php echo esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-credits' ), 'index.php' ) ) ); ?>">
					<?php _e( 'Credits', 'buddypress' ); ?>
				</a>
			</h2>

			<?php if ( $is_new_install ) : ?>
			<h3><?php _e( 'Getting Started', 'buddypress' ); ?></h3>

				<div class="feature-section">
					<h4><?php _e( 'Your Default Setup', 'buddypress' ); ?></h4>

					<?php if ( bp_is_active( 'members' ) && bp_is_active( 'activity' ) ) : ?>
						<p><?php printf(
						__( 'BuddyPress&#8217;s powerful features help your users connect and collaborate. To help get your community started, we&#8217;ve activated two of the most commonly used tools in BP: <strong>Extended Profiles</strong> and <strong>Activity Streams</strong>. See these components in action at the %1$s and %2$s directories, and be sure to spend a few minutes <a href="%3$s">configuring user profiles</a>. Want to explore more of BP&#8217;s features? Visit the <a href="%4$s">Components panel</a>.', 'buddypress' ),
						$pretty_permalinks_enabled ? '<a href="' . trailingslashit( bp_get_root_domain() . '/' . bp_get_members_root_slug() ) . '">' . __( 'Members', 'buddypress' ) . '</a>' : __( 'Members', 'buddypress' ),
						$pretty_permalinks_enabled ? '<a href="' . trailingslashit( bp_get_root_domain() . '/' . bp_get_activity_root_slug() ) . '">' . __( 'Activity', 'buddypress' ) . '</a>' : __( 'Activity', 'buddypress' ),
						bp_get_admin_url( add_query_arg( array( 'page' => 'bp-profile-setup' ), 'users.php' ) ),
						bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), $this->settings_page ) )
					); ?></p>

					<?php else : ?>
						<p><?php printf(
						__( 'BuddyPress&#8217;s powerful features help your users connect and collaborate. Want to explore BP&#8217;s features? Visit the <a href="%s">Components panel</a>.', 'buddypress' ),
						bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), $this->settings_page ) )
					); ?></p>

					<?php endif; ?>

					<h4><?php _e( 'Community and Support', 'buddypress' ); ?></h4>
					<p><?php _e( 'Looking for help? The <a href="http://codex.buddypress.org/">BuddyPress Codex</a> has you covered, with dozens of user-contributed guides on how to configure and use your BP site. Can&#8217;t find what you need? Stop by <a href="http://buddypress.org/support/">our support forums</a>, where a vibrant community of BuddyPress users and developers is waiting to share tips, show off their sites, talk about the future of BuddyPress, and much more.', 'buddypress' ) ?></p>
				</div>

			<?php endif; ?>

			<hr />
			<div class="changelog">
				<h3><?php _e( 'Dynamic links for custom navigation menus', 'buddypress' ); ?></h3>

				<div class="feature-section">
					<p><?php printf( __( 'It&#8217;s now easy to add BuddyPress-specific links to your menus through <a href="%s">Appearance &gt; Menus</a>. For example, you can now add a link to a specific user profile screen, and each person will end up at that screen inside their own user profile.', 'buddypress' ), admin_url( 'nav-menus.php' ) ); ?></p>
				</div>
			</div>

			<hr />
			<div class="changelog">
				<h3><?php _e( 'Notifications component', 'buddypress' ); ?></h3>

				<div class="feature-section">
					<p><?php _e( 'The notification features have been promoted into a new component. Use it to keep your site&#8217;s members abreast of the latest connections and @mentions within the site, via email notifications and Toolbar alerts.', 'buddypress' ); ?></p>

				</div>
			</div>

			<hr />
			<div class="changelog">
				<h3><?php _e( 'Widgets', 'buddypress' ); ?></h3>

				<div class="feature-section">
					<ul>
						<li><?php _e( '<strong>Friends Widget</strong>: a list of recently active, popular, and newest friends of the displayed member.', 'buddypress' ); ?></li>
						<li><?php _e( '<strong>Log In Widget</strong>: adds a simple &ldquo;Log In&rdquo; form to your site.', 'buddypress' ); ?></li>
						<li><?php _e( '<strong>Sitewide Notices Widget</strong>: display Sitewide Notices from the Private Messaging component.', 'buddypress' ); ?></li>
					</ul>
				</div>
			</div>

			<hr />
			<div class="changelog">
				<h3><?php _e( 'Developer changes', 'buddypress' ); ?></h3>

				<div class="feature-section">
					<ul>
						<li><?php _e( '<code>bp_redirect_canonical()</code> functionality has been reinstated', 'buddypress' ); ?></li>
						<li><?php _e( 'Improved phpDoc inline documentation', 'buddypress' ); ?></li>
						<li><?php printf( __( 'Improved compatibility with <a href="%s">develop.svn.wordpress.org</a> unit-test suite', 'buddypress' ), 'https://develop.svn.wordpress.org/' ); ?></li>
						<li><?php printf( __( '<a href="%s">&hellip;and lots more!</a>' ), 'http://codex.buddypress.org/releases/version-1-9' ); ?></li>
					</ul>
				</div>

				<div class="return-to-dashboard">
					<a href="<?php echo esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), $this->settings_page ) ) ); ?>"><?php _e( 'Go to the BuddyPress Settings page', 'buddypress' ); ?></a>
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

		list( $display_version ) = explode( '-', bp_get_version() ); ?>

		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to BuddyPress %s', 'buddypress' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( __( 'BuddyPress %s is our first version with a new component in over two years. Not only that, there are plenty of new features, enhancements, and bug fixes.', 'buddypress' ), $display_version ); ?></div>
			<div class="bp-badge"><?php printf( __( 'Version %s', 'buddypress' ), $display_version ); ?></div>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-about' ), 'index.php' ) ) ); ?>" class="nav-tab">
					<?php _e( 'What&#8217;s New', 'buddypress' ); ?>
				</a><a href="<?php echo esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-credits' ), 'index.php' ) ) ); ?>" class="nav-tab nav-tab-active">
					<?php _e( 'Credits', 'buddypress' ); ?>
				</a>
			</h2>

			<p class="about-description"><?php _e( 'BuddyPress is created by a worldwide network of friendly folks.', 'buddypress' ); ?></p>

			<h4 class="wp-people-group"><?php _e( 'Project Leaders', 'buddypress' ); ?></h4>
			<ul class="wp-people-group " id="wp-people-group-project-leaders">
				<li class="wp-person" id="wp-person-apeatling">
					<a href="http://profiles.wordpress.org/apeatling"><img src="http://0.gravatar.com/avatar/bb29d699b5cba218c313b61aa82249da?s=60" class="gravatar" alt="Andy Peatling" /></a>
					<a class="web" href="http://profiles.wordpress.org/apeatling">Andy Peatling</a>
					<span class="title"><?php _e( 'Founding Developer', 'buddypress' ); ?></span>
				</li>
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

			<h4 class="wp-people-group"><?php _e( 'Core Developers', 'buddypress' ); ?></h4>
			<ul class="wp-people-group " id="wp-people-group-core-developers">
				<li class="wp-person" id="wp-person-r-a-y">
					<a href="http://profiles.wordpress.org/r-a-y"><img src="http://0.gravatar.com/avatar/3bfa556a62b5bfac1012b6ba5f42ebfa?s=60" class="gravatar" alt="Ray" /></a>
					<a class="web" href="http://profiles.wordpress.org/r-a-y">Ray</a>
				</li>
			</ul>

			<h4 class="wp-people-group"><?php _e( 'Recent Rockstars', 'buddypress' ); ?></h4>
			<ul class="wp-people-group " id="wp-people-group-rockstars">
				<li class="wp-person" id="wp-person-imath">
					<a href="http://profiles.wordpress.org/imath"><img src="http://0.gravatar.com/avatar/8b208ca408dad63888253ee1800d6a03?s=60" class="gravatar" alt="Mathieu Viet" /></a>
					<a class="web" href="http://profiles.wordpress.org/imath">Mathieu Viet</a>
				</li>
				<li class="wp-person" id="wp-person-hnla">
					<a href="http://profiles.wordpress.org/hnla"><img src="http://0.gravatar.com/avatar/3860c955aa3f79f13b92826ae47d07fe?s=60" class="gravatar" alt="Hugo Ashmore" /></a>
					<a class="web" href="http://profiles.wordpress.org/hnla">Hugo Ashmore</a>
				</li>
				<li class="wp-person" id="wp-person-mercime">
					<a href="http://profiles.wordpress.org/mercime"><img src="http://0.gravatar.com/avatar/fae451be6708241627983570a1a1817a?s=60" class="gravatar" alt="Mercime" /></a>
					<a class="web" href="http://profiles.wordpress.org/mercime">Mercime</a>
				</li>
				<li class="wp-person" id="wp-person-karmatosed">
					<a href="http://profiles.wordpress.org/karmatosed"><img src="http://0.gravatar.com/avatar/d36d2c1821af9249b69ff7f5ed60529b?s=60" class="gravatar" alt="Tammie Lister" /></a>
					<a class="web" href="http://profiles.wordpress.org/karmatosed">Tammie Lister</a>
				</li>
			</ul>

			<h4 class="wp-people-group"><?php _e( 'Contributors to BuddyPress 1.9', 'buddypress' ); ?></h4>
			<p class="wp-credits-list">
				<a href="http://profiles.wordpress.org/AliMH/">AliMH</a>,
				<a href="http://profiles.wordpress.org/asakurayoh/">asakurayoh</a>,
				<a href="http://profiles.wordpress.org/boonebgorges/">boonebgorges</a>,
				<a href="http://profiles.wordpress.org/burakali/">burakali</a>,
				<a href="http://profiles.wordpress.org/dcavins/">dcavins</a>,
				<a href="http://profiles.wordpress.org/ddean/">ddean</a>,
				<a href="http://profiles.wordpress.org/DennisSmolek/">DennisSmolek</a>,
				<a href="http://profiles.wordpress.org/dimensionmedia/">dimensionmedia</a>,
				<a href="http://profiles.wordpress.org/djpaul/">DJPaul</a>,
				<a href="http://profiles.wordpress.org/dtc7240/">dtc7240</a>,
				<a href="http://profiles.wordpress.org/ericlewis/">ericlewis</a>,
				<a href="http://profiles.wordpress.org/gametako/">gametako</a>,
				<a href="http://profiles.wordpress.org/geoffroycochard/">geoffroycochard</a>,
				<a href="http://profiles.wordpress.org/graham-washbrook/">graham-washbrook</a>,
				<a href="http://profiles.wordpress.org/hanni/">hanni</a>,
				<a href="http://profiles.wordpress.org/haykayltduk/">haykayltduk</a>,
				<a href="http://profiles.wordpress.org/henrywright/">henrywright</a>,
				<a href="http://profiles.wordpress.org/hnla/">hnla</a>,
				<a href="http://profiles.wordpress.org/imath/">imath</a>,
				<a href="http://profiles.wordpress.org/johnjamesjacoby/">johnjamesjacoby</a>,
				<a href="http://profiles.wordpress.org/lenasterg/">lenasterg</a>,
				<a href="http://profiles.wordpress.org/mboynes/">mboynes</a>,
				<a href="http://profiles.wordpress.org/megainfo/">megainfo</a>,
				<a href="http://profiles.wordpress.org/Mike_Cowobo/">Mike_Cowobo</a>,
				<a href="http://profiles.wordpress.org/modemlooper/">modemlooper</a>,
				<a href="http://profiles.wordpress.org/olivM/">olivM</a>,
				<a href="http://profiles.wordpress.org/needle/">needle</a>,
				<a href="http://profiles.wordpress.org/netweblogic/">netweblogic</a>,
				<a href="http://profiles.wordpress.org/r-a-y/">r-a-y</a>,
				<a href="http://profiles.wordpress.org/ryderlewis/">ryderlewis</a>,
				<a href="http://profiles.wordpress.org/sbrajesh/">sbrajesh</a>,
				<a href="http://profiles.wordpress.org/sgr33n/">sgr33n</a>,
				<a href="http://profiles.wordpress.org/sooskriszta/">sooskriszta</a>,
				<a href="http://profiles.wordpress.org/terraling/">terraling</a>,
				<a href="http://profiles.wordpress.org/tomdxw/">tomdxw</a>,
				<a href="http://profiles.wordpress.org/trishasalas/">trishasalas</a>,
				<a href="http://profiles.wordpress.org/vhauri/">vhauri</a>,
				<a href="http://profiles.wordpress.org/williamsba1/">williamsba1</a>,
				<a href="http://profiles.wordpress.org/wpdennis/">wpdennis</a>
			</p>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), $this->settings_page ) ) ); ?>"><?php _e( 'Go to the BuddyPress Settings page', 'buddypress' ); ?></a>
			</div>

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
}
