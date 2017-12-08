<?php
/**
 * BuddyPress Groups Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when
 * their specific URL is caught. They will first save or manipulate data using
 * business functions, then pass on the user to a template file.
 *
 * @package BuddyPress
 * @subpackage GroupsScreens
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the display of the Groups directory index.
 *
 * @since 1.0.0
 */
function groups_directory_groups_setup() {
	if ( bp_is_groups_directory() ) {
		bp_update_is_directory( true, 'groups' );

		/**
		 * Fires before the loading of the Groups directory index.
		 *
		 * @since 1.1.0
		 */
		do_action( 'groups_directory_groups_setup' );

		/**
		 * Filters the template to load for the Groups directory index.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Path to the groups directory index template to load.
		 */
		bp_core_load_template( apply_filters( 'groups_template_directory_groups', 'groups/index' ) );
	}
}
add_action( 'bp_screens', 'groups_directory_groups_setup', 2 );

/**
 * Handle the loading of the My Groups page.
 *
 * @since 1.0.0
 */
function groups_screen_my_groups() {

	/**
	 * Fires before the loading of the My Groups page.
	 *
	 * @since 1.1.0
	 */
	do_action( 'groups_screen_my_groups' );

	/**
	 * Filters the template to load for the My Groups page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to the My Groups page template to load.
	 */
	bp_core_load_template( apply_filters( 'groups_template_my_groups', 'members/single/home' ) );
}

/**
 * Handle the loading of a user's Groups > Invites page.
 *
 * @since 1.0.0
 */
function groups_screen_group_invites() {
	$group_id = (int)bp_action_variable( 1 );

	if ( bp_is_action_variable( 'accept' ) && is_numeric( $group_id ) ) {
		// Check the nonce.
		if ( !check_admin_referer( 'groups_accept_invite' ) )
			return false;

		if ( !groups_accept_invite( bp_loggedin_user_id(), $group_id ) ) {
			bp_core_add_message( __('Group invite could not be accepted', 'buddypress'), 'error' );
		} else {
			// Record this in activity streams.
			$group = groups_get_group( $group_id );

			bp_core_add_message( sprintf( __( 'Group invite accepted. Visit %s.', 'buddypress' ), bp_get_group_link( $group ) ) );

			groups_record_activity( array(
				'type'    => 'joined_group',
				'item_id' => $group->id
			) );
		}

		if ( isset( $_GET['redirect_to'] ) ) {
			$redirect_to = urldecode( $_GET['redirect_to'] );
		} else {
			$redirect_to = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() . '/' . bp_current_action() );
		}

		bp_core_redirect( $redirect_to );

	} elseif ( bp_is_action_variable( 'reject' ) && is_numeric( $group_id ) ) {
		// Check the nonce.
		if ( !check_admin_referer( 'groups_reject_invite' ) )
			return false;

		if ( !groups_reject_invite( bp_loggedin_user_id(), $group_id ) ) {
			bp_core_add_message( __( 'Group invite could not be rejected', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Group invite rejected', 'buddypress' ) );
		}

		if ( isset( $_GET['redirect_to'] ) ) {
			$redirect_to = urldecode( $_GET['redirect_to'] );
		} else {
			$redirect_to = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() . '/' . bp_current_action() );
		}

		bp_core_redirect( $redirect_to );
	}

	/**
	 * Fires before the loading of a users Groups > Invites template.
	 *
	 * @since 1.0.0
	 *
	 * @param int $group_id ID of the group being displayed
	 */
	do_action( 'groups_screen_group_invites', $group_id );

	/**
	 * Filters the template to load for a users Groups > Invites page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to a users Groups > Invites page template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_invites', 'members/single/home' ) );
}

/**
 * Handle the loading of a single group's page.
 *
 * @since 1.0.0
 */
function groups_screen_group_home() {

	if ( ! bp_is_single_item() ) {
		return false;
	}

	/**
	 * Fires before the loading of a single group's page.
	 *
	 * @since 1.0.0
	 */
	do_action( 'groups_screen_group_home' );

	/**
	 * Filters the template to load for a single group's page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to a single group's template to load.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
}

/**
 * Handle the display of a group's Members page.
 *
 * @since 1.0.0
 */
function groups_screen_group_members() {

	if ( !bp_is_single_item() )
		return false;

	$bp = buddypress();

	// Refresh the group member count meta.
	groups_update_groupmeta( $bp->groups->current_group->id, 'total_member_count', groups_get_total_member_count( $bp->groups->current_group->id ) );

	/**
	 * Fires before the loading of a group's Members page.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the group whose members are being displayed.
	 */
	do_action( 'groups_screen_group_members', $bp->groups->current_group->id );

	/**
	 * Filters the template to load for a group's Members page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to a group's Members template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_members', 'groups/single/home' ) );
}

/**
 * Handle the display of a group's Send Invites page.
 *
 * @since 1.0.0
 */
function groups_screen_group_invite() {

	if ( !bp_is_single_item() )
		return false;

	$bp = buddypress();

	if ( bp_is_action_variable( 'send', 0 ) ) {

		if ( !check_admin_referer( 'groups_send_invites', '_wpnonce_send_invites' ) )
			return false;

		if ( !empty( $_POST['friends'] ) ) {
			foreach( (array) $_POST['friends'] as $friend ) {
				groups_invite_user( array( 'user_id' => $friend, 'group_id' => $bp->groups->current_group->id ) );
			}
		}

		// Send the invites.
		groups_send_invites( bp_loggedin_user_id(), $bp->groups->current_group->id );
		bp_core_add_message( __('Group invites sent.', 'buddypress') );

		/**
		 * Fires after the sending of a group invite inside the group's Send Invites page.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id ID of the group whose members are being displayed.
		 */
		do_action( 'groups_screen_group_invite', $bp->groups->current_group->id );
		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );

	} elseif ( !bp_action_variable( 0 ) ) {

		/**
		 * Filters the template to load for a group's Send Invites page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Path to a group's Send Invites template.
		 */
		bp_core_load_template( apply_filters( 'groups_template_group_invite', 'groups/single/home' ) );

	} else {
		bp_do_404();
	}
}

/**
 * Process group invitation removal requests.
 *
 * Note that this function is only used when JS is disabled. Normally, clicking
 * Remove Invite removes the invitation via AJAX.
 *
 * @since 2.0.0
 */
function groups_remove_group_invite() {
	if ( ! bp_is_group_invites() ) {
		return;
	}

	if ( ! bp_is_action_variable( 'remove', 0 ) || ! is_numeric( bp_action_variable( 1 ) ) ) {
		return;
	}

	if ( ! check_admin_referer( 'groups_invite_uninvite_user' ) ) {
		return false;
	}

	$friend_id = intval( bp_action_variable( 1 ) );
	$group_id  = bp_get_current_group_id();
	$message   = __( 'Invite successfully removed', 'buddypress' );
	$redirect  = wp_get_referer();
	$error     = false;

	if ( ! bp_groups_user_can_send_invites( $group_id ) ) {
		$message = __( 'You are not allowed to send or remove invites', 'buddypress' );
		$error = 'error';
	} elseif ( groups_check_for_membership_request( $friend_id, $group_id ) ) {
		$message = __( 'The member requested to join the group', 'buddypress' );
		$error = 'error';
	} elseif ( ! groups_uninvite_user( $friend_id, $group_id ) ) {
		$message = __( 'There was an error removing the invite', 'buddypress' );
		$error = 'error';
	}

	bp_core_add_message( $message, $error );
	bp_core_redirect( $redirect );
}
add_action( 'bp_screens', 'groups_remove_group_invite' );

/**
 * Handle the display of a group's Request Membership page.
 *
 * @since 1.0.0
 */
function groups_screen_group_request_membership() {

	if ( !is_user_logged_in() )
		return false;

	$bp = buddypress();

	if ( 'private' != $bp->groups->current_group->status )
		return false;

	// If the user is already invited, accept invitation.
	if ( groups_check_user_has_invite( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {
		if ( groups_accept_invite( bp_loggedin_user_id(), $bp->groups->current_group->id ) )
			bp_core_add_message( __( 'Group invite accepted', 'buddypress' ) );
		else
			bp_core_add_message( __( 'There was an error accepting the group invitation. Please try again.', 'buddypress' ), 'error' );
		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
	}

	// If the user has submitted a request, send it.
	if ( isset( $_POST['group-request-send']) ) {

		// Check the nonce.
		if ( !check_admin_referer( 'groups_request_membership' ) )
			return false;

		if ( !groups_send_membership_request( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {
			bp_core_add_message( __( 'There was an error sending your group membership request. Please try again.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Your membership request was sent to the group administrator successfully. You will be notified when the group administrator responds to your request.', 'buddypress' ) );
		}
		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
	}

	/**
	 * Fires before the loading of a group's Request Memebership page.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the group currently being displayed.
	 */
	do_action( 'groups_screen_group_request_membership', $bp->groups->current_group->id );

	/**
	 * Filters the template to load for a group's Request Membership page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to a group's Request Membership template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_request_membership', 'groups/single/home' ) );
}

/**
 * Handle the loading of a single group's activity.
 *
 * @since 2.4.0
 */
function groups_screen_group_activity() {

	if ( ! bp_is_single_item() ) {
		return false;
	}

	/**
	 * Fires before the loading of a single group's activity page.
	 *
	 * @since 2.4.0
	 */
	do_action( 'groups_screen_group_activity' );

	/**
	 * Filters the template to load for a single group's activity page.
	 *
	 * @since 2.4.0
	 *
	 * @param string $value Path to a single group's template to load.
	 */
	bp_core_load_template( apply_filters( 'groups_screen_group_activity', 'groups/single/activity' ) );
}

/**
 * Handle the display of a single group activity item.
 *
 * @since 1.2.0
 */
function groups_screen_group_activity_permalink() {

	if ( !bp_is_groups_component() || !bp_is_active( 'activity' ) || ( bp_is_active( 'activity' ) && !bp_is_current_action( bp_get_activity_slug() ) ) || !bp_action_variable( 0 ) )
		return false;

	buddypress()->is_single_item = true;

	/** This filter is documented in bp-groups/bp-groups-screens.php */
	bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_activity_permalink' );

/**
 * Handle the display of a group's Admin pages.
 *
 * @since 1.0.0
 */
function groups_screen_group_admin() {
	if ( !bp_is_groups_component() || !bp_is_current_action( 'admin' ) )
		return false;

	if ( bp_action_variables() )
		return false;

	bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/edit-details/' );
}

/**
 * Handle the display of a group's admin/edit-details page.
 *
 * @since 1.0.0
 */
function groups_screen_group_admin_edit_details() {

	if ( 'edit-details' != bp_get_group_current_admin_tab() )
		return false;

	if ( bp_is_item_admin() ) {

		$bp = buddypress();

		// If the edit form has been submitted, save the edited details.
		if ( isset( $_POST['save'] ) ) {
			// Check the nonce.
			if ( !check_admin_referer( 'groups_edit_group_details' ) )
				return false;

			$group_notify_members = isset( $_POST['group-notify-members'] ) ? (int) $_POST['group-notify-members'] : 0;

			// Name and description are required and may not be empty.
			if ( empty( $_POST['group-name'] ) || empty( $_POST['group-desc'] ) ) {
				bp_core_add_message( __( 'Groups must have a name and a description. Please try again.', 'buddypress' ), 'error' );
			} elseif ( ! groups_edit_base_group_details( array(
				'group_id'       => $_POST['group-id'],
				'name'           => $_POST['group-name'],
				'slug'           => null, // @TODO: Add to settings pane? If yes, editable by site admin only, or allow group admins to do this?
				'description'    => $_POST['group-desc'],
				'notify_members' => $group_notify_members,
			) ) ) {
				bp_core_add_message( __( 'There was an error updating group details. Please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Group details were successfully updated.', 'buddypress' ) );
			}

			/**
			 * Fires before the redirect if a group details has been edited and saved.
			 *
			 * @since 1.0.0
			 *
			 * @param int $id ID of the group that was edited.
			 */
			do_action( 'groups_group_details_edited', $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/edit-details/' );
		}

		/**
		 * Fires before the loading of the group admin/edit-details page template.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id ID of the group that is being displayed.
		 */
		do_action( 'groups_screen_group_admin_edit_details', $bp->groups->current_group->id );

		/**
		 * Filters the template to load for a group's admin/edit-details page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Path to a group's admin/edit-details template.
		 */
		bp_core_load_template( apply_filters( 'groups_template_group_admin', 'groups/single/home' ) );
	}
}
add_action( 'bp_screens', 'groups_screen_group_admin_edit_details' );

/**
 * Handle the display of a group's admin/group-settings page.
 *
 * @since 1.0.0
 */
function groups_screen_group_admin_settings() {

	if ( 'group-settings' != bp_get_group_current_admin_tab() )
		return false;

	if ( ! bp_is_item_admin() )
		return false;

	$bp = buddypress();

	// If the edit form has been submitted, save the edited details.
	if ( isset( $_POST['save'] ) ) {
		$enable_forum   = ( isset($_POST['group-show-forum'] ) ) ? 1 : 0;

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_status = apply_filters( 'groups_allowed_status', array( 'public', 'private', 'hidden' ) );
		$status         = ( in_array( $_POST['group-status'], (array) $allowed_status ) ) ? $_POST['group-status'] : 'public';

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_invite_status = apply_filters( 'groups_allowed_invite_status', array( 'members', 'mods', 'admins' ) );
		$invite_status	       = isset( $_POST['group-invite-status'] ) && in_array( $_POST['group-invite-status'], (array) $allowed_invite_status ) ? $_POST['group-invite-status'] : 'members';

		// Check the nonce.
		if ( !check_admin_referer( 'groups_edit_group_settings' ) )
			return false;

		/*
		 * Save group types.
		 *
		 * Ensure we keep types that have 'show_in_create_screen' set to false.
		 */
		$current_types = bp_groups_get_group_type( bp_get_current_group_id(), false );
		$current_types = array_intersect( bp_groups_get_group_types( array( 'show_in_create_screen' => false ) ), (array) $current_types );
		if ( isset( $_POST['group-types'] ) ) {
			$current_types = array_merge( $current_types, $_POST['group-types'] );

			// Set group types.
			bp_groups_set_group_type( bp_get_current_group_id(), $current_types );

		// No group types checked, so this means we want to wipe out all group types.
		} else {
			/*
			 * Passing a blank string will wipe out all types for the group.
			 *
			 * Ensure we keep types that have 'show_in_create_screen' set to false.
			 */
			$current_types = empty( $current_types ) ? '' : $current_types;

			// Set group types.
			bp_groups_set_group_type( bp_get_current_group_id(), $current_types );
		}

		if ( !groups_edit_group_settings( $_POST['group-id'], $enable_forum, $status, $invite_status ) ) {
			bp_core_add_message( __( 'There was an error updating group settings. Please try again.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Group settings were successfully updated.', 'buddypress' ) );
		}

		/**
		 * Fires before the redirect if a group settings has been edited and saved.
		 *
		 * @since 1.0.0
		 *
		 * @param int $id ID of the group that was edited.
		 */
		do_action( 'groups_group_settings_edited', $bp->groups->current_group->id );

		bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/group-settings/' );
	}

	/**
	 * Fires before the loading of the group admin/group-settings page template.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the group that is being displayed.
	 */
	do_action( 'groups_screen_group_admin_settings', $bp->groups->current_group->id );

	/**
	 * Filters the template to load for a group's admin/group-settings page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to a group's admin/group-settings template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_admin_settings', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_admin_settings' );

/**
 * Handle the display of a group's Change Avatar page.
 *
 * @since 1.0.0
 */
function groups_screen_group_admin_avatar() {

	if ( 'group-avatar' != bp_get_group_current_admin_tab() )
		return false;

	// If the logged-in user doesn't have permission or if avatar uploads are disabled, then stop here.
	if ( ! bp_is_item_admin() || bp_disable_group_avatar_uploads() || ! buddypress()->avatar->show_avatars )
		return false;

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

	if ( !empty( $_FILES ) ) {

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

		if ( !bp_core_avatar_handle_crop( $args ) ) {
			bp_core_add_message( __( 'There was a problem cropping the group profile photo.', 'buddypress' ), 'error' );
		} else {
			/**
			 * Fires after a group avatar is uploaded.
			 *
			 * @since 2.8.0
			 *
			 * @param int    $group_id ID of the group.
			 * @param string $type     Avatar type. 'crop' or 'full'.
			 * @param array  $args     Array of parameters passed to the avatar handler.
			 */
			do_action( 'groups_avatar_uploaded', bp_get_current_group_id(), 'crop', $args );
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

	/**
	 * Filters the template to load for a group's Change Avatar page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to a group's Change Avatar template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_admin_avatar', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_admin_avatar' );

/**
 * Handle the display of a group's Change cover image page.
 *
 * @since 2.4.0
 */
function groups_screen_group_admin_cover_image() {
	if ( 'group-cover-image' != bp_get_group_current_admin_tab() ) {
		return false;
	}

	// If the logged-in user doesn't have permission or if cover image uploads are disabled, then stop here.
	if ( ! bp_is_item_admin() || ! bp_group_use_cover_image_header() ) {
		return false;
	}

	/**
	 * Fires before the loading of the group Change cover image page template.
	 *
	 * @since 2.4.0
	 *
	 * @param int $id ID of the group that is being displayed.
	 */
	do_action( 'groups_screen_group_admin_cover_image', bp_get_current_group_id() );

	/**
	 * Filters the template to load for a group's Change cover image page.
	 *
	 * @since 2.4.0
	 *
	 * @param string $value Path to a group's Change cover image template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_admin_cover_image', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_admin_cover_image' );

/**
 * This function handles actions related to member management on the group admin.
 *
 * @since 1.0.0
 */
function groups_screen_group_admin_manage_members() {

	if ( 'manage-members' != bp_get_group_current_admin_tab() )
		return false;

	if ( ! bp_is_item_admin() )
		return false;

	$bp = buddypress();

	if ( bp_action_variable( 1 ) && bp_action_variable( 2 ) && bp_action_variable( 3 ) ) {
		if ( bp_is_action_variable( 'promote', 1 ) && ( bp_is_action_variable( 'mod', 2 ) || bp_is_action_variable( 'admin', 2 ) ) && is_numeric( bp_action_variable( 3 ) ) ) {
			$user_id = bp_action_variable( 3 );
			$status  = bp_action_variable( 2 );

			// Check the nonce first.
			if ( !check_admin_referer( 'groups_promote_member' ) )
				return false;

			// Promote a user.
			if ( !groups_promote_member( $user_id, $bp->groups->current_group->id, $status ) )
				bp_core_add_message( __( 'There was an error when promoting that user. Please try again.', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'User promoted successfully', 'buddypress' ) );

			/**
			 * Fires before the redirect after a group member has been promoted.
			 *
			 * @since 1.0.0
			 *
			 * @param int $user_id ID of the user being promoted.
			 * @param int $id      ID of the group user is promoted within.
			 */
			do_action( 'groups_promoted_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/manage-members/' );
		}
	}

	if ( bp_action_variable( 1 ) && bp_action_variable( 2 ) ) {
		if ( bp_is_action_variable( 'demote', 1 ) && is_numeric( bp_action_variable( 2 ) ) ) {
			$user_id = bp_action_variable( 2 );

			// Check the nonce first.
			if ( !check_admin_referer( 'groups_demote_member' ) )
				return false;

			// Stop sole admins from abandoning their group.
			$group_admins = groups_get_group_admins( $bp->groups->current_group->id );
			if ( 1 == count( $group_admins ) && $group_admins[0]->user_id == $user_id )
				bp_core_add_message( __( 'This group must have at least one admin', 'buddypress' ), 'error' );

			// Demote a user.
			elseif ( !groups_demote_member( $user_id, $bp->groups->current_group->id ) )
				bp_core_add_message( __( 'There was an error when demoting that user. Please try again.', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'User demoted successfully', 'buddypress' ) );

			/**
			 * Fires before the redirect after a group member has been demoted.
			 *
			 * @since 1.0.0
			 *
			 * @param int $user_id ID of the user being demoted.
			 * @param int $id      ID of the group user is demoted within.
			 */
			do_action( 'groups_demoted_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/manage-members/' );
		}

		if ( bp_is_action_variable( 'ban', 1 ) && is_numeric( bp_action_variable( 2 ) ) ) {
			$user_id = bp_action_variable( 2 );

			// Check the nonce first.
			if ( !check_admin_referer( 'groups_ban_member' ) )
				return false;

			// Ban a user.
			if ( !groups_ban_member( $user_id, $bp->groups->current_group->id ) )
				bp_core_add_message( __( 'There was an error when banning that user. Please try again.', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'User banned successfully', 'buddypress' ) );

			/**
			 * Fires before the redirect after a group member has been banned.
			 *
			 * @since 1.0.0
			 *
			 * @param int $user_id ID of the user being banned.
			 * @param int $id      ID of the group user is banned from.
			 */
			do_action( 'groups_banned_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/manage-members/' );
		}

		if ( bp_is_action_variable( 'unban', 1 ) && is_numeric( bp_action_variable( 2 ) ) ) {
			$user_id = bp_action_variable( 2 );

			// Check the nonce first.
			if ( !check_admin_referer( 'groups_unban_member' ) )
				return false;

			// Remove a ban for user.
			if ( !groups_unban_member( $user_id, $bp->groups->current_group->id ) )
				bp_core_add_message( __( 'There was an error when unbanning that user. Please try again.', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'User ban removed successfully', 'buddypress' ) );

			/**
			 * Fires before the redirect after a group member has been unbanned.
			 *
			 * @since 1.0.0
			 *
			 * @param int $user_id ID of the user being unbanned.
			 * @param int $id      ID of the group user is unbanned from.
			 */
			do_action( 'groups_unbanned_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/manage-members/' );
		}

		if ( bp_is_action_variable( 'remove', 1 ) && is_numeric( bp_action_variable( 2 ) ) ) {
			$user_id = bp_action_variable( 2 );

			// Check the nonce first.
			if ( !check_admin_referer( 'groups_remove_member' ) )
				return false;

			// Remove a user.
			if ( !groups_remove_member( $user_id, $bp->groups->current_group->id ) )
				bp_core_add_message( __( 'There was an error removing that user from the group. Please try again.', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'User removed successfully', 'buddypress' ) );

			/**
			 * Fires before the redirect after a group member has been removed.
			 *
			 * @since 1.2.6
			 *
			 * @param int $user_id ID of the user being removed.
			 * @param int $id      ID of the group the user is removed from.
			 */
			do_action( 'groups_removed_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/manage-members/' );
		}
	}

	/**
	 * Fires before the loading of a group's manage members template.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the group whose manage members page is being displayed.
	 */
	do_action( 'groups_screen_group_admin_manage_members', $bp->groups->current_group->id );

	/**
	 * Filters the template to load for a group's manage members page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to a group's manage members template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_admin_manage_members', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_admin_manage_members' );

/**
 * Handle the display of Admin > Membership Requests.
 *
 * @since 1.0.0
 */
function groups_screen_group_admin_requests() {
	$bp = buddypress();

	if ( 'membership-requests' != bp_get_group_current_admin_tab() ) {
		return false;
	}

	if ( ! bp_is_item_admin() || ( 'public' == $bp->groups->current_group->status ) ) {
		return false;
	}

	$request_action = (string) bp_action_variable( 1 );
	$membership_id  = (int) bp_action_variable( 2 );

	if ( !empty( $request_action ) && !empty( $membership_id ) ) {
		if ( 'accept' == $request_action && is_numeric( $membership_id ) ) {

			// Check the nonce first.
			if ( !check_admin_referer( 'groups_accept_membership_request' ) )
				return false;

			// Accept the membership request.
			if ( !groups_accept_membership_request( $membership_id ) )
				bp_core_add_message( __( 'There was an error accepting the membership request. Please try again.', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'Group membership request accepted', 'buddypress' ) );

		} elseif ( 'reject' == $request_action && is_numeric( $membership_id ) ) {
			/* Check the nonce first. */
			if ( !check_admin_referer( 'groups_reject_membership_request' ) )
				return false;

			// Reject the membership request.
			if ( !groups_reject_membership_request( $membership_id ) )
				bp_core_add_message( __( 'There was an error rejecting the membership request. Please try again.', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'Group membership request rejected', 'buddypress' ) );
		}

		/**
		 * Fires before the redirect if a group membership request has been handled.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $id             ID of the group that was edited.
		 * @param string $request_action Membership request action being performed.
		 * @param int    $membership_id  The key of the action_variables array that you want.
		 */
		do_action( 'groups_group_request_managed', $bp->groups->current_group->id, $request_action, $membership_id );
		bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/membership-requests/' );
	}

	/**
	 * Fires before the loading of the group membership request page template.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the group that is being displayed.
	 */
	do_action( 'groups_screen_group_admin_requests', $bp->groups->current_group->id );

	/**
	 * Filters the template to load for a group's membership request page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to a group's membership request template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_admin_requests', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_admin_requests' );

/**
 * Handle the display of the Delete Group page.
 *
 * @since 1.0.0
 */
function groups_screen_group_admin_delete_group() {

	if ( 'delete-group' != bp_get_group_current_admin_tab() )
		return false;

	if ( ! bp_is_item_admin() && !bp_current_user_can( 'bp_moderate' ) )
		return false;

	$bp = buddypress();

	if ( isset( $_REQUEST['delete-group-button'] ) && isset( $_REQUEST['delete-group-understand'] ) ) {

		// Check the nonce first.
		if ( !check_admin_referer( 'groups_delete_group' ) ) {
			return false;
		}

		/**
		 * Fires before the deletion of a group from the Delete Group page.
		 *
		 * @since 1.5.0
		 *
		 * @param int $id ID of the group being deleted.
		 */
		do_action( 'groups_before_group_deleted', $bp->groups->current_group->id );

		// Group admin has deleted the group, now do it.
		if ( !groups_delete_group( $bp->groups->current_group->id ) ) {
			bp_core_add_message( __( 'There was an error deleting the group. Please try again.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'The group was deleted successfully.', 'buddypress' ) );

			/**
			 * Fires after the deletion of a group from the Delete Group page.
			 *
			 * @since 1.0.0
			 *
			 * @param int $id ID of the group being deleted.
			 */
			do_action( 'groups_group_deleted', $bp->groups->current_group->id );

			bp_core_redirect( trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) );
		}

		bp_core_redirect( trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) );
	}

	/**
	 * Fires before the loading of the Delete Group page template.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the group that is being displayed.
	 */
	do_action( 'groups_screen_group_admin_delete_group', $bp->groups->current_group->id );

	/**
	 * Filters the template to load for the Delete Group page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to the Delete Group template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_admin_delete_group', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_admin_delete_group' );

/**
 * Render the group settings fields on the Notification Settings page.
 *
 * @since 1.0.0
 */
function groups_screen_notification_settings() {

	if ( !$group_invite = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_invite', true ) )
		$group_invite  = 'yes';

	if ( !$group_update = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_group_updated', true ) )
		$group_update  = 'yes';

	if ( !$group_promo = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_admin_promotion', true ) )
		$group_promo   = 'yes';

	if ( !$group_request = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_membership_request', true ) )
		$group_request = 'yes';

	if ( ! $group_request_completed = bp_get_user_meta( bp_displayed_user_id(), 'notification_membership_request_completed', true ) ) {
		$group_request_completed = 'yes';
	}
	?>

	<table class="notification-settings" id="groups-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _ex( 'Groups', 'Group settings on notification settings page', 'buddypress' ) ?></th>
				<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
				<th class="no"><?php _e( 'No', 'buddypress' )?></th>
			</tr>
		</thead>

		<tbody>
			<tr id="groups-notification-settings-invitation">
				<td></td>
				<td><?php _ex( 'A member invites you to join a group', 'group settings on notification settings page','buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_groups_invite]" id="notification-groups-invite-yes" value="yes" <?php checked( $group_invite, 'yes', true ) ?>/><label for="notification-groups-invite-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_groups_invite]" id="notification-groups-invite-no" value="no" <?php checked( $group_invite, 'no', true ) ?>/><label for="notification-groups-invite-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>
			<tr id="groups-notification-settings-info-updated">
				<td></td>
				<td><?php _ex( 'Group information is updated', 'group settings on notification settings page', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_groups_group_updated]" id="notification-groups-group-updated-yes" value="yes" <?php checked( $group_update, 'yes', true ) ?>/><label for="notification-groups-group-updated-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_groups_group_updated]" id="notification-groups-group-updated-no" value="no" <?php checked( $group_update, 'no', true ) ?>/><label for="notification-groups-group-updated-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>
			<tr id="groups-notification-settings-promoted">
				<td></td>
				<td><?php _ex( 'You are promoted to a group administrator or moderator', 'group settings on notification settings page', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_groups_admin_promotion]" id="notification-groups-admin-promotion-yes" value="yes" <?php checked( $group_promo, 'yes', true ) ?>/><label for="notification-groups-admin-promotion-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_groups_admin_promotion]" id="notification-groups-admin-promotion-no" value="no" <?php checked( $group_promo, 'no', true ) ?>/><label for="notification-groups-admin-promotion-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>
			<tr id="groups-notification-settings-request">
				<td></td>
				<td><?php _ex( 'A member requests to join a private group for which you are an admin', 'group settings on notification settings page', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_groups_membership_request]" id="notification-groups-membership-request-yes" value="yes" <?php checked( $group_request, 'yes', true ) ?>/><label for="notification-groups-membership-request-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_groups_membership_request]" id="notification-groups-membership-request-no" value="no" <?php checked( $group_request, 'no', true ) ?>/><label for="notification-groups-membership-request-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>
			<tr id="groups-notification-settings-request-completed">
				<td></td>
				<td><?php _ex( 'Your request to join a group has been approved or denied', 'group settings on notification settings page', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_membership_request_completed]" id="notification-groups-membership-request-completed-yes" value="yes" <?php checked( $group_request_completed, 'yes', true ) ?>/><label for="notification-groups-membership-request-completed-yes" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
				?></label></td>
				<td class="no"><input type="radio" name="notifications[notification_membership_request_completed]" id="notification-groups-membership-request-completed-no" value="no" <?php checked( $group_request_completed, 'no', true ) ?>/><label for="notification-groups-membership-request-completed-no" class="bp-screen-reader-text"><?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
				?></label></td>
			</tr>

			<?php

			/**
			 * Fires at the end of the available group settings fields on Notification Settings page.
			 *
			 * @since 1.0.0
			 */
			do_action( 'groups_screen_notification_settings' ); ?>

		</tbody>
	</table>

<?php
}
add_action( 'bp_notification_settings', 'groups_screen_notification_settings' );

/** Theme Compatibility *******************************************************/

new BP_Groups_Theme_Compat();
