<?php
/**
 * BuddyPress Messages Loader.
 *
 * A private messages component, for users to send messages to each other.
 *
 * @package BuddyPress
 * @subpackage MessagesLoader
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-messages component.
 *
 * @since 1.5.0
 */
function bp_setup_messages() {
	buddypress()->messages = new BP_Messages_Component();
}
add_action( 'bp_setup_components', 'bp_setup_messages', 6 );
