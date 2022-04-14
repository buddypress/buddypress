<?php
/**
 * BuddyPress Messages Notifications.
 *
 * @package BuddyPress
 * @subpackage MessagesNotifications
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Format notifications for the Messages component.
 *
 * @since 1.0.0
 *
 * @param string $action            The kind of notification being rendered.
 * @param int    $item_id           The primary item id.
 * @param int    $secondary_item_id The secondary item id.
 * @param int    $total_items       The total number of messaging-related notifications
 *                                  waiting for the user.
 * @param string $format            'string' for notification HTML link or 'array' for separate link and text.
 * @return string|array Formatted notifications.
 */
function messages_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {
	$total_items = (int) $total_items;
	$text        = '';
	$link        = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/inbox' );
	$title       = __( 'Inbox', 'buddypress' );
	$amount      = 'single';

	if ( 'new_message' === $action ) {
		if ( $total_items > 1 ) {
			$amount = 'multiple';

			/* translators: %d: number of new messages */
			$text = sprintf( __( 'You have %d new messages', 'buddypress' ), $total_items );

		} else {
			// Get message thread ID.
			$message   = new BP_Messages_Message( $item_id );
			$thread_id = $message->thread_id;
			$link      = '';

			if ( ! empty( $thread_id ) ) {
				$link = bp_get_message_thread_view_link( $thread_id );
			}

			if ( ! empty( $secondary_item_id ) ) {
				/* translators: %s: member name */
				$text = sprintf( __( '%s sent you a new private message', 'buddypress' ), bp_core_get_user_displayname( $secondary_item_id ) );
			} else {
				/* translators: %s: number of private messages */
				$text = sprintf( _n( 'You have %s new private message', 'You have %s new private messages', $total_items, 'buddypress' ), bp_core_number_format( $total_items ) );
			}
		}

		if ( 'string' === $format ) {
			if ( ! empty( $link ) ) {
				$retval = '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>';
			} else {
				$retval = esc_html( $text );
			}

			/** This filter is documented in wp-includes/deprecated.php */
			$retval = apply_filters_deprecated(
				'bp_messages_' . $amount . '_new_message_notification',
				array( $retval, $total_items, $text, $link, $item_id, $secondary_item_id ),
				'10.0.0',
				'bp_messages_' . $amount . '_new_message_' . $format . '_notification'
			);
		} else {
			$retval = array(
				'text' => $text,
				'link' => $link,
			);

			/** This filter is documented in wp-includes/deprecated.php */
			$retval = apply_filters_deprecated(
				'bp_messages_' . $amount . '_new_message_notification',
				array(
					$retval,
					$link, // This extra `$link` variable is the reason why we deprecated the filter.
					$total_items,
					$text,
					$link,
					$item_id,
					$secondary_item_id,
				),
				'10.0.0',
				'bp_messages_' . $amount . '_new_message_' . $format . '_notification'
			);
		}

		/**
		 * Filters the new message notification text before the notification is created.
		 *
		 * This is a dynamic filter. Possible filter names are:
		 *   - 'bp_messages_multiple_new_message_string_notification'.
		 *   - 'bp_messages_single_new_message_string_notification'.
		 *   - 'bp_messages_multiple_new_message_array_notification'.
		 *   - 'bp_messages_single_new_message_array_notification'.
		 *
		 * @param array|string $retval            An array containing the text and the link of the Notification or simply its text.
		 * @param int          $total_items       Number of messages referred to by the notification.
		 * @param string       $text              The raw notification text (ie, not wrapped in a link).
		 * @param string       $link              The link of the notification.
		 * @param int          $item_id           ID of the associated item.
		 * @param int          $secondary_item_id ID of the secondary associated item.
		 */
		$retval = apply_filters(
			'bp_messages_' . $amount . '_new_message_' . $format . '_notification',
			$retval,
			$total_items,
			$text,
			$link,
			$item_id,
			$secondary_item_id
		);

	// Custom notification action for the Messages component.
	} else {
		if ( 'string' === $format ) {
			$retval = $text;
		} else {
			$retval = array(
				'text' => $text,
				'link' => $link,
			);
		}

		/**
		 * Backcompat for plugins that used to filter bp_messages_single_new_message_notification
		 * for their custom actions. These plugins should now use 'bp_messages_' . $action . '_notification'
		 */
		if ( has_filter( 'bp_messages_single_new_message_notification' ) ) {
			if ( 'string' === $format ) {
				/** This filter is documented in wp-includes/deprecated.php */
				$retval = apply_filters_deprecated(
					'bp_messages_' . $amount . '_new_message_notification',
					array( $retval, $total_items, $text, $link, $item_id, $secondary_item_id ),
					'10.0.0',
					"bp_messages_{$action}_notification"
				);

			} else {
				/** This filter is documented in wp-includes/deprecated.php */
				$retval = apply_filters_deprecated(
					'bp_messages_' . $amount . '_new_message_notification',
					array( $retval, $link, $total_items, $text, $link, $item_id, $secondary_item_id ),
					'10.0.0',
					"bp_messages_{$action}_notification"
				);
			}
		}

		/**
		 * Filters the custom action notification before the notification is created.
		 *
		 * This is a dynamic filter based on the message notification action.
		 *
		 * @since 2.6.0
		 *
		 * @param array  $value             An associative array containing the text and the link of the notification
		 * @param int    $item_id           ID of the associated item.
		 * @param int    $secondary_item_id ID of the secondary associated item.
		 * @param int    $total_items       Number of messages referred to by the notification.
		 * @param string $format            Return value format. 'string' for BuddyBar-compatible
		 *                                  notifications; 'array' for WP Toolbar. Default: 'string'.
		 */
		$retval = apply_filters( "bp_messages_{$action}_notification", $retval, $item_id, $secondary_item_id, $total_items, $format );
	}

	/**
	 * Fires right before returning the formatted message notifications.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action            The type of message notification.
	 * @param int    $item_id           The primary item ID.
	 * @param int    $secondary_item_id The secondary item ID.
	 * @param int    $total_items       Total amount of items to format.
	 */
	do_action( 'messages_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return $retval;
}

/**
 * Send notifications to message recipients.
 *
 * @since 1.9.0
 *
 * @param BP_Messages_Message $message Message object.
 */
function bp_messages_message_sent_add_notification( $message ) {
	if ( ! empty( $message->recipients ) ) {
		foreach ( (array) $message->recipients as $recipient ) {
			bp_notifications_add_notification( array(
				'user_id'           => $recipient->user_id,
				'item_id'           => $message->id,
				'secondary_item_id' => $message->sender_id,
				'component_name'    => buddypress()->messages->id,
				'component_action'  => 'new_message',
				'date_notified'     => bp_core_current_time(),
				'is_new'            => 1,
			) );
		}
	}
}
add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

/**
 * Mark new message notification when member reads a message thread directly.
 *
 * @since 1.9.0
 *
 * @global BP_Messages_Thread_Template $thread_template
 */
function bp_messages_screen_conversation_mark_notifications() {
	global $thread_template;

	/*
	 * Bail if viewing the logged-in user's profile or a single message thread.
	 * The `messages_action_conversation()` action already marks the current thread as read.
	 */
	if ( ! bp_is_my_profile() || ( bp_is_current_action( 'view' ) && bp_action_variable( 0 ) ) ) {
		return;
	}

	// Get unread PM notifications for the user.
	$new_pm_notifications = BP_Notifications_Notification::get( array(
		'user_id'           => bp_loggedin_user_id(),
		'component_name'    => buddypress()->messages->id,
		'component_action'  => 'new_message',
		'is_new'            => 1,
	) );
	$unread_message_ids = wp_list_pluck( $new_pm_notifications, 'item_id' );

	// No unread PMs, so stop!
	if ( empty( $unread_message_ids ) ) {
		return;
	}

	// Get the unread message ids for this thread only.
	$message_ids = array_intersect( $unread_message_ids, wp_list_pluck( $thread_template->thread->messages, 'id' ) );

	// Mark each notification for each PM message as read.
	bp_notifications_mark_notifications_by_item_ids( bp_loggedin_user_id(), $message_ids, 'messages', 'new_message', false );
}
add_action( 'thread_loop_start', 'bp_messages_screen_conversation_mark_notifications', 10 );

/**
 * Mark new message notification as read when the corresponding message is mark read.
 *
 * This callback covers mark-as-read bulk actions.
 *
 * @since 3.0.0
 *
 * @param int $thread_id ID of the thread being marked as read.
 * @param int $user_id   ID of the user who read the thread.
 * @param int $num_rows  The number of affected rows by the "mark read" update query.
 */
function bp_messages_mark_notification_on_mark_thread( $thread_id, $user_id = 0, $num_rows = 0 ) {
	if ( ! $num_rows ) {
		return;
	}

	$thread_messages = BP_Messages_Thread::get_messages( $thread_id );
	$message_ids     = wp_list_pluck( $thread_messages, 'id' );

	bp_notifications_mark_notifications_by_item_ids( $user_id, $message_ids, 'messages', 'new_message', false );
}
add_action( 'messages_thread_mark_as_read', 'bp_messages_mark_notification_on_mark_thread', 10, 3 );

/**
 * When a message is deleted, delete corresponding notifications.
 *
 * @since 2.0.0
 *
 * @param int   $thread_id   ID of the thread.
 * @param int[] $message_ids The list of message IDs to delete.
 */
function bp_messages_message_delete_notifications( $thread_id, $message_ids ) {
	// For each recipient, delete notifications corresponding to each message.
	$thread = new BP_Messages_Thread( $thread_id );
	foreach ( $thread->get_recipients() as $recipient ) {
		if ( ! isset( $recipient->user_id ) || ! $recipient->user_id ) {
			continue;
		}

		bp_notifications_delete_notifications_by_item_ids( $recipient->user_id, $message_ids, buddypress()->messages->id, 'new_message' );
	}
}
add_action( 'bp_messages_thread_after_delete', 'bp_messages_message_delete_notifications', 10, 2 );

/**
 * Render the markup for the Messages section of Settings > Notifications.
 *
 * @since 1.0.0
 */
function messages_screen_notification_settings() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	if ( !$new_messages = bp_get_user_meta( bp_displayed_user_id(), 'notification_messages_new_message', true ) ) {
		$new_messages = 'yes';
	} ?>

	<table class="notification-settings" id="messages-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php esc_html_e( 'Messages', 'buddypress' ); ?></th>
				<th class="yes"><?php esc_html_e( 'Yes', 'buddypress' ); ?></th>
				<th class="no"><?php esc_html_e( 'No', 'buddypress' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<tr id="messages-notification-settings-new-message">
				<td></td>
				<td><?php esc_html_e( 'A member sends you a new message', 'buddypress' ); ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_messages_new_message]" id="notification-messages-new-messages-yes" value="yes" <?php checked( $new_messages, 'yes', true ) ?>/><label for="notification-messages-new-messages-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					esc_html_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_messages_new_message]" id="notification-messages-new-messages-no" value="no" <?php checked( $new_messages, 'no', true ) ?>/><label for="notification-messages-new-messages-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					esc_html_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>

			<?php

			/**
			 * Fires inside the closing </tbody> tag for messages screen notification settings.
			 *
			 * @since 1.0.0
			 */
			do_action( 'messages_screen_notification_settings' ); ?>
		</tbody>
	</table>

<?php
}
add_action( 'bp_notification_settings', 'messages_screen_notification_settings', 2 );
