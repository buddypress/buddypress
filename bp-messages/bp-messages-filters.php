<?php

/* Apply WordPress defined filters */
add_filter( 'bp_message_notice_subject', 'wptexturize' );
add_filter( 'bp_message_notice_text', 'wptexturize' );
add_filter( 'bp_message_thread_subject', 'wptexturize' );
add_filter( 'bp_message_thread_excerpt', 'wptexturize' );
add_filter( 'bp_message_content', 'wptexturize' );

add_filter( 'bp_message_notice_subject', 'convert_smilies' );
add_filter( 'bp_message_notice_text', 'convert_smilies' );
add_filter( 'bp_message_thread_subject', 'convert_smilies' );
add_filter( 'bp_message_thread_excerpt', 'convert_smilies' );
add_filter( 'bp_message_content', 'convert_smilies' );

add_filter( 'bp_message_notice_subject', 'convert_chars' );
add_filter( 'bp_message_notice_text', 'convert_chars' );
add_filter( 'bp_message_thread_subject', 'convert_chars' );
add_filter( 'bp_message_thread_excerpt', 'convert_chars' );
add_filter( 'bp_message_content', 'convert_chars' );

add_filter( 'bp_message_notice_subject', 'wpautop' );
add_filter( 'bp_message_notice_text', 'wpautop' );
add_filter( 'bp_message_thread_subject', 'wpautop' );
add_filter( 'bp_message_thread_excerpt', 'wpautop' );
add_filter( 'bp_message_content', 'wpautop' );

add_filter( 'bp_message_notice_subject', 'stripslashes_deep' );
add_filter( 'bp_message_notice_text', 'stripslashes_deep' );
add_filter( 'bp_message_thread_subject', 'stripslashes_deep' );
add_filter( 'bp_message_thread_excerpt', 'stripslashes_deep' );
add_filter( 'bp_messages_subject_value', 'stripslashes_deep' );
add_filter( 'bp_messages_content_value', 'stripslashes_deep' );
add_filter( 'bp_message_content', 'stripslashes_deep' );

add_filter( 'bp_message_notice_subject', 'wp_filter_kses', 1 );
add_filter( 'bp_message_notice_text', 'wp_filter_kses', 1 );
add_filter( 'bp_message_thread_subject', 'wp_filter_kses', 1 );
add_filter( 'bp_message_thread_excerpt', 'wp_filter_kses', 1 );
add_filter( 'bp_messages_subject_value', 'wp_filter_kses', 1 );
add_filter( 'bp_messages_content_value', 'wp_filter_kses', 1 );
add_filter( 'bp_message_content', 'wp_filter_kses', 1 );

?>