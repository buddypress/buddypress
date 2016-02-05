<?php
/**
 * BuddyPress Member Loader.
 *
 * @package BuddyPress
 * @subpackage Members
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require dirname( __FILE__ ) . '/classes/class-bp-members-component.php';

/**
 * Set up the bp-members component.
 */
function bp_setup_members() {
	buddypress()->members = new BP_Members_Component();
}
add_action( 'bp_setup_components', 'bp_setup_members', 1 );
