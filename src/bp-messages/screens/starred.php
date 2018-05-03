<?php
/**
 * Messages: User's "Messages > Starred" screen handler
 *
 * @package BuddyPress
 * @subpackage MessageScreens
 * @since 3.0.0
 */

/**
 * Screen handler to display a user's "Starred" private messages page.
 *
 * @since 2.3.0
 */
function bp_messages_star_screen() {
	add_action( 'bp_template_content', 'bp_messages_star_content' );

	/**
	 * Fires right before the loading of the "Starred" messages box.
	 *
	 * @since 2.3.0
	 */
	do_action( 'bp_messages_screen_star' );

	bp_core_load_template( 'members/single/plugins' );
}

/**
 * Screen content callback to display a user's "Starred" messages page.
 *
 * @since 2.3.0
 */
function bp_messages_star_content() {
	// Add our message thread filter.
	add_filter( 'bp_after_has_message_threads_parse_args', 'bp_messages_filter_starred_message_threads' );

	// Load the message loop template part.
	bp_get_template_part( 'members/single/messages/messages-loop' );

	// Remove our filter.
	remove_filter( 'bp_after_has_message_threads_parse_args', 'bp_messages_filter_starred_message_threads' );
}
