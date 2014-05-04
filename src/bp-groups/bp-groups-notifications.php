<?php

/**
 * BuddyPress Groups Notification Functions
 *
 * These functions handle the recording, deleting and formatting of notifications
 * for the user and for this specific component.
 *
 * @package BuddyPress
 * @subpackage GroupsActivity
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** Emails ********************************************************************/

function groups_notification_group_updated( $group_id ) {

	$group    = groups_get_group( array( 'group_id' => $group_id ) );
	$subject  = bp_get_email_subject( array( 'text' => __( 'Group Details Updated', 'buddypress' ) ) );
	$user_ids = BP_Groups_Member::get_group_member_ids( $group->id );

	foreach ( (array) $user_ids as $user_id ) {
		if ( 'no' == bp_get_user_meta( $user_id, 'notification_groups_group_updated', true ) ) continue;

		$ud = bp_core_get_core_userdata( $user_id );

		// Set up and send the message
		$to = $ud->user_email;

		$group_link    = bp_get_group_permalink( $group );
		$settings_slug = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
		$settings_link = bp_core_get_user_domain( $user_id ) . $settings_slug . '/notifications/';

		$message = sprintf( __(
'Group details for the group "%1$s" were updated:

To view the group: %2$s

---------------------
', 'buddypress' ), $group->name, $group_link );

		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

		/* Send the message */
		$to      = apply_filters( 'groups_notification_group_updated_to', $to );
		$subject = apply_filters_ref_array( 'groups_notification_group_updated_subject', array( $subject, &$group ) );
		$message = apply_filters_ref_array( 'groups_notification_group_updated_message', array( $message, &$group, $group_link, $settings_link ) );

		wp_mail( $to, $subject, $message );

		unset( $message, $to );
	}

	do_action( 'bp_groups_sent_updated_email', $user_ids, $subject, '', $group_id );
}

function groups_notification_new_membership_request( $requesting_user_id, $admin_id, $group_id, $membership_id ) {

	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_add_notification( array(
			'user_id'           => $admin_id,
			'item_id'           => $group_id,
			'secondary_item_id' => $requesting_user_id,
			'component_name'    => buddypress()->groups->id,
			'component_action'  => 'new_membership_request'
		) );
	}

	if ( 'no' == bp_get_user_meta( $admin_id, 'notification_groups_membership_request', true ) )
		return false;

	// Username of the user requesting a membership: %1$s in mail
	$requesting_user_name = bp_core_get_user_displayname( $requesting_user_id );
	$group                = groups_get_group( array( 'group_id' => $group_id ) );

	// Group Administrator user's data
	$ud             = bp_core_get_core_userdata( $admin_id );
	$group_requests = bp_get_group_permalink( $group ) . 'admin/membership-requests';

	// Link to the user's profile who's requesting a membership: %3$s in mail
	$profile_link   = bp_core_get_user_domain( $requesting_user_id );

	$settings_slug  = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
	// Link to the group administrator email settings: %s in "disable notifications" part of the email
	$settings_link  = bp_core_get_user_domain( $admin_id ) . $settings_slug . '/notifications/';

	// Set up and send the message
	$to       = $ud->user_email;
	$subject  = bp_get_email_subject( array( 'text' => sprintf( __( 'Membership request for group: %s', 'buddypress' ), $group->name ) ) );

$message = sprintf( __(
'%1$s wants to join the group "%2$s".

Because you are the administrator of this group, you must either accept or reject the membership request.

To view all pending membership requests for this group, please visit:
%3$s

To view %4$s\'s profile: %5$s

---------------------
', 'buddypress' ), $requesting_user_name, $group->name, $group_requests, $requesting_user_name, $profile_link );

	// Only show the disable notifications line if the settings component is enabled
	if ( bp_is_active( 'settings' ) ) {
		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
	}

	/* Send the message */
	$to      = apply_filters( 'groups_notification_new_membership_request_to', $to );
	$subject = apply_filters_ref_array( 'groups_notification_new_membership_request_subject', array( $subject, &$group ) );
	$message = apply_filters_ref_array( 'groups_notification_new_membership_request_message', array( $message, &$group, $requesting_user_name, $profile_link, $group_requests, $settings_link ) );

	wp_mail( $to, $subject, $message );

	do_action( 'bp_groups_sent_membership_request_email', $admin_id, $subject, $message, $requesting_user_id, $group_id, $membership_id );
}

function groups_notification_membership_request_completed( $requesting_user_id, $group_id, $accepted = true ) {

	// Post a screen notification first.
	if ( bp_is_active( 'notifications' ) ) {

		$type = ! empty( $accepted ) ? 'membership_request_accepted' : 'membership_request_rejected' ;

		bp_notifications_add_notification( array(
			'user_id'           => $requesting_user_id,
			'item_id'           => $group_id,
			'component_name'    => buddypress()->groups->id,
			'component_action'  => $type
		) );
	}

	if ( 'no' == bp_get_user_meta( $requesting_user_id, 'notification_membership_request_completed', true ) )
		return false;

	$group = groups_get_group( array( 'group_id' => $group_id ) );

	$ud = bp_core_get_core_userdata($requesting_user_id);

	$group_link   = bp_get_group_permalink( $group );
	$settings_slug = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
	$settings_link = bp_core_get_user_domain( $requesting_user_id ) . $settings_slug . '/notifications/';

	// Set up and send the message
	$to       = $ud->user_email;

	if ( $accepted ) {
		$subject = bp_get_email_subject( array( 'text' => sprintf( __( 'Membership request for group "%s" accepted', 'buddypress' ), $group->name ) ) );
		$message = sprintf( __(
'Your membership request for the group "%1$s" has been accepted.

To view the group please login and visit: %2$s

---------------------
', 'buddypress' ), $group->name, $group_link );

	} else {
		$subject = bp_get_email_subject( array( 'text' => sprintf( __( 'Membership request for group "%s" rejected', 'buddypress' ), $group->name ) ) );
		$message = sprintf( __(
'Your membership request for the group "%1$s" has been rejected.

To submit another request please log in and visit: %2$s

---------------------
', 'buddypress' ), $group->name, $group_link );
	}

	// Only show the disable notifications line if the settings component is enabled
	if ( bp_is_active( 'settings' ) ) {
		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
	}

	/* Send the message */
	$to      = apply_filters( 'groups_notification_membership_request_completed_to', $to );
	$subject = apply_filters_ref_array( 'groups_notification_membership_request_completed_subject', array( $subject, &$group ) );
	$message = apply_filters_ref_array( 'groups_notification_membership_request_completed_message', array( $message, &$group, $group_link, $settings_link ) );

	wp_mail( $to, $subject, $message );

	do_action( 'bp_groups_sent_membership_approved_email', $requesting_user_id, $subject, $message, $group_id );
}
add_action( 'groups_membership_accepted', 'groups_notification_membership_request_completed', 10, 3 );
add_action( 'groups_membership_rejected', 'groups_notification_membership_request_completed', 10, 3 );

function groups_notification_promoted_member( $user_id, $group_id ) {

	if ( groups_is_user_admin( $user_id, $group_id ) ) {
		$promoted_to = __( 'an administrator', 'buddypress' );
		$type = 'member_promoted_to_admin';
	} else {
		$promoted_to = __( 'a moderator', 'buddypress' );
		$type = 'member_promoted_to_mod';
	}

	// Post a screen notification first.
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_add_notification( array(
			'user_id'           => $user_id,
			'item_id'           => $group_id,
			'component_name'    => buddypress()->groups->id,
			'component_action'  => $type,
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		) );
	}

	if ( 'no' == bp_get_user_meta( $user_id, 'notification_groups_admin_promotion', true ) )
		return false;

	$group         = groups_get_group( array( 'group_id' => $group_id ) );
	$ud            = bp_core_get_core_userdata($user_id);
	$group_link    = bp_get_group_permalink( $group );
	$settings_slug = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
	$settings_link = bp_core_get_user_domain( $user_id ) . $settings_slug . '/notifications/';

	// Set up and send the message
	$to       = $ud->user_email;
	$subject  = bp_get_email_subject( array( 'text' => sprintf( __( 'You have been promoted in the group: "%s"', 'buddypress' ), $group->name ) ) );
	$message  = sprintf( __(
'You have been promoted to %1$s for the group: "%2$s".

To view the group please visit: %3$s

---------------------
', 'buddypress' ), $promoted_to, $group->name, $group_link );

	// Only show the disable notifications line if the settings component is enabled
	if ( bp_is_active( 'settings' ) ) {
		$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
	}

	/* Send the message */
	$to      = apply_filters( 'groups_notification_promoted_member_to', $to );
	$subject = apply_filters_ref_array( 'groups_notification_promoted_member_subject', array( $subject, &$group ) );
	$message = apply_filters_ref_array( 'groups_notification_promoted_member_message', array( $message, &$group, $promoted_to, $group_link, $settings_link ) );

	wp_mail( $to, $subject, $message );

	do_action( 'bp_groups_sent_promoted_email', $user_id, $subject, $message, $group_id );
}
add_action( 'groups_promoted_member', 'groups_notification_promoted_member', 10, 2 );

function groups_notification_group_invites( &$group, &$member, $inviter_user_id ) {

	// @todo $inviter_up may be used for caching, test without it
	$inviter_ud   = bp_core_get_core_userdata( $inviter_user_id );
	$inviter_name = bp_core_get_userlink( $inviter_user_id, true, false, true );
	$inviter_link = bp_core_get_user_domain( $inviter_user_id );

	$group_link = bp_get_group_permalink( $group );

	if ( !$member->invite_sent ) {
		$invited_user_id = $member->user_id;

		// Post a screen notification first.
		if ( bp_is_active( 'notifications' ) ) {
			bp_notifications_add_notification( array(
				'user_id'           => $invited_user_id,
				'item_id'           => $group->id,
				'component_name'    => buddypress()->groups->id,
				'component_action'  => 'group_invite'
			) );
		}

		if ( 'no' == bp_get_user_meta( $invited_user_id, 'notification_groups_invite', true ) )
			return false;

		$invited_ud    = bp_core_get_core_userdata($invited_user_id);
		$settings_slug = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
		$settings_link = bp_core_get_user_domain( $invited_user_id ) . $settings_slug . '/notifications/';
		$invited_link  = bp_core_get_user_domain( $invited_user_id );
		$invites_link  = trailingslashit( $invited_link . bp_get_groups_slug() . '/invites' );

		// Set up and send the message
		$to       = $invited_ud->user_email;
		$subject  = bp_get_email_subject( array( 'text' => sprintf( __( 'You have an invitation to the group: "%s"', 'buddypress' ), $group->name ) ) );

		$message = sprintf( __(
'One of your friends %1$s has invited you to the group: "%2$s".

To view your group invites visit: %3$s

To view the group visit: %4$s

To view %5$s\'s profile visit: %6$s

---------------------
', 'buddypress' ), $inviter_name, $group->name, $invites_link, $group_link, $inviter_name, $inviter_link );

		// Only show the disable notifications line if the settings component is enabled
		if ( bp_is_active( 'settings' ) ) {
			$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
		}

		/* Send the message */
		$to      = apply_filters( 'groups_notification_group_invites_to', $to );
		$subject = apply_filters_ref_array( 'groups_notification_group_invites_subject', array( $subject, &$group ) );
		$message = apply_filters_ref_array( 'groups_notification_group_invites_message', array( $message, &$group, $inviter_name, $inviter_link, $invites_link, $group_link, $settings_link ) );

		wp_mail( $to, $subject, $message );

		do_action( 'bp_groups_sent_invited_email', $invited_user_id, $subject, $message, $group );
	}
}

/** Notifications *************************************************************/

/**
 * Format the BuddyBar/Toolbar notifications for the Groups component
 *
 * @since BuddyPress (1.0)
 * @param string $action The kind of notification being rendered
 * @param int $item_id The primary item id
 * @param int $secondary_item_id The secondary item id
 * @param int $total_items The total number of messaging-related notifications waiting for the user
 * @param string $format 'string' for BuddyBar-compatible notifications; 'array' for WP Toolbar
 */
function groups_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

	switch ( $action ) {
		case 'new_membership_request':
			$group_id = $item_id;
			$requesting_user_id = $secondary_item_id;

			$group = groups_get_group( array( 'group_id' => $group_id ) );
			$group_link = bp_get_group_permalink( $group );

			// Set up the string and the filter
			// Because different values are passed to the filters, we'll return the
			// values inline
			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( '%1$d new membership requests for the group "%2$s"', 'buddypress' ), (int) $total_items, $group->name );
				$filter = 'bp_groups_multiple_new_membership_requests_notification';
				$notification_link = $group_link . 'admin/membership-requests/?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . __( 'Group Membership Requests', 'buddypress' ) . '">' . $text . '</a>', $group_link, $total_items, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $total_items, $group->name, $text, $notification_link );
				}
			} else {
				$user_fullname = bp_core_get_user_displayname( $requesting_user_id );
				$text = sprintf( __( '%s requests group membership', 'buddypress' ), $user_fullname );
				$filter = 'bp_groups_single_new_membership_request_notification';
				$notification_link = $group_link . 'admin/membership-requests/?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . sprintf( __( '%s requests group membership', 'buddypress' ), $user_fullname ) . '">' . $text . '</a>', $group_link, $user_fullname, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $user_fullname, $group->name, $text, $notification_link );
				}
			}

			break;

		case 'membership_request_accepted':
			$group_id = $item_id;

			$group = groups_get_group( array( 'group_id' => $group_id ) );
			$group_link = bp_get_group_permalink( $group );

			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( '%d accepted group membership requests', 'buddypress' ), (int) $total_items, $group->name );
				$filter = 'bp_groups_multiple_membership_request_accepted_notification';
				$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . __( 'Groups', 'buddypress' ) . '">' . $text . '</a>', $total_items, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $total_items, $group->name, $text, $notification_link );
				}
			} else {
				$text = sprintf( __( 'Membership for group "%s" accepted', 'buddypress' ), $group->name );
				$filter = 'bp_groups_single_membership_request_accepted_notification';
				$notification_link = $group_link . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $group->name, $text, $notification_link );
				}
			}

			break;

		case 'membership_request_rejected':
			$group_id = $item_id;

			$group = groups_get_group( array( 'group_id' => $group_id ) );
			$group_link = bp_get_group_permalink( $group );

			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( '%d rejected group membership requests', 'buddypress' ), (int) $total_items, $group->name );
				$filter = 'bp_groups_multiple_membership_request_rejected_notification';
				$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . __( 'Groups', 'buddypress' ) . '">' . $text . '</a>', $total_items, $group->name );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $total_items, $group->name, $text, $notification_link );
				}
			} else {
				$text = sprintf( __( 'Membership for group "%s" rejected', 'buddypress' ), $group->name );
				$filter = 'bp_groups_single_membership_request_rejected_notification';
				$notification_link = $group_link . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $group->name, $text, $notification_link );
				}
			}

			break;

		case 'member_promoted_to_admin':
			$group_id = $item_id;

			$group = groups_get_group( array( 'group_id' => $group_id ) );
			$group_link = bp_get_group_permalink( $group );

			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( 'You were promoted to an admin in %d groups', 'buddypress' ), (int) $total_items );
				$filter = 'bp_groups_multiple_member_promoted_to_admin_notification';
				$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . __( 'Groups', 'buddypress' ) . '">' . $text . '</a>', $total_items, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $total_items, $text, $notification_link );
				}
			} else {
				$text = sprintf( __( 'You were promoted to an admin in the group "%s"', 'buddypress' ), $group->name );
				$filter = 'bp_groups_single_member_promoted_to_admin_notification';
				$notification_link = $group_link . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $group->name, $text, $notification_link );
				}
			}

			break;

		case 'member_promoted_to_mod':
			$group_id = $item_id;

			$group = groups_get_group( array( 'group_id' => $group_id ) );
			$group_link = bp_get_group_permalink( $group );

			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( 'You were promoted to a mod in %d groups', 'buddypress' ), (int) $total_items );
				$filter = 'bp_groups_multiple_member_promoted_to_mod_notification';
				$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . __( 'Groups', 'buddypress' ) . '">' . $text . '</a>', $total_items, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $total_items, $text, $notification_link );
				}
			} else {
				$text = sprintf( __( 'You were promoted to a mod in the group "%s"', 'buddypress' ), $group->name );
				$filter = 'bp_groups_single_member_promoted_to_mod_notification';
				$notification_link = $group_link . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $group->name, $text, $notification_link );
				}
			}

			break;

		case 'group_invite':
			$group_id = $item_id;
			$group = groups_get_group( array( 'group_id' => $group_id ) );
			$group_link = bp_get_group_permalink( $group );

			$notification_link = bp_loggedin_user_domain() . bp_get_groups_slug() . '/invites/?n=1';

			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( 'You have %d new group invitations', 'buddypress' ), (int) $total_items );
				$filter = 'bp_groups_multiple_group_invite_notification';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . __( 'Group Invites', 'buddypress' ) . '">' . $text . '</a>', $total_items, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $total_items, $text, $notification_link );
				}
			} else {
				$text = sprintf( __( 'You have an invitation to the group: %s', 'buddypress' ), $group->name );
				$filter = 'bp_groups_single_group_invite_notification';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $group->name, $text, $notification_link );
				}
			}

			break;
	}

	do_action( 'groups_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return false;
}

/**
 * Remove all notifications for any member belonging to a specific group
 *
 * @since BuddyPress (1.9.0)
 */
function bp_groups_delete_group_delete_all_notifications( $group_id ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_delete_all_notifications_by_type( $group_id, buddypress()->groups->id );
	}
}
add_action( 'groups_delete_group', 'bp_groups_delete_group_delete_all_notifications', 10 );

/**
 * When a demotion takes place, delete any corresponding promotion notifications.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_groups_delete_promotion_notifications( $user_id = 0, $group_id = 0 ) {
	if ( bp_is_active( 'notifications' ) && ! empty( $group_id ) && ! empty( $user_id ) ) {
		bp_notifications_delete_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'member_promoted_to_admin' );
		bp_notifications_delete_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'member_promoted_to_mod' );
	}
}
add_action( 'groups_demoted_member', 'bp_groups_delete_promotion_notifications', 10, 2 );

/**
 * Mark notifications read when a member accepts a group invitation
 *
 * @since BuddyPress (1.9.0)
 * @param int $user_id
 * @param int $group_id
 */
function bp_groups_accept_invite_mark_notifications( $user_id, $group_id ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'group_invite' );
	}
}
add_action( 'groups_accept_invite', 'bp_groups_accept_invite_mark_notifications', 10, 2 );
add_action( 'groups_reject_invite', 'bp_groups_accept_invite_mark_notifications', 10, 2 );
add_action( 'groups_delete_invite', 'bp_groups_accept_invite_mark_notifications', 10, 2 );

/**
 * Mark notifications read when a member views their group memberships
 *
 * @since BuddyPress (1.9.0)
 */
function bp_groups_screen_my_groups_mark_notifications() {

	// Delete group request notifications for the user
	if ( isset( $_GET['n'] ) && bp_is_active( 'notifications' ) ) {

		// Get the necessary ID's
		$group_id = buddypress()->groups->id;
		$user_id  = bp_loggedin_user_id();

		// Mark notifications read
		bp_notifications_mark_notifications_by_type( $user_id, $group_id, 'membership_request_accepted' );
		bp_notifications_mark_notifications_by_type( $user_id, $group_id, 'membership_request_rejected' );
		bp_notifications_mark_notifications_by_type( $user_id, $group_id, 'member_promoted_to_mod'      );
		bp_notifications_mark_notifications_by_type( $user_id, $group_id, 'member_promoted_to_admin'    );
	}
}
add_action( 'groups_screen_my_groups',  'bp_groups_screen_my_groups_mark_notifications', 10 );
add_action( 'groups_screen_group_home', 'bp_groups_screen_my_groups_mark_notifications', 10 );

/**
 * Mark group invitation notifications read when a member views their invitations
 *
 * @since BuddyPress (1.9.0)
 */
function bp_groups_screen_invites_mark_notifications() {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->groups->id, 'group_invite' );
	}
}
add_action( 'groups_screen_group_invites', 'bp_groups_screen_invites_mark_notifications', 10 );

/**
 * Mark group join requests read when an admin or moderator visits the group
 * administration area.
 *
 * @since BuddyPress (1.9.0)
 * @param int $group_id
 */
function bp_groups_screen_group_admin_requests_mark_notifications( $group_id ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->groups->id, 'new_membership_request' );
	}
}
add_action( 'groups_screen_group_admin_requests', 'bp_groups_screen_group_admin_requests_mark_notifications', 10 );

/**
 * Delete new group membership notifications when a user is being deleted.
 *
 * @since BuddyPress (1.9.0)
 * @param int $user_id
 */
function bp_groups_remove_data_for_user_notifications( $user_id ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_delete_notifications_from_user( $user_id, buddypress()->groups->id, 'new_membership_request' );
	}
}
add_action( 'groups_remove_data_for_user', 'bp_groups_remove_data_for_user_notifications', 10 );
