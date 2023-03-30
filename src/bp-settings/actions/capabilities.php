<?php
/**
 * Settings: Capabilities action handler.
 *
 * @package BuddyPress
 * @subpackage SettingsActions
 * @since 3.0.0
 */

/**
 * Handles the setting of user capabilities, spamming, hamming, role, etc.
 *
 * @since 1.6.0
 */
function bp_settings_action_capabilities() {
	if ( ! bp_is_post_request() ) {
		return;
	}

	// Bail if no submit action.
	if ( ! isset( $_POST['capabilities-submit'] ) ) {
		return;
	}

	// Bail if not in settings.
	if ( ! bp_is_settings_component() || ! bp_is_current_action( 'capabilities' ) ) {
		return false;
	}

	// 404 if there are any additional action variables attached
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// Only super admins can currently spam users (but they can't spam
	// themselves).
	if ( ! is_super_admin() || bp_is_my_profile() ) {
		return;
	}

	// Nonce check.
	check_admin_referer( 'capabilities' );

	/**
	 * Fires before the capabilities settings have been saved.
	 *
	 * @since 1.6.0
	 */
	do_action( 'bp_settings_capabilities_before_save' );

	/* Spam **************************************************************/

	$is_spammer = ! empty( $_POST['user-spammer'] ) ? true : false;

	if ( bp_is_user_spammer( bp_displayed_user_id() ) != $is_spammer ) {
		$status = ( true == $is_spammer ) ? 'spam' : 'ham';
		bp_core_process_spammer_status( bp_displayed_user_id(), $status );

		/**
		 * Fires after processing a user as a spammer.
		 *
		 * @since 1.1.0
		 *
		 * @param int    $value  ID of the currently displayed user.
		 * @param string $status Determined status of "spam" or "ham" for the displayed user.
		 */
		do_action( 'bp_core_action_set_spammer_status', bp_displayed_user_id(), $status );
	}

	/* Other *************************************************************/

	/**
	 * Fires after the capabilities settings have been saved and before redirect.
	 *
	 * @since 1.6.0
	 */
	do_action( 'bp_settings_capabilities_after_save' );

	$path_chunks = bp_members_get_path_chunks( array( bp_get_settings_slug(), 'capabilities' ) );
	$redirect    = bp_displayed_user_url( $path_chunks );

	// Redirect to the settings capability page.
	bp_core_redirect( $redirect );
}
add_action( 'bp_actions', 'bp_settings_action_capabilities' );
