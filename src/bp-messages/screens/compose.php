<?php
/**
 * Messages: User's "Messages > Compose" screen handler.
 *
 * @package BuddyPress
 * @subpackage MessageScreens
 * @since 3.0.0
 */

/**
 * Load the Messages > Compose screen.
 *
 * @since 1.0.0
 */
function messages_screen_compose() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// Remove any saved message data from a previous session.
	messages_remove_callback_values();

	/**
	 * Fires right before the loading of the Messages compose screen template file.
	 *
	 * @since 1.0.0
	 */
	do_action( 'messages_screen_compose' );

	/**
	 * Filters the template to load for the Messages compose screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Path to the messages template to load.
	 */
	bp_core_load_template( apply_filters( 'messages_template_compose', 'members/single/home' ) );
}
