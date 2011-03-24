<?php

/**
 * BuddyPress Members Admin Bar
 *
 * Handles the member functions related to the WordPress Admin Bar
 *
 * @package BuddyPress
 * @subpackage Core
 */

/**
 * Add the "My Account" menu and all submenus.
 *
 * @since BuddyPress (r4151)
 */
function bp_members_admin_bar_my_account_menu() {
	global $bp, $wp_admin_bar;

	// Create the root blog menu
	$wp_admin_bar->add_menu( array(
		'id'    => 'bp-root-blog',
		'title' => get_blog_option( BP_ROOT_BLOG, 'blogname' ),
		'href'  => bp_get_root_domain()
	) );

	// Logged in user
	if ( is_user_logged_in() ) {

		// User avatar
		$avatar = bp_core_fetch_avatar( array(
			'item_id' => $bp->loggedin_user->id,
			'email'   => $bp->loggedin_user->userdata->user_email,
			'width'   => 16,
			'height'  => 16
		) );

		// Unique ID for the 'My Account' menu
		$bp->my_account_menu_id = ( ! empty( $avatar ) ) ? 'my-account-with-avatar' : 'my-account';

		// Create the main 'My Account' menu
		$wp_admin_bar->add_menu( array(
			'id'    => $bp->my_account_menu_id,
			'title' => $avatar . bp_get_user_firstname(),
			'href'  => bp_core_get_user_domain( $bp->loggedin_user->id )
		) );

	// Show login and sign-up links
	} elseif ( !empty( $wp_admin_bar ) ) {

		add_filter ( 'show_admin_bar', '__return_true' );

		// Create the main 'My Account' menu
		$wp_admin_bar->add_menu( array(
			'id'    => 'bp-login',
			'title' => __( 'Log in', 'buddypress' ),
			'href'  => wp_login_url()
		) );

		// Sign up
		if ( bp_get_signup_allowed() ) {
			$wp_admin_bar->add_menu( array(
				'id'    => 'bp-register',
				'title' => __( 'Register', 'buddypress' ),
				'href'  => bp_get_signup_page()
			) );
		}
	}
}
if ( defined( 'BP_USE_WP_ADMIN_BAR' ) )
	add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_my_account_menu', 4 );

/**
 * Make sure the logout link is at the bottom of the "My Account" menu
 *
 * @since BuddyPress (r4151)
 *
 * @global obj $bp
 * @global obj $wp_admin_bar
 */
function bp_members_admin_bar_my_account_logout() {
	global $bp, $wp_admin_bar;

	if ( is_user_logged_in() ) {
		// Log out
		$wp_admin_bar->add_menu( array(
			'parent' => $bp->my_account_menu_id,
			'title'  => __( 'Log Out', 'buddypress' ),
			'href'   => wp_logout_url()
		) );
	}
}
if ( defined( 'BP_USE_WP_ADMIN_BAR' ) )
	add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_my_account_logout', 9999 );

?>