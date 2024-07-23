<?php
/**
 * Main BuddyPress Admin Class.
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'BP_Admin' ) ) {
	return;
}

/**
 * Load BuddyPress plugin admin area.
 *
 * @todo Break this apart into each applicable Component.
 *
 * @since 1.6.0
 */
#[AllowDynamicProperties]
class BP_Admin {

	/** Directory *************************************************************/

	/**
	 * Path to the BuddyPress admin directory.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public $admin_dir = '';

	/** URLs ******************************************************************/

	/**
	 * URL to the BuddyPress admin directory.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public $admin_url = '';

	/**
	 * URL to the BuddyPress images directory.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public $images_url = '';

	/**
	 * URL to the BuddyPress admin CSS directory.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public $css_url = '';

	/**
	 * URL to the BuddyPress admin JS directory.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public $js_url = '';

	/** Other *****************************************************************/

	/**
	 * Notices used for user feedback, like saving settings.
	 *
	 * @since 1.9.0
	 * @var array
	 */
	public $notices = array();

	/**
	 * BuddyPress admin screens nav tabs.
	 *
	 * @since 10.0.0
	 * @var array
	 */
	public $nav_tabs = array();

	/**
	 * BuddyPress admin active nav tab.
	 *
	 * @since 10.0.0
	 * @var string
	 */
	public $active_nav_tab = '';

	/**
	 * BuddyPress admin screens submenu pages.
	 *
	 * @since 10.0.0
	 * @var array
	 */
	public $submenu_pages = array();

	/** Methods ***************************************************************/

	/**
	 * The main BuddyPress admin loader.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Set admin-related globals.
	 *
	 * @since 1.6.0
	 */
	private function setup_globals() {
		$bp = buddypress();

		// Paths and URLs.
		$this->admin_dir  = trailingslashit( $bp->plugin_dir . 'bp-core/admin' ); // Admin path.
		$this->admin_url  = trailingslashit( $bp->plugin_url . 'bp-core/admin' ); // Admin url.
		$this->images_url = trailingslashit( $this->admin_url . 'images' ); // Admin images URL.
		$this->css_url    = trailingslashit( $this->admin_url . 'css' ); // Admin css URL.
		$this->js_url     = trailingslashit( $this->admin_url . 'js' ); // Admin css URL.

		// Main settings page.
		$this->settings_page = bp_core_do_network_admin() ? 'settings.php' : 'options-general.php';

		// Main capability.
		$this->capability = bp_core_do_network_admin() ? 'manage_network_options' : 'manage_options';
	}

	/**
	 * Include required files.
	 *
	 * @since 1.6.0
	 */
	private function includes() {
		require $this->admin_dir . 'bp-core-admin-actions.php';
		require $this->admin_dir . 'bp-core-admin-settings.php';
		require $this->admin_dir . 'bp-core-admin-functions.php';
		require $this->admin_dir . 'bp-core-admin-components.php';
		require $this->admin_dir . 'bp-core-admin-tools.php';
		require $this->admin_dir . 'bp-core-admin-optouts.php';

		if ( 'rewrites' === bp_core_get_query_parser() ) {
			require $this->admin_dir . 'bp-core-admin-rewrites.php';
		}
	}

	/**
	 * Set up the admin hooks, actions, and filters.
	 *
	 * @since 1.6.0
	 */
	private function setup_actions() {

		/* General Actions ***************************************************/

		// Add some page specific output to the <head>.
		add_action( 'bp_admin_head',            array( $this, 'admin_head' ), 999 );

		// Add menu item to settings menu.
		add_action( 'admin_menu',               array( $this, 'site_admin_menus' ), 5 );
		add_action( bp_core_admin_hook(),       array( $this, 'admin_menus' ), 5 );

		// Enqueue all admin JS and CSS.
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'admin_register_styles' ), 1 );
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'admin_register_scripts' ), 1 );
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/* BuddyPress Actions ************************************************/

		// Load the BuddyPress metabox in the WP Nav Menu Admin UI.
		add_action( 'load-nav-menus.php', 'bp_admin_wp_nav_menu_meta_box' );

		// Add settings.
		add_action( 'bp_register_admin_settings', array( $this, 'register_admin_settings' ) );

		// Add a link to BuddyPress Hello in the admin bar.
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_about_link' ), 100 );
		}

		// Add a description of BuddyPress tools in the available tools page.
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			add_action( 'tool_box',            'bp_core_admin_available_tools_intro' );
			add_action( 'bp_network_tool_box', 'bp_core_admin_available_tools_intro' );
		}

		// On non-multisite, catch.
		add_action( 'load-users.php', 'bp_core_admin_user_manage_spammers' );

		// Emails.
		add_filter( 'manage_' . bp_get_email_post_type() . '_posts_columns',       array( $this, 'emails_register_situation_column' ) );
		add_action( 'manage_' . bp_get_email_post_type() . '_posts_custom_column', array( $this, 'emails_display_situation_column_data' ), 10, 2 );

		// Privacy Policy.
		add_action( 'bp_admin_init', array( $this, 'add_privacy_policy_content' ) );

		// BuddyPress Hello.
		add_action( 'admin_footer', array( $this, 'about_screen' ) );

		// BuddyPress Types administration.
		add_action( 'load-edit-tags.php', array( 'BP_Admin_Types', 'register_types_admin' ) );

		// Official BuddyPress supported Add-ons.
		add_action( 'install_plugins_bp-add-ons', array( $this, 'display_addons_table' ) );

		/* Filters ***********************************************************/

		// Add link to settings page.
		add_filter( 'plugin_action_links',               array( $this, 'modify_plugin_action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );

		// Add "Mark as Spam" row actions on users.php.
		add_filter( 'ms_user_row_actions', 'bp_core_admin_user_row_actions', 10, 2 );
		add_filter( 'user_row_actions',    'bp_core_admin_user_row_actions', 10, 2 );

		// Emails.
		add_filter( 'bp_admin_menu_order', array( $this, 'emails_admin_menu_order' ), 20 );
		add_action( 'load-edit.php', array( $this, 'post_type_load_admin_screen' ), 20 );
		add_action( 'load-post.php', array( $this, 'post_type_load_admin_screen' ), 20 );

		// Official BuddyPress supported Add-ons.
		add_filter( 'install_plugins_tabs', array( $this, 'addons_tab' ) );
		add_filter( 'install_plugins_table_api_args_bp-add-ons', array( $this, 'addons_args' ) );
	}

	/**
	 * Register site- or network-admin nav menu elements.
	 *
	 * Contextually hooked to site or network-admin depending on current configuration.
	 *
	 * @since 1.6.0
	 */
	public function admin_menus() {

		// Bail if user cannot moderate.
		if ( ! bp_current_user_can( 'manage_options' ) ) {
			return;
		}

		$hooks = array();

		// Changed in BP 1.6 . See bp_core_admin_backpat_menu().
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

		// Add the option pages.
		$bp_components_page = add_submenu_page(
			$this->settings_page,
			__( 'BuddyPress Components', 'buddypress' ),
			__( 'BuddyPress', 'buddypress' ),
			$this->capability,
			'bp-components',
			'bp_core_admin_components_settings'
		);

		$this->submenu_pages['settings']['bp-components'] = $bp_components_page;
		$hooks[]                                          = $bp_components_page;

		if ( 'rewrites' === bp_core_get_query_parser() ) {
			$bp_rewrites_settings_page = add_submenu_page(
				$this->settings_page,
				__( 'BuddyPress URLs', 'buddypress' ),
				__( 'BuddyPress URLs', 'buddypress' ),
				$this->capability,
				'bp-rewrites',
				'bp_core_admin_rewrites_settings'
			);

			$this->submenu_pages['settings']['bp-rewrites'] = $bp_rewrites_settings_page;
			$hooks[]                                        = $bp_rewrites_settings_page;
		}

		$bp_settings_page = add_submenu_page(
			$this->settings_page,
			__( 'BuddyPress Options', 'buddypress' ),
			__( 'BuddyPress Options', 'buddypress' ),
			$this->capability,
			'bp-settings',
			'bp_core_admin_settings'
		);

		$this->submenu_pages['settings']['bp-settings'] = $bp_settings_page;
		$hooks[]                                        = $bp_settings_page;

		// Admin notifications.
		$bp_admin_notifications = add_submenu_page(
			$this->settings_page,
			__( 'BuddyPress Admin Notifications', 'buddypress' ),
			__( 'BuddyPress Admin Notifications', 'buddypress' ),
			$this->capability,
			'bp-admin-notifications',
			array( $this, 'admin_notifications' )
		);

		$this->submenu_pages['settings']['bp-admin-notifications'] = $bp_admin_notifications;
		$hooks[] = $bp_admin_notifications;

		// Credits.
		$bp_credits_page = add_submenu_page(
			$this->settings_page,
			__( 'BuddyPress Credits', 'buddypress' ),
			__( 'BuddyPress Credits', 'buddypress' ),
			$this->capability,
			'bp-credits',
			array( $this, 'credits_screen' )
		);

		$this->submenu_pages['settings']['bp-credits'] = $bp_credits_page;
		$hooks[]                                       = $bp_credits_page;

		// For consistency with non-Multisite, we add a Tools menu in
		// the Network Admin as a home for our Tools panel.
		if ( is_multisite() && bp_core_do_network_admin() ) {
			$tools_parent = 'network-tools';

			$hooks[] = add_menu_page(
				__( 'Tools', 'buddypress' ),
				__( 'Tools', 'buddypress' ),
				$this->capability,
				$tools_parent,
				'bp_core_tools_top_level_item',
				'',
				24 // Just above Settings.
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

		// Init the Tools submenu pages global.
		$this->submenu_pages['tools'] = array();

		$bp_repair_tools = add_submenu_page(
			$tools_parent,
			__( 'BuddyPress Tools', 'buddypress' ),
			__( 'BuddyPress', 'buddypress' ),
			$this->capability,
			'bp-tools',
			'bp_core_admin_tools'
		);

		$this->submenu_pages['tools']['bp-tools'] = $bp_repair_tools;
		$hooks[]                                  = $bp_repair_tools;

		$bp_optouts_tools = add_submenu_page(
			$tools_parent,
			__( 'Manage Opt-outs', 'buddypress' ),
			__( 'Manage Opt-outs', 'buddypress' ),
			$this->capability,
			'bp-optouts',
			'bp_core_optouts_admin'
		);

		$this->submenu_pages['tools']['bp-optouts'] = $bp_optouts_tools;
		$hooks[]                                    = $bp_optouts_tools;

		// For network-wide configs, add a link to (the root site's) Emails screen.
		if ( is_network_admin() && bp_is_network_activated() ) {
			$email_labels = bp_get_email_post_type_labels();
			$email_url    = get_admin_url( bp_get_root_blog_id(), 'edit.php?post_type=' . bp_get_email_post_type() );

			$hooks[] = add_menu_page(
				$email_labels['name'],
				$email_labels['menu_name'],
				$this->capability,
				'',
				'',
				'dashicons-email',
				26
			);

			// Hack: change the link to point to the root site's admin, not the network admin.
			$GLOBALS['menu'][26][2] = esc_url_raw( $email_url );
		}

		foreach ( $hooks as $hook ) {
			add_action( "admin_head-$hook", 'bp_core_modify_admin_menu_highlight' );

			if ( 'settings_page_bp-rewrites' === $hook ) {
				add_action( "load-{$hook}", 'bp_core_admin_rewrites_load' );
			}
		}

		/**
		 * Fires before adding inline styles for BP Admin tabs.
		 *
		 * @since 10.0.0
		 *
		 * @param array $submenu_pages The BP_Admin submenu pages passed by reference.
		 */
		do_action_ref_array( 'bp_admin_submenu_pages', array( &$this->submenu_pages ) );

		foreach ( $this->submenu_pages as $subpage_type => $subpage_hooks ) {
			foreach ( $subpage_hooks as $subpage_hook ) {
				add_action( "admin_print_styles-{$subpage_hook}", array( $this, 'add_inline_styles' ), 20 );

				// When BuddyPress is activated on the network, the settings screens need an admin notice when settings have been updated.
				if ( is_network_admin() && bp_is_network_activated() && 'settings' === $subpage_type && 'settings_page_bp-credits' !== $subpage_hook ) {
					add_action( "load-{$subpage_hook}", array( $this, 'admin_load' ) );
				}
			}
		}
	}

	/**
	 * Register site-admin nav menu elements.
	 *
	 * @since 2.5.0
	 */
	public function site_admin_menus() {
		if ( ! bp_current_user_can( 'manage_options' ) ) {
			return;
		}

		$hooks = array();

		// Appearance > Emails.
		$hooks[] = add_theme_page(
			_x( 'Emails', 'screen heading', 'buddypress' ),
			_x( 'Emails', 'screen heading', 'buddypress' ),
			$this->capability,
			'bp-emails-customizer-redirect',
			'bp_email_redirect_to_customizer'
		);

		// Emails > Customize.
		$hooks[] = add_submenu_page(
			'edit.php?post_type=' . bp_get_email_post_type(),
			_x( 'Customize', 'email menu label', 'buddypress' ),
			_x( 'Customize', 'email menu label', 'buddypress' ),
			$this->capability,
			'bp-emails-customizer-redirect',
			'bp_email_redirect_to_customizer'
		);

		foreach ( $hooks as $hook ) {
			add_action( "admin_head-$hook", 'bp_core_modify_admin_menu_highlight' );
		}
	}

	/**
	 * Register the settings.
	 *
	 * @since 1.6.0
	 */
	public function register_admin_settings() {

		/* Core Section ******************************************************/

		// Add the Core section.
		add_settings_section( 'bp_main', __( 'BuddyPress Core', 'buddypress' ), 'bp_admin_setting_callback_main_section', 'buddypress' );

		// Hide toolbar for logged out users setting.
		add_settings_field( 'hide-loggedout-adminbar', __( 'Toolbar', 'buddypress' ), 'bp_admin_setting_callback_admin_bar', 'buddypress', 'bp_main' );
		register_setting( 'buddypress', 'hide-loggedout-adminbar', 'intval' );

		// Community Visibility.
		if ( 'rewrites' === bp_core_get_query_parser() ) {
			add_settings_field( '_bp_community_visibility', __( 'Community Visibility', 'buddypress' ), 'bp_admin_setting_callback_community_visibility', 'buddypress', 'bp_main' );
			register_setting( 'buddypress', '_bp_community_visibility', 'bp_admin_sanitize_callback_community_visibility' );
		}

		// Template pack picker.
		add_settings_field( '_bp_theme_package_id', __( 'Template Pack', 'buddypress' ), 'bp_admin_setting_callback_theme_package_id', 'buddypress', 'bp_main', array( 'label_for' => '_bp_theme_package_id' ) );
		register_setting( 'buddypress', '_bp_theme_package_id', 'sanitize_text_field' );

		/* Account settings Section  *****************************************/

		if ( bp_is_active( 'settings' ) ) {
			// Add the Settings section.
			add_settings_section( 'bp_account_settings', _x( 'Account Settings', 'BuddyPress setting tab', 'buddypress' ), 'bp_admin_setting_callback_settings_section', 'buddypress' );

			// Allow account deletion.
			add_settings_field( 'bp-disable-account-deletion', __( 'Account Deletion', 'buddypress' ), 'bp_admin_setting_callback_account_deletion', 'buddypress', 'bp_account_settings' );
			register_setting( 'buddypress', 'bp-disable-account-deletion', 'intval' );
		}

		/* Members Section  **************************************************/

		// Add the main section.
		add_settings_section( 'bp_members', _x( 'Community Members', 'BuddyPress setting tab', 'buddypress' ), 'bp_admin_setting_callback_members_section', 'buddypress' );

		// Avatars.
		add_settings_field( 'bp-disable-avatar-uploads', __( 'Profile Photo Uploads', 'buddypress' ), 'bp_admin_setting_callback_avatar_uploads', 'buddypress', 'bp_members' );
		register_setting( 'buddypress', 'bp-disable-avatar-uploads', 'intval' );

		// Cover images.
		if ( bp_is_active( 'members', 'cover_image' ) ) {
			add_settings_field( 'bp-disable-cover-image-uploads', __( 'Cover Image Uploads', 'buddypress' ), 'bp_admin_setting_callback_cover_image_uploads', 'buddypress', 'bp_members' );
			register_setting( 'buddypress', 'bp-disable-cover-image-uploads', 'intval' );
		}

		// Community Invitations.
		if ( bp_is_active( 'members', 'invitations' ) ) {
			add_settings_field( 'bp-enable-members-invitations', __( 'Invitations', 'buddypress' ), 'bp_admin_setting_callback_members_invitations', 'buddypress', 'bp_members' );
			register_setting( 'buddypress', 'bp-enable-members-invitations', 'intval' );
		}

		// Membership requests.
		if ( bp_is_active( 'members', 'membership_requests' ) ) {
			add_settings_field( 'bp-enable-membership-requests', __( 'Membership Requests', 'buddypress' ), 'bp_admin_setting_callback_membership_requests', 'buddypress', 'bp_members' );
			register_setting( 'buddypress', 'bp-enable-membership-requests', 'intval' );
		}

		/* XProfile Section **************************************************/

		if ( bp_is_active( 'xprofile' ) ) {

			// Add the main section.
			add_settings_section( 'bp_xprofile', _x( 'Extended Profiles', 'BuddyPress setting tab', 'buddypress' ), 'bp_admin_setting_callback_xprofile_section', 'buddypress' );

			// Profile sync setting.
			add_settings_field( 'bp-disable-profile-sync',   __( 'Profile Syncing',  'buddypress' ), 'bp_admin_setting_callback_profile_sync', 'buddypress', 'bp_xprofile' );
			register_setting( 'buddypress', 'bp-disable-profile-sync', 'intval' );
		}

		/* Groups Section ****************************************************/

		if ( bp_is_active( 'groups' ) ) {

			// Add the main section.
			add_settings_section( 'bp_groups', __( 'User Groups',  'buddypress' ), 'bp_admin_setting_callback_groups_section', 'buddypress' );

			// Allow subscriptions setting.
			add_settings_field( 'bp_restrict_group_creation', __( 'Group Creation', 'buddypress' ), 'bp_admin_setting_callback_group_creation',   'buddypress', 'bp_groups' );
			register_setting( 'buddypress', 'bp_restrict_group_creation', 'intval' );

			// Allow group avatars.
			add_settings_field( 'bp-disable-group-avatar-uploads', __( 'Group Photo Uploads', 'buddypress' ), 'bp_admin_setting_callback_group_avatar_uploads', 'buddypress', 'bp_groups' );
			register_setting( 'buddypress', 'bp-disable-group-avatar-uploads', 'intval' );

			// Allow group cover images.
			if ( bp_is_active( 'groups', 'cover_image' ) ) {
				add_settings_field( 'bp-disable-group-cover-image-uploads', __( 'Group Cover Image Uploads', 'buddypress' ), 'bp_admin_setting_callback_group_cover_image_uploads', 'buddypress', 'bp_groups' );
				register_setting( 'buddypress', 'bp-disable-group-cover-image-uploads', 'intval' );
			}

			// Allow group activity deletions.
			add_settings_field( 'bp-disable-group-activity-deletions', esc_html__( 'Group Activity Deletions', 'buddypress' ), 'bp_admin_setting_callback_group_activity_deletions', 'buddypress', 'bp_groups' );
			register_setting( 'buddypress', 'bp-disable-group-activity-deletions', 'intval' );
		}

		/* Activity Section **************************************************/

		if ( bp_is_active( 'activity' ) ) {

			// Add the main section.
			add_settings_section( 'bp_activity', __( 'Activity Streams', 'buddypress' ), 'bp_admin_setting_callback_activity_section', 'buddypress' );

			// Activity commenting on post and comments.
			add_settings_field( 'bp-disable-blogforum-comments', __( 'Post Comments', 'buddypress' ), 'bp_admin_setting_callback_blogforum_comments', 'buddypress', 'bp_activity' );
			register_setting( 'buddypress', 'bp-disable-blogforum-comments', 'bp_admin_sanitize_callback_blogforum_comments' );

			// Activity Heartbeat refresh.
			add_settings_field( '_bp_enable_heartbeat_refresh', __( 'Activity auto-refresh', 'buddypress' ), 'bp_admin_setting_callback_heartbeat', 'buddypress', 'bp_activity' );
			register_setting( 'buddypress', '_bp_enable_heartbeat_refresh', 'intval' );

			// Allow activity akismet.
			if ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) {
				add_settings_field( '_bp_enable_akismet', __( 'Akismet', 'buddypress' ), 'bp_admin_setting_callback_activity_akismet', 'buddypress', 'bp_activity' );
				register_setting( 'buddypress', '_bp_enable_akismet', 'intval' );
			}
		}
	}

	/**
	 * Add a link to BuddyPress Hello to the admin bar.
	 *
	 * @since 1.9.0
	 * @since 3.0.0 Hooked at priority 100 (was 15).
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WordPress object implementing a Toolbar API.
	 */
	public function admin_bar_about_link( $wp_admin_bar ) {
		if ( ! bp_current_user_can( $this->capability ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'parent' => 'wp-logo',
				'id'     => 'bp-about',
				'title'  => esc_html_x( 'Hello, BuddyPress!', 'Colloquial alternative to "learn about BuddyPress"', 'buddypress' ),
				'href'   => add_query_arg(
					array(
						'page'  => 'bp-components',
						'hello' => 'buddypress'
					),
					bp_get_admin_url( $this->settings_page )
				),
				'meta'   => array(
					'class' => 'say-hello-buddypress',
				),
			)
		);
	}

	/**
	 * Add Settings link to plugins area.
	 *
	 * @since 1.6.0
	 *
	 * @param array  $links Links array in which we would prepend our link.
	 * @param string $file  Current plugin basename.
	 * @return array Processed links.
	 */
	public function modify_plugin_action_links( $links, $file ) {

		// Return normal links if not BuddyPress.
		if ( plugin_basename( buddypress()->basename ) !== $file ) {
			return $links;
		}

		$settings_args = array(
			'page' => 'bp-components',
		);

		$about_args = array_merge(
			$settings_args,
			array(
				'hello' => 'buddypress',
			)
		);

		// Add a few links to the existing links array.
		return array_merge( $links, array(
			'settings' => '<a href="' . esc_url( add_query_arg( $settings_args, bp_get_admin_url( $this->settings_page ) ) ) . '">' . esc_html__( 'Settings', 'buddypress' ) . '</a>',
			'about'    => '<a href="' . esc_url( add_query_arg( $about_args, bp_get_admin_url( $this->settings_page ) ) ) . '">' . esc_html_x( 'Hello, BuddyPress!', 'Colloquial alternative to "learn about BuddyPress"', 'buddypress' ) . '</a>'
		) );
	}

	/**
	 * Displays an admin notice to inform settings have been saved.
	 *
	 * The notice is only displayed inside the Network Admin when
	 * BuddyPress is network activated.
	 *
	 * @since 10.0.0
	 */
	public function admin_load() {
		if ( ! isset( $_GET['updated'] ) ) {
			return;
		}

		bp_core_add_admin_notice( __( 'Settings saved.', 'buddypress' ), 'updated' );
	}

	/**
	 * Add some general styling to the admin area.
	 *
	 * @since 1.6.0
	 */
	public function admin_head() {

		// Settings pages.
		remove_submenu_page( $this->settings_page, 'bp-rewrites'            );
		remove_submenu_page( $this->settings_page, 'bp-settings'            );
		remove_submenu_page( $this->settings_page, 'bp-credits'             );
		remove_submenu_page( $this->settings_page, 'bp-admin-notifications' );

		// Network Admin Tools.
		remove_submenu_page( 'network-tools', 'network-tools' );

		// About and Credits pages.
		remove_submenu_page( 'index.php', 'bp-about'   );
		remove_submenu_page( 'index.php', 'bp-credits' );

		// Nonmembers Opt-outs page.
		if ( is_network_admin() ) {
			remove_submenu_page( 'network-tools', 'bp-optouts' );
		} else {
			remove_submenu_page( 'tools.php', 'bp-optouts' );
		}
	}

	/**
	 * Add some general styling to the admin area.
	 *
	 * @since 1.6.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'bp-admin-common-css' );

		// BuddyPress Hello.
		if ( isset( $this->submenu_pages['settings']['bp-components'] ) && 0 === strpos( get_current_screen()->id, $this->submenu_pages['settings']['bp-components'] ) && ! empty( $_GET['hello'] ) && $_GET['hello'] === 'buddypress' ) {
			wp_enqueue_style( 'bp-hello-css' );
			wp_enqueue_script( 'bp-hello-js' );
			wp_localize_script( 'bp-hello-js', 'bpHelloStrings', array(
				'pageNotFound' => __( 'Sorry, the page you requested was not found.', 'buddypress' ),
				'modalLabel'   => __( 'Hello BuddyPress', 'buddypress' ),
			) );
		}
	}

	/**
	 * Registers BuddyPress's suggested privacy policy language.
	 *
	 * @since 4.0.0
	 */
	public function add_privacy_policy_content() {
		$suggested_text = '<strong class="privacy-policy-tutorial">' . esc_html__( 'Suggested text:', 'buddypress' ) . ' </strong>';
		$content = '';

		$content .= '<div class="wp-suggested-text">';

		$content .= '<h2>' . esc_html__( 'What personal data we collect and why we collect it', 'buddypress' ) . '</h2>';
		$content .= '<p class="privacy-policy-tutorial">' . esc_html__( 'Sites powered by BuddyPress rely heavily on user-provided data. In this section, you should note what data you collect, from both registered users and anonymous visitors.', 'buddypress' ) . '</p>';

		if ( bp_is_active( 'xprofile' ) ) {
			$content .= '<h3>' . esc_html__( 'Profile Data', 'buddypress' ) . '</h3>';
			$content .= '<p class="privacy-policy-tutorial">' . esc_html__( 'In this section you should note what information is collected on user profiles. The suggested text gives an overview of the kinds of profile data collected by BuddyPress.', 'buddypress' ) . '</p>';

			$content .= '<p>' . $suggested_text . esc_html__( 'When you register for the site, you may be asked to provide certain personal data for display on your profile. The "Name" field is required as well as public, and user profiles are visible to any site visitor. Other profile information may be required or optional, as configured by the site administrator.', 'buddypress' ) . '</p>';
			$content .= '<p>' . esc_html__( 'User information provided during account registration can be modified or removed on the Profile > Edit panel. In most cases, users also have control over who is able to view a particular piece of profile content, limiting visibility on a field-by-field basis to friends, logged-in users, or administrators only. Site administrators can read and edit all profile data for all users.', 'buddypress' ) . '</p>';
		}

		if ( bp_is_active( 'activity' ) ) {
			$content .= '<h3>' . esc_html__( 'Activity', 'buddypress' ) . '</h3>';
			$content .= '<p class="privacy-policy-tutorial">' . esc_html__( 'In this section you should describe the kinds of information collected in the activity stream, how and whether it can be edited or deleted, and to whom the activity is visible.', 'buddypress' ) . '</p>';

			$content .= '<p>' . $suggested_text . esc_html__( 'This site records certain user actions, in the form of "activity" data. Activity includes updates and comments posted directly to activity streams, as well as descriptions of other actions performed while using the site, such as new friendships, newly joined groups, and profile updates.', 'buddypress' ) . '</p>';
			$content .= '<p>' . esc_html__( 'The content of activity items obey the same privacy rules as the contexts in which the activity items are created. For example, activity updates created in a user\'s profile is publicly visible, while activity items generated in a private group are visible only to members of that group. Site administrators can view all activity items, regardless of context.', 'buddypress' ) . '</p>';
			$content .= '<p>' . esc_html__( 'Activity items may be deleted at any time by users who created them. Site administrators can edit all activity items.', 'buddypress' ) . '</p>';
		}

		if ( bp_is_active( 'messages' ) ) {
			$content .= '<h3>' . esc_html__( 'Messages', 'buddypress' ) . '</h3>';
			$content .= '<p class="privacy-policy-tutorial">' . esc_html__( 'In this section you should describe any personal data related to private messages.', 'buddypress' ) . '</p>';

			$content .= '<p>' . $suggested_text . esc_html__( 'The content of private messages is visible only to the sender and the recipients of the message. With the exception of site administrators, who can read all private messages, private message content is never visible to other users or site visitors. Site administrators may delete the content of any message.', 'buddypress' ) . '</p>';
		}

		$content .= '<h3>' . esc_html__( 'Cookies', 'buddypress' ) . '</h3>';
		$content .= '<p class="privacy-policy-tutorial">' . esc_html__( 'In this section you should describe the BuddyPress-specific cookies that your site collects. The suggested text describes the default cookies.', 'buddypress' ) . '</p>';

		$content .= '<p>' . $suggested_text . esc_html__( 'We use a cookie to show success and failure messages to logged-in users, in response to certain actions, like joining a group. These cookies contain no personal data, and are deleted immediately after the next page load.', 'buddypress' ) . '</p>';

		$content .= '<p>' . esc_html__( 'We use cookies on group, member, and activity directories to keep track of a user\'s browsing preferences. These preferences include the last-selected values of the sort and filter dropdowns, as well as pagination information. These cookies contain no personal data, and are deleted after 24 hours.', 'buddypress' ) . '</p>';

		if ( bp_is_active( 'groups' ) ) {
			$content .= '<p>' . esc_html__( 'When a logged-in user creates a new group, we use a number of cookies to keep track of the group creation process. These cookies contain no personal data, and are deleted either upon the successful creation of the group or after 24 hours.', 'buddypress' ) . '</p>';
		}

		$content .= '</div><!-- .wp-suggested-text -->';

		wp_add_privacy_policy_content(
			'BuddyPress',
			wp_kses_post( wpautop( $content, false ) )
		);
	}

	/** About *****************************************************************/

	/**
	 * Output the BuddyPress Hello template.
	 *
	 * @since 1.7.0 Screen content.
	 * @since 3.0.0 Now outputs BuddyPress Hello template.
	 */
	public function about_screen() {
		if ( ! isset( $this->submenu_pages['settings']['bp-components'] ) || 0 !== strpos( get_current_screen()->id, $this->submenu_pages['settings']['bp-components'] ) || empty( $_GET['hello'] ) || $_GET['hello'] !== 'buddypress' ) {
			return;
		}

		// Get BuddyPress stable version.
		$version      = self::display_version();
		$version_slug = 'version-' . str_replace( '.', '-', $version );
	?>

		<div id="bp-hello-container">
			<div id="plugin-information-scrollable" role="document">
				<div id='plugin-information-title' class="with-banner">
					<div class='vignette'></div>
					<h1>
						<?php printf(
							/* translators: %s is the placeholder for the BuddyPress version number. */
							esc_html__( 'BuddyPress %s', 'buddypress' ),
							esc_html( $version )
						); ?>
					</h1>
				</div>
				<div id="plugin-information-tabs">
					<a name="whats-new" href="#whats-new" class="current"><?php esc_html_e( 'What\'s new?', 'buddypress' ); ?></a>
					<a name="changelog" href="#changelog" class="dynamic" data-slug="<?php echo esc_attr( $version_slug ); ?>" data-endpoint="https://codex.buddypress.org/wp-json/wp/v2/pages"><?php esc_html_e( 'Changelog', 'buddypress' ); ?></a>
					<a name="get-involved" href="#get-involved" class="dynamic" data-slug="participate-and-contribute" data-endpoint="https://codex.buddypress.org/wp-json/wp/v2/pages"><?php esc_html_e( 'Get involved', 'buddypress' ); ?></a>
				</div>

				<div class="bp-hello-content">
					<div id="dynamic-content"></div>
					<div id="top-features">
						<p>
							<?php esc_html_e( 'Thanks for upgrading BuddyPress to 14.0.0. This new major version of your site’s community engine introduces around 80 changes mostly acting under the hood to improve documentation, code formatting, consistency and the stability of the plugin.', 'buddypress' ); ?>
							<?php esc_html_e( 'Here are five improvements we would like to highlight:', 'buddypress' ); ?>
						</p>
						<ol>
							<li>
								<?php esc_html_e( 'There’s a new "BuddyPress constants" panel added to the WordPress Site Health information tool. Use it to check whether you’re using deprecated constants in your custom code or third party BP Plugins/Add-ons.', 'buddypress' ); ?>
								<?php esc_html_e( 'The information in the "BuddyPress" and "BuddyPress constants" panels is also very useful when you need to ask for support.', 'buddypress' ); ?>
							</li>
							<li>
								<?php
								printf(
									/* Translators: %s is a the link to the new User Documentation on GitHub */
									esc_html__( 'Most BuddyPress Admin screens now have a help tab in their top right corner which includes a link to an updated %s.', 'buddypress' ),
									sprintf(
										'<a href="%1$s">%2$s</a>',
										esc_url( 'https://github.com/buddypress/buddypress/tree/master/docs/user/administration#readme' ),
										esc_html__( 'documentation resource', 'buddypress' )
									)
								);
								?>
							</li>
							<li>
								<?php
								printf(
									/* translators: %s is the placeholder for the link to a developer note. */
									esc_html__( 'Whether BuddyPress is installed on a multisite network or on a single site, %s are now managed the exact same way.', 'buddypress' ),
									sprintf(
										'<a href="%1$s">%2$s</a>',
										esc_url( 'https://bpdevel.wordpress.com/2024/04/21/signups-are-becoming-members-only-after-validating-their-accounts/' ),
										esc_html__( 'signups', 'buddypress' )
									)
								);
								?>
							</li>
							<li>
								<?php
								printf(
									/* translators: %s is the placeholder for the link to a developer note. */
									esc_html__( 'Speaking of signups, the %s has been improved so that you can now submit values for any xProfile field registered as part of the Signups profile field group.', 'buddypress' ),
									sprintf(
										'<a href="%1$s">%2$s</a>',
										esc_url( 'https://bpdevel.wordpress.com/2024/05/07/signup-fields-via-the-rest-api/' ),
										esc_html__( 'BP REST API', 'buddypress' )
									)
								);
								?>
							</li>
							<li>
							<?php
								printf(
									/* translators: %s is the placeholder for the link to a developer note. */
									esc_html__( 'Last but not least, we again offer native support for overriding BuddyPress’s language with your community vocabulary using %s.', 'buddypress' ),
									sprintf(
										'<a href="%1$s">%2$s</a>',
										esc_url( 'https://bpdevel.wordpress.com/2024/06/28/translating-buddypress-texts-into-your-community-vocabulary-is-back-in-14-0-0/' ),
										esc_html__( 'custom translations', 'buddypress' )
									)
								);
								?>
							</li>
						</ol>

						<hr class="bp-hello-divider"/>

						<p>
							<?php
							printf(
								/* Translators: %s is a black cat emoji. */
								esc_html__( 'Compared to our previous major version (12.0.0 - the number right after was too intimidating %s), 14.0.0 is a quieter update.', 'buddypress' ),
								// phpcs:ignore WordPress.Security.EscapeOutput
								wp_staticize_emoji( '🐈‍⬛' )
							);
							echo '&nbsp;';
							esc_html_e( 'After the huge BP Rewrites API revolution, the humans (us the BP Team) who maintain and support your favorite community plugin needed to catch their breath to get ready for the new round of big changes arriving in 15.0.0.', 'buddypress' );
							?>
						</p>

						<hr class="bp-hello-divider"/>

						<figure class="bp-hello-aligncenter">
							<div class="dashicons dashicons-buddicons-buddypress-logo big"></div>
						</figure>

						<hr class="bp-hello-divider"/>

						<p>
							<?php
							esc_html_e( 'Let’s keep in mind BuddyPress is an open source project maintained by volunteers giving freely of their time and energy to help you build great WordPress community sites.', 'buddypress' );
							echo '&nbsp;';
							printf(
									/* Translators: %s is a the link to the new Contributor Documentation on GitHub */
									esc_html__( 'Don’t hesitate to send us some encouraging words and please consider contributing back to %s.', 'buddypress' ),
									sprintf(
										'<a href="%1$s">%2$s</a>',
										esc_url( 'https://github.com/buddypress/buddypress/tree/master/docs/contributor#readme' ),
										esc_html__( 'the project', 'buddypress' )
									)
								);
							?>
						</p>

						<hr class="bp-hello-divider"/>

						<h2><?php echo esc_html( _x( 'Your feedback', 'screen heading', 'buddypress' ) ); ?></h2>
						<p>
							<?php esc_html_e( 'How are you using BuddyPress? Receiving your feedback and suggestions for future versions of BuddyPress genuinely motivates and encourages our contributors.', 'buddypress' ); ?>
							<?php
							printf(
								/* translators: %s is the placeholder for the link to BuddyPress support forums. */
								esc_html__( 'Please %s about this version of BuddyPress on our website.', 'buddypress' ),
								sprintf(
									'<a href="%1$s">%2$s</a>',
									esc_url( 'https://buddypress.org/support/' ),
									esc_html__( 'share your feedback', 'buddypress' )
								)
							);
							?>
						</p>
						<p>
							<?php
								printf(
									/* Translators: %s is a smiling face with heart-eyes emoji. */
									esc_html__( 'Many thanks to you for trusting BuddyPress to power your community site %s', 'buddypress' ),
									// phpcs:ignore WordPress.Security.EscapeOutput
									wp_staticize_emoji( '😍' )
								);
							?>
						</p>

						<br /><br />
					</div>
				</div>
			</div>
			<div id="plugin-information-footer">
				<div class="bp-hello-social-cta">
					<p>
						<?php
						echo wp_kses(
							sprintf(
								/* translators: 1: heart dashicons. 2: BP Credits screen url. 3: number of BuddyPress contributors to this version. */
								_n( 'Built with %1$s by <a href="%2$s">%3$d volunteer</a>.', 'Built with %1$s by <a href="%2$s">%3$d volunteers</a>.', 47, 'buddypress' ),
								'<span class="dashicons dashicons-heart"></span>',
								esc_url( bp_get_admin_url( 'admin.php?page=bp-credits' ) ),
								esc_html( number_format_i18n( 47 ) )
							),
							array(
								'a'    => array(
									'href' => true,
								),
								'span' => array(
									'class' => true,
								),
							)
						);
						?>
					</p>
				</div>

				<div class="bp-hello-social-links">
					<ul class="bp-hello-social">
						<li>
							<?php
							printf(
								'<a class="twitter bp-tooltip" data-bp-tooltip="%1$s" href="%2$s"><span class="screen-reader-text">%3$s</span></a>',
								esc_attr__( 'Follow BuddyPress on X/Twitter', 'buddypress' ),
								esc_url( 'https://twitter.com/buddypress' ),
								esc_html__( 'Follow BuddyPress on X/Twitter', 'buddypress' )
							);
							?>
						</li>

						<li>
							<?php
							printf(
								'<a class="support bp-tooltip" data-bp-tooltip="%1$s" href="%2$s"><span class="screen-reader-text">%3$s</span></a>',
								esc_attr__( 'Visit the Support Forums', 'buddypress' ),
								esc_url( 'https://buddypress.org/support/' ),
								esc_html__( 'Visit the Support Forums', 'buddypress' )
							);
							?>
						</li>
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
	 * @since 1.7.0
	 */
	public function credits_screen() {
		bp_core_admin_tabbed_screen_header( __( 'BuddyPress Settings', 'buddypress' ), __( 'Credits', 'buddypress' ) );
	?>

		<div class="buddypress-body bp-about-wrap">

			<p class="about-description"><?php esc_html_e( 'Meet the contributors behind BuddyPress:', 'buddypress' ); ?></p>

			<h3 class="wp-people-group"><?php esc_html_e( 'Chefs', 'buddypress' ); ?></h3>
			<ul class="wp-people-group " id="wp-people-group-project-leaders">
				<li class="wp-person" id="wp-person-johnjamesjacoby">
					<a class="web" href="https://profiles.wordpress.org/johnjamesjacoby/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/7a2644fb53ae2f7bfd7143b504af396c?s=120">
					John James Jacoby</a>
					<span class="title"><?php esc_html_e( 'Grand Chef', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-boonebgorges">
					<a class="web" href="https://profiles.wordpress.org/boonebgorges/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/9cf7c4541a582729a5fc7ae484786c0c?s=120">
					Boone B. Gorges</a>
					<span class="title"><?php esc_html_e( 'Chef', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-djpaul">
					<a class="web" href="https://profiles.wordpress.org/djpaul/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/3bc9ab796299d67ce83dceb9554f75df?s=120">
					Paul Gibbs</a>
					<span class="title"><?php esc_html_e( 'Chef', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-r-a-y">
					<a class="web" href="https://profiles.wordpress.org/r-a-y/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/3bfa556a62b5bfac1012b6ba5f42ebfa?s=120">
					Ray</a>
					<span class="title"><?php esc_html_e( 'Chef', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-imath">
					<a class="web" href="https://profiles.wordpress.org/imath/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/8b208ca408dad63888253ee1800d6a03?s=120">
					Mathieu Viet</a>
					<span class="title"><?php esc_html_e( 'Chef', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-mercime">
					<a class="web" href="https://profiles.wordpress.org/mercime/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/fae451be6708241627983570a1a1817a?s=120">
					Mercime</a>
					<span class="title"><?php esc_html_e( 'Chef', 'buddypress' ); ?></span>
				</li>
			</ul>

			<h3 class="wp-people-group"><?php esc_html_e( 'BuddyPress Team', 'buddypress' ); ?></h3>
			<ul class="wp-people-group " id="wp-people-group-core-team">
				<li class="wp-person" id="wp-person-hnla">
					<a class="web" href="https://profiles.wordpress.org/hnla/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/3860c955aa3f79f13b92826ae47d07fe?s=120">
					Hugo Ashmore</a>
					<span class="title"><?php esc_html_e( 'Pizzaïolo', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-dcavins">
					<a class="web" href="https://profiles.wordpress.org/dcavins/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/a5fa7e83d59cb45ebb616235a176595a?s=120">
					David Cavins</a>
					<span class="title"><?php esc_html_e( 'Pizzaïolo', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-tw2113">
					<a class="web" href="https://profiles.wordpress.org/tw2113/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/a5d7c934621fa1c025b83ee79bc62366?s=120">
					Michael Beckwith</a>
					<span class="title"><?php esc_html_e( 'Pizzaïolo', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-slaffik">
					<a class="web" href="https://profiles.wordpress.org/slaffik/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/61fb07ede3247b63f19015f200b3eb2c?s=120">
					Slava Abakumov</a>
					<span class="title"><?php esc_html_e( 'Pizzaïolo', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-offereins">
					<a class="web" href="https://profiles.wordpress.org/Offereins/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/2404ed0a35bb41aedefd42b0a7be61c1?s=120">
					Laurens Offereins</a>
					<span class="title"><?php esc_html_e( 'Pizzaïolo', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-netweb">
					<a class="web" href="https://profiles.wordpress.org/netweb/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/97e1620b501da675315ba7cfb740e80f?s=120">
					Stephen Edgar</a>
					<span class="title"><?php esc_html_e( 'Pizzaïolo', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-espellcaste">
					<a class="web" href="https://profiles.wordpress.org/espellcaste/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/b691e67be0ba5cad6373770656686bc3?s=120">
					Renato Alves</a>
					<span class="title"><?php esc_html_e( 'Pizzaïolo', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-vapvarun">
					<a class="web" href="https://profiles.wordpress.org/vapvarun/"><img alt="" class="gravatar" src="//gravatar.com/avatar/78a3bf7eb3a1132fc667f96f2631e448?s=120">
					Varun Dubey</a>
					<span class="title"><?php esc_html_e( 'Dough Commis', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-emaralive">
					<a class="web" href="https://profiles.wordpress.org/emaralive/"><img alt="" class="gravatar" src="//gravatar.com/avatar/310c3a56a7ea3c0816524a33cb8d7105?s=120">
					emaralive</a>
					<span class="title"><?php esc_html_e( 'Dough Commis', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-henry-wright">
					<a class="web" href="https://profiles.wordpress.org/henry.wright/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/0da2f1a9340d6af196b870f6c107a248?s=120">
					Henry Wright</a>
					<span class="title"><?php esc_html_e( 'Pizza deliverer', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-danbp">
					<a class="web" href="https://profiles.wordpress.org/danbp/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/0deae2e7003027fbf153500cd3fa5501?s=120">
					danbp</a>
					<span class="title"><?php esc_html_e( 'Pizza deliverer', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-shanebp">
					<a class="web" href="https://profiles.wordpress.org/shanebp/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/ffd294ab5833ba14aaf175f9acc71cc4?s=120">
					shanebp</a>
					<span class="title"><?php esc_html_e( 'Pizza deliverer', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-venutius">
					<a class="web" href="https://profiles.wordpress.org/venutius/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/6a7c42a77fd94b82b217a7a97afdddbc?s=120">
					Venutius</a>
					<span class="title"><?php esc_html_e( 'Pizza deliverer', 'buddypress' ); ?></span>
				</li>
			</ul>

			<h3 class="wp-people-group">
				<?php
				printf(
					/* translators: %s: BuddyPress version number */
					esc_html__( 'Noteworthy Contributors to %s', 'buddypress' ),
					esc_html( self::display_version() )
				);
				?>
			</h3>
			<ul class="wp-people-group " id="wp-people-group-noteworthy">
				<li class="wp-person" id="wp-person-shailu25">
					<a class="web" href="https://profiles.wordpress.org/shailu25/"><img alt="" class="gravatar" src="//gravatar.com/avatar/898d977196c2e4f5db4aab41edf1f5ad?s=120">
					Shail Mehta</a>
				</li>
				<li class="wp-person" id="wp-person-niftythree">
					<a class="web" href="https://profiles.wordpress.org/niftythree/"><img alt="" class="gravatar" src="//gravatar.com/avatar/c8d0f5560b6e8f5749d81fc3232d6345?s=120">
					Nifty</a>
				</li>
				<li class="wp-person" id="wp-person-needle">
					<a class="web" href="https://profiles.wordpress.org/needle/"><img alt="" class="gravatar" src="//gravatar.com/avatar/b4fa5015e88f2b2983e10b776ade83f5?s=120">
					Christian Wach</a>
				</li>
			</ul>

			<h3 class="wp-people-group">
				<?php
				printf(
					/* translators: %s: BuddyPress version number */
					esc_html__( 'All Contributors to BuddyPress %s', 'buddypress' ),
					esc_html( self::display_version() )
				);
				?>
			</h3>
			<p class="wp-credits-list">
				<a href="https://profiles.wordpress.org/ahegyes/">ahegyes</a>,
				<a href="https://profiles.wordpress.org/boonebgorges/">Boone Gorges (boonebgorges)</a>,
				<a href="https://profiles.wordpress.org/chairmanbrando/">chairmanbrando</a>,
				<a href="https://profiles.wordpress.org/dcavins/">David Cavins (dcavins)</a>,
				<a href="https://profiles.wordpress.org/dd32/">Dion Hulse (dd32)</a>,
				<a href="https://profiles.wordpress.org/djpaul/">Paul Wong-Gibbs (DJPaul)</a>,
				<a href="https://profiles.wordpress.org/dontdream/">Andrea Tarantini (dontdream)</a>,
				<a href="https://profiles.wordpress.org/emaralive/">emaralive</a>,
				<a href="https://profiles.wordpress.org/espellcaste/">Renato Alves (espellcaste)</a>,
				<a href="https://profiles.wordpress.org/gingerbooch/">gingerbooch</a>,
				<a href="https://profiles.wordpress.org/iandunn/">Ian Dunn (iandunn)</a>,
				<a href="https://profiles.wordpress.org/imath/">Mathieu Viet (imath)</a>,
				<a href="https://profiles.wordpress.org/itpathsolutions/">IT Path Solutions (itpathsolutions)</a>,
				<a href="https://profiles.wordpress.org/jnie/">jnie</a>,
				<a href="https://profiles.wordpress.org/johndawson155/">johndawson155</a>,
				<a href="https://profiles.wordpress.org/johnjamesjacoby/">John James Jacoby (johnjamesjacoby)</a>,
				<a href="https://profiles.wordpress.org/josevarghese/">Jose Varghese (josevarghese)</a>,
				<a href="https://profiles.wordpress.org/kainelabsteam/">KaineLabs Team (kainelabsteam)</a>,
				<a href="https://profiles.wordpress.org/lenasterg/">Lena Stergatou (lenasterg)</a>,
				<a href="https://profiles.wordpress.org/needle/">Christian Wach (needle)</a>,
				<a href="https://profiles.wordpress.org/nhrrob/">Nazmul Hasan Robin (nhrrob)</a>,
				<a href="https://profiles.wordpress.org/niftythree/">Nifty (niftythree)</a>,
				<a href="https://profiles.wordpress.org/nitinp544/">Nitin Patil (nitinp544)</a>,
				<a href="https://profiles.wordpress.org/pawelhalickiotgs/">pawelhalickiotgs</a>,
				<a href="https://profiles.wordpress.org/perchenet/">perchenet</a>,
				<a href="https://profiles.wordpress.org/poojasahgal/">Pooja Sahgal (poojasahgal)</a>,
				<a href="https://profiles.wordpress.org/r-a-y/">r-a-y</a>,
				<a href="https://profiles.wordpress.org/respawnsive/">respawnsive</a>,
				<a href="https://profiles.wordpress.org/roberthemsing/">Rosso Digital (roberthemsing)</a>,
				<a href="https://profiles.wordpress.org/sabernhardt/">Stephen Bernhardt (sabernhardt)</a>,
				<a href="https://profiles.wordpress.org/shailu25/">Shail Mehta (shailu25)</a>,
				<a href="https://profiles.wordpress.org/shawfactor/">shawfactor</a>,
				<a href="https://profiles.wordpress.org/sjregan/">sjregan</a>,
				<a href="https://profiles.wordpress.org/slaffik/">Slava Abakumov (Slaffik)</a>,
				<a href="https://profiles.wordpress.org/strategio/">Pierre Sylvestre (strategio)</a>,
				<a href="https://profiles.wordpress.org/testovac/">testovac</a>,
				<a href="https://profiles.wordpress.org/vapvarun/">Varun Dubey (vapvarun)</a>,
				<a href="https://profiles.wordpress.org/yagniksangani/">Yagnik Sangani (yagniksangani)</a>,
				<a href="https://profiles.wordpress.org/dancaragea/">Dan Caragea (dancaragea)</a>,
				<a href="https://profiles.wordpress.org/modelaid/">modelaid</a>,
				<a href="https://profiles.wordpress.org/nekojonez/">Pieterjan Deneys (nekojonez)</a>,
				<a href="https://profiles.wordpress.org/mehrazmorshed/">Mehraz Morshed (mehrazmorshed)</a>,
				<a href="https://profiles.wordpress.org/shenyanzhi/">沈唁 (shenyanzhi)</a>,
				<a href="https://profiles.wordpress.org/haozi/">耗子 (haozi)</a>,
				<a href="https://profiles.wordpress.org/cyrfer/">cyrfer</a>,
				<a href="https://profiles.wordpress.org/narolainfotech/">narolainfotech</a>,
				<a href="https://profiles.wordpress.org/benjamin_zekavica/">Benjamin Zekavica (benjamin_zekavica)</a>.
			</p>

			<h3 class="wp-people-group"><?php esc_html_e( 'With our thanks to these Open Source projects', 'buddypress' ); ?></h3>
			<p class="wp-credits-list">
				<a href="https://github.com/ichord/At.js">At.js</a>,
				<a href="https://bbpress.org">bbPress</a>,
				<a href="https://github.com/ichord/Caret.js">Caret.js</a>,
				<a href="https://tedgoas.github.io/Cerberus/">Cerberus</a>,
				<a href="https://ionicons.com/">Ionicons</a>,
				<a href="https://github.com/carhartl/jquery-cookie">jquery.cookie</a>,
				<a href="https://mattbradley.github.io/livestampjs/">Livestamp.js</a>,
				<a href="https://www.mediawiki.org/wiki/MediaWiki">MediaWiki</a>,
				<a href="https://wordpress.org">WordPress</a>.
			</p>

			<h3 class="wp-people-group"><?php esc_html_e( 'Contributor Emeriti', 'buddypress' ); ?></h3>
			<ul class="wp-people-group " id="wp-people-group-emeriti">
				<li class="wp-person" id="wp-person-apeatling">
					<a class="web" href="https://profiles.wordpress.org/apeatling/"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/bb29d699b5cba218c313b61aa82249da?s=120">
					Andy Peatling</a>
					<span class="title"><?php esc_html_e( 'Project Founder', 'buddypress' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-burtadsit">
					<a class="web" href="https://profiles.wordpress.org/burtadsit"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/185e1d3e2d653af9d49a4e8e4fc379df?s=120">
					Burt Adsit</a>
				</li>
				<li class="wp-person" id="wp-person-dimensionmedia">
					<a class="web" href="https://profiles.wordpress.org/dimensionmedia"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/7735aada1ec39d0c1118bd92ed4551f1?s=120">
					David Bisset</a>
				</li>
				<li class="wp-person" id="wp-person-jeffsayre">
					<a class="web" href="https://profiles.wordpress.org/jeffsayre"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/8e009a84ff5d245c22a69c7df6ab45f7?s=120">
					Jeff Sayre</a>
				</li>
				<li class="wp-person" id="wp-person-karmatosed">
					<a class="web" href="https://profiles.wordpress.org/karmatosed"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/ae3b73dbc5228474b0765c38d09176bb?s=120">
					Tammie Lister</a>
				</li>
				<li class="wp-person" id="wp-person-modemlooper">
					<a class="web" href="https://profiles.wordpress.org/modemlooper"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/1c07be1016e845de514931477c939307?s=120">
					modemlooper</a>
				</li>
			</ul>
		</div>

		<?php
	}

	/** Emails ****************************************************************/

	/**
	 * Registers 'Situations' column on Emails dashboard page.
	 *
	 * @since 2.6.0
	 *
	 * @param array $columns Current column data.
	 * @return array
	 */
	public function emails_register_situation_column( $columns = array() ) {
		$situation = array(
			'situation' => _x( 'Situations', 'Email post type', 'buddypress' )
		);

		// Inject our 'Situations' column just before the last 'Date' column.
		return array_slice( $columns, 0, -1, true ) + $situation + array_slice( $columns, -1, null, true );
	}

	/**
	 * Output column data for our custom 'Situations' column.
	 *
	 * @since 2.6.0
	 *
	 * @param string $column  Current column name.
	 * @param int    $post_id Current post ID.
	 */
	public function emails_display_situation_column_data( $column = '', $post_id = 0 ) {
		if ( 'situation' !== $column ) {
			return;
		}

		// Grab email situations for the current post.
		$terms           = get_the_terms( $post_id, bp_get_email_tax_type() );
		$taxonomy_object = get_taxonomy( bp_get_email_tax_type() );

		if ( is_wp_error( $terms ) || ! $terms ) {
			printf( '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">%s</span>', esc_html( $taxonomy_object->labels->no_terms ) );
		} else {
			$situations = wp_list_pluck( $terms, 'description' );

			// Output each situation as a list item.
			echo '<ul><li>';
			echo implode( '</li><li>', array_map( 'esc_html', $situations ) );
			echo '</li></ul>';
		}
	}

	/**
	 * Adds BP Custom Post Types Admin screen's help tab.
	 *
	 * @since 14.0.0
	 */
	public function post_type_load_admin_screen() {
		$screen = null;
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
		}

		if ( ! isset( $screen->post_type ) || bp_get_email_post_type() !== $screen->post_type ) {
			return;
		}

		bp_core_add_contextual_help( $screen );
	}

	/** Helpers ***************************************************************/

	/**
	 * Return true/false based on whether a query argument is set.
	 *
	 * @see bp_do_activation_redirect()
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	public static function is_new_install() {
		return (bool) isset( $_GET['is_new_install'] );
	}

	/**
	 * Return a user-friendly version-number string, for use in translations.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public static function display_version() {

		// Use static variable to prevent recalculations.
		static $display = '';

		// Only calculate on first run.
		if ( '' === $display ) {

			// Get current version.
			$version = bp_get_version();

			// Check for prerelease hyphen.
			$pre = strpos( $version, '-' );

			// Strip prerelease suffix.
			$display = ( false !== $pre )
				? substr( $version, 0, $pre )
				: $version;
		}

		// Done!
		return $display;
	}

	/**
	 * Add Emails menu item to custom menus array.
	 *
	 * Several BuddyPress components have top-level menu items in the Dashboard,
	 * which all appear together in the middle of the Dashboard menu. This function
	 * adds the Emails screen to the array of these menu items.
	 *
	 * @since 2.4.0
	 *
	 * @param array $custom_menus The list of top-level BP menu items.
	 * @return array $custom_menus List of top-level BP menu items, with Emails added.
	 */
	public function emails_admin_menu_order( $custom_menus = array() ) {
		array_push( $custom_menus, 'edit.php?post_type=' . bp_get_email_post_type() );

		if ( is_network_admin() && bp_is_network_activated() ) {
			array_push(
				$custom_menus,
				get_admin_url( bp_get_root_blog_id(), 'edit.php?post_type=' . bp_get_email_post_type() )
			);
		}

		return $custom_menus;
	}

	/**
	 * Register styles commonly used by BuddyPress wp-admin screens.
	 *
	 * @since 2.5.0
	 */
	public function admin_register_styles() {
		$min = bp_core_get_minified_asset_suffix();
		$url = $this->css_url;

		/**
		 * Filters the BuddyPress Core Admin CSS file path.
		 *
		 * @since 1.6.0
		 *
		 * @param string $file File path for the admin CSS.
		 */
		$common_css = apply_filters( 'bp_core_admin_common_css', "{$url}common{$min}.css" );

		/**
		 * Filters the BuddyPress admin stylesheet files to register.
		 *
		 * @since 2.5.0
		 *
		 * @param array $value Array of admin stylesheet file information to register.
		 */
		$styles = apply_filters( 'bp_core_admin_register_styles', array(
			// Legacy.
			'bp-admin-common-css' => array(
				'file'         => $common_css,
				'dependencies' => array(),
			),

			// 2.5
			'bp-customizer-controls' => array(
				'file'         => "{$url}customizer-controls{$min}.css",
				'dependencies' => array(),
			),

			// 3.0
			'bp-hello-css' => array(
				'file'         => "{$url}hello{$min}.css",
				'dependencies' => array( 'bp-admin-common-css', 'thickbox', 'bp-tooltips' ),
			),
		) );

		$version = bp_get_version();

		foreach ( $styles as $id => $style ) {
			wp_register_style( $id, $style['file'], $style['dependencies'], $version );
			wp_style_add_data( $id, 'rtl', 'replace' );

			if ( $min ) {
				wp_style_add_data( $id, 'suffix', $min );
			}
		}
	}

	/**
	 * Register JS commonly used by BuddyPress wp-admin screens.
	 *
	 * @since 2.5.0
	 */
	public function admin_register_scripts() {
		$min = bp_core_get_minified_asset_suffix();
		$url = $this->js_url;

		/**
		 * Filters the BuddyPress admin JS files to register.
		 *
		 * @since 2.5.0
		 *
		 * @param array $value Array of admin JS file information to register.
		 */
		$scripts = apply_filters( 'bp_core_admin_register_scripts', array(
			// 2.5
			'bp-customizer-controls' => array(
				'file'         => "{$url}customizer-controls{$min}.js",
				'dependencies' => array( 'jquery' ),
				'footer'       => true,
			),

			// 10.0
			'bp-thickbox' => array(
				'file'         => "{$url}bp-thickbox{$min}.js",
				'dependencies' => array( 'thickbox' ),
				'footer'       => true,
			),

			// 3.0
			'bp-hello-js' => array(
				'file'         => "{$url}hello{$min}.js",
				'dependencies' => array( 'bp-thickbox', 'wp-api-request', 'underscore', 'plugin-install' ),
				'footer'       => true,
			),

			// 10.0
			'bp-dismissible-admin-notices' => array(
				'file'         => "{$url}dismissible-admin-notices.js",
				'dependencies' => array(),
				'footer'       => true,
				'extra'        => array(
					'name' => 'bpDismissibleAdminNoticesSettings',
					'data' => array(
						'url'    => bp_core_ajax_url(),
						'nonce'  => wp_create_nonce( 'bp_dismiss_admin_notice' ),
					),
				),
			),

			// 12.0
			'bp-rewrites-ui' => array(
				'file' => "{$url}rewrites-ui.js",
				'dependencies' => array(),
				'footer'       => true,
			),
		) );

		$version = bp_get_version();

		foreach ( $scripts as $id => $script ) {
			wp_register_script( $id, $script['file'], $script['dependencies'], $version, $script['footer'] );

			if ( isset( $script['extra'] ) ) {
				// List the block specific props.
				wp_add_inline_script(
					$id,
					sprintf( 'var %1$s = %2$s;', $script['extra']['name'], wp_json_encode( $script['extra']['data'] ) ),
					'before'
				);
			}
		}
	}

	/**
	 * Adds inline styles to adapt the number of grid columns according to the number of BP Admin tabs.
	 *
	 * @since 10.0.0
	 */
	public function add_inline_styles() {
		$screen = get_current_screen();

		if ( ! isset( $screen->id ) ) {
			return;
		}

		// We might need to edit this id, see below code.
		$screen_id = $screen->id;

		// Multisite configs adds a '-network' suffix to page hooknames inside the Network Admin screens.
		if ( is_multisite() && is_network_admin() && bp_is_network_activated() ) {
			$screen_id = str_replace( '-network', '', $screen_id );
		}

		$current_settings_tab_id = array_search( $screen_id, $this->submenu_pages['settings'], true );
		$current_tools_tab_id    = array_search( $screen_id, $this->submenu_pages['tools'], true );
		$current_tab_id          = '';
		$tabs                    = array();
		$context                 = '';

		if ( $current_settings_tab_id ) {
			$current_tab_id = $current_settings_tab_id;
			$tabs           = wp_list_pluck( bp_core_get_admin_settings_tabs(), 'name', 'id' );
			$context        = 'settings';
		} elseif ( $current_tools_tab_id ) {
			$current_tab_id = $current_tools_tab_id;
			$tabs           = wp_list_pluck( bp_core_get_admin_tools_tabs(), 'name', 'id' );
			$context        = 'tools';
		}

		if ( $current_tab_id && isset( $tabs[ $current_tab_id ] ) ) {
			$this->nav_tabs = bp_core_admin_tabs( $tabs[ $current_tab_id ], $context, false );
			$grid_columns   = array_fill( 0, count( $this->nav_tabs ), '1fr');
			$help_tab_css   = '';

			if ( $screen->get_help_tabs() ) {
				$help_tab_css  = '#screen-meta { margin-right: 0; } #screen-meta-links { position: absolute; right: 0; }';
			}

			wp_add_inline_style(
				'bp-admin-common-css',
				sprintf(
					'.buddypress-tabs-wrapper {
						-ms-grid-columns: %1$s;
						grid-template-columns: %1$s;
					}
					%2$s',
					implode( ' ', $grid_columns ),
					$help_tab_css
				)
			);
		}
	}

	/**
	 * Add a "BuddyPress Add-ons" tab to the Add Plugins Admin screen.
	 *
	 * @since 10.0.0
	 *
	 * @param array $tabs The list of "Add Plugins" Tabs (Featured, Recommended, etc..).
	 * @return array      The same list including the "BuddyPress Add-ons" tab.
	 */
	public function addons_tab( $tabs = array() ) {
		$keys  = array_keys( $tabs );
		$index = array_search( 'favorites', $keys, true );

		// Makes sure the "BuddyPress Add-ons" tab is right after the "Favorites" one.
		$new_tabs = array_merge(
			array_slice( $tabs, 0, $index + 1, true ),
			array(
				'bp-add-ons' => __( 'BuddyPress Add-ons', 'buddypress' ),
			),
			$tabs
		);

		return $new_tabs;
	}

	/**
	 * Customize the Plugins API query arguments.
	 *
	 * The most important argument is the $user one which is set to "buddypress".
	 * Using this key and value will fetch the plugins the w.org "buddypress" user favorited.
	 *
	 * @since 10.0.0
	 *
	 * @global int $paged The current page of the Plugin results.
	 *
	 * @return array The "BuddyPress add-ons" args.
	 */
	public function addons_args() {
		global $paged;

		return array(
			'page'     => $paged,
			'per_page' => 10,
			'locale'   => get_user_locale(),
			'author'   => 'buddypress',
		);
	}

	/**
	 * Displays the list of "BuddyPress Add-ons".
	 *
	 * @since 10.0.0
	 */
	public function display_addons_table() {
		if ( isset( $_GET['show'] ) && 'bp-classic' === $_GET['show'] ) {
			wp_add_inline_script(
				'plugin-install',
				'
				( function () {
					document.onreadystatechange = function ()  {
						if ( document.readyState === "complete" ) {
							document.querySelector( \'.plugin-card-bp-classic .open-plugin-details-modal\' ).click();
						}
					}
				} )();
				'
			);
		}

		if ( isset( $_GET['n'] ) && $_GET['n'] ) {
			$notification_id = sanitize_text_field( wp_unslash( $_GET['n'] ) );
			bp_core_dismiss_admin_notification( $notification_id );
		}

		// Display the "buddypress" favorites.
		display_plugins_table();
	}

	/**
	 * Display the Admin Notifications screen.
	 *
	 * @since 11.4.0
	 */
	public function admin_notifications() {
		bp_core_admin_tabbed_screen_header( __( 'BuddyPress Settings', 'buddypress' ), __( 'Notifications', 'buddypress' ) );
		$notifications = bp_core_get_admin_notifications();
		$class         = '';

		if ( $notifications ) {
			wp_enqueue_script( 'bp-dismissible-admin-notices' );
			$notifications = array_reverse( bp_sort_by_key( $notifications, 'version', 'num' ) );
			$class         = 'hide';
		}
		?>
		<div class="buddypress-body admin-notifications">
			<table id="no-admin-notifications" class="form-table <?php echo sanitize_html_class( $class ); ?>" role="presentation">
				<tbody>
					<tr><td><?php esc_html_e( 'No new Admin Notfications', 'buddypress' ); ?></td><tr>
				</tbody>
			</table>

			<?php if ( $notifications ) : foreach ( $notifications as $notification ) : ?>
				<?php bp_core_admin_format_notifications( $notification ); ?>
			<?php endforeach; endif; ?>
		</div>
		<?php
	}
}
