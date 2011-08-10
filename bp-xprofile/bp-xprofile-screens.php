<?php
/*******************************************************************************
 * Screen functions are the controllers of BuddyPress. They will execute when
 * their specific URL is caught. They will first save or manipulate data using
 * business functions, then pass on the user to a template file.
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
	global $bp;

	if ( !bp_is_my_profile() && !is_super_admin() )
		return false;

	// Make sure a group is set.
	if ( !bp_action_variable( 1 ) )
		bp_core_redirect( bp_displayed_user_domain() . $bp->profile->slug . '/edit/group/1' );

	// Check the field group exists
	if ( !bp_is_action_variable( 'group' ) || !xprofile_get_field_group( bp_action_variable( 1 ) ) ) {
		bp_do_404();
		return;
	}

	// Check to see if any new information has been submitted
	if ( isset( $_POST['field_ids'] ) ) {

		// Check the nonce
		check_admin_referer( 'bp_xprofile_edit' );

		// Check we have field ID's
		if ( empty( $_POST['field_ids'] ) )
			bp_core_redirect( trailingslashit( $bp->displayed_user->domain . $bp->profile->slug . '/edit/group/' . bp_action_variable( 1 ) ) );

		// Explode the posted field IDs into an array so we know which
		// fields have been submitted
		$posted_field_ids = explode( ',', $_POST['field_ids'] );
		$is_required      = array();

		// Loop through the posted fields formatting any datebox values
		// then validate the field
		foreach ( (array)$posted_field_ids as $field_id ) {
			if ( !isset( $_POST['field_' . $field_id] ) ) {

				if ( !empty( $_POST['field_' . $field_id . '_day'] ) && is_numeric( $_POST['field_' . $field_id . '_day'] ) ) {
					// Concatenate the values
					$date_value =   $_POST['field_' . $field_id . '_day'] . ' ' . $_POST['field_' . $field_id . '_month'] . ' ' . $_POST['field_' . $field_id . '_year'];

					// Turn the concatenated value into a timestamp
					$_POST['field_' . $field_id] = strtotime( $date_value );
				}

			}

			$is_required[$field_id] = xprofile_check_is_required_field( $field_id );
			if ( $is_required[$field_id] && empty( $_POST['field_' . $field_id] ) )
				$errors = true;
		}

		// There are errors
		if ( !empty( $errors ) ) {
			bp_core_add_message( __( 'Please make sure you fill in all required fields in this profile field group before saving.', 'buddypress' ), 'error' );

		// No errors
		} else {
			// Reset the errors var
			$errors = false;

			// Now we've checked for required fields, lets save the values.
			foreach ( (array)$posted_field_ids as $field_id ) {

				// Certain types of fields (checkboxes, multiselects) may come through empty. Save them as an empty array so that they don't get overwritten by the default on the next edit.
				if ( empty( $_POST['field_' . $field_id] ) )
					$value = array();
				else
					$value = $_POST['field_' . $field_id];

				if ( !xprofile_set_field_data( $field_id, $bp->displayed_user->id, $value, $is_required[$field_id] ) )
					$errors = true;
				else
					do_action( 'xprofile_profile_field_data_updated', $field_id, $value );
			}

			do_action( 'xprofile_updated_profile', $bp->displayed_user->id, $posted_field_ids, $errors );

			// Set the feedback messages
			if ( $errors )
				bp_core_add_message( __( 'There was a problem updating some of your profile information, please try again.', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'Changes saved.', 'buddypress' ) );

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
	global $bp;

	if ( !bp_is_my_profile() && !is_super_admin() )
		return false;

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

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

		if ( !bp_core_avatar_handle_crop( array( 'item_id' => $bp->displayed_user->id, 'original_file' => $_POST['image_src'], 'crop_x' => $_POST['x'], 'crop_y' => $_POST['y'], 'crop_w' => $_POST['w'], 'crop_h' => $_POST['h'] ) ) )
			bp_core_add_message( __( 'There was a problem cropping your avatar, please try uploading it again', 'buddypress' ), 'error' );
		else {
			bp_core_add_message( __( 'Your new avatar was uploaded successfully!', 'buddypress' ) );
			do_action( 'xprofile_avatar_uploaded' );
		}
	}

	do_action( 'xprofile_screen_change_avatar' );

	bp_core_load_template( apply_filters( 'xprofile_template_change_avatar', 'members/single/home' ) );
}

?>