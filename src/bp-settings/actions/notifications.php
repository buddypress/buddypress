<?php
/**
 * Settings: Email notifications action handler.
 *
 * @package BuddyPress
 * @subpackage SettingsActions
 * @since 3.0.0
 */

/**
 * Handles the changing and saving of user notification settings.
 *
 * @since 1.6.0
 *
 * @return bool|void
 */
function bp_settings_action_notifications() {
	if ( ! bp_is_post_request() ) {
		return;
	}

	// Bail if no submit action.
	if ( ! isset( $_POST['submit'] ) ) {
		return;
	}

	// Bail if not in settings.
	if ( ! bp_is_settings_component() || ! bp_is_current_action( 'notifications' ) ) {
		return false;
	}

	// 404 if there are any additional action variables attached
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	check_admin_referer( 'bp_settings_notifications' );

	bp_settings_update_notification_settings( bp_displayed_user_id(), (array) $_POST['notifications'] );

	// Switch feedback for super admins.
	if ( bp_is_my_profile() ) {
		bp_core_add_message( __( 'Your notification settings have been saved.',        'buddypress' ), 'success' );
	} else {
		bp_core_add_message( __( "This user's notification settings have been saved.", 'buddypress' ), 'success' );
	}

	/**
	 * Fires after the notification settings have been saved, and before redirect.
	 *
	 * @since 1.5.0
	 */
	do_action( 'bp_core_notification_settings_after_save' );

	$settings_slug = bp_get_settings_slug();
	$path_chunks   = array(
		'single_item_component' => bp_rewrites_get_slug( 'members', 'member_' . $settings_slug, $settings_slug ),
		'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_' . $settings_slug . '_notifications', 'notifications' ),
	);

	bp_core_redirect( bp_displayed_user_url( $path_chunks ) );
}
add_action( 'bp_actions', 'bp_settings_action_notifications' );
