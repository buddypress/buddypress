<?php

/* Define the protocol to be used, change to https:// if on secure server. */
define( 'PROTOCOL', 'http://' );

/* Define the current version number for checking if DB tables are up to date. */
define( 'BP_CORE_VERSION', '0.2.3' );

/* Require all needed files */
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-catchuri.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-classes.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-cssjs.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-thirdlevel.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-settingstab.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-avatars.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-templatetags.php' );

/* If disable blog tab option is set, don't combine blog tabs by skipping blogtab file */
if ( !get_site_option('bp_disable_blog_tab') ) {
	include_once(ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-blogtab.php');
}

/* If admin settings have been posted, redirect to correct function to save settings */
if ( isset($_POST['submit']) && $_POST['save_admin_settings'] && is_site_admin() ) {
	bp_core_save_admin_settings();
}

/**
 * bp_core_setup_globals()
 *
 * Sets up default global BuddyPress configuration settings and stores
 * them in a $bp variable.
 *
 * @package BuddyPress Core Core
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
}
add_action( 'wp', 'bp_core_setup_globals', 1 );
add_action( 'admin_menu', 'bp_core_setup_globals' );

/**
 * bp_core_setup_nav()
 *
 * Adds "Blog" to the navigation arrays for the current and logged in user.
 * $bp['bp_nav'] represents the main component navigation 
 * $bp['bp_users_nav'] represents the sub navigation when viewing a users
 * profile other than that of the current logged in user.
 * 
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_core_is_blog() Checks to see current page is a blog page eg: /blog/ or /archives/2008/09/01/
 * @uses bp_is_home() Checks to see if the current user being viewed is the logged in user
 */
function bp_core_setup_nav() {
	global $bp;
	
	/* Add "Blog" to the main component navigation */
	$bp['bp_nav'][1] = array(
		'id'	=> 'blog',
		'name'  => 'Blog', 
		'link'  => $bp['loggedin_domain'] . 'blog'
	);
	
	/* Add "Blog" to the sub nav for a current user */
	$bp['bp_users_nav'][1] = array(
		'id'	=> 'blog',
		'name'  => 'Blog', 
		'link'  => $bp['current_domain'] . 'blog'
	);
	
	/* This will be a check to see if profile or blog is set as the default component. */
	if ( $bp['current_component'] == '' ) {
		if ( function_exists('xprofile_setup_nav') ) {
			$bp['current_component'] = 'profile';
		} else {
			$bp['current_component'] = 'blog';
		}
	/* If we are on a blog specific page, always set the current component to Blog */
	} else if ( bp_core_is_blog() ) {
		$bp['current_component'] = 'blog';
	}
	
	/* Set up the component options navigation for Blog */
	if ( $bp['current_component'] == 'blog' ) {
		if ( bp_is_home() ) {
			if ( function_exists('xprofile_setup_nav') ) {
				$bp['bp_options_title'] = __('My Blog'); 
				$bp['bp_options_nav']['blog'] = array(
					''   => array(
						'name' => __('Public'),
						'link' => $bp['loggedin_domain'] . 'blog/' ),
					'admin'	   => array( 
						'name' => __('Blog Admin'),
						'link' => $bp['loggedin_domain'] . 'wp-admin/' )
				);
			}
		} else {
			/* If we are not viewing the logged in user, set up the current users avatar and name */
			$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = bp_user_fullname( $bp['current_userid'], false ); 
		}
	}
}
add_action( 'wp', 'bp_core_setup_nav', 2 );

/**
 * bp_core_get_loggedin_domain()
 *
 * Returns the domain for the user that is currently logged in.
 * eg: http://andy.domain.com/ or http://domain.com/andy/
 * 
 * @package BuddyPress Core
 * @global $current_user WordPress global variable containing current logged in user information
 * @uses bp_core_is_blog() Checks to see current page is a blog page eg: /blog/ or /archives/2008/09/01/
 * @uses bp_is_home() Checks to see if the current user being viewed is the logged in user
 */
function bp_core_get_loggedin_domain() {
	global $current_user;
	
	if ( VHOST == 'yes' ) {
		$loggedin_domain = PROTOCOL . get_usermeta( $current_user->ID, 'source_domain' ) . '/';
	} else {
		$loggedin_domain = PROTOCOL . get_usermeta( $current_user->ID, 'source_domain' ) . '/' . get_usermeta( $current_user->ID, 'user_login' ) . '/';
	}

	return $loggedin_domain;
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
 * @uses bp_core_get_primary_username() Returns the username based on http:// [username] .site.com OR http://site.com/ [username]
 * @uses bp_core_get_userid() Returns the user id for the username given.
 * @return $current_userid The user id for the user that is currently being viewed.
 */
function bp_core_get_current_userid() {
	$siteuser = bp_core_get_primary_username();
	$current_userid = bp_core_get_userid($siteuser);
	
	return $current_userid;
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
	bp_core__blog_switcher( $contents );
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

	$primary_blog = get_usermeta( $current_user->ID, 'primary_blog' );
	
	foreach ( $blogs = get_blogs_of_user( $current_user->ID ) as $blog ) {
		if ( !$blog->blogname )
			continue;

		// Use siteurl for this in case of mapping
		$parsed = parse_url( $blog->siteurl );
		$domain = $parsed['host'];
		
		if ( $blog->userblog_id == $primary_blog ) {
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

/**
 * bp_core_add_settings_tab()
 *
 * Adds a new submenu page under the Admin Settings tab for BuddyPress specific settings.
 * 
 * @package BuddyPress Core
 * @param $add_submenu_pag str The contents of the buffer.
 * @uses add_submenu_page() WordPress function for adding submenu pages to existing admin area menus.
 */
function bp_core_add_settings_tab() {
	add_submenu_page( 'wpmu-admin.php', "BuddyPress", "BuddyPress", 1, basename(__FILE__), "bp_core_admin_settings" );
}
add_action( 'admin_menu', 'bp_core_add_settings_tab' );

/**
 * bp_core_admin_settings()
 *
 * Renders the admin area settings for BuddyPress
 * 
 * @package BuddyPress Core
 * @uses get_site_option() Fetches sitemeta based on setting name passed
 */
function bp_core_admin_settings() {
	if ( get_site_option('bp_disable_blog_tab') ) {
		$blog_tab_checked = ' checked="checked"';
	}
	
	if ( get_site_option('bp_disable_design_tab') ) {
		$design_tab_checked = ' checked="checked"';		
	}
	
?>	
	<div class="wrap">
		
		<h2><?php _e("BuddyPress Settings") ?></h2>
		
		<form action="" method="post">
			<table class="form-table">
			<tbody>
			<tr valign="top">
			<th scope="row" valign="top">Tabs</th>
			<td>
				<input type="checkbox" value="1" name="disable_blog_tab"<?php echo $blog_tab_checked; ?> />
				<label for="disable_blog_tab"> Disable merging of 'Write', 'Manage' and 'Comments' into one 'Blog' tab.</label>
				<br />
				<input type="checkbox" value="1" name="disable_design_tab"<?php echo $design_tab_checked; ?> />
				<label for="disable_design_tab"> Disable 'Design' tab for all members except site administrators.</label>
			</td>
			</tr>
			</tbody>
			</table>

			<p class="submit">
				  <input name="submit" value="Save Changes" type="submit" />
			</p>
		
			<input type="hidden" name="save_admin_settings" value="1" />
		</form>
		
	</div>
<?php
}

/**
 * bp_core_save_admin_settings()
 *
 * Saves the administration settings once the admin settings form has been posted.
 * Checks first to see if the current user is a site administrator.
 * 
 * @package BuddyPress Core
 * @param $contents str The contents of the buffer.
 * @uses is_site_admin() WordPress function to check if current user has site admin privileges.
 * @uses add_site_option() WordPress function to add or update sitemeta based on passed meta name.
 */
function bp_core_save_admin_settings() {
	if ( !is_site_admin() )
		return false;

	if ( !isset($_POST['disable_blog_tab']) ) {
		$_POST['disable_blog_tab'] = 0;
	}
	else if ( !isset($_POST['disable_design_tab']) )
	{
		$_POST['disable_design_tab'] = 0;
	}

	// temp code for now, until full settings page is added
	add_site_option( 'bp_disable_blog_tab', $_POST['disable_blog_tab'] );
	add_site_option( 'bp_disable_design_tab', $_POST['disable_design_tab'] );
}

// Commenting out dashboard replacement for now, until more is implemented.

// /* Are we viewing the dashboard? */
// if ( strpos( $_SERVER['SCRIPT_NAME'],'/index.php') ) {
// 	add_action( 'admin_head', 'start_dash' );
// }

// function start_dash($dash_contents) {	
// 	ob_start();
// 	add_action('admin_footer', 'end_dash');
// }
// 
// function replace_dash($dash_contents) {
// 	$filter = preg_split( '/\<div class=\"wrap\"\>[\S\s]*\<div id=\"footer\"\>/', $dash_contents );
// 	$filter[0] .= '<div class="wrap">';
// 	$filter[1] .= '</div>';
// 	
// 	echo $filter[0];
// 	echo render_dash();
// 	echo '<div style="clear: both">&nbsp;<br clear="all" /></div></div><div id="footer">';
// 	echo $filter[1];
// }
// 
// function end_dash() {
// 	$dash_contents = ob_get_contents();
// 	ob_end_clean();
// 	replace_dash($dash_contents);
// }
// 
// function render_dash() {
// 	$dash .= '
// 		
// 		<h2>' . __("My Activity Feed") . '</h2>
// 		<p>' . __("This is where your personal activity feed will go.") . '</p>
// 		<p>&nbsp;</p><p>&nbsp;</p>
// 	';
// 	
// 	if ( is_site_admin() ) {	
// 		$dash .= '
// 			
// 			<h4>Admin Options</h4>
// 			<ul>
// 				<li><a href="wpmu-blogs.php">' . __("Manage Site Members") . '</a></li>
// 				<li><a href="wpmu-options.php">' . __("Manage Site Options") . '</a></li>
// 		';
// 		
// 	}
// 	return $dash;	
// }

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
	global $userdata;
	
	$ud = get_userdata($uid);
	
	if ( VHOST == 'no' )
		$ud->path = $ud->user_login;
	else
		$ud->path = null;
		
	$url = PROTOCOL . $ud->source_domain . '/' . $ud->path;
	
	if ( !$ud )
		return false;
	
	return $url;
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
		$display_name = bp_user_fullname($uid, false);
	else
		$display_name = $ud->display_name;
	
	if ( $uid == $userdata->ID && !$no_you )
		$display_name = 'You';

	if ( $no_anchor )
		return $display_name;
		
	if ( VHOST == 'no' )
		$ud->path = $ud->user_login;
	else
		$ud->path = null;
	
	if ( $just_link )
		return PROTOCOL . $ud->source_domain . '/' . $ud->path;

	return '<a href="' . PROTOCOL . $ud->source_domain . '/' . $ud->path . '">' . $display_name . '</a>';	
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
 * bp_core_is_blog()
 *
 * Checks to see if the current page is part of the blog.
 * Some example blog pages:
 *   - Single post, Archives, Categories, Tags, Pages, Blog Home, Search Results ...
 * 
 * @package BuddyPress Core
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $cached_page_id The page id of the current page if cached
 * @uses is_tag() WordPress function to check if on tags page
 * @uses is_category() WordPress function to check if on category page
 * @uses is_day() WordPress function to check if on day page
 * @uses is_month() WordPress function to check if on month page
 * @uses is_year() WordPress function to check if on year page
 * @uses is_paged() WordPress function to check if on page
 * @uses is_single() WordPress function to check if on single post page
 * @return bool true if 
 * @return bool false on no match.
 */
function bp_core_is_blog() {
	global $bp, $cached_page_id;
	
	$blog_page_id = bp_get_page_id('Blog');
	if ( is_tag() || is_category() || is_day() || is_month() || is_year() || is_paged() || is_single() )
		return true;
	if ( isset($cached_page_id) && ( $blog_page_id == $cached_page_id ) )
		return true;
	if ( is_page('Blog') )
		return true;
	if ( $bp['current_component'] == 'blog' )
		return true;
		
	return false;
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


?>