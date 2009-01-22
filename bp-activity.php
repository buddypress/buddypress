<?php
require_once( 'bp-core.php' );

define ( 'BP_ACTIVITY_IS_INSTALLED', 1 );
define ( 'BP_ACTIVITY_VERSION', '1.0b1.1' );

/* How long before activity items in streams are re-cached? */
define ( 'BP_ACTIVITY_CACHE_LENGTH', '6 HOURS' );

include_once( 'bp-activity/bp-activity-classes.php' );
include_once( 'bp-activity/bp-activity-templatetags.php' );
include_once( 'bp-activity/bp-activity-widgets.php' );
include_once( 'bp-activity/bp-activity-cssjs.php' );
include_once( 'bp-activity/bp-activity-filters.php' );


/**************************************************************************
 bp_bp_activity_install()
 
 Sets up the component ready for use on a site installation.
 **************************************************************************/

function bp_activity_user_install() {
	global $wpdb, $bp;
	
	if ( !$bp['current_userid'] )
		return false;
	
	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	$sql[] = "CREATE TABLE ". $bp['activity']['table_name_current_user'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		item_id int(11) NOT NULL,
				secondary_item_id int(11),
				user_id int(11) NOT NULL,
		  		component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
		  		date_recorded datetime NOT NULL,
				is_private tinyint(1) NOT NULL DEFAULT 0,
				no_sitewide_cache tinyint(1) NOT NULL DEFAULT 0,
			    KEY item_id (item_id),
				KEY user_id (user_id),
			    KEY is_private (is_private),
				KEY component_name (component_name)
		 	   ) {$charset_collate};";

	$sql[] = "CREATE TABLE ". $bp['activity']['table_name_current_user_cached'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				item_id int(11) NOT NULL,
				secondary_item_id int(11),
		  		content longtext NOT NULL,
				primary_link varchar(150) NOT NULL,
				component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
				date_cached datetime NOT NULL,
				date_recorded datetime NOT NULL,
				is_private tinyint(1) NOT NULL DEFAULT 0,
				KEY date_cached (date_cached),
				KEY date_recorded (date_recorded),
			    KEY is_private (is_private),
				KEY item_id (item_id),
				KEY component_name (component_name)
		 	   ) {$charset_collate};";
	
	$sql[] = "CREATE TABLE ". $bp['activity']['table_name_current_user_friends_cached'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id int(11) NOT NULL,
		  		content longtext NOT NULL,
				primary_link varchar(150) NOT NULL,
				component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
				date_cached datetime NOT NULL,
				date_recorded datetime NOT NULL,
				KEY date_cached (date_cached),
				KEY date_recorded (date_recorded),
				KEY user_id (user_id),
				KEY component_name (component_name)
		 	   ) {$charset_collate};";

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);

	// dbDelta won't change character sets, so we need to do this seperately.
	// This will only be in here pre v1.0
	$wpdb->query( $wpdb->prepare( "ALTER TABLE " . $bp['activity']['table_name_current_user'] . " DEFAULT CHARACTER SET %s", $wpdb->charset ) );
	$wpdb->query( $wpdb->prepare( "ALTER TABLE " . $bp['activity']['table_name_current_user_cached'] . " DEFAULT CHARACTER SET %s", $wpdb->charset ) );
	$wpdb->query( $wpdb->prepare( "ALTER TABLE " . $bp['activity']['table_name_current_user_friends_cached'] . " DEFAULT CHARACTER SET %s", $wpdb->charset ) );
	
	update_usermeta( $bp['current_userid'], 'bp-activity-version', BP_ACTIVITY_VERSION );
}

function bp_activity_sitewide_install() {
	global $wpdb, $bp;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	$sql[] = "CREATE TABLE ". $bp['activity']['table_name_sitewide'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id int(11) NOT NULL,
				item_id int(11) NOT NULL,
				secondary_item_id int(11),
		  		content longtext NOT NULL,
				primary_link varchar(150) NOT NULL,
				component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
				date_cached datetime NOT NULL,
				date_recorded datetime NOT NULL,
				KEY date_cached (date_cached),
				KEY date_recorded (date_recorded),
				KEY user_id (user_id),
				KEY item_id (item_id),
				KEY component_name (component_name)
		 	   ) {$charset_collate};";
	
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
	
	// dbDelta won't change character sets, so we need to do this seperately.
	// This will only be in here pre v1.0
	$wpdb->query( $wpdb->prepare( "ALTER TABLE " . $bp['activity']['table_name_sitewide'] . " DEFAULT CHARACTER SET %s", $wpdb->charset ) );

	update_site_option( 'bp-activity-version', BP_ACTIVITY_VERSION );
}

/**************************************************************************
 bp_activity_setup_globals()
 
 Set up and add all global variables for this component, and add them to 
 the $bp global variable array.
 **************************************************************************/

function bp_activity_setup_globals() {
	global $bp, $wpdb, $current_blog;

	$bp['activity'] = array(
		'table_name_loggedin_user' => $wpdb->base_prefix . 'user_' . $bp['loggedin_userid'] . '_activity',
		'table_name_loggedin_user_cached' => $wpdb->base_prefix . 'user_' . $bp['loggedin_userid'] . '_activity_cached',
		
		'table_name_current_user' => $wpdb->base_prefix . 'user_' . $bp['current_userid'] . '_activity',
		'table_name_current_user_cached' => $wpdb->base_prefix . 'user_' . $bp['current_userid'] . '_activity_cached',
		
		'table_name_loggedin_user_friends_cached' => $wpdb->base_prefix . 'user_' . $bp['loggedin_userid'] . '_friends_activity_cached',
		'table_name_current_user_friends_cached' => $wpdb->base_prefix . 'user_' . $bp['current_userid'] . '_friends_activity_cached',
		
		'table_name_sitewide' => $wpdb->base_prefix . 'bp_activity_sitewide',
		
		'image_base' => site_url( MUPLUGINDIR . '/bp-activity/images' ),
		'slug'		 => 'activity'
	);

	if ( $bp['current_userid'] ) {
		/* Check to see if the current user has their activity table set up. If not, set them up. */
		if ( !$wpdb->get_var("show tables like '%" . $bp['activity']['table_name_current_user'] . "%'") || get_usermeta( $bp['current_userid'], 'bp-activity-version' ) < BP_ACTIVITY_VERSION  )
			bp_activity_user_install();
	}
	
	if ( is_site_admin() && $current_blog->blog_id == 1 ) {
		/* Check to see if the site wide activity table is set up. */
		if ( !$wpdb->get_var("show tables like '%" . $bp['activity']['table_name_sitewide'] . "%'") || get_site_option( 'bp-activity-version' ) < BP_ACTIVITY_VERSION  )
			bp_activity_sitewide_install();
	}
}
add_action( 'wp', 'bp_activity_setup_globals', 1 );
add_action( 'admin_menu', 'bp_activity_setup_globals', 1 );


/**************************************************************************
 bp_activity_setup_nav()
 
 Set up front end navigation.
 **************************************************************************/

function bp_activity_setup_nav() {
	global $bp;
	
	/* Add 'Activity' to the main navigation */
	bp_core_add_nav_item( __('Activity', 'buddypress'), $bp['activity']['slug'] );
	bp_core_add_nav_default( $bp['activity']['slug'], 'bp_activity_screen_my_activity', 'just-me' );
		
	$activity_link = $bp['loggedin_domain'] . $bp['activity']['slug'] . '/';
	
	/* Add the subnav items to the activity nav item */
	bp_core_add_subnav_item( $bp['activity']['slug'], 'just-me', __('Just Me', 'buddypress'), $activity_link, 'bp_activity_screen_my_activity' );
	bp_core_add_subnav_item( $bp['activity']['slug'], 'my-friends', __('My Friends', 'buddypress'), $activity_link, 'bp_activity_screen_friends_activity', 'activity-my-friends', bp_is_home() );
	
	if ( $bp['current_component'] == $bp['activity']['slug'] ) {
		if ( bp_is_home() ) {
			$bp['bp_options_title'] = __('My Activity', 'buddypress');
		} else {
			$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = $bp['current_fullname']; 
		}
	}
}
add_action( 'wp', 'bp_activity_setup_nav', 2 );
add_action( 'admin_menu', 'bp_activity_setup_nav', 2 );

/***** Screens **********/

function bp_activity_screen_my_activity() {
	do_action( 'bp_activity_screen_my_activity' );
	bp_core_load_template( 'activity/just-me' );	
}

function bp_activity_screen_friends_activity() {
	do_action( 'bp_activity_screen_friends_activity' );
	bp_core_load_template( 'activity/my-friends' );	
}

/***** Actions **********/

function bp_activity_record( $item_id, $component_name, $component_action, $is_private, $secondary_item_id = false, $user_id = false, $secondary_user_id = false ) {
	global $bp, $wpdb;
	
	if ( !$user_id )
		$user_id = $bp['loggedin_userid'];

	$recorded_time = time();
	
	$activity = new BP_Activity_Activity;
	$activity->item_id = $item_id;
	$activity->secondary_item_id = $secondary_item_id;
	$activity->user_id = $user_id;
	$activity->component_name = $component_name;
	$activity->component_action = $component_action;
	$activity->date_recorded = $recorded_time;
	$activity->is_private = $is_private;

	$loggedin_user_save = $activity->save();
	
	/* Save an activity entry for both logged in and secondary user. For example for a new friend connection
	   you would want to show "X and Y are now friends" on both users activity stream */
	if ( $secondary_user_id  ) {
		$activity = new BP_Activity_Activity;
		$activity->item_id = $item_id;
		$activity->user_id = $secondary_user_id;
		$activity->component_name = $component_name;
		$activity->component_action = $component_action;
		$activity->date_recorded = $recorded_time;
		$activity->is_private = $is_private;

		// We don't want to record this on the sitewide stream, otherwise we will get duplicates.
		$activity->no_sitewide_cache = true;

		$secondary_user_save = $activity->save();
	}
	
	do_action( 'bp_activity_record', $item_id, $component_name, $component_action, $is_private, $secondary_item_id, $user_id, $secondary_user_id );
	
	return true;
}

function bp_activity_action_sitewide_feed() {
	global $bp;

	if ( $bp['current_component'] != $bp['activity']['slug'] || $bp['current_action'] != 'feed' || $bp['current_userid'] )
		return false;

	include_once( 'bp-activity/feeds/bp-activity-sitewide-feed.php' );
	die;
}
add_action( 'wp', 'bp_activity_action_sitewide_feed', 3 );

function bp_activity_action_personal_feed() {
	global $bp;	

	if ( $bp['current_component'] != $bp['activity']['slug'] || !$bp['current_userid'] || $bp['current_action'] != 'feed' )
		return false;

	include_once( 'bp-activity/feeds/bp-activity-personal-feed.php' );
	die;
}
add_action( 'wp', 'bp_activity_action_personal_feed', 3 );

function bp_activity_action_friends_feed() {
	global $bp;

	if ( $bp['current_component'] != $bp['activity']['slug'] || !$bp['current_userid'] || $bp['current_action'] != 'my-friends' || $bp['action_variables'][0] != 'feed' )
		return false;
	
	include_once( 'bp-activity/feeds/bp-activity-friends-feed.php' );
	die;	
}
add_action( 'wp', 'bp_activity_action_friends_feed', 3 );

function bp_activity_get_last_updated() {
	return BP_Activity_Activity::get_last_updated();
}

function bp_activity_get_sitewide_activity( $max_items ) {
	return BP_Activity_Activity::get_sitewide_activity( $max_items );
}

function bp_activity_delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id ) {
	if ( !BP_Activity_Activity::delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id ) )
		return false;
		
	do_action( 'bp_activity_delete', $item_id, $component_name, $component_action, $user_id, $secondary_item_id );
	
	return true;
}

function bp_activity_order_by_date( $a, $b ) {
	return strcasecmp( $b['date_recorded'], $a['date_recorded'] );	
}

function bp_activity_remove_data( $user_id ) {
	// Clear the user's activity from the sitewide stream and clear their activity tables
	BP_Activity_Activity::delete_activity_for_user( $user_id );
	
	// Remove the deleted users activity tables
	BP_Activity_Activity::kill_tables_for_user( $user_id );
	
	do_action( 'bp_activity_remove_data', $user_id );
}
add_action( 'wpmu_delete_user', 'bp_activity_remove_data', 1 );
add_action( 'delete_user', 'bp_activity_remove_data', 1 );


?>