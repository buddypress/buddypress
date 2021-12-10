<?php
/**
 * BuddyPress Membership Requests
 *
 * @package BuddyPress
 * @subpackage MembersMembershipRequest
 * @since 10.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Single site: When a user creates a membership request,
 * prevent the sending of the activation email so that
 * the site admins can send it manually.
 *
 * @since 10.0.0
 *
 * @param bool   $send           Whether or not to send the activation key.
 * @param int    $user_id        User ID to send activation key to.
 * @param string $user_email     User email to send activation key to.
 * @param string $activation_key Activation key to be sent.
 * @param array  $usermeta       Miscellaneous metadata about the user (blog-specific
 *                               signup data, xprofile data, etc).
 * @return bool Whether or not to send the activation key.
 */
function bp_members_membership_requests_cancel_activation_email( $send, $user_id = 0, $user_email = '', $activation_key = '', $usermeta = array() ) {

	$details = array(
		'user_id'        => $user_id,
		'user_email'     => $user_email,
		'activation_key' => $activation_key,
		'usermeta'       => $usermeta,
	);

	/**
	 * Allow some membership requests to be approved immediately.
	 * For example, you might want to approve all requests
	 * coming from users with certain email address domains.
	 * If `true` is returned the activation email will be sent to the user.
	 *
	 * @since 10.0.0
	 *
	 * @param bool  $send    Whether or not this membership request should be approved
	 *                       immediately and the activation email sent.
	 *                       Default is `false` meaning that the request should be
	 *                       manually approved by a site admin.
	 * @param array $details The details of the request.
	 */
	$send = apply_filters( 'bp_members_membership_requests_bypass_manual_approval', false, $details );

	// If the registration process has been interrupted, this is a new membership request.
	if ( ! $send ) {
		$signup = bp_members_get_signup_by( 'activation_key', $activation_key );

		/**
		 * Fires when a site membership request has been created and is pending.
		 *
		 * @since 10.0.0
		 *
		 * @param BP_Signup $signup  The signup object that has been created.
		 * @param array     $details The details of the request.
		 */
		do_action( 'bp_members_membership_request_submitted', $signup, $details );
	}

	return $send;
}
add_filter( 'bp_core_signup_send_activation_key', 'bp_members_membership_requests_cancel_activation_email', 10, 5 );

/**
 * WP Multisite: When a user creates a membership request,
 * prevent the sending of the activation email so that
 * the site admins can send it manually.
 *
 * @since 10.0.0
 *
 * @param bool   $send             Whether or not to send the activation key.
 * @param string $user_login       User login name.
 * @param string $user_email       User email address.
 * @param string $activation_key   Activation key created in wpmu_signup_user().
 * @param bool   $is_signup_resend Is the site admin sending this email?
 * @return bool Whether or not to send the activation key.
 */
function bp_members_membership_requests_cancel_activation_email_multisite( $send = true, $user_login = '', $user_email = '', $activation_key = '', $is_signup_resend = false ) {

	$details = array(
		'user_login'       => $user_login,
		'user_email'       => $user_email,
		'activation_key'   => $activation_key,
		'is_signup_resend' => $is_signup_resend,
	);

	// Allow the site admin to send/resend approval emails.
	if ( $is_signup_resend ) {
		$to_send = true;
	} else {
		$to_send = false;
	}

	/**
	 * Allow some membership requests to be approved immediately.
	 * For example, you might want to approve all requests
	 * coming from users with certain email address domains.
	 * If `true` is returned the activation email will be sent to the user.
	 *
	 * @since 10.0.0
	 *
	 * @param bool  $to_send Whether or not this membership request should be approved
	 *                       immediately and the activation email sent.
	 *                       Default is `false` meaning that the request should be
	 *                       manually approved by a site admin.
	 * @param array $details The details of the request.
	 */
	$send = apply_filters( 'bp_members_membership_requests_bypass_manual_approval_multisite', $to_send, $details );

	// If the registration process has been interrupted, this is a new membership request.
	if ( ! $send ) {
		$signup = bp_members_get_signup_by( 'activation_key', $activation_key );

		/**
		 * Fires when a site membership request has been created and is pending.
		 *
		 * @since 10.0.0
		 *
		 * @param BP_Signup $signup  The signup object that has been created.
		 * @param array     $details The details of the request.
		 */
		do_action( 'bp_members_membership_request_submitted', $signup, $details );
	}

	return $send;
}
add_filter( 'bp_core_signup_send_activation_key_multisite', 'bp_members_membership_requests_cancel_activation_email_multisite', 10, 5 );
add_filter( 'bp_core_signup_send_activation_key_multisite_blog', 'bp_members_membership_requests_cancel_activation_email_multisite', 10, 5 );

/**
 * Notifications
 *********************************************************************/

/**
 * Notify site admins about a new membership request.
 *
 * @since 10.0.0
 *
 * @param BP_Signup $signup The signup object that has been created.
 */
function bp_members_membership_requests_notify_site_admins( $signup ) {

	if ( ! isset( $signup->signup_id ) ) {
		return;
	}

	// Notify all site admins so the request can be handled.
	$admin_ids = get_users(
		array(
			'fields' => 'ids',
			'role'   => 'administrator',
		)
	);

	foreach ( $admin_ids as $admin_id ) {
		// Trigger a BuddyPress Notification.
		if ( bp_is_active( 'notifications' ) ) {
			bp_notifications_add_notification(
				array(
					'user_id'          => $admin_id,
					'item_id'          => $signup->signup_id,
					'component_name'   => buddypress()->members->id,
					'component_action' => 'membership_request_submitted',
					'date_notified'    => bp_core_current_time(),
					'is_new'           => 1,
				)
			);
		}

		// Bail if member opted out of receiving this email.
		if ( 'no' === bp_get_user_meta( $admin_id, 'notification_members_membership_request', true ) ) {
			return;
		}

		$unsubscribe_args = array(
			'user_id'           => $admin_id,
			'notification_type' => 'members-membership-request',
		);

		$manage_url = add_query_arg(
			array(
				'mod_req'   => 1,
				'page'      => 'bp-signups',
				'signup_id' => $signup->signup_id,
				'action'    => 'resend',
			),
			bp_get_admin_url( 'users.php' )
		);

		$args  = array(
			'tokens' => array(
				'admin.id'                   => $admin_id,
				'manage.url'                 => esc_url_raw( $manage_url ),
				'requesting-user.user_login' => esc_html( $signup->user_login ),
				'unsubscribe'                => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
			),
		);

		bp_send_email( 'members-membership-request', (int) $admin_id, $args );
	}
}
add_action( 'bp_members_membership_request_submitted', 'bp_members_membership_requests_notify_site_admins' );

/**
 * Send a message to the requesting user when his or her
 * site membership request has been rejected.
 *
 * @since 10.0.0
 *
 * @param array $signup_ids Array of pending IDs to delete.
 */
function bp_members_membership_requests_send_rejection_mail( $signup_ids ) {
	$signups = BP_Signup::get(
		array(
			'include' => $signup_ids,
		)
	);

	if ( empty( $signups['signups'] ) ) {
		return;
	}

	foreach ( $signups['signups'] as $signup ) {
		if ( ! empty( $signup->user_email ) ) {
			bp_send_email( 'members-membership-request-rejected', $signup->user_email );
		}
	}
}
add_action( 'bp_core_signup_before_delete', 'bp_members_membership_requests_send_rejection_mail' );

/**
 * When a request is approved, activated or deleted,
 * delete the associated notifications.
 *
 * @since 10.0.0
 *
 * @param array $signup_ids Array of changing signup IDs.
 */
function bp_members_membership_requests_delete_notifications_on_change( $signup_ids ) {
	foreach ( $signup_ids as $signup_id ) {
		BP_Notifications_Notification::delete(
			array(
				'item_id'          => $signup_id,
				'component_action' => 'membership_request_submitted',
			)
		);
	}
}
add_action( 'bp_core_signup_after_resend',   'bp_members_membership_requests_delete_notifications_on_change' );
add_action( 'bp_core_signup_after_activate', 'bp_members_membership_requests_delete_notifications_on_change' );
add_action( 'bp_core_signup_after_delete',   'bp_members_membership_requests_delete_notifications_on_change' );

