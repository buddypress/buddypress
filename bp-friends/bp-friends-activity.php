<?php

/**
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 */

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

function friends_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;

	switch ( $action ) {
		case 'friendship_accepted':
		$user_fullname = bp_core_get_user_displayname( $item_id );
			if ( (int)$total_items > 1 )
				return apply_filters( 'bp_friends_multiple_friendship_accepted_notification', '<a href="' . $bp->loggedin_user->domain . $bp->friends->slug . '/my-friends/newest">' . sprintf( __('%d friends accepted your friendship requests', 'buddypress' ), (int)$total_items ) . '</a>', (int)$total_items );
			else
				return apply_filters( 'bp_friends_single_friendship_accepted_notification', '<a href="' . $bp->loggedin_user->domain . $bp->friends->slug . '/my-friends/newest">' . sprintf( __( '%s accepted your friendship request', 'buddypress' ), $user_fullname ) . '</a>', $user_fullname );

			break;

		case 'friendship_request':
			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_friends_multiple_friendship_request_notification', '<a href="' . $bp->loggedin_user->domain . $bp->friends->slug . '/requests/?new" title="' . __( 'Friendship requests', 'buddypress' ) . '">' . sprintf( __('You have %d pending friendship requests', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );
			} else {
				$user_fullname = bp_core_get_user_displayname( $item_id );
				$user_url = bp_core_get_user_domain( $item_id );
				return apply_filters( 'bp_friends_single_friendship_request_notification', '<a href="' . $bp->loggedin_user->domain . $bp->friends->slug . '/requests/?new" title="' . __( 'Friendship requests', 'buddypress' ) . '">' . sprintf( __('You have a friendship request from %s', 'buddypress' ), $user_fullname ) . '</a>', $user_fullname );
			}
			break;
	}

	do_action( 'friends_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return false;
}

?>
