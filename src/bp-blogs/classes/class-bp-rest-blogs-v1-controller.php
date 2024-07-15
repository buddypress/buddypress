<?php
/**
 * BP_REST_Blogs_V1_Controller class
 *
 * @package BuddyPress
 * @since 6.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Blogs endpoints.
 *
 * Use /blogs/
 * Use /blogs/{id}
 *
 * @since 6.0.0
 */
class BP_REST_Blogs_V1_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 6.0.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->blogs->id;
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
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the Blog.', 'buddypress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve Blogs.
	 *
	 * @since 6.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$args = array(
			'type'             => $request->get_param( 'type' ),
			'include_blog_ids' => $request->get_param( 'include' ),
			'user_id'          => $request->get_param( 'user_id' ),
			'search_terms'     => $request->get_param( 'search' ),
			'page'             => $request->get_param( 'page' ),
			'per_page'         => $request->get_param( 'per_page' ),
		);

		/**
		 * Filter the query arguments for the request.
		 *
		 * @since 6.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$args = apply_filters( 'bp_rest_blogs_get_items_query_args', $args, $request );

		// false is the default value for some args.
		foreach ( $args as $key => $value ) {
			if ( empty( $value ) ) {
				$args[ $key ] = false;
			}
		}

		// Check if user is valid.
		if ( 0 !== absint( $args['user_id'] ) ) {
			$user = get_user_by( 'id', absint( $args['user_id'] ) );
			if ( ! $user instanceof WP_User ) {
				return new WP_Error(
					'bp_rest_blogs_get_items_user_failed',
					__( 'There was a problem confirming if user ID provided is a valid one.', 'buddypress' ),
					array(
						'status' => 404,
					)
				);
			}
		}

		// Actually, query it.
		$blogs  = bp_blogs_get_blogs( $args );
		$retval = array();
		foreach ( (array) $blogs['blogs'] as $blog ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $blog, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $blogs['total'], $args['per_page'] );

		/**
		 * Fires after blogs are fetched via the REST API.
		 *
		 * @since 6.0.0
		 *
		 * @param array            $blogs     Fetched blogs.
		 * @param WP_REST_Response $response  The response data.
		 * @param WP_REST_Request  $request   The request sent to the API.
		 */
		do_action( 'bp_rest_blogs_get_items', $blogs, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to blog items.
	 *
	 * @since 6.0.0
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

		if ( bp_current_user_can( 'bp_view', array( 'bp_component' => 'blogs' ) ) ) {
			$retval = true;
		}

		/**
		 * Filter the blogs `get_items` permissions check.
		 *
		 * @since 6.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_blogs_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a blog.
	 *
	 * @since 6.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$blog = $this->get_blog_object( $request->get_param( 'id' ) );

		if ( empty( $blog->blog_id ) || empty( $blog->admin_user_id ) ) {
			return new WP_Error(
				'bp_rest_blog_invalid_id',
				__( 'Invalid blog ID.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $blog, $request )
			),
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a blog is fetched via the REST API.
		 *
		 * @since 6.0.0
		 *
		 * @param stdClass         $blog     Fetched blog.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_blogs_get_item', $blog, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific blog.
	 *
	 * @since 6.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
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

		if ( bp_current_user_can( 'bp_view', array( 'bp_component' => 'blogs' ) ) ) {
			$retval = true;
		}

		/**
		 * Filter the blog `get_item` permissions check.
		 *
		 * @since 6.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_blogs_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a new blog.
	 *
	 * @since 7.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$request->set_param( 'context', 'edit' );

		// Get WP_User object.
		$user = bp_rest_get_user( $request->get_param( 'user_id' ) );

		// Validate blog signup.
		$blog_meta = wpmu_validate_blog_signup(
			$request->get_param( 'name' ),
			$request->get_param( 'title' ),
			$user
		);

		// Check if validation failed.
		if ( is_wp_error( $blog_meta['errors'] ) && ! empty( $blog_meta['errors']->errors ) ) {
			return new WP_Error(
				'bp_rest_blog_validation_failed',
				$blog_meta['errors']->get_error_message(),
				array(
					'status' => 500,
				)
			);
		}

		$site_id = $request->get_param( 'site_id' );
		$meta    = $request->get_param( 'meta' );

		// Assign blog meta.
		if ( empty( $meta['public'] ) ) {
			$meta['public'] = 1;
		}

		if ( empty( $meta['lang_id'] ) ) {
			$meta['lang_id'] = 1;
		}

		/**
		 * Filter the meta arguments for the new Blog.
		 *
		 * @since 7.0.0
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		$meta = apply_filters( 'bp_rest_blogs_create_item_meta', $meta, $request );

		// Create blog.
		$blog_id = wpmu_create_blog(
			$blog_meta['domain'],
			$blog_meta['path'],
			$blog_meta['blog_title'],
			$user->ID,
			$meta,
			$site_id
		);

		// If something went wrong, bail it.
		if ( is_wp_error( $blog_id ) ) {
			return new WP_Error(
				'bp_rest_blogs_create_error',
				__( 'There was a problem creating blog.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		$blog = $this->get_blog_object( $blog_id );

		if ( empty( $blog->blog_id ) || empty( $blog->admin_user_id ) ) {
			return new WP_Error(
				'bp_rest_blog_invalid_id',
				__( 'Invalid blog ID.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $blog, $request )
			),
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a blog is created via the REST API.
		 *
		 * @since 7.0.0
		 *
		 * @param stdClass         $blog     Created blog.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_blogs_create_item', $blog, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a blog.
	 *
	 * @since 7.0.0
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

		if ( is_user_logged_in() ) {
			if ( true === bp_blog_signup_enabled() ) {
				$retval = true;
			} else {
				$retval = new WP_Error(
					'bp_rest_blogs_signup_disabled',
					__( 'Sorry, blog creation is disabled.', 'buddypress' ),
					array(
						'status' => 500,
					)
				);
			}
		}

		/**
		 * Filter the blogs `create_item` permissions check.
		 *
		 * @since 7.0.0
		 *
		 * @param true|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_blogs_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares blogs data for return as an object.
	 *
	 * @since 6.0.0
	 *
	 * @param stdClass        $blog    Blog object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $blog, $request ) {
		$data = array(
			'id'                => absint( $blog->blog_id ),
			'user_id'           => absint( $blog->admin_user_id ),
			'name'              => apply_filters( 'bp_get_blog_name', $blog->name ),
			'domain'            => (string) $blog->domain,
			'path'              => (string) $blog->path,
			'permalink'         => $this->get_blog_permalink( $blog ),
			'last_activity'     => bp_rest_prepare_date_response( $blog->last_activity, get_date_from_gmt( $blog->last_activity ) ),
			'last_activity_gmt' => bp_rest_prepare_date_response( $blog->last_activity ),
			'lastest_post_id'   => 0,
			'description'       => array(
				'raw'      => $blog->description,
				'rendered' => apply_filters( 'bp_get_blog_description', $blog->description ),
			),
		);

		if ( ! empty( $blog->latest_post->ID ) ) {
			$data['lastest_post_id'] = absint( $blog->latest_post->ID );
		}

		// Blog Avatars.
		if ( true === buddypress()->avatar->show_avatars ) {
			$data['avatar_urls'] = array(
				'thumb' => bp_get_blog_avatar(
					array(
						'type'    => 'thumb',
						'blog_id' => $blog->blog_id,
						'html'    => false,
					)
				),
				'full'  => bp_get_blog_avatar(
					array(
						'type'    => 'full',
						'blog_id' => $blog->blog_id,
						'html'    => false,
					)
				),
			);
		}

		$context  = ! empty( $request->get_param( 'context' ) ) ? $request->get_param( 'context' ) : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $blog ) );

		/**
		 * Filter a blog returned from the API.
		 *
		 * @since 6.0.0
		 *
		 * @param WP_REST_Response  $response Response generated by the request.
		 * @param WP_REST_Request   $request  Request used to generate the response.
		 * @param stdClass          $blog     The blog object.
		 */
		return apply_filters( 'bp_rest_blogs_prepare_value', $response, $request, $blog );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 6.0.0
	 *
	 * @param stdClass $blog Blog object.
	 * @return array
	 */
	protected function prepare_links( $blog ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . $blog->blog_id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		if ( ! empty( $blog->admin_user_id ) ) {
			$links['user'] = array(
				'href'       => bp_rest_get_object_url( absint( $blog->admin_user_id ), 'members' ),
				'embeddable' => true,
			);
		}

		// Embed the latest blog post.
		if ( ! empty( $blog->latest_post->ID ) ) {
			$links['post'] = array(
				'embeddable' => true,
				'href'       => sprintf(
					'%s/%d',
					get_rest_url( absint( $blog->blog_id ), 'wp/v2/posts' ),
					absint( $blog->latest_post->ID )
				),
			);
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 5.0.0
		 *
		 * @param array    $links The prepared links of the REST response.
		 * @param stdClass $blog  The blog object.
		 */
		return apply_filters( 'bp_rest_blogs_prepare_links', $links, $blog );
	}

	/**
	 * Get blog permalink.
	 *
	 * @param stdClass $blog Blog object.
	 * @return string
	 */
	protected function get_blog_permalink( $blog ) {

		// Bail early.
		if ( empty( $blog->domain ) && empty( $blog->path ) ) {
			return '';
		}

		if ( empty( $blog->domain ) && ! empty( $blog->path ) ) {
			return bp_get_root_url() . $blog->path;
		}

		$protocol  = is_ssl() ? 'https://' : 'http://';
		$permalink = $protocol . $blog->domain . $blog->path;

		return apply_filters( 'bp_get_blog_permalink', $permalink );
	}

	/**
	 * Get a blog object from a blog_id.
	 *
	 * @since 6.0.0
	 *
	 * @param int $blog_id Blog ID.
	 * @return stdClass|int
	 */
	public function get_blog_object( $blog_id ) {
		$blogs = current(
			bp_blogs_get_blogs(
				array(
					'include_blog_ids' => array( $blog_id ),
				)
			)
		);

		if ( ! empty( $blogs[0] ) ) {
			return $blogs[0];
		}

		return 0;
	}

	/**
	 * Edit the type of the some properties for the CREATABLE method.
	 *
	 * @since 7.0.0
	 *
	 * @param string $method Optional. HTTP method of the request.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = parent::get_endpoint_args_for_item_schema( $method );
		$key  = 'get_item';

		if ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			unset( $args['last_activity'] );

			$args['name'] = array(
				'required'          => true,
				'description'       => __( 'The new site\'s name (used for the site URL).', 'buddypress' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['title'] = array(
				'required'          => true,
				'description'       => __( 'The new site\'s title.', 'buddypress' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['site_id'] = array(
				'required'          => false,
				'default'           => get_current_network_id(),
				'description'       => __( 'The new site\'s network ID. (Only relevant on multi-network installations)', 'buddypress' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['user_id'] = array(
				'required'          => false,
				'default'           => bp_loggedin_user_id(),
				'description'       => __( 'The user ID of the new site\'s admin.', 'buddypress' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['meta'] = array(
				'required'          => false,
				'description'       => __( 'Set initial Blog options.', 'buddypress' ),
				'default'           => array(),
				'type'              => 'array',
				'items'             => array( 'type' => 'string' ),
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @since 7.0.0
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 */
		return apply_filters( "bp_rest_blogs_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the blogs schema, conforming to JSON Schema.
	 *
	 * @since 6.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( is_null( $this->schema ) ) {
			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'bp_blogs',
				'type'       => 'object',
				'properties' => array(
					'id'                => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'A unique numeric ID for the blog.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'integer',
					),
					'user_id'           => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'A unique numeric ID for the blog admin.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'integer',
					),
					'name'              => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The name of the blog.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'string',
						'arg_options' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
					'permalink'         => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The permalink of the blog.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'string',
						'format'      => 'uri',
					),
					'description'       => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The description of the blog.', 'buddypress' ),
						'type'        => 'object',
						'arg_options' => array(
							'sanitize_callback' => null,
							'validate_callback' => null,
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Content for the description of the blog, as it exists in the database.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'rendered' => array(
								'description' => __( 'HTML content for the description of the blog, transformed for display.', 'buddypress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
						),
					),
					'path'              => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The path of the blog.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'string',
					),
					'domain'            => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The domain of the blog.', 'buddypress' ),
						'readonly'    => true,
						'type'        => 'string',
					),
					'last_activity'     => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The date of the last activity from the blog, in the site\'s timezone.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'last_activity_gmt' => array(
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'The date of the last activity from the blog, as GMT.', 'buddypress' ),
						'readonly'    => true,
						'type'        => array( 'string', 'null' ),
						'format'      => 'date-time',
					),
					'lastest_post_id'   => array(
						'context'     => array( 'view', 'edit', 'embed' ),
						'description' => __( 'The latest post ID from the blog', 'buddypress' ),
						'type'        => 'integer',
						'readonly'    => true,
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
					'description' => __( 'Avatar URLs for the blog.', 'buddypress' ),
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
		 * Filter the blogs schema.
		 *
		 * @since 6.0.0
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_blogs_schema', $this->add_additional_fields_schema( $this->schema ) );
	}

	/**
	 * Get the query params for blogs collections.
	 *
	 * @since 6.0.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['user_id'] = array(
			'description'       => __( 'ID of the user whose blogs user can post to.', 'buddypress' ),
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

		$params['type'] = array(
			'description'       => __( 'Limit result set to items with a specific type.', 'buddypress' ),
			'default'           => 'active',
			'type'              => 'string',
			'enum'              => array( 'active', 'alphabetical', 'newest', 'random' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_blogs_collection_params', $params );
	}
}
