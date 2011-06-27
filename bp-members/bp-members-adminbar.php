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

	// Bail if this is an ajax request
	if ( defined( 'DOING_AJAX' ) )
		return;

	// Create the root blog menu
	$wp_admin_bar->add_menu( array(
		'id'    => 'bp-root-blog',
		'title' => get_blog_option( BP_ROOT_BLOG, 'blogname' ),
		'href'  => bp_get_root_domain()
	) );

	// Logged in user
	if ( is_user_logged_in() ) {

		// Dashboard links
		if ( is_super_admin() ) {

			// Add site admin link
			$wp_admin_bar->add_menu( array(
				'parent' => 'bp-root-blog',
				'title'  => __( 'Admin Dashboard', 'buddypress' ),
				'href'   => get_admin_url( BP_ROOT_BLOG )
			) );

			// Add network admin link
			if ( is_multisite() ) {

				// Link to the network admin dashboard
				$wp_admin_bar->add_menu( array(
					'parent' => 'bp-root-blog',
					'title'  => __( 'Network Dashboard', 'buddypress' ),
					'href'   => network_admin_url()
				) );
			}
		}

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
			'title' => $avatar . bp_get_user_firstname( $bp->loggedin_user->fullname ),
			'href'  => $bp->loggedin_user->domain
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
add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_my_account_menu', 4 );

function bp_members_user_admin_menu() {
	global $wp_admin_bar;

	// Only show if viewing a user
	if ( !bp_is_user() )
		return false;

	// Don't show this menu to non site admins or if you're viewing your own profile
	if ( !current_user_can( 'edit_users' ) || bp_is_my_profile() )
		return false;

	// Add the top-level User Admin button
	$wp_admin_bar->add_menu( array(
		'id'    => 'user-admin',
		'title' => __( 'User Admin', 'buddypress' ),
		'href'  => bp_displayed_user_domain()
	) );

	// User Admin > Edit this user's profile
	$wp_admin_bar->add_menu( array(
		'parent' => 'user-admin',
		'id'     => 'edit-profile',
		'title'  => sprintf( __( "Edit %s's Profile", 'buddypress' ), bp_get_displayed_user_fullname() ),
		'href'   => bp_get_members_component_link( 'profile', 'edit' )
	) );
	
	// User Admin > Edit this user's avatar
	$wp_admin_bar->add_menu( array(
		'parent' => 'user-admin',
		'id'     => 'change-avatar',
		'title'  => sprintf( __( "Edit %s's Avatar", 'buddypress' ), bp_get_displayed_user_fullname() ),
		'href'   => bp_get_members_component_link( 'profile', 'change-avatar' )
	) );
	
	// User Admin > Edit this user's avatar
	$wp_admin_bar->add_menu( array(
		'parent' => 'user-admin',
		'id'     => 'change-avatar',
		'title'  => sprintf( __( "Edit %s's Avatar", 'buddypress' ), bp_get_displayed_user_fullname() ),
		'href'   => bp_get_members_component_link( 'profile', 'change-avatar' )
	) );
	
	// User Admin > Spam/unspam
	if ( !bp_core_is_user_spammer( bp_displayed_user_id() ) ) {
		$wp_admin_bar->add_menu( array(
			'parent' => 'user-admin',
			'id'     => 'spam-user',
			'title'  => __( "Mark as Spammer", 'buddypress' ),
			'href'   => wp_nonce_url( bp_displayed_user_domain() . 'admin/mark-spammer/', 'mark-unmark-spammer' ),
			'meta'   => array( 'onclick' => 'confirm(" ' . __( 'Are you sure you want to mark this user as a spammer?', 'buddypress' ) . '");' ) 
		) );	
	} else {
		$wp_admin_bar->add_menu( array(
			'parent' => 'user-admin',
			'id'     => 'unspam-user',
			'title'  => __( "Not a Spammer", 'buddypress' ),
			'href'   => wp_nonce_url( bp_displayed_user_domain() . 'admin/unmark-spammer/', 'mark-unmark-spammer' ),
			'meta'   => array( 'onclick' => 'confirm(" ' . __( 'Are you sure you want to mark this user as not a spammer?', 'buddypress' ) . '");' ) 
		) );
	}
	
	// User Admin > Delete Account
	$wp_admin_bar->add_menu( array(
		'parent' => 'user-admin',
		'id'     => 'delete-user',
		'title'  => sprintf( __( "Delete %s's Account", 'buddypress' ), bp_get_displayed_user_fullname() ),
		'href'   => wp_nonce_url( bp_displayed_user_domain() . 'admin/delete-user/', 'delete-user' ),
		'meta'   => array( 'onclick' => 'confirm(" ' . __( "Are you sure you want to delete this user's account?", 'buddypress' ) . '");' ) 
	) );
}
add_action( 'bp_setup_admin_bar', 'bp_members_user_admin_menu', 99 );

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

	// Bail if this is an ajax request
	if ( defined( 'DOING_AJAX' ) )
		return;

	if ( is_user_logged_in() ) {
		// Log out
		$wp_admin_bar->add_menu( array(
			'parent' => $bp->my_account_menu_id,
			'title'  => __( 'Log Out', 'buddypress' ),
			'href'   => wp_logout_url()
		) );
	}
}
add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_my_account_logout', 9999 );

?>