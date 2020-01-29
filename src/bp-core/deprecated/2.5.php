<?php
/**
 * Deprecated functions.
 *
 * @deprecated 2.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set "From" name in outgoing email to the site name.
 *
 * @since 1.0.0
 * @deprecated 2.5.0 Not used. Was hooked to WordPress' "wp_mail_from_name" action.
 *                   Use the "bp_email_get_from" action instead.
 *
 * @return string The blog name for the root blog.
 */
function bp_core_email_from_name_filter() {
	_deprecated_function( __FUNCTION__, '2.5' );

	/**
	 * Filters the "From" name in outgoing email to the site name.
	 *
	 * @since 1.2.0
	 * @deprecated 2.5.0 Not used.
	 *
	 * @param string $value Value to set the "From" name to.
	 */
	return apply_filters( 'bp_core_email_from_name_filter', bp_get_option( 'blogname', 'WordPress' ) );
}

/**
 * Add support for pre-2.5 email filters.
 *
 * @since 2.5.0
 *
 * @param mixed $value
 * @param string $property Name of property.
 * @param string $transform Return value transformation. Unused.
 * @param BP_Email $email Email object reference.
 * @return mixed
 */
function bp_core_deprecated_email_filters( $value, $property, $transform, $email ) {
	$pre_2_5_emails = array(
		'activity-at-message',
		'activity-comment',
		'activity-comment-author',
		'core-user-registration',
		'core-user-registration-with-blog',
		'friends-request',
		'friends-request-accepted',
		'groups-at-message',
		'groups-details-updated',
		'groups-invitation',
		'groups-member-promoted',
		'groups-membership-request',
		'groups-membership-request-accepted',
		'groups-membership-request-rejected',
		'messages-unread',
		'settings-verify-email-change',
	);

	remove_filter( 'bp_email_get_property', 'bp_core_deprecated_email_filters', 20 );
	$email_type = $email->get( 'type' );
	$tokens     = $email->get( 'tokens' );
	add_filter( 'bp_email_get_property', 'bp_core_deprecated_email_filters', 20, 4 );

	// Backpat for pre-2.5 emails only.
	if ( ! in_array( $email_type, $pre_2_5_emails, true ) ) {
		return $value;
	}

	$original_value = $value;
	$to_changed     = false;

	if ( $property === 'to' && $original_value ) {
		$to_changed     = true;

		$value          = array_shift( $original_value );
		$recipient_name = $value->get_name();     // Value - name.
		$value          = $value->get_address();  // Key   - email.
	}

	if ( $email_type === 'activity-comment' ) {
		if ( $property === 'to' ) {
			/**
			 * Filters the user email that the new comment notification will be sent to.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value
			 */
			$value = apply_filters( 'bp_activity_new_comment_notification_to', $value );

		} elseif ( $property === 'subject' ) {
			/**
			 * Filters the new comment notification subject that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value       Email notification subject text.
			 * @param string $poster_name Name of the person who made the comment.
			 */
			$value = apply_filters( 'bp_activity_new_comment_notification_subject', $value, $tokens['poster.name'] );

		} elseif ( $property === 'content' ) {
			/**
			 * Filters the new comment notification message that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $settings_link argument unset and deprecated.
			 *
			 * @param string $value         Email notification message text.
			 * @param string $poster_name   Name of the person who made the comment.
			 * @param string $content       Content of the comment.
			 * @param string $thread_link   URL permalink for the activity thread.
			 * @param string $deprecated    Removed in 2.5; now an empty string.
			 */
			$value = apply_filters( 'bp_activity_new_comment_notification_message', $value, $tokens['poster.name'], $tokens['content'], $tokens['thread.url'], '' );
		}

	} elseif ( $email_type === 'activity-comment-author' ) {
		if ( $property === 'to' ) {
			/**
			 * Filters the user email that the new comment reply notification will be sent to.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value
			 */
			$value = apply_filters( 'bp_activity_new_comment_notification_comment_author_to', $value );

		} elseif ( $property === 'subject' ) {
			/**
			 * Filters the new comment reply notification subject that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value       Email notification subject text.
			 * @param string $poster_name Name of the person who made the comment.
			 */
			$value = apply_filters( 'bp_activity_new_comment_notification_comment_author_subject', $value, $tokens['poster.name'] );

		} elseif ( $property === 'content' ) {
			/**
			 * Filters the new comment reply notification message that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $settings_link argument unset and deprecated.
			 *
			 * @param string $value         Email notification message text.
			 * @param string $poster_name   Name of the person who made the comment.
			 * @param string $content       Content of the comment.
			 * @param string $deprecated    Removed in 2.5; now an empty string.
			 * @param string $thread_link   URL permalink for the activity thread.
			 */
			$value = apply_filters( 'bp_activity_new_comment_notification_comment_author_message', $value, $tokens['poster.name'], $tokens['content'], '', $tokens['thread.url'] );
		}

	} elseif ( $email_type === 'activity-at-message' || $email_type === 'groups-at-message' ) {
		if ( $property === 'to' ) {
			/**
			 * Filters the user email that the @mention notification will be sent to.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value
			 */
			$value = apply_filters( 'bp_activity_at_message_notification_to', $value );

		} elseif ( $property === 'subject' ) {
			/**
			 * Filters the @mention notification subject that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value       Email notification subject text.
			 * @param string $poster_name Name of the person who made the @mention.
			 */
			$value = apply_filters( 'bp_activity_at_message_notification_subject', $value, $tokens['poster.name'] );

		} elseif ( $property === 'content' ) {
			/**
			 * Filters the @mention notification message that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $settings_link argument unset and deprecated.
			 *
			 * @param string $message       Email notification message text.
			 * @param string $poster_name   Name of the person who made the @mention.
			 * @param string $content       Content of the @mention.
			 * @param string $message_link  URL permalink for the activity message.
			 * @param string $deprecated    Removed in 2.5; now an empty string.
			 */
			$value = apply_filters( 'bp_activity_at_message_notification_message', $value, $tokens['poster.name'], $tokens['content'], $tokens['mentioned.url'], '' );
		}

	} elseif ( $email_type === 'core-user-registration' ) {
		// Filters are different according to the WordPress config.
		if ( is_multisite() ) {
			$registration_filters = array(
				'to'      => 'bp_core_activation_signup_user_notification_to',
				'subject' => 'bp_core_activation_signup_user_notification_subject',
				'content' => 'bp_core_activation_signup_user_notification_message',
			);
		} else {
			$registration_filters = array(
				'to'      => 'bp_core_signup_send_validation_email_to',
				'subject' => 'bp_core_signup_send_validation_email_subject',
				'content' => 'bp_core_signup_send_validation_email_message',
			);
		}

		if ( $property === 'to' ) {
			/**
			 * Filters the email that the notification is going to upon successful registration without blog.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $meta argument unset and deprecated.
			 *
			 * @param string $value              User email the notification is being sent to.
			 * @param string $recipient_username The user's login name.
			 * @param array $value               User email the notification is being sent to (again).
			 *                                   Array key is email address, value is the name.
			 * @param string $key                The activation key created in wpmu_signup_blog().
			 * @param array $meta                Removed in 2.5; now an empty array.
			 */
			$value = apply_filters( $registration_filters['to'], $value, $tokens['recipient.username'], $value, $tokens['key'], array() );

		} elseif ( $property === 'subject' ) {
			/**
			 * Filters the subject that the notification uses upon successful registration without blog.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $meta argument unset and deprecated.
			 *
			 * @param string $value              Email notification subject text.
			 * @param string $recipient_username The user's login name.
			 * @param string $recipient_email    The user's email address.
			 * @param string $key                The activation key created in wpmu_signup_blog().
			 * @param array $meta                Removed in 2.5; now an empty array.
			 */
			$value = apply_filters( $registration_filters['subject'], $value, $tokens['recipient.username'], $tokens['recipient.email'], $tokens['key'], array() );

		} elseif ( $property === 'content' ) {
			/**
			 * Filters the message that the notification uses upon successful registration without blog.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $meta argument unset and deprecated.
			 *
			 * @param string $value              The message to use.
			 * @param string $recipient_username The user's login name.
			 * @param string $recipient_email    The user's email address.
			 * @param string $key                The activation key created in wpmu_signup_blog().
			 * @param array $meta                Removed in 2.5; now an empty array.
			 */
			$value = apply_filters( $registration_filters['content'], $value, $tokens['recipient.username'], $tokens['recipient.email'], $tokens['key'], array() );
		}

	} elseif ( $email_type === 'core-user-registration-with-blog' ) {
		if ( $property === 'to' ) {
			/**
			 * Filters the email that the notification is going to upon successful registration with blog.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $meta argument unset and deprecated.
			 *
			 * @param string $value              User email the notification is being sent to.
			 * @param string $domain             The new blog domain.
			 * @param string $path               The new blog path.
			 * @param string $title              The site title.
			 * @param string $recipient_username The user's login name.
			 * @param string $recipient_email    The user's email address.
			 * @param string $key                The activation key created in wpmu_signup_blog().
			 * @param array  $meta               Removed in 2.5; now an empty array.
			 */
			$value = apply_filters( 'bp_core_activation_signup_blog_notification_to', $value, $tokens['domain'], $tokens['path'], $tokens['title'], $tokens['recipient.username'], $tokens['recipient.email'], $tokens['key_blog'], array() );

		} elseif ( $property === 'subject' ) {
			/**
			 * Filters the subject that the notification uses upon successful registration with blog.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $meta argument unset and deprecated.
			 *
			 * @param string $value              The subject to use.
			 * @param string $domain             The new blog domain.
			 * @param string $path               The new blog path.
			 * @param string $title              The site title.
			 * @param string $recipient_username The user's login name.
			 * @param string $recipient_email    The user's email address.
			 * @param string $key                The activation key created in wpmu_signup_blog().
			 * @param array  $meta               Removed in 2.5; now an empty array.
			 */
			$value = apply_filters( 'bp_core_activation_signup_blog_notification_subject', $value, $tokens['domain'], $tokens['path'], $tokens['title'], $tokens['recipient.username'], $tokens['recipient.email'], $tokens['key_blog'], array() );

		} elseif ( $property === 'content' ) {
			/**
			 * Filters the message that the notification uses upon successful registration with blog.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $meta argument unset and deprecated.
			 *
			 * @param string $value              The message to use.
			 * @param string $domain             The new blog domain.
			 * @param string $path               The new blog path.
			 * @param string $title              The site title.
			 * @param string $recipient_username The user's login name.
			 * @param string $recipient_email    The user's email address.
			 * @param string $key                The activation key created in wpmu_signup_blog().
			 * @param array  $meta               Removed in 2.5; now an empty array.
			 */
			$value = apply_filters( 'bp_core_activation_signup_blog_notification_message', $value, $tokens['domain'], $tokens['path'], $tokens['title'], $tokens['recipient.username'], $tokens['recipient.email'], $tokens['key_blog'], array() );
		}

	} elseif ( $email_type === 'friends-request' ) {
		if ( $property === 'to' ) {
			/**
			 * Filters the email address for who is getting the friend request.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value
			 */
			$value = apply_filters( 'friends_notification_new_request_to', $value );

		} elseif ( $property === 'subject' ) {
			/**
			 * Filters the subject for the friend request email.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value          Subject line to be used in friend request email.
			 * @param string $initiator_name Name of the person requesting friendship.
			 */
			$value = apply_filters( 'friends_notification_new_request_subject', $value, $tokens['initiator.name'] );

		} elseif ( $property === 'content' ) {
			/**
			 * Filters the message for the friend request email.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $settings_link argument unset and deprecated.
			 *
			 * @param string $value             Message to be used in friend request email.
			 * @param string $initiator_name    Name of the person requesting friendship.
			 * @param string $initiator_link    Profile link of person requesting friendship.
			 * @param string $all_requests_link User's friends request management link.
			 * @param string $settings_link     Removed in 2.5; now an empty string.
			 */
			$value = apply_filters( 'friends_notification_new_request_message', $value, $tokens['initiator.name'], $tokens['initiator.url'], $tokens['friend-requests.url'], '' );
		}

	} elseif ( $email_type === 'friends-request-accepted' ) {
		if ( $property === 'to' ) {
			/**
			 * Filters the email address for whose friend request got accepted.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value Email address for whose friend request got accepted.
			 */
			$value = apply_filters( 'friends_notification_accepted_request_to', $value );

		} elseif ( $property === 'subject' ) {
			/**
			 * Filters the subject for the friend request accepted email.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value       Subject line to be used in friend request accepted email.
			 * @param string $friend_name Name of the person who accepted the friendship request.
			 */
			$value = apply_filters( 'friends_notification_accepted_request_subject', $value, $tokens['friend.name'] );

		} elseif ( $property === 'content' ) {
			/**
			 * Filters the message for the friend request accepted email.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $settings_link argument unset and deprecated.
			 *
			 * @param string $value         Message to be used in friend request email.
			 * @param string $friend_name   Name of the person who accepted the friendship request.
			 * @param string $friend_link   Profile link of person who accepted the friendship request.
			 * @param string $settings_link Removed in 2.5; now an empty string.
			 */
			$value = apply_filters( 'friends_notification_accepted_request_message', $value, $tokens['friend.name'], $tokens['friendship.url'], '' );
		}

	} elseif ( $email_type === 'groups-details-updated' ) {
		if ( $property === 'to' ) {
			/**
			 * Filters the user email that the group update notification will be sent to.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value User email the notification is being sent to.
			 */
			$value = apply_filters( 'groups_notification_group_updated_to', $value );

		} elseif ( $property === 'subject' ) {
			/**
			 * Filters the group update notification subject that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string          $value Email notification subject text.
			 * @param BP_Groups_Group $group Object holding the current group instance. Passed by reference.
			 */
			$value = apply_filters_ref_array( 'groups_notification_group_updated_subject', array( $value, &$tokens['group'] ) );

		} elseif ( $property === 'content' ) {
			/**
			 * Filters the group update notification message that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $settings_link argument unset and deprecated.
			 *
			 * @param string          $value         Email notification message text.
			 * @param BP_Groups_Group $group         Object holding the current group instance. Passed by reference.
			 * @param string          $group_link    URL permalink to the group that was updated.
			 * @param string          $settings_link Removed in 2.5; now an empty string.
			 */
			$value = apply_filters_ref_array( 'groups_notification_group_updated_message', array( $value, &$tokens['group'], $tokens['group.url'], '' ) );
		}

	} elseif ( $email_type === 'groups-invitation' ) {
		if ( $property === 'to' ) {
			/**
			 * Filters the user email that the group invite notification will be sent to.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value User email the invite notification is being sent to.
			 */
			$value = apply_filters( 'groups_notification_group_invites_to', $value );

		} elseif ( $property === 'subject' ) {
			/**
			 * Filters the group invite notification subject that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string          $value Invite notification email subject text.
			 * @param BP_Groups_Group $group Object holding the current group instance. Passed by reference.
			 */
			$value = apply_filters_ref_array( 'groups_notification_group_invites_subject', array( $value, &$tokens['group'] ) );

		} elseif ( $property === 'content' ) {
			/**
			 * Filters the group invite notification message that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $settings_link argument unset and deprecated.
			 *
			 * @param string          $value         Invite notification email message text.
			 * @param BP_Groups_Group $group         Object holding the current group instance. Passed by reference.
			 * @param string          $inviter_name  Username for the person doing the inviting.
			 * @param string          $inviter_link  Profile link for the person doing the inviting.
			 * @param string          $invites_link  URL permalink for the invited user's invite management screen.
			 * @param string          $group_link    URL permalink for the group that the invite was related to.
			 * @param string          $settings_link Removed in 2.5; now an empty string.
			 */
			$value = apply_filters_ref_array( 'groups_notification_group_invites_message', array( $value, &$tokens['group'], $tokens['inviter.name'], $tokens['inviter.url'], $tokens['invites.url'], $tokens['group.url'], '' ) );
		}

	} elseif ( $email_type === 'groups-member-promoted' ) {
		if ( $property === 'to' ) {
			/**
			 * Filters the user email that the group promotion notification will be sent to.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value User email the promotion notification is being sent to.
			 */
			$value = apply_filters( 'groups_notification_promoted_member_to', $value );

		} elseif ( $property === 'subject' ) {
			/**
			 * Filters the group promotion notification subject that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string          $value Promotion notification email subject text.
			 * @param BP_Groups_Group $group Object holding the current group instance. Passed by reference.
			 */
			$value = apply_filters_ref_array( 'groups_notification_promoted_member_subject', array( $value, &$tokens['group'] ) );

		} elseif ( $property === 'content' ) {
			/**
			 * Filters the group promotion notification message that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $settings_link argument unset and deprecated.
			 *
			 * @param string          $value         Promotion notification email message text.
			 * @param BP_Groups_Group $group         Object holding the current group instance. Passed by reference.
			 * @param string          $promoted_to   Role that the user was promoted to within the group.
			 * @param string          $group_link    URL permalink for the group that the promotion was related to.
			 * @param string          $settings_link Removed in 2.5; now an empty string.
			 */
			$value = apply_filters_ref_array( 'groups_notification_promoted_member_message', array( $value, &$tokens['group'], $tokens['promoted_to'], $tokens['group.url'], '' ) );
		}

	} elseif ( $email_type === 'groups-membership-request' ) {
		if ( $property === 'to' ) {
			/**
			 * Filters the user email that the group membership request will be sent to.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value User email the request is being sent to.
			 */
			$value = apply_filters( 'groups_notification_new_membership_request_to', $value );

		} elseif ( $property === 'subject' ) {
			/**
			 * Filters the group membership request subject that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string          $value Membership request email subject text.
			 * @param BP_Groups_Group $group Object holding the current group instance. Passed by reference.
			 */
			$value = apply_filters_ref_array( 'groups_notification_new_membership_request_subject', array( $value, &$tokens['group'] ) );

		} elseif ( $property === 'content' ) {
			/**
			 * Filters the group membership request message that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $settings_link argument unset and deprecated.
			 *
			 * @param string          $value                Membership request email message text.
			 * @param BP_Groups_Group $group                Object holding the current group instance. Passed by reference.
			 * @param string          $requesting_user_name Username of who is requesting membership.
			 * @param string          $profile_link         URL permalink for the profile for the user requesting membership.
			 * @param string          $group_requests       URL permalink for the group requests screen for group being requested membership to.
			 * @param string          $deprecated           Removed in 2.5; now an empty string.
			 */
			$value = apply_filters_ref_array( 'groups_notification_new_membership_request_message', array( $value, &$tokens['group'], $tokens['requesting-user.name'], $tokens['profile.url'], $tokens['group-requests.url'], '' ) );
		}

	} elseif ( $email_type === 'groups-membership-request-accepted' || $email_type === 'groups-membership-request-rejected' ) {
		if ( $property === 'to' ) {
			/**
			 * Filters the user email that the group membership request result will be sent to.
			 *
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string $value User email the request is being sent to.
			 */
			$value = apply_filters( 'groups_notification_membership_request_completed_to', $value );

		} elseif ( $property === 'subject' ) {
			/**
			 * Filters the group membership request result subject that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *
			 * @param string          $value Membership request result email subject text.
			 * @param BP_Groups_Group $group Object holding the current group instance. Passed by reference.
			 */
			$value = apply_filters_ref_array( 'groups_notification_membership_request_completed_subject', array( $value, &$tokens['group'] ) );

		} elseif ( $property === 'content' ) {
			/**
			 * Filters the group membership request result message that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $settings_link argument unset and deprecated.
			 *
			 * @param string          $value      Membership request result email message text.
			 * @param BP_Groups_Group $group      Object holding the current group instance. Passed by reference.
			 * @param string          $group_link URL permalink for the group that was requested membership for.
			 * @param string          $deprecated Removed in 2.5; now an empty string.
			 */
			$value = apply_filters_ref_array( 'groups_notification_membership_request_completed_message', array( $value, &$tokens['group'], $tokens['group.url'], '' ) );
		}

	} elseif ( $email_type === 'messages-unread' ) {
		if ( $property === 'to' ) {
			/**
			 * Filters the user email that the message notification will be sent to.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $ud argument unset and deprecated.
			 *
			 * @param string $value      User email the message notification is being sent to.
			 * @param bool   $deprecated Removed in 2.5; now a bool (false).
			 */
			$value = apply_filters( 'messages_notification_new_message_to', $value, false );

		} elseif ( $property === 'subject' ) {
			/**
			 * Filters the message notification subject that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $ud argument unset and deprecated.
			 *
			 * @param string $value       Email notification subject text.
			 * @param string $sender_name Name of the person who sent the message.
			 * @param bool   $deprecated  Removed in 2.5; now a bool (false).
			 */
			$value = apply_filters( 'messages_notification_new_message_subject', $value, $tokens['sender.name'], false );

		} elseif ( $property === 'content' ) {
			/**
			 * Filters the message notification message that will be sent to user.
			 *
			 * @since 1.2.0
			 * @deprecated 2.5.0 Use the filters in BP_Email.
			 *                   $settings_link and $ud arguments unset and deprecated.
			 *
			 * @param string $value        Email notification message text.
			 * @param string $sender_name  Name of the person who sent the message.
			 * @param string $usersubjec   Email notification subject text.
			 * @param string $content      Content of the message.
			 * @param string $message_link URL permalink for the message.
			 * @param string $deprecated   Removed in 2.5; now an empty string.
			 * @param bool   $deprecated   Removed in 2.5; now a bool (false).
			 */
			$value = apply_filters( 'messages_notification_new_message_message', $value, $tokens['sender.name'], $tokens['usersubject'], $tokens['content'], $tokens['message.url'], '', false );
		}

	} elseif ( $email_type === 'settings-verify-email-change' ) {
		if ( $property === 'content' ) {
			/**
			 * Filter the email text sent when a user changes emails.
			 *
			 * @since 2.1.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $update_user argument unset and deprecated.
			 *
			 * @param string  $value           Text of the email.
			 * @param string  $recipient_email New user email that the current user has changed to.
			 * @param string  $old_user_email  Existing email address for the current user.
			 * @param bool    $deprecated      Removed in 2.5; now a bool (false).
			 */
			$value = apply_filters( 'bp_new_user_email_content', $value, $tokens['recipient.email'], $tokens['old-user.email'], false );
		}
	}

	if ( $property === 'to' && $to_changed ) {
		// We normally apart $to, so we often need to rebuild it.
		array_unshift( $original_value, new BP_Email_Recipient( $value, $recipient_name ) );
		$value = $original_value;
	}

	return $value;
}
add_filter( 'bp_email_get_property', 'bp_core_deprecated_email_filters', 20, 4 );

/**
 * Add support for pre-2.5 email actions.
 *
 * @since 2.5.0
 *
 * @param bool|WP_Error $delivery_status Bool if the email was sent or not.
 *                                       If a WP_Error, there was a failure.
 * @param BP_Email $email Email object reference.
 * @return mixed
 */
function bp_core_deprecated_email_actions( $delivery_status, $email ) {
	$pre_2_5_emails = array(
		'activity-comment',
		'activity-comment-author',
		'core-user-registration',
		'core-user-registration-with-blog',
		'friends-request',
		'friends-request-accepted',
		'groups-details-updated',
		'groups-invitation',
		'groups-member-promoted',
		'groups-membership-request',
		'groups-membership-request-accepted',
		'groups-membership-request-rejected',
		'messages-unread',
		'settings-verify-email-change',
	);

	remove_action( 'bp_send_email_success', 'bp_core_deprecated_email_actions', 20 );
	$email_content = $email->get( 'content' );
	$email_subject = $email->get( 'subject' );
	$email_type    = $email->get( 'type' );
	$tokens        = $email->get( 'tokens' );
	add_action( 'bp_send_email_success', 'bp_core_deprecated_email_actions', 20, 2 );

	// Backpat for pre-2.5 emails only.
	if ( ! in_array( $email_type, $pre_2_5_emails, true ) ) {
		return;
	}

	if ( $email_type === 'activity-comment' ) {
		/**
		 * Fires after the sending of a reply to an update email notification.
		 *
		 * @since 1.5.0
		 * @deprecated 2.5.0 Use the filters in BP_Email. $params argument unset and deprecated.
		 *
		 * @param int    $user_id       ID of the original activity item author.
		 * @param string $email_subject Email notification subject text.
		 * @param string $email_content Email notification message text.
		 * @param int    $comment_id    ID for the newly received comment.
		 * @param int    $commenter_id  ID of the user who made the comment.
		 * @param array  $deprecated    Removed in 2.5; now an empty array.
		 */
		do_action( 'bp_activity_sent_reply_to_update_email', $tokens['original_activity.user_id'], $email_subject, $email_content, $tokens['comment.id'], $tokens['commenter.id'], array() );

	} elseif ( $email_type === 'activity-comment-author' ) {
		/**
		 * Fires after the sending of a reply to a reply email notification.
		 *
		 * @since 1.5.0
		 * @deprecated 2.5.0 Use the filters in BP_Email. $params argument unset and deprecated.
		 *
		 * @param int    $user_id       ID of the parent activity item author.
		 * @param string $email_subject Email notification subject text.
		 * @param string $email_content Email notification message text.
		 * @param int    $comment_id    ID for the newly received comment.
		 * @param int    $commenter_id  ID of the user who made the comment.
		 * @param array  $deprecated    Removed in 2.5; now an empty array.
		 */
		do_action( 'bp_activity_sent_reply_to_reply_email', $tokens['parent-comment-user.id'], $email_subject, $email_content, $tokens['comment.id'], $tokens['commenter.id'], array() );

	} elseif ( $email_type === 'core-user-registration' ) {
		if ( ! empty( $tokens['user.id'] ) ) {
			/**
			 * Fires after the sending of activation email to a newly registered user.
			 *
			 * @since 1.5.0
			 *
			 * @param string $email_subject   Subject for the sent email.
			 * @param string $email_content   Message for the sent email.
			 * @param int    $user_id         ID of the new user.
			 * @param string $recipient_email Email address of the new user.
			 * @param string $key             Activation key.
			 */
			do_action( 'bp_core_sent_user_validation_email', $email_subject, $email_content, $tokens['user.id'], $tokens['recipient.email'], $tokens['key'] );

		} else {
			/**
			 * Fires after the sending of the notification to new users for successful registration without blog.
			 *
			 * @since 1.5.0
			 * @deprecated 2.5.0 Use the filters in BP_Email. $meta argument unset and deprecated.
			 *
			 * @param string $admin_email        Admin Email address for the site.
			 * @param string $email_subject      Subject used in the notification email.
			 * @param string $email_content      Message used in the notification email.
			 * @param string $recipient_username The user's login name.
			 * @param string $recipient_email    The user's email address.
			 * @param string $key                The activation key created in wpmu_signup_blog().
			 * @param array  $meta               Removed in 2.5; now an empty array.
			 */
			do_action( 'bp_core_sent_user_signup_email', bp_get_option( 'admin_email' ), $email_subject, $email_content, $tokens['recipient.username'], $tokens['recipient.email'], $tokens['key'], array() );
		}

	} elseif ( $email_type === 'core-user-registration-with-blog' ) {
		/**
		 * Fires after the sending of the notification to new users for successful registration with blog.
		 *
		 * @since 1.5.0
		 * @deprecated 2.5.0 Use the filters in BP_Email. $meta argument unset and deprecated.
		 *
		 * @param string $admin_email        Admin Email address for the site.
		 * @param string $email_subject      Subject used in the notification email.
		 * @param string $email_content      Message used in the notification email.
		 * @param string $domain             The new blog domain.
		 * @param string $path               The new blog path.
		 * @param string $title              The site title.
		 * @param string $recipient_username The user's login name.
		 * @param string $recipient_email    The user's email address.
		 * @param string $key                The activation key created in wpmu_signup_blog().
		 * @param array  $meta               Removed in 2.5; now an empty array.
		 */
		do_action( 'bp_core_sent_blog_signup_email', bp_get_option( 'admin_email' ), $email_subject, $email_content, $tokens['domain'], $tokens['path'], $tokens['title'], $tokens['recipient.username'], $tokens['recipient.email'], $tokens['key_blog'], array() );

	} elseif ( $email_type === 'friends-request' ) {
		/**
		 * Fires after the new friend request email is sent.
		 *
		 * @since 1.5.0
		 * @deprecated 2.5.0 Use the filters in BP_Email.
		 *
		 * @param int    $friend_id     ID of the request recipient.
		 * @param string $email_subject Text for the friend request subject field.
		 * @param string $email_content Text for the friend request message field.
		 * @param int    $friendship_id ID of the friendship object.
		 * @param int    $initiator_id  ID of the friendship requester.
		 */
		do_action( 'bp_friends_sent_request_email', $tokens['friend.id'], $email_subject, $email_content, $tokens['friendship.id'], $tokens['initiator.id'] );

	} elseif ( $email_type === 'friends-request-accepted' ) {
		/**
		 * Fires after the friend request accepted email is sent.
		 *
		 * @since 1.5.0
		 * @deprecated 2.5.0 Use the filters in BP_Email.
		 *
		 * @param int    $initiator_id  ID of the friendship requester.
		 * @param string $email_subject Text for the friend request subject field.
		 * @param string $email_content Text for the friend request message field.
		 * @param int    $friendship_id ID of the friendship object.
		 * @param int    $friend_id     ID of the request recipient.
		 */
		do_action( 'bp_friends_sent_accepted_email', $tokens['initiator.id'], $email_subject, $email_content, $tokens['friendship.id'], $tokens['friend.id'] );

	} elseif ( $email_type === 'groups-invitation' ) {
		/**
		 * Fires after the notification is sent that a member has been invited to a group.
		 *
		 * @since 1.5.0
		 * @deprecated 2.5.0 Use the filters in BP_Email.
		 *
		 * @param int             $invited_user_id  ID of the user who was invited.
		 * @param string          $email_subject    Email notification subject text.
		 * @param string          $email_content    Email notification message text.
		 * @param BP_Groups_Group $group            Group object.
		 */
		do_action( 'bp_groups_sent_invited_email', $tokens['inviter.id'], $email_subject, $email_content, $tokens['group'] );

	} elseif ( $email_type === 'groups-member-promoted' ) {
		/**
		 * Fires after the notification is sent that a member has been promoted.
		 *
		 * @since 1.5.0
		 * @deprecated 2.5.0 Use the filters in BP_Email.
		 *
		 * @param int    $user_id       ID of the user who was promoted.
		 * @param string $email_subject Email notification subject text.
		 * @param string $email_content Email notification message text.
		 * @param int    $group_id      ID of the group that the user is a member of.
		 */
		do_action( 'bp_groups_sent_promoted_email', $tokens['user.id'], $email_subject, $email_content, $tokens['group.id'] );

	} elseif ( $email_type === 'groups-membership-request' ) {
		/**
		 * Fires after the notification is sent that a member has requested group membership.
		 *
		 * @since 1.5.0
		 * @deprecated 2.5.0 Use the filters in BP_Email.
		 *
		 * @param int    $admin_id           ID of the group administrator.
		 * @param string $email_subject      Email notification subject text.
		 * @param string $email_content      Email notification message text.
		 * @param int    $requesting_user_id ID of the user requesting membership.
		 * @param int    $group_id           ID of the group receiving membership request.
		 * @param int    $membership_id      ID of the group membership object.
		 */
		do_action( 'bp_groups_sent_membership_request_email', $tokens['admin.id'], $email_subject, $email_content, $tokens['requesting-user.id'], $tokens['group.id'], $tokens['membership.id'] );

	} elseif ( $email_type === 'groups-membership-request-accepted' || $email_type === 'groups-membership-request-rejected' ) {
		/**
		 * Fires after the notification is sent that a membership has been approved.
		 *
		 * @since 1.5.0
		 * @deprecated 2.5.0 Use the filters in BP_Email.
		 *
		 * @param int    $requesting_user_id ID of the user whose membership was approved.
		 * @param string $email_subject      Email notification subject text.
		 * @param string $email_content      Email notification message text.
		 * @param int    $group_id           ID of the group that was joined.
		 */
		do_action( 'bp_groups_sent_membership_approved_email', $tokens['requesting-user.id'], $email_subject, $email_content, $tokens['group.id'] );
	}
}
add_action( 'bp_send_email_success', 'bp_core_deprecated_email_actions', 20, 2 );

/**
 * When a blog comment status transition occurs, update the relevant activity's status.
 *
 * @since 1.6.0
 * @deprecated 2.5.0
 *
 * @param string $new_status New comment status.
 * @param string $old_status Previous comment status.
 * @param object $comment Comment data.
 */
function bp_blogs_transition_activity_status( $new_status, $old_status, $comment ) {
	_deprecated_function( __FUNCTION__, '2.5.0', 'bp_activity_transition_post_type_comment_status()' );
	bp_activity_transition_post_type_comment_status( $new_status, $old_status, $comment );
}

/**
 * Record a new blog comment in the BuddyPress activity stream.
 *
 * Only posts the item if blog is public and post is not password-protected.
 *
 * @deprecated 2.5.0
 *
 * @param int $comment_id ID of the comment being recorded.
 * @param bool|string $is_approved Optional. The $is_approved value passed to
 *        the 'comment_post' action. Default: true.
 * @return bool|object|null Returns false on failure, the comment object on success.
 */
function bp_blogs_record_comment( $comment_id, $is_approved = true ) {
	_deprecated_function( __FUNCTION__, '2.5.0', 'bp_activity_post_type_comment()' );
	bp_activity_post_type_comment( $comment_id, $is_approved );
}

/**
 * Remove a blog comment activity item from the activity stream.
 *
 * @deprecated 2.5.0
 *
 * @param int $comment_id ID of the comment to be removed.
 */
function bp_blogs_remove_comment( $comment_id ) {
	_deprecated_function( __FUNCTION__, '2.5.0', 'bp_activity_post_type_remove_comment()' );
	bp_activity_post_type_remove_comment( $comment_id );
}
