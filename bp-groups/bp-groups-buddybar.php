<?php

function bp_groups_adminbar_admin_menu() {
	global $bp, $groups_template;

	if ( empty( $bp->groups->current_group ) )
		return false;

	// Don't show this menu to non site admins or if you're viewing your own profile
	if ( !current_user_can( 'edit_users' ) || !is_super_admin() || ( !$bp->is_item_admin && !$bp->is_item_mod ) )
		return false; ?>

	<li id="bp-adminbar-adminoptions-menu">
		<a href="<?php bp_groups_action_link( 'admin' ); ?>"><?php _e( 'Admin Options', 'buddypress' ); ?></a>

		<ul>
			<li><a href="<?php bp_groups_action_link( 'admin/edit-details' ); ?>"><?php _e( 'Edit Details', 'buddypress' ); ?></a></li>
			
			<li><a href="<?php bp_groups_action_link( 'admin/group-settings' );  ?>"><?php _e( 'Group Settings', 'buddypress' ); ?></a></li>
			
			<li><a href="<?php bp_groups_action_link( 'admin/group-avatar' ); ?>"><?php _e( 'Group Avatar', 'buddypress' ); ?></a></li>

			<?php if ( bp_is_active( 'friends' ) ) : ?>

				<li><a href="<?php bp_groups_action_link( 'send-invites' ); ?>"><?php _e( 'Manage Invitations', 'buddypress' ); ?></a></li>

			<?php endif; ?>

			<li><a href="<?php bp_groups_action_link( 'admin/manage-members' ); ?>"><?php _e( 'Manage Members', 'buddypress' ); ?></a></li>

			<?php if ( $bp->groups->current_group->status == 'private' ) : ?>

				<li><a href="<?php bp_groups_action_link( 'admin/membership-requests' ); ?>"><?php _e( 'Membership Requests', 'buddypress' ); ?></a></li>

			<?php endif; ?>

			<li><a class="confirm" href="<?php echo wp_nonce_url( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/delete-group/', 'groups_delete_group' ); ?>&amp;delete-group-button=1&amp;delete-group-understand=1"><?php _e( "Delete Group", 'buddypress' ) ?></a></li>
			
			<?php /* These advanced admin items have been removed for the 1.3 release */ ?>
			<?php /*
			<li>
				<li><a href="<?php bp_groups_action_link( 'admin/edit-details' ); ?>"><?php _e( 'Admin', 'buddypress' ); ?></a>

				<ul>
					<li><a href="<?php bp_groups_action_link( 'admin/edit-details' ); ?>"><?php _e( 'Edit Details', 'buddypress' ); ?></a></li>
					<li><a href="<?php bp_groups_action_link( 'admin/group-settings' );  ?>"><?php _e( 'Group Settings', 'buddypress' ); ?></a></li>
					<li><a href="<?php bp_groups_action_link( 'admin/group-avatar' ); ?>"><?php _e( 'Group Avatar', 'buddypress' ); ?></a></li>

					<?php if ( bp_is_active( 'friends' ) ) : ?>

						<li><a href="<?php bp_groups_action_link( 'send-invites' ); ?>"><?php _e( 'Manage Invitations', 'buddypress' ); ?></a></li>

					<?php endif; ?>

					<li><a href="<?php bp_groups_action_link( 'admin/manage-members' ); ?>"><?php _e( 'Manage Members', 'buddypress' ); ?></a></li>

					<?php if ( $bp->groups->current_group->status == 'private' ) : ?>

						<li><a href="<?php bp_groups_action_link( 'admin/membership-requests' ); ?>"><?php _e( 'Membership Requests', 'buddypress' ); ?></a></li>

					<?php endif; ?>

					<li><a class="confirm" href="<?php echo wp_nonce_url( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/delete-group/', 'groups_delete_group' ); ?>&amp;delete-group-button=1&amp;delete-group-understand=1"><?php _e( "Delete Group", 'buddypress' ) ?></a></li>
				</ul>
			</li>

			<?php if ( bp_is_active( 'activity' ) ) : ?>

				<li>
					<a href="<?php bp_groups_action_link( 'activity' ); ?>"><?php _e( 'Activity', 'buddypress' ); ?></a>
					<ul>
						<li><a href="<?php bp_groups_action_link( 'activity/admin', array( 'clear' => 'all' ), true ); ?>" class="confirm"><?php _e( "Delete All Activity", 'buddypress' ); ?></a></li>
					</ul>
				</li>

			<?php endif; ?>

			<?php if ( bp_is_active( 'forums' ) ) : ?>

				<li>
					<a href="<?php bp_groups_action_link( 'forums' ); ?>"><?php _e( 'Forums', 'buddypress' ); ?></a>
					<ul>
						<li><a href="<?php bp_groups_action_link( 'forums/admin', array( 'clear' => 'all' ), true ); ?>" class="confirm"><?php _e( "Delete Forum Contents", 'buddypress' ); ?></a></li>
					</ul>
				</li>

			<?php endif; ?>

			*/ ?>

			<?php do_action( 'bp_groups_adminbar_admin_menu' ) ?>

		</ul>
	</li>

	<?php
}
add_action( 'bp_adminbar_menus', 'bp_groups_adminbar_admin_menu', 20 );

?>
