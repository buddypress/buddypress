<?php

function bp_core_admin_bar() {
	global $bp, $wpdb, $current_blog;

	if ( is_user_logged_in() ) {
		echo '<div id="wp-admin-bar">';
		echo '<a href="' . get_blog_option( 1, 'siteurl' ) . '"><img id="admin-bar-logo" src="' . site_url() . '/wp-content/mu-plugins/bp-core/images/admin_bar_logo.gif" alt="BuddyPress" /></a>';
		echo '<ul class="main-nav">';
		
		echo '<li><a href="">';
		
		if ( function_exists('bp_core_get_avatar') )
		 	bp_core_get_avatar( $bp['loggedin_userid'], 1 );
		
		echo __('My Account', 'buddypress') . '</a>';
		echo '<ul>';
		
		/* Loop through each navigation item */
		$counter = 0;
		foreach( $bp['bp_nav'] as $nav_item ) {
			$alt = ( $counter % 2 == 0 ) ? ' class="alt"' : '';
			echo '<li' . $alt . '>';
			echo '<a id="' . $nav_item['css_id'] . '" href="' . $nav_item['link'] . '">' . $nav_item['name'] . '</a>';

			if ( is_array( $bp['bp_options_nav'][$nav_item['css_id']] ) ) {
				echo '<ul>';
				$sub_counter = 0;
				foreach( $bp['bp_options_nav'][$nav_item['css_id']] as $subnav_item ) {
					$alt = ( $sub_counter % 2 == 0 ) ? ' class="alt"' : '';
					echo '<li' . $alt . '><a id="' . $subnav_item['css_id'] . '" href="' . $subnav_item['link'] . '">' . $subnav_item['name'] . '</a></li>';				
					$sub_counter++;
				}
				echo '</ul>';
			}
			
			echo '</li>';
			$counter++;
		}
		echo '<li><a id="logout" href="' . site_url() . '/wp-login.php?action=logout">' . __('Log Out', 'buddypress') . '</a></li>';
		echo '</ul>';
		echo '</li>';
		
		/* List out the blogs for the user */
		
		if ( function_exists('bp_blogs_install') ) {
			$blogs = BP_Blogs_Blog::get_blogs_for_user( $bp['loggedin_userid'] );

			echo '<li><a href="' . $bp['loggedin_domain'] . $bp['blogs']['slug'] . '/my-blogs">';
			_e('My Blogs', 'buddypress');
			echo '</a>';
			
			echo '<ul>';			
			if ( is_array( $blogs['blogs'] ) ) {
				
				$counter = 0;
				foreach( $blogs['blogs'] as $blog ) {
					$alt = ( $counter % 2 == 0 ) ? ' class="alt"' : '';
					echo '<li' . $alt . '>';
					echo '<div class="admin-bar-clear"><a href="' . $blog['siteurl'] . '">' . $blog['title'] . '</a>';
					echo '</div>';
					
					echo '<ul>';
					echo '<li class="alt"><a href="' . $blog['siteurl']  . '/wp-admin/">' . __('Dashboard', 'buddypress') . '</a></li>';
					echo '<li><a href="' . $blog['siteurl']  . '/wp-admin/post-new.php">' . __('New Post', 'buddypress') . '</a></li>';
					echo '<li class="alt"><a href="' . $blog['siteurl']  . '/wp-admin/edit.php">' . __('Manage Posts', 'buddypress') . '</a></li>';
					echo '<li><a href="' . $blog['siteurl']  . '/wp-admin/themes.php">' . __('Switch Theme', 'buddypress') . '</a></li>';					
					echo '<li class="alt"><a href="' . $blog['siteurl']  . '/wp-admin/edit-comments.php">' . __('Manage Comments', 'buddypress') . '</a></li>';					
					echo '</ul>';
					
					echo '</li>';
					$counter++;
				}
			} else {
				echo '<li>';
				echo '<a href="' . $bp['loggedin_domain'] . $bp['blogs']['slug'] . '/create-a-blog">' . __('Create a Blog!', 'buddypress') . '</a>';
				echo '</li>';
			}
			echo '</ul>';
			echo '</li>';
		}
		
		/* Show notifications for this user */
		echo '<li id="notifications_menu"><a href="' . $bp['loggedin_domain'] . '">';
		_e('Notifications', 'buddypress');
		
		if ( $notifications = bp_core_get_notifications_for_user( $bp['loggedin_userid']) ) { ?>
			<span><?php echo count($notifications) ?></span>
		<?php
		}
		echo '</a>';
		echo '<ul>';
		if ( $notifications ) { ?>
			<?php $counter = 0; ?>
			<?php for ( $i = 0; $i < count($notifications); $i++ ) { ?>
				<?php $alt = ( $counter % 2 == 0 ) ? ' class="alt"' : ''; ?>
				<li<?php echo $alt ?>><?php echo $notifications[$i] ?></li>
				<?php $counter++; ?>
			<?php } ?>
		<?php } else { ?>
			<li><a href="<?php echo $bp['loggedin_domain'] ?>"><?php _e( 'No new notifications.', 'buddypress' ); ?></a></li>
		<?php
		}
		echo '</ul>';
		echo '</li>';
		
		if ( $current_blog->blog_id > 1 ) {
			$authors = get_users_of_blog(); 
		
			if ( is_array( $authors ) ) {
				/* This is a blog, render a menu with links to all authors */
				echo '<li><a href="/">';
				_e('Blog Authors', 'buddypress');
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