<?php
/**
 * BuddyPress Members Notices feature REST API Controller.
 *
 * @package buddypress\bp-members\classes\class-bp-members-notices-endpoint
 * @since 15.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notices REST API Controller.
 *
 * @since 15.0.0
 */
class BP_Members_Notices_REST_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 15.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'notices';
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
			'/' . $this->rest_base . '/dismiss/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the Sitewide notice.', 'buddypress' ),
						'type'        => 'integer',
					),
				),
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
	 * Retrieve Notices.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$context = $request->get_param( 'context' );

		$args = array(
			'user_id'    => $request->get_param( 'user_id' ),
			'pag_page'   => $request->get_param( 'page' ),
			'pag_num'    => $request->get_param( 'per_page' ),
			'status'     => $request->get_param( 'status' ),
			'type'       => $request->get_param( 'type' ),
			'target__in' => $request->get_param( 'target' ),
			'priority'   => $request->get_param( 'priority' ),
		);

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 15.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters( 'bp_members_notices_rest_get_items_query_args', $args, $request );

		if ( $args['user_id'] ) {
			$result  = bp_members_get_notices_for_user( $args['user_id'], $args['status'] );
			$notices = $result['items'];
			$count   = $result['count'];

			// All notices are only needed when managing them.
		} elseif ( 'edit' === $context && bp_current_user_can( 'bp_moderate' ) ) {
			unset( $args['status'] );

			$notices = bp_members_get_notices( $args );

			// Clean args.
			$count_args = array_diff(
				$args,
				array(
					'pag_page' => 0,
					'pag_num'  => 0,
				)
			);

			$count = bp_members_get_notices_count( $count_args );
		} else {
			$notices = array();
			$count   = 0;
		}

		$retval = array();
		foreach ( (array) $notices as $notice ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $notice, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $count, $args['pag_num'] );

		/**
		 * Fires after notices are fetched via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param array            $notices  Fetched notices.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_members_notices_rest_get_items', $notices, $response, $request );

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
		 * Filter the notices `get_items` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_members_notices_rest_get_items_permissions_check', $retval, $request );
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
		$notice   = bp_members_get_notice( $request->get_param( 'id' ) );
		$retval   = $this->prepare_item_for_response( $notice, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a notice is fetched via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Members_Notice $notice  Notice object.
		 * @param WP_REST_Response  $retval  The response data.
		 * @param WP_REST_Request   $request The request sent to the API.
		 */
		do_action( 'bp_members_notices_rest_get_item', $notice, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request is allowed to get a notice.
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
			$notice = bp_members_get_notice( $request->get_param( 'id' ) );
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
				$retval = $error;

				if ( isset( $notice->target, $notice->priority ) && 127 !== (int) $notice->priority ) {
					if ( ( 'contributors' === $notice->target && bp_current_user_can( 'edit_posts'  ) ) || 'community' === $notice->target ) {
						$retval = true;
					}
				}
			}
		}

		/**
		 * Filter the  notices `get_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_members_notices_rest_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a notice.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		$notice_id = bp_members_save_notice(
			array(
				'title'    => $request->get_param( 'title' ),
				'content'  => $request->get_param( 'content' ),
				'target'   => $request->get_param( 'target' ),
				'priority' => $request->get_param( 'priority' ),
				'date'     => $request->get_param( 'date' ),
				'url'      => $request->get_param( 'action_url' ),
				'text'     => $request->get_param( 'action_text' ),
			)
		);

		if ( is_wp_error( $notice_id ) ) {
			$notice_id->add_data(
				array(
					'status' => 500,
				)
			);

			return $notice_id;
		}

		// The notice we just created will be active.
		$notice        = bp_members_get_notice( $id );
		$fields_update = $this->update_additional_fields_for_object( $notice, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval   = $this->prepare_item_for_response( $notice, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a notice is created via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Members_Notice $notice   Notice object.
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  The request sent to the API.
		 */
		do_action( 'bp_members_notices_rest_create_item', $notice, $response, $request );

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
		 * Filter the notices `create_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_members_notices_rest_create_item_permissions_check', $retval, $request );
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
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Check the notice exists.
		$notice = bp_members_get_notice( $request->get_param( 'id' ) );
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
		$notice_id      = bp_members_save_notice( wp_slash( (array) $updated_notice ) );

		if ( is_wp_error( $notice_id ) ) {
			$notice_id->add_data(
				array(
					'status' => 500,
				)
			);

			return $notice_id;
		}

		$fields_update = $this->update_additional_fields_for_object( $updated_notice, $request );
		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval   = $this->prepare_item_for_response( $updated_notice, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a notice is updated via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Members_Notice $updated_notice The notice object.
		 * @param WP_REST_Response  $response       The response data.
		 * @param WP_REST_Request   $request        The request sent to the API.
		 */
		do_action( 'bp_members_notices_rest_update_item', $updated_notice, $response, $request );

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
		 * Filter the notices `update_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_members_notices_rest_update_item_permissions_check', $retval, $request );
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
		$notice_id = $request->get_param( 'id' );

		// Mark the requested notice as closed.
		if ( $notice_id ) {
			$notice = bp_members_get_notice( $notice_id );

			// Mark the first priority notice as closed.
		} else {
			$notice = bp_get_active_notice_for_user();
		}

		if ( is_null( $notice ) ) {
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
		$dismissed = bp_members_dismiss_notice( bp_loggedin_user_id(), $notice->id );

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'dismissed' => $dismissed,
				'previous'  => $previous->get_data(),
			)
		);

		/**
		 * Fires after a notice is dismissed via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Members_Notice $notice   Notice object.
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  The request sent to the API.
		 */
		do_action( 'bp_members_notices_rest_dismiss_notice', $notice, $response, $request );

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
		 * Filter the notices `dismiss_notice` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_members_notices_rest_dismiss_notice_permissions_check', $retval, $request );
	}

	/**
	 * Delete a notice.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the notice before it's deleted.
		$notice = bp_members_get_notice( $request->get_param( 'id' ) );
		if ( is_null( $notice ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Sorry, this notice does not exist.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		$previous = $this->prepare_item_for_response( $notice, $request );

		// Delete a notice.
		if ( ! $notice->delete() ) {
			return new WP_Error(
				'bp_members_notices_rest_delete_failed',
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
		 * Fires after a notice is deleted via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Members_Notice $notice   Notice object.
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  The request sent to the API.
		 */
		do_action( 'bp_members_notices_rest_delete_item', $notice, $response, $request );

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
		return apply_filters( 'bp_members_notices_rest_manage_item_permissions_check', $retval, $request );
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
		 * Filter the notices `create_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_members_notices_rest_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Members_Notice $notice Notice object.
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
		 * @param array             $links   The prepared links of the REST response.
		 * @param BP_Members_Notice $notice  Notice object.
		 */
		return apply_filters( 'bp_members_notices_rest_prepare_links', $links, $notice );
	}

	/**
	 * Prepares notice data for return as an object.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Members_Notice $notice  The notice object.
	 * @param WP_REST_Request   $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $notice, $request ) {
		$data = array(
			'id'          => (int) $notice->id,
			'title'       => array(
				'raw'      => $notice->subject,
				'rendered' => wp_staticize_emoji( bp_get_notice_title( $notice ) ),
			),
			'content'     => array(
				'raw'      => bp_get_notice_content( $notice, true ),
				'rendered' => wp_staticize_emoji( bp_get_notice_content( $notice ) ),
			),
			'target'      => bp_get_notice_target( $notice ),
			'date'        => bp_rest_prepare_date_response( $notice->date_sent, get_date_from_gmt( $notice->date_sent ) ),
			'date_gmt'    => bp_rest_prepare_date_response( $notice->date_sent ),
			'priority'    => bp_get_notice_priority( $notice ),
			'action_url'  => bp_get_notice_action_url( $notice ),
			'action_text' => bp_get_notice_action_text( $notice ),
		);

		$context  = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		// Add prepare links.
		$response->add_links( $this->prepare_links( $notice ) );

		/**
		 * Filter notice data returned from the API.
		 *
		 * @since 15.0.0
		 *
		 * @param WP_REST_Response  $response Response generated by the request.
		 * @param WP_REST_Request   $request  Request used to generate the response.
		 * @param BP_Members_Notice $notice   The notice object.
		 */
		return apply_filters( 'bp_members_notices_rest_prepare_value', $response, $request, $notice );
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
		$args['id']['description'] = __( 'ID of the community notice.', 'buddypress' );

		if ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			// Edit the Sitewide Notice ID description and default properties.
			$args['id']['description'] = __( 'ID of the community notice. Required when editing an existing notice.', 'buddypress' );
			$args['id']['default']     = 0;

			// Edit title's properties.
			$args['title']['type']        = 'string';
			$args['title']['default']     = false;
			$args['title']['description'] = __( 'Subject of the community notice.', 'buddypress' );

			// Edit content's properties.
			$args['content']['type']        = 'string';
			$args['content']['description'] = __( 'Content of the community notice.', 'buddypress' );

		} else {
			unset( $args['title'], $args['content'] );
			$args['id']['required'] = true;

			if ( WP_REST_Server::EDITABLE === $method ) {
				$key = 'update_item';

				// Edit the Sitewide Notice ID description and default properties.
				$args['id']['description'] = __( 'ID of the community notice to update. Required when editing an existing notice.', 'buddypress' );
			}

			if ( WP_REST_Server::DELETABLE === $method ) {
				$key = 'delete_item';

				// Edit the Sitewide Notice ID description and default properties.
				$args['id']['description'] = __( 'ID of the community notice to delete. Required when deleting an existing notice.', 'buddypress' );
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
		return apply_filters( "bp_members_notices_rest_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the notice schema, conforming to JSON Schema.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( is_null( $this->schema ) ) {
			$this->schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'bp_notices',
				'type'       => 'object',
				'properties' => array(
					'id'           => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'A unique numeric ID for the community notice.', 'buddypress' ),
						'type'        => 'integer',
					),
					'title'       => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'Subject of the community notice.', 'buddypress' ),
						'type'        => 'object',
						'arg_options' => array(
							'sanitize_callback' => null,
							'validate_callback' => null,
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Title of the community notice, as it exists in the database.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
								'default'     => false,
							),
							'rendered' => array(
								'description' => __( 'Title of the community notice, transformed for display.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
								'default'     => false,
							),
						),
					),
					'content'     => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'Content of the community notice.', 'buddypress' ),
						'type'        => 'object',
						'required'    => true,
						'arg_options' => array(
							'sanitize_callback' => null,
							'validate_callback' => null,
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Content for the community notice, as it exists in the database.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered' => array(
								'description' => __( 'HTML content for the community notice, transformed for display.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
						),
					),
					'target'      => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The target of the community notice.', 'buddypress' ),
						'enum'        => array( 'admins', 'contributors', 'community' ),
						'default'     => 'community',
						'type'        => 'string',
					),
					'date'        => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The date of the community notice, in the site\'s timezone.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'date_gmt'    => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The date of the community notice, as GMT.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'priority'    => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The notice priority.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'integer',
					),
					'action_url'  => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The Notice Action URL.', 'buddypress' ),
						'format'      => 'uri',
						'type'        => 'string',
					),
					'action_text' => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The Notice Action text.', 'buddypress' ),
						'type'        => 'string',
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
		return apply_filters( 'bp_members_notices_rest_schema', $this->add_additional_fields_schema( $this->schema ) );
	}

	/**
	 * Get the query params for notices collection.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		if ( isset( $params['per_page']['default'] ) ) {
			$params['per_page']['default'] = 5;
		}

		$params['user_id'] = array(
			'description'       => __( 'Limit result set to items concerning a specific user (ID).', 'buddypress' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'description'       => __( 'Limit result set to items with a specific status.', 'buddypress' ),
			'default'           => 'unread',
			'type'              => 'string',
			'items'             => array(
				'enum' => array( 'unread', 'dismissed' ),
				'type' => 'string',
			),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['type'] = array(
			'description'       => __( 'Limit result set to items with a specific type.', 'buddypress' ),
			'default'           => 'active',
			'type'              => 'string',
			'items'             => array(
				'enum' => array( 'active', 'inactive' ),
				'type' => 'string',
			),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['target'] = array(
			'description'       => __( 'Limit result set to items concerning one or more specific targets.', 'buddypress' ),
			'type'              => 'array',
			'items'             => array(
				'enum' => array( 'community', 'contributors', 'admins' ),
				'type' => 'string',
			),
			'sanitize_callback' => 'wp_parse_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['priority'] = array(
			'description'       => __( 'Limit result set to items having a specific priority.', 'buddypress' ),
			'type'              => 'integer',
			'items'             => array(
				'enum' => array( 1, 2, 3 ),
				'type' => 'integer',
			),
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @since 15.0.0
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_members_notices_rest_collection_params', $params );
	}

	/**
	 * Prepare a notice for creation or update.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return BP_Members_Notice|WP_Error Object or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {
		$schema        = $this->get_item_schema();
		$notice_id     = $request->get_param( 'id' );
		$prepared_item = bp_members_get_notice( $notice_id );

		// Notice ID.
		if ( ! empty( $schema['properties']['id'] ) && ! empty( $prepared_item->id ) ) {
			$prepared_item->id = $prepared_item->id;
		}

		// Notice title.
		$title = $request->get_param( 'title' );
		if ( ! empty( $schema['properties']['title'] ) && $title ) {
			if ( is_string( $title ) ) {
				$prepared_item->title = $title;
			} elseif ( isset( $title['raw'] ) ) {
				$prepared_item->title = $title['raw'];
			}
		}

		// Notice content.
		$content = $request->get_param( 'content' );
		if ( ! empty( $schema['properties']['content'] ) && $content ) {
			if ( is_string( $content ) ) {
				$prepared_item->content = $content;
			} elseif ( isset( $content['raw'] ) ) {
				$prepared_item->content = $content['raw'];
			}
		}

		// Notice target.
		$target = $request->get_param( 'target' );
		if ( ! empty( $schema['properties']['target'] ) && ! is_null( $target ) ) {
			$prepared_item->target = $target;
		}

		// Date is set at creation, so nothing to do.
		$date = $request->get_param( 'date' );
		if ( ! empty( $schema['properties']['date'] ) && ! is_null( $date ) ) {
			$prepared_item->date = $date;
		}

		// Priority.
		$priority = $request->get_param( 'priority' );
		if ( ! empty( $schema['properties']['priority'] ) && ! is_null( $priority ) ) {
			$prepared_item->priority = absint( $priority );
		}

		// Action URL.
		$action_url = $request->get_param( 'action_url' );
		if ( ! empty( $schema['properties']['action_url'] ) && ! is_null( $action_url ) ) {
			$prepared_item->url = $action_url;
		}

		// Action Text.
		$action_text = $request->get_param( 'action_text' );
		if ( ! empty( $schema['properties']['action_text'] ) && ! is_null( $action_text ) ) {
			$prepared_item->text = $action_text;
		}

		/**
		 * Filters a notice before it is updated via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Members_Notice $prepared_item A BP_Members_Notice object prepared for inserting or updating the database.
		 * @param WP_REST_Request $request Request object.
		 */
		return apply_filters( 'bp_members_notices_rest_pre_update_value', $prepared_item, $request );
	}
}
