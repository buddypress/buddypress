<?php
/**
 * Members: User's "Members > Notices" screen handler.
 *
 * @package BuddyPress
 * @subpackage MembersScreens
 * @since 14.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the Messages > Notices screen.
 *
 * @since 1.0.0
 *
 * @return void
 */
function messages_screen_notices() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/**
	 * Fires right before the loading of the Messages notices screen template file.
	 *
	 * @since 1.0.0
	 */
	do_action( 'messages_screen_notices' );

	$templates = array(
		/**
		 * Filters the template to load for the Messages notices screen.
		 *
		 * @since 1.0.0
		 *
		 * @param string $template Path to the messages template to load.
		 */
		apply_filters( 'messages_template_notices', 'members/single/home' ),
		'members/single/index',
	);

	bp_core_load_template( $templates );
}
