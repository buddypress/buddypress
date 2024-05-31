<?php
/**
 * Groups: Single group "Activity" screen handler
 *
 * @package BuddyPress
 * @subpackage GroupsScreens
 * @since 3.0.0
 */

/**
 * Handle the loading of a single group's activity.
 *
 * @since 2.4.0
 */
function groups_screen_group_activity() {

	if ( ! bp_is_single_item() ) {
		return;
	}

	/**
	 * Fires before the loading of a single group's activity page.
	 *
	 * @since 2.4.0
	 */
	do_action( 'groups_screen_group_activity' );

	$templates = array(
		/**
		 * Filters the template to load for a single group's activity page.
		 *
		 * @since 2.4.0
		 *
		 * @param string $value Path to a single group's template to load.
		 */
		apply_filters( 'groups_screen_group_activity', 'groups/single/activity' ),
		'groups/single/index',
	);

	bp_core_load_template( $templates );
}
