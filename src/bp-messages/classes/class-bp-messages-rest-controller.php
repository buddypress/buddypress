<?php
/**
 * BP_Messages_REST_Controller class
 *
 * @package BuddyPress
 * @since 15.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Messages endpoints.
 *
 * /messages/
 * /messages/{thread_id}
 * /messages/starred/{message_id}
 *
 * @since 15.0.0
 */
class BP_Messages_REST_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 15.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->messages->id;
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

		// Attention: (?P<id>[\d]+) is the placeholder for **Thread** ID, not the Message ID one.
		$thread_endpoint = '/' . $this->rest_base . '/(?P<id>[\d]+)';

		register_rest_route(
			$this->namespace,
			$thread_endpoint,
			array(
				'args'        => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the Thread.', 'buddypress' ),
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

		// Register the starred route.
		if ( bp_is_active( 'messages', 'star' ) ) {
			// Attention: (?P<id>[\d]+) is the placeholder for **Message** ID, not the Thread ID one.
			$starred_endpoint = '/' . $this->rest_base . '/' . bp_get_messages_starred_slug() . '/(?P<id>[\d]+)';

			register_rest_route(
				$this->namespace,
				$starred_endpoint,
				array(
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_starred' ),
						'permission_callback' => array( $this, 'update_starred_permissions_check' ),
					),
					'schema' => array( $this, 'get_item_schema' ),
				)
			);
		}
	}

	/**
	 * Retrieve threads.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$args = array(
			'user_id'             => $request->get_param( 'user_id' ),
			'box'                 => $request->get_param( 'box' ),
			'type'                => $request->get_param( 'type' ),
			'page'                => $request->get_param( 'page' ),
			'per_page'            => $request->get_param( 'per_page' ),
			'search_terms'        => $request->get_param( 'search' ),
			'recipients_page'     => $request->get_param( 'recipients_page' ),
			'recipients_per_page' => $request->get_param( 'recipients_per_page' ),
			'messages_page'       => $request->get_param( 'messages_page' ),
			'messages_per_page'   => $request->get_param( 'messages_per_page' ),
		);

		// Include the meta_query for starred messages.
		if ( 'starred' === $args['box'] ) {
			$args['meta_query'] = array(
				array(
					'key'   => 'starred_by_user',
					'value' => $args['user_id'],
				),
			);
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 15.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters( 'bp_rest_messages_get_items_query_args', $args, $request );

		// Actually, query it.
		$messages_box = new BP_Messages_Box_Template( $args );

		$retval = array();
		foreach ( (array) $messages_box->threads as $thread ) {
			$messages_box->the_message_thread();

			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $thread, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $messages_box->total_thread_count, $args['per_page'] );

		/**
		 * Fires after threads are fetched via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Messages_Box_Template  $messages_box Messages box
		 * @param WP_REST_Response          $response     The response data.
		 * @param WP_REST_Request           $request      The request sent to the API.
		 */
		do_action( 'bp_rest_messages_get_items', $messages_box, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to thread items.
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

		if ( is_user_logged_in() ) {
			$user = bp_rest_get_user( $request->get_param( 'user_id' ) );

			if ( ! $user instanceof WP_User ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid member ID.', 'buddypress' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( (int) bp_loggedin_user_id() === $user->ID || bp_current_user_can( 'bp_moderate' ) ) {
				$retval = true;
			} else {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you cannot view the messages.', 'buddypress' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the messages `get_items` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_messages_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Get a single thread.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function get_item( $request ) {
		$args = array(
			'recipients_page'     => $request->get_param( 'recipients_page' ),
			'recipients_per_page' => $request->get_param( 'recipients_per_page' ),
			'page'                => $request->get_param( 'messages_page' ),
			'per_page'            => $request->get_param( 'messages_per_page' ),
			'order'               => $request->get_param( 'order' ),
			'user_id'             => $request->get_param( 'user_id' ),
		);

		if ( empty( $args['user_id'] ) ) {
			$args['user_id'] = bp_loggedin_user_id();
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters( 'bp_rest_messages_get_item_query_args', $args, $request );

		$thread = new BP_Messages_Thread(
			$request->get_param( 'id' ),
			'ASC', // not used.
			$args
		);

		$response = $this->prepare_item_for_response( $thread, $request );
		$response = bp_rest_response_add_total_headers( $response, $thread->messages_total_count, $args['per_page'] );

		/**
		 * Fires after a thread is fetched via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Messages_Thread $thread   Thread object.
		 * @param WP_REST_Response   $response The response data.
		 * @param WP_REST_Request    $request  The request sent to the API.
		 */
		do_action( 'bp_rest_messages_get_item', $thread, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to a thread item.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		$error = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to see this thread.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$retval  = $error;
		$user_id = bp_loggedin_user_id();
		if ( ! empty( $request->get_param( 'user_id' ) ) ) {
			$user_id = $request->get_param( 'user_id' );
		}

		$id = $request->get_param( 'id' );

		if ( is_user_logged_in() ) {
			$thread = BP_Messages_Thread::is_valid( $id );

			if ( empty( $thread ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Sorry, this thread does not exist.', 'buddypress' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( bp_current_user_can( 'bp_moderate' ) || messages_check_thread_access( $id, $user_id ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the messages `get_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_messages_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Init a Messages Thread or add a reply to an existing Thread.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$create_args = $this->prepare_item_for_database( $request );

		// Let's return the original error if possible.
		$create_args->error_type = 'wp_error';

		// Create the message or the reply.
		$thread_id = messages_new_message( $create_args );

		// Validate it created a Thread or was added to it.
		if ( $thread_id instanceof WP_Error ) {
			return new WP_Error(
				'bp_rest_messages_create_failed',
				$thread_id->get_error_message(),
				array( 'status' => 500 )
			);
		}

		// Make sure to get the newest message to update REST Additional fields.
		$thread        = $this->get_thread_object( $thread_id );
		$last_message  = wp_list_filter( $thread->messages, array( 'id' => $thread->last_message_id ) );
		$last_message  = reset( $last_message );
		$fields_update = $this->update_additional_fields_for_object( $last_message, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$response = $this->prepare_item_for_response( $thread, $request );

		/**
		 * Fires after a message is created via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Messages_Thread $thread   Thread object.
		 * @param WP_REST_Response   $response The response data.
		 * @param WP_REST_Request    $request  The request sent to the API.
		 */
		do_action( 'bp_rest_messages_create_item', $thread, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a message.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to create a message.', 'buddypress' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		} else {
			$thread_id = (int) $request->get_param( 'id' );

			// It's an existing thread.
			if ( $thread_id ) {
				if ( bp_current_user_can( 'bp_moderate' ) || ( messages_is_valid_thread( $thread_id ) && messages_check_thread_access( $thread_id ) ) ) {
					$retval = true;
				}
			} else {
				// It's a new thread.
				$retval = true;
			}
		}

		/**
		 * Filter the messages `create_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_messages_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update metadata for one of the messages of the thread.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {

		// Updated user id.
		$updated_user_id = bp_loggedin_user_id();
		if ( ! empty( $request->get_param( 'user_id' ) ) ) {
			$updated_user_id = $request->get_param( 'user_id' );
		}

		// Get the thread.
		$thread = $this->get_thread_object( $request->get_param( 'id' ), $updated_user_id );
		$error  = new WP_Error(
			'bp_rest_messages_update_failed',
			__( 'There was an error trying to update the message.', 'buddypress' ),
			array(
				'status' => 500,
			)
		);

		// Is someone updating the thread status?
		$thread_status_update = ( (bool) $request->get_param( 'read' ) || (bool) $request->get_param( 'unread' ) );

		// Mark thread as read.
		if ( true === (bool) $request->get_param( 'read' ) ) {
			messages_mark_thread_read( $thread->thread_id, $updated_user_id );
		}

		// Mark thread as unread.
		if ( true === (bool) $request->get_param( 'unread' ) ) {
			messages_mark_thread_unread( $thread->thread_id, $updated_user_id );
		}

		// By default, use the last message.
		$message_id = $thread->last_message_id;
		if ( $request->get_param( 'message_id' ) ) {
			$message_id = $request->get_param( 'message_id' );
		}

		$updated_message = wp_list_filter( $thread->messages, array( 'id' => $message_id ) );
		$updated_message = reset( $updated_message );

		/**
		 * Filter here to allow more users to edit the message meta (eg: the recipients).
		 *
		 * @since 15.0.0
		 *
		 * @param boolean             $value           Whether the user can edit the message meta.
		 *                                             By default: only the sender and a community moderator can.
		 * @param BP_Messages_Message $updated_message The updated message object.
		 * @param WP_REST_Request     $request         Full details about the request.
		 */
		$can_edit_item_meta = apply_filters(
			'bp_rest_messages_can_edit_item_meta',
			bp_loggedin_user_id() === $updated_message->sender_id || bp_current_user_can( 'bp_moderate' ),
			$updated_message,
			$request
		);

		// The message must exist in the thread, and the logged in user must be the sender.
		if (
			false === $thread_status_update
			&& (
				! isset( $updated_message->id )
				|| ! $updated_message->id
				|| ! $can_edit_item_meta
			)
		) {
			return $error;
		}

		$fields_update = $this->update_additional_fields_for_object( $updated_message, $request );
		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$thread   = $this->get_thread_object( $thread->thread_id, $updated_user_id );
		$response = $this->prepare_item_for_response( $thread, $request );

		/**
		 * Fires after a thread or a message is updated via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Messages_Message $updated_message The updated message.
		 * @param WP_REST_Response    $response        The response data.
		 * @param WP_REST_Request     $request         The request sent to the API.
		 */
		do_action( 'bp_rest_messages_update_item', $updated_message, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a message.
	 *
	 * @since 15.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the message `update_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_messages_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Adds or removes the message from the current user's starred box.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_starred( $request ) {
		$message_id = $request->get_param( 'id' );
		$message    = $this->get_message_object( $message_id );
		$user_id    = bp_loggedin_user_id();
		$action     = 'star';
		$info       = __( 'Sorry, you cannot add the message to your starred box.', 'buddypress' );

		if ( ! $message instanceof BP_Messages_Message ) {
			return new WP_Error(
				'bp_rest_message_invalid_id',
				__( 'Invalid message ID.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( bp_messages_is_message_starred( $message_id, $user_id ) ) {
			$action = 'unstar';
			$info   = __( 'Sorry, you cannot remove the message from your starred box.', 'buddypress' );
		}

		$result = bp_messages_star_set_action(
			array(
				'user_id'    => $user_id,
				'message_id' => $message_id,
				'action'     => $action,
			)
		);

		if ( ! $result ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_starred_message',
				$info,
				array( 'status' => 500 )
			);
		}

		$response = $this->prepare_message_for_response( $message, $request );
		$response = rest_ensure_response( $response );

		/**
		 * Fires after a message is starred/unstarred via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Messages_Message $message  Message object.
		 * @param string              $action   Informs about the update performed.
		 *                                      Possible values are `star` or `unstar`.
		 * @param WP_REST_Response    $response The response data.
		 * @param WP_REST_Request     $request  The request sent to the API.
		 */
		do_action( 'bp_rest_message_update_starred_item', $message, $action, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update user starred messages.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function update_starred_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to star/unstar messages.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to star/unstar a message.', 'buddypress' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		} else {
			$thread_id = messages_get_message_thread_id( $request->get_param( 'id' ) ); // This is a message id.

			if ( empty( $thread_id ) ) {
				return new WP_Error(
					'bp_rest_invalid_id',
					__( 'Sorry, the thread of this message does not exist.', 'buddypress' ),
					array( 'status' => 404 )
				);
			}

			if ( messages_check_thread_access( $thread_id ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the message `update_starred` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_messages_update_starred_permissions_check', $retval, $request );
	}

	/**
	 * Delete a thread.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$user_id = bp_loggedin_user_id();
		if ( ! empty( $request->get_param( 'user_id' ) ) ) {
			$user_id = $request->get_param( 'user_id' );
		}

		// Get the thread before it's deleted.
		$thread   = $this->get_thread_object( $request->get_param( 'id' ), $user_id );
		$previous = $this->prepare_item_for_response( $thread, $request );

		// Check the user is one of the recipients.
		if ( ! in_array( $user_id, wp_parse_id_list( wp_list_pluck( $thread->get_recipients(), 'user_id' ) ), true ) ) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to perform this action.', 'buddypress' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		// Delete a thread.
		if ( false === messages_delete_thread( $thread->thread_id, $user_id ) ) {
			return new WP_Error(
				'bp_rest_messages_delete_thread_failed',
				__( 'There was an error trying to delete the thread.', 'buddypress' ),
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
		 * Fires after a thread is deleted via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param BP_Messages_Thread $thread   The thread object.
		 * @param WP_REST_Response   $response The response data.
		 * @param WP_REST_Request    $request  The request sent to the API.
		 */
		do_action( 'bp_rest_messages_delete_item', $thread, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a thread.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the thread `delete_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_messages_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepare a message for create.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request The request sent to the API.
	 * @return stdClass
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_thread = new stdClass();
		$schema          = $this->get_item_schema();
		$thread          = $this->get_thread_object( $request->get_param( 'id' ) );

		if ( ! empty( $schema['properties']['id'] ) && ! empty( $request->get_param( 'id' ) ) ) {
			$prepared_thread->thread_id = $request->get_param( 'id' );
		} elseif ( ! empty( $thread->thread_id ) ) {
			$prepared_thread->thread_id = $thread->thread_id;
		}

		if ( ! empty( $schema['properties']['sender_id'] ) && ! empty( $request->get_param( 'sender_id' ) ) ) {
			$prepared_thread->sender_id = $thread->sender_id;
		} elseif ( ! empty( $thread->sender_id ) ) {
			$prepared_thread->sender_id = $thread->sender_id;
		} else {
			$prepared_thread->sender_id = bp_loggedin_user_id();
		}

		if ( ! empty( $thread->message ) ) {
			$prepared_thread->message = $thread->message;
		} elseif ( ! empty( $schema['properties']['message'] ) ) {
			$prepared_thread->content = $request->get_param( 'message' );
		}

		if ( ! empty( $schema['properties']['subject'] ) && ! empty( $request->get_param( 'subject' ) ) ) {
			$prepared_thread->subject = $request->get_param( 'subject' );
		} elseif ( ! empty( $thread->subject ) ) {
			$prepared_thread->subject = $thread->subject;
		}

		if ( ! empty( $schema['properties']['recipients'] ) && ! empty( $request->get_param( 'recipients' ) ) ) {
			$prepared_thread->recipients = $request->get_param( 'recipients' );
		} elseif ( ! empty( $thread->recipients ) ) {
			$prepared_thread->recipients = wp_parse_id_list( wp_list_pluck( $thread->recipients, 'user_id' ) );
		}

		/**
		 * Filters a message before it is inserted via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param stdClass        $prepared_thread An object prepared for inserting into the database.
		 * @param WP_REST_Request $request Request object.
		 */
		return apply_filters( 'bp_rest_message_pre_insert_value', $prepared_thread, $request );
	}

	/**
	 * Prepares message data for the REST response.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Messages_Message $message The Message object.
	 * @param WP_REST_Request     $request Full details about the request.
	 * @return array The Message data for the REST response.
	 */
	public function prepare_message_for_response( $message, $request ) {
		$user         = bp_rest_get_user( $message->sender_id );
		$deleted_user = ! $user instanceof WP_User;
		$content      = $deleted_user
			? esc_html__( '[deleted]', 'buddypress' )
			: $message->message;

		$data = array(
			'id'            => (int) $message->id,
			'thread_id'     => (int) $message->thread_id,
			'sender_id'     => (int) $message->sender_id,
			'subject'       => array(
				'raw'      => $message->subject,
				'rendered' => apply_filters( 'bp_get_message_thread_subject', $message->subject ),
			),
			'message'       => array(
				'raw'      => $content,
				'rendered' => apply_filters( 'bp_get_the_thread_message_content', $content ),
			),
			'date_sent'     => bp_rest_prepare_date_response( $message->date_sent, get_date_from_gmt( $message->date_sent ) ),
			'date_sent_gmt' => bp_rest_prepare_date_response( $message->date_sent ),
		);

		if ( bp_is_active( 'messages', 'star' ) ) {
			$user_id = bp_loggedin_user_id();

			if ( ! empty( $request->get_param( 'user_id' ) ) ) {
				$user_id = (int) $request->get_param( 'user_id' );
			}

			$data['is_starred'] = bp_messages_is_message_starred( $data['id'], $user_id );
		}

		$context = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		/**
		 * Filter a message value returned from the API.
		 *
		 * @since 15.0.0
		 *
		 * @param array               $data    The message value for the REST response.
		 * @param BP_Messages_Message $message The Message object.
		 * @param WP_REST_Request     $request Request used to generate the response.
		 */
		return apply_filters( 'bp_rest_message_prepare_value', $data, $message, $request );
	}

	/**
	 * Prepares recipient data for the REST response.
	 *
	 * @since 15.0.0
	 *
	 * @param object          $recipient The recipient object.
	 * @param WP_REST_Request $request   Full details about the request.
	 * @return array                     The recipient data for the REST response.
	 */
	public function prepare_recipient_for_response( $recipient, $request ) {
		$display_name = '';
		$user_info    = get_userdata( (int) $recipient->user_id );
		$user_exists  = $user_info instanceof WP_User;

		if ( $user_exists && ! empty( $user_info->display_name ) ) {
			$display_name = $user_info->display_name;
		}

		if ( false === $user_exists ) {
			$display_name = esc_html__( 'Deleted User', 'buddypress' );
		}

		$data = array(
			'id'           => (int) $recipient->id,
			'is_deleted'   => $recipient->is_deleted || ! $user_exists,
			'name'         => $display_name,
			'sender_only'  => (bool) $recipient->sender_only,
			'thread_id'    => (int) $recipient->thread_id,
			'unread_count' => (int) $recipient->unread_count,
			'user_id'      => (int) $recipient->user_id,
			'user_link'    => $user_exists ? esc_url( bp_members_get_user_url( $recipient->user_id ) ) : '',
		);

		// Fetch the user avatar urls (Full & thumb).
		if ( true === buddypress()->avatar->show_avatars ) {
			foreach ( array( 'full', 'thumb' ) as $type ) {
				$data['user_avatars'][ $type ] = bp_core_fetch_avatar(
					array(
						'item_id' => $recipient->user_id,
						'html'    => false,
						'type'    => $type,
					)
				);
			}
		}

		/**
		 * Filter a recipient value returned from the API.
		 *
		 * @since 15.0.0
		 *
		 * @param array           $data      The recipient value for the REST response.
		 * @param object          $recipient The recipient object.
		 * @param WP_REST_Request $request   Request used to generate the response.
		 */
		return apply_filters( 'bp_rest_messages_prepare_recipient_value', $data, $recipient, $request );
	}

	/**
	 * Prepares thread data for return as an object.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Messages_Thread $thread  The thread object.
	 * @param WP_REST_Request    $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $thread, $request ) {
		$user_exists = function ( $user_id ) {
			$user = bp_rest_get_user( $user_id );

			return $user instanceof WP_User;
		};

		$deleted_user = false === $user_exists( $thread->last_sender_id );
		$raw_excerpt  = '';

		if ( isset( $thread->last_message_content ) ) {
			$raw_excerpt = wp_strip_all_tags( bp_create_excerpt( $thread->last_message_content, 75 ) );
		}

		$deleted_text = esc_html__( '[deleted]', 'buddypress' );

		$content = $deleted_user
			? $deleted_text
			: $thread->last_message_content;

		$excerpt = $deleted_user
			? $deleted_text
			: $raw_excerpt;

		$data = array(
			'id'             => (int) isset( $thread->thread_id ) ? $thread->thread_id : 0,
			'message_id'     => (int) isset( $thread->last_message_id ) ? $thread->last_message_id : 0,
			'last_sender_id' => (int) isset( $thread->last_sender_id ) ? $thread->last_sender_id : 0,
			'subject'        => array(
				'raw'      => $thread->last_message_subject,
				'rendered' => apply_filters( 'bp_get_message_thread_subject', $thread->last_message_subject ),
			),
			'excerpt'        => array(
				'raw'      => $excerpt,
				'rendered' => apply_filters( 'bp_get_message_thread_excerpt', $excerpt ),
			),
			'message'        => array(
				'raw'      => $content,
				'rendered' => apply_filters( 'bp_get_the_thread_message_content', $content ),
			),
			'date'           => bp_rest_prepare_date_response( $thread->last_message_date, get_date_from_gmt( $thread->last_message_date ) ),
			'date_gmt'       => bp_rest_prepare_date_response( $thread->last_message_date ),
			'unread_count'   => (int) $thread->unread_count,
			'sender_ids'     => wp_parse_id_list( array_values( $thread->sender_ids ) ),
			'recipients'     => array(),
			'messages'       => array(),
		);

		// Loop through messages to prepare them for the response.
		foreach ( $thread->messages as $message ) {
			$data['messages'][] = $this->prepare_message_for_response( $message, $request );
		}

		// Loop through recipients to prepare them for the response.
		foreach ( $thread->recipients as $recipient ) {
			$data['recipients'][] = $this->prepare_recipient_for_response( $recipient, $request );
		}

		// Pluck starred message ids.
		$data['starred_message_ids'] = wp_parse_id_list(
			array_keys( array_filter( wp_list_pluck( $data['messages'], 'is_starred', 'id' ) ) )
		);

		$context  = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		// Add prepare links.
		$response->add_links( $this->prepare_links( $thread ) );

		/**
		 * Filter a thread value returned from the API.
		 *
		 * @since 15.0.0
		 *
		 * @param WP_REST_Response   $response Response generated by the request.
		 * @param WP_REST_Request    $request  Request used to generate the response.
		 * @param BP_Messages_Thread $thread   The thread object.
		 */
		return apply_filters( 'bp_rest_messages_prepare_value', $response, $request, $thread );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 15.0.0
	 *
	 * @param BP_Messages_Thread $thread  Thread object.
	 * @return array
	 */
	protected function prepare_links( $thread ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . $thread->thread_id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		// Add star links for each message of the thread.
		if ( is_user_logged_in() && bp_is_active( 'messages', 'star' ) ) {
			$starred_base              = $base . bp_get_messages_starred_slug() . '/';
			$links['starred-messages'] = array();

			foreach ( $thread->messages as $message ) {
				$links['star-messages'][ $message->id ] = array(
					'href' => rest_url( $starred_base . $message->id ),
				);
			}
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 15.0.0
		 *
		 * @param array              $links  The prepared links of the REST response.
		 * @param BP_Messages_Thread $thread The thread object.
		 */
		return apply_filters( 'bp_rest_messages_prepare_links', $links, $thread );
	}

	/**
	 * Get the thread object.
	 *
	 * @since 15.0.0
	 *
	 * @param int $thread_id Thread ID.
	 * @param int $user_id   User ID.
	 * @return BP_Messages_Thread|string
	 */
	public function get_thread_object( $thread_id, $user_id = 0 ) {
		$args = array();
		if ( ! empty( $user_id ) ) {
			$args = array( 'user_id' => $user_id );
		}

		// Validate the thread ID.
		$thread_id = BP_Messages_Thread::is_valid( $thread_id );

		if ( false === (bool) $thread_id ) {
			return '';
		}

		return new BP_Messages_Thread( (int) $thread_id, 'ASC', $args );
	}

	/**
	 * Get the message object.
	 *
	 * @since 15.0.0
	 *
	 * @param int $message_id Message ID.
	 * @return BP_Messages_Message|string
	 */
	public function get_message_object( $message_id ) {
		$message_object = new BP_Messages_Message( (int) $message_id );

		if ( empty( $message_object->id ) ) {
			return '';
		}

		return $message_object;
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
		$args                      = parent::get_endpoint_args_for_item_schema( $method );
		$args['id']['description'] = __( 'A unique numeric ID for the Thread.', 'buddypress' );

		if ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			// Edit the Thread ID description and default properties.
			$args['id']['description'] = __( 'A unique numeric ID for the Thread. Required when replying to an existing Thread.', 'buddypress' );
			$args['id']['default']     = 0;

			// Add the sender_id argument.
			$args['sender_id'] = array(
				'description'       => __( 'The user ID of the Message sender.', 'buddypress' ),
				'required'          => false,
				'default'           => bp_loggedin_user_id(),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			// Edit subject's properties.
			$args['subject']['type']        = 'string';
			$args['subject']['default']     = false;
			$args['subject']['description'] = __( 'Subject of the Message initializing the Thread.', 'buddypress' );

			// Edit message's properties.
			$args['message']['type']        = 'string';
			$args['message']['description'] = __( 'Content of the Message to add to the Thread.', 'buddypress' );

			// Edit recipients properties.
			$args['recipients']['required']          = true;
			$args['recipients']['items']             = array( 'type' => 'integer' );
			$args['recipients']['sanitize_callback'] = 'wp_parse_id_list';
			$args['recipients']['validate_callback'] = 'rest_validate_request_arg';
			$args['recipients']['description']       = __( 'The list of the recipients user IDs of the Message.', 'buddypress' );

			// Remove unused properties for this transport method.
			unset( $args['subject']['properties'], $args['message']['properties'] );

		} else {
			unset( $args['sender_id'], $args['subject'], $args['message'], $args['recipients'] );

			$args['user_id'] = array(
				'description'       => __( 'The user ID to get the thread for.', 'buddypress' ),
				'required'          => false,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			if ( WP_REST_Server::EDITABLE === $method ) {
				unset( $args['message'], $args['recipients'], $args['subject'] );
				$key = 'update_item';

				$args['read'] = array(
					'description'       => __( 'Whether to mark the thread as read.', 'buddypress' ),
					'required'          => false,
					'default'           => false,
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'validate_callback' => 'rest_validate_request_arg',
				);

				$args['unread'] = array(
					'description'       => __( 'Whether to mark the thread as unread.', 'buddypress' ),
					'required'          => false,
					'default'           => false,
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'validate_callback' => 'rest_validate_request_arg',
				);

				$args['message_id'] = array(
					'description'       => __( 'By default the latest message of the thread will be updated. Specify this message ID to edit another message of the thread.', 'buddypress' ),
					'required'          => false,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				);
			}

			if ( WP_REST_Server::DELETABLE === $method ) {
				$key = 'delete_item';
			}

			if ( WP_REST_Server::READABLE === $method ) {
				$key = 'get_item';

				$args['recipients_page'] = array(
					'description'       => __( 'Current page of the recipients collection.', 'buddypress' ),
					'type'              => 'integer',
					'default'           => 1,
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
					'minimum'           => 1,
				);

				$args['recipients_per_page'] = array(
					'description'       => __( 'Maximum number of recipients to be returned in result set.', 'buddypress' ),
					'type'              => 'integer',
					'default'           => 10,
					'minimum'           => 1,
					'maximum'           => 100,
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				);

				$args['messages_page'] = array(
					'description'       => __( 'Current page of the messages collection.', 'buddypress' ),
					'type'              => 'integer',
					'default'           => 1,
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
					'minimum'           => 1,
				);

				$args['messages_per_page'] = array(
					'description'       => __( 'Maximum number of messages to be returned in result set.', 'buddypress' ),
					'type'              => 'integer',
					'default'           => 10,
					'minimum'           => 1,
					'maximum'           => 100,
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				);

				$args['order'] = array(
					'description'       => __( 'Order sort attribute ascending or descending.', 'buddypress' ),
					'default'           => 'asc',
					'type'              => 'string',
					'enum'              => array( 'asc', 'desc' ),
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'rest_validate_request_arg',
				);
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
		return apply_filters( "bp_rest_messages_{$key}_query_arguments", $args, $method );
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
			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'bp_messages',
				'type'       => 'object',
				'properties' => array(
					'id'                  => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'A unique numeric ID for the Thread.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'integer',
					),
					'message_id'          => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The ID of the latest message of the Thread.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'integer',
					),
					'last_sender_id'      => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The ID of latest sender of the Thread.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'integer',
					),
					'subject'             => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Title of the latest message of the Thread.', 'buddypress' ),
						'type'        => 'object',
						'arg_options' => array(
							'sanitize_callback' => null,
							'validate_callback' => null,
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Title of the latest message of the Thread, as it exists in the database.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
								'default'     => false,
							),
							'rendered' => array(
								'description' => __( 'Title of the latest message of the Thread, transformed for display.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'default'     => false,
							),
						),
					),
					'excerpt'             => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Summary of the latest message of the Thread.', 'buddypress' ),
						'type'        => 'object',
						'readonly'    => true,
						'arg_options' => array(
							'sanitize_callback' => null,
							'validate_callback' => null,
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Summary for the latest message of the Thread, as it exists in the database.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered' => array(
								'description' => __( 'HTML summary for the latest message of the Thread, transformed for display.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
					'message'             => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Content of the latest message of the Thread.', 'buddypress' ),
						'type'        => 'object',
						'required'    => true,
						'arg_options' => array(
							'sanitize_callback' => null,
							'validate_callback' => null,
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Content for the latest message of the Thread, as it exists in the database.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered' => array(
								'description' => __( 'HTML content for the latest message of the Thread, transformed for display.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
					'date'                => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Date of the latest message of the Thread, in the site\'s timezone.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'date_gmt'            => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Date of the latest message of the Thread, as GMT.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'unread_count'        => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Total count of unread messages into the Thread for the requested user.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'integer',
					),
					'sender_ids'          => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The list of user IDs for all messages in the Thread.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'array',
						'items'       => array(
							'type' => 'integer',
						),
					),
					'recipients'          => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The list of Avatar URLs for the recipient involved into the Thread.', 'buddypress' ),
						'type'        => 'array',
						'items'       => array(
							'type' => 'object',
						),
					),
					'messages'            => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'List of message objects for the thread.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'array',
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'id'           => array(
									'description' => __( 'ID of the recipient.', 'buddypress' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'thread_id'    => array(
									'description' => __( 'Thread ID.', 'buddypress' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'user_id'      => array(
									'description' => __( 'The user ID of the recipient.', 'buddypress' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'unread_count' => array(
									'description' => __( 'The unread count for the recipient.', 'buddypress' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'is_deleted'   => array(
									'description' => __( 'Status of the recipient.', 'buddypress' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'sender_only'  => array(
									'description' => __( 'If recipient is the only sender.', 'buddypress' ),
									'type'        => 'boolean',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'name'         => array(
									'description' => __( 'Name of the recipient.', 'buddypress' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'user_link'    => array(
									'description' => __( 'The link of the recipient.', 'buddypress' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
					),
					'starred_message_ids' => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'List of starred message IDs.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'array',
						'items'       => array(
							'type' => 'integer',
						),
						'default'     => array(),
					),
				),
			);

			if ( true === buddypress()->avatar->show_avatars ) {
				$avatar_properties = array();

				$avatar_properties['full'] = array(
					/* translators: 1: Full avatar width in pixels. 2: Full avatar height in pixels */
					'description' => sprintf( __( 'Avatar URL with full image size (%1$d x %2$d pixels).', 'buddypress' ), number_format_i18n( bp_core_avatar_full_width() ), number_format_i18n( bp_core_avatar_full_height() ) ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
				);

				$avatar_properties['thumb'] = array(
					/* translators: 1: Thumb avatar width in pixels. 2: Thumb avatar height in pixels */
					'description' => sprintf( __( 'Avatar URL with thumb image size (%1$d x %2$d pixels).', 'buddypress' ), number_format_i18n( bp_core_avatar_thumb_width() ), number_format_i18n( bp_core_avatar_thumb_height() ) ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
				);

				$schema['properties']['recipients']['items']['properties']['avatar_urls'] = array(
					'description' => __( 'Avatar URLs for the recipient.', 'buddypress' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => $avatar_properties,
				);
			}

			$this->schema = $schema;
		}

		/**
		 * Filters the message schema.
		 *
		 * @since 15.0.0
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_message_schema', $this->add_additional_fields_schema( $this->schema ) );
	}

	/**
	 * Get the query params for Messages collections.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';
		$boxes                        = array( 'sentbox', 'inbox' );

		if ( bp_is_active( 'messages', 'star' ) ) {
			$boxes[] = 'starred';
		}

		$params['box'] = array(
			'description'       => __( 'Filter the result by box.', 'buddypress' ),
			'default'           => 'inbox',
			'type'              => 'string',
			'enum'              => $boxes,
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['type'] = array(
			'description'       => __( 'Filter the result by thread status.', 'buddypress' ),
			'default'           => 'all',
			'type'              => 'string',
			'enum'              => array( 'all', 'read', 'unread' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Limit result to messages created by a specific user.', 'buddypress' ),
			'default'           => bp_loggedin_user_id(),
			'type'              => 'integer',
			'required'          => true,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['recipients_page'] = array(
			'description'       => __( 'Current page of the recipients collection.', 'buddypress' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);

		$params['recipients_per_page'] = array(
			'description'       => __( 'Maximum number of recipients to be returned in result set.', 'buddypress' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['messages_page'] = array(
			'description'       => __( 'Current page of the messages collection.', 'buddypress' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);

		$params['messages_per_page'] = array(
			'description'       => __( 'Maximum number of messages to be returned in result set.', 'buddypress' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_messages_collection_params', $params );
	}
}
