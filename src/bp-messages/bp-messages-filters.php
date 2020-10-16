<?php
/**
 * BuddyPress Messages Filters.
 *
 * Apply WordPress defined filters to private messages.
 *
 * @package BuddyPress
 * @subpackage MessagesFilters
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bp_get_message_notice_subject',        'wp_filter_kses', 1 );
add_filter( 'bp_get_message_notice_text',           'wp_filter_kses', 1 );
add_filter( 'bp_get_message_thread_subject',        'wp_filter_kses', 1 );
add_filter( 'bp_get_message_thread_excerpt',        'wp_filter_kses', 1 );
add_filter( 'bp_get_messages_subject_value',        'wp_filter_kses', 1 );
add_filter( 'bp_get_messages_content_value',        'wp_filter_kses', 1 );
add_filter( 'messages_message_subject_before_save', 'wp_filter_kses', 1 );
add_filter( 'messages_notice_subject_before_save',  'wp_filter_kses', 1 );
add_filter( 'bp_get_the_thread_subject',            'wp_filter_kses', 1 );

add_filter( 'bp_get_the_thread_message_content',    'bp_messages_filter_kses', 1 );
add_filter( 'messages_message_content_before_save', 'bp_messages_filter_kses', 1 );
add_filter( 'messages_notice_message_before_save',  'bp_messages_filter_kses', 1 );
add_filter( 'bp_get_message_thread_content',        'bp_messages_filter_kses', 1 );

add_filter( 'messages_message_content_before_save', 'force_balance_tags' );
add_filter( 'messages_message_subject_before_save', 'force_balance_tags' );
add_filter( 'messages_notice_message_before_save',  'force_balance_tags' );
add_filter( 'messages_notice_subject_before_save',  'force_balance_tags' );

add_filter( 'messages_message_subject_before_save', 'wp_encode_emoji' );
add_filter( 'messages_message_content_before_save', 'wp_encode_emoji' );
add_filter( 'messages_notice_message_before_save',  'wp_encode_emoji' );
add_filter( 'messages_notice_subject_before_save',  'wp_encode_emoji' );

add_filter( 'bp_get_message_notice_subject',     'wptexturize' );
add_filter( 'bp_get_message_notice_text',        'wptexturize' );
add_filter( 'bp_get_message_thread_subject',     'wptexturize' );
add_filter( 'bp_get_message_thread_excerpt',     'wptexturize' );
add_filter( 'bp_get_the_thread_message_content', 'wptexturize' );
add_filter( 'bp_get_message_thread_content',     'wptexturize' );

add_filter( 'bp_get_message_notice_subject',     'convert_smilies', 2 );
add_filter( 'bp_get_message_notice_text',        'convert_smilies', 2 );
add_filter( 'bp_get_message_thread_subject',     'convert_smilies', 2 );
add_filter( 'bp_get_message_thread_excerpt',     'convert_smilies', 2 );
add_filter( 'bp_get_the_thread_message_content', 'convert_smilies', 2 );
add_filter( 'bp_get_message_thread_content',     'convert_smilies', 2 );

add_filter( 'bp_get_message_notice_subject',     'convert_chars' );
add_filter( 'bp_get_message_notice_text',        'convert_chars' );
add_filter( 'bp_get_message_thread_subject',     'convert_chars' );
add_filter( 'bp_get_message_thread_excerpt',     'convert_chars' );
add_filter( 'bp_get_the_thread_message_content', 'convert_chars' );
add_filter( 'bp_get_message_thread_content',     'convert_chars' );

add_filter( 'bp_get_message_notice_text',        'make_clickable', 9 );
add_filter( 'bp_get_the_thread_message_content', 'make_clickable', 9 );
add_filter( 'bp_get_message_thread_content',     'make_clickable', 9 );

add_filter( 'bp_get_message_notice_text',        'wpautop' );
add_filter( 'bp_get_the_thread_message_content', 'wpautop' );
add_filter( 'bp_get_message_thread_content',     'wpautop' );

add_filter( 'bp_get_message_notice_subject',          'stripslashes_deep'    );
add_filter( 'bp_get_message_notice_text',             'stripslashes_deep'    );
add_filter( 'bp_get_message_thread_subject',          'stripslashes_deep'    );
add_filter( 'bp_get_message_thread_excerpt',          'stripslashes_deep'    );
add_filter( 'bp_get_message_get_recipient_usernames', 'stripslashes_deep'    );
add_filter( 'bp_get_messages_subject_value',          'stripslashes_deep'    );
add_filter( 'bp_get_messages_content_value',          'stripslashes_deep'    );
add_filter( 'bp_get_the_thread_message_content',      'stripslashes_deep'    );
add_filter( 'bp_get_the_thread_subject',              'stripslashes_deep'    );
add_filter( 'bp_get_message_thread_content',          'stripslashes_deep', 1 );

add_filter( 'bp_get_the_thread_message_content', 'bp_core_add_loading_lazy_attribute' );

// Personal data export.
add_filter( 'wp_privacy_personal_data_exporters', 'bp_messages_register_personal_data_exporter' );

/**
 * Enforce limitations on viewing private message contents
 *
 * @since 2.3.2
 *
 * @see bp_has_message_threads() for description of parameters
 *
 * @param array|string $args See {@link bp_has_message_threads()}.
 * @return array|string
 */
function bp_messages_enforce_current_user( $args = array() ) {

	// Non-community moderators can only ever see their own messages.
	if ( is_user_logged_in() && ! bp_current_user_can( 'bp_moderate' ) ) {
		$_user_id = (int) bp_loggedin_user_id();
		if ( $_user_id !== (int) $args['user_id'] ) {
			$args['user_id'] = $_user_id;
		}
	}

	// Return possibly modified $args array.
	return $args;
}
add_filter( 'bp_after_has_message_threads_parse_args', 'bp_messages_enforce_current_user', 5 );

/**
 * Custom kses filtering for message content.
 *
 * @since 3.0.0
 *
 * @param string $content The message content.
 * @return string         The filtered message content.
 */
function bp_messages_filter_kses( $content ) {
	$messages_allowedtags      = bp_get_allowedtags();
	$messages_allowedtags['p'] = array();

	/**
	 * Filters the allowed HTML tags for BuddyPress Messages content.
	 *
	 * @since 3.0.0
	 *
	 * @param array $value Array of allowed HTML tags and attributes.
	 */
	$messages_allowedtags = apply_filters( 'bp_messages_allowed_tags', $messages_allowedtags );
	return wp_kses( $content, $messages_allowedtags );
}

/**
 * Register Messages personal data exporter.
 *
 * @since 4.0.0
 * @since 5.0.0 adds an `exporter_bp_friendly_name` param to exporters.
 *
 * @param array $exporters  An array of personal data exporters.
 * @return array An array of personal data exporters.
 */
function bp_messages_register_personal_data_exporter( $exporters ) {
	$exporters['buddypress-messages'] = array(
		'exporter_friendly_name'    => __( 'BuddyPress Messages', 'buddypress' ),
		'callback'                  => 'bp_messages_personal_data_exporter',
		'exporter_bp_friendly_name' => _x( 'Private Messages', 'BuddyPress Messages data exporter friendly name', 'buddypress' ),
	);

	return $exporters;
}
