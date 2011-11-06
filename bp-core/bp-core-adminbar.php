<?php

/**
 * BuddyPress Core Admin Bar
 *
 * Handles the core functions related to the WordPress Admin Bar
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !bp_use_wp_admin_bar() || defined( 'DOING_AJAX' ) )
	return;

/**
 * Adds the secondary BuddyPress area to the my-account menu
 *
 * @since BuddyPress 1.6
 * @global WP_Admin_Bar $wp_admin_bar
 * @return If doing ajax
 */
function bp_admin_bar_my_account_secondary() {
	global $wp_admin_bar;

	// Bail if this is an ajax request
	if ( defined( 'DOING_AJAX' ) )
		return;

	// Only add menu for logged in user
	if ( is_user_logged_in() ) {

		// Add secondary parent item for all BuddyPress components
		$wp_admin_bar->add_menu( array(
			'parent' => 'my-account',
			'id'     => 'my-account-buddypress',
			'title'  => '&nbsp;',
			'meta'   => array(
				'class' => 'secondary',
			)
		) );
	}
}
add_action( 'admin_bar_menu', 'bp_admin_bar_my_account_secondary', 9999 );

/**
 * Handle the Admin Bar CSS
 *
 * @since BuddyPress 1.5
 */
function bp_core_load_admin_bar_css() {

	$version = '2011116';

	if ( !bp_use_wp_admin_bar() )
		return;

	// Admin bar styles
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
		$stylesheet = BP_PLUGIN_URL . '/bp-core/css/admin-bar.dev.css';
	else
		$stylesheet = BP_PLUGIN_URL . '/bp-core/css/admin-bar.css';

	wp_enqueue_style( 'bp-admin-bar', apply_filters( 'bp_core_admin_bar_css', $stylesheet ), array( 'admin-bar' ), $version );

	if ( !is_rtl() )
		return;

	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
		$stylesheet = BP_PLUGIN_URL . '/bp-core/css/admin-bar-rtl.dev.css';
	else
		$stylesheet = BP_PLUGIN_URL . '/bp-core/css/admin-bar-rtl.css';

	wp_enqueue_style( 'bp-admin-bar-rtl', apply_filters( 'bp_core_admin_bar_rtl_css', $stylesheet ), array( 'bp-admin-bar' ), $version );
}
add_action( 'bp_init', 'bp_core_load_admin_bar_css' );

?>