<?php

include_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-thirdlevel.php' );
include_once( ABSPATH . 'wp-content/mu-plugins/bp-core/bp-core-settingstab.php' );


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
	
	return $text;
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

// get the start number for pagination
function bp_get_page_start( $p, $num ) {
	$p   = bp_int($p);
	$num = bp_int($num);
	
	if ( $p == "" ) {
		return 0;
	} else {
		return ( $p * $num ) - $num;
	}
}

// get the page number from the $_GET["p"] variable
function bp_get_page() {
	if ( isset( $_GET["p"] ) ) {
		return (int) $_GET["p"]; 		
	}
	else {
		return 1;
	}		
}

// generate page links
function bp_generate_pages_links( $totalRows, $maxPerPage = 25, $linktext = "", $var, $attributes = "" ) {
    // loop all the pages in the result set
    for ( $i = 1; $i <= ceil( $totalRows / $maxPerPage ); $i++ ) {
		// if the current page is different to this link, create the querystring
		$page = bp_int( @$var, true );
		if ($i != $page)
		{
			if ( $linktext == "" ) {
				$link = "?p=" . $i;
			} else {
				$link = str_replace( "%%", $i, $linktext );
			}
			$links["link"][] = $link;
			$links["text"][] = $i;
			$links["attributes"][] = str_replace( "%%", $i, $attributes );
		// otherwise make the link empty
		} else {
			$links["link"][] = "";
			$links["text"][] = $i;
			$links["attributes"][] = str_replace( "%%", $i, $attributes );
		}
    }
    // return the links
    return $links;
}

// generate page link list
function bp_paginate( $links, $currentPage = 1, $firstItem = "", $listclass = "" ) {
	$return = "";
	// check the parameter is an array with more than 1 items in
	if ( is_array($links) && count($links["text"]) > 1 ) {
		// get the total number of links
		$totalPages = count($links["text"]);
		
		// set showstart and showend to false
		$showStart = false;
		$showEnd   = false;
		
		// if the total number of pages is greater than 10
		if ( $totalPages > 10 ) {
			
			// if the current page is less than 5 from the start
			if ( $currentPage <= 5 ) {
				// set the minimum and maximum pages to show
				$minimum = 0;
				$maximum = 9;
				$showEnd = true;
			}
			
			// if the current page is less than 5 from the end
			if ( $currentPage >= ( $totalPages - 5 ) ) {
				// set the minumum and maximum pages to show
				$minimum   = $totalPages - 9;
				$maximum   = $totalPages;
				$showStart = true;
			}
			
			// if the current page is somewhere in the middle
			if ( $currentPage > 5 && $currentPage < ( $totalPages - 5 ) )
			{
				$showEnd   = true;
				$showStart = true;
				$minimum   = $currentPage - 4;
				$maximum   = $currentPage + 4;
			}
			
		} else {
			$minimum = 0;
			$maximum = $totalPages;
		}
		
		// print the start of the list
		$return .= "\n\n<ul class=\"pagelinks";
		
		if ( $listclass != "" )
			$return .= " ".$listclass;
			
		$return .= "\">\n";
		
		// print the first item, it if is set
		if ( $firstItem != "" ) {
			$return .= "<li>" . $firstItem . "</li>\n";
		}
		
		// print the page text
		$return .= "<li>Pages:</li>\n";
		
		// if set, show the start
		if ( $showStart )
			$return .= "<li><a href=\"" . str_replace( "&", "&amp;", $links["link"][0] ) . "\">" . $links["text"][0] . "...</a></li>\n";

		// loop the links
		for ( $i = $minimum; $i < $maximum; $i++ ) {
			if ( $i == ( $currentPage - 1 ) ) {
				$url = "<li class=\"current\">" . $links["text"][$i] . "</li>\n";
			} else {
				if ($links["attributes"][$i] != "")
					$attributes = " " . $links["attributes"][$i];
				else
					$attributes = "";
					
				$url = "<li><a href=\"" . str_replace( "&", "&amp;", $links["link"][$i] ) . "\"" . $attributes . ">" . $links["text"][$i] . "</a></li>\n";
			}
			$return .= $url;
		}
		// if set, show the end
		if ( $showEnd ) {
			$return .= "<li><a href=\"" . str_replace( "&", "&amp;", $links["link"][$totalPages - 1]) . "\">..." . $links["text"][$totalPages-1] . "</a></li>\n";
		}
		$return .= "</ul>\n\n";
	}
	
	return $return;
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

?>