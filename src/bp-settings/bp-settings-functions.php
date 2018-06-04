<?php
/**
 * BuddyPress Settings Functions
 *
 * @package BuddyPress
 * @subpackage SettingsFunctions
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Update email notification settings for a specific user.
 *
 * @since 2.3.5
 *
 * @param int   $user_id  ID of the user whose settings are being updated.
 * @param array $settings Settings array.
 */
function bp_settings_update_notification_settings( $user_id, $settings ) {
	$user_id = (int) $user_id;

	$settings = bp_settings_sanitize_notification_settings( $settings );
	foreach ( $settings as $setting_key => $setting_value ) {
		bp_update_user_meta( $user_id, $setting_key, $setting_value );
	}
}

/**
 * Sanitize email notification settings as submitted by a user.
 *
 * @since 2.3.5
 *
 * @param array $settings Array of settings.
 * @return array Sanitized settings.
 */
function bp_settings_sanitize_notification_settings( $settings = array() ) {
	$sanitized_settings = array();

	if ( empty( $settings ) ) {
		return $sanitized_settings;
	}

	// Get registered notification keys.
	$registered_notification_settings = bp_settings_get_registered_notification_keys();

	/*
	 * We sanitize values for core notification keys.
	 *
	 * @todo use register_meta()
	 */
	$core_notification_settings = array(
		'notification_messages_new_message',
		'notification_activity_new_mention',
		'notification_activity_new_reply',
		'notification_groups_invite',
		'notification_groups_group_updated',
		'notification_groups_admin_promotion',
		'notification_groups_membership_request',
		'notification_membership_request_completed',
		'notification_friends_friendship_request',
		'notification_friends_friendship_accepted',
	);

	foreach ( (array) $settings as $key => $value ) {
		// Skip if not a registered setting.
		if ( ! in_array( $key, $registered_notification_settings, true ) ) {
			continue;
		}

		// Force core keys to 'yes' or 'no' values.
		if ( in_array( $key, $core_notification_settings, true ) ) {
			$value = 'yes' === $value ? 'yes' : 'no';
		}

		$sanitized_settings[ $key ] = $value;
	}

	return $sanitized_settings;
}

/**
 * Build a dynamic whitelist of notification keys, based on what's hooked to 'bp_notification_settings'.
 *
 * @since 2.3.5
 *
 * @return array
 */
function bp_settings_get_registered_notification_keys() {

	ob_start();
	/**
	 * Fires at the start of the notification keys whitelisting.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bp_notification_settings' );
	$screen = ob_get_clean();

	$matched = preg_match_all( '/<input[^>]+name="notifications\[([^\]]+)\]/', $screen, $matches );

	if ( $matched && isset( $matches[1] ) ) {
		$key_whitelist = $matches[1];
	} else {
		$key_whitelist = array();
	}

	return $key_whitelist;
}

/**
 * Finds and exports personal data associated with an email address from the Settings component.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_settings_personal_data_exporter( $email_address, $page ) {
	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$yes = __( 'Yes', 'buddypress' );
	$no  = __( 'No', 'buddypress' );

	$user_settings = array();

	// These settings all default to 'yes' when nothing is saved, so we have to do some pre-processing.
	$notification_settings = array();

	if ( bp_is_active( 'activity' ) ) {
		$notification_settings[] = array(
			'name' => __( 'Receive email when a member mentions you in an update?', 'buddypress' ),
			'key'  => 'notification_activity_new_mention',
		);
		$notification_settings[] = array(
			'name' => __( 'Receive email when a member replies to an update or comment you\'ve posted?', 'buddypress' ),
			'key'  => 'notification_activity_new_reply',
		);
	}

	if ( bp_is_active( 'messages' ) ) {
		$notification_settings[] = array(
			'name' => __( 'Receive email when a member sends you a new message?', 'buddypress' ),
			'key'  => 'notification_messages_new_message',
		);
	}

	if ( bp_is_active( 'friends' ) ) {
		$notification_settings[] = array(
			'name' => __( 'Receive email when a member invites you to join a group?', 'buddypress' ),
			'key'  => 'notification_groups_invite',
		);
	}

	if ( bp_is_active( 'groups' ) ) {
		$notification_settings[] = array(
			'name' => __( 'Receive email when group information is updated?', 'buddypress' ),
			'key'  => 'notification_groups_group_updated',
		);
		$notification_settings[] = array(
			'name' => __( 'Receive email when you are promoted to a group administrator or moderator?', 'buddypress' ),
			'key'  => 'notification_groups_admin_promoted',
		);
		$notification_settings[] = array(
			'name' => __( 'Receive email when a member requests to join a private group for which you are an admin?', 'buddypress' ),
			'key'  => 'notification_groups_membership_request',
		);
		$notification_settings[] = array(
			'name' => __( 'Receive email when your request to join a group has been approved or denied?', 'buddypress' ),
			'key'  => 'notification_membership_request_completed',
		);
	}

	foreach ( $notification_settings as $notification_setting ) {
		$user_notification_setting = bp_get_user_meta( $user->ID, $notification_setting['key'], true );
		if ( empty( $user_notification_setting ) ) {
			$user_notification_setting = 'yes';
		}

		$user_settings[] = array(
			'name'  => $notification_setting['name'],
			'value' => 'yes' === $user_notification_setting ? $yes : $no,
		);
	}

	if ( function_exists( 'bp_nouveau_groups_get_group_invites_setting' ) ) {
		$user_settings[] = array(
			'name'  => __( 'Receive group invitations from my friends only?', 'buddypress' ),
			'value' => bp_nouveau_groups_get_group_invites_setting() ? $yes : $no,
		);
	}

	$data_to_export[] = array(
		'group_id'    => 'bp_settings',
		'group_label' => __( 'Settings', 'buddypress' ),
		'item_id'     => "bp-settings-{$user->ID}",
		'data'        => $user_settings,
	);

	return array(
		'data' => $data_to_export,
		'done' => true,
	);
}
