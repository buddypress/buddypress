<?php
/**
 * Blogs Controller Tests.
 *
 * @group blogs
 */
class BP_Test_REST_Blogs_Controller extends WP_Test_REST_Controller_Testcase {
	protected $endpoint;
	protected $bp;
	protected $endpoint_url;
	protected $admin;
	protected $server;

	public function set_up() {
		parent::set_up();

		$this->endpoint     = new BP_REST_Blogs_Controller();
		$this->bp           = new BP_UnitTestCase();
		$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/' . buddypress()->blogs->id;
		$this->admin        = static::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);

		if ( ! $this->server ) {
			$this->server = rest_get_server();
		}
	}

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

		$this->bp::set_current_user( $this->admin );

		self::factory()->blog->create_many( 2 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$blogs   = $response->get_data();
		$headers = $response->get_headers();

		$this->assertEquals( 3, $headers['X-WP-Total'] );
		$this->assertEquals( 1, $headers['X-WP-TotalPages'] );
		$this->assertTrue( count( $blogs ) === 3 );
		$this->assertNotEmpty( $blogs[0] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$this->skipWithoutMultisite();

		$this->bp::set_current_user( $this->admin );

		$blog_id = self::factory()->blog->create(
			array( 'title' => 'The Foo Bar Blog' )
		);

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $blog_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$blogs = $response->get_data();

		$this->assertNotEmpty( $blogs );
		$this->assertSame( $blogs[0]['id'], $blog_id );
		$this->assertSame( $blogs[0]['name'], 'The Foo Bar Blog' );
		$this->assertSame( $blogs[0]['user_id'], $this->admin );
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

		$this->bp::set_current_user( $this->admin );

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

		$this->assertEquals( 200, $response->get_status() );

		$data = $this->server->response_to_data( $response, true )[0];

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

		$this->bp::set_current_user( $this->admin );

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

		$this->assertEquals( 200, $response->get_status() );

		$data = $this->server->response_to_data( $response, true )[0];

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

		$this->bp::set_current_user( $this->admin );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$request->set_body( wp_json_encode( $this->set_blog_data() ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$blogs = $response->get_data();

		$this->assertNotEmpty( $blogs );
		$this->assertSame( $blogs[0]['name'], 'Blog Name' );

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

		$this->bp::set_current_user( $this->admin );

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

		$this->bp::set_current_user( $this->admin );

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
		$this->markTestSkipped();
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$this->skipWithoutMultisite();
		$this->markTestSkipped();
	}

	/**
	 * @group get_item
	 */
	public function test_prepare_item() {
		$this->skipWithoutMultisite();
		$this->markTestSkipped();
	}

	protected function set_blog_data( $args = array() ) {
		return wp_parse_args(
			$args,
			array(
				'name'    => 'Cool Blog',
				'title'   => 'Blog Name',
				'user_id' => $this->admin,
				'data'    => array(
					'public' => 1,
				),
			)
		);
	}

	/**
	 * @group additional_fields
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
		$this->bp::set_current_user( $u );

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

		$this->assertEquals( 11, count( $properties ) );
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

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}
}
