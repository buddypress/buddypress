<?php

define ( 'BP_ACTIVITY_DB_VERSION', '1900' );

/* Define the slug for the component */
if ( !defined( 'BP_ACTIVITY_SLUG' ) )
	define ( 'BP_ACTIVITY_SLUG', 'activity' );

require ( BP_PLUGIN_DIR . '/bp-activity/bp-activity-classes.php' );
require ( BP_PLUGIN_DIR . '/bp-activity/bp-activity-templatetags.php' );
require ( BP_PLUGIN_DIR . '/bp-activity/bp-activity-filters.php' );

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
				mptt_left int(11) NOT NULL,
				mptt_right int(11) NOT NULL,
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

	/* TODO: Rename the old user activity cached table */
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

	do_action( 'bp_activity_setup_globals' );
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
	bp_core_load_template( apply_filters( 'bp_activity_template_my_activity', 'members/single/activity' ) );
}

function bp_activity_screen_friends_activity() {
	global $bp;

	/* Make sure delete links do not show for friends activity items */
	if ( !is_site_admin() )
		$bp->is_item_admin = false;

	do_action( 'bp_activity_screen_friends_activity' );
	bp_core_load_template( apply_filters( 'bp_activity_template_friends_activity', 'activity/my-friends' ) );
}

function bp_activity_screen_single_activity_permalink() {
	global $bp;

	if ( !$bp->displayed_user->id || $bp->current_component != $bp->activity->slug )
		return false;

	if ( empty( $bp->current_action ) || !is_numeric( $bp->current_action ) )
		return false;

	/* Get the activity details */
	$activity = bp_activity_get_specific( array( 'activity_ids' => $bp->current_action ) );

	if ( !$activity = $activity['activities'][0] )
		bp_core_redirect( $bp->root_domain );

	$has_access = true;
	/* Redirect based on the type of activity */
	if ( $activity->component_name == $bp->groups->id ) {
		if ( !function_exists( 'groups_get_group' ) )
			bp_core_redirect( $bp->root_domain );

		if ( $group = groups_get_group( array( 'group_id' => $activity->item_id ) ) ) {
			/* Check to see if the group is not public, if so, check the user has access to see this activity */
			if ( 'public' != $group->status ) {
				if ( !groups_is_user_member( $bp->loggedin_user->id, $group->id ) )
					$has_access = false;
			}
		}
	}

	$has_access = apply_filters( 'bp_activity_permalink_access', $has_access, &$activity );

	if ( !$has_access ) {
		bp_core_add_message( __( 'You do not have access to this activity.', 'buddypress' ), 'error' );
		bp_core_redirect( $bp->loggedin_user->domain );
	}

	bp_core_load_template( apply_filters( 'bp_activity_template_profile_activity_permalink', 'members/single/activity/permalink' ) );
}
/* This screen is not attached to a nav item, so we need to add an action for it. */
add_action( 'wp', 'bp_activity_screen_single_activity_permalink', 3 );

/********************************************************************************
 * Action Functions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 */

function bp_activity_action_permalink_router() {
	global $bp;

	if ( $bp->current_component != $bp->activity->slug || $bp->current_action != 'p' )
		return false;

	if ( empty( $bp->action_variables[0] ) || !is_numeric( $bp->action_variables[0] ) )
		return false;

	/* Get the activity details */
	$activity = bp_activity_get_specific( array( 'activity_ids' => $bp->action_variables[0] ) );

	if ( !$activity = $activity['activities'][0] )
		bp_core_redirect( $bp->root_domain );

	$redirect = false;
	/* Redirect based on the type of activity */
	if ( $activity->component_name == $bp->groups->id ) {
		if ( $activity->user_id )
			$redirect = bp_core_get_userurl( $activity->user_id ) . $bp->activity->slug . '/' . $activity->id;
		else {
			if ( $group = groups_get_group( array( 'group_id' => $activity->item_id ) ) )
				$redirect = bp_get_group_permalink( $group ) . $bp->activity->slug . '/' . $activity->id;
		}
	} else
		$redirect = bp_core_get_userurl( $activity->user_id ) . $bp->activity->slug . '/' . $activity->id;

	if ( !$redirect )
		bp_core_redirect( $bp->root_domain );

	/* Redirect to the actual activity permalink page */
	bp_core_redirect( apply_filters( 'bp_activity_action_permalink_url', $redirect . '/', &$activity ) );
}
add_action( 'wp', 'bp_activity_action_permalink_router', 3 );

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

function bp_activity_get( $args = '' ) {
	$defaults = array(
		'max' => false, // Maximum number of results to return
		'page' => 1, // page 1 without a per_page will result in no pagination.
		'per_page' => false, // results per page
		'sort' => 'DESC', // sort ASC or DESC
		'display_comments' => false, // false for no comments. 'stream' for within stream display, 'threaded' for below each activity item

		'search_terms' => false, // Pass search terms as a string
		'show_hidden' => false, // Show activity items that are hidden site-wide?

		/**
		 * Pass filters as an array -- all filter items can be multiple values comma separated:
		 * array(
		 * 	'user_id' => false, // user_id to filter on
		 *	'object' => false, // object to filter on e.g. groups, profile, status, friends
		 *	'action' => false, // action to filter on e.g. new_wire_post, new_forum_post, profile_updated
		 *	'primary_id' => false, // object ID to filter on e.g. a group_id or forum_id or blog_id etc.
		 *	'secondary_id' => false, // secondary object ID to filter on e.g. a post_id
		 * );
		 */
		'filter' => array()
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	return apply_filters( 'bp_activity_get', BP_Activity_Activity::get( $max, $page, $per_page, $sort, $search_terms, $filter, $display_comments, $show_hidden ), &$r );
}

function bp_activity_get_specific( $args = '' ) {
	$defaults = array(
		'activity_ids' => false, // A single activity_id or array of IDs.
		'max' => false, // Maximum number of results to return
		'page' => 1, // page 1 without a per_page will result in no pagination.
		'per_page' => false, // results per page
		'sort' => 'DESC', // sort ASC or DESC
		'display_comments' => false // true or false to display threaded comments for these specific activity items
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	return apply_filters( 'bp_activity_get_specific', BP_Activity_Activity::get_specific( $activity_ids, $max, $page, $per_page, $sort, $display_comments ) );
}

function bp_activity_add( $args = '' ) {
	global $bp, $wpdb;

	$defaults = array(
		'content' => false, // The content of the activity item
		'primary_link' => false, // The primary URL for this item in RSS feeds
		'component_name' => false, // The name/ID of the component e.g. groups, profile, mycomponent
		'component_action' => false, // The component action e.g. new_wire_post, profile_updated

		'user_id' => $bp->loggedin_user->id, // Optional: The user to record the activity for, can be false if this activity is not for a user.
		'item_id' => false, // Optional: The ID of the specific item being recorded, e.g. a blog_id, or wire_post_id
		'secondary_item_id' => false, // Optional: A second ID used to further filter e.g. a comment_id
		'recorded_time' => time(), // The time that this activity was recorded
		'hide_sitewide' => false // Should this be hidden on the sitewide activity stream?
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	/* Insert the "time-since" placeholder */
	if ( $content )
		$content = bp_activity_add_timesince_placeholder( $content );

	/* Pass certain values so we can update an activity item if it already exists */
	$activity = new BP_Activity_Activity();

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

	/* If this is an activity comment, rebuild the tree */
	if ( 'activity_comment' == $activity->component_action )
		BP_Activity_Activity::rebuild_activity_comment_tree( $activity->item_id );

	do_action( 'bp_activity_add', $r );

	return $activity->id;
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

/**
 * bp_activity_filter_template_paths()
 *
 * Add fallback for the bp-sn-parent theme template locations used in BuddyPress versions
 * older than 1.2.
 *
 * @package BuddyPress Core
 */
function bp_activity_filter_template_paths() {
	if ( 'bp-sn-parent' != basename( TEMPLATEPATH ) && !defined( 'BP_CLASSIC_TEMPLATE_STRUCTURE' ) )
		return false;

	add_filter( 'bp_activity_template_my_activity', create_function( '', 'return "activity/just-me";' ) );
	add_filter( 'bp_activity_template_friends_activity', create_function( '', 'return "activity/my-friends";' ) );
	add_filter( 'bp_activity_template_profile_activity_permalink', create_function( '', 'return "activity/single";' ) );

	/* Activity widget should only be available to older themes since the new default has it all in the template */
	require ( BP_PLUGIN_DIR . '/bp-activity/bp-activity-widgets.php' );
}
add_action( 'widgets_init', 'bp_activity_filter_template_paths' );

function bp_activity_remove_data( $user_id ) {
	// Clear the user's activity from the sitewide stream and clear their activity tables
	BP_Activity_Activity::delete_for_user( $user_id );

	do_action( 'bp_activity_remove_data', $user_id );
}
add_action( 'wpmu_delete_user', 'bp_activity_remove_data' );
add_action( 'delete_user', 'bp_activity_remove_data' );
add_action( 'make_spam_user', 'bp_activity_remove_data' );

?>