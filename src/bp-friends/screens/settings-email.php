<?php
/**
 * Friends: Integration into user's "Settings > Email" screen
 *
 * @package BuddyPress
 * @subpackage FriendsScreens
 * @since 3.0.0
 */

/**
 * Add Friends-related settings to the Settings > Notifications page.
 *
 * @since 1.0.0
 */
function friends_screen_notification_settings() {

	if ( !$send_requests = bp_get_user_meta( bp_displayed_user_id(), 'notification_friends_friendship_request', true ) )
		$send_requests   = 'yes';

	if ( !$accept_requests = bp_get_user_meta( bp_displayed_user_id(), 'notification_friends_friendship_accepted', true ) )
		$accept_requests = 'yes'; ?>

	<table class="notification-settings" id="friends-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _ex( 'Friends', 'Friend settings on notification settings page', 'buddypress' ) ?></th>
				<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
				<th class="no"><?php _e( 'No', 'buddypress' )?></th>
			</tr>
		</thead>

		<tbody>
			<tr id="friends-notification-settings-request">
				<td></td>
				<td><?php _ex( 'A member sends you a friendship request', 'Friend settings on notification settings page', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_friends_friendship_request]" id="notification-friends-friendship-request-yes" value="yes" <?php checked( $send_requests, 'yes', true ) ?>/><label for="notification-friends-friendship-request-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_friends_friendship_request]" id="notification-friends-friendship-request-no" value="no" <?php checked( $send_requests, 'no', true ) ?>/><label for="notification-friends-friendship-request-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>
			<tr id="friends-notification-settings-accepted">
				<td></td>
				<td><?php _ex( 'A member accepts your friendship request', 'Friend settings on notification settings page', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_friends_friendship_accepted]" id="notification-friends-friendship-accepted-yes" value="yes" <?php checked( $accept_requests, 'yes', true ) ?>/><label for="notification-friends-friendship-accepted-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_friends_friendship_accepted]" id="notification-friends-friendship-accepted-no" value="no" <?php checked( $accept_requests, 'no', true ) ?>/><label for="notification-friends-friendship-accepted-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
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