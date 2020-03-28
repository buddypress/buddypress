<?php
/**
 * BuddyPress Blogs Activity.
 *
 * @package BuddyPress
 * @subpackage BlogsActivity
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register activity actions for the blogs component.
 *
 * @since 1.0.0
 *
 * @return bool|null Returns false if activity component is not active.
 */
function bp_blogs_register_activity_actions() {
	if ( is_multisite() ) {
		bp_activity_set_action(
			buddypress()->blogs->id,
			'new_blog',
			__( 'New site created', 'buddypress' ),
			'bp_blogs_format_activity_action_new_blog',
			__( 'New Sites', 'buddypress' ),
			array( 'activity', 'member' ),
			0
		);
	}

	/**
	 * Fires after the registry of the default blog component activity actions.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_blogs_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'bp_blogs_register_activity_actions' );

/**
 * Set up the tracking arguments for the 'post' post type.
 *
 * @since 2.5.0 This was moved out of the BP_Blogs_Component class.
 *
 * @see bp_activity_get_post_type_tracking_args() for information on parameters.
 *
 * @param object|null $params    Tracking arguments.
 * @param string|int  $post_type Post type to track.
 * @return object|null
 */
function bp_blogs_register_post_tracking_args( $params = null, $post_type = 0 ) {

	/**
	 * Filters the post types to track for the Blogs component.
	 *
	 * @since 1.5.0
	 * @deprecated 2.3.0
	 *
	 * Make sure plugins still using 'bp_blogs_record_post_post_types'
	 * to track their post types will generate new_blog_post activities
	 * See https://buddypress.trac.wordpress.org/ticket/6306
	 *
	 * @param array $value Array of post types to track.
	 */
	$post_types = apply_filters( 'bp_blogs_record_post_post_types', array( 'post' ) );
	$post_types_array = array_flip( $post_types );

	if ( ! isset( $post_types_array[ $post_type ] ) ) {
		return $params;
	}

	// Set specific params for the 'post' post type.
	$params->component_id    = buddypress()->blogs->id;
	$params->action_id       = 'new_blog_post';
	$params->admin_filter    = __( 'New post published', 'buddypress' );
	$params->format_callback = 'bp_blogs_format_activity_action_new_blog_post';
	$params->front_filter    = __( 'Posts', 'buddypress' );
	$params->contexts        = array( 'activity', 'member' );
	$params->position        = 5;

	if ( post_type_supports( $post_type, 'comments' ) ) {
		$params->comment_action_id = 'new_blog_comment';

		/**
		 * Filters the post types to track for the Blogs component.
		 *
		 * @since 1.5.0
		 * @deprecated 2.5.0
		 *
		 * Make sure plugins still using 'bp_blogs_record_comment_post_types'
		 * to track comment about their post types will generate new_blog_comment activities
		 * See https://buddypress.trac.wordpress.org/ticket/6306
		 *
		 * @param array $value Array of post types to track.
		 */
		$comment_post_types = apply_filters( 'bp_blogs_record_comment_post_types', array( 'post' ) );
		$comment_post_types_array = array_flip( $comment_post_types );

		if ( isset( $comment_post_types_array[ $post_type ] ) ) {
			$params->comments_tracking = new stdClass();
			$params->comments_tracking->component_id    = buddypress()->blogs->id;
			$params->comments_tracking->action_id       = 'new_blog_comment';
			$params->comments_tracking->admin_filter    = __( 'New post comment posted', 'buddypress' );
			$params->comments_tracking->format_callback = 'bp_blogs_format_activity_action_new_blog_comment';
			$params->comments_tracking->front_filter    = __( 'Comments', 'buddypress' );
			$params->comments_tracking->contexts        = array( 'activity', 'member' );
			$params->comments_tracking->position        = 10;
		}
	}

	return $params;
}
add_filter( 'bp_activity_get_post_type_tracking_args', 'bp_blogs_register_post_tracking_args', 10, 2 );

/**
 * Format 'new_blog' activity actions.
 *
 * @since 2.0.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 * @return string
 */
function bp_blogs_format_activity_action_new_blog( $action, $activity ) {
	$blog_url  = bp_blogs_get_blogmeta( $activity->item_id, 'url' );
	$blog_name = bp_blogs_get_blogmeta( $activity->item_id, 'name' );

	$action = sprintf(
		/* translators: 1: the activity user link. 2: the blog link. */
		esc_html__( '%1$s created the site %2$s', 'buddypress' ),
		bp_core_get_userlink( $activity->user_id ),
		'<a href="' . esc_url( $blog_url ) . '">' . esc_html( $blog_name ) . '</a>'
	);

	// Legacy filter - requires the BP_Blogs_Blog object.
	if ( has_filter( 'bp_blogs_activity_created_blog_action' ) ) {
		$user_blog = BP_Blogs_Blog::get_user_blog( $activity->user_id, $activity->item_id );
		if ( $user_blog ) {
			$recorded_blog = new BP_Blogs_Blog( $user_blog );
		}

		if ( isset( $recorded_blog ) ) {
			$action = apply_filters( 'bp_blogs_activity_created_blog_action', $action, $recorded_blog, $blog_name, bp_blogs_get_blogmeta( $activity->item_id, 'description' ) );
		}
	}

	/**
	 * Filters the new blog activity action for the new blog.
	 *
	 * @since 2.0.0
	 *
	 * @param string $action   Constructed activity action.
	 * @param object $activity Activity data object.
	 */
	return apply_filters( 'bp_blogs_format_activity_action_new_blog', $action, $activity );
}

/**
 * Format 'new_blog_post' activity actions.
 *
 * @since 2.0.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 * @return string Constructed activity action.
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

	/**
	 * When the post is published we are faking an activity object
	 * to which we add 2 properties :
	 * - the post url
	 * - the post title
	 * This is done to build the 'post link' part of the activity
	 * action string.
	 * NB: in this case the activity has not yet been created.
	 */
	if ( isset( $activity->post_url ) ) {
		$post_url = $activity->post_url;

	/**
	 * The post_url property is not set, we need to build the url
	 * thanks to the post id which is also saved as the secondary
	 * item id property of the activity object.
	 */
	} else {
		$post_url = add_query_arg( 'p', $activity->secondary_item_id, trailingslashit( $blog_url ) );
	}

	// Should be the case when the post has just been published.
	if ( isset( $activity->post_title ) ) {
		$post_title = $activity->post_title;

	// If activity already exists try to get the post title from activity meta.
	} else if ( ! empty( $activity->id ) ) {
		$post_title = bp_activity_get_meta( $activity->id, 'post_title' );
	}

	/**
	 * In case the post was published without a title
	 * or the activity meta was not found.
	 */
	if ( empty( $post_title ) ) {
		// Defaults to no title.
		$post_title = __( '(no title)', 'buddypress' );

		switch_to_blog( $activity->item_id );

		$post = get_post( $activity->secondary_item_id );
		if ( is_a( $post, 'WP_Post' ) ) {
			// Does the post have a title ?
			if ( ! empty( $post->post_title ) ) {
				$post_title = $post->post_title;
			}

			// Make sure the activity exists before saving the post title in activity meta.
			if ( ! empty( $activity->id ) ) {
				bp_activity_update_meta( $activity->id, 'post_title', $post_title );
			}
		}

		restore_current_blog();
	}

	// Build the 'post link' part of the activity action string.
	$post_link = '<a href="' . esc_url( $post_url ) . '">' . esc_html( $post_title ) . '</a>';

	$user_link = bp_core_get_userlink( $activity->user_id );

	// Build the complete activity action string.
	if ( is_multisite() ) {
		$action = sprintf(
			/* translators: 1: the activity user link. 2: the post link. 3: the blog link. */
			esc_html_x( '%1$s wrote a new post, %2$s, on the site %3$s', '`new_blog_post` activity action', 'buddypress' ),
			$user_link,
			$post_link,
			'<a href="' . esc_url( $blog_url ) . '">' . esc_html( $blog_name ) . '</a>'
		);
	} else {
		$action = sprintf(
			/* translators: 1: the activity user link. 2: the post link. */
			esc_html_x( '%1$s wrote a new post, %2$s', '`new_blog_post` activity action', 'buddypress' ),
			$user_link,
			$post_link
		);
	}

	// Legacy filter - requires the post object.
	if ( has_filter( 'bp_blogs_activity_new_post_action' ) ) {
		switch_to_blog( $activity->item_id );
		$post = get_post( $activity->secondary_item_id );
		restore_current_blog();

		if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
			$action = apply_filters( 'bp_blogs_activity_new_post_action', $action, $post, $post_url );
		}
	}

	/**
	 * Filters the new blog post action for the new blog.
	 *
	 * @since 2.0.0
	 *
	 * @param string $action   Constructed activity action.
	 * @param object $activity Activity data object.
	 */
	return apply_filters( 'bp_blogs_format_activity_action_new_blog_post', $action, $activity );
}

/**
 * Format 'new_blog_comment' activity actions.
 *
 * @since 2.0.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 * @return string Constructed activity action.
 */
function bp_blogs_format_activity_action_new_blog_comment( $action, $activity ) {
	/**
	 * When the comment is published we are faking an activity object
	 * to which we add 4 properties :
	 * - the post url
	 * - the post title
	 * - the blog url
	 * - the blog name
	 * This is done to build the 'post link' part of the activity
	 * action string.
	 * NB: in this case the activity has not yet been created.
	 */

	$blog_url = false;

	// Try to get the blog url from the activity object.
	if ( isset( $activity->blog_url ) ) {
		$blog_url = $activity->blog_url;
	} else {
		$blog_url = bp_blogs_get_blogmeta( $activity->item_id, 'url' );
	}

	$blog_name = false;

	// Try to get the blog name from the activity object.
	if ( isset( $activity->blog_name ) ) {
		$blog_name = $activity->blog_name;
	} else {
		$blog_name = bp_blogs_get_blogmeta( $activity->item_id, 'name' );
	}

	if ( empty( $blog_url ) || empty( $blog_name ) ) {
		$blog_url  = get_home_url( $activity->item_id );
		$blog_name = get_blog_option( $activity->item_id, 'blogname' );

		bp_blogs_update_blogmeta( $activity->item_id, 'url', $blog_url );
		bp_blogs_update_blogmeta( $activity->item_id, 'name', $blog_name );
	}

	$post_url = false;

	// Try to get the post url from the activity object.
	if ( isset( $activity->post_url ) ) {
		$post_url = $activity->post_url;

	/**
	 * The post_url property is not set, we need to build the url
	 * thanks to the post id which is also saved as the secondary
	 * item id property of the activity object.
	 */
	} elseif ( ! empty( $activity->id ) ) {
		$post_url = bp_activity_get_meta( $activity->id, 'post_url' );
	}

	$post_title = false;

	// Should be the case when the comment has just been published.
	if ( isset( $activity->post_title ) ) {
		$post_title = $activity->post_title;

	// If activity already exists try to get the post title from activity meta.
	} elseif ( ! empty( $activity->id ) ) {
		$post_title = bp_activity_get_meta( $activity->id, 'post_title' );
	}

	// Should only be empty at the time of post creation.
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

	$post_link = '<a href="' . esc_url( $post_url ) . '">' . esc_html( $post_title ) . '</a>';
	$user_link = bp_core_get_userlink( $activity->user_id );

	if ( is_multisite() ) {
		$action = sprintf(
			/* translators: 1: the activity user link. 2: the post link. 3: the blog link. */
			esc_html_x( '%1$s commented on the post, %2$s, on the site %3$s', '`new_blog_comment` activity action', 'buddypress' ),
			$user_link,
			$post_link,
			'<a href="' . esc_url( $blog_url ) . '">' . esc_html( $blog_name ) . '</a>'
		);
	} else {
		$action = sprintf(
			/* translators: 1: the activity user link. 2: the post link. */
			esc_html_x( '%1$s commented on the post, %2$s', '`new_blog_comment` activity action', 'buddypress' ),
			$user_link,
			$post_link
		);
	}

	// Legacy filter - requires the comment object.
	if ( has_filter( 'bp_blogs_activity_new_comment_action' ) ) {
		switch_to_blog( $activity->item_id );
		$comment = get_comment( $activity->secondary_item_id );
		restore_current_blog();

		if ( ! empty( $comment ) && ! is_wp_error( $comment ) ) {
			$action = apply_filters( 'bp_blogs_activity_new_comment_action', $action, $comment, $post_url . '#' . $activity->secondary_item_id );
		}
	}

	/**
	 * Filters the new blog comment action for the new blog.
	 *
	 * @since 2.0.0
	 *
	 * @param string $action   Constructed activity action.
	 * @param object $activity Activity data object.
	 */
	return apply_filters( 'bp_blogs_format_activity_action_new_blog_comment', $action, $activity );
}

/**
 * Fetch data related to blogs at the beginning of an activity loop.
 *
 * This reduces database overhead during the activity loop.
 *
 * @since 2.0.0
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

	return $activities;
}
add_filter( 'bp_activity_prefetch_object_data', 'bp_blogs_prefetch_activity_object_data' );

/**
 * Record blog-related activity to the activity stream.
 *
 * @since 1.0.0
 *
 * @see bp_activity_add() for description of parameters.
 *
 * @param array|string $args {
 *     See {@link bp_activity_add()} for complete description of arguments.
 *     The arguments listed here have different default values from
 *     bp_activity_add().
 *     @type string $component Default: 'blogs'.
 * }
 * @return WP_Error|bool|int On success, returns the activity ID. False on failure.
 */
function bp_blogs_record_activity( $args = '' ) {
	$defaults = array(
		'user_id'           => bp_loggedin_user_id(),
		'action'            => '',
		'content'           => '',
		'primary_link'      => '',
		'component'         => buddypress()->blogs->id,
		'type'              => false,
		'item_id'           => false,
		'secondary_item_id' => false,
		'recorded_time'     => bp_core_current_time(),
		'hide_sitewide'     => false
	);

	$r = wp_parse_args( $args, $defaults );

	if ( ! empty( $r['action'] ) ) {

		/**
		 * Filters the action associated with activity for activity stream.
		 *
		 * @since 1.2.0
		 *
		 * @param string $value Action for the activity stream.
		 */
		$r['action'] = apply_filters( 'bp_blogs_record_activity_action', $r['action'] );
	}

	if ( ! empty( $r['content'] ) ) {

		/**
		 * Filters the content associated with activity for activity stream.
		 *
		 * @since 1.2.0
		 *
		 * @param string $value Generated summary from content for the activity stream.
		 * @param string $value Content for the activity stream.
		 * @param array  $r     Array of arguments used for the activity stream item.
		 */
		$r['content'] = apply_filters( 'bp_blogs_record_activity_content', bp_activity_create_summary( $r['content'], $r ), $r['content'], $r );
	}

	// Check for an existing entry and update if one exists.
	$id = bp_activity_get_activity_id( array(
		'user_id'           => $r['user_id'],
		'component'         => $r['component'],
		'type'              => $r['type'],
		'item_id'           => $r['item_id'],
		'secondary_item_id' => $r['secondary_item_id'],
	) );

	return bp_activity_add( array( 'id' => $id, 'user_id' => $r['user_id'], 'action' => $r['action'], 'content' => $r['content'], 'primary_link' => $r['primary_link'], 'component' => $r['component'], 'type' => $r['type'], 'item_id' => $r['item_id'], 'secondary_item_id' => $r['secondary_item_id'], 'recorded_time' => $r['recorded_time'], 'hide_sitewide' => $r['hide_sitewide'] ) );
}

/**
 * Delete a blog-related activity stream item.
 *
 * @since 1.0.0
 *
 * @see bp_activity_delete() for description of parameters.
 *
 * @param array|string $args {
 *     See {@link bp_activity_delete()} for complete description of arguments.
 *     The arguments listed here have different default values from
 *     bp_activity_add().
 *     @type string $component Default: 'blogs'.
 * }
 * @return bool True on success, false on failure.
 */
function bp_blogs_delete_activity( $args = '' ) {
	$r = bp_parse_args( $args, array(
		'item_id'           => false,
		'component'         => buddypress()->blogs->id,
		'type'              => false,
		'user_id'           => false,
		'secondary_item_id' => false
	) );

	bp_activity_delete_by_item_id( $r );
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
 * @since 2.0.0
 *
 * @param object $activity The BP_Activity_Activity object.
 * @return bool
 */
function bp_blogs_comments_open( $activity ) {
	$open = true;

	$blog_id = $activity->item_id;

	// See if we've mirrored the close comments option before.
	$days_old = bp_blogs_get_blogmeta( $blog_id, 'close_comments_days_old' );

	// We've never cached these items before, so do it now.
	if ( '' === $days_old ) {
		switch_to_blog( $blog_id );

		// Use comments_open().
		remove_filter( 'comments_open', 'bp_comments_open', 10 );
		$open = comments_open( $activity->secondary_item_id );
		add_filter( 'comments_open', 'bp_comments_open', 10, 2 );

		// Might as well mirror values to blogmeta since we're here!
		$thread_depth = get_option( 'thread_comments' );
		if ( ! empty( $thread_depth ) ) {
			$thread_depth = get_option( 'thread_comments_depth' );
		} else {
			// Perhaps filter this?
			$thread_depth = 1;
		}

		bp_blogs_update_blogmeta( $blog_id, 'close_comments_for_old_posts', get_option( 'close_comments_for_old_posts' ) );
		bp_blogs_update_blogmeta( $blog_id, 'close_comments_days_old',      get_option( 'close_comments_days_old' ) );
		bp_blogs_update_blogmeta( $blog_id, 'thread_comments_depth',        $thread_depth );

		restore_current_blog();

	// Check blogmeta and manually check activity item.
	// Basically a copy of _close_comments_for_old_post().
	} else {

		// Comments are closed.
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

		/*
		   Commenting out for now - needs some more thought...
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

/** SITE TRACKING *******************************************************/

/**
 * Add an activity entry for a newly-created site.
 *
 * Hooked to the 'bp_blogs_new_blog' action.
 *
 * @since 2.6.0
 *
 * @param BP_Blogs_Blog $recorded_blog Current site being recorded. Passed by reference.
 * @param bool          $is_private    Whether the current site being recorded is private.
 * @param bool          $is_recorded   Whether the current site was recorded.
 */
function bp_blogs_record_activity_on_site_creation( $recorded_blog, $is_private, $is_recorded, $no_activity ) {
	// Only record this activity if the blog is public.
	if ( ! $is_private && ! $no_activity && bp_blogs_is_blog_trackable( $recorded_blog->blog_id, $recorded_blog->user_id ) ) {
		bp_blogs_record_activity( array(
			'user_id'      => $recorded_blog->user_id,

			/**
			 * Filters the activity created blog primary link.
			 *
			 * @since 1.1.0
			 *
			 * @param string $value Blog primary link.
			 * @param int    $value Blog ID.
			 */
			'primary_link' => apply_filters( 'bp_blogs_activity_created_blog_primary_link', bp_blogs_get_blogmeta( $recorded_blog->blog_id, 'url' ), $recorded_blog->blog_id ),
			'type'         => 'new_blog',
			'item_id'      => $recorded_blog->blog_id
		) );
	}
}
add_action( 'bp_blogs_new_blog', 'bp_blogs_record_activity_on_site_creation', 10, 4 );

/**
 * Deletes the 'new_blog' activity entry when a site is deleted.
 *
 * @since 2.6.0
 *
 * @param int $blog_id Site ID.
 */
function bp_blogs_delete_new_blog_activity_for_site( $blog_id, $user_id = 0 ) {
	$args = array(
		'item_id'   => $blog_id,
		'component' => buddypress()->blogs->id,
		'type'      => 'new_blog'
	);

	/**
	 * In the case a user is removed, make sure he is the author of the 'new_blog' activity
	 * when trying to delete it.
	 */
	if ( ! empty( $user_id ) ) {
		$args['user_id'] = $user_id;
	}

	bp_blogs_delete_activity( $args );
}
add_action( 'bp_blogs_remove_blog',          'bp_blogs_delete_new_blog_activity_for_site', 10, 1 );
add_action( 'bp_blogs_remove_blog_for_user', 'bp_blogs_delete_new_blog_activity_for_site', 10, 2 );

/**
 * Delete all 'blogs' activity items for a site when the site is deleted.
 *
 * @since 2.6.0
 *
 * @param int $blog_id Site ID.
 */
function bp_blogs_delete_activity_for_site( $blog_id ) {
	bp_blogs_delete_activity( array(
		'item_id'   => $blog_id,
		'component' => buddypress()->blogs->id,
		'type'      => false
	) );
}
add_action( 'bp_blogs_remove_data_for_blog', 'bp_blogs_delete_activity_for_site' );

/**
 * Remove a blog post activity item from the activity stream.
 *
 * @since 1.0.0
 *
 * @param int $post_id ID of the post to be removed.
 * @param int $blog_id Optional. Defaults to current blog ID.
 * @param int $user_id Optional. Defaults to the logged-in user ID. This param
 *                     is currently unused in the function (but is passed to hooks).
 * @return bool
 */
function bp_blogs_remove_post( $post_id, $blog_id = 0, $user_id = 0 ) {
	global $wpdb;

	if ( empty( $wpdb->blogid ) ) {
		return false;
	}

	$post_id = (int) $post_id;

	if ( ! $blog_id ) {
		$blog_id = (int) $wpdb->blogid;
	}

	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	/**
	 * Fires before removal of a blog post activity item from the activity stream.
	 *
	 * @since 1.5.0
	 *
	 * @param int $blog_id ID of the blog associated with the post that was removed.
	 * @param int $post_id ID of the post that was removed.
	 * @param int $user_id ID of the user having the blog removed for.
	 */
	do_action( 'bp_blogs_before_remove_post', $blog_id, $post_id, $user_id );

	bp_blogs_delete_activity( array(
		'item_id'           => $blog_id,
		'secondary_item_id' => $post_id,
		'component'         => buddypress()->blogs->id,
		'type'              => 'new_blog_post'
	) );

	/**
	 * Fires after removal of a blog post activity item from the activity stream.
	 *
	 * @since 1.0.0
	 *
	 * @param int $blog_id ID of the blog associated with the post that was removed.
	 * @param int $post_id ID of the post that was removed.
	 * @param int $user_id ID of the user having the blog removed for.
	 */
	do_action( 'bp_blogs_remove_post', $blog_id, $post_id, $user_id );
}
add_action( 'delete_post', 'bp_blogs_remove_post' );

/** POST COMMENT SYNCHRONIZATION ****************************************/

/**
 * Syncs activity comments and posts them back as blog comments.
 *
 * Note: This is only a one-way sync - activity comments -> blog comment.
 *
 * For blog post -> activity comment, see {@link bp_activity_post_type_comment()}.
 *
 * @since 2.0.0
 * @since 2.5.0 Allow custom post types to sync their comments with activity ones
 *
 * @param int    $comment_id      The activity ID for the posted activity comment.
 * @param array  $params          Parameters for the activity comment.
 * @param object $parent_activity Parameters of the parent activity item (in this case, the blog post).
 */
function bp_blogs_sync_add_from_activity_comment( $comment_id, $params, $parent_activity ) {
	// if parent activity isn't a post type having the buddypress-activity support, stop now!
	if ( ! bp_activity_type_supports( $parent_activity->type, 'post-type-comment-tracking' ) ) {
		return;
	}

	// If activity comments are disabled for blog posts, stop now!
	if ( bp_disable_blogforum_comments() ) {
		return;
	}

	// Do not sync if the activity comment was marked as spam.
	$activity = new BP_Activity_Activity( $comment_id );
	if ( $activity->is_spam ) {
		return;
	}

	// Check if comments are still open for parent item.
	$comments_open = bp_blogs_comments_open( $parent_activity );
	if ( ! $comments_open ) {
		return;
	}

	// Get userdata.
	if ( $params['user_id'] == bp_loggedin_user_id() ) {
		$user = buddypress()->loggedin_user->userdata;
	} else {
		$user = bp_core_get_core_userdata( $params['user_id'] );
	}

	// Get associated post type and set default comment parent.
	$post_type      = bp_activity_post_type_get_tracking_arg( $parent_activity->type, 'post_type' );
	$comment_parent = 0;

	// See if a parent WP comment ID exists.
	if ( ! empty( $params['parent_id'] ) && ! empty( $post_type ) ) {
		$comment_parent = bp_activity_get_meta( $params['parent_id'], "bp_blogs_{$post_type}_comment_id" );
	}

	// Comment args.
	$args = array(
		'comment_post_ID'      => $parent_activity->secondary_item_id,
		'comment_author'       => bp_core_get_user_displayname( $params['user_id'] ),
		'comment_author_email' => $user->user_email,
		'comment_author_url'   => bp_core_get_user_domain( $params['user_id'], $user->user_nicename, $user->user_login ),
		'comment_content'      => $params['content'],
		'comment_type'         => '', // Could be interesting to add 'BuddyPress' here...
		'comment_parent'       => (int) $comment_parent,
		'user_id'              => $params['user_id'],
		'comment_approved'     => 1
	);

	// Prevent separate activity entry being made.
	remove_action( 'comment_post', 'bp_activity_post_type_comment', 10 );

	// Handle multisite.
	switch_to_blog( $parent_activity->item_id );

	// Handle timestamps for the WP comment after we've switched to the blog.
	$args['comment_date']     = current_time( 'mysql' );
	$args['comment_date_gmt'] = current_time( 'mysql', 1 );

	// Post the comment.
	$post_comment_id = wp_insert_comment( $args );

	// Add meta to comment.
	add_comment_meta( $post_comment_id, 'bp_activity_comment_id', $comment_id );

	// Add meta to activity comment.
	if ( ! empty( $post_type ) ) {
		bp_activity_update_meta( $comment_id, "bp_blogs_{$post_type}_comment_id", $post_comment_id );
	}

	// Resave activity comment with WP comment permalink.
	//
	// in bp_blogs_activity_comment_permalink(), we change activity comment
	// permalinks to use the post comment link
	//
	// @todo since this is done after AJAX posting, the activity comment permalink
	// doesn't change on the front end until the next page refresh.
	$resave_activity = new BP_Activity_Activity( $comment_id );
	$resave_activity->primary_link = get_comment_link( $post_comment_id );

	/**
	 * Now that the activity id exists and the post comment was created, we don't need to update
	 * the content of the comment as there are no chances it has evolved.
	 */
	remove_action( 'bp_activity_before_save', 'bp_blogs_sync_activity_edit_to_post_comment', 20 );

	$resave_activity->save();

	// Add the edit activity comment hook back.
	add_action( 'bp_activity_before_save', 'bp_blogs_sync_activity_edit_to_post_comment', 20 );

	// Multisite again!
	restore_current_blog();

	// Add the comment hook back.
	add_action( 'comment_post', 'bp_activity_post_type_comment', 10, 2 );

	/**
	 * Fires after activity comments have been synced and posted as blog comments.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $comment_id      The activity ID for the posted activity comment.
	 * @param array  $args            Array of args used for the comment syncing.
	 * @param object $parent_activity Parameters of the blog post parent activity item.
	 * @param object $user            User data object for the blog comment.
	 */
	do_action( 'bp_blogs_sync_add_from_activity_comment', $comment_id, $args, $parent_activity, $user );
}
add_action( 'bp_activity_comment_posted', 'bp_blogs_sync_add_from_activity_comment', 10, 3 );

/**
 * Deletes the blog comment when the associated activity comment is deleted.
 *
 * Note: This is hooked on the 'bp_activity_delete_comment_pre' filter instead
 * of the 'bp_activity_delete_comment' action because we need to fetch the
 * activity comment children before they are deleted.
 *
 * @since 2.0.0
 * @since 2.5.0 Add the $delected parameter
 *
 * @param bool $retval             Whether BuddyPress should continue or not.
 * @param int  $parent_activity_id The parent activity ID for the activity comment.
 * @param int  $activity_id        The activity ID for the pending deleted activity comment.
 * @param bool $deleted            Whether the comment was deleted or not.
 * @return bool
 */
function bp_blogs_sync_delete_from_activity_comment( $retval, $parent_activity_id, $activity_id, &$deleted ) {
	// Check if parent activity is a blog post.
	$parent_activity = new BP_Activity_Activity( $parent_activity_id );

	// if parent activity isn't a post type having the buddypress-activity support, stop now!
	if ( ! bp_activity_type_supports( $parent_activity->type, 'post-type-comment-tracking' ) ) {
		return $retval;
	}

	// Fetch the activity comments for the activity item.
	$activity = bp_activity_get( array(
		'in'               => $activity_id,
		'display_comments' => 'stream',
		'spam'             => 'all',
	) );

	// Get all activity comment IDs for the pending deleted item.
	$activity_ids   = bp_activity_recurse_comments_activity_ids( $activity );
	$activity_ids[] = $activity_id;

	// Handle multisite.
	// switch to the blog where the comment was made.
	switch_to_blog( $parent_activity->item_id );

	// Remove associated blog comments.
	bp_blogs_remove_associated_blog_comments( $activity_ids, current_user_can( 'moderate_comments' ) );

	// Multisite again!
	restore_current_blog();

	// Rebuild activity comment tree
	// emulate bp_activity_delete_comment().
	BP_Activity_Activity::rebuild_activity_comment_tree( $parent_activity_id );

	// Avoid the error message although the comments were successfully deleted.
	$deleted = true;

	// We're overriding the default bp_activity_delete_comment() functionality
	// so we need to return false.
	return false;
}
add_filter( 'bp_activity_delete_comment_pre', 'bp_blogs_sync_delete_from_activity_comment', 10, 4 );

/**
 * Updates the blog comment when the associated activity comment is edited.
 *
 * @since 2.0.0
 *
 * @param BP_Activity_Activity $activity The activity object.
 */
function bp_blogs_sync_activity_edit_to_post_comment( BP_Activity_Activity $activity ) {
	// This is a new entry, so stop!
	// We only want edits!
	if ( empty( $activity->id ) || bp_disable_blogforum_comments() ) {
		return;
	}

	// Fetch parent activity item.
	$parent_activity = new BP_Activity_Activity( $activity->item_id );

	// If parent activity isn't a post type having the buddypress-activity support for comments, stop now!
	if ( ! bp_activity_type_supports( $parent_activity->type, 'post-type-comment-tracking' ) ) {
		return;
	}

	$post_type = bp_activity_post_type_get_tracking_arg( $parent_activity->type, 'post_type' );

	// No associated post type for this activity comment, stop.
	if ( ! $post_type ) {
		return;
	}

	// Try to see if a corresponding blog comment exists.
	$post_comment_id = bp_activity_get_meta( $activity->id, "bp_blogs_{$post_type}_comment_id" );

	if ( empty( $post_comment_id ) ) {
		return;
	}

	// Handle multisite.
	switch_to_blog( $parent_activity->item_id );

	// Get the comment status.
	$post_comment_status = wp_get_comment_status( $post_comment_id );
	$old_comment_status  = $post_comment_status;

	// No need to edit the activity, as it's the activity who's updating the comment.
	remove_action( 'transition_comment_status', 'bp_activity_transition_post_type_comment_status', 10 );
	remove_action( 'bp_activity_post_type_comment', 'bp_blogs_comment_sync_activity_comment', 10 );

	if ( 1 === $activity->is_spam && 'spam' !== $post_comment_status ) {
		wp_spam_comment( $post_comment_id );
	} elseif ( ! $activity->is_spam ) {
		if ( 'spam' === $post_comment_status  ) {
			wp_unspam_comment( $post_comment_id );
		} elseif ( 'trash' === $post_comment_status ) {
			wp_untrash_comment( $post_comment_id );
		} else {
			// Update the blog post comment.
			wp_update_comment( array(
				'comment_ID'       => $post_comment_id,
				'comment_content'  => $activity->content,
			) );
		}
	}

	// Restore actions.
	add_action( 'transition_comment_status',     'bp_activity_transition_post_type_comment_status', 10, 3 );
	add_action( 'bp_activity_post_type_comment', 'bp_blogs_comment_sync_activity_comment',          10, 4 );

	restore_current_blog();
}
add_action( 'bp_activity_before_save', 'bp_blogs_sync_activity_edit_to_post_comment', 20 );

/**
 * When a post is trashed, remove each comment's associated activity meta.
 *
 * When a post is trashed and later untrashed, we currently don't reinstate
 * activity items for these comments since their activity entries are already
 * deleted when initially trashed.
 *
 * Since these activity entries are deleted, we need to remove the deleted
 * activity comment IDs from each comment's meta when a post is trashed.
 *
 * @since 2.0.0
 *
 * @param int   $post_id  The post ID.
 * @param array $comments Array of comment statuses. The key is comment ID, the
 *                        value is the $comment->comment_approved value.
 */
function bp_blogs_remove_activity_meta_for_trashed_comments( $post_id = 0, $comments = array() ) {
	if ( ! empty( $comments ) ) {
		foreach ( array_keys( $comments ) as $comment_id ) {
			delete_comment_meta( $comment_id, 'bp_activity_comment_id' );
		}
	}
}
add_action( 'trashed_post_comments', 'bp_blogs_remove_activity_meta_for_trashed_comments', 10, 2 );

/**
 * Filter 'new_blog_comment' bp_has_activities() loop to include new- and old-style blog activity comment items.
 *
 * In BuddyPress 2.0, the schema for storing activity items related to blog
 * posts changed. Instead creating new top-level 'new_blog_comment' activity
 * items, blog comments are recorded in the activity stream as comments on the
 * 'new_blog_post' activity items corresponding to the parent post. This filter
 * ensures that the 'new_blog_comment' filter in bp_has_activities() (which
 * powers the 'Comments' filter in the activity directory dropdown) includes
 * both old-style and new-style activity comments.
 *
 * @since 2.1.0
 * @since 2.5.0 Used for any synced Post type comments, in wp-admin or front-end contexts.
 *
 * @param array $args Arguments passed from bp_parse_args() in bp_has_activities().
 * @return array $args
 */
function bp_blogs_new_blog_comment_query_backpat( $args ) {
	global $wpdb;
	$bp = buddypress();

	// If activity comments are disabled for blog posts, stop now!
	if ( bp_disable_blogforum_comments() ) {
		return $args;
	}

	// Get the associated post type.
	$post_type = bp_activity_post_type_get_tracking_arg( $args['action'], 'post_type' );

	// Bail if this is not an activity associated with a post type.
	if ( empty( $post_type ) ) {
		return $args;
	}

	// Bail if this is an activity about posts and not comments.
	if ( bp_activity_post_type_get_tracking_arg( $args['action'], 'comment_action_id' ) ) {
		return $args;
	}

	// Comment synced ?
	$activity_ids = $wpdb->get_col( $wpdb->prepare( "SELECT activity_id FROM {$bp->activity->table_name_meta} WHERE meta_key = %s", "bp_blogs_{$post_type}_comment_id" ) );

	if ( empty( $activity_ids ) ) {
		return $args;
	}

	// Init the filter query.
	$filter_query = array();

	if ( ! isset( $args['scope'] ) || 'null' === $args['scope'] ) {
		$args['scope'] = '';
	} elseif ( 'just-me' === $args['scope'] ) {
		$filter_query = array(
			'relation' => 'AND',
			array(
				'column' => 'user_id',
				'value'  => bp_displayed_user_id(),
			),
		);
		$args['scope'] = '';
	}

	$filter_query[] = array(
		'relation' => 'OR',
		array(
			'column' => 'type',
			'value'  => $args['action'],
		),
		array(
			'column'  => 'id',
			'value'   =>  $activity_ids,
			'compare' => 'IN'
		),
	);

	$args['filter_query'] = $filter_query;

	// Make sure to have comment in stream mode && avoid duplicate content.
	$args['display_comments'] = 'stream';

	// Finally reset the action.
	$args['action'] = '';
	$args['type']   = '';

	// Return the original arguments.
	return $args;
}
add_filter( 'bp_after_has_activities_parse_args',                'bp_blogs_new_blog_comment_query_backpat' );
add_filter( 'bp_activity_list_table_filter_activity_type_items', 'bp_blogs_new_blog_comment_query_backpat' );

/**
 * Utility function to set up some variables for use in the activity loop.
 *
 * Grabs the blog's comment depth and the post's open comment status options
 * for later use in the activity and activity comment loops.
 *
 * This is to prevent having to requery these items later on.
 *
 * @since 2.0.0
 *
 * @see bp_blogs_disable_activity_commenting()
 * @see bp_blogs_setup_comment_loop_globals_on_ajax()
 *
 * @param object $activity The BP_Activity_Activity object.
 */
function bp_blogs_setup_activity_loop_globals( $activity ) {
	if ( ! is_object( $activity ) ) {
		return;
	}

	// The activity type does not support comments or replies ? stop now!
	if ( ! bp_activity_type_supports( $activity->type, 'post-type-comment-reply' ) ) {
		return;
	}

	if ( empty( $activity->id ) ) {
		return;
	}

	// If we've already done this before, stop now!
	if ( isset( buddypress()->blogs->allow_comments[ $activity->id ] ) ) {
		return;
	}

	$allow_comments = bp_blogs_comments_open( $activity );
	$thread_depth   = bp_blogs_get_blogmeta( $activity->item_id, 'thread_comments_depth' );
	$moderation     = bp_blogs_get_blogmeta( $activity->item_id, 'comment_moderation' );

	// Initialize a local object so we won't have to query this again in the
	// comment loop.
	if ( ! isset( buddypress()->blogs->allow_comments ) ) {
		buddypress()->blogs->allow_comments = array();
	}
	if ( ! isset( buddypress()->blogs->thread_depth ) ) {
		buddypress()->blogs->thread_depth   = array();
	}
	if ( ! isset( buddypress()->blogs->comment_moderation ) ) {
		buddypress()->blogs->comment_moderation = array();
	}

	/*
	 * Cache comment settings in the buddypress() singleton for later reference.
	 *
	 * See bp_blogs_disable_activity_commenting() / bp_blogs_can_comment_reply().
	 *
	 * thread_depth is keyed by activity ID instead of blog ID because when we're
	 * in an actvity comment loop, we don't have access to the blog ID...
	 *
	 * Should probably object cache these values instead...
	 */
	buddypress()->blogs->allow_comments[ $activity->id ]     = $allow_comments;
	buddypress()->blogs->thread_depth[ $activity->id ]       = $thread_depth;
	buddypress()->blogs->comment_moderation[ $activity->id ] = $moderation;
}

/**
 * Set up some globals used in the activity comment loop when AJAX is used.
 *
 * @since 2.0.0
 *
 * @see bp_blogs_setup_activity_loop_globals()
 */
function bp_blogs_setup_comment_loop_globals_on_ajax() {
	// Not AJAX? stop now!
	if ( ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	if ( false === (bool) constant( 'DOING_AJAX' ) ) {
		return;
	}

	// Get the parent activity item.
	$comment         = bp_activity_current_comment();
	$parent_activity = new BP_Activity_Activity( $comment->item_id );

	// Setup the globals.
	bp_blogs_setup_activity_loop_globals( $parent_activity );
}
add_action( 'bp_before_activity_comment', 'bp_blogs_setup_comment_loop_globals_on_ajax' );

/**
 * Disable activity commenting for blog posts based on certain criteria.
 *
 * If activity commenting is enabled for blog posts, we still need to disable
 * commenting if:
 *  - comments are disabled for the WP blog post from the admin dashboard
 *  - the WP blog post is supposed to be automatically closed from comments
 *    based on a certain age
 *  - the activity entry is a 'new_blog_comment' type
 *
 * @since 2.0.0
 *
 * @param bool $retval Is activity commenting enabled for this activity entry.
 * @return bool
 */
function bp_blogs_disable_activity_commenting( $retval ) {
	global $activities_template;

	// If activity commenting is disabled, return current value.
	if ( bp_disable_blogforum_comments() || ! isset( $activities_template->in_the_loop ) ) {
		return $retval;
	}

	$type = bp_get_activity_type();

	// It's a post type supporting comment tracking.
	if ( bp_activity_type_supports( $type, 'post-type-comment-tracking' ) ) {
		// The activity type is supporting comments or replies.
		if ( bp_activity_type_supports( $type, 'post-type-comment-reply' ) ) {
			// Setup some globals we'll need to reference later.
			bp_blogs_setup_activity_loop_globals( $activities_template->activity );

			// If comments are closed for the WP blog post, we should disable
			// activity comments for this activity entry.
			if ( ! isset( buddypress()->blogs->allow_comments[ bp_get_activity_id() ] ) || ! buddypress()->blogs->allow_comments[ bp_get_activity_id() ] ) {
				$retval = false;
			}

			// If comments need moderation, disable activity commenting.
			if ( isset( buddypress()->blogs->comment_moderation[ bp_get_activity_id() ] ) && buddypress()->blogs->comment_moderation[ bp_get_activity_id() ] ) {
				$retval = false;
			}
		// The activity type does not support comments or replies.
		} else {
			$retval = false;
		}
	}

	return $retval;
}
add_filter( 'bp_activity_can_comment', 'bp_blogs_disable_activity_commenting' );

/**
 * Limit the display of post type synced comments.
 *
 * @since  2.5.0
 *
 * When viewing the synced comments in stream mode, this prevents comments to
 * be displayed twice, and avoids a Javascript error as the form to add replies
 * is not available.
 *
 * @param  int $retval  The comment count for the activity.
 * @return int          The comment count, or 0 to hide activity comment replies.
 */
function bp_blogs_post_type_comments_avoid_duplicates( $retval ) {
	/**
	 * Only limit the display when Post type comments are synced with
	 * activity comments.
	 */
	if ( bp_disable_blogforum_comments() ) {
		return $retval;
	}

	if ( 'activity_comment' !== bp_get_activity_type() ) {
		return $retval;
	}

	// Check the parent activity.
	$parent_activity = new BP_Activity_Activity( bp_get_activity_item_id() );

	if ( isset( $parent_activity->type ) && bp_activity_post_type_get_tracking_arg( $parent_activity->type, 'post_type' ) ) {
		$retval = 0;
	}

	return $retval;
}
add_filter( 'bp_activity_get_comment_count', 'bp_blogs_post_type_comments_avoid_duplicates' );

/**
 * Check if an activity comment associated with a blog post can be replied to.
 *
 * By default, disables replying to activity comments if the corresponding WP
 * blog post no longer accepts comments.
 *
 * This check uses a locally-cached value set in {@link bp_blogs_disable_activity_commenting()}
 * via {@link bp_blogs_setup_activity_loop_globals()}.
 *
 * @since 2.0.0
 *
 * @param bool         $retval  Are replies allowed for this activity reply.
 * @param object|array $comment The activity comment object.
 *
 * @return bool
 */
function bp_blogs_can_comment_reply( $retval, $comment ) {
	if ( is_array( $comment ) ) {
		$comment = (object) $comment;
	}

	// Check comment depth and disable if depth is too large.
	if ( isset( buddypress()->blogs->thread_depth[$comment->item_id] ) ){
		if ( bp_activity_get_comment_depth( $comment ) >= buddypress()->blogs->thread_depth[$comment->item_id] ) {
			$retval = false;
		}
	}

	// Check if we should disable activity replies based on the parent activity.
	if ( isset( buddypress()->blogs->allow_comments[$comment->item_id] ) ){
		// The blog post has closed off commenting, so we should disable all activity
		// comments under the parent 'new_blog_post' activity entry.
		if ( ! buddypress()->blogs->allow_comments[ $comment->item_id ] ) {
			$retval = false;
		}
	}

	// If comments need moderation, disable activity commenting.
	if ( isset( buddypress()->blogs->comment_moderation[ $comment->item_id ] ) && buddypress()->blogs->comment_moderation[ $comment->item_id ] ) {
		$retval = false;
	}

	return $retval;
}
add_filter( 'bp_activity_can_comment_reply', 'bp_blogs_can_comment_reply', 10, 2 );

/**
 * Changes activity comment permalinks to use the blog comment permalink
 * instead of the activity permalink.
 *
 * This is only done if activity commenting is allowed and whether the parent
 * activity item is a 'new_blog_post' entry.
 *
 * @since 2.0.0
 *
 * @param string $retval The activity comment permalink.
 * @return string
 */
function bp_blogs_activity_comment_permalink( $retval = '' ) {
	global $activities_template;

	// Get the current comment ID.
	$item_id = isset( $activities_template->activity->current_comment->item_id )
		? $activities_template->activity->current_comment->item_id
		: false;

	// Maybe adjust the link if item ID exists.
	if ( ( false !== $item_id ) && isset( buddypress()->blogs->allow_comments[ $item_id ] ) ) {
		$retval = $activities_template->activity->current_comment->primary_link;
	}

	return $retval;
}
add_filter( 'bp_get_activity_comment_permalink', 'bp_blogs_activity_comment_permalink' );

/**
 * Changes single activity comment entries to use the blog comment permalink.
 *
 * This is only done if the activity comment is associated with a blog comment.
 *
 * @since 2.0.1
 *
 * @param string               $retval   The activity permalink.
 * @param BP_Activity_Activity $activity Activity object.
 * @return string
 */
function bp_blogs_activity_comment_single_permalink( $retval, $activity ) {
	if ( 'activity_comment' !== $activity->type ) {
		return $retval;
	}

	if ( bp_disable_blogforum_comments() ) {
		return $retval;
	}

	$parent_activity = new BP_Activity_Activity( $activity->item_id );

	if ( isset( $parent_activity->type ) && bp_activity_post_type_get_tracking_arg( $parent_activity->type, 'post_type' ) ) {
		$retval = $activity->primary_link;
	}

	return $retval;
}
add_filter( 'bp_activity_get_permalink', 'bp_blogs_activity_comment_single_permalink', 10, 2 );

/**
 * Formats single activity comment entries to use the blog comment action.
 *
 * This is only done if the activity comment is associated with a blog comment.
 *
 * @since 2.0.1
 *
 * @param string               $retval   The activity action.
 * @param BP_Activity_Activity $activity Activity object.
 * @return string
 */
function bp_blogs_activity_comment_single_action( $retval, $activity ) {
	if ( 'activity_comment' !== $activity->type ) {
		return $retval;
	}

	if ( bp_disable_blogforum_comments() ) {
		return $retval;
	}

	$parent_activity = new BP_Activity_Activity( $activity->item_id );

	if ( ! isset( $parent_activity->type ) ) {
		return $retval;
	}

	$post_type = bp_activity_post_type_get_tracking_arg( $parent_activity->type, 'post_type' );

	if ( ! $post_type ) {
		return $retval;
	}

	$blog_comment_id = bp_activity_get_meta( $activity->id, "bp_blogs_{$post_type}_comment_id" );

	if ( ! empty( $blog_comment_id ) ) {
		$bp = buddypress();

		// Check if a comment action id is set for the parent activity.
		$comment_action_id = bp_activity_post_type_get_tracking_arg( $parent_activity->type, 'comment_action_id' );

		// Use the action string callback for the activity type.
		if ( ! empty( $comment_action_id ) ) {
			// Fake a 'new_{post_type}_comment' by cloning the activity object.
			$object = clone $activity;

			// Set the type of the activity to be a comment about a post type.
			$object->type = $comment_action_id;

			// Use the blog ID as the item_id.
			$object->item_id = $parent_activity->item_id;

			// Use comment ID as the secondary_item_id.
			$object->secondary_item_id = $blog_comment_id;

			// Get the format callback for this activity comment.
			$format_callback = bp_activity_post_type_get_tracking_arg( $comment_action_id, 'format_callback' );

			// Now format the activity action using the 'new_{post_type}_comment' action callback.
			if ( is_callable( $format_callback ) ) {
				$retval = call_user_func_array( $format_callback, array( '', $object ) );
			}
		}
	}

	return $retval;
}
add_filter( 'bp_get_activity_action_pre_meta', 'bp_blogs_activity_comment_single_action', 10, 2 );
