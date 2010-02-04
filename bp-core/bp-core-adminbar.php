<?php

function bp_core_admin_bar() {
	global $bp, $wpdb, $current_blog;

	if ( defined( 'BP_DISABLE_ADMIN_BAR' ) )
		return false;

	if ( (int)get_site_option( 'hide-loggedout-adminbar' ) && !is_user_logged_in() )
		return false;

	echo '<div id="wp-admin-bar"><div class="padder">';

	// **** Do bp-adminbar-logo Actions ********
	do_action( 'bp_adminbar_logo' );

	echo '<ul class="main-nav">';

	// **** Do bp-adminbar-menus Actions ********
	do_action( 'bp_adminbar_menus' );

	echo '</ul>';
	echo "</div></div><!-- #wp-admin-bar -->\n\n";
}

// **** Default BuddyPress admin bar logo ********
function bp_adminbar_logo() {
	global $bp;

	echo '<a href="' . $bp->root_domain . '" id="admin-bar-logo">' . get_blog_option( BP_ROOT_BLOG, 'blogname') . '</a>';
}

// **** "Log In" and "Sign Up" links (Visible when not logged in) ********
function bp_adminbar_login_menu() {
	global $bp;

	if ( is_user_logged_in() )
		return false;

	echo '<li class="bp-login no-arrow"><a href="' . $bp->root_domain . '/wp-login.php?redirect_to=' . urlencode( $bp->root_domain ) . '">' . __( 'Log In', 'buddypress' ) . '</a></li>';

	// Show "Sign Up" link if user registrations are allowed
	if ( bp_get_signup_allowed() ) {
		echo '<li class="bp-signup no-arrow"><a href="' . bp_get_signup_page(false) . '">' . __( 'Sign Up', 'buddypress' ) . '</a></li>';
	}
}


// **** "My Account" Menu ******
function bp_adminbar_account_menu() {
	global $bp;

	if ( !$bp->bp_nav || !is_user_logged_in() )
		return false;

	echo '<li id="bp-adminbar-account-menu"><a href="' . bp_loggedin_user_domain() . '">';

	echo __( 'My Account', 'buddypress' ) . '</a>';
	echo '<ul>';

	/* Loop through each navigation item */
	$counter = 0;
	foreach( (array)$bp->bp_nav as $nav_item ) {
		$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';

		echo '<li' . $alt . '>';
		echo '<a id="bp-admin-' . $nav_item['css_id'] . '" href="' . $nav_item['link'] . '">' . $nav_item['name'] . '</a>';

		if ( is_array( $bp->bp_options_nav[$nav_item['slug']] ) ) {
			echo '<ul>';
			$sub_counter = 0;

			foreach( (array)$bp->bp_options_nav[$nav_item['slug']] as $subnav_item ) {
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

	echo '<li' . $alt . '><a id="bp-admin-logout" class="logout" href="' . wp_logout_url(site_url()) . '">' . __( 'Log Out', 'buddypress' ) . '</a></li>';
	echo '</ul>';
	echo '</li>';
}

// *** "My Blogs" Menu ********
function bp_adminbar_blogs_menu() {
	global $bp;

	if ( !is_user_logged_in() || !function_exists('bp_blogs_install') )
		return false;

	if ( !$blogs = wp_cache_get( 'bp_blogs_of_user_' . $bp->loggedin_user->id . '_inc_hidden', 'bp' ) ) {
		$blogs = bp_blogs_get_blogs_for_user( $bp->loggedin_user->id, true );
		wp_cache_set( 'bp_blogs_of_user_' . $bp->loggedin_user->id . '_inc_hidden', $blogs, 'bp' );
	}

	echo '<li id="bp-adminbar-blogs-menu"><a href="' . $bp->loggedin_user->domain . $bp->blogs->slug . '/my-blogs">';

	_e( 'My Blogs', 'buddypress' );

	echo '</a>';
	echo '<ul>';

	if ( is_array( $blogs['blogs'] ) && (int)$blogs['count'] ) {
		$counter = 0;
		foreach ( (array)$blogs['blogs'] as $blog ) {
			$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';

			echo '<li' . $alt . '>';
			echo '<a href="' . esc_attr( $blog->siteurl ) . '">' . esc_html( $blog->name ) . '</a>';

			echo '<ul>';
			echo '<li class="alt"><a href="' . esc_attr( $blog->siteurl ) . 'wp-admin/">' . __( 'Dashboard', 'buddypress' ) . '</a></li>';
			echo '<li><a href="' . esc_attr( $blog->siteurl ) . 'wp-admin/post-new.php">' . __( 'New Post', 'buddypress' ) . '</a></li>';
			echo '<li class="alt"><a href="' . esc_attr( $blog->siteurl ) . 'wp-admin/edit.php">' . __( 'Manage Posts', 'buddypress' ) . '</a></li>';
			echo '<li><a href="' . esc_attr( $blog->siteurl ) . 'wp-admin/edit-comments.php">' . __( 'Manage Comments', 'buddypress' ) . '</a></li>';
			echo '</ul>';

			echo '</li>';
			$counter++;
		}
	}

	$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';

	if ( bp_blog_signup_enabled() ) {
		echo '<li' . $alt . '>';
		echo '<a href="' . $bp->root_domain . '/' . $bp->blogs->slug . '/create/">' . __( 'Create a Blog!', 'buddypress' ) . '</a>';
		echo '</li>';
	}

	echo '</ul>';
	echo '</li>';
}

// **** "Notifications" Menu *********
function bp_adminbar_notifications_menu() {
	global $bp;

	if ( !is_user_logged_in() )
		return false;

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

// **** "Blog Authors" Menu (visible when not logged in) ********
function bp_adminbar_authors_menu() {
	global $bp, $current_blog, $wpdb;

	if ( $current_blog->blog_id == BP_ROOT_BLOG || !function_exists( 'bp_blogs_install' ) )
		return false;

	$blog_prefix = $wpdb->get_blog_prefix( $current_blog->id );
	$authors = $wpdb->get_results( "SELECT user_id, user_login, user_nicename, display_name, user_email, meta_value as caps FROM $wpdb->users u, $wpdb->usermeta um WHERE u.ID = um.user_id AND meta_key = '{$blog_prefix}capabilities' ORDER BY um.user_id" );

	if ( !empty( $authors ) ) {
		/* This is a blog, render a menu with links to all authors */
		echo '<li id="bp-adminbar-authors-menu"><a href="/">';
		_e('Blog Authors', 'buddypress');
		echo '</a>';

		echo '<ul class="author-list">';
		foreach( (array)$authors as $author ) {
			$caps = maybe_unserialize( $author->caps );
			if ( isset( $caps['subscriber'] ) || isset( $caps['contributor'] ) ) continue;

			echo '<li>';
			echo bp_core_fetch_avatar( array( 'item_id' => $author->user_id, 'email' => $author->user_email, 'width' => 25, 'height' => 25 ) ) ;
			echo '<a href="' . bp_core_get_user_domain( $author->user_id, $author->user_nicename, $author->user_login ) . '">' . $author->display_name . '</a>';
			echo '</a>';
			echo '<div class="admin-bar-clear"></div>';
			echo '</li>';
		}
		echo '</ul>';
		echo '</li>';
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

			<?php if ( function_exists('bp_blogs_install') && bp_core_is_multisite() ) : ?>
			<li><a href="<?php echo $bp->root_domain . '/' . $bp->blogs->slug . '/?random-blog' ?>"><?php _e( 'Random Blog', 'buddypress' ) ?></a></li>

			<?php endif; ?>

			<?php do_action( 'bp_adminbar_random_menu' ) ?>
		</ul>
	</li>
	<?php
}

add_action( 'bp_adminbar_logo', 'bp_adminbar_logo' );
add_action( 'bp_adminbar_menus', 'bp_adminbar_login_menu', 2 );
add_action( 'bp_adminbar_menus', 'bp_adminbar_account_menu', 4 );

if ( bp_core_is_multisite() )
	add_action( 'bp_adminbar_menus', 'bp_adminbar_blogs_menu', 6 );

add_action( 'bp_adminbar_menus', 'bp_adminbar_notifications_menu', 8 );

if ( bp_core_is_multisite() )
	add_action( 'bp_adminbar_menus', 'bp_adminbar_authors_menu', 12 );

add_action( 'bp_adminbar_menus', 'bp_adminbar_random_menu', 100 );

add_action( 'wp_footer', 'bp_core_admin_bar', 8 );
add_action( 'admin_footer', 'bp_core_admin_bar' );

?>