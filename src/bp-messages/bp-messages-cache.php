<?php

/**
 * BuddyPress Messages Caching
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 *
 * @package BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Slurp up metadata for a set of messages.
 *
 * It grabs all message meta associated with all of the messages passed in
 * $message_ids and adds it to WP cache. This improves efficiency when using
 * message meta within a loop context.
 *
 * @since BuddyPress (2.2.0)
 *
 * @param int|str|array $message_ids Accepts a single message_id, or a
 *        comma-separated list or array of message ids.
 */
function bp_messages_update_meta_cache( $message_ids = false ) {
	bp_update_meta_cache( array(
		'object_ids' 	   => $message_ids,
		'object_type' 	   => buddypress()->messages->id,
		'cache_group'      => 'message_meta',
		'object_column'    => 'message_id',
		'meta_table' 	   => buddypress()->messages->table_name_meta,
		'cache_key_prefix' => 'bp_messages_meta'
	) );
}

// List actions to clear super cached pages on, if super cache is installed
add_action( 'messages_delete_thread',  'bp_core_clear_cache' );
add_action( 'messages_send_notice',    'bp_core_clear_cache' );
add_action( 'messages_message_sent',   'bp_core_clear_cache' );

// Don't cache message inbox/sentbox/compose as it's too problematic
add_action( 'messages_screen_compose', 'bp_core_clear_cache' );
add_action( 'messages_screen_sentbox', 'bp_core_clear_cache' );
add_action( 'messages_screen_inbox',   'bp_core_clear_cache' );

/**
 * Clear unread count cache for each recipient after a message is sent.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param BP_Messages_Message $message
 */
function bp_messages_clear_unread_count_cache_on_message_save( BP_Messages_Message $message ) {
	foreach ( (array) $message->recipients as $recipient ) {
		wp_cache_delete( $recipient->user_id, 'bp_messages_unread_count' );
	}
}
add_action( 'messages_message_after_save', 'bp_messages_clear_unread_count_cache_on_message_save' );

/**
 * Clear unread count cache for the logged-in user after a message is deleted.
 *
 * @since BuddyPress (2.0.0)
 *
 * @param int|array $thread_ids If single thread, the thread ID. Otherwise, an
 *  array of thread IDs
 */
function bp_messages_clear_unread_count_cache_on_message_delete( $thread_ids ) {
	wp_cache_delete( bp_loggedin_user_id(), 'bp_messages_unread_count' );
}
add_action( 'messages_before_delete_thread', 'bp_messages_clear_unread_count_cache_on_message_delete' );

/**
 * Invalidate cache for notices.
 *
 * Currently, invalidates active notice cache.
 *
 * @since BuddyPress (2.0.0)
 */
function bp_notices_clear_cache( $notice ) {
	wp_cache_delete( 'active_notice', 'bp_messages' );
}
add_action( 'messages_notice_after_save',    'bp_notices_clear_cache' );
add_action( 'messages_notice_before_delete', 'bp_notices_clear_cache' );
