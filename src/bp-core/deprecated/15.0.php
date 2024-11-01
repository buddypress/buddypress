<?php
/**
 * Deprecated functions.
 *
 * @package BuddyPress
 * @deprecated 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output whether signup is allowed.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 */
function bp_signup_allowed() {
	_deprecated_function( __FUNCTION__, '15.0.0' );
}

/**
 * Migrate signups from pre-2.0 configuration to wp_signups.
 *
 * @since 2.0.1
 * @deprecated 15.0.0
 *
 * @global wpdb $wpdb WordPress database object.
 */
function bp_members_migrate_signups() {
	_deprecated_function( __FUNCTION__, '15.0.0' );
	global $wpdb;

	$status_2_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->users} WHERE user_status = '2'" );

	if ( ! empty( $status_2_ids ) ) {
		$signups = get_users(
			array(
				'fields'  => array( 'ID', 'user_login', 'user_pass', 'user_registered', 'user_email', 'display_name' ),
				'include' => $status_2_ids,
			)
		);

		// Fetch activation keys separately, to avoid the all_with_meta overhead.
		$status_2_ids_sql = implode( ',', $status_2_ids );
		$ak_data = $wpdb->get_results( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'activation_key' AND user_id IN ({$status_2_ids_sql})" );

		// Rekey.
		$activation_keys = array();
		foreach ( $ak_data as $ak_datum ) {
			$activation_keys[ intval( $ak_datum->user_id ) ] = $ak_datum->meta_value;
		}

		unset( $status_2_ids_sql, $status_2_ids, $ak_data );

		// Merge.
		foreach ( $signups as &$signup ) {
			if ( isset( $activation_keys[ $signup->ID ] ) ) {
				$signup->activation_key = $activation_keys[ $signup->ID ];
			}
		}

		// Reset the signup var as we're using it to process the migration.
		unset( $signup );

	} else {
		return;
	}

	foreach ( $signups as $signup ) {
		$meta = array();

		// Rebuild the activation key, if missing.
		if ( empty( $signup->activation_key ) ) {
			$signup->activation_key = wp_generate_password( 32, false );
		}

		if ( bp_is_active( 'xprofile' ) ) {
			$meta['field_1'] = $signup->display_name;
		}

		$meta['password'] = $signup->user_pass;

		$user_login = preg_replace( '/\s+/', '', sanitize_user( $signup->user_login, true ) );
		$user_email = sanitize_email( $signup->user_email );

		BP_Signup::add(
			array(
				'user_login'     => $user_login,
				'user_email'     => $user_email,
				'registered'     => $signup->user_registered,
				'activation_key' => $signup->activation_key,
				'meta'           => $meta
			)
		);

		// Deleting these options will remove signups from users count.
		delete_user_option( $signup->ID, 'capabilities' );
		delete_user_option( $signup->ID, 'user_level'   );
	}
}
