<?php

/**
 * BuddyPress Groups BuddyBar
 *
 * @package BuddyPress
 * @subpackage GroupsBuddyBar
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Adds menu items to the BuddyBar
 *
 * @since BuddyPress (1.0)
 * @global BuddyPress $bp
 * @return False if no menu
 */
function bp_groups_adminbar_admin_menu() {
	global $bp;

	if ( empty( $bp->groups->current_group ) )
		return false;

	// Only group admins and site admins can see this menu
	if ( !current_user_can( 'edit_users' ) && !bp_current_user_can( 'bp_moderate' ) && !bp_is_item_admin() )
		return false; ?>

	<li id="bp-adminbar-adminoptions-menu">
		<a href="<?php bp_groups_action_link( 'admin' ); ?>"><?php _e( 'Admin Options', 'buddypress' ); ?></a>

		<ul>
			<li><a href="<?php bp_groups_action_link( 'admin/edit-details' ); ?>"><?php _e( 'Edit Details', 'buddypress' ); ?></a></li>

			<li><a href="<?php bp_groups_action_link( 'admin/group-settings' );  ?>"><?php _e( 'Group Settings', 'buddypress' ); ?></a></li>

			<?php if ( !(int)bp_get_option( 'bp-disable-avatar-uploads' ) ) : ?>

				<li><a href="<?php bp_groups_action_link( 'admin/group-avatar' ); ?>"><?php _e( 'Group Avatar', 'buddypress' ); ?></a></li>

			<?php endif; ?>

			<?php if ( bp_is_active( 'friends' ) ) : ?>

				<li><a href="<?php bp_groups_action_link( 'send-invites' ); ?>"><?php _e( 'Manage Invitations', 'buddypress' ); ?></a></li>

			<?php endif; ?>

			<li><a href="<?php bp_groups_action_link( 'admin/manage-members' ); ?>"><?php _e( 'Manage Members', 'buddypress' ); ?></a></li>

			<?php if ( $bp->groups->current_group->status == 'private' ) : ?>

				<li><a href="<?php bp_groups_action_link( 'admin/membership-requests' ); ?>"><?php _e( 'Membership Requests', 'buddypress' ); ?></a></li>

			<?php endif; ?>

			<li><a class="confirm" href="<?php echo wp_nonce_url( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/delete-group/', 'groups_delete_group' ); ?>&amp;delete-group-button=1&amp;delete-group-understand=1"><?php _e( "Delete Group", 'buddypress' ) ?></a></li>

			<?php do_action( 'bp_groups_adminbar_admin_menu' ) ?>

		</ul>
	</li>

	<?php
}
add_action( 'bp_adminbar_menus', 'bp_groups_adminbar_admin_menu', 20 );

?>
