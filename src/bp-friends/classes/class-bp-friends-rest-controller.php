<?php
/**
 * BP_Friends_REST_Controller class
 *
 * @package BuddyPress
 * @since 15.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Friendship endpoints.
 *
 * /friends/
 * /friends/{id}
 *
 * @since 15.0.0
 */
class BP_Friends_REST_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 15.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->friends->id;
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
			'/' . $this->rest_base . '/(?P<id>[\w-]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Numeric identifier of a user ID.', 'buddypress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::READABLE ),
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
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::DELETABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve friendships.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$args = array(
			'id'                => $request->get_param( 'id' ),
			'initiator_user_id' => $request->get_param( 'initiator_id' ),
			'friend_user_id'    => $request->get_param( 'friend_id' ),
			'is_confirmed'      => $request->get_param( 'is_confirmed' ),
			'order_by'          => $request->get_param( 'order_by' ),
			'sort_order'        => strtoupper( $request->get_param( 'order' ) ),
			'page'              => $request->get_param( 'page' ),
			'per_page'          => $request->get_param( 'per_page' ),
		);

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 15.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters( 'bp_rest_friends_get_items_query_args', $args, $request );

		// null is the default values.
		foreach ( $args as $key => $value ) {
			if ( empty( $value ) ) {
				$args[ $key ] = null;
			}
		}

		// Check if user is valid.
		$user = get_user_by( 'id', $request->get_param( 'user_id' ) );
		if ( ! $user instanceof WP_User ) {
			return new WP_Error(
				'bp_rest_friends_get_items_user_failed',
				__( 'There was a problem confirming if user is valid.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		// Actually, query it.
		$friendships = BP_Friends_Friendship::get_friendships( $user->ID, $args );

		$retval = array();
		foreach ( $friendships as $friendship ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $friendship, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, count( $friendships ), $args['per_page'] );

		/**
		 * Fires after friendships are fetched via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param array            $friendships Fetched friendships.
		 * @param WP_REST_Response $response    The response data.
		 * @param WP_REST_Request  $request     The request sent to the API.
		 */
		do_action( 'bp_rest_friends_get_items', $friendships, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to friendship items.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to perform this action.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the friends `get_items` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_friends_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve single friendship.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$user = get_user_by( 'id', $request->get_param( 'id' ) );

		// Check if user is valid.
		if ( false === $user ) {
			return new WP_Error(
				'bp_rest_friends_get_item_failed',
				__( 'There was a problem confirming if user is valid.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		// Get friendship.
		$friendship = $this->get_friendship_object(
			BP_Friends_Friendship::get_friendship_id( bp_loggedin_user_id(), $user->ID )
		);

		if ( ! $friendship || empty( $friendship->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Friendship does not exist.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval   = $this->prepare_item_for_response( $friendship, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires before a friendship is retrieved via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Friends_Friendship $friendship  The friendship object.
		 * @param WP_REST_Response      $response    The response data.
		 * @param WP_REST_Request       $request     The request sent to the API.
		 */
		do_action( 'bp_rest_friends_get_item', $friendship, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get a friendship.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to perform this action.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the friendship `get_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_friends_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a new friendship.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$initiator_id = get_user_by( 'id', $request->get_param( 'initiator_id' ) );
		$friend_id    = get_user_by( 'id', $request->get_param( 'friend_id' ) );

		// Check if users are valid.
		if ( ! $initiator_id || ! $friend_id ) {
			return new WP_Error(
				'bp_rest_friends_create_item_failed',
				__( 'There was a problem confirming if user is valid.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		// Check if users are friends or if there is a friendship request.
		if ( 'not_friends' !== friends_check_friendship_status( $initiator_id->ID, $friend_id->ID ) ) {
			return new WP_Error(
				'bp_rest_friends_create_item_failed',
				__( 'Those users are already friends or have sent friendship request(s) recently.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$is_moderator    = bp_current_user_can( 'bp_moderate' );
		$current_user_id = bp_loggedin_user_id();

		/**
		 * - Only admins can create friendship requests for other people.
		 * - Admins can't create friendship requests to themselves from other people.
		 * - Users can't create friendship requests to themselves from other people.
		 */
		if (
			( $current_user_id !== $initiator_id->ID && ! $is_moderator )
			|| ( $current_user_id === $friend_id->ID && $is_moderator )
			|| ( ! in_array( $current_user_id, array( $initiator_id->ID, $friend_id->ID ), true ) && ! $is_moderator )
		) {
			return new WP_Error(
				'bp_rest_friends_create_item_failed',
				__( 'You are not allowed to perform this action.', 'buddypress' ),
				array(
					'status' => 403,
				)
			);
		}

		// Only admins can force a friendship request.
		$force = ( true === $request->get_param( 'force' ) && $is_moderator );

		// Adding friendship.
		if ( ! friends_add_friend( $initiator_id->ID, $friend_id->ID, $force ) ) {
			return new WP_Error(
				'bp_rest_friends_create_item_failed',
				__( 'There was an error trying to create the friendship.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		// Get friendship.
		$friendship = $this->get_friendship_object(
			BP_Friends_Friendship::get_friendship_id( $initiator_id->ID, $friend_id->ID )
		);

		if ( ! $friendship || empty( $friendship->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Friendship does not exist.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval   = $this->prepare_item_for_response( $friendship, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a friendship is created via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Friends_Friendship $friendship The friendship object.
		 * @param WP_REST_Response      $retval     The response data.
		 * @param WP_REST_Request       $request    The request sent to the API.
		 */
		do_action( 'bp_rest_friends_create_item', $friendship, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a friendship.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the friends `create_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_friends_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update, accept, friendship.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$user = get_user_by( 'id', $request->get_param( 'id' ) );

		// Check if user is valid.
		if ( false === $user ) {
			return new WP_Error(
				'bp_rest_friends_update_item_failed',
				__( 'There was a problem confirming if user is valid.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		// Get friendship.
		$friendship = $this->get_friendship_object(
			BP_Friends_Friendship::get_friendship_id( bp_loggedin_user_id(), $user->ID )
		);

		if ( ! $friendship || empty( $friendship->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid friendship ID.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		// Accept friendship.
		if ( false === friends_accept_friendship( $friendship->id ) ) {
			return new WP_Error(
				'bp_rest_friends_cannot_update_item',
				__( 'Could not accept friendship.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		// Getting new, updated, friendship object.
		$friendship = $this->get_friendship_object( $friendship->id );
		$retval     = $this->prepare_item_for_response( $friendship, $request );
		$response   = rest_ensure_response( $retval );

		/**
		 * Fires after a friendship is updated via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Friends_Friendship $friendship Friendship object.
		 * @param WP_REST_Response      $response   The response data.
		 * @param WP_REST_Request       $request    The request sent to the API.
		 */
		do_action( 'bp_rest_friends_update_item', $friendship, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a friendship.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the friendship `update_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_friends_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Reject/withdraw/remove friendship.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$user = get_user_by( 'id', $request->get_param( 'id' ) );

		// Check if user is valid.
		if ( false === $user ) {
			return new WP_Error(
				'bp_rest_friends_delete_item_failed',
				__( 'There was a problem confirming if user is valid.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		// Get friendship.
		$friendship = $this->get_friendship_object(
			BP_Friends_Friendship::get_friendship_id( bp_loggedin_user_id(), $user->ID )
		);

		if ( ! $friendship || empty( $friendship->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid friendship ID.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		$previous = $this->prepare_item_for_response( $friendship, $request );

		// Remove a friendship.
		if ( true === $request->get_param( 'force' ) ) {
			$deleted = friends_remove_friend( $friendship->initiator_user_id, $friendship->friend_user_id );

			/**
			 * If this change is being initiated by the initiator,
			 * use the `reject` function.
			 *
			 * This is the user who requested the friendship, and is doing the withdrawing.
			 */
		} elseif ( bp_loggedin_user_id() === $friendship->initiator_user_id ) {
			$deleted = friends_withdraw_friendship( $friendship->initiator_user_id, $friendship->friend_user_id );
		} else {
			/**
			 * Otherwise, this change is being initiated by the user, friend,
			 * who received the friendship reject.
			 */
			$deleted = friends_reject_friendship( $friendship->id );
		}

		if ( false === $deleted ) {
			return new WP_Error(
				'bp_rest_friends_cannot_delete_item',
				__( 'Could not delete friendship.', 'buddypress' ),
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
		 * Fires after a friendship is deleted via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Friends_Friendship $friendship Friendship object.
		 * @param WP_REST_Response      $response   The response data.
		 * @param WP_REST_Request       $request    The request sent to the API.
		 */
		do_action( 'bp_rest_friends_delete_item', $friendship, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a friendship.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the friendship `delete_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_friends_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares friendship data to return as an object.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Friends_Friendship $friendship Friendship object.
	 * @param WP_REST_Request       $request    Full details about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $friendship, $request ) {
		$data = array(
			'id'               => (int) $friendship->id,
			'initiator_id'     => (int) $friendship->initiator_user_id,
			'friend_id'        => (int) $friendship->friend_user_id,
			'is_confirmed'     => (bool) $friendship->is_confirmed,
			'date_created'     => bp_rest_prepare_date_response( $friendship->date_created, get_date_from_gmt( $friendship->date_created ) ),
			'date_created_gmt' => bp_rest_prepare_date_response( $friendship->date_created ),
		);

		$context  = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		// Add prepare links.
		$response->add_links( $this->prepare_links( $friendship ) );

		/**
		 * Filter a friendship value returned from the API.
		 *
		 * @since 15.0.0
		 *
		 * @param WP_REST_Response      $response   Response generated by the request.
		 * @param WP_REST_Request       $request    Request used to generate the response.
		 * @param BP_Friends_Friendship $friendship The friendship object.
		 */
		return apply_filters( 'bp_rest_friends_prepare_value', $response, $request, $friendship );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Friends_Friendship $friendship Friendship object.
	 * @return array
	 */
	protected function prepare_links( $friendship ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . $friendship->id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'initiator'  => array(
				'href'       => bp_rest_get_object_url( $friendship->initiator_user_id, 'members' ),
				'embeddable' => true,
			),
			'friend'     => array(
				'href'       => bp_rest_get_object_url( $friendship->friend_user_id, 'members' ),
				'embeddable' => true,
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 15.0.0
		 *
		 * @param array                 $links      The prepared links of the REST response.
		 * @param BP_Friends_Friendship $friendship Friendship object.
		 */
		return apply_filters( 'bp_rest_friends_prepare_links', $links, $friendship );
	}

	/**
	 * Get friendship object.
	 *
	 * @since 15.0.0
	 *
	 * @param int $friendship_id Friendship ID.
	 * @return BP_Friends_Friendship
	 */
	public function get_friendship_object( $friendship_id ) {
		return new BP_Friends_Friendship( (int) $friendship_id );
	}

	/**
	 * Edit some arguments for the endpoint's methods.
	 *
	 * @since 15.0.0
	 *
	 * @param string $method Optional. HTTP method of the request.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args    = parent::get_endpoint_args_for_item_schema( $method );
		$context = 'view';

		$args['id']['required']    = true;
		$args['id']['description'] = __( 'A unique numeric ID of a user.', 'buddypress' );

		if ( WP_REST_Server::EDITABLE === $method ) {
			$key = 'update_item';

			unset( $args['initiator_id'] );
			unset( $args['friend_id'] );
		} elseif ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			// Remove the ID for POST requests since it is not available.
			unset( $args['id'] );

			// Those fields are required.
			$args['initiator_id']['required'] = true;
			$args['friend_id']['required']    = true;

			// This one is optional.
			$args['force'] = array(
				'description'       => __( 'Whether to force the friendship agreement.', 'buddypress' ),
				'default'           => false,
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			);

		} elseif ( WP_REST_Server::DELETABLE === $method ) {
			$key = 'delete_item';

			// This one is optional.
			$args['force'] = array(
				'description'       => __( 'Whether to force friendship removal.', 'buddypress' ),
				'default'           => false,
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			);

			unset( $args['initiator_id'] );
			unset( $args['friend_id'] );
		} elseif ( WP_REST_Server::READABLE === $method ) {
			$key = 'get_item';

			$args['id']['required'] = true;

			// Removing those args from the GET request.
			unset( $args['initiator_id'] );
			unset( $args['friend_id'] );
		}

		if ( 'get_item' !== $key ) {
			$context = 'edit';
		}

		$args = array_merge(
			array(
				'context' => $this->get_context_param(
					array(
						'default' => $context,
					)
				),
			),
			$args
		);

		/**
		 * Filters the method query arguments.
		 *
		 * @since 15.0.0
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 */
		return apply_filters( "bp_rest_friends_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the friends schema, conforming to JSON Schema.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( is_null( $this->schema ) ) {
			$this->schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'bp_friends',
				'type'       => 'object',
				'properties' => array(
					'id'               => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Unique numeric identifier of the friendship.', 'buddypress' ),
						'type'        => 'integer',
					),
					'initiator_id'     => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The unique numeric identifier of the user who is requesting the Friendship.', 'buddypress' ),
						'type'        => 'integer',
					),
					'friend_id'        => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The unique numeric identifier of the user who is invited to agree to the Friendship request.', 'buddypress' ),
						'type'        => 'integer',
					),
					'is_confirmed'     => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Whether the friendship been confirmed/accepted.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'boolean',
					),
					'date_created'     => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The date the friendship was created, in the site\'s timezone.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'date_created_gmt' => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The date the friendship was created, as GMT.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
				),
			);
		}

		/**
		 * Filters the friends schema.
		 *
		 * @since 15.0.0
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_friends_schema', $this->add_additional_fields_schema( $this->schema ) );
	}

	/**
	 * Get the query params for friends collections.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		unset( $params['search'] );

		$params['user_id'] = array(
			'description'       => __( 'ID of the member whose friendships are being retrieved.', 'buddypress' ),
			'default'           => bp_loggedin_user_id(),
			'type'              => 'integer',
			'required'          => true,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['is_confirmed'] = array(
			'description'       => __( 'Wether the friendship has been accepted.', 'buddypress' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['id'] = array(
			'description'       => __( 'Unique numeric identifier of the friendship.', 'buddypress' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['initiator_id'] = array(
			'description'       => __( 'The ID of the user who is requesting the Friendship.', 'buddypress' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['friend_id'] = array(
			'description'       => __( 'The ID of the user who is invited to agree to the Friendship request.', 'buddypress' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order_by'] = array(
			'description'       => __( 'Column name to order the results by.', 'buddypress' ),
			'default'           => 'date_created',
			'type'              => 'string',
			'enum'              => array( 'date_created', 'initiator_user_id', 'friend_user_id', 'id' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order'] = array(
			'description'       => __( 'Order results ascending or descending.', 'buddypress' ),
			'default'           => 'desc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_friends_collection_params', $params );
	}
}
