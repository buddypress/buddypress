<?php
/**
 * BP_REST_Sitewide_Notices_Endpoint class
 *
 * @package BuddyPress
 * @since 15.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sitewide Notices endpoints.
 *
 * @since 15.0.0
 */
class BP_REST_Sitewide_Notices_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 15.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'sitewide-notices';
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
					'args'                => $this->get_endpoint_args_for_item_schema(),
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
						'description' => __( 'A unique numeric ID for the Sitewide notice.', 'buddypress' ),
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

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/dismiss',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'dismiss_notice' ),
					'permission_callback' => array( $this, 'dismiss_notice_permissions_check' ), // Anyone who can get items can dismiss them.
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve sitewide notices.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$context = $request->get_param( 'context' );

		if ( 'edit' === $context && bp_current_user_can( 'bp_moderate' ) ) {

			$args = array(
				'pag_page' => $request->get_param( 'page' ),
				'pag_num'  => $request->get_param( 'per_page' ),
			);

			/**
			 * Filter the query arguments for the request.
			 *
			 * @since 15.0.0
			 *
			 * @param array           $args    Key value array of query var to query value.
			 * @param WP_REST_Request $request The request sent to the API.
			 */
			$args = apply_filters( 'bp_rest_sitewide_notices_get_items_query_args', $args, $request );

			$notices = BP_Messages_Notice::get_notices( $args );

			$retval = array();
			foreach ( (array) $notices as $notice ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $notice, $request )
				);
			}

			$response = rest_ensure_response( $retval );
			$response = bp_rest_response_add_total_headers( $response, BP_Messages_Notice::get_total_notice_count(), $args['pag_num'] );

		} else {
			// Ordinary users, or Admins who aren't currently managing notices, only get the most recent notice.
			$retval  = array();
			$notice  = BP_Messages_Notice::get_active();
			$notices = array();
			if ( ! empty( $notice ) ) {
				// Make sure the user hasn't already dismissed it.
				$closed_notices = bp_get_user_meta( bp_loggedin_user_id(), 'closed_notices', true );
				if ( empty( $closed_notices ) ) {
					$closed_notices = array();
				}
				if ( $notice->id && is_array( $closed_notices ) && ! in_array( $notice->id, $closed_notices, true ) ) {
					$retval[] = $this->prepare_response_for_collection(
						$this->prepare_item_for_response( $notice, $request )
					);
					// Add the item to the notices array used in the filter.
					$notices[] = $notice;
				}
			}
			$response = rest_ensure_response( $retval );
			// The count is either 0 or 1, since there can only be one active notice at a time.
			$response = bp_rest_response_add_total_headers( $response, count( $retval ), 1 );
		}

		/**
		 * Fires after notices are fetched via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param array            $notices  Fetched notices.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_sitewide_notices_get_items', $notices, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request is allowed to get notices.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		$context = $request->get_param( 'context' );
		$retval  = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to see the notices.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			if ( 'view' === $context ) {
				$retval = true;
			} elseif ( 'edit' === $context ) {
				$retval = bp_current_user_can( 'bp_moderate' );
			}
		}

		/**
		 * Filter the messages `get_items` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_sitewide_notices_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Get a single notice by ID.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function get_item( $request ) {
		$notice   = $this->get_notice_object( $request->get_param( 'id' ) );
		$response = $this->prepare_item_for_response( $notice, $request );

		/**
		 * Fires after a sitewide notice is fetched via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Messages_Notice $notice  Notice object.
		 * @param WP_REST_Response   $retval  The response data.
		 * @param WP_REST_Request    $request The request sent to the API.
		 */
		do_action( 'bp_rest_sitewide_notices_get_item', $notice, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request is allowed to get a sitewide notice.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		$error  = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to see this notice.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
		$retval = $error;

		if ( is_user_logged_in() ) {
			$notice = $this->get_notice_object( $request->get_param( 'id' ) );
			if ( empty( $notice->id ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Sorry, this notice does not exist.', 'buddypress' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
					$retval = true;
			} else {
				// Non-admin users can only see the active notice.
				$is_active = isset( $notice->is_active ) ? $notice->is_active : false;
				if ( ! $is_active ) {
					$retval = $error;
				} else {
					$retval = true;
				}
			}
		}

		/**
		 * Filter the sitewide notices `get_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_sitewide_notices_get_item_permissions_check', $retval, $request, $notice );
	}

	/**
	 * Create a sitewide notice.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$subject = $request->get_param( 'subject' );
		$message = $request->get_param( 'message' );
		$success = messages_send_notice( $subject, $message );

		if ( ! $success ) {
			return new WP_Error(
				'bp_rest_user_cannot_create_sitewide_notice',
				__( 'Cannot create new sitewide notice.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		// The notice we just created will be active.
		$notice        = BP_Messages_Notice::get_active();
		$fields_update = $this->update_additional_fields_for_object( $notice, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$response = $this->prepare_item_for_response( $notice, $request );

		/**
		 * Fires after a sitewide notice is created via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Messages_Notice $notice   Notice object.
		 * @param WP_REST_Response   $response The response data.
		 * @param WP_REST_Request    $request  The request sent to the API.
		 */
		do_action( 'bp_rest_sitewide_notices_create_item', $notice, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a notice.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {
		$retval = $this->manage_item_permissions_check( $request );

		/**
		 * Filter the sitewide notices `create_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_sitewide_notices_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update a notice.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		// Check the notice exists.
		$notice = $this->get_notice_object( $request->get_param( 'id' ) );
		if ( ! $notice->id ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Sorry, this notice does not exist.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		// Update the notice.
		$updated_notice = $this->prepare_item_for_database( $request );
		if ( ! $updated_notice->save() ) {
			return new WP_Error(
				'bp_rest_sitewide_notices_update_failed',
				__( 'There was an error trying to update the notice.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$fields_update = $this->update_additional_fields_for_object( $updated_notice, $request );
		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$response = $this->prepare_item_for_response( $updated_notice, $request );

		/**
		 * Fires after a sitewide notice is updated via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Messages_Notice $updated_notice The notice object.
		 * @param WP_REST_Response   $response       The response data.
		 * @param WP_REST_Request    $request        The request sent to the API.
		 */
		do_action( 'bp_rest_sitewide_notices_update_item', $updated_notice, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a notice.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
		$retval = $this->manage_item_permissions_check( $request );

		/**
		 * Filter the sitewide notices `update_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_sitewide_notices_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Dismisses the currently active notice for the current user.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function dismiss_notice( $request ) {
		// Mark the active notice as closed.
		$notice = BP_Messages_Notice::get_active();

		if ( ! $notice->id ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Sorry, this notice does not exist.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		// Get Previous active notice.
		$previous = $this->prepare_item_for_response( $notice, $request );

		// Dismiss the active notice for the current user.
		$dismissed = bp_messages_dismiss_sitewide_notice();

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'dismissed' => $dismissed,
				'previous'  => $previous->get_data(),
			)
		);

		/**
		 * Fires after a sitewide notice is dismissed via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Messages_Notice  $notice   Notice object.
		 * @param WP_REST_Response    $response The response data.
		 * @param WP_REST_Request     $request  The request sent to the API.
		 */
		do_action( 'bp_rest_sitewide_notices_dismiss_notice', $notice, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to dismiss the current notice.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool
	 */
	public function dismiss_notice_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to dismiss notices.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the sitewide notices `dismiss_notice` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_sitewide_notices_dismiss_notice_permissions_check', $retval, $request );
	}

	/**
	 * Delete a sitewide notice.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		// Get the notice before it's deleted.
		$notice = $this->get_notice_object( $request->get_param( 'id' ) );
		if ( ! $notice->id ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Sorry, this notice does not exist.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		$previous = $this->prepare_item_for_response( $notice, $request );

		// Delete a sitewide notice.
		if ( ! $notice->delete() ) {
			return new WP_Error(
				'bp_rest_sitewide_notice_delete_failed',
				__( 'There was an error trying to delete a notice.', 'buddypress' ),
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
		 * Fires after a sitewide notice is deleted via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Messages_Notice $notice  Notice object.
		 * @param WP_REST_Response   $response The response data.
		 * @param WP_REST_Request    $request  The request sent to the API.
		 */
		do_action( 'bp_rest_sitewide_notices_delete_item', $notice, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to generally manage a notice.
	 * Granular filters are provided in the edit_, create_, and delete_
	 * permissions checks.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool
	 */
	public function manage_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
		}

		/**
		 * Filter the notice `manage_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_sitewide_notices_manage_item_permissions_check', $retval, $request );
	}

	/**
	 * Check if a given request has access to delete a notice.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = $this->manage_item_permissions_check( $request );

		/**
		 * Filter the sitewide notices `create_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_sitewide_notices_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Messages_Notice $notice Notice object.
	 * @return array
	 */
	protected function prepare_links( $notice ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . $notice->id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 15.0.0
		 *
		 * @param array              $links   The prepared links of the REST response.
		 * @param BP_Messages_Notice $notice  Notice object.
		 */
		return apply_filters( 'bp_rest_sitewide_notices_prepare_links', $links, $notice );
	}

	/**
	 * Prepares sitewide notice data for return as an object.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Messages_Notice $notice  The notice object.
	 * @param WP_REST_Request    $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $notice, $request ) {
		$data = array(
			'id'        => (int) $notice->id,
			'subject'   => array(
				'raw'      => $notice->subject,
				'rendered' => apply_filters( 'bp_get_message_notice_subject', wp_staticize_emoji( $notice->subject ) ),
			),
			'message'   => array(
				'raw'      => $notice->message,
				'rendered' => apply_filters( 'bp_get_message_notice_text', wp_staticize_emoji( $notice->message ) ),
			),
			'date'      => bp_rest_prepare_date_response( $notice->date_sent, get_date_from_gmt( $notice->date_sent ) ),
			'date_gmt'  => bp_rest_prepare_date_response( $notice->date_sent ),
			'is_active' => (bool) $notice->is_active,
		);

		$context  = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		// Add prepare links.
		$response->add_links( $this->prepare_links( $notice ) );

		/**
		 * Filter sitewide notice data returned from the API.
		 *
		 * @since 15.0.0
		 *
		 * @param WP_REST_Response   $response Response generated by the request.
		 * @param WP_REST_Request    $request  Request used to generate the response.
		 * @param BP_Messages_Notice $notice   The notice object.
		 */
		return apply_filters( 'bp_rest_sitewide_notices_prepare_value', $response, $request, $notice );
	}

	/**
	 * Get sitewide notice object.
	 *
	 * @since 15.0.0
	 *
	 * @param int $id Notice ID.
	 * @return BP_Messages_Notice
	 */
	public function get_notice_object( $id ) {
		$notice = new BP_Messages_Notice( $id );

		if ( ! $notice->date_sent ) {
			$notice->id = null;
		}

		return $notice;
	}

	/**
	 * Select the item schema arguments needed for the CREATABLE, EDITABLE and DELETABLE methods.
	 *
	 * @since 15.0.0
	 *
	 * @param string $method Optional. HTTP method of the request.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$key                       = 'get_item';
		$args                      = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$args['id']['description'] = __( 'ID of the sitewide notice.', 'buddypress' );

		if ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			// Edit the Sitewide Notice ID description and default properties.
			$args['id']['description'] = __( 'ID of the sitewide notice. Required when editing an existing notice.', 'buddypress' );
			$args['id']['default']     = 0;

			// Edit subject's properties.
			$args['subject']['type']        = 'string';
			$args['subject']['default']     = false;
			$args['subject']['description'] = __( 'Subject of the sitewide notice.', 'buddypress' );

			// Edit message's properties.
			$args['message']['type']        = 'string';
			$args['message']['description'] = __( 'Content of the sitewide notice.', 'buddypress' );

		} else {
			unset( $args['subject'], $args['message'] );
			$args['id']['required'] = true;

			if ( WP_REST_Server::EDITABLE === $method ) {
				$key = 'update_item';

				// Edit the Sitewide Notice ID description and default properties.
				$args['id']['description'] = __( 'ID of the sitewide notice to update. Required when editing an existing notice.', 'buddypress' );
			}

			if ( WP_REST_Server::DELETABLE === $method ) {
				$key = 'delete_item';

				// Edit the Sitewide Notice ID description and default properties.
				$args['id']['description'] = __( 'ID of the sitewide notice to delete. Required when deleting an existing notice.', 'buddypress' );
			}
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @since 15.0.0
		 *
		 * @param array $args Query arguments.
		 * @param string $method HTTP method of the request.
		 */
		return apply_filters( "bp_rest_sitewide_notices_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the message schema, conforming to JSON Schema.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( is_null( $this->schema ) ) {
			$this->schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'bp_sitewide_notices',
				'type'       => 'object',
				'properties' => array(
					'id'        => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'A unique numeric ID for the sitewide notice.', 'buddypress' ),
						'type'        => 'integer',
					),
					'subject'   => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Subject of the sitewide notice.', 'buddypress' ),
						'type'        => 'object',
						'arg_options' => array(
							'sanitize_callback' => null,
							'validate_callback' => null,
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Title of the sitewide notice, as it exists in the database.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
								'default'     => false,
							),
							'rendered' => array(
								'description' => __( 'Title of the sitewide notice, transformed for display.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'default'     => false,
							),
						),
					),
					'message'   => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Content of the sitewide notice.', 'buddypress' ),
						'type'        => 'object',
						'required'    => true,
						'arg_options' => array(
							'sanitize_callback' => null,
							'validate_callback' => null,
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Content for the sitewide notice, as it exists in the database.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered' => array(
								'description' => __( 'HTML content for the sitewide notice, transformed for display.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
					'date'      => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The date of the sitewide notice, in the site\'s timezone.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'date_gmt'  => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The date of the sitewide notice, as GMT.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'is_active' => array(
						'context'     => array( 'edit' ),
						'description' => __( 'Whether this notice is active or not.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'boolean',
					),
				),
			);
		}

		/**
		 * Filters the notice schema.
		 *
		 * @since 15.0.0
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_sitewide_notices_schema', $this->add_additional_fields_schema( $this->schema ) );
	}

	/**
	 * Get the query params for sitewide notices collections.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		/**
		 * Filters the collection query params.
		 *
		 * @since 15.0.0
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_sitewide_notices_collection_params', $params );
	}

	/**
	 * Prepare a notice for creation or update.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return BP_Messages_Notice|WP_Error Object or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {
		$schema        = $this->get_item_schema();
		$notice_id     = $request->get_param( 'id' );
		$prepared_item = $this->get_notice_object( $notice_id );

		// Notice ID.
		if ( ! empty( $schema['properties']['id'] ) && ! empty( $prepared_item->id ) ) {
			$prepared_item->id = $prepared_item->id;
		}

		// Notice subject.
		$subject = $request->get_param( 'subject' );
		if ( ! empty( $schema['properties']['subject'] ) && $subject ) {
			if ( is_string( $subject ) ) {
				$prepared_item->subject = $subject;
			} elseif ( isset( $subject['raw'] ) ) {
				$prepared_item->subject = $subject['raw'];
			}
		}

		// Notice message.
		$message = $request->get_param( 'message' );
		if ( ! empty( $schema['properties']['message'] ) && $message ) {
			if ( is_string( $message ) ) {
				$prepared_item->message = $message;
			} elseif ( isset( $message['raw'] ) ) {
				$prepared_item->message = $message['raw'];
			}
		}

		// Date_sent is set at creation, so nothing to do.

		// Is active.
		$is_active = $request->get_param( 'is_active' );
		if ( ! empty( $schema['properties']['is_active'] ) && ! is_null( $is_active ) ) {
			// The method get_param() returns a string, so we must convert to an integer.
			$prepared_item->is_active = absint( $is_active );
		}

		/**
		 * Filters a notice before it is updated via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Messages_Notice $prepared_item A BP_Messages_Notice object prepared for inserting or updating the database.
		 * @param WP_REST_Request $request Request object.
		 */
		return apply_filters( 'bp_rest_sitewide_notices_pre_update_value', $prepared_item, $request );
	}
}
