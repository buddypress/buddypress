<?php
/**
 * BP_Core_Components_REST_Controller class
 *
 * @package BuddyPress
 * @since 15.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Components endpoints.
 *
 * @since 15.0.0
 */
class BP_Core_Components_REST_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 15.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'components';
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
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'name'   => array(
							'type'        => 'string',
							'required'    => true,
							'description' => __( 'Name of the component.', 'buddypress' ),
						),
						'action' => array(
							'description' => __( 'Whether to activate or deactivate the component.', 'buddypress' ),
							'type'        => 'string',
							'enum'        => array( 'activate', 'deactivate' ),
							'required'    => true,
						),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve components.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$args = array(
			'type'     => $request->get_param( 'type' ),
			'status'   => $request->get_param( 'status' ),
			'per_page' => $request->get_param( 'per_page' ),
		);

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 15.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters( 'bp_rest_components_get_items_query_args', $args, $request );

		$type = $args['type'];

		// Get all components based on type.
		$components = bp_core_get_components( $type );

		// Active components.
		$active_components = (array) apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components' ) );

		// Core component is always active.
		if ( 'optional' !== $type && ! empty( $components['core'] ) ) {
			$active_components['core'] = '1';
		}

		// Inactive components.
		$inactive_components = array_diff( array_keys( $components ), array_keys( $active_components ) );

		$current_components = array();
		switch ( $args['status'] ) {
			case 'all':
				foreach ( array_keys( $components ) as $name ) {
					$current_components[] = $this->get_component_info( $name );
				}
				break;

			case 'active':
				foreach ( array_keys( $active_components ) as $component ) {
					$current_components[] = $this->get_component_info( $component );
				}
				break;

			case 'inactive':
				foreach ( $inactive_components as $component ) {
					$current_components[] = $this->get_component_info( $component );
				}
				break;
		}

		$retval = array();
		foreach ( $current_components as $component ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $component, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, count( $current_components ), $args['per_page'] );

		/**
		 * Fires after a list of components is fetched via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param array            $current_components Fetched components.
		 * @param WP_REST_Response $response           The response data.
		 * @param WP_REST_Request  $request            The request sent to the API.
		 */
		do_action( 'bp_rest_components_get_items', $current_components, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to list components.
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

		// The `get_items` endpoint can be used by BP Blocks to check whether some component's features are active or not.
		if ( bp_current_user_can( 'manage_options' ) || ( 'active' === $request->get_param( 'status' ) && current_user_can( 'publish_posts' ) ) ) {
			$retval = true;
		}

		/**
		 * Filter the components `get_items` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_components_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Activate/Deactivate a component.
	 *
	 * @since 15.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$component = $request->get_param( 'name' );

		if ( ! $this->component_exists( $component ) ) {
			return new WP_Error(
				'bp_rest_component_nonexistent',
				__( 'Sorry, this component does not exist.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( 'activate' === $request->get_param( 'action' ) ) {
			if ( bp_is_active( $component ) ) {
				return new WP_Error(
					'bp_rest_component_already_active',
					__( 'Sorry, this component is already active.', 'buddypress' ),
					array(
						'status' => 400,
					)
				);
			}

			$component_info = $this->activate_helper( $component );
		} else {
			if ( ! bp_is_active( $component ) ) {
				return new WP_Error(
					'bp_rest_component_inactive',
					__( 'Sorry, this component is not active.', 'buddypress' ),
					array(
						'status' => 400,
					)
				);
			}

			if ( array_key_exists( $component, bp_core_get_components( 'required' ) ) ) {
				return new WP_Error(
					'bp_rest_required_component',
					__( 'Sorry, you cannot deactivate a required component.', 'buddypress' ),
					array(
						'status' => 400,
					)
				);
			}

			$component_info = $this->deactivate_helper( $component );
		}

		$retval   = $this->prepare_item_for_response( $component_info, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a component is updated via the REST API.
		 *
		 * @since 15.0.0
		 *
		 * @param array             $component_info Component info.
		 * @param WP_REST_Response  $response       The response data.
		 * @param WP_REST_Request   $request        The request sent to the API.
		 */
		do_action( 'bp_rest_components_update_item', $component_info, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a component.
	 *
	 * @since 15.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		$retval = $this->get_items_permissions_check( $request );

		/**
		 * Filter the components `update_item` permissions check.
		 *
		 * @since 15.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_components_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares component data for return as an object.
	 *
	 * @since 15.0.0
	 *
	 * @param array           $component The component and its values.
	 * @param WP_REST_Request $request   Full details about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $component, $request ) {
		$context  = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';
		$data     = $this->add_additional_fields_to_object( $component, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		/**
		 * Filter a component value returned from the API.
		 *
		 * @since 15.0.0
		 *
		 * @param WP_REST_Response $response  The Response data.
		 * @param WP_REST_Request  $request   Request used to generate the response.
		 * @param array            $component The component and its values.
		 */
		return apply_filters( 'bp_rest_components_prepare_value', $response, $request, $component );
	}

	/**
	 * Verify Component Status.
	 *
	 * @since 15.0.0
	 *
	 * @param string $name        Component name.
	 * @param string $return_type Use `string` to get the l10n string. Default.
	 *                            Use `bool` to get whether the component is active or not.
	 *                            Use `array` to get both information.
	 * @return string|bool|array By default a l10n string is returned.
	 *                           True if the component is active, false otherwise when 'bool' is requested.
	 *                           An array containing both information when 'array' is requested.
	 */
	protected function verify_component_status( $name, $return_type = 'string' ) {
		$retval = array(
			'string' => __( 'inactive', 'buddypress' ),
			'bool'   => false,
		);

		if ( 'core' === $name || bp_is_active( $name ) ) {
			$retval = array(
				'string' => __( 'active', 'buddypress' ),
				'bool'   => true,
			);
		}

		if ( isset( $retval[ $return_type ] ) ) {
			return $retval[ $return_type ];
		}

		return $retval;
	}

	/**
	 * Deactivate component helper.
	 *
	 * @since 15.0.0
	 *
	 * @param string $component Component id.
	 * @return array
	 */
	protected function deactivate_helper( $component ) {

		$active_components =& buddypress()->active_components;

		// Set for the rest of the page load.
		unset( $active_components[ $component ] );

		// Save in the db.
		bp_update_option( 'bp-active-components', $active_components );

		return $this->get_component_info( $component );
	}

	/**
	 * Activate component helper.
	 *
	 * @since 15.0.0
	 *
	 * @param string $component Component id.
	 * @return array
	 */
	protected function activate_helper( $component ) {

		$active_components =& buddypress()->active_components;

		// Set for the rest of the page load.
		$active_components[ $component ] = 1;

		// Save in the db.
		bp_update_option( 'bp-active-components', $active_components );

		// Ensure that dbDelta() is defined.
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Run the setup, in case tables have to be created.
		require_once buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-schema.php';

		bp_core_install( $active_components );
		bp_core_add_page_mappings( $active_components );

		return $this->get_component_info( $component );
	}

	/**
	 * Get component info helper.
	 *
	 * @since 15.0.0
	 *
	 * @param string $component Component id.
	 * @return array
	 */
	public function get_component_info( $component ) {

		// Get all components.
		$components = bp_core_get_components();

		// Init the component's data.
		$data = array();

		// Get specific component info.
		if ( isset( $components[ $component ] ) ) {
			$data = (array) $components[ $component ];
		}

		// Return empty early.
		if ( ! $data ) {
			return $data;
		}

		// Get BuddyPress main instance.
		$bp = buddypress();

		// Get status data.
		$status = $this->verify_component_status( $component, 'array' );

		// Set component's basic information.
		$info = array(
			'name'        => $component,
			'status'      => $status['string'],
			'is_active'   => $status['bool'],
			'title'       => $data['title'],
			'description' => $data['description'],
			'features'    => null,
		);

		// Set component's features.
		if ( $status['bool'] ) {
			// @todo check the features list is exhaustive.
			switch ( $component ) {
				case 'groups':
					$features = array(
						'avatar'         => $bp->avatar && $bp->avatar->show_avatars && ! bp_disable_group_avatar_uploads(),
						'cover'          => bp_is_active( 'groups', 'cover_image' ),
						'group_creation' => bp_restrict_group_creation() ? 'adminsonly' : 'members',
					);
					break;
				case 'members':
					$features = array(
						'account_deletion'    => ! bp_disable_account_deletion(),
						'avatar'              => $bp->avatar && $bp->avatar->show_avatars,
						'cover'               => bp_is_active( 'members', 'cover_image' ),
						'invitations'         => bp_get_members_invitations_allowed(),
						'membership_requests' => bp_is_active( 'members', 'membership_requests' ) && ! bp_get_signup_allowed() && (bool) bp_get_option( 'bp-enable-membership-requests' ),
					);
					break;
				case 'activity':
					$features = array(
						'auto_refresh' => bp_is_activity_heartbeat_active(),
						'embeds'       => bp_is_active( 'activity', 'embeds' ),
						'favorite'     => bp_activity_can_favorite(),
						'mentions'     => bp_activity_do_mentions(),
						'types'        => bp_activity_get_types_list(),
					);
					break;
				case 'blogs':
					$features = array(
						'site_icon'       => bp_is_active( 'blogs', 'site-icon' ),
						'sites_directory' => is_multisite(),
					);
					break;
				case 'messages':
					$features = array(
						'star' => bp_is_active( 'messages', 'star' ),
					);
					break;
				default:
					$features = null;
					break;
			}

			/**
			 * Filter here to edit component's features.
			 *
			 * The dynamic portion of the filter is filled with the component's ID.
			 *
			 * @since 15.0.0
			 *
			 * @param array $features The component's features.
			 */
			$info['features'] = apply_filters( 'bp_rest_' . $component . '_component_features', $features );
		}

		return $info;
	}

	/**
	 * Does the component exist?
	 *
	 * @since 15.0.0
	 *
	 * @param string $component Component.
	 * @return bool
	 */
	protected function component_exists( $component ) {
		return in_array( $component, array_keys( bp_core_get_components() ), true );
	}

	/**
	 * Get the components schema, conforming to JSON Schema.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( is_null( $this->schema ) ) {
			$this->schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'bp_components',
				'type'       => 'object',
				'properties' => array(
					'name'        => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Name of the object.', 'buddypress' ),
						'type'        => 'string',
					),
					'is_active'   => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Whether the component is active or not.', 'buddypress' ),
						'type'        => 'boolean',
						'default'     => false,
					),
					'status'      => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Whether the object is active or inactive.', 'buddypress' ),
						'type'        => 'string',
						// We need to use what returns `$this->verify_component_status()` by default here.
						'enum'        => array(
							__( 'active', 'buddypress' ),
							__( 'inactive', 'buddypress' ),
						),
					),
					'title'       => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'HTML title of the object.', 'buddypress' ),
						'type'        => 'string',
					),
					'description' => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'HTML description of the object.', 'buddypress' ),
						'type'        => 'string',
					),
					'features'    => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Information about active features for the component.', 'buddypress' ),
						'type'        => array( 'object', 'null' ),
						'properties'  => array(),
						'default'     => null,
					),
				),
			);
		}

		/**
		 * Filters the components schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_components_schema', $this->add_additional_fields_schema( $this->schema ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['status'] = array(
			'description'       => __( 'Limit result set to items with a specific status.', 'buddypress' ),
			'default'           => 'all',
			'type'              => 'string',
			'enum'              => array( 'all', 'active', 'inactive' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['type'] = array(
			'description'       => __( 'Limit result set to items with a specific type.', 'buddypress' ),
			'default'           => 'all',
			'type'              => 'string',
			'enum'              => array( 'all', 'optional', 'retired', 'required' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_components_collection_params', $params );
	}
}
