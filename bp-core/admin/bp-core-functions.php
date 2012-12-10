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
 * @since BuddyPress (1.6)
 */
function bp_core_admin_backpat_menu() {
	global $_parent_pages, $_registered_pages, $submenu;

	/**
	 * By default, only the core "Help" submenu is added under the top-level BuddyPress menu.
	 * This means that if no third-party plugins have registered their admin pages into the
	 * 'bp-general-settings' menu, it will only contain one item. Kill it.
	 */
	if ( 1 != count( $submenu['bp-general-settings'] ) )
		remove;

	// This removes the top-level menu
	remove_submenu_page( 'bp-general-settings', 'bp-general-settings' );
	remove_menu_page( 'bp-general-settings' );

	// These stop people accessing the URL directly
	unset( $_parent_pages['bp-general-settings'] );
	unset( $_registered_pages['toplevel_page_bp-general-settings'] );
}
add_action( bp_core_admin_hook(), 'bp_core_admin_backpat_menu', 999 );

/**
 * This tells WP to highlight the Settings > BuddyPress menu item,
 * regardless of which actual BuddyPress admin screen we are on.
 *
 * The conditional prevents the behaviour when the user is viewing the
 * backpat "Help" page, the Activity page, or any third-party plugins.
 *
 * @global string $plugin_page
 * @global array $submenu
 * @since BuddyPress (1.6)
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
 * @since BuddyPress (1.6)
 * @todo Add convenience links into the markup once new positions are finalised.
 */
function bp_core_admin_backpat_page() {
	$url          = bp_core_do_network_admin() ? network_admin_url( 'settings.php' ) : admin_url( 'options-general.php' );
	$settings_url = add_query_arg( 'page', 'bp-components', $url ); ?>

	<div class="wrap">
		<?php screen_icon( 'buddypress'); ?>
		<h2><?php _e( 'Why have all my BuddyPress menus disappeared?', 'buddypress' ); ?></h2>

		<p><?php _e( "Don't worry! We've moved the BuddyPress options into more convenient and easier to find locations. You're seeing this page because you are running a legacy BuddyPress plugin which has not been updated.", 'buddypress' ); ?></p>
		<p><?php printf( __( 'Components, Pages, Settings, and Forums, have been moved to <a href="%s">Settings &gt; BuddyPress</a>. Profile Fields has been moved into the <a href="%s">Users</a> menu.', 'buddypress' ), esc_url( $settings_url ), bp_get_admin_url( 'users.php?page=bp-profile-setup' ) ); ?></p>
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
 * @uses bp_current_user_can() to check current user permissions before showing the notices
 * @uses bp_is_root_blog()
 */
function bp_core_print_admin_notices() {
	$bp = buddypress();

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
 * @param string $notice The notice you are adding to the queue
 */
function bp_core_add_admin_notice( $notice ) {
	$bp = buddypress();

	if ( empty( $bp->admin->notices ) ) {
		$bp->admin->notices = array();
	}

	$bp->admin->notices[] = $notice;
}

/**
 * Redirect user to BuddyPress's What's New page on activation
 *
 * @since BuddyPress (1.7)
 *
 * @internal Used internally to redirect BuddyPress to the about page on activation
 *
 * @uses get_transient() To see if transient to redirect exists
 * @uses delete_transient() To delete the transient if it exists
 * @uses is_network_admin() To bail if being network activated
 * @uses wp_safe_redirect() To redirect
 * @uses add_query_arg() To help build the URL to redirect to
 * @uses admin_url() To get the admin URL to index.php
 *
 * @return If no transient, or is bulk activation
 */
function bp_do_activation_redirect() {

	// Bail if no activation redirect
    if ( ! get_transient( '_bp_activation_redirect' ) )
		return;

	// Delete the redirect transient
	delete_transient( '_bp_activation_redirect' );

	// Bail if activating from network, or bulk
	if ( isset( $_GET['activate-multi'] ) )
		return;

	// Redirect to BuddyPress about page
	wp_safe_redirect( add_query_arg( array( 'page' => 'bp-about' ), bp_get_admin_url( 'index.php' ) ) );
}

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
