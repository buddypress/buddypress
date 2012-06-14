<?php

/**
 * BuddyPress Groups Activity Functions
 *
 * These functions handle the recording, deleting and formatting of activity
 * for the user and for this specific component.
 *
 * @package BuddyPress
 * @subpackage GroupsActivity
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

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

	// Set the default for hide_sitewide by checking the status of the group
	$hide_sitewide = false;
	if ( !empty( $args['item_id'] ) ) {
		if ( bp_get_current_group_id() == $args['item_id'] ) {
			$group = groups_get_current_group();
		} else {
			$group = groups_get_group( array( 'group_id' => $args['item_id'] ) );
		}

		if ( isset( $group->status ) && 'public' != $group->status ) {
			$hide_sitewide = true;
		}
	}

	$defaults = array (
		'id'                => false,
		'user_id'           => bp_loggedin_user_id(),
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

	if ( empty( $group_id ) )
		$group_id = $bp->groups->current_group->id;

	if ( empty( $group_id ) )
		return false;

	groups_update_groupmeta( $group_id, 'last_activity', bp_core_current_time() );
}
add_action( 'groups_leave_group',          'groups_update_last_activity' );
add_action( 'groups_created_group',        'groups_update_last_activity' );
add_action( 'groups_new_forum_topic',      'groups_update_last_activity' );
add_action( 'groups_new_forum_topic_post', 'groups_update_last_activity' );

function groups_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

	switch ( $action ) {
		case 'new_membership_request':
			$group_id = $secondary_item_id;
			$requesting_user_id = $item_id;

			$group = groups_get_group( array( 'group_id' => $group_id ) );
			$group_link = bp_get_group_permalink( $group );

			// Set up the string and the filter
			// Because different values are passed to the filters, we'll return the
			// values inline
			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( '%1$d new membership requests for the group "%2$s"', 'buddypress' ), (int) $total_items, $group->name );
				$filter = 'bp_groups_multiple_new_membership_requests_notification';
				$notification_link = $group_link . 'admin/membership-requests/?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . __( 'Group Membership Requests', 'buddypress' ) . '">' . $text . '</a>', $group_link, $total_items, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $total_items, $group->name, $text, $notification_link );
				}
			} else {
				$user_fullname = bp_core_get_user_displayname( $requesting_user_id );
				$text = sprintf( __( '%s requests group membership', 'buddypress' ), $user_fullname );
				$filter = 'bp_groups_single_new_membership_request_notification';
				$notification_link = $group_link . 'admin/membership-requests/?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . sprintf( __( '%s requests group membership', 'buddypress' ), $user_fullname ) . '">' . $text . '</a>', $group_link, $user_fullname, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $user_fullname, $group->name, $text, $notification_link );
				}
			}

			break;

		case 'membership_request_accepted':
			$group_id = $item_id;

			$group = groups_get_group( array( 'group_id' => $group_id ) );
			$group_link = bp_get_group_permalink( $group );

			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( '%d accepted group membership requests', 'buddypress' ), (int) $total_items, $group->name );
				$filter = 'bp_groups_multiple_membership_request_accepted_notification';
				$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . __( 'Groups', 'buddypress' ) . '">' . $text . '</a>', $total_items, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $total_items, $group->name, $text, $notification_link );
				}
			} else {
				$text = sprintf( __( 'Membership for group "%s" accepted', 'buddypress' ), $group->name );
				$filter = 'bp_groups_single_membership_request_accepted_notification';
				$notification_link = $group_link . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $group->name, $text, $notification_link );
				}
			}

			break;

		case 'membership_request_rejected':
			$group_id = $item_id;

			$group = groups_get_group( array( 'group_id' => $group_id ) );
			$group_link = bp_get_group_permalink( $group );

			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( '%d rejected group membership requests', 'buddypress' ), (int) $total_items, $group->name );
				$filter = 'bp_groups_multiple_membership_request_rejected_notification';
				$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . __( 'Groups', 'buddypress' ) . '">' . $text . '</a>', $total_items, $group->name );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $total_items, $group->name, $text, $notification_link );
				}
			} else {
				$text = sprintf( __( 'Membership for group "%s" rejected', 'buddypress' ), $group->name );
				$filter = 'bp_groups_single_membership_request_rejected_notification';
				$notification_link = $group_link . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $group->name, $text, $notification_link );
				}
			}

			break;

		case 'member_promoted_to_admin':
			$group_id = $item_id;

			$group = groups_get_group( array( 'group_id' => $group_id ) );
			$group_link = bp_get_group_permalink( $group );

			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( 'You were promoted to an admin in %d groups', 'buddypress' ), (int) $total_items );
				$filter = 'bp_groups_multiple_member_promoted_to_admin_notification';
				$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . __( 'Groups', 'buddypress' ) . '">' . $text . '</a>', $total_items, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $total_items, $text, $notification_link );
				}
			} else {
				$text = sprintf( __( 'You were promoted to an admin in the group "%s"', 'buddypress' ), $group->name );
				$filter = 'bp_groups_single_member_promoted_to_admin_notification';
				$notification_link = $group_link . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $group->name, $text, $notification_link );
				}
			}

			break;

		case 'member_promoted_to_mod':
			$group_id = $item_id;

			$group = groups_get_group( array( 'group_id' => $group_id ) );
			$group_link = bp_get_group_permalink( $group );

			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( 'You were promoted to a mod in %d groups', 'buddypress' ), (int) $total_items );
				$filter = 'bp_groups_multiple_member_promoted_to_mod_notification';
				$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . __( 'Groups', 'buddypress' ) . '">' . $text . '</a>', $total_items, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $total_items, $text, $notification_link );
				}
			} else {
				$text = sprintf( __( 'You were promoted to a mod in the group "%s"', 'buddypress' ), $group->name );
				$filter = 'bp_groups_single_member_promoted_to_mod_notification';
				$notification_link = $group_link . '?n=1';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $group->name, $text, $notification_link );
				}
			}

			break;

		case 'group_invite':
			$group_id = $item_id;
			$group = groups_get_group( array( 'group_id' => $group_id ) );
			$group_link = bp_get_group_permalink( $group );

			$notification_link = bp_loggedin_user_domain() . bp_get_groups_slug() . '/invites/?n=1';

			if ( (int) $total_items > 1 ) {
				$text = sprintf( __( 'You have %d new group invitations', 'buddypress' ), (int) $total_items );
				$filter = 'bp_groups_multiple_group_invite_notification';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '" title="' . __( 'Group Invites', 'buddypress' ) . '">' . $text . '</a>', $total_items, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $total_items, $text, $notification_link );
				}
			} else {
				$text = sprintf( __( 'You have an invitation to the group: %s', 'buddypress' ), $group->name );
				$filter = 'bp_groups_single_group_invite_notification';

				if ( 'string' == $format ) {
					return apply_filters( $filter, '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {
					return apply_filters( $filter, array(
						'link' => $notification_link,
						'text' => $text
					), $group_link, $group->name, $text, $notification_link );
				}
			}

			break;
	}

	do_action( 'groups_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return false;
}

?>
