<?php

/**
 * BuddyPress Blogs Activity.
 *
 * @package BuddyPress
 * @subpackage BlogsActivity
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Register activity actions for the blogs component.
 *
 * @since BuddyPress (1.0.0)
 *
 * @global object $bp The BuddyPress global settings object.
 *
 * @return bool|null Returns false if activity component is not active.
 */
function bp_blogs_register_activity_actions() {
	global $bp;

	// Bail if activity is not active
	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	if ( is_multisite() ) {
		bp_activity_set_action(
			$bp->blogs->id,
			'new_blog',
			__( 'New site created', 'buddypress' ),
			'bp_blogs_format_activity_action_new_blog'
		);
	}

	bp_activity_set_action(
		$bp->blogs->id,
		'new_blog_post',
		__( 'New post published', 'buddypress' ),
		'bp_blogs_format_activity_action_new_blog_post'
	);

	bp_activity_set_action(
		$bp->blogs->id,
		'new_blog_comment',
		__( 'New post comment posted', 'buddypress' ),
		'bp_blogs_format_activity_action_new_blog_comment'
	);

	do_action( 'bp_blogs_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'bp_blogs_register_activity_actions' );

/**
 * Format 'new_blog' activity actions.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param string $action Static activity action.
 * @param obj $activity Activity data object.
 */
function bp_blogs_format_activity_action_new_blog( $action, $activity ) {
	$blog_url  = bp_blogs_get_blogmeta( $activity->item_id, 'url' );
	$blog_name = bp_blogs_get_blogmeta( $activity->item_id, 'name' );

	$action = sprintf( __( '%s created the site %s', 'buddypress' ), bp_core_get_userlink( $activity->user_id ), '<a href="' . esc_url( $blog_url ) . '">' . esc_html( $blog_name ) . '</a>' );

	// Legacy filter - requires the BP_Blogs_Blog object
	if ( has_filter( 'bp_blogs_activity_created_blog_action' ) ) {
		$user_blog = BP_Blogs_Blog::get_user_blog( $activity->user_id, $activity->item_id );
		if ( $user_blog ) {
			$recorded_blog = new BP_Blogs_Blog( $user_blog );
		}

		if ( isset( $recorded_blog ) ) {
			$action = apply_filters( 'bp_blogs_activity_created_blog_action', $action, $recorded_blog, $blog_name, bp_blogs_get_blogmeta( $activity->item_id, 'description' ) );
		}
	}

	return apply_filters( 'bp_blogs_format_activity_action_new_blog', $action, $activity );
}

/**
 * Format 'new_blog_post' activity actions.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param string $action Static activity action.
 * @param obj $activity Activity data object.
 */
function bp_blogs_format_activity_action_new_blog_post( $action, $activity ) {
	$blog_url  = bp_blogs_get_blogmeta( $activity->item_id, 'url' );
	$blog_name = bp_blogs_get_blogmeta( $activity->item_id, 'name' );

	if ( empty( $blog_url ) || empty( $blog_name ) ) {
		$blog_url  = get_home_url( $activity->item_id );
		$blog_name = get_blog_option( $activity->item_id, 'blogname' );

		bp_blogs_update_blogmeta( $activity->item_id, 'url', $blog_url );
		bp_blogs_update_blogmeta( $activity->item_id, 'name', $blog_name );
	}

	$post_url = add_query_arg( 'p', $activity->secondary_item_id, trailingslashit( $blog_url ) );

	$post_title = bp_activity_get_meta( $activity->id, 'post_title' );

	// Should only be empty at the time of post creation
	if ( empty( $post_title ) ) {
		switch_to_blog( $activity->item_id );

		$post = get_post( $activity->secondary_item_id );
		if ( is_a( $post, 'WP_Post' ) ) {
			$post_title = $post->post_title;
			bp_activity_update_meta( $activity->id, 'post_title', $post_title );
		}

		restore_current_blog();
	}

	$post_link  = '<a href="' . $post_url . '">' . $post_title . '</a>';

	$user_link = bp_core_get_userlink( $activity->user_id );

	if ( is_multisite() ) {
		$action  = sprintf( __( '%1$s wrote a new post, %2$s, on the site %3$s', 'buddypress' ), $user_link, $post_link, '<a href="' . esc_url( $blog_url ) . '">' . esc_html( $blog_name ) . '</a>' );
	} else {
		$action  = sprintf( __( '%1$s wrote a new post, %2$s', 'buddypress' ), $user_link, $post_link );
	}

	// Legacy filter - requires the post object
	if ( has_filter( 'bp_blogs_activity_new_post_action' ) ) {
		switch_to_blog( $activity->item_id );
		$post = get_post( $activity->secondary_item_id );
		restore_current_blog();

		if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
			$action = apply_filters( 'bp_blogs_activity_new_post_action', $action, $post, $post_url );
		}
	}

	return apply_filters( 'bp_blogs_format_activity_action_new_blog_post', $action, $activity );
}

/**
 * Format 'new_blog_comment' activity actions.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param string $action Static activity action.
 * @param obj $activity Activity data object.
 */
function bp_blogs_format_activity_action_new_blog_comment( $action, $activity ) {
	$blog_url  = bp_blogs_get_blogmeta( $activity->item_id, 'url' );
	$blog_name = bp_blogs_get_blogmeta( $activity->item_id, 'name' );

	if ( empty( $blog_url ) || empty( $blog_name ) ) {
		$blog_url  = get_home_url( $activity->item_id );
		$blog_name = get_blog_option( $activity->item_id, 'blogname' );

		bp_blogs_update_blogmeta( $activity->item_id, 'url', $blog_url );
		bp_blogs_update_blogmeta( $activity->item_id, 'name', $blog_name );
	}

	$post_url   = bp_activity_get_meta( $activity->id, 'post_url' );
	$post_title = bp_activity_get_meta( $activity->id, 'post_title' );

	// Should only be empty at the time of post creation
	if ( empty( $post_url ) || empty( $post_title ) ) {
		switch_to_blog( $activity->item_id );

		$comment = get_comment( $activity->secondary_item_id );

		if ( ! empty( $comment->comment_post_ID ) ) {
			$post_url = add_query_arg( 'p', $comment->comment_post_ID, trailingslashit( $blog_url ) );
			bp_activity_update_meta( $activity->id, 'post_url', $post_url );

			$post = get_post( $comment->comment_post_ID );

			if ( is_a( $post, 'WP_Post' ) ) {
				$post_title = $post->post_title;
				bp_activity_update_meta( $activity->id, 'post_title', $post_title );
			}
		}

		restore_current_blog();
	}

	$post_link = '<a href="' . $post_url . '">' . $post_title . '</a>';
	$user_link = bp_core_get_userlink( $activity->user_id );

	if ( is_multisite() ) {
		$action  = sprintf( __( '%1$s commented on the post, %2$s, on the site %3$s', 'buddypress' ), $user_link, $post_link, '<a href="' . esc_url( $blog_url ) . '">' . esc_html( $blog_name ) . '</a>' );
	} else {
		$action  = sprintf( __( '%1$s commented on the post, %2$s', 'buddypress' ), $user_link, $post_link );
	}

	// Legacy filter - requires the comment object
	if ( has_filter( 'bp_blogs_activity_new_comment_action' ) ) {
		switch_to_blog( $activity->item_id );
		$comment = get_comment( $activity->secondary_item_id );
		restore_current_blog();

		if ( ! empty( $comment ) && ! is_wp_error( $comment ) ) {
			$action = apply_filters( 'bp_blogs_activity_new_comment_action', $action, $comment, $post_url . '#' . $activity->secondary_item_id );
		}
	}

	return apply_filters( 'bp_blogs_format_activity_action_new_blog_comment', $action, $activity );
}

/**
 * Fetch data related to blogs at the beginning of an activity loop.
 *
 * This reduces database overhead during the activity loop.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param array $activities Array of activity items.
 * @return array
 */
function bp_blogs_prefetch_activity_object_data( $activities ) {
	if ( empty( $activities ) ) {
		return $activities;
	}

	$blog_ids = array();

	foreach ( $activities as $activity ) {
		if ( buddypress()->blogs->id !== $activity->component ) {
			continue;
		}

		$blog_ids[] = $activity->item_id;
	}

	if ( ! empty( $blog_ids ) ) {
		bp_blogs_update_meta_cache( $blog_ids );
	}
}
add_filter( 'bp_activity_prefetch_object_data', 'bp_blogs_prefetch_activity_object_data' );

/**
 * Record blog-related activity to the activity stream.
 *
 * @since BuddyPress (1.0.0)
 *
 * @see bp_activity_add() for description of parameters.
 * @global object $bp The BuddyPress global settings object.
 *
 * @param array $args {
 *     See {@link bp_activity_add()} for complete description of arguments.
 *     The arguments listed here have different default values from
 *     bp_activity_add().
 *     @type string $component Default: 'blogs'.
 * }
 * @return int|bool On success, returns the activity ID. False on failure.
 */
function bp_blogs_record_activity( $args = '' ) {
	global $bp;

	// Bail if activity is not active
	if ( ! bp_is_active( 'activity' ) )
		return false;

	$defaults = array(
		'user_id'           => bp_loggedin_user_id(),
		'action'            => '',
		'content'           => '',
		'primary_link'      => '',
		'component'         => $bp->blogs->id,
		'type'              => false,
		'item_id'           => false,
		'secondary_item_id' => false,
		'recorded_time'     => bp_core_current_time(),
		'hide_sitewide'     => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	// Remove large images and replace them with just one image thumbnail
 	if ( !empty( $content ) )
		$content = bp_activity_thumbnail_content_images( $content, $primary_link, $r );

	if ( !empty( $action ) )
		$action = apply_filters( 'bp_blogs_record_activity_action', $action );

	if ( !empty( $content ) )
		$content = apply_filters( 'bp_blogs_record_activity_content', bp_create_excerpt( $content ), $content );

	// Check for an existing entry and update if one exists.
	$id = bp_activity_get_activity_id( array(
		'user_id'           => $user_id,
		'component'         => $component,
		'type'              => $type,
		'item_id'           => $item_id,
		'secondary_item_id' => $secondary_item_id
	) );

	return bp_activity_add( array( 'id' => $id, 'user_id' => $user_id, 'action' => $action, 'content' => $content, 'primary_link' => $primary_link, 'component' => $component, 'type' => $type, 'item_id' => $item_id, 'secondary_item_id' => $secondary_item_id, 'recorded_time' => $recorded_time, 'hide_sitewide' => $hide_sitewide ) );
}

/**
 * Delete a blog-related activity stream item.
 *
 * @since BuddyPress (1.0.0)
 *
 * @see bp_activity_delete() for description of parameters.
 * @global object $bp The BuddyPress global settings object.
 *
 * @param array $args {
 *     See {@link bp_activity_delete()} for complete description of arguments.
 *     The arguments listed here have different default values from
 *     bp_activity_add().
 *     @type string $component Default: 'blogs'.
 * }
 * @return bool True on success, false on failure.
 */
function bp_blogs_delete_activity( $args = true ) {
	global $bp;

	// Bail if activity is not active
	if ( ! bp_is_active( 'activity' ) )
		return false;

	$defaults = array(
		'item_id'           => false,
		'component'         => $bp->blogs->id,
		'type'              => false,
		'user_id'           => false,
		'secondary_item_id' => false
	);

	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );

	bp_activity_delete_by_item_id( array(
		'item_id'           => $item_id,
		'component'         => $component,
		'type'              => $type,
		'user_id'           => $user_id,
		'secondary_item_id' => $secondary_item_id
	) );
}

/**
 * Check if a blog post's activity item should be closed from commenting.
 *
 * This mirrors the {@link comments_open()} and {@link _close_comments_for_old_post()}
 * functions, but for use with the BuddyPress activity stream to be as
 * lightweight as possible.
 *
 * By lightweight, we actually mirror a few of the blog's commenting settings
 * to blogmeta and checks the values in blogmeta instead.  This is to prevent
 * multiple {@link switch_to_blog()} calls in the activity stream.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param object $activity The BP_Activity_Activity object
 * @return bool
 */
function bp_blogs_comments_open( $activity ) {
	$open = true;

	$blog_id = $activity->item_id;

	// see if we've mirrored the close comments option before
	$days_old = bp_blogs_get_blogmeta( $blog_id, 'close_comments_days_old' );

	// we've never cached these items before, so do it now
	if ( '' === $days_old ) {
		switch_to_blog( $blog_id );

		// use comments_open()
		remove_filter( 'comments_open', 'bp_comments_open', 10, 2 );
		$open = comments_open( $activity->secondary_item_id );
		add_filter( 'comments_open', 'bp_comments_open', 10, 2 );

		// might as well mirror values to blogmeta since we're here!
		$thread_depth = get_option( 'thread_comments' );
		if ( ! empty( $thread_depth ) ) {
			$thread_depth = get_option( 'thread_comments_depth' );
		} else {
			// perhaps filter this?
			$thread_depth = 1;
		}

		bp_blogs_update_blogmeta( $blog_id, 'close_comments_for_old_posts', get_option( 'close_comments_for_old_posts' ) );
		bp_blogs_update_blogmeta( $blog_id, 'close_comments_days_old',      get_option( 'close_comments_days_old' ) );
		bp_blogs_update_blogmeta( $blog_id, 'thread_comments_depth',        $thread_depth );

		restore_current_blog();

	// check blogmeta and manually check activity item
	// basically a copy of _close_comments_for_old_post()
	} else {

		// comments are closed
		if ( 'closed' == bp_activity_get_meta( $activity->id, 'post_comment_status' ) ) {
			return false;
		}

		if ( ! bp_blogs_get_blogmeta( $blog_id, 'close_comments_for_old_posts' ) ) {
			return $open;
		}

		$days_old = (int) $days_old;
		if ( ! $days_old ) {
			return $open;
		}

		/* commenting out for now - needs some more thought...
		   should we add the post type to activity meta?

		$post = get_post($post_id);

		// This filter is documented in wp-includes/comment.php
		$post_types = apply_filters( 'close_comments_for_post_types', array( 'post' ) );
		if ( ! in_array( $post->post_type, $post_types ) )
			return $open;
		*/

		if ( time() - strtotime( $activity->date_recorded ) > ( $days_old * DAY_IN_SECONDS ) ) {
			return false;
		}

		return $open;
	}

	return $open;
}