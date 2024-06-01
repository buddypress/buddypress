<?php
/**
 * Members: Change Avatar screen handler
 *
 * @package BuddyPress
 * @subpackage MembersScreens
 * @since 6.0.0
 */

/**
 * Handle the display of the profile Change Avatar page by loading the correct template file.
 *
 * @since 6.0.0
 */
function bp_members_screen_change_avatar() {
	// Bail if not the correct screen.
	if ( ! bp_is_my_profile() && ! bp_current_user_can( 'bp_moderate' ) ) {
		return;
	}

	// Bail if there are action variables.
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	$bp = buddypress();

	if ( ! isset( $bp->avatar_admin ) ) {
		$bp->avatar_admin = new stdClass();
	}

	$bp->avatar_admin->step = 'upload-image';

	if ( ! empty( $_FILES ) ) {

		// Check the nonce.
		check_admin_referer( 'bp_avatar_upload' );

		// Pass the file to the avatar upload handler.
		if ( bp_core_avatar_handle_upload( $_FILES, 'bp_members_avatar_upload_dir' ) ) {
			$bp->avatar_admin->step = 'crop-image';

			// Make sure we include the jQuery jCrop file for image cropping.
			add_action( 'wp_print_scripts', 'bp_core_add_jquery_cropper' );
		}
	}

	// If the image cropping is done, crop the image and save a full/thumb version.
	if ( isset( $_POST['avatar-crop-submit'] ) ) {

		// Check the nonce.
		check_admin_referer( 'bp_avatar_cropstore' );

		$args = array(
			'item_id'       => bp_displayed_user_id(),
			'original_file' => $_POST['image_src'],
			'crop_x'        => $_POST['x'],
			'crop_y'        => $_POST['y'],
			'crop_w'        => $_POST['w'],
			'crop_h'        => $_POST['h']
		);

		// Handle crop.
		$cropped_avatar = bp_core_avatar_handle_crop( $args, 'array' );

		if ( ! $cropped_avatar ) {
			bp_core_add_message( __( 'There was a problem cropping your profile photo.', 'buddypress' ), 'error' );
		} else {

			/** This action is documented in wp-includes/deprecated.php */
			do_action_deprecated( 'xprofile_avatar_uploaded', array( (int) $args['item_id'], 'crop' ), '6.0.0', 'bp_members_avatar_uploaded' );

			/**
			 * Fires right before the redirect, after processing a new avatar.
			 *
			 * @since 6.0.0
			 * @since 10.0.0 Adds a new param: an array containing the full, thumb avatar and the timestamp.
			 *
			 * @param string $item_id        Inform about the user id the avatar was set for.
			 * @param string $type           Inform about the way the avatar was set ('camera').
			 * @param array  $args           Array of parameters passed to the crop handler.
			 * @param array  $cropped_avatar Array containing the full, thumb avatar and the timestamp.
			 */
			do_action( 'bp_members_avatar_uploaded', (int) $args['item_id'], 'crop', $args, $cropped_avatar );

			bp_core_add_message( __( 'Your new profile photo was uploaded successfully.', 'buddypress' ) );
			bp_core_redirect( bp_displayed_user_url() );
		}
	}

	/** This action is documented in wp-includes/deprecated.php */
	do_action_deprecated( 'xprofile_screen_change_avatar', array(), '6.0.0', 'bp_members_screen_change_avatar' );

	/**
	 * Fires right before the loading of the Member Change Avatar screen template file.
	 *
	 * @since 6.0.0
	 */
	do_action( 'bp_members_screen_change_avatar' );

	$templates = array(
		/** This filter is documented in wp-includes/deprecated.php */
		apply_filters_deprecated( 'xprofile_template_change_avatar', array( 'members/single/home' ), '6.0.0', 'bp_members_template_change_avatar' ),
		'members/single/index'
	);

	/**
	 * Filters the template to load for the Member Change Avatar page screen.
	 *
	 * @since 6.0.0
	 *
	 * @param string[] $templates Path to the Member templates to load.
	 */
	bp_core_load_template( apply_filters( 'bp_members_template_change_avatar', $templates ) );
}
