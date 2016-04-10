<?php
/**
 * BuddyPress Members Admin
 *
 * @package BuddyPress
 * @subpackage MembersAdmin
 * @since 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! buddypress()->do_autoload ) {
	require dirname( __FILE__ ) . '/classes/class-bp-members-admin.php';
}

// Load the BP Members admin.
add_action( 'bp_init', array( 'BP_Members_Admin', 'register_members_admin' ) );
