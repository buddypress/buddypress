<?php
/**
 * Main BuddyPress Admin Class.
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup BuddyPress Admin.
 *
 * @since 1.6.0
 */
function bp_admin() {
	buddypress()->admin = new BP_Admin();
}
