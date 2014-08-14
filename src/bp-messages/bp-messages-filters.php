<?php

/**
 * BuddyPress Messages Filters
 *
 * Apply WordPress defined filters to private messages
 *
 * @package BuddyPress
 * @subpackage MessagesFilters
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

add_filter( 'bp_get_message_notice_subject',        'wp_filter_kses', 1 );
add_filter( 'bp_get_message_notice_text',           'wp_filter_kses', 1 );
add_filter( 'bp_get_message_thread_subject',        'wp_filter_kses', 1 );
add_filter( 'bp_get_message_thread_excerpt',        'wp_filter_kses', 1 );
add_filter( 'bp_get_messages_subject_value',        'wp_filter_kses', 1 );
add_filter( 'bp_get_messages_content_value',        'wp_filter_kses', 1 );
add_filter( 'bp_get_the_thread_message_content',    'wp_filter_kses', 1 );

add_filter( 'messages_message_content_before_save', 'wp_filter_kses', 1 );
add_filter( 'messages_message_subject_before_save', 'wp_filter_kses', 1 );
add_filter( 'messages_notice_message_before_save',  'wp_filter_kses', 1 );
add_filter( 'messages_notice_subject_before_save',  'wp_filter_kses', 1 );

add_filter( 'bp_get_the_thread_message_content',    'wp_filter_kses', 1 );
add_filter( 'bp_get_the_thread_subject',            'wp_filter_kses', 1 );

add_filter( 'messages_message_content_before_save', 'force_balance_tags' );
add_filter( 'messages_message_subject_before_save', 'force_balance_tags' );
add_filter( 'messages_notice_message_before_save',  'force_balance_tags' );
add_filter( 'messages_notice_subject_before_save',  'force_balance_tags' );

add_filter( 'bp_get_message_notice_subject',     'wptexturize' );
add_filter( 'bp_get_message_notice_text',        'wptexturize' );
add_filter( 'bp_get_message_thread_subject',     'wptexturize' );
add_filter( 'bp_get_message_thread_excerpt',     'wptexturize' );
add_filter( 'bp_get_the_thread_message_content', 'wptexturize' );

add_filter( 'bp_get_message_notice_subject',     'convert_smilies', 2 );
add_filter( 'bp_get_message_notice_text',        'convert_smilies', 2 );
add_filter( 'bp_get_message_thread_subject',     'convert_smilies', 2 );
add_filter( 'bp_get_message_thread_excerpt',     'convert_smilies', 2 );
add_filter( 'bp_get_the_thread_message_content', 'convert_smilies', 2 );

add_filter( 'bp_get_message_notice_subject',     'convert_chars' );
add_filter( 'bp_get_message_notice_text',        'convert_chars' );
add_filter( 'bp_get_message_thread_subject',     'convert_chars' );
add_filter( 'bp_get_message_thread_excerpt',     'convert_chars' );
add_filter( 'bp_get_the_thread_message_content', 'convert_chars' );

add_filter( 'bp_get_message_notice_text',        'make_clickable', 9 );
add_filter( 'bp_get_the_thread_message_content', 'make_clickable', 9 );

add_filter( 'bp_get_message_notice_text',        'wpautop' );
add_filter( 'bp_get_the_thread_message_content', 'wpautop' );

add_filter( 'bp_get_message_notice_subject',     'stripslashes_deep' );
add_filter( 'bp_get_message_notice_text',        'stripslashes_deep' );
add_filter( 'bp_get_message_thread_subject',     'stripslashes_deep' );
add_filter( 'bp_get_message_thread_excerpt',     'stripslashes_deep' );
add_filter( 'bp_get_messages_subject_value',     'stripslashes_deep' );
add_filter( 'bp_get_messages_content_value',     'stripslashes_deep' );
add_filter( 'bp_get_the_thread_message_content', 'stripslashes_deep' );
add_filter( 'bp_get_the_thread_subject',         'stripslashes_deep' );
