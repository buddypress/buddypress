<?php

/**
 * Action functions are exactly the same as screen functions, however they do
 * not have a template screen associated with them. Usually they will send the
 * user back to the default screen after execution.
 *
 * @package BuddyPress
 * @subpackage ActivityActions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Allow core components and dependent plugins to register activity actions.
 *
 * @since BuddyPress (1.2)
 *
 * @uses do_action() To call 'bp_register_activity_actions' hook.
 */
function bp_register_activity_actions() {
	do_action( 'bp_register_activity_actions' );
}
add_action( 'bp_init', 'bp_register_activity_actions', 8 );

/**
 * Catch and route requests for single activity item permalinks.
 *
 * @since BuddyPress (1.2)
 *
 * @global object $bp BuddyPress global settings
 * @uses bp_is_activity_component()
 * @uses bp_is_current_action()
 * @uses bp_action_variable()
 * @uses bp_activity_get_specific()
 * @uses bp_is_active()
 * @uses bp_core_get_user_domain()
 * @uses groups_get_group()
 * @uses bp_get_group_permalink()
 * @uses apply_filters_ref_array() To call the 'bp_activity_permalink_redirect_url' hook.
 * @uses bp_core_redirect()
 * @uses bp_get_root_domain()
 *
 * @return bool False on failure.
 */
function bp_activity_action_permalink_router() {

	// Not viewing activity
	if ( ! bp_is_activity_component() || ! bp_is_current_action( 'p' ) )
		return false;

	// No activity to display
	if ( ! bp_action_variable( 0 ) || ! is_numeric( bp_action_variable( 0 ) ) )
		return false;

	// Get the activity details
	$activity = bp_activity_get_specific( array( 'activity_ids' => bp_action_variable( 0 ), 'show_hidden' => true ) );

	// 404 if activity does not exist
	if ( empty( $activity['activities'][0] ) ) {
		bp_do_404();
		return;
	} else {
		$activity = $activity['activities'][0];
	}

	// Do not redirect at default
	$redirect = false;

	// Redirect based on the type of activity
	if ( bp_is_active( 'groups' ) && $activity->component == buddypress()->groups->id ) {

		// Activity is a user update
		if ( ! empty( $activity->user_id ) ) {
			$redirect = bp_core_get_user_domain( $activity->user_id, $activity->user_nicename, $activity->user_login ) . bp_get_activity_slug() . '/' . $activity->id . '/';

		// Activity is something else
		} else {

			// Set redirect to group activity stream
			if ( $group = groups_get_group( array( 'group_id' => $activity->item_id ) ) ) {
				$redirect = bp_get_group_permalink( $group ) . bp_get_activity_slug() . '/' . $activity->id . '/';
			}
		}

	// Set redirect to users' activity stream
	} else if ( ! empty( $activity->user_id ) ) {
		$redirect = bp_core_get_user_domain( $activity->user_id, $activity->user_nicename, $activity->user_login ) . bp_get_activity_slug() . '/' . $activity->id . '/';
	}

	// If set, add the original query string back onto the redirect URL
	if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
		$query_frags = array();
		wp_parse_str( $_SERVER['QUERY_STRING'], $query_frags );
		$redirect = add_query_arg( urlencode_deep( $query_frags ), $redirect );
	}

	// Allow redirect to be filtered
	if ( ! $redirect = apply_filters_ref_array( 'bp_activity_permalink_redirect_url', array( $redirect, &$activity ) ) ) {
		bp_core_redirect( bp_get_root_domain() );
	}

	// Redirect to the actual activity permalink page
	bp_core_redirect( $redirect );
}
add_action( 'bp_actions', 'bp_activity_action_permalink_router' );

/**
 * Delete specific activity item and redirect to previous page.
 *
 * @since BuddyPress (1.1)
 *
 * @param int $activity_id Activity id to be deleted. Defaults to 0.
 *
 * @uses bp_is_activity_component()
 * @uses bp_is_current_action()
 * @uses bp_action_variable()
 * @uses check_admin_referer()
 * @uses bp_activity_user_can_delete()
 * @uses do_action() Calls 'bp_activity_before_action_delete_activity' hook to allow actions to be taken before the activity is deleted.
 * @uses bp_activity_delete()
 * @uses bp_core_add_message()
 * @uses do_action() Calls 'bp_activity_action_delete_activity' hook to allow actions to be taken after the activity is deleted.
 * @uses bp_core_redirect()
 *
 * @return bool False on failure.
 */
function bp_activity_action_delete_activity( $activity_id = 0 ) {

	// Not viewing activity or action is not delete
	if ( !bp_is_activity_component() || !bp_is_current_action( 'delete' ) )
		return false;

	if ( empty( $activity_id ) && bp_action_variable( 0 ) )
		$activity_id = (int) bp_action_variable( 0 );

	// Not viewing a specific activity item
	if ( empty( $activity_id ) )
		return false;

	// Check the nonce
	check_admin_referer( 'bp_activity_delete_link' );

	// Load up the activity item
	$activity = new BP_Activity_Activity( $activity_id );

	// Check access
	if ( ! bp_activity_user_can_delete( $activity ) )
		return false;

	// Call the action before the delete so plugins can still fetch information about it
	do_action( 'bp_activity_before_action_delete_activity', $activity_id, $activity->user_id );

	// Delete the activity item and provide user feedback
	if ( bp_activity_delete( array( 'id' => $activity_id, 'user_id' => $activity->user_id ) ) )
		bp_core_add_message( __( 'Activity deleted successfully', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was an error when deleting that activity', 'buddypress' ), 'error' );

	do_action( 'bp_activity_action_delete_activity', $activity_id, $activity->user_id );

	// Check for the redirect query arg, otherwise let WP handle things
 	if ( !empty( $_GET['redirect_to'] ) )
		bp_core_redirect( esc_url( $_GET['redirect_to'] ) );
	else
		bp_core_redirect( wp_get_referer() );
}
add_action( 'bp_actions', 'bp_activity_action_delete_activity' );

/**
 * Mark specific activity item as spam and redirect to previous page.
 *
 * @since BuddyPress (1.6)
 *
 * @global object $bp BuddyPress global settings
 * @param int $activity_id Activity id to be deleted. Defaults to 0.
 * @return bool False on failure.
 */
function bp_activity_action_spam_activity( $activity_id = 0 ) {
	global $bp;

	// Not viewing activity, or action is not spam, or Akismet isn't present
	if ( !bp_is_activity_component() || !bp_is_current_action( 'spam' ) || empty( $bp->activity->akismet ) )
		return false;

	if ( empty( $activity_id ) && bp_action_variable( 0 ) )
		$activity_id = (int) bp_action_variable( 0 );

	// Not viewing a specific activity item
	if ( empty( $activity_id ) )
		return false;

	// Is the current user allowed to spam items?
	if ( !bp_activity_user_can_mark_spam() )
		return false;

	// Load up the activity item
	$activity = new BP_Activity_Activity( $activity_id );
	if ( empty( $activity->id ) )
		return false;

	// Check nonce
	check_admin_referer( 'bp_activity_akismet_spam_' . $activity->id );

	// Call an action before the spamming so plugins can modify things if they want to
	do_action( 'bp_activity_before_action_spam_activity', $activity->id, $activity );

	// Mark as spam
	bp_activity_mark_as_spam( $activity );
	$activity->save();

	// Tell the user the spamming has been succesful
	bp_core_add_message( __( 'The activity item has been marked as spam and is no longer visible.', 'buddypress' ) );

	do_action( 'bp_activity_action_spam_activity', $activity_id, $activity->user_id );

	// Check for the redirect query arg, otherwise let WP handle things
 	if ( !empty( $_GET['redirect_to'] ) )
		bp_core_redirect( esc_url( $_GET['redirect_to'] ) );
	else
		bp_core_redirect( wp_get_referer() );
}
add_action( 'bp_actions', 'bp_activity_action_spam_activity' );

/**
 * Post user/group activity update.
 *
 * @since BuddyPress (1.2)
 *
 * @uses is_user_logged_in()
 * @uses bp_is_activity_component()
 * @uses bp_is_current_action()
 * @uses check_admin_referer()
 * @uses apply_filters() To call 'bp_activity_post_update_content' hook.
 * @uses apply_filters() To call 'bp_activity_post_update_object' hook.
 * @uses apply_filters() To call 'bp_activity_post_update_item_id' hook.
 * @uses bp_core_add_message()
 * @uses bp_core_redirect()
 * @uses bp_activity_post_update()
 * @uses groups_post_update()
 * @uses bp_core_redirect()
 * @uses apply_filters() To call 'bp_activity_custom_update' hook.
 *
 * @return bool False on failure.
 */
function bp_activity_action_post_update() {

	// Do not proceed if user is not logged in, not viewing activity, or not posting
	if ( !is_user_logged_in() || !bp_is_activity_component() || !bp_is_current_action( 'post' ) )
		return false;

	// Check the nonce
	check_admin_referer( 'post_update', '_wpnonce_post_update' );

	// Get activity info
	$content = apply_filters( 'bp_activity_post_update_content', $_POST['whats-new'] );

	if ( ! empty( $_POST['whats-new-post-object'] ) ) {
		$object = apply_filters( 'bp_activity_post_update_object', $_POST['whats-new-post-object'] );
	}

	if ( ! empty( $_POST['whats-new-post-in'] ) ) {
		$item_id = apply_filters( 'bp_activity_post_update_item_id', $_POST['whats-new-post-in'] );
	}

	// No activity content so provide feedback and redirect
	if ( empty( $content ) ) {
		bp_core_add_message( __( 'Please enter some content to post.', 'buddypress' ), 'error' );
		bp_core_redirect( wp_get_referer() );
	}

	// No existing item_id
	if ( empty( $item_id ) ) {
		$activity_id = bp_activity_post_update( array( 'content' => $content ) );

	// Post to groups object
	} else if ( 'groups' == $object && bp_is_active( 'groups' ) ) {
		if ( (int) $item_id ) {
			$activity_id = groups_post_update( array( 'content' => $content, 'group_id' => $item_id ) );
		}

	// Special circumstance so let filters handle it
	} else {
		$activity_id = apply_filters( 'bp_activity_custom_update', $object, $item_id, $content );
	}

	// Provide user feedback
	if ( !empty( $activity_id ) )
		bp_core_add_message( __( 'Update Posted!', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was an error when posting your update, please try again.', 'buddypress' ), 'error' );

	// Redirect
	bp_core_redirect( wp_get_referer() );
}
add_action( 'bp_actions', 'bp_activity_action_post_update' );

/**
 * Post new activity comment.
 *
 * @since BuddyPress (1.2)
 *
 * @uses is_user_logged_in()
 * @uses bp_is_activity_component()
 * @uses bp_is_current_action()
 * @uses check_admin_referer()
 * @uses apply_filters() To call 'bp_activity_post_comment_activity_id' hook.
 * @uses apply_filters() To call 'bp_activity_post_comment_content' hook.
 * @uses bp_core_add_message()
 * @uses bp_core_redirect()
 * @uses bp_activity_new_comment()
 * @uses wp_get_referer()
 *
 * @return bool False on failure.
 */
function bp_activity_action_post_comment() {

	if ( !is_user_logged_in() || !bp_is_activity_component() || !bp_is_current_action( 'reply' ) )
		return false;

	// Check the nonce
	check_admin_referer( 'new_activity_comment', '_wpnonce_new_activity_comment' );

	$activity_id = apply_filters( 'bp_activity_post_comment_activity_id', $_POST['comment_form_id'] );
	$content = apply_filters( 'bp_activity_post_comment_content', $_POST['ac_input_' . $activity_id] );

	if ( empty( $content ) ) {
		bp_core_add_message( __( 'Please do not leave the comment area blank.', 'buddypress' ), 'error' );
		bp_core_redirect( wp_get_referer() . '#ac-form-' . $activity_id );
	}

	$comment_id = bp_activity_new_comment( array(
		'content'     => $content,
		'activity_id' => $activity_id,
		'parent_id'   => false
	));

	if ( !empty( $comment_id ) )
		bp_core_add_message( __( 'Reply Posted!', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was an error posting that reply, please try again.', 'buddypress' ), 'error' );

	bp_core_redirect( wp_get_referer() . '#ac-form-' . $activity_id );
}
add_action( 'bp_actions', 'bp_activity_action_post_comment' );

/**
 * Mark activity as favorite.
 *
 * @since BuddyPress (1.2)
 *
 * @uses is_user_logged_in()
 * @uses bp_is_activity_component()
 * @uses bp_is_current_action()
 * @uses check_admin_referer()
 * @uses bp_activity_add_user_favorite()
 * @uses bp_action_variable()
 * @uses bp_core_add_message()
 * @uses bp_core_redirect()
 * @uses wp_get_referer()
 *
 * @return bool False on failure.
 */
function bp_activity_action_mark_favorite() {

	if ( !is_user_logged_in() || !bp_is_activity_component() || !bp_is_current_action( 'favorite' ) )
		return false;

	// Check the nonce
	check_admin_referer( 'mark_favorite' );

	if ( bp_activity_add_user_favorite( bp_action_variable( 0 ) ) )
		bp_core_add_message( __( 'Activity marked as favorite.', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was an error marking that activity as a favorite, please try again.', 'buddypress' ), 'error' );

	bp_core_redirect( wp_get_referer() . '#activity-' . bp_action_variable( 0 ) );
}
add_action( 'bp_actions', 'bp_activity_action_mark_favorite' );

/**
 * Remove activity from favorites.
 *
 * @since BuddyPress (1.2)
 *
 * @uses is_user_logged_in()
 * @uses bp_is_activity_component()
 * @uses bp_is_current_action()
 * @uses check_admin_referer()
 * @uses bp_activity_remove_user_favorite()
 * @uses bp_action_variable()
 * @uses bp_core_add_message()
 * @uses bp_core_redirect()
 * @uses wp_get_referer()
 *
 * @return bool False on failure.
 */
function bp_activity_action_remove_favorite() {

	if ( ! is_user_logged_in() || ! bp_is_activity_component() || ! bp_is_current_action( 'unfavorite' ) )
		return false;

	// Check the nonce
	check_admin_referer( 'unmark_favorite' );

	if ( bp_activity_remove_user_favorite( bp_action_variable( 0 ) ) )
		bp_core_add_message( __( 'Activity removed as favorite.', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was an error removing that activity as a favorite, please try again.', 'buddypress' ), 'error' );

	bp_core_redirect( wp_get_referer() . '#activity-' . bp_action_variable( 0 ) );
}
add_action( 'bp_actions', 'bp_activity_action_remove_favorite' );

/**
 * Load the sitewide activity feed.
 *
 * @since BuddyPress (1.0)
 *
 * @global object $bp BuddyPress global settings
 * @uses bp_is_activity_component()
 * @uses bp_is_current_action()
 * @uses bp_is_user()
 * @uses status_header()
 *
 * @return bool False on failure.
 */
function bp_activity_action_sitewide_feed() {
	global $bp;

	if ( ! bp_is_activity_component() || ! bp_is_current_action( 'feed' ) || bp_is_user() || ! empty( $bp->groups->current_group ) )
		return false;

	// setup the feed
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'sitewide',

		/* translators: Sitewide activity RSS title - "[Site Name] | Site Wide Activity" */
		'title'         => sprintf( __( '%s | Site Wide Activity', 'buddypress' ), bp_get_site_name() ),

		'link'          => bp_get_activity_directory_permalink(),
		'description'   => __( 'Activity feed for the entire site.', 'buddypress' ),
		'activity_args' => 'display_comments=threaded'
	) );
}
add_action( 'bp_actions', 'bp_activity_action_sitewide_feed' );

/**
 * Load a user's personal activity feed.
 *
 * @since BuddyPress (1.0)
 *
 * @uses bp_is_user_activity()
 * @uses bp_is_current_action()
 * @uses status_header()
 *
 * @return bool False on failure.
 */
function bp_activity_action_personal_feed() {
	if ( ! bp_is_user_activity() || ! bp_is_current_action( 'feed' ) ) {
		return false;
	}

	// setup the feed
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'personal',

		/* translators: Personal activity RSS title - "[Site Name] | [User Display Name] | Activity" */
		'title'         => sprintf( __( '%1$s | %2$s | Activity', 'buddypress' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),

		'link'          => trailingslashit( bp_displayed_user_domain() . bp_get_activity_slug() ),
		'description'   => sprintf( __( 'Activity feed for %s.', 'buddypress' ), bp_get_displayed_user_fullname() ),
		'activity_args' => 'user_id=' . bp_displayed_user_id()
	) );
}
add_action( 'bp_actions', 'bp_activity_action_personal_feed' );

/**
 * Load a user's friends' activity feed.
 *
 * @since BuddyPress (1.0)
 *
 * @uses bp_is_active()
 * @uses bp_is_user_activity()
 * @uses bp_is_current_action()
 * @uses bp_get_friends_slug()
 * @uses bp_is_action_variable()
 * @uses status_header()
 *
 * @return bool False on failure.
 */
function bp_activity_action_friends_feed() {
	if ( ! bp_is_active( 'friends' ) || ! bp_is_user_activity() || ! bp_is_current_action( bp_get_friends_slug() ) || ! bp_is_action_variable( 'feed', 0 ) ) {
		return false;
	}

	// setup the feed
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'friends',

		/* translators: Friends activity RSS title - "[Site Name] | [User Display Name] | Friends Activity" */
		'title'         => sprintf( __( '%1$s | %2$s | Friends Activity', 'buddypress' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),

		'link'          => trailingslashit( bp_displayed_user_domain() . bp_get_activity_slug() . '/' . bp_get_friends_slug() ),
		'description'   => sprintf( __( "Activity feed for %s's friends.", 'buddypress' ), bp_get_displayed_user_fullname() ),
		'activity_args' => 'scope=friends'
	) );
}
add_action( 'bp_actions', 'bp_activity_action_friends_feed' );

/**
 * Load the activity feed for a user's groups.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_is_active()
 * @uses bp_is_user_activity()
 * @uses bp_is_current_action()
 * @uses bp_get_groups_slug()
 * @uses bp_is_action_variable()
 * @uses status_header()
 *
 * @return bool False on failure.
 */
function bp_activity_action_my_groups_feed() {
	if ( ! bp_is_active( 'groups' ) || ! bp_is_user_activity() || ! bp_is_current_action( bp_get_groups_slug() ) || ! bp_is_action_variable( 'feed', 0 ) ) {
		return false;
	}

	// get displayed user's group IDs
	$groups    = groups_get_user_groups();
	$group_ids = implode( ',', $groups['groups'] );

	// setup the feed
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'mygroups',

		/* translators: Member groups activity RSS title - "[Site Name] | [User Display Name] | Groups Activity" */
		'title'         => sprintf( __( '%1$s | %2$s | Group Activity', 'buddypress' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),

		'link'          => trailingslashit( bp_displayed_user_domain() . bp_get_activity_slug() . '/' . bp_get_groups_slug() ),
		'description'   => sprintf( __( "Public group activity feed of which %s is a member of.", 'buddypress' ), bp_get_displayed_user_fullname() ),
		'activity_args' => array(
			'object'           => buddypress()->groups->id,
			'primary_id'       => $group_ids,
			'display_comments' => 'threaded'
		)
	) );
}
add_action( 'bp_actions', 'bp_activity_action_my_groups_feed' );

/**
 * Load a user's @mentions feed.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_is_user_activity()
 * @uses bp_is_current_action()
 * @uses bp_is_action_variable()
 * @uses status_header()
 *
 * @return bool False on failure.
 */
function bp_activity_action_mentions_feed() {
	if ( ! bp_activity_do_mentions() ) {
		return false;
	}

	if ( !bp_is_user_activity() || ! bp_is_current_action( 'mentions' ) || ! bp_is_action_variable( 'feed', 0 ) ) {
		return false;
	}

	// setup the feed
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'mentions',

		/* translators: User mentions activity RSS title - "[Site Name] | [User Display Name] | Mentions" */
		'title'         => sprintf( __( '%1$s | %2$s | Mentions', 'buddypress' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),

		'link'          => bp_displayed_user_domain() . bp_get_activity_slug() . '/mentions/',
		'description'   => sprintf( __( "Activity feed mentioning %s.", 'buddypress' ), bp_get_displayed_user_fullname() ),
		'activity_args' => array(
			'search_terms' => '@' . bp_core_get_username( bp_displayed_user_id() )
		)
	) );
}
add_action( 'bp_actions', 'bp_activity_action_mentions_feed' );

/**
 * Load a user's favorites feed.
 *
 * @since BuddyPress (1.2)
 *
 * @uses bp_is_user_activity()
 * @uses bp_is_current_action()
 * @uses bp_is_action_variable()
 * @uses status_header()
 *
 * @return bool False on failure.
 */
function bp_activity_action_favorites_feed() {
	if ( ! bp_is_user_activity() || ! bp_is_current_action( 'favorites' ) || ! bp_is_action_variable( 'feed', 0 ) ) {
		return false;
	}

	// get displayed user's favorite activity IDs
	$favs = bp_activity_get_user_favorites( bp_displayed_user_id() );
	$fav_ids = implode( ',', (array) $favs );

	// setup the feed
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'favorites',

		/* translators: User activity favorites RSS title - "[Site Name] | [User Display Name] | Favorites" */
		'title'         => sprintf( __( '%1$s | %2$s | Favorites', 'buddypress' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),

		'link'          => bp_displayed_user_domain() . bp_get_activity_slug() . '/favorites/',
		'description'   => sprintf( __( "Activity feed of %s's favorites.", 'buddypress' ), bp_get_displayed_user_fullname() ),
		'activity_args' => 'include=' . $fav_ids
	) );
}
add_action( 'bp_actions', 'bp_activity_action_favorites_feed' );

/**
 * Loads Akismet filtering for activity.
 *
 * @since BuddyPress (1.6)
 *
 * @global object $bp BuddyPress global settings
 */
function bp_activity_setup_akismet() {
	global $bp;

	// Bail if Akismet is not active
	if ( ! defined( 'AKISMET_VERSION' ) )
		return;

	// Bail if no Akismet key is set
	if ( ! bp_get_option( 'wordpress_api_key' ) && ! defined( 'WPCOM_API_KEY' ) )
		return;

	// Bail if BuddyPress Activity Akismet support has been disabled by another plugin
	if ( ! apply_filters( 'bp_activity_use_akismet', bp_is_akismet_active() ) )
		return;

	// Instantiate Akismet for BuddyPress
	$bp->activity->akismet = new BP_Akismet();
}
