<?php
/**
 * BuddyPress Groups Admin Bar
 *
 * Handles the groups functions related to the WordPress Admin Bar
 *
 * @package BuddyPress
 * @subpackage Groups
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Adjust the admin bar menus based on which WordPress version this is
 *
 * @since BuddyPress (1.5.2)
 */
function bp_groups_admin_bar_version_check() {
	if ( '3.2' == bp_get_major_wp_version() ) {
		add_action( 'bp_setup_admin_bar', 'bp_groups_group_admin_menu', 99 );
	} elseif ( '3.3' == bp_get_major_wp_version() ) {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu',  80  );
		add_action( 'admin_bar_menu', 'bp_groups_group_admin_menu', 400 );
	}
}
add_action( 'admin_bar_menu', 'bp_groups_admin_bar_version_check', 4 );

/**
 * Adds the Group Admin top-level menu to group pages
 *
 * @package BuddyPress
 * @since 1.5
 *
 * @todo Add dynamic menu items for group extensions
 */
function bp_groups_group_admin_menu() {
	global $wp_admin_bar, $bp;

	// Only show if viewing a group
	if ( !bp_is_group() )
		return false;

	// Only show this menu to group admins and super admins
	if ( !is_super_admin() && !bp_group_is_admin() )
		return false;

	if ( '3.2' == bp_get_major_wp_version() ) {

		// Group avatar
		$avatar = bp_core_fetch_avatar( array(
			'object'     => 'group',
			'type'       => 'thumb',
			'avatar_dir' => 'group-avatars',
			'item_id'    => $bp->groups->current_group->id,
			'width'      => 16,
			'height'     => 16
		) );

		// Unique ID for the 'My Account' menu
		$bp->group_admin_menu_id = ( ! empty( $avatar ) ) ? 'group-admin-with-avatar' : 'group-admin';

		// Add the top-level Group Admin button
		$wp_admin_bar->add_menu( array(
			'id'    => $bp->group_admin_menu_id,
			'title' => $avatar . bp_get_current_group_name(),
			'href'  => bp_get_group_permalink( $bp->groups->current_group )
		) );

	} elseif ( '3.3' == bp_get_major_wp_version() ) {
		
		// Unique ID for the 'My Account' menu
		$bp->group_admin_menu_id = 'group-admin';

		// Add the top-level Group Admin button
		$wp_admin_bar->add_menu( array(
			'id'    => $bp->group_admin_menu_id,
			'title' => __( 'Edit Group', 'buddypress' ),
			'href'  => bp_get_group_permalink( $bp->groups->current_group )
		) );
	}

	// Group Admin > Edit details
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->group_admin_menu_id,
		'id'     => 'edit-details',
		'title'  => __( 'Edit Details', 'buddypress' ),
		'href'   =>  bp_get_groups_action_link( 'admin/edit-details' )
	) );

	// Group Admin > Group settings
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->group_admin_menu_id,
		'id'     => 'group-settings',
		'title'  => __( 'Edit Settings', 'buddypress' ),
		'href'   =>  bp_get_groups_action_link( 'admin/group-settings' )
	) );

	// Group Admin > Group avatar
	if ( !(int)bp_get_option( 'bp-disable-avatar-uploads' ) ) {
		$wp_admin_bar->add_menu( array(
			'parent' => $bp->group_admin_menu_id,
			'id'     => 'group-avatar',
			'title'  => __( 'Edit Avatar', 'buddypress' ),
			'href'   =>  bp_get_groups_action_link( 'admin/group-avatar' )
		) );
	}

	// Group Admin > Manage invitations
	if ( bp_is_active( 'friends' ) ) {
		$wp_admin_bar->add_menu( array(
			'parent' => $bp->group_admin_menu_id,
			'id'     => 'manage-invitations',
			'title'  => __( 'Manage Invitations', 'buddypress' ),
			'href'   =>  bp_get_groups_action_link( 'send-invites' )
		) );
	}

	// Group Admin > Manage members
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->group_admin_menu_id,
		'id'     => 'manage-members',
		'title'  => __( 'Manage Members', 'buddypress' ),
		'href'   =>  bp_get_groups_action_link( 'admin/manage-members' )
	) );

	// Group Admin > Membership Requests
	if ( bp_get_group_status( $bp->groups->current_group ) == 'private' ) {
		$wp_admin_bar->add_menu( array(
			'parent' => $bp->group_admin_menu_id,
			'id'     => 'membership-requests',
			'title'  => __( 'Membership Requests', 'buddypress' ),
			'href'   =>  bp_get_groups_action_link( 'admin/membership-requests' )
		) );
	}

	// Delete Group
	$wp_admin_bar->add_menu( array(
		'parent' => $bp->group_admin_menu_id,
		'id'     => 'delete-group',
		'title'  => __( 'Delete Group', 'buddypress' ),
		'href'   =>  bp_get_groups_action_link( 'admin/delete-group' )
	) );
}

?>