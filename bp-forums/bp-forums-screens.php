<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function bp_forums_directory_forums_setup() {
	global $bp;

	if ( bp_is_forums_component() && ( !bp_current_action() || ( 'tag' == bp_current_action() && bp_action_variables() ) ) && !bp_current_item() ) {
		if ( !bp_forums_has_directory() )
			return false;

		if ( !bp_forums_is_installed_correctly() ) {
			bp_core_add_message( __( 'The forums component has not been set up yet.', 'buddypress' ), 'error' );
			bp_core_redirect( bp_get_root_domain() );
		}

		bp_update_is_directory( true, 'forums' );

		do_action( 'bbpress_init' );

		// Check to see if the user has posted a new topic from the forums page.
		if ( isset( $_POST['submit_topic'] ) && bp_is_active( 'forums' ) ) {
			check_admin_referer( 'bp_forums_new_topic' );

			$bp->groups->current_group = groups_get_group( array( 'group_id' => $_POST['topic_group_id'] ) );
			if ( !empty( $bp->groups->current_group->id ) ) {
				// Auto join this user if they are not yet a member of this group
				if ( !is_super_admin() && 'public' == $bp->groups->current_group->status && !groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) )
					groups_join_group( $bp->groups->current_group->id );

				$error_message = '';

				$forum_id = groups_get_groupmeta( $bp->groups->current_group->id, 'forum_id' );
				if ( !empty( $forum_id ) ) {
					if ( empty( $_POST['topic_title'] ) )
						$error_message = __( 'Please provide a title for your forum topic.', 'buddypress' );
					else if ( empty( $_POST['topic_text'] ) )
						$error_message = __( 'Forum posts cannot be empty. Please enter some text.', 'buddypress' );

					if ( $error_message ) {
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

				} else {
					bp_core_add_message( __( 'Please pick the group forum where you would like to post this topic.', 'buddypress' ), 'error' );
					bp_core_redirect( add_query_arg( 'new', '', bp_get_forums_directory_permalink() ) );
				}

			}	 else {
				bp_core_add_message( __( 'Please pick the group forum where you would like to post this topic.', 'buddypress' ), 'error' );
				bp_core_redirect( add_query_arg( 'new', '', bp_get_forums_directory_permalink() ) );
			}
		}

		do_action( 'bp_forums_directory_forums_setup' );

		bp_core_load_template( apply_filters( 'bp_forums_template_directory_forums_setup', 'forums/index' ) );
	}
}
add_action( 'bp_screens', 'bp_forums_directory_forums_setup', 2 );

function bp_member_forums_screen_topics() {
	global $bp;

	do_action( 'bp_member_forums_screen_topics' );

	bp_core_load_template( apply_filters( 'bp_member_forums_screen_topics', 'members/single/home' ) );
}

function bp_member_forums_screen_replies() {
	global $bp;

	do_action( 'bp_member_forums_screen_replies' );

	bp_core_load_template( apply_filters( 'bp_member_forums_screen_replies', 'members/single/home' ) );
}

/**
 * Loads the template content for a user's Favorites forum tab.
 *
 * Note that this feature is not fully implemented at the moment.
 *
 * @package BuddyPress Forums
 */
function bp_member_forums_screen_favorites() {
	global $bp;

	do_action( 'bp_member_forums_screen_favorites' );

	bp_core_load_template( apply_filters( 'bp_member_forums_screen_favorites', 'members/single/home' ) );
}

function bp_forums_screen_single_forum() {
	global $bp;

	if ( !bp_is_forums_component() || !bp_is_current_action( 'forum' ) || !bp_action_variable( 0 ) )
		return false;

	do_action( 'bp_forums_screen_single_forum' );

	bp_core_load_template( apply_filters( 'bp_forums_screen_single_forum', 'forums/single/forum' ) );
}
add_action( 'bp_screens', 'bp_forums_screen_single_forum' );

function bp_forums_screen_single_topic() {
	global $bp;

	if ( !bp_is_forums_component() || !bp_is_current_action( 'topic' ) || !bp_action_variable( 0 ) )
		return false;

	do_action( 'bp_forums_screen_single_topic' );

	bp_core_load_template( apply_filters( 'bp_forums_screen_single_topic', 'forums/single/topic' ) );
}
add_action( 'bp_screens', 'bp_forums_screen_single_topic' );
?>