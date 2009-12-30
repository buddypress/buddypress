<?php

/* Define the current version number for checking if DB tables are up to date. */
define( 'BP_CORE_DB_VERSION', '1800' );

/***
 * Define the path and url of the BuddyPress plugins directory.
 * It is important to use plugins_url() core function to obtain
 * the correct scheme used (http or https).
 */
define( 'BP_PLUGIN_DIR', WP_PLUGIN_DIR . '/buddypress' );
define( 'BP_PLUGIN_URL', plugins_url( $path = '/buddypress' ) );

/* Load the WP abstraction file so BuddyPress can run on all WordPress setups. */
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-wpabstraction.php' );

/* Place your custom code (actions/filters) in a file called /plugins/bp-custom.php and it will be loaded before anything else. */
if ( file_exists( WP_PLUGIN_DIR . '/bp-custom.php' ) )
	require( WP_PLUGIN_DIR . '/bp-custom.php' );

/* Define on which blog ID BuddyPress should run */
if ( !defined( 'BP_ROOT_BLOG' ) )
	define( 'BP_ROOT_BLOG', 1 );

/* Define the user and usermeta table names, useful if you are using custom or shared tables */
if ( !defined( 'CUSTOM_USER_TABLE' ) )
	define( 'CUSTOM_USER_TABLE', $wpdb->base_prefix . 'users' );

if ( !defined( 'CUSTOM_USER_META_TABLE' ) )
	define( 'CUSTOM_USER_META_TABLE', $wpdb->base_prefix . 'usermeta' );

/* Load the files containing functions that we globally will need. */
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-catchuri.php' );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-classes.php' );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-cssjs.php' );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-avatars.php' );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-templatetags.php' );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-settings.php' );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-widgets.php' );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-notifications.php' );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-signup.php' );
require ( BP_PLUGIN_DIR . '/bp-core/bp-core-activation.php' );

/* If BP_DISABLE_ADMIN_BAR is defined, do not load the global admin bar */
if ( !defined( 'BP_DISABLE_ADMIN_BAR' ) )
	require ( BP_PLUGIN_DIR . '/bp-core/bp-core-adminbar.php' );

/* Define the slug for member pages and the members directory (e.g. domain.com/[members] ) */
if ( !defined( 'BP_MEMBERS_SLUG' ) )
	define( 'BP_MEMBERS_SLUG', 'members' );

/* Define the slug for the register/signup page */
if ( !defined( 'BP_REGISTER_SLUG' ) )
	define( 'BP_REGISTER_SLUG', 'register' );

/* Define the slug for the activation page */
if ( !defined( 'BP_ACTIVATION_SLUG' ) )
	define( 'BP_ACTIVATION_SLUG', 'activate' );

/* Define the slug for the search page */
if ( !defined( 'BP_SEARCH_SLUG' ) )
	define( 'BP_SEARCH_SLUG', 'search' );

/* Define the slug for the search page */
if ( !defined( 'BP_HOME_BLOG_SLUG' ) )
	define( 'BP_HOME_BLOG_SLUG', 'blog' );

/* Register BuddyPress themes contained within the bp-theme folder */
if ( function_exists( 'register_theme_directory') )
	register_theme_directory( WP_PLUGIN_DIR . '/buddypress/bp-themes' );


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

	$current_user = wp_get_current_user();

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

	/* The component being used eg: http://domain.com/members/andy/ [profile] */
	$bp->current_component = $current_component; // type: string

	/* The current action for the component eg: http://domain.com/members/andy/profile/ [edit] */
	$bp->current_action = $current_action; // type: string

	/* The action variables for the current action eg: http://domain.com/members/andy/profile/edit/ [group] / [6] */
	$bp->action_variables = $action_variables; // type: array

	/* Only used where a component has a sub item, e.g. groups: http://domain.com/members/andy/groups/ [my-group] / home - manipulated in the actual component not in catch uri code.*/
	$bp->current_item = ''; // type: string

	/* Used for overriding the 2nd level navigation menu so it can be used to display custom navigation for an item (for example a group) */
	$bp->is_single_item = false;

	/* The default component to use if none are set and someone visits: http://domain.com/members/andy */
	if ( !defined( 'BP_DEFAULT_COMPONENT' ) ) {
		if ( defined( 'BP_ACTIVITY_SLUG' ) )
			$bp->default_component = BP_ACTIVITY_SLUG;
		else
			$bp->default_component = 'profile';
	} else {
		$bp->default_component = BP_DEFAULT_COMPONENT;
	}

	/* Sets up the array container for the component navigation rendered by bp_get_nav() */
	$bp->bp_nav = array();

	/* Sets up the array container for the component options navigation rendered by bp_get_options_nav() */
	$bp->bp_options_nav = array();

	/* Sets up container used for the title of the current component option and rendered by bp_get_options_title() */
	$bp->bp_options_title = '';

	/* Sets up container used for the avatar of the current component being viewed. Rendered by bp_get_options_avatar() */
	$bp->bp_options_avatar = '';

	/* Contains an array of all the active components. The key is the slug, value the internal ID of the component */
	$bp->active_components = array();

	/* Fetches the default Gravatar image to use if the user/group/blog has no avatar or gravatar */
	$bp->grav_default->user = apply_filters( 'bp_user_gravatar_default', get_site_option( 'user-avatar-default' ) );
	$bp->grav_default->group = apply_filters( 'bp_group_gravatar_default', 'identicon' );
	$bp->grav_default->blog = apply_filters( 'bp_blog_gravatar_default', 'identicon' );

	/* Fetch the full name for the logged in and current user */
	$bp->loggedin_user->fullname = bp_core_get_user_displayname( $bp->loggedin_user->id );
	$bp->displayed_user->fullname = bp_core_get_user_displayname( $bp->displayed_user->id );

	/* Used to determine if user has admin rights on current content. If the logged in user is viewing
	   their own profile and wants to delete a post on their wire, is_item_admin is used. This is a
	   generic variable so it can be used in other components. It can also be modified, so when viewing a group
	   'is_item_admin' would be 1 if they are a group admin, 0 if they are not. */
	$bp->is_item_admin = bp_is_home();

	/* Used to determine if the logged in user is a moderator for the current content. */
	$bp->is_item_mod = false;

	$bp->core->table_name_notifications = $wpdb->base_prefix . 'bp_notifications';

	if ( !$bp->current_component && $bp->displayed_user->id )
		$bp->current_component = $bp->default_component;

	do_action( 'bp_core_setup_globals' );
}
add_action( 'plugins_loaded', 'bp_core_setup_globals', 5 );
add_action( '_admin_menu', 'bp_core_setup_globals', 2 ); // must be _admin_menu hook.


/**
 * bp_core_setup_root_uris()
 *
 * Adds the core URIs that should run in the root of the installation.
 *
 * For example: http://example.org/search or http://example.org/members
 *
 * @package BuddyPress Core
 * @uses bp_core_add_root_component() Adds a slug to the root components global variable.
 */
function bp_core_setup_root_uris() {
	/* Add core root components */
	bp_core_add_root_component( BP_MEMBERS_SLUG );
	bp_core_add_root_component( BP_REGISTER_SLUG );
	bp_core_add_root_component( BP_ACTIVATION_SLUG );
	bp_core_add_root_component( BP_SEARCH_SLUG );
	bp_core_add_root_component( BP_HOME_BLOG_SLUG );
}
add_action( 'plugins_loaded', 'bp_core_setup_root_uris', 2 );


/**
 * bp_core_install()
 *
 * Installs the core DB tables for BuddyPress.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @uses dbDelta() Performs a table creation, or upgrade based on what already exists in the DB.
 * @uses bp_core_add_illegal_names() Adds illegal blog names to the WP settings
 */
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
		 	   	KEY component_action (component_action),
				KEY useritem (user_id, is_new)
			   ) {$charset_collate};";

	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	dbDelta( $sql );

	/* Add names of root components to the banned blog list to avoid conflicts */
	bp_core_add_illegal_names();

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

	if ( !is_site_admin() )
		return false;

	require ( BP_PLUGIN_DIR . '/bp-core/bp-core-admin.php' );

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( get_site_option('bp-core-db-version') < BP_CORE_DB_VERSION )
		bp_core_install();
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
	if ( !is_site_admin() )
		return false;

	/* Add the administration tab under the "Site Admin" tab for site administrators */
	bp_core_add_admin_menu_page( array(
		'menu_title' => __( 'BuddyPress', 'buddypress' ),
		'page_title' => __( 'BuddyPress', 'buddypress' ),
		'access_level' => 10, 'file' => 'bp-general-settings',
		'function' => 'bp_core_admin_settings',
		'position' => 2
	) );

	add_submenu_page( 'bp-general-settings', __( 'General Settings', 'buddypress'), __( 'General Settings', 'buddypress' ), 'manage_options', 'bp-general-settings', 'bp_core_admin_settings' );
	add_submenu_page( 'bp-general-settings', __( 'Component Setup', 'buddypress'), __( 'Component Setup', 'buddypress' ), 'manage_options', 'bp-component-setup', 'bp_core_admin_component_setup' );
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
 * @uses bp_core_new_nav_item() Adds a navigation item to the top level buddypress navigation
 * @uses bp_core_new_subnav_item() Adds a sub navigation item to a nav item
 * @uses bp_is_home() Returns true if the current user being viewed is equal the logged in user
 * @uses bp_core_fetch_avatar() Returns the either the thumb or full avatar URL for the user_id passed
 */
function bp_core_setup_nav() {
	global $bp;

	/***
	 * If the extended profiles component is disabled, we need to revert to using the
	 * built in WordPress profile information
	 */
	if ( !function_exists( 'xprofile_install' ) ) {
		/* Fallback wire values if xprofile is disabled */
		$bp->core->profile->slug = 'profile';
		$bp->active_components[$bp->core->profile->slug] = $bp->core->profile->slug;

		/* Add 'Profile' to the main navigation */
		bp_core_new_nav_item( array(
			'name' => __('Profile', 'buddypress'),
			'slug' => $bp->core->profile->slug,
			'position' => 20,
			'screen_function' => 'bp_core_catch_profile_uri',
			'default_subnav_slug' => 'public'
		) );

		$profile_link = $bp->loggedin_user->domain . '/profile/';

		/* Add the subnav items to the profile */
		bp_core_new_subnav_item( array(
			'name' => __( 'Public', 'buddypress' ),
			'slug' => 'public',
			'parent_url' => $profile_link,
			'parent_slug' => $bp->core->profile->slug,
			'screen_function' => 'bp_core_catch_profile_uri'
		) );


		if ( 'profile' == $bp->current_component ) {
			if ( bp_is_home() ) {
				$bp->bp_options_title = __('My Profile', 'buddypress');
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb' ) );
				$bp->bp_options_title = $bp->displayed_user->fullname;
			}
		}
	}
}
add_action( 'plugins_loaded', 'bp_core_setup_nav' );
add_action( 'admin_menu', 'bp_core_setup_nav' );


/********************************************************************************
 * Action Functions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 */

/**
 * bp_core_action_directory_members()
 *
 * Listens to the $bp component and action variables to determine if the user is viewing the members
 * directory page. If they are, it will set up the directory and load the members directory template.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses wp_enqueue_script() Loads a JS script into the header of the page.
 * @uses bp_core_load_template() Loads a specific template file.
 */
function bp_core_action_directory_members() {
	global $bp;

	if ( is_null( $bp->displayed_user->id ) && $bp->current_component == BP_MEMBERS_SLUG ) {
		$bp->is_directory = true;

		do_action( 'bp_core_action_directory_members' );
		bp_core_load_template( apply_filters( 'bp_core_template_directory_members', 'members/index' ) );
	}
}
add_action( 'wp', 'bp_core_action_directory_members', 2 );

/**
 * bp_core_action_set_spammer_status()
 *
 * When a site admin selects "Mark as Spammer/Not Spammer" from the admin menu
 * this action will fire and mark or unmark the user and their blogs as spam.
 * Must be a site admin for this function to run.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_action_set_spammer_status() {
	global $bp, $wpdb;

	if ( !is_site_admin() || bp_is_home() || !$bp->displayed_user->id )
		return false;

	if ( 'admin' == $bp->current_component && ( 'mark-spammer' == $bp->current_action || 'unmark-spammer' == $bp->current_action ) ) {
		/* Check the nonce */
		check_admin_referer( 'mark-unmark-spammer' );

		/* Get the functions file */
		if ( file_exists( ABSPATH . 'wp-admin/includes/mu.php' ) && bp_core_is_multiblog_install() )
			require( ABSPATH . 'wp-admin/includes/mu.php' );

		if ( 'mark-spammer' == $bp->current_action )
			$is_spam = 1;
		else
			$is_spam = 0;

		/* Get the blogs for the user */
		$blogs = get_blogs_of_user( $bp->displayed_user->id, true );

		foreach ( (array) $blogs as $key => $details ) {
			/* Do not mark the main or current root blog as spam */
			if ( 1 == $details->userblog_id || BP_ROOT_BLOG == $details->userblog_id )
				continue;

			/* Update the blog status */
			update_blog_status( $details->userblog_id, 'spam', $is_spam );

			/* Fire the standard WPMU hook */
			do_action( 'make_spam_blog', $details->userblog_id );
		}

		/* Finally, mark this user as a spammer */
		if ( bp_core_is_multiblog_install() )
			$wpdb->update( $wpdb->users, array( 'spam' => $is_spam ), array( 'ID' => $bp->displayed_user->id ) );

		$wpdb->update( $wpdb->users, array( 'user_status' => $is_spam ), array( 'ID' => $bp->displayed_user->id ) );

		if ( $is_spam )
			bp_core_add_message( __( 'User marked as spammer. Spam users are visible only to site admins.', 'buddypress' ) );
		else
			bp_core_add_message( __( 'User removed as spammer.', 'buddypress' ) );

		do_action( 'bp_core_action_set_spammer_status' );

		bp_core_redirect( wp_get_referer() );
	}
}
add_action( 'wp', 'bp_core_action_set_spammer_status', 3 );

/**
 * bp_core_action_delete_user()
 *
 * Allows a site admin to delete a user from the adminbar menu.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_action_delete_user() {
	global $bp;

	if ( !is_site_admin() || bp_is_home() || !$bp->displayed_user->id )
		return false;

	if ( 'admin' == $bp->current_component && 'delete-user' == $bp->current_action ) {
		/* Check the nonce */
		check_admin_referer( 'delete-user' );

		$errors = false;

		if ( bp_core_delete_account( $bp->displayed_user->id ) ) {
			bp_core_add_message( sprintf( __( '%s has been deleted from the system.', 'buddypress' ), $bp->displayed_user->fullname ) );
		} else {
			bp_core_add_message( sprintf( __( 'There was an error deleting %s from the system. Please try again.', 'buddypress' ), $bp->displayed_user->fullname ), 'error' );
			$errors = true;
		}

		do_action( 'bp_core_action_set_spammer_status', $errors );

		if ( $errors )
			bp_core_redirect( $bp->displayed_user->domain );
		else
			bp_core_redirect( $bp->loggedin_user->domain );
	}
}
add_action( 'wp', 'bp_core_action_delete_user', 3 );


/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

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
function bp_core_get_user_domain( $user_id, $user_nicename = false, $user_login = false ) {
	global $bp;

	if ( !$user_id ) return;

	if ( $domain = wp_cache_get( 'bp_user_domain_' . $user_id, 'bp' ) )
		return apply_filters( 'bp_core_get_user_domain', $domain );

	if ( !$user_nicename && !$user_login ) {
		$ud = get_userdata($user_id);
		$user_nicename = $ud->user_nicename;
		$user_login = $ud->user_login;
	}

	if ( defined( 'BP_ENABLE_USERNAME_COMPATIBILITY_MODE' ) )
		$username = $user_login;
	else
		$username = $user_nicename;

	/* If we are using a members slug, include it. */
	if ( !defined( 'BP_ENABLE_ROOT_PROFILES' ) )
		$domain = $bp->root_domain . '/' . BP_MEMBERS_SLUG . '/' . $username . '/';
	else
		$domain = $bp->root_domain . '/' . $username . '/';

	/* Cache the link */
	wp_cache_set( 'bp_user_domain_' . $user_id, $domain, 'bp' );

	return apply_filters( 'bp_core_get_user_domain', $domain );
}

/**
 * bp_core_get_root_domain()
 *
 * Returns the domain for the root blog.
 * eg: http://domain.com/ OR https://domain.com
 *
 * @package BuddyPress Core
 * @uses get_blog_option() WordPress function to fetch blog meta.
 * @return $domain The domain URL for the blog.
 */
function bp_core_get_root_domain() {
	global $current_blog;

	if ( defined( 'BP_ENABLE_MULTIBLOG' ) )
		$domain = get_blog_option( $current_blog->blog_id, 'siteurl' );
	else
		$domain = get_blog_option( BP_ROOT_BLOG, 'siteurl' );

	return apply_filters( 'bp_core_get_root_domain', $domain );
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
	return apply_filters( 'bp_core_get_displayed_userid', bp_core_get_userid( $user_login ) );
}

/**
 * bp_core_new_nav_item()
 *
 * Adds a navigation item to the main navigation array used in BuddyPress themes.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_new_nav_item( $args = '' ) {
	global $bp;

	$defaults = array(
		'name' => false, // Display name for the nav item
		'slug' => false, // URL slug for the nav item
		'item_css_id' => false, // The CSS ID to apply to the HTML of the nav item
		'show_for_displayed_user' => true, // When viewing another user does this nav item show up?
		'site_admin_only' => false, // Can only site admins see this nav item?
		'position' => 99, // Index of where this nav item should be positioned
		'screen_function' => false, // The name of the function to run when clicked
		'default_subnav_slug' => false // The slug of the default subnav item to select when clicked
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	/* If we don't have the required info we need, don't create this subnav item */
	if ( empty($name) || empty($slug) )
		return false;

	/* If this is for site admins only and the user is not one, don't create the subnav item */
	if ( $site_admin_only && !is_site_admin() )
		return false;

	if ( empty( $item_css_id ) )
		$item_css_id = $slug;

	$bp->bp_nav[$slug] = array(
		'name' => $name,
		'link' => $bp->loggedin_user->domain . $slug . '/',
		'css_id' => $item_css_id,
		'show_for_displayed_user' => $show_for_displayed_user,
		'position' => $position
	);

 	/***
	 * If this nav item is hidden for the displayed user, and
	 * the logged in user is not the displayed user
	 * looking at their own profile, don't create the nav item.
	 */
	if ( !$show_for_displayed_user && !bp_is_my_profile() )
		return false;

	/***
 	 * If we are not viewing a user, and this is a root component, don't attach the
 	 * default subnav function so we can display a directory or something else.
 	 */
	if ( bp_core_is_root_component( $slug ) && !$bp->displayed_user->id )
		return;

	if ( $bp->current_component == $slug && !$bp->current_action ) {
		if ( !is_object( $screen_function[0] ) )
			add_action( 'wp', $screen_function, 3 );
		else
			add_action( 'wp', array( &$screen_function[0], $screen_function[1] ), 3 );

		if ( $default_subnav_slug )
			$bp->current_action = $default_subnav_slug;
	}
}

/**
 * bp_core_new_nav_default()
 *
 * Modify the default subnav item to load when a top level nav item is clicked.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_new_nav_default( $args = '' ) {
	global $bp;

	$defaults = array(
		'parent_slug' => false, // Slug of the parent
		'screen_function' => false, // The name of the function to run when clicked
		'subnav_slug' => false // The slug of the subnav item to select when clicked
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

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
 * bp_core_sort_nav_items()
 *
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

	foreach ( $bp->bp_nav as $slug => $nav_item ) {
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
add_action( 'wp_head', 'bp_core_sort_nav_items' );

/**
 * bp_core_new_subnav_item()
 *
 * Adds a navigation item to the sub navigation array used in BuddyPress themes.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_new_subnav_item( $args = '' ) {
	global $bp;

	$defaults = array(
		'name' => false, // Display name for the nav item
		'slug' => false, // URL slug for the nav item
		'parent_slug' => false, // URL slug of the parent nav item
		'parent_url' => false, // URL of the parent item
		'item_css_id' => false, // The CSS ID to apply to the HTML of the nav item
		'user_has_access' => true, // Can the logged in user see this nav item?
		'site_admin_only' => false, // Can only site admins see this nav item?
		'position' => 90, // Index of where this nav item should be positioned
		'screen_function' => false // The name of the function to run when clicked
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	/* If we don't have the required info we need, don't create this subnav item */
	if ( empty($name) || empty($slug) || empty($parent_slug) || empty($parent_url) || empty($screen_function) )
		return false;

	/* If this is for site admins only and the user is not one, don't create the subnav item */
	if ( $site_admin_only && !is_site_admin() )
		return false;

	if ( empty( $item_css_id ) )
		$item_css_id = $slug;

	$bp->bp_options_nav[$parent_slug][$slug] = array(
		'name' => $name,
		'link' => $parent_url . $slug . '/',
		'slug' => $slug,
		'css_id' => $item_css_id,
		'position' => $position,
		'user_has_access' => $user_has_access
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

	foreach ( $bp->bp_options_nav as $parent_slug => $subnav_items ) {
		if ( !is_array( $subnav_items ) )
			continue;

		foreach ( $subnav_items as $subnav_item ) {
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
		unset($temp);
	}
}
add_action( 'wp_head', 'bp_core_sort_subnav_items' );

/**
 * bp_core_remove_nav_item()
 *
 * Removes a navigation item from the sub navigation array used in BuddyPress themes.
 *
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @param $slug The slug of the sub navigation item.
 */
function bp_core_remove_nav_item( $parent_id ) {
	global $bp;

	/* Unset subnav items for this nav item */
	foreach( $bp->bp_options_nav[$parent_id] as $subnav_item ) {
		bp_core_remove_subnav_item( $parent_id, $subnav_item['slug'] );
	}

	unset( $bp->bp_nav[$parent_id] );
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

	$function = $bp->bp_options_nav[$parent_id][$slug]['screen_function'];

	if ( $function ) {
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
 * bp_core_reset_subnav_items()
 *
 * Clear the subnav items for a specific nav item.
 *
 * @package BuddyPress Core
 * @param $parent_id The id of the parent navigation item.
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_reset_subnav_items($parent_slug) {
	global $bp;

	unset($bp->bp_options_nav[$parent_slug]);
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

	if ( isset( $_GET['random-member'] ) ) {
		$user = BP_Core_User::get_users( 'random', 1);

		$ud = get_userdata( $user['users'][0]->user_id );

		bp_core_redirect( bp_core_get_user_domain( $user['users'][0]->user_id ) );
	}
}
add_action( 'wp', 'bp_core_get_random_member' );

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

	if ( !empty( $username ) )
		return apply_filters( 'bp_core_get_userid', $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . CUSTOM_USER_TABLE . " WHERE user_login = %s", $username ) ) );
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
		$username =  __( 'You', 'buddypress' );

	if ( !$ud = get_userdata($uid) )
		return false;

	$username = $ud->user_login;

	return apply_filters( 'bp_core_get_username', $username );
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

	if ( !$url = wp_cache_get( 'bp_user_url_' . $uid, 'bp' ) ) {
		$url = bp_core_get_user_domain( $uid );
	}

	return apply_filters( 'bp_core_get_userurl', $url );
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
	if ( !$email = wp_cache_get( 'bp_user_email_' . $uid, 'bp' ) ) {
		$ud = get_userdata($uid);
		$email = $ud->user_email;
	}
	return apply_filters( 'bp_core_get_user_email', $email );
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

	if ( function_exists( 'bp_core_get_user_displayname' ) ) {
		$display_name = bp_core_get_user_displayname( $user_id );

		if ( $with_s )
			$display_name = sprintf( __( "%s's", 'buddypress' ), $display_name );

	} else {
		$display_name = $ud->display_name;
	}

	if ( $no_anchor )
		return $display_name;

	if ( !$url = bp_core_get_userurl($user_id) )
		return false;

	if ( $just_link )
		return $url;

	return apply_filters( 'bp_core_get_userlink', '<a href="' . $url . '">' . $display_name . '</a>', $user_id );
}


/**
 * bp_core_get_user_displayname()
 *
 * Fetch the display name for a user. This will use the "Name" field in xprofile if it is installed.
 * Otherwise, it will fall back to the normal WP display_name, or user_nicename, depending on what has been set.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses wp_cache_get() Will try and fetch the value from the cache, rather than querying the DB again.
 * @uses get_userdata() Fetches the WP userdata for a specific user.
 * @uses xprofile_set_field_data() Will update the field data for a user based on field name and user id.
 * @uses wp_cache_set() Adds a value to the cache.
 * @return str The display name for the user in question.
 */
function bp_core_get_user_displayname( $user_id ) {
	global $bp;

	if ( !$user_id )
		return false;

	if ( !$fullname = wp_cache_get( 'bp_user_fullname_' . $user_id, 'bp' ) ) {
		if ( function_exists('xprofile_install') ) {
			$fullname = xprofile_get_field_data( 1, $user_id );

			if ( empty($fullname) || !$fullname ) {
				$ud = get_userdata($user_id);

				if ( !empty( $ud->display_name ) )
					$fullname = $ud->display_name;
				else
					$fullname = $ud->user_nicename;

				xprofile_set_field_data( 1, $user_id, $fullname );
			}
		} else {
			$ud = get_userdata($user_id);

			if ( !empty( $ud->display_name ) )
				$fullname = $ud->display_name;
			else
				$fullname = $ud->user_nicename;
		}

		wp_cache_set( 'bp_user_fullname_' . $user_id, $fullname, 'bp' );
	}

	return apply_filters( 'bp_core_get_user_displayname', $fullname );
}
add_filter( 'bp_core_get_user_displayname', 'strip_tags', 1 );
add_filter( 'bp_core_get_user_displayname', 'trim' );
add_filter( 'bp_core_get_user_displayname', 'stripslashes' );


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
	return apply_filters( 'bp_core_get_userlink_by_email', bp_core_get_userlink( $user->ID, false, false, true ) );
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

	$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . CUSTOM_USER_TABLE . " WHERE user_login = %s", $username ) );
	return apply_filters( 'bp_core_get_userlink_by_username', bp_core_get_userlink( $user_id, false, false, true ) );
}

/**
 * bp_core_get_total_member_count()
 *
 * Returns the total number of members for the installation.
 *
 * @package BuddyPress Core
 * @return int The total number of members.
 */
function bp_core_get_total_member_count() {
	global $wpdb, $bp;

	$status_sql = bp_core_get_status_sql();

	$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM " . CUSTOM_USER_TABLE . " WHERE {$status_sql}" ) );
	return apply_filters( 'bp_core_get_total_member_count', $count );
}

/**
 * bp_core_is_user_spammer()
 *
 * Checks if the user has been marked as a spammer.
 *
 * @package BuddyPress Core
 * @param $user_id int The id for the user.
 * @return int 1 if spammer, 0 if not.
 */
function bp_core_is_user_spammer( $user_id ) {
	global $wpdb;

	if ( bp_core_is_multiblog_install() )
		$is_spammer = (int) $wpdb->get_var( $wpdb->prepare( "SELECT spam FROM " . CUSTOM_USER_TABLE . " WHERE ID = %d", $user_id ) );
	else
		$is_spammer = (int) $wpdb->get_var( $wpdb->prepare( "SELECT user_status FROM " . CUSTOM_USER_TABLE . " WHERE ID = %d", $user_id ) );

	return apply_filters( 'bp_core_is_user_spammer', $is_spammer );
}

/**
 * bp_core_is_user_deleted()
 *
 * Checks if the user has been marked as deleted.
 *
 * @package BuddyPress Core
 * @param $user_id int The id for the user.
 * @return int 1 if deleted, 0 if not.
 */
function bp_core_is_user_deleted( $user_id ) {
	global $wpdb;

	return apply_filters( 'bp_core_is_user_spammer', (int) $wpdb->get_var( $wpdb->prepare( "SELECT deleted FROM " . CUSTOM_USER_TABLE . " WHERE ID = %d", $user_id ) ) );
}

/**
 * bp_core_format_time()
 */
function bp_core_format_time( $time, $just_date = false ) {
	if ( !$time )
		return false;

	$date = date( "F j, Y ", $time );

	if ( !$just_date ) {
		$date .= __('at', 'buddypress') . date( ' g:iA', $time );
	}

	return $date;
}


/**
 * bp_core_add_message()
 *
 * Adds a feedback (error/success) message to the WP cookie so it can be displayed after the page reloads.
 *
 * @package BuddyPress Core
 */
function bp_core_add_message( $message, $type = false ) {
	global $bp;

	if ( !$type )
		$type = 'success';

	/* Send the values to the cookie for page reload display */
	@setcookie( 'bp-message', $message, time()+60*60*24, COOKIEPATH );
	@setcookie( 'bp-message-type', $type, time()+60*60*24, COOKIEPATH );

	/***
	 * Send the values to the $bp global so we can still output messages
	 * without a page reload
	 */
	$bp->template_message = $message;
	$bp->template_message_type = $type;
}

/**
 * bp_core_setup_message()
 *
 * Checks if there is a feedback message in the WP cookie, if so, adds a "template_notices" action
 * so that the message can be parsed into the template and displayed to the user.
 *
 * After the message is displayed, it removes the message vars from the cookie so that the message
 * is not shown to the user multiple times.
 *
 * @package BuddyPress Core
 * @global $bp_message The message text
 * @global $bp_message_type The type of message (error/success)
 * @uses setcookie() Sets a cookie value for the user.
 */
function bp_core_setup_message() {
	global $bp;

	if ( empty( $bp->template_message ) )
		$bp->template_message = $_COOKIE['bp-message'];

	if ( empty( $bp->template_message_type ) )
		$bp->template_message_type = $_COOKIE['bp-message-type'];

	add_action( 'template_notices', 'bp_core_render_message' );

	@setcookie( 'bp-message', false, time() - 1000, COOKIEPATH );
	@setcookie( 'bp-message-type', false, time() - 1000, COOKIEPATH );
}
add_action( 'wp', 'bp_core_setup_message' );

/**
 * bp_core_render_message()
 *
 * Renders a feedback message (either error or success message) to the theme template.
 * The hook action 'template_notices' is used to call this function, it is not called directly.
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function bp_core_render_message() {
	global $bp;

	if ( $bp->template_message ) {
		$type = ( 'success' == $bp->template_message_type ) ? 'updated' : 'error';
	?>
		<div id="message" class="<?php echo $type; ?>">
			<p><?php echo stripslashes( attribute_escape( $bp->template_message ) ); ?></p>
		</div>
	<?php
		do_action( 'bp_core_render_message' );
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

	$older_date = strtotime( gmdate( 'Y-m-d H:i:s', $older_date ) );

	/* $newer_date will equal false if we want to know the time elapsed between a date and the current time */
	/* $newer_date will have a value if we want to work out time elapsed between two known dates */
	$newer_date = ( !$newer_date ) ? ( strtotime( gmdate( 'Y-m-d H:i:s' ) ) + ( 60*60*0 ) ) : $newer_date;

	/* Difference in seconds */
	$since = $newer_date - $older_date;

	/* Something went wrong with date calculation and we ended up with a negative date. */
	if ( 0 > $since )
		return __( 'sometime', 'buddypress' );

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
	if ( $i + 2 < $j ) {
		$seconds2 = $chunks[$i + 1][0];
		$name2 = $chunks[$i + 1][1];

		//if ( $chunks[$i + 1][1] == __( 'second', 'buddypress' ) ) return $output;

		if ( ( $count2 = floor( ( $since - ( $seconds * $count ) ) / $seconds2 ) ) != 0 ) {
			/* Add to output var */
			$output .= ( 1 == $count2 ) ? _c( ',|Separator in time since', 'buddypress' ) . ' 1 '. $chunks[$i + 1][1] : _c( ',|Separator in time since', 'buddypress' ) . ' ' . $count2 . ' ' . $chunks[$i + 1][2];
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
 * bp_core_get_site_path()
 *
 * Get the path of of the current site.
 *
 * @package BuddyPress Core
 * @global $comment WordPress comment global for the current comment.
 * @uses bp_core_get_userlink_by_email() Fetches a userlink via email address.
 */
function bp_core_get_site_path() {
	global $current_site;

	return $current_site->path;
}

/**
 * bp_core_redirect()
 *
 * Performs a status safe wp_redirect() that is compatible with bp_catch_uri()
 *
 * @package BuddyPress Core
 * @global $bp_no_status_set Makes sure that there are no conflicts with status_header() called in bp_core_do_catch_uri()
 * @uses get_themes()
 * @return An array containing all of the themes.
 */
function bp_core_redirect( $location, $status = 302 ) {
	global $bp_no_status_set;

	// Make sure we don't call status_header() in bp_core_do_catch_uri()
    // as this conflicts with wp_redirect()
	$bp_no_status_set = true;

	wp_redirect( $location, $status );
	die;
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
	$referer = explode( '/', wp_get_referer() );
	unset( $referer[0], $referer[1], $referer[2] );
	return implode( '/', $referer );
}

/**
 * bp_core_add_illegal_names()
 *
 * Adds illegal names to WP so that root components will not conflict with
 * blog names on a subdirectory installation.
 *
 * For example, it would stop someone creating a blog with the slug "groups".
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
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
	return get_blog_option( BP_ROOT_BLOG, 'blogname' );
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
 * bp_core_delete_account()
 *
 * Allows a user to completely remove their account from the system
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses check_admin_referer() Checks for a valid security nonce.
 * @uses is_site_admin() Checks to see if the user is a site administrator.
 * @uses wpmu_delete_user() Deletes a user from the system.
 */
function bp_core_delete_account( $user_id = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	/* Make sure account deletion is not disabled */
	if ( (int)get_site_option( 'bp-disable-account-deletion' ) )
		return false;

	/* Site admins should not be allowed to be deleted */
	if ( is_site_admin( bp_core_get_username( $user_id ) ) )
		return false;

	require_once( ABSPATH . '/wp-admin/includes/mu.php' );
	require_once( ABSPATH . '/wp-admin/includes/user.php' );

	return wpmu_delete_user( $user_id );
}


/**
 * bp_core_search_site()
 *
 * A javascript free implementation of the search functions in BuddyPress
 *
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @param $slug The slug to redirect to for searching.
 */
function bp_core_action_search_site( $slug = false ) {
	global $bp;

	if ( $bp->current_component == BP_SEARCH_SLUG ) {
		$search_terms = $_POST['search-terms'];
		$search_which = $_POST['search-which'];

		if ( !$slug || empty( $slug ) ) {
			switch ( $search_which ) {
				case 'members': default:
					$slug = BP_MEMBERS_SLUG;
					$var = '/?s=';
					break;
				case 'groups':
					$slug = BP_GROUPS_SLUG;
					$var = '/?s=';
					break;
				case 'forums':
					$slug = BP_FORUMS_SLUG;
					$var = '/?fs=';
					break;
				case 'blogs':
					$slug = BP_BLOGS_SLUG;
					$var = '/?s=';
					break;
			}
		}

		$search_url = apply_filters( 'bp_core_search_site', site_url( $slug . $var . urlencode($search_terms) ), $search_terms );

		bp_core_redirect( $search_url );
	}
}
add_action( 'init', 'bp_core_action_search_site', 5 );


/**
 * bp_core_ucfirst()
 *
 * Localization safe ucfirst() support.
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

/**
 * bp_core_print_generation_time()
 *
 * Prints the generation time in the footer of the site.
 *
 * @package BuddyPress Core
 */
function bp_core_print_generation_time() {
	global $wpdb;
	?>
<!-- Generated in <?php timer_stop(1); ?> seconds. <?php echo get_num_queries(); ?> -->
	<?php
}
add_action( 'wp_footer', 'bp_core_print_generation_time' );

/**
 * bp_core_add_admin_menu_page()
 *
 * A better version of add_admin_menu_page() that allows positioning of menus.
 *
 * @package BuddyPress Core
 */
function bp_core_add_admin_menu_page( $args = '' ) {
	global $menu, $admin_page_hooks, $_registered_pages;

	$defaults = array(
		'page_title' => '',
		'menu_title' => '',
		'access_level' => 2,
		'file' => false,
		'function' => false,
		'icon_url' => false,
		'position' => 100
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$file = plugin_basename( $file );

	$admin_page_hooks[$file] = sanitize_title( $menu_title );

	$hookname = get_plugin_page_hookname( $file, '' );
	if (!empty ( $function ) && !empty ( $hookname ))
		add_action( $hookname, $function );

	if ( empty($icon_url) )
		$icon_url = 'images/generic.png';
	elseif ( is_ssl() && 0 === strpos($icon_url, 'http://') )
		$icon_url = 'https://' . substr($icon_url, 7);

	do {
		$position++;
	} while ( !empty( $menu[$position] ) );

	$menu[$position] = array ( $menu_title, $access_level, $file, $page_title, 'menu-top ' . $hookname, $hookname, $icon_url );

	$_registered_pages[$hookname] = true;

	return $hookname;
}

/**
 * bp_core_boot_spammer()
 *
 * When a user logs in, check if they have been marked as a spammer. If yes then simply
 * redirect them to the home page and stop them from logging in.
 *
 * @package BuddyPress Core
 * @param $username The username of the user
 * @uses delete_usermeta() deletes a row from the wp_usermeta table based on meta_key
 */
function bp_core_boot_spammer( $auth_obj, $username ) {
	global $bp;

	$user = get_userdatabylogin( $username );

	if ( (int)$user->spam )
		bp_core_redirect( $bp->root_domain );
	else
		return $auth_obj;
}
add_filter( 'authenticate', 'bp_core_boot_spammer', 11, 2 );

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

	/* Flush the cache to remove the user from all cached objects */
	wp_cache_flush();
}
add_action( 'wpmu_delete_user', 'bp_core_remove_data', 1 );
add_action( 'delete_user', 'bp_core_remove_data', 1 );
add_action( 'make_spam_user', 'bp_core_remove_data', 1 );

/**
 * bp_load_buddypress_textdomain()
 *
 * Load the buddypress translation file for current language
 *
 * @package BuddyPress Core
 */
function bp_core_load_buddypress_textdomain() {
	$locale = apply_filters( 'buddypress_locale', get_locale() );
	$mofile = BP_PLUGIN_DIR . "/bp-languages/buddypress-$locale.mo";

	if ( file_exists( $mofile ) )
		load_textdomain( 'buddypress', $mofile );
}
add_action ( 'plugins_loaded', 'bp_core_load_buddypress_textdomain', 5 );

function bp_core_add_ajax_hook() {
	/* Theme only, we already have the wp_ajax_ hook firing in wp-admin */
	if ( !defined( 'WP_ADMIN' ) )
		do_action( 'wp_ajax_' . $_REQUEST['action'] );
}
add_action( 'init', 'bp_core_add_ajax_hook' );

/**
 * bp_core_update_message()
 *
 * Add an extra update message to the update plugin notification.
 *
 * @package BuddyPress Core
 */
function bp_core_update_message() {
	echo '<p style="color: red; margin: 3px 0 0 0; border-top: 1px solid #ddd; padding-top: 3px">' . __( 'IMPORTANT: <a href="http://codex.buddypress.org/getting-started/upgrading-from-10x/">Read this before attempting to update BuddyPress</a>', 'buddypress' ) . '</p>';
}
add_action( 'in_plugin_update_message-buddypress/bp-loader.php', 'bp_core_update_message' );

/**
 * bp_core_filter_template_paths()
 *
 * Add fallback for the bp-sn-parent theme template locations used in BuddyPress versions
 * older than 1.2.
 *
 * @package BuddyPress Core
 */
function bp_core_filter_template_paths() {
	if ( 'bp-sn-parent' != basename( TEMPLATEPATH ) && !defined( 'BP_CLASSIC_TEMPLATE_STRUCTURE' ) )
		return false;

	add_filter( 'bp_core_template_directory_members', create_function( '', 'return "directories/members/index";' ) );
	add_filter( 'bp_core_template_plugin', create_function( '', 'return "plugin-template";' ) );
}
add_action( 'init', 'bp_core_filter_template_paths' );

/**
 * bp_core_clear_user_object_cache()
 *
 * Clears all cached objects for a user, or a user is part of.
 *
 * @package BuddyPress Core
 */
function bp_core_clear_user_object_cache( $user_id ) {
	wp_cache_delete( 'bp_user_' . $user_id, 'bp' );
	wp_cache_delete( 'bp_core_avatar_v1_u' . $user_id, 'bp' );
	wp_cache_delete( 'bp_core_avatar_v2_u' . $user_id, 'bp' );
	wp_cache_delete( 'online_users' );
	wp_cache_delete( 'newest_users' );
}

// List actions to clear object caches on
add_action( 'bp_core_delete_avatar', 'bp_core_clear_user_object_cache' );
add_action( 'bp_core_avatar_save', 'bp_core_clear_user_object_cache' );

// List actions to clear super cached pages on, if super cache is installed
add_action( 'wp_login', 'bp_core_clear_cache' );
add_action( 'bp_core_delete_avatar', 'bp_core_clear_cache' );
add_action( 'bp_core_avatar_save', 'bp_core_clear_cache' );
add_action( 'bp_core_render_notice', 'bp_core_clear_cache' );

// Remove the catch non existent blogs hook so WPMU doesn't think BuddyPress pages are non existing blogs
remove_action( 'plugins_loaded', 'catch_nonexistant_blogs' );

?>