<?php
/**
 * Blog Avatar Controller Tests.
 *
 * @group blogs
 * @group blog-avatar
 */
class BP_Test_REST_Attachments_Blog_Avatar_Endpoint extends BP_Test_REST_Controller_Testcase {
	protected $controller = 'BP_REST_Attachments_Blog_Avatar_Endpoint';
	protected $handle     = 'blogs';

	public function test_register_routes() {
		$routes   = $this->server->get_routes();
		$endpoint = $this->endpoint_url . '/(?P<id>[\d]+)/avatar';

		// Single.
		$this->assertArrayHasKey( $endpoint, $routes );
		$this->assertCount( 1, $routes[ $endpoint ] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$this->markTestSkipped();
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$this->skipWithoutMultisite();

		$u        = $this->bp::factory()->user->create();
		$expected = array(
			'full'  => get_avatar_url( $u, array( 'size' => 150 ) ),
			'thumb' => get_avatar_url( $u, array( 'size' => 50 ) ),
		);

		$this->bp::set_current_user( $u );

		$blog_id = self::factory()->blog->create();

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', $blog_id ) );
		$request->set_param( 'context', 'view' );

		$response = $this->server->dispatch( $request );
		$all_data = $response->get_data();

		$this->assertSame( $all_data, $expected );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_support_for_the_community_visibility() {
		$this->skipWithoutMultisite();

		toggle_component_visibility();

		$blog_id = self::factory()->blog->create();
		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', $blog_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_site_icon() {
		$this->skipWithoutMultisite();

		$expected = array(
			'full'  => bp_blogs_default_avatar(
				'',
				array(
					'object' => 'blog',
					'type'   => 'full',
				)
			),
			'thumb' => bp_blogs_default_avatar(
				'',
				array(
					'object' => 'blog',
					'type'   => 'thumb',
				)
			),
		);

		$u = $this->bp::factory()->user->create();

		$this->bp::set_current_user( $u );

		$blog_id = self::factory()->blog->create();

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', $blog_id ) );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'no_user_gravatar', true );

		$response = $this->server->dispatch( $request );
		$all_data = $response->get_data();

		$this->assertSame( $all_data, $expected );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_no_grav() {
		$this->skipWithoutMultisite();

		$u = $this->bp::factory()->user->create();

		$this->bp::set_current_user( $u );

		$blog_id  = self::factory()->blog->create();
		$expected = array(
			'full'  => bp_get_blog_avatar(
				array(
					'blog_id' => $blog_id,
					'html'    => false,
					'type'    => 'full',
				)
			),
			'thumb' => bp_get_blog_avatar(
				array(
					'blog_id' => $blog_id,
					'html'    => false,
					'type'    => 'thumb',
				)
			),
		);

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', $blog_id ) );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'no_user_gravatar', true );

		$response = $this->server->dispatch( $request );
		$all_data = $response->get_data();

		$this->assertSame( $all_data, $expected );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_invalid_user_id() {
		$this->skipWithoutMultisite();

		$current_user = get_current_user_id();

		$u = $this->bp::factory()->user->create();

		$this->bp::set_current_user( $u );

		$blog_id = self::factory()->blog->create( array( 'meta' => array( 'public' => 1 ) ) );

		$this->bp::set_current_user( $current_user );

		// Remove admins.
		add_filter( 'bp_blogs_get_blogs', array( $this, 'filter_admin_user_id' ) );

		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', $blog_id ) );
		$response = $this->server->dispatch( $request );

		remove_filter( 'bp_blogs_get_blogs', array( $this, 'filter_admin_user_id' ) );

		$this->assertErrorResponse( 'bp_rest_blog_avatar_get_item_user_failed', $response, 500 );
	}

	public function filter_admin_user_id( $blog_results ) {
		unset( $blog_results['blogs'][0]->admin_user_id );

		return $blog_results;
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_invalid_blog_id() {
		$this->skipWithoutMultisite();

		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_blog_invalid_id', $response, 404 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		$this->markTestSkipped();
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$this->markTestSkipped();
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$this->markTestSkipped();
	}

	/**
	 * @group prepare_item
	 */
	public function test_prepare_item() {
		$this->markTestSkipped();
	}

	public function test_get_item_schema() {
		$this->skipWithoutMultisite();

		$blog_id = self::factory()->blog->create();

		// Single.
		$request    = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d/avatar', $blog_id ) );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 2, count( $properties ) );
		$this->assertArrayHasKey( 'full', $properties );
		$this->assertArrayHasKey( 'thumb', $properties );
	}

	public function test_context_param() {
		$this->skipWithoutMultisite();

		$blog_id = self::factory()->blog->create();

		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d/avatar', $blog_id ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertNotEmpty( $data );
	}
}
