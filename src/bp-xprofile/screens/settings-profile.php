<?php
/**
 * XProfile: User's "Settings > Profile Visibility" screen handler
 *
 * @package BuddyPress
 * @subpackage XProfileScreens
 * @since 3.0.0
 */

/**
 * Show the xprofile settings template.
 *
 * @since 2.0.0
 */
function bp_xprofile_screen_settings() {

	// Redirect if no privacy settings page is accessible.
	if ( bp_action_variables() || ! bp_is_active( 'xprofile' ) ) {
		bp_do_404();
		return;
	}

	$templates = array(
		/**
		 * Filters the template to load for the XProfile settings screen.
		 *
		 * @since 2.0.0
		 *
		 * @param string $template Path to the XProfile change avatar template to load.
		 */
		apply_filters( 'bp_settings_screen_xprofile', '/members/single/settings/profile' ),
		'members/single/index',
	);

	bp_core_load_template( $templates );
}

/**
 * Handles the saving of xprofile field visibilities.
 *
 * @since 1.9.0
 */
function bp_xprofile_action_settings() {

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		return;
	}

	// Bail if no submit action.
	if ( ! isset( $_POST['xprofile-settings-submit'] ) ) {
		return;
	}

	// Bail if not in settings.
	if ( ! bp_is_user_settings_profile() ) {
		return;
	}

	// 404 if there are any additional action variables attached.
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// Nonce check.
	check_admin_referer( 'bp_xprofile_settings' );

	/**
	 * Fires before saving xprofile field visibilities.
	 *
	 * @since 2.0.0
	 */
	do_action( 'bp_xprofile_settings_before_save' );

	/* Save ******************************************************************/

	// Only save if there are field ID's being posted.
	if ( ! empty( $_POST['field_ids'] ) ) {

		// Get the POST'ed field ID's.
		$posted_field_ids = explode( ',', $_POST['field_ids'] );

		// Backward compatibility: a bug in BP 2.0 caused only a single
		// group's field IDs to be submitted. Look for values submitted
		// in the POST request that may not appear in 'field_ids', and
		// add them to the list of IDs to save.
		foreach ( $_POST as $posted_key => $posted_value ) {
			preg_match( '/^field_([0-9]+)_visibility$/', $posted_key, $matches );
			if ( ! empty( $matches[1] ) && ! in_array( $matches[1], $posted_field_ids ) ) {
				$posted_field_ids[] = $matches[1];
			}
		}

		// Save the visibility settings.
		foreach ( $posted_field_ids as $field_id ) {

			$visibility_level = 'public';

			if ( ! empty( $_POST[ 'field_' . $field_id . '_visibility' ] ) ) {
				$visibility_level = $_POST[ 'field_' . $field_id . '_visibility' ];
			}

			xprofile_set_field_visibility_level( $field_id, bp_displayed_user_id(), $visibility_level );
		}
	}

	/* Other *****************************************************************/

	/**
	 * Fires after saving xprofile field visibilities.
	 *
	 * @since 2.0.0
	 */
	do_action( 'bp_xprofile_settings_after_save' );

	// Redirect to the User's profile settings.
	bp_core_redirect(
		bp_displayed_user_url( bp_members_get_path_chunks( array( bp_get_settings_slug(), 'profile' ) ) )
	);
}
add_action( 'bp_actions', 'bp_xprofile_action_settings' );
