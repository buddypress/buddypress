<?php
/**
 * BP REST: BP_REST_Group_Membership_Endpoint class
 *
 * @package BuddyPress
 * @since 5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Group membership endpoints.
 *
 * Use /groups/{group_id}/members
 * Use /groups/{group_id}/members/{user_id}
 *
 * @since 5.0.0
 */
class BP_REST_Group_Membership_V1_Controller extends WP_REST_Controller {

	/**
	 * Reuse some parts of the BP_REST_Groups_Endpoint class.
	 *
	 * @since 5.0.0
	 *
	 * @var BP_REST_Groups_Endpoint
	 */
	protected $groups_endpoint;

	/**
	 * Reuse some parts of the BP_REST_Members_Endpoint class.
	 *
	 * @since 5.0.0
	 *
	 * @var BP_REST_Members_Endpoint
	 */
	protected $members_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 */
	public function __construct() {
		$this->namespace        = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base        = buddypress()->groups->id;
		$this->groups_endpoint  = new BP_REST_Groups_V1_Controller();
		$this->members_endpoint = new BP_REST_Members_V1_Controller();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 5.0.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<group_id>[\d]+)/members',
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
					'args'                => $this->get_endpoint_args_for_method( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<group_id>[\d]+)/members/(?P<user_id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_method( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_method( WP_REST_Server::DELETABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve group members.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$group = $this->groups_endpoint->get_group_object( $request->get_param( 'group_id' ) );
		$args  = array(
			'group_id'            => $group->id,
			'group_role'          => $request->get_param( 'roles' ),
			'type'                => $request->get_param( 'status' ),
			'per_page'            => $request->get_param( 'per_page' ),
			'page'                => $request->get_param( 'page' ),
			'search_terms'        => $request->get_param( 'search' ),
			'exclude'             => $request->get_param( 'exclude' ),
			'exclude_admins_mods' => (bool) $request->get_param( 'exclude_admins' ),
			'exclude_banned'      => (bool) $request->get_param( 'exclude_banned' ),
		);

		if ( empty( $args['exclude'] ) ) {
			$args['exclude'] = false;
		}

		if ( is_null( $args['search_terms'] ) ) {
			$args['search_terms'] = false;
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 5.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters( 'bp_rest_group_members_get_items_query_args', $args, $request );

		// Get our members.
		$members = groups_get_group_members( $args );

		$retval = array();
		foreach ( $members['members'] as $member ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $member, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $members['count'], $args['per_page'] );

		/**
		 * Fires after a list of group members are fetched via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param array            $members  Fetched group members.
		 * @param BP_Groups_Group  $group    The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_group_members_get_items', $members, $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to group members.
	 *
	 * We are using the same permissions check done on group access.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you cannot view the group.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( bp_current_user_can( 'bp_view', array( 'bp_component' => 'groups' ) ) ) {
			$group = $this->groups_endpoint->get_group_object( $request->get_param( 'group_id' ) );
			if ( empty( $group->id ) ) {
				$retval = new WP_Error(
					'bp_rest_group_invalid_id',
					__( 'Invalid group ID.', 'buddypress' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( bp_current_user_can( 'bp_moderate' ) || 'public' === $group->status || groups_is_user_member( bp_loggedin_user_id(), $group->id ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the group members `get_items` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_group_members_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Add member to a group.
	 *
	 * @since 5.0.0
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$user  = bp_rest_get_user( $request->get_param( 'user_id' ) );
		$group = $this->groups_endpoint->get_group_object( $request->get_param( 'group_id' ) );

		if ( ! $request->get_param( 'context' ) || 'view' === $request->get_param( 'context' ) ) {
			if ( ! groups_join_group( $group->id, $user->ID ) ) {
				return new WP_Error(
					'bp_rest_group_member_failed_to_join',
					__( 'Could not join the group.', 'buddypress' ),
					array(
						'status' => 500,
					)
				);
			}

			// Get the group member.
			$group_member = new BP_Groups_Member( $user->ID, $group->id );
		} else {
			$role         = $request->get_param( 'role' );
			$group_id     = $group->id;
			$group_member = new BP_Groups_Member( $user->ID, $group_id );

			// Add member to the group.
			$group_member->group_id      = $group_id;
			$group_member->user_id       = $user->ID;
			$group_member->is_admin      = 0;
			$group_member->date_modified = bp_core_current_time();
			$group_member->is_confirmed  = 1;
			$saved                       = $group_member->save();

			if ( ! $saved ) {
				return new WP_Error(
					'bp_rest_group_member_failed_to_join',
					__( 'Could not add member to the group.', 'buddypress' ),
					array(
						'status' => 500,
					)
				);
			}

			// If new role set, promote it too.
			if ( $saved && 'member' !== $role ) {
				// Make sure to update the group role.
				if ( groups_promote_member( $user->ID, $group_id, $role ) ) {
					$group_member = new BP_Groups_Member( $user->ID, $group_id );
				}
			}
		}

		// Setting context.
		$request->set_param( 'context', 'edit' );

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $group_member, $request )
			),
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a member is added to a group via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_User          $user         The user.
		 * @param BP_Groups_Member $group_member The group member object.
		 * @param BP_Groups_Group  $group        The group object.
		 * @param WP_REST_Response $response     The response data.
		 * @param WP_REST_Request  $request      The request sent to the API.
		 */
		do_action( 'bp_rest_group_members_create_item', $user, $group_member, $group, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to join a group.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddypress' ),
			array( 'status' => rest_authorization_required_code() )
		);

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to join a group.', 'buddypress' ),
				array( 'status' => rest_authorization_required_code() )
			);
		} else {
			$user             = bp_rest_get_user( $request->get_param( 'user_id' ) );
			$loggedin_user_id = bp_loggedin_user_id();

			if ( ! $user instanceof WP_User ) {
				$retval = new WP_Error(
					'bp_rest_group_member_invalid_id',
					__( 'Invalid group member ID.', 'buddypress' ),
					array( 'status' => 404 )
				);
			} else {
				$group = $this->groups_endpoint->get_group_object( $request->get_param( 'group_id' ) );

				if ( ! $group instanceof BP_Groups_Group ) {
					$retval = new WP_Error(
						'bp_rest_group_invalid_id',
						__( 'Invalid group ID.', 'buddypress' ),
						array( 'status' => 404 )
					);
				} elseif ( bp_current_user_can( 'bp_moderate' ) || ( bp_current_user_can( 'groups_join_group', array( 'group_id' => $group->id ) ) && $loggedin_user_id === $user->ID ) ) {
					$retval = true;
				} else {
					$retval = new WP_Error(
						'bp_rest_group_member_failed_to_join',
						__( 'Could not join the group.', 'buddypress' ),
						array( 'status' => 500 )
					);
				}
			}
		}

		/**
		 * Filter the group members `create_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_group_members_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update user status on a group (add, remove, promote, demote or ban).
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$user     = bp_rest_get_user( $request->get_param( 'user_id' ) );
		$group    = $this->groups_endpoint->get_group_object( $request->get_param( 'group_id' ) );
		$action   = $request->get_param( 'action' );
		$role     = $request->get_param( 'role' );
		$group_id = $group->id;

		$bp_loggedin_user_id = bp_loggedin_user_id();

		// Check if the user is a member of the group.
		if ( 'unban' !== $action && ! groups_is_user_member( $user->ID, $group_id ) ) {
			return new WP_Error(
				'bp_rest_group_member_not_member',
				__( 'Sorry, you are not allowed to perform this action.', 'buddypress' ),
				array( 'status' => 500 )
			);
		}

		if ( 'promote' === $action ) {
			if ( ! groups_promote_member( $user->ID, $group_id, $role, $bp_loggedin_user_id ) ) {
				return new WP_Error(
					'bp_rest_group_member_failed_to_promote',
					__( 'Could not promote member.', 'buddypress' ),
					array( 'status' => 500 )
				);
			}
		} elseif ( 'demote' === $action && 'member' !== $role ) {
			if ( ! groups_promote_member( $user->ID, $group_id, $role, $bp_loggedin_user_id ) ) {
				return new WP_Error(
					'bp_rest_group_member_failed_to_demote',
					__( 'Could not demote member.', 'buddypress' ),
					array( 'status' => 500 )
				);
			}
		} elseif ( in_array( $action, array( 'demote', 'ban', 'unban' ), true ) ) {
			if ( ! call_user_func( 'groups_' . $action . '_member', $user->ID, $group_id, $bp_loggedin_user_id ) ) {
				$messages = array(
					'demote' => __( 'Could not demote member from the group.', 'buddypress' ),
					'ban'    => __( 'Could not ban member from the group.', 'buddypress' ),
					'unban'  => __( 'Could not unban member from the group.', 'buddypress' ),
				);

				return new WP_Error(
					'bp_rest_group_member_failed_to_' . $action,
					$messages[ $action ],
					array( 'status' => 500 )
				);
			}
		}

		// Get updated group member.
		$group_member = new BP_Groups_Member( $user->ID, $group_id );

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $group_member, $request )
			),
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group member status is updated via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_User          $user         The updated member.
		 * @param BP_Groups_Member $group_member The group member object.
		 * @param BP_Groups_Group  $group        The group object.
		 * @param WP_REST_Response $response     The response data.
		 * @param WP_REST_Request  $request      The request sent to the API.
		 */
		do_action( 'bp_rest_group_members_update_item', $user, $group_member, $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a group member.
	 *
	 * @since 5.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		$error = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddypress' ),
			array( 'status' => rest_authorization_required_code() )
		);

		$admin_error = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Group needs 1 admin at least. Please promote a user before trying to remove this group admin.', 'buddypress' ),
			array( 'status' => rest_authorization_required_code() )
		);

		$retval = $error;
		$action = $request->get_param( 'action' );

		if ( ! is_user_logged_in() ) {
			$retval = $error;
		} else {
			$user             = bp_rest_get_user( $request->get_param( 'user_id' ) );
			$loggedin_user_id = bp_loggedin_user_id();

			if ( ! $user instanceof WP_User ) {
				$retval = new WP_Error(
					'bp_rest_group_member_invalid_id',
					__( 'Invalid group member ID.', 'buddypress' ),
					array( 'status' => 404 )
				);
			} else {
				$group = $this->groups_endpoint->get_group_object( $request->get_param( 'group_id' ) );

				if ( ! $group instanceof BP_Groups_Group ) {
					$retval = new WP_Error(
						'bp_rest_group_invalid_id',
						__( 'Invalid group ID.', 'buddypress' ),
						array( 'status' => 404 )
					);
				} elseif ( bp_current_user_can( 'bp_moderate' ) && $loggedin_user_id !== $user->ID ) {
					if ( 'demote' === $action && groups_is_user_admin( $user->ID, $group->id ) ) {
						$group_admins = groups_get_group_admins( $group->id );

						if ( 1 !== count( $group_admins ) ) {
							$retval = true;
						} else {
							$retval = $admin_error;
						}
					} else {
						$retval = true;
					}
				} elseif ( groups_is_user_admin( $loggedin_user_id, $group->id ) && $loggedin_user_id !== $user->ID ) {
					$retval = true;
				} else {
					$messages = array(
						'ban'     => __( 'Sorry, you are not allowed to ban this group member.', 'buddypress' ),
						'unban'   => __( 'Sorry, you are not allowed to unban this group member.', 'buddypress' ),
						'promote' => __( 'Sorry, you are not allowed to promote this group member.', 'buddypress' ),
						'demote'  => __( 'Sorry, you are not allowed to demote this group member.', 'buddypress' ),
					);

					$retval = new WP_Error(
						'bp_rest_group_member_cannot_' . $action,
						$messages[ $action ],
						array( 'status' => rest_authorization_required_code() )
					);
				}
			}
		}

		/**
		 * Filter the group members `update_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_group_members_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a group membership.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$group_id = $request->get_param( 'group_id' );
		$user_id  = $request->get_param( 'user_id' );

		// Get the Group member before it's removed.
		$member = new BP_Groups_Member( $user_id, $group_id );

		if ( ! $member->remove() ) {
			return new WP_Error(
				'bp_rest_group_member_failed_to_remove',
				__( 'Could not remove member from this group.', 'buddypress' ),
				array( 'status' => 500 )
			);
		}

		$previous_member = $this->prepare_item_for_response( $member, $request );

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'removed'  => true,
				'previous' => $previous_member->get_data(),
			)
		);

		$user  = bp_rest_get_user( $user_id );
		$group = $this->groups_endpoint->get_group_object( $group_id );

		/**
		 * Fires after a group member is deleted via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_User          $user     The updated member.
		 * @param BP_Groups_Member $member   The group member object.
		 * @param BP_Groups_Group  $group    The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_group_members_delete_item', $user, $member, $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a group member.
	 *
	 * @since 5.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$authorization_code = rest_authorization_required_code();

		$error = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddypress' ),
			array( 'status' => $authorization_code )
		);

		$admin_error = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Group needs 1 admin at least. Please promote a user before trying to remove this group admin.', 'buddypress' ),
			array( 'status' => $authorization_code )
		);

		$retval = $error;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to view a group membership.', 'buddypress' ),
				array( 'status' => $authorization_code )
			);
		} else {
			$user             = bp_rest_get_user( $request->get_param( 'user_id' ) );
			$loggedin_user_id = bp_loggedin_user_id();

			if ( ! $user instanceof WP_User ) {
				return new WP_Error(
					'bp_rest_group_member_invalid_id',
					__( 'Invalid group member ID.', 'buddypress' ),
					array( 'status' => 404 )
				);
			} else {
				$group = $this->groups_endpoint->get_group_object( $request->get_param( 'group_id' ) );

				if ( ! $group instanceof BP_Groups_Group ) {
					$retval = new WP_Error(
						'bp_rest_group_invalid_id',
						__( 'Invalid group ID.', 'buddypress' ),
						array( 'status' => 404 )
					);
				} elseif ( bp_current_user_can( 'bp_moderate' ) || ( groups_is_user_admin( $loggedin_user_id, $group->id ) && $loggedin_user_id !== $user->ID ) ) {
					if ( groups_is_user_admin( $user->ID, $group->id ) ) {
						$group_admins = groups_get_group_admins( $group->id );

						if ( 1 !== count( $group_admins ) ) {
							$retval = true;
						} else {
							$retval = $admin_error;
						}
					} else {
						$retval = true;
					}
				} elseif ( $user->ID === $loggedin_user_id && ! groups_is_user_banned( $user->ID, $group->id ) ) {
					if ( groups_is_user_admin( $loggedin_user_id, $group->id ) ) {
						$group_admins = groups_get_group_admins( $group->id );

						if ( 1 !== count( $group_admins ) ) {
							$retval = true;
						} else {
							$retval = $admin_error;
						}
					} else {
						$retval = true;
					}
				}
			}
		}

		/**
		 * Filter the group members `delete_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_group_members_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares group member data for return as an object.
	 *
	 * @since 5.0.0
	 *
	 * @param BP_Groups_Member $group_member Group member object.
	 * @param WP_REST_Request  $request      Full details about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $group_member, $request ) {
		$user                   = bp_rest_get_user( $group_member->user_id );
		$context                = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';
		$member_data            = $this->members_endpoint->user_data( $user, $context, $request );
		$group_member->group_id = $request->get_param( 'group_id' );
		$date_modified          = $group_member->date_modified;

		if ( is_null( $date_modified ) ) {
			$date_modified = '';
		}

		// Merge both info.
		$data = array_merge(
			$member_data,
			array(
				'group'             => (int) $group_member->group_id,
				'is_mod'            => (bool) $group_member->is_mod,
				'is_admin'          => (bool) $group_member->is_admin,
				'is_banned'         => (bool) $group_member->is_banned,
				'is_confirmed'      => (bool) $group_member->is_confirmed,
				'date_modified'     => bp_rest_prepare_date_response( $date_modified, get_date_from_gmt( $date_modified ) ),
				'date_modified_gmt' => bp_rest_prepare_date_response( $date_modified ),
			)
		);

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		// Add prepare links.
		$response->add_links( $this->prepare_links( $group_member ) );

		/**
		 * Filter a group member value returned from the API.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_REST_Response $response      The response data.
		 * @param WP_REST_Request  $request       Request used to generate the response.
		 * @param BP_Groups_Member $group_member  The group member object.
		 */
		return apply_filters( 'bp_rest_group_members_prepare_value', $response, $request, $group_member );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 5.0.0
	 *
	 * @param BP_Groups_Member $group_member Group member object.
	 * @return array
	 */
	protected function prepare_links( $group_member ) {
		$base = sprintf( '/%s/%s', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => bp_rest_get_object_url( $group_member->user_id, 'members' ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%d/members', $base, $group_member->group_id ) ),
			),
			'group'      => array(
				'href'       => rest_url( sprintf( '/%s/%d', $base, $group_member->group_id ) ),
				'embeddable' => true,
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 5.0.0
		 *
		 * @param array $          links         The prepared links of the REST response.
		 * @param BP_Groups_Member $group_member Group member object.
		 */
		return apply_filters( 'bp_rest_group_members_prepare_links', $links, $group_member );
	}

	/**
	 * GET arguments for the endpoint's CREATABLE, EDITABLE & DELETABLE methods.
	 *
	 * @since 5.0.0
	 *
	 * @param string $method Optional. HTTP method of the request.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_method( $method = WP_REST_Server::CREATABLE ) {
		$key  = 'get_item';
		$args = array(
			'context' => $this->get_context_param(
				array(
					'default' => 'edit',
				)
			),
		);

		if ( WP_REST_Server::CREATABLE === $method || WP_REST_Server::EDITABLE === $method ) {
			$group_roles = array_diff( array_keys( bp_groups_get_group_roles() ), array( 'banned' ) );

			$args['role'] = array(
				'description'       => __( 'Group role to assign the user to.', 'buddypress' ),
				'default'           => 'member',
				'type'              => 'string',
				'enum'              => $group_roles,
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			);

			if ( WP_REST_Server::CREATABLE === $method ) {
				$key             = 'create_item';
				$schema          = $this->get_item_schema();
				$args['user_id'] = array_merge(
					$schema['properties']['id'],
					array(
						'description' => __( 'A unique numeric ID for the Member to add to the Group.', 'buddypress' ),
						'default'     => bp_loggedin_user_id(),
						'required'    => true,
						'readonly'    => false,
					)
				);
			}

			if ( WP_REST_Server::EDITABLE === $method ) {
				$key            = 'update_item';
				$args['action'] = array(
					'description'       => __( 'Action used to update a group member.', 'buddypress' ),
					'default'           => 'promote',
					'type'              => 'string',
					'enum'              => array( 'promote', 'demote', 'ban', 'unban' ),
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'rest_validate_request_arg',
				);
			}
		} elseif ( WP_REST_Server::DELETABLE === $method ) {
			$key = 'delete_item';
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @since 5.0.0
		 *
		 * @param array $args Query arguments.
		 * @param string $method HTTP method of the request.
		 */
		return apply_filters( "bp_rest_group_members_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the group member schema, conforming to JSON Schema.
	 *
	 * @since 5.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( is_null( $this->schema ) ) {

			// Get schema from members.
			$schema = $this->members_endpoint->get_item_schema();

			// Set title to this endpoint.
			$schema['title'] = 'bp_group_members';

			$schema['properties']['group_id'] = array(
				'context'     => array( 'view', 'edit', 'embed' ),
				'description' => __( 'A unique numeric ID for the Group.', 'buddypress' ),
				'type'        => 'integer',
			);

			$schema['properties']['is_mod'] = array(
				'context'     => array( 'view', 'edit' ),
				'description' => __( 'Whether the member is a group moderator.', 'buddypress' ),
				'type'        => 'boolean',
			);

			$schema['properties']['is_banned'] = array(
				'context'     => array( 'view', 'edit' ),
				'description' => __( 'Whether the member has been banned from the group.', 'buddypress' ),
				'type'        => 'boolean',
			);

			$schema['properties']['is_admin'] = array(
				'context'     => array( 'view', 'edit' ),
				'description' => __( 'Whether the member is a group administrator.', 'buddypress' ),
				'type'        => 'boolean',
			);

			$schema['properties']['is_confirmed'] = array(
				'context'     => array( 'view', 'edit' ),
				'description' => __( 'Whether the membership of this user has been confirmed.', 'buddypress' ),
				'type'        => 'boolean',
			);

			$schema['properties']['date_modified'] = array(
				'context'     => array( 'view', 'edit' ),
				'description' => __( 'The date of the last time the membership of this user was modified, in the site\'s timezone.', 'buddypress' ),
				'readonly'    => true,
				'type'        => array( 'string', 'null' ),
				'format'      => 'date-time',
			);

			$schema['properties']['date_modified_gmt'] = array(
				'context'     => array( 'view', 'edit' ),
				'description' => __( 'The date of the last time the membership of this user was modified, as GMT.', 'buddypress' ),
				'readonly'    => true,
				'type'        => array( 'string', 'null' ),
				'format'      => 'date-time',
			);

			// Cache current schema here.
			$this->schema = $schema;
		}

		/**
		 * Filters the group membership schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_group_members_schema', $this->add_additional_fields_schema( $this->schema ) );
	}

	/**
	 * Get the query params for collections of group memberships.
	 *
	 * @since 5.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';
		$statuses                     = array( 'last_joined', 'first_joined', 'alphabetical' );

		if ( bp_is_active( 'activity' ) ) {
			$statuses[] = 'group_activity';
		}

		$params['status'] = array(
			'description'       => __( 'Sort the order of results by the status of the group members.', 'buddypress' ),
			'default'           => 'last_joined',
			'type'              => 'string',
			'enum'              => $statuses,
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['roles'] = array(
			'description'       => __( 'Ensure result set includes specific group roles.', 'buddypress' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array_keys( bp_groups_get_group_roles() ),
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific member IDs.', 'buddypress' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude_admins'] = array(
			'description'       => __( 'Whether results should exclude group admins and mods.', 'buddypress' ),
			'default'           => true,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude_banned'] = array(
			'description'       => __( 'Whether results should exclude banned group members.', 'buddypress' ),
			'default'           => true,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_group_members_collection_params', $params );
	}
}
