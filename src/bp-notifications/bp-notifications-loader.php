<?php
/**
 * BuddyPress Member Notifications Loader.
 *
 * Initializes the Notifications component.
 *
 * @package BuddyPress
 * @subpackage NotificationsLoader
 * @since 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require dirname( __FILE__ ) . '/classes/class-bp-notifications-component.php';

/**
 * Bootstrap the Notifications component.
 *
 * @since 1.9.0
 */
function bp_setup_notifications() {
	buddypress()->notifications = new BP_Notifications_Component();
}
add_action( 'bp_setup_components', 'bp_setup_notifications', 6 );
