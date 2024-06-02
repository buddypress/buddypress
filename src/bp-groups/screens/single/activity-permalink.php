<?php
/**
 * Groups: Single group activity permalink screen handler
 *
 * Note - This has never worked.
 * See {@link https://buddypress.trac.wordpress.org/ticket/2579}
 *
 * @package BuddyPress
 * @subpackage GroupsScreens
 * @since 3.0.0
 */

/**
 * Handle the display of a single group activity item.
 *
 * @since 1.2.0
 */
function groups_screen_group_activity_permalink() {
	if ( ! bp_is_groups_component() || ! bp_is_active( 'activity' ) || ( bp_is_active( 'activity' ) && ! bp_is_current_action( bp_get_activity_slug() ) ) || ! bp_action_variable( 0 ) ) {
		return;
	}

	buddypress()->is_single_item = true;

	$templates = array(
		/** This filter is documented in bp-groups/screens/home.php */
		apply_filters( 'groups_template_group_home', 'groups/single/home' ),
		'groups/single/index',
	);

	bp_core_load_template( $templates );
}
add_action( 'bp_screens', 'groups_screen_group_activity_permalink' );
