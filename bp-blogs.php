<?php

define ( 'BP_BLOGS_DB_VERSION', '2015' );

/* Define the slug for the component */
if ( !defined( 'BP_BLOGS_SLUG' ) )
	define ( 'BP_BLOGS_SLUG', 'blogs' );

require ( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-classes.php' );
require ( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-templatetags.php' );

/* Include the sitewide blog posts widget if this is a multisite installation */
if ( bp_core_is_multisite() )
	require ( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-widgets.php' );

function bp_blogs_install() {
	global $wpdb, $bp;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	$sql[] = "CREATE TABLE {$bp->blogs->table_name} (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id bigint(20) NOT NULL,
				blog_id bigint(20) NOT NULL,
				KEY user_id (user_id),
				KEY blog_id (blog_id)
			 ) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp->blogs->table_name_blog_posts} (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id bigint(20) NOT NULL,
				blog_id bigint(20) NOT NULL,
				post_id bigint(20) NOT NULL,
				date_created datetime NOT NULL,
				KEY user_id (user_id),
				KEY blog_id (blog_id),
				KEY post_id (post_id)
			 ) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp->blogs->table_name_blog_comments} (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id bigint(20) NOT NULL,
				blog_id bigint(20) NOT NULL,
				comment_id bigint(20) NOT NULL,
				comment_post_id bigint(20) NOT NULL,
				date_created datetime NOT NULL,
				KEY user_id (user_id),
				KEY blog_id (blog_id),
				KEY comment_id (comment_id),
				KEY comment_post_id (comment_post_id)
			 ) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp->blogs->table_name_blogmeta} (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				blog_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				KEY blog_id (blog_id),
				KEY meta_key (meta_key)
		     ) {$charset_collate};";


	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

	dbDelta($sql);

	// On first installation - record all existing blogs in the system.
	if ( !(int)$bp->site_options['bp-blogs-first-install'] && bp_core_is_multisite() ) {
		bp_blogs_record_existing_blogs();
		add_site_option( 'bp-blogs-first-install', 1 );
	}

	update_site_option( 'bp-blogs-db-version', BP_BLOGS_DB_VERSION );
}

function bp_blogs_check_installed() {
	global $wpdb, $bp, $userdata;

	/* Only create the bp-blogs tables if this is a multisite install */
	if ( is_site_admin() && bp_core_is_multisite() ) {
		/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
		if ( get_site_option( 'bp-blogs-db-version' ) < BP_BLOGS_DB_VERSION )
			bp_blogs_install();
	}
}
add_action( 'admin_menu', 'bp_blogs_check_installed' );

function bp_blogs_setup_globals() {
	global $bp, $wpdb;

	/* For internal identification */
	$bp->blogs->id = 'blogs';

	$bp->blogs->table_name = $wpdb->base_prefix . 'bp_user_blogs';
	$bp->blogs->table_name_blog_posts = $wpdb->base_prefix . 'bp_user_blogs_posts';
	$bp->blogs->table_name_blog_comments = $wpdb->base_prefix . 'bp_user_blogs_comments';
	$bp->blogs->table_name_blogmeta = $wpdb->base_prefix . 'bp_user_blogs_blogmeta';
	$bp->blogs->format_notification_function = 'bp_blogs_format_notifications';
	$bp->blogs->slug = BP_BLOGS_SLUG;

	/* Register this in the active components array */
	$bp->active_components[$bp->blogs->slug] = $bp->blogs->id;

	do_action( 'bp_blogs_setup_globals' );
}
add_action( 'bp_setup_globals', 'bp_blogs_setup_globals' );

function bp_blogs_setup_root_component() {
	/* Register 'blogs' as a root component */
	bp_core_add_root_component( BP_BLOGS_SLUG );
}
add_action( 'bp_setup_root_components', 'bp_blogs_setup_root_component' );

/**
 * bp_blogs_setup_nav()
 *
 * Adds "Blog" to the navigation arrays for the current and logged in user.
 *
 * @package BuddyPress Blogs
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_is_my_profile() Checks to see if the current user being viewed is the logged in user
 */
function bp_blogs_setup_nav() {
	global $bp;

	/* Blog/post/comment menus should not appear on single WordPress setups. Although comments
	   and posts made by users will still show on their activity stream .*/
	if ( !bp_core_is_multisite() )
		return false;

	/* Add 'Blogs' to the main navigation */
	bp_core_new_nav_item( array( 'name' => sprintf( __( 'Blogs <span>(%d)</span>', 'buddypress' ), bp_blogs_total_blogs_for_user() ), 'slug' => $bp->blogs->slug, 'position' => 30, 'screen_function' => 'bp_blogs_screen_my_blogs', 'default_subnav_slug' => 'my-blogs', 'item_css_id' => $bp->blogs->id ) );

	$blogs_link = $bp->loggedin_user->domain . $bp->blogs->slug . '/';

	/* Set up the component options navigation for Blog */
	if ( 'blogs' == $bp->current_component ) {
		if ( bp_is_my_profile() ) {
			if ( function_exists('xprofile_setup_nav') ) {
				$bp->bp_options_title = __('My Blogs', 'buddypress');
			}
		} else {
			/* If we are not viewing the logged in user, set up the current users avatar and name */
			$bp->bp_options_avatar = bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb' ) );
			$bp->bp_options_title = $bp->displayed_user->fullname;
		}
	}

	do_action( 'bp_blogs_setup_nav' );
}
add_action( 'bp_setup_nav', 'bp_blogs_setup_nav' );

function bp_blogs_directory_blogs_setup() {
	global $bp;

	if ( bp_core_is_multisite() && $bp->current_component == $bp->blogs->slug && empty( $bp->current_action ) ) {
		$bp->is_directory = true;

		do_action( 'bp_blogs_directory_blogs_setup' );
		bp_core_load_template( apply_filters( 'bp_blogs_template_directory_blogs_setup', 'blogs/index' ) );
	}
}
add_action( 'wp', 'bp_blogs_directory_blogs_setup', 2 );


/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

function bp_blogs_screen_my_blogs() {
	global $bp;

	if ( !bp_core_is_multisite() )
		return false;

	do_action( 'bp_blogs_screen_my_blogs' );
	bp_core_load_template( apply_filters( 'bp_blogs_template_my_blogs', 'members/single/home' ) );
}

function bp_blogs_screen_recent_posts() {
	do_action( 'bp_blogs_screen_recent_posts' );
	bp_core_load_template( apply_filters( 'bp_blogs_template_recent_posts', 'members/single/home' ) );
}

function bp_blogs_screen_recent_comments() {
	do_action( 'bp_blogs_screen_recent_comments' );
	bp_core_load_template( apply_filters( 'bp_blogs_template_recent_comments', 'members/single/home' ) );
}

function bp_blogs_screen_create_a_blog() {
	global $bp;

	if ( !bp_core_is_multisite() || $bp->current_component != $bp->blogs->slug || 'create' != $bp->current_action )
		return false;

	if ( !is_user_logged_in() || !bp_blog_signup_enabled() )
		return false;

	do_action( 'bp_blogs_screen_create_a_blog' );
	bp_core_load_template( apply_filters( 'bp_blogs_template_create_a_blog', 'blogs/create' ) );
}
/* The create screen is not attached to a nav item, so we need to attach it to an action */
add_action( 'wp', 'bp_blogs_screen_create_a_blog', 3 );


/********************************************************************************
 * Activity & Notification Functions
 *
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 */

function bp_blogs_register_activity_actions() {
	global $bp;

	if ( !function_exists( 'bp_activity_set_action' ) )
		return false;

	bp_activity_set_action( $bp->blogs->id, 'new_blog', __( 'New blog created', 'buddypress' ) );
	bp_activity_set_action( $bp->blogs->id, 'new_blog_post', __( 'New blog post published', 'buddypress' ) );
	bp_activity_set_action( $bp->blogs->id, 'new_blog_comment', __( 'New blog post comment posted', 'buddypress' ) );

	do_action( 'bp_blogs_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'bp_blogs_register_activity_actions' );

function bp_blogs_record_activity( $args = '' ) {
	global $bp;

	if ( !function_exists( 'bp_activity_add' ) )
		return false;

	/* Because blog, comment, and blog post code execution happens before anything else
	   we may need to manually instantiate the activity component globals */
	if ( !$bp->activity && function_exists('bp_activity_setup_globals') )
		bp_activity_setup_globals();

	$defaults = array(
		'user_id' => $bp->loggedin_user->id,
		'action' => '',
		'content' => '',
		'primary_link' => '',
		'component' => $bp->blogs->id,
		'type' => false,
		'item_id' => false,
		'secondary_item_id' => false,
		'recorded_time' => gmdate( "Y-m-d H:i:s" ),
		'hide_sitewide' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	/* Remove large images and replace them with just one image thumbnail */
 	if ( function_exists( 'bp_activity_thumbnail_content_images' ) && !empty( $content ) )
		$content = bp_activity_thumbnail_content_images( $content );

	if ( !empty( $action ) )
		$action = apply_filters( 'bp_blogs_record_activity_action', $action );

	if ( !empty( $content ) )
		$content = apply_filters( 'bp_blogs_record_activity_content', bp_create_excerpt( $content ) );

	/* Check for an existing entry and update if one exists. */
	$id = bp_activity_get_activity_id( array(
		'user_id' => $user_id,
		'component' => $component,
		'type' => $type,
		'item_id' => $item_id,
		'secondary_item_id' => $secondary_item_id
	) );

	return bp_activity_add( array( 'id' => $id, 'user_id' => $user_id, 'action' => $action, 'content' => $content, 'primary_link' => $primary_link, 'component' => $component, 'type' => $type, 'item_id' => $item_id, 'secondary_item_id' => $secondary_item_id, 'recorded_time' => $recorded_time, 'hide_sitewide' => $hide_sitewide ) );
}

function bp_blogs_delete_activity( $args = true ) {
	global $bp;

	if ( function_exists('bp_activity_delete_by_item_id') ) {
		$defaults = array(
			'item_id' => false,
			'component' => $bp->blogs->id,
			'type' => false,
			'user_id' => false,
			'secondary_item_id' => false
		);

		$params = wp_parse_args( $args, $defaults );
		extract( $params, EXTR_SKIP );

		bp_activity_delete_by_item_id( array(
			'item_id' => $item_id,
			'component' => $component,
			'type' => $type,
			'user_id' => $user_id,
			'secondary_item_id' => $secondary_item_id
		) );
	}
}

/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

function bp_blogs_get_blogs( $args = '' ) {
	global $bp;

	$defaults = array(
		'type' => 'active', // active, alphabetical, newest, or random.
		'user_id' => false, // Pass a user_id to limit to only blogs that this user has privilages higher than subscriber on.
		'search_terms' => false, // Limit to blogs that match these search terms

		'per_page' => 20, // The number of results to return per page
		'page' => 1, // The page to return if limiting per page
	);

	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );

	return apply_filters( 'bp_blogs_get_blogs', BP_Blogs_Blog::get( $type, $per_page, $page, $user_id, $search_terms ), &$params );
}


function bp_blogs_record_existing_blogs() {
	global $bp, $wpdb;

	/* Truncate user blogs table and re-record. */
	$wpdb->query( "TRUNCATE TABLE {$bp->blogs->table_name}" );

	$blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM {$wpdb->base_prefix}blogs WHERE mature = 0 AND spam = 0 AND deleted = 0" ) );

	if ( $blog_ids ) {
		foreach( (array)$blog_ids as $blog_id ) {
			$users = get_users_of_blog( $blog_id );

			if ( $users ) {
				foreach ( (array)$users as $user ) {
					$role = unserialize( $user->meta_value );

					if ( !isset( $role['subscriber'] ) )
						bp_blogs_record_blog( $blog_id, $user->user_id, true );
				}
			}
		}
	}
}

function bp_blogs_record_blog( $blog_id, $user_id, $no_activity = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	$name = get_blog_option( $blog_id, 'blogname' );
	$description = get_blog_option( $blog_id, 'blogdescription' );

	if ( empty( $name ) )
		return false;

	$recorded_blog = new BP_Blogs_Blog;
	$recorded_blog->user_id = $user_id;
	$recorded_blog->blog_id = $blog_id;

	$recorded_blog_id = $recorded_blog->save();

	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'name', $name );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'description', $description );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'last_activity', gmdate( "Y-m-d H:i:s" ) );

	/* Only record this activity if the blog is public */
	if ( (int)$_POST['blog_public'] && !$no_activity ) {
		/* Record this in activity streams */
		bp_blogs_record_activity( array(
			'user_id' => $recorded_blog->user_id,
			'action' => apply_filters( 'bp_blogs_activity_created_blog_action', sprintf( __( '%s created the blog %s', 'buddypress'), bp_core_get_userlink( $recorded_blog->user_id ), '<a href="' . get_blog_option( $recorded_blog->blog_id, 'siteurl' ) . '">' . attribute_escape( $name ) . '</a>' ), &$recorded_blog, $name, $description ),
			'primary_link' => apply_filters( 'bp_blogs_activity_created_blog_primary_link', get_blog_option( $recorded_blog->blog_id, 'siteurl' ), $recorded_blog->blog_id ),
			'type' => 'new_blog',
			'item_id' => $recorded_blog->blog_id
		) );
	}

	do_action( 'bp_blogs_new_blog', &$recorded_blog, $is_private, $is_recorded );
}
add_action( 'wpmu_new_blog', 'bp_blogs_record_blog', 10, 2 );

function bp_blogs_record_post( $post_id, $post, $user_id = false ) {
	global $bp, $wpdb;

	$post_id = (int)$post_id;
	$blog_id = (int)$wpdb->blogid;

	if ( !$user_id )
		$user_id = (int)$post->post_author;

	/* This is to stop infinate loops with Donncha's sitewide tags plugin */
	if ( (int)$bp->site_options['tags_blog_id'] == (int)$blog_id )
		return false;

	/* Don't record this if it's not a post */
	if ( $post->post_type != 'post' )
		return false;

	if ( !$is_recorded = BP_Blogs_Post::is_recorded( $post_id, $blog_id, $user_id ) ) {
		if ( 'publish' == $post->post_status && '' == $post->post_password ) {

			/* If we're on a multiblog install, record this post */
			if ( bp_core_is_multisite() ) {
				$recorded_post = new BP_Blogs_Post;
				$recorded_post->user_id = $user_id;
				$recorded_post->blog_id = $blog_id;
				$recorded_post->post_id = $post_id;
				$recorded_post->date_created = strtotime( $post->post_date );

				$recorded_post_id = $recorded_post->save();

				bp_blogs_update_blogmeta( $recorded_post->blog_id, 'last_activity', gmdate( "Y-m-d H:i:s" ) );
			}

			if ( (int)get_blog_option( $blog_id, 'blog_public' ) || !bp_core_is_multisite() ) {
				/* Record this in activity streams */
				$post_permalink = get_permalink( $post_id );

				$activity_action = sprintf( __( '%s wrote a new blog post: %s', 'buddypress' ), bp_core_get_userlink( (int)$post->post_author ), '<a href="' . $post_permalink . '">' . $post->post_title . '</a>' );
				$activity_content = $post->post_content;

				bp_blogs_record_activity( array(
					'user_id' => (int)$post->post_author,
					'action' => apply_filters( 'bp_blogs_activity_new_post_action', $activity_action, &$post, $post_permalink ),
					'content' => apply_filters( 'bp_blogs_activity_new_post_content', $activity_content, &$post, $post_permalink ),
					'primary_link' => apply_filters( 'bp_blogs_activity_new_post_primary_link', $post_permalink, $post_id ),
					'type' => 'new_blog_post',
					'item_id' => $blog_id,
					'secondary_item_id' => $post_id,
					'recorded_time' => $post->post_date_gmt
				));
			}
		}
	} else {
		$existing_post = new BP_Blogs_Post( null, $blog_id, $post_id );

		/* Delete the recorded post if the status is not published or it is password protected */
		if ( 'publish' != $post->post_status || '' != $post->post_password ) {
			return bp_blogs_remove_post( $post_id, $blog_id, $existing_post );

		/* If the post author has changed, delete the post and re-add it. */
		} else if ( (int)$existing_post->user_id != (int)$post->post_author ) {
			// Delete the existing recorded post
			bp_blogs_remove_post( $post_id, $blog_id, $existing_post );

			// Re-record the post with the new author.
			bp_blogs_record_post( $post_id );
		}

		if ( (int)get_blog_option( $blog_id, 'blog_public' ) || !bp_core_is_multisite() ) {
			/* Now re-record the post in the activity streams */
			$post_permalink = get_permalink( $post_id );

			$activity_action = sprintf( __( '%s wrote a new blog post: %s', 'buddypress' ), bp_core_get_userlink( (int)$post->post_author ), '<a href="' . $post_permalink . '">' . $post->post_title . '</a>' );
			$activity_content = $post->post_content;

			bp_blogs_record_activity( array(
				'user_id' => (int)$post->post_author,
				'action' => apply_filters( 'bp_blogs_activity_new_post_action', $activity_action, &$post, $post_permalink ),
				'content' => apply_filters( 'bp_blogs_activity_new_post_content', $activity_content, &$post, $post_permalink ),
				'primary_link' => apply_filters( 'bp_blogs_activity_new_post_primary_link', $post_permalink, $post_id ),
				'type' => 'new_blog_post',
				'item_id' => $blog_id,
				'secondary_item_id' => $post_id,
				'recorded_time' => $post->post_date_gmt
			) );
		}
	}

	do_action( 'bp_blogs_new_blog_post', $existing_post, $is_private, $is_recorded );
}
add_action( 'save_post', 'bp_blogs_record_post', 10, 2 );

function bp_blogs_record_comment( $comment_id, $is_approved ) {
	global $wpdb, $bp;

	if ( !$is_approved )
		return false;

	$comment = get_comment($comment_id);
	$comment->post = get_post( $comment->comment_post_ID );

	/* Get the user_id from the author email. */
	$user = get_user_by_email( $comment->comment_author_email );
	$user_id = (int)$user->ID;

	if ( !$user_id )
		return false;

	/* If this is a password protected post, don't record the comment */
	if ( !empty( $post->post_password ) )
		return false;

	/* If we're on a multiblog install, record this post */
	if ( bp_core_is_multisite() ) {
		$recorded_comment = new BP_Blogs_Comment;
		$recorded_comment->user_id = $user_id;
		$recorded_comment->blog_id = $wpdb->blogid;
		$recorded_comment->comment_id = $comment_id;
		$recorded_comment->comment_post_id = $comment->comment_post_ID;
		$recorded_comment->date_created = strtotime( $comment->comment_date_gmt );

		$recorded_commment_id = $recorded_comment->save();

		bp_blogs_update_blogmeta( $recorded_comment->blog_id, 'last_activity', gmdate( "Y-m-d H:i:s" ) );
	}

	if ( (int)get_blog_option( $recorded_comment->blog_id, 'blog_public' ) || !bp_core_is_multisite() ) {
		/* Record in activity streams */
		$comment_link = get_permalink( $comment->comment_post_ID ) . '#comment-' . $comment_id;
		$activity_action = sprintf( __( '%s commented on the blog post %s', 'buddypress' ), bp_core_get_userlink( $user_id ), '<a href="' . $comment_link . '#comment-' . $comment->comment_ID . '">' . $comment->post->post_title . '</a>' );
		$activity_content = $comment->comment_content;

		/* Record this in activity streams */
		bp_blogs_record_activity( array(
			'user_id' => $user_id,
			'action' => apply_filters( 'bp_blogs_activity_new_comment_action', $activity_action, &$comment, &$recorded_comment, $comment_link ),
			'content' => apply_filters( 'bp_blogs_activity_new_comment_content', $activity_content, &$comment, &$recorded_comment, $comment_link ),
			'primary_link' => apply_filters( 'bp_blogs_activity_new_comment_primary_link', $comment_link, &$comment, &$recorded_comment ),
			'type' => 'new_blog_comment',
			'item_id' => $wpdb->blogid,
			'secondary_item_id' => $comment_id,
			'recorded_time' => $comment->comment_date_gmt
		) );
	}

	return $recorded_comment;
}
add_action( 'comment_post', 'bp_blogs_record_comment', 10, 2 );

function bp_blogs_manage_comment( $comment_id, $comment_status ) {
	if ( 'spam' == $comment_status || 'hold' == $comment_status || 'delete' == $comment_status || 'trash' == $comment_status )
		return bp_blogs_remove_comment( $comment_id );

	return bp_blogs_record_comment( $comment_id, true );
}
add_action( 'wp_set_comment_status', 'bp_blogs_manage_comment', 10, 2 );

function bp_blogs_add_user_to_blog( $user_id, $role, $blog_id = false ) {
	global $current_blog;

	if ( empty( $blog_id ) )
		$blog_id = $current_blog->blog_id;

	if ( $role != 'subscriber' )
		bp_blogs_record_blog( $blog_id, $user_id, true );
}
add_action( 'add_user_to_blog', 'bp_blogs_add_user_to_blog', 10, 3 );

function bp_blogs_remove_user_from_blog( $user_id, $blog_id = false ) {
	global $current_blog;

	if ( empty( $blog_id ) )
		$blog_id = $current_blog->blog_id;

	bp_blogs_remove_blog_for_user( $user_id, $blog_id );
}
add_action( 'remove_user_from_blog', 'bp_blogs_remove_user_from_blog', 10, 2 );

function bp_blogs_remove_blog( $blog_id ) {
	global $bp;

	$blog_id = (int)$blog_id;

	BP_Blogs_Blog::delete_blog_for_all( $blog_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'component' => $bp->blogs->slug, 'type' => 'new_blog' ) );

	do_action( 'bp_blogs_remove_blog', $blog_id );
}
add_action( 'delete_blog', 'bp_blogs_remove_blog' );

function bp_blogs_remove_blog_for_user( $user_id, $blog_id ) {
	global $current_user;

	$blog_id = (int)$blog_id;
	$user_id = (int)$user_id;

	BP_Blogs_Blog::delete_blog_for_user( $blog_id, $user_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'component' => $bp->blogs->slug, 'type' => 'new_blog' ) );

	do_action( 'bp_blogs_remove_blog_for_user', $blog_id, $user_id );
}
add_action( 'remove_user_from_blog', 'bp_blogs_remove_blog_for_user', 10, 2 );

function bp_blogs_remove_post( $post_id, $blog_id = false, $existing_post = false ) {
	global $current_blog, $bp;

	$post_id = (int)$post_id;

	if ( !$blog_id )
		$blog_id = (int)$current_blog->blog_id;

	if ( !$existing_post )
		$existing_post = new BP_Blogs_Post( null, $blog_id, $post_id );

	// Delete post from the bp_blogs table
	BP_Blogs_Post::delete( $post_id, $blog_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'secondary_item_id' => $post_id, 'component' => $bp->blogs->slug, 'type' => 'new_blog_post' ) );

	do_action( 'bp_blogs_remove_post', $blog_id, $post_id, $post->user_id );
}
add_action( 'delete_post', 'bp_blogs_remove_post' );

function bp_blogs_remove_comment( $comment_id ) {
	global $wpdb, $bp;

	$recorded_comment = new BP_Blogs_Comment( false, $wpdb->blogid, $comment_id );
	BP_Blogs_Comment::delete( $comment_id, $wpdb->blogid );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $wpdb->blogid , 'secondary_item_id' => $comment_id, 'type' => 'new_blog_comment' ) );

	do_action( 'bp_blogs_remove_comment', $blog_id, $comment_id, $bp->loggedin_user->id );
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

function bp_blogs_total_blogs_for_user( $user_id = false ) {
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

	/* If this is regular blog, delete all data for that blog. */
	BP_Blogs_Blog::delete_blog_for_all( $blog_id );
	BP_Blogs_Post::delete_posts_for_blog( $blog_id );
	BP_Blogs_Comment::delete_comments_for_blog( $blog_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'component' => $bp->blogs->slug, 'type' => false ) );

	do_action( 'bp_blogs_remove_data_for_blog', $blog_id );
}
add_action( 'delete_blog', 'bp_blogs_remove_data_for_blog', 1 );

function bp_blogs_get_blogs_for_user( $user_id, $show_hidden = false ) {
	return BP_Blogs_Blog::get_blogs_for_user( $user_id, $show_hidden );
}

function bp_blogs_get_posts_for_user( $user_id ) {
	return BP_Blogs_Post::get_posts_for_user( $user_id );
}

function bp_blogs_get_comments_for_user( $user_id ) {
	return BP_Blogs_Comment::get_comments_for_user( $user_id );
}

function bp_blogs_get_latest_posts( $blog_id = null, $limit = 5 ) {
	global $bp;

	if ( !is_numeric( $limit ) )
		$limit = 5;

	return BP_Blogs_Post::get_latest_posts( $blog_id, $limit );
}

function bp_blogs_get_all_blogs( $limit = null, $page = null ) {
	return BP_Blogs_Blog::get_all( $limit, $page );
}

function bp_blogs_get_random_blogs( $limit = null, $page = null ) {
	return BP_Blogs_Blog::get( 'random', $limit, $page );
}

function bp_blogs_get_all_posts( $limit = null, $page = null ) {
	return BP_Blogs_Post::get_all( $limit, $page );
}

function bp_blogs_total_post_count( $blog_id ) {
	return BP_Blogs_Post::total_post_count( $blog_id );
}

function bp_blogs_total_comment_count( $blog_id, $post_id = false ) {
	return BP_Blogs_Post::total_comment_count( $blog_id, $post_id );
}

function bp_blogs_is_blog_hidden( $blog_id ) {
	return BP_Blogs_Blog::is_hidden( $blog_id );
}

function bp_blogs_redirect_to_random_blog() {
	global $bp, $wpdb;

	if ( $bp->current_component == $bp->blogs->slug && isset( $_GET['random-blog'] ) ) {
		$blog = bp_blogs_get_random_blogs( 1, 1 );

		bp_core_redirect( get_blog_option( $blog['blogs'][0]->blog_id, 'siteurl') );
	}
}
add_action( 'wp', 'bp_blogs_redirect_to_random_blog', 6 );


//
// Blog meta functions
// These functions are used to store specific blogmeta in one global table, rather than in each
// blog's options table. Significantly speeds up global blog queries.
// By default each blog's name, description and last updated time are stored and synced here.
//

function bp_blogs_delete_blogmeta( $blog_id, $meta_key = false, $meta_value = false ) {
	global $wpdb, $bp;

	if ( !is_numeric( $blog_id ) )
		return false;

	$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

	if ( is_array($meta_value) || is_object($meta_value) )
		$meta_value = serialize($meta_value);

	$meta_value = trim( $meta_value );

	if ( !$meta_key ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blogmeta} WHERE blog_id = %d", $blog_id ) );
	} else if ( $meta_value ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blogmeta} WHERE blog_id = %d AND meta_key = %s AND meta_value = %s", $blog_id, $meta_key, $meta_value ) );
	} else {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name_blogmeta} WHERE blog_id = %d AND meta_key = %s", $blog_id, $meta_key ) );
	}

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

	if (empty($meta_value)) {
		return bp_blogs_delete_blogmeta( $blog_id, $meta_key );
	}

	$cur = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->blogs->table_name_blogmeta} WHERE blog_id = %d AND meta_key = %s", $blog_id, $meta_key ) );

	if ( !$cur ) {
		$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->blogs->table_name_blogmeta} ( blog_id, meta_key, meta_value ) VALUES ( %d, %s, %s )", $blog_id, $meta_key, $meta_value ) );
	} else if ( $cur->meta_value != $meta_value ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->blogs->table_name_blogmeta} SET meta_value = %s WHERE blog_id = %d AND meta_key = %s", $meta_value, $blog_id, $meta_key ) );
	} else {
		return false;
	}

	wp_cache_set( 'bp_blogs_blogmeta_' . $blog_id . '_' . $meta_key, $metas, 'bp' );

	return true;
}

function bp_blogs_remove_data( $user_id ) {
	/* If this is regular blog, delete all data for that blog. */
	BP_Blogs_Blog::delete_blogs_for_user( $user_id );
	BP_Blogs_Post::delete_posts_for_user( $user_id );
	BP_Blogs_Comment::delete_comments_for_user( $user_id );

	do_action( 'bp_blogs_remove_data', $user_id );
}
add_action( 'wpmu_delete_user', 'bp_blogs_remove_data', 1 );
add_action( 'delete_user', 'bp_blogs_remove_data', 1 );


/********************************************************************************
 * Caching
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 */

function bp_blogs_clear_blog_object_cache( $blog_id, $user_id ) {
	wp_cache_delete( 'bp_blogs_of_user_' . $user_id, 'bp' );
	wp_cache_delete( 'bp_blogs_for_user_' . $user_id, 'bp' );
	wp_cache_delete( 'bp_total_blogs_for_user_' . $user_id, 'bp' );

	/* Clear the sitewide activity cache */
	wp_cache_delete( 'sitewide_activity', 'bp' );
}

function bp_blogs_format_clear_blog_cache( $recorded_blog_obj ) {
	bp_blogs_clear_blog_object_cache( false, $recorded_blog_obj->user_id );

	/* Clear the sitewide activity cache */
	wp_cache_delete( 'sitewide_activity', 'bp' );
	wp_cache_delete( 'bp_total_blogs', 'bp' );
}

function bp_blogs_clear_post_object_cache( $blog_id, $post_id, $user_id ) {
	wp_cache_delete( 'bp_user_posts_' . $user_id, 'bp' );
}

function bp_blogs_format_clear_post_cache( $recorded_post_obj ) {
	bp_blogs_clear_post_object_cache( false, false, $recorded_post_obj->user_id );

	/* Clear the sitewide activity cache */
	wp_cache_delete( 'sitewide_activity', 'bp' );
}

function bp_blogs_clear_comment_object_cache( $blog_id, $comment_id, $user_id ) {
	wp_cache_delete( 'bp_user_comments_' . $user_id, 'bp' );
}

function bp_blogs_format_clear_comment_cache( $recorded_comment_obj ) {
	bp_blogs_clear_comment_object_cache( false, false, $recorded_comment_obj->user_id );

	/* Clear the sitewide activity cache */
	wp_cache_delete( 'sitewide_activity', 'bp' );
}

// List actions to clear object caches on
add_action( 'bp_blogs_remove_blog_for_user', 'bp_blogs_clear_blog_object_cache', 10, 2 );
add_action( 'bp_blogs_remove_post', 'bp_blogs_clear_post_object_cache', 10, 3 );
add_action( 'bp_blogs_remove_comment', 'bp_blogs_clear_comment_object_cache', 10, 3 );

add_action( 'bp_blogs_new_blog', 'bp_blogs_format_clear_blog_cache', 10, 2 );
add_action( 'bp_blogs_new_blog_post', 'bp_blogs_format_clear_post_cache', 10, 2 );
add_action( 'bp_blogs_new_blog_comment', 'bp_blogs_format_clear_comment_cache', 10, 2 );

// List actions to clear super cached pages on, if super cache is installed
add_action( 'bp_blogs_remove_data_for_blog', 'bp_core_clear_cache' );
add_action( 'bp_blogs_remove_comment', 'bp_core_clear_cache' );
add_action( 'bp_blogs_remove_post', 'bp_core_clear_cache' );
add_action( 'bp_blogs_remove_blog_for_user', 'bp_core_clear_cache' );
add_action( 'bp_blogs_remove_blog', 'bp_core_clear_cache' );
add_action( 'bp_blogs_new_blog_comment', 'bp_core_clear_cache' );
add_action( 'bp_blogs_new_blog_post', 'bp_core_clear_cache' );
add_action( 'bp_blogs_new_blog', 'bp_core_clear_cache' );
add_action( 'bp_blogs_remove_data', 'bp_core_clear_cache' );

?>