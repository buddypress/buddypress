<?php
/**
 * Blogs component functions.
 *
 * @package BuddyPress
 * @subpackage BlogsFunctions
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Check whether the $bp global lists an activity directory page.
 *
 * @since 1.5.0
 *
 * @return bool True if set, false if empty.
 */
function bp_blogs_has_directory() {
	$bp = buddypress();

	return (bool) !empty( $bp->pages->blogs->id );
}

/**
 * Retrieve a set of blogs.
 *
 * @see BP_Blogs_Blog::get() for a description of arguments and return value.
 *
 * @param array|string $args {
 *     Arguments are listed here with their default values. For more
 *     information about the arguments, see {@link BP_Blogs_Blog::get()}.
 *     @type string      $type              Default: 'active'.
 *     @type int|bool    $user_id           Default: false.
 *     @type array       $include_blog_ids  Default: false.
 *     @type string|bool $search_terms      Default: false.
 *     @type int         $per_page          Default: 20.
 *     @type int         $page              Default: 1.
 *     @type bool        $update_meta_cache Whether to pre-fetch blogmeta. Default: true.
 * }
 * @return array See {@link BP_Blogs_Blog::get()}.
 */
function bp_blogs_get_blogs( $args = '' ) {

	// Parse query arguments.
	$r = bp_parse_args( $args, array(
		'type'              => 'active', // 'active', 'alphabetical', 'newest', or 'random'
		'include_blog_ids'  => false,    // Array of blog IDs to include
		'user_id'           => false,    // Limit to blogs this user can post to
		'search_terms'      => false,    // Limit to blogs matching these search terms
		'per_page'          => 20,       // The number of results to return per page
		'page'              => 1,        // The page to return if limiting per page
		'update_meta_cache' => true      // Whether to pre-fetch blogmeta
	), 'blogs_get_blogs' );

	// Get the blogs.
	$blogs = BP_Blogs_Blog::get(
		$r['type'],
		$r['per_page'],
		$r['page'],
		$r['user_id'],
		$r['search_terms'],
		$r['update_meta_cache'],
		$r['include_blog_ids']
	);

	// Filter and return.
	return apply_filters( 'bp_blogs_get_blogs', $blogs, $r );
}

/**
 * Populate the BP blogs table with existing blogs.
 *
 * @since 1.0.0
 *
 * @global object $wpdb WordPress database object.
 * @uses get_users()
 * @uses bp_blogs_record_blog()
 *
 * @return bool
 */
function bp_blogs_record_existing_blogs() {
	global $wpdb;

	// Query for all sites in network.
	if ( is_multisite() ) {

		// Get blog ID's if not a large network.
		if ( ! wp_is_large_network() ) {
			$blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM {$wpdb->base_prefix}blogs WHERE mature = 0 AND spam = 0 AND deleted = 0 AND site_id = %d", $wpdb->siteid ) );

			// If error running this query, set blog ID's to false.
			if ( is_wp_error( $blog_ids ) ) {
				$blog_ids = false;
			}

		// Large networks are not currently supported.
		} else {
			$blog_ids = false;
		}

	// Record a single site.
	} else {
		$blog_ids = $wpdb->blogid;
	}

	// Bail if there are no blogs in the network.
	if ( empty( $blog_ids ) ) {
		return false;
	}

	// Get BuddyPress.
	$bp = buddypress();

	// Truncate user blogs table.
	$truncate = $wpdb->query( "TRUNCATE {$bp->blogs->table_name}" );
	if ( is_wp_error( $truncate ) ) {
		return false;
	}

	// Truncate user blogsmeta table.
	$truncate = $wpdb->query( "TRUNCATE {$bp->blogs->table_name_blogmeta}" );
	if ( is_wp_error( $truncate ) ) {
		return false;
	}

	// Loop through users of blogs and record the relationship.
	foreach ( (array) $blog_ids as $blog_id ) {

		// Ensure that the cache is clear after the table TRUNCATE above.
		wp_cache_delete( $blog_id, 'blog_meta' );

		// Get all users.
		$users = get_users( array(
			'blog_id' => $blog_id
		) );

		// Continue on if no users exist for this site (how did this happen?).
		if ( empty( $users ) ) {
			continue;
		}

		// Loop through users and record their relationship to this blog.
		foreach ( (array) $users as $user ) {
			bp_blogs_add_user_to_blog( $user->ID, false, $blog_id );
		}
	}

	/**
	 * Fires after the BP blogs tables have been populated with existing blogs.
	 *
	 * @since 2.4.0
	 */
	do_action( 'bp_blogs_recorded_existing_blogs' );

	// No errors.
	return true;
}

/**
 * Check whether a given blog should be recorded in activity streams.
 *
 * If $user_id is provided, you can restrict site from being recordable
 * only to particular users.
 *
 * @since 1.7.0
 *
 * @uses apply_filters()
 *
 * @param int $blog_id ID of the blog being checked.
 * @param int $user_id Optional. ID of the user for whom access is being checked.
 * @return bool True if blog is recordable, otherwise false.
 */
function bp_blogs_is_blog_recordable( $blog_id, $user_id = 0 ) {

	$recordable_globally = apply_filters( 'bp_blogs_is_blog_recordable', true, $blog_id );

	if ( !empty( $user_id ) ) {
		$recordable_for_user = apply_filters( 'bp_blogs_is_blog_recordable_for_user', $recordable_globally, $blog_id, $user_id );
	} else {
		$recordable_for_user = $recordable_globally;
	}

	if ( !empty( $recordable_for_user ) ) {
		return true;
	}

	return $recordable_globally;
}

/**
 * Check whether a given blog should be tracked by the Blogs component.
 *
 * If $user_id is provided, the developer can restrict site from
 * being trackable only to particular users.
 *
 * @since 1.7.0
 *
 * @uses bp_blogs_is_blog_recordable
 * @uses apply_filters()
 *
 * @param int $blog_id ID of the blog being checked.
 * @param int $user_id Optional. ID of the user for whom access is being checked.
 * @return bool True if blog is trackable, otherwise false.
 */
function bp_blogs_is_blog_trackable( $blog_id, $user_id = 0 ) {

	$trackable_globally = apply_filters( 'bp_blogs_is_blog_trackable', bp_blogs_is_blog_recordable( $blog_id, $user_id ), $blog_id );

	if ( !empty( $user_id ) ) {
		$trackable_for_user = apply_filters( 'bp_blogs_is_blog_trackable_for_user', $trackable_globally, $blog_id, $user_id );
	} else {
		$trackable_for_user = $trackable_globally;
	}

	if ( !empty( $trackable_for_user ) ) {
		return $trackable_for_user;
	}

	return $trackable_globally;
}

/**
 * Make BuddyPress aware of a new site so that it can track its activity.
 *
 * @since 1.0.0
 *
 * @uses BP_Blogs_Blog
 *
 * @param int  $blog_id     ID of the blog being recorded.
 * @param int  $user_id     ID of the user for whom the blog is being recorded.
 * @param bool $no_activity Optional. Whether to skip recording an activity
 *                          item about this blog creation. Default: false.
 * @return bool|null Returns false on failure.
 */
function bp_blogs_record_blog( $blog_id, $user_id, $no_activity = false ) {

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	// If blog is not recordable, do not record the activity.
	if ( !bp_blogs_is_blog_recordable( $blog_id, $user_id ) )
		return false;

	$name = get_blog_option( $blog_id, 'blogname' );
	$url  = get_home_url( $blog_id );

	if ( empty( $name ) ) {
		$name = $url;
	}

	$description     = get_blog_option( $blog_id, 'blogdescription' );
	$close_old_posts = get_blog_option( $blog_id, 'close_comments_for_old_posts' );
	$close_days_old  = get_blog_option( $blog_id, 'close_comments_days_old' );

	$thread_depth = get_blog_option( $blog_id, 'thread_comments' );
	if ( ! empty( $thread_depth ) ) {
		$thread_depth = get_blog_option( $blog_id, 'thread_comments_depth' );
	} else {
		// Perhaps filter this?
		$thread_depth = 1;
	}

	$recorded_blog          = new BP_Blogs_Blog;
	$recorded_blog->user_id = $user_id;
	$recorded_blog->blog_id = $blog_id;
	$recorded_blog_id       = $recorded_blog->save();
	$is_recorded            = !empty( $recorded_blog_id ) ? true : false;

	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'url', $url );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'name', $name );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'description', $description );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'last_activity', bp_core_current_time() );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'close_comments_for_old_posts', $close_old_posts );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'close_comments_days_old', $close_days_old );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'thread_comments_depth', $thread_depth );

	$is_private = !empty( $_POST['blog_public'] ) && (int) $_POST['blog_public'] ? false : true;
	$is_private = !apply_filters( 'bp_is_new_blog_public', !$is_private );

	// Only record this activity if the blog is public.
	if ( !$is_private && !$no_activity && bp_blogs_is_blog_trackable( $blog_id, $user_id ) ) {

		// Record this in activity streams.
		bp_blogs_record_activity( array(
			'user_id'      => $recorded_blog->user_id,
			'primary_link' => apply_filters( 'bp_blogs_activity_created_blog_primary_link', $url, $recorded_blog->blog_id ),
			'type'         => 'new_blog',
			'item_id'      => $recorded_blog->blog_id
		) );
	}

	/**
	 * Fires after BuddyPress has been made aware of a new site for activity tracking.
	 *
	 * @since 1.0.0
	 *
	 * @param BP_Blogs_Blog $recorded_blog Current blog being recorded. Passed by reference.
	 * @param bool          $is_private    Whether or not the current blog being recorded is private.
	 * @param bool          $is_recorded   Whether or not the current blog was recorded.
	 */
	do_action_ref_array( 'bp_blogs_new_blog', array( &$recorded_blog, $is_private, $is_recorded ) );
}
add_action( 'wpmu_new_blog', 'bp_blogs_record_blog', 10, 2 );

/**
 * Update blog name in BuddyPress blogmeta table.
 *
 * @global object $wpdb DB Layer.
 *
 * @param string $oldvalue Value before save. Passed by do_action() but
 *                         unused here.
 * @param string $newvalue Value to change meta to.
 */
function bp_blogs_update_option_blogname( $oldvalue, $newvalue ) {
	global $wpdb;

	bp_blogs_update_blogmeta( $wpdb->blogid, 'name', $newvalue );
}
add_action( 'update_option_blogname', 'bp_blogs_update_option_blogname', 10, 2 );

/**
 * Update blog description in BuddyPress blogmeta table.
 *
 * @global object $wpdb DB Layer.
 *
 * @param string $oldvalue Value before save. Passed by do_action() but
 *                         unused here.
 * @param string $newvalue Value to change meta to.
 */
function bp_blogs_update_option_blogdescription( $oldvalue, $newvalue ) {
	global $wpdb;

	bp_blogs_update_blogmeta( $wpdb->blogid, 'description', $newvalue );
}
add_action( 'update_option_blogdescription', 'bp_blogs_update_option_blogdescription', 10, 2 );

/**
 * Update "Close comments for old posts" option in BuddyPress blogmeta table.
 *
 * @since 2.0.0
 *
 * @global object $wpdb DB Layer.
 *
 * @param string $oldvalue Value before save. Passed by do_action() but
 *                         unused here.
 * @param string $newvalue Value to change meta to.
 */
function bp_blogs_update_option_close_comments_for_old_posts( $oldvalue, $newvalue ) {
	global $wpdb;

	bp_blogs_update_blogmeta( $wpdb->blogid, 'close_comments_for_old_posts', $newvalue );
}
add_action( 'update_option_close_comments_for_old_posts', 'bp_blogs_update_option_close_comments_for_old_posts', 10, 2 );

/**
 * Update "Close comments after days old" option in BuddyPress blogmeta table.
 *
 * @since 2.0.0
 *
 * @global object $wpdb DB Layer.
 *
 * @param string $oldvalue Value before save. Passed by do_action() but
 *                         unused here.
 * @param string $newvalue Value to change meta to.
 */
function bp_blogs_update_option_close_comments_days_old( $oldvalue, $newvalue ) {
	global $wpdb;

	bp_blogs_update_blogmeta( $wpdb->blogid, 'close_comments_days_old', $newvalue );
}
add_action( 'update_option_close_comments_days_old', 'bp_blogs_update_option_close_comments_days_old', 10, 2 );

/**
 * When toggling threaded comments, update thread depth in blogmeta table.
 *
 * @since 2.0.0
 *
 * @global object $wpdb DB Layer.
 *
 * @param string $oldvalue Value before save. Passed by do_action() but
 *                         unused here.
 * @param string $newvalue Value to change meta to.
 */
function bp_blogs_update_option_thread_comments( $oldvalue, $newvalue ) {
	global $wpdb;

	if ( empty( $newvalue ) ) {
		$thread_depth = 1;
	} else {
		$thread_depth = get_option( 'thread_comments_depth' );
	}

	bp_blogs_update_blogmeta( $wpdb->blogid, 'thread_comments_depth', $thread_depth );
}
add_action( 'update_option_thread_comments', 'bp_blogs_update_option_thread_comments', 10, 2 );

/**
 * When updating comment depth, update thread depth in blogmeta table.
 *
 * @since 2.0.0
 *
 * @global object $wpdb DB Layer.
 *
 * @param string $oldvalue Value before save. Passed by do_action() but
 *                         unused here.
 * @param string $newvalue Value to change meta to.
 */
function bp_blogs_update_option_thread_comments_depth( $oldvalue, $newvalue ) {
	global $wpdb;

	$comments_enabled = get_option( 'thread_comments' );

	if (  $comments_enabled ) {
		bp_blogs_update_blogmeta( $wpdb->blogid, 'thread_comments_depth', $newvalue );
	}
}
add_action( 'update_option_thread_comments_depth', 'bp_blogs_update_option_thread_comments_depth', 10, 2 );

/**
 * Deletes the 'url' blogmeta for a site.
 *
 * Hooked to 'refresh_blog_details', which is notably used when editing a site
 * under "Network Admin > Sites".
 *
 * @since 2.3.0
 *
 * @param int $site_id The site ID.
 */
function bp_blogs_delete_url_blogmeta( $site_id = 0 ) {
	bp_blogs_delete_blogmeta( (int) $site_id, 'url' );
}
add_action( 'refresh_blog_details', 'bp_blogs_delete_url_blogmeta' );

/**
 * Record activity metadata about a published blog post.
 *
 * @since 2.2.0
 *
 * @param int     $activity_id ID of the activity item.
 * @param WP_Post $post        Post object.
 * @param array   $args        Array of arguments.
 */
function bp_blogs_publish_post_activity_meta( $activity_id, $post, $args ) {
	if ( empty( $activity_id ) || 'post' != $post->post_type ) {
		return;
	}

	bp_activity_update_meta( $activity_id, 'post_title', $post->post_title );

	if ( ! empty( $args['post_url'] ) ) {
		$post_permalink = $args['post_url'];
	} else {
		$post_permalink = $post->guid;
	}

	bp_activity_update_meta( $activity_id, 'post_url',   $post_permalink );

	// Update the blog's last activity.
	bp_blogs_update_blogmeta( $args['item_id'], 'last_activity', bp_core_current_time() );

	/**
	 * Fires after BuddyPress has recorded metadata about a published blog post.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $ID    ID of the blog post being recorded.
	 * @param WP_Post $post  WP_Post object for the current blog post.
	 * @param string  $value ID of the user associated with the current blog post.
	 */
	do_action( 'bp_blogs_new_blog_post', $post->ID, $post, $args['user_id'] );
}
add_action( 'bp_activity_post_type_published', 'bp_blogs_publish_post_activity_meta', 10, 3 );

/**
 * Updates a blog post's activity meta entry during a post edit.
 *
 * @since 2.2.0
 * @since 2.5.0 Add the post type tracking args object parameter
 *
 * @param WP_Post              $post                 Post object.
 * @param BP_Activity_Activity $activity             Activity object.
 * @param object               $activity_post_object The post type tracking args object.
 */
function bp_blogs_update_post_activity_meta( $post, $activity, $activity_post_object ) {
	if ( empty( $activity->id ) || empty( $activity_post_object->action_id ) ) {
		return;
	}

	// Update post title in activity meta.
	$existing_title = bp_activity_get_meta( $activity->id, 'post_title' );
	if ( $post->post_title !== $existing_title ) {
		bp_activity_update_meta( $activity->id, 'post_title', $post->post_title );

		if ( ! empty( $activity_post_object->comments_tracking->action_id ) ) {
			// Now update activity meta for post comments... sigh.
			add_filter( 'comments_clauses', 'bp_blogs_comments_clauses_select_by_id' );
			$comments = get_comments( array( 'post_id' => $post->ID ) );
			remove_filter( 'comments_clauses', 'bp_blogs_comments_clauses_select_by_id' );

			if ( ! empty( $comments ) ) {
				$activity_ids = array();
				$comment_ids  = wp_list_pluck( $comments, 'comment_ID' );

				// Set up activity args.
				$args = array(
					'update_meta_cache' => false,
					'show_hidden'       => true,
					'per_page'          => 99999,
				);

				// Query for old-style "new_blog_comment" activity items.
				$args['filter'] = array(
					'object'       => $activity_post_object->comments_tracking->component_id,
					'action'       => $activity_post_object->comments_tracking->action_id,
					'secondary_id' => implode( ',', $comment_ids ),
				);

				$activities = bp_activity_get( $args );
				if ( ! empty( $activities['activities'] ) ) {
					$activity_ids = (array) wp_list_pluck( $activities['activities'], 'id' );
				}

				// Query for activity comments connected to a blog post.
				unset( $args['filter'] );
				$args['meta_query'] = array( array(
					'key'     => 'bp_blogs_' . $post->post_type . '_comment_id',
					'value'   => $comment_ids,
					'compare' => 'IN',
				) );
				$args['type'] = 'activity_comment';
				$args['display_comments'] = 'stream';

				$activities = bp_activity_get( $args );
				if ( ! empty( $activities['activities'] ) ) {
					$activity_ids = array_merge( $activity_ids, (array) wp_list_pluck( $activities['activities'], 'id' ) );
				}

				// Update activity meta for all found activity items.
				if ( ! empty( $activity_ids ) ) {
					foreach ( $activity_ids as $aid ) {
						bp_activity_update_meta( $aid, 'post_title', $post->post_title );
					}
				}

				unset( $activities, $activity_ids, $comment_ids, $comments );
			}
		}
	}

	// Add post comment status to activity meta if closed.
	if( 'closed' == $post->comment_status ) {
		bp_activity_update_meta( $activity->id, 'post_comment_status', $post->comment_status );
	} else {
		bp_activity_delete_meta( $activity->id, 'post_comment_status' );
	}
}
add_action( 'bp_activity_post_type_updated', 'bp_blogs_update_post_activity_meta', 10, 3 );

/**
 * Update Activity and blogs meta and eventually sync comment with activity comment
 *
 * @since  2.5.0
 *
 * @param  int|bool   $activity_id          ID of recorded activity, or false if sync is active.
 * @param  WP_Comment $comment              The comment object.
 * @param  array      $activity_args        Array of activity arguments.
 * @param  object     $activity_post_object The post type tracking args object.
 * @return int|bool   Returns false if no activity, the activity id otherwise.
 */
function bp_blogs_comment_sync_activity_comment( &$activity_id, $comment = null, $activity_args = array(), $activity_post_object = null ) {
	if ( empty( $activity_args ) || empty( $comment->post->ID ) || empty( $activity_post_object->comment_action_id ) ) {
		return false;
	}

	// Set the current blog id.
	$blog_id = get_current_blog_id();

	// These activity metadatas are used to build the new_blog_comment action string
	if ( ! empty( $activity_id ) && ! empty( $activity_args['item_id'] ) && 'new_blog_comment' === $activity_post_object->comment_action_id ) {
		// add some post info in activity meta
		bp_activity_update_meta( $activity_id, 'post_title', $comment->post->post_title );
		bp_activity_update_meta( $activity_id, 'post_url',   esc_url_raw( add_query_arg( 'p', $comment->post->ID, home_url( '/' ) ) ) );
	}

	// Sync comment - activity comment
	if ( ! bp_disable_blogforum_comments() ) {

		if ( ! empty( $_REQUEST['action'] ) ) {
			$existing_activity_id = get_comment_meta( $comment->comment_ID, 'bp_activity_comment_id', true );

			if ( ! empty( $existing_activity_id ) ) {
				$activity_args['id'] = $existing_activity_id;
			}
		}

		if ( empty( $activity_post_object ) ) {
			$activity_post_object = bp_activity_get_post_type_tracking_args( $comment->post->post_type );
		}

		if ( isset( $activity_post_object->action_id ) && isset( $activity_post_object->component_id ) ) {
			// find the parent 'new_post_type' activity entry
			$parent_activity_id = bp_activity_get_activity_id( array(
				'component'         => $activity_post_object->component_id,
				'type'              => $activity_post_object->action_id,
				'item_id'           => $blog_id,
				'secondary_item_id' => $comment->comment_post_ID
			) );

			// Try to create a new activity item for the parent blog post.
			if ( empty( $parent_activity_id ) ) {
				$parent_activity_id = bp_activity_post_type_publish( $comment->post->ID, $comment->post );
			}
		}

		// we found the parent activity entry
		// so let's go ahead and reconfigure some activity args
		if ( ! empty( $parent_activity_id ) ) {
			// set the parent activity entry ID
			$activity_args['activity_id'] = $parent_activity_id;

			// now see if the WP parent comment has a BP activity ID
			$comment_parent = 0;
			if ( ! empty( $comment->comment_parent ) ) {
				$comment_parent = get_comment_meta( $comment->comment_parent, 'bp_activity_comment_id', true );
			}

			// WP parent comment does not have a BP activity ID
			// so set to 'new_' . post_type activity ID
			if ( empty( $comment_parent ) ) {
				$comment_parent = $parent_activity_id;
			}

			$activity_args['parent_id']         = $comment_parent;
			$activity_args['skip_notification'] = true;

		// could not find corresponding parent activity entry
		// so wipe out $args array
		} else {
			$activity_args = array();
		}

		// Record in activity streams
		if ( ! empty( $activity_args ) ) {
			$activity_id = bp_activity_new_comment( $activity_args );

			if ( empty( $activity_args['id'] ) ) {
				// The activity metadata to inform about the corresponding comment ID
				bp_activity_update_meta( $activity_id, "bp_blogs_{$comment->post->post_type}_comment_id", $comment->comment_ID );

				// The comment metadata to inform about the corresponding activity ID
				add_comment_meta( $comment->comment_ID, 'bp_activity_comment_id', $activity_id );

				// These activity metadatas are used to build the new_blog_comment action string
				if ( 'new_blog_comment' === $activity_post_object->comment_action_id ) {
					bp_activity_update_meta( $activity_id, 'post_title', $comment->post->post_title );
					bp_activity_update_meta( $activity_id, 'post_url', esc_url_raw( add_query_arg( 'p', $comment->post->ID, home_url( '/' ) ) ) );
				}
			}
		}
	}

	// Update the blogs last active date
	bp_blogs_update_blogmeta( $blog_id, 'last_activity', bp_core_current_time() );

	if ( 'new_blog_comment' === $activity_post_object->comment_action_id ) {
		/**
		 * Fires after BuddyPress has recorded metadata about a published blog post comment.
		 *
		 * @since 2.5.0
		 *
		 * @param int     $value    Comment ID of the blog post comment being recorded.
		 * @param WP_Post $post  WP_Comment object for the current blog post.
		 * @param string  $value ID of the user associated with the current blog post comment.
		 */
		do_action( 'bp_blogs_new_blog_comment', $comment->comment_ID, $comment, bp_loggedin_user_id() );
	}

	return $activity_id;
}
add_action( 'bp_activity_post_type_comment', 'bp_blogs_comment_sync_activity_comment', 10, 4 );

/**
 * Record a user's association with a blog.
 *
 * This function is hooked to several WordPress actions where blog roles are
 * set/changed ('add_user_to_blog', 'profile_update', 'user_register'). It
 * parses the changes, and records them as necessary in the BP blog tracker.
 *
 * BuddyPress does not track blogs for users with the 'subscriber' role by
 * default, though as of 2.1.0 you can filter 'bp_blogs_get_allowed_roles' to
 * modify this behavior.
 *
 * @param int         $user_id The ID of the user.
 * @param string|bool $role    User's WordPress role for this blog ID.
 * @param int         $blog_id Blog ID user is being added to.
 * @return bool|null False on failure.
 */
function bp_blogs_add_user_to_blog( $user_id, $role = false, $blog_id = 0 ) {
	global $wpdb;

	// If no blog ID was passed, use the root blog ID.
	if ( empty( $blog_id ) ) {
		$blog_id = isset( $wpdb->blogid ) ? $wpdb->blogid : bp_get_root_blog_id();
	}

	// If no role was passed, try to find the blog role.
	if ( empty( $role ) ) {

		// Get user capabilities.
		$key        = $wpdb->get_blog_prefix( $blog_id ). 'capabilities';
		$user_roles = array_keys( (array) bp_get_user_meta( $user_id, $key, true ) );

		// User has roles so lets.
		if ( ! empty( $user_roles ) ) {

			// Get blog roles.
			$blog_roles      = array_keys( bp_get_current_blog_roles() );

			// Look for blog only roles of the user.
			$intersect_roles = array_intersect( $user_roles, $blog_roles );

			// If there's a role in the array, use the first one. This isn't
			// very smart, but since roles aren't exactly hierarchical, and
			// WordPress does not yet have a UI for multiple user roles, it's
			// fine for now.
			if ( ! empty( $intersect_roles ) ) {
				$role = array_shift( $intersect_roles );
			}
		}
	}

	// Bail if no role was found or role is not in the allowed roles array.
	if ( empty( $role ) || ! in_array( $role, bp_blogs_get_allowed_roles() ) ) {
		return false;
	}

	// Record the blog activity for this user being added to this blog.
	bp_blogs_record_blog( $blog_id, $user_id, true );
}
add_action( 'add_user_to_blog', 'bp_blogs_add_user_to_blog', 10, 3 );
add_action( 'profile_update',   'bp_blogs_add_user_to_blog'        );
add_action( 'user_register',    'bp_blogs_add_user_to_blog'        );

/**
 * The allowed blog roles a member must have to be recorded into the
 * `bp_user_blogs` pointer table.
 *
 * This added and was made filterable in BuddyPress 2.1.0 to make it easier
 * to extend the functionality of the Blogs component.
 *
 * @since 2.1.0
 *
 * @return string
 */
function bp_blogs_get_allowed_roles() {
	return apply_filters( 'bp_blogs_get_allowed_roles', array( 'contributor', 'author', 'editor', 'administrator' ) );
}

/**
 * Remove a blog-user pair from BP's blog tracker.
 *
 * @param int $user_id ID of the user whose blog is being removed.
 * @param int $blog_id Optional. ID of the blog being removed. Default: current blog ID.
 */
function bp_blogs_remove_user_from_blog( $user_id, $blog_id = 0 ) {
	global $wpdb;

	if ( empty( $blog_id ) ) {
		$blog_id = $wpdb->blogid;
	}

	bp_blogs_remove_blog_for_user( $user_id, $blog_id );
}
add_action( 'remove_user_from_blog', 'bp_blogs_remove_user_from_blog', 10, 2 );

/**
 * Rehook WP's maybe_add_existing_user_to_blog with a later priority.
 *
 * WordPress catches add-user-to-blog requests at init:10. In some cases, this
 * can precede BP's Blogs component. This function bumps the priority of the
 * core function, so that we can be sure that the Blogs component is loaded
 * first. See https://buddypress.trac.wordpress.org/ticket/3916.
 *
 * @since 1.6.0
 */
function bp_blogs_maybe_add_user_to_blog() {
	if ( ! is_multisite() )
		return;

	remove_action( 'init', 'maybe_add_existing_user_to_blog' );
	add_action( 'init', 'maybe_add_existing_user_to_blog', 20 );
}
add_action( 'init', 'bp_blogs_maybe_add_user_to_blog', 1 );

/**
 * Remove the "blog created" item from the BP blogs tracker and activity stream.
 *
 * @param int $blog_id ID of the blog being removed.
 */
function bp_blogs_remove_blog( $blog_id ) {

	$blog_id = (int) $blog_id;

	/**
	 * Fires before a "blog created" item is removed from blogs
	 * tracker and activity stream.
	 *
	 * @since 1.5.0
	 *
	 * @param int $blog_id ID of the blog having its item removed.
	 */
	do_action( 'bp_blogs_before_remove_blog', $blog_id );

	BP_Blogs_Blog::delete_blog_for_all( $blog_id );

	// Delete activity stream item.
	bp_blogs_delete_activity( array(
		'item_id'   => $blog_id,
		'component' => buddypress()->blogs->id,
		'type'      => 'new_blog'
	) );

	/**
	 * Fires after a "blog created" item has been removed from blogs
	 * tracker and activity stream.
	 *
	 * @since 1.0.0
	 *
	 * @param int $blog_id ID of the blog who had its item removed.
	 */
	do_action( 'bp_blogs_remove_blog', $blog_id );
}
add_action( 'delete_blog', 'bp_blogs_remove_blog' );

/**
 * Remove a blog from the tracker for a specific user.
 *
 * @param int $user_id ID of the user for whom the blog is being removed.
 * @param int $blog_id ID of the blog being removed.
 */
function bp_blogs_remove_blog_for_user( $user_id, $blog_id ) {

	$blog_id = (int) $blog_id;
	$user_id = (int) $user_id;

	/**
	 * Fires before a blog is removed from the tracker for a specific user.
	 *
	 * @since 1.5.0
	 *
	 * @param int $blog_id ID of the blog being removed.
	 * @param int $user_id ID of the user having the blog removed for.
	 */
	do_action( 'bp_blogs_before_remove_blog_for_user', $blog_id, $user_id );

	BP_Blogs_Blog::delete_blog_for_user( $blog_id, $user_id );

	// Delete activity stream item.
	bp_blogs_delete_activity( array(
		'item_id'   => $blog_id,
		'component' => buddypress()->blogs->id,
		'type'      => 'new_blog'
	) );

	/**
	 * Fires after a blog has been removed from the tracker for a specific user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $blog_id ID of the blog that was removed.
	 * @param int $user_id ID of the user having the blog removed for.
	 */
	do_action( 'bp_blogs_remove_blog_for_user', $blog_id, $user_id );
}
add_action( 'remove_user_from_blog', 'bp_blogs_remove_blog_for_user', 10, 2 );

/**
 * Remove a blog post activity item from the activity stream.
 *
 * @param int $post_id ID of the post to be removed.
 * @param int $blog_id Optional. Defaults to current blog ID.
 * @param int $user_id Optional. Defaults to the logged-in user ID. This param
 *                     is currently unused in the function (but is passed to hooks).
 * @return bool
 */
function bp_blogs_remove_post( $post_id, $blog_id = 0, $user_id = 0 ) {
	global $wpdb;

	if ( empty( $wpdb->blogid ) )
		return false;

	$post_id = (int) $post_id;

	if ( !$blog_id )
		$blog_id = (int) $wpdb->blogid;

	if ( !$user_id )
		$user_id = bp_loggedin_user_id();

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

	// Delete activity stream item.
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

/**
 * Remove a synced activity comment from the activity stream.
 *
 * @since 2.5.0
 *
 * @param bool   $deleted              True when a comment post type activity was successfully removed.
 * @param int    $comment_id           ID of the comment to be removed.
 * @param object $activity_post_object The post type tracking args object.
 * @param string $activity_type        The post type comment activity type.
 *
 * @return bool True on success. False on error.
 */
function bp_blogs_post_type_remove_comment( $deleted, $comment_id, $activity_post_object, $activity_type = '' ) {
	// Remove synced activity comments, if needed.
	if ( ! bp_disable_blogforum_comments() ) {
		// Get associated activity ID from comment meta
		$activity_id = get_comment_meta( $comment_id, 'bp_activity_comment_id', true );

		/**
		 * Delete the associated activity comment & also remove
		 * child post comments and associated activity comments.
		 */
		if ( ! empty( $activity_id ) ) {
			// fetch the activity comments for the activity item
			$activity = bp_activity_get( array(
				'in'               => $activity_id,
				'display_comments' => 'stream',
				'spam'             => 'all',
			) );

			// get all activity comment IDs for the pending deleted item
			if ( ! empty( $activity['activities'] ) ) {
				$activity_ids   = bp_activity_recurse_comments_activity_ids( $activity );
				$activity_ids[] = $activity_id;

				// delete activity items
				foreach ( $activity_ids as $activity_id ) {
					bp_activity_delete( array(
						'id' => $activity_id
					) );
				}

				// remove associated blog comments
				bp_blogs_remove_associated_blog_comments( $activity_ids );

				// rebuild activity comment tree
				BP_Activity_Activity::rebuild_activity_comment_tree( $activity['activities'][0]->item_id );

				// Set the result
				$deleted = true;
			}
		}
	}

	// Backcompat for comments about the 'post' post type.
	if ( 'new_blog_comment' === $activity_type ) {
		/**
		 * Fires after a blog comment activity item was removed from activity stream.
		 *
		 * @since 1.0.0
		 *
		 * @param int $value      ID for the blog associated with the removed comment.
		 * @param int $comment_id ID of the comment being removed.
		 * @param int $value      ID of the current logged in user.
		 */
		do_action( 'bp_blogs_remove_comment', get_current_blog_id(), $comment_id, bp_loggedin_user_id() );
	}

	return $deleted;
}
add_action( 'bp_activity_post_type_remove_comment', 'bp_blogs_post_type_remove_comment', 10, 4 );

/**
 * Removes blog comments that are associated with activity comments.
 *
 * @since 2.0.0
 *
 * @see bp_blogs_remove_synced_comment()
 * @see bp_blogs_sync_delete_from_activity_comment()
 *
 * @param array $activity_ids The activity IDs to check association with blog
 *                            comments.
 * @param bool  $force_delete  Whether to force delete the comments. If false,
 *                            comments are trashed instead.
 */
function bp_blogs_remove_associated_blog_comments( $activity_ids = array(), $force_delete = true ) {
	// Query args.
	$query_args = array(
		'meta_query' => array(
			array(
				'key'     => 'bp_activity_comment_id',
				'value'   => implode( ',', (array) $activity_ids ),
				'compare' => 'IN',
			)
		)
	);

	// Get comment.
	$comment_query = new WP_Comment_Query;
	$comments = $comment_query->query( $query_args );

	// Found the corresponding comments
	// let's delete them!
	foreach ( $comments as $comment ) {
		wp_delete_comment( $comment->comment_ID, $force_delete );

		// If we're trashing the comment, remove the meta key as well.
		if ( empty( $force_delete ) ) {
			delete_comment_meta( $comment->comment_ID, 'bp_activity_comment_id' );
		}
	}
}

/**
 * Get the total number of blogs being tracked by BuddyPress.
 *
 * @return int $count Total blog count.
 */
function bp_blogs_total_blogs() {
	$count = wp_cache_get( 'bp_total_blogs', 'bp' );

	if ( false === $count ) {
		$blogs = BP_Blogs_Blog::get_all();
		$count = $blogs['total'];
		wp_cache_set( 'bp_total_blogs', $count, 'bp' );
	}
	return $count;
}

/**
 * Get the total number of blogs being tracked by BP for a specific user.
 *
 * @since 1.2.0
 *
 * @param int $user_id ID of the user being queried. Default: on a user page,
 *                     the displayed user. Otherwise, the logged-in user.
 * @return int $count Total blog count for the user.
 */
function bp_blogs_total_blogs_for_user( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	// No user ID? do not attempt to look at cache.
	if ( empty( $user_id ) ) {
		return 0;
	}

	$count = wp_cache_get( 'bp_total_blogs_for_user_' . $user_id, 'bp' );
	if ( false === $count ) {
		$count = BP_Blogs_Blog::total_blog_count_for_user( $user_id );
		wp_cache_set( 'bp_total_blogs_for_user_' . $user_id, $count, 'bp' );
	}

	return $count;
}

/**
 * Remove the all data related to a given blog from the BP blogs tracker and activity stream.
 *
 * @param int $blog_id The ID of the blog to expunge.
 */
function bp_blogs_remove_data_for_blog( $blog_id ) {

	/**
	 * Fires before all data related to a given blog is removed from blogs tracker
	 * and activity stream.
	 *
	 * @since 1.5.0
	 *
	 * @param int $blog_id ID of the blog whose data is being removed.
	 */
	do_action( 'bp_blogs_before_remove_data_for_blog', $blog_id );

	// If this is regular blog, delete all data for that blog.
	BP_Blogs_Blog::delete_blog_for_all( $blog_id );

	// Delete activity stream item.
	bp_blogs_delete_activity( array(
		'item_id'   => $blog_id,
		'component' => buddypress()->blogs->id,
		'type'      => false
	) );

	/**
	 * Fires after all data related to a given blog has been removed from blogs tracker
	 * and activity stream.
	 *
	 * @since 1.0.0
	 *
	 * @param int $blog_id ID of the blog whose data is being removed.
	 */
	do_action( 'bp_blogs_remove_data_for_blog', $blog_id );
}
add_action( 'delete_blog', 'bp_blogs_remove_data_for_blog', 1 );

/**
 * Get all of a user's blogs, as tracked by BuddyPress.
 *
 * @see BP_Blogs_Blog::get_blogs_for_user() for a description of parameters
 *      and return values.
 *
 * @param int  $user_id     See {@BP_Blogs_Blog::get_blogs_for_user()}.
 * @param bool $show_hidden See {@BP_Blogs_Blog::get_blogs_for_user()}.
 * @return array See {@BP_Blogs_Blog::get_blogs_for_user()}.
 */
function bp_blogs_get_blogs_for_user( $user_id, $show_hidden = false ) {
	return BP_Blogs_Blog::get_blogs_for_user( $user_id, $show_hidden );
}

/**
 * Retrieve a list of all blogs.
 *
 * @see BP_Blogs_Blog::get_all() for a description of parameters and return values.
 *
 * @param int|null $limit See {@BP_Blogs_Blog::get_all()}.
 * @param int|null $page  See {@BP_Blogs_Blog::get_all()}.
 * @return array See {@BP_Blogs_Blog::get_all()}.
 */
function bp_blogs_get_all_blogs( $limit = null, $page = null ) {
	return BP_Blogs_Blog::get_all( $limit, $page );
}

/**
 * Retrieve a random list of blogs.
 *
 * @see BP_Blogs_Blog::get() for a description of parameters and return values.
 *
 * @param int|null $limit See {@BP_Blogs_Blog::get()}.
 * @param int|null $page  See {@BP_Blogs_Blog::get()}.
 * @return array See {@BP_Blogs_Blog::get()}.
 */
function bp_blogs_get_random_blogs( $limit = null, $page = null ) {
	return BP_Blogs_Blog::get( 'random', $limit, $page );
}

/**
 * Check whether a given blog is hidden.
 *
 * @see BP_Blogs_Blog::is_hidden() for a description of parameters and return values.
 *
 * @param int $blog_id See {@BP_Blogs_Blog::is_hidden()}.
 * @return bool See {@BP_Blogs_Blog::is_hidden()}.
 */
function bp_blogs_is_blog_hidden( $blog_id ) {
	return BP_Blogs_Blog::is_hidden( $blog_id );
}

/*
 * Blog meta functions
 *
 * These functions are used to store specific blogmeta in one global table,
 * rather than in each blog's options table. Significantly speeds up global blog
 * queries. By default each blog's name, description and last updated time are
 * stored and synced here.
 */

/**
 * Delete a metadata from the DB for a blog.
 *
 * @global object $wpdb WordPress database access object.
 *
 * @param int         $blog_id    ID of the blog whose metadata is being deleted.
 * @param string|bool $meta_key   Optional. The key of the metadata being deleted. If
 *                                omitted, all BP metadata associated with the blog will
 *                                be deleted.
 * @param string|bool $meta_value Optional. If present, the metadata will only be
 *                                deleted if the meta_value matches this parameter.
 * @param bool        $delete_all Optional. If true, delete matching metadata entries for
 *                                all objects, ignoring the specified blog_id. Otherwise, only
 *                                delete matching metadata entries for the specified blog.
 *                                Default: false.
 * @return bool True on success, false on failure.
 */
function bp_blogs_delete_blogmeta( $blog_id, $meta_key = false, $meta_value = false, $delete_all = false ) {
	global $wpdb;

	// Legacy - if no meta_key is passed, delete all for the blog_id.
	if ( empty( $meta_key ) ) {
		$keys = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->blogmeta} WHERE blog_id = %d", $blog_id ) );
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	add_filter( 'query', 'bp_filter_metaid_column_name' );

	$retval = false;
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'blog', $blog_id, $key, $meta_value, $delete_all );
	}

	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get metadata for a given blog.
 *
 * @since 1.2.0
 *
 * @global object $wpdb WordPress database access object.
 *
 * @param int    $blog_id  ID of the blog whose metadata is being requested.
 * @param string $meta_key Optional. If present, only the metadata matching
 *                         that meta key will be returned. Otherwise, all
 *                         metadata for the blog will be fetched.
 * @param bool   $single   Optional. If true, return only the first value of the
 *                         specified meta_key. This parameter has no effect if
 *                         meta_key is not specified. Default: true.
 * @return mixed The meta value(s) being requested.
 */
function bp_blogs_get_blogmeta( $blog_id, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'blog', $blog_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Update a piece of blog meta.
 *
 * @global object $wpdb WordPress database access object.
 *
 * @param int    $blog_id    ID of the blog whose metadata is being updated.
 * @param string $meta_key   Key of the metadata being updated.
 * @param mixed  $meta_value Value to be set.
 * @param mixed  $prev_value Optional. If specified, only update existing
 *                           metadata entries with the specified value.
 *                           Otherwise, update all entries.
 * @return bool|int Returns false on failure. On successful update of existing
 *                  metadata, returns true. On successful creation of new metadata,
 *                  returns the integer ID of the new metadata row.
 */
function bp_blogs_update_blogmeta( $blog_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'blog', $blog_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of blog metadata.
 *
 * @since 2.0.0
 *
 * @param int    $blog_id    ID of the blog.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value.
 * @param bool   $unique     Optional. Whether to enforce a single metadata value
 *                           for the given key. If true, and the object already has a value for
 *                           the key, no change will be made. Default: false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bp_blogs_add_blogmeta( $blog_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'blog', $blog_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}
/**
 * Remove all blog associations for a given user.
 *
 * @param int $user_id ID whose blog data should be removed.
 * @return bool Returns false on failure.
 */
function bp_blogs_remove_data( $user_id ) {
	if ( !is_multisite() )
		return false;

	/**
	 * Fires before all blog associations are removed for a given user.
	 *
	 * @since 1.5.0
	 *
	 * @param int $user_id ID of the user whose blog associations are being removed.
	 */
	do_action( 'bp_blogs_before_remove_data', $user_id );

	// If this is regular blog, delete all data for that blog.
	BP_Blogs_Blog::delete_blogs_for_user( $user_id );

	/**
	 * Fires after all blog associations are removed for a given user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id ID of the user whose blog associations were removed.
	 */
	do_action( 'bp_blogs_remove_data', $user_id );
}
add_action( 'wpmu_delete_user',  'bp_blogs_remove_data' );
add_action( 'delete_user',       'bp_blogs_remove_data' );
add_action( 'bp_make_spam_user', 'bp_blogs_remove_data' );

/**
 * Restore all blog associations for a given user.
 *
 * @since 2.2.0
 *
 * @param int $user_id ID whose blog data should be restored.
 */
function bp_blogs_restore_data( $user_id = 0 ) {
	if ( ! is_multisite() ) {
		return;
	}

	// Get the user's blogs.
	$user_blogs = get_blogs_of_user( $user_id );
	if ( empty( $user_blogs ) ) {
		return;
	}

	$blogs = array_keys( $user_blogs );

	foreach ( $blogs as $blog_id ) {
		bp_blogs_add_user_to_blog( $user_id, false, $blog_id );
	}
}
add_action( 'bp_make_ham_user', 'bp_blogs_restore_data', 10, 1 );
