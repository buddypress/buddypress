<?php

function bp_core_admin_bar() {
	global $bp, $wpdb, $current_blog, $doing_admin_bar;
	
	if ( defined( 'BP_DISABLE_ADMIN_BAR' ) )
		return false;
	
	$doing_admin_bar = true;
	
	if ( (int)get_site_option( 'hide-loggedout-adminbar' ) && !is_user_logged_in() )
		return false;

	echo '<div id="wp-admin-bar">';

	// **** Do bp-adminbar-logo Actions ********
	do_action( 'bp_adminbar_logo' );

	echo '<ul class="main-nav">';
	
	// **** Do bp-adminbar-menus Actions ********
	do_action( 'bp_adminbar_menus' );

	echo '</ul>';
	echo '</div>';
}

// **** Default BuddyPress admin bar logo ********
function bp_adminbar_logo() {
	global $bp;
	
	echo '<a href="' . $bp->root_domain . '" id="admin-bar-logo">' . get_blog_option( BP_ROOT_BLOG, 'blogname') . '</a>';
}

// **** "Log In" and "Sign Up" links (Visible when not logged in) ********
function bp_adminbar_login_menu() {
	global $bp;

	if ( !is_user_logged_in() ) {	
		echo '<li class="bp-login no-arrow"><a href="' . $bp->root_domain . '/wp-login.php?redirect_to=' . urlencode( $bp->root_domain ) . '">' . __( 'Log In', 'buddypress' ) . '</a></li>';
		
		// Show "Sign Up" link if user registrations are allowed
		if ( get_site_option( 'registration' ) != 'none' && get_site_option( 'registration' ) != 'blog' ) {
			echo '<li class="bp-signup no-arrow"><a href="' . bp_signup_page(false) . '">' . __( 'Sign Up', 'buddypress' ) . '</a></li>';
		}
	}
}

// **** "My Account" Menu ******
function bp_adminbar_account_menu() {
	global $bp;

	if ( !$bp->bp_nav )
		return false;

	if ( is_user_logged_in() ) {
		
		echo '<li id="bp-adminbar-account-menu"><a href="">';
	
		echo __( 'My Account', 'buddypress' ) . '</a>';
		echo '<ul>';
	
		/* Loop through each navigation item */
		$counter = 0;
		foreach( $bp->bp_nav as $nav_item ) {
			$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';
			
			echo '<li' . $alt . '>';
			echo '<a id="bp-admin-' . $nav_item['css_id'] . '" href="' . $nav_item['link'] . '">' . $nav_item['name'] . '</a>';
			
			if ( is_array( $bp->bp_options_nav[$nav_item['css_id']] ) ) {
				echo '<ul>';
				$sub_counter = 0;

				foreach( $bp->bp_options_nav[$nav_item['css_id']] as $subnav_item ) {
					$alt = ( 0 == $sub_counter % 2 ) ? ' class="alt"' : '';
					echo '<li' . $alt . '><a id="bp-admin-' . $subnav_item['css_id'] . '" href="' . $subnav_item['link'] . '">' . $subnav_item['name'] . '</a></li>';				
					$sub_counter++;
				}
				echo '</ul>';
			}
		
			echo '</li>';
			
			$counter++;
		}
	
		$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';

		if ( function_exists('wp_logout_url') ) { 
			echo '<li' . $alt . '><a id="bp-admin-logout" href="' . wp_logout_url(site_url()) . '">' . __( 'Log Out', 'buddypress' ) . '</a></li>';                   
		} else {  
			echo '<li' . $alt . '><a id="bp-admin-logout" href="' . site_url() . '/wp-login.php?action=logout&amp;redirect_to=' . site_url() . '">' . __( 'Log Out', 'buddypress' ) . '</a></li>'; 
		} 
		   	 
 		echo '</ul>';
		echo '</li>';
	}
}

// return a string indicating user's role in that blog
function get_blog_role_for_user( $user, $blog ) {
	
	// If the user is a site admin, just display admin.
	if ( is_site_admin() ) 
		return __( 'Admin', 'buddypress');
	
	$roles = get_usermeta( $user, 'wp_' . $blog . '_capabilities' );

	if ( isset( $roles['subscriber'] ) )
		$role = __( 'Subscriber', 'buddypress' ); 
	elseif	( isset( $roles['contributor'] ) )
		$role = __( 'Contributor', 'buddypress' );
	elseif	( isset( $roles['author'] ) )
		$role = __( 'Author', 'buddypress' );
	elseif ( isset( $roles['editor'] ) )
		$role = __( 'Editor', 'buddypress' );
	elseif ( isset( $roles['administrator'] ) )
		$role = __( 'Admin', 'buddypress' );
	else
		return false;
	
	return $role;
}

// *** "My Blogs" Menu ********
function bp_adminbar_blogs_menu() {
	if ( is_user_logged_in() ) {
		global $bp; 
	
		if ( function_exists('bp_blogs_install') ) {
			
			if ( !$blogs = wp_cache_get( 'bp_blogs_of_user_' . $bp->loggedin_user->id, 'bp' ) ) {
				$blogs = get_blogs_of_user( $bp->loggedin_user->id );
				wp_cache_set( 'bp_blogs_of_user_' . $bp->loggedin_user->id, $blogs, 'bp' );
			}

			echo '<li id="bp-adminbar-blogs-menu"><a href="' . $bp->loggedin_user->domain . $bp->blogs->slug . '/my-blogs">';
			
			_e( 'My Blogs', 'buddypress' );
			
			echo '</a>';
	
			echo '<ul>';			
			if ( is_array( $blogs )) {
		
				$counter = 0;
				foreach( $blogs as $blog ) {
					$role = get_blog_role_for_user( $bp->loggedin_user->id, $blog->userblog_id );

					$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';
					echo '<li' . $alt . '>';
					echo '<a href="' . $blog->siteurl . '">' . $blog->blogname . ' (' . $role . ')</a>';
					if ( !( 'Subscriber' == $role ) ) { // then they have something to display on the flyout menu
						echo '<ul>';
						echo '<li class="alt"><a href="' . $blog->siteurl  . '/wp-admin/">' . __('Dashboard', 'buddypress') . '</a></li>';
						echo '<li><a href="' . $blog->siteurl  . '/wp-admin/post-new.php">' . __('New Post', 'buddypress') . '</a></li>';
						echo '<li class="alt"><a href="' . $blog->siteurl  . '/wp-admin/edit.php">' . __('Manage Posts', 'buddypress') . '</a></li>';
						echo '<li><a href="' . $blog->siteurl  . '/wp-admin/edit-comments.php">' . __('Manage Comments', 'buddypress') . '</a></li>';					
						if ( 'Admin' == $role ) {	
							echo '<li class="alt"><a href="' . $blog->siteurl  . '/wp-admin/themes.php">' . __('Switch Theme', 'buddypress') . '</a></li>'; 
						}					
						echo '</ul>';					
					}
					echo '</li>';
					$counter++;
				}
			}
	
			$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';
			
			if ( bp_blog_signup_enabled() ) {
				echo '<li' . $alt . '>';
				echo '<a href="' . $bp->loggedin_user->domain . $bp->blogs->slug . '/create-a-blog">' . __('Create a Blog!', 'buddypress') . '</a>';
				echo '</li>';
			}
			
			echo '</ul>';
			echo '</li>';
		}
	}
}	

// **** "Notifications" Menu *********
function bp_adminbar_notifications_menu() {	
	if ( is_user_logged_in() ) {
		global $bp;
		
		echo '<li id="bp-adminbar-notifications-menu"><a href="' . $bp->loggedin_user->domain . '">';
		_e( 'Notifications', 'buddypress' );
	
		if ( $notifications = bp_core_get_notifications_for_user( $bp->loggedin_user->id ) ) { ?>
			<span><?php echo count($notifications) ?></span>
		<?php
		}
		
		echo '</a>';
		echo '<ul>';
		
		if ( $notifications ) { ?>
			<?php $counter = 0; ?>
			<?php for ( $i = 0; $i < count($notifications); $i++ ) { ?>
				<?php $alt = ( 0 == $counter % 2 ) ? ' class="alt"' : ''; ?>
				<li<?php echo $alt ?>><?php echo $notifications[$i] ?></li>
				<?php $counter++; ?>
			<?php } ?>
		<?php } else { ?>
			<li><a href="<?php echo $bp->loggedin_user->domain ?>"><?php _e( 'No new notifications.', 'buddypress' ); ?></a></li>
		<?php
		}
		
		echo '</ul>';
		echo '</li>';
	}
}

// **** "Blog Authors" Menu (visible when not logged in) ********
function bp_adminbar_authors_menu() {
	global $current_blog;
	
	if ( $current_blog->blog_id > 1 ) {
		$authors = get_users_of_blog(); 
	
		if ( is_array( $authors ) ) {
			/* This is a blog, render a menu with links to all authors */
			echo '<li id="bp-adminbar-authors-menu"><a href="/">';
			_e('Blog Authors', 'buddypress');
			echo '</a>';
		
			echo '<ul class="author-list">';
			foreach( $authors as $author ) {
				$author = new BP_Core_User( $author->user_id );
				echo '<li>';

				echo '<a href="' . $author->user_url . '">';
				echo $author->avatar_mini;
				echo ' ' . $author->fullname;
				echo '<span class="activity">' . $author->last_active . '</span>';
				echo '</a>';
				echo '<div class="admin-bar-clear"></div>';
				echo '</li>';
			}
			echo '</ul>';
			echo '</li>';
		}
	}
}
	
// **** "Random" Menu (visible when not logged in) ********
function bp_adminbar_random_menu() { 
	global $bp; ?>
	<li class="align-right" id="bp-adminbar-visitrandom-menu">
		<a href="#"><?php _e( 'Visit', 'buddypress' ) ?></a>
		<ul class="random-list">
			<li><a href="<?php echo $bp->root_domain . '/' . BP_MEMBERS_SLUG . '/?random-member' ?>"><?php _e( 'Random Member', 'buddypress' ) ?></a></li>

			<?php if ( function_exists('groups_install') ) : ?>
			<li class="alt"><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug . '/?random-group' ?>"><?php _e( 'Random Group', 'buddypress' ) ?></a></li>
			<?php endif; ?>

			<?php if ( function_exists('bp_blogs_install') ) : ?>
			<li><a href="<?php echo $bp->root_domain . '/' . $bp->blogs->slug . '/?random-blog' ?>"><?php _e( 'Random Blog', 'buddypress' ) ?></a></li>
			
			<?php endif; ?>
			
			<?php do_action( 'bp_adminbar_random_menu' ) ?>
		</ul>
	</li>
	<?php

	$doing_admin_bar = false;
}


add_action( 'bp_adminbar_logo', 'bp_adminbar_logo' );
add_action( 'bp_adminbar_menus', 'bp_adminbar_login_menu', 2 );
add_action( 'bp_adminbar_menus', 'bp_adminbar_account_menu', 4 );
add_action( 'bp_adminbar_menus', 'bp_adminbar_blogs_menu', 6 );
add_action( 'bp_adminbar_menus', 'bp_adminbar_notifications_menu', 8 );
add_action( 'bp_adminbar_menus', 'bp_adminbar_authors_menu', 12 );
add_action( 'bp_adminbar_menus', 'bp_adminbar_random_menu', 100 );

add_action( 'wp_footer', 'bp_core_admin_bar', 8 );
add_action( 'admin_footer', 'bp_core_admin_bar' );

?>