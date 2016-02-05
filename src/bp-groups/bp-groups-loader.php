<?php
/**
 * BuddyPress Groups Loader.
 *
 * A groups component, for users to group themselves together. Includes a
 * robust sub-component API that allows Groups to be extended.
 * Comes preconfigured with an activity stream, discussion forums, and settings.
 *
 * @package BuddyPress
 * @subpackage GroupsLoader
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require dirname( __FILE__ ) . '/classes/class-bp-groups-component.php';

/**
 * Bootstrap the Notifications component.
 *
 * @since 1.5.0
 */
function bp_setup_groups() {
	buddypress()->groups = new BP_Groups_Component();
}
add_action( 'bp_setup_components', 'bp_setup_groups', 6 );
