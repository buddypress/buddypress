<?php
require_once( 'bp-core.php' );

define ( 'BP_BLOGS_VERSION', '1.0b1' );

/* These will be moved into admin configurable settings */
define ( 'TOTAL_RECORDED_POSTS', 10 );
define ( 'TOTAL_RECORDED_COMMENTS', 25 );

include_once( 'bp-blogs/bp-blogs-classes.php' );
include_once( 'bp-blogs/bp-blogs-cssjs.php' );
include_once( 'bp-blogs/bp-blogs-templatetags.php' );
include_once( 'bp-blogs/bp-blogs-widgets.php' );
include_once( 'bp-blogs/bp-blogs-ajax.php' );
include_once( 'bp-blogs/directories/bp-blogs-directory-blogs.php' );


/**************************************************************************
 bp_blogs_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function bp_blogs_install( $version ) {
	global $wpdb, $bp;
	
	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	$sql[] = "CREATE TABLE ". $bp['blogs']['table_name'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id int(11) NOT NULL,
				blog_id int(11) NOT NULL,
				KEY user_id (user_id),
				KEY blog_id (blog_id)
			 ) {$charset_collate};";

	$sql[] = "CREATE TABLE ". $bp['blogs']['table_name_blog_posts'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id int(11) NOT NULL,
				blog_id int(11) NOT NULL,
				post_id int(11) NOT NULL,
				date_created datetime NOT NULL,
				KEY user_id (user_id),
				KEY blog_id (blog_id),
				KEY post_id (post_id)
			 ) {$charset_collate};";

	$sql[] = "CREATE TABLE ". $bp['blogs']['table_name_blog_comments'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id int(11) NOT NULL,
				blog_id int(11) NOT NULL,
				comment_id int(11) NOT NULL,
				comment_post_id int(11) NOT NULL,
				date_created datetime NOT NULL,
				KEY user_id (user_id),
				KEY blog_id (blog_id),
				KEY comment_id (comment_id),
				KEY comment_post_id (comment_post_id)
			 ) {$charset_collate};";
	
	$sql[] = "CREATE TABLE ". $bp['blogs']['table_name_blogmeta'] ." (
			id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			blog_id int(11) NOT NULL,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext DEFAULT NULL,
			KEY blog_id (blog_id),
			KEY meta_key (meta_key)
		   ) {$charset_collate};";
		
	
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

	dbDelta($sql);
	
	// dbDelta won't change character sets, so we need to do this seperately.
	// This will only be in here pre v1.0
	$wpdb->query( $wpdb->prepare( "ALTER TABLE " . $bp['blogs']['table_name'] . " DEFAULT CHARACTER SET %s", $wpdb->charset ) );
	$wpdb->query( $wpdb->prepare( "ALTER TABLE " . $bp['blogs']['table_name_blog_posts'] . " DEFAULT CHARACTER SET %s", $wpdb->charset ) );
	$wpdb->query( $wpdb->prepare( "ALTER TABLE " . $bp['blogs']['table_name_blog_comments'] . " DEFAULT CHARACTER SET %s", $wpdb->charset ) );
	
	// On first installation - record all existing blogs in the system.
	if ( !(int)get_site_option( 'bp-blogs-first-install') ) {
		
		bp_blogs_record_existing_blogs();
		add_site_option( 'bp-blogs-first-install', 1 );
		
	} else {
		
		// Import blog titles and descriptions into the blogmeta table 	
		if ( get_site_option( 'bp-blogs-version' ) <= '0.1.5' ) {
			$blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM " . $bp['blogs']['table_name'] ) );

			for ( $i = 0; $i < count($blog_ids); $i++ ) {
				$name = get_blog_option( $blog_ids[$i], 'blogname' );
				$desc = get_blog_option( $blog_ids[$i], 'blogdescription' );
				
				bp_blogs_update_blogmeta( $blog_ids[$i], 'name', $name );
				bp_blogs_update_blogmeta( $blog_ids[$i], 'description', $desc );
				bp_blogs_update_blogmeta( $blog_ids[$i], 'last_activity', time() );
			}
		}
		
	}
	
	add_site_option( 'bp-blogs-version', $version );
}


function bp_blogs_check_installed() {	
	global $wpdb, $bp, $userdata;
	
	if ( is_site_admin() ) {
		/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
		if ( ( $wpdb->get_var("show tables like '%" . $bp['blogs']['table_name'] . "%'") == false ) || ( get_site_option('bp-blogs-version') < BP_BLOGS_VERSION )  )
			bp_blogs_install(BP_BLOGS_VERSION);
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

	$bp['blogs'] = array(
		'table_name' => $wpdb->base_prefix . 'bp_user_blogs',
		'table_name_blog_posts' => $wpdb->base_prefix . 'bp_user_blogs_posts',
		'table_name_blog_comments' => $wpdb->base_prefix . 'bp_user_blogs_comments',
		'table_name_blogmeta' => $wpdb->base_prefix . 'bp_user_blogs_blogmeta',
		'format_activity_function' => 'bp_blogs_format_activity',
		'image_base' => site_url( MUPLUGINDIR . '/bp-groups/images' ),
		'slug'		 => 'blogs'
	);
}
add_action( 'wp', 'bp_blogs_setup_globals', 1 );	
add_action( 'admin_menu', 'bp_blogs_setup_globals', 1 );


/**
 * bp_blogs_setup_nav()
 *
 * Adds "Blog" to the navigation arrays for the current and logged in user.
 * $bp['bp_nav'] represents the main component navigation 
 * $bp['bp_users_nav'] represents the sub navigation when viewing a users
 * profile other than that of the current logged in user.
 * 
 * @package BuddyPress Blogs
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_is_home() Checks to see if the current user being viewed is the logged in user
 */
function bp_blogs_setup_nav() {
	global $bp;
	
	/* Add 'Blogs' to the main navigation */
	bp_core_add_nav_item( __('Blogs', 'buddypress'), $bp['blogs']['slug'] );

	if ( $bp['current_userid'] )
		bp_core_add_nav_default( $bp['blogs']['slug'], 'bp_blogs_screen_my_blogs', 'my-blogs' );
	
	$blogs_link = $bp['loggedin_domain'] . $bp['blogs']['slug'] . '/';
	
	/* Add the subnav items to the blogs nav item */
	bp_core_add_subnav_item( $bp['blogs']['slug'], 'my-blogs', __('My Blogs', 'buddypress'), $blogs_link, 'bp_blogs_screen_my_blogs', 'my-blogs-list' );
	bp_core_add_subnav_item( $bp['blogs']['slug'], 'recent-posts', __('Recent Posts', 'buddypress'), $blogs_link, 'bp_blogs_screen_recent_posts' );
	bp_core_add_subnav_item( $bp['blogs']['slug'], 'recent-comments', __('Recent Comments', 'buddypress'), $blogs_link, 'bp_blogs_screen_recent_comments' );
	bp_core_add_subnav_item( $bp['blogs']['slug'], 'create-a-blog', __('Create a Blog', 'buddypress'), $blogs_link, 'bp_blogs_screen_create_a_blog' );
	
	/* Set up the component options navigation for Blog */
	if ( $bp['current_component'] == 'blogs' ) {
		if ( bp_is_home() ) {
			if ( function_exists('xprofile_setup_nav') ) {
				$bp['bp_options_title'] = __('My Blogs', 'buddypress'); 
			}
		} else {
			/* If we are not viewing the logged in user, set up the current users avatar and name */
			$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = $bp['current_fullname']; 
		}
	}
}
add_action( 'wp', 'bp_blogs_setup_nav', 2 );
add_action( 'admin_menu', 'bp_blogs_setup_nav', 2 );

function bp_blogs_screen_my_blogs() {
	bp_catch_uri( 'blogs/my-blogs' );	
}

function bp_blogs_screen_recent_posts() {
	bp_catch_uri( 'blogs/recent-posts' );
}

function bp_blogs_screen_recent_comments() {
	bp_catch_uri( 'blogs/recent-comments' );
}

function bp_blogs_screen_create_a_blog() {
	bp_catch_uri( 'blogs/create' );
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
	if ( !$bp['activity'] && function_exists('bp_activity_setup_globals') )
		bp_activity_setup_globals();

	if ( function_exists('bp_activity_record') ) {
		extract($args);
				
		bp_activity_record( $item_id, $component_name, $component_action, $is_private, $secondary_item_id, $user_id, $secondary_user_id );
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
				
			return array( 
				'primary_link' => get_blog_option( $blog->blog_id, 'siteurl' ),
				'content' => sprintf( __( '%s created a new blog: %s', 'buddypress' ), bp_core_get_userlink($user_id), '<a href="' . get_blog_option( $blog->blog_id, 'siteurl' ) . '">' . get_blog_option( $blog->blog_id, 'blogname' ) . '</a>' ) . ' <span class="time-since">%s</span>'
			);	
		break;
		case 'new_blog_post':
			$post = new BP_Blogs_Post($item_id);
			
			if ( !$post )
				return false;
			
			$post = BP_Blogs_Post::fetch_post_content($post);
			
			if ( !$post || $post->post_type != 'post' || $post->post_status != 'publish' || $post->post_password != '' )
				return false;

			$post_link = bp_post_get_permalink( $post, $post->blog_id );
			$content = sprintf( __( '%s wrote a new blog post: %s', 'buddypress' ), bp_core_get_userlink($user_id), '<a href="' . $post_link . '">' . $post->post_title . '</a>' ) . ' <span class="time-since">%s</span>';		
			$content .= '<blockquote>' . bp_create_excerpt($post->post_content) . '</blockquote>';
			
			return array( 
				'primary_link' => $post_link,
				'content' => $content
			);
		break;
		case 'new_blog_comment':
		
			if ( !is_user_logged_in() )
				return false;

			$comment = new BP_Blogs_Comment($item_id);
			
			if ( !$comment )
				return false;

			$comment = BP_Blogs_Comment::fetch_comment_content($comment);
			
			if ( !$comment )
				return false;
				
			$post_link = bp_post_get_permalink( $comment->post, $comment->blog_id );
			$content = sprintf( __( '%s commented on the blog post %s', 'buddypress' ), bp_core_get_userlink($user_id), '<a href="' . $post_link . '#comment-' . $comment->comment_ID . '">' . $comment->post->post_title . '</a>' ) . ' <span class="time-since">%s</span>';			
			$content .= '<blockquote>' . bp_create_excerpt($comment->comment_content) . '</blockquote>';

			return array( 
				'primary_link' => $post_link . '#comment-' . $comment->comment_ID,
				'content' => $content
			);
		break;
	}
	
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
	
	if ( !$bp ) {
		bp_core_setup_globals();
		bp_blogs_setup_globals();
	}
	
	if ( !$user_id )
		$user_id = $bp['loggedin_userid'];
		
	$name = get_blog_option( $blog_id, 'blogname' );
	$description = get_blog_option( $blog_id, 'blogdescription' );
	
	$recorded_blog = new BP_Blogs_Blog;
	$recorded_blog->user_id = $user_id;
	$recorded_blog->blog_id = $blog_id;
	
	$recorded_blog_id = $recorded_blog->save();
	
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'name', $name );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'description', $description );
	bp_blogs_update_blogmeta( $recorded_blog->blog_id, 'last_activity', time() );
	
	$is_private = bp_blogs_is_blog_hidden( $recorded_blog_id );
		
	// Record in activity streams
	bp_blogs_record_activity( array( 'item_id' => $recorded_blog_id, 'component_name' => 'blogs', 'component_action' => 'new_blog', 'is_private' => $is_private ) );
	
	do_action( 'bp_blogs_new_blog', $recorded_blog, $is_private );
}
add_action( 'wpmu_new_blog', 'bp_blogs_record_blog', 10, 2 );


function bp_blogs_record_post($post_id) {
	global $bp, $current_blog;
	
	if ( !$bp ) {
		bp_core_setup_globals();
		bp_blogs_setup_globals();
	}
	
	$post_id = (int)$post_id;
	$user_id = (int)$bp['loggedin_userid'];
	$blog_id = (int)$current_blog->blog_id;

	/* This is to stop infinate loops with Donncha's sitewide tags plugin */
	if ( get_site_option('tags_blog_id') == $blog_id )
		return false;
	
	$post = get_post($post_id);
	
	/* Don't record this if it's not a post, not published, or password protected */
	if ( $post->post_type != 'post' || $post->post_status != 'publish' || $post->post_password != '' )
		return false;
	
	/** 
	 * Check how many recorded posts there are for the user. If we are
	 * at the max, then delete the oldest recorded post first.
	 */
	if ( BP_Blogs_Post::get_total_recorded_for_user() >= TOTAL_RECORDED_POSTS )
		BP_Blogs_Post::delete_oldest();

	if ( !BP_Blogs_Post::is_recorded( $post_id, $blog_id ) ) {
		if ( $post->post_status == 'publish' ) {
			$recorded_post = new BP_Blogs_Post;
			$recorded_post->user_id = $user_id;
			$recorded_post->blog_id = $blog_id;
			$recorded_post->post_id = $post_id;
			$recorded_post->date_created = strtotime( $post->post_date );
			
			$recorded_post_id = $recorded_post->save();
			
			bp_blogs_update_blogmeta( $recorded_post->blog_id, 'last_activity', time() );
			
			$is_private = bp_blogs_is_blog_hidden( $recorded_post->blog_id );
			
			// Record in activity streams
			bp_blogs_record_activity( array( 'item_id' => $recorded_post_id, 'component_name' => 'blogs', 'component_action' => 'new_blog_post', 'is_private' => $is_private, 'user_id' => $recorded_post->user_id ) );

			do_action( 'bp_blogs_new_blog_post', $recorded_post, $is_private );
		}
	} else {
		/** 
		 * Check to see if the post have previously been recorded.
		 * If the post status has changed from public to private then we need
		 * to remove the record of the post.
		 */
		if ( $post->post_status != 'publish' )
			BP_Blogs_Post::delete( $post_id, $blog_id );	
	}
}
add_action( 'publish_post', 'bp_blogs_record_post' );

function bp_blogs_record_comment( $comment_id, $from_ajax = false ) {
	global $bp, $current_blog, $current_user;

	if ( !$bp ) {
		bp_core_setup_globals();
		bp_blogs_setup_globals();
	}

	$comment = get_comment($comment_id);
	
	/* Get the user_id from the author email. */
	$user = get_user_by_email( $comment->comment_author_email );
	$user_id = (int)$user->ID;

	/* Only record a comment if it is by a registered user. */
	if ( $user_id ) {
		$comment_id = (int)$comment_id;
		$blog_id = (int)$current_blog->blog_id;
		$post_id = (int)$comment->comment_post_ID;
	
		/** 
		 * Check how many recorded posts there are for the user. If we are
		 * at the max, then delete the oldest recorded post first.
		 */
		if ( BP_Blogs_Comment::get_total_recorded_for_user() >= TOTAL_RECORDED_COMMENTS )
			BP_Blogs_Comment::delete_oldest();

		if ( !BP_Blogs_Comment::is_recorded( $comment_id, $post_id, $blog_id ) ) {
			if ( $comment->comment_approved || $from_ajax ) {
				$recorded_comment = new BP_Blogs_Comment;
				$recorded_comment->user_id = $user_id;
				$recorded_comment->blog_id = $blog_id;
				$recorded_comment->comment_id = $comment_id;
				$recorded_comment->comment_post_id = $post_id;
				$recorded_comment->date_created = strtotime( $comment->comment_date );
					
				$recorded_commment_id = $recorded_comment->save();
				
				bp_blogs_update_blogmeta( $recorded_comment->blog_id, 'last_activity', time() );
				
				$is_private = bp_blogs_is_blog_hidden( $recorded_comment->blog_id );
				
				// Record in activity streams
				bp_blogs_record_activity( array( 'item_id' => $recorded_commment_id, 'component_name' => 'blogs', 'component_action' => 'new_blog_comment', 'is_private' => $is_private, 'user_id' => $user_id ) );

				do_action( 'bp_blogs_new_blog_comment', $recorded_comment, $is_private );
			}
		} else {
			/** 
			 * Check to see if the post have previously been recorded.
			 * If the post status has changed from public to private then we need
			 * to remove the record of the post.
			 */
			if ( !$comment->comment_approved || $comment->comment_approved == 'spam' )
				BP_Blogs_Comment::delete( $comment_id, $blog_id );	
		}
	}
}
add_action( 'comment_post', 'bp_blogs_record_comment', 10, 2 );
add_action( 'edit_comment', 'bp_blogs_record_comment', 10, 2 );


function bp_blogs_modify_comment( $comment_id, $comment_status ) {
	global $bp;
	
	if ( !$bp ) {
		bp_core_setup_globals();
		bp_blogs_setup_globals();
	}
	
	$comment = get_comment($comment_id);
	
	// This is backwards, but it's just the way things work with WP AJAX.
	if ( $comment->comment_approved ) {
		bp_blogs_remove_comment( $comment_id ); 
	} else {
		bp_blogs_record_comment( $comment_id, true ); 		
	}
}
add_action( 'wp_set_comment_status', 'bp_blogs_modify_comment', 10, 2 );

function bp_blogs_remove_blog( $blog_id ) {
	global $bp;
	
	if ( !$bp ) {
		bp_core_setup_globals();
		bp_blogs_setup_globals();
	}
	
	$blog_id = (int)$blog_id;

	BP_Blogs_Blog::delete_blog_for_all( $blog_id );
	
	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $blog_id, 'component_name' => 'blogs', 'component_action' => 'new_blog', 'user_id' => $bp['loggedin_userid'] ) );
	
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
	
	if ( !$bp ) {
		bp_core_setup_globals();
		bp_blogs_setup_globals();
	}
	
	$post_id = (int)$post_id;
	$blog_id = (int)$current_blog->blog_id;

	BP_Blogs_Post::delete( $post_id, $blog_id, $bp['loggedin_userid'] );

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $post_id, 'component_name' => 'blogs', 'component_action' => 'new_blog_post', 'user_id' => $bp['loggedin_userid'] ) );

	do_action( 'bp_blogs_remove_post', $blog_id, $post_id );
}
add_action( 'delete_post', 'bp_blogs_remove_post' );

function bp_blogs_remove_comment( $comment_id ) {
	global $current_blog, $bp;
	
	if ( !$bp ) {
		bp_core_setup_globals();
		bp_blogs_setup_globals();
	}
	
	$comment_id = (int)$comment_id;
	$blog_id = (int)$current_blog->blog_id;
	
	BP_Blogs_Comment::delete( $comment_id, $blog_id, $bp['loggedin_userid'] );	

	// Delete activity stream item
	bp_blogs_delete_activity( array( 'item_id' => $comment_id, 'component_name' => 'blogs', 'component_action' => 'new_blog_comment', 'user_id' => $bp['loggedin_userid'] ) );

	do_action( 'bp_blogs_remove_comment', $blog_id, $comment_id );
}
add_action( 'delete_comment', 'bp_blogs_remove_comment' );

function bp_blogs_remove_data_for_blog( $blog_id ) {
	/* If this is regular blog, delete all data for that blog. */
	BP_Blogs_Blog::delete_blog_for_all( $blog_id );
	BP_Blogs_Post::delete_posts_for_blog( $blog_id );		
	BP_Blogs_Comment::delete_comments_for_blog( $blog_id );

	do_action( 'bp_blogs_remove_data_for_blog', $blog_id );
}
add_action( 'delete_blog', 'bp_blogs_remove_data_for_blog', 1 );

function bp_blogs_register_existing_content( $blog_id ) {
	global $bp, $current_blog;
	
	if ( !$bp ) {
		bp_core_setup_globals();
		bp_blogs_setup_globals();
	}	
	
	$user_id = $bp['loggedin_userid'];
	$blogs = get_blogs_of_user($user_id);

	if ( is_array($blogs) ) {
		foreach ( $blogs as $blog ) {
			bp_blogs_record_blog( (int)$blog->userblog_id, (int)$user_id );

			switch_to_blog( $blog->userblog_id );
			$posts_for_blog = bp_core_get_all_posts_for_user( $user_id );
		
			for ( $i = 0; $i < count($posts); $i++ ) {
				bp_blogs_record_post( $posts[$i] );
			}
			
			do_action( 'bp_blogs_register_existing_content', $blog );
		}
	}	
	
	switch_to_blog( $current_blog->blog_id );
}
add_action( 'bp_homebase_signup_completed', 'bp_blogs_register_existing_content', 10 );

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
	
	if ( $bp['current_component'] == $bp['blogs']['slug'] && isset( $_GET['random-blog'] ) ) {
		$blog_id = bp_blogs_get_random_blog();

		bp_core_redirect( get_blog_option( $blog_id, 'siteurl') );
	}
}
add_action( 'wp', 'bp_blogs_redirect_to_random_blog', 6 );




//
// Blog meta functions
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
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['blogs']['table_name_blogpmeta'] . " WHERE blog_id = %d", $blog_id ) );		
	} else if ( !$meta_value ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['blogs']['table_name_blogmeta'] . " WHERE blog_id = %d AND meta_key = %s AND meta_value = %s", $blog_id, $meta_key, $meta_value ) );
	} else {
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp['blogs']['table_name_blogmeta'] . " WHERE blog_id = %d AND meta_key = %s", $blog_id, $meta_key ) );
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
		$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM " . $bp['blogs']['table_name_blogmeta'] . " WHERE blog_id = %d AND meta_key = %s", $blog_id, $meta_key) );
	} else {
		$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM " . $bp['blogs']['table_name_blogmeta'] . " WHERE blog_id = %d", $blog_id) );
	}

	if ( empty($metas) ) {
		if ( empty($meta_key) )
			return array();
		else
			return '';
	}

	$metas = array_map('maybe_unserialize', $metas);

	if ( count($metas) == 1 )
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

	$cur = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $bp['blogs']['table_name_blogmeta'] . " WHERE blog_id = %d AND meta_key = %s", $blog_id, $meta_key ) );
	
	if ( !$cur ) {
		$wpdb->query( $wpdb->prepare( "INSERT INTO " . $bp['blogs']['table_name_blogmeta'] . " ( blog_id, meta_key, meta_value ) VALUES ( %d, %s, %s )", $blog_id, $meta_key, $meta_value ) );
	} else if ( $cur->meta_value != $meta_value ) {
		$wpdb->query( $wpdb->prepare( "UPDATE " . $bp['blogs']['table_name_blogmeta'] . " SET meta_value = %s WHERE blog_id = %d AND meta_key = %s", $meta_value, $blog_id, $meta_key ) );
	} else {
		return false;
	}

	// TODO need to look into using this.
	// wp_cache_delete($user_id, 'users');

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




?>