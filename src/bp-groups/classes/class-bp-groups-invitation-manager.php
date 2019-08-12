<?php
/**
 * Group invitations class.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 5.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Group invitations class.
 *
 * An extension of the core Invitations class that adapts the
 * core logic to accommodate group invitation behavior.
 *
 * @since 5.0.0
 */
class BP_Groups_Invitation_Manager extends BP_Invitation_Manager {
	/**
	 * Construct parameters.
	 *
	 * @since 5.0.0
	 *
	 * @param array|string $args.
	 */
	public function __construct( $args = '' ) {
		parent::__construct();
	}

	/**
	 * This is where custom actions are added to run when notifications of an
	 * invitation or request need to be generated & sent.
	 *
	 * @since 5.0.0
	 *
	 * @param int $id The ID of the invitation to mark as sent.
	 * @return bool True on success, false on failure.
	 */
	public function run_send_action( BP_Invitation $invitation ) {
		// Notify group admins of the pending request
		if ( 'request' === $invitation->type ) {
			$admins = groups_get_group_admins( $invitation->item_id );

			foreach ( $admins as $admin ) {
				groups_notification_new_membership_request( $invitation->user_id, $admin->user_id, $invitation->item_id, $invitation->id );
			}
			return true;

		// Notify the invitee of the invitation.
		} else {
			$group = groups_get_group( $invitation->item_id );
			groups_notification_group_invites( $group, $invitation->user_id, $invitation->inviter_id );
			return true;
		}
	}

	/**
	 * This is where custom actions are added to run when an invitation
	 * or request is accepted.
	 *
	 * @since 5.0.0
	 *
	 * @param string $type Are we accepting an invitation or request?
	 * @param array  $r    Parameters that describe the invitation being accepted.
	 * @return bool True on success, false on failure.
	 */
	public function run_acceptance_action( $type = 'invite', $r  ) {
		// If the user is already a member (because BP at one point allowed two invitations to
		// slip through), return early.
		if ( groups_is_user_member( $r['user_id'], $r['item_id'] ) ) {
			return true;
		}

		// Create the new membership
		$member = new BP_Groups_Member( $r['user_id'], $r['item_id'] );

		if ( 'request' === $type ) {
			$member->accept_request();
		} else {
			$member->accept_invite();
		}

		if ( ! $member->save() ) {
			return false;
		}

		if ( 'request' === $type ) {
			/**
			 * Fires after a group membership request has been accepted.
			 *
			 * @since 1.0.0
			 *
			 * @param int  $user_id  ID of the user who accepted membership.
			 * @param int  $group_id ID of the group that was accepted membership to.
			 * @param bool $value    If membership was accepted.
			 */
			do_action( 'groups_membership_accepted', $r['user_id'], $r['item_id'], true );
		} else {
			// Get an inviter_id from the invitation.
			$invites = groups_get_invites( $r );
			$inviter_id = 0;
			if ( $invites ) {
				$inviter_id = current( $invites )->inviter_id;
			}

			/**
			 * Fires after a user has accepted a group invite.
			 *
			 * @since 1.0.0
			 * @since 2.8.0 The $inviter_id arg was added.
			 *
			 * @param int $user_id    ID of the user who accepted the group invite.
			 * @param int $group_id   ID of the group being accepted to.
			 * @param int $inviter_id ID of the user who invited this user to the group.
			 */
			do_action( 'groups_accept_invite', $r['user_id'], $r['item_id'], $inviter_id );
		}

		// Modify group meta.
		groups_update_groupmeta( $r['item_id'], 'last_activity', bp_core_current_time() );

		return true;
	}

	/**
	 * With group invitations, we don't need to keep the old record, so we delete rather than
	 * mark invitations as "accepted."
	 *
	 * @since 5.0.0
	 *
	 * @see BP_Invitation::mark_accepted_by_data()
	 *      for a description of arguments.
	 *
	 * @param array $args.
	 */
	public function mark_accepted( $args ) {
		// Delete all existing invitations/requests to this group for this user.
		$this->delete( array(
			'user_id' => $args['user_id'],
			'item_id' => $args['item_id'],
			'type'    => 'all'
		) );
	}

	/**
	 * Should this invitation be created?
	 *
	 * @since 5.0.0
	 *
	 * @param array $args.
	 * @return bool
	 */
	public function allow_invitation( $args ) {
		// Does the inviter have this capability?
		if ( ! bp_user_can( $args['inviter_id'], 'groups_send_invitation', array( 'group_id' => $args['item_id'] ) ) ) {
			return false;
		}

		// Is the invited user eligible to receive an invitation?
		if ( ! bp_user_can( $args['user_id'], 'groups_receive_invitation', array( 'group_id' => $args['item_id'] ) ) ) {
			return false;
		}

		// Prevent duplicated invitations.
		if ( groups_check_has_invite_from_user( $args['user_id'], $args['item_id'], $args['inviter_id'], 'all' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Should this request be created?
	 *
	 * @since 5.0.0
	 *
	 * @param array $args.
	 * @return bool.
	 */
	public function allow_request( $args ) {
		// Does the requester have this capability? (Also checks for duplicates.)
		if ( ! bp_user_can( $args['user_id'], 'groups_request_membership', array( 'group_id' => $args['item_id'] ) ) ) {
			return false;
		}

		return true;
	}
}
