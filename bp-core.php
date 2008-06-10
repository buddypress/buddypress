<?php

require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-thirdlevel.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-settingstab.php' );
require_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-pagination.php' );


if ( !get_site_option('bp_disable_blog_tab') ) {
	include_once(ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-blogtab.php');
}

if ( isset($_POST['submit']) && $_POST['save_admin_settings'] ) {
	save_admin_settings();
}

function start_buffer() {
	ob_start();
	add_action( 'dashmenu', 'stop_buffer' );
} 
add_action( 'admin_menu', 'start_buffer' );

function stop_buffer() {
	$contents = ob_get_contents();
	ob_end_clean();
	buddypress_blog_switcher( $contents );
}

function buddypress_blog_switcher( $contents ) {
	global $current_user, $blog_id; // current blog
	
	// This code is duplicated from the MU core so it can
	// be modified for BuddyPress.
	
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

function add_settings_tab() {
	add_submenu_page( 'wpmu-admin.php', "BuddyPress", "BuddyPress", 1, basename(__FILE__), "core_admin_settings" );
}
add_action( 'admin_menu', 'add_settings_tab' );


function core_admin_settings() {
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

function save_admin_settings() {
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


/* Are we viewing the dashboard? */
if ( strpos( $_SERVER['SCRIPT_NAME'],'/index.php') ) {
	add_action( 'admin_head', 'start_dash' );
}

function start_dash($dash_contents) {	
	ob_start();
	add_action('admin_footer', 'end_dash');
}

function replace_dash($dash_contents) {
	$filter = preg_split( '/\<div class=\"wrap\"\>[\S\s]*\<div id=\"footer\"\>/', $dash_contents );
	$filter[0] .= '<div class="wrap">';
	$filter[1] .= '</div>';
	
	echo $filter[0];
	echo render_dash();
	echo '<div style="clear: both">&nbsp;<br clear="all" /></div></div><div id="footer">';
	echo $filter[1];
}

function end_dash() {
	$dash_contents = ob_get_contents();
	ob_end_clean();
	replace_dash($dash_contents);
}

function render_dash() {
	$dash .= '
		
		<h2>' . __("My Activity Feed") . '</h2>
		<p>' . __("This is where your personal activity feed will go.") . '</p>
		<p>&nbsp;</p><p>&nbsp;</p>
	';
	
	if ( is_site_admin() ) {	
		$dash .= '
			
			<h4>Admin Options</h4>
			<ul>
				<li><a href="wpmu-blogs.php">' . __("Manage Site Members") . '</a></li>
				<li><a href="wpmu-options.php">' . __("Manage Site Options") . '</a></li>
		';
		
	}
	return $dash;	
}

function bp_core_get_userid( $username ) {
	global $wpdb;
	
	$sql = "SELECT ID FROM " . $wpdb->base_prefix . "users
			WHERE user_login = '" . $username . "'";

	$user_id = $wpdb->get_var($sql);
	
	return $user_id;
}

function bp_core_get_username( $uid ) {
	global $userdata;
	
	if ( $uid == $userdata->ID )
		return 'You';
	
	$ud = get_userdata($uid);
	return $ud->user_login;	
}

function bp_core_get_blogdetails( $domain ) {
	global $wpdb;
	
	$sql = $wpdb->prepare("SELECT * FROM $wpdb->site WHERE domain = %s", $domain);
	
	if ( !$blog = $wpdb->get_row($sql) )
		return false;
	
	return $blog;
}

function bp_core_get_userlink( $uid ) {
	global $userdata;
	
	$ud = get_userdata($uid);
	$display_name = $ud->display_name;
	
	if ( $uid == $userdata->ID )
		$display_name = 'You';

	return '<a href="http://' . $ud->source_domain . '">' . $display_name . '</a>';
	
}

function bp_core_clean( $dirty ) {
	if ( get_magic_quotes_gpc() ) {
		$clean = mysql_real_escape_string( stripslashes( $dirty ) );
	} else {
		$clean = mysql_real_escape_string( $dirty );
	}
	
	return $clean;
}

function bp_core_truncate( $text, $numb ) {
	$text = html_entity_decode( $text, ENT_QUOTES );
	
	if ( strlen($text) > $numb ) {
		$text = substr( $text, 0, $numb );
		$text = substr( $text, 0, strrpos( $text, " " ) );
		$etc  = " ..."; 
		$text = $text . $etc;
	}
	
	$text = htmlentities( $text, ENT_QUOTES ); 
	
	return $text;
}

function bp_core_validate( $num ) {	
	if( !is_numeric($num) ) {
		return false;
	}
	
	return true;
}

function bp_format_time( $time, $just_date = false ) {
	$date = date( "F j, Y ", $time );
	
	if ( !$just_date ) {
		$date .= __('at') . date( ' g:iA', $time );
	}
	
	return $date;
}

function bp_endkey( $array ) {
	end( $array );
	return key( $array );
}

function bp_get_homeurl() {
	return get_blogaddress_by_id( 0 );
}

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


// get the IDs of user blogs in a comma-separated list for use in SQL statements
function bp_get_blog_ids_of_user( $id, $all = false ) {
	$blogs = get_blogs_of_user( $id, $all );
	$blog_ids = "";
	
	if ( $blogs && count($blogs) > 0 ){
		foreach( $blogs as $blog ) {
			$blog_ids .= $blog->blog_id.",";
		}
	}
	$blog_ids = trim( $blog_ids, "," );
	return $blog_ids;
}

// return a tick for a checkbox for a true boolean value
function bp_boolean_ticked($bool) {
	if ( $bool ) {
		return " checked=\"checked\"";
	}
	return "";
}

// return a tick for a checkbox for a particular value
function bp_value_ticked( $var, $value ) {
	if ( $var == $value ) {
		return " checked=\"checked\"";
	}
	return "";
}

// return true for a boolean value from a checkbox
function bp_boolean( $value = 0 ) {
	if ( $value != "" ) {
		return 1;
	} else {
		return 0;
	}
}

// return an integer
function bp_int( $var, $nullToOne=false ) {
	if ( @$var == "" ) {
		if ( $nullToOne ) {
			return 1;
		} else {
			return 0;
		}
	} else {
		return (int)$var;
	}
}


// show a friendly date
function bp_friendly_date($timestamp) {
	// set the timestamp to now if it hasn't been given
	if ( strlen($timestamp) == 0 )
		$timestamp = time();
	
	// create the date string
	if ( date( "m", $timestamp ) == date("m") && date( "d", $timestamp ) == date("d") - 1 && date( "Y", $timestamp ) == date("Y") ) {
		return "yesterday at " . date( "g:i a", $timestamp );
	} else if ( date( "m", $timestamp ) == date("m") && date( "d", $timestamp ) == date("d") && date( "Y", $timestamp ) == date("Y") ) {
		return "at " . date( "g:i a", $timestamp );
	} else if ( date( "m", $timestamp) == date("m") && date( "d", $timestamp ) > date("d") - 5 && date( "Y", $timestamp ) == date("Y") ) {
		return "on " . date( "l", $timestamp ) . " at " . date( "g:i a", $timestamp );
	} else if ( date( "Y", $timestamp) == date("Y") ) {
		return "on " . date( "F jS", $timestamp );
	} else {
		return "on " . date( "F jS Y", $timestamp );
	}
}

// search users
function bp_search_users( $q, $start = 0, $num = 10 ) {
	if ( trim($q) != "" ) {
		global $wpdb;
		global $current_user;
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS id, user_login, display_name, user_nicename
		 		FROM " . $wpdb->base_prefix . "users
				WHERE (user_nicename like '%" . $wpdb->escape($q) . "%'
				OR user_email like '%" . $wpdb->escape($q) . "%'
				OR display_name like '%" . $wpdb->escape($q) . "%')
				AND (id <> " . $current_user->ID . " and id > 1)
				LIMIT " . $wpdb->escape($start) . ", " . $wpdb->escape($num) . ";";

		if ( !$users = $wpdb->get_results($sql) ) {
			return false;
		}
		
		$rows = $wpdb->get_var( "SELECT found_rows() AS found_rows" );
		
		if ( is_array($users) && count($users) > 0 ) {
			for ( $i = 0; $i < count($users); $i++ ) {
				$user          = $users[$i];
				$user->siteurl = $user->user_url;
				$user->blogs   = "";
				$user->blogs   = get_blogs_of_user($user->id);
				$user->rows    = $rows;
			}
			return $users;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

// return a ' if the text ends in an "s", or "'s" otherwise
function bp_end_with_s( $string ) {
	if ( substr( strtolower($string), - 1 ) == "s" ) {
		return $string . "'";
	} else {
		return $string . "'s";
	}
}

// pluralise a string
function bp_plural( $num, $ifone = "", $ifmore = "s" ) {
	if ( bp_int($num) != 1 ) {
		return $ifmore;
	} else {
		return $ifone;
	}
}

function bp_is_serialized( $data ) {
   if ( trim($data) == "" ) {
      return false;
   }

   if ( preg_match( "/^(i|s|a|o|d)(.*);/si", $data ) ) {
      return true;
   }

   return false;
}

function bp_upload_dir( $time = NULL, $blog_id ) {
	// copied from wordpress, need to be able to create a users
	// upload dir on activation, before 'upload_path' is
	// placed into options table.
	// Fix for this would be adding a hook for 'activate_footer'
	// in wp-activate.php

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


?>