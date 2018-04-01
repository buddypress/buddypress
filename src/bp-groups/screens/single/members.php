<?php
/**
 * Groups: Single group "Members" screen handler
 *
 * @package BuddyPress
 * @subpackage GroupsScreens
 * @since 3.0.0
 */

/**
 * Handle the display of a group's Members page.
 *
 * @since 1.0.0
 */
function groups_screen_group_members() {

	if ( !bp_is_single_item() )
		return false;

	$bp = buddypress();

	// Refresh the group member count meta.
	groups_update_groupmeta( $bp->groups->current_group->id, 'total_member_count', groups_get_total_member_count( $bp->groups->current_group->id ) );

	/**
	 * Fires before the loading of a group's Members page.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the group whose members are being displayed.
	 */
	do_action( 'groups_screen_group_members', $bp->groups->current_group->id );

	/**
	 * Filters the template to load for a group's Members page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to a group's Members template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_members', 'groups/single/home' ) );
}