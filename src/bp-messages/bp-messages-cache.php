<?php

/**
 * BuddyPress Messages Caching
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 *
 * @package BuddyPress
 * @subpackage SettingsLoader
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// List actions to clear super cached pages on, if super cache is installed
add_action( 'messages_delete_thread',  'bp_core_clear_cache' );
add_action( 'messages_send_notice',    'bp_core_clear_cache' );
add_action( 'messages_message_sent',   'bp_core_clear_cache' );

// Don't cache message inbox/sentbox/compose as it's too problematic
add_action( 'messages_screen_compose', 'bp_core_clear_cache' );
add_action( 'messages_screen_sentbox', 'bp_core_clear_cache' );
add_action( 'messages_screen_inbox',   'bp_core_clear_cache' );

/**
 * Clears unread count cache for each recipient after a message is sent.
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
 * Clears unread count cache for the logged-in user after a message is deleted.
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
 * Invalidates cache for notices.
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