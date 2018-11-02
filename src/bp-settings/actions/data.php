<?php
/**
 * Settings: Data management action handler
 *
 * @package BuddyPress
 * @subpackage SettingsActions
 * @since 4.0.0
 */

/**
 * Data export request handler.
 *
 * @since 4.0.0
 */
function bp_settings_action_data() {
	if ( ! bp_is_post_request() || ! bp_displayed_user_id() || empty( $_POST['bp-data-export-nonce'] ) ) {
		return;
	}

	// Nonce check.
	check_admin_referer( 'bp-data-export', 'bp-data-export-nonce' );

	// Delete existing request if available.
	if ( ! empty( $_POST['bp-data-export-delete-request-nonce'] ) && wp_verify_nonce( $_POST['bp-data-export-delete-request-nonce'], 'bp-data-export-delete-request' ) ) {
		$existing = bp_settings_get_personal_data_request();
		if ( ! empty( $existing->ID ) ) {
			wp_delete_post( $existing->ID, true );
		}
	}

	// Create the user request.
	$request_id = wp_create_user_request( buddypress()->displayed_user->userdata->user_email, 'export_personal_data' );

	$success = true;
	if ( is_wp_error( $request_id ) ) {
		$success = false;
		$message = $request_id->get_error_message();
	} elseif ( ! $request_id ) {
		$success = false;
		$message = __( 'We were unable to generate the data export request.', 'buddypress' );
	}

	/*
	 * Auto-confirm the user request since the user already consented by
	 * submitting our form.
	 */
	if ( $success ) {
		/** This hook is documented in /wp-login.php */
		do_action( 'user_request_action_confirmed', $request_id );

		$message = __( 'Data export request successfully created', 'buddypress' );
	}

	/**
	 * Fires after a user has created a data export request.
	 *
	 * This hook can be used to intervene in the data export request process.
	 *
	 * @since 4.0.0
	 *
	 * @param int  $request_id ID of the request.
	 * @param bool $success    Whether the request was successfully created by WordPress.
	 */
	do_action( 'bp_user_data_export_requested', $request_id, $success );

	bp_core_add_message( $message, $success ? 'success' : 'error' );
	bp_core_redirect( bp_get_requested_url() );
}
add_action( 'bp_actions', 'bp_settings_action_data' );
