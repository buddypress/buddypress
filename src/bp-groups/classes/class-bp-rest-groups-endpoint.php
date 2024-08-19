<?php
/**
 * BP REST: BP_REST_Groups_Endpoint class
 *
 * @package BuddyPress
 * @since 15.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Groups endpoints.
 *
 * @since 15.0.0
 */
class BP_REST_Groups_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 15.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->groups->id;
	}

	/**
	 * Register the component routes.
	 *
	 * @since 15.0.0
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
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the Group.', 'buddypress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context'         => $this->get_context_param( array( 'default' => 'view' ) ),
						'populate_extras' => array(
							'description'       => __( 'Whether to fetch extra BP data about the returned group.', 'buddypress' ),
							'context'           => array( 'view', 'edit' ),
							'default'           => false,
							'type'              => 'boolean',
							'sanitize_callback' => 'rest_sanitize_boolean',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/me',
			array(
				'args'   => array(
					'max' => array(
						'description' => __( 'The maximum amount of groups the user is member of to return. Defaults to all groups.', 'buddypress' ),
						'type'        => 'integer',
						'default'     => 0,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_current_user_groups' ),
					'permission_callback' => array( $this, 'get_current_user_groups_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve groups.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Request List of groups object data.
	 */
	public function get_items( $request ) {
		$args = array(
			'type'         => $request->get_param( 'type' ),
			'order'        => $request->get_param( 'order' ),
			'fields'       => $request->get_param( 'fields' ),
			'orderby'      => $request->get_param( 'orderby' ),
			'user_id'      => $request->get_param( 'user_id' ),
			'include'      => $request->get_param( 'include' ),
			'parent_id'    => $request->get_param( 'parent_id' ),
			'exclude'      => $request->get_param( 'exclude' ),
			'search_terms' => $request->get_param( 'search' ),
			'meta_query'   => $request->get_param( 'meta' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'group_type'   => $request->get_param( 'group_type' ),
			'show_hidden'  => $request->get_param( 'show_hidden' ),
			'status'       => $request->get_param( 'status' ),
			'per_page'     => $request->get_param( 'per_page' ),
			'page'         => $request->get_param( 'page' ),
		);

		if ( empty( $request->get_param( 'parent_id' ) ) ) {
			$args['parent_id'] = null;
		}

		// See if the user can see hidden groups.
		if ( ! empty( $request->get_param( 'show_hidden' ) ) && true === (bool) $request->get_param( 'show_hidden' ) && ! $this->can_see_hidden_groups( $request ) ) {
			$args['show_hidden'] = false;
		}

		if ( true === $request->get_param( 'populate_extras' ) ) {
			$args['update_meta_cache'] = true;
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 15.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters( 'bp_rest_groups_get_items_query_args', $args, $request );

		// Actually, query it.
		$groups = groups_get_groups( $args );

		// Users need (at least, should we be more restrictive ?) to be logged in to use the edit context.
		if ( 'edit' === $request->get_param( 'context' ) && ! is_user_logged_in() ) {
			$request->set_param( 'context', 'view' );
		}

		$retval = array();
		foreach ( $groups['groups'] as $group ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $group, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $groups['total'], $args['per_page'] );

		/**
		 * Fires after a list of groups is fetched via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param array            $groups   Fetched groups.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_groups_get_items', $groups, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to group items.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( bp_current_user_can( 'bp_view', array( 'bp_component' => 'groups' ) ) ) {
			$retval = true;
		}

		/**
		 * Filter the groups `get_items` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Whether the user has access to groups component items.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_groups_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a group.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_item( $request ) {
		$group    = $this->get_group_object( $request );
		$response = $this->prepare_item_for_response( $group, $request );

		/**
		 * Fires after a group is fetched via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Groups_Group  $group    Fetched group.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_groups_get_item', $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific group.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you cannot view the group.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( bp_current_user_can( 'bp_view', array( 'bp_component' => 'groups' ) ) ) {
			$group = $this->get_group_object( $request );

			if ( empty( $group->id ) ) {
				$retval = new WP_Error(
					'bp_rest_group_invalid_id',
					__( 'Invalid group ID.', 'buddypress' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( $this->can_see( $group ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the groups `get_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_groups_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a group.
	 *
	 * @since 15.0.0
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {

		// If no group name.
		if ( empty( $request->get_param( 'name' ) ) ) {
			return new WP_Error(
				'bp_rest_create_group_empty_name',
				__( 'Please, enter the name of group.', 'buddypress' ),
				array(
					'status' => 400,
				)
			);
		}

		$group_id = groups_create_group( $this->prepare_item_for_database( $request ) );

		if ( ! is_numeric( $group_id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_create_group',
				__( 'Cannot create new group.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$group         = $this->get_group_object( $group_id );
		$fields_update = $this->update_additional_fields_for_object( $group, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		// Set group type(s).
		if ( ! empty( $request->get_param( 'types' ) ) ) {
			bp_groups_set_group_type( $group_id, $request->get_param( 'types' ) );
		}

		$retval   = $this->prepare_item_for_response( $group, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group is created via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Groups_Group  $group    The created group.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_groups_create_item', $group, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create a group.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create groups.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$current_user_id = (int) bp_loggedin_user_id();
		$creator_id      = (int) $request->get_param( 'creator_id' );

		if ( ( $current_user_id && $current_user_id === $creator_id && bp_user_can_create_groups() ) || bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
		}

		/**
		 * Filter the groups `create_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_groups_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update a group.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$group_id = groups_create_group( $this->prepare_item_for_database( $request ) );

		if ( ! is_numeric( $group_id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_group',
				__( 'Cannot update existing group.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$group         = $this->get_group_object( $group_id );
		$fields_update = $this->update_additional_fields_for_object( $group, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval   = $this->prepare_item_for_response( $group, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group is updated via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Groups_Group  $group    The updated group.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_groups_update_item', $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a group.
	 *
	 * @since 15.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to update this group.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
		$group  = $this->get_group_object( $request );

		if ( empty( $group->id ) ) {
			$retval = new WP_Error(
				'bp_rest_group_invalid_id',
				__( 'Invalid group ID.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		} elseif ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to update this group.', 'buddypress' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		} elseif ( $this->can_user_delete_or_update( $group ) ) {
			$retval = true;
		}

		/**
		 * Filter the groups `update_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_groups_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a group.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		// Get the group before it's deleted.
		$group    = $this->get_group_object( $request );
		$previous = $this->prepare_item_for_response( $group, $request );

		if ( ! groups_delete_group( $group->id ) ) {
			return new WP_Error(
				'bp_rest_group_cannot_delete',
				__( 'Could not delete the group.', 'buddypress' ),
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

		/**
		 * Fires after a group is deleted via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param object           $group    The deleted group.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_groups_delete_item', $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a group.
	 *
	 * @since 15.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to delete this group.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
		$group  = $this->get_group_object( $request );

		if ( empty( $group->id ) ) {
			$retval = new WP_Error(
				'bp_rest_group_invalid_id',
				__( 'Invalid group ID.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		} elseif ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to delete this group.', 'buddypress' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		} elseif ( $this->can_user_delete_or_update( $group ) ) {
			$retval = true;
		}

		/**
		 * Filter the groups `delete_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_groups_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Retrieves the current user groups.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_current_user_groups( $request ) {
		$current_user_id = get_current_user_id();
		$max             = $request->get_param( 'max' );

		if ( empty( $current_user_id ) ) {
			return new WP_Error(
				'bp_rest_group_invalid_user_id',
				__( 'Invalid user ID.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		$per_page = -1;
		if ( $max ) {
			$per_page = (int) $max;
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 15.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters(
			'bp_rest_groups_get_current_user_groups_query_args',
			array(
				'user_id'            => $current_user_id,
				'per_page'           => $per_page,
				'page'               => 1,
				'show_hidden'        => true,
				'update_admin_cache' => false,
				'update_meta_cache'  => false,
			),
			$request
		);

		// Actually, query it.
		$groups = groups_get_groups( $args );

		$retval = array();
		foreach ( $groups['groups'] as $group ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $group, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after the user's list of groups is fetched via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param array            $groups   Fetched groups.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_groups_get_current_user_groups', $groups, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to fetch the user's groups.
	 *
	 * @since 15.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function get_current_user_groups_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to view your groups.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the groups `get_current_user_groups` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_groups_get_current_user_groups_permissions_check', $retval, $request );
	}

	/**
	 * Prepares group data for return as an object.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Groups_Group $item     The group object.
	 * @param WP_REST_Request $request  Full details about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'                 => $item->id,
			'creator_id'         => bp_get_group_creator_id( $item ),
			'parent_id'          => $item->parent_id,
			'date_created'       => bp_rest_prepare_date_response( $item->date_created, get_date_from_gmt( $item->date_created ) ),
			'date_created_gmt'   => bp_rest_prepare_date_response( $item->date_created ),
			'created_since'      => bp_core_time_since( $item->date_created ),
			'description'        => array(
				'raw'      => $item->description,
				'rendered' => bp_get_group_description( $item ),
			),
			'enable_forum'       => bp_group_is_forum_enabled( $item ),
			'link'               => bp_get_group_url( $item ),
			'name'               => bp_get_group_name( $item ),
			'slug'               => bp_get_group_slug( $item ),
			'status'             => bp_get_group_status( $item ),
			'types'              => bp_groups_get_group_type( $item->id, false ),
			'admins'             => array(),
			'mods'               => array(),
			'total_member_count' => 0,
			'last_activity'      => null,
			'last_activity_diff' => null,
		);

		// Return avatars, if allowed.
		if ( true !== bp_disable_group_avatar_uploads() ) {
			$data['avatar_urls'] = array(
				'full'  => bp_core_fetch_avatar(
					array(
						'html'    => false,
						'object'  => 'group',
						'item_id' => $item->id,
						'type'    => 'full',
					)
				),
				'thumb' => bp_core_fetch_avatar(
					array(
						'html'    => false,
						'object'  => 'group',
						'item_id' => $item->id,
						'type'    => 'thumb',
					)
				),
			);
		}

		// Get group type(s).
		if ( false === $data['types'] ) {
			$data['types'] = array();
		}

		$context = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';

		// If this is the 'edit' context or 'populate_extras' has been requested.
		if ( 'edit' === $context || true === $request->get_param( 'populate_extras' ) ) {
			$data['total_member_count'] = (int) $item->total_member_count;
			$data['last_activity']      = bp_rest_prepare_date_response( $item->last_activity, get_date_from_gmt( $item->last_activity ) );
			$data['last_activity_gmt']  = bp_rest_prepare_date_response( $item->last_activity );
			$data['last_activity_diff'] = bp_get_group_last_active( $item );
		}

		// If this is the 'edit' context, get more data about the group.
		if ( 'edit' === $context ) {
			// Add admins and moderators to their respective arrays.
			$admin_mods = groups_get_group_members(
				array(
					'group_id'   => $item->id,
					'group_role' => array(
						'admin',
						'mod',
					),
				)
			);

			foreach ( (array) $admin_mods['members'] as $user ) {
				// Make sure to unset private data.
				$private_keys = array_intersect(
					array_keys( get_object_vars( $user ) ),
					array(
						'user_pass',
						'user_email',
						'user_activation_key',
					)
				);

				foreach ( $private_keys as $private_key ) {
					unset( $user->{$private_key} );
				}

				if ( ! empty( $user->is_admin ) ) {
					$data['admins'][] = $user;
				} else {
					$data['mods'][] = $user;
				}
			}
		}

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		// Add prepare links.
		$response->add_links( $this->prepare_links( $item ) );

		/**
		 * Filter a group value returned from the API.
		 *
		 * @since 15.0.0
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BP_Groups_Group  $item     The group object.
		 */
		return apply_filters( 'bp_rest_groups_prepare_value', $response, $request, $item );
	}

	/**
	 * Prepare a group for create or update.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return stdClass|WP_Error
	 */
	protected function prepare_item_for_database( $request ) {
		$schema = $this->get_item_schema();
		$group  = $this->get_group_object( $request );

		if ( isset( $group->id ) && $group->id ) {
			$prepared_group = $group;
		} else {
			$prepared_group = new stdClass();
		}

		// Group ID.
		if ( ! empty( $group->id ) ) {
			$prepared_group->group_id = $group->id;
		}

		// Group Creator ID.
		if ( ! empty( $schema['properties']['creator_id'] ) && ! empty( $request->get_param( 'creator_id' ) ) ) {
			$prepared_group->creator_id = (int) $request->get_param( 'creator_id' );

			// Fallback on the current user otherwise.
		} else {
			$prepared_group->creator_id = bp_loggedin_user_id();
		}

		// Group Slug.
		if ( ! empty( $schema['properties']['slug'] ) && ! empty( $request->get_param( 'slug' ) ) ) {
			$prepared_group->slug = $request->get_param( 'slug' );
		}

		// Group Name.
		if ( ! empty( $schema['properties']['name'] ) && ! empty( $request->get_param( 'name' ) ) ) {
			$prepared_group->name = $request->get_param( 'name' );
		}

		// Do additional checks for the Group's slug.
		if ( WP_REST_Server::CREATABLE === $request->get_method() || ( isset( $group->slug ) && isset( $prepared_group->slug ) && $group->slug !== $prepared_group->slug ) ) {
			// Fallback on the group name if the slug is not defined.
			if ( ! isset( $prepared_group->slug ) && ! isset( $group->slug ) ) {
				$prepared_group->slug = $prepared_group->name;
			}

			// Make sure it is unique and sanitize it.
			$prepared_group->slug = groups_check_slug( sanitize_title( esc_attr( $prepared_group->slug ) ) );
		}

		// Group description.
		if ( ! empty( $schema['properties']['description'] ) && ! empty( $request->get_param( 'description' ) ) ) {
			if ( is_string( $request->get_param( 'description' ) ) ) {
				$prepared_group->description = $request->get_param( 'description' );
			} elseif ( isset( $request['description']['raw'] ) ) {
				$prepared_group->description = $request['description']['raw'];
			}
		}

		// Group status.
		if ( ! empty( $schema['properties']['status'] ) && ! empty( $request->get_param( 'status' ) ) ) {
			$prepared_group->status = $request->get_param( 'status' );
		}

		// Group Forum Enabled.
		if ( ! empty( $schema['properties']['enable_forum'] ) && ! empty( $request->get_param( 'enable_forum' ) ) ) {
			$prepared_group->enable_forum = (bool) $request->get_param( 'enable_forum' );
		}

		// Group Parent ID.
		if ( ! empty( $schema['properties']['parent_id'] ) && ! empty( $request->get_param( 'parent_id' ) ) ) {
			$prepared_group->parent_id = $request->get_param( 'parent_id' );
		}

		// Update group type(s).
		if ( isset( $prepared_group->group_id ) && ! empty( $request->get_param( 'types' ) ) ) {
			bp_groups_set_group_type( $prepared_group->group_id, $request->get_param( 'types' ), false );
		}

		// Remove group type(s).
		if ( isset( $prepared_group->group_id ) && ! empty( $request->get_param( 'remove_types' ) ) ) {
			array_map(
				function ( $type ) use ( $prepared_group ) {
					bp_groups_remove_group_type( $prepared_group->group_id, $type );
				},
				$request->get_param( 'remove_types' )
			);
		}

		// Append group type(s).
		if ( isset( $prepared_group->group_id ) && ! empty( $request->get_param( 'append_types' ) ) ) {
			bp_groups_set_group_type( $prepared_group->group_id, $request->get_param( 'append_types' ), true );
		}

		/**
		 * Filters a group before it is inserted or updated via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param stdClass        $prepared_group An object prepared for inserting or updating the database.
		 * @param WP_REST_Request $request        Request object.
		 */
		return apply_filters( 'bp_rest_groups_pre_insert_value', $prepared_group, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Groups_Group $group Group object.
	 * @return array
	 */
	protected function prepare_links( $group ) {
		$base  = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . $group->id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		// Embed group creator if available.
		if ( ! empty( $group->creator_id ) ) {
			$links['user'] = array(
				'href'       => bp_rest_get_object_url( $group->creator_id, 'members' ),
				'embeddable' => true,
			);
		}

		// Embed parent group if available.
		if ( ! empty( $group->parent ) ) {
			$links['parent'] = array(
				'href'       => rest_url( $base . $group->parent ),
				'embeddable' => true,
			);
		}

		// Actions.

		$user_id = bp_loggedin_user_id();

		if ( is_user_logged_in() && false === bp_group_is_user_banned( $group->id, $user_id ) ) {
			$membership_namespace = "{$this->rest_base}/membership-requests";
			$request_id           = groups_check_for_membership_request( $user_id, $group->id );
			$is_group_member      = wp_validate_boolean( $group->is_member );

			switch ( true ) {
				// The logged in user is not a member of a private group, action to: request a membership.
				case 'private' === $group->status && false === $is_group_member:
					$links['bp-action-group-membership-request-membership'] = array(
						'href'     => rest_url( sprintf( '/%1$s/%2$s/', $this->namespace, $membership_namespace ) ),
						'group_id' => $group->id,
						'user_id'  => $user_id,
					);
					break;

				// The logged in user already requested a membership to the group, action to: withdraw the request.
				case is_numeric( $request_id ):
					$links['bp-action-group-membership-withdraw-request'] = array(
						'href' => bp_rest_get_object_url( $request_id, $membership_namespace ),
					);
					break;

				// The logged in user is a member of the group, action to: leave.
				case true === $is_group_member:
					$links['bp-action-group-leave'] = array(
						'href' => bp_rest_get_object_url( $user_id, "{$this->rest_base}/{$group->id}/members" ),
					);
					break;

				// The logged in user is not a member of a public group, action to: join.
				case 'public' === $group->status && false === $is_group_member:
					$links['bp-action-group-join'] = array(
						'href'     => rest_url( sprintf( '/%1$s/%2$s/', $this->namespace, "{$this->rest_base}/{$group->id}/members" ) ),
						'group_id' => $group->id,
						'user_id'  => $user_id,
					);
					break;

				// The logged in user is invited to join the group, actions to: accept and reject invite.
				case true === $group->is_invited:
					$invites_class = new BP_Groups_Invitation_Manager();
					$ids           = $invites_class->get_invitations(
						array(
							'user_id' => $user_id,
							'item_id' => $group->id,
							'fields'  => 'ids',
						)
					);

					$invite_action = array( 'href' => bp_rest_get_object_url( $ids[0], "{$this->rest_base}/{$group->id}/invites" ) );

					$links['bp-action-group-accept-invite'] = $invite_action;
					$links['bp-action-group-reject-invite'] = $invite_action;
					break;
			}
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 15.0.0
		 *
		 * @param array           $links The prepared links of the REST response.
		 * @param BP_Groups_Group $group Group object.
		 */
		return apply_filters( 'bp_rest_groups_prepare_links', $links, $group );
	}

	/**
	 * See if user can delete or update a group.
	 *
	 * @since 15.0.0
	 *
	 * @param  BP_Groups_Group $group Group item.
	 * @return bool
	 */
	protected function can_user_delete_or_update( $group ) {
		return ( bp_current_user_can( 'bp_moderate' ) || groups_is_user_admin( bp_loggedin_user_id(), $group->id ) );
	}

	/**
	 * Can a user see a group?
	 *
	 * @since 15.0.0
	 *
	 * @param  BP_Groups_Group $group Group object.
	 * @return bool
	 */
	public function can_see( $group ) {
		// If it is not a hidden group, user can see it.
		if ( 'hidden' !== $group->status ) {
			return true;
		}

		// Check for moderators or if user is a member of the group.
		return ( bp_current_user_can( 'bp_moderate' ) || groups_is_user_member( bp_loggedin_user_id(), $group->id ) );
	}

	/**
	 * Can this user see hidden groups?
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool
	 */
	protected function can_see_hidden_groups( $request ) {
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			return true;
		}

		return (
			is_user_logged_in()
			&& ! empty( $request->get_param( 'user_id' ) )
			&& absint( $request->get_param( 'user_id' ) ) === bp_loggedin_user_id()
		);
	}

	/**
	 * Get group object.
	 *
	 * @since 15.0.0
	 *
	 * @param  int|WP_REST_Request $request Full details about the request or an group integer.
	 * @return false|BP_Groups_Group
	 */
	public function get_group_object( $request ) {
		if ( $request instanceof WP_REST_Request ) {
			if ( ! empty( $request->get_param( 'group_id' ) ) ) {
				$group_id = (int) $request->get_param( 'group_id' );
			} else {
				$group_id = (int) $request->get_param( 'id' );
			}
		} elseif ( is_numeric( $request ) ) {
			$group_id = (int) $request;
		}

		$group = groups_get_group( $group_id );

		if ( empty( $group ) || empty( $group->id ) ) {
			return false;
		}

		return $group;
	}

	/**
	 * Edit some arguments for the endpoint's CREATABLE and EDITABLE methods.
	 *
	 * @since 15.0.0
	 *
	 * @param string $method Optional. HTTP method of the request.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = parent::get_endpoint_args_for_item_schema( $method );
		$key  = 'get_item';

		if ( WP_REST_Server::CREATABLE === $method || WP_REST_Server::EDITABLE === $method ) {
			$key                         = 'create_item';
			$args['description']['type'] = 'string';

			// Add group types.
			$args['types'] = array(
				'description'       => __( 'Assign one or more type to a group. To assign more than one type, use a comma separated list of types.', 'buddypress' ),
				'type'              => 'string',
				'enum'              => bp_groups_get_group_types(),
				'sanitize_callback' => 'bp_rest_sanitize_group_types',
				'validate_callback' => 'bp_rest_validate_group_types',
			);

			if ( WP_REST_Server::EDITABLE === $method ) {
				$key = 'update_item';
				unset( $args['slug'] );

				// Append group types.
				$args['append_types'] = array(
					'description'       => __( 'Append one or more type to a group. To append more than one type, use a comma separated list of types.', 'buddypress' ),
					'type'              => 'string',
					'enum'              => bp_groups_get_group_types(),
					'sanitize_callback' => 'bp_rest_sanitize_group_types',
					'validate_callback' => 'bp_rest_validate_group_types',
				);

				// Remove group types.
				$args['remove_types'] = array(
					'description'       => __( 'Remove one or more type of a group. To remove more than one type, use a comma separated list of types.', 'buddypress' ),
					'type'              => 'string',
					'enum'              => bp_groups_get_group_types(),
					'sanitize_callback' => 'bp_rest_sanitize_group_types',
					'validate_callback' => 'bp_rest_validate_group_types',
				);
			}
		} elseif ( WP_REST_Server::DELETABLE === $method ) {
			$key = 'delete_item';
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @since 15.0.0
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 */
		return apply_filters( "bp_rest_groups_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the group schema, conforming to JSON Schema.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( is_null( $this->schema ) ) {
			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'bp_groups',
				'type'       => 'object',
				'properties' => array(
					'id'                 => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'A unique numeric ID for the Group.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'integer',
					),
					'creator_id'         => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The ID of the user who created the Group.', 'buddypress' ),
						'type'        => 'integer',
						'default'     => bp_loggedin_user_id(),
					),
					'name'               => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The name of the Group.', 'buddypress' ),
						'type'        => 'string',
						'required'    => true,
						'arg_options' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
					'slug'               => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The URL-friendly slug for the Group.', 'buddypress' ),
						'type'        => 'string',
						'arg_options' => array(
							'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
						),
					),
					'link'               => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The permalink to the Group on the site.', 'buddypress' ),
						'type'        => 'string',
						'format'      => 'uri',
						'readonly'    => true,
					),
					'description'        => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The description of the Group.', 'buddypress' ),
						'type'        => 'object',
						'required'    => true,
						'arg_options' => array(
							'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
							'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Content for the description of the Group, as it exists in the database.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit', 'embed' ),
							),
							'rendered' => array(
								'description' => __( 'HTML content for the description of the Group, transformed for display.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
						),
					),
					'status'             => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The status of the Group.', 'buddypress' ),
						'type'        => 'string',
						'enum'        => buddypress()->groups->valid_status,
						'default'     => 'public',
						'arg_options' => array(
							'sanitize_callback' => 'sanitize_key',
						),
					),
					'enable_forum'       => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'Whether the Group has a forum enabled or not.', 'buddypress' ),
						'type'        => 'boolean',
					),
					'parent_id'          => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'ID of the parent Group.', 'buddypress' ),
						'type'        => 'integer',
					),
					'date_created'       => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The date the Group was created, in the site\'s timezone.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'date_created_gmt'   => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The date the Group was created, as GMT.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'created_since'      => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'Time elapsed since the Group was created, in the site\'s timezone.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'string',
						'default'     => '',
					),
					'types'              => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The type(s) of the Group.', 'buddypress' ),
						'readonly'    => true,
						'enum'        => bp_groups_get_group_types(),
						'type'        => 'array',
						'items'       => array(
							'type' => 'string',
						),
					),
					'admins'             => array(
						'context'     => array( 'edit' ),
						'description' => __( 'Group administrators.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'array',
						'items'       => array(
							'type' => 'object',
						),
					),
					'mods'               => array(
						'context'     => array( 'edit' ),
						'description' => __( 'Group moderators.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'array',
						'items'       => array(
							'type' => 'object',
						),
					),
					'total_member_count' => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'Count of all Group members.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'integer',
					),
					'last_activity'      => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The date the Group was last active, in the site\'s timezone.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'last_activity_gmt'  => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The date the Group was last active, as GMT.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'last_activity_diff' => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The human diff time the Group was last active, in the site\'s timezone.', 'buddypress' ),
						'type'        => 'string',
						'readonly'    => true,
					),
				),
			);

			if ( true !== bp_disable_group_avatar_uploads() ) {
				$avatar_properties = array();

				$avatar_properties['full'] = array(
					/* translators: 1: Full avatar width in pixels. 2: Full avatar height in pixels */
					'description' => sprintf( __( 'Avatar URL with full image size (%1$d x %2$d pixels).', 'buddypress' ), number_format_i18n( bp_core_avatar_full_width() ), number_format_i18n( bp_core_avatar_full_height() ) ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit', 'embed' ),
				);

				$avatar_properties['thumb'] = array(
					/* translators: 1: Thumb avatar width in pixels. 2: Thumb avatar height in pixels */
					'description' => sprintf( __( 'Avatar URL with thumb image size (%1$d x %2$d pixels).', 'buddypress' ), number_format_i18n( bp_core_avatar_thumb_width() ), number_format_i18n( bp_core_avatar_thumb_height() ) ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit', 'embed' ),
				);

				$schema['properties']['avatar_urls'] = array(
					'description' => __( 'Avatar URLs for the group.', 'buddypress' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
					'properties'  => $avatar_properties,
				);
			}

			// Cache current schema here.
			$this->schema = $schema;
		}

		/**
		 * Filters the group schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_group_schema', $this->add_additional_fields_schema( $this->schema ) );
	}

	/**
	 * Get the query params for collections of groups.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['type'] = array(
			'description'       => __( 'Shorthand for certain orderby/order combinations.', 'buddypress' ),
			'default'           => 'active',
			'type'              => 'string',
			'enum'              => array( 'active', 'newest', 'alphabetical', 'random', 'popular' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'buddypress' ),
			'default'           => 'desc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Order Groups by which attribute.', 'buddypress' ),
			'default'           => 'date_created',
			'type'              => 'string',
			'enum'              => array( 'date_created', 'last_activity', 'total_member_count', 'name', 'random' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'description'       => __( 'Group statuses to limit results to.', 'buddypress' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array(
				'enum' => buddypress()->groups->valid_status,
				'type' => 'string',
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Pass a user ID to limit to only Groups that this user is a member of.', 'buddypress' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['parent_id'] = array(
			'description'       => __( 'Get Groups that are children of the specified Group(s) IDs.', 'buddypress' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		// @todo Confirm what's the proper sanitization here.
		$params['meta'] = array(
			'description'       => __( 'Get Groups based on their meta data information.', 'buddypress' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'string' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['include'] = array(
			'description'       => __( 'Ensure result set includes Groups with specific IDs.', 'buddypress' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes Groups with specific IDs', 'buddypress' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['group_type'] = array(
			'description'       => __( 'Limit results set to a certain Group type.', 'buddypress' ),
			'default'           => '',
			'type'              => 'string',
			'enum'              => bp_groups_get_group_types(),
			'sanitize_callback' => 'bp_rest_sanitize_group_types',
			'validate_callback' => 'bp_rest_validate_group_types',
		);

		$params['enable_forum'] = array(
			'description'       => __( 'Whether the Group has a forum enabled or not.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['show_hidden'] = array(
			'description'       => __( 'Whether results should include hidden Groups.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['populate_extras'] = array(
			'description'       => __( 'Whether to fetch extra BP data about the returned groups.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_groups_collection_params', $params );
	}
}
