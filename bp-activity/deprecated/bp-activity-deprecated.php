<?php
/***
 * Deprecated Activity Stream Functionality
 *
 * This file contains functions that are deprecated.
 * You should not under any circumstance use these functions as they are
 * either no longer valid, or have been replaced with something much more awesome.
 *
 * If you are using functions in this file you should slap the back of your head
 * and then use the functions or solutions that have replaced them.
 * Most functions contain a note telling you what you should be doing or using instead.
 *
 * Of course, things will still work if you use these functions but you will
 * be the laughing stock of the BuddyPress community. We will all point and laugh at
 * you. You'll also be making things harder for yourself in the long run,
 * and you will miss out on lovely performance and functionality improvements.
 *
 * If you've checked you are not using any deprecated functions and finished your little
 * dance, you can add the following line to your wp-config.php file to prevent any of
 * these old functions from being loaded:
 *
 * define( 'BP_IGNORE_DEPRECATED', true );
 */

function bp_activity_deprecated_globals() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;

	$bp->activity->image_base = BP_PLUGIN_URL . '/bp-activity/images';
}
add_action( 'plugins_loaded', 'bp_activity_deprecated_globals', 5 );
add_action( 'admin_menu', 'bp_activity_deprecated_globals', 2 );

/* DEPRECATED - use bp_activity_add() */
function bp_activity_record( $item_id, $component_name, $component_action, $is_private, $secondary_item_id = false, $user_id = false, $secondary_user_id = false, $recorded_time = false ) {
	global $bp, $wpdb;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	if ( !$recorded_time )
		$recorded_time = time();

	$args = compact( 'user_id', 'content', 'component_name', 'component_action', 'item_id', 'secondary_item_id', 'recorded_time' );
	bp_activity_add( $args );

	if ( $secondary_user_id  ) {
		$hide_sitewide = true;
		$args = compact( 'user_id', 'content', 'component_name', 'component_action', 'item_id', 'secondary_item_id', 'recorded_time', 'hide_sitewide' );
		bp_activity_add( $args );
	}

	do_action( 'bp_activity_record', $item_id, $component_name, $component_action, $is_private, $secondary_item_id, $user_id, $secondary_user_id );

	return true;
}

/* DEPRECATED - use bp_activity_delete_by_item_id() */
function bp_activity_delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id ) {
	if ( !bp_activity_delete_by_item_id( array( 'item_id' => $item_id, 'component_name' => $component_name, 'component_action' => $component_action, 'user_id' => $user_id, 'secondary_item_id' => $secondary_item_id ) ) )
		return false;

	do_action( 'bp_activity_delete', $item_id, $component_name, $component_action, $user_id, $secondary_item_id );

	return true;
}

/* DEPRECATED - use the activity template loop directly */
function bp_activity_get_list( $user_id, $title, $no_activity, $limit = false ) {
	global $bp_activity_user_id, $bp_activity_limit, $bp_activity_title, $bp_activity_no_activity;

	$bp_activity_user_id = $user_id;
	$bp_activity_limit = $limit;
	$bp_activity_title = $title;
	$bp_activity_no_activity = $no_activity;

	locate_template( array( '/activity/activity-list.php' ), true );
}


/* DEPRECATED - Structural CSS is now theme based. */
function bp_activity_add_structure_css() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;

	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-activity-structure', BP_PLUGIN_URL . '/bp-activity/deprecated/css/structure.css' );
}
add_action( 'bp_styles', 'bp_activity_add_structure_css' );

?>