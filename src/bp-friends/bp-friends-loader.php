<?php
/**
 * BuddyPress Friends Streams Loader.
 *
 * The friends component is for users to create relationships with each other.
 *
 * @package BuddyPress
 * @subpackage Friends
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! buddypress()->do_autoload ) {
	require dirname( __FILE__ ) . '/classes/class-bp-friends-component.php';
}

/**
 * Set up the bp-friends component.
 *
 * @since 1.6.0
 */
function bp_setup_friends() {
	buddypress()->friends = new BP_Friends_Component();
}
add_action( 'bp_setup_components', 'bp_setup_friends', 6 );
