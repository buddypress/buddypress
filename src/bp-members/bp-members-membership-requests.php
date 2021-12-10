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

