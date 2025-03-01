<?php
/**
 * BuddyPress Friends Activity Functions.
 *
 * These functions handle the recording, deleting and formatting of activity
 * for the user and for this specific component.
 *
 * @package BuddyPress
 * @subpackage Friends
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
	$friends_slug = bp_get_friends_slug();

	switch ( $action ) {
		case 'friendship_accepted':
			$link = bp_loggedin_user_url( bp_members_get_path_chunks( array( $friends_slug, 'my-friends' ) ) );

			// $action and $amount are used to generate dynamic filter names.
			$action = 'accepted';

			// Set up the string and the filter.
			if ( (int) $total_items > 1 ) {
				/* translators: %d: the number of friends */
				$text = sprintf( __( '%d friends accepted your friendship requests', 'buddypress' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				/* translators: %s: friend name */
				$text = sprintf( __( '%s accepted your friendship request', 'buddypress' ), bp_core_get_user_displayname( $item_id ) );
				$amount = 'single';
			}

			break;

		case 'friendship_request':
			$link = add_query_arg(
				'new',
				1,
				bp_loggedin_user_url( bp_members_get_path_chunks( array( $friends_slug, 'requests' ) ) )
			);

			$action = 'request';

			// Set up the string and the filter.
			if ( (int) $total_items > 1 ) {
				/* translators: %d: the number of pending requests */
				$text = sprintf( __( 'You have %d pending friendship requests', 'buddypress' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				/* translators: %s: friend name */
				$text = sprintf( __( 'You have a friendship request from %s', 'buddypress' ), bp_core_get_user_displayname( $item_id ) );
				$amount = 'single';
			}

			break;
	}

	// Return either an HTML link or an array, depending on the requested format.
	if ( 'string' === $format ) {

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
		 * @since 6.0.0 Adds the $secondary_item_id parameter.
		 *
		 * @param string|array $value             Depending on format, an HTML link to new requests profile tab or array with link and text.
		 * @param int          $total_items       The total number of messaging-related notifications waiting for the user.
		 * @param int          $item_id           The primary item ID.
		 * @param int          $secondary_item_id The secondary item ID.
		 */
		$return = apply_filters( 'bp_friends_' . $amount . '_friendship_' . $action . '_notification', '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $item_id, $secondary_item_id );
	} else {
		/** This filter is documented in bp-friends/bp-friends-notifications.php */
		$return = apply_filters( 'bp_friends_' . $amount . '_friendship_' . $action . '_notification', array(
			'link' => $link,
			'text' => $text
		), (int) $total_items, $item_id, $secondary_item_id );
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
	if ( isset( $_GET['new'] ) ) {
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
	if ( isset( $_GET['new'] ) ) {
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
	bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->friends->id, 'friendship_accepted' );
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
add_action( 'friends_friendship_requested', 'bp_friends_friendship_requested_notification', 10, 3 );

/**
 * Remove friend request notice when a member rejects another members
 *
 * @since 1.9.0
 *
 * @param int                   $friendship_id Friendship ID (not used).
 * @param BP_Friends_Friendship $friendship    The friendship object.
 */
function bp_friends_mark_friendship_rejected_notifications_by_item_id( $friendship_id, $friendship ) {
	bp_notifications_mark_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, buddypress()->friends->id, 'friendship_request' );
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
 * @param int                   $friendship_id Friendship ID (not used).
 * @param BP_Friends_Friendship $friendship    The friendship object.
 */
function bp_friends_mark_friendship_withdrawn_notifications_by_item_id( $friendship_id, $friendship ) {
	bp_notifications_delete_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, buddypress()->friends->id, 'friendship_request' );
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
	bp_notifications_delete_notifications_from_user( $user_id, buddypress()->friends->id, 'friendship_request' );
}
add_action( 'friends_remove_data', 'bp_friends_remove_notifications_data', 10, 1 );

/**
 * Add Friends-related settings to the Settings > Notifications page.
 *
 * @since 1.0.0
 */
function friends_screen_notification_settings() {

	if ( ! $send_requests = bp_get_user_meta( bp_displayed_user_id(), 'notification_friends_friendship_request', true ) ) {
		$send_requests = 'yes';
	}

	if ( ! $accept_requests = bp_get_user_meta( bp_displayed_user_id(), 'notification_friends_friendship_accepted', true ) ) {
		$accept_requests = 'yes';
	}
	?>

	<table class="notification-settings" id="friends-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php echo esc_html_x( 'Friends', 'Friend settings on notification settings page', 'buddypress' ); ?></th>
				<th class="yes"><?php esc_html_e( 'Yes', 'buddypress' ); ?></th>
				<th class="no"><?php esc_html_e( 'No', 'buddypress' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<tr id="friends-notification-settings-request">
				<td></td>
				<td><?php echo esc_html_x( 'A member sends you a friendship request', 'Friend settings on notification settings page', 'buddypress' ); ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_friends_friendship_request]" id="notification-friends-friendship-request-yes" value="yes" <?php checked( $send_requests, 'yes', true ) ?>/><label for="notification-friends-friendship-request-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					esc_html_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_friends_friendship_request]" id="notification-friends-friendship-request-no" value="no" <?php checked( $send_requests, 'no', true ) ?>/><label for="notification-friends-friendship-request-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					esc_html_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>
			<tr id="friends-notification-settings-accepted">
				<td></td>
				<td><?php echo esc_html_x( 'A member accepts your friendship request', 'Friend settings on notification settings page', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_friends_friendship_accepted]" id="notification-friends-friendship-accepted-yes" value="yes" <?php checked( $accept_requests, 'yes', true ) ?>/><label for="notification-friends-friendship-accepted-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					esc_html_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_friends_friendship_accepted]" id="notification-friends-friendship-accepted-no" value="no" <?php checked( $accept_requests, 'no', true ) ?>/><label for="notification-friends-friendship-accepted-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					esc_html_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>

			<?php

			/**
			 * Fires after the last table row on the friends notification screen.
			 *
			 * @since 1.0.0
			 */
			do_action( 'friends_screen_notification_settings' ); ?>

		</tbody>
	</table>

<?php
}
add_action( 'bp_notification_settings', 'friends_screen_notification_settings' );
