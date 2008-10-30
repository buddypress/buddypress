<?php

/* Define the current version number for checking if DB tables are up to date. */
define( 'BP_CORE_VERSION', '0.2.7' );

/* Define the slug for member pages and the members directory (e.g. domain.com/[members] ) */
define( 'MEMBERS_SLUG', 'members' );

/* These components are accessed via the root, and not under a blog name or home base.
   e.g Groups is accessed via: http://domain.com/groups/group-name NOT http://domain.com/andy/groups/group-name */
define( 'BP_CORE_ROOT_COMPONENTS', 'groups' . ',' . MEMBERS_SLUG );

/* Load the language file */
if ( file_exists(ABSPATH . 'wp-content/mu-plugins/bp-languages/buddypress-' . get_locale() . '.mo') )
	load_textdomain( 'buddypress', ABSPATH . 'wp-content/mu-plugins/bp-languages/buddypress-' . get_locale() . '.mo' );

/* Functions to handle pretty URLs and breaking them down into usable variables */
require_once( 'bp-core/bp-core-catchuri.php' );

/* Database access classes */
require_once( 'bp-core/bp-core-classes.php' );

/* Functions to control the inclusion of CSS and JS files */
require_once( 'bp-core/bp-core-cssjs.php' );

/* Functions that handle the uploading, cropping, validation and storing of avatars */
require_once( 'bp-core/bp-core-avatars.php' );

/* Template functions/tags that can be used in template files */
require_once( 'bp-core/bp-core-templatetags.php' );

/* Functions to enable the site wide administration bar */
require_once( 'bp-core/bp-core-adminbar.php' );

/* Functions to handle the display and saving of account settings for members */
require_once( 'bp-core/bp-core-settings.php' );

/* Bundled core widgets that can be dropped into themes */
require_once( 'bp-core/bp-core-widgets.php' );

/* AJAX functionality */
require_once( 'bp-core/bp-core-ajax.php' );

/* Functions to handle the calculations and display of notifications for a user */
require_once( 'bp-core/bp-core-notifications.php' );

/* Functions to handle and display the member and blog directory pages */
require_once( 'bp-core/directories/bp-core-directory-members.php' );


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
 * @uses bp_core_get_loggedin_domain() Returns the domain for the logged in user
 * @uses bp_core_get_current_domain() Returns the domain for the current user being viewed
 * @uses bp_core_get_current_userid() Returns the user id for the current user being viewed
 * @uses bp_core_get_loggedin_userid() Returns the user id for the logged in user
 */
function bp_core_setup_globals() {
	global $bp, $wpdb;
	global $current_user, $current_component, $current_action, $current_blog;
	global $current_userid;
	global $action_variables;
	
	/* The domain for the root of the site where the main blog resides */	
	$bp['root_domain'] = bp_core_get_root_domain();
	
	/* The user ID of the user who is currently logged in. */
	$bp['loggedin_userid'] = $current_user->ID;
	
	/* The user id of the user currently being viewed */
	$bp['current_userid'] = $current_userid;
	
	/* The domain for the user currently logged in. eg: http://domain.com/members/andy */
	$bp['loggedin_domain'] = bp_core_get_user_domain($current_user->ID);
	
	/* The domain for the user currently being viewed */
	$bp['current_domain'] = bp_core_get_user_domain($current_userid);
	
	/* The component being used eg: http://andy.domain.com/ [profile] */
	$bp['current_component'] = $current_component; // type: string
	
	/* The current action for the component eg: http://andy.domain.com/profile/ [edit] */
	$bp['current_action'] = $current_action; // type: string
	
	/* The action variables for the current action eg: http://andy.domain.com/profile/edit/ [group] / [6] */
	$bp['action_variables']	= $action_variables; // type: array
	
	/* Only used where a component has a sub item, e.g. groups: http://andy.domain.com/groups/ [my-group] / home - manipulated in the actual component not in catch uri code.*/
	$bp['current_item'] = ''; // type: string

	/* The default component to use if none are set and someone visits: http://andy.domain.com/ */
	$bp['default_component'] = 'profile';
	
	/* Sets up the array container for the component navigation rendered by bp_get_nav() */
	$bp['bp_nav'] = array();

	/* Sets up the array container for the user navigation rendered by bp_get_user_nav() */
	$bp['bp_users_nav'] = array();
	
	/* Sets up the array container for the component options navigation rendered by bp_get_options_nav() */
	$bp['bp_options_nav'] = array();
	
	/* Sets up container used for the title of the current component option and rendered by bp_get_options_title() */
	$bp['bp_options_title']	= '';
	
	/* Sets up container used for the avatar of the current component being viewed. Rendered by bp_get_options_avatar() */
	$bp['bp_options_avatar'] = '';
	
	/* Sets up container for callback messages rendered by bp_core_render_notice() */
	$bp['message'] = '';
	
	/* Sets up container for callback message type rendered by bp_core_render_notice() */
	$bp['message_type'] = ''; // error/success

	/* Fetch the full name for the logged in and current user */
	$bp['loggedin_fullname'] = bp_core_global_user_fullname( $bp['loggedin_userid'] );
	$bp['current_fullname'] = bp_core_global_user_fullname( $bp['current_userid'] );

	/* Used to determine if user has admin rights on current content. If the logged in user is viewing
	   their own profile and wants to delete a post on their wire, is_item_admin is used. This is a
	   generic variable so it can be used in other components. It can also be modified, so when viewing a group
	   'is_item_admin' would be 1 if they are a group admin, 0 if they are not. */
	$bp['is_item_admin'] = bp_is_home();

	$bp['core'] = array(
		'image_base' => site_url() . '/wp-content/mu-plugins/bp-core/images',
		'table_name_notifications' => $wpdb->base_prefix . 'bp_notifications'
	);
	
	if ( !$bp['current_component'] )
		$bp['current_component'] = $bp['default_component'];
}
add_action( 'wp', 'bp_core_setup_globals', 1 );
add_action( '_admin_menu', 'bp_core_setup_globals', 1 ); // must be _admin_menu hook.


function bp_core_install() {
	global $wpdb, $bp;
	
	$sql[] = "CREATE TABLE ". $bp['core']['table_name_notifications'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT,
				user_id int(11) NOT NULL,
				item_id int(11) NOT NULL,
		  		component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
		  		date_notified datetime NOT NULL,
				is_new tinyint(1) NOT NULL,
		    	PRIMARY KEY id (id),
			    KEY item_id (item_id),
				KEY user_id (user_id),
			    KEY is_new (is_new),
				KEY component_name (component_name),
		 	   	KEY component_action (component_action)
			   );";

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
	
	add_site_option( 'bp-core-version', BP_CORE_VERSION );
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
		if ( ( $wpdb->get_var("show tables like '%" . $bp['core']['table_name_notifications'] . "%'") == false ) || ( get_site_option('bp-core-version') < BP_CORE_VERSION )  )
			bp_core_install();
	}
}
add_action( 'admin_menu', 'bp_core_check_installed' );

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

		$profile_link = $bp['loggedin_domain'] . '/profile/';

		/* Add the subnav items to the profile */
		bp_core_add_subnav_item( 'profile', 'public', __('Public', 'buddypress'), $profile_link, 'xprofile_screen_display_profile' );

		if ( $bp['current_component'] == 'profile' ) {
			if ( bp_is_home() ) {
				$bp['bp_options_title'] = __('My Profile', 'buddypress');
			} else {
				$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
				$bp['bp_options_title'] = $bp['current_fullname']; 
			}
		}
	}	
}
add_action( 'wp', 'bp_core_setup_nav', 2 );

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
	
	return $bp['root_domain'] . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/';
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
	global $current_blog;
	
	switch_to_blog(1);
	$domain = site_url();
	switch_to_blog($current_blog->blog_id);
	
	return $domain;
}

/**
 * bp_core_get_current_userid()
 *
 * Returns the user id for the user that is currently being viewed.
 * eg: http://andy.domain.com/ or http://domain.com/andy/
 * 
 * @package BuddyPress Core
 * @global $current_blog WordPress global containing information and settings for the current blog being viewed.
 * @uses bp_core_get_userid_from_user_login() Returns the user id for the username passed
 * @return $current_userid The user id for the user that is currently being viewed, return zero if this is not a user home and just a normal blog.
 */
function bp_core_get_current_userid( $user_login ) {
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
	
	$nav_key = count($bp['bp_nav']) + 1;
	$user_nav_key = count($bp['bp_users_nav']) + 1;
	
	if ( !$css_id )
		$css_id = $slug;

	$bp['bp_nav'][$nav_key] = array(
		'name'   => $name, 
		'link'   => $bp['loggedin_domain'] . $slug,
		'css_id' => $css_id
	);
	
	if ( $add_to_usernav ) {
		$bp['bp_users_nav'][$user_nav_key] = array(
			'name'   => $name, 
			'link'   => $bp['current_domain'] . $slug,
			'css_id' => $css_id
		);
	}
}

/**
 * bp_core_add_subnav_item()
 *
 * Adds a navigation item to the sub navigation array used in BuddyPress themes.
 * 
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @param $id A unique id for the sub navigation item.
 * @param $name The display name for the sub navigation item, e.g. 'Public' or 'Change Avatar'
 * @param $link The url for the sub navigation item.
 * @param $function The function to run when this sub nav item is selected.
 * @param $css_id The id to give the nav item in the HTML (for css highlighting)
 * @param $loggedin_user_only Should only the logged in user be able to access this page?
 * @param $admin_only Should this sub nav item only be visible/accessible to the site admin?
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_add_subnav_item( $parent_id, $slug, $name, $link, $function, $css_id = false, $user_has_access = true, $admin_only = false ) {
	global $bp;
	
	if ( !$user_has_access && !bp_is_home() )
		return false;
		
	if ( $admin_only && !is_site_admin() )
		return false;
	
	if ( !$css_id )
		$css_id = $slug;

	$bp['bp_options_nav'][$parent_id][$slug] = array(
		'name' => $name,
		'link' => $link . $slug,
		'css_id' => $css_id
	);
	
	if ( function_exists($function) && $bp['current_action'] == $slug && $bp['current_component'] == $parent_id )
		add_action( 'wp', $function, 3 );
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

	unset($bp['bp_options_nav'][$parent_id]);
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

	if ( $bp['current_component'] == $parent_id && !$bp['current_action'] ) {
		if ( function_exists($function) ) {
			add_action( 'wp', $function, 3 );
		}
		
		if ( $slug )
			$bp['current_action'] = $slug;
	}
}

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
		
	return $bp['root_domain'] . '/' . MEMBERS_SLUG . '/' . $ud->user_login . '/';
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
function bp_core_get_userlink( $user_id, $no_anchor = false, $just_link = false, $no_you = false, $with_s = false ) {
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
function bp_create_excerpt( $text, $excerpt_length = 55 ) { // Fakes an excerpt if needed
	$text = str_replace(']]>', ']]&gt;', $text);
	$text = strip_tags($text);
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
   if ( trim($data) == "" ) {
      return false;
   }

   if ( preg_match( "/^(i|s|a|o|d)(.*);/si", $data ) ) {
      return true;
   }

   return false;
}

/**
 * bp_avatar_upload_dir()
 *
 * This function will create an avatar upload directory for a new user.
 * This is directly copied from WordPress so that the code can be
 * accessed on user activation *before* 'upload_path' is placed
 * into the options table for the user.
 *
 * FIX: A fix for this would be to add a hook for 'activate_footer'
 * in wp-activate.php
 * 
 * @package BuddyPress Core
 * @return array Containing path, url, subdirectory and error message (if applicable).
 */
function bp_avatar_upload_dir( $user_id, $path = '/avatars' ) {
	$siteurl = site_url();
	$upload_path = 'wp-content/blogs.dir/1/files' . $path . '/' . $user_id;
	if ( trim($upload_path) === '' )
		$upload_path = 'wp-content/uploads';
	$dir = $upload_path;
	
	// $dir is absolute, $path is (maybe) relative to ABSPATH
	$dir = path_join( ABSPATH, $upload_path );
	$path = str_replace( ABSPATH, '', trim( $upload_path ) );

	if ( !$url = get_option( 'upload_url_path' ) )
		$url = trailingslashit( $siteurl ) . $path;

	if ( defined('UPLOADS') ) {
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}

	// Make sure we have an uploads dir
	if ( ! wp_mkdir_p( $dir ) ) {
		echo "no dir"; die;
		$message = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' , 'buddypress'), $dir );
		return array( 'error' => $message );
	}

	$uploads = array( 'path' => $dir, 'url' => $url, 'subdir' => $subdir, 'error' => false );
	return apply_filters( 'upload_dir', $uploads );
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

/**
 * bp_core_render_notice()
 *
 * Renders a feedback notice (either error or success message) to the theme template.
 * The hook action 'template_notices' is used to call this function, it is not called directly.
 * The message and message type are stored in the $bp global, and are set up right before
 * the add_action( 'template_notices', 'bp_core_render_notice' ); is called where needed. 
 * 
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_render_notice() {
	global $bp;

	if ( $bp['message'] != '' ) {
		$type = ( $bp['message_type'] == 'success' ) ? 'updated' : 'error';
	?>
		<div id="message" class="<?php echo $type; ?>">
			<p><?php echo $bp['message']; ?></p>
		</div>
	<?php 
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
	array( 60 * 60 * 24 * 365 , __('year', 'buddypress') ),
	array( 60 * 60 * 24 * 30 , __('month', 'buddypress') ),
	array( 60 * 60 * 24 * 7, __('week', 'buddypress') ),
	array( 60 * 60 * 24 , __('day', 'buddypress') ),
	array( 60 * 60 , __('hour', 'buddypress') ),
	array( 60 , __('minute', 'buddypress') ),
	array( 1, __('second', 'buddypress') )
	);

	/* $newer_date will equal false if we want to know the time elapsed between a date and the current time */
	/* $newer_date will have a value if we want to work out time elapsed between two known dates */
	$newer_date = ( $newer_date == false ) ? ( time() + ( 60*60*0 ) ) : $newer_date;

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
		$name = $chunks[$i][1];

		/* Finding the biggest chunk (if the chunk fits, break) */
		if ( ( $count = floor($since / $seconds) ) != 0 )
			break;
	}

	/* Set output var */
	$output = ( $count == 1 ) ? '1 '. $name : "$count {$name}s";

	/* Step two: the second chunk */
	if ( $i + 1 < $j ) {
		$seconds2 = $chunks[$i + 1][0];
		$name2 = $chunks[$i + 1][1];
		
		if ( $name2 == __('second', 'buddypress') ) return $output;
	
		if ( ( $count2 = floor( ( $since - ( $seconds * $count ) ) / $seconds2 ) ) != 0 ) {
			/* Add to output var */
			$output .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
		}
	}

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
	
	if ( !is_user_logged_in() || !get_usermeta( $bp['loggedin_userid'], 'last_activity') )
		return false;
	
	if ( time() >= strtotime('+5 minutes', get_usermeta( $bp['loggedin_userid'], 'last_activity') ) || get_usermeta( $bp['loggedin_userid'], 'last_activity') == '' ) {
		// Updated last site activity for this user.
		update_usermeta( $bp['loggedin_userid'], 'last_activity', time() );
	}
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
	if ( !$last_activity_date || $last_activity_date == '' ) {
		$last_active = __('not recently active', 'buddypress');
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
		$user_id = $bp['current_userid'];
	
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

/**
 * bp_core_sort_nav_items()
 *
 * Reorder the core component navigation array items into the desired order.
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
			case $bp['activity']['slug']:
				$new_nav[0] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp['profile']['slug']:
				$new_nav[1] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case 'profile':
				$new_nav[1] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp['blogs']['slug']:
				$new_nav[2] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp['wire']['slug']:
				$new_nav[3] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp['messages']['slug']:
				$new_nav[4] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp['friends']['slug']:
				$new_nav[5] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp['groups']['slug']:
				$new_nav[6] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp['gallery']['slug']:
				$new_nav[7] = $nav_array[$key];
				unset($nav_array[$key]);
			break;
			case $bp['account']['slug']:
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

?>