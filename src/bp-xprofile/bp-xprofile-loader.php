<?php
/**
 * BuddyPress XProfile Loader.
 *
 * An extended profile component for users. This allows site admins to create
 * groups of fields for users to enter information about themselves.
 *
 * @package BuddyPress
 * @subpackage XProfileLoader
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-xprofile component.
 *
 * @since 1.6.0
 */
function bp_setup_xprofile() {
	$bp = buddypress();

	if ( ! isset( $bp->profile->id ) ) {
		$bp->profile = new BP_XProfile_Component();
	}
}
add_action( 'bp_setup_components', 'bp_setup_xprofile', 2 );
