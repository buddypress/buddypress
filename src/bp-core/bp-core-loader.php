<?php
/**
 * BuddyPress Core Loader.
 *
 * Core contains the commonly used functions, classes, and APIs.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-core component.
 *
 * @since 1.6.0
 */
function bp_setup_core() {
	buddypress()->core = new BP_Core();
}
add_action( 'bp_loaded', 'bp_setup_core', 0 );
