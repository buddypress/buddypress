<?php

/**
 * BuddyPress Groups Activity & Notification Functions
 *
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 *
 * @package BuddyPress
 * @subpackage Groups
 */

function groups_register_activity_actions() {
	global $bp;

	if ( !bp_is_active( 'activity' ) )
		return false;

	bp_activity_set_action( $bp->groups->id, 'created_group',   __( 'Created a group',       'buddypress' ) );
	bp_activity_set_action( $bp->groups->id, 'joined_group',    __( 'Joined a group',        'buddypress' ) );
	bp_activity_set_action( $bp->groups->id, 'new_forum_topic', __( 'New group forum topic', 'buddypress' ) );
	bp_activity_set_action( $bp->groups->id, 'new_forum_post',  __( 'New group forum post',  'buddypress' ) );

	do_action( 'groups_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'groups_register_activity_actions' );

function groups_record_activity( $args = '' ) {
	global $bp;

	if ( !bp_is_active( 'activity' ) )
		return false;

	// If the group is not public, hide the activity sitewide.
	if ( isset( $bp->groups->current_group->status ) && 'public' == $bp->groups->current_group->status )
		$hide_sitewide = false;
	else
		$hide_sitewide = true;

	$defaults = array (
		'id'                => false,
		'user_id'           => $bp->loggedin_user->id,
		'action'            => '',
		'content'           => '',
		'primary_link'      => '',
		'component'         => $bp->groups->id,
		'type'              => false,
		'item_id'           => false,
		'secondary_item_id' => false,
		'recorded_time'     => bp_core_current_time(),
		'hide_sitewide'     => $hide_sitewide
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	return bp_activity_add( array( 'id' => $id, 'user_id' => $user_id, 'action' => $action, 'content' => $content, 'primary_link' => $primary_link, 'component' => $component, 'type' => $type, 'item_id' => $item_id, 'secondary_item_id' => $secondary_item_id, 'recorded_time' => $recorded_time, 'hide_sitewide' => $hide_sitewide ) );
}

function groups_update_last_activity( $group_id = 0 ) {
	global $bp;

	if ( !$group_id )
		$group_id = $bp->groups->current_group->id;

	if ( !$group_id )
		return false;

	groups_update_groupmeta( $group_id, 'last_activity', bp_core_current_time() );
}
add_action( 'groups_leave_group', 'groups_update_last_activity' );
add_action( 'groups_created_group', 'groups_update_last_activity' );
add_action( 'groups_new_forum_topic', 'groups_update_last_activity' );
add_action( 'groups_new_forum_topic_post', 'groups_update_last_activity' );

function groups_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;

	switch ( $action ) {
		case 'new_membership_request':
			$group_id = $secondary_item_id;
			$requesting_user_id = $item_id;

			$group = new BP_Groups_Group( $group_id );

			$group_link = bp_get_group_permalink( $group );

			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_groups_multiple_new_membership_requests_notification', '<a href="' . $group_link . '/admin/membership-requests/?n=1" title="' . __( 'Group Membership Requests', 'buddypress' ) . '">' . sprintf( __( '%1$d new membership requests for the group "%2$s"', 'buddypress' ), (int)$total_items, $group->name ) . '</a>', $group_link, $total_items, $group->name );
			} else {
				$user_fullname = bp_core_get_user_displayname( $requesting_user_id );
				return apply_filters( 'bp_groups_single_new_membership_request_notification', '<a href="' . $group_link . 'admin/membership-requests/?n=1" title="' . sprintf( __( '%s requests group membership', 'buddypress' ), $user_fullname ) . '">' . sprintf( __( '%1$s requests membership for the group "%2$s"', 'buddypress' ), $user_fullname, $group->name ) . '</a>', $group_link, $user_fullname, $group->name );
			}
			break;

		case 'membership_request_accepted':
			$group_id = $item_id;

			$group = new BP_Groups_Group( $group_id );
			$group_link = bp_get_group_permalink( $group );

			if ( (int)$total_items > 1 )
				return apply_filters( 'bp_groups_multiple_membership_request_accepted_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '/?n=1" title="' . __( 'Groups', 'buddypress' ) . '">' . sprintf( __( '%d accepted group membership requests', 'buddypress' ), (int)$total_items, $group->name ) . '</a>', $total_items, $group->name );
			else
				return apply_filters( 'bp_groups_single_membership_request_accepted_notification', '<a href="' . $group_link . '?n=1">' . sprintf( __( 'Membership for group "%s" accepted', 'buddypress' ), $group->name ) . '</a>', $group_link, $group->name );

			break;

		case 'membership_request_rejected':
			$group_id = $item_id;

			$group = new BP_Groups_Group( $group_id );
			$group_link = bp_get_group_permalink( $group );

			if ( (int)$total_items > 1 )
				return apply_filters( 'bp_groups_multiple_membership_request_rejected_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '/?n=1" title="' . __( 'Groups', 'buddypress' ) . '">' . sprintf( __( '%d rejected group membership requests', 'buddypress' ), (int)$total_items, $group->name ) . '</a>', $total_items, $group->name );
			else
				return apply_filters( 'bp_groups_single_membership_request_rejected_notification', '<a href="' . $group_link . '?n=1">' . sprintf( __( 'Membership for group "%s" rejected', 'buddypress' ), $group->name ) . '</a>', $group_link, $group->name );

			break;

		case 'member_promoted_to_admin':
			$group_id = $item_id;

			$group = new BP_Groups_Group( $group_id );
			$group_link = bp_get_group_permalink( $group );

			if ( (int)$total_items > 1 )
				return apply_filters( 'bp_groups_multiple_member_promoted_to_admin_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '/?n=1" title="' . __( 'Groups', 'buddypress' ) . '">' . sprintf( __( 'You were promoted to an admin in %d groups', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );
			else
				return apply_filters( 'bp_groups_single_member_promoted_to_admin_notification', '<a href="' . $group_link . '?n=1">' . sprintf( __( 'You were promoted to an admin in the group %s', 'buddypress' ), $group->name ) . '</a>', $group_link, $group->name );

			break;

		case 'member_promoted_to_mod':
			$group_id = $item_id;

			$group = new BP_Groups_Group( $group_id );
			$group_link = bp_get_group_permalink( $group );

			if ( (int)$total_items > 1 )
				return apply_filters( 'bp_groups_multiple_member_promoted_to_mod_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '/?n=1" title="' . __( 'Groups', 'buddypress' ) . '">' . sprintf( __( 'You were promoted to a mod in %d groups', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );
			else
				return apply_filters( 'bp_groups_single_member_promoted_to_mod_notification', '<a href="' . $group_link . '?n=1">' . sprintf( __( 'You were promoted to a mod in the group %s', 'buddypress' ), $group->name ) . '</a>', $group_link, $group->name );

			break;

		case 'group_invite':
			$group_id = $item_id;

			$group = new BP_Groups_Group( $group_id );
			$user_url = bp_core_get_user_domain( $bp->loggedin_user->id );

			if ( (int)$total_items > 1 )
				return apply_filters( 'bp_groups_multiple_group_invite_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '/invites/?n=1" title="' . __( 'Group Invites', 'buddypress' ) . '">' . sprintf( __( 'You have %d new group invitations', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );
			else
				return apply_filters( 'bp_groups_single_group_invite_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '/invites/?n=1" title="' . __( 'Group Invites', 'buddypress' ) . '">' . sprintf( __( 'You have an invitation to the group: %s', 'buddypress' ), $group->name ) . '</a>', $group->name );

			break;
	}

	do_action( 'groups_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return false;
}


?>
