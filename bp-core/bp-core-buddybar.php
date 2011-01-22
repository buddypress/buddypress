<?php


/**
 * Adds a navigation item to the main navigation array used in BuddyPress themes.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_new_nav_item( $args = '' ) {
	global $bp;

	$defaults = array(
		'name'                    => false, // Display name for the nav item
		'slug'                    => false, // URL slug for the nav item
		'item_css_id'             => false, // The CSS ID to apply to the HTML of the nav item
		'show_for_displayed_user' => true,  // When viewing another user does this nav item show up?
		'site_admin_only'         => false, // Can only site admins see this nav item?
		'position'                => 99,    // Index of where this nav item should be positioned
		'screen_function'         => false, // The name of the function to run when clicked
		'default_subnav_slug'     => false  // The slug of the default subnav item to select when clicked
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	// If we don't have the required info we need, don't create this subnav item
	if ( empty( $name ) || empty( $slug ) )
		return false;

	// If this is for site admins only and the user is not one, don't create the subnav item
	if ( $site_admin_only && !is_super_admin() )
		return false;

	if ( empty( $item_css_id ) )
		$item_css_id = $slug;

	$bp->bp_nav[$slug] = array(
		'name'                    => $name,
		'slug'                    => $slug,
		'link'                    => $bp->loggedin_user->domain . $slug . '/',
		'css_id'                  => $item_css_id,
		'show_for_displayed_user' => $show_for_displayed_user,
		'position'                => $position,
		'screen_function'         => &$screen_function
	);

 	/***
	 * If this nav item is hidden for the displayed user, and
	 * the logged in user is not the displayed user
	 * looking at their own profile, don't create the nav item.
	 */
	if ( !$show_for_displayed_user && !bp_user_has_access() )
		return false;

	/***
 	 * If we are not viewing a user, and this is a root component, don't attach the
 	 * default subnav function so we can display a directory or something else.
 	 */
	if ( bp_is_root_component( $slug ) && !bp_displayed_user_id() )
		return;

	if ( bp_is_current_component( $slug ) && !bp_current_action() ) {
		if ( !is_object( $screen_function[0] ) )
			add_action( 'wp', $screen_function, 3 );
		else
			add_action( 'wp', array( &$screen_function[0], $screen_function[1] ), 3 );

		if ( !empty( $default_subnav_slug ) )
			$bp->current_action = $default_subnav_slug;
	}
}

/**
 * Modify the default subnav item to load when a top level nav item is clicked.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_new_nav_default( $args = '' ) {
	global $bp;

	$defaults = array(
		'parent_slug'     => false, // Slug of the parent
		'screen_function' => false, // The name of the function to run when clicked
		'subnav_slug'     => false  // The slug of the subnav item to select when clicked
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( $function = $bp->bp_nav[$parent_slug]['screen_function'] ) {
		if ( !is_object( $function[0] ) )
			remove_action( 'wp', $function, 3 );
		else
			remove_action( 'wp', array( &$function[0], $function[1] ), 3 );
	}

	$bp->bp_nav[$parent_slug]['screen_function'] = &$screen_function;

	if ( $bp->current_component == $parent_slug && !$bp->current_action ) {
		if ( !is_object( $screen_function[0] ) )
			add_action( 'wp', $screen_function, 3 );
		else
			add_action( 'wp', array( &$screen_function[0], $screen_function[1] ), 3 );

		if ( $subnav_slug )
			$bp->current_action = $subnav_slug;
	}
}

/**
 * We can only sort nav items by their position integer at a later point in time, once all
 * plugins have registered their navigation items.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_sort_nav_items() {
	global $bp;

	if ( empty( $bp->bp_nav ) || !is_array( $bp->bp_nav ) )
		return false;

	foreach ( (array)$bp->bp_nav as $slug => $nav_item ) {
		if ( empty( $temp[$nav_item['position']]) )
			$temp[$nav_item['position']] = $nav_item;
		else {
			// increase numbers here to fit new items in.
			do {
				$nav_item['position']++;
			} while ( !empty( $temp[$nav_item['position']] ) );

			$temp[$nav_item['position']] = $nav_item;
		}
	}

	ksort( $temp );
	$bp->bp_nav = &$temp;
}
add_action( 'wp_head',    'bp_core_sort_nav_items' );
add_action( 'admin_head', 'bp_core_sort_nav_items' );

/**
 * Adds a navigation item to the sub navigation array used in BuddyPress themes.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_new_subnav_item( $args = '' ) {
	global $bp;

	$defaults = array(
		'name'            => false, // Display name for the nav item
		'slug'            => false, // URL slug for the nav item
		'parent_slug'     => false, // URL slug of the parent nav item
		'parent_url'      => false, // URL of the parent item
		'item_css_id'     => false, // The CSS ID to apply to the HTML of the nav item
		'user_has_access' => true,  // Can the logged in user see this nav item?
		'site_admin_only' => false, // Can only site admins see this nav item?
		'position'        => 90,    // Index of where this nav item should be positioned
		'screen_function' => false  // The name of the function to run when clicked
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	// If we don't have the required info we need, don't create this subnav item
	if ( empty( $name ) || empty( $slug ) || empty( $parent_slug ) || empty( $parent_url ) || empty( $screen_function ) )
		return false;

	// If this is for site admins only and the user is not one, don't create the subnav item
	if ( $site_admin_only && !is_super_admin() )
		return false;

	if ( empty( $item_css_id ) )
		$item_css_id = $slug;

	$bp->bp_options_nav[$parent_slug][$slug] = array(
		'name'            => $name,
		'link'            => $parent_url . $slug . '/',
		'slug'            => $slug,
		'css_id'          => $item_css_id,
		'position'        => $position,
		'user_has_access' => $user_has_access,
		'screen_function' => &$screen_function
	);

	if ( ( $bp->current_action == $slug && $bp->current_component == $parent_slug ) && $user_has_access ) {
		if ( !is_object( $screen_function[0] ) )
			add_action( 'wp', $screen_function, 3 );
		else
			add_action( 'wp', array( &$screen_function[0], $screen_function[1] ), 3 );
	}
}

function bp_core_sort_subnav_items() {
	global $bp;

	if ( empty( $bp->bp_options_nav ) || !is_array( $bp->bp_options_nav ) )
		return false;

	foreach ( (array)$bp->bp_options_nav as $parent_slug => $subnav_items ) {
		if ( !is_array( $subnav_items ) )
			continue;

		foreach ( (array)$subnav_items as $subnav_item ) {
			if ( empty( $temp[$subnav_item['position']]) )
				$temp[$subnav_item['position']] = $subnav_item;
			else {
				// increase numbers here to fit new items in.
				do {
					$subnav_item['position']++;
				} while ( !empty( $temp[$subnav_item['position']] ) );

				$temp[$subnav_item['position']] = $subnav_item;
			}
		}
		ksort( $temp );
		$bp->bp_options_nav[$parent_slug] = &$temp;
		unset( $temp );
	}
}
add_action( 'wp_head',    'bp_core_sort_subnav_items' );
add_action( 'admin_head', 'bp_core_sort_subnav_items' );

/**
 * Removes a navigation item from the sub navigation array used in BuddyPress themes.
 *
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @param $slug The slug of the sub navigation item.
 */
function bp_core_remove_nav_item( $parent_id ) {
	global $bp;

	// Unset subnav items for this nav item
	if ( is_array( $bp->bp_options_nav[$parent_id] ) ) {
		foreach( (array)$bp->bp_options_nav[$parent_id] as $subnav_item ) {
			bp_core_remove_subnav_item( $parent_id, $subnav_item['slug'] );
		}
	}

	if ( $function = $bp->bp_nav[$parent_id]['screen_function'] ) {
		if ( !is_object( $function[0] ) )
			remove_action( 'wp', $function, 3 );
		else
			remove_action( 'wp', array( &$function[0], $function[1] ), 3 );
	}

	unset( $bp->bp_nav[$parent_id] );
}

/**
 * Removes a navigation item from the sub navigation array used in BuddyPress themes.
 *
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @param $slug The slug of the sub navigation item.
 */
function bp_core_remove_subnav_item( $parent_id, $slug ) {
	global $bp;

	$screen_function = $bp->bp_options_nav[$parent_id][$slug]['screen_function'];

	if ( $screen_function ) {
		if ( !is_object( $screen_function[0] ) )
			remove_action( 'wp', $screen_function, 3 );
		else
			remove_action( 'wp', array( &$screen_function[0], $screen_function[1] ), 3 );
	}

	unset( $bp->bp_options_nav[$parent_id][$slug] );

	if ( !count( $bp->bp_options_nav[$parent_id] ) )
		unset($bp->bp_options_nav[$parent_id]);
}

/**
 * Clear the subnav items for a specific nav item.
 *
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_reset_subnav_items( $parent_slug ) {
	global $bp;

	unset( $bp->bp_options_nav[$parent_slug] );
}

/** Template functions ********************************************************/

function bp_core_admin_bar() {
	global $bp, $wpdb, $current_blog;

	if ( defined( 'BP_DISABLE_ADMIN_BAR' ) )
		return false;

	if ( (int)get_site_option( 'hide-loggedout-adminbar' ) && !is_user_logged_in() )
		return false;

	$bp->doing_admin_bar = true;

	echo '<div id="wp-admin-bar"><div class="padder">';

	// **** Do bp-adminbar-logo Actions ********
	do_action( 'bp_adminbar_logo' );

	echo '<ul class="main-nav">';

	// **** Do bp-adminbar-menus Actions ********
	do_action( 'bp_adminbar_menus' );

	echo '</ul>';
	echo "</div></div><!-- #wp-admin-bar -->\n\n";

	$bp->doing_admin_bar = false;
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
	if ( bp_get_signup_allowed() )
		echo '<li class="bp-signup no-arrow"><a href="' . bp_get_signup_page(false) . '">' . __( 'Sign Up', 'buddypress' ) . '</a></li>';
}


// **** "My Account" Menu ******
function bp_adminbar_account_menu() {
	global $bp;

	if ( !$bp->bp_nav || !is_user_logged_in() )
		return false;

	echo '<li id="bp-adminbar-account-menu"><a href="' . bp_loggedin_user_domain() . '">';

	echo __( 'My Account', 'buddypress' ) . '</a>';
	echo '<ul>';

	// Loop through each navigation item
	$counter = 0;
	foreach( (array)$bp->bp_nav as $nav_item ) {
		$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';

		if ( -1 == $nav_item['position'] )
			continue;

		echo '<li' . $alt . '>';
		echo '<a id="bp-admin-' . $nav_item['css_id'] . '" href="' . $nav_item['link'] . '">' . $nav_item['name'] . '</a>';

		if ( isset( $bp->bp_options_nav[$nav_item['slug']] ) && is_array( $bp->bp_options_nav[$nav_item['slug']] ) ) {
			echo '<ul>';
			$sub_counter = 0;

			foreach( (array)$bp->bp_options_nav[$nav_item['slug']] as $subnav_item ) {
				$link = $subnav_item['link'];
				$name = $subnav_item['name'];

				if ( isset( $bp->displayed_user->domain ) )
					$link = str_replace( $bp->displayed_user->domain, $bp->loggedin_user->domain, $subnav_item['link'] );

				if ( isset( $bp->displayed_user->userdata->user_login ) )
					$name = str_replace( $bp->displayed_user->userdata->user_login, $bp->loggedin_user->userdata->user_login, $subnav_item['name'] );

				$alt = ( 0 == $sub_counter % 2 ) ? ' class="alt"' : '';
				echo '<li' . $alt . '><a id="bp-admin-' . $subnav_item['css_id'] . '" href="' . $link . '">' . $name . '</a></li>';
				$sub_counter++;
			}
			echo '</ul>';
		}

		echo '</li>';

		$counter++;
	}

	$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';

	echo '<li' . $alt . '><a id="bp-admin-logout" class="logout" href="' . wp_logout_url( site_url() ) . '">' . __( 'Log Out', 'buddypress' ) . '</a></li>';
	echo '</ul>';
	echo '</li>';
}

// *** "My Blogs" Menu ********
function bp_adminbar_blogs_menu() {
	global $bp;

	if ( !is_user_logged_in() || !bp_is_active( 'blogs' ) )
		return false;

	if ( !is_multisite() )
		return false;

	if ( !$blogs = wp_cache_get( 'bp_blogs_of_user_' . $bp->loggedin_user->id . '_inc_hidden', 'bp' ) ) {
		$blogs = bp_blogs_get_blogs_for_user( $bp->loggedin_user->id, true );
		wp_cache_set( 'bp_blogs_of_user_' . $bp->loggedin_user->id . '_inc_hidden', $blogs, 'bp' );
	}

	echo '<li id="bp-adminbar-blogs-menu"><a href="' . $bp->loggedin_user->domain . $bp->blogs->slug . '/">';

	_e( 'My Blogs', 'buddypress' );

	echo '</a>';
	echo '<ul>';

	$counter = 0;
	if ( is_array( $blogs['blogs'] ) && (int)$blogs['count'] ) {
		foreach ( (array)$blogs['blogs'] as $blog ) {
			$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';
			$site_url = esc_attr( $blog->siteurl );

			echo '<li' . $alt . '>';
			echo '<a href="' . $site_url . '">' . esc_html( $blog->name ) . '</a>';
			echo '<ul>';
			echo '<li class="alt"><a href="' . $site_url . 'wp-admin/">' . __( 'Dashboard', 'buddypress' ) . '</a></li>';
			echo '<li><a href="' . $site_url . 'wp-admin/post-new.php">' . __( 'New Post', 'buddypress' ) . '</a></li>';
			echo '<li class="alt"><a href="' . $site_url . 'wp-admin/edit.php">' . __( 'Manage Posts', 'buddypress' ) . '</a></li>';
			echo '<li><a href="' . $site_url . 'wp-admin/edit-comments.php">' . __( 'Manage Comments', 'buddypress' ) . '</a></li>';
			echo '</ul>';

			do_action( 'bp_adminbar_blog_items', $blog );

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

function bp_adminbar_thisblog_menu() {
	if ( current_user_can( 'edit_posts' ) ) {
		echo '<li id="bp-adminbar-thisblog-menu"><a href="' . admin_url() . '">';

		_e( 'Dashboard', 'buddypress' );

		echo '</a>';
		echo '<ul>';

		echo '<li class="alt"><a href="' . admin_url() . 'post-new.php">' . __( 'New Post', 'buddypress' ) . '</a></li>';
		echo '<li><a href="' . admin_url() . 'edit.php">' . __( 'Manage Posts', 'buddypress' ) . '</a></li>';
		echo '<li class="alt"><a href="' . admin_url() . 'edit-comments.php">' . __( 'Manage Comments', 'buddypress' ) . '</a></li>';

		do_action( 'bp_adminbar_thisblog_items' );

		echo '</ul>';
		echo '</li>';
	}
}

// **** "Notifications" Menu *********
function bp_adminbar_notifications_menu() {
	global $bp;

	if ( !is_user_logged_in() )
		return false;

	echo '<li id="bp-adminbar-notifications-menu"><a href="' . $bp->loggedin_user->domain . '">';
	_e( 'Notifications', 'buddypress' );

	if ( $notifications = bp_users_get_notifications_for_user( $bp->loggedin_user->id ) ) { ?>
		<span><?php echo count( $notifications ) ?></span>
	<?php
	}

	echo '</a>';
	echo '<ul>';

	if ( $notifications ) {
		$counter = 0;
		for ( $i = 0; $i < count($notifications); $i++ ) {
			$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : ''; ?>

			<li<?php echo $alt ?>><?php echo $notifications[$i] ?></li>

			<?php $counter++;
		}
	} else { ?>

		<li><a href="<?php echo $bp->loggedin_user->domain ?>"><?php _e( 'No new notifications.', 'buddypress' ); ?></a></li>

	<?php
	}

	echo '</ul>';
	echo '</li>';
}

// **** "Blog Authors" Menu (visible when not logged in) ********
function bp_adminbar_authors_menu() {
	global $bp, $current_blog, $wpdb;

	// Only for multisite
	if ( !is_multisite() )
		return false;

	// Hide on root blog
	if ( $current_blog->blog_id == BP_ROOT_BLOG || !bp_is_active( 'blogs' ) )
		return false;

	$blog_prefix = $wpdb->get_blog_prefix( $current_blog->blog_id );
	$authors     = $wpdb->get_results( "SELECT user_id, user_login, user_nicename, display_name, user_email, meta_value as caps FROM $wpdb->users u, $wpdb->usermeta um WHERE u.ID = um.user_id AND meta_key = '{$blog_prefix}capabilities' ORDER BY um.user_id" );

	if ( !empty( $authors ) ) {
		// This is a blog, render a menu with links to all authors
		echo '<li id="bp-adminbar-authors-menu"><a href="/">';
		_e('Blog Authors', 'buddypress');
		echo '</a>';

		echo '<ul class="author-list">';
		foreach( (array)$authors as $author ) {
			$caps = maybe_unserialize( $author->caps );
			if ( isset( $caps['subscriber'] ) || isset( $caps['contributor'] ) ) continue;

			echo '<li>';
			echo '<a href="' . bp_core_get_user_domain( $author->user_id, $author->user_nicename, $author->user_login ) . '">';
			echo bp_core_fetch_avatar( array( 'item_id' => $author->user_id, 'email' => $author->user_email, 'width' => 15, 'height' => 15 ) ) ;
 			echo ' ' . $author->display_name . '</a>';
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

			<?php if ( bp_is_active( 'groups' ) ) : ?>

				<li class="alt"><a href="<?php echo $bp->root_domain . '/' . $bp->groups->slug . '/?random-group' ?>"><?php _e( 'Random Group', 'buddypress' ) ?></a></li>

			<?php endif; ?>

			<?php if ( bp_is_active( 'blogs' ) && is_multisite() ) : ?>

				<li><a href="<?php echo $bp->root_domain . '/' . $bp->blogs->slug . '/?random-blog' ?>"><?php _e( 'Random Blog', 'buddypress' ) ?></a></li>

			<?php endif; ?>

			<?php do_action( 'bp_adminbar_random_menu' ) ?>

		</ul>
	</li>

	<?php
}

function bp_core_load_admin_bar() {
	global $wp_version;

	if ( defined( 'BP_USE_WP_ADMIN_BAR' ) && BP_USE_WP_ADMIN_BAR && $wp_version >= 3.1 ) {
		// TODO: Add BP support to WP admin bar
		return;
	} elseif ( !defined( 'BP_DISABLE_ADMIN_BAR' ) || !BP_DISABLE_ADMIN_BAR ) {
		// Keep the WP admin bar from loading
		show_admin_bar( false );

		// Actions used to build the BP admin bar
		add_action( 'bp_adminbar_logo',  'bp_adminbar_logo' );
		add_action( 'bp_adminbar_menus', 'bp_adminbar_login_menu',         2   );
		add_action( 'bp_adminbar_menus', 'bp_adminbar_account_menu',       4   );
		add_action( 'bp_adminbar_menus', 'bp_adminbar_blogs_menu',         6   );
		add_action( 'bp_adminbar_menus', 'bp_adminbar_thisblog_menu',      6   );
		add_action( 'bp_adminbar_menus', 'bp_adminbar_notifications_menu', 8   );
		add_action( 'bp_adminbar_menus', 'bp_adminbar_authors_menu',       12  );
		add_action( 'bp_adminbar_menus', 'bp_adminbar_random_menu',        100 );

		// Actions used to append BP admin bar to footer
		add_action( 'wp_footer',    'bp_core_admin_bar', 8 );
		add_action( 'admin_footer', 'bp_core_admin_bar'    );
	}
}

?>
