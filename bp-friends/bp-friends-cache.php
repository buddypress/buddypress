<?php

/**
 * BuddyPress Friends Caching.
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 *
 * @package BuddyPress
 * @subpackage FriendsCaching
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Clear friends-related cache for members of a specific friendship.
 *
 * @param int $friendship_id ID of the friendship whose two members should
 *        have their friends cache busted.
 */
function friends_clear_friend_object_cache( $friendship_id ) {
	if ( !$friendship = new BP_Friends_Friendship( $friendship_id ) )
		return false;

	wp_cache_delete( 'friends_friend_ids_' .    $friendship->initiator_user_id, 'bp' );
	wp_cache_delete( 'friends_friend_ids_' .    $friendship->friend_user_id,    'bp' );
}

// List actions to clear object caches on
add_action( 'friends_friendship_accepted', 'friends_clear_friend_object_cache' );
add_action( 'friends_friendship_deleted',  'friends_clear_friend_object_cache' );

// List actions to clear super cached pages on, if super cache is installed
add_action( 'friends_friendship_rejected',  'bp_core_clear_cache' );
add_action( 'friends_friendship_accepted',  'bp_core_clear_cache' );
add_action( 'friends_friendship_deleted',   'bp_core_clear_cache' );
add_action( 'friends_friendship_requested', 'bp_core_clear_cache' );
