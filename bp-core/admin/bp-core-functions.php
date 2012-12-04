<?php

/**
 * BuddyPress Common Admin Functions
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Menu **********************************************************************/

/**
 * Initializes the wp-admin area "BuddyPress" menus and sub menus.
 *
 * @package BuddyPress Core
 * @uses bp_current_user_can() returns true if the current user is a site admin, false if not
 */
function bp_core_admin_menu_init() {
	add_action( bp_core_admin_hook(), 'bp_core_add_admin_menu', 9 );
}

/**
 * In BP 1.6, the top-level admin menu was removed. For backpat, this function
 * keeps the top-level menu if a plugin has registered a menu into the old
 * 'bp-general-settings' menu.
 *
 * The old "bp-general-settings" page was renamed "bp-components".
 *
 * @global array $_parent_pages
 * @global array $_registered_pages
 * @global array $submenu
 * @since 1.6
 */
function bp_core_admin_backpat_menu() {
	global $_parent_pages, $_registered_pages, $submenu;

	if ( ! is_super_admin() )
		return;

	// Don't do anything if a BP upgrade is in progress, or if the bp-wizard is in progress.
	if ( defined( 'BP_IS_UPGRADE' ) && BP_IS_UPGRADE || empty( $submenu['bp-general-settings'] ) )
		return;

	/**
	 * By default, only the core "Help" submenu is added under the top-level BuddyPress menu.
	 * This means that if no third-party plugins have registered their admin pages into the
	 * 'bp-general-settings' menu, it will only contain one item. Kill it.
	 */
	if ( 1 == count( $submenu['bp-general-settings'] ) ) {

		// This removes the top-level menu
		remove_submenu_page( 'bp-general-settings', 'bp-general-settings' );
		remove_menu_page( 'bp-general-settings' );

		// These stop people accessing the URL directly
		unset( $_parent_pages['bp-general-settings'] );
		unset( $_registered_pages['toplevel_page_bp-general-settings'] );
	}
}
add_action( bp_core_admin_hook(), 'bp_core_admin_backpat_menu', 999 );

/**
 * Tweak the Settings subnav menu to show only one BuddyPress menu item (Settings > BuddyPress).
 *
 * @since 1.6
 */
function bp_core_modify_admin_menu() {
 	$page  = bp_core_do_network_admin()  ? 'settings.php' : 'options-general.php';

	remove_submenu_page( $page, 'bb-forums-setup' );
	remove_submenu_page( $page, 'bp-page-settings' );
	remove_submenu_page( $page, 'bp-settings'     );
}
add_action( 'admin_head', 'bp_core_modify_admin_menu', 999 );

/**
 * This tells WP to highlight the Settings > BuddyPress menu item,
 * regardless of which actual BuddyPress admin screen we are on.
 *
 * The conditional prevents the behaviour when the user is viewing the
 * backpat "Help" page, the Activity page, or any third-party plugins.
 *
 * @global string $plugin_page
 * @global array $submenu
 * @since 1.6
 */
function bp_core_modify_admin_menu_highlight() {
	global $plugin_page, $submenu_file;

	// This tweaks the Settings subnav menu to show only one BuddyPress menu item
	if ( ! in_array( $plugin_page, array( 'bp-activity', 'bp-general-settings', ) ) )
		$submenu_file = 'bp-components';
}

/**
 * Generates markup for a fallback top-level BuddyPress menu page, if the site is running
 * a legacy plugin which hasn't been updated. If the site is up to date, this page
 * will never appear.
 *
 * @see bp_core_admin_backpat_menu()
 * @since 1.6
 * @todo Add convenience links into the markup once new positions are finalised.
 */
function bp_core_admin_backpat_page() {
	$url          = bp_core_do_network_admin() ? network_admin_url( 'settings.php' ) : admin_url( 'options-general.php' );
	$settings_url = add_query_arg( 'page', 'bp-components', $url ); ?>

	<div class="wrap">
		<?php screen_icon( 'buddypress'); ?>
		<h2><?php _e( 'Why have all my BuddyPress menus disappeared?', 'buddypress' ); ?></h2>

		<p><?php _e( "Don't worry! We've moved the BuddyPress options into more convenient and easier to find locations. You're seeing this page because you are running a legacy BuddyPress plugin which has not been updated.", 'buddypress' ); ?></p>
		<p><?php printf( __( 'Components, Pages, Settings, and Forums, have been moved to <a href="%s">Settings &gt; BuddyPress</a>. Profile Fields has been moved into the <a href="%s">Users</a> menu.', 'buddypress' ), $settings_url, network_admin_url( 'users.php?page=bp-profile-setup' ) ); ?></p>
	</div>

<?php
}

/** Notices *******************************************************************/

/**
 * Print admin messages to admin_notices or network_admin_notices
 *
 * BuddyPress combines all its messages into a single notice, to avoid a preponderance of yellow
 * boxes.
 *
 * @package BuddyPress Core
 * @since BuddyPress (1.5)
 *
 * @global BuddyPress $bp The one true BuddyPress instance
 * @uses bp_current_user_can() to check current user permissions before showing the notices
 * @uses bp_is_root_blog()
 */
function bp_core_print_admin_notices() {
	global $bp;

	// Only the super admin should see messages
	if ( !bp_current_user_can( 'bp_moderate' ) )
		return;

	// On multisite installs, don't show on the Site Admin of a non-root blog, unless
	// do_network_admin is overridden
	if ( is_multisite() && bp_core_do_network_admin() && !bp_is_root_blog() )
		return;

	// Show the messages
	if ( !empty( $bp->admin->notices ) ) {
	?>
		<div id="message" class="updated fade">
			<?php foreach( $bp->admin->notices as $notice ) : ?>
				<p><?php echo $notice ?></p>
			<?php endforeach ?>
		</div>
	<?php
	}
}
add_action( 'admin_notices',         'bp_core_print_admin_notices' );
add_action( 'network_admin_notices', 'bp_core_print_admin_notices' );

/**
 * Add an admin notice to the BP queue
 *
 * Messages added with this function are displayed in BuddyPress's general purpose admin notices
 * box. It is recommended that you hook this function to admin_init, so that your messages are
 * loaded in time.
 *
 * @package BuddyPress Core
 * @since BuddyPress (1.5)
 *
 * @global BuddyPress $bp The one true BuddyPress instance
 * @param string $notice The notice you are adding to the queue
 */
function bp_core_add_admin_notice( $notice ) {
	global $bp;

	if ( empty( $bp->admin->notices ) ) {
		$bp->admin->notices = array();
	}

	$bp->admin->notices[] = $notice;
}

/**
 * Verify that some BP prerequisites are set up properly, and notify the admin if not
 *
 * On every Dashboard page, this function checks the following:
 *   - that pretty permalinks are enabled
 *   - that a BP-compatible theme is activated
 *   - that every BP component that needs a WP page for a directory has one
 *   - that no WP page has multiple BP components associated with it
 * The administrator will be shown a notice for each check that fails.
 *
 * @package BuddyPress Core
 */
function bp_core_activation_notice() {
	global $wp_rewrite, $wpdb, $bp;

	// Only the super admin gets warnings
	if ( !bp_current_user_can( 'bp_moderate' ) )
		return;

	// On multisite installs, don't load on a non-root blog, unless do_network_admin is
	// overridden
	if ( is_multisite() && bp_core_do_network_admin() && !bp_is_root_blog() )
		return;

	// Don't show these messages during setup or upgrade
	if ( !empty( $bp->maintenance_mode ) )
		return;

	/**
	 * Check to make sure that the blog setup routine has run. This can't happen during the
	 * wizard because of the order which the components are loaded. We check for multisite here
	 * on the off chance that someone has activated the blogs component and then disabled MS
	 */
	if ( bp_is_active( 'blogs' ) ) {
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->blogs->table_name}" );

		if ( empty( $count ) ) {
			bp_blogs_record_existing_blogs();
		}
	}

	/**
	 * Are pretty permalinks enabled?
	 */
	if ( isset( $_POST['permalink_structure'] ) )
		return false;

	if ( empty( $wp_rewrite->permalink_structure ) ) {
		bp_core_add_admin_notice( sprintf( __( '<strong>BuddyPress is almost ready</strong>. You must <a href="%s">update your permalink structure</a> to something other than the default for it to work.', 'buddypress' ), admin_url( 'options-permalink.php' ) ) );
	}

	/**
	 * Are you using a BP-compatible theme?
	 */

	// Get current theme info
	$ct = wp_get_theme();

	// Make sure tags is an array to suppress notices
	if ( ! isset( $ct->tags ) )
		$ct->tags = array();
	else
		$ct->tags = (array) $ct->tags;

	// The best way to remove this notice is to add a "buddypress" tag to
	// your active theme's CSS header.
	if ( !defined( 'BP_SILENCE_THEME_NOTICE' ) && !in_array( 'buddypress', $ct->tags ) ) {
		bp_core_add_admin_notice( sprintf( __( "You'll need to <a href='%s'>activate a <strong>BuddyPress-compatible theme</strong></a> to take advantage of all of BuddyPress's features. We've bundled a default theme, but you can always <a href='%s'>install some other compatible themes</a> or <a href='%s'>update your existing WordPress theme</a>.", 'buddypress' ), admin_url( 'themes.php' ), network_admin_url( 'theme-install.php?type=tag&s=buddypress&tab=search' ), network_admin_url( 'plugin-install.php?type=term&tab=search&s=%22bp-template-pack%22' ) ) );
	}

	/**
	 * Check for orphaned BP components (BP component is enabled, no WP page exists)
	 */

	$orphaned_components = array();
	$wp_page_components  = array();

	// Only components with 'has_directory' require a WP page to function
	foreach( $bp->loaded_components as $component_id => $is_active ) {
		if ( !empty( $bp->{$component_id}->has_directory ) ) {
			$wp_page_components[] = array(
				'id'   => $component_id,
				'name' => isset( $bp->{$component_id}->name ) ? $bp->{$component_id}->name : ucwords( $bp->{$component_id}->id )
			);
		}
	}

	// Activate and Register are special cases. They are not components but they need WP pages.
	// If user registration is disabled, we can skip this step.
	if ( bp_get_signup_allowed() ) {
		$wp_page_components[] = array(
			'id'   => 'activate',
			'name' => __( 'Activate', 'buddypress' )
		);

		$wp_page_components[] = array(
			'id'   => 'register',
			'name' => __( 'Register', 'buddypress' )
		);
	}

	foreach( $wp_page_components as $component ) {
		if ( !isset( $bp->pages->{$component['id']} ) ) {
			$orphaned_components[] = $component['name'];
		}
	}

	// Special case: If the Forums component is orphaned, but the bbPress 1.x installation is
	// not correctly set up, don't show a nag. (In these cases, it's probably the case that the
	// user is using bbPress 2.x; see https://buddypress.trac.wordpress.org/ticket/4292
	if ( isset( $bp->forums->name ) && in_array( $bp->forums->name, $orphaned_components ) && !bp_forums_is_installed_correctly() ) {
		$forum_key = array_search( $bp->forums->name, $orphaned_components );
		unset( $orphaned_components[$forum_key] );
		$orphaned_components = array_values( $orphaned_components );
	}

	if ( !empty( $orphaned_components ) ) {
		$admin_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings' ), 'admin.php' ) );
		$notice    = sprintf( __( 'The following active BuddyPress Components do not have associated WordPress Pages: %2$s. <a href="%1$s" class="button-secondary">Repair</a>', 'buddypress' ), $admin_url, '<strong>' . implode( '</strong>, <strong>', $orphaned_components ) . '</strong>' );

		bp_core_add_admin_notice( $notice );
	}

	/**
	 * BP components cannot share a single WP page. Check for duplicate assignments, and post
	 * a message if found.
	 */
	$dupe_names = array();
	$page_ids   = (array)bp_core_get_directory_page_ids();
	$dupes      = array_diff_assoc( $page_ids, array_unique( $page_ids ) );

	if ( !empty( $dupes ) ) {
		foreach( $dupes as $dupe_component => $dupe_id ) {
			$dupe_names[] = $bp->pages->{$dupe_component}->title;
		}

		// Make sure that there are no duplicate duplicates :)
		$dupe_names = array_unique( $dupe_names );
	}

	// If there are duplicates, post a message about them
	if ( !empty( $dupe_names ) ) {
		$admin_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings' ), 'admin.php' ) );
		$notice    = sprintf( __( 'Each BuddyPress Component needs its own WordPress page. The following WordPress Pages have more than one component associated with them: %2$s. <a href="%1$s" class="button-secondary">Repair</a>', 'buddypress' ), $admin_url, '<strong>' . implode( '</strong>, <strong>', $dupe_names ) . '</strong>' );

		bp_core_add_admin_notice( $notice );
	}
}
add_action( 'admin_init', 'bp_core_activation_notice' );

/**
 * Add an extra update message to the update plugin notification.
 *
 * @package BuddyPress Core
 */
function bp_core_update_message() {
	echo '<p style="color: red; margin: 3px 0 0 0; border-top: 1px solid #ddd; padding-top: 3px">' . __( 'IMPORTANT: <a href="http://codex.buddypress.org/buddypress-site-administration/upgrading-buddypress/">Read this before attempting to update BuddyPress</a>', 'buddypress' ) . '</p>';
}
add_action( 'in_plugin_update_message-buddypress/bp-loader.php', 'bp_core_update_message' );

/** UI/Styling ****************************************************************/

/**
 * Output the tabs in the admin area
 *
 * @since BuddyPress (1.5)
 * @param string $active_tab Name of the tab that is active
 */
function bp_core_admin_tabs( $active_tab = '' ) {

	// Declare local variables
	$tabs_html    = '';
	$idle_class   = 'nav-tab';
	$active_class = 'nav-tab nav-tab-active';

	// Setup core admin tabs
	$tabs = array(
		'0' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components'    ), 'admin.php' ) ),
			'name' => __( 'Components', 'buddypress' )
		),
		'1' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings' ), 'admin.php' ) ),
			'name' => __( 'Pages', 'buddypress' )
		),
		'2' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-settings'      ), 'admin.php' ) ),
			'name' => __( 'Settings',   'buddypress' )
		)
	);

	// If forums component is active, add additional tab
	if ( bp_is_active( 'forums' ) ) {
		$tabs['3'] = array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bb-forums-setup'  ), 'admin.php' ) ),
			'name' => __( 'Forums', 'buddypress' )
		);
	}

	// Loop through tabs and build navigation
	foreach( $tabs as $tab_id => $tab_data ) {
		$is_current = (bool) ( $tab_data['name'] == $active_tab );
		$tab_class  = $is_current ? $active_class : $idle_class;
		$tabs_html .= '<a href="' . $tab_data['href'] . '" class="' . $tab_class . '">' . $tab_data['name'] . '</a>';
	}

	// Output the tabs
	echo $tabs_html;

	// Do other fun things
	do_action( 'bp_admin_tabs' );
}

?>
