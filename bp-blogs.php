<?php

define ( 'BP_BLOGS_VERSION', '1.0-RC2' );
define ( 'BP_BLOGS_DB_VERSION', '1300' );

/* Define the slug for the component */
if ( !defined( 'BP_BLOGS_SLUG' ) )
	define ( 'BP_BLOGS_SLUG', 'blogs' );

require ( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-classes.php' );
require ( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-cssjs.php' );
require ( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-templatetags.php' );
require ( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-widgets.php' );
require ( BP_PLUGIN_DIR . '/bp-blogs/bp-blogs-ajax.php' );


/**************************************************************************
 bp_blogs_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

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
	if ( !(int)get_site_option( 'bp-blogs-first-install') ) {
		
		bp_blogs_record_existing_blogs();
		add_site_option( 'bp-blogs-first-install', 1 );
		
	} else {
		
		// Import blog titles and descriptions into the blogmeta table 	
		if ( get_site_option( 'bp-blogs-version' ) <= '0.1.5' ) {
			$blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM " . $bp->blogs->table_name ) );

			for ( $i = 0; $i < count($blog_ids); $i++ ) {
				$name = get_blog_option( $blog_ids[$i], 'blogname' );
				$desc = get_blog_option( $blog_ids[$i], 'blogdescription' );
				
				bp_blogs_update_blogmeta( $blog_ids[$i], 'name', $name );
				bp_blogs_update_blogmeta( $blog_ids[$i], 'description', $desc );
				bp_blogs_update_blogmeta( $blog_ids[$i], 'last_activity', time() );
			}
		}
		
	}
	
	update_site_option( 'bp-blogs-db-version', BP_BLOGS_DB_VERSION );
}


function bp_blogs_check_installed() {	
	global $wpdb, $bp, $userdata;
	
	if ( is_site_admin() ) {
		/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
		if ( get_site_option('bp-blogs-db-version') < BP_BLOGS_DB_VERSION )
			bp_blogs_install();
	}
}
add_action( 'admin_menu', 'bp_blogs_check_installed' );


/**************************************************************************
 bp_blogs_setup_globals()
 
 Set up and add all global variables for this component, and add them to 
 the $bp global variable array.
 **************************************************************************/

function bp_blogs_setup_globals() {
	global $bp, $wpdb;

	$bp->blogs->table_name = $wpdb->base_prefix . 'bp_user_blogs';
	$bp->blogs->table_name_blog_posts = $wpdb->base_prefix . 'bp_user_blogs_posts';
	$bp->blogs->table_name_blog_comments = $wpdb->base_prefix . 'bp_user_blogs_comments';
	$bp->blogs->table_name_blogmeta = $wpdb->base_prefix . 'bp_user_blogs_blogmeta';
	$bp->blogs->format_activity_function = 'bp_blogs_format_activity';
	$bp->blogs->format_notification_function = 'bp_blogs_format_notifications';
	$bp->blogs->image_base = BP_PLUGIN_URL . '/bp-groups/images';
	$bp->blogs->slug = BP_BLOGS_SLUG;

	$bp->version_numbers->blogs = BP_BLOGS_VERSION;
}
add_action( 'plugins_loaded', 'bp_blogs_setup_globals', 5 );	
add_action( 'admin_menu', 'bp_blogs_setup_globals', 1 );

function bp_blogs_setup_root_component() {
	/* Register 'groups' as a root component */
	bp_core_add_root_component( BP_BLOGS_SLUG );
}
add_action( 'plugins_loaded', 'bp_blogs_setup_root_component', 1 );

/**
 * bp_blogs_setup_nav()
 *
 * Adds "Blog" to the navigation arrays for the current and logged in user.
 * 
 * @package BuddyPress Blogs
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_is_home() Checks to see if the current user being viewed is the logged in user
 */
function bp_blogs_setup_nav() {
	global $bp;
	
	/* Add 'Blogs' to the main navigation */
	bp_core_add_nav_item( __( 'Blogs', 'buddypress' ), $bp->blogs->slug );

	if ( $bp->displayed_user->id )
		bp_core_add_nav_default( $bp->blogs->slug, 'bp_blogs_screen_my_blogs', 'my-blogs' );
	
	$blogs_link = $bp->loggedin_user->domain . $bp->blogs->slug . '/';
	
	/* Add the subnav items to the blogs nav item */
	bp_core_add_subnav_item( $bp->blogs->slug, 'my-blogs', __('My Blogs', 'buddypress'), $blogs_link, 'bp_blogs_screen_my_blogs', 'my-blogs-list' );
	bp_core_add_subnav_item( $bp->blogs->slug, 'recent-posts', __('Recent Posts', 'buddypress'), $blogs_link, 'bp_blogs_screen_recent_posts' );
	bp_core_add_subnav_item( $bp->blogs->slug, 'recent-comments', __('Recent Comments', 'buddypress'), $blogs_link, 'bp_blogs_screen_recent_comments' );
	bp_core_add_subnav_item( $bp->blogs->slug, 'create-a-blog', __('Create a Blog', 'buddypress'), $blogs_link, 'bp_blogs_screen_create_a_blog' );
	
	/* Set up the component options navigation for Blog */
	if ( 'blogs' == $bp->current_component ) {
		if ( bp_is_home() ) {
			if ( function_exists('xprofile_setup_nav') ) {
				$bp->bp_options_title = __('My Blogs', 'buddypress'); 
			}
		} else {
			/* If we are not viewing the logged in user, set up the current users avatar and name */
			$bp->bp_options_avatar = bp_core_get_avatar( $bp->displayed_user->id, 1 );
			$bp->bp_options_title = $bp->displayed_user->fullname; 
		}
	}
}
add_action( 'wp', 'bp_blogs_setup_nav', 2 );
add_action( 'admin_menu', 'bp_blogs_setup_nav', 2 );

function bp_blogs_directory_blogs_setup() {
	global $bp;
	
	if ( $bp->current_component == $bp->blogs->slug && empty( $bp->current_action ) ) {
		$bp->is_directory = true;

		wp_enqueue_script( 'bp-blogs-directory-blogs', BP_PLUGIN_URL . '/bp-blogs/js/directory-blogs.js', array( 'jquery', 'jquery-livequery-pack' ) );
		bp_core_load_template( 'directories/blogs/index' );
	}
}
add_action( 'wp', 'bp_blogs_directory_blogs_setup', 5 );

function bp_blogs_screen_my_blogs() {
	do_action( 'bp_blogs_screen_my_blogs' );
	bp_core_load_template( 'blogs/my-blogs' );	
}

function bp_blogs_screen_recent_posts() {
	do_action( 'bp_blogs_screen_recent_posts' );
	bp_core_load_template( 'blogs/recent-posts' );
}

function bp_blogs_screen_recent_comments() {
	do_action( 'bp_blogs_screen_recent_comments' );
	bp_core_load_template( 'blogs/recent-comments' );
}

function bp_blogs_screen_create_a_blog() {
	do_action( 'bp_blogs_screen_create_a_blog' );
	bp_core_load_template( 'blogs/create' );
}


/**************************************************************************
 bp_blogs_record_activity()
 
 Records activity for the logged in user within the friends component so that
 it will show in the users activity stream (if installed)
 **************************************************************************/

function bp_blogs_record_activity( $args = true ) {
	global $bp;
	
	/* Because blog, comment, and blog post code execution happens before anything else
	   we need to manually instantiate the activity component globals */
	if ( !$bp->activity && function_exists('bp_activity_setup_globals') )
		bp_activity_setup_globals();

	if ( function_exists('bp_activity_record') ) {
		extract($args);
						
		bp_activity_record( $item_id, $component_name, $component_action, $is_private, $secondary_item_id, $user_id, $secondary_user_id, $recorded_time );
	}
}

function bp_blogs_delete_activity( $args = true ) {
	if ( function_exists('bp_activity_delete') ) {
		extract($args);
		bp_activity_delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id );
	}
}

/**************************************************************************
 bp_blogs_format_activity()
 
 Selects and formats recorded blogs component activity.
 **************************************************************************/

function bp_blogs_format_activity( $item_id, $user_id, $action, $secondary_item_id = false, $for_secondary_user = false  ) {
	global $bp;
	
	switch( $action ) {
		case 'new_blog':
			$blog = new BP_Blogs_Blog($item_id);
			
			if ( !$blog )
				return false;
			
			if ( !$user_id )
				return false;
			
			$blog_url = get_blog_option( $blog->blog_id, 'siteurl' );
			$user_link = bp_core_get_userlink($user_id);
			$blog_name = get_blog_option( $blog->blog_id, 'blogname' );
				
			return array( 
				'primary_link' => $blog_url,
				'content' => apply_filters( 'bp_blogs_new_blog_activity', sprintf( __( '%s created a new blog: %s', 'buddypress' ), $user_link, '<a href="' . $blog_url . '">' . $blog_name . '</a>' ) . ' <span class="time-since">%s</span>', $user_link, $blog_url, $blog_name )
			);	
		break;
		case 'new_blog_post':
			$post = new BP_Blogs_Post( $item_id );
			
			if ( !$post )
				return false;
			
			$post = BP_Blogs_Post::fetch_post_content($post);
			
			if ( !$post || $post->post_type != 'post' )
				return false;

			$post_link = bp_post_get_permalink( $post, $post->blog_id );
			$user_link = bp_core_get_userlink($user_id);
			
			$content = sprintf( __( '%s wrote a new blog post: %s', 'buddypress' ), $user_link, '<a href="' . $post_link . '">' . $post->post_title . '</a>' ) . ' <span class="time-since">%s</span>';		
			$content .= '<blockquote>' . bp_create_excerpt($post->post_content) . '</blockquote>';
			
			$content = apply_filters( 'bp_blogs_new_post_activity', $content, $user_link, $post );
			
			return array( 
				'primary_link' => $post_link,
				'content' => $content
			);
		break;
		case 'new_blog_comment':
		
			if ( !is_user_logged_in() )
				return false;

			$comment = new BP_Blogs_Comment($secondary_item_id);
			
			if ( !$comment )
				return false;

			$comment = BP_Blogs_Comment::fetch_comment_content($comment);
			
			if ( !$comment )
				return false;
				
			$comment_link = bp_post_get_permalink( $comment->post, $comment->blog_id );
			$user_link = bp_core_get_userlink($user_id);
			
			$content = sprintf( __( '%s commented on the blog post %s', 'buddypress' ), $user_link, '<a href="' . $comment_link . '#comment-' . $comment->comment_ID . '">' . $comment->post->post_title . '</a>' ) . ' <span class="time-since">%s</span>';			
			$content .= '<blockquote>' . bp_create_excerpt($comment->comment_content) . '</blockquote>';
			
			$content = apply_filters( 'bp_blogs_new_comment_activity', $content, $user_link, $comment );

			return array( 
				'primary_link' => $post_link . '#comment-' . $comment->comment_ID,
				'content' => $content
			);
		break;
	}
	
	do_action( 'bp_blogs_format_activity', $action, $item_id, $user_id, $action, $secondary_item_id, $for_secondary_user );
	
	return false;
}

function bp_blogs_record_existing_blogs() {
	global $wpdb;

	$blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM {$wpdb->base_prefix}blogs WHERE public = 1 AND mature = 0 AND spam = 0 AND deleted = 0" ) );
	
	if ( $blog_ids ) {
		foreach( $blog_ids as $blog_id ) {
			$users = get_users_of_blog( $blog_id );

			if ( $users ) {
				foreach ( $users as $user ) {
					$role = unserialize( $user->meta_value );

					if ( !isset( $role['subscriber'] ) )
						bp_blogs_record_blog( $blog_id, $user->user_id );
				}
			}
		}
	}
}


function bp_blogs_record_blog( $blog_id, $user_id ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;
		
	$name = get_blog_option( $blog_id, 'blogname' );
	$description = get_blog_option( $blog_id, 'blogdescription' );
	
	$recorded_blog = new BP_Blogs_Blog;
	$recorded_blog->user_id = $user_id;
	$recorded_blog->blog_id = $blog_id;
	
	$recorded_blog_id = $recorded_blog->save();
	
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'name', $name );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'description', $description );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'last_activity', time() );

	if ( (int)$_POST['blog_public'] )
		$is_private = 0;
	else
		$is_private = 1;

	// Record in activity streams
	bp_blogs_record_activity( array( 'item_id' => $recorded_blog_id, 'component_name' => 'blogs', 'component_action' => 'new_blog', 'is_private' => $is_private, 'user_id' => $recorded_blog->user_id ) );		
	
	do_action( 'bp_blogs_new_blog', $recorded_blog, $is_private, $is_recorded );
}
add_action( 'wpmu_new_blog', 'bp_blogs_record_blog', 10, 2 );

function bp_blogs_record_post( $post_id, $blog_id = false, $user_id = false ) {
	global $bp, $wpdb;

	$post_id = (int)$post_id;
	$post = get_post($post_id);
	
	if ( !$user_id )
		$user_id = (int)$post->post_author;
		
	if ( !$blog_id )
		$blog_id = (int)$wpdb->blogid;

	/* This is to stop infinate loops with Donncha's sitewide tags plugin */
	if ( (int)get_site_option('tags_blog_id') == (int)$blog_id )
		return false;
		
	/* Don't record this if it's not a post */
	if ( $post->post_type != 'post' )
		return false;
	
	if ( !$is_recorded = BP_Blogs_Post::is_recorded( $post_id, $blog_id, $user_id ) ) {
		if ( 'publish' == $post->post_status && '' == $post->post_password ) {
			
			$recorded_post = new BP_Blogs_Post;
			$recorded_post->user_id = $user_id;
			$recorded_post->blog_id = $blog_id;
			$recorded_post->post_id = $post_id;
			$recorded_post->date_created = strtotime( $post->post_date );
			
			$recorded_post_id = $recorded_post->save();
			
			bp_blogs_update_blogmeta( $recorded_post->blog_id, 'last_activity', time() );
			bp_blogs_record_activity( array( 'item_id' => $recorded_post->id, 'component_name' => 'blogs', 'component_action' => 'new_blog_post', 'is_private' => bp_blogs_is_blog_hidden( $recorded_post->blog_id ), 'user_id' => $recorded_post->user_id, 'recorded_time' => strtotime( $post->post_date ) ) );
		}
	} else {
		$existing_post = new BP_Blogs_Post( null, $blog_id, $post_id );

		/**
		 *  Delete the recorded post if:
		 *  - The status is no longer "published"
		 *  - The post is password protected
		 */
		if ( 'publish' != $post->post_status || '' != $post->post_password )
			bp_blogs_remove_post( $post_id, $blog_id );
		
		// Check to see if the post author has changed.
		if ( (int)$existing_post->user_id != (int)$post->post_author ) {
			// Delete the existing recorded post
			bp_blogs_remove_post( $post_id, $blog_id );
			
			// Re-record the post with the new author.
			bp_blogs_record_post( $post_id );
		}
		
		$recorded_post = $existing_post;

		/* Delete and re-add the activity stream item to reflect potential content changes. */
		bp_blogs_delete_activity( array( 'item_id' => $recorded_post->id, 'component_name' => 'blogs', 'component_action' => 'new_blog_post', 'user_id' => $recorded_post->user_id ) );
		bp_blogs_record_activity( array( 'item_id' => $recorded_post->id, 'component_name' => 'blogs', 'component_action' => 'new_blog_post', 'is_private' => bp_blogs_is_blog_hidden( $recorded_post->blog_id ), 'user_id' => $recorded_post->user_id, 'recorded_time' => strtotime( $post->post_date ) ) );
	}

	do_action( 'bp_blogs_new_blog_post', $recorded_post, $is_private, $is_recorded );
}
add_action( 'publish_post', 'bp_blogs_record_post' );
add_action( 'edit_post', 'bp_blogs_record_post' );


function bp_blogs_record_comment( $comment_id, $is_approved ) {
	global $wpdb;
	
	if ( !$is_approved )
		return false;
		
	$comment = get_comment($comment_id);
	
	/* Get the user_id from the author email. */
	$user = get_user_by_email( $comment->comment_author_email );
	$user_id = (int)$user->ID;
	
	if ( !$user_id )
		return false;

	$recorded_comment = new BP_Blogs_Comment;
	$recorded_comment->user_id = $user_id;
	$recorded_comment->blog_id = $wpdb->blogid;
	$recorded_comment->comment_id = $comment_id;
	$recorded_comment->comment_post_id = $comment->comment_post_ID;
	$recorded_comment->date_created = strtotime( $comment->comment_date );

	$recorded_commment_id = $recorded_comment->save();
	
	bp_blogs_update_blogmeta( $recorded_comment->blog_id, 'last_activity', time() );
	bp_blogs_record_activity( array( 'item_id' => $recorded_comment->blog_id, 'secondary_item_id' => $recorded_commment_id, 'component_name' => 'blogs', 'component_action' => 'new_blog_comment', 'is_private' => $is_private, 'user_id' => $recorded_comment->user_id, 'recorded_time' => $recorded_comment->date_created ) );	
}
add_action( 'comment_post', 'bp_blogs_record_comment', 10, 2 );

function bp_blogs_approve_comment( $comment_id, $comment ) {
	global $bp, $wpdb;

	$recorded_comment = bp_blogs_record_comment( $comment_id, true );

	bp_blogs_delete_activity( array( 'item_id' => $recorded_comment->blog_id, 'secondary_item_id' => $recorded_commment_id, 'component_name' => 'blogs', 'component_action' => 'new_blog_comment', 'user_id' => $recorded_comment->user_id ) );
	bp_blogs_record_activity( array( 'item_id' => $recorded_comment->blog_id, 'secondary_item_id' => $recorded_commment_id, 'component_name' => 'blogs', 'component_action' => 'new_blog_comment', 'is_private' => $is_private, 'user_id' => $recorded_comment->user_id, 'recorded_time' => $recorded_comment->date_created ) );	
}
add_action( 'comment_approved_', 'bp_blogs_approve_comment', 10, 2 );

function bp_blogs_unapprove_comment( $comment_id, $status = false ) {
	if ( 'spam' == $status || !$status )
		bp_blogs_remove_comment( $comment_id ); 	
}
add_action( 'comment_unapproved_', 'bp_blogs_unapprove_comment' );
add_action( 'wp_set_comment_status', 'bp_blogs_unapprove_comment', 10, 2 );

function bp_blogs_add_user_to_blog( $user_id, $role, $blog_id ) {
	if ( $role != 'subscriber' ) {
		bp_blogs_record_blog( $blog_id, $user_id );
	}
}
add_action( 'add_user_to_blog', 'bp_blogs_add_user_to_blog', 10, 3 );

function bp_blogs_remove_user_from_blog( $user_id, $blog_id ) {
	bp_blogs_remove_blog_for_user( $user_id, $blog_id );
}
add_action( 'remove_user_from_blog', 'bp_blogs_remove_user_from_blog', 10, 2 );

function bp_blogs_remove_blog( $blog_id ) {
	global $bp;

	$blog_id = (int)$blog_id;

	BP_Blogs_Blog::delete_blog_for_all( $blog_id );
	
	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'component_name' => 'blogs', 'component_action' => 'new_blog', 'user_id' => $bp->loggedin_user->id ) );
	
	do_action( 'bp_blogs_remove_blog', $blog_id );
}
add_action( 'delete_blog', 'bp_blogs_remove_blog' );

function bp_blogs_remove_blog_for_user( $user_id, $blog_id ) {
	global $current_user;
	
	$blog_id = (int)$blog_id;
	$user_id = (int)$user_id;

	BP_Blogs_Blog::delete_blog_for_user( $blog_id, $user_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'component_name' => 'blogs', 'component_action' => 'new_blog', 'user_id' => $current_user->ID ) );

	do_action( 'bp_blogs_remove_blog_for_user', $blog_id, $user_id );
}
add_action( 'remove_user_from_blog', 'bp_blogs_remove_blog_for_user', 10, 2 );

function bp_blogs_remove_post( $post_id ) {
	global $current_blog, $bp;

	$post_id = (int)$post_id;
	$blog_id = (int)$current_blog->blog_id;
	
	$post = new BP_Blogs_Post( null, $blog_id, $post_id );

	// Delete post from the bp_blogs table
	BP_Blogs_Post::delete( $post_id, $blog_id );
		
	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $post->id, 'component_name' => 'blogs', 'component_action' => 'new_blog_post', 'user_id' => $post->user_id ) );

	do_action( 'bp_blogs_remove_post', $blog_id, $post_id, $post->user_id );
}
add_action( 'delete_post', 'bp_blogs_remove_post' );

function bp_blogs_remove_comment( $comment_id ) {
	global $wpdb, $bp;

	$recorded_comment = new BP_Blogs_Comment( false, $wpdb->blogid, $comment_id );
	BP_Blogs_Comment::delete( $comment_id, $wpdb->blogid );	

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $recorded_comment->blog_id, 'secondary_item_id' => $recorded_comment->id, 'component_name' => 'blogs', 'component_action' => 'new_blog_comment', 'user_id' => $recorded_comment->user_id ) );

	do_action( 'bp_blogs_remove_comment', $blog_id, $comment_id, $bp->loggedin_user->id );
}
add_action( 'delete_comment', 'bp_blogs_remove_comment' );

function bp_blogs_remove_data_for_blog( $blog_id ) {
	global $bp;
	
	/* If this is regular blog, delete all data for that blog. */
	BP_Blogs_Blog::delete_blog_for_all( $blog_id );
	BP_Blogs_Post::delete_posts_for_blog( $blog_id );		
	BP_Blogs_Comment::delete_comments_for_blog( $blog_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'component_name' => 'blogs', 'component_action' => false, 'user_id' => $bp->loggedin_user->id ) );

	do_action( 'bp_blogs_remove_data_for_blog', $blog_id );
}
add_action( 'delete_blog', 'bp_blogs_remove_data_for_blog', 1 );

function bp_blogs_get_blogs_for_user( $user_id ) {
	return BP_Blogs_Blog::get_blogs_for_user( $user_id );
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

function bp_blogs_get_random_blog( $limit = null, $page = null ) {
	return BP_Blogs_Blog::get_random( $limit, $page );
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
		$blog = bp_blogs_get_random_blog();

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
	
	// TODO need to look into using this.
	// wp_cache_delete($group_id, 'groups');

	return true;
}

function bp_blogs_get_blogmeta( $blog_id, $meta_key = '') {
	global $wpdb, $bp;
	
	$blog_id = (int) $blog_id;

	if ( !$blog_id )
		return false;

	if ( !empty($meta_key) ) {
		$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);
		
		// TODO need to look into using this.
		//$user = wp_cache_get($user_id, 'users');
		
		// Check the cached user object
		//if ( false !== $user && isset($user->$meta_key) )
		//	$metas = array($user->$meta_key);
		//else
		$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM {$bp->blogs->table_name_blogmeta} WHERE blog_id = %d AND meta_key = %s", $blog_id, $meta_key) );
	} else {
		$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM {$bp->blogs->table_name_blogmeta} WHERE blog_id = %d", $blog_id) );
	}

	if ( empty($metas) ) {
		if ( empty($meta_key) )
			return array();
		else
			return '';
	}

	$metas = array_map('maybe_unserialize', $metas);

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

	// TODO need to look into using this.
	// wp_cache_delete($user_id, 'users');

	return true;
}

function bp_blogs_force_buddypress_theme( $template ) {	
	global $bp;
	
	if ( $bp->current_component == $bp->blogs->slug && empty( $bp->current_action ) ) {
		$member_theme = get_site_option( 'active-member-theme' );

		if ( empty( $member_theme ) )
			$member_theme = 'buddypress-member';

		add_filter( 'theme_root', 'bp_core_set_member_theme_root' );
		add_filter( 'theme_root_uri', 'bp_core_set_member_theme_root_uri' );

		return $member_theme;
	} else {
		return $template;
	}
}
add_filter( 'template', 'bp_blogs_force_buddypress_theme', 1, 1 );

function bp_blogs_force_buddypress_stylesheet( $stylesheet ) {
	global $bp;

	if ( $bp->current_component == $bp->blogs->slug && empty( $bp->current_action ) ) {
		$member_theme = get_site_option( 'active-member-theme' );
	
		if ( empty( $member_theme ) )
			$member_theme = 'buddypress-member';

		add_filter( 'theme_root', 'bp_core_set_member_theme_root' );
		add_filter( 'theme_root_uri', 'bp_core_set_member_theme_root_uri' );

		return $member_theme;
	} else {
		return $stylesheet;
	}
}
add_filter( 'stylesheet', 'bp_blogs_force_buddypress_stylesheet', 1, 1 );



function bp_blogs_remove_data( $user_id ) {
	/* If this is regular blog, delete all data for that blog. */
	BP_Blogs_Blog::delete_blogs_for_user( $user_id );
	BP_Blogs_Post::delete_posts_for_user( $user_id );		
	BP_Blogs_Comment::delete_comments_for_user( $user_id );

	do_action( 'bp_blogs_remove_data', $user_id );
}
add_action( 'wpmu_delete_user', 'bp_blogs_remove_data', 1 );
add_action( 'delete_user', 'bp_blogs_remove_data', 1 );


function bp_blogs_clear_blog_object_cache( $blog_id, $user_id ) {
	wp_cache_delete( 'bp_blogs_of_user_' . $user_id, 'bp' );
	wp_cache_delete( 'bp_blogs_for_user_' . $user_id, 'bp' );

	/* Clear the sitewide activity cache */
	wp_cache_delete( 'sitewide_activity', 'bp' );
}

function bp_blogs_format_clear_blog_cache( $recorded_blog_obj ) {
	bp_blogs_clear_blog_object_cache( false, $recorded_blog_obj->user_id );

	/* Clear the sitewide activity cache */
	wp_cache_delete( 'sitewide_activity', 'bp' );
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