<?php
/**
 * Groups: Single group "Manage > Photo" screen handler
 *
 * @package BuddyPress
 * @subpackage GroupsScreens
 * @since 3.0.0
 */

/**
 * Handle the display of a group's Change Avatar page.
 *
 * @since 1.0.0
 */
function groups_screen_group_admin_avatar() {

	if ( 'group-avatar' !== bp_get_group_current_admin_tab() ) {
		return;
	}

	// If the logged-in user doesn't have permission or if avatar uploads are disabled, then stop here.
	if ( ! bp_is_item_admin() || bp_disable_group_avatar_uploads() || ! buddypress()->avatar->show_avatars ) {
		return;
	}

	$bp = buddypress();

	// If the group admin has deleted the admin avatar.
	if ( bp_is_action_variable( 'delete', 1 ) ) {

		// Check the nonce.
		check_admin_referer( 'bp_group_avatar_delete' );

		if ( bp_core_delete_existing_avatar( array( 'item_id' => $bp->groups->current_group->id, 'object' => 'group' ) ) ) {
			bp_core_add_message( __( 'The group profile photo was deleted successfully!', 'buddypress' ) );
		} else {
			bp_core_add_message( __( 'There was a problem deleting the group profile photo. Please try again.', 'buddypress' ), 'error' );
		}
	}

	if ( ! isset( $bp->avatar_admin ) ) {
		$bp->avatar_admin = new stdClass();
	}

	$bp->avatar_admin->step = 'upload-image';

	if ( ! empty( $_FILES ) ) {

		// Check the nonce.
		check_admin_referer( 'bp_avatar_upload' );

		// Pass the file to the avatar upload handler.
		if ( bp_core_avatar_handle_upload( $_FILES, 'groups_avatar_upload_dir' ) ) {
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
			'object'        => 'group',
			'avatar_dir'    => 'group-avatars',
			'item_id'       => $bp->groups->current_group->id,
			'original_file' => $_POST['image_src'],
			'crop_x'        => $_POST['x'],
			'crop_y'        => $_POST['y'],
			'crop_w'        => $_POST['w'],
			'crop_h'        => $_POST['h']
		);

		$cropped_avatar = bp_core_avatar_handle_crop( $args, 'array' );

		if ( ! $cropped_avatar ) {
			bp_core_add_message( __( 'There was a problem cropping the group profile photo.', 'buddypress' ), 'error' );
		} else {
			/**
			 * Fires after a group avatar is uploaded.
			 *
			 * @since 2.8.0
			 * @since 10.0.0 Adds a new param: an array containing the full, thumb avatar and the timestamp.
			 *
			 * @param int    $group_id       ID of the group.
			 * @param string $type           Avatar type. 'crop' or 'camera'.
			 * @param array  $args           Array of parameters passed to the avatar handler.
			 * @param array  $cropped_avatar Array containing the full, thumb avatar and the timestamp.
			 */
			do_action( 'groups_avatar_uploaded', bp_get_current_group_id(), 'crop', $args, $cropped_avatar );
			bp_core_add_message( __( 'The new group profile photo was uploaded successfully.', 'buddypress' ) );
		}
	}

	/**
	 * Fires before the loading of the group Change Avatar page template.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the group that is being displayed.
	 */
	do_action( 'groups_screen_group_admin_avatar', $bp->groups->current_group->id );

	$templates = array(
		/**
		 * Filters the template to load for a group's Change Avatar page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Path to a group's Change Avatar template.
		 */
		apply_filters( 'groups_template_group_admin_avatar', 'groups/single/home' ),
		'groups/single/index',
	);

	bp_core_load_template( $templates );
}
add_action( 'bp_screens', 'groups_screen_group_admin_avatar' );
