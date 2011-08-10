<?php
/**
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function friends_record_activity( $args = '' ) {
	global $bp;

	if ( !bp_is_active( 'activity' ) )
		return false;

	$defaults = array (
		'user_id'           => $bp->loggedin_user->id,
		'action'            => '',
		'content'           => '',
		'primary_link'      => '',
		'component'         => $bp->friends->id,
		'type'              => false,
		'item_id'           => false,
		'secondary_item_id' => false,
		'recorded_time'     => bp_core_current_time(),
		'hide_sitewide'     => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	return bp_activity_add( array( 'user_id' => $user_id, 'action' => $action, 'content' => $content, 'primary_link' => $primary_link, 'component' => $component, 'type' => $type, 'item_id' => $item_id, 'secondary_item_id' => $secondary_item_id, 'recorded_time' => $recorded_time, 'hide_sitewide' => $hide_sitewide ) );
}

function friends_delete_activity( $args ) {
	global $bp;

	if ( bp_is_active( 'activity' ) ) {
		extract( (array)$args );
		bp_activity_delete_by_item_id( array( 'item_id' => $item_id, 'component' => $bp->friends->id, 'type' => $type, 'user_id' => $user_id ) );
	}
}

function friends_register_activity_actions() {
	global $bp;

	if ( !bp_is_active( 'activity' ) )
		return false;

	bp_activity_set_action( $bp->friends->id, 'friends_register_activity_action', __( 'New friendship created', 'buddypress' ) );

	do_action( 'friends_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'friends_register_activity_actions' );

/**
 * Format the BuddyBar/admin bar notifications for the Friends component
 *
 * @package BuddyPress
 *
 * @param str $action The kind of notification being rendered
 * @param int $item_id The primary item id
 * @param int $secondary_item_id The secondary item id
 * @param int $total_items The total number of messaging-related notifications waiting for the user
 * @param str $format 'string' for BuddyBar-compatible notifications; 'array' for WP Admin Bar
 */
function friends_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {
	global $bp;

	switch ( $action ) {
		case 'friendship_accepted':
			$link = bp_loggedin_user_domain() . bp_get_friends_slug() . '/my-friends/newest';

			// Set up the string and the filter
			if ( (int)$total_items > 1 ) {
				$text = sprintf( __( '%d friends accepted your friendship requests', 'buddypress' ), (int)$total_items );
				$filter = 'bp_friends_multiple_friendship_accepted_notification';
			} else {
				$text = sprintf( __( '%s accepted your friendship request', 'buddypress' ),  bp_core_get_user_displayname( $item_id ) );
				$filter = 'bp_friends_single_friendship_accepted_notification';
			}

			break;

		case 'friendship_request':
			$link = bp_loggedin_user_domain() . bp_get_friends_slug() . '/requests?new';

			// Set up the string and the filter
			if ( (int)$total_items > 1 ) {
				$text = sprintf( __( 'You have %d pending friendship requests', 'buddypress' ), (int)$total_items );
				$filter = 'bp_friends_multiple_friendship_request_notification';
			} else {
				$text = sprintf( __( 'You have a friendship request from %s', 'buddypress' ),  bp_core_get_user_displayname( $item_id ) );
				$filter = 'bp_friends_single_friendship_request_notification';
			}

			break;
	}

	// Return either an HTML link or an array, depending on the requested format
	if ( 'string' == $format ) {
		$return = apply_filters( $filter, '<a href="' . $link . '">' . $text . '</a>', (int)$total_items );
	} else {
		$return = apply_filters( $filter, array(
			'link' => $link,
			'text' => $text
		), (int)$total_items );
	}

	do_action( 'friends_format_notifications', $action, $item_id, $secondary_item_id, $total_items, $return );

	return $return;
}

?>