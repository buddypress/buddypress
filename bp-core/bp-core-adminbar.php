<?php

/**
 * BuddyPress Core Toolbar
 *
 * Handles the core functions related to the WordPress Toolbar
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Adds the secondary BuddyPress area to the my-account menu
 *
 * @since BuddyPress 1.6
 * @global WP_Admin_Bar $wp_admin_bar
 * @return If doing ajax
 */
function bp_admin_bar_my_account_root() {
	global $wp_admin_bar;

	// Bail if this is an ajax request
	if ( !bp_use_wp_admin_bar() || defined( 'DOING_AJAX' ) )
		return;

	// Only add menu for logged in user
	if ( is_user_logged_in() ) {

		// Add secondary parent item for all BuddyPress components
		$wp_admin_bar->add_menu( array(
			'parent'    => 'my-account',
			'id'        => 'my-account-buddypress',
			'title'     => __( 'My Account' ),
			'group'     => true,
			'meta'      => array(
				'class' => 'ab-sub-secondary'
			)
		) );
	}
}
add_action( 'admin_bar_menu', 'bp_admin_bar_my_account_root', 100 );

/**
 * Handle the Toolbar CSS
 *
 * @since BuddyPress 1.5
 */
function bp_core_load_admin_bar_css() {

	if ( ! bp_use_wp_admin_bar() || ! is_admin_bar_showing() )
		return;

	// Toolbar styles
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
		$stylesheet = BP_PLUGIN_URL . 'bp-core/css/admin-bar.dev.css';
	else
		$stylesheet = BP_PLUGIN_URL . 'bp-core/css/admin-bar.css';

	wp_enqueue_style( 'bp-admin-bar', apply_filters( 'bp_core_admin_bar_css', $stylesheet ), array( 'admin-bar' ), bp_get_version() );

	if ( !is_rtl() )
		return;

	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
		$stylesheet = BP_PLUGIN_URL . 'bp-core/css/admin-bar-rtl.dev.css';
	else
		$stylesheet = BP_PLUGIN_URL . 'bp-core/css/admin-bar-rtl.css';

	wp_enqueue_style( 'bp-admin-bar-rtl', apply_filters( 'bp_core_admin_bar_rtl_css', $stylesheet ), array( 'bp-admin-bar' ), bp_get_version() );
}
add_action( 'bp_init', 'bp_core_load_admin_bar_css' );

?>