<?php

define ( 'BP_ACTIVITY_DB_VERSION', '1800' );

/* Define the slug for the component */
if ( !defined( 'BP_ACTIVITY_SLUG' ) )
	define ( 'BP_ACTIVITY_SLUG', 'activity' );

require ( BP_PLUGIN_DIR . '/bp-activity/bp-activity-classes.php' );
require ( BP_PLUGIN_DIR . '/bp-activity/bp-activity-templatetags.php' );
require ( BP_PLUGIN_DIR . '/bp-activity/bp-activity-widgets.php' );
require ( BP_PLUGIN_DIR . '/bp-activity/bp-activity-filters.php' );

/* Include deprecated functions if settings allow */
if ( !defined( 'BP_IGNORE_DEPRECATED' ) )
	require ( BP_PLUGIN_DIR . '/bp-activity/deprecated/bp-activity-deprecated.php' );	
	
function bp_activity_install() {
	global $wpdb, $bp;
	
	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	$sql[] = "CREATE TABLE {$bp->activity->table_name} (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				user_id bigint(20) NOT NULL,
				component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
				content longtext NOT NULL,
				primary_link varchar(150) NOT NULL,
				item_id varchar(75) NOT NULL,
				secondary_item_id varchar(75) NOT NULL,
				date_recorded datetime NOT NULL,
				hide_sitewide bool DEFAULT 0,
				KEY date_recorded (date_recorded),
				KEY user_id (user_id),
				KEY item_id (item_id),
				KEY component_name (component_name)
		 	   ) {$charset_collate};";

	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	dbDelta($sql);
	
	/* Drop the old sitewide and user activity tables */
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->base_prefix}bp_activity_user_activity" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->base_prefix}bp_activity_sitewide" );
	
	/* Rename the old user activity cached table */
	//$wpdb->query( "RENAME TABLE {$wpdb->base_prefix}bp_activity_user_activity_cached TO {$bp->activity->table_name}" );

	update_site_option( 'bp-activity-db-version', BP_ACTIVITY_DB_VERSION );
}

function bp_activity_setup_globals() {
	global $bp, $wpdb, $current_blog;

	/* Internal identifier */
	$bp->activity->id = 'activity';
	
	$bp->activity->table_name = $wpdb->base_prefix . 'bp_activity_user_activity_cached';
	$bp->activity->slug = BP_ACTIVITY_SLUG;
		
	/* Register this in the active components array */
	$bp->active_components[$bp->activity->slug] = $bp->activity->id;
}
add_action( 'plugins_loaded', 'bp_activity_setup_globals', 5 );
add_action( 'admin_menu', 'bp_activity_setup_globals', 2 );

function bp_activity_check_installed() {	
	global $wpdb, $bp;
	
	if ( get_site_option('bp-activity-db-version') < BP_ACTIVITY_DB_VERSION )
		bp_activity_install();
}
add_action( 'admin_menu', 'bp_activity_check_installed' );

function bp_activity_setup_root_component() {
	/* Register 'activity' as a root component (for RSS feed use) */
	bp_core_add_root_component( BP_ACTIVITY_SLUG );
}
add_action( 'plugins_loaded', 'bp_activity_setup_root_component', 2 );

function bp_activity_setup_nav() {
	global $bp;

	/* Add 'Activity' to the main navigation */
	bp_core_new_nav_item( array( 'name' => __( 'Activity', 'buddypress' ), 'slug' => $bp->activity->slug, 'position' => 10, 'screen_function' => 'bp_activity_screen_my_activity', 'default_subnav_slug' => 'just-me', 'item_css_id' => $bp->activity->id ) );

	$activity_link = $bp->loggedin_user->domain . $bp->activity->slug . '/';

	/* Add the subnav items to the activity nav item */
	bp_core_new_subnav_item( array( 'name' => __( 'Just Me', 'buddypress' ), 'slug' => 'just-me', 'parent_url' => $activity_link, 'parent_slug' => $bp->activity->slug, 'screen_function' => 'bp_activity_screen_my_activity', 'position' => 10 ) );
	bp_core_new_subnav_item( array( 'name' => __( 'My Friends', 'buddypress' ), 'slug' => 'my-friends', 'parent_url' => $activity_link, 'parent_slug' => $bp->activity->slug, 'screen_function' => 'bp_activity_screen_friends_activity', 'position' => 20, 'item_css_id' => 'activity-my-friends' ) );

	if ( $bp->current_component == $bp->activity->slug ) {
		if ( bp_is_home() ) {
			$bp->bp_options_title = __( 'My Activity', 'buddypress' );
		} else {
			$bp->bp_options_avatar = bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb' ) );
			$bp->bp_options_title = $bp->displayed_user->fullname; 
		}
	}

	do_action( 'bp_activity_setup_nav' );
}
add_action( 'plugins_loaded', 'bp_activity_setup_nav' );
add_action( 'admin_menu', 'bp_activity_setup_nav' );


/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

function bp_activity_screen_my_activity() {
	do_action( 'bp_activity_screen_my_activity' );
	bp_core_load_template( apply_filters( 'bp_activity_template_my_activity', 'activity/just-me' ) );	
}

function bp_activity_screen_friends_activity() {
	do_action( 'bp_activity_screen_friends_activity' );
	bp_core_load_template( apply_filters( 'bp_activity_template_friends_activity', 'activity/my-friends' ) );	
}


/********************************************************************************
 * Action Functions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 */

function bp_activity_action_delete_activity() {
	global $bp;

	if ( $bp->current_component != $bp->activity->slug || $bp->current_action != 'delete' )
		return false;

	if ( empty( $bp->action_variables[0] ) || !is_numeric( $bp->action_variables[0] ) )
		return false;

	/* Check the nonce */
	check_admin_referer( 'bp_activity_delete_link' );
	
	$activity_id = $bp->action_variables[0];
	
	/* Check access */
	if ( !is_site_admin() ) {
		$activity = new BP_Activity_Activity( $activity_id );
		
		if ( $activity->user_id != $bp->loggedin_user->id )
			return false;
	}
	
	/* Now delete the activity item */
	if ( bp_activity_delete_by_activity_id( $activity_id ) )
		bp_core_add_message( __( 'Activity deleted', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was an error when deleting that activity', 'buddypress' ), 'error' );
		
	do_action( 'bp_activity_action_delete_activity', $activity_id );
	
	bp_core_redirect( $_SERVER['HTTP_REFERER'] );
}
add_action( 'wp', 'bp_activity_action_delete_activity', 3 );


function bp_activity_action_sitewide_feed() {
	global $bp, $wp_query;

	if ( $bp->current_component != $bp->activity->slug || $bp->current_action != 'feed' || $bp->displayed_user->id )
		return false;

	$wp_query->is_404 = false;
	status_header( 200 );

	include_once( 'bp-activity/feeds/bp-activity-sitewide-feed.php' );
	die;
}
add_action( 'wp', 'bp_activity_action_sitewide_feed', 3 );

function bp_activity_action_personal_feed() {
	global $bp, $wp_query;	

	if ( $bp->current_component != $bp->activity->slug || !$bp->displayed_user->id || $bp->current_action != 'feed' )
		return false;
	
	$wp_query->is_404 = false;
	status_header( 200 );

	include_once( 'bp-activity/feeds/bp-activity-personal-feed.php' );
	die;
}
add_action( 'wp', 'bp_activity_action_personal_feed', 3 );

function bp_activity_action_friends_feed() {
	global $bp, $wp_query;

	if ( $bp->current_component != $bp->activity->slug || !$bp->displayed_user->id || $bp->current_action != 'my-friends' || $bp->action_variables[0] != 'feed' )
		return false;

	$wp_query->is_404 = false;	
	status_header( 200 );

	include_once( 'bp-activity/feeds/bp-activity-friends-feed.php' );
	die;	
}
add_action( 'wp', 'bp_activity_action_friends_feed', 3 );


/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

function bp_activity_add( $args = '' ) {
	global $bp, $wpdb;
	
	$defaults = array(
		'user_id' => $bp->loggedin_user->id,
		'content' => false,
		'primary_link' => false,
		'component_name' => false,
		'component_action' => false,
		'item_id' => false,
		'secondary_item_id' => false,
		'recorded_time' => time(),
		'hide_sitewide' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	/* Insert the "time-since" placeholder */
	if ( $content )
		$content = bp_activity_add_timesince_placeholder( $content );

	$activity = new BP_Activity_Activity;
	$activity->user_id = $user_id;
	$activity->content = $content;
	$activity->primary_link = $primary_link;
	$activity->component_name = $component_name;
	$activity->component_action = $component_action;
	$activity->item_id = $item_id;
	$activity->secondary_item_id = $secondary_item_id;
	$activity->date_recorded = $recorded_time;
	$activity->hide_sitewide = $hide_sitewide;
	
	if ( !$activity->save() )
		return false;

	do_action( 'bp_activity_add', $args );
	
	return true;
}

/* There are multiple ways to delete activity items, depending on the information you have at the time. */

function bp_activity_delete_by_item_id( $args = '' ) {
	global $bp;

	$defaults = array(
		'item_id' => false,
		'component_name' => false,
		'component_action' => false, // optional
		'user_id' => false, // optional
		'secondary_item_id' => false // optional
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( !BP_Activity_Activity::delete_by_item_id( $item_id, $component_name, $component_action, $user_id, $secondary_item_id ) )
		return false;

	do_action( 'bp_activity_delete_by_item_id', $item_id, $component_name, $component_action, $user_id, $secondary_item_id );

	return true;
}

function bp_activity_delete_by_activity_id( $activity_id ) {
	if ( !BP_Activity_Activity::delete_by_activity_id( $activity_id ) )
		return false;

	do_action( 'bp_activity_delete_by_activity_id', $activity_id );

	return true;	
}

function bp_activity_delete_by_content( $user_id, $content, $component_name, $component_action ) {
	/* Insert the "time-since" placeholder to match the existing content in the DB */
	$content = bp_activity_add_timesince_placeholder( $content );

	if ( !BP_Activity_Activity::delete_by_content( $user_id, $content, $component_name, $component_action ) )
		return false;

	do_action( 'bp_activity_delete_by_content', $user_id, $content, $component_name, $component_action );

	return true;
}

function bp_activity_delete_for_user_by_component( $user_id, $component_name ) {
	if ( !BP_Activity_Activity::delete_for_user_by_component( $user_id, $component_name ) )
		return false;

	do_action( 'bp_activity_delete_for_user_by_component', $user_id, $component_name );
	
	return true;
}

function bp_activity_add_timesince_placeholder( $content ) {
	/* Check a time-since span doesn't already exist */
	if ( false === strpos( $content, '<span class="time-since">' ) ) {		
		if ( !$pos = strpos( $content, '<blockquote' ) ) {
			if ( !$pos = strpos( $content, '<div' ) ) {
				if ( !$pos = strpos( $content, '<ul' ) ) {
					$content .= ' <span class="time-since">%s</span>';
				}
			}
		}
	}
	
	if ( (int) $pos ) {
		$before = substr( $content, 0, (int) $pos );
		$after = substr( $content, (int) $pos, strlen( $content ) );
		
		$content = $before . ' <span class="time-since">%s</span>' . $after;
	}

	return apply_filters( 'bp_activity_add_timesince_placeholder', $content );
}

function bp_activity_set_action( $component_id, $key, $value ) { 
	global $bp; 

	if ( empty( $component_id ) || empty( $key ) || empty( $value ) ) 
		return false; 

	$bp->activity->actions->{$component_id}->{$key} = apply_filters( 'bp_activity_set_action', array( 
		'key' => $key, 
		'value' => $value 
	), $component_id, $key, $value ); 
} 

function bp_activity_get_action( $component_id, $key ) { 
	global $bp; 

	if ( empty( $component_id ) || empty( $key ) ) 
		return false; 

	return apply_filters( 'bp_activity_get_action', $bp->activity->actions->{$component_id}->{$key}, $component_id, $key ); 
} 

function bp_activity_check_exists_by_content( $content ) {
	/* Insert the "time-since" placeholder to match the existing content in the DB */
	$content = bp_activity_add_timesince_placeholder( $content );

	return apply_filters( 'bp_activity_check_exists_by_content', BP_Activity_Activity::check_exists_by_content( $content ) );
}

function bp_activity_get_last_updated() {
	return apply_filters( 'bp_activity_get_last_updated', BP_Activity_Activity::get_last_updated() );
}

function bp_activity_get_sitewide_activity( $max_items = 30, $pag_num = false, $pag_page = false, $filter = false ) {
 	return apply_filters( 'bp_activity_get_sitewide_activity', BP_Activity_Activity::get_sitewide_activity( $max_items, $pag_num, $pag_page, $filter ), $max_items, $pag_num, $pag_page, $filter );
}

function bp_activity_get_user_activity( $user_id, $max_items = 30, $pag_num = false, $pag_page = false, $filter = false ) {
	return apply_filters( 'bp_activity_get_user_activity', BP_Activity_Activity::get_activity_for_user( $user_id, $max_items, $pag_num, $pag_page, $filter ), $user_id, $max_items, $pag_num, $pag_page, $filter );
}

function bp_activity_get_friends_activity( $user_id, $max_items = 30, $max_items_per_friend = false, $pag_num = false, $pag_page = false, $filter = false ) {
	return apply_filters( 'bp_activity_get_friends_activity', BP_Activity_Activity::get_activity_for_friends( $user_id, $max_items, $max_items_per_friend, $pag_num, $pag_page, $filter ), $user_id, $max_items, $max_items_per_friend, $pag_num, $pag_page, $filter );
}

function bp_activity_remove_data( $user_id ) {
	// Clear the user's activity from the sitewide stream and clear their activity tables
	BP_Activity_Activity::delete_for_user( $user_id );
	
	do_action( 'bp_activity_remove_data', $user_id );
}
add_action( 'wpmu_delete_user', 'bp_activity_remove_data' );
add_action( 'delete_user', 'bp_activity_remove_data' );
add_action( 'make_spam_user', 'bp_activity_remove_data' );

/* Ordering function - don't call this directly */
function bp_activity_order_by_date( $a, $b ) {
	return apply_filters( 'bp_activity_order_by_date', strcasecmp( $b['date_recorded'], $a['date_recorded'] ) );	
}

?>