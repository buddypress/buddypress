<?php
/**
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * REQUIRES WP-SUPER-CACHE
 *
 * When wp-super-cache is installed this function will clear cached pages
 * so that success/error messages are not cached, or time sensitive content.
 *
 * @package BuddyPress Core
 */
function bp_core_clear_cache() {
	global $cache_path, $cache_filename;

	if ( function_exists( 'prune_super_cache' ) ) {
		do_action( 'bp_core_clear_cache' );
		return prune_super_cache( $cache_path, true );
	}
}

/**
 * Add's 'bp' to global group of network wide cachable objects
 *
 * @package BuddyPress Core
 */
function bp_core_add_global_group() {
	wp_cache_init();
	wp_cache_add_global_groups( array( 'bp' ) );
}
add_action( 'bp_loaded', 'bp_core_add_global_group' );

/**
 * Clears all cached objects for a user, or a user is part of.
 *
 * @package BuddyPress Core
 */
function bp_core_clear_user_object_cache( $user_id ) {
	wp_cache_delete( 'bp_user_' . $user_id, 'bp' );
}

// List actions to clear super cached pages on, if super cache is installed
add_action( 'wp_login',              'bp_core_clear_cache' );
add_action( 'bp_core_render_notice', 'bp_core_clear_cache' );

?>