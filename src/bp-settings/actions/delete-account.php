<?php
/**
 * Settings: Account deletion action handler
 *
 * @package BuddyPress
 * @subpackage SettingsActions
 * @since 3.0.0
 */

/**
 * Handles the deleting of a user.
 *
 * @since 1.6.0
 */
function bp_settings_action_delete_account() {
	if ( ! bp_is_post_request() ) {
		return;
	}

	// Bail if no submit action.
	if ( ! isset( $_POST['delete-account-understand'] ) ) {
		return;
	}

	// Bail if not in settings.
	if ( ! bp_is_settings_component() || ! bp_is_current_action( 'delete-account' ) ) {
		return false;
	}

	// 404 if there are any additional action variables attached
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// Bail if account deletion is disabled.
	if ( bp_disable_account_deletion() && ! bp_current_user_can( 'delete_users' ) ) {
		return false;
	}

	// Nonce check.
	check_admin_referer( 'delete-account' );

	// Get username now because it might be gone soon!
	$username = bp_get_displayed_user_fullname();

	// Delete the users account.
	if ( bp_core_delete_account( bp_displayed_user_id() ) ) {

		// Add feedback after deleting a user.
		bp_core_add_message(
			sprintf(
				/* translators: %s: user username */
				__( '%s was successfully deleted.', 'buddypress' ),
				$username
			),
			'success'
		);

		// Redirect to the root domain.
		bp_core_redirect( bp_get_root_domain() );
	}
}
add_action( 'bp_actions', 'bp_settings_action_delete_account' );
