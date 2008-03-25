<?php

/**************************************************************************
 xprofile_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function xprofile_install()
{
	global $wpdb, $table_name;

	$sql = "CREATE TABLE ". $table_name ." (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  initiator_user_id mediumint(9) NOT NULL,
		  friend_user_id mediumint(9) NOT NULL,
		  is_confirmed bool DEFAULT 0,
		  date_created int(11) NOT NULL,
		  UNIQUE KEY id (id)
		 );";

	//require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	//dbDelta($sql);
		
}

/**************************************************************************
 xprofile_add_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function xprofile_add_menu() 
{
	global $wpdb, $table_name, $wpmuBaseTablePrefix, $bp_xprofile, $groups;
	$table_name = $wpmuBaseTablePrefix . "bp_xprofile";

	include_once('bp_xprofile/bp_xprofile.classes.php');
	include_once('bp_xprofile/bp_xprofile.admin.php');
	include_once('bp_xprofile/bp_xprofile.signup.php');
	include_once('bp_xprofile/bp_xprofile.cssjs.php');

	add_menu_page("Profile", "Profile", 1, basename(__FILE__), "xprofile_picture");
	add_submenu_page(basename(__FILE__), "Picture", "Picture", 1, basename(__FILE__), "xprofile_picture");		
	
	$groups = BP_XProfile_Group::get_all();

	for($i=0; $i<count($groups); $i++) {
		if($groups[$i]->fields) {
			add_submenu_page(basename(__FILE__), $groups[$i]->name, $groups[$i]->name, 1, "xprofile_" . $groups[$i]->name, "xprofile_edit");		
		}
	}
	
	/* Add the administration tab under the "Site Admin" tab for site administrators */
	add_submenu_page('bp_core.php', "Profiles", "Profiles", 1, "xprofile_settings", "xprofile_admin");

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) xprofile_install();
}
add_action('admin_menu','xprofile_add_menu');


/**************************************************************************
 xprofile_setup()
 
 Setups up the plugin's global variables, sets the profile picture upload
 directory as well as adding actions for CSS and JS rendering.
 **************************************************************************/

function xprofile_setup()
{
	global $image_base, $profile_picture_path, $profile_picture_base;
	
	$image_base = get_option('siteurl') . '/wp-content/mu-plugins/bp_xprofile/images';
	$profile_picture_path = trim(get_option('upload_path')) . '/profilepics';
	$profile_picture_base = get_option('site_url') . 'files/profilepics';
	
	// Check to see if the users profile picture folder exists. If not, make it.
	if(!is_dir(ABSPATH . $profile_picture_path)) {
		wp_mkdir_p(ABSPATH . $profile_picture_path);
	}
			
	/* Setup CSS and JS */
	add_action("admin_print_scripts", "xprofile_add_css");
	add_action("admin_print_scripts", "xprofile_add_js");
}
add_action('admin_menu','xprofile_setup');


/**************************************************************************
 xprofile_invoke_authordata()
 
 Set up access to authordata so that profile data can be pulled without
 being logged in.
 **************************************************************************/

function xprofile_invoke_authordata()
{
	query_posts('showposts=1');
	if (have_posts()) : while (have_posts()) : the_post(); endwhile; endif;
	global $is_author, $userdata, $authordata;
}
add_action('wp_head', 'xprofile_invoke_authordata');


?>