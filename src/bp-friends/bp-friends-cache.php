<?php
/**
 * BuddyPress Friends Caching.
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 *
 * @package BuddyPress
 * @subpackage FriendsCaching
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Clear friends-related cache for members of a specific friendship.
 *
 * @since 1.0.0
 *
 * @param int $friendship_id ID of the friendship whose two members should
 *                           have their friends cache busted.
 * @return bool
 */
function friends_clear_friend_object_cache( $friendship_id ) {
	if ( !$friendship = new BP_Friends_Friendship( $friendship_id ) )
		return false;

	wp_cache_delete( 'friends_friend_ids_' .    $friendship->initiator_user_id, 'bp' );
	wp_cache_delete( 'friends_friend_ids_' .    $friendship->friend_user_id,    'bp' );
}

// List actions to clear object caches on.
add_action( 'friends_friendship_accepted', 'friends_clear_friend_object_cache' );
add_action( 'friends_friendship_deleted',  'friends_clear_friend_object_cache' );

/**
 * Clear friendship caches on friendship changes.
 *
 * @since 2.7.0
 *
 * @param int $friendship_id     ID of the friendship that has changed.
 * @param int $initiator_user_id ID of the first user.
 * @param int $friend_user_id    ID of the second user.
 * @return bool
 */
function bp_friends_clear_bp_friends_friendships_cache( $friendship_id, $initiator_user_id, $friend_user_id ) {
	// Clear friendship ID cache for each user.
	wp_cache_delete( $initiator_user_id, 'bp_friends_friendships_for_user' );
	wp_cache_delete( $friend_user_id,    'bp_friends_friendships_for_user' );

	// Clear the friendship object cache.
	wp_cache_delete( $friendship_id, 'bp_friends_friendships' );

	// Clear incremented cache.
	$friendship = new stdClass;
	$friendship->initiator_user_id = $initiator_user_id;
	$friendship->friend_user_id    = $friend_user_id;
	bp_friends_delete_cached_friendships_on_friendship_save( $friendship );
}
add_action( 'friends_friendship_requested', 'bp_friends_clear_bp_friends_friendships_cache', 10, 3 );
add_action( 'friends_friendship_accepted',  'bp_friends_clear_bp_friends_friendships_cache', 10, 3 );
add_action( 'friends_friendship_deleted',   'bp_friends_clear_bp_friends_friendships_cache', 10, 3 );

/**
 * Clear friendship caches on friendship changes.
 *
 * @since 2.7.0
 *
 * @param int                   $friendship_id The friendship ID.
 * @param BP_Friends_Friendship $friendship Friendship object.
 */
function bp_friends_clear_bp_friends_friendships_cache_remove( $friendship_id, BP_Friends_Friendship $friendship ) {
	// Clear friendship ID cache for each user.
	wp_cache_delete( $friendship->initiator_user_id, 'bp_friends_friendships_for_user' );
	wp_cache_delete( $friendship->friend_user_id,    'bp_friends_friendships_for_user' );

	// Clear the friendship object cache.
	wp_cache_delete( $friendship_id, 'bp_friends_friendships' );

	// Clear incremented cache.
	bp_friends_delete_cached_friendships_on_friendship_save( $friendship );
}
add_action( 'friends_friendship_withdrawn', 'bp_friends_clear_bp_friends_friendships_cache_remove', 10, 2 );
add_action( 'friends_friendship_rejected',  'bp_friends_clear_bp_friends_friendships_cache_remove', 10, 2 );

/**
 * Clear the friend request cache for the user not initiating the friendship.
 *
 * @since 2.0.0
 *
 * @param int $friend_user_id The user ID not initiating the friendship.
 */
function bp_friends_clear_request_cache( $friend_user_id ) {
	wp_cache_delete( $friend_user_id, 'bp_friends_requests' );
}

/**
 * Clear the friend request cache when a friendship is saved.
 *
 * A friendship is deemed saved when a friendship is requested or accepted.
 *
 * @since 2.0.0
 *
 * @param int $friendship_id     The friendship ID.
 * @param int $initiator_user_id The user ID initiating the friendship.
 * @param int $friend_user_id    The user ID not initiating the friendship.
 */
function bp_friends_clear_request_cache_on_save( $friendship_id, $initiator_user_id, $friend_user_id ) {
	bp_friends_clear_request_cache( $friend_user_id );
}
add_action( 'friends_friendship_requested', 'bp_friends_clear_request_cache_on_save', 10, 3 );
add_action( 'friends_friendship_accepted',  'bp_friends_clear_request_cache_on_save', 10, 3 );

/**
 * Clear the friend request cache when a friendship is removed.
 *
 * A friendship is deemed removed when a friendship is withdrawn or rejected.
 *
 * @since 2.0.0
 *
 * @param int                   $friendship_id The friendship ID.
 * @param BP_Friends_Friendship $friendship Friendship object.
 */
function bp_friends_clear_request_cache_on_remove( $friendship_id, BP_Friends_Friendship $friendship ) {
	bp_friends_clear_request_cache( $friendship->friend_user_id );
}
add_action( 'friends_friendship_withdrawn', 'bp_friends_clear_request_cache_on_remove', 10, 2 );
add_action( 'friends_friendship_rejected',  'bp_friends_clear_request_cache_on_remove', 10, 2 );

/**
 * Delete individual friendships from the cache when they are changed.
 *
 * @since 3.0.0
 *
 * @param BP_Friends_Friendship $friendship Friendship object.
 */
function bp_friends_delete_cached_friendships_on_friendship_save( $friendship ) {
	bp_core_delete_incremented_cache( $friendship->friend_user_id . ':' . $friendship->initiator_user_id, 'bp_friends' );
	bp_core_delete_incremented_cache( $friendship->initiator_user_id . ':' . $friendship->friend_user_id, 'bp_friends' );
}
add_action( 'friends_friendship_after_save', 'bp_friends_delete_cached_friendships_on_friendship_save' );

// List actions to clear super cached pages on, if super cache is installed.
add_action( 'friends_friendship_rejected',  'bp_core_clear_cache' );
add_action( 'friends_friendship_accepted',  'bp_core_clear_cache' );
add_action( 'friends_friendship_deleted',   'bp_core_clear_cache' );
add_action( 'friends_friendship_requested', 'bp_core_clear_cache' );
