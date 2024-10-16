<?php
/**
 * BuddyPress Notices functions.
 *
 * @package buddypress\bp-members\screens\notices
 * @since 15.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bp_members_notices_load_screen() {
	$templates = array(
		/**
		 * Filters the template used to display the notices page.
		 *
		 * @since 15.0.0
		 *
		 * @param string $template Path to the notices template to load.
		 */
		apply_filters( 'members_template_load_notices', 'members/single/home' ),
		'members/single/index',
	);

	bp_core_load_template( $templates );
}
