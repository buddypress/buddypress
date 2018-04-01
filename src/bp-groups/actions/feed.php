<?php
/**
 * Groups: RSS feed action
 *
 * @package BuddyPress
 * @subpackage GroupActions
 * @since 3.0.0
 */

/**
 * Load the activity feed for the current group.
 *
 * @since 1.2.0
 *
 * @return false|null False on failure.
 */
function groups_action_group_feed() {

	// Get current group.
	$group = groups_get_current_group();

	if ( ! bp_is_active( 'activity' ) || ! bp_is_groups_component() || ! $group || ! bp_is_current_action( 'feed' ) )
		return false;

	// If group isn't public or if logged-in user is not a member of the group, do
	// not output the group activity feed.
	if ( ! bp_group_is_visible( $group ) ) {
		return false;
	}

	// Set up the feed.
	buddypress()->activity->feed = new BP_Activity_Feed( array(
		'id'            => 'group',

		/* translators: Group activity RSS title - "[Site Name] | [Group Name] | Activity" */
		'title'         => sprintf( __( '%1$s | %2$s | Activity', 'buddypress' ), bp_get_site_name(), bp_get_current_group_name() ),

		'link'          => bp_get_group_permalink( $group ),
		'description'   => sprintf( __( "Activity feed for the group, %s.", 'buddypress' ), bp_get_current_group_name() ),
		'activity_args' => array(
			'object'           => buddypress()->groups->id,
			'primary_id'       => bp_get_current_group_id(),
			'display_comments' => 'threaded'
		)
	) );
}
add_action( 'bp_actions', 'groups_action_group_feed' );