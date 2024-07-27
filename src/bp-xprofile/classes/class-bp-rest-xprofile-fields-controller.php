<?php
/**
 * BP_REST_XProfile_Fields_Controller class
 *
 * @package BuddyPress
 * @since 5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * XProfile Fields endpoints.
 *
 * Use /xprofile/fields
 * Use /xprofile/fields/{id}
 *
 * @since 5.0.0
 */
class BP_REST_XProfile_Fields_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->profile->id . '/fields';
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
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the profile field.', 'buddypress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'user_id'          => array(
							'description'       => __( 'Required if you want to load a specific user\'s data.', 'buddypress' ),
							'default'           => 0,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'fetch_field_data' => array(
							'description'       => __( 'Whether to fetch data for the field. Requires a $user_id.', 'buddypress' ),
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
					'args'                => array(
						'delete_data' => array(
							'description'       => __( 'Required if you want to delete users data for the field.', 'buddypress' ),
							'default'           => false,
							'type'              => 'boolean',
							'sanitize_callback' => 'rest_sanitize_boolean',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve XProfile fields.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$args = array(
			'profile_group_id'       => $request->get_param( 'profile_group_id' ),
			'user_id'                => $request->get_param( 'user_id' ),
			'member_type'            => $request->get_param( 'member_type' ),
			'hide_empty_groups'      => $request->get_param( 'hide_empty_groups' ),
			'hide_empty_fields'      => $request->get_param( 'hide_empty_fields' ),
			'fetch_field_data'       => $request->get_param( 'fetch_field_data' ),
			'fetch_visibility_level' => $request->get_param( 'fetch_visibility_level' ),
			'exclude_groups'         => $request->get_param( 'exclude_groups' ),
			'exclude_fields'         => $request->get_param( 'exclude_fields' ),
			'update_meta_cache'      => $request->get_param( 'update_meta_cache' ),
			'signup_fields_only'     => $request->get_param( 'signup_fields_only' ),
			'fetch_fields'           => true,
		);

		if ( empty( $request->get_param( 'member_type' ) ) ) {
			$args['member_type'] = false;
		}

		$include_groups = $request->get_param( 'include_groups' );
		if ( $include_groups && ! $args['profile_group_id'] ) {
			$args['profile_group_id'] = $include_groups;
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 5.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters( 'bp_rest_xprofile_fields_get_items_query_args', $args, $request );

		/**
		 * Actually, query it.
		 *
		 * Let's not use `bp_xprofile_get_groups`, since `BP_XProfile_Data_Template` handles signup fields better.
		 */
		$template_query = new BP_XProfile_Data_Template( $args );
		$field_groups   = (array) $template_query->groups;

		$retval = array();
		foreach ( $field_groups as $group ) {
			foreach ( $group->fields as $field ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $field, $request )
				);
			}
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of XProfile group fields are fetched via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param array            $field_groups Fetched field groups.
		 * @param WP_REST_Response $response     The response data.
		 * @param WP_REST_Request  $request      The request sent to the API.
		 */
		do_action( 'bp_rest_xprofile_fields_get_items', $field_groups, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to XProfile fields.
	 *
	 * @since 5.0.0
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

		if ( bp_current_user_can( 'bp_view', array( 'bp_component' => 'xprofile' ) ) ) {
			$retval = true;
		}

		/**
		 * Filter the XProfile fields `get_items` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Whether the user has access to xprofile fields.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_xprofile_fields_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve single XProfile field.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$field = $this->get_xprofile_field_object( $request );

		if ( empty( $field->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid field ID.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( ! empty( $request->get_param( 'user_id' ) ) ) {
			$field->data = new stdClass();

			// Ensure that the requester is allowed to see this field.
			$hidden_user_fields = bp_xprofile_get_hidden_fields_for_user( $request->get_param( 'user_id' ) );

			if ( in_array( $field->id, $hidden_user_fields, true ) ) {
				$field->data->value = __( 'Value suppressed.', 'buddypress' );
			} else {
				// Get the raw value for the field.
				$field->data->value = BP_XProfile_ProfileData::get_value_byid( $field->id, $request->get_param( 'user_id' ) );
			}
		}

		$retval   = $this->prepare_item_for_response( $field, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after XProfile field is fetched via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param BP_XProfile_Field $field    Fetched field object.
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  The request sent to the API.
		 */
		do_action( 'bp_rest_xprofile_fields_get_item', $field, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific XProfile field.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
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

		if ( bp_current_user_can( 'bp_view', array( 'bp_component' => 'xprofile' ) ) ) {
			$retval = true;
		}

		/**
		 * Filter the XProfile fields `get_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Whether the user has access to xprofile fields.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_xprofile_fields_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Set additional field properties.
	 *
	 * @since 5.0.0
	 *
	 * @param integer         $field_id The profile field object ID.
	 * @param WP_REST_Request $request  The request sent to the API.
	 */
	public function set_additional_field_properties( $field_id, WP_REST_Request $request ) {
		if ( ! $field_id ) {
			return;
		}

		// Get the edit schema.
		$schema = $this->get_endpoint_args_for_item_schema( $request->get_method() );

		// Define default visibility property.
		if ( isset( $schema['default_visibility'] ) ) {
			$default_visibility = $schema['default_visibility']['default'];

			if ( $request->get_param( 'default_visibility' ) ) {
				$default_visibility = $request->get_param( 'default_visibility' );
			}

			// Save the default visibility.
			bp_xprofile_update_field_meta( $field_id, 'default_visibility', $default_visibility );
		}

		// Define allow custom visibility property.
		if ( isset( $schema['allow_custom_visibility'] ) ) {
			$allow_custom_visibility = $schema['allow_custom_visibility']['default'];

			if ( $request->get_param( 'allow_custom_visibility' ) ) {
				$allow_custom_visibility = $request->get_param( 'allow_custom_visibility' );
			}

			// Save the default visibility.
			bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', $allow_custom_visibility );
		}

		// Define autolink property.
		if ( isset( $schema['do_autolink'] ) ) {
			$do_autolink = $schema['do_autolink']['default'];

			if ( $request->get_param( 'do_autolink' ) ) {
				$do_autolink = $request->get_param( 'do_autolink' );
			}

			// Save the default visibility.
			bp_xprofile_update_field_meta( $field_id, 'do_autolink', $do_autolink );
		}
	}

	/**
	 * Create a XProfile field.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$args = array(
			'field_group_id'    => $request->get_param( 'group_id' ),
			'parent_id'         => $request->get_param( 'parent_id' ),
			'type'              => $request->get_param( 'type' ),
			'name'              => $request->get_param( 'name' ),
			'description'       => $request->get_param( 'description' ),
			'is_required'       => $request->get_param( 'required' ),
			'can_delete'        => $request->get_param( 'can_delete' ),
			'order_by'          => $request->get_param( 'order_by' ),
			'is_default_option' => $request->get_param( 'is_default_option' ),
			'option_order'      => $request->get_param( 'option_order' ),
			'field_order'       => $request->get_param( 'field_order' ),
		);

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 5.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters( 'bp_rest_xprofile_fields_create_item_query_args', $args, $request );

		$field_id = xprofile_insert_field( $args );
		if ( ! $field_id ) {
			return new WP_Error(
				'bp_rest_user_cannot_create_xprofile_field',
				__( 'Cannot create new XProfile field.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		// Define visibility and autolink field properties.
		$this->set_additional_field_properties( $field_id, $request );

		$field = $this->get_xprofile_field_object( $field_id );

		// Create Additional fields.
		$fields_update = $this->update_additional_fields_for_object( $field, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval   = $this->prepare_item_for_response( $field, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a XProfile field is created via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param BP_XProfile_Field $field     Created field object.
		 * @param WP_REST_Response  $response  The response data.
		 * @param WP_REST_Request   $request   The request sent to the API.
		 */
		do_action( 'bp_rest_xprofile_fields_create_item', $field, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a XProfile field.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create a XProfile field.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() && bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
		}

		/**
		 * Filter the XProfile fields `create_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_xprofile_fields_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update a XProfile field.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$field = $this->get_xprofile_field_object( $request );

		if ( empty( $field->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid profile field ID.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		$args = array(
			'field_id'          => $field->id,
			'field_group_id'    => empty( $request->get_param( 'group_id' ) ) ? $field->group_id : $request->get_param( 'group_id' ),
			'parent_id'         => empty( $request->get_param( 'parent_id' ) ) ? $field->parent_id : $request->get_param( 'parent_id' ),
			'type'              => empty( $request->get_param( 'type' ) ) ? $field->type : $request->get_param( 'type' ),
			'name'              => empty( $request->get_param( 'name' ) ) ? $field->name : $request->get_param( 'name' ),
			'description'       => empty( $request->get_param( 'description' ) ) ? $field->description : $request->get_param( 'description' ),
			'is_required'       => empty( $request->get_param( 'required' ) ) ? $field->is_required : $request->get_param( 'required' ),
			'can_delete'        => $request->get_param( 'can_delete' ), // Set to true by default.
			'order_by'          => empty( $request->get_param( 'order_by' ) ) ? $field->order_by : $request->get_param( 'order_by' ),
			'is_default_option' => empty( $request->get_param( 'is_default_option' ) ) ? $field->is_default_option : $request->get_param( 'is_default_option' ),
			'option_order'      => empty( $request->get_param( 'option_order' ) ) ? $field->option_order : $request->get_param( 'option_order' ),
			'field_order'       => empty( $request->get_param( 'field_order' ) ) ? $field->field_order : $request->get_param( 'field_order' ),
		);

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 5.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters( 'bp_rest_xprofile_fields_update_item_query_args', $args, $request );

		// Specific check to make sure the Full Name xprofile field will remain undeletable.
		if ( bp_xprofile_fullname_field_id() === $field->id ) {
			$args['can_delete'] = false;
		}

		$field_id = xprofile_insert_field( $args );
		if ( ! $field_id ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_xprofile_field',
				__( 'Cannot update XProfile field.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		// Define visibility and autolink field properties.
		$this->set_additional_field_properties( $field_id, $request );

		$field = $this->get_xprofile_field_object( $field_id );

		// Update Additional fields.
		$fields_update = $this->update_additional_fields_for_object( $field, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $field, $request )
			),
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a XProfile field is updated via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param BP_XProfile_Field  $field      Updated field object.
		 * @param WP_REST_Response  $response  The response data.
		 * @param WP_REST_Request   $request   The request sent to the API.
		 */
		do_action( 'bp_rest_xprofile_fields_update_item', $field, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a XProfile field.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		$retval = $this->delete_item_permissions_check( $request );

		/**
		 * Filter the XProfile fields `update_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_xprofile_fields_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a XProfile field.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		// Get the field before it's deleted.
		$field    = new BP_XProfile_Field( (int) $request->get_param( 'id' ) );
		$previous = $this->prepare_item_for_response( $field, $request );

		if ( ! $field->delete( $request->get_param( 'delete_data' ) ) ) {
			return new WP_Error(
				'bp_rest_xprofile_field_cannot_delete',
				__( 'Could not delete XProfile field.', 'buddypress' ),
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
		 * Fires after a XProfile field is deleted via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param BP_XProfile_Field $field     Deleted field object.
		 * @param WP_REST_Response  $response  The response data.
		 * @param WP_REST_Request   $request   The request sent to the API.
		 */
		do_action( 'bp_rest_xprofile_fields_delete_item', $field, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a XProfile field.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to delete this field.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$field = $this->get_xprofile_field_object( $request );

			if ( empty( $field->id ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid field ID.', 'buddypress' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the XProfile fields `delete_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_xprofile_fields_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares single XProfile field data to return as an object.
	 *
	 * @since 5.0.0
	 *
	 * @param BP_XProfile_Field $field   XProfile field object.
	 * @param WP_REST_Request   $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $field, $request ) {
		$response = rest_ensure_response(
			$this->assemble_response_data( $field, $request )
		);
		$response->add_links( $this->prepare_links( $field ) );

		/**
		 * Filter the XProfile field returned from the API.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  Request used to generate the response.
		 * @param BP_XProfile_Field  $field     XProfile field object.
		 */
		return apply_filters( 'bp_rest_xprofile_fields_prepare_value', $response, $request, $field );
	}

	/**
	 * Assembles single XProfile field data to return as an object.
	 *
	 * @since 5.0.0
	 *
	 * @param BP_XProfile_Field $field   XProfile field object.
	 * @param WP_REST_Request   $request Full data about the request.
	 * @return array
	 */
	public function assemble_response_data( $field, $request ) {
		$data = array(
			'id'                => (int) $field->id,
			'group_id'          => (int) $field->group_id,
			'parent_id'         => (int) $field->parent_id,
			'type'              => $field->type,
			'name'              => $field->name,
			'description'       => array(
				'raw'      => $field->description,
				'rendered' => apply_filters( 'bp_get_the_profile_field_description', $field->description ),
			),
			'is_required'       => (bool) $field->is_required,
			'can_delete'        => (bool) $field->can_delete,
			'field_order'       => (int) $field->field_order,
			'option_order'      => (int) $field->option_order,
			'order_by'          => strtoupper( $field->order_by ),
			'is_default_option' => (bool) $field->is_default_option,
		);

		if ( ! empty( $request->get_param( 'fetch_visibility_level' ) && ! empty( $field->visibility_level ) ) ) {
			$data['visibility_level'] = $field->visibility_level;
		}

		if (
			0 === $data['parent_id']
			&& true === wp_validate_boolean( $request->get_param( 'fetch_field_data' ) )
			&& ! empty( $request->get_param( 'user_id' ) )
		) {
			if ( isset( $field->data->id ) ) {
				$data['data']['id'] = $field->data->id;
			}

			$data['data']['value'] = array(
				'raw'          => $field->data->value,
				'unserialized' => $this->get_profile_field_unserialized_value( $field->data->value ),
				'rendered'     => $this->get_profile_field_rendered_value( $field->data->value, $field ),
			);
		}

		// Adding the options.
		if ( method_exists( $field, 'get_children' ) ) {
			$data['options'] = array_map(
				function ( $item ) use ( $request ) {
					return $this->assemble_response_data( $item, $request );
				},
				$field->get_children()
			);
		}

		$context = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		return $data;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 5.0.0
	 *
	 * @param BP_XProfile_Field $field XProfile field object.
	 * @return array
	 */
	protected function prepare_links( $field ) {
		$base       = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );
		$group_base = sprintf( '/%s/%s/', $this->namespace, 'xprofile/groups' );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . $field->id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'group'      => array(
				'href'       => rest_url( $group_base . $field->group_id ),
				'embeddable' => true,
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 5.0.0
		 *
		 * @param array             $links The prepared links of the REST response.
		 * @param BP_XProfile_Field $field XProfile field object.
		 */
		return apply_filters( 'bp_rest_xprofile_fields_prepare_links', $links, $field );
	}

	/**
	 * Get XProfile field object.
	 *
	 * @since 5.0.0
	 *
	 * @param  WP_REST_Request|int $request Request info or integer.
	 * @return BP_XProfile_Field|string
	 */
	public function get_xprofile_field_object( $request ) {
		if ( is_numeric( $request ) ) {
			$field_id = $request;
			$user_id  = null;
		} else {
			$field_id = $request->get_param( 'id' );
			$user_id  = $request->get_param( 'user_id' );
		}

		$field = xprofile_get_field( $field_id, $user_id );

		if ( empty( $field ) ) {
			return '';
		}

		return $field;
	}

	/**
	 * Retrieve the rendered value of a profile field.
	 *
	 * @since 5.0.0
	 *
	 * @param  string                    $value         The raw value of the field.
	 * @param  integer|BP_XProfile_Field $profile_field The ID or the full object for the field.
	 * @return string                                   The field value for the display context.
	 */
	public function get_profile_field_rendered_value( $value = '', $profile_field = null ) {
		if ( empty( $value ) ) {
			return '';
		}

		$profile_field = xprofile_get_field( $profile_field );

		if ( ! isset( $profile_field->id ) ) {
			return '';
		}

		// Unserialize the BuddyPress way.
		$value = bp_unserialize_profile_field( $value );

		global $field;
		$reset_global = $field;

		// Set the $field global as the `xprofile_filter_link_profile_data` filter needs it.
		$field = $profile_field;

		/**
		 * Apply filters to sanitize XProfile field value.
		 *
		 * @since 5.0.0
		 *
		 * @param string $value Value for the profile field.
		 * @param string $type  Type for the profile field.
		 * @param int    $id    ID for the profile field.
		 */
		$value = apply_filters( 'bp_get_the_profile_field_value', $value, $field->type, $field->id );

		// Reset the global before returning the value.
		$field = $reset_global;

		return $value;
	}

	/**
	 * Retrieve the unserialized value of a profile field.
	 *
	 * @since 5.0.0
	 *
	 * @param  string $value The raw value of the field.
	 * @return array The unserialized field value.
	 */
	public function get_profile_field_unserialized_value( $value = '' ) {
		if ( empty( $value ) ) {
			return array();
		}

		$unserialized_value = maybe_unserialize( $value );
		if ( ! is_array( $unserialized_value ) ) {
			$unserialized_value = (array) $unserialized_value;
		}

		return $unserialized_value;
	}

	/**
	 * Edit some properties for the CREATABLE & EDITABLE methods.
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
			$args['description']['type']    = 'string';
			$args['description']['default'] = '';
			unset( $args['description']['properties'] );

			// Add specific properties to the edit context.
			$edit_args = array();

			// The visibility level chose by the administrator is the default visibility.
			$edit_args['default_visibility']                = $args['visibility_level'];
			$edit_args['default_visibility']['description'] = __( 'Default visibility for the profile field.', 'buddypress' );

			// Unset the visibility level which can be the user defined visibility.
			unset( $args['visibility_level'] );

			// Add specific properties to the edit context.
			$edit_args['allow_custom_visibility'] = array(
				'context'     => array( 'edit' ),
				'description' => __( 'Whether to allow members to set the visibility for the profile field data or not.', 'buddypress' ),
				'default'     => 'allowed',
				'type'        => 'string',
				'enum'        => array( 'allowed', 'disabled' ),
			);

			$edit_args['do_autolink'] = array(
				'context'     => array( 'edit' ),
				'description' => __( 'Autolink status for this profile field', 'buddypress' ),
				'default'     => 'off',
				'type'        => 'string',
				'enum'        => array( 'on', 'off' ),
			);

			// Set required params for the CREATABLE method.
			if ( WP_REST_Server::CREATABLE === $method ) {
				$key                          = 'create_item';
				$args['group_id']['required'] = true;
				$args['type']['required']     = true;
				$args['name']['required']     = true;
			} elseif ( WP_REST_Server::EDITABLE === $method ) {
				$key                                        = 'update_item';
				$args['can_delete']['default']              = true;
				$args['order_by']['default']                = 'asc';
				$edit_args['default_visibility']['default'] = 'public';
			}

			// Merge arguments.
			$args = array_merge( $args, $edit_args );
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
		return apply_filters( "bp_rest_xprofile_fields_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the XProfile field schema, conforming to JSON Schema.
	 *
	 * @since 5.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( is_null( $this->schema ) ) {
			$this->schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'bp_xprofile_field',
				'type'       => 'object',
				'properties' => array(
					'id'                => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'A unique numeric ID for the profile field.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'integer',
					),
					'group_id'          => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The ID of the group the field is part of.', 'buddypress' ),
						'type'        => 'integer',
					),
					'parent_id'         => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The ID of the parent field.', 'buddypress' ),
						'type'        => 'integer',
					),
					'type'              => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The type for the profile field.', 'buddypress' ),
						'type'        => 'string',
						'enum'        => buddypress()->profile->field_types,
						'arg_options' => array(
							'sanitize_callback' => 'sanitize_key',
						),
					),
					'name'              => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The name of the profile field.', 'buddypress' ),
						'type'        => 'string',
						'arg_options' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
					'description'       => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The description of the profile field.', 'buddypress' ),
						'type'        => 'object',
						'arg_options' => array(
							'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
							'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Content for the profile field, as it exists in the database.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered' => array(
								'description' => __( 'HTML content for the profile field, transformed for display.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
					'is_required'       => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Whether the profile field must have a value.', 'buddypress' ),
						'type'        => 'boolean',
					),
					'can_delete'        => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Whether the profile field can be deleted or not.', 'buddypress' ),
						'default'     => true,
						'type'        => 'boolean',
					),
					'field_order'       => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The order of the profile field into the group of fields.', 'buddypress' ),
						'type'        => 'integer',
					),
					'option_order'      => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The order of the option into the profile field list of options', 'buddypress' ),
						'type'        => 'integer',
					),
					'order_by'          => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The way profile field\'s options are ordered.', 'buddypress' ),
						'default'     => 'asc',
						'type'        => 'string',
						'enum'        => array( 'asc', 'desc' ),
					),
					'is_default_option' => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Whether the option is the default one for the profile field.', 'buddypress' ),
						'type'        => 'boolean',
					),
					'visibility_level'  => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Who may see the saved value for this profile field.', 'buddypress' ),
						'default'     => 'public',
						'type'        => 'string',
						'enum'        => array_keys( bp_xprofile_get_visibility_levels() ),
					),
					'options'           => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Options of the profile field.', 'buddypress' ),
						'type'        => 'array',
						'readonly'    => true,
					),
					'data'              => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The saved value for this profile field.', 'buddypress' ),
						'type'        => 'object',
						'readonly'    => true,
						'properties'  => array(
							'raw'          => array(
								'description' => __( 'Value for the field, as it exists in the database.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'unserialized' => array(
								'description' => __( 'Unserialized value for the field, regular string will be casted as array.', 'buddypress' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'rendered'     => array(
								'description' => __( 'HTML value for the field, transformed for display.', 'buddypress' ),
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
		 * Filters the xprofile field schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_xprofile_field_schema', $this->add_additional_fields_schema( $this->schema ) );
	}

	/**
	 * Get the query params for the XProfile fields.
	 *
	 * @since 5.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['profile_group_id'] = array(
			'description'       => __( 'ID of the profile group of fields that have profile fields', 'buddypress' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['hide_empty_groups'] = array(
			'description'       => __( 'Whether to hide profile groups of fields that do not have any profile fields or not.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Required if you want to load a specific user\'s data.', 'buddypress' ),
			'default'           => bp_loggedin_user_id(),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['member_type'] = array(
			'description'       => __( 'Limit fields by those restricted to a given member type, or array of member types. If `$user_id` is provided, the value of `$member_type` will be overridden by the member types of the provided user. The special value of \'any\' will return only those fields that are unrestricted by member type - i.e., those applicable to any type.', 'buddypress' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'string' ),
			'sanitize_callback' => 'bp_rest_sanitize_member_types',
			'validate_callback' => 'bp_rest_validate_member_types',
		);

		$params['hide_empty_fields'] = array(
			'description'       => __( 'Whether to hide profile fields where the user has not provided data or not.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['fetch_field_data'] = array(
			'description'       => __( 'Whether to fetch data for each field. Requires a $user_id.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['signup_fields_only'] = array(
			'description'       => __( 'Whether to only return signup fields.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['fetch_visibility_level'] = array(
			'description'       => __( 'Whether to fetch the visibility level for each field.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['include_groups'] = array(
			'description'       => __( 'Ensure result set inludes specific profile field groups.', 'buddypress' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude_groups'] = array(
			'description'       => __( 'Ensure result set excludes specific profile field groups.', 'buddypress' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude_fields'] = array(
			'description'       => __( 'Ensure result set excludes specific profile fields.', 'buddypress' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'string' ),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['update_meta_cache'] = array(
			'description'       => __( 'Whether to pre-fetch xprofilemeta for all retrieved groups, fields, and data.', 'buddypress' ),
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
		return apply_filters( 'bp_rest_xprofile_fields_collection_params', $params );
	}
}
