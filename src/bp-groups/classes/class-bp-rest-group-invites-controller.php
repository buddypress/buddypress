<?php
/**
 * BP REST: BP_REST_Group_Invites_Endpoint class
 *
 * @package BuddyPress
 * @since 5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Group Invites endpoints.
 *
 * Use /groups/{group_id}/invites
 * Use /groups/{group_id}/invites/{invite_id}
 *
 * @since 5.0.0
 */
class BP_REST_Group_Invites_Controller extends WP_REST_Controller {

	/**
	 * Reuse some parts of the BP_REST_Groups_Endpoint class.
	 *
	 * @since 5.0.0
	 *
	 * @var BP_REST_Groups_Endpoint
	 */
	protected $groups_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 */
	public function __construct() {
		$this->namespace              = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base              = buddypress()->groups->id . '/invites';
		$this->groups_endpoint        = new BP_REST_Groups_Controller();
		$this->group_members_endpoint = new BP_REST_Group_Membership_Controller();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 5.0.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),

				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<invite_id>[\d]+)',
			array(
				'args'   => array(
					'invite_id' => array(
						'description' => __( 'A unique numeric ID for the group invitation.', 'buddypress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve group invitations.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$args = array(
			'item_id'     => $request->get_param( 'group_id' ),
			'user_id'     => $request->get_param( 'user_id' ),
			'invite_sent' => $request->get_param( 'invite_sent' ),
			'per_page'    => $request->get_param( 'per_page' ),
			'page'        => $request->get_param( 'page' ),
		);

		/**
		 * Inviter_id is a special case, because 0 can be meaningful for requests,
		 * but if it is zero for invitations, we can safely ignore it and should.
		 * So, only apply non-zero inviter_ids.
		 */
		if ( $request->get_param( 'inviter_id' ) ) {
			$args['inviter_id'] = $request->get_param( 'inviter_id' );
		}

		// If the query is not restricted by group, user or inviter, limit it to the current user, if not an admin.
		if ( ! $args['item_id'] && ! $args['user_id'] && ! $args['inviter_id'] && ! bp_current_user_can( 'bp_moderate' ) ) {
			$args['user_id'] = bp_loggedin_user_id();
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 5.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters( 'bp_rest_group_invites_get_items_query_args', $args, $request );

		// Get invites.
		$invites_data = groups_get_invites( $args );

		$retval = array();
		foreach ( $invites_data as $invitation ) {
			if ( $invitation instanceof BP_Invitation ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $invitation, $request )
				);
			}
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, count( $invites_data ), $args['per_page'] );

		/**
		 * Fires after a list of group invites are fetched via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param array            $invites_data  Invited users from the group.
		 * @param WP_REST_Response $response      The response data.
		 * @param WP_REST_Request  $request       The request sent to the API.
		 */
		do_action( 'bp_rest_group_invites_get_items', $invites_data, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to group invitations.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		$error = new WP_Error(
			'bp_rest_group_invites_cannot_get_items',
			__( 'Sorry, you are not allowed to fetch group invitations with those arguments.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$retval = $error;

		if ( bp_current_user_can( 'bp_view', array( 'bp_component' => 'groups' ) ) ) {
			$group_id        = (int) $request->get_param( 'group_id' );
			$current_user_id = (int) bp_loggedin_user_id();
			$user_id_arg     = (int) $request->get_param( 'user_id' );
			$inviter_id      = (int) $request->get_param( 'inviter_id' );

			if ( ! is_user_logged_in() ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you need to be logged in to see the group invitations.', 'buddypress' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			} elseif ( $group_id ) {
				if ( ! $user_id_arg ) {
					$user_id_arg = $current_user_id;
				}

				$user = bp_rest_get_user( $user_id_arg );

				// Check the user is valid.
				if ( ! $user instanceof WP_User ) {
					$retval = new WP_Error(
						'bp_rest_member_invalid_id',
						__( 'Invalid member ID.', 'buddypress' ),
						array(
							'status' => 404,
						)
					);
				} else {
					$group = $this->groups_endpoint->get_group_object( $group_id );

					// Check the group is valid.
					if ( ! $group instanceof BP_Groups_Group ) {
						$retval = new WP_Error(
							'bp_rest_group_invalid_id',
							__( 'Invalid group ID.', 'buddypress' ),
							array(
								'status' => 404,
							)
						);
					} elseif ( bp_current_user_can( 'bp_moderate' ) || $this->can_see( $group->id ) ) {
						/*
						* Users can see invitations if they
						* - are a site admin,
						* - are a group admin of the subject group (group_id must be specified).
						*/
						$retval = true;
					} else {
						$retval = $error;
					}
				}
			} elseif ( $user_id_arg ) {
				$user = bp_rest_get_user( $user_id_arg );

				// Check the user is valid.
				if ( ! $user instanceof WP_User ) {
					$retval = new WP_Error(
						'bp_rest_member_invalid_id',
						__( 'Invalid member ID.', 'buddypress' ),
						array(
							'status' => 404,
						)
					);
				} elseif ( bp_current_user_can( 'bp_moderate' ) || $user->ID === $current_user_id ) {
					/*
					* Users can see invitations if they:
					* - are a site admin,
					* - are the invite recipient (user_id must be specified).
					*/
					$retval = true;
				} else {
					$retval = $error;
				}
			} elseif ( $inviter_id ) {
				$inviter = bp_rest_get_user( $inviter_id );

				// Check the inviter is valid.
				if ( ! $inviter instanceof WP_User ) {
					$retval = new WP_Error(
						'bp_rest_member_invalid_id',
						__( 'Invalid member ID.', 'buddypress' ),
						array(
							'status' => 404,
						)
					);
				} elseif ( bp_current_user_can( 'bp_moderate' ) || $inviter->ID === $current_user_id ) {
					/*
					* Users can see invitations if they:
					* - are a site admin,
					* - are the inviter (inviter_id must be specified).
					*/
					$retval = true;
				} else {
					$retval = $error;
				}
			}
		}

		/**
		 * Filter the group invites `get_items` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Whether the request can continue.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_group_invites_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Fetch a specific group invitation by ID.
	 *
	 * @since 5.0.0
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$invite = $this->fetch_single_invite( $request->get_param( 'invite_id' ) );
		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $invite, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a membership request is fetched via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param BP_Invitation     $invite      Invitation object.
		 * @param WP_REST_Response  $response    The response data.
		 * @param WP_REST_Request   $request     The request sent to the API.
		 */
		do_action( 'bp_rest_group_invite_get_item', $invite, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to fetch group invitation.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( bp_current_user_can( 'bp_view', array( 'bp_component' => 'groups' ) ) ) {
			$user_id = bp_loggedin_user_id();

			if ( ! $user_id ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you need to be logged in to see the group invitations.', 'buddypress' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			} else {
				$invite = $this->fetch_single_invite( $request->get_param( 'invite_id' ) );

				if ( ! $invite ) {
					$retval = new WP_Error(
						'bp_rest_group_invite_invalid_id',
						__( 'Invalid group invitation ID.', 'buddypress' ),
						array(
							'status' => 404,
						)
					);
				} elseif ( bp_current_user_can( 'bp_moderate' ) || $this->can_see( $invite->item_id ) || in_array( $user_id, array( $invite->user_id, $invite->inviter_id ), true ) ) {
					/*
					* Users can see a specific invitation if they
					* - are a site admin
					* - are a group admin of the subject group
					* - are the invite recipient
					* - are the inviter
					*/
					$retval = true;
				} else {
					$retval = new WP_Error(
						'bp_rest_group_invites_cannot_get_item',
						__( 'Sorry, you are not allowed to fetch an invitation.', 'buddypress' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}
		}

		/**
		 * Filter the group membership request `get_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Whether the request can continue.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_group_invites_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Invite a member to a group.
	 *
	 * @since 5.0.0
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$inviter_id_arg = ! empty( $request->get_param( 'inviter_id' ) ) ? $request->get_param( 'inviter_id' ) : bp_loggedin_user_id();
		$group          = $this->groups_endpoint->get_group_object( $request->get_param( 'group_id' ) );
		$user           = bp_rest_get_user( $request->get_param( 'user_id' ) );
		$inviter        = bp_rest_get_user( $inviter_id_arg );

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $user->ID,
				'group_id'    => $group->id,
				'inviter_id'  => $inviter->ID,
				'send_invite' => ! empty( $request->get_param( 'invite_sent' ) ) ? (bool) $request->get_param( 'invite_sent' ) : 1,
				'content'     => $request->get_param( 'message' ),
			)
		);

		if ( ! $invite_id ) {
			return new WP_Error(
				'bp_rest_group_invite_cannot_create_item',
				__( 'Could not invite member to the group.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$invite = new BP_Invitation( $invite_id );

		// Set context.
		$request->set_param( 'context', 'edit' );

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $invite, $request )
			),
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a member is invited to a group via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param BP_Invitation    $invite         The invitation object.
		 * @param WP_User          $user           The invited user.
		 * @param WP_User          $inviter        The inviter user.
		 * @param BP_Groups_Group  $group          The group object.
		 * @param WP_REST_Response $response       The response data.
		 * @param WP_REST_Request  $request        The request sent to the API.
		 */
		do_action( 'bp_rest_group_invites_create_item', $invite, $user, $inviter, $group, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to invite a member to a group.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		$inviter_id_arg = ! empty( $request->get_param( 'inviter_id' ) ) ? $request->get_param( 'inviter_id' ) : bp_loggedin_user_id();
		$retval         = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to create an invitation.', 'buddypress' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		} else {
			$user    = bp_rest_get_user( $request->get_param( 'user_id' ) );
			$inviter = bp_rest_get_user( $inviter_id_arg );

			if ( empty( $user->ID ) || empty( $inviter->ID ) || $user->ID === $inviter->ID ) {
				$retval = new WP_Error(
					'bp_rest_member_invalid_id',
					__( 'Invalid member ID.', 'buddypress' ),
					array(
						'status' => 404,
					)
				);
			} else {
				$group = $this->groups_endpoint->get_group_object( $request->get_param( 'group_id' ) );

				if ( empty( $group->id ) ) {
					$retval = new WP_Error(
						'bp_rest_group_invalid_id',
						__( 'Invalid group ID.', 'buddypress' ),
						array(
							'status' => 404,
						)
					);
				} elseif ( bp_current_user_can( 'bp_moderate' ) || bp_loggedin_user_id() === $inviter_id_arg ) {
						$retval = true;
				} else {
					$retval = new WP_Error(
						'bp_rest_group_invite_cannot_create_item',
						__( 'Sorry, you are not allowed to create the invitation as requested.', 'buddypress' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}
		}

		/**
		 * Filter the group invites `create_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Whether the request can continue.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_group_invites_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Accept a group invitation.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$request->set_param( 'context', 'edit' );

		$invite = $this->fetch_single_invite( $request->get_param( 'invite_id' ) );
		$accept = groups_accept_invite( $invite->user_id, $invite->item_id );
		if ( ! $accept ) {
			return new WP_Error(
				'bp_rest_group_invite_cannot_update_item',
				__( 'Could not accept group invitation.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$accepted_member = new BP_Groups_Member( $invite->user_id, $invite->item_id );

		$retval = array(
			$this->prepare_response_for_collection(
				$this->group_members_endpoint->prepare_item_for_response( $accepted_member, $request )
			),
		);

		$response = rest_ensure_response( $retval );
		$group    = $this->groups_endpoint->get_group_object( $invite->item_id );

		/**
		 * Fires after a group invite is accepted via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param BP_Groups_Member $accepted_member Accepted group member.
		 * @param BP_Groups_Group  $group           The group object.
		 * @param WP_REST_Response $response        The response data.
		 * @param WP_REST_Request  $request         The request sent to the API.
		 */
		do_action( 'bp_rest_group_invites_update_item', $accepted_member, $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to accept a group invitation.
	 *
	 * @since 5.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		$retval  = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
		$user_id = bp_loggedin_user_id();

		if ( ! $user_id ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to see the group invitations.', 'buddypress' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		} else {
			$invite = $this->fetch_single_invite( $request->get_param( 'invite_id' ) );

			if ( ! $invite ) {
				$retval = new WP_Error(
					'bp_rest_group_invite_invalid_id',
					__( 'Invalid group invitation ID.', 'buddypress' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( bp_current_user_can( 'bp_moderate' ) || $user_id === $invite->user_id ) {
				$retval = true;
			} else {
				$retval = new WP_Error(
					'bp_rest_group_invite_cannot_update_item',
					__( 'Sorry, you are not allowed to accept the invitation as requested.', 'buddypress' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the group invites `update_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Whether the request can continue.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_group_invites_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Remove (reject/delete) a group invitation.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$request->set_param( 'context', 'edit' );

		$user_id = bp_loggedin_user_id();
		$invite  = $this->fetch_single_invite( $request->get_param( 'invite_id' ) );

		// Set the invite response before it is deleted.
		$previous = $this->prepare_item_for_response( $invite, $request );

		/**
		 * If this change is being initiated by the invited user,
		 * use the `reject` function.
		 */
		if ( $user_id === $invite->user_id ) {
			$deleted = groups_reject_invite( $invite->user_id, $invite->item_id, $invite->inviter_id );
			/**
			 * Otherwise, this change is being initiated by a group admin, site admin,
			 * or the inviter, and we should use the `uninvite` function.
			 */
		} else {
			$deleted = groups_uninvite_user( $invite->user_id, $invite->item_id, $invite->inviter_id );
		}

		if ( ! $deleted ) {
			return new WP_Error(
				'bp_rest_group_invite_cannot_delete_item',
				__( 'Could not delete group invitation.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $previous->get_data(),
			)
		);

		$user  = bp_rest_get_user( $invite->user_id );
		$group = $this->groups_endpoint->get_group_object( $invite->item_id );

		/**
		 * Fires after a group invite is deleted via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_User          $user     The invited user.
		 * @param BP_Groups_Group  $group    The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_group_invites_delete_item', $user, $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a group invitation.
	 *
	 * @since 5.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$retval  = true;
		$user_id = bp_loggedin_user_id();
		$invite  = $this->fetch_single_invite( $request->get_param( 'invite_id' ) );

		if ( ! $user_id ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to see the group invitations.', 'buddypress' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( true === $retval && ! $invite ) {
			$retval = new WP_Error(
				'bp_rest_group_invite_invalid_id',
				__( 'Invalid group invitation ID.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		// The inviter, the invitee, group admins, and site admins can all delete invites.
		if ( true === $retval
			&& ! bp_current_user_can( 'bp_moderate' )
			&& ! in_array( $user_id, array( $invite->user_id, $invite->inviter_id ), true )
			&& ! groups_is_user_admin( $user_id, $invite->item_id )
			) {
			$retval = new WP_Error(
				'bp_rest_group_invite_cannot_delete_item',
				__( 'Sorry, you are not allowed to delete the invitation as requested.', 'buddypress' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the group invites `delete_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param bool|WP_Error   $retval  Whether the request can continue.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_group_invites_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares group invitation data to return as an object.
	 *
	 * @since 5.0.0
	 *
	 * @param BP_Invitation   $invite  The invitation object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $invite, $request ) {
		$data = array(
			'id'                => (int) $invite->id,
			'user_id'           => (int) $invite->user_id,
			'invite_sent'       => (int) $invite->invite_sent,
			'inviter_id'        => (int) $invite->inviter_id,
			'group_id'          => (int) $invite->item_id,
			'date_modified'     => bp_rest_prepare_date_response( $invite->date_modified, get_date_from_gmt( $invite->date_modified ) ),
			'date_modified_gmt' => bp_rest_prepare_date_response( $invite->date_modified ),
			'type'              => (string) $invite->type,
			'message'           => array(
				'raw'      => $invite->content,
				'rendered' => apply_filters( 'the_content', $invite->content ),
			),
		);

		$context  = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		// Add prepare links.
		$response->add_links( $this->prepare_links( $invite ) );

		/**
		 * Filter a group invite value returned from the API.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BP_Invitation    $invite   The invitation object.
		 */
		return apply_filters( 'bp_rest_group_invites_prepare_value', $response, $request, $invite );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 5.0.0
	 *
	 * @param BP_Invitation $invite Invite object.
	 * @return array
	 */
	protected function prepare_links( $invite ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );
		$url  = $base . $invite->id;

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $url ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'user'       => array(
				'href'       => bp_rest_get_object_url( $invite->user_id, 'members' ),
				'embeddable' => true,
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 5.0.0
		 *
		 * @param array    $links  The prepared links of the REST response.
		 * @param stdClass $invite Invite object.
		 */
		return apply_filters( 'bp_rest_group_invites_prepare_links', $links, $invite );
	}

	/**
	 * Check access.
	 *
	 * @param int $group_id Group ID.
	 * @return bool
	 */
	protected function can_see( $group_id ) {
		$user_id = bp_loggedin_user_id();

		if ( ! groups_is_user_admin( $user_id, $group_id ) && ! groups_is_user_mod( $user_id, $group_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Helper function to fetch a single group invite.
	 *
	 * @since 5.0.0
	 *
	 * @param int $invite_id The ID of the invitation you wish to fetch.
	 * @return BP_Invitation|bool $invite Invitation if found, false otherwise.
	 */
	public function fetch_single_invite( $invite_id = 0 ) {
		$invites = groups_get_invites( array( 'id' => $invite_id ) );
		if ( $invites ) {
			return current( $invites );
		} else {
			return false;
		}
	}

	/**
	 * Edit the type of the some properties for the CREATABLE & EDITABLE methods.
	 *
	 * @since 5.0.0
	 *
	 * @param string $method Optional. HTTP method of the request.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = parent::get_endpoint_args_for_item_schema( $method );
		$key  = 'get_item';

		if ( WP_REST_Server::CREATABLE === $method || WP_REST_Server::EDITABLE === $method ) {
			$key                            = 'create_item';
			$args['message']['type']        = 'string';
			$args['message']['description'] = __( 'The optional message to send to the invited user.', 'buddypress' );
			$args['message']['default']     = '';
			$args['send_invite']            = $args['invite_sent'];
			$args['inviter_id']['default']  = bp_loggedin_user_id();
			$args['group_id']['required']   = true;
			$args['user_id']['required']    = true;

			// Remove arguments not needed for the CREATABLE transport method.
			unset( $args['message']['properties'], $args['invite_sent'], $args['date_modified'], $args['type'] );

			$args['send_invite']['description'] = __( 'Whether the invite should be sent to the invitee.', 'buddypress' );
			$args['send_invite']['default']     = true;

			if ( WP_REST_Server::EDITABLE === $method ) {
				$key = 'update_item';
			}
		} elseif ( WP_REST_Server::DELETABLE === $method ) {
			$key = 'delete_item';
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @since 5.0.0
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 */
		return apply_filters( "bp_rest_group_invites_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the group invite schema, conforming to JSON Schema.
	 *
	 * @since 5.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( is_null( $this->schema ) ) {
			$this->schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'bp_group_invites',
				'type'       => 'object',
				'properties' => array(
					'id'                => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'A unique numeric ID for the BP Invitation object.', 'buddypress' ),
						'type'        => 'integer',
						'readonly'    => true,
					),
					'user_id'           => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The ID of the user who is invited to join the Group.', 'buddypress' ),
						'type'        => 'integer',
					),
					'invite_sent'       => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Whether the invite has been sent to the invitee.', 'buddypress' ),
						'type'        => 'boolean',
					),
					'inviter_id'        => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The ID of the user who made the invite.', 'buddypress' ),
						'type'        => 'integer',
					),
					'group_id'          => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The ID of the group to which the user has been invited.', 'buddypress' ),
						'type'        => 'integer',
					),
					'date_modified'     => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The date the object was created or last updated, in the site\'s timezone.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'date_modified_gmt' => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The date the object was created or last updated, as GMT.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'type'              => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Invitation or request.', 'buddypress' ),
						'type'        => 'string',
						'enum'        => array( 'invite', 'request' ),
						'default'     => 'invite',
					),
					'message'           => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The raw and rendered versions for the content of the message.', 'buddypress' ),
						'type'        => 'object',
						'arg_options' => array(
							'sanitize_callback' => null,
							'validate_callback' => null,
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Content for the object, as it exists in the database.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'rendered' => array(
								'description' => __( 'HTML content for the object, transformed for display.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),

				),
			);
		}

		/**
		 * Filters the group invites schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_group_invites_schema', $this->add_additional_fields_schema( $this->schema ) );
	}

	/**
	 * Get the query params for collections of group invites.
	 *
	 * @since 5.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		// Remove the search param.
		unset( $params['search'] );

		$params['group_id'] = array(
			'description'       => __( 'ID of the group to limit results to.', 'buddypress' ),
			'required'          => false,
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Return only invitations extended to this user.', 'buddypress' ),
			'required'          => false,
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['inviter_id'] = array(
			'description'       => __( 'Return only invitations extended by this user.', 'buddypress' ),
			'required'          => false,
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['invite_sent'] = array(
			'description'       => __( 'Limit result set to invites that have been sent, not sent, or include all.', 'buddypress' ),
			'default'           => 'sent',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
			'enum'              => array( 'draft', 'sent', 'all' ),
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_group_invites_collection_params', $params );
	}
}
