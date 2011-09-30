<?php
/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function groups_directory_groups_setup() {
	if ( bp_is_groups_component() && !bp_current_action() && !bp_current_item() ) {
		bp_update_is_directory( true, 'groups' );

		do_action( 'groups_directory_groups_setup' );

		bp_core_load_template( apply_filters( 'groups_template_directory_groups', 'groups/index' ) );
	}
}
add_action( 'bp_screens', 'groups_directory_groups_setup', 2 );

function groups_screen_my_groups() {
	global $bp;

	// Delete group request notifications for the user
	if ( isset( $_GET['n'] ) ) {
		bp_core_delete_notifications_by_type( $bp->loggedin_user->id, $bp->groups->id, 'membership_request_accepted' );
		bp_core_delete_notifications_by_type( $bp->loggedin_user->id, $bp->groups->id, 'membership_request_rejected' );
		bp_core_delete_notifications_by_type( $bp->loggedin_user->id, $bp->groups->id, 'member_promoted_to_mod'      );
		bp_core_delete_notifications_by_type( $bp->loggedin_user->id, $bp->groups->id, 'member_promoted_to_admin'    );
	}

	do_action( 'groups_screen_my_groups' );

	bp_core_load_template( apply_filters( 'groups_template_my_groups', 'members/single/home' ) );
}

function groups_screen_group_invites() {
	$group_id = (int)bp_action_variable( 1 );

	if ( bp_is_action_variable( 'accept' ) && is_numeric( $group_id ) ) {
		// Check the nonce
		if ( !check_admin_referer( 'groups_accept_invite' ) )
			return false;

		if ( !groups_accept_invite( bp_loggedin_user_id(), $group_id ) ) {
			bp_core_add_message( __('Group invite could not be accepted', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('Group invite accepted', 'buddypress') );

			// Record this in activity streams
			$group = new BP_Groups_Group( $group_id );

			groups_record_activity( array(
				'action'  => apply_filters_ref_array( 'groups_activity_accepted_invite_action', array( sprintf( __( '%1$s joined the group %2$s', 'buddypress'), bp_core_get_userlink( bp_loggedin_user_id() ), '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' ), bp_loggedin_user_id(), &$group ) ),
				'type'    => 'joined_group',
				'item_id' => $group->id
			) );
		}

		bp_core_redirect( bp_loggedin_user_domain() . bp_get_groups_slug() . '/' . bp_current_action() );

	} else if ( bp_is_action_variable( 'reject' ) && is_numeric( $group_id ) ) {
		// Check the nonce
		if ( !check_admin_referer( 'groups_reject_invite' ) )
			return false;

		if ( !groups_reject_invite( bp_loggedin_user_id(), $group_id ) )
			bp_core_add_message( __('Group invite could not be rejected', 'buddypress'), 'error' );
		else
			bp_core_add_message( __('Group invite rejected', 'buddypress') );

		bp_core_redirect( bp_loggedin_user_domain() . bp_get_groups_slug() . '/' . bp_current_action() );
	}

	// Remove notifications
	bp_core_delete_notifications_by_type( bp_loggedin_user_id(), 'groups', 'group_invite' );

	do_action( 'groups_screen_group_invites', $group_id );

	bp_core_load_template( apply_filters( 'groups_template_group_invites', 'members/single/home' ) );
}

function groups_screen_group_home() {
	global $bp;

	if ( bp_is_single_item() ) {
		if ( isset( $_GET['n'] ) ) {
			bp_core_delete_notifications_by_type( $bp->loggedin_user->id, $bp->groups->id, 'membership_request_accepted' );
			bp_core_delete_notifications_by_type( $bp->loggedin_user->id, $bp->groups->id, 'membership_request_rejected' );
			bp_core_delete_notifications_by_type( $bp->loggedin_user->id, $bp->groups->id, 'member_promoted_to_mod'      );
			bp_core_delete_notifications_by_type( $bp->loggedin_user->id, $bp->groups->id, 'member_promoted_to_admin'    );
		}

		do_action( 'groups_screen_group_home' );

		bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
	}
}

/**
 * This screen function handles actions related to group forums
 *
 * @package BuddyPress
 */
function groups_screen_group_forum() {
	global $bp;

	if ( !bp_is_active( 'forums' ) || !bp_forums_is_installed_correctly() )
		return false;

	if ( bp_action_variable( 0 ) && !bp_is_action_variable( 'topic', 0 ) ) {
		bp_do_404();
		return;
	}

	if ( !$bp->groups->current_group->user_has_access ) {
		bp_core_no_access();
		return;
	}

	if ( bp_is_single_item() ) {

		// Fetch the details we need
		$topic_slug	= (string)bp_action_variable( 1 );
		$topic_id       = bp_forums_get_topic_id_from_slug( $topic_slug );
		$forum_id       = groups_get_groupmeta( $bp->groups->current_group->id, 'forum_id' );
		$user_is_banned = false;

		if ( !is_super_admin() && groups_is_user_banned( $bp->loggedin_user->id, $bp->groups->current_group->id ) )
			$user_is_banned = true;

		if ( !empty( $topic_slug ) && !empty( $topic_id ) ) {

			// Posting a reply
			if ( !$user_is_banned && !bp_action_variable( 2 ) && isset( $_POST['submit_reply'] ) ) {
				// Check the nonce
				check_admin_referer( 'bp_forums_new_reply' );

				// Auto join this user if they are not yet a member of this group
				if ( bp_groups_auto_join() && !is_super_admin() && 'public' == $bp->groups->current_group->status && !groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) )
					groups_join_group( $bp->groups->current_group->id, $bp->loggedin_user->id );

				$topic_page = isset( $_GET['topic_page'] ) ? $_GET['topic_page'] : false;

				if ( !$post_id = groups_new_group_forum_post( $_POST['reply_text'], $topic_id, $topic_page ) )
					bp_core_add_message( __( 'There was an error when replying to that topic', 'buddypress'), 'error' );
				else
					bp_core_add_message( __( 'Your reply was posted successfully', 'buddypress') );

				if ( isset( $_SERVER['QUERY_STRING'] ) )
					$query_vars = '?' . $_SERVER['QUERY_STRING'];

				bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'forum/topic/' . $topic_slug . '/' . $query_vars . '#post-' . $post_id );
			}

			// Sticky a topic
			else if ( bp_is_action_variable( 'stick', 2 ) && ( isset( $bp->is_item_admin ) || isset( $bp->is_item_mod ) ) ) {
				// Check the nonce
				check_admin_referer( 'bp_forums_stick_topic' );

				if ( !bp_forums_sticky_topic( array( 'topic_id' => $topic_id ) ) )
					bp_core_add_message( __( 'There was an error when making that topic a sticky', 'buddypress' ), 'error' );
				else
					bp_core_add_message( __( 'The topic was made sticky successfully', 'buddypress' ) );

				do_action( 'groups_stick_forum_topic', $topic_id );
				bp_core_redirect( wp_get_referer() );
			}

			// Un-Sticky a topic
			else if ( bp_is_action_variable( 'unstick', 2 ) && ( isset( $bp->is_item_admin ) || isset( $bp->is_item_mod ) ) ) {
				// Check the nonce
				check_admin_referer( 'bp_forums_unstick_topic' );

				if ( !bp_forums_sticky_topic( array( 'topic_id' => $topic_id, 'mode' => 'unstick' ) ) )
					bp_core_add_message( __( 'There was an error when unsticking that topic', 'buddypress'), 'error' );
				else
					bp_core_add_message( __( 'The topic was unstuck successfully', 'buddypress') );

				do_action( 'groups_unstick_forum_topic', $topic_id );
				bp_core_redirect( wp_get_referer() );
			}

			// Close a topic
			else if ( bp_is_action_variable( 'close', 2 ) && ( isset( $bp->is_item_admin ) || isset( $bp->is_item_mod ) ) ) {
				// Check the nonce
				check_admin_referer( 'bp_forums_close_topic' );

				if ( !bp_forums_openclose_topic( array( 'topic_id' => $topic_id ) ) )
					bp_core_add_message( __( 'There was an error when closing that topic', 'buddypress'), 'error' );
				else
					bp_core_add_message( __( 'The topic was closed successfully', 'buddypress') );

				do_action( 'groups_close_forum_topic', $topic_id );
				bp_core_redirect( wp_get_referer() );
			}

			// Open a topic
			else if ( bp_is_action_variable( 'open', 2 ) && ( isset( $bp->is_item_admin ) || isset( $bp->is_item_mod ) ) ) {
				// Check the nonce
				check_admin_referer( 'bp_forums_open_topic' );

				if ( !bp_forums_openclose_topic( array( 'topic_id' => $topic_id, 'mode' => 'open' ) ) )
					bp_core_add_message( __( 'There was an error when opening that topic', 'buddypress'), 'error' );
				else
					bp_core_add_message( __( 'The topic was opened successfully', 'buddypress') );

				do_action( 'groups_open_forum_topic', $topic_id );
				bp_core_redirect( wp_get_referer() );
			}

			// Delete a topic
			else if ( empty( $user_is_banned ) && bp_is_action_variable( 'delete', 2 ) && !bp_action_variable( 3 ) ) {
				// Fetch the topic
				$topic = bp_forums_get_topic_details( $topic_id );

				/* Check the logged in user can delete this topic */
				if ( !$bp->is_item_admin && !$bp->is_item_mod && (int)$bp->loggedin_user->id != (int)$topic->topic_poster )
					bp_core_redirect( wp_get_referer() );

				// Check the nonce
				check_admin_referer( 'bp_forums_delete_topic' );

				do_action( 'groups_before_delete_forum_topic', $topic_id );

				if ( !groups_delete_group_forum_topic( $topic_id ) )
					bp_core_add_message( __( 'There was an error deleting the topic', 'buddypress' ), 'error' );
				else
					bp_core_add_message( __( 'The topic was deleted successfully', 'buddypress' ) );

				do_action( 'groups_delete_forum_topic', $topic_id );
				bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'forum/' );
			}

			// Editing a topic
			else if ( empty( $user_is_banned ) && bp_is_action_variable( 'edit', 2 ) && !bp_action_variable( 3 ) ) {
				// Fetch the topic
				$topic = bp_forums_get_topic_details( $topic_id );

				// Check the logged in user can edit this topic
				if ( !$bp->is_item_admin && !$bp->is_item_mod && (int)$bp->loggedin_user->id != (int)$topic->topic_poster )
					bp_core_redirect( wp_get_referer() );

				if ( isset( $_POST['save_changes'] ) ) {
					// Check the nonce
					check_admin_referer( 'bp_forums_edit_topic' );

					$topic_tags = !empty( $_POST['topic_tags'] ) ? $_POST['topic_tags'] : false;

					if ( !groups_update_group_forum_topic( $topic_id, $_POST['topic_title'], $_POST['topic_text'], $topic_tags ) )
						bp_core_add_message( __( 'There was an error when editing that topic', 'buddypress'), 'error' );
					else
						bp_core_add_message( __( 'The topic was edited successfully', 'buddypress') );

					do_action( 'groups_edit_forum_topic', $topic_id );
					bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'forum/topic/' . $topic_slug . '/' );
				}

				bp_core_load_template( apply_filters( 'groups_template_group_forum_topic_edit', 'groups/single/home' ) );
			}

			// Delete a post
			else if ( empty( $user_is_banned ) && bp_is_action_variable( 'delete', 2 ) && $post_id = bp_action_variable( 4 ) ) {
				// Fetch the post
				$post = bp_forums_get_post( $post_id );

				// Check the logged in user can edit this topic
				if ( !$bp->is_item_admin && !$bp->is_item_mod && (int)$bp->loggedin_user->id != (int)$post->poster_id )
					bp_core_redirect( wp_get_referer() );

				// Check the nonce
				check_admin_referer( 'bp_forums_delete_post' );

				do_action( 'groups_before_delete_forum_post', $post_id );

				if ( !groups_delete_group_forum_post( $post_id ) )
					bp_core_add_message( __( 'There was an error deleting that post', 'buddypress'), 'error' );
				else
					bp_core_add_message( __( 'The post was deleted successfully', 'buddypress') );

				do_action( 'groups_delete_forum_post', $post_id );
				bp_core_redirect( wp_get_referer() );
			}

			// Editing a post
			else if ( empty( $user_is_banned ) && bp_is_action_variable( 'edit', 2 ) && $post_id = bp_action_variable( 4 ) ) {
				// Fetch the post
				$post = bp_forums_get_post( $post_id );

				// Check the logged in user can edit this topic
				if ( !$bp->is_item_admin && !$bp->is_item_mod && (int)$bp->loggedin_user->id != (int)$post->poster_id )
					bp_core_redirect( wp_get_referer() );

				if ( isset( $_POST['save_changes'] ) ) {
					// Check the nonce
					check_admin_referer( 'bp_forums_edit_post' );

					$topic_page = isset( $_GET['topic_page'] ) ? $_GET['topic_page'] : false;

					if ( !$post_id = groups_update_group_forum_post( $post_id, $_POST['post_text'], $topic_id, $topic_page ) )
						bp_core_add_message( __( 'There was an error when editing that post', 'buddypress'), 'error' );
					else
						bp_core_add_message( __( 'The post was edited successfully', 'buddypress') );

					if ( $_SERVER['QUERY_STRING'] )
						$query_vars = '?' . $_SERVER['QUERY_STRING'];

					do_action( 'groups_edit_forum_post', $post_id );
					bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic_slug . '/' . $query_vars . '#post-' . $post_id );
				}

				bp_core_load_template( apply_filters( 'groups_template_group_forum_topic_edit', 'groups/single/home' ) );
			}

			// Standard topic display
			else {
				if ( !empty( $user_is_banned ) )
					bp_core_add_message( __( "You have been banned from this group.", 'buddypress' ) );

				bp_core_load_template( apply_filters( 'groups_template_group_forum_topic', 'groups/single/home' ) );
			}

		// Forum topic does not exist
		} elseif ( !empty( $topic_slug ) && empty( $topic_id ) ) {
			bp_do_404();
			return;

		} else {
			// Posting a topic
			if ( isset( $_POST['submit_topic'] ) && bp_is_active( 'forums' ) ) {
				// Check the nonce
				check_admin_referer( 'bp_forums_new_topic' );

				if ( $user_is_banned ) {
				 	$error_message = __( "You have been banned from this group.", 'buddypress' );

				} elseif ( bp_groups_auto_join() && !is_super_admin() && 'public' == $bp->groups->current_group->status && !groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {
					// Auto join this user if they are not yet a member of this group
					groups_join_group( $bp->groups->current_group->id, $bp->loggedin_user->id );
				}

				if ( empty( $_POST['topic_title'] ) )
					$error_message = __( 'Please provide a title for your forum topic.', 'buddypress' );
				else if ( empty( $_POST['topic_text'] ) )
					$error_message = __( 'Forum posts cannot be empty. Please enter some text.', 'buddypress' );

				if ( empty( $forum_id ) )
					$error_message = __( 'This group does not have a forum setup yet.', 'buddypress' );

				if ( isset( $error_message ) ) {
					bp_core_add_message( $error_message, 'error' );
					$redirect = bp_get_group_permalink( $bp->groups->current_group ) . 'forum';
				} else {
					if ( !$topic = groups_new_group_forum_topic( $_POST['topic_title'], $_POST['topic_text'], $_POST['topic_tags'], $forum_id ) ) {
						bp_core_add_message( __( 'There was an error when creating the topic', 'buddypress'), 'error' );
						$redirect = bp_get_group_permalink( $bp->groups->current_group ) . 'forum';
					} else {
						bp_core_add_message( __( 'The topic was created successfully', 'buddypress') );
						$redirect = bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic->topic_slug . '/';
					}
				}

				bp_core_redirect( $redirect );
			}

			do_action( 'groups_screen_group_forum', $topic_id, $forum_id );

			bp_core_load_template( apply_filters( 'groups_template_group_forum', 'groups/single/home' ) );
		}
	}
}

function groups_screen_group_members() {
	global $bp;

	if ( $bp->is_single_item ) {
		// Refresh the group member count meta
		groups_update_groupmeta( $bp->groups->current_group->id, 'total_member_count', groups_get_total_member_count( $bp->groups->current_group->id ) );

		do_action( 'groups_screen_group_members', $bp->groups->current_group->id );
		bp_core_load_template( apply_filters( 'groups_template_group_members', 'groups/single/home' ) );
	}
}

function groups_screen_group_invite() {
	global $bp;

	if ( $bp->is_single_item ) {
		if ( bp_is_action_variable( 'send', 0 ) ) {

			if ( !check_admin_referer( 'groups_send_invites', '_wpnonce_send_invites' ) )
				return false;

			if ( !empty( $_POST['friends'] ) ) {
				foreach( (array)$_POST['friends'] as $friend ) {
					groups_invite_user( array( 'user_id' => $friend, 'group_id' => $bp->groups->current_group->id ) );
				}
			}

			// Send the invites.
			groups_send_invites( $bp->loggedin_user->id, $bp->groups->current_group->id );
			bp_core_add_message( __('Group invites sent.', 'buddypress') );
			do_action( 'groups_screen_group_invite', $bp->groups->current_group->id );
			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );

		} elseif ( !bp_action_variable( 0 ) ) {
			// Show send invite page
			bp_core_load_template( apply_filters( 'groups_template_group_invite', 'groups/single/home' ) );

		} else {
			bp_do_404();
		}
	}
}

function groups_screen_group_request_membership() {
	global $bp;

	if ( !is_user_logged_in() )
		return false;

	if ( 'private' == $bp->groups->current_group->status ) {
		// If the user has submitted a request, send it.
		if ( isset( $_POST['group-request-send']) ) {
			// Check the nonce
			if ( !check_admin_referer( 'groups_request_membership' ) )
				return false;

			if ( !groups_send_membership_request( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error sending your group membership request, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Your membership request was sent to the group administrator successfully. You will be notified when the group administrator responds to your request.', 'buddypress' ) );
			}
			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
		}

		do_action( 'groups_screen_group_request_membership', $bp->groups->current_group->id );

		bp_core_load_template( apply_filters( 'groups_template_group_request_membership', 'groups/single/home' ) );
	}
}

function groups_screen_group_activity_permalink() {
	global $bp;

	if ( !bp_is_groups_component() || !bp_is_active( 'activity' ) || ( bp_is_active( 'activity' ) && !bp_is_current_action( bp_get_activity_slug() ) ) || !bp_action_variable( 0 ) )
		return false;

	$bp->is_single_item = true;

	bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_activity_permalink' );

function groups_screen_group_admin() {
	if ( !bp_is_groups_component() || !bp_is_current_action( 'admin' ) )
		return false;

	if ( bp_action_variables() )
		return false;

	bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/edit-details/' );
}

function groups_screen_group_admin_edit_details() {
	global $bp;

	if ( bp_is_groups_component() && bp_is_action_variable( 'edit-details', 0 ) ) {

		if ( $bp->is_item_admin || $bp->is_item_mod  ) {

			// If the edit form has been submitted, save the edited details
			if ( isset( $_POST['save'] ) ) {
				// Check the nonce
				if ( !check_admin_referer( 'groups_edit_group_details' ) )
					return false;

				if ( !groups_edit_base_group_details( $_POST['group-id'], $_POST['group-name'], $_POST['group-desc'], (int)$_POST['group-notify-members'] ) ) {
					bp_core_add_message( __( 'There was an error updating group details, please try again.', 'buddypress' ), 'error' );
				} else {
					bp_core_add_message( __( 'Group details were successfully updated.', 'buddypress' ) );
				}

				do_action( 'groups_group_details_edited', $bp->groups->current_group->id );

				bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/edit-details/' );
			}

			do_action( 'groups_screen_group_admin_edit_details', $bp->groups->current_group->id );

			bp_core_load_template( apply_filters( 'groups_template_group_admin', 'groups/single/home' ) );
		}
	}
}
add_action( 'bp_screens', 'groups_screen_group_admin_edit_details' );

function groups_screen_group_admin_settings() {
	global $bp;

	if ( bp_is_groups_component() && bp_is_action_variable( 'group-settings', 0 ) ) {

		if ( !$bp->is_item_admin )
			return false;

		// If the edit form has been submitted, save the edited details
		if ( isset( $_POST['save'] ) ) {
			$enable_forum   = ( isset($_POST['group-show-forum'] ) ) ? 1 : 0;

			// Checked against a whitelist for security
			$allowed_status = apply_filters( 'groups_allowed_status', array( 'public', 'private', 'hidden' ) );
			$status         = ( in_array( $_POST['group-status'], (array)$allowed_status ) ) ? $_POST['group-status'] : 'public';

			// Checked against a whitelist for security
			$allowed_invite_status = apply_filters( 'groups_allowed_invite_status', array( 'members', 'mods', 'admins' ) );
			$invite_status	       = in_array( $_POST['group-invite-status'], (array)$allowed_invite_status ) ? $_POST['group-invite-status'] : 'members';

			// Check the nonce
			if ( !check_admin_referer( 'groups_edit_group_settings' ) )
				return false;

			if ( !groups_edit_group_settings( $_POST['group-id'], $enable_forum, $status, $invite_status ) ) {
				bp_core_add_message( __( 'There was an error updating group settings, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Group settings were successfully updated.', 'buddypress' ) );
			}

			do_action( 'groups_group_settings_edited', $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/group-settings/' );
		}

		do_action( 'groups_screen_group_admin_settings', $bp->groups->current_group->id );

		bp_core_load_template( apply_filters( 'groups_template_group_admin_settings', 'groups/single/home' ) );
	}
}
add_action( 'bp_screens', 'groups_screen_group_admin_settings' );

function groups_screen_group_admin_avatar() {
	global $bp;

	if ( bp_is_groups_component() && bp_is_action_variable( 'group-avatar', 0 ) ) {

		// If the logged-in user doesn't have permission or if avatar uploads are disabled, then stop here
		if ( !$bp->is_item_admin || (int)bp_get_option( 'bp-disable-avatar-uploads' ) )
			return false;

		// If the group admin has deleted the admin avatar
		if ( bp_is_action_variable( 'delete', 1 ) ) {

			// Check the nonce
			check_admin_referer( 'bp_group_avatar_delete' );

			if ( bp_core_delete_existing_avatar( array( 'item_id' => $bp->groups->current_group->id, 'object' => 'group' ) ) )
				bp_core_add_message( __( 'Your avatar was deleted successfully!', 'buddypress' ) );
			else
				bp_core_add_message( __( 'There was a problem deleting that avatar, please try again.', 'buddypress' ), 'error' );

		}

		$bp->avatar_admin->step = 'upload-image';

		if ( !empty( $_FILES ) ) {

			// Check the nonce
			check_admin_referer( 'bp_avatar_upload' );

			// Pass the file to the avatar upload handler
			if ( bp_core_avatar_handle_upload( $_FILES, 'groups_avatar_upload_dir' ) ) {
				$bp->avatar_admin->step = 'crop-image';

				// Make sure we include the jQuery jCrop file for image cropping
				add_action( 'wp_print_scripts', 'bp_core_add_jquery_cropper' );
			}

		}

		// If the image cropping is done, crop the image and save a full/thumb version
		if ( isset( $_POST['avatar-crop-submit'] ) ) {

			// Check the nonce
			check_admin_referer( 'bp_avatar_cropstore' );

			if ( !bp_core_avatar_handle_crop( array( 'object' => 'group', 'avatar_dir' => 'group-avatars', 'item_id' => $bp->groups->current_group->id, 'original_file' => $_POST['image_src'], 'crop_x' => $_POST['x'], 'crop_y' => $_POST['y'], 'crop_w' => $_POST['w'], 'crop_h' => $_POST['h'] ) ) )
				bp_core_add_message( __( 'There was a problem cropping the avatar, please try uploading it again', 'buddypress' ) );
			else
				bp_core_add_message( __( 'The new group avatar was uploaded successfully!', 'buddypress' ) );

		}

		do_action( 'groups_screen_group_admin_avatar', $bp->groups->current_group->id );

		bp_core_load_template( apply_filters( 'groups_template_group_admin_avatar', 'groups/single/home' ) );
	}
}
add_action( 'bp_screens', 'groups_screen_group_admin_avatar' );

/**
 * This function handles actions related to member management on the group admin.
 *
 * @package BuddyPress
 */
function groups_screen_group_admin_manage_members() {
	global $bp;

	if ( bp_is_groups_component() && bp_is_action_variable( 'manage-members', 0 ) ) {

		if ( !$bp->is_item_admin )
			return false;

		if ( bp_action_variable( 1 ) && bp_action_variable( 2 ) && bp_action_variable( 3 ) ) {
			if ( bp_is_action_variable( 'promote', 1 ) && ( bp_is_action_variable( 'mod', 2 ) || bp_is_action_variable( 'admin', 2 ) ) && is_numeric( bp_action_variable( 3 ) ) ) {
				$user_id = bp_action_variable( 3 );
				$status  = bp_action_variable( 2 );

				// Check the nonce first.
				if ( !check_admin_referer( 'groups_promote_member' ) )
					return false;

				// Promote a user.
				if ( !groups_promote_member( $user_id, $bp->groups->current_group->id, $status ) )
					bp_core_add_message( __( 'There was an error when promoting that user, please try again', 'buddypress' ), 'error' );
				else
					bp_core_add_message( __( 'User promoted successfully', 'buddypress' ) );

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

				// Stop sole admins from abandoning their group
		 		$group_admins = groups_get_group_admins( $bp->groups->current_group->id );
			 	if ( 1 == count( $group_admins ) && $group_admins[0]->user_id == $user_id )
					bp_core_add_message( __( 'This group must have at least one admin', 'buddypress' ), 'error' );

				// Demote a user.
				elseif ( !groups_demote_member( $user_id, $bp->groups->current_group->id ) )
					bp_core_add_message( __( 'There was an error when demoting that user, please try again', 'buddypress' ), 'error' );
				else
					bp_core_add_message( __( 'User demoted successfully', 'buddypress' ) );

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
					bp_core_add_message( __( 'There was an error when banning that user, please try again', 'buddypress' ), 'error' );
				else
					bp_core_add_message( __( 'User banned successfully', 'buddypress' ) );

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
					bp_core_add_message( __( 'There was an error when unbanning that user, please try again', 'buddypress' ), 'error' );
				else
					bp_core_add_message( __( 'User ban removed successfully', 'buddypress' ) );

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
					bp_core_add_message( __( 'There was an error removing that user from the group, please try again', 'buddypress' ), 'error' );
				else
					bp_core_add_message( __( 'User removed successfully', 'buddypress' ) );

				do_action( 'groups_removed_member', $user_id, $bp->groups->current_group->id );

				bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/manage-members/' );
			}
		}

		do_action( 'groups_screen_group_admin_manage_members', $bp->groups->current_group->id );

		bp_core_load_template( apply_filters( 'groups_template_group_admin_manage_members', 'groups/single/home' ) );
	}
}
add_action( 'bp_screens', 'groups_screen_group_admin_manage_members' );

function groups_screen_group_admin_requests() {
	global $bp;

	if ( bp_is_groups_component() && bp_is_action_variable( 'membership-requests', 0 ) ) {

		if ( !$bp->is_item_admin || 'public' == $bp->groups->current_group->status )
			return false;

		// Remove any screen notifications
		bp_core_delete_notifications_by_type( bp_loggedin_user_id(), $bp->groups->id, 'new_membership_request' );

		$request_action = (string)bp_action_variable( 1 );
		$membership_id  = (int)bp_action_variable( 2 );

		if ( !empty( $request_action ) && !empty( $membership_id ) ) {
			if ( 'accept' == $request_action && is_numeric( $membership_id ) ) {

				// Check the nonce first.
				if ( !check_admin_referer( 'groups_accept_membership_request' ) )
					return false;

				// Accept the membership request
				if ( !groups_accept_membership_request( $membership_id ) )
					bp_core_add_message( __( 'There was an error accepting the membership request, please try again.', 'buddypress' ), 'error' );
				else
					bp_core_add_message( __( 'Group membership request accepted', 'buddypress' ) );

			} elseif ( 'reject' == $request_action && is_numeric( $membership_id ) ) {
				/* Check the nonce first. */
				if ( !check_admin_referer( 'groups_reject_membership_request' ) )
					return false;

				// Reject the membership request
				if ( !groups_reject_membership_request( $membership_id ) )
					bp_core_add_message( __( 'There was an error rejecting the membership request, please try again.', 'buddypress' ), 'error' );
				else
					bp_core_add_message( __( 'Group membership request rejected', 'buddypress' ) );
			}

			do_action( 'groups_group_request_managed', $bp->groups->current_group->id, $request_action, $membership_id );
			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/membership-requests/' );
		}

		do_action( 'groups_screen_group_admin_requests', $bp->groups->current_group->id );
		bp_core_load_template( apply_filters( 'groups_template_group_admin_requests', 'groups/single/home' ) );
	}
}
add_action( 'bp_screens', 'groups_screen_group_admin_requests' );

function groups_screen_group_admin_delete_group() {
	global $bp;

	if ( bp_is_groups_component() && bp_is_action_variable( 'delete-group', 0 ) ) {

		if ( !$bp->is_item_admin && !is_super_admin() )
			return false;

		if ( isset( $_REQUEST['delete-group-button'] ) && isset( $_REQUEST['delete-group-understand'] ) ) {
			// Check the nonce first.
			if ( !check_admin_referer( 'groups_delete_group' ) )
				return false;

			do_action( 'groups_before_group_deleted', $bp->groups->current_group->id );

			// Group admin has deleted the group, now do it.
			if ( !groups_delete_group( $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error deleting the group, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'The group was deleted successfully', 'buddypress' ) );

				do_action( 'groups_group_deleted', $bp->groups->current_group->id );

				bp_core_redirect( bp_loggedin_user_domain() . bp_get_groups_slug() . '/' );
			}

			bp_core_redirect( bp_loggedin_user_domain() . bp_get_groups_slug() );
		}

		do_action( 'groups_screen_group_admin_delete_group', $bp->groups->current_group->id );

		bp_core_load_template( apply_filters( 'groups_template_group_admin_delete_group', 'groups/single/home' ) );
	}
}
add_action( 'bp_screens', 'groups_screen_group_admin_delete_group' );

/**
 * Renders the group settings fields on the Notification Settings page
 *
 * @package BuddyPress
 */
function groups_screen_notification_settings() {
	global $bp;

	if ( !$group_invite = bp_get_user_meta( $bp->displayed_user->id, 'notification_groups_invite', true ) )
		$group_invite  = 'yes';

	if ( !$group_update = bp_get_user_meta( $bp->displayed_user->id, 'notification_groups_group_updated', true ) )
		$group_update  = 'yes';

	if ( !$group_promo = bp_get_user_meta( $bp->displayed_user->id, 'notification_groups_admin_promotion', true ) )
		$group_promo   = 'yes';

	if ( !$group_request = bp_get_user_meta( $bp->displayed_user->id, 'notification_groups_membership_request', true ) )
		$group_request = 'yes';
?>

	<table class="notification-settings" id="groups-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _e( 'Groups', 'buddypress' ) ?></th>
				<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
				<th class="no"><?php _e( 'No', 'buddypress' )?></th>
			</tr>
		</thead>

		<tbody>
			<tr id="groups-notification-settings-invitation">
				<td></td>
				<td><?php _e( 'A member invites you to join a group', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_groups_invite]" value="yes" <?php checked( $group_invite, 'yes', true ) ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_groups_invite]" value="no" <?php checked( $group_invite, 'no', true ) ?>/></td>
			</tr>
			<tr id="groups-notification-settings-info-updated">
				<td></td>
				<td><?php _e( 'Group information is updated', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_groups_group_updated]" value="yes" <?php checked( $group_update, 'yes', true ) ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_groups_group_updated]" value="no" <?php checked( $group_update, 'no', true ) ?>/></td>
			</tr>
			<tr id="groups-notification-settings-promoted">
				<td></td>
				<td><?php _e( 'You are promoted to a group administrator or moderator', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_groups_admin_promotion]" value="yes" <?php checked( $group_promo, 'yes', true ) ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_groups_admin_promotion]" value="no" <?php checked( $group_promo, 'no', true ) ?>/></td>
			</tr>
			<tr id="groups-notification-settings-request">
				<td></td>
				<td><?php _e( 'A member requests to join a private group for which you are an admin', 'buddypress' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_groups_membership_request]" value="yes" <?php checked( $group_request, 'yes', true ) ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_groups_membership_request]" value="no" <?php checked( $group_request, 'no', true ) ?>/></td>
			</tr>

			<?php do_action( 'groups_screen_notification_settings' ); ?>

		</tbody>
	</table>

<?php
}
add_action( 'bp_notification_settings', 'groups_screen_notification_settings' );
?>