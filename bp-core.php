<?php
/*
Plugin Name: BuddyPress Core
Plugin URI: http://buddypress.org/
Description: This plugin must be activated when using any other BuddyPress plugins.
Author: BuddyPress
Version: 1.0-RC1
Author URI: http://buddypress.org
Site Wide Only: true
*/

/* Define the current version number for checking if DB tables are up to date. */
define( 'BP_CORE_VERSION', '1.0-RC1' );
define( 'BP_CORE_DB_VERSION', '1030' );

/* Load the language file */
if ( file_exists( WPMU_PLUGIN_DIR . '/bp-languages/buddypress-' . get_locale() . '.mo' ) )
	load_textdomain( 'buddypress', WPMU_PLUGIN_DIR . '/bp-languages/buddypress-' . get_locale() . '.mo' );

/* Place your custom code (actions/filters) in a file called bp-custom.php and it will be loaded before anything else. */
if ( file_exists( WPMU_PLUGIN_DIR . '/bp-custom.php' ) )
	require( WPMU_PLUGIN_DIR . '/bp-custom.php' );

require ( 'bp-core/bp-core-catchuri.php' );
require ( 'bp-core/bp-core-classes.php' );
require ( 'bp-core/bp-core-cssjs.php' );
require ( 'bp-core/bp-core-avatars.php' );
require ( 'bp-core/bp-core-templatetags.php' );
require ( 'bp-core/bp-core-adminbar.php' );
require ( 'bp-core/bp-core-settings.php' );
require ( 'bp-core/bp-core-widgets.php' );
require ( 'bp-core/bp-core-ajax.php' );
require ( 'bp-core/bp-core-notifications.php' );
require ( 'bp-core/bp-core-admin.php' );
require ( 'bp-core/bp-core-signup.php' );
require ( 'bp-core/bp-core-activation.php' );
require ( 'bp-core/directories/bp-core-directory-members.php' );

/* Define the slug for member pages and the members directory (e.g. domain.com/[members] ) */
define( 'MEMBERS_SLUG', apply_filters( 'bp_members_slug', 'members' ) );

/* Define the slug for the register/signup page */
define( 'REGISTER_SLUG', apply_filters( 'bp_register_slug', 'register' ) );

/* Define the slug for the activation page */
define( 'ACTIVATION_SLUG', apply_filters( 'bp_activate_slug', 'activate' ) );

/* Define the slug for the search page */
define( 'SEARCH_SLUG', apply_filters( 'bp_search_slug', 'search' ) );

/* Define the slug for the search page */
define( 'HOME_BLOG_SLUG', apply_filters( 'bp_home_blog_slug', 'blog' ) );


/* "And now for something completely different" .... */


/**
 * bp_core_setup_globals()
 *
 * Sets up default global BuddyPress configuration settings and stores
 * them in a $bp variable.
 *
 * @package BuddyPress Core Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $current_user A WordPress global containing current user information
 * @global $current_component Which is set up in /bp-core/bp-core-catch-uri.php
 * @global $current_action Which is set up in /bp-core/bp-core-catch-uri.php
 * @global $action_variables Which is set up in /bp-core/bp-core-catch-uri.php
 * @uses bp_core_get_user_domain() Returns the domain for a user
 */
function bp_core_setup_globals() {
	global $bp, $wpdb;
	global $current_user, $current_component, $current_action, $current_blog;
	global $displayed_user_id;
	global $action_variables;

	/* The domain for the root of the site where the main blog resides */	
	$bp->root_domain = bp_core_get_root_domain();
	
	/* The user ID of the user who is currently logged in. */
	$bp->loggedin_user->id = $current_user->ID;

	/* The domain for the user currently logged in. eg: http://domain.com/members/andy */
	$bp->loggedin_user->domain = bp_core_get_user_domain($current_user->ID);
	
	/* The user id of the user currently being viewed, set in /bp-core/bp-core-catchuri.php */
	$bp->displayed_user->id = $displayed_user_id;
	
	/* The domain for the user currently being displayed */
	$bp->displayed_user->domain = bp_core_get_user_domain($displayed_user_id);
	
	/* The component being used eg: http://andy.domain.com/ [profile] */
	$bp->current_component = $current_component; // type: string
	
	/* The current action for the component eg: http://andy.domain.com/profile/ [edit] */
	$bp->current_action = $current_action; // type: string
	
	/* The action variables for the current action eg: http://andy.domain.com/profile/edit/ [group] / [6] */
	$bp->action_variables = $action_variables; // type: array
	
	/* Only used where a component has a sub item, e.g. groups: http://andy.domain.com/groups/ [my-group] / home - manipulated in the actual component not in catch uri code.*/
	$bp->current_item = ''; // type: string

	/* Used for overriding the 2nd level navigation menu so it can be used to display custom navigation for an item (for example a group) */
	$bp->is_single_item = false;

	/* The default component to use if none are set and someone visits: http://andy.domain.com/ */
	$bp->default_component = 'profile';
	
	/* Sets up the array container for the component navigation rendered by bp_get_nav() */
	$bp->bp_nav = array();

	/* Sets up the array container for the user navigation rendered by bp_get_user_nav() */
	$bp->bp_users_nav = array();
	
	/* Sets up the array container for the component options navigation rendered by bp_get_options_nav() */
	$bp->bp_options_nav = array();
	
	/* Sets up container used for the title of the current component option and rendered by bp_get_options_title() */
	$bp->bp_options_title = '';
	
	/* Sets up container used for the avatar of the current component being viewed. Rendered by bp_get_options_avatar() */
	$bp->bp_options_avatar = '';

	/* Fetch the full name for the logged in and current user */
	$bp->loggedin_user->fullname = bp_core_global_user_fullname( $bp->loggedin_user->id );
	$bp->displayed_user->fullname = bp_core_global_user_fullname( $bp->displayed_user->id );

	/* Used to determine if user has admin rights on current content. If the logged in user is viewing
	   their own profile and wants to delete a post on their wire, is_item_admin is used. This is a
	   generic variable so it can be used in other components. It can also be modified, so when viewing a group
	   'is_item_admin' would be 1 if they are a group admin, 0 if they are not. */
	$bp->is_item_admin = bp_is_home();
	
	/* Used to determine if the logged in user is a moderator for the current content. */
	$bp->is_item_mod = false;
	
	$bp->core->image_base = WPMU_PLUGIN_URL . '/bp-core/images';
	$bp->core->table_name_notifications = $wpdb->base_prefix . 'bp_notifications';
	
	/* Used to print version numbers in the footer for reference */
	$bp->version_numbers = new stdClass;
	$bp->version_numbers->core = BP_CORE_VERSION;
	
	if ( !$bp->current_component )
		$bp->current_component = $bp->default_component;
}
add_action( 'wp', 'bp_core_setup_globals', 1 );
add_action( '_admin_menu', 'bp_core_setup_globals', 1 ); // must be _admin_menu hook.

function bp_core_setup_root_components() {
	/* Add core root components */
	bp_core_add_root_component( MEMBERS_SLUG );
	bp_core_add_root_component( REGISTER_SLUG );
	bp_core_add_root_component( ACTIVATION_SLUG );
	bp_core_add_root_component( SEARCH_SLUG );
	bp_core_add_root_component( HOME_BLOG_SLUG );
}
add_action( 'plugins_loaded', 'bp_core_setup_root_components' );

function bp_core_setup_session() {
	// Start a session for error/success feedback on redirect and for signup functions.
	@session_start();
	
	// Render any error/success feedback on the template
	if ( $_SESSION['message'] != '' )
		add_action( 'template_notices', 'bp_core_render_notice' );
}
add_action( 'wp', 'bp_core_setup_session', 3 );


function bp_core_install() {
	global $wpdb, $bp;
	
	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	$sql[] = "CREATE TABLE {$bp->core->table_name_notifications} (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id bigint(20) NOT NULL,
				item_id bigint(20) NOT NULL,
				secondary_item_id bigint(20),
		  		component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
		  		date_notified datetime NOT NULL,
				is_new bool NOT NULL DEFAULT 0,
			    KEY item_id (item_id),
				KEY secondary_item_id (secondary_item_id),
				KEY user_id (user_id),
				KEY is_new (is_new),
				KEY component_name (component_name),
		 	   	KEY component_action (component_action)
			   ) {$charset_collate};";

	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	dbDelta( $sql );
	
	/* Add names of root components to the banned blog list to avoid conflicts */
	bp_core_add_illegal_names();
	
	// dbDelta won't change character sets, so we need to do this seperately.
	// This will only be in here pre v1.0
	$wpdb->query( $wpdb->prepare( "ALTER TABLE {$bp->core->table_name_notifications} DEFAULT CHARACTER SET %s", $wpdb->charset ) );
	
	update_site_option( 'bp-core-db-version', BP_CORE_DB_VERSION );
}

/**
 * bp_core_check_installed()
 *
 * Checks to make sure the database tables are set up for the core component.
 * 
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @global $current_user WordPress global variable containing current logged in user information
 * @uses is_site_admin() returns true if the current user is a site admin, false if not
 * @uses get_site_option() fetches the value for a meta_key in the wp_sitemeta table
 * @uses bp_core_install() runs the installation of DB tables for the core component
 */
function bp_core_check_installed() {
	global $wpdb, $bp;

	if ( is_site_admin() ) {
		/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
		if ( get_site_option('bp-core-db-version') < BP_CORE_DB_VERSION )
			bp_core_install();
	}
}
add_action( 'admin_menu', 'bp_core_check_installed' );

/**
 * bp_core_add_admin_menu()
 *
 * Adds the "BuddyPress" admin submenu item to the Site Admin tab.
 * 
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @uses is_site_admin() returns true if the current user is a site admin, false if not
 * @uses add_submenu_page() WP function to add a submenu item
 */
function bp_core_add_admin_menu() {
	global $wpdb, $bp;
	
	if ( is_site_admin() ) {
		/* Add the administration tab under the "Site Admin" tab for site administrators */
		add_submenu_page( 'wpmu-admin.php', __("BuddyPress", 'buddypress'), __("BuddyPress", 'buddypress'), 1, "bp_core_admin_settings", "bp_core_admin_settings" );
	}
}
add_action( 'admin_menu', 'bp_core_add_admin_menu' );

/**
 * bp_core_is_root_component()
 *
 * Checks to see if a component's URL should be in the root, not under a member page:
 * eg: http://domain.com/groups/the-group NOT http://domain.com/members/andy/groups/the-group
 * 
 * @package BuddyPress Core
 * @return true if root component, else false.
 */
function bp_core_is_root_component( $component_name ) {
	global $bp;

	return in_array( $component_name, $bp->root_components );
}

/**
 * bp_core_setup_nav()
 *
 * Sets up the profile navigation item if the Xprofile component is not installed.
 * 
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_core_add_nav_item() Adds a navigation item to the top level buddypress navigation
 * @uses bp_core_add_nav_default() Sets which sub navigation item is selected by default
 * @uses bp_core_add_subnav_item() Adds a sub navigation item to a nav item
 * @uses bp_is_home() Returns true if the current user being viewed is equal the logged in user
 * @uses bp_core_get_avatar() Returns the either the thumb (1) or full (2) avatar URL for the user_id passed
 */
function bp_core_setup_nav() {
	global $bp;
	
	if ( !function_exists('xprofile_install') ) {
		/* Add 'Profile' to the main navigation */
		bp_core_add_nav_item( __('Profile', 'buddypress'), 'profile' );
		bp_core_add_nav_default( 'profile', 'bp_core_catch_profile_uri', 'public' );

		$profile_link = $bp->loggedin_user->domain . '/profile/';

		/* Add the subnav items to the profile */
		bp_core_add_subnav_item( 'profile', 'public', __('Public', 'buddypress'), $profile_link, 'xprofile_screen_display_profile' );

		if ( 'profile' == $bp->current_component ) {
			if ( bp_is_home() ) {
				$bp->bp_options_title = __('My Profile', 'buddypress');
			} else {
				$bp->bp_options_avatar = bp_core_get_avatar( $bp->displayed_user->id, 1 );
				$bp->bp_options_title = $bp->displayed_user->fullname; 
			}
		}
	}	
}
add_action( 'wp', 'bp_core_setup_nav', 2 );
add_action( 'admin_menu', 'bp_core_setup_nav', 2 );

/**
 * bp_core_get_user_domain()
 *
 * Returns the domain for the passed user:
 * e.g. http://domain.com/members/andy/
 * 
 * @package BuddyPress Core
 * @global $current_user WordPress global variable containing current logged in user information
 * @param user_id The ID of the user.
 * @uses get_usermeta() WordPress function to get the usermeta for a user.
 */
function bp_core_get_user_domain( $user_id ) {
	global $bp;
	
	if ( !$user_id ) return;
	
	$ud = get_userdata($user_id);
	
	return $bp->root_domain . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/';
}

/**
 * bp_core_get_root_domain()
 *
 * Returns the domain for the root blog.
 * eg: http://domain.com/ OR https://domain.com
 * 
 * @package BuddyPress Core
 * @global $current_blog WordPress global variable containing information for the current blog being viewed.
 * @uses switch_to_blog() WordPress function to switch to a blog of the given ID.
 * @uses site_url() WordPress function to return the current site url.
 * @return $domain The domain URL for the blog.
 */
function bp_core_get_root_domain() {
	switch_to_blog(1);
	$domain = site_url();
	restore_current_blog();
	
	return $domain;
}

/**
 * bp_core_get_displayed_userid()
 *
 * Returns the user id for the user that is currently being displayed.
 * eg: http://andy.domain.com/ or http://domain.com/andy/
 * 
 * @package BuddyPress Core
 * @global $current_blog WordPress global containing information and settings for the current blog being viewed.
 * @uses bp_core_get_userid_from_user_login() Returns the user id for the username passed
 * @return The user id for the user that is currently being displayed, return zero if this is not a user home and just a normal blog.
 */
function bp_core_get_displayed_userid( $user_login ) {
	return bp_core_get_userid_from_user_login( $user_login );
}

/**
 * bp_core_add_nav_item()
 *
 * Adds a navigation item to the main navigation array used in BuddyPress themes.
 * 
 * @package BuddyPress Core
 * @param $id A unique id for the navigation item.
 * @param $name The display name for the navigation item, e.g. 'Profile' or 'Messages'
 * @param $slug The slug for the navigation item, e.g. 'profile' or 'messages'
 * @param $function The function to run when this sub nav item is selected.
 * @param $css_id The id to give the nav item in the HTML (for css highlighting)
 * @param $add_to_usernav Should this navigation item show up on the users home when not logged in? Or when another user views the user's page?
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_add_nav_item( $name, $slug, $css_id = false, $add_to_usernav = true ) {
	global $bp;
	
	$nav_key = count($bp->bp_nav) + 1;
	$user_nav_key = count($bp->bp_users_nav) + 1;
	
	if ( !$css_id )
		$css_id = $slug;

	$bp->bp_nav[$nav_key] = array(
		'name'   => $name, 
		'link'   => $bp->loggedin_user->domain . $slug,
		'css_id' => $css_id
	);
	
	if ( $add_to_usernav ) {
		$bp->bp_users_nav[$user_nav_key] = array(
			'name'   => $name, 
			'link'   => $bp->displayed_user->domain . $slug,
			'css_id' => $css_id
		);
	}
}

/**
 * bp_core_remove_nav_item()
 *
 * Removes a navigation item from the navigation array used in BuddyPress themes.
 * 
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @param $slug The slug of the sub navigation item.
 */
function bp_core_remove_nav_item( $name ) {
	global $bp;

	foreach( (array) $bp->bp_nav as $item_key => $item_value ) {
		if ( $item_value['name'] == $name ) {
			unset( $bp->bp_nav[$item_key] );
		}
	}
	
	foreach( (array) $bp->bp_users_nav as $item_key => $item_value ) {
		if ( $item_value['name'] == $name ) {
			unset( $bp->bp_nav[$item_key] );
		}
	}
}

/**
 * bp_core_add_subnav_item()
 *
 * Adds a navigation item to the sub navigation array used in BuddyPress themes.
 * 
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @param $slug The slug of the sub navigation item.
 * @param $name The display name for the sub navigation item, e.g. 'Public' or 'Change Avatar'
 * @param $link The url for the sub navigation item.
 * @param $function The function to run when this sub nav item is selected.
 * @param $css_id The id to give the nav item in the HTML (for css highlighting)
 * @param $user_has_access Should the logged in user be able to access this page?
 * @param $admin_only Should this sub nav item only be visible/accessible to the site admin?
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_add_subnav_item( $parent_id, $slug, $name, $link, $function, $css_id = false, $user_has_access = true, $admin_only = false ) {
	global $bp;

	if ( $admin_only && !is_site_admin() )
		return false;
	
	if ( !$css_id )
		$css_id = $slug;

	$bp->bp_options_nav[$parent_id][$slug] = array(
		'name' => $name,
		'link' => $link . $slug,
		'css_id' => $css_id
	);
	
	if ( function_exists($function) && $user_has_access && $bp->current_action == $slug && $bp->current_component == $parent_id )
		add_action( 'wp', $function, 3 );
}

/**
 * bp_core_remove_subnav_item()
 *
 * Removes a navigation item from the sub navigation array used in BuddyPress themes.
 * 
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @param $slug The slug of the sub navigation item.
 */
function bp_core_remove_subnav_item( $parent_id, $slug ) {
	global $bp;
	
	unset( $bp->bp_options_nav[$parent_id][$slug] );
}

/**
 * bp_core_reset_subnav_items()
 *
 * Clear the subnav items for a specific nav item.
 * 
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_reset_subnav_items($parent_id) {
	global $bp;

	unset($bp->bp_options_nav[$parent_id]);
}

/**
 * bp_core_add_nav_default()
 *
 * Set a default action for a nav item, when a sub nav item has not yet been selected.
 * 
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @param $function The function to run when this sub nav item is selected.
 * @param $slug The slug of the sub nav item to highlight.
 * @uses is_site_admin() returns true if the current user is a site admin, false if not
 * @uses bp_is_home() Returns true if the current user being viewed is equal the logged in user
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_add_nav_default( $parent_id, $function, $slug = false, $user_has_access = true, $admin_only = false ) {
	global $bp;
	
	if ( !$user_has_access && !bp_is_home() )
		return false;
		
	if ( $admin_only && !is_site_admin() )
		return false;

	if ( $bp->current_component == $parent_id && !$bp->current_action ) {
		if ( function_exists($function) ) {
			add_action( 'wp', $function, 3 );
		}
		
		if ( $slug )
			$bp->current_action = $slug;
	}
}

/**
 * bp_core_load_template()
 *
 * Uses the bp_catch_uri function to load a specific template file with fallback support.
 *
 * Example:
 *   bp_core_load_template( 'profile/edit-profile' );
 * Loads:
 *   wp-content/member-themes/[activated_theme]/profile/edit-profile.php
 * 
 * @package BuddyPress Core
 * @param $username str Username to check.
 * @global $wpdb WordPress DB access object.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_load_template( $template, $skip_blog_check = false ) {
	return bp_catch_uri( $template, $skip_blog_check );
}

/**
 * bp_core_add_root_component()
 *
 * Adds a component to the $bp->root_components global.
 * Any component that runs in the "root" of an install should be added.
 * The "root" as in, it can or always runs outside of the /members/username/ path.
 *
 * Example of a root component:
 *  Groups: http://domain.com/groups/group-name
 *          http://community.domain.com/groups/group-name
 *          http://domain.com/wpmu/groups/group-name
 *
 * Example of a component that is NOT a root component:
 *  Friends: http://domain.com/members/andy/friends
 *           http://community.domain.com/members/andy/friends
 *           http://domain.com/wpmu/members/andy/friends
 * 
 * @package BuddyPress Core
 * @param $slug str The slug of the component
 * @global $bp BuddyPress global settings
 */
function bp_core_add_root_component( $slug ) {
	global $bp;

	$bp->root_components[] = $slug;
}

/**
 * bp_core_get_random_member()
 *
 * Returns the user_id for a user based on their username.
 * 
 * @package BuddyPress Core
 * @param $username str Username to check.
 * @global $wpdb WordPress DB access object.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_get_random_member() {
	global $bp, $wpdb;
	
	if ( $bp->current_component == MEMBERS_SLUG && isset( $_GET['random'] ) ) {
		$user = BP_Core_User::get_random_users(1);

		$ud = get_userdata( $user['users'][0]->user_id );
		bp_core_redirect( $bp->root_domain . '/' . MEMBERS_SLUG . '/' . $ud->user_login );
	}
}
add_action( 'wp', 'bp_core_get_random_member', 6 );

/**
 * bp_core_get_userid()
 *
 * Returns the user_id for a user based on their username.
 * 
 * @package BuddyPress Core
 * @param $username str Username to check.
 * @global $wpdb WordPress DB access object.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_get_userid( $username ) {
	global $wpdb;
	
	$sql = $wpdb->prepare( "SELECT ID FROM " . $wpdb->base_prefix . "users WHERE user_login = %s", $username );
	return $wpdb->get_var($sql);
}

/**
 * bp_core_get_userid_from_user_login()
 *
 * Returns the user_id from a user login
 * @package BuddyPress Core
 * @param $path str Path to check.
 * @global $wpdb WordPress DB access object.
 * @return false on no match
 * @return int the user ID of the matched user.
 */
function bp_core_get_userid_from_user_login( $user_login ) {
	global $wpdb;

	return $wpdb->get_var( $wpdb->prepare( "SELECT ID from {$wpdb->base_prefix}users WHERE user_login = %s", $user_login ) );
}

/**
 * bp_core_get_username()
 *
 * Returns the username for a user based on their user id.
 * 
 * @package BuddyPress Core
 * @param $uid int User ID to check.
 * @global $userdata WordPress user data for the current logged in user.
 * @uses get_userdata() WordPress function to fetch the userdata for a user ID
 * @return false on no match
 * @return str the username of the matched user.
 */
function bp_core_get_username( $uid ) {
	global $userdata;
	
	if ( $uid == $userdata->ID )
		return 'You';
	
	if ( !$ud = get_userdata($uid) )
		return false;
		
	return $ud->user_login;	
}

/**
 * bp_core_get_userurl()
 *
 * Returns the URL with no HTML markup for a user based on their user id.
 * 
 * @package BuddyPress Core
 * @param $uid int User ID to check.
 * @global $userdata WordPress user data for the current logged in user.
 * @uses get_userdata() WordPress function to fetch the userdata for a user ID
 * @return false on no match
 * @return str The URL for the user with no HTML formatting.
 */
function bp_core_get_userurl( $uid ) {
	global $bp;
	
	if ( !is_numeric($uid) )
		return false;
	
	$ud = get_userdata($uid);
		
	return $bp->root_domain . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/';
}

/**
 * bp_core_get_user_email()
 *
 * Returns the email address for the user based on user ID
 * 
 * @package BuddyPress Core
 * @param $uid int User ID to check.
 * @uses get_userdata() WordPress function to fetch the userdata for a user ID
 * @return false on no match
 * @return str The email for the matched user.
 */
function bp_core_get_user_email( $uid ) {
	$ud = get_userdata($uid);
	return $ud->user_email;
}

/**
 * bp_core_get_userlink()
 *
 * Returns a HTML formatted link for a user with the user's full name as the link text.
 * eg: <a href="http://andy.domain.com/">Andy Peatling</a>
 * Optional parameters will return just the name, or just the URL, or disable "You" text when
 * user matches the logged in user. 
 *
 * [NOTES: This function needs to be cleaned up or split into separate functions]
 * 
 * @package BuddyPress Core
 * @param $uid int User ID to check.
 * @param $no_anchor bool Disable URL and HTML and just return full name. Default false.
 * @param $just_link bool Disable full name and HTML and just return the URL text. Default false.
 * @param $no_you bool Disable replacing full name with "You" when logged in user is equal to the current user. Default false.
 * @global $userdata WordPress user data for the current logged in user.
 * @uses get_userdata() WordPress function to fetch the userdata for a user ID
 * @uses bp_fetch_user_fullname() Returns the full name for a user based on user ID.
 * @uses bp_core_get_userurl() Returns the URL for the user with no anchor tag based on user ID
 * @return false on no match
 * @return str The link text based on passed parameters.
 */
function bp_core_get_userlink( $user_id, $no_anchor = false, $just_link = false, $deprecated = false, $with_s = false ) {
	global $userdata;
	
	$ud = get_userdata($user_id);
	
	if ( !$ud )
		return false;

	if ( function_exists('bp_fetch_user_fullname') ) { 
		$display_name = bp_fetch_user_fullname( $user_id, false );
		
		if ( $with_s )
			$display_name .= "'s";
			
	} else {
		$display_name = $ud->display_name;
	}
	
	// if ( $user_id == $userdata->ID && !$no_you )
	// 	$display_name = 'You';
	
	if ( $no_anchor )
		return $display_name;

	if ( !$url = bp_core_get_userurl($user_id) )
		return false;
		
	if ( $just_link )
		return $url;

	return '<a href="' . $url . '">' . $display_name . '</a>';	
}

/**
 * bp_core_global_user_fullname()
 *
 * Returns the full name for the user, or the display name if Xprofile component is not installed.
 * 
 * @package BuddyPress Core
 * @param $user_id string The user ID of the user.
 * @param
 * @uses bp_fetch_user_fullname() Returns the full name for a user based on user ID.
 * @uses get_userdata() Fetches a new userdata object for the user ID passed.
 * @return Either the users full name, or the display name.
 */
function bp_core_global_user_fullname( $user_id ) {
	if ( function_exists('bp_fetch_user_fullname') ) {
		return bp_fetch_user_fullname( $user_id, false );
	} else {
		$ud = get_userdata($user_id);
		return $current_user->display_name;
	}
}

/**
 * bp_core_get_userlink_by_email()
 *
 * Returns the user link for the user based on user email address
 * 
 * @package BuddyPress Core
 * @param $email str The email address for the user.
 * @uses bp_core_get_userlink() BuddyPress function to get a userlink by user ID.
 * @uses get_user_by_email() WordPress function to get userdata via an email address
 * @return str The link to the users home base. False on no match.
 */
function bp_core_get_userlink_by_email( $email ) {
	$user = get_user_by_email( $email );
	return bp_core_get_userlink( $user->ID, false, false, true );
}

/**
 * bp_core_get_userlink_by_username()
 *
 * Returns the user link for the user based on user's username
 * 
 * @package BuddyPress Core
 * @param $username str The username for the user.
 * @uses bp_core_get_userlink() BuddyPress function to get a userlink by user ID.
 * @return str The link to the users home base. False on no match.
 */
function bp_core_get_userlink_by_username( $username ) {
	global $wpdb;
	
	$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->base_prefix}users WHERE user_login = %s", $username ) ); 
	return bp_core_get_userlink( $user_id, false, false, true );
}

/**
 * bp_core_get_user_email()
 *
 * Returns the email address for the user based on user ID
 * 
 * @package BuddyPress Core
 * @param $uid int User ID to check.
 * @uses get_userdata() WordPress function to fetch the userdata for a user ID
 * @return false on no match
 * @return str The email for the matched user.
 */
function bp_core_format_time( $time, $just_date = false ) {
	$date = date( "F j, Y ", $time );
	
	if ( !$just_date ) {
		$date .= __('at', 'buddypress') . date( ' g:iA', $time );
	}
	
	return $date;
}

/**
 * bp_create_excerpt()
 *
 * Fakes an excerpt on any content. Will not truncate words.
 * 
 * @package BuddyPress Core
 * @param $text str The text to create the excerpt from
 * @uses $excerpt_length The maximum length in characters of the excerpt.
 * @return str The excerpt text
 */
function bp_create_excerpt( $text, $excerpt_length = 55, $filter_shortcodes = true ) { // Fakes an excerpt if needed
	$text = str_replace(']]>', ']]&gt;', $text);
	$text = strip_tags($text);
	
	if ( $filter_shortcodes )
		$text = preg_replace( '|\[(.+?)\](.+?\[/\\1\])?|s', '', $text );

	$words = explode(' ', $text, $excerpt_length + 1);
	if (count($words) > $excerpt_length) {
		array_pop($words);
		array_push($words, '[...]');
		$text = implode(' ', $words);
	}
	
	return stripslashes($text);
}

/**
 * bp_is_serialized()
 *
 * Checks to see if the data passed has been serialized.
 * 
 * @package BuddyPress Core
 * @param $data str The data that will be checked
 * @return bool false if the data is not serialized
 * @return bool true if the data is serialized
 */
function bp_is_serialized( $data ) {
   if ( '' == trim($data) ) {
      return false;
   }

   if ( preg_match( "/^(i|s|a|o|d)(.*);/si", $data ) ) {
      return true;
   }

   return false;
}


/**
 * bp_get_page_id()
 *
 * This function will return the ID of a page based on the page title.
 * 
 * @package BuddyPress Core
 * @param $page_title str Title of the page
 * @global $wpdb WordPress DB access object
 * @return int The page ID
 * @return bool false on no match.
 */
function bp_get_page_id($page_title) {
	global $wpdb;

	return $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'page'", $page_title) );
}

function bp_core_add_message( $message, $type = false ) {
	if ( !$type )
		$type = 'success';
	
	$_SESSION['message'] = $message;
	$_SESSION['message_type'] = $type;
}

/**
 * bp_core_render_notice()
 *
 * Renders a feedback notice (either error or success message) to the theme template.
 * The hook action 'template_notices' is used to call this function, it is not called directly.
 * 
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_render_notice() {
	if ( $_SESSION['message'] ) {
		$type = ( 'success' == $_SESSION['message_type'] ) ? 'updated' : 'error';
	?>
		<div id="message" class="<?php echo $type; ?>">
			<p><?php echo $_SESSION['message']; ?></p>
		</div>
	<?php 
		unset( $_SESSION['message'] );
		unset( $_SESSION['message_type'] );
		
		do_action( 'bp_core_render_notice' );
	}
}

/**
 * bp_core_time_since()
 *
 * Based on function created by Dunstan Orchard - http://1976design.com
 * 
 * This function will return an English representation of the time elapsed
 * since a given date.
 * eg: 2 hours and 50 minutes
 * eg: 4 days
 * eg: 4 weeks and 6 days
 * 
 * @package BuddyPress Core
 * @param $older_date int Unix timestamp of date you want to calculate the time since for
 * @param $newer_date int Unix timestamp of date to compare older date to. Default false (current time).
 * @return str The time since.
 */
function bp_core_time_since( $older_date, $newer_date = false ) {
	// array of time period chunks

	$chunks = array(
	array( 60 * 60 * 24 * 365 , __( 'year', 'buddypress' ), __( 'years', 'buddypress' ) ),
	array( 60 * 60 * 24 * 30 , __( 'month', 'buddypress' ), __( 'months', 'buddypress' ) ),
	array( 60 * 60 * 24 * 7, __( 'week', 'buddypress' ), __( 'weeks', 'buddypress' ) ),
	array( 60 * 60 * 24 , __( 'day', 'buddypress' ), __( 'days', 'buddypress' ) ),
	array( 60 * 60 , __( 'hour', 'buddypress' ), __( 'hours', 'buddypress' ) ),
	array( 60 , __( 'minute', 'buddypress' ), __( 'minutes', 'buddypress' ) ),
	array( 1, __( 'second', 'buddypress' ), __( 'seconds', 'buddypress' ) )
	);

	/* $newer_date will equal false if we want to know the time elapsed between a date and the current time */
	/* $newer_date will have a value if we want to work out time elapsed between two known dates */
	$newer_date = ( !$newer_date ) ? ( time() + ( 60*60*0 ) ) : $newer_date;

	/* Difference in seconds */
	$since = $newer_date - $older_date;

	/**
	 * We only want to output two chunks of time here, eg:
	 * x years, xx months
	 * x days, xx hours
	 * so there's only two bits of calculation below:
	 */

	/* Step one: the first chunk */
	for ( $i = 0, $j = count($chunks); $i < $j; $i++) {
		$seconds = $chunks[$i][0];

		/* Finding the biggest chunk (if the chunk fits, break) */
		if ( ( $count = floor($since / $seconds) ) != 0 )
			break;
	}

	/* Set output var */
	$output = ( 1 == $count ) ? '1 '. $chunks[$i][1] : $count . ' ' . $chunks[$i][2];

	/* Step two: the second chunk */
	if ( $i + 1 < $j ) {
		$seconds2 = $chunks[$i + 1][0];
		$name2 = $chunks[$i + 1][1];
		
		//if ( $chunks[$i + 1][1] == __( 'second', 'buddypress' ) ) return $output;
	
		if ( ( $count2 = floor( ( $since - ( $seconds * $count ) ) / $seconds2 ) ) != 0 ) {
			/* Add to output var */
			$output .= ( 1 == $count2 ) ? ', 1 '. $chunks[$i + 1][1] : ", " . $count2 . ' ' . $chunks[$i + 1][2];
		}
	}

	if ( !(int)trim($output) )
		$output = '0 ' . __( 'seconds', 'buddypress' );

	return $output;
}

/**
 * bp_core_record_activity()
 *
 * Record user activity to the database. Many functions use a "last active" feature to
 * show the length of time since the user was last active.
 * This function will update that time as a usermeta setting for the user every 5 minutes.
 * 
 * @package BuddyPress Core
 * @global $userdata WordPress user data for the current logged in user.
 * @uses update_usermeta() WordPress function to update user metadata in the usermeta table.
 */
function bp_core_record_activity() {
	global $bp;
	
	if ( !is_user_logged_in() )
		return false;
	
	$activity = get_usermeta( $bp->loggedin_user->id, 'last_activity' );
	
	if ( '' == $activity || time() >= strtotime( '+5 minutes', $activity ) )
		update_usermeta( $bp->loggedin_user->id, 'last_activity', time() );
}
add_action( 'wp_head', 'bp_core_record_activity' );


/**
 * bp_core_get_last_activity()
 *
 * Formats last activity based on time since date given.
 * 
 * @package BuddyPress Core
 * @param last_activity_date The date of last activity.
 * @param $before The text to prepend to the activity time since figure.
 * @param $after The text to append to the activity time since figure.
 * @uses bp_core_time_since() This function will return an English representation of the time elapsed.
 */
function bp_core_get_last_activity( $last_activity_date, $string ) {
	if ( !$last_activity_date || empty( $last_activity_date ) ) {
		$last_active = __( 'not recently active', 'buddypress' );
	} else {
		if ( strstr( $last_activity_date, '-' ) ) {
			$last_active = bp_core_time_since( strtotime( $last_activity_date ) ); 
		} else {
			$last_active = bp_core_time_since( $last_activity_date ); 
		}
		
		$last_active = sprintf( $string, $last_active );
	}
	
	return $last_active;
}

/**
 * bp_core_get_all_posts_for_user()
 *
 * Fetch every post that is authored by the given user for the current blog.
 * 
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress user data for the current logged in user.
 * @return array of post ids.
 */
function bp_core_get_all_posts_for_user( $user_id = null ) {
	global $bp, $wpdb;
	
	if ( !$user_id )
		$user_id = $bp->displayed_user->id;
	
	return $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->posts WHERE post_author = %d AND post_status = 'publish' AND post_type = 'post'", $user_id ) );
}

/**
 * bp_core_replace_comment_author_link()
 *
 * Replace the author link on comments to point to a user home base.
 * 
 * @package BuddyPress Core
 * @global $comment WordPress comment global for the current comment.
 * @uses bp_core_get_userlink_by_email() Fetches a userlink via email address.
 */
function bp_core_replace_comment_author_link( $author ) {
	global $comment;

	if ( !$comment->comment_author_email ) {
		$bp_author_link = bp_core_get_userlink_by_username( $comment->comment_author );
	} else {
		$bp_author_link = bp_core_get_userlink_by_email( $comment->comment_author_email );	
	}
	
	return ( !$bp_author_link ) ? $author : $bp_author_link; 
}
add_filter( 'get_comment_author_link', 'bp_core_replace_comment_author_link', 10, 4 );

/**
 * bp_core_get_site_path()
 *
 * Get the path of of the current site.
 * 
 * @package BuddyPress Core
 * @global $comment WordPress comment global for the current comment.
 * @uses bp_core_get_userlink_by_email() Fetches a userlink via email address.
 */
function bp_core_get_site_path() {
	global $wpdb;
	
	return $wpdb->get_var( $wpdb->prepare( "SELECT path FROM {$wpdb->base_prefix}site WHERE id = 1") );
}


function bp_core_redirect( $location, $status = 302 ) {
	global $bp_no_status_set;
	
	// Make sure we don't call status_header() in bp_core_do_catch_uri() 
    // as this conflicts with wp_redirect()
	$bp_no_status_set = true;
	
	wp_redirect( $location, $status );
	die;
}

/**
 * bp_core_sort_nav_items()
 *
 * Reorder the core component navigation array items into the desired order.
 * This is done this way because we cannot assume that any one component is present.
 * 
 * @package BuddyPress Core
 * @param $nav_array the navigation array variable
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses ksort() Sort an array by key
 * @return $new_nav array reordered navigation array
 */
function bp_core_sort_nav_items( $nav_array ) {
	global $bp;
	
	foreach ( (array)$nav_array as $key => $value ) {
		switch ( $nav_array[$key]['css_id'] ) {
			case $bp->activity->slug:
				$new_nav[0] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp->profile->slug:
				$new_nav[1] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case 'profile': // For profiles without bp-xprofile installed
				$new_nav[1] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp->blogs->slug:
				$new_nav[2] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp->wire->slug:
				$new_nav[3] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp->messages->slug:
				$new_nav[4] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp->friends->slug:
				$new_nav[5] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp->groups->slug:
				$new_nav[6] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp->photos->slug:
				$new_nav[7] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp->account->slug:
				$new_nav[8] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
		}
	}
	
	/* Sort the navigation array by key */
	ksort($new_nav);
	
	/* Merge the remaining nav items, so they can be appended on the end */
	$new_nav = array_merge( $new_nav, $nav_array );
	
	return $new_nav;
}

/**
 * bp_core_referrer()
 *
 * Returns the referrer URL without the http(s)://
 * 
 * @package BuddyPress Core
 * @return The referrer URL
 */
function bp_core_referrer() {
	$referer = explode( '/', $_SERVER['HTTP_REFERER'] );
	unset( $referer[0], $referer[1], $referer[2] );
	return implode( '/', $referer );
}

function bp_core_get_member_themes() {
	add_filter( 'theme_root', 'bp_core_set_member_theme_root' );
	$themes = get_themes();

	if ( $themes ) {
		foreach ( $themes as $name => $values ) {
			$member_themes[] = array(
				'name' => $name,
				'template' => $values['Template']
			);
		}
	}
	
	return $member_themes;
}

function bp_core_set_member_theme_root() {
	return apply_filters( 'bp_core_set_member_theme_root', WP_CONTENT_DIR . "/bp-themes" );
}

function bp_core_set_member_theme_root_uri() {
	return apply_filters( 'bp_core_set_member_theme_root_uri', WP_CONTENT_URL . '/bp-themes' );
}

function bp_core_add_illegal_names() {
	global $bp;
	
	$current = maybe_unserialize( get_site_option( 'illegal_names' ) );
	$bp_illegal_names = $bp->root_components;
	
	if ( is_array( $current ) ) {
		foreach( $bp_illegal_names as $bp_illegal_name ) {
			if ( !in_array( $bp_illegal_name, $current ) )
				$current[] = $bp_illegal_name;
		}
		$new = $current;
	} else {
		$bp_illegal_names[] = $current;
		$new = $bp_illegal_names;
	}

	update_site_option( 'illegal_names', $new );
}


/**
 * bp_core_email_from_name_filter()
 *
 * Sets the "From" name in emails sent to the name of the site and not "WordPress"
 * 
 * @package BuddyPress Core
 * @uses get_blog_option() fetches the value for a meta_key in the wp_X_options table
 * @return The blog name for the root blog
 */
function bp_core_email_from_name_filter() {
	return get_blog_option( 1, 'blogname' );
}
add_filter( 'wp_mail_from_name', 'bp_core_email_from_name_filter' );

/**
 * bp_core_email_from_name_filter()
 *
 * Sets the "From" address in emails sent
 * 
 * @package BuddyPress Core
 * @global $current_site Object containing current site metadata
 * @return noreply@sitedomain email address
 */
function bp_core_email_from_address_filter() {
	global $current_site;
	return 'noreply@' . $current_site->domain;
}
add_filter( 'wp_mail_from', 'bp_core_email_from_address_filter' );


function bp_core_delete_account() {
	global $bp;

	// Be careful with this function!
	
	require_once( ABSPATH . '/wp-admin/includes/mu.php' );
	require_once( ABSPATH . '/wp-admin/includes/user.php' );

	return wpmu_delete_user( $bp->loggedin_user->id  );
}

function bp_core_search_site() {
	global $bp;
	
	if ( $bp->current_component == SEARCH_SLUG ) {
		$search_terms = $_POST['search-terms'];
		$search_which = $_POST['search-which'];
		
		switch ( $search_which ) {
			case 'members': default:
				$search = MEMBERS_SLUG;
				break;
			case 'groups':
				$search = BP_GROUPS_SLUG;
				break;
			case 'blogs':
				$search = BP_BLOGS_SLUG;
				break;
		}
		
		$search_url = apply_filters( 'bp_core_search_site', site_url( $search . '/?s=' . urlencode($search_terms) ), $search_terms );
		
		bp_core_redirect( $search_url );
	}
}
add_action( 'wp', 'bp_core_search_site', 5 );

/**
 * bp_core_ucfirst()
 * 
 * Localization save ucfirst() support.
 * 
 * @package BuddyPress Core
 */
function bp_core_ucfirst( $str ) {
	if ( function_exists( 'mb_strtoupper' ) && function_exists( 'mb_substr' ) ) {
	    $fc = mb_strtoupper( mb_substr( $str, 0, 1 ) );
	    return $fc.mb_substr( $str, 1 );	
	} else {
		return ucfirst( $str );
	}
}

/**
 * bp_core_strip_username_spaces()
 * 
 * Strips spaces from usernames that are created using add_user() and wp_insert_user()
 * 
 * @package BuddyPress Core
 */
function bp_core_strip_username_spaces( $username ) {
	return str_replace( ' ', '-', $username );
}
add_action( 'pre_user_login', 'bp_core_strip_username_spaces' );

/**
 * bp_core_clear_cache()
 * REQUIRES WP-SUPER-CACHE 
 * 
 * When wp-super-cache is installed this function will clear cached pages
 * so that success/error messages are not cached, or time sensitive content.
 * 
 * @package BuddyPress Core
 */
function bp_core_clear_cache() {
	global $cache_path, $cache_filename;
	
	if ( function_exists( 'prune_super_cache' ) ) {
		do_action( 'bp_core_clear_cache' );
		
		return prune_super_cache( $cache_path, true );		
	}
}

function bp_core_print_version_numbers() {
	global $bp;
	
	foreach ( $bp->version_numbers as $name => $version ) {
		echo ucwords($name) . ': <b>' . $version . '</b> / ';
	}
}

function bp_core_print_generation_time() {
	global $wpdb;
	?>
<!-- Generated in <?php timer_stop(1); ?> seconds. <?php echo $wpdb->num_queries ?> q. -->
	<?php
}
add_action( 'wp_footer', 'bp_core_print_generation_time' );


/**
 * bp_core_remove_data()
 *
 * Deletes usermeta for the user when the user is deleted.
 * 
 * @package BuddyPress Core
 * @param $user_id The user id for the user to delete usermeta for
 * @uses delete_usermeta() deletes a row from the wp_usermeta table based on meta_key
 */
function bp_core_remove_data( $user_id ) {
	/* Remove usermeta */
	delete_usermeta( $user_id, 'last_activity' );
}
add_action( 'wpmu_delete_user', 'bp_core_remove_data', 1 );
add_action( 'delete_user', 'bp_core_remove_data', 1 );

// List actions to clear super cached pages on, if super cache is installed
add_action( 'wp_login', 'bp_core_clear_cache' );
add_action( 'bp_core_delete_avatar', 'bp_core_clear_cache' );
add_action( 'bp_core_avatar_save', 'bp_core_clear_cache' );
add_action( 'bp_core_render_notice', 'bp_core_clear_cache' );

// Remove the catch non existent blogs hook so WPMU doesn't think BuddyPress pages are non existing blogs
remove_action( 'plugins_loaded', 'catch_nonexistant_blogs' );

?>