<?php
/**
 * BuddyPress Friends Activity Functions.
 *
 * These functions handle the recording, deleting and formatting of activity
 * for the user and for this specific component.
 *
 * @package BuddyPress
 * @subpackage FriendsNotifications
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Emails ********************************************************************/

/**
 * Send notifications related to a new friendship request.
 *
 * When a friendship is requested, an email and a BP notification are sent to
 * the user of whom friendship has been requested ($friend_id).
 *
 * @since 1.0.0
 *
 * @param int $friendship_id ID of the friendship object.
 * @param int $initiator_id  ID of the user who initiated the request.
 * @param int $friend_id     ID of the request recipient.
 */
function friends_notification_new_request( $friendship_id, $initiator_id, $friend_id ) {
	if ( 'no' == bp_get_user_meta( (int) $friend_id, 'notification_friends_friendship_request', true ) ) {
		return;
	}

	$args = array(
		'tokens' => array(
			'friend-requests.url' => esc_url( bp_core_get_user_domain( $friend_id ) . bp_get_friends_slug() . '/requests/' ),
			'friend.id'           => $friend_id,
			'friendship.id'       => $friendship_id,
			'initiator.id'        => $initiator_id,
			'initiator.url'       => esc_url( bp_core_get_user_domain( $initiator_id ) ),
			'initiator.name'      => bp_core_get_user_displayname( $initiator_id ),
		),
	);
	bp_send_email( 'friends-request', $friend_id, $args );
}
add_action( 'friends_friendship_requested', 'friends_notification_new_request', 10, 3 );

/**
 * Send notifications related to the acceptance of a friendship request.
 *
 * When a friendship request is accepted, an email and a BP notification are
 * sent to the user who requested the friendship ($initiator_id).
 *
 * @since 1.0.0
 *
 * @param int $friendship_id ID of the friendship object.
 * @param int $initiator_id  ID of the user who initiated the request.
 * @param int $friend_id     ID of the request recipient.
 */
function friends_notification_accepted_request( $friendship_id, $initiator_id, $friend_id ) {
	if ( 'no' == bp_get_user_meta( (int) $initiator_id, 'notification_friends_friendship_accepted', true ) ) {
		return;
	}

	$args = array(
		'tokens' => array(
			'friend.id'      => $friend_id,
			'friendship.url' => esc_url( bp_core_get_user_domain( $friend_id ) ),
			'friend.name'    => bp_core_get_user_displayname( $friend_id ),
			'friendship.id'  => $friendship_id,
			'initiator.id'   => $initiator_id,
		),
	);
	bp_send_email( 'friends-request-accepted', $initiator_id, $args );
}
add_action( 'friends_friendship_accepted', 'friends_notification_accepted_request', 10, 3 );

/** Notifications *************************************************************/

/**
 * Notification formatting callback for bp-friends notifications.
 *
 * @since 1.0.0
 *
 * @param string $action            The kind of notification being rendered.
 * @param int    $item_id           The primary item ID.
 * @param int    $secondary_item_id The secondary item ID.
 * @param int    $total_items       The total number of messaging-related notifications
 *                                  waiting for the user.
 * @param string $format            'string' for BuddyBar-compatible notifications;
 *                                  'array' for WP Toolbar. Default: 'string'.
 * @return array|string
 */
function friends_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

	switch ( $action ) {
		case 'friendship_accepted':
			$link = trailingslashit( bp_loggedin_user_domain() . bp_get_friends_slug() . '/my-friends' );

			// $action and $amount are used to generate dynamic filter names.
			$action = 'accepted';

			// Set up the string and the filter.
			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( '%d friends accepted your friendship requests', 'buddypress' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				$text = sprintf( __( '%s accepted your friendship request', 'buddypress' ),  bp_core_get_user_displayname( $item_id ) );
				$amount = 'single';
			}

			break;

		case 'friendship_request':
			$link = bp_loggedin_user_domain() . bp_get_friends_slug() . '/requests/?new';

			$action = 'request';

			// Set up the string and the filter.
			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( 'You have %d pending friendship requests', 'buddypress' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				$text = sprintf( __( 'You have a friendship request from %s', 'buddypress' ),  bp_core_get_user_displayname( $item_id ) );
				$amount = 'single';
			}

			break;
	}

	// Return either an HTML link or an array, depending on the requested format.
	if ( 'string' == $format ) {

		/**
		 * Filters the format of friendship notifications based on type and amount * of notifications pending.
		 *
		 * This is a variable filter that has four possible versions.
		 * The four possible versions are:
		 *   - bp_friends_single_friendship_accepted_notification
		 *   - bp_friends_multiple_friendship_accepted_notification
		 *   - bp_friends_single_friendship_request_notification
		 *   - bp_friends_multiple_friendship_request_notification
		 *
		 * @since 1.0.0
		 *
		 * @param string|array $value       Depending on format, an HTML link to new requests profile
		 *                                  tab or array with link and text.
		 * @param int          $total_items The total number of messaging-related notifications
		 *                                  waiting for the user.
		 * @param int          $item_id     The primary item ID.
		 */
		$return = apply_filters( 'bp_friends_' . $amount . '_friendship_' . $action . '_notification', '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $item_id );
	} else {
		/** This filter is documented in bp-friends/bp-friends-notifications.php */
		$return = apply_filters( 'bp_friends_' . $amount . '_friendship_' . $action . '_notification', array(
			'link' => $link,
			'text' => $text
		), (int) $total_items, $item_id );
	}

	/**
	 * Fires at the end of the bp-friends notification format callback.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $action            The kind of notification being rendered.
	 * @param int          $item_id           The primary item ID.
	 * @param int          $secondary_item_id The secondary item ID.
	 * @param int          $total_items       The total number of messaging-related notifications
	 *                                        waiting for the user.
	 * @param array|string $return            Notification text string or array of link and text.
	 */
	do_action( 'friends_format_notifications', $action, $item_id, $secondary_item_id, $total_items, $return );

	return $return;
}

/**
 * Clear friend-related notifications when ?new=1
 *
 * @since 1.2.0
 */
function friends_clear_friend_notifications() {
	if ( isset( $_GET['new'] ) && bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->friends->id, 'friendship_accepted' );
	}
}
add_action( 'bp_activity_screen_my_activity', 'friends_clear_friend_notifications' );

/**
 * Delete any friendship request notifications for the logged in user.
 *
 * @since 1.9.0
 */
function bp_friends_mark_friendship_request_notifications_by_type() {
	if ( isset( $_GET['new'] ) && bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->friends->id, 'friendship_request' );
	}
}
add_action( 'friends_screen_requests', 'bp_friends_mark_friendship_request_notifications_by_type' );

/**
 * Delete any friendship acceptance notifications for the logged in user.
 *
 * @since 1.9.0
 */
function bp_friends_mark_friendship_accepted_notifications_by_type() {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->friends->id, 'friendship_accepted' );
	}
}
add_action( 'friends_screen_my_friends', 'bp_friends_mark_friendship_accepted_notifications_by_type' );

/**
 * Notify one use that another user has requested their virtual friendship.
 *
 * @since 1.9.0
 *
 * @param int $friendship_id     The unique ID of the friendship.
 * @param int $initiator_user_id The friendship initiator user ID.
 * @param int $friend_user_id    The friendship request receiver user ID.
 */
function bp_friends_friendship_requested_notification( $friendship_id, $initiator_user_id, $friend_user_id ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_add_notification( array(
			'user_id'           => $friend_user_id,
			'item_id'           => $initiator_user_id,
			'secondary_item_id' => $friendship_id,
			'component_name'    => buddypress()->friends->id,
			'component_action'  => 'friendship_request',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		) );
	}
}
add_action( 'friends_friendship_requested', 'bp_friends_friendship_requested_notification', 10, 3 );

/**
 * Remove friend request notice when a member rejects another members
 *
 * @since 1.9.0
 *
 * @param int    $friendship_id Friendship ID (not used).
 * @param object $friendship    Friendship object.
 */
function bp_friends_mark_friendship_rejected_notifications_by_item_id( $friendship_id, $friendship ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, buddypress()->friends->id, 'friendship_request' );
	}
}
add_action( 'friends_friendship_rejected', 'bp_friends_mark_friendship_rejected_notifications_by_item_id', 10, 2 );

/**
 * Notify a member when another member accepts their virtual friendship request.
 *
 * @since 1.9.0
 *
 * @param int $friendship_id     The unique ID of the friendship.
 * @param int $initiator_user_id The friendship initiator user ID.
 * @param int $friend_user_id    The friendship request receiver user ID.
 */
function bp_friends_add_friendship_accepted_notification( $friendship_id, $initiator_user_id, $friend_user_id ) {

	// Bail if notifications is not active.
	if ( ! bp_is_active( 'notifications' ) ) {
		return;
	}

	// Remove the friend request notice.
	bp_notifications_mark_notifications_by_item_id( $friend_user_id, $initiator_user_id, buddypress()->friends->id, 'friendship_request' );

	// Add a friend accepted notice for the initiating user.
	bp_notifications_add_notification(  array(
		'user_id'           => $initiator_user_id,
		'item_id'           => $friend_user_id,
		'secondary_item_id' => $friendship_id,
		'component_name'    => buddypress()->friends->id,
		'component_action'  => 'friendship_accepted',
		'date_notified'     => bp_core_current_time(),
		'is_new'            => 1,
	) );
}
add_action( 'friends_friendship_accepted', 'bp_friends_add_friendship_accepted_notification', 10, 3 );

/**
 * Remove friend request notice when a member withdraws their friend request.
 *
 * @since 1.9.0
 *
 * @param int    $friendship_id Friendship ID (not used).
 * @param object $friendship    Friendship Object.
 */
function bp_friends_mark_friendship_withdrawn_notifications_by_item_id( $friendship_id, $friendship ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_delete_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, buddypress()->friends->id, 'friendship_request' );
	}
}
add_action( 'friends_friendship_withdrawn', 'bp_friends_mark_friendship_withdrawn_notifications_by_item_id', 10, 2 );

/**
 * Remove friendship requests FROM user, used primarily when a user is deleted.
 *
 * @since 1.9.0
 *
 * @param int $user_id ID of the user whose notifications are removed.
 */
function bp_friends_remove_notifications_data( $user_id = 0 ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_delete_notifications_from_user( $user_id, buddypress()->friends->id, 'friendship_request' );
	}
}
add_action( 'friends_remove_data', 'bp_friends_remove_notifications_data', 10, 1 );
