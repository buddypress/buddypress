<?php

/**
 * Activity component CSS/JS
 *
 * @package BuddyPress
 * @subpackage ActivityScripts
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue @mentions JS.
 *
 * @since BuddyPress (2.1)
 */
function bp_activity_mentions_script() {
	if ( ! bp_activity_maybe_load_mentions_scripts() ) {
		return;
	}

	// Special handling for New/Edit screens in wp-admin
	if ( is_admin() ) {
		if (
			! get_current_screen() ||
			! in_array( get_current_screen()->base, array( 'page', 'post' ) ) ||
			! post_type_supports( get_current_screen()->post_type, 'editor' ) ) {
			return;
		}
	}


	$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	wp_enqueue_script( 'bp-mentions', buddypress()->plugin_url . "bp-activity/js/mentions{$min}.js", array( 'jquery', 'jquery-atwho' ), bp_get_version(), true );
	wp_enqueue_style( 'bp-mentions-css', buddypress()->plugin_url . "bp-activity/css/mentions{$min}.css", array(), bp_get_version() );

	wp_style_add_data( 'bp-mentions-css', 'rtl', true );
	if ( $min ) {
		wp_style_add_data( 'bp-mentions-css', 'suffix', $min );
	}

	/**
	 * Fires at the end of the Activity Mentions script.
	 *
	 * This is the hook where BP components can add their own prefetched results
	 * friends to the page for quicker @mentions lookups.
	 *
	 * @since BuddyPress (2.1.0)
	 */
	do_action( 'bp_activity_mentions_prime_results' );
}
add_action( 'bp_enqueue_scripts', 'bp_activity_mentions_script' );
add_action( 'bp_admin_enqueue_scripts', 'bp_activity_mentions_script' );
