<?php

/* Define the protocol to be used, change to https:// if on secure server. */
define( 'PROTOCOL', 'http://' );

/* Define the current version number for checking if DB tables are up to date. */
define( 'BP_CORE_VERSION', '0.2.4' );

/* Require all needed files */
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-catchuri.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-classes.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-cssjs.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-avatars.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-templatetags.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-adminbar.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/admin-mods/bp-core-remove-blogtabs.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/admin-mods/bp-core-admin-styles.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/homebase-creation/bp-core-homebase-functions.php' );

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
	global $bp;
	global $current_user, $current_component, $current_action;
	global $action_variables;

	$bp = array(
		/* The user ID of the user who is currently logged in. */
		'loggedin_userid' 	=> $current_user->ID,
		
		/* The domain for the user currently logged in. eg: http://andy.domain.com/ */
		'loggedin_domain' 	=> bp_core_get_loggedin_domain(),
		
		/* The domain for the user currently being viewed */
		'current_domain'  	=> bp_core_get_current_domain(),
		
		/* The user id of the user currently being viewed */
		'current_userid'  	=> bp_core_get_current_userid(),
		
		/* The component being used eg: http://andy.domain.com/ [profile] */
		'current_component' => $current_component, // type: string
		
		/* The current action for the component eg: http://andy.domain.com/profile/ [edit] */
		'current_action'	=> $current_action, // type: string
		
		/* The action variables for the current action eg: http://andy.domain.com/profile/edit/ [group] / [6] */
		'action_variables'	=> $action_variables, // type: array

		/* The default component to use if none are set and someone visits: http://andy.domain.com/ */
		'default_component'	=> 'profile',
		
		/* Sets up the array container for the component navigation rendered by bp_get_nav() */
		'bp_nav'		  	=> array(),
		
		/* Sets up the array container for the user navigation rendered by bp_get_user_nav() */
		'bp_users_nav'	  	=> array(),
		
		/* Sets up the array container for the component options navigation rendered by bp_get_options_nav() */
		'bp_options_nav'	=> array(),
		
		/* Sets up container used for the title of the current component option and rendered by bp_get_options_title() */
		'bp_options_title'	=> '',
		
		/* Sets up container used for the avatar of the current component being viewed. Rendered by bp_get_options_avatar() */
		'bp_options_avatar'	=> '',
		
		/* Sets up container for callback messages rendered by bp_core_render_notice() */
		'message'			=> '',
		
		/* Sets up container for callback message type rendered by bp_core_render_notice() */
		'message_type'		=> '' // error/success
	);
	
	if ( !$bp['current_component'] )
		$bp['current_component'] = $bp['default_component'];
}
add_action( 'wp', 'bp_core_setup_globals', 1 );
add_action( '_admin_menu', 'bp_core_setup_globals', 1 ); // must be _admin_menu hook.

/**
 * bp_core_component_exists()
 *
 * Check to see if a component with the given name actually exists.
 * If not, redirect to the 404.
 * 
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @return false if no, or true if yes.
 */
function bp_core_component_exists() {
	global $bp, $wpdb;

	if ( $wpdb->blogid == get_usermeta( $bp['current_userid'], 'home_base' ) ) {
		$component_check = $bp['current_component'];

		if ( strpos( $component_check, 'activate.php' ) )
			return true;

		if ( empty($bp[$component_check]) ) {
			status_header('404');
			load_template( TEMPLATEPATH . '/header.php'); 
			load_template( TEMPLATEPATH . '/404.php');
			load_template( TEMPLATEPATH . '/footer.php');
			die;
		}
	}
}
add_action( 'wp', 'bp_core_component_exists', 10 );


/**
 * bp_core_add_settings_tab()
 *
 * Adds a custom settings tab to the home base for the user
 * in the admin area.
 * 
 * @package BuddyPress Core
 * @global $menu The global WordPress admin navigation menu.
 */
function bp_core_add_settings_tab() {
	global $menu;
	
	$account_settings_tab = add_menu_page( __('Account'), __('Account'), 10, 'bp-core/admin-mods/bp-core-account-tab.php' );
}
add_action( 'admin_menu', 'bp_core_add_settings_tab' );

/**
 * bp_core_get_loggedin_domain()
 *
 * Returns the domain for the user that is currently logged in.
 * eg: http://andy.domain.com/ or http://domain.com/andy/
 * 
 * @package BuddyPress Core
 * @global $current_user WordPress global variable containing current logged in user information
 * @param optional user_id
 * @uses get_usermeta() WordPress function to get the usermeta for a current user.
 */
function bp_core_get_loggedin_domain( $user_id = null ) {
	global $current_user;
	
	if ( !$user_id )
		$user_id = $current_user->ID;
	
	/* Get the ID of the home base blog */
	$home_base_id = get_usermeta( $user_id, 'home_base' );
	
	return get_blog_option( $home_base_id, 'siteurl' ) . '/';
}

/**
 * bp_core_get_current_domain()
 *
 * Returns the domain for the user that is currently being viewed.
 * eg: http://andy.domain.com/ or http://domain.com/andy/
 * 
 * @package BuddyPress Core
 * @global $current_blog WordPress global variable containing information for the current blog being viewed.
 * @uses get_bloginfo() WordPress function to return the value of a blog setting based on param passed
 * @return $current_domain The domain for the user that is currently being viewed.
 */
function bp_core_get_current_domain() {
	global $current_blog;
	
	if ( VHOST == 'yes' ) {
		$current_domain = PROTOCOL . $current_blog->domain . '/';
	} else {
		$current_domain = get_bloginfo('wpurl') . '/';
	}
	
	return $current_domain;
}

/**
 * bp_core_get_current_userid()
 *
 * Returns the user id for the user that is currently being viewed.
 * eg: http://andy.domain.com/ or http://domain.com/andy/
 * 
 * @package BuddyPress Core
 * @global $current_blog WordPress global containing information and settings for the current blog being viewed.
 * @uses bp_core_get_user_home_userid() Checks to see if there is user_home usermeta set for the current_blog.
 * @return $current_userid The user id for the user that is currently being viewed, return zero if this is not a user home and just a normal blog.
 */
function bp_core_get_current_userid() {
	global $current_blog;
	
	/* Get the ID of the current blog being viewed. */
	$blog_id = $current_blog->blog_id;
	
	/* Check to see if this is a user home, and if it is, get the user id */
	if ( !$current_userid = bp_core_get_homebase_userid( $blog_id ) )
		return false; // return 0 if this is a normal blog, and not a user home.
	
	return $current_userid;
}

/**
 * bp_core_get_user_home_userid()
 *
 * Checks to see if there is user_home usermeta set for the current_blog.
 * If it is set, return the user_id, if not, return false.
 * 
 * @package BuddyPress Core
 * @param $blog_id The ID of the blog to check user_home metadata for.
 * @global $wpdb WordPress DB access object.
 * @return $current_userid The user id for the home base.
 */
function bp_core_get_homebase_userid( $blog_id ) {
	global $wpdb;
	
	return $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = 'home_base' AND meta_value = %d", $blog_id ) );
}

/**
 * bp_core_is_home_base()
 *
 * Checks a blog id to see if it is a home base or not.
 * 
 * @package BuddyPress Core
 * @param $blog_id The ID of the blog to check user_home metadata for.
 * @global $wpdb WordPress DB access object.
 * @return $current_userid The user id for the home base.
 */
function bp_core_is_home_base( $blog_id ) {
	global $wpdb;
	
 	if ( $wpdb->get_var( $wpdb->prepare( "SELECT umeta_id FROM " . $wpdb->base_prefix . "usermeta WHERE meta_key = 'home_base' AND meta_value = %d", $blog_id ) ) )
		return true;
	
	return false;
}

/**
 * bp_core_user_has_home()
 *
 * Checks to see if a user has assigned a blog as their user_home.
 * 
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses get_usermeta() WordPress function to get the usermeta for a current user.
 * @return false if no, or true if yes.
 */
function bp_core_user_has_home() {
	global $bp;

	if ( get_usermeta( $bp['loggedin_userid'], 'home_base' ) == '' )
		return false;
	
	return true;
}

/**
 * bp_core_get_primary_username()
 *
 * Returns the username based on http:// [username] .site.com OR http://site.com/ [username]
 * 
 * @package BuddyPress Core
 * @global $current_blog WordPress global containing information and settings for the current blog
 * @return $siteuser Username for current blog or user home.
 */
function bp_core_get_primary_username() {
	global $current_blog;
	
	if ( VHOST == 'yes' ) {
		$siteuser = explode('.', $current_blog->domain);
		$siteuser = $siteuser[0];
	} else {
		$siteuser = str_replace('/', '', $current_blog->path);
	}
	
	return $siteuser;
}

/**
 * bp_core_start_buffer()
 *
 * Start the output buffer to replace content not easily accessible.
 * 
 * @package BuddyPress Core
 */
function bp_core_start_buffer() {
	ob_start();
	add_action( 'dashmenu', 'bp_core_stop_buffer' );
} 
add_action( 'admin_menu', 'bp_core_start_buffer' );

/**
 * bp_core_stop_buffer()
 *
 * Stop the output buffer to replace content not easily accessible.
 * 
 * @package BuddyPress Core
 */
function bp_core_stop_buffer() {
	$contents = ob_get_contents();
	ob_end_clean();
	bp_core_blog_switcher( $contents );
}

/**
 * bp_core_blog_switcher()
 *
 * Replaces the standard blog switcher included in the WordPress core so that
 * BuddyPress specific icons can be used in tabs and the order can be changed.
 * An output buffer is used, as the function cannot be overridden or replaced
 * any other way.
 * 
 * @package BuddyPress Core
 * @param $contents str The contents of the buffer.
 * @global $current_user obj WordPress global containing information and settings for the current user
 * @global $blog_id int WordPress global containing the current blog id
 * @return $siteuser Username for current blog or user home.
 */
function bp_core_blog_switcher( $contents ) {
	global $current_user, $blog_id; // current blog
	
	/* Code duplicated from wp-admin/includes/mu.php */
	/* function blogswitch_markup() */
	
	$filter = preg_split( '/\<ul id=\"dashmenu\"\>[\S\s]/', $contents );
	echo $filter[0];
	
	$list = array();
	$options = array();

	$home_base = get_usermeta( $current_user->ID, 'home_base' );
	
	foreach ( $blogs = get_blogs_of_user( $current_user->ID ) as $blog ) {
		if ( !$blog->blogname )
			continue;

		// Use siteurl for this in case of mapping
		$parsed = parse_url( $blog->siteurl );
		$domain = $parsed['host'];
		
		if ( $blog->userblog_id == $home_base ) {
			$current = ' id="primary_blog"';
			$image   = ' style="background-image: url(' . get_option('home') . '/wp-content/mu-plugins/bp-core/images/member.png);
							  background-position: 2px 4px;
							  background-repeat: no-repeat;
							  padding-left: 22px;"';
		} else { 
			$current = ''; 
			$image   = ' style="background-image: url(' . get_option('home') . '/wp-content/mu-plugins/bp-core/images/blog.png);
							  background-position: 3px 3px;
							  background-repeat: no-repeat;
							  padding-left: 22px;"';; 
		}
			
		if ( VHOST == 'yes' ) {
			if ( $_SERVER['HTTP_HOST'] === $domain ) {
				$current  .= ' class="current"';
				$selected  = ' selected="selected"';
			} else {
				$current  .= '';
				$selected  = '';
			}			
		} else {
			$path = explode( '/', str_replace( '/wp-admin', '', $_SERVER['REQUEST_URI'] ) );

			if ( $path[1] == str_replace( '/', '', $blog->path ) ) {
				$current  .= ' class="current"';
				$selected  = ' selected="selected"';
			} else {
				$current  .= '';
				$selected  = '';
			}
		}

		$url = clean_url( $blog->siteurl ) . '/wp-admin/';
		$name = wp_specialchars( strip_tags( $blog->blogname ) );
		
		$list_item   = "<li><a$image href='$url'$current>$name</a></li>";
		$option_item = "<option value='$url'$selected>$name</option>";

		$list[]    = $list_item;
		$options[] = $option_item; // [sic] don't reorder dropdown based on current blog
	
	}
	ksort($list);
	ksort($options);

	$list = array_slice( $list, 0, 4 ); // First 4

	$select = "\n\t\t<select>\n\t\t\t" . join( "\n\t\t\t", $options ) . "\n\t\t</select>";

	echo "<ul id=\"dashmenu\">\n\t" . join( "\n\t", $list );

	if ( count($list) < count($options) ) :
?>
	<li id="all-my-blogs-tab" class="wp-no-js-hidden"><a href="#" class="blog-picker-toggle"><?php _e( 'All my blogs' ); ?></a></li>

	</ul>

	<form id="all-my-blogs" action="" method="get" style="display: none">
		<p>
			<?php printf( __( 'Choose a blog: %s' ), $select ); ?>

			<input type="submit" class="button" value="<?php _e( 'Go' ); ?>" />
			<a href="#" class="blog-picker-toggle"><?php _e( 'Cancel' ); ?></a>
		</p>
	</form>
<?php
	endif; // counts
}

function bp_core_replace_home_base_dashboard() {
	global $wpdb, $bp;
	
	if ( strpos( $_SERVER['SCRIPT_NAME'], '/index.php' ) && $wpdb->blogid == get_usermeta( $bp['current_userid'], 'home_base' ) ) {
		add_action( 'admin_head', 'bp_core_start_dash_replacement' );
	}	
}
add_action( 'admin_menu', 'bp_core_replace_home_base_dashboard' );

function bp_core_start_dash_replacement( $dash_contents ) {	
	ob_start();
	add_action('admin_footer', 'bp_core_end_dash_replacement');
}

function bp_core_insert_new_dashboard( $dash_contents ) {
	global $bp;
	
	$filter = preg_split( '/\<div class=\"wrap\"\>[\S\s]*\<div id=\"footer\"\>/', $dash_contents );
	$filter[0] .= '<div class="wrap">';
	$filter[1] .= '</div>';
	
	echo $filter[0];
	
	require_once( ABSPATH . '/wp-content/mu-plugins/bp-core/admin-mods/bp-core-homebase-dashboard.php' );
	
	echo '<div style="clear: both">&nbsp;<br clear="all" /></div></div><div id="footer">';
	echo $filter[1];
}

function bp_core_end_dash_replacement() {
	$dash_contents = ob_get_contents();
	ob_end_clean();
	bp_core_insert_new_dashboard($dash_contents);
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
	$home_base_id = get_usermeta( $uid, 'home_base' );
	$home_base_url = get_blog_option( $home_base_id, 'siteurl' ) . '/';

	return $home_base_url;
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
 * @uses bp_user_fullname() Returns the full name for a user based on user ID.
 * @return false on no match
 * @return str The link text based on passed parameters.
 */
function bp_core_get_userlink( $uid, $no_anchor = false, $just_link = false, $no_you = false ) {
	global $userdata;
	
	$ud = get_userdata($uid);
	
	if ( !$ud )
		return false;

	if ( function_exists('bp_user_fullname') )
		$display_name = bp_user_fullname( $uid, false );
	else
		$display_name = $ud->display_name;
	
	if ( $uid == $userdata->ID && !$no_you )
		$display_name = 'You';

	if ( $no_anchor )
		return $display_name;

	$home_base_id = get_usermeta( $uid, 'home_base' );
	
	if ( !$home_base_id )
		return false;
		
	$home_base_url = get_blog_option( $home_base_id, 'siteurl' ) . '/';
	
	if ( $just_link )
		return $home_base_url;

	return '<a href="' . $home_base_url . '">' . $display_name . '</a>';	
}

/**
 * bp_core_get_userlink_by_email()
 *
 * Returns the email address for the user based on user ID
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
		$date .= __('at') . date( ' g:iA', $time );
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
 * bp_upload_dir()
 *
 * This function will create an upload directory for a new user.
 * This is directly copied from WordPress so that the code can be
 * accessed on user activation *before* 'upload_path' is placed
 * into the options table for the user.
 *
 * FIX: A fix for this would be to add a hook for 'activate_footer'
 * in wp-activate.php
 * 
 * @package BuddyPress Core
 * @param $time str? The time so that upload folders can be created for month and day.
 * @param $blog_id int The ID of the blog (or user in BP) to create the upload dir for.
 * @return array Containing path, url, subdirectory and error message (if applicable).
 */
function bp_upload_dir( $time = NULL, $blog_id ) {
	$siteurl = get_option( 'siteurl' );
	$upload_path = 'wp-content/blogs.dir/' . $blog_id . '/files';
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

	$subdir = '';
	if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
		// Generate the yearly and monthly dirs
		if ( !$time )
			$time = current_time( 'mysql' );
		$y = substr( $time, 0, 4 );
		$m = substr( $time, 5, 2 );
		$subdir = "/$y/$m";
	}

	$dir .= $subdir;
	$url .= $subdir;
	
	// Make sure we have an uploads dir
	if ( ! wp_mkdir_p( $dir ) ) {
		$message = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $dir );
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
	array( 60 * 60 * 24 * 365 , 'year' ),
	array( 60 * 60 * 24 * 30 , 'month' ),
	array( 60 * 60 * 24 * 7, 'week' ),
	array( 60 * 60 * 24 , 'day' ),
	array( 60 * 60 , 'hour' ),
	array( 60 , 'minute' ),
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
 * This function will update that time as a usermeta setting for the user.
 * 
 * @package BuddyPress Core
 * @global $userdata WordPress user data for the current logged in user.
 * @uses update_usermeta() WordPress function to update user metadata in the usermeta table.
 */
function bp_core_record_activity() {
	global $userdata;
	
	// Updated last site activity for this user.
	update_usermeta( $userdata->ID, 'last_activity', time() ); 
}
add_action( 'login_head', 'bp_core_record_activity' );

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

	$bp_author_link = bp_core_get_userlink_by_email( $comment->comment_author_email );
	
	echo ( !$bp_author_link ) ? $author : $bp_author_link; 
}
add_action( 'get_comment_author_link', 'bp_core_replace_comment_author_link', 10, 4 );


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
	
	foreach ( (array)$nav_array as $nav_item ) {
		switch ( $nav_item['id'] ) {
			case $bp['profile']['slug']:
				$new_nav[0] = $nav_item;
			break;
			case $bp['blogs']['slug']:
				$new_nav[1] = $nav_item;
			break;
			case $bp['wire']['slug']:
				$new_nav[2] = $nav_item;
			break;
			case $bp['messages']['slug']:
				$new_nav[3] = $nav_item;
			break;
			case $bp['friends']['slug']:
				$new_nav[4] = $nav_item;
			break;
			case $bp['groups']['slug']:
				$new_nav[5] = $nav_item;
			break;
			case $bp['gallery']['slug']:
				$new_nav[6] = $nav_item;
			break;
			case $bp['account']['slug']:
				$new_nav[7] = $nav_item;
			break;
		}
	}
	
	ksort($new_nav);
	return $new_nav;
}
?>