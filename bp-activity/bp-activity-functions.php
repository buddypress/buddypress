<?php

/*******************************************************************************
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

function bp_activity_get( $args = '' ) {
	$defaults = array(
		'max'              => false,  // Maximum number of results to return
		'page'             => 1,      // page 1 without a per_page will result in no pagination.
		'per_page'         => false,  // results per page
		'sort'             => 'DESC', // sort ASC or DESC
		'display_comments' => false,  // false for no comments. 'stream' for within stream display, 'threaded' for below each activity item

		'search_terms'     => false,  // Pass search terms as a string
		'show_hidden'      => false,  // Show activity items that are hidden site-wide?
		'exclude'          => false,  // Comma-separated list of activity IDs to exclude
		'in'               => false,  // Comma-separated list or array of activity IDs to which you want to limit the query

		/**
		 * Pass filters as an array -- all filter items can be multiple values comma separated:
		 * array(
		 * 	'user_id'      => false, // user_id to filter on
		 *	'object'       => false, // object to filter on e.g. groups, profile, status, friends
		 *	'action'       => false, // action to filter on e.g. activity_update, profile_updated
		 *	'primary_id'   => false, // object ID to filter on e.g. a group_id or forum_id or blog_id etc.
		 *	'secondary_id' => false, // secondary object ID to filter on e.g. a post_id
		 * );
		 */
		'filter' => array()
	);
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	// Attempt to return a cached copy of the first page of sitewide activity.
	if ( 1 == (int)$page && empty( $max ) && empty( $search_terms ) && empty( $filter ) && 'DESC' == $sort && empty( $exclude ) ) {
		if ( !$activity = wp_cache_get( 'bp_activity_sitewide_front', 'bp' ) ) {
			$activity = BP_Activity_Activity::get( $max, $page, $per_page, $sort, $search_terms, $filter, $display_comments, $show_hidden );
			wp_cache_set( 'bp_activity_sitewide_front', $activity, 'bp' );
		}
	} else
		$activity = BP_Activity_Activity::get( $max, $page, $per_page, $sort, $search_terms, $filter, $display_comments, $show_hidden, $exclude, $in );

	return apply_filters( 'bp_activity_get', $activity, &$r );
}

function bp_activity_get_specific( $args = '' ) {
	$defaults = array(
		'activity_ids'     => false,  // A single activity_id or array of IDs.
		'page'             => 1,      // page 1 without a per_page will result in no pagination.
		'per_page'         => false,  // results per page
		'max'              => false,  // Maximum number of results to return
		'sort'             => 'DESC', // sort ASC or DESC
		'display_comments' => false   // true or false to display threaded comments for these specific activity items
	);
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	return apply_filters( 'bp_activity_get_specific', BP_Activity_Activity::get( $max, $page, $per_page, $sort, false, false, $display_comments, false, false, $activity_ids ) );
}

function bp_activity_add( $args = '' ) {
	global $bp;

	$defaults = array(
		'id'                => false, // Pass an existing activity ID to update an existing entry.

		'action'            => '',    // The activity action - e.g. "Jon Doe posted an update"
		'content'           => '',    // Optional: The content of the activity item e.g. "BuddyPress is awesome guys!"

		'component'         => false, // The name/ID of the component e.g. groups, profile, mycomponent
		'type'              => false, // The activity type e.g. activity_update, profile_updated
		'primary_link'      => '',    // Optional: The primary URL for this item in RSS feeds (defaults to activity permalink)

		'user_id'           => $bp->loggedin_user->id, // Optional: The user to record the activity for, can be false if this activity is not for a user.
		'item_id'           => false, // Optional: The ID of the specific item being recorded, e.g. a blog_id
		'secondary_item_id' => false, // Optional: A second ID used to further filter e.g. a comment_id
		'recorded_time'     => bp_core_current_time(), // The GMT time that this activity was recorded
		'hide_sitewide'     => false  // Should this be hidden on the sitewide activity stream?
	);
	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );

	// Make sure we are backwards compatible
	if ( empty( $component ) && !empty( $component_name ) )
		$component = $component_name;

	if ( empty( $type ) && !empty( $component_action ) )
		$type = $component_action;

	// Setup activity to be added
	$activity                    = new BP_Activity_Activity( $id );
	$activity->user_id           = $user_id;
	$activity->component         = $component;
	$activity->type              = $type;
	$activity->action            = $action;
	$activity->content           = $content;
	$activity->primary_link      = $primary_link;
	$activity->item_id           = $item_id;
	$activity->secondary_item_id = $secondary_item_id;
	$activity->date_recorded     = $recorded_time;
	$activity->hide_sitewide     = $hide_sitewide;

	if ( !$activity->save() )
		return false;

	// If this is an activity comment, rebuild the tree
	if ( 'activity_comment' == $activity->type )
		BP_Activity_Activity::rebuild_activity_comment_tree( $activity->item_id );

	wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );
	do_action( 'bp_activity_add', $params );

	return $activity->id;
}

function bp_activity_post_update( $args = '' ) {
	global $bp;

	$defaults = array(
		'content' => false,
		'user_id' => $bp->loggedin_user->id
	);
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( empty( $content ) || !strlen( trim( $content ) ) )
		return false;

	// Record this on the user's profile
	$from_user_link = bp_core_get_userlink( $user_id );
	$activity_action = sprintf( __( '%s posted an update:', 'buddypress' ), $from_user_link );
	$activity_content = $content;

	$primary_link = bp_core_get_userlink( $user_id, false, true );

	// Now write the values
	$activity_id = bp_activity_add( array(
		'user_id'      => $user_id,
		'action'       => apply_filters( 'bp_activity_new_update_action', $activity_action ),
		'content'      => apply_filters( 'bp_activity_new_update_content', $activity_content ),
		'primary_link' => apply_filters( 'bp_activity_new_update_primary_link', $primary_link ),
		'component'    => $bp->activity->id,
		'type'         => 'activity_update'
	) );

	// Add this update to the "latest update" usermeta so it can be fetched anywhere.
	update_user_meta( $bp->loggedin_user->id, 'bp_latest_update', array( 'id' => $activity_id, 'content' => wp_filter_kses( $content ) ) );

 	// Require the notifications code so email notifications can be set on the 'bp_activity_posted_update' action.
	require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-notifications.php' );

	do_action( 'bp_activity_posted_update', $content, $user_id, $activity_id );

	return $activity_id;
}

function bp_activity_new_comment( $args = '' ) {
	global $bp;

	$defaults = array(
		'id'          => false,
		'content'     => false,
		'user_id'     => $bp->loggedin_user->id,
		'activity_id' => false, // ID of the root activity item
		'parent_id'   => false  // ID of a parent comment (optional)
	);

	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );

	if ( empty($content) || empty($user_id) || empty($activity_id) )
		return false;

	if ( empty($parent_id) )
		$parent_id = $activity_id;

	// Check to see if the parent activity is hidden, and if so, hide this comment publically.
	$activity = new BP_Activity_Activity( $activity_id );
	$is_hidden = ( (int)$activity->hide_sitewide ) ? 1 : 0;

	// Insert the activity comment
	$comment_id = bp_activity_add( array(
		'id' => $id,
		'action' => apply_filters( 'bp_activity_comment_action', sprintf( __( '%s posted a new activity comment:', 'buddypress' ), bp_core_get_userlink( $user_id ) ) ),
		'content' => apply_filters( 'bp_activity_comment_content', $content ),
		'component' => $bp->activity->id,
		'type' => 'activity_comment',
		'user_id' => $user_id,
		'item_id' => $activity_id,
		'secondary_item_id' => $parent_id,
		'hide_sitewide' => $is_hidden
	) );

	// Send an email notification if settings allow
	require_once( BP_PLUGIN_DIR . '/bp-activity/bp-activity-notifications.php' );
	bp_activity_new_comment_notification( $comment_id, $user_id, $params );

	// Clear the comment cache for this activity
	wp_cache_delete( 'bp_activity_comments_' . $parent_id );

	do_action( 'bp_activity_comment_posted', $comment_id, $params );

	return $comment_id;
}

/**
 * bp_activity_get_activity_id()
 *
 * Fetch the activity_id for an existing activity entry in the DB.
 *
 * @package BuddyPress Activity
 */
function bp_activity_get_activity_id( $args = '' ) {
	$defaults = array(
		'user_id'           => false,
		'component'         => false,
		'type'              => false,
		'item_id'           => false,
		'secondary_item_id' => false,
		'action'            => false,
		'content'           => false,
		'date_recorded'     => false,
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

 	return apply_filters( 'bp_activity_get_activity_id', BP_Activity_Activity::get_id( $user_id, $component, $type, $item_id, $secondary_item_id, $action, $content, $date_recorded ) );
}

/***
 * Deleting Activity
 *
 * If you're looking to hook into one action that provides the ID(s) of
 * the activity/activities deleted, then use:
 *
 * add_action( 'bp_activity_deleted_activities', 'my_function' );
 *
 * The action passes one parameter that is a single activity ID or an
 * array of activity IDs depending on the number deleted.
 *
 * If you are deleting an activity comment please use bp_activity_delete_comment();
*/

function bp_activity_delete( $args = '' ) {
	global $bp;

	// Pass one or more the of following variables to delete by those variables
	$defaults = array(
		'id'                => false,
		'action'            => false,
		'content'           => false,
		'component'         => false,
		'type'              => false,
		'primary_link'      => false,
		'user_id'           => false,
		'item_id'           => false,
		'secondary_item_id' => false,
		'date_recorded'     => false,
		'hide_sitewide'     => false
	);

	$args = wp_parse_args( $args, $defaults );

	if ( !$activity_ids_deleted = BP_Activity_Activity::delete( $args ) )
		return false;

	// Check if the user's latest update has been deleted
	if ( empty( $args['user_id'] ) )
		$user_id = $bp->loggedin_user->id;
	else
		$user_id = $args['user_id'];

	$latest_update = get_user_meta( $user_id, 'bp_latest_update', true );
	if ( !empty( $latest_update ) ) {
		if ( in_array( (int)$latest_update['id'], (array)$activity_ids_deleted ) )
			delete_user_meta( $user_id, 'bp_latest_update' );
	}

	do_action( 'bp_activity_delete', $args );
	do_action( 'bp_activity_deleted_activities', $activity_ids_deleted );

	wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );

	return true;
}
	// The following functions have been deprecated in place of bp_activity_delete()
	function bp_activity_delete_by_item_id( $args = '' ) {
		global $bp;

		$defaults = array( 'item_id' => false, 'component' => false, 'type' => false, 'user_id' => false, 'secondary_item_id' => false );
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return bp_activity_delete( array( 'item_id' => $item_id, 'component' => $component, 'type' => $type, 'user_id' => $user_id, 'secondary_item_id' => $secondary_item_id ) );
	}

	function bp_activity_delete_by_activity_id( $activity_id ) {
		return bp_activity_delete( array( 'id' => $activity_id ) );
	}

	function bp_activity_delete_by_content( $user_id, $content, $component, $type ) {
		return bp_activity_delete( array( 'user_id' => $user_id, 'content' => $content, 'component' => $component, 'type' => $type ) );
	}

	function bp_activity_delete_for_user_by_component( $user_id, $component ) {
		return bp_activity_delete( array( 'user_id' => $user_id, 'component' => $component ) );
	}
	// End deprecation

function bp_activity_delete_comment( $activity_id, $comment_id ) {
	/***
	 * You may want to hook into this filter if you want to override this function and
	 * handle the deletion of child comments differently. Make sure you return false.
	 */
	if ( !apply_filters( 'bp_activity_delete_comment_pre', true, $activity_id, $comment_id ) )
		return false;

	// Delete any children of this comment.
	bp_activity_delete_children( $activity_id, $comment_id );

	// Delete the actual comment
	if ( !bp_activity_delete( array( 'id' => $comment_id, 'type' => 'activity_comment' ) ) )
		return false;

	// Recalculate the comment tree
	BP_Activity_Activity::rebuild_activity_comment_tree( $activity_id );

	do_action( 'bp_activity_delete_comment', $activity_id, $comment_id );

	return true;
}
	function bp_activity_delete_children( $activity_id, $comment_id) {
		// Recursively delete all children of this comment.
		if ( $children = BP_Activity_Activity::get_child_comments( $comment_id ) ) {
			foreach( (array)$children as $child )
				bp_activity_delete_children( $activity_id, $child->id );
		}
		bp_activity_delete( array( 'secondary_item_id' => $comment_id, 'type' => 'activity_comment', 'item_id' => $activity_id ) );
	}

function bp_activity_get_permalink( $activity_id, $activity_obj = false ) {
	global $bp;

	if ( !$activity_obj )
		$activity_obj = new BP_Activity_Activity( $activity_id );

	if ( 'new_blog_post' == $activity_obj->type || 'new_blog_comment' == $activity_obj->type || 'new_forum_topic' == $activity_obj->type || 'new_forum_post' == $activity_obj->type )
		$link = $activity_obj->primary_link;
	else {
		if ( 'activity_comment' == $activity_obj->type )
			$link = $bp->root_domain . '/' . $bp->activity->root_slug . '/p/' . $activity_obj->item_id . '/';
		else
			$link = $bp->root_domain . '/' . $bp->activity->root_slug . '/p/' . $activity_obj->id . '/';
	}

	return apply_filters( 'bp_activity_get_permalink', $link );
}

function bp_activity_hide_user_activity( $user_id ) {
	return BP_Activity_Activity::hide_all_for_user( $user_id );
}

/**
 * bp_activity_thumbnail_content_images()
 *
 * Take content, remove all images and replace them with one thumbnail image.
 *
 * @package BuddyPress Activity
 * @param $content str - The content to work with
 * @param $link str - Optional. The URL that the image should link to
 * @return $content str - The content with images stripped and replaced with a single thumb.
 */
function bp_activity_thumbnail_content_images( $content, $link = false ) {
	global $post;

	preg_match_all( '/<img[^>]*>/Ui', $content, $matches );
	$content = preg_replace('/<img[^>]*>/Ui', '', $content );

	if ( !empty( $matches ) && !empty( $matches[0] ) ) {
		// Get the SRC value
		preg_match( '/<img.*?(src\=[\'|"]{0,1}.*?[\'|"]{0,1})[\s|>]{1}/i', $matches[0][0], $src );

		// Get the width and height
		preg_match( '/<img.*?(height\=[\'|"]{0,1}.*?[\'|"]{0,1})[\s|>]{1}/i', $matches[0][0], $height );
		preg_match( '/<img.*?(width\=[\'|"]{0,1}.*?[\'|"]{0,1})[\s|>]{1}/i', $matches[0][0], $width );

		if ( !empty( $src ) ) {
			$src = substr( substr( str_replace( 'src=', '', $src[1] ), 0, -1 ), 1 );
			$height = substr( substr( str_replace( 'height=', '', $height[1] ), 0, -1 ), 1 );
			$width = substr( substr( str_replace( 'width=', '', $width[1] ), 0, -1 ), 1 );

			if ( empty( $width ) || empty( $height ) ) {
				$width = 100;
				$height = 100;
			}

			$ratio = (int)$width / (int)$height;
			$new_height = (int)$height >= 100 ? 100 : $height;
			$new_width = $new_height * $ratio;

			$image = '<img src="' . esc_attr( $src ) . '" width="' . $new_width . '" height="' . $new_height . '" alt="' . __( 'Thumbnail', 'buddypress' ) . '" class="align-left thumbnail" />';

			if ( !empty( $link ) ) {
				$image = '<a href="' . $link . '">' . $image . '</a>';
			}

			$content = $image . $content;
		}
	}

	return apply_filters( 'bp_activity_thumbnail_content_images', $content, $matches );
}

?>
