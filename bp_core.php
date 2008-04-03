<?php

/**************************************************
 * 
 *  Plugin Name: BuddyPress Admin Configuration
 *  Version: 0.1
 *  Type: Core
 *	Description: Configures BuddyPress core admin tabs  
 *	             based on level of access.
 *
 **************************************************/

ini_set("memory_limit","12M");

function setup_tabs() 
{
	global $menu, $submenu;

	/***
	 * REMOVE tabs we don't want.
	 */
	
	/** Remove Presentation Tab **/		
	reset($menu); $page = key($menu);
   	
	if(!is_site_admin()) {
		while ((__('Presentation') != $menu[$page][0]) && next($menu))
	           $page = key($menu);
	   	if (__('Presentation') == $menu[$page][0]) unset($menu[$page]);
	   		reset($menu); $page = key($menu);
	
		/** Remove Plugins Tab **/
	   	while ((__('Plugins') != $menu[$page][0]) && next($menu))
	           $page = key($menu);
	   	if (__('Plugins') == $menu[$page][0]) unset($menu[$page]);
	   		reset($menu); $page = key($menu);
	
		/** Remove Users Tab **/
	   	while ((__('Users') != $menu[$page][0]) && next($menu))
	           $page = key($menu);
	   	if (__('Users') == $menu[$page][0]) unset($menu[$page]);
	   		reset($menu); $page = key($menu);

		/** Remove Options Tab **/
	   	while ((__('Options') != $menu[$page][0]) && next($menu))
	           $page = key($menu);
	   	if (__('Options') == $menu[$page][0]) unset($menu[$page]);
	   		reset($menu); $page = key($menu);
	}

	/** Remove Manage Tab **/
   	while ((__('Manage') != $menu[$page][0]) && next($menu))
           $page = key($menu);
   	if (__('Manage') == $menu[$page][0]) unset($menu[$page]);
   		reset($menu); $page = key($menu);

	/** Remove Comments Tab **/
   	while ((__('Comments') != $menu[$page][0]) && next($menu))
           $page = key($menu);
   	if (__('Comments') == $menu[$page][0]) unset($menu[$page]);
   		reset($menu); $page = key($menu);

	/** Remove Write Tab **/
   	while ((__('Write') != $menu[$page][0]) && next($menu))
           $page = key($menu);
   	if (__('Write') == $menu[$page][0]) unset($menu[$page]);
   		reset($menu); $page = key($menu);


	/** Remove Pages Sub Tabs **/
	unset($submenu["post-new.php"][10]);
	unset($submenu["edit.php"][10]);
	reset($submenu);

	/***
	 * RENAME tabs we want to appear differently.
	 */	
	
	/** Build the Blog Tab and sub menus */
	add_menu_page('Blog', 'Blog', 1, 'post-new.php');	
	add_submenu_page('post-new.php', 'Manage Posts', 'Manage Posts', 1, 'edit.php');
	add_submenu_page('post-new.php', 'Manage Categories', 'Manage Categories', 1, 'categories.php');
	add_submenu_page('post-new.php', 'Manage Comments', 'Manage Comments', 1, 'edit-comments.php');	
	add_submenu_page('post-new.php', 'Manage Uploads', 'Manage Uploads', 1, 'upload.php');		
	add_submenu_page('post-new.php', 'Import', 'Import', 1, 'import.php');	
	add_submenu_page('post-new.php', 'Export', 'Export', 1, 'export.php');
			
}
add_action('admin_menu', 'setup_tabs');

function alter_tab_positions()
{
	global $parent_file;
	
	/** BLOG tab **/
	if(strpos($_SERVER['SCRIPT_NAME'],'/import.php')) $parent_file = 'post-new.php';
	if(strpos($_SERVER['SCRIPT_NAME'],'/export.php')) $parent_file = 'post-new.php';
	if(strpos($_SERVER['SCRIPT_NAME'],'/upload.php')) $parent_file = 'post-new.php';
	if(strpos($_SERVER['SCRIPT_NAME'],'/edit-comments.php')) $parent_file = 'post-new.php';
	if(strpos($_SERVER['SCRIPT_NAME'],'/categories.php')) $parent_file = 'post-new.php';
	if(strpos($_SERVER['SCRIPT_NAME'],'/edit.php')) $parent_file = 'post-new.php';
}
add_action('admin_head', 'alter_tab_positions');

function reorder_tabs()
{
	global $menu;
	
	foreach($menu as $key => $value)
	{
		for($j=0; $j<count($menu[$key]); $j++)
		{
			if($menu[$key][$j] == "Blogroll" || $menu[$key][$j] == "Links")
			{
				$blogroll_key = $key;
			}
			else if($menu[$key][$j] == "Options")
			{
				$options_key = $key;
			}
		}
	}
	
	$new_key = bp_endkey($menu) + 1;
	$menu[$new_key] = $menu[$blogroll_key];
	$menu[$new_key+1] = $menu[$options_key];
	unset($menu[$blogroll_key]);
	unset($menu[$options_key]);
}
add_action('admin_head', 'reorder_tabs');

function add_settings_tab()
{
	if(is_site_admin()) {
		add_menu_page("BP Settings", "BP Settings", 1, basename(__FILE__), "core_admin_settings");
		add_submenu_page(basename(__FILE__), "BuddyPress", "BuddyPress", 1, basename(__FILE__), "core_admin_settings");
	}
}
add_action('admin_menu', 'add_settings_tab');

function core_admin_settings()
{

?>	
	<div class="wrap">
		
		<h2><?php _e("Core Settings") ?></h2>
		
		<p>BuddyPress core settings will be administered from this area.</p>
		
	</div>
<?php
}


function limit_access()
{
	global $parent_file;
	
	if(!is_site_admin()) {
		if(strpos($_SERVER['SCRIPT_NAME'],'/themes.php')) header("Location: index.php");
		if(strpos($_SERVER['SCRIPT_NAME'],'/plugins.php')) header("Location: index.php");
		if(strpos($_SERVER['SCRIPT_NAME'],'/users.php')) header("Location: index.php");
		if(strpos($_SERVER['SCRIPT_NAME'],'/profile.php')) header("Location: index.php");
		if(strpos($_SERVER['SCRIPT_NAME'],'/page-new.php')) header("Location: index.php");
		if(strpos($_SERVER['SCRIPT_NAME'],'/edit-pages.php')) header("Location: index.php");
		if(strpos($_SERVER['SCRIPT_NAME'],'/options')) header("Location: index.php");
		if(strpos($_SERVER['SCRIPT_NAME'],'/admin.php?page=bp_core.php')) header("Location: index.php");
	}
}
add_action('admin_menu', 'limit_access');




/**************************************************
 * 
 *  Plugin Name: BuddyPress Dashboard
 *  Version: 0.1
 *  Type: Core
 *	Description: Replaces the normal Wordpress  
 *	             dashboard with a BuddyPress 
 *               Dashboard.
 *
 **************************************************/

/* Are we viewing the dashboard? */
if(strpos($_SERVER['SCRIPT_NAME'],'/index.php'))
{
	add_action('admin_head', 'start_dash');
}

function start_dash($dash_contents)
{	
	ob_start();
	add_action('admin_footer', 'end_dash');
}

function replace_dash($dash_contents)
{
	$filter = preg_split('/\<div class=\"wrap\"\>[\S\s]*\<div id=\"footer\"\>/',$dash_contents);
	$filter[0] .= '<div class="wrap">';
	$filter[1] .= '</div>';
	
	echo $filter[0];
	echo render_dash();
	echo '<div style="clear: both">&nbsp;<br clear="all" /></div></div><div id="footer">';
	echo $filter[1];
}

function end_dash()
{
	$dash_contents = ob_get_contents();
	ob_end_clean();
	replace_dash($dash_contents);
}

function render_dash() {
	$dash .= '
		
		<h2>' . __("BuddyPress Dashboard") . '</h2>
		<p>' . __("Welcome to your personal dashboard.") . '</p>
		
	';
	
	if(is_site_admin()) {	
		
		$dash .= '
			
			<h4>Admin Options</h4>
			<ul>
				<li><a href="wpmu-blogs.php">' . __("Manage Site Members") . '</a></li>
				<li><a href="wpmu-options.php">' . __("Manage Site Options") . '</a></li>
		';
		
	}
	
	return $dash;
	
}



/**************************************************
 * 
 *  Plugin Name: Core BuddyPress Functions
 *  Version: 0.1
 *  Type: Core
 *	Description: Functions used by multiple
 *				 BuddyPress plugins.
 *
 **************************************************/

function bp_core_get_userid($username)
{
	global $wpdb, $wpmuBaseTablePrefix;

	$sql = "SELECT ID FROM " . $wpmuBaseTablePrefix . "users
			WHERE user_login = '" . $username . "'";

	if(!$user_id = $wpdb->get_var($sql)) {
		return 0;
	}
	
	return $user_id;	
}

function bp_core_clean($dirty)
{
	if (get_magic_quotes_gpc()) 
	{
		$clean = mysql_real_escape_string(stripslashes($dirty));
	}
	else
	{
		$clean = mysql_real_escape_string($dirty);
	}
	
	return $clean;
}

function bp_core_get_username($user_id)
{
	global $wpdb, $wpmuBaseTablePrefix;

	$sql = "SELECT user_login FROM " . $wpmuBaseTablePrefix . "users
			WHERE ID = " . $user_id;

	if(!$username = $wpdb->get_var($sql)) {
		return false;
	}
	
	return $username;	
}

function bp_core_truncate($text, $numb)
{
	$text = html_entity_decode($text, ENT_QUOTES);
	
	if (strlen($text) > $numb) 
	{
		$text = substr($text, 0, $numb);
		$text = substr($text, 0, strrpos($text, " "));
		$etc = " ..."; 
		$text = $text . $etc;
	}
	
	$text = htmlentities($text, ENT_QUOTES); 
	
	return $text;
}

function bp_core_validate($num)
{	
	if(!is_numeric($num))
	{
		return false;
	}
	
	return true;
}

function bp_format_time($time)
{
	return date("F j, Y - g:iA", $time);
}

function bp_endkey($array)
{
	end($array);
	return key($array);
}

function bp_get_homeurl()
{
	$url = get_option('home');
	$url_temp = explode('.', $url);
	
	if($url_temp[0] == "www") {
		return $url;
	}
	else {
		if(VHOST == "yes") {
			return "http://" . $url_temp[1] . "." . $url_temp[2];
		}
		else {
			return "http://www." . $url_temp[1] . "." . $url_temp[2];
		}
	}
}

// get the IDs of user blogs in a comma-separated list for use in SQL statements
function bp_get_blog_ids_of_user($id, $all = false)
{
	$blogs = get_blogs_of_user($id, $all);
	$blog_ids = "";
	if ($blogs && count($blogs) > 0)
	{
		foreach($blogs as $blog)
		{
			$blog_ids .= $blog->blog_id.",";
		}
	}
	$blog_ids = trim($blog_ids, ",");
	return $blog_ids;
}

// return a tick for a checkbox for a true boolean value
function bp_boolean_ticked($bool)
{
	if ($bool)
	{
		return " checked=\"checked\"";
	}
	return "";
}

// return a tick for a checkbox for a particular value
function bp_value_ticked($var, $value)
{
	if ($var == $value)
	{
		return " checked=\"checked\"";
	}
	return "";
}

// return true for a boolean value from a checkbox
function bp_boolean($value = 0)
{
	if ($value != "")
	{
		return 1;
	} else {
		return 0;
	}
}

// return an integer
function bp_int($var,$nullToOne=false)
{
	if (@$var == "")
	{
		if ($nullToOne)
		{
			return 1;
		} else {
			return 0;
		}
	} else {
		return (int)$var;
	}
}

// get the start number for pagination
function bp_get_page_start($p, $num)
{
	$p = bp_int($p);
	$num = bp_int($num);
	if ($p == "")
	{
		return 0;
	} else {
		return ($p*$num)-$num;
	}
}

// get the page number from the $_GET["p"] variable
function bp_get_page()
{
	if (isset($_GET["p"]) ? $page = (int)$_GET["p"] : $page = 1);
	return $page;
}

// generate page links
function bp_generate_pages_links($totalRows,$maxPerPage=25,$linktext="",$var,$attributes="")
{
    // loop all the pages in the result set
    for ($i = 1; $i <= ceil($totalRows/$maxPerPage); $i++)
    {
		// if the current page is different to this link, create the querystring
		$page = bp_int(@$var,true);
		if ($i != $page)
		{
			if ($linktext == ""){
				$link = "?p=" . $i;
			} else {
				$link = str_replace("%%", $i, $linktext);
			}
			$links["link"][] = $link;
			$links["text"][] = $i;
			$links["attributes"][] = str_replace("%%", $i, $attributes);
			// otherwise make the link empty
		} else {
			$links["link"][] = "";
			$links["text"][] = $i;
			$links["attributes"][] = str_replace("%%", $i, $attributes);
		}
    }
    // return the links
    return $links;
}

// generate page link list
function bp_paginate($links,$currentPage=1, $firstItem="", $listclass="")
{
	$return = "";
	// check the parameter is an array with more than 1 items in
	if (is_array($links) && count($links["text"]) > 1)
	{
		// get the total number of links
		$totalPages = count($links["text"]);
		// set showstart and showend to false
		$showStart = false;
		$showEnd = false;
		// if the total number of pages is greater than 10
		if ($totalPages > 10)
		{
			// if the current page is less than 5 from the start
			if ($currentPage <= 5)
			{
				// set the minimum and maximum pages to show
				$minimum = 0;
				$maximum = 9;
				$showEnd = true;
			}
			// if the current page is less than 5 from the end
			if ($currentPage >= ($totalPages-5))
			{
				// set the minumum and maximum pages to show
				$minimum = $totalPages-9;
				$maximum = $totalPages;
				$showStart = true;
			}
			// if the current page is somewhere in the middle
			if ($currentPage > 5 && $currentPage < ($totalPages-5))
			{
				$showEnd = true;
				$showStart = true;
				$minimum = $currentPage-4;
				$maximum = $currentPage+4;
			}
		} else {
			$minimum = 0;
			$maximum = $totalPages;
		}
		// print the start of the list
		$return .= "\n\n<ul class=\"pagelinks";
		if ($listclass!=""){ $return .= " ".$listclass; }
		$return .= "\">\n";
		// print the first item, it if is set
		if ($firstItem != "")
		{
			$return .= "<li>".$firstItem."</li>\n";
		}
		// print the page text
		$return .= "<li>Pages:</li>\n";
		// if set, show the start
		if ($showStart){
			$return .= "<li><a href=\"" . str_replace("&", "&amp;", $links["link"][0]) . "\">" . $links["text"][0] . "...</a></li>\n";
		}
		// loop the links
		for ($i = $minimum; $i < $maximum; $i++)
		{
			if ($i == ($currentPage-1))
			{
				$url = "<li class=\"current\">" . $links["text"][$i] . "</li>\n";
			} else {
				if ($links["attributes"][$i] != "")
				{
					$attributes = " ".$links["attributes"][$i];
				} else {
					$attributes = "";
				}
				$url = "<li><a href=\"" . str_replace("&", "&amp;", $links["link"][$i]) . "\"".$attributes.">" . $links["text"][$i] . "</a></li>\n";
			}
			$return .= $url;
		}
		// if set, show the end
		if ($showEnd){
			$return .= "<li><a href=\"" . str_replace("&", "&amp;", $links["link"][$totalPages-1]) . "\">..." . $links["text"][$totalPages-1] . "</a></li>\n";
		}
		$return .= "</ul>\n\n";
	}
return $return;
}

// show a friendly date
function bp_friendly_date($timestamp)
{
	// set the timestamp to now if it hasn't been given
	if (strlen($timestamp) == 0){ $timestamp = time(); }
	
	// create the date string
	if (date("m", $timestamp)==date("m") && date("d", $timestamp)==date("d")-1 && date("Y", $timestamp)==date("Y")){
		return "yesterday at ".date("g:i a", $timestamp);
	} else if (date("m", $timestamp)==date("m") && date("d", $timestamp)==date("d") && date("Y", $timestamp)==date("Y")){
		return "at ".date("g:i a", $timestamp);
	} else if (date("m", $timestamp)==date("m") && date("d", $timestamp)>date("d")-5 && date("Y", $timestamp)==date("Y")){
		return "on ".date("l", $timestamp)." at ".date("g:i a", $timestamp);
	} else if (date("Y", $timestamp)==date("Y")){
		return "on ".date("F jS", $timestamp);
	} else {
		return "on ".date("F jS Y", $timestamp);
	}
}

// search users
function bp_search_users($q, $start = 0, $num = 10)
{
	if (trim($q) != "")
	{
		global $wpdb;
		global $wpmuBaseTablePrefix;
		global $current_user;
		$sql = "select SQL_CALC_FOUND_ROWS id, user_login, display_name, user_nicename from ".$wpmuBaseTablePrefix."users
				where (user_nicename like '%".$wpdb->escape($q)."%'
				or user_email like '%".$wpdb->escape($q)."%'
				or display_name like '%".$wpdb->escape($q)."%')
				and (id <> ".$current_user->ID." and id > 1)
				limit ".$wpdb->escape($start).", ".$wpdb->escape($num).";";
				//print $sql;
		$users = $wpdb->get_results($sql);
		$rows = $wpdb->get_var("SELECT found_rows() AS found_rows");
		if (is_array($users) && count($users) >0)
		{
			for ($i = 0; $i < count($users); $i++)
			{
				$user = $users[$i];
				$user->siteurl = $user->user_url;
				$user->blogs = "";
				$user->blogs = get_blogs_of_user($user->id);
				$user->rows = $rows;
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
function bp_end_with_s($string)
{
	if (substr(strtolower($string), -1) == "s")
	{
		return $string."'";
	} else {
		return $string."'s";
	}
}

// pluralise a string
function bp_plural($num, $ifone="", $ifmore="s")
{
	if (bp_int($num) <> 1)
	{
		return $ifmore;
	} else {
		return $ifone;
	}
}

?>