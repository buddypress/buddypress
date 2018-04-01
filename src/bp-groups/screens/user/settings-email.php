<?php
/**
 * Groups: Integration into user's "Settings > Email" screen
 *
 * @package BuddyPress
 * @subpackage GroupScreens
 * @since 3.0.0
 */

/**
 * Render the group settings fields on the Notification Settings page.
 *
 * @since 1.0.0
 */
function groups_screen_notification_settings() {

	if ( !$group_invite = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_invite', true ) )
		$group_invite  = 'yes';

	if ( !$group_update = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_group_updated', true ) )
		$group_update  = 'yes';

	if ( !$group_promo = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_admin_promotion', true ) )
		$group_promo   = 'yes';

	if ( !$group_request = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_membership_request', true ) )
		$group_request = 'yes';

	if ( ! $group_request_completed = bp_get_user_meta( bp_displayed_user_id(), 'notification_membership_request_completed', true ) ) {
		$group_request_completed = 'yes';
	}
	?>

	<table class="notification-settings" id="groups-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _ex( 'Groups', 'Group settings on notification settings page', 'buddypress' ) ?></th>
				<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
				<th class="no"><?php _e( 'No', 'buddypress' )?></th>
			</tr>
		</thead>

		<tbody>
			<tr id="groups-notification-settings-invitation">
				<td></td>
				<td><?php _ex( 'A member invites you to join a group', 'group settings on notification settings page','buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_groups_invite]" id="notification-groups-invite-yes" value="yes" <?php checked( $group_invite, 'yes', true ) ?>/><label for="notification-groups-invite-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_groups_invite]" id="notification-groups-invite-no" value="no" <?php checked( $group_invite, 'no', true ) ?>/><label for="notification-groups-invite-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>
			<tr id="groups-notification-settings-info-updated">
				<td></td>
				<td><?php _ex( 'Group information is updated', 'group settings on notification settings page', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_groups_group_updated]" id="notification-groups-group-updated-yes" value="yes" <?php checked( $group_update, 'yes', true ) ?>/><label for="notification-groups-group-updated-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_groups_group_updated]" id="notification-groups-group-updated-no" value="no" <?php checked( $group_update, 'no', true ) ?>/><label for="notification-groups-group-updated-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>
			<tr id="groups-notification-settings-promoted">
				<td></td>
				<td><?php _ex( 'You are promoted to a group administrator or moderator', 'group settings on notification settings page', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_groups_admin_promotion]" id="notification-groups-admin-promotion-yes" value="yes" <?php checked( $group_promo, 'yes', true ) ?>/><label for="notification-groups-admin-promotion-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_groups_admin_promotion]" id="notification-groups-admin-promotion-no" value="no" <?php checked( $group_promo, 'no', true ) ?>/><label for="notification-groups-admin-promotion-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>
			<tr id="groups-notification-settings-request">
				<td></td>
				<td><?php _ex( 'A member requests to join a private group for which you are an admin', 'group settings on notification settings page', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_groups_membership_request]" id="notification-groups-membership-request-yes" value="yes" <?php checked( $group_request, 'yes', true ) ?>/><label for="notification-groups-membership-request-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_groups_membership_request]" id="notification-groups-membership-request-no" value="no" <?php checked( $group_request, 'no', true ) ?>/><label for="notification-groups-membership-request-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>
			<tr id="groups-notification-settings-request-completed">
				<td></td>
				<td><?php _ex( 'Your request to join a group has been approved or denied', 'group settings on notification settings page', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_membership_request_completed]" id="notification-groups-membership-request-completed-yes" value="yes" <?php checked( $group_request_completed, 'yes', true ) ?>/><label for="notification-groups-membership-request-completed-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_membership_request_completed]" id="notification-groups-membership-request-completed-no" value="no" <?php checked( $group_request_completed, 'no', true ) ?>/><label for="notification-groups-membership-request-completed-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>

			<?php

			/**
			 * Fires at the end of the available group settings fields on Notification Settings page.
			 *
			 * @since 1.0.0
			 */
			do_action( 'groups_screen_notification_settings' ); ?>

		</tbody>
	</table>

<?php
}
add_action( 'bp_notification_settings', 'groups_screen_notification_settings' );