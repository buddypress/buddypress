<?php
/**
 * BuddyPress Activity Notifications.
 *
 * @package BuddyPress
 * @subpackage ActivityNotifications
 * @since 1.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Format notifications related to activity.
 *
 * @since 1.5.0
 *
 * @param string $action            The type of activity item. Just 'new_at_mention' for now.
 * @param int    $item_id           The activity ID.
 * @param int    $secondary_item_id In the case of at-mentions, this is the mentioner's ID.
 * @param int    $total_items       The total number of notifications to format.
 * @param string $format            'string' to get a BuddyBar-compatible notification, 'array' otherwise.
 * @param int    $id                Optional. The notification ID.
 * @return string $return Formatted @mention notification.
 */
function bp_activity_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string', $id = 0 ) {
	$action_filter = $action;
	$return        = false;
	$activity_id   = $item_id;
	$user_id       = $secondary_item_id;
	$user_fullname = bp_core_get_user_displayname( $user_id );

	switch ( $action ) {
		case 'new_at_mention':
			$action_filter = 'at_mentions';
			$link          = bp_loggedin_user_domain() . bp_get_activity_slug() . '/mentions/';

			/* translators: %s: the current user display name */
			$title  = sprintf( __( '@%s Mentions', 'buddypress' ), bp_get_loggedin_user_username() );
			$amount = 'single';

			if ( (int) $total_items > 1 ) {
				/* translators: 1: the number of activity mentions */
				$text   = sprintf( __( 'You have %1$d new mentions', 'buddypress' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				/* translators: 1: the user display name */
				$text = sprintf( __( '%1$s mentioned you', 'buddypress' ), $user_fullname );
			}
		break;

		case 'update_reply':
			$link   = bp_get_notifications_permalink();
			$title  = __( 'New Activity reply', 'buddypress' );
			$amount = 'single';

			if ( (int) $total_items > 1 ) {
				$link = add_query_arg( 'type', $action, $link );

				/* translators: 1: the number of activity replies */
				$text   = sprintf( __( 'You have %1$d new replies', 'buddypress' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				$link = add_query_arg( 'rid', (int) $id, bp_activity_get_permalink( $activity_id ) );

				/* translators: 1: the user display name */
				$text = sprintf( __( '%1$s commented on one of your updates', 'buddypress' ), $user_fullname );
			}
		break;

		case 'comment_reply':
			$link   = bp_get_notifications_permalink();
			$title  = __( 'New Activity comment reply', 'buddypress' );
			$amount = 'single';

			if ( (int) $total_items > 1 ) {
				$link = add_query_arg( 'type', $action, $link );

				/* translators: 1: the number of activity comment replies */
				$text   = sprintf( __( 'You have %1$d new comment replies', 'buddypress' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				$link = add_query_arg( 'crid', (int) $id, bp_activity_get_permalink( $activity_id ) );

				/* translators: 1: the user display name */
				$text = sprintf( __( '%1$s replied to one of your activity comments', 'buddypress' ), $user_fullname );
			}
		break;
	}

	if ( 'string' == $format ) {

		/**
		 * Filters the activity notification for the string format.
		 *
		 * This is a variable filter that is dependent on how many items
		 * need notified about. The two possible hooks are bp_activity_single_at_mentions_notification
		 * or bp_activity_multiple_at_mentions_notification.
		 *
		 * @since 1.5.0
		 * @since 2.6.0 use the $action_filter as a new dynamic portion of the filter name.
		 *
		 * @param string $string          HTML anchor tag for the interaction.
		 * @param string $link            The permalink for the interaction.
		 * @param int    $total_items     How many items being notified about.
		 * @param int    $activity_id     ID of the activity item being formatted.
		 * @param int    $user_id         ID of the user who inited the interaction.
		 */
		$return = apply_filters( 'bp_activity_' . $amount . '_' . $action_filter . '_notification', '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>', $link, (int) $total_items, $activity_id, $user_id );
	} else {

		/**
		 * Filters the activity notification for any non-string format.
		 *
		 * This is a variable filter that is dependent on how many items need notified about.
		 * The two possible hooks are bp_activity_single_at_mentions_notification
		 * or bp_activity_multiple_at_mentions_notification.
		 *
		 * @since 1.5.0
		 * @since 2.6.0 use the $action_filter as a new dynamic portion of the filter name.
		 *
		 * @param array  $array           Array holding the content and permalink for the interaction notification.
		 * @param string $link            The permalink for the interaction.
		 * @param int    $total_items     How many items being notified about.
		 * @param int    $activity_id     ID of the activity item being formatted.
		 * @param int    $user_id         ID of the user who inited the interaction.
		 */
		$return = apply_filters( 'bp_activity_' . $amount . '_' . $action_filter . '_notification', array(
			'text' => $text,
			'link' => $link
		), $link, (int) $total_items, $activity_id, $user_id );
	}

	/**
	 * Fires right before returning the formatted activity notifications.
	 *
	 * @since 1.2.0
	 *
	 * @param string $action            The type of activity item.
	 * @param int    $item_id           The activity ID.
	 * @param int    $secondary_item_id The user ID who inited the interaction.
	 * @param int    $total_items       Total amount of items to format.
	 */
	do_action( 'activity_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return $return;
}

/**
 * Notify a member when their nicename is mentioned in an activity stream item.
 *
 * Hooked to the 'bp_activity_sent_mention_email' action, we piggy back off the
 * existing email code for now, since it does the heavy lifting for us. In the
 * future when we separate emails from Notifications, this will need its own
 * 'bp_activity_at_name_send_emails' equivalent helper function.
 *
 * @since 1.9.0
 *
 * @param object $activity           Activity object.
 * @param string $subject (not used) Notification subject.
 * @param string $message (not used) Notification message.
 * @param string $content (not used) Notification content.
 * @param int    $receiver_user_id   ID of user receiving notification.
 */
function bp_activity_at_mention_add_notification( $activity, $subject, $message, $content, $receiver_user_id ) {
	bp_notifications_add_notification( array(
			'user_id'           => $receiver_user_id,
			'item_id'           => $activity->id,
			'secondary_item_id' => $activity->user_id,
			'component_name'    => buddypress()->activity->id,
			'component_action'  => 'new_at_mention',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
	) );
}
add_action( 'bp_activity_sent_mention_email', 'bp_activity_at_mention_add_notification', 10, 5 );

/**
 * Notify a member one of their activity received a reply.
 *
 * @since 2.6.0
 *
 * @param BP_Activity_Activity $activity     The original activity.
 * @param int                  $comment_id   ID for the newly received comment.
 * @param int                  $commenter_id ID of the user who made the comment.
 */
function bp_activity_update_reply_add_notification( $activity, $comment_id, $commenter_id ) {
	bp_notifications_add_notification( array(
		'user_id'           => $activity->user_id,
		'item_id'           => $comment_id,
		'secondary_item_id' => $commenter_id,
		'component_name'    => buddypress()->activity->id,
		'component_action'  => 'update_reply',
		'date_notified'     => bp_core_current_time(),
		'is_new'            => 1,
	) );
}
add_action( 'bp_activity_sent_reply_to_update_notification', 'bp_activity_update_reply_add_notification', 10, 3 );

/**
 * Notify a member one of their activity comment received a reply.
 *
 * @since 2.6.0
 *
 * @param BP_Activity_Activity $activity_comment The parent activity.
 * @param int                  $comment_id       ID for the newly received comment.
 * @param int                  $commenter_id     ID of the user who made the comment.
 */
function bp_activity_comment_reply_add_notification( $activity_comment, $comment_id, $commenter_id ) {
	bp_notifications_add_notification( array(
		'user_id'           => $activity_comment->user_id,
		'item_id'           => $comment_id,
		'secondary_item_id' => $commenter_id,
		'component_name'    => buddypress()->activity->id,
		'component_action'  => 'comment_reply',
		'date_notified'     => bp_core_current_time(),
		'is_new'            => 1,
	) );
}
add_action( 'bp_activity_sent_reply_to_reply_notification', 'bp_activity_comment_reply_add_notification', 10, 3 );

/**
 * Mark at-mention notifications as read when users visit their Mentions page.
 *
 * @since 1.5.0
 * @since 2.5.0 Add the $user_id parameter
 *
 * @param int $user_id The id of the user whose notifications are marked as read.
 */
function bp_activity_remove_screen_notifications( $user_id = 0 ) {
	// Only mark read if the current user is looking at his own mentions.
	if ( empty( $user_id ) || (int) $user_id !== (int) bp_loggedin_user_id() ) {
		return;
	}

	bp_notifications_mark_notifications_by_type( $user_id, buddypress()->activity->id, 'new_at_mention' );
}
add_action( 'bp_activity_clear_new_mentions', 'bp_activity_remove_screen_notifications', 10, 1 );

/**
 * Mark notifications as read when a user visits an activity permalink.
 *
 * @since 2.0.0
 * @since 3.2.0 Marks replies to parent update and replies to an activity comment as read.
 *
 * @param BP_Activity_Activity $activity Activity object.
 */
function bp_activity_remove_screen_notifications_single_activity_permalink( $activity ) {
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Mark as read any notifications for the current user related to this activity item.
	bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $activity->id, buddypress()->activity->id, 'new_at_mention' );

	$comment_id = 0;
	// For replies to a parent update.
	if ( ! empty( $_GET['rid'] ) ) {
		$comment_id = (int) $_GET['rid'];

	// For replies to an activity comment.
	} elseif ( ! empty( $_GET['crid'] ) ) {
		$comment_id = (int) $_GET['crid'];
	}

	// Mark individual activity reply notification as read.
	if ( ! empty( $comment_id ) ) {
		BP_Notifications_Notification::update(
			array(
				'is_new' => false
			),
			array(
				'user_id' => bp_loggedin_user_id(),
				'id'      => $comment_id
			)
		);
	}
}
add_action( 'bp_activity_screen_single_activity_permalink', 'bp_activity_remove_screen_notifications_single_activity_permalink' );

/**
 * Mark non-mention notifications as read when user visits our read permalink.
 *
 * In particular, 'update_reply' and 'comment_reply' notifications are handled
 * here. See {@link bp_activity_format_notifications()} for more info.
 *
 * @since 2.6.0
 */
function bp_activity_remove_screen_notifications_for_non_mentions() {
	if ( false === is_singular() || false === is_user_logged_in() || empty( $_GET['nid'] ) ) {
		return;
	}

	// Mark notification as read.
	BP_Notifications_Notification::update(
		array(
			'is_new'  => false
		),
		array(
			'user_id' => bp_loggedin_user_id(),
			'id'      => (int) $_GET['nid']
		)
	);
}
add_action( 'bp_screens', 'bp_activity_remove_screen_notifications_for_non_mentions' );

/**
 * Delete at-mention notifications when the corresponding activity item is deleted.
 *
 * @since 2.0.0
 *
 * @param array $activity_ids_deleted IDs of deleted activity items.
 */
function bp_activity_at_mention_delete_notification( $activity_ids_deleted = array() ) {
	// Let's delete all without checking if content contains any mentions
	// to avoid a query to get the activity.
	if ( ! empty( $activity_ids_deleted ) ) {
		foreach ( $activity_ids_deleted as $activity_id ) {
			bp_notifications_delete_all_notifications_by_type( $activity_id, buddypress()->activity->id );
		}
	}
}
add_action( 'bp_activity_deleted_activities', 'bp_activity_at_mention_delete_notification', 10 );

/**
 * Add a notification for post comments to the post author or post commenter.
 *
 * Requires "activity stream commenting on posts and comments" to be enabled.
 *
 * @since 2.6.0
 *
 * @param int        $activity_id          The activity comment ID.
 * @param WP_Comment $post_type_comment    WP Comment object.
 * @param array      $activity_args        Activity comment arguments.
 * @param object     $activity_post_object The post type tracking args object.
 */
function bp_activity_add_notification_for_synced_blog_comment( $activity_id, $post_type_comment, $activity_args, $activity_post_object ) {
	// If activity comments are disabled for WP posts, stop now!
	if ( bp_disable_blogforum_comments() || empty( $activity_id ) ) {
		return;
	}

	// Send a notification to the blog post author.
	if ( (int) $post_type_comment->post->post_author !== (int) $activity_args['user_id'] ) {
		// Only add a notification if comment author is a registered user.
		// @todo Should we remove this restriction?
		if ( ! empty( $post_type_comment->user_id ) ) {
			bp_notifications_add_notification( array(
				'user_id'           => $post_type_comment->post->post_author,
				'item_id'           => $activity_id,
				'secondary_item_id' => $post_type_comment->user_id,
				'component_name'    => buddypress()->activity->id,
				'component_action'  => 'update_reply',
				'date_notified'     => $post_type_comment->comment_date_gmt,
				'is_new'            => 1,
			) );
		}
	}

	// Send a notification to the parent comment author for follow-up comments.
	if ( ! empty( $post_type_comment->comment_parent ) ) {
		$parent_comment = get_comment( $post_type_comment->comment_parent );

		if ( ! empty( $parent_comment->user_id ) && (int) $parent_comment->user_id !== (int) $activity_args['user_id'] ) {
			bp_notifications_add_notification( array(
				'user_id'           => $parent_comment->user_id,
				'item_id'           => $activity_id,
				'secondary_item_id' => $post_type_comment->user_id,
				'component_name'    => buddypress()->activity->id,
				'component_action'  => 'comment_reply',
				'date_notified'     => $post_type_comment->comment_date_gmt,
				'is_new'            => 1,
			) );
		}
	}
}
add_action( 'bp_blogs_comment_sync_activity_comment', 'bp_activity_add_notification_for_synced_blog_comment', 10, 4 );

/**
 * Add activity notifications settings to the notifications settings page.
 *
 * @since 1.2.0
 */
function bp_activity_screen_notification_settings() {
	if ( bp_activity_do_mentions() ) {
		if ( ! $mention = bp_get_user_meta( bp_displayed_user_id(), 'notification_activity_new_mention', true ) ) {
			$mention = 'yes';
		}
	}

	if ( ! $reply = bp_get_user_meta( bp_displayed_user_id(), 'notification_activity_new_reply', true ) ) {
		$reply = 'yes';
	}

	?>

	<table class="notification-settings" id="activity-notification-settings">
		<thead>
			<tr>
				<th class="icon">&nbsp;</th>
				<th class="title"><?php _e( 'Activity', 'buddypress' ) ?></th>
				<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
				<th class="no"><?php _e( 'No', 'buddypress' )?></th>
			</tr>
		</thead>

		<tbody>
			<?php if ( bp_activity_do_mentions() ) : ?>
				<tr id="activity-notification-settings-mentions">
					<td>&nbsp;</td>
					<td>
						<?php
						/* translators: %s: the displayed user username */
						printf( __( 'A member mentions you in an update using "@%s"', 'buddypress' ), bp_core_get_username( bp_displayed_user_id() ) );
						?>
					</td>
					<td class="yes"><input type="radio" name="notifications[notification_activity_new_mention]" id="notification-activity-new-mention-yes" value="yes" <?php checked( $mention, 'yes', true ) ?>/><label for="notification-activity-new-mention-yes" class="bp-screen-reader-text"><?php
						/* translators: accessibility text */
						esc_html_e( 'Yes, send email', 'buddypress' );
					?></label></td>
					<td class="no"><input type="radio" name="notifications[notification_activity_new_mention]" id="notification-activity-new-mention-no" value="no" <?php checked( $mention, 'no', true ) ?>/><label for="notification-activity-new-mention-no" class="bp-screen-reader-text"><?php
						/* translators: accessibility text */
						esc_html_e( 'No, do not send email', 'buddypress' );
					?></label></td>
				</tr>
			<?php endif; ?>

			<tr id="activity-notification-settings-replies">
				<td>&nbsp;</td>
				<td><?php _e( "A member replies to an update or comment you've posted", 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_activity_new_reply]" id="notification-activity-new-reply-yes" value="yes" <?php checked( $reply, 'yes', true ) ?>/><label for="notification-activity-new-reply-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					esc_html_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_activity_new_reply]" id="notification-activity-new-reply-no" value="no" <?php checked( $reply, 'no', true ) ?>/><label for="notification-activity-new-reply-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					esc_html_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>

			<?php

			/**
			 * Fires inside the closing </tbody> tag for activity screen notification settings.
			 *
			 * @since 1.2.0
			 */
			do_action( 'bp_activity_screen_notification_settings' ) ?>
		</tbody>
	</table>

<?php
}
add_action( 'bp_notification_settings', 'bp_activity_screen_notification_settings', 1 );
