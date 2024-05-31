<?php
/**
 * Messages: User's "Messages" screen handler.
 *
 * @package BuddyPress
 * @subpackage MessageScreens
 * @since 3.0.0
 */

/**
 * Load the Messages > Inbox screen.
 *
 * @since 1.0.0
 */
function messages_screen_inbox() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/**
	 * Fires right before the loading of the Messages inbox screen template file.
	 *
	 * @since 1.0.0
	 */
	do_action( 'messages_screen_inbox' );

	$templates = array(
		/**
		 * Filters the template to load for the Messages inbox screen.
		 *
		 * @since 1.0.0
		 *
		 * @param string $template Path to the messages template to load.
		 */
		apply_filters( 'messages_template_inbox', 'members/single/home' ),
		'members/single/index',
	);

	bp_core_load_template( $templates );
}
