<?php
/**
 * Blogs component functions.
 *
 * @package BuddyPress
 * @subpackage BlogsFunctions
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Check whether the $bp global lists an activity directory page.
 *
 * @since BuddyPress (1.5.0)
 *
 * @global BuddyPress $bp The one true BuddyPress instance.
 *
 * @return bool True if set, false if empty.
 */
function bp_blogs_has_directory() {
	global $bp;

	return (bool) !empty( $bp->pages->blogs->id );
}

/**
 * Retrieve a set of blogs
 *
 * @see BP_Blogs_Blog::get() for a description of arguments and return value.
 *
 * @param array $args {
 *     Arguments are listed here with their default values. For more
 *     information about the arguments, see {@link BP_Blogs_Blog::get()}.
 *     @type string $type Default: 'active'.
 *     @type int|bool $user_id Default: false.
 *     @type array $include_blog_ids Default: false.
 *     @type string|bool $search_terms Default: false.
 *     @type int $per_page Default: 20.
 *     @type int $page Default: 1.
 *     @type bool $update_meta_cache Whether to pre-fetch blogmeta. Default: true.
 * }
 * @return array See {@link BP_Blogs_Blog::get()}.
 */
function bp_blogs_get_blogs( $args = '' ) {

	// Parse query arguments
	$r = bp_parse_args( $args, array(
		'type'              => 'active', // 'active', 'alphabetical', 'newest', or 'random'
		'include_blog_ids'  => false,    // Array of blog IDs to include
		'user_id'           => false,    // Limit to blogs this user can post to
		'search_terms'      => false,    // Limit to blogs matching these search terms
		'per_page'          => 20,       // The number of results to return per page
		'page'              => 1,        // The page to return if limiting per page
		'update_meta_cache' => true      // Whether to pre-fetch blogmeta
	), 'blogs_get_blogs' );

	// Get the blogs
	$blogs = BP_Blogs_Blog::get(
		$r['type'],
		$r['per_page'],
		$r['page'],
		$r['user_id'],
		$r['search_terms'],
		$r['update_meta_cache'],
		$r['include_blog_ids']
	);

	// Filter and return
	return apply_filters( 'bp_blogs_get_blogs', $blogs, $r );
}

/**
 * Populate the BP blogs table with existing blogs.
 *
 * @since BuddyPress (1.0.0)
 *
 * @global object $wpdb WordPress database object
 * @uses get_users()
 * @uses bp_blogs_record_blog()
 */
function bp_blogs_record_existing_blogs() {
	global $wpdb;

	// Query for all sites in network
	if ( is_multisite() ) {

		// Get blog ID's if not a large network
		if ( ! wp_is_large_network() ) {
			$blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM {$wpdb->base_prefix}blogs WHERE mature = 0 AND spam = 0 AND deleted = 0 AND site_id = %d", $wpdb->siteid ) );

			// If error running this query, set blog ID's to false
			if ( is_wp_error( $blog_ids ) ) {
				$blog_ids = false;
			}

		// Large networks are not currently supported
		} else {
			$blog_ids = false;
		}

	// Record a single site
	} else {
		$blog_ids = $wpdb->blogid;
	}

	// Bail if there are no blogs in the network
	if ( empty( $blog_ids ) ) {
		return false;
	}

	// Get BuddyPress
	$bp = buddypress();

	// Truncate user blogs table
	$truncate = $wpdb->query( "TRUNCATE {$bp->blogs->table_name}" );
	if ( is_wp_error( $truncate ) ) {
		return false;
	}

	// Truncate user blogsmeta table
	$truncate = $wpdb->query( "TRUNCATE {$bp->blogs->table_name_blogmeta}" );
	if ( is_wp_error( $truncate ) ) {
		return false;
	}

	// Loop through users of blogs and record the relationship
	foreach ( (array) $blog_ids as $blog_id ) {

		// Ensure that the cache is clear after the table TRUNCATE above
		wp_cache_delete( $blog_id, 'blog_meta' );

		// Get all users
		$users = get_users( array(
			'blog_id' => $blog_id
		) );

		// Continue on if no users exist for this site (how did this happen?)
		if ( empty( $users ) ) {
			continue;
		}

		// Loop through users and record their relationship to this blog
		foreach ( (array) $users as $user ) {
			bp_blogs_add_user_to_blog( $user->ID, false, $blog_id );
		}
	}

	// No errors
	return true;
}

/**
 * Check whether a given blog should be recorded in activity streams.
 *
 * If $user_id is provided, you can restrict site from being recordable
 * only to particular users.
 *
 * @since BuddyPress (1.7.0)
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
 * @since BuddyPress (1.7.0)
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
 * @since BuddyPress (1.0.0)
 *
 * @uses BP_Blogs_Blog
 *
 * @param int $blog_id ID of the blog being recorded.
 * @param int $user_id ID of the user for whom the blog is being recorded.
 * @param bool $no_activity Optional. Whether to skip recording an activity
 *        item about this blog creation. Default: false.
 * @return bool|null Returns false on failure.
 */
function bp_blogs_record_blog( $blog_id, $user_id, $no_activity = false ) {

	if ( empty( $user_id ) )
		$user_id = bp_loggedin_user_id();

	// If blog is not recordable, do not record the activity.
	if ( !bp_blogs_is_blog_recordable( $blog_id, $user_id ) )
		return false;

	$name = get_blog_option( $blog_id, 'blogname' );

	if ( empty( $name ) ) {
		return false;
	}

	$url             = get_home_url( $blog_id );
	$description     = get_blog_option( $blog_id, 'blogdescription' );
	$close_old_posts = get_blog_option( $blog_id, 'close_comments_for_old_posts' );
	$close_days_old  = get_blog_option( $blog_id, 'close_comments_days_old' );

	$thread_depth = get_blog_option( $blog_id, 'thread_comments' );
	if ( ! empty( $thread_depth ) ) {
		$thread_depth = get_blog_option( $blog_id, 'thread_comments_depth' );
	} else {
		// perhaps filter this?
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

	// Only record this activity if the blog is public
	if ( !$is_private && !$no_activity && bp_blogs_is_blog_trackable( $blog_id, $user_id ) ) {

		// Record this in activity streams
		bp_blogs_record_activity( array(
			'user_id'      => $recorded_blog->user_id,
			'primary_link' => apply_filters( 'bp_blogs_activity_created_blog_primary_link', $url, $recorded_blog->blog_id ),
			'type'         => 'new_blog',
			'item_id'      => $recorded_blog->blog_id
		) );
	}

	do_action_ref_array( 'bp_blogs_new_blog', array( &$recorded_blog, $is_private, $is_recorded ) );
}
add_action( 'wpmu_new_blog', 'bp_blogs_record_blog', 10, 2 );

/**
 * Update blog name in BuddyPress blogmeta table.
 *
 * @global object $wpdb DB Layer.
 *
 * @param string $oldvalue Value before save. Passed by do_action() but
 *        unused here.
 * @param string $newvalue Value to change meta to.
 */
function bp_blogs_update_option_blogname( $oldvalue, $newvalue ) {
	global $wpdb;

	bp_blogs_update_blogmeta( $wpdb->blogid, 'name', $newvalue );
}
add_action( 'update_option_blogname', 'bp_blogs_update_option_blogname', 10, 2 );

/**
 * Update blog description in BuddyPress blogmeta table
 *
 * @global object $wpdb DB Layer.
 *
 * @param string $oldvalue Value before save. Passed by do_action() but
 *        unused here.
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
 * @since BuddyPress (2.0.0)
 *
 * @global object $wpdb DB Layer.
 *
 * @param string $oldvalue Value before save. Passed by do_action() but
 *        unused here.
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
 * @since BuddyPress (2.0.0)
 *
 * @global object $wpdb DB Layer.
 *
 * @param string $oldvalue Value before save. Passed by do_action() but
 *        unused here.
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
 * @since BuddyPress (2.0.0)
 *
 * @global object $wpdb DB Layer.
 *
 * @param string $oldvalue Value before save. Passed by do_action() but
 *        unused here.
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
 * @since BuddyPress (2.0.0)
 *
 * @global object $wpdb DB Layer.
 *
 * @param string $oldvalue Value before save. Passed by do_action() but
 *        unused here.
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
 * Detect a change in post status, and initiate an activity update if necessary.
 *
 * Posts get new activity updates when (a) they are being published, and (b)
 * they have not already been published. This enables proper posting for
 * regular posts as well as scheduled posts, while preventing post bumping.
 *
 * See #4090, #3746, #2546 for background.
 *
 * @since BuddyPress (2.0.0)
 *
 * @todo Support untrashing better
 *
 * @param string $new_status New status for the post.
 * @param string $old_status Old status for the post.
 * @param object $post Post data.
 */
function bp_blogs_catch_transition_post_status( $new_status, $old_status, $post ) {

	// This is an edit
	if ( $new_status === $old_status ) {
		if ( $new_status == 'publish' ) {
			bp_blogs_update_post( $post );
			return;
		}
	}

	// Publishing a previously unpublished post
	if ( 'publish' === $new_status ) {
		// Untrashing the post
		// Nothing here yet
		if ( 'trash' == $old_status ) {}

		// Record the post
		bp_blogs_record_post( $post->ID, $post );

	// Unpublishing a previously published post
	} else if ( 'publish' === $old_status ) {
		// Some form of pending status
		// Only remove the activity entry
		bp_blogs_delete_activity( array(
			'item_id'           => get_current_blog_id(),
			'secondary_item_id' => $post->ID,
			'component'         => buddypress()->blogs->id,
			'type'              => 'new_blog_post'
		) );
	}
}
add_action( 'transition_post_status', 'bp_blogs_catch_transition_post_status', 10, 3 );

/**
 * Record a new blog post in the BuddyPress activity stream.
 *
 * @param int $post_id ID of the post being recorded.
 * @param object $post The WP post object passed to the 'save_post' action.
 * @param int $user_id Optional. The user to whom the activity item will be
 *        associated. Defaults to the post_author.
 * @return bool|null Returns false on failure.
 */
function bp_blogs_record_post( $post_id, $post, $user_id = 0 ) {
	global $bp, $wpdb;

	$post_id = (int) $post_id;
	$blog_id = (int) $wpdb->blogid;

	// If blog is not trackable, do not record the activity.
	if ( ! bp_blogs_is_blog_trackable( $blog_id, $user_id ) )
		return false;

	if ( !$user_id )
		$user_id = (int) $post->post_author;

	// Stop infinite loops with WordPress MU Sitewide Tags.
	// That plugin changed the way its settings were stored at some point. Thus the dual check.
	if ( !empty( $bp->site_options['sitewide_tags_blog'] ) ) {
		$st_options = maybe_unserialize( $bp->site_options['sitewide_tags_blog'] );
		$tags_blog_id = isset( $st_options['tags_blog_id'] ) ? $st_options['tags_blog_id'] : 0;
	} else {
		$tags_blog_id = isset( $bp->site_options['tags_blog_id'] ) ? $bp->site_options['tags_blog_id'] : 0;
	}

	if ( (int) $blog_id == $tags_blog_id && apply_filters( 'bp_blogs_block_sitewide_tags_activity', true ) )
		return false;

	// Don't record this if it's not a post
	if ( !in_array( $post->post_type, apply_filters( 'bp_blogs_record_post_post_types', array( 'post' ) ) ) )
		return false;

	$is_blog_public = apply_filters( 'bp_is_blog_public', (int)get_blog_option( $blog_id, 'blog_public' ) );

	if ( 'publish' == $post->post_status && empty( $post->post_password ) ) {
		if ( $is_blog_public || !is_multisite() ) {

			// Record this in activity streams
			$post_permalink = add_query_arg(
				'p',
				$post_id,
				trailingslashit( get_home_url( $blog_id ) )
			);

			if ( is_multisite() )
				$activity_action  = sprintf( __( '%1$s wrote a new post, %2$s, on the site %3$s', 'buddypress' ), bp_core_get_userlink( (int) $post->post_author ), '<a href="' . $post_permalink . '">' . $post->post_title . '</a>', '<a href="' . get_blog_option( $blog_id, 'home' ) . '">' . get_blog_option( $blog_id, 'blogname' ) . '</a>' );
			else
				$activity_action  = sprintf( __( '%1$s wrote a new post, %2$s', 'buddypress' ), bp_core_get_userlink( (int) $post->post_author ), '<a href="' . $post_permalink . '">' . $post->post_title . '</a>' );

			// Make sure there's not an existing entry for this post (prevent bumping)
			if ( bp_is_active( 'activity' ) ) {
				$existing = bp_activity_get( array(
					'filter' => array(
						'action'       => 'new_blog_post',
						'primary_id'   => $blog_id,
						'secondary_id' => $post_id,
					)
				) );

				if ( !empty( $existing['activities'] ) ) {
					return;
				}
			}

			$activity_content = $post->post_content;

			$activity_id = bp_blogs_record_activity( array(
				'user_id'           => (int) $post->post_author,
				'content'           => apply_filters( 'bp_blogs_activity_new_post_content',      $activity_content, $post, $post_permalink ),
				'primary_link'      => apply_filters( 'bp_blogs_activity_new_post_primary_link', $post_permalink,   $post_id               ),
				'type'              => 'new_blog_post',
				'item_id'           => $blog_id,
				'secondary_item_id' => $post_id,
				'recorded_time'     => $post->post_date_gmt,
			) );

			// save post title in activity meta
			if ( bp_is_active( 'activity' ) ) {
				bp_activity_update_meta( $activity_id, 'post_title', $post->post_title );
				bp_activity_update_meta( $activity_id, 'post_url',   $post_permalink );
			}
		}

		// Update the blogs last activity
		bp_blogs_update_blogmeta( $blog_id, 'last_activity', bp_core_current_time() );
	} else {
		bp_blogs_remove_post( $post_id, $blog_id, $user_id );
	}

	do_action( 'bp_blogs_new_blog_post', $post_id, $post, $user_id );
}

/**
 * Updates a blog post's corresponding activity entry during a post edit.
 *
 * @since BuddyPress (2.0.0)
 *
 * @see bp_blogs_catch_transition_post_status()
 *
 * @param WP_Post $post
 */
function bp_blogs_update_post( $post ) {
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	$activity_id = bp_activity_get_activity_id( array(
		'component'         => buddypress()->blogs->id,
		'item_id'           => get_current_blog_id(),
		'secondary_item_id' => $post->ID,
		'type'              => 'new_blog_post',
	 ) );

	// activity ID doesn't exist, so stop!
	if ( empty( $activity_id ) ) {
		return;
	}

	// update the activity entry
	$activity = new BP_Activity_Activity( $activity_id );

	if ( ! empty( $post->post_content ) ) {
		// Make sure to update the thumbnail image
		$post_content = bp_activity_thumbnail_content_images( $post->post_content, $activity->primary_link, (array) $activity );

		// Make sure to apply the blop post excerpt
		$activity->content = apply_filters( 'bp_blogs_record_activity_content', bp_create_excerpt( $post_content ), $post_content, (array) $activity );
	}

	// Save the updated activity
	$activity->save();

	// update post title in activity meta
	$existing_title = bp_activity_get_meta( $activity_id, 'post_title' );
	if ( $post->post_title !== $existing_title ) {
		bp_activity_update_meta( $activity_id, 'post_title', $post->post_title );

		// now update activity meta for post comments... sigh
		add_filter( 'comments_clauses', 'bp_blogs_comments_clauses_select_by_id' );
		$comments = get_comments( array( 'post_id' => $post->ID ) );
		remove_filter( 'comments_clauses', 'bp_blogs_comments_clauses_select_by_id' );

		if ( ! empty( $comments ) ) {
			$activity_ids = array();
			$comment_ids  = wp_list_pluck( $comments, 'comment_ID' );

			// setup activity args
			$args = array(
				'update_meta_cache' => false,
				'show_hidden'       => true,
				'per_page'          => 99999,
			);

			// query for old-style "new_blog_comment" activity items
			$args['filter'] = array(
				'object'       => buddypress()->blogs->id,
				'action'       => 'new_blog_comment',
				'secondary_id' => implode( ',', $comment_ids ),
			);

			$activities = bp_activity_get( $args );
			if ( ! empty( $activities['activities'] ) ) {
				$activity_ids = (array) wp_list_pluck( $activities['activities'], 'id' );
			}

			// query for activity comments connected to a blog post
			unset( $args['filter'] );
			$args['meta_query'] = array( array(
				'key'     => 'bp_blogs_post_comment_id',
				'value'   => $comment_ids,
				'compare' => 'IN',
			) );
			$args['type'] = 'activity_comment';
			$args['display_comments'] = 'stream';

			$activities = bp_activity_get( $args );
			if ( ! empty( $activities['activities'] ) ) {
				$activity_ids = array_merge( $activity_ids, (array) wp_list_pluck( $activities['activities'], 'id' ) );
			}

			// update activity meta for all found activity items
			if ( ! empty( $activity_ids ) ) {
				foreach ( $activity_ids as $aid ) {
					bp_activity_update_meta( $aid, 'post_title', $post->post_title );
				}
			}

			unset( $activities, $activity_ids, $comment_ids, $comments );
		}
	}

	// add post comment status to activity meta if closed
	if( 'closed' == $post->comment_status ) {
		bp_activity_update_meta( $activity_id, 'post_comment_status', $post->comment_status );
	} else {
		bp_activity_delete_meta( $activity_id, 'post_comment_status' );
	}
}

/**
 * Record a new blog comment in the BuddyPress activity stream.
 *
 * Only posts the item if blog is public and post is not password-protected.
 *
 * @param int $comment_id ID of the comment being recorded.
 * @param bool|string $is_approved Optional. The $is_approved value passed to
 *        the 'comment_post' action. Default: true.
 * @return bool|object Returns false on failure, the comment object on success.
 */
function bp_blogs_record_comment( $comment_id, $is_approved = true ) {
	// bail if activity component is not active
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	// Get the users comment
	$recorded_comment = get_comment( $comment_id );

	// Don't record activity if the comment hasn't been approved
	if ( empty( $is_approved ) )
		return false;

	// Don't record activity if no email address has been included
	if ( empty( $recorded_comment->comment_author_email ) )
		return false;

	// Don't record activity if the comment has already been marked as spam
	if ( 'spam' === $is_approved )
		return false;

	// Get the user by the comment author email.
	$user = get_user_by( 'email', $recorded_comment->comment_author_email );

	// If user isn't registered, don't record activity
	if ( empty( $user ) )
		return false;

	// Get the user_id
	$user_id = (int) $user->ID;

	// Get blog and post data
	$blog_id = get_current_blog_id();

	// If blog is not trackable, do not record the activity.
	if ( ! bp_blogs_is_blog_trackable( $blog_id, $user_id ) )
		return false;

	$recorded_comment->post = get_post( $recorded_comment->comment_post_ID );

	if ( empty( $recorded_comment->post ) || is_wp_error( $recorded_comment->post ) )
		return false;

	// If this is a password protected post, don't record the comment
	if ( !empty( $recorded_comment->post->post_password ) )
		return false;

	// Don't record activity if the comment's associated post isn't a WordPress Post
	if ( !in_array( $recorded_comment->post->post_type, apply_filters( 'bp_blogs_record_comment_post_types', array( 'post' ) ) ) )
		return false;

	$is_blog_public = apply_filters( 'bp_is_blog_public', (int)get_blog_option( $blog_id, 'blog_public' ) );

	// If blog is public allow activity to be posted
	if ( $is_blog_public ) {

		// Get activity related links
		$post_permalink = get_permalink( $recorded_comment->comment_post_ID );
		$comment_link   = get_comment_link( $recorded_comment->comment_ID );

		// Setup activity args
		$args = array();

		$args['user_id']       = $user_id;
		$args['content']       = apply_filters_ref_array( 'bp_blogs_activity_new_comment_content', array( $recorded_comment->comment_content, &$recorded_comment, $comment_link ) );
		$args['primary_link']  = apply_filters_ref_array( 'bp_blogs_activity_new_comment_primary_link', array( $comment_link,     &$recorded_comment ) );
		$args['recorded_time'] = $recorded_comment->comment_date_gmt;

		// Setup some different activity args depending if activity commenting is
		// enabled or not

		// if cannot comment, record separate activity entry
		// this is the old way of doing things
		if ( bp_disable_blogforum_comments() ) {
			$args['type']              = 'new_blog_comment';
			$args['item_id']           = $blog_id;
			$args['secondary_item_id'] = $comment_id;

			// record the activity entry
			$activity_id = bp_blogs_record_activity( $args );

			// add some post info in activity meta
			bp_activity_update_meta( $activity_id, 'post_title', $recorded_comment->post->post_title );
			bp_activity_update_meta( $activity_id, 'post_url',   add_query_arg( 'p', $recorded_comment->post->ID, home_url( '/' ) ) );

		// record comment as BP activity comment under the parent 'new_blog_post'
		// activity item
		} else {
			// this is a comment edit
			// check to see if corresponding activity entry already exists
			if ( ! empty( $_REQUEST['action'] ) ) {
				$existing_activity_id = get_comment_meta( $comment_id, 'bp_activity_comment_id', true );

				if ( ! empty( $existing_activity_id ) ) {
					$args['id'] = $existing_activity_id;
				}
			}

			// find the parent 'new_blog_post' activity entry
			$parent_activity_id = bp_activity_get_activity_id( array(
				'component'         => 'blogs',
				'type'              => 'new_blog_post',
				'item_id'           => $blog_id,
				'secondary_item_id' => $recorded_comment->comment_post_ID
			) );

			// we found the parent activity entry
			// so let's go ahead and reconfigure some activity args
			if ( ! empty( $parent_activity_id ) ) {
				// set the 'item_id' with the parent activity entry ID
				$args['item_id'] = $parent_activity_id;

				// now see if the WP parent comment has a BP activity ID
				$comment_parent = 0;
				if ( ! empty( $recorded_comment->comment_parent ) ) {
					$comment_parent = get_comment_meta( $recorded_comment->comment_parent, 'bp_activity_comment_id', true );
				}

				// WP parent comment does not have a BP activity ID
				// so set to 'new_blog_post' activity ID
				if ( empty( $comment_parent ) ) {
					$comment_parent = $parent_activity_id;
				}

				$args['secondary_item_id'] = $comment_parent;
				$args['component']         = 'activity';
				$args['type']              = 'activity_comment';

			// could not find corresponding parent activity entry
			// so wipe out $args array
			} else {
				$args = array();
			}

			// Record in activity streams
			if ( ! empty( $args ) ) {
				// @todo should we use bp_activity_new_comment()? that function will also send
				// an email to people in the activity comment thread
				//
				// what if a site already has some comment email notification plugin setup?
				// this is why I decided to go with bp_activity_add() to avoid any conflict
				// with existing comment email notification plugins
				$comment_activity_id = bp_activity_add( $args );

				if ( empty( $args['id'] ) ) {
					// add meta to activity comment
					bp_activity_update_meta( $comment_activity_id, 'bp_blogs_post_comment_id', $comment_id );
					bp_activity_update_meta( $comment_activity_id, 'post_title', $recorded_comment->post->post_title );
					bp_activity_update_meta( $comment_activity_id, 'post_url', add_query_arg( 'p', $recorded_comment->post->ID, home_url( '/' ) ) );

					// add meta to comment
					add_comment_meta( $comment_id, 'bp_activity_comment_id', $comment_activity_id );
				}
			}
		}

		// Update the blogs last active date
		bp_blogs_update_blogmeta( $blog_id, 'last_activity', bp_core_current_time() );
	}

	return $recorded_comment;
}
add_action( 'comment_post', 'bp_blogs_record_comment', 10, 2 );
add_action( 'edit_comment', 'bp_blogs_record_comment', 10    );

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
 * @param int         $user_id The ID of the user
 * @param string|bool $role    User's WordPress role for this blog ID
 * @param int         $blog_id Blog ID user is being added to
 *
 * @return bool|null False on failure.
 */
function bp_blogs_add_user_to_blog( $user_id, $role = false, $blog_id = 0 ) {
	global $wpdb;

	// If no blog ID was passed, use the root blog ID
	if ( empty( $blog_id ) ) {
		$blog_id = isset( $wpdb->blogid ) ? $wpdb->blogid : bp_get_root_blog_id();
	}

	// If no role was passed, try to find the blog role
	if ( empty( $role ) ) {

		// Get user capabilities
		$key        = $wpdb->get_blog_prefix( $blog_id ). 'capabilities';
		$user_roles = array_keys( (array) bp_get_user_meta( $user_id, $key, true ) );

		// User has roles so lets
		if ( ! empty( $user_roles ) ) {

			// Get blog roles
			$blog_roles      = array_keys( bp_get_current_blog_roles() );

			// Look for blog only roles of the user
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

	// Bail if no role was found or role is not in the allowed roles array
	if ( empty( $role ) || ! in_array( $role, bp_blogs_get_allowed_roles() ) ) {
		return false;
	}

	// Record the blog activity for this user being added to this blog
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
 * @since BuddyPress (2.1.0)
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
 * Rehook WP's maybe_add_existing_user_to_blog with a later priority
 *
 * WordPress catches add-user-to-blog requests at init:10. In some cases, this
 * can precede BP's Blogs component. This function bumps the priority of the
 * core function, so that we can be sure that the Blogs component is loaded
 * first. See http://buddypress.trac.wordpress.org/ticket/3916.
 *
 * @since BuddyPress (1.6)
 * @access private
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
	global $bp;

	$blog_id = (int) $blog_id;
	do_action( 'bp_blogs_before_remove_blog', $blog_id );

	BP_Blogs_Blog::delete_blog_for_all( $blog_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'component' => $bp->blogs->id, 'type' => 'new_blog' ) );

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
	global $bp;

	$blog_id = (int) $blog_id;
	$user_id = (int) $user_id;

	do_action( 'bp_blogs_before_remove_blog_for_user', $blog_id, $user_id );

	BP_Blogs_Blog::delete_blog_for_user( $blog_id, $user_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array(
		'item_id'   => $blog_id,
		'component' => $bp->blogs->id,
		'type'      => 'new_blog'
	) );

	do_action( 'bp_blogs_remove_blog_for_user', $blog_id, $user_id );
}
add_action( 'remove_user_from_blog', 'bp_blogs_remove_blog_for_user', 10, 2 );

/**
 * Remove a blog post activity item from the activity stream.
 *
 * @param int $post_id ID of the post to be removed.
 * @param int $blog_id Optional. Defaults to current blog ID.
 * @param int $user_id Optional. Defaults to the logged-in user ID. This param
 *        is currently unused in the function (but is passed to hooks).
 */
function bp_blogs_remove_post( $post_id, $blog_id = 0, $user_id = 0 ) {
	global $wpdb, $bp;

	if ( empty( $wpdb->blogid ) )
		return false;

	$post_id = (int) $post_id;

	if ( !$blog_id )
		$blog_id = (int) $wpdb->blogid;

	if ( !$user_id )
		$user_id = bp_loggedin_user_id();

	do_action( 'bp_blogs_before_remove_post', $blog_id, $post_id, $user_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'secondary_item_id' => $post_id, 'component' => $bp->blogs->id, 'type' => 'new_blog_post' ) );

	do_action( 'bp_blogs_remove_post', $blog_id, $post_id, $user_id );
}
add_action( 'delete_post', 'bp_blogs_remove_post' );

/**
 * Remove a blog comment activity item from the activity stream.
 *
 * @param int $comment_id ID of the comment to be removed.
 */
function bp_blogs_remove_comment( $comment_id ) {
	global $wpdb;

	// activity comments are disabled for blog posts
	// which means that individual activity items exist for blog comments
	if ( bp_disable_blogforum_comments() ) {
		// Delete the individual activity stream item
		bp_blogs_delete_activity( array(
			'item_id'           => $wpdb->blogid,
			'secondary_item_id' => $comment_id,
			'type'              => 'new_blog_comment'
		) );

	// activity comments are enabled for blog posts
	// remove the associated activity item
	} else {
		// get associated activity ID from comment meta
		$activity_id = get_comment_meta( $comment_id, 'bp_activity_comment_id', true );

		// delete the associated activity comment
		//
		// also removes child post comments and associated activity comments
		if ( ! empty( $activity_id ) && bp_is_active( 'activity' ) ) {
			// fetch the activity comments for the activity item
			$activity = bp_activity_get( array(
				'in'               => $activity_id,
				'display_comments' => 'stream',
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
			}
		}
	}

	do_action( 'bp_blogs_remove_comment', $wpdb->blogid, $comment_id, bp_loggedin_user_id() );
}
add_action( 'delete_comment', 'bp_blogs_remove_comment' );

/**
 * Removes blog comments that are associated with activity comments.
 *
 * @since BuddyPress (2.0.0)
 *
 * @see bp_blogs_remove_comment()
 * @see bp_blogs_sync_delete_from_activity_comment()
 *
 * @param array $activity_ids The activity IDs to check association with blog
 *              comments.
 * @param bool $force_delete Whether to force delete the comments. If false,
 *             comments are trashed instead.
 */
function bp_blogs_remove_associated_blog_comments( $activity_ids = array(), $force_delete = true ) {
	// query args
	$query_args = array(
		'meta_query' => array(
			array(
				'key'     => 'bp_activity_comment_id',
				'value'   => implode( ',', (array) $activity_ids ),
				'compare' => 'IN',
			)
		)
	);

	// get comment
	$comment_query = new WP_Comment_Query;
	$comments = $comment_query->query( $query_args );

	// found the corresponding comments
	// let's delete them!
	foreach ( $comments as $comment ) {
		wp_delete_comment( $comment->comment_ID, $force_delete );

		// if we're trashing the comment, remove the meta key as well
		if ( empty( $force_delete ) ) {
			delete_comment_meta( $comment->comment_ID, 'bp_activity_comment_id' );
		}
	}
}

/**
 * When a blog comment status transition occurs, update the relevant activity's status.
 *
 * @since BuddyPress (1.6.0)
 *
 * @global object $bp BuddyPress global settings.
 *
 * @param string $new_status New comment status.
 * @param string $old_status Previous comment status.
 * @param object $comment Comment data.
 */
function bp_blogs_transition_activity_status( $new_status, $old_status, $comment ) {
	global $bp;

	// Check the Activity component is active
	if ( ! bp_is_active( 'activity' ) )
		return;

	/**
	 * Activity currently doesn't have any concept of a trash, or an unapproved/approved state.
	 *
	 * If a blog comment transitions to a "delete" or "hold" status, delete the activity item.
	 * If a blog comment transitions to trashed, or spammed, mark the activity as spam.
	 * If a blog comment transitions to approved (and the activity exists), mark the activity as ham.
	 * If a blog comment transitions to unapproved (and the activity exists), mark the activity as spam.
	 * Otherwise, record the comment into the activity stream.
	 */

	// This clause was moved in from bp_blogs_remove_comment() in BuddyPress 1.6. It handles delete/hold.
	if ( in_array( $new_status, array( 'delete', 'hold' ) ) ) {
		return bp_blogs_remove_comment( $comment->comment_ID );

	// These clauses handle trash, spam, and un-spams.
	} elseif ( in_array( $new_status, array( 'trash', 'spam', 'unapproved' ) ) ) {
		$action = 'spam_activity';
	} elseif ( 'approved' == $new_status ) {
		$action = 'ham_activity';
	}

	// Get the activity
	if ( bp_disable_blogforum_comments() ) {
		$activity_id = bp_activity_get_activity_id( array( 'component' => $bp->blogs->id, 'item_id' => get_current_blog_id(), 'secondary_item_id' => $comment->comment_ID, 'type' => 'new_blog_comment', ) );
	} else {
		$activity_id = get_comment_meta( $comment->comment_ID, 'bp_activity_comment_id', true );
	}

	// Check activity item exists
	if ( empty( $activity_id ) ) {
		// If no activity exists, but the comment has been approved, record it into the activity table.
		if ( 'approved' == $new_status ) {
			return bp_blogs_record_comment( $comment->comment_ID, true );
		}

		return;
	}

	// Create an activity object
	$activity = new BP_Activity_Activity( $activity_id );
	if ( empty( $activity->component ) )
		return;

	// Spam/ham the activity if it's not already in that state
	if ( 'spam_activity' == $action && ! $activity->is_spam ) {
		bp_activity_mark_as_spam( $activity );
	} elseif ( 'ham_activity' == $action) {
		bp_activity_mark_as_ham( $activity );
	}

	// Add "new_blog_comment" to the whitelisted activity types, so that the activity's Akismet history is generated
	$comment_akismet_history = create_function( '$t', '$t[] = "new_blog_comment"; return $t;' );
	add_filter( 'bp_akismet_get_activity_types', $comment_akismet_history );

	// Save the updated activity
	$activity->save();

	// Remove the "new_blog_comment" activity type whitelist so we don't break anything
	remove_filter( 'bp_akismet_get_activity_types', $comment_akismet_history );
}
add_action( 'transition_comment_status', 'bp_blogs_transition_activity_status', 10, 3 );

/**
 * Get the total number of blogs being tracked by BuddyPress.
 *
 * @return int $count Total blog count.
 */
function bp_blogs_total_blogs() {
	if ( !$count = wp_cache_get( 'bp_total_blogs', 'bp' ) ) {
		$blogs = BP_Blogs_Blog::get_all();
		$count = $blogs['total'];
		wp_cache_set( 'bp_total_blogs', $count, 'bp' );
	}
	return $count;
}

/**
 * Get the total number of blogs being tracked by BP for a specific user.
 *
 * @since BuddyPress (1.2.0)
 *
 * @param int $user_id ID of the user being queried. Default: on a user page,
 *        the displayed user. Otherwise, the logged-in user.
 * @return int $count Total blog count for the user.
 */
function bp_blogs_total_blogs_for_user( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	// no user ID? do not attempt to look at cache
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
	global $bp;

	do_action( 'bp_blogs_before_remove_data_for_blog', $blog_id );

	// If this is regular blog, delete all data for that blog.
	BP_Blogs_Blog::delete_blog_for_all( $blog_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'component' => $bp->blogs->id, 'type' => false ) );

	do_action( 'bp_blogs_remove_data_for_blog', $blog_id );
}
add_action( 'delete_blog', 'bp_blogs_remove_data_for_blog', 1 );

/**
 * Get all of a user's blogs, as tracked by BuddyPress.
 *
 * @see BP_Blogs_Blog::get_blogs_for_user() for a description of parameters
 *      and return values.
 *
 * @param int $user_id See {@BP_Blogs_Blog::get_blogs_for_user()}.
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
 * @param int $limit See {@BP_Blogs_Blog::get_all()}.
 * @param int $page See {@BP_Blogs_Blog::get_all()}.
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
 * @param int $limit See {@BP_Blogs_Blog::get()}.
 * @param int $page See {@BP_Blogs_Blog::get()}.
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

/*******************************************************************************
 * Blog meta functions
 *
 * These functions are used to store specific blogmeta in one global table,
 * rather than in each blog's options table. Significantly speeds up global blog
 * queries. By default each blog's name, description and last updated time are
 * stored and synced here.
 */

/**
 * Delete a metadta from the DB for a blog.
 *
 * @global object $wpdb WordPress database access object.
 * @global object $bp BuddyPress global settings.
 *
 * @param int $blog_id ID of the blog whose metadata is being deleted.
 * @param string $meta_key Optional. The key of the metadata being deleted. If
 *        omitted, all BP metadata associated with the blog will be deleted.
 * @param string $meta_value Optional. If present, the metadata will only be
 *        deleted if the meta_value matches this parameter.
 * @param bool $delete_all Optional. If true, delete matching metadata entries
 * 	  for all objects, ignoring the specified blog_id. Otherwise, only
 * 	  delete matching metadata entries for the specified blog.
 * 	  Default: false.
 * @return bool True on success, false on failure.
 */
function bp_blogs_delete_blogmeta( $blog_id, $meta_key = false, $meta_value = false, $delete_all = false ) {
	global $wpdb, $bp;

	// Legacy - if no meta_key is passed, delete all for the blog_id
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
 * @since BuddyPress (1.2.0)
 *
 * @global object $wpdb WordPress database access object.
 * @global object $bp BuddyPress global settings.
 *
 * @param int $blog_id ID of the blog whose metadata is being requested.
 * @param string $meta_key Optional. If present, only the metadata matching
 *        that meta key will be returned. Otherwise, all metadata for the
 *        blog will be fetched.
 * @param bool $single Optional. If true, return only the first value of the
 *	  specified meta_key. This parameter has no effect if meta_key is not
 *	  specified. Default: true.
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
 * @global object $bp BuddyPress global settings.
 *
 * @param int $blog_id ID of the blog whose metadata is being updated.
 * @param string $meta_key Key of the metadata being updated.
 * @param mixed $meta_value Value to be set.
 * @param mixed $prev_value Optional. If specified, only update existing
 *        metadata entries with the specified value. Otherwise, update all
 *        entries.
 * @return bool|int Returns false on failure. On successful update of existing
 *         metadata, returns true. On successful creation of new metadata,
 *         returns the integer ID of the new metadata row.
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
 * @since BuddyPress (2.0.0)
 *
 * @param int $blog_id ID of the blog.
 * @param string $meta_key Metadata key.
 * @param mixed $meta_value Metadata value.
 * @param bool $unique Optional. Whether to enforce a single metadata value
 *        for the given key. If true, and the object already has a value for
 *        the key, no change will be made. Default: false.
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
 * @return bool|null Returns false on failure.
 */
function bp_blogs_remove_data( $user_id ) {
	if ( !is_multisite() )
		return false;

	do_action( 'bp_blogs_before_remove_data', $user_id );

	// If this is regular blog, delete all data for that blog.
	BP_Blogs_Blog::delete_blogs_for_user( $user_id );

	do_action( 'bp_blogs_remove_data', $user_id );
}
add_action( 'wpmu_delete_user',  'bp_blogs_remove_data' );
add_action( 'delete_user',       'bp_blogs_remove_data' );
add_action( 'bp_make_spam_user', 'bp_blogs_remove_data' );
