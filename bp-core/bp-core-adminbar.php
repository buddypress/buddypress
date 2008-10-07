<?php

function bp_core_admin_bar() {
	global $bp, $wpdb;

	if ( is_user_logged_in() && bp_core_user_has_home() ) {
		echo '<div id="wp-admin-bar">';
		echo '<a href="' . get_blog_option( 1, 'siteurl' ) . '"><img id="admin-bar-logo" src="' . site_url() . '/wp-content/mu-plugins/bp-core/images/admin_bar_logo.gif" alt="BuddyPress" /></a>';
		echo '<ul class="main-nav">';
		
		echo '<li><a href="">';
		
		if ( function_exists('bp_core_get_avatar') )
		 	bp_core_get_avatar( $bp['loggedin_userid'], 1 );
		
		echo __('My Account') . '</a>';
		echo '<ul>';
		
		/* Loop through each navigation item */
		foreach( $bp['bp_nav'] as $nav_item ) {
			echo '<li>';
			echo '<a id="' . $nav_item['id'] . '" href="' . $nav_item['link'] . '">' . $nav_item['name'] . '</a>';

			if ( is_array( $bp['bp_options_nav'][$nav_item['id']] ) ) {
				echo '<ul>';
				foreach( $bp['bp_options_nav'][$nav_item['id']] as $subnav_item ) {
					echo '<li><a id="' . $subnav_item['id'] . '" href="' . $subnav_item['link'] . '">' . $subnav_item['name'] . '</a></li>';				
				}
				echo '</ul>';
			}
			
			echo '</li>';
		}
		echo '<li><a id="logout" href="' . site_url() . '/wp-login.php?action=logout">' . __('Log Out') . '</a></li>';
		echo '</ul>';
		echo '</li>';
		
		/* List out the blogs for the user */
		
		if ( function_exists('bp_blogs_install') ) {
			$blogs = BP_Blogs_Blog::get_blogs_for_user( $bp['loggedin_userid'] );

			echo '<li><a href="' . $bp['loggedin_domain'] . $bp['blogs']['slug'] . '/my-blogs">';
			_e('My Blogs');
			echo '</a>';

			echo '<ul>';			
			if ( is_array( $blogs['blogs'] ) ) {

				foreach( $blogs['blogs'] as $blog ) {
					echo '<li>';
					echo '<div class="admin-bar-clear"><a href="' . $blog['siteurl'] . '">' . $blog['title'] . '</a>';
					echo '</div>';
					
					echo '<ul>';
					echo '<li><a href="' . $blog['siteurl']  . '/wp-admin/">' . __('Dashboard') . '</a></li>';
					echo '<li><a href="' . $blog['siteurl']  . '/wp-admin/post-new.php">' . __('New Post') . '</a></li>';
					echo '<li><a href="' . $blog['siteurl']  . '/wp-admin/post-new.php">' . __('Manage Posts') . '</a></li>';
					echo '<li><a href="' . $blog['siteurl']  . '/wp-admin/themes.php">' . __('Switch Theme') . '</a></li>';					
					echo '<li><a href="' . $blog['siteurl']  . '/wp-admin/edit-comments.php">' . __('Manage Comments') . '</a></li>';					
					echo '</ul>';
					
					echo '</li>';
				}
			} else {
				echo '<li>';
				echo '<a href="' . $bp['loggedin_domain'] . $bp['blogs']['slug'] . '/create-a-blog">' . __('Create a Blog!') . '</a>';
				echo '</li>';
			}
			echo '</ul>';
			echo '</li>';
		}

		if ( bp_core_is_home_base( $wpdb->blogid ) ) {
			// TODO: possible menu for current group/user/photo etc
		} else {
			$authors = get_users_of_blog(); 
			
			if ( is_array( $authors ) ) {
				/* This is a blog, render a menu with links to all authors */
				echo '<li><a href="/">';
				_e('Blog Authors');
				echo '</a>';
				
				echo '<ul class="author-list">';
				foreach( $authors as $author ) {
					$author = new BP_Core_User( $author->user_id );
					echo '<li>';

					echo '<div class="admin-bar-clear"><a href="' . $author->user_url . '">';
					echo $author->avatar_mini;
					echo ' ' . $author->fullname;
					echo '<span class="activity">' . $author->last_active . '</span>';
					echo '</a>';
					echo '</div>';
					echo '</li>';
				}
				echo '</ul>';
				echo '</li>';
			}
		}
				
		echo '</ul>';
		echo '</div>';
	}
}

add_action( 'wp_footer', 'bp_core_admin_bar' );
//add_action( 'admin_footer', 'bp_core_admin_bar' )

?>