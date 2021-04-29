<?php
/**
 * BuddyPress Members Activity Functions.
 *
 * These functions handle the recording, deleting and formatting of activity
 * for the user and for this specific component.
 *
 * @package BuddyPress
 * @subpackage MembersNotifications
 * @since 8.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Notification formatting callback for bp-members notifications.
 *
 * @since 8.0.0
 *
 * @param string $action            The kind of notification being rendered.
 * @param int    $item_id           The primary item ID.
 * @param int    $secondary_item_id The secondary item ID.
 * @param int    $total_items       The total number of members-related notifications
 *                                  waiting for the user.
 * @param string $format            'string' for BuddyBar-compatible notifications;
 *                                  'array' for WP Toolbar. Default: 'string'.
 * @return array|string
 */
function members_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

	switch ( $action ) {
		case 'accepted_invitation':

			// Set up the string and the filter.
			if ( (int) $total_items > 1 ) {
				$link   = bp_get_notifications_permalink();
				$amount = 'multiple';

				// This is the inviter whose invitation was accepted.
				if ( 0 !== (int) $secondary_item_id )  {
					/* translators: %d: the number of new users */
					$text = sprintf( __( '%d members accepted your membership invitations', 'buddypress' ), (int) $total_items );
				// This is someone who also invited that user to join.
				} else {
					/* translators: %d: the number of new users */
					$text = sprintf( __( '%d members are now members of the site', 'buddypress' ), (int) $total_items );
				}
			} else {
				$link   = add_query_arg( 'welcome', 1, bp_core_get_user_domain( $item_id ) );
				$amount = 'single';

				// This is the inviter whose invitation was accepted.
				if ( 0 !== (int) $secondary_item_id )  {
					/* translators: %s: new user name */
					$text = sprintf( __( '%s accepted your membership invitation', 'buddypress' ),  bp_core_get_user_displayname( $item_id ) );
					// This is someone who also invited that user to join.
				} else {
					/* translators: %s: new user name */
					$text = sprintf( __( '%s is now a member of the site', 'buddypress' ),  bp_core_get_user_displayname( $item_id ) );
				}
			}
			break;
	}

	// Return either an HTML link or an array, depending on the requested format.
	if ( 'string' == $format ) {

		/**
		 * Filters the format of members notifications based on type and amount * of notifications pending.
		 *
		 * This is a variable filter that has several versions.
		 * The possible versions are:
		 *   - bp_members_single_accepted_invitation_notification
		 *   - bp_members_multiple_accepted_invitation_notification
		 *
		 * @since 8.0.0
		 *
		 * @param string|array $value             Depending on format, an HTML link to new requests profile tab or array with link and text.
		 * @param int          $total_items       The total number of messaging-related notifications waiting for the user.
		 * @param int          $item_id           The primary item ID.
		 * @param int          $secondary_item_id The secondary item ID.
		 */
		$return = apply_filters( 'bp_members_' . $amount . '_'. $action . '_notification', '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $item_id, $secondary_item_id );
	} else {
		/** This filter is documented in bp-members/bp-members-notifications.php */
		$return = apply_filters( 'bp_members_' . $amount . '_'. $action . '_notification', array(
			'link' => $link,
			'text' => $text
		), (int) $total_items, $item_id, $secondary_item_id );
	}

	/**
	 * Fires at the end of the bp-members notification format callback.
	 *
	 * @since 8.0.0
	 *
	 * @param string       $action            The kind of notification being rendered.
	 * @param int          $item_id           The primary item ID.
	 * @param int          $secondary_item_id The secondary item ID.
	 * @param int          $total_items       The total number of members-related notifications
	 *                                        waiting for the user.
	 * @param array|string $return            Notification text string or array of link and text.
	 */
	do_action( 'members_format_notifications', $action, $item_id, $secondary_item_id, $total_items, $return );

	return $return;
}

/**
 * Notify one use that another user has accepted their site membership invitation.
 *
 * @since 8.0.0
 *
 * @param BP_Invitation $invite     Invitation that was accepted.
 * @param WP_user       $new_user   User who accepted the membership invite.
 * @param int           $inviter_id ID of the user who invited this user to the site.
 */
function bp_members_invitations_accepted_invitation_notification( $invite, $new_user, $inviter_id ) {

	// Notify all inviters.
	$args = array(
		'invitee_email' => $new_user->user_email,
		'accepted'      => 'all',
	);
	$invites = bp_members_invitations_get_invites( $args );

	if ( ! $invites ) {
		return;
	}
	foreach ( $invites as $invite) {
		// Include the id of the "accepted" invitation.
		if ( $invite->inviter_id === $inviter_id ) {
			$secondary_item_id = $invite->id;
		} else {
			// Else don't store the invite id, so we know this is not the primary.
			$secondary_item_id = 0;
		}
		$res = bp_notifications_add_notification( array(
			'user_id'           => $invite->inviter_id,
			'item_id'           => $new_user->ID,
			'secondary_item_id' => $secondary_item_id,
			'component_name'    => buddypress()->members->id,
			'component_action'  => 'accepted_invitation',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		) );
	}
}
add_action( 'members_invitations_invite_accepted', 'bp_members_invitations_accepted_invitation_notification', 10, 3 );

/**
 * Mark accepted invitation notifications as read when user visits new user profile.
 *
 *
 * @since 8.0.0
 */
function bp_members_mark_read_accepted_invitation_notification() {
	if ( false === is_singular() || false === is_user_logged_in() || ! bp_is_user() || empty( $_GET['welcome'] ) ) {
		return;
	}

	// Mark notification as read.
	BP_Notifications_Notification::update(
		array(
			'is_new'  => false
		),
		array(
			'user_id' => bp_loggedin_user_id(),
			'item_id' => bp_displayed_user_id(),
		)
	);
}
add_action( 'bp_screens', 'bp_members_mark_read_accepted_invitation_notification' );

/**
 * Add Members-related settings to the Settings > Notifications page.
 *
 * @since 8.0.0
 */
function members_screen_notification_settings() {

	// Bail early if invitations are not allowed--they are the only members notification so far.
	if ( ! bp_get_members_invitations_allowed () ) {
		return;
	}

	if ( ! $allow_acceptance_emails = bp_get_user_meta( bp_displayed_user_id(), 'notification_members_invitation_accepted', true ) ) {
		$allow_acceptance_emails = 'yes';
	}
	?>

	<table class="notification-settings" id="members-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _ex( 'Members', 'Member settings on notification settings page', 'buddypress' ) ?></th>
				<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
				<th class="no"><?php _e( 'No', 'buddypress' )?></th>
			</tr>
		</thead>

		<tbody>
			<tr id="members-notification-settings-invitation_accepted">
				<td></td>
				<td><?php _ex( 'Someone accepts your membership invitation', 'Member settings on notification settings page', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_members_invitation_accepted]" id="notification-members-invitation-accepted-yes" value="yes" <?php checked( $allow_acceptance_emails, 'yes', true ) ?>/><label for="notification-members-invitation-accepted-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_members_invitation_accepted]" id="notification-members-invitation-accepted-no" value="no" <?php checked( $allow_acceptance_emails, 'no', true ) ?>/><label for="notification-members-invitation-accepted-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>

			<?php

			/**
			 * Fires after the last table row on the members notification screen.
			 *
			 * @since 1.0.0
			 */
			do_action( 'members_screen_notification_settings' ); ?>

		</tbody>
	</table>

<?php
}
add_action( 'bp_notification_settings', 'members_screen_notification_settings' );
