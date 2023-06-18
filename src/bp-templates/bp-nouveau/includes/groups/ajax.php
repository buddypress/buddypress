<?php
/**
 * Groups Ajax functions
 *
 * @since 3.0.0
 * @version 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', function() {
	$ajax_actions = array(
		array( 'groups_filter'                      => array( 'function' => 'bp_nouveau_ajax_object_template_loader', 'nopriv' => true  ) ),
		array( 'groups_join_group'                  => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_leave_group'                 => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_accept_invite'               => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_reject_invite'               => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_request_membership'          => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_get_group_potential_invites' => array( 'function' => 'bp_nouveau_ajax_get_users_to_invite', 'nopriv' => false ) ),
		array( 'groups_send_group_invites'          => array( 'function' => 'bp_nouveau_ajax_send_group_invites', 'nopriv' => false ) ),
		array( 'groups_delete_group_invite'         => array( 'function' => 'bp_nouveau_ajax_remove_group_invite', 'nopriv' => false ) ),
	);

	foreach ( $ajax_actions as $ajax_action ) {
		$action = key( $ajax_action );

		add_action( 'wp_ajax_' . $action, $ajax_action[ $action ]['function'] );

		if ( ! empty( $ajax_action[ $action ]['nopriv'] ) ) {
			add_action( 'wp_ajax_nopriv_' . $action, $ajax_action[ $action ]['function'] );
		}
	}
}, 12 );

/**
 * Join or leave a group when clicking the "join/leave" button via a POST request.
 *
 * @since 3.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_joinleave_group() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddypress' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() || empty( $_POST['action'] ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['nonce'] ) || empty( $_POST['item_id'] ) || ! bp_is_active( 'groups' ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce
	$nonce = $_POST['nonce'];
	$check = 'bp_nouveau_groups';

	// Use a specific one for actions needed it
	if ( ! empty( $_POST['_wpnonce'] ) && ! empty( $_POST['action'] ) ) {
		$nonce = $_POST['_wpnonce'];
		$check = $_POST['action'];
	}

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	// Cast gid as integer.
	$group_id = (int) $_POST['item_id'];

	$errors = array(
		'cannot' => sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'You cannot join this group.', 'buddypress' ) ),
		'member' => sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'You are already a member of the group.', 'buddypress' ) ),
	);

	if ( groups_is_user_banned( bp_loggedin_user_id(), $group_id ) ) {
		$response['feedback'] = $errors['cannot'];

		wp_send_json_error( $response );
	}

	// Validate and get the group
	$group = groups_get_group( array( 'group_id' => $group_id ) );

	if ( empty( $group->id ) ) {
		wp_send_json_error( $response );
	}

	// Manage all button's possible actions here.
	switch ( $_POST['action'] ) {

		case 'groups_accept_invite':
			if ( ! groups_check_user_has_invite( bp_displayed_user_id(), $group_id ) ) {
				wp_send_json_error( $response );
			}

			if ( ! groups_accept_invite( bp_displayed_user_id(), $group_id ) ) {
				$response = array(
					'feedback' => sprintf(
						'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
						esc_html__( 'Group invitation could not be accepted.', 'buddypress' )
					),
					'type'     => 'error',
				);

			} else {
				if ( bp_is_active( 'activity' ) ) {
					groups_record_activity(
						array(
							'type'    => 'joined_group',
							'item_id' => $group->id,
						)
					);
				}

				// User is now a member of the group
				$group->is_member = '1';

				$response = array(
					'feedback' => sprintf(
						'<div class="bp-feedback success"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
						esc_html__( 'Group invite accepted.', 'buddypress' )
					),
					'type'     => 'success',
					'is_user'  => bp_is_user(),
					'contents' => bp_get_group_join_button( $group ),
					'is_group' => bp_is_group(),
				);
			}
			break;

		case 'groups_reject_invite':
			if ( ! groups_reject_invite( bp_displayed_user_id(), $group_id ) ) {
				$response = array(
					'feedback' => sprintf(
						'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
						esc_html__( 'Group invite could not be rejected', 'buddypress' )
					),
					'type'     => 'error',
				);
			} else {
				$response = array(
					'feedback' => sprintf(
						'<div class="bp-feedback success"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
						esc_html__( 'Group invite rejected', 'buddypress' )
					),
					'type'     => 'success',
					'is_user'  => bp_is_user(),
				);
			}
			break;

		case 'groups_join_group':
			if ( groups_is_user_member( bp_loggedin_user_id(), $group->id ) ) {
				$response = array(
					'feedback' => $errors['member'],
					'type'     => 'error',
				);
			} elseif ( 'public' !== $group->status ) {
				$response = array(
					'feedback' => $errors['cannot'],
					'type'     => 'error',
				);
			} elseif ( ! groups_join_group( $group->id ) ) {
				$response = array(
					'feedback' => sprintf(
						'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
						esc_html__( 'Error joining this group.', 'buddypress' )
					),
					'type'     => 'error',
				);
			} else {
				// User is now a member of the group
				$group->is_member = '1';

				$response = array(
					'contents' => bp_get_group_join_button( $group ),
					'is_group' => bp_is_group(),
					'type'     => 'success',
				);
			}
			break;

			case 'groups_request_membership' :
				if ( ! groups_send_membership_request( [ 'user_id' => bp_loggedin_user_id(), 'group_id' => $group->id ] ) ) {
					$response = array(
						'feedback' => sprintf(
							'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
							esc_html__( 'Error requesting membership.', 'buddypress' )
						),
						'type'     => 'error',
					);
				} else {
					// Request is pending
					$group->is_pending = '1';

					$response = array(
						'contents' => bp_get_group_join_button( $group ),
						'is_group' => bp_is_group(),
						'type'     => 'success',
					);
				}
				break;

			case 'groups_leave_group' :
				if (  ! groups_leave_group( $group->id ) ) {
					$response = array(
						'feedback' => sprintf(
							'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
							esc_html__( 'Error leaving group.', 'buddypress' )
						),
						'type'     => 'error',
					);
				} else {
					// User is no more a member of the group
					$group->is_member = '0';
					$bp               = buddypress();

					/**
					 * When inside the group or in the loggedin user's group memberships screen
					 * we need to reload the page.
					 */
					$bp_is_group = bp_is_group() || ( bp_is_user_groups() && bp_is_my_profile() );

					$response = array(
						'contents' => bp_get_group_join_button( $group ),
						'is_group' => $bp_is_group,
						'type'     => 'success',
					);

					// Reset the message if not in a Group or in a loggedin user's group memberships one!
					if ( ! $bp_is_group && isset( $bp->template_message ) && isset( $bp->template_message_type ) ) {
						unset( $bp->template_message, $bp->template_message_type );

						@setcookie( 'bp-message', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
						@setcookie( 'bp-message-type', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
					}
				}
				break;
	}

	if ( 'error' === $response['type'] ) {
		wp_send_json_error( $response );
	}

	wp_send_json_success( $response );
}

/**
 * @since 3.0.0
 */
function bp_nouveau_ajax_get_users_to_invite() {
	$bp = buddypress();

	$response = array(
		'feedback' => __( 'There was a problem performing this action. Please try again.', 'buddypress' ),
		'type'     => 'error',
	);

	if ( empty( $_POST['nonce'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce
	$nonce = $_POST['nonce'];
	$check = 'bp_nouveau_groups';

	// Use a specific one for actions needed it
	if ( ! empty( $_POST['_wpnonce'] ) && ! empty( $_POST['action'] ) ) {
		$nonce = $_POST['_wpnonce'];
		$check = $_POST['action'];
	}

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	if ( ! bp_is_group_create() && ! bp_groups_user_can_send_invites( bp_get_current_group_id(), bp_loggedin_user_id() ) ) {
		$invite_status = bp_group_get_invite_status( bp_get_current_group_id() );
		if ( 'admins' === $invite_status ) {
			$message = __( 'Inviting members to join this group is restricted to Group Administrators.', 'buddypress' );
		} else {
			$message = __( 'Inviting members to join this group is restricted to Group Moderators and Administrators.', 'buddypress' );
		}

		wp_send_json_error(
			array(
				'feedback' => $message,
				'type'     => 'error',
			)
		);
	}

	$request = bp_parse_args(
		$_POST,
		array(
			'scope' => 'members',
		),
		'nouveau_ajax_get_users_to_invite'
	);

	$bp->groups->invites_scope = 'members';
	$message = __( 'Select members to invite by clicking the + button. Once you\'ve made your selection, use the "Send Invites" navigation item to continue.', 'buddypress' );

	if ( 'friends' === $request['scope'] ) {
		$request['user_id'] = bp_loggedin_user_id();
		$bp->groups->invites_scope = 'friends';
		$message = __( 'Select friends to invite by clicking the + button. Once you\'ve made your selection, use the "Send Invites" navigation item to continue.', 'buddypress' );
	}

	if ( 'invited' === $request['scope'] ) {

		if ( ! bp_group_has_invites( array( 'user_id' => 'any' ) ) ) {
			wp_send_json_error( array(
				'feedback' => __( 'No pending group invitations found.', 'buddypress' ),
				'type'     => 'info',
			) );
		}

		$request['is_confirmed'] = false;
		$bp->groups->invites_scope = 'invited';
		$message = __( 'You can view the group\'s pending invitations from this screen.', 'buddypress' );
	}

	$potential_invites = bp_nouveau_get_group_potential_invites( $request );

	if ( empty( $potential_invites->users ) ) {
		$error = array(
			'feedback' => __( 'No members were found. Try another filter.', 'buddypress' ),
			'type'     => 'info',
		);

		if ( 'friends' === $bp->groups->invites_scope ) {
			$error = array(
				'feedback' => __( 'All your friends are already members of this group, or have already received an invite to join this group, or have requested to join it.', 'buddypress' ),
				'type'     => 'info',
			);

			if ( 0 === (int) bp_get_total_friend_count( bp_loggedin_user_id() ) ) {
				$error = array(
					'feedback' => __( 'You have no friends!', 'buddypress' ),
					'type'     => 'info',
				);
			} else {
				$error = array(
					'feedback' => __( 'No friends were found. Try another filter.', 'buddypress' ),
					'type'     => 'info',
				);
			}
		}

		unset( $bp->groups->invites_scope );

		wp_send_json_error( $error );
	}

	$potential_invites->users = array_map( 'bp_nouveau_prepare_group_potential_invites_for_js', array_values( $potential_invites->users ) );
	$potential_invites->users = array_filter( $potential_invites->users );

	// Set a message to explain use of the current scope
	$potential_invites->feedback = $message;

	unset( $bp->groups->invites_scope );

	wp_send_json_success( $potential_invites );
}

/**
 * @since 3.0.0
 */
function bp_nouveau_ajax_send_group_invites() {
	$response = array(
		'feedback' => __( 'Invites could not be sent. Please try again.', 'buddypress' ),
		'type'     => 'error',
	);

	// Verify nonce
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'groups_send_invites' ) ) {
		wp_send_json_error( $response );
	}

	$group_id = bp_get_current_group_id();

	if ( bp_is_group_create() && ! empty( $_POST['group_id'] ) ) {
		$group_id = (int) $_POST['group_id'];
	}

	if ( ! bp_groups_user_can_send_invites( $group_id ) ) {
		$response['feedback'] = __( 'You are not allowed to send invitations for this group.', 'buddypress' );
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['users'] ) ) {
		wp_send_json_error( $response );
	}

	// For feedback
	$invited           = array();
	$is_friends_active = bp_is_active( 'friends' );

	foreach ( (array) $_POST['users'] as $user_id ) {
		$user_id = (int) $user_id;

		if ( $is_friends_active && bp_nouveau_groups_get_group_invites_setting( $user_id ) && 'is_friend' !== BP_Friends_Friendship::check_is_friend( bp_loggedin_user_id(), $user_id ) ) {
			continue;
		} else {
			$invited[ $user_id ] = groups_invite_user(
				array(
					'user_id'  => $user_id,
					'group_id' => $group_id,
					'content'  => $_POST['message'],
				)
			);
		}
	}

	if ( ! $invited ) {
		wp_send_json_error( $response );
	}

	// Send the invites.
	groups_send_invites( array(	'group_id' => $group_id ) );

	if ( array_search( false, $invited ) ) {
		$errors = array_keys( $invited, false );

		$error_count   = count( $errors );
		$error_message = sprintf(
			/* translators: count of users affected */
			_n(
				'Invitation failed for %s user.',
				'Invitation failed for %s users.',
				$error_count, 'buddypress'
			),
			number_format_i18n( $error_count )
		);

		wp_send_json_error(
			array(
				'feedback' => $error_message,
				'users'    => $errors,
				'type'     => 'error',
			)
		);
	}

	wp_send_json_success(
		array(
			'feedback' => __( 'Invitations sent.', 'buddypress' ),
			'type'     => 'success',
		)
	);
}

/**
 * @since 3.0.0
 */
function bp_nouveau_ajax_remove_group_invite() {
	$user_id  = (int) $_POST['user'];
	$group_id = bp_get_current_group_id();

	$response = array(
		'feedback' => __( 'Group invitation could not be removed.', 'buddypress' ),
		'type'     => 'error',
	);

	// Verify nonce
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'groups_invite_uninvite_user' ) ) {
		wp_send_json_error( $response );
	}

	// Verify that a sent invite exists.
	$inviter_ids = groups_get_invites( array(
		'user_id'     => $user_id,
		'item_id'     => $group_id,
		'invite_sent' => 'sent',
		'fields'      => 'inviter_ids'
	) );

	if ( empty( $inviter_ids ) ) {
		wp_send_json_error( $response );
	}

	// Is the current user the inviter?
	$inviter_id = in_array( bp_loggedin_user_id(), $inviter_ids, true ) ? bp_loggedin_user_id() : false;

	// A site moderator, group admin or the inviting user should be able to remove an invitation.
	if ( ! bp_is_item_admin() && ! $inviter_id ) {
		wp_send_json_error( $response );
	}

	if ( groups_is_user_member( $user_id, $group_id ) ) {
		wp_send_json_error(
			array(
				'feedback' => __( 'The member is already a member of the group.', 'buddypress' ),
				'type'     => 'warning',
				'code'     => 1,
			)
		);
	}

	// Remove the invitation.
	if ( ! groups_uninvite_user( $user_id, $group_id, $inviter_id ) ) {
		wp_send_json_error(
			array(
				'feedback' => __( 'Group invitation could not be removed.', 'buddypress' ),
				'type'     => 'error',
				'code'     => 0,
			)
		);
	}

	wp_send_json_success(
		array(
			'feedback'    => __( 'There are no more pending invitations for the group.', 'buddypress' ),
			'type'        => 'info',
			'has_invites' => bp_group_has_invites( array( 'user_id' => 'any' ) ),
		)
	);
}
