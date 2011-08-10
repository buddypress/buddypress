<?php
/**
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function friends_screen_my_friends() {
	global $bp;

	// Delete any friendship acceptance notifications for the user when viewing a profile
	bp_core_delete_notifications_by_type( $bp->loggedin_user->id, $bp->friends->id, 'friendship_accepted' );

	do_action( 'friends_screen_my_friends' );

	bp_core_load_template( apply_filters( 'friends_template_my_friends', 'members/single/home' ) );
}

function friends_screen_requests() {
	if ( bp_is_action_variable( 'accept', 0 ) && is_numeric( bp_action_variable( 1 ) ) ) {
		// Check the nonce
		check_admin_referer( 'friends_accept_friendship' );

		if ( friends_accept_friendship( bp_action_variable( 1 ) ) )
			bp_core_add_message( __( 'Friendship accepted', 'buddypress' ) );
		else
			bp_core_add_message( __( 'Friendship could not be accepted', 'buddypress' ), 'error' );

		bp_core_redirect( bp_loggedin_user_domain() . bp_current_component() . '/' . bp_current_action() );

	} elseif ( bp_is_action_variable( 'reject', 0 ) && is_numeric( bp_action_variable( 1 ) ) ) {
		// Check the nonce
		check_admin_referer( 'friends_reject_friendship' );

		if ( friends_reject_friendship( bp_action_variable( 1 ) ) )
			bp_core_add_message( __( 'Friendship rejected', 'buddypress' ) );
		else
			bp_core_add_message( __( 'Friendship could not be rejected', 'buddypress' ), 'error' );

		bp_core_redirect( bp_loggedin_user_domain() . bp_current_component() . '/' . bp_current_action() );
	}

	do_action( 'friends_screen_requests' );

	if ( isset( $_GET['new'] ) )
		bp_core_delete_notifications_by_type( bp_loggedin_user_id(), 'friends', 'friendship_request' );

	bp_core_load_template( apply_filters( 'friends_template_requests', 'members/single/home' ) );
}

function friends_screen_notification_settings() {
	global $bp;

	if ( !$send_requests = bp_get_user_meta( $bp->displayed_user->id, 'notification_friends_friendship_request', true ) )
		$send_requests   = 'yes';

	if ( !$accept_requests = bp_get_user_meta( $bp->displayed_user->id, 'notification_friends_friendship_accepted', true ) )
		$accept_requests = 'yes';
?>

	<table class="notification-settings" id="friends-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _e( 'Friends', 'buddypress' ) ?></th>
				<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
				<th class="no"><?php _e( 'No', 'buddypress' )?></th>
			</tr>
		</thead>

		<tbody>
			<tr id="friends-notification-settings-request">
				<td></td>
				<td><?php _e( 'A member sends you a friendship request', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_friends_friendship_request]" value="yes" <?php checked( $send_requests, 'yes', true ) ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_friends_friendship_request]" value="no" <?php checked( $send_requests, 'no', true ) ?>/></td>
			</tr>
			<tr id="friends-notification-settings-accepted">
				<td></td>
				<td><?php _e( 'A member accepts your friendship request', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_friends_friendship_accepted]" value="yes" <?php checked( $accept_requests, 'yes', true ) ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_friends_friendship_accepted]" value="no" <?php checked( $accept_requests, 'no', true ) ?>/></td>
			</tr>

			<?php do_action( 'friends_screen_notification_settings' ); ?>

		</tbody>
	</table>

<?php
}
add_action( 'bp_notification_settings', 'friends_screen_notification_settings' );

?>