<?php
/**
 * Membership invitations class.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 8.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Membership invitations class.
 *
 * An extension of the core Invitations class that adapts the
 * core logic to accommodate site membership invitation behavior.
 *
 * @since 8.0.0
 */
class BP_Members_Invitation_Manager extends BP_Invitation_Manager {
	/**
	 * Construct parameters.
	 *
	 * @since 8.0.0
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
	 * @since 8.0.0
	 *
	 * @param obj BP_Invitation $invitation The invitation to send.
	 * @return bool
	 */
	public function run_send_action( BP_Invitation $invitation ) {
		// Notify site admins of the pending request
		if ( 'request' === $invitation->type ) {
			// Coming soon to a BuddyPress near you!
			return true;

		// Notify the invitee of the invitation.
		} else {
			// Stop if the invitation has already been accepted.
			if ( $invitation->accepted ) {
				return false;
			}

			$invite_url = esc_url(
				add_query_arg(
					array(
						'inv' => $invitation->id,
						'ih'  => bp_members_invitations_get_hash( $invitation ),
					),
					bp_get_signup_page()
				)
			);
			$unsubscribe_args = array(
				'user_id'           => 0,
				'email_address'     => $invitation->invitee_email,
				'member_id'         => $invitation->inviter_id,
				'notification_type' => 'bp-members-invitation',
			);

			$args = array(
				'tokens' => array(
					'inviter.name'      => bp_core_get_userlink( $invitation->inviter_id, true, false ),
					'inviter.url'       => bp_members_get_user_url( $invitation->inviter_id ),
					'inviter.id'        => $invitation->inviter_id,
					'invite.accept_url' => esc_url( $invite_url ),
					'usermessage'       => wp_kses( $invitation->content, array() ),
					'unsubscribe'       => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
				),
			);

			return bp_send_email( 'bp-members-invitation', $invitation->invitee_email, $args );
		}
	}

	/**
	 * This is where custom actions are added to run when an invitation
	 * or request is accepted.
	 *
	 * @since 8.0.0
	 *
	 * @param string $type Are we accepting an invitation or request?
	 * @param array  $r    Parameters that describe the invitation being accepted.
	 * @return bool
	 */
	public function run_acceptance_action( $type, $r ) {
		if ( ! $type || ! in_array( $type, array( 'request', 'invite' ), true ) ) {
			return false;
		}

		if ( 'invite' === $type ) {

			$invites = $this->get_invitations( $r );
			if ( ! $invites ) {
				return;
			}

			foreach ( $invites as $invite ) {
				// Add the accepted invitation ID to the user's meta.
				$new_user = get_user_by( 'email', $invite->invitee_email );
				bp_update_user_meta( $new_user->ID, 'accepted_members_invitation', $invite->id );

				// We will mark all invitations to this user as "accepted."
				if ( ! empty( $invite->invitee_email )  ) {
					$args  = array(
						'invitee_email' => $invite->invitee_email,
						'item_id'       => get_current_network_id(),
						'type'          => 'all'
					);
					$this->mark_accepted( $args );
				}

				/**
				 * Fires after a user has accepted a site membership invite.
				 *
				 * @since 8.0.0
				 *
				 * @param BP_Invitation $invite     Invitation that was accepted.
				 * @param WP_user       $new_user   ID of the user who accepted the membership invite.
				 * @param int           $inviter_id ID of the user who invited this user to the site.
				 */
				do_action( 'members_invitations_invite_accepted', $invite, $new_user, $invite->inviter_id );
			}
		}

		return true;
	}

	/**
	 * Should this invitation be created?
	 *
	 * @since 8.0.0
	 *
	 * @param array $args Array of arguments.
	 * @return bool
	 */
	public function allow_invitation( $args ) {
		// Does the inviter have this capability?
		if ( ! bp_user_can( $args['inviter_id'], 'bp_members_send_invitation' ) ) {
			return false;
		}

		// Is the invited user eligible to receive an invitation? Hasn't opted out?
		if ( ! bp_user_can( 0, 'bp_members_receive_invitation', $args ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Should this request be created?
	 *
	 * @since 8.0.0
	 *
	 * @param array $args.
	 * @return bool.
	 */
	public function allow_request( $args ) {
		// Does the requester have this capability?
		if ( ! bp_user_can( 0, 'bp_network_request_membership', $args ) ) {
			return false;
		}

		return true;
	}
}
