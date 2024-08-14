<?php
/**
 * BP REST: BP_REST_Attachments_Group_Avatar_Endpoint class
 *
 * @package BuddyPress
 * @since 5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Group Avatar endpoints.
 *
 * @since 5.0.0
 */
class BP_REST_Attachments_Group_Avatar_Endpoint extends WP_REST_Controller {
	use BP_REST_Attachments;

	/**
	 * Reuse some parts of the BP_REST_Groups_Endpoint class.
	 *
	 * @since 5.0.0
	 *
	 * @var BP_REST_Groups_Endpoint
	 */
	protected $groups_endpoint;

	/**
	 * BP_Attachment_Avatar Instance.
	 *
	 * @since 5.0.0
	 *
	 * @var BP_Attachment_Avatar
	 */
	protected $avatar_instance;

	/**
	 * Hold the group object.
	 *
	 * @since 5.0.0
	 *
	 * @var BP_Groups_Group
	 */
	protected $group;

	/**
	 * Group object type.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	protected $object = 'group';

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 */
	public function __construct() {
		$this->namespace       = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base       = buddypress()->groups->id;
		$this->groups_endpoint = new BP_REST_Groups_Endpoint();
		$this->avatar_instance = new BP_Attachment_Avatar();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 5.0.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<group_id>[\d]+)/avatar',
			array(
				'args'   => array(
					'group_id' => array(
						'description' => __( 'A unique numeric ID for the Group.', 'buddypress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_item_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
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
	 * Fetch an existing group avatar.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$args = array();

		foreach ( array( 'full', 'thumb' ) as $type ) {
			$args[ $type ] = bp_core_fetch_avatar(
				array(
					'object'  => $this->object,
					'type'    => $type,
					'item_id' => (int) $this->group->id,
					'html'    => (bool) $request->get_param( 'html' ),
					'alt'     => $request->get_param( 'alt' ),
				)
			);
		}

		// Get the avatar object.
		$avatar = $this->get_avatar_object( $args );

		if ( ! $avatar->full && ! $avatar->thumb ) {
			return new WP_Error(
				'bp_rest_attachments_group_avatar_no_image',
				__( 'Sorry, there was a problem fetching this group avatar.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$retval   = $this->prepare_item_for_response( $avatar, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group avatar is fetched via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param string            $avatar   The group avatar.
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  The request sent to the API.
		 */
		do_action( 'bp_rest_attachments_group_avatar_get_item', $avatar, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get a group avatar.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you cannot view group details.', 'buddypress' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( bp_current_user_can( 'bp_view', array( 'bp_component' => 'groups' ) ) ) {
			$retval = new WP_Error(
				'bp_rest_group_invalid_id',
				__( 'Invalid group ID.', 'buddypress' ),
				array( 'status' => 404 )
			);

			$this->group = $this->groups_endpoint->get_group_object( $request );

			if ( false !== $this->group ) {
				$retval = true;
			}
		}

		/**
		 * Filter the group avatar `get_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_attachments_group_avatar_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Upload a group avatar.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {

		// Get the image file from $_FILES.
		$files = $request->get_file_params();

		if ( empty( $files ) ) {
			return new WP_Error(
				'bp_rest_attachments_group_avatar_no_image_file',
				__( 'Sorry, you need an image file to upload.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		// Upload the avatar.
		$avatar = $this->upload_avatar_from_file( $files );
		if ( is_wp_error( $avatar ) ) {
			return $avatar;
		}

		$retval   = $this->prepare_item_for_response( $avatar, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group avatar is uploaded via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param stdClass          $avatar   The group avatar object.
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  The request sent to the API.
		 */
		do_action( 'bp_rest_attachments_group_avatar_create_item', $avatar, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to upload a group avatar.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		if ( ! is_wp_error( $retval ) ) {
			if ( bp_disable_group_avatar_uploads() || false === buddypress()->avatar->show_avatars ) {
				$retval = new WP_Error(
					'bp_rest_attachments_group_avatar_disabled',
					__( 'Sorry, group avatar upload is disabled.', 'buddypress' ),
					array(
						'status' => 500,
					)
				);
			} elseif ( groups_is_user_admin( bp_loggedin_user_id(), $this->group->id ) || current_user_can( 'bp_moderate' ) ) {
				$retval = true;
			} else {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not authorized to perform this action.', 'buddypress' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the group avatar `create_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_attachments_group_avatar_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete an existing group avatar.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$group_id = (int) $this->group->id;

		if ( ! bp_get_group_has_avatar( $group_id ) ) {
			return new WP_Error(
				'bp_rest_attachments_group_avatar_no_uploaded_avatar',
				__( 'Sorry, there are no uploaded avatars for this group on this site.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		$args = array();

		foreach ( array( 'full', 'thumb' ) as $type ) {
			$args[ $type ] = bp_core_fetch_avatar(
				array(
					'object'  => $this->object,
					'type'    => $type,
					'item_id' => $group_id,
					'html'    => false,
				)
			);
		}

		// Get the avatar object before deleting it.
		$avatar = $this->get_avatar_object( $args );

		$deleted = bp_core_delete_existing_avatar(
			array(
				'object'  => $this->object,
				'item_id' => $group_id,
			)
		);

		if ( ! $deleted ) {
			return new WP_Error(
				'bp_rest_attachments_group_avatar_delete_failed',
				__( 'Sorry, there was a problem deleting this group avatar.', 'buddypress' ),
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
				'previous' => $avatar,
			)
		);

		/**
		 * Fires after a group avatar is deleted via the REST API.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  The request sent to the API.
		 */
		do_action( 'bp_rest_attachments_group_avatar_delete_item', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to delete a group avatar.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = $this->create_item_permissions_check( $request );

		/**
		 * Filter the group avatar `delete_item` permissions check.
		 *
		 * @since 5.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_attachments_group_avatar_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares avatar data to return as an object.
	 *
	 * @since 5.0.0
	 *
	 * @param stdClass|string $avatar  Avatar object or string with url or image with html.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $avatar, $request ) {
		$data = array(
			'full'  => $avatar->full,
			'thumb' => $avatar->thumb,
		);

		$context  = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		/**
		 * Filter a group avatar value returned from the API.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_REST_Response  $response Response.
		 * @param WP_REST_Request   $request  Request used to generate the response.
		 * @param stdClass|string   $avatar   Avatar object or string with url or image with html.
		 */
		return apply_filters( 'bp_rest_attachments_group_avatar_prepare_value', $response, $request, $avatar );
	}

	/**
	 * Get the plugin schema, conforming to JSON Schema.
	 *
	 * @since 5.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( is_null( $this->schema ) ) {
			$this->schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'bp_attachments_group_avatar',
				'type'       => 'object',
				'properties' => array(
					'full'  => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Full size of the image file.', 'buddypress' ),
						'type'        => 'string',
						'format'      => 'uri',
						'readonly'    => true,
					),
					'thumb' => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Thumb size of the image file.', 'buddypress' ),
						'type'        => 'string',
						'format'      => 'uri',
						'readonly'    => true,
					),
				),
			);
		}

		/**
		 * Filters the attachments group avatar schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_attachments_group_avatar_schema', $this->add_additional_fields_schema( $this->schema ) );
	}

	/**
	 * Get the query params for the `get_item`.
	 *
	 * @since 5.0.0
	 *
	 * @return array
	 */
	public function get_item_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		// Removing unused params.
		unset( $params['search'], $params['page'], $params['per_page'] );

		$params['html'] = array(
			'description'       => __( 'Whether to return an <img> HTML element, vs a raw URL to a group avatar.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['alt'] = array(
			'description'       => __( 'The alt attribute for the <img> element.', 'buddypress' ),
			'default'           => '',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the item collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_attachments_group_avatar_collection_params', $params );
	}
}
