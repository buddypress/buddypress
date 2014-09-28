<?php

/**
 * BuddyPress XProfile Screens
 *
 * Screen functions are the controllers of BuddyPress. They will execute when
 * their specific URL is caught. They will first save or manipulate data using
 * business functions, then pass on the user to a template file.
 *
 * @package BuddyPress
 * @subpackage XProfileScreens
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Handles the display of the profile page by loading the correct template file.
 *
 * @package BuddyPress XProfile
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_screen_display_profile() {
	$new = isset( $_GET['new'] ) ? $_GET['new'] : '';

	do_action( 'xprofile_screen_display_profile', $new );
	bp_core_load_template( apply_filters( 'xprofile_template_display_profile', 'members/single/home' ) );
}

/**
 * Handles the display of the profile edit page by loading the correct template file.
 * Also checks to make sure this can only be accessed for the logged in users profile.
 *
 * @package BuddyPress XProfile
 * @uses bp_is_my_profile() Checks to make sure the current user being viewed equals the logged in user
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_screen_edit_profile() {

	if ( !bp_is_my_profile() && !bp_current_user_can( 'bp_moderate' ) )
		return false;

	$bp = buddypress();

	// Make sure a group is set.
	if ( !bp_action_variable( 1 ) )
		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . $bp->profile->slug . '/edit/group/1' ) );

	// Check the field group exists
	if ( !bp_is_action_variable( 'group' ) || !xprofile_get_field_group( bp_action_variable( 1 ) ) ) {
		bp_do_404();
		return;
	}

	// No errors
	$errors = false;

	// Check to see if any new information has been submitted
	if ( isset( $_POST['field_ids'] ) ) {

		// Check the nonce
		check_admin_referer( 'bp_xprofile_edit' );

		// Check we have field ID's
		if ( empty( $_POST['field_ids'] ) )
			bp_core_redirect( trailingslashit( bp_displayed_user_domain() . $bp->profile->slug . '/edit/group/' . bp_action_variable( 1 ) ) );

		// Explode the posted field IDs into an array so we know which
		// fields have been submitted
		$posted_field_ids = wp_parse_id_list( $_POST['field_ids'] );
		$is_required      = array();

		// Loop through the posted fields formatting any datebox values
		// then validate the field
		foreach ( (array) $posted_field_ids as $field_id ) {
			if ( !isset( $_POST['field_' . $field_id] ) ) {

				if ( !empty( $_POST['field_' . $field_id . '_day'] ) && !empty( $_POST['field_' . $field_id . '_month'] ) && !empty( $_POST['field_' . $field_id . '_year'] ) ) {
					// Concatenate the values
					$date_value =   $_POST['field_' . $field_id . '_day'] . ' ' . $_POST['field_' . $field_id . '_month'] . ' ' . $_POST['field_' . $field_id . '_year'];

					// Turn the concatenated value into a timestamp
					$_POST['field_' . $field_id] = date( 'Y-m-d H:i:s', strtotime( $date_value ) );
				}

			}

			$is_required[$field_id] = xprofile_check_is_required_field( $field_id );
			if ( $is_required[$field_id] && empty( $_POST['field_' . $field_id] ) ) {
				$errors = true;
			}
		}

		// There are errors
		if ( !empty( $errors ) ) {
			bp_core_add_message( __( 'Please make sure you fill in all required fields in this profile field group before saving.', 'buddypress' ), 'error' );

		// No errors
		} else {

			// Reset the errors var
			$errors = false;

			// Now we've checked for required fields, lets save the values.
			$old_values = $new_values = array();
			foreach ( (array) $posted_field_ids as $field_id ) {

				// Certain types of fields (checkboxes, multiselects) may come through empty. Save them as an empty array so that they don't get overwritten by the default on the next edit.
				$value = isset( $_POST['field_' . $field_id] ) ? $_POST['field_' . $field_id] : '';

				$visibility_level = !empty( $_POST['field_' . $field_id . '_visibility'] ) ? $_POST['field_' . $field_id . '_visibility'] : 'public';

				// Save the old and new values. They will be
				// passed to the filter and used to determine
				// whether an activity item should be posted
				$old_values[ $field_id ] = array(
					'value'      => xprofile_get_field_data( $field_id, bp_displayed_user_id() ),
					'visibility' => xprofile_get_field_visibility_level( $field_id, bp_displayed_user_id() ),
				);

				// Update the field data and visibility level
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
					do_action( 'xprofile_profile_field_data_updated', $field_id, $value );
				}
			}

			do_action( 'xprofile_updated_profile', bp_displayed_user_id(), $posted_field_ids, $errors, $old_values, $new_values );

			// Set the feedback messages
			if ( !empty( $errors ) ) {
				bp_core_add_message( __( 'There was a problem updating some of your profile information; please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Changes saved.', 'buddypress' ) );
			}

			// Redirect back to the edit screen to display the updates and message
			bp_core_redirect( trailingslashit( bp_displayed_user_domain() . $bp->profile->slug . '/edit/group/' . bp_action_variable( 1 ) ) );
		}
	}

	do_action( 'xprofile_screen_edit_profile' );
	bp_core_load_template( apply_filters( 'xprofile_template_edit_profile', 'members/single/home' ) );
}

/**
 * Handles the uploading and cropping of a user avatar. Displays the change avatar page.
 *
 * @package BuddyPress XProfile
 * @uses bp_is_my_profile() Checks to make sure the current user being viewed equals the logged in user
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_screen_change_avatar() {

	// Bail if not the correct screen
	if ( !bp_is_my_profile() && !bp_current_user_can( 'bp_moderate' ) )
		return false;

	// Bail if there are action variables
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	$bp = buddypress();

	if ( ! isset( $bp->avatar_admin ) )
		$bp->avatar_admin = new stdClass();

	$bp->avatar_admin->step = 'upload-image';

	if ( !empty( $_FILES ) ) {

		// Check the nonce
		check_admin_referer( 'bp_avatar_upload' );

		// Pass the file to the avatar upload handler
		if ( bp_core_avatar_handle_upload( $_FILES, 'xprofile_avatar_upload_dir' ) ) {
			$bp->avatar_admin->step = 'crop-image';

			// Make sure we include the jQuery jCrop file for image cropping
			add_action( 'wp_print_scripts', 'bp_core_add_jquery_cropper' );
		}
	}

	// If the image cropping is done, crop the image and save a full/thumb version
	if ( isset( $_POST['avatar-crop-submit'] ) ) {

		// Check the nonce
		check_admin_referer( 'bp_avatar_cropstore' );

		$args = array(
			'item_id'       => bp_displayed_user_id(),
			'original_file' => $_POST['image_src'],
			'crop_x'        => $_POST['x'],
			'crop_y'        => $_POST['y'],
			'crop_w'        => $_POST['w'],
			'crop_h'        => $_POST['h']
		);

		if ( ! bp_core_avatar_handle_crop( $args ) ) {
			bp_core_add_message( __( 'There was a problem cropping your profile photo.', 'buddypress' ), 'error' );
		} else {
			do_action( 'xprofile_avatar_uploaded' );
			bp_core_add_message( __( 'Your new profile photo was uploaded successfully.', 'buddypress' ) );
			bp_core_redirect( bp_displayed_user_domain() );
		}
	}

	do_action( 'xprofile_screen_change_avatar' );

	bp_core_load_template( apply_filters( 'xprofile_template_change_avatar', 'members/single/home' ) );
}

/**
 * Show the xprofile settings template
 *
 * @since BuddyPress (2.0.0)
 */
function bp_xprofile_screen_settings() {

	// Redirect if no privacy settings page is accessible
	if ( bp_action_variables() || ! bp_is_active( 'xprofile' ) ) {
		bp_do_404();
		return;
	}

	// Load the template
	bp_core_load_template( apply_filters( 'bp_settings_screen_xprofile', '/members/single/settings/profile' ) );
}
