<?php
require_once( 'bp-core.php' );

define ( 'BP_BLOGS_IS_INSTALLED', 1 );
define ( 'BP_BLOGS_VERSION', '0.1.1' );

/* These will be moved into admin configurable settings */
define ( 'TOTAL_RECORDED_POSTS', 10 );
define ( 'TOTAL_RECORDED_COMMENTS', 25 );

include_once( 'bp-blogs/bp-blogs-classes.php' );
//include_once( 'bp-blogs/bp-blogs-ajax.php' );
include_once( 'bp-blogs/bp-blogs-cssjs.php' );
/*include_once( 'bp-blogs/bp-blogs-admin.php' );*/
include_once( 'bp-blogs/bp-blogs-templatetags.php' );


/**************************************************************************
 bp_blogs_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function bp_blogs_install( $version ) {
	global $wpdb, $bp;
	
	$sql[] = "CREATE TABLE ". $bp['blogs']['table_name'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT,
				user_id int(11) NOT NULL,
				blog_id int(11) NOT NULL,
		    	PRIMARY KEY id (id),
				KEY user_id (user_id),
				KEY blog_id (blog_id)
			 );";

	$sql[] = "CREATE TABLE ". $bp['blogs']['table_name_blog_posts'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT,
				user_id int(11) NOT NULL,
				blog_id int(11) NOT NULL,
				post_id int(11) NOT NULL,
				date_created datetime NOT NULL,
		    	PRIMARY KEY id (id),
				KEY user_id (user_id),
				KEY blog_id (blog_id),
				KEY post_id (post_id)
			 );";

	$sql[] = "CREATE TABLE ". $bp['blogs']['table_name_blog_comments'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT,
				user_id int(11) NOT NULL,
				blog_id int(11) NOT NULL,
				comment_id int(11) NOT NULL,
				comment_post_id int(11) NOT NULL,
				date_created datetime NOT NULL,
		    	PRIMARY KEY id (id),
				KEY user_id (user_id),
				KEY blog_id (blog_id),
				KEY comment_id (comment_id),
				KEY comment_post_id (comment_post_id)
			 );";
			
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

	dbDelta($sql);
	add_site_option( 'bp-blogs-version', $version );
}

/**************************************************************************
 bp_blogs_add_admin_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function bp_blogs_add_admin_menu() {	
	global $wpdb, $bp, $userdata;

	if ( $wpdb->blogid == $bp['current_homebase_id'] ) {
		add_menu_page( __("Blogs"), __("Blogs"), 10, 'bp-blogs/admin-tabs/bp-blogs-tab.php' );
		add_submenu_page( 'bp-blogs/admin-tabs/bp-blogs-tab.php', __("My Blogs"), __("My Blogs"), 10, 'bp-blogs/admin-tabs/bp-blogs-tab.php' );
		add_submenu_page( 'bp-blogs/admin-tabs/bp-blogs-tab.php', __('Recent Posts'), __('Recent Posts'), 10, 'bp-blogs/admin-tabs/bp-blogs-posts-tab.php' );		
		add_submenu_page( 'bp-blogs/admin-tabs/bp-blogs-tab.php', __('Recent Comments'), __('Recent Comments'), 10, 'bp-blogs/admin-tabs/bp-blogs-comments-tab.php' );		
	}

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( ( $wpdb->get_var("show tables like '%" . $bp['blogs']['table_name'] . "%'") == false ) || ( get_site_option('bp-blogs-version') < BP_BLOGS_VERSION )  )
		bp_blogs_install(BP_BLOGS_VERSION);
}
add_action( 'admin_menu', 'bp_blogs_add_admin_menu' );


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
		'format_activity_function' => 'bp_blogs_format_activity',
		'image_base' => get_option('siteurl') . '/wp-content/mu-plugins/bp-groups/images',
		'slug'		 => 'blogs'
	);
}
add_action( 'wp', 'bp_blogs_setup_globals', 1 );	
add_action( '_admin_menu', 'bp_blogs_setup_globals', 1 );


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
	bp_core_add_nav_item( __('Blogs'), $bp['blogs']['slug'] );
	bp_core_add_nav_default( $bp['blogs']['slug'], 'bp_blogs_screen_my_blogs', 'my-blogs' );
	
	$blogs_link = $bp['loggedin_domain'] . $bp['blogs']['slug'] . '/';
	
	/* Add the subnav items to the blogs nav item */
	bp_core_add_subnav_item( $bp['blogs']['slug'], 'my-blogs', __('My Blogs'), $blogs_link, 'bp_blogs_screen_my_blogs' );
	bp_core_add_subnav_item( $bp['blogs']['slug'], 'recent-posts', __('Recent Posts'), $blogs_link, 'bp_blogs_screen_recent_posts' );
	bp_core_add_subnav_item( $bp['blogs']['slug'], 'recent-comments', __('Recent Comments'), $blogs_link, 'bp_blogs_screen_recent_comments' );
	bp_core_add_subnav_item( $bp['blogs']['slug'], 'create-a-blog', __('Create a Blog'), $blogs_link, 'bp_blogs_screen_create_a_blog' );
	
	/* Set up the component options navigation for Blog */
	if ( $bp['current_component'] == 'blogs' ) {
		if ( bp_is_home() ) {
			if ( function_exists('xprofile_setup_nav') ) {
				$bp['bp_options_title'] = __('My Blogs'); 
			}
		} else {
			/* If we are not viewing the logged in user, set up the current users avatar and name */
			$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = $bp['current_fullname']; 
		}
	}
}
add_action( 'wp', 'bp_blogs_setup_nav', 2 );

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
		bp_activity_record( $item_id, $component_name, $component_action, $is_private );
	}
}
add_action( 'bp_blogs_new_blog', 'bp_blogs_record_activity' );
add_action( 'bp_blogs_new_blog_post', 'bp_blogs_record_activity' );
add_action( 'bp_blogs_new_blog_comment', 'bp_blogs_record_activity' );

/**************************************************************************
 bp_blogs_format_activity()
 
 Selects and formats recorded blogs component activity.
 **************************************************************************/

function bp_blogs_format_activity( $item_id, $action, $for_secondary_user = false  ) {
	global $bp;
	
	switch( $action ) {
		case 'new_blog':
			$blog = new BP_Blogs_Blog($item_id);
			
			if ( !$blog )
				return false;
				
			return bp_core_get_userlink($blog->user_id) . ' ' . __('created a new blog:') . ' <a href="' . get_blog_option( $blog->blog_id, 'siteurl' ) . '">' . get_blog_option( $blog->blog_id, 'blogname' ) . '</a> <span class="time-since">%s</span>';		
		break;
		case 'new_blog_post':
			$post = new BP_Blogs_Post($item_id);
			
			if ( !$post )
				return false;
			
			$post = BP_Blogs_Post::fetch_post_content($post);

			$content = bp_core_get_userlink($post->user_id) . ' ' . __('wrote a new blog post') . ' <a href="' . bp_post_get_permalink( $post, $post->blog_id ) . '">' . $post->post_title . '</a> <span class="time-since">%s</span>';		
			$content .= '<blockquote>' . bp_create_excerpt($post->post_content) . '</blockquote>';
			return $content;
		break;
		case 'new_blog_comment':
		
			if ( !is_user_logged_in() )
				return false;

			$comment = new BP_Blogs_Comment($item_id);
			
			if ( !$comment )
				return false;

			$comment = BP_Blogs_Comment::fetch_comment_content($comment);
			$content = bp_core_get_userlink($comment->user_id) . ' ' . __('commented on the blog post ') . ' <a href="' . bp_post_get_permalink( $comment->post, $comment->blog_id ) . '#comment-' . $comment->comment_ID . '">' . $comment->post->post_title . '</a> <span class="time-since">%s</span>';		
			$content .= '<blockquote>' . bp_create_excerpt($comment->comment_content) . '</blockquote>';
			return $content;
		break;
	}
	
	return false;
}


function bp_blogs_record_blog( $blog_id = '', $user_id = '' ) {
	global $bp;
	
	if ( !$bp ) {
		bp_core_setup_globals();
		bp_blogs_setup_globals();
	}
	
	if ( !$user_id )
		$user_id = $bp['loggedin_userid'];
	
	if ( !get_usermeta( $user_id, 'home_base' ) )
		return false;
		
	if ( (int)$blog_id != (int)get_usermeta( $user_id, 'home_base' ) ) {		
		$recorded_blog = new BP_Blogs_Blog;
		$recorded_blog->user_id = $user_id;
		$recorded_blog->blog_id = $blog_id;

		$recorded_blog_id = $recorded_blog->save();
		
		do_action( 'bp_blogs_new_blog', array( 'item_id' => $recorded_blog_id, 'component_name' => 'blogs', 'component_action' => 'new_blog', 'is_private' => 0 ) );
	}
}
add_action( 'wpmu_new_blog', 'bp_blogs_record_blog', 10 );

function bp_blogs_record_post($post_id = '') {
	global $bp, $current_blog;
	
	if ( !$bp ) {
		bp_core_setup_globals();
		bp_blogs_setup_globals();
	}
	
	$post_id = (int)$post_id;
	$user_id = (int)$bp['loggedin_userid'];
	$blog_id = (int)$current_blog->blog_id;
	
	$post = get_post($post_id);
	
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
			
			do_action( 'bp_blogs_new_blog_post', array( 'item_id' => $recorded_post_id, 'component_name' => 'blogs', 'component_action' => 'new_blog_post', 'is_private' => 0 ) );
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

function bp_blogs_record_comment( $comment_id = '', $from_ajax = false ) {
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

				do_action( 'bp_blogs_new_blog_comment', array( 'item_id' => $recorded_commment_id, 'component_name' => 'blogs', 'component_action' => 'new_blog_comment', 'is_private' => 0 ) );
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
add_action( 'comment_post', 'bp_blogs_record_comment' );
add_action( 'edit_comment', 'bp_blogs_record_comment' );


function bp_blogs_modify_comment( $comment_id = '', $comment_status = '' ) {
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
add_action( 'wp_set_comment_status', 'bp_blogs_modify_comment' );

function bp_blogs_remove_blog( $blog_id = '' ) {
	$blog_id = (int)$blog_id;

	BP_Blogs_Blog::delete_blog_for_all( $blog_id );
}
add_action( 'delete_blog', 'bp_blogs_remove_blog' );

function bp_blogs_remove_blog_for_user( $user_id = '', $blog_id = '' ) {
	$blog_id = (int)$blog_id;
	$user_id = (int)$user_id;

	BP_Blogs_Blog::delete_blog_for_user( $blog_id, $user_id );
}
add_action( 'remove_user_from_blog', 'bp_blogs_remove_blog' );

function bp_blogs_remove_post( $post_id = '' ) {
	global $current_blog, $bp;
	
	if ( !$bp ) {
		bp_core_setup_globals();
		bp_blogs_setup_globals();
	}
	
	$post_id = (int)$post_id;
	$blog_id = (int)$current_blog->blog_id;

	BP_Blogs_Post::delete( $post_id, $blog_id, $bp['loggedin_userid'] );
}
add_action( 'delete_post', 'bp_blogs_remove_post' );

function bp_blogs_remove_comment( $comment_id = '' ) {
	global $current_blog, $bp;
	
	if ( !$bp ) {
		bp_core_setup_globals();
		bp_blogs_setup_globals();
	}
	
	$comment_id = (int)$comment_id;
	$blog_id = (int)$current_blog->blog_id;
	
	BP_Blogs_Comment::delete( $comment_id, $blog_id, $bp['loggedin_userid'] );	
}
add_action( 'delete_comment', 'bp_blogs_remove_comment' );

function bp_blogs_remove_data( $blog_id ) {

	if ( $user_id = bp_core_get_homebase_userid( $blog_id ) ) {
		/* If this is a home base, delete everything for that user. */
		BP_Blogs_Blog::delete_blogs_for_user( $user_id );
		BP_Blogs_Post::delete_posts_for_user( $user_id );
		BP_Blogs_Comment::delete_comments_for_user( $user_id );
	} else {
		/* If this is regular blog, delete all data for that blog. */
		BP_Blogs_Blog::delete_blog_for_all( $blog_id );
		BP_Blogs_Post::delete_posts_for_blog( $blog_id );		
		BP_Blogs_Comment::delete_comments_for_blog( $blog_id );
	}
	
}
add_action( 'delete_blog', 'bp_blogs_remove_data', 1 );

function bp_blogs_register_existing_content( $blog_id ) {
	global $wpdb, $bp;
	
	if ( !$bp ) {
		bp_core_setup_globals();
		bp_blogs_setup_globals();
	}	
	
	$user_id = $bp['loggedin_userid'];
	$blogs = get_blogs_of_user($user_id);

	if ( is_array($blogs) ) {
		foreach ( $blogs as $blog ) {
			if ( (int)$blog->userblog_id != (int)get_usermeta( $user_id, 'home_base' ) ) {
				bp_blogs_record_blog( (int)$blog->userblog_id, (int)$user_id );

				$wpdb->set_blog_id( $blog->userblog_id );
				$posts_for_blog = bp_core_get_all_posts_for_user( $user_id );
			
				for ( $i = 0; $i < count($posts); $i++ ) {
					bp_blogs_record_post( $posts[$i] );
				}
			}
		}
	}	
}
add_action( 'bp_homebase_signup_completed', 'bp_blogs_register_existing_content', 10 );


?>