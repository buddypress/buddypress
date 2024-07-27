<?php
/**
 * Core invitations class.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 5.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP Invitations class.
 *
 * Extend it to manage your class's invitations.
 * Your extension class, must, at a minimum, provide the
 * run_send_action() and run_acceptance_action() methods.
 *
 * @since 5.0.0
 */
abstract class BP_Invitation_Manager {

	/**
	 * The name of the related class.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	protected $class_name;

	/**
	 * Construct parameters.
	 *
	 * @since 5.0.0
	 */
	public function __construct() {
		$this->class_name = get_class( $this );
	}

	/**
	 * Get the invitations table name.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public static function get_table_name() {
		return buddypress()->members->table_name_invitations;
	}

	/** Create ********************************************************************/

	/**
	 * Add an invitation to a specific user, from a specific user, related to a
	 * specific class.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args {
	 *     Array of arguments describing the invitation. All are optional.
	 *     @type int    $user_id           ID of the invited user.
	 *     @type int    $inviter_id        ID of the user who created the invitation.
	 *     @type string $invitee_email     Email address of the invited user.
	 *     @type int    $item_id           ID associated with the invitation and class.
	 *     @type int    $secondary_item_id Secondary ID associated with the
	 *                                     invitation and class.
	 *     @type string $type              Type of record this is: 'invite' or 'request'.
	 *     @type string $content           Extra information provided by the requester
	 *                                     or inviter.
	 *     @type string $date_modified     Date the invitation was last modified.
	 *     @type int    $send_invite       Should the invitation also be sent, or is it a
	 *                                     draft invite?
	 * }
	 * @return int|bool ID of the newly created invitation on success, false
	 *                  on failure.
	 */
	public function add_invitation( $args = array() ) {
		$r = bp_parse_args(
			$args,
			array(
				'user_id'           => 0,
				'invitee_email'     => '',
				'inviter_id'        => 0,
				'item_id'           => 0,
				'secondary_item_id' => 0,
				'type'              => 'invite',
				'content'           => '',
				'date_modified'     => bp_core_current_time(),
				'send_invite'       => 0,
				'accepted'          => 0,
			),
			'add_invitation'
		);

		// Invitations must have an invitee and inviter.
		if ( ! ( ( $r['user_id'] || $r['invitee_email'] ) && $r['inviter_id'] ) ) {
			return false;
		}

		// If an email address is specified, it must be a valid email address.
		if ( $r['invitee_email'] && ! is_email( $r['invitee_email'] ) ) {
			return false;
		}

		/**
		 * Is this user allowed to extend invitations in this situation?
		 *
		 * @since 5.0.0
		 */
		if ( ! $this->allow_invitation( $r ) ) {
			return false;
		}

		// Avoid creating duplicate invitations.
		$invite_id = $this->invitation_exists(
			array(
				'user_id'           => $r['user_id'],
				'invitee_email'     => $r['invitee_email'],
				'inviter_id'        => $r['inviter_id'],
				'item_id'           => $r['item_id'],
				'secondary_item_id' => $r['secondary_item_id'],
			)
		);

		if ( ! $invite_id ) {
			// Set up the new invitation as a draft.
			$invitation                    = new BP_Invitation();
			$invitation->user_id           = $r['user_id'];
			$invitation->inviter_id        = $r['inviter_id'];
			$invitation->invitee_email     = $r['invitee_email'];
			$invitation->class             = $this->class_name;
			$invitation->item_id           = $r['item_id'];
			$invitation->secondary_item_id = $r['secondary_item_id'];
			$invitation->type              = $r['type'];
			$invitation->content           = $r['content'];
			$invitation->date_modified     = $r['date_modified'];
			$invitation->invite_sent       = 0;
			$invitation->accepted          = 0;

			$invite_id = $invitation->save();
		}

		// "Send" the invite if necessary.
		if ( $invite_id && $r['send_invite'] ) {
			$sent = $this->send_invitation_by_id( $invite_id, $r );
			if ( ! $sent ) {
				return false;
			}
		}

		return $invite_id;
	}

	/**
	 * Send an invitation notification.
	 *
	 * @since 5.0.0
	 *
	 * @param int   $invitation_id ID of invitation to send.
	 * @param array $args          See BP_Invitation::mark_sent().
	 * @return bool
	 */
	public function send_invitation_by_id( $invitation_id = 0, $args = array() ) {
		$invitation = new BP_Invitation( $invitation_id );

		if ( ! $invitation->id ) {
			return false;
		}

		/**
		 * Fires before an invitation is sent.
		 *
		 * @since 5.0.0
		 *
		 * @param BP_Invitation object $invitation Invitation about to be sent.
		 */
		do_action( 'bp_invitations_send_invitation_by_id_before_send', $invitation );

		/*
		 * Before sending an invitation, check for outstanding requests to the same item.
		 * A sent invitation + a request = acceptance.
		 */
		$request_args = array(
			'user_id'           => $invitation->user_id,
			'invitee_email'     => $invitation->invitee_email,
			'item_id'           => $invitation->item_id,
			'secondary_item_id' => $invitation->secondary_item_id,
		);
		$request      = $this->request_exists( $request_args );

		if ( ! empty( $request ) ) {
			// Accept the request.
			return $this->accept_request( $request_args );
		}

		// Perform the send action.
		$success = $this->run_send_action( $invitation );

		if ( $success ) {
			BP_Invitation::mark_sent( $invitation->id, $args );
		}

		return $success;
	}

	/**
	 * Add a request to an item for a specific user, related to a
	 * specific class.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args {
	 *     Array of arguments describing the invitation. All are optional.
	 *     @type int    $user_id ID of the invited user.
	 *     @type int    $inviter_id ID of the user who created the invitation.
	 *     @type string $class Name of the invitations class.
	 *     @type int    $item_id ID associated with the invitation and class.
	 *     @type int    $secondary_item_id secondary ID associated with the
	 *                  invitation and class.
	 *     @type string $type    Type of record this is: 'invite' or 'request'.
	 *     @type string $content Extra information provided by the requester
	 *                  or inviter.
	 *     @type string $date_modified Date the invitation was last modified.
	 *     @type int    $invite_sent Has the invitation been sent, or is it a
	 *           draft invite?
	 * }
	 * @return int|bool ID of the newly created invitation on success, false
	 *                  on failure.
	 */
	public function add_request( $args = array() ) {
		$r = bp_parse_args(
			$args,
			array(
				'user_id'           => 0,
				'inviter_id'        => 0,
				'invitee_email'     => '',
				'item_id'           => 0,
				'secondary_item_id' => 0,
				'type'              => 'request',
				'content'           => '',
				'date_modified'     => bp_core_current_time(),
				'invite_sent'       => 0,
				'accepted'          => 0,
			),
			'add_request'
		);

		// If there is no invitee, bail.
		if ( ! ( $r['user_id'] || $r['invitee_email'] ) ) {
			return false;
		}

		/**
		 * Is this user allowed to make a request in this situation?
		 *
		 * @since 5.0.0
		 *
		 * @param array $r Describes the invitation to be added.
		 */
		if ( ! $this->allow_request( $r ) ) {
			return false;
		}

		/*
		 * Avoid creating duplicate requests.
		 */
		$base_args = array(
			'user_id'           => $r['user_id'],
			'invitee_email'     => $r['invitee_email'],
			'item_id'           => $r['item_id'],
			'secondary_item_id' => $r['secondary_item_id'],
		);
		if ( $this->request_exists( $base_args ) ) {
			return false;
		}

		/*
		 * Check for outstanding invitations to the same item.
		 * A request + a sent invite = acceptance.
		 */
		$invite_args = array_merge( $base_args, array( 'invite_sent' => 'sent' ) );
		$invite      = $this->invitation_exists( $invite_args );

		if ( $invite ) {
			// Accept the invite.
			return $this->accept_invitation( $base_args );
		} else {
			// Set up the new request.
			$request                    = new BP_Invitation();
			$request->user_id           = $r['user_id'];
			$request->inviter_id        = $r['inviter_id'];
			$request->invitee_email     = $r['invitee_email'];
			$request->class             = $this->class_name;
			$request->item_id           = $r['item_id'];
			$request->secondary_item_id = $r['secondary_item_id'];
			$request->type              = $r['type'];
			$request->content           = $r['content'];
			$request->date_modified     = $r['date_modified'];
			$request->invite_sent       = $r['invite_sent'];
			$request->accepted          = $r['accepted'];

			// Save the new invitation.
			return $request->save();
		}
	}

	/**
	 * Send a request notification.
	 *
	 * @since 5.0.0
	 *
	 * @param int   $request_id ID of request to send.
	 * @param array $args       See BP_Invitation::mark_sent().
	 * @return bool
	 */
	public function send_request_notification_by_id( $request_id = 0, $args = array() ) {
		$request = new BP_Invitation( $request_id );

		if ( ! $request->id ) {
			return false;
		}

		// Different uses may need different actions on sending. Plugins can hook in here to perform their own tasks.
		do_action( 'bp_invitations_send_request_notification_by_id_before_send', $request_id, $request );

		/*
		 * Before sending notifications, check for outstanding invitations to the same item.
		 * A sent invitation + a request = acceptance.
		 */
		$invite_args = array(
			'user_id'           => $request->user_id,
			'invitee_email'     => $request->invitee_email,
			'item_id'           => $request->item_id,
			'secondary_item_id' => $request->secondary_item_id,
			'invite_sent'       => 'sent',
		);
		$invites     = $this->invitation_exists( $invite_args );

		if ( ! empty( $invites ) ) {
			// Accept the request.
			return $this->accept_invitation( $invite_args );
		}

		// Perform the send action.
		$success = $this->run_send_action( $request );

		if ( $success ) {
			BP_Invitation::mark_sent( $request->id, $args );
		}

		return $success;
	}

	/** Retrieve ******************************************************************/

	/**
	 * Get a specific invitation by its ID.
	 *
	 * @since 5.0.0
	 *
	 * @param int $id ID of the invitation.
	 * @return BP_Invitation object
	 */
	public function get_by_id( $id = 0 ) {
		return new BP_Invitation( $id );
	}

	/**
	 * Get invitations, based on provided filter parameters.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args {@see BP_Invitation::get()}.
	 * @return array Located invitations.
	 */
	public function get_invitations( $args = array() ) {
		// Default to returning invitations, not requests.
		if ( empty( $args['type'] ) ) {
			$args['type'] = 'invite';
		}
		// Use the class_name property value.
		$args['class'] = $this->class_name;

		return BP_Invitation::get( $args );
	}

	/**
	 * Get a count of the number of invitations that match provided filter parameters.
	 *
	 * @since 8.0.0
	 *
	 * @param array $args {@see BP_Invitation::get_total_count()}.
	 * @return int Total number of invitations.
	 */
	public function get_invitations_total_count( $args = array() ) {
		// Default to returning invitations, not requests.
		if ( empty( $args['type'] ) ) {
			$args['type'] = 'invite';
		}
		// Use the class_name property value.
		$args['class'] = $this->class_name;

		return BP_Invitation::get_total_count( $args );
	}

	/**
	 * Get requests, based on provided filter parameters.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args {@see BP_Invitation::get()}.
	 * @return array Located invitations.
	 */
	public function get_requests( $args = array() ) {
		// Set request-specific parameters.
		$args['type']        = 'request';
		$args['inviter_id']  = false;
		$args['invite_sent'] = 'all';

		// Use the class_name property value.
		$args['class'] = $this->class_name;

		return BP_Invitation::get( $args );
	}

	/**
	 * Check whether an invitation exists matching the passed arguments.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args {@see BP_Invitation::get()}.
	 * @return int|bool ID of first found invitation or false if none found.
	 */
	public function invitation_exists( $args = array() ) {
		$is_invited = false;

		$args['fields'] = 'ids';
		$invites        = $this->get_invitations( $args );
		if ( $invites ) {
			$is_invited = current( $invites );
		}
		return $is_invited;
	}

	/**
	 * Check whether a request exists matching the passed arguments.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args {@see BP_Invitation::get()}.
	 * @return int|bool ID of existing request or false if none found.
	 */
	public function request_exists( $args = array() ) {
		$has_request = false;

		$args['fields'] = 'ids';
		$requests       = $this->get_requests( $args );
		if ( $requests ) {
			$has_request = current( $requests );
		}
		return $has_request;
	}

	/** Update ********************************************************************/

	/**
	 * Accept invitation, based on provided filter parameters.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args {BP_Invitation::get()}.
	 * @return int|bool Number of rows updated on success, false on failure.
	 */
	public function accept_invitation( $args = array() ) {
		$r = bp_parse_args(
			$args,
			array(
				'id'                => false,
				'user_id'           => 0,
				'invitee_email'     => '',
				'item_id'           => null,
				'secondary_item_id' => null,
				'invite_sent'       => 'sent',
				'date_modified'     => bp_core_current_time(),
			),
			'accept_invitation'
		);

		$r['class'] = $this->class_name;

		if ( ! $r['id'] && ! ( ( $r['user_id'] || $r['invitee_email'] ) && $r['class'] && $r['item_id'] ) ) {
			return false;
		}

		if ( ! $this->invitation_exists( $r ) ) {
			return false;
		}

		$success = $this->run_acceptance_action( 'invite', $r );
		if ( $success ) {
			// Mark invitations & requests to this item for this user.
			$this->mark_accepted( $r );

			// Allow plugins an opportunity to act on the change.
			do_action( 'bp_invitations_accepted_invite', $r );
		}
		return $success;
	}

	/**
	 * Accept invitation, based on provided filter parameters.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args {BP_Invitation::get()}.
	 * @return bool Number of rows updated on success, false on failure.
	 */
	public function accept_request( $args = array() ) {
		$r = bp_parse_args(
			$args,
			array(
				'user_id'           => 0,
				'item_id'           => null,
				'secondary_item_id' => null,
				'date_modified'     => bp_core_current_time(),
			),
			'accept_request'
		);

		$r['class'] = $this->class_name;

		if ( ! ( $r['user_id'] && $r['class'] && $r['item_id'] ) ) {
			return false;
		}

		if ( ! $this->request_exists( $r ) ) {
			return false;
		}

		$success = $this->run_acceptance_action( 'request', $r );
		if ( $success ) {
			// Update/Delete all related invitations & requests to this item for this user.
			$this->mark_accepted( $r );

			// Allow plugins an opportunity to act on the change.
			do_action( 'bp_invitations_accepted_request', $r );
		}
		return $success;
	}

	/**
	 * Update invitation, based on provided filter parameters.
	 *
	 * @since 5.0.0
	 *
	 * @see BP_Invitation::update() for a description of
	 *      accepted update/where arguments.
	 *
	 * @param array $update_args Associative array of fields to update,
	 *              and the values to update them to. Of the format
	 *              array( 'user_id' => 4 ).
	 * @param array $where_args Associative array of columns/values, to
	 *              determine which invitations should be updated. Formatted as
	 *              array( 'item_id' => 7 ).
	 * @return int|bool Number of rows updated on success, false on failure.
	 */
	public function update_invitation( $update_args = array(), $where_args = array() ) {
		$update_args['class'] = $this->class_name;

		return BP_Invitation::update( $update_args, $where_args );
	}

	/**
	 * This is where custom actions are added (in child classes)
	 * to run when an invitation or request needs to be "sent."
	 *
	 * @since 5.0.0
	 *
	 * @param BP_Invitation $invitation The invitation to send.
	 * @return bool
	 */
	abstract public function run_send_action( BP_Invitation $invitation );

	/**
	 * Mark invitations as sent that are found by user_id, inviter_id,
	 * invitee_email, class name, optional item id,
	 * optional secondary item id.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args {
	 *     Associative array of arguments. All arguments but $page and
	 *     $per_page can be treated as filter values for get_where_sql()
	 *     and get_query_clauses(). All items are optional.
	 *     @type int|array    $user_id ID of user being queried. Can be an
	 *                        array of user IDs.
	 *     @type int|array    $inviter_id ID of user who created the
	 *                        invitation. Can be an array of user IDs.
	 *                        Special cases
	 *     @type string|array $invitee_email Email address of invited users
	 *                        being queried. Can be an array of addresses.
	 *     @type string|array $class Name of the class to
	 *                        filter by. Can be an array of class names.
	 *     @type int|array    $item_id ID of associated item. Can be an array
	 *                        of multiple item IDs.
	 *     @type int|array    $secondary_item_id ID of secondary associated
	 *                        item. Can be an array of multiple IDs.
	 * }
	 */
	public function mark_sent( $args ) {
		$args['class'] = $this->class_name;
		return BP_Invitation::mark_sent_by_data( $args );
	}

	/**
	 * This is where custom actions are added (in child classes)
	 * to run when an invitation or request is accepted.
	 *
	 * @since 5.0.0
	 *
	 * @param string $type The type of record being accepted: 'invite' or 'request'.
	 * @param array  $r    Associative array of arguments.
	 * @return bool
	 */
	abstract public function run_acceptance_action( $type, $r );

	/**
	 * Mark invitation as accepted by invitation ID.
	 *
	 * @since 5.0.0
	 *
	 * @param int   $id   ID of the invitation to mark as accepted.
	 * @param array $args {@see BP_Invitation::mark_accepted()}.
	 * @return int|bool Number of rows updated on success, false on failure.
	 */
	public function mark_accepted_by_id( $id, $args ) {
		return BP_Invitation::mark_accepted( $id, $args );
	}

	/**
	 * Mark invitations as sent that are found by user_id, inviter_id,
	 * invitee_email, class name, item id, and
	 * optional secondary item id.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args {BP_Invitation::mark_accepted_by_data()}.
	 * @return int|bool Number of rows updated on success, false on failure.
	 */
	public function mark_accepted( $args ) {
		$args['class'] = $this->class_name;
		return BP_Invitation::mark_accepted_by_data( $args );
	}

	/** Delete ********************************************************************/

	/**
	 * Delete an invitation or invitations by query data.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args {BP_Invitation::delete()}.
	 * @return int|bool Number of rows deleted on success, false on failure.
	 */
	public function delete( $args ) {
		if ( empty( $args['type'] ) ) {
			$args['type'] = 'invite';
		}
		$args['class'] = $this->class_name;
		return BP_Invitation::delete( $args );
	}

	/**
	 * Delete a request or requests by query data.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args {BP_Invitation::delete()}.
	 * @return int|bool Number of rows deleted on success, false on failure.
	 */
	public function delete_requests( $args ) {
		$args['type'] = 'request';
		return $this->delete( $args );
	}

	/**
	 * Delete all invitations by class.
	 *
	 * Used when clearing out invitations for an entire class. Possibly used
	 * when deactivating a component related to a class that created invitations.
	 *
	 * @since 5.0.0
	 *
	 * @return int|bool Number of rows deleted on success, false on failure.
	 */
	public function delete_all() {
		return BP_Invitation::delete( array( 'class' => $this->class_name ) );
	}

	/**
	 * Delete an invitation by id.
	 *
	 * @since 8.0.0
	 *
	 * @param int $id ID of the invitation to delete.
	 * @return int|bool Number of rows deleted on success, false on failure.
	 */
	public function delete_by_id( $id ) {
		// Ensure that the invitation exists and was created by this class.
		$invite = new BP_Invitation( $id );
		if ( ! $invite->id || sanitize_key( $this->class_name ) !== $invite->class ) {
			return false;
		}

		return BP_Invitation::delete_by_id( $id );
	}

	/**
	 * This is where custom actions are added (in child classes)
	 * to determine whether an invitation should be allowed.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args The parameters that describe the invitation.
	 * @return bool
	 */
	public function allow_invitation( $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return true;
	}

	/**
	 * This is where custom actions are added (in child classes)
	 * to determine whether a request should be allowed.
	 *
	 * @since 5.0.0
	 *
	 * @param array $args The parameters describing the request.
	 * @return bool
	 */
	public function allow_request( $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return true;
	}
}
