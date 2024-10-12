<?php
/**
 * BuddyPress Members component admin screens.
 *
 * @package BuddyPress
 * @subpackage MessagesAdmin
 * @since 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Load the Sitewide Notices Admin.
add_action( bp_core_admin_hook(), array( 'BP_Members_Notices_Admin', 'register_notices_admin' ), 9 );
