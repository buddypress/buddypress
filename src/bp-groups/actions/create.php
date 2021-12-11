<?php
/**
 * Groups: Create action
 *
 * @package BuddyPress
 * @subpackage GroupActions
 * @since 3.0.0
 */

/**
 * Catch and process group creation form submissions.
 *
 * @since 1.2.0
 *
 * @return bool
 */
function groups_action_create_group() {

	// If we're not at domain.org/groups/create/ then return false.
	if ( !bp_is_groups_component() || !bp_is_current_action( 'create' ) )
		return false;

	if ( !is_user_logged_in() )
		return false;

	if ( !bp_user_can_create_groups() ) {
		bp_core_add_message( __( 'Sorry, you are not allowed to create groups.', 'buddypress' ), 'error' );
		bp_core_redirect( bp_get_groups_directory_permalink() );
	}

	$bp = buddypress();

	// Make sure creation steps are in the right order.
	groups_action_sort_creation_steps();

	// If no current step is set, reset everything so we can start a fresh group creation.
	$bp->groups->current_create_step = bp_action_variable( 1 );
	if ( !bp_get_groups_current_create_step() ) {
		unset( $bp->groups->current_create_step );
		unset( $bp->groups->completed_create_steps );

		setcookie( 'bp_new_group_id', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
		setcookie( 'bp_completed_create_steps', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );

		$reset_steps = true;
		$keys        = array_keys( $bp->groups->group_creation_steps );
		bp_core_redirect( trailingslashit( bp_get_groups_directory_permalink() . 'create/step/' . array_shift( $keys ) ) );
	}

	// If this is a creation step that is not recognized, just redirect them back to the first screen.
	if ( bp_get_groups_current_create_step() && empty( $bp->groups->group_creation_steps[bp_get_groups_current_create_step()] ) ) {
		bp_core_add_message( __('There was an error saving group details. Please try again.', 'buddypress'), 'error' );
		bp_core_redirect( trailingslashit( bp_get_groups_directory_permalink() . 'create' ) );
	}

	// Fetch the currently completed steps variable.
	if ( isset( $_COOKIE['bp_completed_create_steps'] ) && !isset( $reset_steps ) )
		$bp->groups->completed_create_steps = json_decode( base64_decode( stripslashes( $_COOKIE['bp_completed_create_steps'] ) ) );

	// Set the ID of the new group, if it has already been created in a previous step.
	if ( bp_get_new_group_id() ) {
		$bp->groups->current_group = groups_get_group( $bp->groups->new_group_id );

		// Only allow the group creator to continue to edit the new group.
		if ( ! bp_is_group_creator( $bp->groups->current_group, bp_loggedin_user_id() ) ) {
			bp_core_add_message( __( 'Only the group creator may continue editing this group.', 'buddypress' ), 'error' );
			bp_core_redirect( trailingslashit( bp_get_groups_directory_permalink() . 'create' ) );
		}
	}

	// If the save, upload or skip button is hit, lets calculate what we need to save.
	if ( isset( $_POST['save'] ) ) {

		// Check the nonce.
		check_admin_referer( 'groups_create_save_' . bp_get_groups_current_create_step() );

		if ( 'group-details' == bp_get_groups_current_create_step() ) {
			if ( empty( $_POST['group-name'] ) || empty( $_POST['group-desc'] ) || !strlen( trim( $_POST['group-name'] ) ) || !strlen( trim( $_POST['group-desc'] ) ) ) {
				bp_core_add_message( __( 'Please fill in all of the required fields', 'buddypress' ), 'error' );
				bp_core_redirect( trailingslashit( bp_get_groups_directory_permalink() . 'create/step/' . bp_get_groups_current_create_step() ) );
			}

			$new_group_id = isset( $bp->groups->new_group_id ) ? $bp->groups->new_group_id : 0;

			if ( !$bp->groups->new_group_id = groups_create_group( array( 'group_id' => $new_group_id, 'name' => $_POST['group-name'], 'description' => $_POST['group-desc'], 'slug' => groups_check_slug( sanitize_title( esc_attr( $_POST['group-name'] ) ) ), 'date_created' => bp_core_current_time(), 'status' => 'public' ) ) ) {
				bp_core_add_message( __( 'There was an error saving group details. Please try again.', 'buddypress' ), 'error' );
				bp_core_redirect( trailingslashit( bp_get_groups_directory_permalink() . 'create/step/' . bp_get_groups_current_create_step() ) );
			}
		}

		if ( 'group-settings' == bp_get_groups_current_create_step() ) {
			$group_status = 'public';
			$group_enable_forum = 1;

			if ( !isset($_POST['group-show-forum']) ) {
				$group_enable_forum = 0;
			}

			if ( 'private' == $_POST['group-status'] )
				$group_status = 'private';
			elseif ( 'hidden' == $_POST['group-status'] )
				$group_status = 'hidden';

			if ( !$bp->groups->new_group_id = groups_create_group( array( 'group_id' => $bp->groups->new_group_id, 'status' => $group_status, 'enable_forum' => $group_enable_forum ) ) ) {
				bp_core_add_message( __( 'There was an error saving group details. Please try again.', 'buddypress' ), 'error' );
				bp_core_redirect( trailingslashit( bp_get_groups_directory_permalink() . 'create/step/' . bp_get_groups_current_create_step() ) );
			}

			// Save group types.
			if ( ! empty( $_POST['group-types'] ) ) {
				bp_groups_set_group_type( $bp->groups->new_group_id, $_POST['group-types'] );
			}

			/**
			 * Filters the allowed invite statuses.
			 *
			 * @since 1.5.0
			 *
			 * @param array $value Array of statuses allowed.
			 *                     Possible values are 'members,
			 *                     'mods', and 'admins'.
			 */
			$allowed_invite_status = apply_filters( 'groups_allowed_invite_status', array( 'members', 'mods', 'admins' ) );
			$invite_status	       = !empty( $_POST['group-invite-status'] ) && in_array( $_POST['group-invite-status'], (array) $allowed_invite_status ) ? $_POST['group-invite-status'] : 'members';

			groups_update_groupmeta( $bp->groups->new_group_id, 'invite_status', $invite_status );
		}

		if ( 'group-invites' === bp_get_groups_current_create_step() ) {
			if ( ! empty( $_POST['friends'] ) ) {
				foreach ( (array) $_POST['friends'] as $friend ) {
					groups_invite_user( array(
						'user_id'  => (int) $friend,
						'group_id' => $bp->groups->new_group_id,
					) );
				}
			}

			groups_send_invites( array(	'group_id' => $bp->groups->new_group_id ) );
		}

		/**
		 * Fires before finalization of group creation and cookies are set.
		 *
		 * This hook is a variable hook dependent on the current step
		 * in the creation process.
		 *
		 * @since 1.1.0
		 */
		do_action( 'groups_create_group_step_save_' . bp_get_groups_current_create_step() );

		/**
		 * Fires after the group creation step is completed.
		 *
		 * Mostly for clearing cache on a generic action name.
		 *
		 * @since 1.1.0
		 */
		do_action( 'groups_create_group_step_complete' );

		/**
		 * Once we have successfully saved the details for this step of the creation process
		 * we need to add the current step to the array of completed steps, then update the cookies
		 * holding the information
		 */
		$completed_create_steps = isset( $bp->groups->completed_create_steps ) ? $bp->groups->completed_create_steps : array();
		if ( !in_array( bp_get_groups_current_create_step(), $completed_create_steps ) )
			$bp->groups->completed_create_steps[] = bp_get_groups_current_create_step();

		// Reset cookie info.
		setcookie( 'bp_new_group_id', $bp->groups->new_group_id, time()+60*60*24, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
		setcookie( 'bp_completed_create_steps', base64_encode( json_encode( $bp->groups->completed_create_steps ) ), time()+60*60*24, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );

		// If we have completed all steps and hit done on the final step we
		// can redirect to the completed group.
		$keys = array_keys( $bp->groups->group_creation_steps );
		if ( count( $bp->groups->completed_create_steps ) == count( $keys ) && bp_get_groups_current_create_step() == array_pop( $keys ) ) {
			unset( $bp->groups->current_create_step );
			unset( $bp->groups->completed_create_steps );

			setcookie( 'bp_new_group_id', false, time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
			setcookie( 'bp_completed_create_steps', false, time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );

			// Once we completed all steps, record the group creation in the activity stream.
			if ( bp_is_active( 'activity' ) ) {
				groups_record_activity( array(
					'type' => 'created_group',
					'item_id' => $bp->groups->new_group_id
				) );
			}

			/**
			 * Fires after the group has been successfully created.
			 *
			 * @since 1.1.0
			 *
			 * @param int $new_group_id ID of the newly created group.
			 */
			do_action( 'groups_group_create_complete', $bp->groups->new_group_id );

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
		} else {
			/**
			 * Since we don't know what the next step is going to be (any plugin can insert steps)
			 * we need to loop the step array and fetch the next step that way.
			 */
			foreach ( $keys as $key ) {
				if ( $key == bp_get_groups_current_create_step() ) {
					$next = 1;
					continue;
				}

				if ( isset( $next ) ) {
					$next_step = $key;
					break;
				}
			}

			bp_core_redirect( trailingslashit( bp_get_groups_directory_permalink() . 'create/step/' . $next_step ) );
		}
	}

	// Remove invitations.
	if ( 'group-invites' === bp_get_groups_current_create_step() && ! empty( $_REQUEST['user_id'] ) && is_numeric( $_REQUEST['user_id'] ) ) {
		if ( ! check_admin_referer( 'groups_invite_uninvite_user' ) ) {
			return false;
		}

		$message = __( 'Invite successfully removed', 'buddypress' );
		$error   = false;

		if( ! groups_uninvite_user( (int) $_REQUEST['user_id'], $bp->groups->new_group_id ) ) {
			$message = __( 'There was an error removing the invite', 'buddypress' );
			$error   = 'error';
		}

		bp_core_add_message( $message, $error );
		bp_core_redirect( trailingslashit( bp_get_groups_directory_permalink() . 'create/step/group-invites' ) );
	}

	// Group avatar is handled separately.
	if ( 'group-avatar' == bp_get_groups_current_create_step() && isset( $_POST['upload'] ) ) {
		if ( ! isset( $bp->avatar_admin ) ) {
			$bp->avatar_admin = new stdClass();
		}

		if ( !empty( $_FILES ) && isset( $_POST['upload'] ) ) {
			// Normally we would check a nonce here, but the group save nonce is used instead.
			// Pass the file to the avatar upload handler.
			if ( bp_core_avatar_handle_upload( $_FILES, 'groups_avatar_upload_dir' ) ) {
				$bp->avatar_admin->step = 'crop-image';

				// Make sure we include the jQuery jCrop file for image cropping.
				add_action( 'wp_print_scripts', 'bp_core_add_jquery_cropper' );
			}
		}

		// If the image cropping is done, crop the image and save a full/thumb version.
		if ( isset( $_POST['avatar-crop-submit'] ) && isset( $_POST['upload'] ) ) {

			// Normally we would check a nonce here, but the group save nonce is used instead.
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
				bp_core_add_message( __( 'There was an error saving the group profile photo, please try uploading again.', 'buddypress' ), 'error' );
			} else {
				/**
				 * Fires after a group avatar is uploaded.
				 *
				 * @since 2.8.0
				 * @since 10.0.0 Adds a new param: an array containing the full, thumb avatar and the timestamp.
				 *
				 * @param int    $group_id       ID of the group.
				 * @param string $type           Avatar type. 'crop' or 'camera'.
				 * @param array  $args           Array of parameters passed to the crop handler.
				 * @param array  $cropped_avatar Array containing the full, thumb avatar and the timestamp.
				 */
				do_action( 'groups_avatar_uploaded', bp_get_current_group_id(), 'crop', $args, $cropped_avatar );

				bp_core_add_message( __( 'The group profile photo was uploaded successfully.', 'buddypress' ) );
			}
		}
	}

	/**
	 * Filters the template to load for the group creation screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to the group creation template to load.
	 */
	bp_core_load_template( apply_filters( 'groups_template_create_group', 'groups/create' ) );
}
add_action( 'bp_actions', 'groups_action_create_group' );

/**
 * Sort the group creation steps.
 *
 * @since 1.1.0
 *
 * @return false|null False on failure.
 */
function groups_action_sort_creation_steps() {

	if ( !bp_is_groups_component() || !bp_is_current_action( 'create' ) )
		return false;

	$bp = buddypress();

	if ( !is_array( $bp->groups->group_creation_steps ) )
		return false;

	foreach ( (array) $bp->groups->group_creation_steps as $slug => $step ) {
		while ( !empty( $temp[$step['position']] ) )
			$step['position']++;

		$temp[$step['position']] = array( 'name' => $step['name'], 'slug' => $slug );
	}

	// Sort the steps by their position key.
	ksort($temp);
	unset($bp->groups->group_creation_steps);

	foreach( (array) $temp as $position => $step )
		$bp->groups->group_creation_steps[$step['slug']] = array( 'name' => $step['name'], 'position' => $position );

	/**
	 * Fires after group creation sets have been sorted.
	 *
	 * @since 2.3.0
	 */
	do_action( 'groups_action_sort_creation_steps' );
}
