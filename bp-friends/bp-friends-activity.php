<?php

/**
 * BuddyPress Friends Activity Functions
 *
 * These functions handle the recording, deleting and formatting of activity
 * for the user and for this specific component.
 *
 * @package BuddyPress
 * @subpackage FriendsActivity
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Record an activity item related to the Friends component.
 *
 * A wrapper for {@link bp_activity_add()} that provides some Friends-specific
 * defaults.
 *
 * @see bp_activity_add() for more detailed description of parameters and
 *      return values.
 *
 * @param array $args {
 *     An array of arguments for the new activity item. Accepts all parameters
 *     of {@link bp_activity_add()}. The one difference is the following
 *     argument, which has a different default here:
 *     @type string $component Default: the id of your Friends component
 *           (usually 'friends').
 * }
 * @return bool See {@link bp_activity_add()}.
 */
function friends_record_activity( $args = '' ) {

	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	$r = wp_parse_args( $args, array(
		'user_id'           => bp_loggedin_user_id(),
		'action'            => '',
		'content'           => '',
		'primary_link'      => '',
		'component'         => buddypress()->friends->id,
		'type'              => false,
		'item_id'           => false,
		'secondary_item_id' => false,
		'recorded_time'     => bp_core_current_time(),
		'hide_sitewide'     => false
	) );

	return bp_activity_add( $r );
}

/**
 * Delete an activity item related to the Friends component.
 *
 * @param array $args {
 *     An array of arguments for the item to delete.
 *     @type int $item_id ID of the 'item' associated with the activity item.
 *           For Friends activity items, this is usually the user ID of one
 *           of the friends.
 *     @type string $type The 'type' of the activity item (eg
 *           'friendship_accepted').
 *     @type int $user_id ID of the user associated with the activity item.
 * }
 * @return bool True on success, false on failure.
 */
function friends_delete_activity( $args ) {
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	bp_activity_delete_by_item_id( array(
		'component' => buddypress()->friends->id,
		'item_id'   => $args['item_id'],
		'type'      => $args['type'],
		'user_id'   => $args['user_id']
	) );
}

/**
 * Register the activity actions for bp-friends.
 */
function friends_register_activity_actions() {

	if ( !bp_is_active( 'activity' ) ) {
		return false;
	}

	$bp = buddypress();

	// These two added in BP 1.6
	bp_activity_set_action(
		$bp->friends->id,
		'friendship_accepted',
		__( 'Friendships accepted', 'buddypress' ),
		'bp_friends_format_activity_action_friendship_accepted'
	);

	bp_activity_set_action(
		$bp->friends->id,
		'friendship_created',
		__( 'New friendships', 'buddypress' ),
		'bp_friends_format_activity_action_friendship_created'
	);

	// < BP 1.6 backpat
	bp_activity_set_action( $bp->friends->id, 'friends_register_activity_action', __( 'New friendship created', 'buddypress' ) );

	do_action( 'friends_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'friends_register_activity_actions' );

/**
 * Format 'friendship_accepted' activity actions.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param object $activity Activity data.
 * @return string $action Formatted activity action.
 */
function bp_friends_format_activity_action_friendship_accepted( $action, $activity ) {
	$initiator_link = bp_core_get_userlink( $activity->user_id );
	$friend_link    = bp_core_get_userlink( $activity->secondary_item_id );

	$action = sprintf( __( '%1$s and %2$s are now friends', 'buddypress' ), $initiator_link, $friend_link );

	// Backward compatibility for legacy filter
	// The old filter has the $friendship object passed to it. We want to
	// avoid having to build this object if it's not necessary
	if ( has_filter( 'friends_activity_friendship_accepted_action' ) ) {
		$friendship = new BP_Friends_Friendship( $activity->item_id );
		$action     = apply_filters( 'friends_activity_friendsip_accepted_action', $action, $friendship );
	}

	return apply_filters( 'bp_friends_format_activity_action_friendship_accepted', $action, $activity );
}

/**
 * Format 'friendship_created' activity actions.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param string $action Static activity action.
 * @param object $activity Activity data.
 * @return string $action Formatted activity action.
 */
function bp_friends_format_activity_action_friendship_created( $action, $activity ) {
	$initiator_link = bp_core_get_userlink( $activity->user_id );
	$friend_link    = bp_core_get_userlink( $activity->secondary_item_id );

	$action = sprintf( __( '%1$s and %2$s are now friends', 'buddypress' ), $initiator_link, $friend_link );

	// Backward compatibility for legacy filter
	// The old filter has the $friendship object passed to it. We want to
	// avoid having to build this object if it's not necessary
	if ( has_filter( 'friends_activity_friendship_accepted_action' ) ) {
		$friendship = new BP_Friends_Friendship( $activity->item_id );
		$action     = apply_filters( 'friends_activity_friendsip_accepted_action', $action, $friendship );
	}

	return apply_filters( 'bp_friends_format_activity_action_friendship_created', $action, $activity );
}

/**
 * Fetch data related to friended users at the beginning of an activity loop.
 *
 * This reduces database overhead during the activity loop.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param array $activities Array of activity items.
 * @return array
 */
function bp_friends_prefetch_activity_object_data( $activities ) {
	if ( empty( $activities ) ) {
		return $activities;
	}

	$friend_ids = array();

	foreach ( $activities as $activity ) {
		if ( buddypress()->friends->id !== $activity->component ) {
			continue;
		}

		$friend_ids[] = $activity->secondary_item_id;
	}

	if ( ! empty( $friend_ids ) ) {
		// Fire a user query to prime user caches
		new BP_User_Query( array(
			'user_ids'          => $friend_ids,
			'populate_extras'   => false,
			'update_meta_cache' => false,
		) );
	}

	return $activities;
}
add_filter( 'bp_activity_prefetch_object_data', 'bp_friends_prefetch_activity_object_data' );

/**
 * Add activity stream items when one members accepts another members request
 * for virtual friendship.
 *
 * @since BuddyPress (1.9.0)
 *
 * @param int $friendship_id
 * @param int $initiator_user_id
 * @param int $friend_user_id
 * @param object $friendship Optional
 */
function bp_friends_friendship_accepted_activity( $friendship_id, $initiator_user_id, $friend_user_id, $friendship = false ) {

	// Bail if Activity component is not active
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	// Get links to both members profiles
	$initiator_link = bp_core_get_userlink( $initiator_user_id );
	$friend_link    = bp_core_get_userlink( $friend_user_id    );

	// Record in activity streams for the initiator
	friends_record_activity( array(
		'user_id'           => $initiator_user_id,
		'type'              => 'friendship_created',
		'item_id'           => $friendship_id,
		'secondary_item_id' => $friend_user_id
	) );

	// Record in activity streams for the friend
	friends_record_activity( array(
		'user_id'           => $friend_user_id,
		'type'              => 'friendship_created',
		'item_id'           => $friendship_id,
		'secondary_item_id' => $initiator_user_id,
		'hide_sitewide'     => true // We've already got the first entry site wide
	) );
}
add_action( 'friends_friendship_accepted', 'bp_friends_friendship_accepted_activity', 10, 4 );
