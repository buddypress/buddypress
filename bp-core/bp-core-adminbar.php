<?php

/**
 * BuddyPress Core Admin Bar
 *
 * Handles the core functions related to the WordPress Admin Bar
 *
 * @package BuddyPress
 * @subpackage Core
 */

/**
 * Unhook the WordPress core menus. We will be adding our own to replace these.
 *
 * @todo Single blog/post/group/user/forum/activity menus
 * @todo Admin/moderator menus
 *
 * @since BuddyPress (r4151)
 *
 * @uses remove_action
 * @uses is_network_admin()
 * @uses is_user_admin()
 */
function bp_admin_bar_remove_wp_menus() {
	remove_action( 'admin_bar_menu', 'wp_admin_bar_my_account_menu', 10 );
	remove_action( 'admin_bar_menu', 'wp_admin_bar_my_sites_menu',   20 );
	remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu',       30 );
	remove_action( 'admin_bar_menu', 'wp_admin_bar_shortlink_menu',  80 );

	if ( !is_network_admin() && !is_user_admin() ) {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_new_content_menu', 40 );
		remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu',    50 );
		remove_action( 'admin_bar_menu', 'wp_admin_bar_appearance_menu',  60 );
	}

	remove_action( 'admin_bar_menu', 'wp_admin_bar_updates_menu',    70 );
}
if ( defined( 'BP_USE_WP_ADMIN_BAR' ) )
	add_action( 'bp_init', 'bp_admin_bar_remove_wp_menus', 2 );

?>