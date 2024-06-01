<?php
/**
 * XProfile: User's "Profile > Edit" screen handler
 *
 * @package BuddyPress
 * @subpackage XProfileScreens
 * @since 3.0.0
 */

/**
 * Handles the display of the profile edit page by loading the correct template file.
 * Also checks to make sure this can only be accessed for the logged in users profile.
 *
 * @since 1.0.0
 */
function xprofile_screen_edit_profile() {

	if ( ! bp_is_my_profile() && ! bp_current_user_can( 'bp_moderate' ) ) {
		return;
	}

	// Make sure a group is set.
	if ( ! bp_action_variable( 1 ) ) {
		bp_core_redirect(
			bp_displayed_user_url( bp_members_get_path_chunks( array( bp_get_profile_slug(), 'edit', array( 'group', 1 ) ) ) )
		);
	}

	// Check the field group exists.
	if ( ! bp_is_action_variable( 'group' ) || ! xprofile_get_field_group( bp_action_variable( 1 ) ) ) {
		bp_do_404();
		return;
	}

	// No errors.
	$errors      = false;
	$path_chunks = array( bp_get_profile_slug(), 'edit' );

	// Check to see if any new information has been submitted.
	if ( isset( $_POST['field_ids'] ) ) {

		// Check the nonce.
		check_admin_referer( 'bp_xprofile_edit' );

		// Check we have field ID's.
		if ( empty( $_POST['field_ids'] ) ) {
			$path_chunks[] = array( 'group', bp_action_variable( 1 ) );
			bp_core_redirect( bp_displayed_user_url( bp_members_get_path_chunks( $path_chunks ) ) );
		}

		// Explode the posted field IDs into an array so we know which
		// fields have been submitted.
		$posted_field_ids = wp_parse_id_list( $_POST['field_ids'] );
		$is_required      = array();

		$bp_displayed_user = bp_get_displayed_user();
		$bp_displayed_user->updated_keys = array();

		// Loop through the posted fields formatting any datebox values then validate the field.
		foreach ( (array) $posted_field_ids as $field_id ) {
			bp_xprofile_maybe_format_datebox_post_data( $field_id );

			$is_required[ $field_id ] = xprofile_check_is_required_field( $field_id ) && ! bp_current_user_can( 'bp_moderate' );
			if ( $is_required[ $field_id ] && empty( $_POST[ 'field_' . $field_id ] ) ) {
				$errors = true;
			}
		}

		// There are errors.
		if ( ! empty( $errors ) ) {
			bp_core_add_message( __( 'Your changes have not been saved. Please fill in all required fields, and save your changes again.', 'buddypress' ), 'error' );

		// No errors.
		} else {

			// Reset the errors var.
			$errors = false;

			// Now we've checked for required fields, lets save the values.
			$old_values = $new_values = array();
			foreach ( (array) $posted_field_ids as $field_id ) {

				// Certain types of fields (checkboxes, multiselects) may come through empty. Save them as an empty array so that they don't get overwritten by the default on the next edit.
				$value = isset( $_POST[ 'field_' . $field_id ] ) ? $_POST[ 'field_' . $field_id ] : '';

				$visibility_level = ! empty( $_POST[ 'field_' . $field_id . '_visibility' ] ) ? $_POST[ 'field_' . $field_id . '_visibility' ] : 'public';

				// Save the old and new values. They will be
				// passed to the filter and used to determine
				// whether an activity item should be posted.
				$old_values[ $field_id ] = array(
					'value'      => xprofile_get_field_data( $field_id, bp_displayed_user_id() ),
					'visibility' => xprofile_get_field_visibility_level( $field_id, bp_displayed_user_id() ),
				);

				// Update the field data and visibility level.
				xprofile_set_field_visibility_level( $field_id, bp_displayed_user_id(), $visibility_level );
				$field_updated = xprofile_set_field_data( $field_id, bp_displayed_user_id(), $value, $is_required[ $field_id ] );
				$value         = xprofile_get_field_data( $field_id, bp_displayed_user_id() );

				$new_values[ $field_id ] = array(
					'value'      => $value,
					'visibility' => xprofile_get_field_visibility_level( $field_id, bp_displayed_user_id() ),
				);

				if ( ! $field_updated ) {
					$errors = true;
				} else {

					/**
					 * Fires on each iteration of an XProfile field being saved with no error.
					 *
					 * @since 1.1.0
					 *
					 * @param int    $field_id ID of the field that was saved.
					 * @param string $value    Value that was saved to the field.
					 */
					do_action( 'xprofile_profile_field_data_updated', $field_id, $value );
				}
			}

			/**
			 * Fires after all XProfile fields have been saved for the current profile.
			 *
			 * @since 1.0.0
			 *
			 * @param int   $value            Displayed user ID.
			 * @param array $posted_field_ids Array of field IDs that were edited.
			 * @param bool  $errors           Whether or not any errors occurred.
			 * @param array $old_values       Array of original values before updated.
			 * @param array $new_values       Array of newly saved values after update.
			 */
			do_action( 'xprofile_updated_profile', bp_displayed_user_id(), $posted_field_ids, $errors, $old_values, $new_values );

			// Some WP User keys have been updated: let's update the WP fiels all together.
			if ( $bp_displayed_user->updated_keys ) {
				$user_id = wp_update_user(
					array_merge(
						array(
							'ID' => bp_displayed_user_id(),
						),
						$bp_displayed_user->updated_keys
					)
				);

				$bp_displayed_user->updated_keys = array();

				if ( is_wp_error( $user_id ) ) {
					$errors = true;
				}
			}

			// Set the feedback messages.
			if ( ! empty( $errors ) ) {
				bp_core_add_message( __( 'There was a problem updating some of your profile information. Please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Changes saved.', 'buddypress' ) );
			}

			// Redirect back to the edit screen to display the updates and message.
			$path_chunks[] = array( 'group', bp_action_variable( 1 ) );
			bp_core_redirect( bp_displayed_user_url( bp_members_get_path_chunks( $path_chunks ) ) );
		}
	}

	/**
	 * Fires right before the loading of the XProfile edit screen template file.
	 *
	 * @since 1.0.0
	 */
	do_action( 'xprofile_screen_edit_profile' );

	$templates = array(
		/**
		 * Filters the template to load for the XProfile edit screen.
		 *
		 * @since 1.0.0
		 *
		 * @param string $template Path to the XProfile edit template to load.
		 */
		apply_filters( 'xprofile_template_edit_profile', 'members/single/home' ),
		'members/single/index',
	);

	bp_core_load_template( $templates );
}
