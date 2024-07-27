<?php
/**
 * BP_REST_Signup_Controller class
 *
 * @package BuddyPress
 * @since 6.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Signup endpoints.
 *
 * Use /signup
 * Use /signup/{id}
 * Use /signup/resend
 * Use /signup/activate/{activation_key}
 *
 * @since 6.0.0
 */
class BP_REST_Signup_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 6.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'signup';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 6.0.0
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
						'description'       => __( 'Identifier for the signup. Can be a signup ID, an email address, or an activation key.', 'buddypress' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
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
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'edit' ) ),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		// Register the activate route.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate/(?P<activation_key>[\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'activate_item' ),
					'permission_callback' => array( $this, 'activate_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'edit' ) ),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		// Register the resend route.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/resend',
			array(
				'args' => array(
					'id' => array(
						'description'       => __( 'Identifier for the signup. Can be a signup ID, an email address, or an activation key.', 'buddypress' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'required'          => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'signup_resend_activation_email' ),
					'permission_callback' => array( $this, 'signup_resend_activation_email_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'edit' ) ),
					),
				),
			)
		);
	}

	/**
	 * Retrieve signups.
	 *
	 * @since 6.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$args = array(
			'include'    => $request->get_param( 'include' ),
			'order'      => $request->get_param( 'order' ),
			'orderby'    => $request->get_param( 'orderby' ),
			'user_login' => $request->get_param( 'user_login' ),
			'number'     => $request->get_param( 'number' ),
			'offset'     => $request->get_param( 'offset' ),
		);

		if ( empty( $request->get_param( 'include' ) ) ) {
			$args['include'] = false;
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 6.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters( 'bp_rest_signup_get_items_query_args', $args, $request );

		// Actually, query it.
		$signups = BP_Signup::get( $args );

		$retval = array();
		foreach ( $signups['signups'] as $signup ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $signup, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $signups['total'], $args['number'] );

		/**
		 * Fires after a list of signups is fetched via the REST API.
		 *
		 * @since 6.0.0
		 *
		 * @param array            $signups   Fetched signups.
		 * @param WP_REST_Response $response  The response data.
		 * @param WP_REST_Request  $request   The request sent to the API.
		 */
		do_action( 'bp_rest_signup_get_items', $signups, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to signup items.
	 *
	 * @since 6.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not authorized to perform this action.', 'buddypress' ),
			array( 'status' => rest_authorization_required_code() )
		);

		$capability = is_multisite() ? 'manage_network_users' : 'edit_users';

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to perform this action.', 'buddypress' ),
				array( 'status' => rest_authorization_required_code() )
			);
		} elseif ( bp_current_user_can( $capability ) ) {
			$retval = true;
		}

		/**
		 * Filter the signup `get_items` permissions check.
		 *
		 * @since 6.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_signup_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve single signup.
	 *
	 * @since 6.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		// Get signup.
		$signup   = $this->get_signup_object( $request->get_param( 'id' ) );
		$retval   = $this->prepare_item_for_response( $signup, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires before a signup is retrieved via the REST API.
		 *
		 * @since 6.0.0
		 *
		 * @param BP_Signup         $signup    The signup object.
		 * @param WP_REST_Response  $response  The response data.
		 * @param WP_REST_Request   $request   The request sent to the API.
		 */
		do_action( 'bp_rest_signup_get_item', $signup, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get a signup.
	 *
	 * @since 6.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not authorized to perform this action.', 'buddypress' ),
			array( 'status' => rest_authorization_required_code() )
		);

		$capability = is_multisite() ? 'manage_network_users' : 'edit_users';

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to perform this action.', 'buddypress' ),
				array( 'status' => rest_authorization_required_code() )
			);
		} elseif ( bp_current_user_can( $capability ) ) {
			$retval = true;
		}

		if ( ! is_wp_error( $retval ) ) {
			$signup = $this->get_signup_object( $request->get_param( 'id' ) );

			if ( empty( $signup ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid signup id.', 'buddypress' ),
					array( 'status' => 404 )
				);
			}
		}

		/**
		 * Filter the signup `get_item` permissions check.
		 *
		 * @since 6.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_signup_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create signup.
	 *
	 * @since 6.0.0
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		// Validate user signup.
		$signup_validation = bp_core_validate_user_signup( $request->get_param( 'user_login' ), $request->get_param( 'user_email' ) );
		if ( is_wp_error( $signup_validation['errors'] ) && $signup_validation['errors']->get_error_messages() ) {
			// Return the first error.
			return new WP_Error(
				'bp_rest_signup_validation_failed',
				$signup_validation['errors']->get_error_message(),
				array( 'status' => 500 )
			);
		}

		// Use the validated login and email.
		$user_login = $signup_validation['user_name'];
		$user_email = $signup_validation['user_email'];

		// Init the signup meta.
		$meta = array();

		// Init some Multisite specific variables.
		$domain     = '';
		$path       = '';
		$site_title = '';
		$site_name  = '';

		if ( is_multisite() ) {
			$user_login    = preg_replace( '/\s+/', '', sanitize_user( $user_login, true ) );
			$user_email    = sanitize_email( $user_email );
			$wp_key_suffix = $user_email;

			if ( $this->is_blog_signup_allowed() ) {
				$site_title = $request->get_param( 'site_title' );
				$site_name  = $request->get_param( 'site_name' );

				if ( $site_title && $site_name ) {
					// Validate the blog signup.
					$blog_signup_validation = bp_core_validate_blog_signup( $site_name, $site_title );
					if ( is_wp_error( $blog_signup_validation['errors'] ) && $blog_signup_validation['errors']->get_error_messages() ) {
						// Return the first error.
						return new WP_Error(
							'bp_rest_blog_signup_validation_failed',
							$blog_signup_validation['errors']->get_error_message(),
							array(
								'status' => 500,
							)
						);
					}

					$domain        = $blog_signup_validation['domain'];
					$wp_key_suffix = $domain;
					$path          = $blog_signup_validation['path'];
					$site_title    = $blog_signup_validation['blog_title'];
					$site_public   = (bool) $request->get_param( 'site_public' );

					$meta = array(
						'lang_id' => 1,
						'public'  => $site_public ? 1 : 0,
					);

					$site_language = $request->get_param( 'site_language' );
					$languages     = $this->get_available_languages();

					if ( in_array( $site_language, $languages, true ) ) {
						$language = wp_unslash( sanitize_text_field( $site_language ) );

						if ( $language ) {
							$meta['WPLANG'] = $language;
						}
					}
				}
			}
		}

		$password       = $request->get_param( 'password' );
		$check_password = $this->check_user_password( $password );

		if ( is_wp_error( $check_password ) ) {
			return $check_password;
		}

		// Hash and store the password.
		$meta['password'] = wp_hash_password( $password );

		// Get signup data.
		$signup_field_data = (array) $request->get_param( 'signup_field_data' );

		// Store the profile field data.
		if ( bp_is_active( 'xprofile' ) ) {
			$profile_field_ids = array();
			$args              = array(
				'signup_fields_only' => true,
				'fetch_fields'       => true,
			);

			/**
			 * Get signup fields.
			 *
			 * Let's not use `bp_xprofile_get_groups`, since `BP_XProfile_Data_Template` handles signup fields better.
			 */
			$template_query = new BP_XProfile_Data_Template( $args );
			$signup_group   = $template_query->groups[0];

			foreach ( $signup_group->fields as $field ) {

				// Skip field if it's already in the profile field IDs.
				if ( in_array( $field->id, $profile_field_ids, true ) ) {
					continue;
				}

				foreach ( $signup_field_data as $field_data ) {
					$field_id         = (int) $field_data['field_id'];
					$field_value      = $field_data['value'];
					$field_visibility = 'public';

					if ( isset( $field_data['visibility'] ) ) {
						$field_visibility = $field_data['visibility'];
					}

					if ( $field_id !== $field->id ) {
						continue;
					}

					if ( (bool) $field->is_required && empty( $field_value ) ) {
						return new WP_Error(
							'bp_rest_signup_field_required',
							sprintf(
								/* translators: %s: Field name. */
								__( 'The %s field, and its value, are required.', 'buddypress' ),
								$field->name
							),
							array( 'status' => 500 )
						);
					}

					$profile_field_ids[] = $field_id;
					$field_value         = array_map( 'trim', explode( ', ', $field_value ) );

					if ( false === (bool) $field->type_obj->supports_multiple_defaults ) {
						$field_value = reset( $field_value );
					}

					if ( ! empty( $field_value ) ) {
						/**
						 * Handle datebox field values.
						 *
						 * We expect a date in the format 'Y-m-d'.
						 */
						if ( 'datebox' === $field->type_obj->name ) {
							// @todo update to use `gmdate` when BP core does it too.
							$field_value = date( 'Y-m-d H:i:s', strtotime( $field_value ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						}

						$meta[ 'field_' . $field_id ] = $field_value;
					}

					if ( ! empty( $field_visibility ) ) {
						$meta[ 'field_' . $field_id . '_visibility' ] = $field_visibility;
					}
				}

				// Check if the required field is filled.
				if ( ! in_array( $field->id, $profile_field_ids, true ) && (bool) $field->is_required ) {
					return new WP_Error(
						'bp_rest_signup_field_required',
						sprintf(
							/* translators: %s: Field name. */
							__( 'The %s field is required.', 'buddypress' ),
							$field->name
						),
						array( 'status' => 500 )
					);
				}
			}

			// Store the profile field ID's in meta.
			$meta['profile_field_ids'] = implode( ',', array_unique( wp_parse_id_list( $profile_field_ids ) ) );
		}

		if ( is_multisite() ) {
			// On Multisite, use the WordPress way to generate the activation key.
			$activation_key = substr( md5( time() . wp_rand() . $wp_key_suffix ), 0, 16 );

			if ( $site_title && $site_name ) {
				/** This filter is documented in wp-includes/ms-functions.php */
				$meta = apply_filters( 'signup_site_meta', $meta, $domain, $path, $site_title, $user_login, $user_email, $activation_key );
			} else {
				/** This filter is documented in wp-includes/ms-functions.php */
				$meta = apply_filters( 'signup_user_meta', $meta, $user_login, $user_email, $activation_key );
			}
		} else {
			$activation_key = wp_generate_password( 32, false );
		}

		/**
		 * Filters the user meta used for signup.
		 *
		 * @param array $meta Array of user meta to add to signup.
		 */
		$meta = apply_filters( 'bp_signup_usermeta', $meta );

		/**
		 * Allow plugins to add their signup meta specific to the BP REST API.
		 *
		 * @since 6.0.0
		 *
		 * @param array           $meta    The signup meta.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$meta = apply_filters( 'bp_rest_signup_create_item_meta', $meta, $request );

		$signup_args = array(
			'user_login'     => $user_login,
			'user_email'     => $user_email,
			'activation_key' => $activation_key,
			'domain'         => $domain,
			'path'           => $path,
			'title'          => $site_title,
			'meta'           => $meta,
		);

		// Add signup.
		$id = \BP_Signup::add( $signup_args );

		if ( ! is_numeric( $id ) ) {
			return new WP_Error(
				'bp_rest_signup_cannot_create',
				__( 'Cannot create new signup.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$signup        = $this->get_signup_object( $id );
		$signup_update = $this->update_additional_fields_for_object( $signup, $request );

		if ( is_wp_error( $signup_update ) ) {
			return $signup_update;
		}

		if ( is_multisite() ) {
			if ( $site_title && $site_name ) {
				/** This action is documented in wp-includes/ms-functions.php */
				do_action( 'after_signup_site', $signup->domain, $signup->path, $signup->title, $signup->user_login, $signup->user_email, $signup->activation_key, $signup->meta );
			} else {
				/** This action is documented in wp-includes/ms-functions.php */
				do_action( 'after_signup_user', $signup->user_login, $signup->user_email, $signup->activation_key, $signup->meta );
			}
			/** This filter is documented in bp-members/bp-members-functions.php */
		} elseif ( apply_filters( 'bp_core_signup_send_activation_key', true, false, $signup->user_email, $signup->activation_key, $signup->meta ) ) {
			$salutation = $signup->user_login;
			if ( isset( $signup->user_name ) && $signup->user_name ) {
				$salutation = $signup->user_name;
			}

			bp_core_signup_send_validation_email( false, $signup->user_email, $signup->activation_key, $salutation );
		}

		$retval   = $this->prepare_item_for_response( $signup, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a signup item is created via the REST API.
		 *
		 * @since 6.0.0
		 *
		 * @param BP_Signup        $signup   The created signup.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_signup_create_item', $signup, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create a signup.
	 *
	 * @since 6.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true
	 */
	public function create_item_permissions_check( $request ) {

		/**
		 * Filter the signup `create_item` permissions check.
		 *
		 * @since 6.0.0
		 *
		 * @param true   $value Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_signup_create_item_permissions_check', true, $request );
	}

	/**
	 * Delete a signup.
	 *
	 * @since 6.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		// Get the signup before it's deleted.
		$signup   = $this->get_signup_object( $request->get_param( 'id' ) );
		$previous = $this->prepare_item_for_response( $signup, $request );
		$deleted  = BP_Signup::delete( array( $signup->id ) );

		if ( ! $deleted ) {
			return new WP_Error(
				'bp_rest_signup_cannot_delete',
				__( 'Could not delete signup.', 'buddypress' ),
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
		 * Fires after a signup is deleted via the REST API.
		 *
		 * @since 6.0.0
		 *
		 * @param BP_Signup        $signup   The deleted signup.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_signup_delete_item', $signup, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a signup.
	 *
	 * @since 6.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the signup `delete_item` permissions check.
		 *
		 * @since 6.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_signup_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Activate a signup.
	 *
	 * @since 6.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function activate_item( $request ) {
		// Get the activation key.
		$activation_key = $request->get_param( 'activation_key' );

		// Get the signup to activate thanks to the activation key.
		$signup    = $this->get_signup_object( $activation_key );
		$activated = bp_core_activate_signup( $activation_key );

		if ( ! $activated ) {
			return new WP_Error(
				'bp_rest_signup_activate_fail',
				__( 'Fail to activate the signup.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$retval   = $this->prepare_item_for_response( $signup, $request );
		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a signup is activated via the REST API.
		 *
		 * @since 6.0.0
		 *
		 * @param BP_Signup        $signup   The activated signup.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_signup_activate_item', $signup, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to activate a signup.
	 *
	 * @since 6.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error
	 */
	public function activate_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_invalid_activation_key',
			__( 'Invalid activation key.', 'buddypress' ),
			array(
				'status' => 404,
			)
		);

		// Get the activation key.
		$activation_key = $request->get_param( 'activation_key' );

		// Check the activation key is valid.
		if ( $this->get_signup_object( $activation_key ) ) {
			$retval = true;
		}

		/**
		 * Filter the signup `activate_item` permissions check.
		 *
		 * @since 6.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_signup_activate_item_permissions_check', $retval, $request );
	}

	/**
	 * Resend the activation email.
	 *
	 * @since 9.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function signup_resend_activation_email( $request ) {
		$signup_id = $request->get_param( 'id' );
		$send      = \BP_Signup::resend( array( $signup_id ) );

		if ( ! empty( $send['errors'] ) ) {
			return new WP_Error(
				'bp_rest_signup_resend_activation_email_fail',
				__( 'Your account has already been activated.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$response = rest_ensure_response( array( 'sent' => true ) );

		/**
		 * Fires after an activation email was (re)sent via the REST API.
		 *
		 * @since 9.0.0
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_signup_resend_activation_email', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to resend the activation email.
	 *
	 * @since 9.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error
	 */
	public function signup_resend_activation_email_permissions_check( $request ) {
		$retval = true;
		$signup = $this->get_signup_object( $request->get_param( 'id' ) );

		if ( empty( $signup ) ) {
			$retval = new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid signup id.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		/**
		 * Filter the signup resend activation email permissions check.
		 *
		 * @since 9.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_signup_resend_activation_email_permissions_check', $retval, $request );
	}

	/**
	 * Prepares signup to return as an object.
	 *
	 * @since 6.0.0
	 *
	 * @param  BP_Signup       $signup  Signup object.
	 * @param  WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $signup, $request ) {
		$data = array(
			'id'             => (int) $signup->id,
			'user_login'     => $signup->user_login,
			'registered'     => bp_rest_prepare_date_response( $signup->registered, get_date_from_gmt( $signup->registered ) ),
			'registered_gmt' => bp_rest_prepare_date_response( $signup->registered ),
		);

		// The user name is only available when the XProfile component is active.
		if ( isset( $signup->user_name ) ) {
			$data['user_name'] = $signup->user_name;
		}

		$context = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';

		if ( 'edit' === $context ) {
			$data['user_email']    = $signup->user_email;
			$data['date_sent']     = bp_rest_prepare_date_response( $signup->date_sent, get_date_from_gmt( $signup->date_sent ) );
			$data['date_sent_gmt'] = bp_rest_prepare_date_response( $signup->date_sent );
			$data['count_sent']    = (int) $signup->count_sent;

			if ( is_multisite() && $signup->domain && $signup->path && $signup->title ) {
				if ( is_subdomain_install() ) {
					$domain_parts = explode( '.', $signup->domain );
					$site_name    = reset( $domain_parts );
				} else {
					$domain_parts = explode( '/', $signup->path );
					$site_name    = end( $domain_parts );
				}

				$data['site_name']     = $site_name;
				$data['site_title']    = $signup->title;
				$data['site_public']   = isset( $signup->meta['public'] ) ? (bool) $signup->meta['public'] : true;
				$data['site_language'] = isset( $signup->meta['WPLANG'] ) ? $signup->meta['WPLANG'] : get_locale();
			}

			// Remove the password from meta.
			if ( isset( $signup->meta['password'] ) ) {
				unset( $signup->meta['password'] );
			}

			$data['meta'] = $signup->meta;
		}

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $signup ) );

		/**
		 * Filter the signup response returned from the API.
		 *
		 * @since 6.0.0
		 *
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  Request used to generate the response.
		 * @param BP_Signup         $signup   Signup object.
		 */
		return apply_filters( 'bp_rest_signup_prepare_value', $response, $request, $signup );
	}

	/**
	 * Prepares links for the signup request.
	 *
	 * @param BP_Signup $signup The signup object.
	 * @return array
	 */
	protected function prepare_links( $signup ) {
		$base  = sprintf( '/%1$s/%2$s/', $this->namespace, $this->rest_base );
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . (int) $signup->id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		if ( is_user_logged_in() && bp_is_active( 'xprofile' ) && ! empty( $signup->meta['profile_field_ids'] ) ) {
			$xprofile_field_ids = explode( ',', $signup->meta['profile_field_ids'] );
			$xprofile_field_ids = wp_parse_id_list( $xprofile_field_ids );

			foreach ( $xprofile_field_ids as $field_id ) {
				$xprofile_field_base = sprintf( '%1$s/%2$s/', $this->namespace, buddypress()->profile->id . '/fields' );

				$links[ $field_id ] = array(
					'href'       => rest_url( $xprofile_field_base . $field_id ),
					'embeddable' => true,
				);
			}
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array     $links  The prepared links of the REST response.
		 * @param BP_Signup $signup The signup object.
		 */
		return apply_filters( 'bp_rest_signup_prepare_links', $links, $signup );
	}

	/**
	 * Get signup object.
	 *
	 * @since 6.0.0
	 *
	 * @param int|string $identifier Signup identifier.
	 * @return BP_Signup|false
	 */
	public function get_signup_object( $identifier ) {
		if ( is_numeric( $identifier ) ) {
			$signup_args['include'] = array( intval( $identifier ) );
		} elseif ( is_email( $identifier ) ) {
			$signup_args['usersearch'] = $identifier;
		} else {
			// The activation key is used when activating a signup.
			$signup_args['activation_key'] = $identifier;
		}

		// Get signups.
		$signups = \BP_Signup::get( $signup_args );

		if ( ! empty( $signups['signups'] ) ) {
			return reset( $signups['signups'] );
		}

		return false;
	}

	/**
	 * Check a user password for the REST API.
	 *
	 * @since 6.0.0
	 *
	 * @param string $value The password submitted in the request.
	 * @return string|WP_Error The sanitized password, if valid, otherwise an error.
	 */
	public function check_user_password( $value ) {
		$password = (string) $value;

		if ( empty( $password ) || false !== strpos( $password, '\\' ) ) {
			return new WP_Error(
				'rest_user_invalid_password',
				__( 'Passwords cannot be empty or contain the "\\" character.', 'buddypress' ),
				array( 'status' => 400 )
			);
		}

		return $password;
	}

	/**
	 * Is it possible to signup with a blog?
	 *
	 * @since 6.0.0
	 *
	 * @return bool True if blog signup is allowed. False otherwise.
	 */
	public function is_blog_signup_allowed() {
		$active_signup = get_network_option( get_main_network_id(), 'registration' );

		return ( 'blog' === $active_signup || 'all' === $active_signup );
	}

	/**
	 * Get site's available locales.
	 *
	 * @since 6.0.0
	 *
	 * @return array The list of available locales.
	 */
	public function get_available_languages() {
		/** This filter is documented in wp-signup.php */
		$languages = (array) apply_filters( 'signup_get_available_languages', get_available_languages() );
		return array_intersect_assoc( $languages, get_available_languages() );
	}

	/**
	 * Edit the type of the some properties for the CREATABLE & EDITABLE methods.
	 *
	 * @since 6.0.0
	 *
	 * @param string $method HTTP method of the request. Default is WP_REST_Server::CREATABLE.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = parent::get_endpoint_args_for_item_schema( $method );
		$key  = 'get_item';

		if ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			// The password is required when creating a signup.
			$args['password'] = array(
				'description' => __( 'Password for the new user (never included).', 'buddypress' ),
				'type'        => 'string',
				'context'     => array(), // Password is never displayed.
				'required'    => true,
			);

			if ( bp_is_active( 'xprofile' ) ) {
				$args['signup_field_data'] = array(
					'description'       => __( 'The XProfile field data for the new user.', 'buddypress' ),
					'type'              => 'array',
					'context'           => array( 'edit' ),
					'required'          => true,
					'items'             => array(
						'type'       => 'object',
						'properties' => array(
							'field_id'   => array(
								'description'       => __( 'The XProfile field ID.', 'buddypress' ),
								'required'          => true,
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
								'validate_callback' => 'rest_validate_request_arg',
							),
							'value'      => array(
								'description'       => __( 'The value(s) (comma separated list of values needs to be used in case of multiple values) for the field data.', 'buddypress' ),
								'default'           => '',
								'required'          => false,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => 'rest_validate_request_arg',
							),
							'visibility' => array(
								'description'       => __( 'The visibility for the XProfile field.', 'buddypress' ),
								'required'          => false,
								'default'           => 'public',
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => 'rest_validate_request_arg',
								'enum'              => array_keys( bp_xprofile_get_visibility_levels() ),
							),
						),
					),
					'validate_callback' => static function ( $data ) {
						if ( ! is_array( $data ) || empty( $data ) ) {
							return false;
						}

						return $data;
					},
				);
			}

			/**
			 * We do not need the meta for the create item method
			 * as we are building it inside this method.
			 */
			unset( $args['meta'] );
		} elseif ( WP_REST_Server::EDITABLE === $method ) {
			$key = 'update_item';
		} elseif ( WP_REST_Server::DELETABLE === $method ) {
			$key = 'delete_item';
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @since 6.0.0
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 */
		return apply_filters( "bp_rest_signup_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the signup schema, conforming to JSON Schema.
	 *
	 * @since 6.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( is_null( $this->schema ) ) {
			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'bp_signup',
				'type'       => 'object',
				'properties' => array(
					'id'             => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'A unique numeric ID for the signup.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'integer',
					),
					'user_login'     => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The username of the user the signup is for.', 'buddypress' ),
						'required'    => true,
						'type'        => 'string',
					),
					'user_email'     => array(
						'context'     => array( 'edit' ),
						'description' => __( 'The email for the user the signup is for.', 'buddypress' ),
						'type'        => 'string',
						'required'    => true,
					),
					'activation_key' => array(
						'context'     => array(), // The activation key is sent to the user via email.
						'description' => __( 'Activation key of the signup.', 'buddypress' ),
						'type'        => 'string',
						'readonly'    => true,
					),
					'registered'     => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The registered date for the user, in the site\'s timezone.', 'buddypress' ),
						'type'        => array( 'string', 'null' ),
						'readonly'    => true,
						'format'      => 'date-time',
					),
					'registered_gmt' => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The registered date for the user, as GMT.', 'buddypress' ),
						'type'        => array( 'string', 'null' ),
						'readonly'    => true,
						'format'      => 'date-time',
					),
					'date_sent'      => array(
						'context'     => array( 'edit' ),
						'description' => __( 'The date the activation email was sent to the user, in the site\'s timezone.', 'buddypress' ),
						'type'        => array( 'string', 'null' ),
						'readonly'    => true,
						'format'      => 'date-time',
					),
					'date_sent_gmt'  => array(
						'context'     => array( 'edit' ),
						'description' => __( 'The date the activation email was sent to the user, as GMT.', 'buddypress' ),
						'type'        => array( 'string', 'null' ),
						'readonly'    => true,
						'format'      => 'date-time',
					),
					'count_sent'     => array(
						'description' => __( 'The number of times the activation email was sent to the user.', 'buddypress' ),
						'type'        => 'integer',
						'context'     => array( 'edit' ),
						'readonly'    => true,
					),
					'meta'           => array(
						'context'     => array( 'edit' ),
						'description' => __( 'The signup meta information', 'buddypress' ),
						'type'        => array( 'object', 'null' ),
					),
				),
			);

			// This will be fully removed in V2.
			if ( bp_is_active( 'xprofile' ) ) {
				$schema['properties']['user_name'] = array(
					'context'     => array(),
					'description' => __( 'The new user\'s full name. (Deprecated)', 'buddypress' ),
					'type'        => 'string',
					'readonly'    => true,
				);
			}

			if ( is_multisite() && $this->is_blog_signup_allowed() ) {
				$schema['properties']['site_name'] = array(
					'context'     => array( 'edit' ),
					'description' => __( 'Unique site name (slug) of the new user\'s child site.', 'buddypress' ),
					'type'        => 'string',
					'default'     => '',
				);

				$schema['properties']['site_title'] = array(
					'context'     => array( 'edit' ),
					'description' => __( 'Title of the new user\'s child site.', 'buddypress' ),
					'type'        => 'string',
					'default'     => '',
				);

				$schema['properties']['site_public'] = array(
					'context'     => array( 'edit' ),
					'description' => __( 'Search engine visibility of the new user\'s site.', 'buddypress' ),
					'type'        => 'boolean',
					'default'     => true,
				);

				$schema['properties']['site_language'] = array(
					'context'     => array( 'edit' ),
					'description' => __( 'Language to use for the new user\'s site.', 'buddypress' ),
					'type'        => 'string',
					'default'     => get_locale(),
					'enum'        => array_merge( array( get_locale() ), $this->get_available_languages() ),
				);
			}

			// Cache current schema here.
			$this->schema = $schema;
		}

		/**
		 * Filters the signup schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_signup_schema', $this->add_additional_fields_schema( $this->schema ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @since 6.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		unset( $params['page'], $params['per_page'], $params['search'] );

		$params['number'] = array(
			'description'       => __( 'Total number of signups to return.', 'buddypress' ),
			'default'           => 10,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['offset'] = array(
			'description'       => __( 'Offset the result set by a specific number of items.', 'buddypress' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
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

		$params['orderby'] = array(
			'description'       => __( 'Order by a specific parameter (default: signup_id).', 'buddypress' ),
			'default'           => 'signup_id',
			'type'              => 'string',
			'enum'              => array( 'signup_id', 'login', 'email', 'registered', 'activated' ),
			'sanitize_callback' => 'sanitize_key',
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

		$params['user_login'] = array(
			'description'       => __( 'Specific user login to return.', 'buddypress' ),
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
		return apply_filters( 'bp_rest_signup_collection_params', $params );
	}
}
