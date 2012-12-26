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
	if ( 1 != count( $submenu['bp-general-settings'] ) ) {
		return;
	}

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
		<?php screen_icon( 'buddypress' ); ?>
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
		<?php foreach ( $bp->admin->notices as $notice ) : ?>
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
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), 'admin.php' ) ),
			'name' => __( 'Components', 'buddypress' )
		),
		'1' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings' ), 'admin.php' ) ),
			'name' => __( 'Pages', 'buddypress' )
		),
		'2' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-settings' ), 'admin.php' ) ),
			'name' => __( 'Settings', 'buddypress' )
		)
	);

	// Loop through tabs and build navigation
	foreach ( array_values( $tabs ) as $tab_data ) {
		$is_current = (bool) ( $tab_data['name'] == $active_tab );
		$tab_class  = $is_current ? $active_class : $idle_class;
		$tabs_html .= '<a href="' . $tab_data['href'] . '" class="' . $tab_class . '">' . $tab_data['name'] . '</a>';
	}

	// Output the tabs
	echo $tabs_html;

	// Do other fun things
	do_action( 'bp_admin_tabs' );
}

/** Help **********************************************************************/

/**
 * adds contextual help to BuddyPress admin pages
 *
 * @since BuddyPress (1.7)
 * @todo Make this part of the BP_Component class and split into each component
 */
function bp_core_add_contextual_help( $screen = '' ) {

	$screen = get_current_screen();

	switch ( $screen->id ) {

		// Compontent page
		case 'settings_page_bp-components' :

			// help tabs
			$screen->add_help_tab( array(
				'id'      => 'bp-comp-overview',
				'title'   => __( 'Overview' ),
				'content' => bp_core_add_contextual_help_content( 'bp-comp-overview' ),
			) );

			// help panel - sidebar links
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddypress' ) . '</strong></p>' .
				'<p>' . __( '<a href="http://codex.buddypress.org/getting-started/configure-buddypress-components/#settings-buddypress-components">Managing Components</a>', 'buddypress' ) . '</p>' .
				'<p>' . __( '<a href="http://buddypress.org/support/">Support Forums</a>', 'buddypress' ) . '</p>'
			);
			break;

		// Pages page
		case 'settings_page_bp-page-settings' :

			// Help tabs
			$screen->add_help_tab( array(
				'id' => 'bp-page-overview',
				'title' => __( 'Overview' ),
				'content' => bp_core_add_contextual_help_content( 'bp-page-overview' ),
			) );

			// Help panel - sidebar links
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddypress' ) . '</strong></p>' .
				'<p>' . __( '<a href="http://codex.buddypress.org/getting-started/configure-buddypress-components/#settings-buddypress-pages">Managing Pages</a>', 'buddypress' ) . '</p>' .
				'<p>' . __( '<a href="http://buddypress.org/support/">Support Forums</a>', 'buddypress' ) . '</p>'
			);

			break;

		// Settings page
		case 'settings_page_bp-settings' :

			// Help tabs
			$screen->add_help_tab( array(
				'id'      => 'bp-settings-overview',
				'title'   => __( 'Overview' ),
				'content' => bp_core_add_contextual_help_content( 'bp-settings-overview' ),
			) );

			// Help panel - sidebar links
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddypress' ) . '</strong></p>' .
				'<p>' . __( '<a href="http://codex.buddypress.org/getting-started/configure-buddypress-components/#settings-buddypress-settings">Managing Settings</a>', 'buddypress' ) . '</p>' .
				'<p>' . __( '<a href="http://buddypress.org/support/">Support Forums</a>', 'buddypress' ) . '</p>'
			);

			break;

		// Profile fields page
		case 'users_page_bp-profile-overview' :

			// Help tabs
			$screen->add_help_tab( array(
				'id'      => 'bp-profile-overview',
				'title'   => __( 'Overview' ),
				'content' => bp_core_add_contextual_help_content( 'bp-profile-overview' ),
			) );

			// Help panel - sidebar links
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddypress' ) . '</strong></p>' .
				'<p>' . __( '<a href="http://codex.buddypress.org/getting-started/configure-buddypress-components/#users-profile-fields">Managing Profile Fields</a>', 'buddypress' ) . '</p>' .
				'<p>' . __( '<a href="http://buddypress.org/support/">Support Forums</a>', 'buddypress' ) . '</p>'
			);

			break;
	}
}
add_action( 'contextual_help', 'bp_core_add_contextual_help' );

/**
 * renders contextual help content to contextual help tabs
 *
 * @since BuddyPress (1.7)
 */
function bp_core_add_contextual_help_content( $tab = '' ) {

	switch ( $tab ) {
		case 'bp-comp-overview' :
			return '<p>' . __( 'By default, all BuddyPress components are enabled. You can selectively disable any of the components by using the form. Your BuddyPress installation will continue to function. However, the features of the disabled components will no longer be accessible to anyone using the site.', 'buddypress' ) . '</p>';
			break;

		case'bp-page-overview' :
			return '<p>' . __( 'BuddyPress Components use WordPress Pages for their root directory/archive pages. Here you can change the page associations for each active component.', 'buddypress' ) . '</p>';
			break;

		case 'bp-settings-overview' :
			return '<p>' . __( 'Extra configuration settings.', 'buddypress' ) . '</p>';
			break;

		case 'bp-profile-overview' :
			return '<p>' . __( 'Your users will distinguish themselves through their profile page. Create relevant profile fields that will show on each users profile.</br></br>Note: Any fields in the first group will appear on the signup page.', 'buddypress' ) . '</p>';
			break;

		default:
			return false;
			break;
	}
}

/** Separator *****************************************************************/

/**
 * Add a separator to the WordPress admin menus
 *
 * @since BuddyPress (1.7)
 *
 * @uses bp_current_user_can() To check users capability on root blog
 */
function bp_admin_separator() {

	// Bail if BuddyPress is not network activated
	if ( is_network_admin() && ! bp_is_network_activated() )
		return;

	// Prevent duplicate separators when no core menu items exist
	if ( ! bp_current_user_can( 'bp_moderate' ) )
		return;

	global $menu;

	$menu[] = array( '', 'read', 'separator-buddypress', '', 'wp-menu-separator buddypress' );
}

/**
 * Tell WordPress we have a custom menu order
 *
 * @since BuddyPress (1.7)
 *
 * @param bool $menu_order Menu order
 * @uses bp_current_user_can() To check users capability on root blog
 * @return bool Always true
 */
function bp_admin_custom_menu_order( $menu_order = false ) {

	// Bail if user cannot see admin pages
	if ( ! bp_current_user_can( 'bp_moderate' ) )
		return $menu_order;

	return $menu_order;
}

/**
 * Move our custom separator above our custom post types
 *
 * @since BuddyPress (1.7)
 *
 * @param array $menu_order Menu Order
 * @uses bp_current_user_can() To check users capability on root blog
 * @return array Modified menu order
 */
function bp_admin_menu_order( $menu_order = array() ) {

	// Bail if user cannot see admin pages
	if ( empty( $menu_order ) || ! bp_current_user_can( 'bp_moderate' ) )
		return $menu_order;

	// Initialize our custom order array
	$bp_menu_order = array();

	// Menu values
	$last_sep     = is_network_admin() ? 'separator1' : 'separator2';

	// Filter the custom admin menus
	$custom_menus = (array) apply_filters( 'bp_admin_menu_order', array() );

	// Add our separator to beginning of array
	array_unshift( $custom_menus, 'separator-buddypress' );

	// Loop through menu order and do some rearranging
	foreach ( (array) $menu_order as $item ) {

		// Position BuddyPress menus above appearance
		if ( $last_sep == $item ) {

			// Add our custom menus
			foreach( (array) $custom_menus as $custom_menu ) {
				if ( array_search( $custom_menu, $menu_order ) ) {
					$bp_menu_order[] = $custom_menu;
				}
			}

			// Add the appearance separator
			$bp_menu_order[] = $last_sep;

		// Skip our menu items
		} elseif ( ! in_array( $item, $custom_menus ) ) {
			$bp_menu_order[] = $item;
		}
	}

	// Return our custom order
	return $bp_menu_order;
}