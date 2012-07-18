<?php

/**
 * BuddyPress Capabilites
 *
 * @package BuddyPress
 * @subpackage Capabilities
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Adds BuddyPress-specific user roles.
 *
 * This is called on plugin activation.
 *
 * @since BuddyPress (1.6)
 *
 * @uses get_option() To get the default role
 * @uses get_role() To get the default role object
 * @uses add_role() To add our own roles
 * @uses do_action() Calls 'bp_add_roles'
 */
function bp_add_roles() {

	// Get new role names
	$moderator_role   = bp_get_moderator_role();
	$participant_role = bp_get_participant_role();

	// Add the Moderator role and add the default role caps.
	// Mod caps are added by the bp_add_caps() function
	$default = get_role( get_option( 'default_role' ) );

	// If role does not exist, default to read cap
	if ( empty( $default->capabilities ) )
		$default->capabilities = array( 'read' );

	// Moderators are default role + community moderating caps in bp_add_caps()
	add_role( $moderator_role,   'Community Moderator',   $default->capabilities );

	// Forum Subscribers are auto added to sites with global communities
	add_role( $participant_role, 'Community Participant', $default->capabilities );

	do_action( 'bp_add_roles' );
}

/**
 * Adds capabilities to WordPress user roles.
 *
 * This is called on plugin activation.
 *
 * @since BuddyPress (1.6)
 *
 * @uses get_role() To get the administrator, default and moderator roles
 * @uses WP_Role::add_cap() To add various capabilities
 * @uses do_action() Calls 'bp_add_caps'
 */
function bp_add_caps() {
	global $wp_roles;

	// Load roles if not set
	if ( ! isset( $wp_roles ) )
		$wp_roles = new WP_Roles();

	// Loop through available roles
	foreach( $wp_roles->roles as $role => $details ) {

		// Load this role
		$this_role = get_role( $role );

		// Loop through caps for this role and remove them
		foreach ( bp_get_caps_for_role( $role ) as $cap ) {
			$this_role->add_cap( $cap );
		}
	}

	do_action( 'bp_add_caps' );
}

/**
 * Removes capabilities from WordPress user roles.
 *
 * This is called on plugin deactivation.
 *
 * @since BuddyPress (1.6)
 *
 * @uses get_role() To get the administrator and default roles
 * @uses WP_Role::remove_cap() To remove various capabilities
 * @uses do_action() Calls 'bp_remove_caps'
 */
function bp_remove_caps() {
	global $wp_roles;

	// Load roles if not set
	if ( ! isset( $wp_roles ) )
		$wp_roles = new WP_Roles();

	// Loop through available roles
	foreach( $wp_roles->roles as $role => $details ) {

		// Load this role
		$this_role = get_role( $role );

		// Loop through caps for this role and remove them
		foreach ( bp_get_caps_for_role( $role ) as $cap ) {
			$this_role->remove_cap( $cap );
		}
	}

	do_action( 'bp_remove_caps' );
}

/**
 * Removes BuddyPress-specific user roles.
 *
 * This is called on plugin deactivation.
 *
 * @since BuddyPress (1.6)
 *
 * @uses remove_role() To remove our roles
 * @uses do_action() Calls 'bp_remove_roles'
 */
function bp_remove_roles() {

	// Get new role names
	$moderator_role   = bp_get_moderator_role();
	$participant_role = bp_get_participant_role();

	// Remove the Moderator role
	remove_role( $moderator_role );

	// Remove the Moderator role
	remove_role( $participant_role );

	do_action( 'bp_remove_roles' );
}

/**
 * Maps community caps to built in WordPress caps
 *
 * @since BuddyPress (1.6)
 *
 * @param array $caps Capabilities for meta capability
 * @param string $cap Capability name
 * @param int $user_id User id
 * @param mixed $args Arguments
 * @uses get_post() To get the post
 * @uses get_post_type_object() To get the post type object
 * @uses apply_filters() Calls 'bp_map_meta_caps' with caps, cap, user id and
 *                        args
 * @return array Actual capabilities for meta capability
 */
function bp_map_meta_caps( $caps, $cap, $user_id, $args ) {
	return apply_filters( 'bp_map_meta_caps', $caps, $cap, $user_id, $args );
}

/**
 * Return community capabilities
 *
 * @since BuddyPress (1.6)
 *
 * @uses apply_filters() Calls 'bp_get_community_caps' with the capabilities
 * @return array Forum capabilities
 */
function bp_get_community_caps() {

	// Forum meta caps
	$caps = array();

	return apply_filters( 'bp_get_community_caps', $caps );
}

/**
 * Returns an array of capabilities based on the role that is being requested.
 *
 * @since BuddyPress (1.6)
 *
 * @param string $role Optional. Defaults to The role to load caps for
 * @uses apply_filters() Allow return value to be filtered
 *
 * @return array Capabilities for $role
 */
function bp_get_caps_for_role( $role = '' ) {

	// Get new role names
	$moderator_role   = bp_get_moderator_role();
	$participant_role = bp_get_participant_role();

	// Which role are we looking for?
	switch ( $role ) {

		// Administrator
		case 'administrator' :
			$caps = array(
				// Misc
				'bp_moderate',
			);

			break;

		// Moderator
		case $moderator_role :
			$caps = array(
				// Misc
				'bp_moderate',
			);

			break;

		// WordPress Core Roles
		case 'editor'          :
		case 'author'          :
		case 'contributor'     :
		case 'subscriber'      :

		// BuddyPress Participant Role
		case $participant_role :
		default                :
			$caps = array();
			break;
	}

	return apply_filters( 'bp_get_caps_for_role', $caps, $role );
}

/**
 * Give a user the default 'Forum Participant' role when creating a topic/reply
 * on a site they do not have a role or capability on.
 *
 * @since BuddyPress (1.6)
 *
 * @global BuddyPress $bbp
 *
 * @uses is_multisite()
 * @uses bp_allow_global_access()
 * @uses bp_is_user_inactive()
 * @uses is_user_logged_in()
 * @uses current_user_can()
 * @uses WP_User::set_role()
 *
 * @return If user is not spam/deleted or is already capable
 */
function bp_global_access_auto_role() {

	// Bail if not multisite or community is not global
	if ( !is_multisite() || !bp_allow_global_access() )
		return;

	// Bail if user is not active
	if ( bp_is_user_inactive() )
		return;

	// Bail if user is not logged in
	if ( !is_user_logged_in() )
		return;

	// Give the user the 'Forum Participant' role
	if ( current_user_can( 'bp_masked' ) ) {
		global $bbp;

		// Get the default role
		$default_role = bp_get_participant_role();

		// Set the current users default role
		$bbp->current_user->set_role( $default_role );
	}
}

/**
 * The participant role for registered users without roles
 *
 * This is primarily for multisite compatibility when users without roles on
 * sites that have global communities enabled
 *
 * @since BuddyPress (1.6)
 *
 * @param string $role
 * @uses apply_filters()
 * @return string
 */
function bp_get_participant_role() {

	// Hardcoded participant role
	$role = 'bp_participant';

	// Allow override
	return apply_filters( 'bp_get_participant_role', $role );
}

/**
 * The moderator role for BuddyPress users
 *
 * @since BuddyPress (1.6)
 *
 * @param string $role
 * @uses apply_filters()
 * @return string
 */
function bp_get_moderator_role() {

	// Hardcoded moderated user role
	$role = 'bp_moderator';

	// Allow override
	return apply_filters( 'bp_get_moderator_role', $role );
}

/**
 * Add the default role and mapped BuddyPress caps to the current user if needed
 *
 * This function will bail if the community is not global in a multisite
 * installation of WordPress, or if the user is marked as spam or deleted.
 *
 * @since BuddyPress (1.6)
 *
 * @uses is_multisite()
 * @uses bp_allow_global_access()
 * @uses bp_is_user_inactive()
 * @uses is_user_logged_in()
 * @uses current_user_can()
 * @uses get_option()
 * @uses bp_get_caps_for_role()
 *
 * @global BuddyPress $bbp
 * @return If not multisite, not global, or user is deleted/spammed
 */
function bp_global_access_role_mask() {

	// Bail if not multisite or community is not global
	if ( !is_multisite() || !bp_allow_global_access() )
		return;

	// Bail if user is marked as spam or is deleted
	if ( bp_is_user_inactive() )
		return;

	// Normal user is logged in but has no caps
	if ( is_user_logged_in() && !current_user_can( 'read' ) ) {

		// Define local variable
		$mapped_meta_caps = array();

		// Assign user the minimal participant role to map caps to
		$default_role  = bp_get_participant_role();

		// Get BuddyPress caps for the default role
		$caps_for_role = bp_get_caps_for_role( $default_role );

		// Set all caps to true
		foreach ( $caps_for_role as $cap ) {
			$mapped_meta_caps[$cap] = true;
		}

		// Add 'read' cap just in case
		$mapped_meta_caps['read']      = true;
		$mapped_meta_caps['bp_masked'] = true;

		// Allow global access caps to be manipulated
		$mapped_meta_caps = apply_filters( 'bp_global_access_mapped_meta_caps', $mapped_meta_caps );

		// Assign the role and mapped caps to the current user
		global $bp;
		$bp->current_user->roles[0] = $default_role;
		$bp->current_user->caps     = $mapped_meta_caps;
		$bp->current_user->allcaps  = $mapped_meta_caps;
	}
}

/**
 * Whether current user has a capability or role. Can be passed blog ID, or will
 * use the root blod by default
 *
 * @since BuddyPress (1.6)
 *
 * @param string $capability Capability or role name.
 * @param int $blog_id Blog ID
 * @return bool
 */
function bp_current_user_can( $capability, $blog_id = 0 ) {

	// Use root blog if no ID passed
	if ( empty( $blog_id ) )
		$blog_id = bp_get_root_blog_id();

	$retval = current_user_can_for_blog( $blog_id, $capability );

	return (bool) apply_filters( 'bp_current_user_can', $retval, $capability, $blog_id );
}

/**
 * Temporary implementation of 'bp_moderate' cap
 *
 * In BuddyPress 1.6, the 'bp_moderate' cap was introduced. In order to enforce that
 * bp_current_user_can( 'bp_moderate' ) always returns true for Administrators, we must manually
 * add the 'bp_moderate' cap to the list of user caps for Admins.
 *
 * Note that this level of enforcement is only necessary in the case of non-Multisite. This is
 * because WordPress automatically assigns every capability - and thus 'bp_moderate' - to Super
 * Admins on a Multisite installation. See WP_User::has_cap().
 *
 * This implementation of 'bp_moderate' is temporary, until BuddyPress properly matches caps to
 * roles and stores them in the database. Plugin authors: Do not use this function.
 *
 * @since BuddyPress (1.6)
 * @see WP_User::has_cap()
 *
 * @param array $allcaps The caps that WP associates with the given role
 * @param array $caps The caps being tested for in WP_User::has_cap()
 * @param array $args Miscellaneous arguments passed to the user_has_cap filter
 * @return array $allcaps The user's cap list, with 'bp_moderate' appended, if relevant
 */
function _bp_enforce_bp_moderate_cap_for_admins( $allcaps, $caps, $args ) {
	if ( in_array( 'bp_moderate', $caps ) &&   // We only care if checking for bp_moderate
	     !in_array( 'do_not_allow', $caps ) && // 'do_not_allow' overrides everything else
	     !is_multisite() &&                    // Check not necessary on Multisite
	     isset( $allcaps['delete_users'] ) )   // Mimicking WP's check for Administrator status
	{
		$allcaps['bp_moderate'] = true;
	}

	return $allcaps;
}
add_filter( 'user_has_cap', '_bp_enforce_bp_moderate_cap_for_admins', 10, 3 );

?>
