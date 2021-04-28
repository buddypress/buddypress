<?php
/**
 * BuddyPress Members List Classes.
 *
 * @package BuddyPress
 * @subpackage MembersAdminClasses
 * @since 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WP_Users_List_Table' ) ) {
	require dirname( dirname( __FILE__ ) ) . '/classes/class-bp-members-list-table.php';
	require dirname( dirname( __FILE__ ) ) . '/classes/class-bp-members-invitations-list-table.php';
}

if ( class_exists( 'WP_MS_Users_List_Table' ) ) {
	require dirname( dirname( __FILE__ ) ) . '/classes/class-bp-members-ms-list-table.php';
}
