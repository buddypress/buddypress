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
	 * @uses BP_Admin::setup_globals() Setup the globals needed
	 * @uses BP_Admin::includes() Include the required files
	 * @uses BP_Admin::setup_actions() Setup the hooks and actions
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
	 * Include required files
	 *
	 * @since BuddyPress (1.6)
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
	 * Setup the admin hooks, actions and filters
	 *
	 * @since BuddyPress (1.6)
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 * @uses add_filter() To add various filters
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

		// Add settings
		add_action( 'bp_register_admin_settings', array( $this, 'register_admin_settings' ) );

		/** Filters ***********************************************************/

		// Add link to settings page
		add_filter( 'plugin_action_links',               array( $this, 'modify_plugin_action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );
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
	 * Register the settings
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @uses add_settings_section() To add our own settings section
	 * @uses add_settings_field() To add various settings fields
	 * @uses register_setting() To register various settings
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
	 * Add Settings link to plugins area
	 *
	 * @since BuddyPress (1.6)
	 *
	 * @param array $links Links array in which we would prepend our link
	 * @param string $file Current plugin basename
	 * @return array Processed links
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
	 * Add some general styling to the admin area
	 *
	 * @since BuddyPress (1.6)
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
	 * Add some general styling to the admin area
	 *
	 * @since BuddyPress (1.6)
	 */
	public function enqueue_scripts() {

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$file = $this->css_url . "common{$min}.css";
		$file = apply_filters( 'bp_core_admin_common_css', $file );
		wp_enqueue_style( 'bp-admin-common-css', $file, array(), bp_get_version() );
	}

	/** About *****************************************************************/

	/**
	 * Output the about screen
	 *
	 * @since BuddyPress (1.7)
	 */
	public function about_screen() {
		global $wp_rewrite;

		$is_new_install = ! empty( $_GET['is_new_install'] );

		$pretty_permalinks_enabled = ! empty( $wp_rewrite->permalink_structure );

		list( $display_version ) = explode( '-', bp_get_version() ); ?>

		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to BuddyPress %s' ), $display_version ); ?></h1>
			<div class="about-text">
				<?php if ( $is_new_install ) : ?>
					<?php printf( __( 'BuddyPress %s is our safest, fastest, most flexible version ever.' ), $display_version ); ?>
				<?php else : ?>
					<?php printf( __( 'Thank you for updating! BuddyPress %s is our safest, fastest, most flexible version ever.' ), $display_version ); ?>
				<?php endif; ?>
			</div>
			<div class="bp-badge"><?php printf( __( 'Version %s' ), $display_version ); ?></div>

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
					<p><?php printf(
						__( 'BuddyPress&#8217;s powerful features help your users connect and collaborate. To help get your community started, we&#8217;ve activated two of the most commonly used tools in BP: <strong>Extended Profiles</strong> and <strong>Activity Streams</strong>. See these components in action at the %1$s and %2$s directories, and be sure to spend a few minutes <a href="%3$s">configuring user profiles</a>. Want to explore more of BP&#8217;s features? Visit the <a href="%4$s">Components panel</a>.', 'buddypress' ),
						$pretty_permalinks_enabled ? '<a href="' . trailingslashit( bp_get_root_domain() . '/' . bp_get_members_root_slug() ) . '">' . __( 'Members', 'buddypress' ) . '</a>' : __( 'Members', 'buddypress' ),
						$pretty_permalinks_enabled ? '<a href="' . trailingslashit( bp_get_root_domain() . '/' . bp_get_activity_root_slug() ) . '">' . __( 'Activity', 'buddypress' ) . '</a>' : __( 'Activity', 'buddypress' ),
						bp_get_admin_url( add_query_arg( array( 'page' => 'bp-profile-setup' ), 'users.php' ) ),
						bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), $this->settings_page ) )
					); ?></p>

					<h4><?php _e( 'Community and Support', 'buddypress' ); ?></h4>
					<p><?php _e( 'Looking for help? The <a href="http://codex.buddypress.org/">BuddyPress Codex</a> has you covered, with dozens of user-contributed guides on how to configure and use your BP site. Can&#8217;t find what you need? Stop by <a href="http://buddypress.org/support/">our support forums</a>, where a vibrant community of BuddyPress users and developers is waiting to share tips, show off their sites, talk about the future of BuddyPress, and much more.', 'buddypress' ) ?></p>
				</div>

			<?php endif; ?>

			<div class="changelog">
				<h3><?php _e( 'A Declaration of (Theme) Independence', 'buddypress' ); ?></h3>

				<div class="feature-section">
					<h4><?php _e( 'It Just Works', 'buddypress' ); ?></h4>
					<p><?php _e( 'BuddyPress is now compatible with <strong>any WordPress theme</strong>. If your theme has BuddyPress-specific templates and styling, we&#8217;ll use them. If not, we provide what you need to make your BuddyPress content look great. Still want to customize? No problem - you can override our templates just like you would in a WordPress child theme. <a href="http://codex.buddypress.org/theme-compatibility/">Learn more about theme compatibility</a>.', 'buddypress' ); ?></p>
				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Group Management', 'buddypress' ); ?></h3>

				<div class="feature-section">
					<h4><?php _e( 'Get More Done Quickly', 'buddypress' ); ?></h4>

					<?php
					$group_admin_text = __( 'Groups administration panel', 'buddypress' );
					if ( bp_is_active( 'groups' ) ) {
						$group_admin_text = '<a href="' . bp_get_admin_url( add_query_arg( array( 'page' => 'bp-groups' ), 'admin.php' ) ) . '">' . $group_admin_text . '</a>';
					}
					?>

					<p><?php printf(
						__( 'The new %s makes it easy to handle large numbers of groups on your BuddyPress installation. Delete groups, edit group details, modify memberships, and more, with just a few clicks.', 'buddypress' ),
						$group_admin_text
					); ?></p>
				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Under the Hood', 'buddypress' ); ?></h3>

				<div class="feature-section three-col">
					<div>
						<h4><?php _e( 'Faster Member Queries', 'buddypress' ); ?></h4>
						<p><?php _e( 'The new <code>BP_User_Query</code> makes member queries (like in the Members directory) up to 4x faster than before.', 'buddypress' ); ?></p>

						<h4><?php _e( 'Sortable Profile Options', 'buddypress' ); ?></h4>
						<p><?php _e( 'Profile field types with multiple options - like radio buttons and checkboxes - now support drag-and-drop reordering.', 'buddypress' ); ?></p>
					</div>

					<div>
						<h4><?php _e( 'New Visibility Level', 'buddypress' ); ?></h4>
						<p><?php _e( 'By popular demand, the "Admins Only" visibility setting is now available for profile fields.', 'buddypress' ); ?></p>

						<h4><?php _e( 'Better bbPress Integration', 'buddypress' ); ?></h4>
						<p><?php _e( 'Support for group and sitewide forums, using the latest version of the bbPress plugin, is better than ever. Still using bbPress 1.x? Our new migration tools are field-tested.', 'buddypress' ); ?></p>
					</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), $this->settings_page ) ) ); ?>"><?php _e( 'Go to the BuddyPress Settings page', 'buddypress' ); ?></a>
			</div>

		</div>

		<?php
	}

	/**
	 * Output the credits screen
	 *
	 * Hardcoding this in here is pretty janky. It's fine for 2.2, but we'll
	 * want to leverage api.wordpress.org eventually.
	 *
	 * @since BuddyPress (1.7)
	 */
	public function credits_screen() {

		list( $display_version ) = explode( '-', bp_get_version() ); ?>

		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to BuddyPress %s' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( __( 'Thank you for updating to the latest version! BuddyPress %s is ready to make your community a safer, faster, and better looking place to hang out!' ), $display_version ); ?></div>
			<div class="bp-badge"><?php printf( __( 'Version %s' ), $display_version ); ?></div>

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
				<li class="wp-person" id="wp-person-karmatosed">
					<a href="http://profiles.wordpress.org/karmatosed"><img src="http://0.gravatar.com/avatar/d36d2c1821af9249b69ff7f5ed60529b?s=60" class="gravatar" alt="Tammie Lister" /></a>
					<a class="web" href="http://profiles.wordpress.org/karmatosed">Tammie Lister</a>
					<span class="title"><?php _e( 'Design Officer', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-mercime">
					<a href="http://profiles.wordpress.org/mercime"><img src="http://0.gravatar.com/avatar/fae451be6708241627983570a1a1817a?s=60" class="gravatar" alt="Mercime" /></a>
					<a class="web" href="http://profiles.wordpress.org/mercime">Mercime</a>
					<span class="title"><?php _e( 'Support Officer', 'buddypress' ); ?></span>
				</li>
			</ul>

			<h4 class="wp-people-group"><?php _e( 'Core Contributors to BuddyPress 1.7', 'buddypress' ); ?></h4>
			<p class="wp-credits-list">
				<a href="http://profiles.wordpress.org/aesqe">aesqe</a>,
				<a href="http://profiles.wordpress.org/apeatling">apeatling</a>,
				<a href="http://profiles.wordpress.org/borkweb">borkweb</a>,
				<a href="http://profiles.wordpress.org/calin">calin</a>,
				<a href="http://profiles.wordpress.org/chouf1">chouf1</a>,
				<a href="http://profiles.wordpress.org/chrisclayton">chrisclayton</a>,
				<a href="http://profiles.wordpress.org/cnorris23">cnorris23</a>,
				<a href="http://profiles.wordpress.org/ddean">ddean</a>,
				<a href="http://profiles.wordpress.org/DennisSmolek">DennisSmolek</a>,
				<a href="http://profiles.wordpress.org/Dianakc">Dianakc</a>,
				<a href="http://profiles.wordpress.org/dontdream">dontdream</a>,
				<a href="http://profiles.wordpress.org/empireoflight">empireoflight</a>,
				<a href="http://profiles.wordpress.org/enej">enej</a>,
				<a href="http://profiles.wordpress.org/ethitter">ethitter</a>,
				<a href="http://profiles.wordpress.org/fanquake">fanquake</a>,
				<a href="http://profiles.wordpress.org/gmax21">gmax21</a>,
				<a href="http://profiles.wordpress.org/hnla">hnla</a>,
				<a href="http://profiles.wordpress.org/humanshell">humanshell</a>,
				<a href="http://profiles.wordpress.org/imath">imath</a>,
				<a href="http://profiles.wordpress.org/Jacek">Jacek</a>,
				<a href="http://profiles.wordpress.org/jag1989">jag1989</a>,
				<a href="http://profiles.wordpress.org/jbobich">jbobich</a>,
				<a href="http://profiles.wordpress.org/jkudish">jkudish</a>,
				<a href="http://profiles.wordpress.org/jpsb">jpsb</a>,
				<a href="http://profiles.wordpress.org/MacPresss">MacPresss</a>,
				<a href="http://profiles.wordpress.org/magnus78">magnus78</a>,
				<a href="http://profiles.wordpress.org/markjaquith">markjaquith</a>,
				<a href="http://profiles.wordpress.org/Maty">Maty</a>,
				<a href="http://profiles.wordpress.org/michael.ecklund">michael.ecklund</a>,
				<a href="http://profiles.wordpress.org/modemlooper">modemlooper</a>,
				<a href="http://profiles.wordpress.org/nacin">nacin</a>,
				<a href="http://profiles.wordpress.org/netweb">netweb</a>,
				<a href="http://profiles.wordpress.org/rogercoathup">rogercoathup</a>,
				<a href="http://profiles.wordpress.org/sboisvert">sboisvert</a>,
				<a href="http://profiles.wordpress.org/sbrajesh">sbrajesh</a>,
				<a href="http://profiles.wordpress.org/slaFFik">slaFFik</a>,
				<a href="http://profiles.wordpress.org/steve7777">steve7777</a>,
				<a href="http://profiles.wordpress.org/tiraeth">tiraeth</a>,
				<a href="http://profiles.wordpress.org/will_c">will_c</a>,
				<a href="http://profiles.wordpress.org/wpdennis">wpdennis</a>,
				<a href="http://profiles.wordpress.org/xt4v">xt4v</a>.
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
 * Setup BuddyPress Admin
 *
 * @since BuddyPress (1.6)
 *
 * @uses BP_Admin
 */
function bp_admin() {
       buddypress()->admin = new BP_Admin();
}
