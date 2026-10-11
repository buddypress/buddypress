<?php
/**
 * Blogs Controller Tests.
 *
 * @group blogs
 */
class BP_Tests_Blogs_REST_Controller extends BP_Test_REST_Controller_Testcase {
	protected $handle     = 'blogs';
	protected $controller = 'BP_Blogs_REST_Controller';

	public function test_register_routes() {
		$routes = $this->server->get_routes();

		// Main.
		$this->assertArrayHasKey( $this->endpoint_url, $routes );
		$this->assertCount( 2, $routes[ $this->endpoint_url ] );

		// Single.
		$this->assertArrayHasKey( $this->endpoint_url . '/(?P<id>[\d]+)', $routes );
		$this->assertCount( 1, $routes[ $this->endpoint_url . '/(?P<id>[\d]+)' ] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$this->skipWithoutMultisite();

		wp_set_current_user( $this->user );

		self::factory()->blog->create_many( 2 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$blogs   = $response->get_data();
		$headers = $response->get_headers();

		$this->assertSame( 3, $headers['X-WP-Total'] );
		$this->assertSame( 1, $headers['X-WP-TotalPages'] );
		$this->assertCount( 3, $blogs );
		$this->assertNotEmpty( $blogs[0] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$this->skipWithoutMultisite();

		wp_set_current_user( $this->user );

		$blog_id = self::factory()->blog->create(
			array( 'title' => 'The Foo Bar Blog' )
		);

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $blog_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$blogs = $response->get_data();

		$this->assertNotEmpty( $blogs );
		$this->assertSame( $blogs['id'], $blog_id );
		$this->assertSame( $blogs['name'], 'The Foo Bar Blog' );
		$this->assertSame( $blogs['user_id'], $this->user );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_support_for_the_community_visibility() {
		$this->skipWithoutMultisite();

		toggle_component_visibility();

		$blog_id = self::factory()->blog->create(
			array( 'title' => 'The Foo Bar Blog' )
		);

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $blog_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_invalid_blog_id() {
		$this->skipWithoutMultisite();

		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_blog_invalid_id', $response, 404 );
	}

	/**
	 * @group get_item
	 */
	public function test_get_embedded_latest_post_from_blog_using_subdirectory() {
		$this->skipWithoutMultisite();

		wp_set_current_user( $this->user );

		$blog_id = self::factory()->blog->create(
			array(
				'title'  => 'The Foo Bar Blog',
				'domain' => 'foo-bar',
				'path'   => '/',
			)
		);

		switch_to_blog( $blog_id );

		static::factory()->post->create();
		$latest_post = static::factory()->post->create();
		$permalink   = get_permalink( $latest_post );
		$title       = get_the_title( $latest_post );

		restore_current_blog();

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $blog_id ) );
		$request->set_query_params( array( '_embed' => 'post' ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $this->server->response_to_data( $response, true );

		$this->assertNotEmpty( $data['_embedded']['post'] );

		$embedded_post = current( $data['_embedded']['post'] );

		$this->assertNotEmpty( $embedded_post );
		$this->assertSame( $blog_id, $data['id'] );
		$this->assertSame( $latest_post, $embedded_post['id'] );
		$this->assertSame( $permalink, $embedded_post['link'] );
		$this->assertSame( $title, $embedded_post['title']['rendered'] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_embedded_latest_post_from_blog_using_subdomain() {
		$this->skipWithoutMultisite();

		wp_set_current_user( $this->user );

		$subdomain = 'cool-site.foo-bar';
		$blog_id   = self::factory()->blog->create(
			array(
				'title'  => 'The Foo Bar Blog',
				'domain' => $subdomain,
				'path'   => '/',
			)
		);

		switch_to_blog( $blog_id );

		static::factory()->post->create();
		$latest_post = static::factory()->post->create();
		$permalink   = get_permalink( $latest_post );
		$title       = get_the_title( $latest_post );

		restore_current_blog();

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $blog_id ) );
		$request->set_query_params( array( '_embed' => 'post' ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $this->server->response_to_data( $response, true );

		$this->assertNotEmpty( $data['_embedded']['post'] );

		$embedded_post = current( $data['_embedded']['post'] );

		$this->assertNotEmpty( $embedded_post );
		$this->assertSame( $blog_id, $data['id'] );
		$this->assertSame( $subdomain, $data['domain'] );
		$this->assertSame( $latest_post, $embedded_post['id'] );
		$this->assertSame( $permalink, $embedded_post['link'] );
		$this->assertSame( $title, $embedded_post['title']['rendered'] );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		$this->skipWithoutMultisite();

		$settings     = buddypress()->site_options;
		$old_settings = $settings;

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['registration']  = 'blog';
		buddypress()->site_options = $settings;

		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $this->set_blog_data() ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$blogs = $response->get_data();

		$this->assertSame( $blogs['name'], 'Blog Name' );

		buddypress()->site_options = $old_settings;
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_user_not_logged_in() {
		$this->skipWithoutMultisite();

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_blog_data();
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_signup_disabled() {
		$this->skipWithoutMultisite();

		$old_settings = $settings = buddypress()->site_options;

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['registration']  = 'none';
		buddypress()->site_options = $settings;

		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_blog_data();
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_blogs_signup_disabled', $response, 500 );

		buddypress()->site_options = $old_settings;
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_without_required_field() {
		$this->skipWithoutMultisite();

		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_blog_data();

		// Remove a required field.
		unset( $params['name'] );

		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_missing_callback_param', $response, 400 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$this->skipWithoutMultisite();
		$this->markTestSkipped( 'This endpoint does not support updating blogs.' );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$this->skipWithoutMultisite();
		$this->markTestSkipped( 'This endpoint does not support deleting blogs.' );
	}

	/**
	 * @group prepare_item
	 */
	public function test_prepare_item() {
		$this->skipWithoutMultisite();

		global $wp_rest_additional_fields;

		$original_fields = $wp_rest_additional_fields;
		$blog_id         = self::factory()->blog->create( array( 'user_id' => $this->user ) );
		$blog            = (object) array(
			'blog_id'       => (string) $blog_id,
			'admin_user_id' => (string) $this->user,
			'name'          => 'The Foo Bar Blog',
			'domain'        => 'example.org',
			'path'          => '/test-blog/',
			'description'   => 'Blog description.',
			'last_activity' => '2024-01-02 03:04:05',
			'latest_post'   => (object) array( 'ID' => '123' ),
		);

		register_rest_field(
			'bp_blogs',
			'test_edit_field',
			array(
				'get_callback' => static function () {
					return 'Additional value';
				},
				'schema'       => array(
					'type'    => 'string',
					'context' => array( 'edit' ),
				),
			)
		);

		try {
			foreach ( array( null, 'view', 'edit', 'embed' ) as $context ) {
				$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $blog_id ) );
				if ( null !== $context ) {
					$request->set_param( 'context', $context );
				}

				$response = $this->endpoint->prepare_item_for_response( $blog, $request );
				$expected = array(
					'id'                => $blog_id,
					'user_id'           => $this->user,
					'name'              => 'The Foo Bar Blog',
					'domain'            => 'example.org',
					'path'              => '/test-blog/',
					'permalink'         => set_url_scheme( 'http://example.org/test-blog/' ),
					'last_activity'     => mysql_to_rfc3339( get_date_from_gmt( $blog->last_activity ) ),
					'last_activity_gmt' => '2024-01-02T03:04:05',
					'lastest_post_id'   => 123,
					'description'       => array(
						'raw'      => 'Blog description.',
						'rendered' => apply_filters( 'bp_get_blog_description', $blog->description ),
					),
					'avatar_urls'       => array(
						'thumb' => bp_get_blog_avatar(
							array(
								'blog_id' => $blog_id,
								'type'    => 'thumb',
								'html'    => false,
							)
						),
						'full'  => bp_get_blog_avatar(
							array(
								'blog_id' => $blog_id,
								'type'    => 'full',
								'html'    => false,
							)
						),
					),
				);

				if ( 'edit' === $context ) {
					$expected['test_edit_field'] = 'Additional value';
				} elseif ( 'embed' === $context ) {
					unset( $expected['last_activity_gmt'], $expected['description']['raw'] );
				}

				$this->assertInstanceOf( 'WP_REST_Response', $response );
				$this->assertSame( 200, $response->get_status() );
				$this->assertSame( $expected, $response->get_data() );
				$this->assertSame(
					array(
						'self'       => array(
							array(
								'href'       => rest_url( $this->endpoint_url . '/' . $blog_id ),
								'attributes' => array(),
							),
						),
						'collection' => array(
							array(
								'href'       => rest_url( $this->endpoint_url . '/' ),
								'attributes' => array(),
							),
						),
						'user'       => array(
							array(
								'href'       => bp_rest_get_object_url( $this->user, 'members' ),
								'attributes' => array( 'embeddable' => true ),
							),
						),
						'post'       => array(
							array(
								'href'       => get_rest_url( $blog_id, 'wp/v2/posts' ) . '/123',
								'attributes' => array( 'embeddable' => true ),
							),
						),
					),
					$response->get_links()
				);
			}

			$blog->latest_post = null;
			$request->set_param( 'context', 'view' );
			$response = $this->endpoint->prepare_item_for_response( $blog, $request );

			$this->assertSame( 0, $response->get_data()['lastest_post_id'] );
			$this->assertArrayNotHasKey( 'post', $response->get_links() );
		} finally {
			$wp_rest_additional_fields = $original_fields;
		}
	}

	protected function set_blog_data( $args = array() ) {
		return wp_parse_args(
			$args,
			array(
				'name'    => 'Cool Blog',
				'title'   => 'Blog Name',
				'user_id' => $this->user,
				'data'    => array(
					'public' => 1,
				),
			)
		);
	}

	/**
	 * @group additional_fields
	 *
	 * @param array  $data      Prepared blog data.
	 * @param string $attribute Additional field name.
	 */
	public function get_additional_field( $data, $attribute ) {
		return bp_blogs_get_blogmeta( $data['id'], '_' . $attribute );
	}

	/**
	 * @group additional_fields
	 */
	public function test_get_additional_fields() {
		$this->skipWithoutMultisite();

		$registered_fields = $GLOBALS['wp_rest_additional_fields'];

		bp_rest_register_field(
			'blogs',
			'foo_field',
			array(
				'get_callback' => array( $this, 'get_additional_field' ),
				'schema'       => array(
					'description' => 'Blogs single item Meta Field',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		$u = $this->bp::factory()->user->create();
		wp_set_current_user( $u );

		$blog_id = self::factory()->blog->create(
			array(
				'title'   => 'The Foo Bar Blog',
				'user_id' => $u,
			)
		);

		bp_blogs_record_existing_blogs();
		update_blog_option( $blog_id, 'blog_public', '1' );

		$expected = 'bar_value';

		bp_blogs_update_blogmeta( $blog_id, '_foo_field', $expected );

		// GET
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'include', array( $blog_id ) );
		$response = $this->server->dispatch( $request );

		$get_data = $response->get_data();

		$this->assertTrue( $expected === $get_data[0]['foo_field'] );

		$GLOBALS['wp_rest_additional_fields'] = $registered_fields;
	}

	public function test_get_item_schema() {
		$this->skipWithoutMultisite();

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertCount( 11, $properties );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'user_id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'path', $properties );
		$this->assertArrayHasKey( 'domain', $properties );
		$this->assertArrayHasKey( 'permalink', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'last_activity', $properties );
		$this->assertArrayHasKey( 'last_activity_gmt', $properties );
		$this->assertArrayHasKey( 'lastest_post_id', $properties );
	}

	public function test_context_param() {
		$this->skipWithoutMultisite();

		$request  = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertSame( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}
}
