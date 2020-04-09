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

/**
 * Fetches a user's personal data request.
 *
 * @since 4.0.0
 *
 * @param int WP user ID.
 * @return WP_User_Request|false WP_User_Request object on success, boolean false on failure.
 */
function bp_settings_get_personal_data_request( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	$user = get_userdata( $user_id );
	if ( empty( $user ) ) {
		return false;
	}

	$query = new WP_Query( array(
		'author'        => (int) $user_id,
		'post_type'     => 'user_request',
		'post_status'   => 'any',
		'post_name__in' => array(
			'export_personal_data',
		),
	) );

	if ( ! empty( $query->post ) ) {
		// WP 5.4 changed the user request function name to wp_get_user_request()
		$user_request = function_exists( 'wp_get_user_request' ) ? 'wp_get_user_request' : 'wp_get_user_request_data';
		return $user_request( $query->post->ID );
	} else {
		return false;
	}
}

/**
 * Fetches the expiration date for when a user request expires.
 *
 * @since 4.0.0
 *
 * @param WP_User_Request $request User request object.
 * @return string Formatted date.
 */
function bp_settings_get_personal_data_expiration_date( WP_User_Request $request ) {
	/** This filter is documented in wp-admin/includes/file.php */
	$expiration = apply_filters( 'wp_privacy_export_expiration', 3 * DAY_IN_SECONDS );

	return bp_format_time( $request->completed_timestamp + $expiration, true );
}

/**
 * Fetches the confirmation date for a user request object.
 *
 * @since 4.0.0
 *
 * @param WP_User_Request $request User request object.
 * @return string Formatted date for the confirmation date.
 */
function bp_settings_get_personal_data_confirmation_date( WP_User_Request $request ) {
	return bp_format_time( $request->confirmed_timestamp, true );
}

/**
 * Fetches the URL for a personal data export file.
 *
 * @since 4.0.0
 *
 * @param WP_User_Request $request User request object.
 * @return string Export file URL.
 */
function bp_settings_get_personal_data_export_url( WP_User_Request $request ) {
	return get_post_meta( $request->ID, '_export_file_url', true );
}

/**
 * Check if the generated data export file still exists or not.
 *
 * @since 4.0.0
 *
 * @param  WP_User_Request $request User request object.
 * @return bool
 */
function bp_settings_personal_data_export_exists( WP_User_Request $request ) {
	$file = get_post_meta( $request->ID, '_export_file_path', true );
	if ( file_exists( $file ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Template tag to output a list of data exporter items.
 *
 * Piggybacks off of the 'wp_privacy_personal_data_exporters' filter and the
 * 'exporter_friendly_name' key, which is meant for the admin area.
 *
 * @since 4.0.0
 * @since 5.0.0 Looks for a potential exporter's BP/custom friendly name.
 */
function bp_settings_data_exporter_items() {
	/** This filter is documented in /wp-admin/includes/ajax-actions.php */
	$exporters             = apply_filters( 'wp_privacy_personal_data_exporters', array() );
	$custom_friendly_names = apply_filters( 'bp_settings_data_custom_friendly_names', array(
		'wordpress-comments' => _x( 'Comments', 'WP Comments data exporter friendly name', 'buddypress' ),
		'wordpress-media'    => _x( 'Media', 'WP Media data exporter friendly name', 'buddypress' ),
		'wordpress-user'     => _x( 'Personal information', 'WP Media data exporter friendly name', 'buddypress' ),
	) );

?>
	<ul>
	<?php foreach ( $exporters as $exporter => $data ) :
		// Use the exporter friendly name by default.
		$friendly_name = $data['exporter_friendly_name'];

		/**
		 * Use the exporter friendly name if directly available
		 * into the exporters array.
		 */
		if ( isset( $data['exporter_bp_friendly_name'] ) ) {
			$friendly_name = $data['exporter_bp_friendly_name'];

		// Look for a potential match into the custom friendly names.
		} elseif ( isset( $custom_friendly_names[ $exporter ] ) ) {
			$friendly_name = $custom_friendly_names[ $exporter ];
		}

		/**
		 * Filters the data exporter friendly name for display on the "Settings > Data" page.
		 *
		 * @since 4.0.0
		 * @since 5.0.0 replaces the `$name` parameter with the `$friendly_name` one.
		 *
		 * @param string $friendly_name Data exporter friendly name.
		 * @param string $exporter      Internal exporter name.
		 */
		$item = apply_filters( 'bp_settings_data_exporter_name', esc_html( $friendly_name ), $exporter );
	?>

		<li><?php echo $item; ?></li>

	<?php endforeach; ?>
	</ul>

<?php
}
