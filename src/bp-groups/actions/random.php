<?php
/**
 * Groups: Random group action handler
 *
 * @package BuddyPress
 * @subpackage GroupActions
 * @since 3.0.0
 */

/**
 * Catch requests for a random group page (example.com/groups/?random-group) and redirect.
 *
 * @since 1.2.0
 */
function groups_action_redirect_to_random_group() {

	if ( bp_is_groups_component() && isset( $_GET['random-group'] ) ) {
		$group = BP_Groups_Group::get_random( 1, 1 );

		bp_core_redirect( bp_get_group_url( $group['groups'][0] ) );
	}
}
add_action( 'bp_actions', 'groups_action_redirect_to_random_group' );
