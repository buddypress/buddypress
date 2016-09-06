<?php
/**
 * BuddyPress Activity Streams Loader.
 *
 * An activity stream component, for users, groups, and site tracking.
 *
 * @package BuddyPress
 * @subpackage ActivityCore
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! buddypress()->do_autoload ) {
	require dirname( __FILE__ ) . '/classes/class-bp-activity-component.php';
}

/**
 * Set up the bp-activity component.
 *
 * @since 1.6.0
 */
function bp_setup_activity() {
	buddypress()->activity = new BP_Activity_Component();
}
add_action( 'bp_setup_components', 'bp_setup_activity', 6 );
