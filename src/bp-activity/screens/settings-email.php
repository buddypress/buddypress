<?php
/**
 * Activity: Integration into user's "Settings > Email" screen
 *
 * @package BuddyPress
 * @subpackage ActivityScreens
 * @since 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
					<td><?php printf( __( 'A member mentions you in an update using "@%s"', 'buddypress' ), bp_core_get_username( bp_displayed_user_id() ) ) ?></td>
					<td class="yes"><input type="radio" name="notifications[notification_activity_new_mention]" id="notification-activity-new-mention-yes" value="yes" <?php checked( $mention, 'yes', true ) ?>/><label for="notification-activity-new-mention-yes" class="bp-screen-reader-text"><?php
						/* translators: accessibility text */
						_e( 'Yes, send email', 'buddypress' );
					?></label></td>
					<td class="no"><input type="radio" name="notifications[notification_activity_new_mention]" id="notification-activity-new-mention-no" value="no" <?php checked( $mention, 'no', true ) ?>/><label for="notification-activity-new-mention-no" class="bp-screen-reader-text"><?php
						/* translators: accessibility text */
						_e( 'No, do not send email', 'buddypress' );
					?></label></td>
				</tr>
			<?php endif; ?>

			<tr id="activity-notification-settings-replies">
				<td>&nbsp;</td>
				<td><?php _e( "A member replies to an update or comment you've posted", 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_activity_new_reply]" id="notification-activity-new-reply-yes" value="yes" <?php checked( $reply, 'yes', true ) ?>/><label for="notification-activity-new-reply-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_activity_new_reply]" id="notification-activity-new-reply-no" value="no" <?php checked( $reply, 'no', true ) ?>/><label for="notification-activity-new-reply-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
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