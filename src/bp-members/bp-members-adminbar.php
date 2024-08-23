<?php
/**
 * BuddyPress Members Toolbar.
 *
 * Handles the member functions related to the WordPress Toolbar.
 *
 * @package BuddyPress
 * @subpackage MembersAdminBar
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Add the "My Account" menu and all submenus.
 *
 * @since 1.6.0
 *
 * @global WP_Admin_Bar $wp_admin_bar WordPress object implementing a Toolbar API.
 */
function bp_members_admin_bar_my_account_menu() {
	global $wp_admin_bar;

	// Bail if this is an ajax request.
	if ( wp_doing_ajax() ) {
		return;
	}

	// Logged in user.
	if ( is_user_logged_in() ) {

		$bp = buddypress();

		// Stored in the global so we can add menus easily later on.
		$bp->my_account_menu_id = 'my-account-buddypress';

		// Create the main 'My Account' menu.
		$wp_admin_bar->add_node( array(
			'id'     => $bp->my_account_menu_id,
			'group'  => true,
			'title'  => __( 'Edit My Profile', 'buddypress' ),
			'href'   => bp_loggedin_user_url(),
			'meta'   => array(
			'class'  => 'ab-sub-secondary'
		) ) );

		// Show login and sign-up links.
	} elseif ( !empty( $wp_admin_bar ) ) {

		add_filter( 'show_admin_bar', '__return_true' );

		// Create the main 'My Account' menu.
		$wp_admin_bar->add_node( array(
			'id'    => 'bp-login',
			'title' => __( 'Log In', 'buddypress' ),
			'href'  => wp_login_url( bp_get_requested_url() )
		) );

		// Sign up.
		if ( bp_get_signup_allowed() ) {
			$wp_admin_bar->add_node( array(
				'id'    => 'bp-register',
				'title' => __( 'Register', 'buddypress' ),
				'href'  => bp_get_signup_page()
			) );
		}
	}
}
add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_my_account_menu', 4 );

/**
 * Add the User Admin top-level menu to user pages.
 *
 * @since 1.5.0
 *
 * @global WP_Admin_Bar $wp_admin_bar WordPress object implementing a Toolbar API.
 */
function bp_members_admin_bar_user_admin_menu() {
	global $wp_admin_bar;

	// Only show if viewing a user.
	if ( ! bp_is_user() ) {
		return false;
	}

	// Don't show this menu to non site admins or if you're viewing your own profile.
	if ( ! current_user_can( 'edit_users' ) || bp_is_my_profile() ) {
		return false;
	}

	$bp = buddypress();

	// Unique ID for the 'My Account' menu.
	$bp->user_admin_menu_id = 'user-admin';

	// Add the top-level User Admin button.
	$wp_admin_bar->add_node(
		array(
			'id'    => $bp->user_admin_menu_id,
			'title' => __( 'Edit Member', 'buddypress' ),
			'href'  => bp_displayed_user_url()
		)
	);

	if ( bp_is_active( 'xprofile' ) ) {
		// User Admin > Edit this user's profile.
		$wp_admin_bar->add_node(
			array(
				'parent' => $bp->user_admin_menu_id,
				'id'     => $bp->user_admin_menu_id . '-edit-profile',
				'title'  => __( "Edit Profile", 'buddypress' ),
				'href'   => bp_get_members_component_link( $bp->profile->id, 'edit' ),
			)
		);

		// User Admin > Edit this user's avatar.
		if ( buddypress()->avatar->show_avatars ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => $bp->user_admin_menu_id,
					'id'     => $bp->user_admin_menu_id . '-change-avatar',
					'title'  => __( "Edit Profile Photo", 'buddypress' ),
					'href'   => bp_get_members_component_link( $bp->profile->id, 'change-avatar' ),
				)
			);
		}

		// User Admin > Edit this user's cover image.
		if ( bp_displayed_user_use_cover_image_header() ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => $bp->user_admin_menu_id,
					'id'     => $bp->user_admin_menu_id . '-change-cover-image',
					'title'  => __( 'Edit Cover Image', 'buddypress' ),
					'href'   => bp_get_members_component_link( $bp->profile->id, 'change-cover-image' ),
				)
			);
		}

	}

	if ( bp_is_active( 'settings' ) ) {
		// User Admin > Spam/unspam.
		$wp_admin_bar->add_node(
			array(
				'parent' => $bp->user_admin_menu_id,
				'id'     => $bp->user_admin_menu_id . '-user-capabilities',
				'title'  => __( 'User Capabilities', 'buddypress' ),
				'href'   => bp_get_members_component_link( $bp->settings->id, 'capabilities' ),
			)
		);

		// User Admin > Delete Account.
		$wp_admin_bar->add_node(
			array(
				'parent' => $bp->user_admin_menu_id,
				'id'     => $bp->user_admin_menu_id . '-delete-user',
				'title'  => __( 'Delete Account', 'buddypress' ),
				'href'   => bp_get_members_component_link( $bp->settings->id, 'delete-account' ),
			)
		);
	}
}
add_action( 'admin_bar_menu', 'bp_members_admin_bar_user_admin_menu', 99 );

/**
 * Adds a WP Admin Bar menu containing a button to open the Notices Center.
 *
 * @since 15.0.0
 */
function bp_members_admin_bar_notices_center_menu() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$user_id       = bp_loggedin_user_id();
	$notices_count = bp_members_get_notices_count(
		array(
			'user_id'  => $user_id,
			'exclude'  => bp_members_get_dismissed_notices_for_user( $user_id ),
		)
	);

	$notifications_count = 0;
	if ( bp_is_active( 'notifications' ) ) {
		$notifications_count = bp_notifications_get_unread_notification_count( $user_id );
	}

	$count = $notices_count + $notifications_count;
	if ( ! $count ) {
		return;
	}

	global $wp_admin_bar;

	// Add the top-level Notice center button.
	$wp_admin_bar->add_node(
		array(
			'parent' => 'top-secondary',
			'id'     => 'bp-notifications',
			'title'  => sprintf(
				'<button id="bp-notices-toggler" data-bp-fallback-url="%1$s" popovertarget="bp-notices-container" popovertargetaction="toggle">
					<span id="ab-pending-notifications" class="pending-count alert">
						<span class="ab-icon" aria-hidden="true"></span>
						<span class="count">%2$s</span>
					</span>
				</button>',
				esc_url( bp_get_member_all_notices_url() ),
				number_format_i18n( $count )
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'bp-notices',
			)
		)
	);

	// Get the Notices center script.
	wp_enqueue_script( 'bp-notices-center-script' );

	/*
	 * If There are notices to display, load the Notice popover once
	 * the WP Admin Bar has fully been loaded.
	 */
	add_action( 'wp_after_admin_bar_render', 'bp_render_notices_center' );
}

/**
 * Keep the Notification toolbar menu at the left of the "My Account" one.
 *
 * @since 12.6.0
 */
function bp_members_admin_bar_notifications_menu_priority() {
	/*
	 * WordPress 6.6 edited the WP Admin style & removed the right float.
	 * See: https://core.trac.wordpress.org/changeset/58215/
	 */
	if ( bp_is_running_wp( '6.6-beta2', '>=' ) ) {
		bp_members_admin_bar_notices_center_menu();
	} else {
		add_action( 'admin_bar_menu', 'bp_members_admin_bar_notices_center_menu', 90 );
	}
}
add_action( 'admin_bar_menu', 'bp_members_admin_bar_notifications_menu_priority', 6 );

/**
 * Remove rogue WP core Edit menu when viewing a single user.
 *
 * @since 1.6.0
 */
function bp_members_remove_edit_page_menu() {
	if ( bp_is_user() ) {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
	}
}
add_action( 'add_admin_bar_menus', 'bp_members_remove_edit_page_menu' );
