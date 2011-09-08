<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Adds a navigation item to the main navigation array used in BuddyPress themes.
 *
 * @package BuddyPress Core
 * @global object $bp Global BuddyPress settings object
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
 	 * If the nav item is visible, we are not viewing a user, and this is a root
	 * component, don't attach the default subnav function so we can display a
	 * directory or something else.
 	 */
	if ( ( -1 != $position ) && bp_is_root_component( $slug ) && !bp_displayed_user_id() )
		return;

	// Look for current component
	if ( bp_is_current_component( $slug ) && !bp_current_action() ) {
		if ( !is_object( $screen_function[0] ) )
			add_action( 'bp_screens', $screen_function );
		else
			add_action( 'bp_screens', array( &$screen_function[0], $screen_function[1] ), 3 );

		if ( !empty( $default_subnav_slug ) )
			$bp->current_action = apply_filters( 'bp_default_component_subnav', $default_subnav_slug, $r );

	// Look for current item
	} elseif ( bp_is_current_item( $slug ) && !bp_current_action() ) {
		if ( !is_object( $screen_function[0] ) )
			add_action( 'bp_screens', $screen_function );
		else
			add_action( 'bp_screens', array( &$screen_function[0], $screen_function[1] ), 3 );

		if ( !empty( $default_subnav_slug ) )
			$bp->current_action = apply_filters( 'bp_default_component_subnav', $default_subnav_slug, $r );
	}

	do_action( 'bp_core_new_nav_item', $r, $args, $defaults );
}

/**
 * Modify the default subnav item to load when a top level nav item is clicked.
 *
 * @package BuddyPress Core
 * @global object $bp Global BuddyPress settings object
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
			remove_action( 'bp_screens', $function, 3 );
		else
			remove_action( 'bp_screens', array( &$function[0], $function[1] ), 3 );
	}

	$bp->bp_nav[$parent_slug]['screen_function'] = &$screen_function;

	if ( $bp->current_component == $parent_slug && !$bp->current_action ) {
		if ( !is_object( $screen_function[0] ) )
			add_action( 'bp_screens', $screen_function );
		else
			add_action( 'bp_screens', array( &$screen_function[0], $screen_function[1] ) );

		if ( $subnav_slug )
			$bp->current_action = $subnav_slug;
	}
}

/**
 * We can only sort nav items by their position integer at a later point in time, once all
 * plugins have registered their navigation items.
 *
 * @package BuddyPress Core
 * @global object $bp Global BuddyPress settings object
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
 * @global object $bp Global BuddyPress settings object
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
		'screen_function' => false, // The name of the function to run when clicked
		'link'            => ''     // The link for the subnav item; optional, not usually required.
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	// If we don't have the required info we need, don't create this subnav item
	if ( empty( $name ) || empty( $slug ) || empty( $parent_slug ) || empty( $parent_url ) || empty( $screen_function ) )
		return false;

	if ( empty( $link ) )
		$link = $parent_url . $slug;

	// If this is for site admins only and the user is not one, don't create the subnav item
	if ( $site_admin_only && !is_super_admin() )
		return false;

	if ( empty( $item_css_id ) )
		$item_css_id = $slug;

	$bp->bp_options_nav[$parent_slug][$slug] = array(
		'name'            => $name,
		'link'            => trailingslashit( $link ),
		'slug'            => $slug,
		'css_id'          => $item_css_id,
		'position'        => $position,
		'user_has_access' => $user_has_access,
		'screen_function' => &$screen_function
	);

	/**
	 * The last step is to hook the screen function for the added subnav item. But this only
	 * needs to be done if this subnav item is the current view, and the user has access to the
	 * subnav item. We figure out whether we're currently viewing this subnav by checking the
	 * following two conditions:
	 *   (1) Either:
	 *	 (a) the parent slug matches the current_component, or
	 *	 (b) the parent slug matches the current_item
	 *   (2) And either:
	 * 	 (a) the current_action matches $slug, or
	 *       (b) there is no current_action (ie, this is the default subnav for the parent nav)
	 *	     and this subnav item is the default for the parent item (which we check by
	 *	     comparing this subnav item's screen function with the screen function of the
	 *	     parent nav item in $bp->bp_nav). This condition only arises when viewing a
	 *	     user, since groups should always have an action set.
	 */

	// If we *don't* meet condition (1), return
	if ( $bp->current_component != $parent_slug && $bp->current_item != $parent_slug )
		return;

	// If we *do* meet condition (2), then the added subnav item is currently being requested
	if ( ( !empty( $bp->current_action ) && $slug == $bp->current_action ) || ( bp_is_user() && empty( $bp->current_action ) && $screen_function == $bp->bp_nav[$parent_slug]['screen_function'] ) ) {

		// Before hooking the screen function, check user access
		if ( $user_has_access ) {
			if ( !is_object( $screen_function[0] ) )
				add_action( 'bp_screens', $screen_function );
			else
				add_action( 'bp_screens', array( &$screen_function[0], $screen_function[1] ) );
		} else {
			// When the content is off-limits, we handle the situation differently
			// depending on whether the current user is logged in
			if ( is_user_logged_in() ) {
				// Off-limits to this user. Throw an error and redirect to the displayed user's domain
				bp_core_no_access( array(
					'message'  => __( 'You do not have access to this page.', 'buddypress' ),
					'root'     => bp_displayed_user_domain(),
					'redirect' => false
				) );
			} else {
				// Not logged in. Allow the user to log in, and attempt to redirect
				bp_core_no_access();
			}
		}
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
 * Determines whether a given nav item has subnav items
 *
 * @package BuddyPress
 * @since 1.5
 *
 * @param str $nav_item The id of the top-level nav item whose nav items you're checking
 * @return bool $has_subnav True if the nav item is found and has subnav items; false otherwise
 */
function bp_nav_item_has_subnav( $nav_item = '' ) {
	global $bp;

	if ( !$nav_item )
		$nav_item = bp_current_component();

	$has_subnav = isset( $bp->bp_options_nav[$nav_item] ) && count( $bp->bp_options_nav[$nav_item] ) > 0;

	return apply_filters( 'bp_nav_item_has_subnav', $has_subnav, $nav_item );
}

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
	if ( isset( $bp->bp_options_nav[$parent_id] ) && is_array( $bp->bp_options_nav[$parent_id] ) ) {
		foreach( (array)$bp->bp_options_nav[$parent_id] as $subnav_item ) {
			bp_core_remove_subnav_item( $parent_id, $subnav_item['slug'] );
		}
	}

	if ( $function = $bp->bp_nav[$parent_id]['screen_function'] ) {
		if ( !is_object( $function[0] ) ) {
			remove_action( 'bp_screens', $function );
		} else {
			remove_action( 'bp_screens', array( &$function[0], $function[1] ) );
		}
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

	$screen_function = ( isset( $bp->bp_options_nav[$parent_id][$slug]['screen_function'] ) ) ? $bp->bp_options_nav[$parent_id][$slug]['screen_function'] : false;

	if ( $screen_function ) {
		if ( !is_object( $screen_function[0] ) )
			remove_action( 'bp_screens', $screen_function );
		else
			remove_action( 'bp_screens', array( &$screen_function[0], $screen_function[1] ) );
	}

	unset( $bp->bp_options_nav[$parent_id][$slug] );

	if ( isset( $bp->bp_options_nav[$parent_id] ) && !count( $bp->bp_options_nav[$parent_id] ) )
		unset($bp->bp_options_nav[$parent_id]);
}

/**
 * Clear the subnav items for a specific nav item.
 *
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @global object $bp Global BuddyPress settings object
 */
function bp_core_reset_subnav_items( $parent_slug ) {
	global $bp;

	unset( $bp->bp_options_nav[$parent_slug] );
}

/** Template functions ********************************************************/

function bp_core_admin_bar() {
	global $bp;

	if ( defined( 'BP_DISABLE_ADMIN_BAR' ) && BP_DISABLE_ADMIN_BAR )
		return false;

	if ( (int)bp_get_option( 'hide-loggedout-adminbar' ) && !is_user_logged_in() )
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

	echo '<a href="' . bp_get_root_domain() . '" id="admin-bar-logo">' . get_blog_option( bp_get_root_blog_id(), 'blogname' ) . '</a>';
}

// **** "Log In" and "Sign Up" links (Visible when not logged in) ********
function bp_adminbar_login_menu() {
	global $bp;

	if ( is_user_logged_in() )
		return false;

	echo '<li class="bp-login no-arrow"><a href="' . bp_get_root_domain() . '/wp-login.php?redirect_to=' . urlencode( bp_get_root_domain() ) . '">' . __( 'Log In', 'buddypress' ) . '</a></li>';

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

	echo '<li' . $alt . '><a id="bp-admin-logout" class="logout" href="' . wp_logout_url( home_url() ) . '">' . __( 'Log Out', 'buddypress' ) . '</a></li>';
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


// **** "Random" Menu (visible when not logged in) ********
function bp_adminbar_random_menu() {
	global $bp; ?>

	<li class="align-right" id="bp-adminbar-visitrandom-menu">
		<a href="#"><?php _e( 'Visit', 'buddypress' ) ?></a>
		<ul class="random-list">
			<li><a href="<?php echo trailingslashit( bp_get_root_domain() . '/' . bp_get_members_root_slug() ) . '?random-member' ?>" rel="nofollow"><?php _e( 'Random Member', 'buddypress' ) ?></a></li>

			<?php if ( bp_is_active( 'groups' ) ) : ?>

				<li class="alt"><a href="<?php echo trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() ) . '?random-group' ?>"  rel="nofollow"><?php _e( 'Random Group', 'buddypress' ) ?></a></li>

			<?php endif; ?>

			<?php if ( is_multisite() && bp_is_active( 'blogs' ) ) : ?>

				<li><a href="<?php echo trailingslashit( bp_get_root_domain() . '/' . bp_get_blogs_root_slug() ) . '?random-blog' ?>"  rel="nofollow"><?php _e( 'Random Site', 'buddypress' ) ?></a></li>

			<?php endif; ?>

			<?php do_action( 'bp_adminbar_random_menu' ) ?>

		</ul>
	</li>

	<?php
}

/**
 * Retrieve the admin bar display preference of a user based on context.
 *
 * This is a direct copy of WP's private _get_admin_bar_pref()
 *
 * @since 1.5.0
 *
 * @param string $context Context of this preference check, either 'admin' or 'front'.
 * @param int $user Optional. ID of the user to check, defaults to 0 for current user.
 *
 * @uses get_user_option()
 *
 * @return bool Whether the admin bar should be showing for this user.
 */
function bp_get_admin_bar_pref( $context, $user = 0 ) {
	$pref = get_user_option( "show_admin_bar_{$context}", $user );
	if ( false === $pref )
		return true;

	return 'true' === $pref;
}

/**
 * Handle the Admin Bar/BuddyBar business
 *
 * @since 1.2.0
 *
 * @global string $wp_version
 * @uses bp_get_option()
 * @uses is_user_logged_in()
 * @uses bp_use_wp_admin_bar()
 * @uses show_admin_bar()
 * @uses add_action() To hook 'bp_adminbar_logo' to 'bp_adminbar_logo'
 * @uses add_action() To hook 'bp_adminbar_login_menu' to 'bp_adminbar_menus'
 * @uses add_action() To hook 'bp_adminbar_account_menu' to 'bp_adminbar_menus'
 * @uses add_action() To hook 'bp_adminbar_thisblog_menu' to 'bp_adminbar_menus'
 * @uses add_action() To hook 'bp_adminbar_random_menu' to 'bp_adminbar_menus'
 * @uses add_action() To hook 'bp_core_admin_bar' to 'wp_footer'
 * @uses add_action() To hook 'bp_core_admin_bar' to 'admin_footer'
 */
function bp_core_load_admin_bar() {
	global $wp_version;

	// Don't show if admin bar is disabled for non-logged in users
	if ( (int) bp_get_option( 'hide-loggedout-adminbar' ) && !is_user_logged_in() )
		return;

	// Show the WordPress admin bar
	if ( bp_use_wp_admin_bar() && $wp_version >= 3.1 ) {
		// Respect user's admin bar display preferences
		if ( bp_get_admin_bar_pref( 'front', bp_loggedin_user_id() ) || bp_get_admin_bar_pref( 'admin', bp_loggedin_user_id() ) )
			return;

		show_admin_bar( true );

	// Hide the WordPress admin bar
	} elseif ( !bp_use_wp_admin_bar() ) {

		// Keep the WP admin bar from loading
		show_admin_bar( false );

		// Actions used to build the BP admin bar
		add_action( 'bp_adminbar_logo',  'bp_adminbar_logo' );
		add_action( 'bp_adminbar_menus', 'bp_adminbar_login_menu',         2   );
		add_action( 'bp_adminbar_menus', 'bp_adminbar_account_menu',       4   );
		add_action( 'bp_adminbar_menus', 'bp_adminbar_thisblog_menu',      6   );
		add_action( 'bp_adminbar_menus', 'bp_adminbar_random_menu',        100 );

		// Actions used to append BP admin bar to footer
		add_action( 'wp_footer',    'bp_core_admin_bar', 8 );
		add_action( 'admin_footer', 'bp_core_admin_bar'    );
	}
}

/**
 * Handle the BuddyBar CSS
 */
function bp_core_load_buddybar_css() {
	if ( bp_use_wp_admin_bar() || ( (int) bp_get_option( 'hide-loggedout-adminbar' ) && !is_user_logged_in() ) || ( defined( 'BP_DISABLE_ADMIN_BAR' ) && BP_DISABLE_ADMIN_BAR ) )
		return;

	if ( file_exists( get_stylesheet_directory() . '/_inc/css/adminbar.css' ) ) // Backwards compatibility
		$stylesheet = get_stylesheet_directory_uri() . '/_inc/css/adminbar.css';
	elseif ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
		$stylesheet = BP_PLUGIN_URL . '/bp-core/css/buddybar.dev.css';
	else
		$stylesheet = BP_PLUGIN_URL . '/bp-core/css/buddybar.css';

	wp_enqueue_style( 'bp-admin-bar', apply_filters( 'bp_core_admin_bar_css', $stylesheet ), array(), '20110723' );

	if ( !is_rtl() )
		return;

	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
		$stylesheet = BP_PLUGIN_URL . '/bp-core/css/buddybar-rtl.dev.css';
	else
		$stylesheet = BP_PLUGIN_URL . '/bp-core/css/buddybar-rtl.css';

	wp_enqueue_style( 'bp-admin-bar-rtl', apply_filters( 'bp_core_buddybar_rtl_css', $stylesheet ), array( 'bp-admin-bar' ), '20110723' );
}
add_action( 'bp_init', 'bp_core_load_buddybar_css' );
?>