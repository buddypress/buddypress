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

	$invites = bp_members_invitations_get_invites(
		array(
			'invitee_email' => $user_email,
			'invite_sent'   => 'sent'
		)
	);

	// If the registration process has been interrupted, this is a new membership request or the user was accepting an invitation and we need not send an activation email.
	if ( ! $send && ! $invites ) {
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
			continue;
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
	if ( bp_is_active( 'notifications' ) ) {
		foreach ( $signup_ids as $signup_id ) {
			/**
			 * We use this method instead of one of the notification functions
			 * because we want to delete all notifications for all admins.
			 */
			BP_Notifications_Notification::delete(
				array(
					'item_id'          => $signup_id,
					'component_action' => 'membership_request_submitted',
				)
			);
		}
	}
}
add_action( 'bp_core_signup_after_resend',   'bp_members_membership_requests_delete_notifications_on_change' );
add_action( 'bp_core_signup_after_activate', 'bp_members_membership_requests_delete_notifications_on_change' );
add_action( 'bp_core_signup_after_delete',   'bp_members_membership_requests_delete_notifications_on_change' );

/**
 * In the Nouveau template pack, when membership requests are required,
 * change registration form submit button label to "Submit Request".
 *
 * @since 10.0.0
 *
 * @return string $retval the HTML for the request membership link.
 */
function bp_members_membership_requests_filter_complete_signup_button( $buttons ) {

	$buttons['register']['attributes']['value'] = __( 'Submit Request', 'buddypress' );
	return $buttons;
}
add_filter( 'bp_nouveau_get_submit_button', 'bp_members_membership_requests_filter_complete_signup_button' );

/**
 * Administration: Change certain behavior and labels
 * on the WP Admin > Users > Manage Signups screen.
 *********************************************************************/

/**
 * Filter the actions available on the signups list table.
 *
 * @since 10.0.0
 *
 * @param array  $actions       Array of actions and corresponding links.
 * @param object $signup_object The signup data object.
 */
function bp_members_membership_requests_filter_signup_row_actions( $actions, $signup_object ) {

	// Rename the "email" resend option when membership requests are active.
	$email_link = add_query_arg(
		array(
			'page'	    => 'bp-signups',
			'signup_id' => $signup_object->id,
			'action'    => 'resend',
		),
		bp_get_admin_url( 'users.php' )
	);

	$resend_label = ( 0 === $signup_object->count_sent ) ? __( 'Approve Request', 'buddypress' ) : __( 'Resend Approval', 'buddypress' );

	$actions['resend'] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $email_link ), esc_html( $resend_label ) );

	// Add a link to view profile info when membership requests and xprofile are active.
	if ( bp_is_active( 'xprofile' ) || bp_members_site_requests_enabled() ) {
		$profile_link = add_query_arg(
			array(
				'page'	   => 'bp-signups#TB_inline',
				'inlineId' => 'signup-info-modal-' . $signup_object->id,
			),
			bp_get_admin_url( 'users.php' )
		);

		$actions['profile'] = sprintf( '<a href="%1$s" class="bp-thickbox">%2$s</a>', esc_url( $profile_link ), esc_html__( 'Profile Info', 'buddypress' ) );
	}

	return $actions;
}
add_filter( 'bp_members_ms_signup_row_actions', 'bp_members_membership_requests_filter_signup_row_actions', 10, 2 );

/**
 * Filter the bulk actions available on the signups list table.
 *
 * @since 10.0.0
 *
 * @param array $actions Array of actions and corresponding links.
 * @return array         List of actions and corresponding links.
 */
function bp_members_membership_requests_filter_signup_bulk_actions( $actions ) {
	// Rename the "email" resend option when membership requests are active.
	$actions['resend'] = esc_html_x( 'Approve', 'Pending signup action', 'buddypress' );
	return $actions;
}
add_filter( 'bp_members_ms_signup_bulk_actions', 'bp_members_membership_requests_filter_signup_bulk_actions' );

/**
 * Filter the "Last Sent" column header on the pending users table.
 *
 * @since 10.0.0
 *
 * @param array $columns Array of columns to display.
 * @return array List of columns to display.
 */
function bp_members_membership_requests_filter_signup_table_date_sent_header( $columns ) {
	$columns['date_sent'] = esc_html__( 'Approved', 'buddypress' );
	return $columns;
}
add_filter( 'bp_members_signup_columns', 'bp_members_membership_requests_filter_signup_table_date_sent_header' );
add_filter( 'bp_members_ms_signup_columns', 'bp_members_membership_requests_filter_signup_table_date_sent_header' );

/**
 * Filter the "Last Sent" column message on the pending users table.
 *
 * @since 10.0.0
 *
 * @param string      $message "Not yet sent" message.
 * @param object|null $signup  Signup object instance.
 * @return string              "Not yet approved" message, if needed. Unchanged message otherwise.
 */
function bp_members_membership_requests_filter_signup_table_unsent_message( $message, $signup ) {
	if ( 0 === $signup->count_sent ) {
		$message = esc_html__( 'Not yet approved', 'buddypress' );
	}

	return $message;
}
add_filter( 'bp_members_signup_date_sent_unsent_message', 'bp_members_membership_requests_filter_signup_table_unsent_message', 10, 2 );
add_filter( 'bp_members_ms_signup_date_sent_unsent_message', 'bp_members_membership_requests_filter_signup_table_unsent_message', 10, 2 );

/**
 * Filter/add "Request Membership" links in the following locations:
 * - BP login block widget,
 * - Sidebar register link,
 * - WP Toolbar,
 * - WP login form.
 *********************************************************************/

/**
 * Add "Request Membership" link to Block Widget login form.
 *
 * @since 10.0.0
 *
 * @return string $retval the HTML for the request membership link.
 */
function bp_members_membership_requests_add_link_to_widget_login_form() {
	?>
	<span class="bp-login-widget-request-membership-link"><a href="<?php echo esc_url( bp_get_signup_page() ); ?>"><?php esc_html_e( 'Request Membership', 'buddypress' ); ?></a></span>
	<?php
}
add_action( 'bp_login_widget_form', 'bp_members_membership_requests_add_link_to_widget_login_form' );

/**
 * Filter the "Register" link from `wp_register()` as used in
 * `sidebar.php` and the WP Core meta widget.
 *
 * @since 10.0.0
 *
 * @param string $link The HTML code for the link to the Registration or Admin page.
 * @return string      An empty string or the HTML code for the link to the Membership request page.
 */
function bp_members_membership_requests_filter_sidebar_register_link( $link ) {
	// $link should be an empty string when public registration is disabled.
	if ( ! is_user_logged_in() && empty( $link ) ) {
		$link = '<a href="' . esc_url( wp_registration_url() ) . '">' . esc_html__( 'Request Membership', 'buddypress' ) . '</a>';
	}

	return $link;
}
add_filter( 'register', 'bp_members_membership_requests_filter_sidebar_register_link' );

/**
 * Add a "Request Membership" link to the WP Toolbar.
 * Priority 21 should place it just after the "Log In" link.
 *
 * @since 10.0.0
 *
 * @param WP_Admin_Bar $wp_admin_bar WordPress object implementing a Toolbar API.
 */
function bp_members_membership_requests_add_toolbar_link( $wp_admin_bar ) {
	if ( is_user_logged_in() ) {
		return;
	}

	$args = array(
		'id'    => 'bp-request-membership',
		'title' => __( 'Request Membership', 'buddypress' ),
		'href'  => wp_registration_url(),
		'meta'  => array(
			'class' => 'buddypress-request-membership',
			'title' => __( 'Request Membership', 'buddypress' ),
		),
	);

	$wp_admin_bar->add_node( $args );
}
add_action( 'admin_bar_menu', 'bp_members_membership_requests_add_toolbar_link', 21 );

/**
 * Add a "Request Membership" link to the WP Login form.
 *
 * @since 10.0.0
 *
 * @param string $link HTML link to the home URL of the current site.
 * @return string      HTML link to the home URL of the current site and the one to request a membership.
 */
function bp_members_membership_requests_add_link_wp_login( $link ) {
	$link_separator = apply_filters( 'login_link_separator', ' | ' );

	return $link . $link_separator . '<a href="' . esc_url( wp_registration_url() ) . '">' . esc_html__( 'Request Membership', 'buddypress' ) . '</a>';
}
add_action( 'login_site_html_link', 'bp_members_membership_requests_add_link_wp_login' );
