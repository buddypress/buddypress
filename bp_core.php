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

?>