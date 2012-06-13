<?php
/**
 * BuddyPress Members Admin Bar
 *
 * Handles the member functions related to the WordPress Admin Bar
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Adjust the admin bar menus based on which WordPress version this is
 *
 * @since BuddyPress (1.5.2)
 */
function bp_members_admin_bar_version_check() {
	switch( bp_get_major_wp_version() ) {
		case 3.2 :
			add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_my_account_menu',    4    );
			add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_notifications_menu', 5    );
			add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_user_admin_menu',    99   );
			add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_my_account_logout',  9999 );
			break;
		case 3.3 :
		case 3.4 :
		default  :
			add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_my_account_menu',    4   );
			add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_notifications_menu', 5   );
			add_action( 'admin_bar_menu',     'bp_members_admin_bar_user_admin_menu',    400 );
			break;		
	}
}
add_action( 'admin_bar_menu', 'bp_members_admin_bar_version_check', 4 );

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

	// Logged in user
	if ( is_user_logged_in() ) {

		if ( 3.2 == bp_get_major_wp_version() ) {

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
				'title' => $avatar . bp_get_loggedin_user_fullname(),
				'href'  => $bp->loggedin_user->domain
			) );

		} else {

			// Unique ID for the 'My Account' menu
			$bp->my_account_menu_id = 'my-account-buddypress';

			// Create the main 'My Account' menu
			$wp_admin_bar->add_menu( array(
				'parent' => 'my-account',
				'id'     => $bp->my_account_menu_id,
				'href'   => $bp->loggedin_user->domain,
				'group'  => true,
				'meta'   => array( 'class' => 'ab-sub-secondary' )
			) );
		}

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

/**
 * Adds the User Admin top-level menu to user pages
 *
 * @package BuddyPress
 * @since 1.5
 */
function bp_members_admin_bar_user_admin_menu() {
	global $bp, $wp_admin_bar;

	// Only show if viewing a user
	if ( !bp_is_user() )
		return false;

	// Don't show this menu to non site admins or if you're viewing your own profile
	if ( !current_user_can( 'edit_users' ) || bp_is_my_profile() )
		return false;

	if ( 3.2 == bp_get_major_wp_version() ) {

		// User avatar
		$avatar = bp_core_fetch_avatar( array(
			'item_id' => $bp->displayed_user->id,
			'email'   => $bp->displayed_user->userdata->user_email,
			'width'   => 16,
			'height'  => 16
		) );

		// Unique ID for the 'My Account' menu
		$bp->user_admin_menu_id = ( ! empty( $avatar ) ) ? 'user-admin-with-avatar' : 'user-admin';

		// Add the top-level User Admin button
		$wp_admin_bar->add_menu( array(
			'id'    => $bp->user_admin_menu_id,
			'title' => $avatar . bp_get_displayed_user_fullname(),
			'href'  => bp_displayed_user_domain()
		) );

	} elseif ( 3.3 <= bp_get_major_wp_version() ) {
		
		// Unique ID for the 'My Account' menu
		$bp->user_admin_menu_id = 'user-admin';

		// Add the top-level User Admin button
		$wp_admin_bar->add_menu( array(
			'id'    => $bp->user_admin_menu_id,
			'title' => __( 'Edit Member', 'buddypress' ),
			'href'  => bp_displayed_user_domain()
		) );
	}

	// User Admin > Edit this user's profile
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->user_admin_menu_id,
		'id'     => 'edit-profile',
		'title'  => __( "Edit Profile", 'buddypress' ),
		'href'   => bp_get_members_component_link( 'profile', 'edit' )
	) );

	// User Admin > Edit this user's avatar
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->user_admin_menu_id,
		'id'     => 'change-avatar',
		'title'  => __( "Edit Avatar", 'buddypress' ),
		'href'   => bp_get_members_component_link( 'profile', 'change-avatar' )
	) );

	// User Admin > Spam/unspam
	if ( !bp_core_is_user_spammer( bp_displayed_user_id() ) ) {
		$wp_admin_bar->add_menu( array(
			'parent' => $bp->user_admin_menu_id,
			'id'     => 'spam-user',
			'title'  => __( 'Mark as Spammer', 'buddypress' ),
			'href'   => wp_nonce_url( bp_displayed_user_domain() . 'admin/mark-spammer/', 'mark-unmark-spammer' ),
			'meta'   => array( 'onclick' => 'confirm(" ' . __( 'Are you sure you want to mark this user as a spammer?', 'buddypress' ) . '");' )
		) );
	} else {
		$wp_admin_bar->add_menu( array(
			'parent' => $bp->user_admin_menu_id,
			'id'     => 'unspam-user',
			'title'  => __( 'Not a Spammer', 'buddypress' ),
			'href'   => wp_nonce_url( bp_displayed_user_domain() . 'admin/unmark-spammer/', 'mark-unmark-spammer' ),
			'meta'   => array( 'onclick' => 'confirm(" ' . __( 'Are you sure you want to mark this user as not a spammer?', 'buddypress' ) . '");' )
		) );
	}

	// User Admin > Delete Account
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->user_admin_menu_id,
		'id'     => 'delete-user',
		'title'  => __( 'Delete Account', 'buddypress' ),
		'href'   => wp_nonce_url( bp_displayed_user_domain() . 'admin/delete-user/', 'delete-user' ),
		'meta'   => array( 'onclick' => 'confirm(" ' . __( "Are you sure you want to delete this user's account?", 'buddypress' ) . '");' )
	) );
}

/**
 * Build the "Notifications" dropdown
 *
 * @package Buddypress
 * @since 1.5
 */
function bp_members_admin_bar_notifications_menu() {
	global $bp, $wp_admin_bar;

	if ( !is_user_logged_in() )
		return false;

	if ( $notifications = bp_core_get_notifications_for_user( bp_loggedin_user_id(), 'object' ) ) {
		$menu_title = sprintf( __( 'Notifications <span id="ab-pending-notifications" class="pending-count">%s</span>', 'buddypress' ), count( $notifications ) );
	} else {
		$menu_title = __( 'Notifications', 'buddypress' );
	}

	if ( 3.2 == bp_get_major_wp_version() ) {

		// Add the top-level Notifications button
		$wp_admin_bar->add_menu( array(
			'id'    => 'bp-notifications',
			'title' => $menu_title,
			'href'  => bp_loggedin_user_domain()
		) );

	} elseif ( 3.3 == bp_get_major_wp_version() ) {
		
		// Add the top-level Notifications button
		$wp_admin_bar->add_menu( array(
			'parent' => 'top-secondary',
			'id'     => 'bp-notifications',
			'title'  => $menu_title,
			'href'   => bp_loggedin_user_domain()
		) );
	}

	if ( !empty( $notifications ) ) {
		foreach ( (array)$notifications as $notification ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'bp-notifications',
				'id'     => 'notification-' . $notification->id,
				'title'  => $notification->content,
				'href'   => $notification->href
			) );
		}
	} else {
		$wp_admin_bar->add_menu( array(
			'parent' => 'bp-notifications',
			'id'     => 'no-notifications',
			'title'  => __( 'No new notifications', 'buddypress' ),
			'href'   => bp_loggedin_user_domain()
		) );
	}

	return;
}

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
			'id'     => $bp->my_account_menu_id . '-logout',
			'title'  => __( 'Log Out', 'buddypress' ),
			'href'   => wp_logout_url()
		) );
	}
}

?>