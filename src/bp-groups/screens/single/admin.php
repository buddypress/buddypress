<?php
/**
 * Groups: Single group "Manage" screen handler
 *
 * @package BuddyPress
 * @subpackage GroupsScreens
 * @since 3.0.0
 */

/**
 * Handle the display of a group's Admin pages.
 *
 * @since 1.0.0
 */
function groups_screen_group_admin() {
	if ( !bp_is_groups_component() || !bp_is_current_action( 'admin' ) )
		return false;

	if ( bp_action_variables() )
		return false;

	bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/edit-details/' );
}