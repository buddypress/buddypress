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
 * Build the "Notifications" dropdown.
 *
 * @since 11.4.0
 */
function bp_members_admin_bar_notifications_dropdown( $notifications = array(), $menu_link = '', $type = 'members' ) {
	if ( ! $menu_link || ( 'admin' === $type && empty( $notifications ) ) ) {
		return false;
	}

	global $wp_admin_bar;

	$count       = 0;
	$alert_class = array( 'count', 'no-alert' );

	if ( ! empty( $notifications ) ) {
		$count       = number_format_i18n( count( $notifications ) );
		$alert_class = array( 'pending-count', 'alert' );

		if ( 'admin' === $type ) {
			$count = '!';
		}
	};

	$alert_class[] = $type . '-type';
	$menu_title    = sprintf(
		'<span id="ab-pending-notifications" class="%1$s">%2$s</span>',
		implode( ' ', array_map( 'sanitize_html_class', $alert_class ) ),
		$count
	);

	// Add the top-level Notifications button.
	$wp_admin_bar->add_node( array(
		'parent' => 'top-secondary',
		'id'     => 'bp-notifications',
		'title'  => $menu_title,
		'href'   => $menu_link,
	) );

	if ( ! empty( $notifications ) ) {
		foreach ( (array) $notifications as $notification ) {
			$wp_admin_bar->add_node( array(
				'parent' => 'bp-notifications',
				'id'     => 'notification-' . $notification->id,
				'title'  => $notification->content,
				'href'   => $notification->href,
			) );
		}
	} else {
		$wp_admin_bar->add_node( array(
			'parent' => 'bp-notifications',
			'id'     => 'no-notifications',
			'title'  => __( 'No new notifications', 'buddypress' ),
			'href'   => $menu_link,
		) );
	}

	return true;
}

/**
 * Build the Admin or Members "Notifications" dropdown.
 *
 * @since 1.5.0
 *
 * @return bool
 */
function bp_members_admin_bar_notifications_menu() {
	$admins_notifications = array();
	$capability           = 'manage_options';

	if ( bp_core_do_network_admin() ) {
		$capability = 'manage_network_options';
	}

	if ( bp_current_user_can( $capability ) ) {
		$notifications = bp_core_get_admin_notifications();

		if ( $notifications ) {
			$menu_link = esc_url( bp_get_admin_url( add_query_arg( 'page', 'bp-admin-notifications', 'admin.php' ) ) );
			$count     = count( $notifications );

			$notifications = array(
				(object) array(
					'id'      => 'bp-admin-notifications',
					'href'    => $menu_link,
					'content' => sprintf(
						/* translators: %s: the number of admin notifications */
						_n( 'You have %s new important admin notification.', 'You have %s new important admin notifications.', $count, 'buddypress' ),
						number_format_i18n( $count )
					),
				),
			);

			return bp_members_admin_bar_notifications_dropdown( $notifications, $menu_link, 'admin' );
		}
	}

	// Use Members notifications if the component is active.
	if ( bp_is_active( 'notifications' ) ) {
		return bp_notifications_toolbar_menu();
	}
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
		bp_members_admin_bar_notifications_menu();
	} else {
		add_action( 'admin_bar_menu', 'bp_members_admin_bar_notifications_menu', 90 );
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
