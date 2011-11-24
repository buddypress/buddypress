<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Checks $bp pages global and looks for directory page
 *
 * @since 1.5
 *
 * @global object $bp Global BuddyPress settings object
 * @return bool True if set, False if empty
 */
function bp_blogs_has_directory() {
	global $bp;

	return (bool) !empty( $bp->pages->blogs->id );
}

function bp_blogs_get_blogs( $args = '' ) {
	global $bp;

	$defaults = array(
		'type'         => 'active', // active, alphabetical, newest, or random
		'user_id'      => false,    // Pass a user_id to limit to only blogs that this user has privilages higher than subscriber on
		'search_terms' => false,    // Limit to blogs that match these search terms
		'per_page'     => 20,       // The number of results to return per page
		'page'         => 1,        // The page to return if limiting per page
	);

	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );

	return apply_filters( 'bp_blogs_get_blogs', BP_Blogs_Blog::get( $type, $per_page, $page, $user_id, $search_terms ), $params );
}

/**
 * Populates the BP blogs table with existing blogs.
 *
 * @package BuddyPress Blogs
 *
 * @global object $bp BuddyPress global settings
 * @global object $wpdb WordPress database object
 * @uses get_users()
 * @uses bp_blogs_record_blog()
 */
function bp_blogs_record_existing_blogs() {
	global $bp, $wpdb;

	// Truncate user blogs table and re-record.
	$wpdb->query( "TRUNCATE TABLE {$bp->blogs->table_name}" );

	if ( is_multisite() )
		$blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM {$wpdb->base_prefix}blogs WHERE mature = 0 AND spam = 0 AND deleted = 0" ) );
	else
		$blog_ids = 1;

	if ( $blog_ids ) {
		foreach( (array)$blog_ids as $blog_id ) {
			$users 		= get_users( array( 'blog_id' => $blog_id ) );
			$subscribers 	= get_users( array( 'blog_id' => $blog_id, 'role' => 'subscriber' ) );

			if ( !empty( $users ) ) {
				foreach ( (array)$users as $user ) {
					// Don't record blogs for subscribers
					if ( !in_array( $user, $subscribers ) )
						bp_blogs_record_blog( $blog_id, $user->ID, true );
				}
			}
		}
	}
}

/**
 * Makes BuddyPress aware of a new site so that it can track its activity.
 *
 * @global object $bp BuddyPress global settings
 * @param int $blog_id
 * @param int $user_id
 * @param $bool $no_activity ; optional.
 * @since 1.0
 * @uses BP_Blogs_Blog
 */
function bp_blogs_record_blog( $blog_id, $user_id, $no_activity = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	$name = get_blog_option( $blog_id, 'blogname' );
	$description = get_blog_option( $blog_id, 'blogdescription' );

	if ( empty( $name ) )
		return false;

	$recorded_blog          = new BP_Blogs_Blog;
	$recorded_blog->user_id = $user_id;
	$recorded_blog->blog_id = $blog_id;

	$recorded_blog_id = $recorded_blog->save();

	$is_recorded = !empty( $recorded_blog_id ) ? true : false;

	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'name', $name );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'description', $description );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'last_activity', bp_core_current_time() );

	$is_private = !empty( $_POST['blog_public'] ) && (int)$_POST['blog_public'] ? false : true;
	$is_private = !apply_filters( 'bp_is_new_blog_public', !$is_private );

	// Only record this activity if the blog is public
	if ( !$is_private && !$no_activity ) {
		// Record this in activity streams
		bp_blogs_record_activity( array(
			'user_id'      => $recorded_blog->user_id,
			'action'       => apply_filters( 'bp_blogs_activity_created_blog_action', sprintf( __( '%s created the site %s', 'buddypress'), bp_core_get_userlink( $recorded_blog->user_id ), '<a href="' . get_site_url( $recorded_blog->blog_id ) . '">' . esc_attr( $name ) . '</a>' ), $recorded_blog, $name, $description ),
			'primary_link' => apply_filters( 'bp_blogs_activity_created_blog_primary_link', get_site_url( $recorded_blog->blog_id ), $recorded_blog->blog_id ),
			'type'         => 'new_blog',
			'item_id'      => $recorded_blog->blog_id
		) );
	}

	do_action_ref_array( 'bp_blogs_new_blog', array( &$recorded_blog, $is_private, $is_recorded ) );
}
add_action( 'wpmu_new_blog', 'bp_blogs_record_blog', 10, 2 );

/**
 * Updates blogname in BuddyPress blogmeta table
 *
 * @global object $wpdb DB Layer
 * @param string $oldvalue Value before save (not used)
 * @param string $newvalue Value to change meta to
 */
function bp_blogs_update_option_blogname( $oldvalue, $newvalue ) {
	global $wpdb;

	bp_blogs_update_blogmeta( $wpdb->blogid, 'name', $newvalue );
}
add_action( 'update_option_blogname', 'bp_blogs_update_option_blogname', 10, 2 );

/**
 * Updates blogdescription in BuddyPress blogmeta table
 *
 * @global object $wpdb DB Layer
 * @param string $oldvalue Value before save (not used)
 * @param string $newvalue Value to change meta to
 */
function bp_blogs_update_option_blogdescription( $oldvalue, $newvalue ) {
	global $wpdb;

	bp_blogs_update_blogmeta( $wpdb->blogid, 'description', $newvalue );
}
add_action( 'update_option_blogdescription', 'bp_blogs_update_option_blogdescription', 10, 2 );

function bp_blogs_record_post( $post_id, $post, $user_id = 0 ) {
	global $bp, $wpdb;

	$post_id = (int)$post_id;
	$blog_id = (int)$wpdb->blogid;

	if ( !$user_id )
		$user_id = (int)$post->post_author;

	// Stop infinite loops with WordPress MU Sitewide Tags.
	// That plugin changed the way its settings were stored at some point. Thus the dual check.
	if ( !empty( $bp->site_options['sitewide_tags_blog'] ) ) {
		$st_options = maybe_unserialize( $bp->site_options['sitewide_tags_blog'] );
		$tags_blog_id = isset( $st_options['tags_blog_id'] ) ? $st_options['tags_blog_id'] : 0;
	} else {
		$tags_blog_id = isset( $bp->site_options['tags_blog_id'] ) ? $bp->site_options['tags_blog_id'] : 0;
	}

	if ( (int)$blog_id == $tags_blog_id && apply_filters( 'bp_blogs_block_sitewide_tags_activity', true ) )
		return false;

	// Don't record this if it's not a post
	if ( !in_array( $post->post_type, apply_filters( 'bp_blogs_record_post_post_types', array( 'post' ) ) ) )
		return false;

	$is_blog_public = apply_filters( 'bp_is_blog_public', (int)get_blog_option( $blog_id, 'blog_public' ) );

	if ( 'publish' == $post->post_status && empty( $post->post_password ) ) {
		if ( $is_blog_public || !is_multisite() ) {
			// Record this in activity streams
			$post_permalink   = get_permalink( $post_id );

			if ( is_multisite() )
				$activity_action  = sprintf( __( '%1$s wrote a new post, %2$s, on the site %3$s', 'buddypress' ), bp_core_get_userlink( (int)$post->post_author ), '<a href="' . $post_permalink . '">' . $post->post_title . '</a>', '<a href="' . get_blog_option( $blog_id, 'home' ) . '">' . get_blog_option( $blog_id, 'blogname' ) . '</a>' );
			else
				$activity_action  = sprintf( __( '%1$s wrote a new post, %2$s', 'buddypress' ), bp_core_get_userlink( (int)$post->post_author ), '<a href="' . $post_permalink . '">' . $post->post_title . '</a>' );

			$activity_content = $post->post_content;

			bp_blogs_record_activity( array(
				'user_id'           => (int)$post->post_author,
				'action'            => apply_filters( 'bp_blogs_activity_new_post_action',       $activity_action,  $post, $post_permalink ),
				'content'           => apply_filters( 'bp_blogs_activity_new_post_content',      $activity_content, $post, $post_permalink ),
				'primary_link'      => apply_filters( 'bp_blogs_activity_new_post_primary_link', $post_permalink,   $post_id               ),
				'type'              => 'new_blog_post',
				'item_id'           => $blog_id,
				'secondary_item_id' => $post_id,
				'recorded_time'     => $post->post_modified_gmt
			));
		}

		// Update the blogs last activity
		bp_blogs_update_blogmeta( $blog_id, 'last_activity', bp_core_current_time() );
	} else {
		bp_blogs_remove_post( $post_id, $blog_id, $user_id );
	}

	do_action( 'bp_blogs_new_blog_post', $post_id, $post, $user_id );
}
add_action( 'save_post', 'bp_blogs_record_post', 10, 2 );

/**
 * Record blog comment activity. Checks if blog is public and post is not
 * password protected.
 *
 * @global $bp $bp
 * @param int $comment_id
 * @param bool $is_approved
 * @return mixed
 */
function bp_blogs_record_comment( $comment_id, $is_approved = true ) {
	global $bp;

	// Get the users comment
	$recorded_comment = get_comment( $comment_id );

	// Don't record activity if the comment hasn't been approved
	if ( empty( $is_approved ) )
		return false;

	// Don't record activity if no email address has been included
	if ( empty( $recorded_comment->comment_author_email ) )
		return false;

	// Get the user_id from the comment author email.
	$user    = get_user_by_email( $recorded_comment->comment_author_email );
	$user_id = (int)$user->ID;

	// If there's no registered user id, don't record activity
	if ( empty( $user_id ) )
		return false;

	// Get blog and post data
	$blog_id                = get_current_blog_id();
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
		$comment_link   = htmlspecialchars( get_comment_link( $recorded_comment->comment_ID ) );

		// Prepare to record in activity streams
		if ( is_multisite() )
			$activity_action = sprintf( __( '%1$s commented on the post, %2$s, on the site %3$s', 'buddypress' ), bp_core_get_userlink( $user_id ), '<a href="' . $post_permalink . '">' . apply_filters( 'the_title', $recorded_comment->post->post_title ) . '</a>', '<a href="' . get_blog_option( $blog_id, 'home' ) . '">' . get_blog_option( $blog_id, 'blogname' ) . '</a>' );
		else
			$activity_action = sprintf( __( '%1$s commented on the post, %2$s', 'buddypress' ), bp_core_get_userlink( $user_id ), '<a href="' . $post_permalink . '">' . apply_filters( 'the_title', $recorded_comment->post->post_title ) . '</a>' );

		$activity_content	= $recorded_comment->comment_content;

		// Record in activity streams
		bp_blogs_record_activity( array(
			'user_id'           => $user_id,
			'action'            => apply_filters_ref_array( 'bp_blogs_activity_new_comment_action',       array( $activity_action,  &$recorded_comment, $comment_link ) ),
			'content'           => apply_filters_ref_array( 'bp_blogs_activity_new_comment_content',      array( $activity_content, &$recorded_comment, $comment_link ) ),
			'primary_link'      => apply_filters_ref_array( 'bp_blogs_activity_new_comment_primary_link', array( $comment_link,     &$recorded_comment                ) ),
			'type'              => 'new_blog_comment',
			'item_id'           => $blog_id,
			'secondary_item_id' => $comment_id,
			'recorded_time'     => $recorded_comment->comment_date_gmt
		) );

		// Update the blogs last active date
		bp_blogs_update_blogmeta( $blog_id, 'last_activity', bp_core_current_time() );
	}

	return $recorded_comment;
}
add_action( 'comment_post', 'bp_blogs_record_comment', 10, 2 );
add_action( 'edit_comment', 'bp_blogs_record_comment', 10    );

function bp_blogs_manage_comment( $comment_id, $comment_status ) {
	if ( 'spam' == $comment_status || 'hold' == $comment_status || 'delete' == $comment_status || 'trash' == $comment_status )
		return bp_blogs_remove_comment( $comment_id );

	return bp_blogs_record_comment( $comment_id, true );
}
add_action( 'wp_set_comment_status', 'bp_blogs_manage_comment', 10, 2 );

function bp_blogs_add_user_to_blog( $user_id, $role = false, $blog_id = 0 ) {
	global $wpdb;
	
	if ( empty( $blog_id ) ) {
		$blog_id = isset( $wpdb->blogid ) ? $wpdb->blogid : bp_get_root_blog_id();
	}

	if ( empty( $role ) ) {
		$key = $wpdb->get_blog_prefix( $blog_id ). 'capabilities';

		$roles = get_user_meta( $user_id, $key, true );

		if ( is_array( $roles ) )
			$role = array_search( 1, $roles );
		else
			return false;
	}

	if ( $role != 'subscriber' )
		bp_blogs_record_blog( $blog_id, $user_id, true );
}
add_action( 'add_user_to_blog', 'bp_blogs_add_user_to_blog', 10, 3 );
add_action( 'profile_update',   'bp_blogs_add_user_to_blog'        );
add_action( 'user_register',    'bp_blogs_add_user_to_blog'        );

function bp_blogs_remove_user_from_blog( $user_id, $blog_id = 0 ) {
	global $wpdb;

	if ( empty( $blog_id ) )
		$blog_id = $wpdb->blogid;

	bp_blogs_remove_blog_for_user( $user_id, $blog_id );
}
add_action( 'remove_user_from_blog', 'bp_blogs_remove_user_from_blog', 10, 2 );

function bp_blogs_remove_blog( $blog_id ) {
	global $bp;

	$blog_id = (int)$blog_id;
	do_action( 'bp_blogs_before_remove_blog', $blog_id );

	BP_Blogs_Blog::delete_blog_for_all( $blog_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'component' => $bp->blogs->id, 'type' => 'new_blog' ) );

	do_action( 'bp_blogs_remove_blog', $blog_id );
}
add_action( 'delete_blog', 'bp_blogs_remove_blog' );

function bp_blogs_remove_blog_for_user( $user_id, $blog_id ) {
	global $bp, $current_user;

	$blog_id = (int)$blog_id;
	$user_id = (int)$user_id;

	do_action( 'bp_blogs_before_remove_blog_for_user', $blog_id, $user_id );

	BP_Blogs_Blog::delete_blog_for_user( $blog_id, $user_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'component' => $bp->blogs->id, 'type' => 'new_blog' ) );

	do_action( 'bp_blogs_remove_blog_for_user', $blog_id, $user_id );
}
add_action( 'remove_user_from_blog', 'bp_blogs_remove_blog_for_user', 10, 2 );

function bp_blogs_remove_post( $post_id, $blog_id = 0, $user_id = 0 ) {
	global $wpdb, $bp;

	if ( empty( $wpdb->blogid ) )
		return false;

	$post_id = (int)$post_id;

	if ( !$blog_id )
		$blog_id = (int)$wpdb->blogid;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	do_action( 'bp_blogs_before_remove_post', $blog_id, $post_id, $user_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'secondary_item_id' => $post_id, 'component' => $bp->blogs->id, 'type' => 'new_blog_post' ) );

	do_action( 'bp_blogs_remove_post', $blog_id, $post_id, $user_id );
}
add_action( 'delete_post', 'bp_blogs_remove_post' );

function bp_blogs_remove_comment( $comment_id ) {
	global $wpdb, $bp;

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $wpdb->blogid, 'secondary_item_id' => $comment_id, 'type' => 'new_blog_comment' ) );

	do_action( 'bp_blogs_remove_comment', $wpdb->blogid, $comment_id, $bp->loggedin_user->id );
}
add_action( 'delete_comment', 'bp_blogs_remove_comment' );

function bp_blogs_total_blogs() {
	if ( !$count = wp_cache_get( 'bp_total_blogs', 'bp' ) ) {
		$blogs = BP_Blogs_Blog::get_all();
		$count = $blogs['total'];
		wp_cache_set( 'bp_total_blogs', $count, 'bp' );
	}
	return $count;
}

function bp_blogs_total_blogs_for_user( $user_id = 0 ) {
	global $bp;

	if ( !$user_id )
		$user_id = ( $bp->displayed_user->id ) ? $bp->displayed_user->id : $bp->loggedin_user->id;

	if ( !$count = wp_cache_get( 'bp_total_blogs_for_user_' . $user_id, 'bp' ) ) {
		$count = BP_Blogs_Blog::total_blog_count_for_user( $user_id );
		wp_cache_set( 'bp_total_blogs_for_user_' . $user_id, $count, 'bp' );
	}

	return $count;
}

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

function bp_blogs_get_blogs_for_user( $user_id, $show_hidden = false ) {
	return BP_Blogs_Blog::get_blogs_for_user( $user_id, $show_hidden );
}

function bp_blogs_get_all_blogs( $limit = null, $page = null ) {
	return BP_Blogs_Blog::get_all( $limit, $page );
}

function bp_blogs_get_random_blogs( $limit = null, $page = null ) {
	return BP_Blogs_Blog::get( 'random', $limit, $page );
}

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

function bp_blogs_delete_blogmeta( $blog_id, $meta_key = false, $meta_value = false ) {
	global $wpdb, $bp;

	if ( !is_numeric( $blog_id ) )
		return false;

	$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

	if ( is_array($meta_value) || is_object($meta_value) )
		$meta_value = serialize($meta_value);

	$meta_value = trim( $meta_value );

	if ( !$meta_key )
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blogmeta} WHERE blog_id = %d", $blog_id ) );
	else if ( $meta_value )
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blogmeta} WHERE blog_id = %d AND meta_key = %s AND meta_value = %s", $blog_id, $meta_key, $meta_value ) );
	else
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blogmeta} WHERE blog_id = %d AND meta_key = %s", $blog_id, $meta_key ) );

	wp_cache_delete( 'bp_blogs_blogmeta_' . $blog_id . '_' . $meta_key, 'bp' );

	return true;
}

function bp_blogs_get_blogmeta( $blog_id, $meta_key = '') {
	global $wpdb, $bp;

	$blog_id = (int) $blog_id;

	if ( !$blog_id )
		return false;

	if ( !empty($meta_key) ) {
		$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

		if ( !$metas = wp_cache_get( 'bp_blogs_blogmeta_' . $blog_id . '_' . $meta_key, 'bp' ) ) {
			$metas = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$bp->blogs->table_name_blogmeta} WHERE blog_id = %d AND meta_key = %s", $blog_id, $meta_key ) );
			wp_cache_set( 'bp_blogs_blogmeta_' . $blog_id . '_' . $meta_key, $metas, 'bp' );
		}
	} else {
		$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM {$bp->blogs->table_name_blogmeta} WHERE blog_id = %d", $blog_id) );
	}

	if ( empty($metas) ) {
		if ( empty($meta_key) )
			return array();
		else
			return '';
	}

	$metas = array_map('maybe_unserialize', (array)$metas);

	if ( 1 == count($metas) )
		return $metas[0];
	else
		return $metas;
}

function bp_blogs_update_blogmeta( $blog_id, $meta_key, $meta_value ) {
	global $wpdb, $bp;

	if ( !is_numeric( $blog_id ) )
		return false;

	$meta_key = preg_replace( '|[^a-z0-9_]|i', '', $meta_key );

	if ( is_string($meta_value) )
		$meta_value = stripslashes($wpdb->escape($meta_value));

	$meta_value = maybe_serialize($meta_value);

	if (empty( $meta_value ) )
		return bp_blogs_delete_blogmeta( $blog_id, $meta_key );

	$cur = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->blogs->table_name_blogmeta} WHERE blog_id = %d AND meta_key = %s", $blog_id, $meta_key ) );

	if ( !$cur )
		$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->blogs->table_name_blogmeta} ( blog_id, meta_key, meta_value ) VALUES ( %d, %s, %s )", $blog_id, $meta_key, $meta_value ) );
	else if ( $cur->meta_value != $meta_value )
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->blogs->table_name_blogmeta} SET meta_value = %s WHERE blog_id = %d AND meta_key = %s", $meta_value, $blog_id, $meta_key ) );
	else
		return false;

	wp_cache_set( 'bp_blogs_blogmeta_' . $blog_id . '_' . $meta_key, $meta_value, 'bp' );

	return true;
}

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
?>