<?php
if ( !defined( 'BP_STATUS_SLUG' ) )
	define ( 'BP_STATUS_SLUG', 'status' );

require ( BP_PLUGIN_DIR . '/bp-status/bp-status-templatetags.php' );
require ( BP_PLUGIN_DIR . '/bp-status/bp-status-filters.php' );

function bp_status_setup_globals() {
	global $bp, $wpdb;

	/* For internal identification */
	$bp->status->id = 'status';
	$bp->status->slug = BP_STATUS_SLUG;
	
	/* Register this in the active components array */
	$bp->active_components[$bp->status->slug] = $bp->status->id;

	do_action( 'bp_status_setup_globals' );
}
add_action( 'plugins_loaded', 'bp_status_setup_globals', 5 );	
add_action( 'admin_menu', 'bp_status_setup_globals', 2 );


/********************************************************************************
 * Activity & Notification Functions
 *
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 */

function bp_status_register_activity_actions() {
	global $bp;
	
	if ( !function_exists( 'bp_activity_set_action' ) )
		return false;

	/* Register the activity stream actions for this component */
	bp_activity_set_action( $bp->status->id, 'new_status', __( 'New status update', 'buddypress' ) );

	do_action( 'bp_status_register_activity_actions' );
}
add_action( 'plugins_loaded', 'bp_status_register_activity_actions' );

function bp_status_record_activity( $user_id, $content, $primary_link, $component_action = 'new_status' ) {
	global $bp;
	
	if ( !function_exists( 'bp_activity_add' ) )
		return false;
	
	return bp_activity_add( array( 
			'user_id' => $user_id, 
			'content' => $content, 
			'primary_link' => $primary_link, 
			'component_name' => $bp->status->id,
			'component_action' => $component_action
	) );
}

function bp_status_delete_activity( $user_id, $content ) {
	if ( !function_exists( 'bp_activity_delete_by_content' ) )
		return false;

	return bp_activity_delete_by_content( $user_id, $content, 'status', 'new_status' );
}

function bp_status_register_activity_action( $key, $value ) {
	global $bp;
	
	if ( !function_exists( 'bp_activity_set_action' ) )
		return false;
	
	return apply_filters( 'bp_status_register_activity_action', bp_activity_set_action( $bp->status->id, $key, $value ), $key, $value );
}


/********************************************************************************
 * Action Functions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 */

function bp_status_action_add() {
	global $bp;

	if ( $bp->current_component != BP_STATUS_SLUG || 'add' != $bp->current_action )
		return false;
	
	if ( !check_admin_referer( 'bp_status_add_status', '_wpnonce_add_status' ) )
		return false;

	if ( bp_status_add_status( $bp->loggedin_user->id, $_POST['status-update-input'] ) )
		bp_core_add_message( __( 'Your status was updated successfully!', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was a problem updating your status. Please try again.', 'buddypress' ), 'error' );
	
	bp_core_redirect( $bp->loggedin_user->domain );
}
add_action( 'init', 'bp_status_action_add' );


/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

function bp_status_add_status( $user_id, $content ) {
	global $bp;
	
	$content = apply_filters( 'bp_status_content_before_save', $content );
	$recorded_time = time();
	
	if ( !$content || empty($content) )
		return false;
	
	bp_status_clear_existing_activity( $user_id );
	
	/* Store the status in usermeta for easy access. */
	update_usermeta( $user_id, 'bp_status', array( 'content' => $content, 'recorded_time' => $recorded_time ) );

	/* Recored in activity streams */
	$user_link = bp_core_get_userlink( $user_id );
	$activity_content = sprintf( __( '%s posted a new status update:', 'buddypress' ), $user_link );
	$activity_content .= "<blockquote>$content</blockquote>";

	bp_status_record_activity( $user_id, apply_filters( 'bp_status_activity_new', $activity_content, $content, $user_link ), apply_filters( 'bp_status_activity_new_primary_link', $user_link, $user_id ) );
	
	do_action( 'bp_status_add_status', $user_id, $content );

	return true;
}

function bp_status_clear_status( $user_id = false ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;
	
	bp_status_clear_existing_activity( $user_id );
	
	return delete_usermeta( $user_id, 'bp_status' );
}

function bp_status_clear_existing_activity( $user_id ) {
	/* Fetch existing status update if there is one. */
	$existing_status = get_usermeta( $user_id, 'bp_status' );
	
	if ( '' != $existing_status ) {
		if ( strtotime( '+5 minutes', $existing_status['recorded_time'] ) >= time() ) {
			/***
			 * The last status was updated less than 5 minutes ago, so lets delete the activity stream item for that
			 * status update, to prevent flooding and allow users to change their mind about recording a status.
			 */
			$activity_content = sprintf( __( '%s posted a new status update:', 'buddypress' ), bp_core_get_userlink( $user_id ) );
			$activity_content .= "<blockquote>{$existing_status['content']}</blockquote>";

			bp_status_delete_activity( $user_id, $activity_content );
		}
	}
}

?>