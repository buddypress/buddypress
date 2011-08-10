<?php
/*******************************************************************************
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
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

?>