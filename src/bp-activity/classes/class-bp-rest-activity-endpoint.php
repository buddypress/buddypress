<?php
/**
 * BP_REST_Activity_Endpoint class
 *
 * @package BuddyPress
 * @subpackage BuddyPress\RestApi
 * @since 15.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activity endpoints.
 *
 * @extends WP_REST_Controller
 * @since 15.0.0
 */
class BP_REST_Activity_Endpoint extends WP_REST_Controller {

	/**
	 * User favorites.
	 *
	 * @since 15.0.0
	 *
	 * @var array|null
	 */
	protected $user_favorites = null;

	/**
	 * Constructor.
	 *
	 * @since 15.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->activity->id;
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

		$activity_endpoint = '/' . $this->rest_base . '/(?P<id>[\d]+)';

		register_rest_route(
			$this->namespace,
			$activity_endpoint,
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the activity.', 'buddypress' ),
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

		// Register the favorite route.
		register_rest_route(
			$this->namespace,
			$activity_endpoint . '/favorite',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the activity.', 'buddypress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_favorite' ),
					'permission_callback' => array( $this, 'update_favorite_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve activities.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response List of activities response data.
	 */
	public function get_items( $request ) {
		$args = array(
			'exclude'           => $request->get_param( 'exclude' ),
			'in'                => $request->get_param( 'include' ),
			'page'              => $request->get_param( 'page' ),
			'per_page'          => $request->get_param( 'per_page' ),
			'search_terms'      => $request->get_param( 'search' ),
			'sort'              => $request->get_param( 'order' ),
			'spam'              => $request->get_param( 'status' ),
			'display_comments'  => $request->get_param( 'display_comments' ),
			'site_id'           => $request->get_param( 'site_id' ),
			'group_id'          => $request->get_param( 'group_id' ),
			'scope'             => $request->get_param( 'scope' ),
			'count_total'       => true,
			'fields'            => 'all',
			'show_hidden'       => false,
			'update_meta_cache' => true,
			'filter'            => array(),
		);

		if ( empty( $args['display_comments'] ) || 'false' === $args['display_comments'] ) {
			$args['display_comments'] = false;
		}

		if ( empty( $request->get_param( 'exclude' ) ) ) {
			$args['exclude'] = false;
		}

		if ( empty( $request->get_param( 'include' ) ) ) {
			$args['in'] = false;
		}

		if ( ! empty( $request->get_param( 'after' ) ) ) {
			$args['since'] = $request->get_param( 'after' );
		}

		if ( ! empty( $request->get_param( 'user_id' ) ) ) {
			$args['filter']['user_id'] = $request->get_param( 'user_id' );
		}

		$item_id = 0;
		if ( ! empty( $args['group_id'] ) ) {
			$args['filter']['object']     = 'groups';
			$args['filter']['primary_id'] = $args['group_id'];

			if ( empty( $request->get_param( 'component' ) ) ) {
				$request->set_param( 'component', 'groups' );
			}

			$item_id = $args['group_id'];
		}

		if ( ! empty( $args['site_id'] ) ) {
			$args['filter']['object']     = 'blogs';
			$args['filter']['primary_id'] = $args['site_id'];

			$item_id = $args['site_id'];
		}

		if ( empty( $args['group_id'] ) && empty( $args['site_id'] ) ) {
			if ( ! empty( $request->get_param( 'component' ) ) ) {
				$args['filter']['object'] = $request->get_param( 'component' );
			}

			if ( ! empty( $request->get_param( 'primary_id' ) ) ) {
				$item_id                      = $request->get_param( 'primary_id' );
				$args['filter']['primary_id'] = $item_id;
			}
		}

		if ( empty( $request->get_param( 'scope' ) ) ) {
			$args['scope'] = false;
		}

		if ( ! empty( $request->get_param( 'type' ) ) ) {
			$args['filter']['action'] = $request->get_param( 'type' );
		}

		if ( ! empty( $request->get_param( 'secondary_id' ) ) ) {
			$args['filter']['secondary_id'] = $request->get_param( 'secondary_id' );
		}

		if ( $args['in'] ) {
			$args['count_total'] = false;
		}

		if ( $this->show_hidden( $request->get_param( 'component' ), $item_id ) ) {
			$args['show_hidden'] = true;
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 15.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request Full data about the request.
		 */
		$args = apply_filters( 'bp_rest_activity_get_items_query_args', $args, $request );

		// Actually, query it.
		$activities = bp_activity_get( $args );

		$retval = array();
		foreach ( $activities['activities'] as $activity ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $activity, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $activities['total'], $args['per_page'] );

		/**
		 * Fires after a list of activities is fetched via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param array            $activities Fetched activities.
		 * @param WP_REST_Response $response   The response data.
		 * @param WP_REST_Request  $request    Full data about the request.
		 */
		do_action( 'bp_rest_activity_get_items', $activities, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to activity items.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
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

		if ( bp_current_user_can( 'bp_view', array( 'bp_component' => 'activity' ) ) ) {
			$retval = true;
		}

		/**
		 * Filter the activity `get_items` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request Full data about the request.
		 */
		return apply_filters( 'bp_rest_activity_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve an activity.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$activity = $this->get_activity_object( $request );

		if ( empty( $activity->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid activity ID.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval   = $this->prepare_item_for_response( $activity, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an activity is fetched via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Activity_Activity $activity Fetched activity.
		 * @param WP_REST_Response     $response The response data.
		 * @param WP_REST_Request      $request  The request sent to the API.
		 */
		do_action( 'bp_rest_activity_get_item', $activity, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific activity.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you cannot view the activities.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( bp_current_user_can( 'bp_view', array( 'bp_component' => 'activity' ) ) && $this->can_see( $request ) ) {
			$retval = true;
		}

		/**
		 * Filter the activity `get_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_activity_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create an activity.
	 *
	 * @since 15.0.0
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		if ( empty( $request->get_param( 'content' ) ) ) {
			return new WP_Error(
				'bp_rest_create_activity_empty_content',
				__( 'Please, enter some content.', 'buddypress' ),
				array(
					'status' => 400,
				)
			);
		}

		$prepared_activity = $this->prepare_item_for_database( $request );

		// Fallback for the activity_update type.
		$type = 'activity_update';
		if ( ! empty( $request->get_param( 'type' ) ) ) {
			$type = $request->get_param( 'type' );
		}

		$prime       = $request->get_param( 'primary_item_id' );
		$activity_id = 0;

		// Post a regular activity update.
		if ( 'activity_update' === $type ) {
			if ( bp_is_active( 'groups' ) && ! is_null( $prime ) ) {
				$activity_id = groups_post_update( $prepared_activity );
			} else {
				$activity_id = bp_activity_post_update( $prepared_activity );
			}

			// Post an activity comment.
		} elseif ( 'activity_comment' === $type ) {

			// ID of the root activity item.
			if ( isset( $prime ) ) {
				$prepared_activity->activity_id = (int) $prime;
			}

			// ID of a parent comment.
			if ( ! empty( $request->get_param( 'secondary_item_id' ) ) ) {
				$prepared_activity->parent_id = $request->get_param( 'secondary_item_id' );
			}

			$activity_id = bp_activity_new_comment( $prepared_activity );

			// Otherwise add an activity.
		} else {
			$activity_id = bp_activity_add( $prepared_activity );
		}

		if ( ! is_numeric( $activity_id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_create_activity',
				__( 'Cannot create new activity.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$activity = bp_activity_get(
			array(
				'in'               => $activity_id,
				'display_comments' => 'stream',
				'show_hidden'      => true,
			)
		);

		$activity      = current( $activity['activities'] );
		$fields_update = $this->update_additional_fields_for_object( $activity, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		// Update current user's last activity.
		bp_update_user_last_activity();

		$retval   = $this->prepare_item_for_response( $activity, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an activity item is created via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Activity_Activity $activity The created activity.
		 * @param WP_REST_Response     $response The response data.
		 * @param WP_REST_Request      $request  The request sent to the API.
		 */
		do_action( 'bp_rest_activity_create_item', $activity, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create an activity.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		$error = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create activities.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$retval = $error;

		if ( is_user_logged_in() ) {
			$user_id = $request->get_param( 'user_id' );

			if ( empty( $user_id ) || (int) bp_loggedin_user_id() === (int) $user_id ) {
				$item_id   = $request->get_param( 'primary_item_id' );
				$component = $request->get_param( 'component' );

				// The current user can create an activity.
				$retval = true;

				if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $component && ! is_null( $item_id ) ) {
					if ( ! $this->show_hidden( $component, $item_id ) ) {
						$retval = $error;
					}
				}
			}
		}

		/**
		 * Filter the activity `create_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_activity_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update an activity.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		if ( empty( $request->get_param( 'content' ) ) ) {
			return new WP_Error(
				'bp_rest_update_activity_empty_content',
				__( 'Please, enter some content.', 'buddypress' ),
				array(
					'status' => 400,
				)
			);
		}

		$activity_id = bp_activity_add( $this->prepare_item_for_database( $request ) );

		if ( ! is_numeric( $activity_id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_activity',
				__( 'Cannot update existing activity.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$activity      = $this->get_activity_object( $activity_id );
		$fields_update = $this->update_additional_fields_for_object( $activity, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval   = $this->prepare_item_for_response( $activity, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an activity is updated via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Activity_Activity $activity The updated activity.
		 * @param WP_REST_Response     $response The response data.
		 * @param WP_REST_Request      $request  The request sent to the API.
		 */
		do_action( 'bp_rest_activity_update_item', $activity, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update an activity.
	 *
	 * @since 15.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to update this activity.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$activity = $this->get_activity_object( $request );

			if ( empty( $activity->id ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid activity ID.', 'buddypress' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( bp_activity_user_can_delete( $activity ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the activity `update_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_activity_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete activity.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		// Get the activity before it's deleted.
		$activity = $this->get_activity_object( $request );
		$previous = $this->prepare_item_for_response( $activity, $request );

		if ( 'activity_comment' === $activity->type ) {
			$retval = bp_activity_delete_comment( $activity->item_id, $activity->id );
		} else {
			$retval = bp_activity_delete(
				array(
					'id' => $activity->id,
				)
			);
		}

		if ( ! $retval ) {
			return new WP_Error(
				'bp_rest_activity_cannot_delete',
				__( 'Could not delete the activity.', 'buddypress' ),
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
		 * Fires after an activity is deleted via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Activity_Activity $activity The deleted activity.
		 * @param WP_REST_Response     $response The response data.
		 * @param WP_REST_Request      $request  The request sent to the API.
		 */
		do_action( 'bp_rest_activity_delete_item', $activity, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete an activity.
	 *
	 * @since 15.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to delete this activity.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$activity = $this->get_activity_object( $request );

			if ( empty( $activity->id ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid activity ID.', 'buddypress' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( bp_activity_user_can_delete( $activity ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the activity `delete_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_activity_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Gets the current user's favorites.
	 *
	 * @since 15.0.0
	 *
	 * @return array Array of activity IDs.
	 */
	public function get_user_favorites() {
		if ( null === $this->user_favorites ) {
			if ( is_user_logged_in() ) {
				$user_favorites       = bp_activity_get_user_favorites( get_current_user_id() );
				$this->user_favorites = array_filter( wp_parse_id_list( $user_favorites ) );
			} else {
				$this->user_favorites = array();
			}
		}

		return $this->user_favorites;
	}

	/**
	 * Adds or removes the activity from the current user's favorites.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_favorite( $request ) {
		$activity = $this->get_activity_object( $request );

		if ( empty( $activity->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid activity ID.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		$user_id = get_current_user_id();

		$result = false;
		if ( in_array( $activity->id, $this->get_user_favorites(), true ) ) {
			$result  = bp_activity_remove_user_favorite( $activity->id, $user_id );
			$message = __( 'Sorry, you cannot remove the activity from your favorites.', 'buddypress' );

			// Update the user favorites, removing the activity ID.
			$this->user_favorites = array_diff( $this->get_user_favorites(), array( $activity->id ) );
		} else {
			$result  = bp_activity_add_user_favorite( $activity->id, $user_id );
			$message = __( 'Sorry, you cannot add the activity to your favorites.', 'buddypress' );

			// Update the user favorites, adding the activity ID.
			$this->user_favorites[] = (int) $activity->id;
		}

		if ( ! $result ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_activity_favorite',
				$message,
				array(
					'status' => 500,
				)
			);
		}

		$retval   = $this->prepare_item_for_response( $activity, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after user favorited activities has been updated via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Activity_Activity $activity       The updated activity.
		 * @param array                $user_favorites The updated user favorites.
		 * @param WP_REST_Response     $response       The response data.
		 * @param WP_REST_Request      $request        The request sent to the API.
		 */
		do_action( 'bp_rest_activity_update_favorite', $activity, $this->get_user_favorites(), $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update user favorites.
	 *
	 * @since 15.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function update_favorite_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to update favorites.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() && bp_activity_can_favorite() && $this->can_see( $request ) ) {
			$retval = true;
		}

		/**
		 * Filter the activity `update_favorite` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_activity_update_favorite_permissions_check', $retval, $request );
	}

	/**
	 * Renders the content of an activity.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Activity_Activity $activity Activity data.
	 * @return string The rendered activity content.
	 */
	public function render_item( $activity ) {
		$rendered = '';

		if ( empty( $activity->content ) ) {
			return $rendered;
		}

		// Do not truncate activities.
		add_filter( 'bp_activity_maybe_truncate_entry', '__return_false' );

		if ( 'activity_comment' === $activity->type ) {
			$rendered = apply_filters( 'bp_get_activity_content', $activity->content );
		} else {
			$activities_template = null;

			if ( isset( $GLOBALS['activities_template'] ) ) {
				$activities_template = $GLOBALS['activities_template'];
			}

			// Set the `activities_template` global for the current activity.
			$GLOBALS['activities_template']           = new stdClass();
			$GLOBALS['activities_template']->activity = $activity;

			// Set up activity oEmbed cache.
			bp_activity_embed();

			$rendered = apply_filters( 'bp_get_activity_content_body', $activity->content, $activity );

			// Restore the `activities_template` global.
			$GLOBALS['activities_template'] = $activities_template;
		}

		// Restore the filter to truncate activities.
		remove_filter( 'bp_activity_maybe_truncate_entry', '__return_false' );

		return $rendered;
	}

	/**
	 * Prepares activity data for return as an object.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Activity_Activity $activity Activity object.
	 * @param WP_REST_Request      $request  Full details about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $activity, $request ) {
		$data = array(
			'user_id'           => $activity->user_id,
			'component'         => $activity->component,
			'content'           => array(
				'raw'      => $activity->content,
				'rendered' => $this->render_item( $activity ),
			),
			'date'              => bp_rest_prepare_date_response( $activity->date_recorded, get_date_from_gmt( $activity->date_recorded ) ),
			'date_gmt'          => bp_rest_prepare_date_response( $activity->date_recorded ),
			'id'                => $activity->id,
			'link'              => bp_activity_get_permalink( $activity->id ),
			'primary_item_id'   => $activity->item_id,
			'secondary_item_id' => $activity->secondary_item_id,
			'status'            => $activity->is_spam ? 'spam' : 'published',
			'title'             => $activity->action,
			'type'              => $activity->type,
			'hidden'            => (bool) $activity->hide_sitewide,
			'favorited'         => in_array( $activity->id, $this->get_user_favorites(), true ),
		);

		// Get item schema.
		$schema = $this->get_item_schema();

		// Get comments (count).
		if ( ! empty( $activity->children ) ) {
			$comment_count         = wp_filter_object_list( $activity->children, array( 'type' => 'activity_comment' ), 'AND', 'id' );
			$data['comment_count'] = ! empty( $comment_count ) ? count( $comment_count ) : 0;

			if ( ! empty( $schema['properties']['comments'] ) && 'threaded' === $request->get_param( 'display_comments' ) ) {
				$data['comments'] = $this->prepare_activity_comments( $activity->children, $request );
			}
		}

		if ( true === buddypress()->avatar->show_avatars ) {
			$data['user_avatar'] = array(
				'full'  => bp_core_fetch_avatar(
					array(
						'item_id' => $activity->user_id,
						'html'    => false,
						'type'    => 'full',
					)
				),
				'thumb' => bp_core_fetch_avatar(
					array(
						'item_id' => $activity->user_id,
						'html'    => false,
					)
				),
			);
		}

		$context  = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $activity ) );

		/**
		 * Filter an activity value returned from the API.
		 *
		 * @since 15.0.0
		 *
		 * @param WP_REST_Response     $response The response data.
		 * @param WP_REST_Request      $request  Request used to generate the response.
		 * @param BP_Activity_Activity $activity The activity object.
		 */
		return apply_filters( 'bp_rest_activity_prepare_value', $response, $request, $activity );
	}

	/**
	 * Prepare activity comments.
	 *
	 * @since 15.0.0
	 *
	 * @param  array           $comments Array of comments.
	 * @param  WP_REST_Request $request  Full details about the request.
	 * @return array           An array of activity comments.
	 */
	protected function prepare_activity_comments( $comments, $request ) {
		$data = array();

		if ( empty( $comments ) ) {
			return $data;
		}

		foreach ( $comments as $comment ) {
			$data[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $comment, $request )
			);
		}

		/**
		 * Filter activity comments returned from the API.
		 *
		 * @since 15.0.0
		 *
		 * @param array           $data     An array of activity comments with proper API response.
		 * @param array           $comments An array of activity comments.
		 * @param WP_REST_Request $request  Full details about the request.
		 */
		return apply_filters( 'bp_rest_activity_prepare_comments', $data, $comments, $request );
	}

	/**
	 * Prepare an activity for create or update.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return stdClass|WP_Error Object or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_activity = new stdClass();
		$schema            = $this->get_item_schema();
		$activity          = $this->get_activity_object( $request );

		if ( ! empty( $schema['properties']['id'] ) && ! empty( $activity->id ) ) {
			$prepared_activity->id = $activity->id;

			if ( 'activity_comment' !== $request->get_param( 'type' ) ) {
				$prepared_activity->error_type = 'wp_error';
			}
		}

		// Activity author ID.
		if ( ! empty( $activity->user_id ) ) {
			$prepared_activity->user_id = (int) $activity->user_id;
		} else {
			$prepared_activity->user_id = get_current_user_id();
		}

		// Activity component.
		if ( ! empty( $schema['properties']['component'] ) && ! empty( $request->get_param( 'component' ) ) ) {
			$prepared_activity->component = $request->get_param( 'component' );
		} else {
			$prepared_activity->component = buddypress()->activity->id;
		}

		// Activity date.
		if ( ! empty( $activity->date_recorded ) ) {
			$prepared_activity->recorded_time = $activity->date_recorded;
		}

		// Activity Item ID.
		if ( ! empty( $schema['properties']['primary_item_id'] ) && ! empty( $request->get_param( 'primary_item_id' ) ) ) {
			$item_id = (int) $request->get_param( 'primary_item_id' );

			// Use a generic item ID.
			$prepared_activity->item_id = $item_id;

			// Set the group ID, used in the `groups_post_update` helper function only.
			if ( bp_is_active( 'groups' ) && isset( $prepared_activity->component ) && buddypress()->groups->id === $prepared_activity->component ) {
				$prepared_activity->group_id = $item_id;
			}
		}

		// Secondary Item ID.
		if ( ! empty( $schema['properties']['secondary_item_id'] ) && ! empty( $request->get_param( 'secondary_item_id' ) ) ) {
			$prepared_activity->secondary_item_id = (int) $request->get_param( 'secondary_item_id' );
		}

		// Activity type.
		if ( ! empty( $schema['properties']['type'] ) && ! empty( $request->get_param( 'type' ) ) ) {
			$prepared_activity->type = $request->get_param( 'type' );
		}

		// Activity content.
		if ( ! empty( $schema['properties']['content'] ) && ! empty( $request->get_param( 'content' ) ) ) {
			if ( is_string( $request->get_param( 'content' ) ) ) {
				$prepared_activity->content = $request->get_param( 'content' );
			} elseif ( isset( $request['content']['raw'] ) ) {
				$prepared_activity->content = $request['content']['raw'];
			}
		}

		// Activity Sitewide visibility.
		if ( ! empty( $schema['properties']['hidden'] ) ) {
			$is_hidden = $request->get_param( 'hidden' );

			if ( ! is_null( $is_hidden ) ) {
				$is_hidden = wp_validate_boolean( $is_hidden );
			} elseif ( isset( $activity->hide_sitewide ) ) {
				$is_hidden = (bool) $activity->hide_sitewide;
			} elseif ( bp_is_active( 'groups' ) && isset( $prepared_activity->item_id, $prepared_activity->component ) && $prepared_activity->item_id && buddypress()->groups->id === $prepared_activity->component ) {
				$group     = bp_get_group_by( 'id', $prepared_activity->item_id );
				$is_hidden = isset( $group->status ) && 'public' !== $group->status;
			}

			$prepared_activity->hide_sitewide = $is_hidden;
		}

		/**
		 * Filters an activity before it is inserted or updated via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param stdClass        $prepared_activity An object prepared for inserting or updating the database.
		 * @param WP_REST_Request $request Request object.
		 */
		return apply_filters( 'bp_rest_activity_pre_insert_value', $prepared_activity, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Activity_Activity $activity Activity object.
	 * @return array
	 */
	protected function prepare_links( $activity ) {
		$base = sprintf( '/%1$s/%2$s/', $this->namespace, $this->rest_base );
		$url  = $base . $activity->id;

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $url ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		// Embeds.
		if ( ! empty( $activity->user_id ) ) {
			$links['user'] = array(
				'href'       => bp_rest_get_object_url( absint( $activity->user_id ), 'members' ),
				'embeddable' => true,
			);
		}

		if ( 'activity_comment' === $activity->type ) {
			$links['up'] = array(
				'href' => rest_url( $url ),
			);
		}

		// Embed Group.
		if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component && ! empty( $activity->item_id ) ) {
			$links['group'] = array(
				'embeddable' => true,
				'href'       => rest_url(
					sprintf(
						'/%1$s/%2$s/%3$d',
						$this->namespace,
						buddypress()->groups->id,
						absint( $activity->item_id )
					)
				),
			);
		}

		// Embed Blog.
		if ( is_multisite() && bp_is_active( 'blogs' ) && buddypress()->blogs->id === $activity->component && ! empty( $activity->item_id ) ) {
			$links['blog'] = array(
				'embeddable' => true,
				'href'       => rest_url(
					sprintf(
						'/%1$s/%2$s/%3$d',
						$this->namespace,
						buddypress()->blogs->id,
						absint( $activity->item_id )
					)
				),
			);
		}

		// Actions.
		if ( is_user_logged_in() && bp_activity_can_favorite() ) {
			$favorite_action = array(
				'href'        => rest_url( $url . '/favorite' ),
				'activity_id' => $activity->id,
			);

			// Will be deprecated.
			$links['favorite']           = $favorite_action;
			$links['bp-action-favorite'] = $favorite_action;
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 15.0.0
		 *
		 * @param array                $links     The prepared links of the REST response.
		 * @param BP_Activity_Activity $activity  Activity object.
		 */
		return apply_filters( 'bp_rest_activity_prepare_links', $links, $activity );
	}

	/**
	 * Can this user see the activity?
	 *
	 * @since 15.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	protected function can_see( $request ) {
		return bp_activity_user_can_read(
			$this->get_activity_object( $request ),
			bp_loggedin_user_id()
		);
	}

	/**
	 * Show hidden activity?
	 *
	 * @since 15.0.0
	 *
	 * @param  string $component The component the activity is from.
	 * @param  int    $item_id   The activity item ID.
	 * @return boolean
	 */
	protected function show_hidden( $component, $item_id ) {
		$user_id = get_current_user_id();
		$retval  = false;

		if ( ! is_null( $component ) ) {
			// If activity is from a group, check the user is a confirmed member/admin or mod of the group.
			if ( 'groups' === $component && bp_is_active( 'groups' ) && ! empty( $item_id ) ) {
				$retval = (bool) groups_is_user_member( $user_id, $item_id );
			}

			/**
			 * Filter here to manage hidden activity for the component.
			 *
			 * @since 15.0.0
			 *
			 * @param bool   $retval    True to show hidden activities. False otherwise.
			 * @param string $component The component name/ID.
			 * @param int    $user_id   The current user ID.
			 */
			$retval = apply_filters( 'bp_rest_activity_show_hidden', $retval, $component, $user_id );
		}

		// Moderators as well.
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
		}

		return (bool) $retval;
	}

	/**
	 * Get activity object.
	 *
	 * @since 15.0.0
	 *
	 * @param  WP_REST_Request|int $request Request object or ID.
	 * @return BP_Activity_Activity|string An activity object.
	 */
	public function get_activity_object( $request ) {
		$activity_id = is_numeric( $request ) ? $request : (int) $request->get_param( 'id' );

		$activity = bp_activity_get_specific(
			array(
				'activity_ids'     => array( $activity_id ),
				'display_comments' => true,
			)
		);

		if ( is_array( $activity ) && ! empty( $activity['activities'][0] ) ) {
			return $activity['activities'][0];
		}

		return '';
	}

	/**
	 * Edit the type of some properties for the CREATABLE & EDITABLE methods.
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
			$key                     = 'create_item';
			$args['content']['type'] = 'string';
			unset( $args['content']['properties'] );

			if ( WP_REST_Server::EDITABLE === $method ) {
				$key                      = 'update_item';
				$args['type']['required'] = true;
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
		return apply_filters( "bp_rest_activity_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the plugin schema, conforming to JSON Schema.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( is_null( $this->schema ) ) {
			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'bp_activity',
				'type'       => 'object',
				'properties' => array(
					'id'                => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'A unique numeric ID for the activity.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'integer',
					),
					'primary_item_id'   => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The ID of some other object primarily associated with this one.', 'buddypress' ),
						'type'        => 'integer',
					),
					'secondary_item_id' => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The ID of some other object also associated with this one.', 'buddypress' ),
						'type'        => 'integer',
					),
					'user_id'           => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The ID for the author of the activity.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'integer',
					),
					'link'              => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The permalink to this activity on the site.', 'buddypress' ),
						'format'      => 'uri',
						'type'        => 'string',
					),
					'component'         => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The active BuddyPress component the activity relates to.', 'buddypress' ),
						'type'        => 'string',
						'enum'        => array_keys( buddypress()->active_components ),
						'arg_options' => array(
							'sanitize_callback' => 'sanitize_key',
						),
					),
					'type'              => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The activity type of the activity.', 'buddypress' ),
						'type'        => 'string',
						'enum'        => array_keys( bp_activity_get_types() ),
						'arg_options' => array(
							'sanitize_callback' => 'sanitize_key',
						),
					),
					'title'             => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The description of the activity\'s type (eg: Username posted an update)', 'buddypress' ),
						'type'        => 'string',
						'readonly'    => true,
						'arg_options' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
					'content'           => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'Allowed HTML content for the activity.', 'buddypress' ),
						'type'        => 'object',
						'arg_options' => array(
							'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
							'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Content for the activity, as it exists in the database.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered' => array(
								'description' => __( 'HTML content for the activity, transformed for display.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
						),
					),
					'date'              => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The date the activity was published, in the site\'s timezone.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'date_gmt'          => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The date the activity was published, as GMT.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'status'            => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'Whether the activity has been marked as spam or not.', 'buddypress' ),
						'type'        => 'string',
						'enum'        => array( 'published', 'spam' ),
						'readonly'    => true,
						'arg_options' => array(
							'sanitize_callback' => 'sanitize_key',
						),
					),
					'comments'          => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'A list of objects children of the activity object.', 'buddypress' ),
						'type'        => 'array',
						'readonly'    => true,
					),
					'comment_count'     => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'Total number of comments of the activity object.', 'buddypress' ),
						'type'        => 'integer',
						'readonly'    => true,
					),
					'hidden'            => array(
						'context'     => array( 'edit', 'embed' ),
						'description' => __( 'Whether the activity object should be sitewide hidden or not.', 'buddypress' ),
						'type'        => 'boolean',
					),
					'favorited'         => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'Whether the activity object has been favorited by the current user.', 'buddypress' ),
						'type'        => 'boolean',
						'readonly'    => true,
					),
				),
			);

			if ( true === buddypress()->avatar->show_avatars ) {
				$avatar_properties = array();

				$avatar_properties['full'] = array(
					'context'     => array( 'view', 'edit', 'embed' ),
					/* translators: 1: Full avatar width in pixels. 2: Full avatar height in pixels */
					'description' => sprintf( __( 'Avatar URL with full image size (%1$d x %2$d pixels).', 'buddypress' ), number_format_i18n( bp_core_avatar_full_width() ), number_format_i18n( bp_core_avatar_full_height() ) ),
					'type'        => 'string',
					'format'      => 'uri',
				);

				$avatar_properties['thumb'] = array(
					'context'     => array( 'view', 'edit', 'embed' ),
					/* translators: 1: Thumb avatar width in pixels. 2: Thumb avatar height in pixels */
					'description' => sprintf( __( 'Avatar URL with thumb image size (%1$d x %2$d pixels).', 'buddypress' ), number_format_i18n( bp_core_avatar_thumb_width() ), number_format_i18n( bp_core_avatar_thumb_height() ) ),
					'type'        => 'string',
					'format'      => 'uri',
				);

				$schema['properties']['user_avatar'] = array(
					'context'     => array( 'view', 'edit', 'embed' ),
					'description' => __( 'Avatar URLs for the author of the activity.', 'buddypress' ),
					'type'        => 'object',
					'readonly'    => true,
					'properties'  => $avatar_properties,
				);
			}

			// Cache current schema here.
			$this->schema = $schema;
		}

		/**
		 * Filters the activity schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_activity_schema', $this->add_additional_fields_schema( $this->schema ) );
	}

	/**
	 * Get the query params for collections of plugins.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific IDs.', 'buddypress' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['include'] = array(
			'description'       => __( 'Ensure result set includes specific IDs.', 'buddypress' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
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

		$params['after'] = array(
			'description'       => __( 'Limit result set to items published after a given ISO8601 compliant date.', 'buddypress' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Limit result set to items created by a specific user (ID).', 'buddypress' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'description'       => __( 'Limit result set to items with a specific status.', 'buddypress' ),
			'default'           => 'ham_only',
			'type'              => 'string',
			'enum'              => array( 'ham_only', 'spam_only', 'all' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['scope'] = array(
			'description'       => __( 'Limit result set to items with a specific scope.', 'buddypress' ),
			'type'              => 'string',
			'enum'              => array( 'just-me', 'friends', 'groups', 'favorites', 'mentions' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['group_id'] = array(
			'description'       => __( 'Limit result set to items created by a specific group.', 'buddypress' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['site_id'] = array(
			'description'       => __( 'Limit result set to items created by a specific site.', 'buddypress' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['primary_id'] = array(
			'description'       => __( 'Limit result set to items with a specific prime association ID.', 'buddypress' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['secondary_id'] = array(
			'description'       => __( 'Limit result set to items with a specific secondary association ID.', 'buddypress' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['component'] = array(
			'description'       => __( 'Limit result set to items with a specific active BuddyPress component.', 'buddypress' ),
			'type'              => 'string',
			'enum'              => array_keys( buddypress()->active_components ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['type'] = array(
			'description'       => __( 'Limit result set to items with a specific activity type.', 'buddypress' ),
			'type'              => 'array',
			'items'             => array(
				'enum' => array_keys( bp_activity_get_types() ),
				'type' => 'string',
			),
			'sanitize_callback' => 'wp_parse_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['display_comments'] = array(
			'description'       => __( 'No comments by default, stream for within stream display, threaded for below each activity item.', 'buddypress' ),
			'default'           => '',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_activity_collection_params', $params );
	}
}
