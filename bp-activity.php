<?php
require_once( 'bp-core.php' );

define ( 'BP_ACTIVITY_IS_INSTALLED', 1 );
define ( 'BP_ACTIVITY_VERSION', '0.1.7' );

/* Use english formatted times - e.g. 6 hours / 8 hours / 1 day / 15 minutes */
define ( 'BP_ACTIVITY_CACHE_LENGTH', '6 hours' );

include_once( 'bp-activity/bp-activity-classes.php' );
//include_once( 'bp-activity/bp-activity-ajax.php' );
//include_once( 'bp-activity/bp-activity-cssjs.php' );
/*include_once( 'bp-messages/bp-activity-admin.php' );*/
include_once( 'bp-activity/bp-activity-templatetags.php' );


/**************************************************************************
 bp_bp_activity_install()
 
 Sets up the component ready for use on a site installation.
 **************************************************************************/

function bp_activity_install( $version ) {
	global $wpdb, $bp;
	
	$sql[] = "CREATE TABLE ". $bp['activity']['table_name_loggedin_user'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT,
		  		item_id int(11) NOT NULL,
		  		component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
		  		date_recorded datetime NOT NULL,
				is_private tinyint(1) NOT NULL,
		    	PRIMARY KEY id (id),
			    KEY item_id (item_id),
			    KEY is_private (is_private),
				KEY component_name (component_name)
		 	   );";

	$sql[] = "CREATE TABLE ". $bp['activity']['table_name_loggedin_user_cached'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT,
		  		content longtext NOT NULL,
				component_name varchar(75) NOT NULL,
				date_cached datetime NOT NULL,
				date_recorded datetime NOT NULL,
				is_private tinyint(1) NOT NULL,
		    	PRIMARY KEY id (id),
				KEY date_cached (date_cached),
				KEY date_recorded (date_recorded),
			    KEY is_private (is_private),
				KEY component_name (component_name)
		 	   );";
	
	$sql[] = "CREATE TABLE ". $bp['activity']['table_name_loggedin_user_friends_cached'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT,
				user_id int(11) NOT NULL,
		  		content longtext NOT NULL,
				component_name varchar(75) NOT NULL,
				date_cached datetime NOT NULL,
				date_recorded datetime NOT NULL,
		    	PRIMARY KEY id (id),
				KEY date_cached (date_cached),
				KEY date_recorded (date_recorded),
				KEY user_id (user_id),
				KEY component_name (component_name)
		 	   );";
	
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
	
	update_usermeta( $bp['loggedin_userid'], 'bp-activity-version', BP_ACTIVITY_VERSION );
}

/**************************************************************************
 bp_activity_setup_globals()
 
 Set up and add all global variables for this component, and add them to 
 the $bp global variable array.
 **************************************************************************/

function bp_activity_setup_globals() {
	global $bp, $wpdb, $current_blog;
	
	$bp['activity'] = array(
		'table_name_loggedin_user' => $wpdb->base_prefix . $bp['loggedin_homebase_id'] . '_activity',
		'table_name_loggedin_user_cached' => $wpdb->base_prefix . $bp['loggedin_homebase_id'] . '_activity_cached',
		
		'table_name_current_user' => $wpdb->base_prefix . $bp['current_homebase_id'] . '_activity',
		'table_name_current_user_cached' => $wpdb->base_prefix . $bp['current_homebase_id'] . '_activity_cached',
		
		'table_name_loggedin_user_friends_cached' => $wpdb->base_prefix . $bp['loggedin_homebase_id'] . '_friends_activity_cached',
		
		'image_base' => get_option('siteurl') . '/wp-content/mu-plugins/bp-activity/images',
		'slug'		 => 'activity'
	);
	
	/* Check to see if the logged in user has their activity table set up. If not, set it up. */
	if ( bp_is_home() ) {
		if ( ( $wpdb->get_var("show tables like '%" . $bp['activity']['table_name_loggedin_user'] . "%'") == false ) || ( get_usermeta( $bp['loggedin_userid'], 'bp-activity-version' ) < BP_ACTIVITY_VERSION )  )
			bp_activity_install(BP_ACTIVITY_VERSION);
	}
}
add_action( 'wp', 'bp_activity_setup_globals', 1 );	
add_action( '_admin_menu', 'bp_activity_setup_globals', 1 );

/**************************************************************************
 bp_activity_add_admin_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function bp_activity_add_admin_menu() {	
	global $wpdb, $bp, $userdata;

	if ( $wpdb->blogid == $bp['current_homebase_id'] ) {
		/* Add the administration tab under the "Site Admin" tab for site administrators */
		//add_submenu_page( 'wpmu-admin.php', __("Activity"), __("Activity"), 1, basename(__FILE__), "bp_activity_settings" );
	}
}
add_action( 'admin_menu', 'bp_activity_add_admin_menu' );

/**************************************************************************
 bp_activity_setup_nav()
 
 Set up front end navigation.
 **************************************************************************/

function bp_activity_setup_nav() {
	global $bp;
	
	/* Add 'Activity' to the main navigation */
	bp_core_add_nav_item( __('Activity'), $bp['activity']['slug'] );
	bp_core_add_nav_default( $bp['activity']['slug'], 'bp_activity_screen_my_activity', 'just-me' );
		
	$activity_link = $bp['loggedin_domain'] . $bp['activity']['slug'] . '/';
	
	/* Add the subnav items to the activity nav item */
	bp_core_add_subnav_item( $bp['activity']['slug'], 'just-me', __('Just Me'), $activity_link, 'bp_activity_screen_my_activity' );
	bp_core_add_subnav_item( $bp['activity']['slug'], 'my-friends', __('My Friends'), $activity_link, 'bp_activity_screen_friends_activity' );
	
	if ( $bp['current_component'] == $bp['activity']['slug'] ) {
		if ( bp_is_home() ) {
			$bp['bp_options_title'] = __('My Activity');
		} else {
			$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = $bp['current_fullname']; 
		}
	}
}
add_action( 'wp', 'bp_activity_setup_nav', 2 );

/***** Screens **********/

function bp_activity_screen_my_activity() {
	bp_catch_uri( 'activity/just-me' );	
}

function bp_activity_screen_friends_activity() {
	global $bp;
	
	BP_Activity_Activity::get_activity_for_friends( $bp['loggedin_userid'] );
	bp_catch_uri( 'activity/my-friends' );	
}

/***** Actions **********/

function bp_activity_record( $item_id, $component_name, $component_action, $is_private, $dual_record = false, $secondary_user_homebase_id = false ) {
	global $bp, $wpdb;

	$recorded_time = time();
	
	$activity = new BP_Activity_Activity;
	$activity->item_id = $item_id;
	$activity->component_name = $component_name;
	$activity->component_action = $component_action;
	$activity->date_recorded = $recorded_time;
	$activity->is_private = $is_private;
 
	$loggedin_user_save = $activity->save();
	
	/* Save an activity entry for both logged in and secondary user. For example for a new friend connection
	   you would want to show "X and Y are now friends" on both users activity stream */
	if ( $dual_record && $secondary_user_homebase_id ) {
		$table_name = $wpdb->base_prefix . $secondary_user_homebase_id . '_activity';
		$table_name_cached = $wpdb->base_prefix . $secondary_user_homebase_id . '_activity_cached';
		
		$activity = new BP_Activity_Activity( null, true, $table_name, $table_name_cached );
		$activity->item_id = $item_id;
		$activity->component_name = $component_name;
		$activity->component_action = $component_action;
		$activity->date_recorded = $recorded_time;
		$activity->is_private = $is_private;

		$secondary_user_save = $activity->save();
	}
	
	if ( $secondary_user_save && $loggedin_user_save )
		return true;
	
	return false;
}

function bp_activity_delete( $item_id, $component_name ) {
	return BP_Activity_Activity::delete( $item_id, $component_name );
}

function bp_activity_order_by_date( $a, $b ) {
	return strcasecmp( $b['date_recorded'], $a['date_recorded'] );	
}


?>