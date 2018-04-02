<?php
/**
 * Messages: Integration into user's "Settings > Email" screen
 *
 * @package BuddyPress
 * @subpackage MessageScreens
 * @since 3.0.0
 */

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
				<th class="title"><?php _e( 'Messages', 'buddypress' ) ?></th>
				<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
				<th class="no"><?php _e( 'No', 'buddypress' )?></th>
			</tr>
		</thead>

		<tbody>
			<tr id="messages-notification-settings-new-message">
				<td></td>
				<td><?php _e( 'A member sends you a new message', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_messages_new_message]" id="notification-messages-new-messages-yes" value="yes" <?php checked( $new_messages, 'yes', true ) ?>/><label for="notification-messages-new-messages-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_messages_new_message]" id="notification-messages-new-messages-no" value="no" <?php checked( $new_messages, 'no', true ) ?>/><label for="notification-messages-new-messages-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
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