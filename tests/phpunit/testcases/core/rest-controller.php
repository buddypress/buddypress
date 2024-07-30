<?php
/**
 * Components Controller Tests.
 *
 * @group components
 */
class BP_Test_REST_Components_Controller extends WP_Test_REST_Controller_Testcase {
	protected $endpoint;
	protected $bp;
	protected $endpoint_url;
	protected $user;
	protected $server;

	public function set_up() {
		parent::set_up();

		$this->endpoint     = new BP_REST_Components_Controller();
		$this->bp           = new BP_UnitTestCase();
		$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/components';
		$this->user         = static::factory()->user->create(
			array(
				'role'       => 'administrator',
				'user_email' => 'admin@example.com',
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
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->assertTrue( 10 === count( $all_data ) );

		$component = $this->endpoint->get_component_info( $all_data[0]['name'] );
		$this->check_component_data( $component, $all_data[0] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_paginated() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'per_page' => 5,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$headers = $response->get_headers();
		$this->assertEquals( 10, $headers['X-WP-Total'] );
		$this->assertEquals( 2, $headers['X-WP-TotalPages'] );

		$all_data = $response->get_data();

		$this->assertNotEmpty( $all_data );

		$this->assertCount( 10, $all_data );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_invalid_status() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_query_params(
			array(
				'status' => 'another',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_user_is_not_logged_in() {
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, 401 );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_without_permission() {
		$u = static::factory()->user->create();

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, 403 );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_active_permission() {
		$u = static::factory()->user->create(
			array(
				'role' => 'author',
			)
		);

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'status', 'active' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$all_features = wp_list_pluck( $all_data, 'features' );
		$this->assertNotEmpty( array_filter( $all_features ) );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_active_component_features() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$members_info = wp_list_filter( $all_data, array( 'name' => 'members' ) );
		$members_info = reset( $members_info );

		$this->assertTrue( $members_info['features']['avatar'] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_inactive_component_features() {
		$this->bp::set_current_user( $this->user );

		add_filter( 'bp_is_messages_star_active', '__return_false' );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		remove_filter( 'bp_is_messages_star_active', '__return_false' );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$messages_info = wp_list_filter( $all_data, array( 'name' => 'messages' ) );
		$messages_info = reset( $messages_info );

		$this->assertFalse( $messages_info['features']['star'] );
	}

	public function deactivate_activity_component( $retval, $component ) {
		if ( 'activity' === $component ) {
			$retval = false;
		}

		return $retval;
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_inactive_component() {
		$this->bp::set_current_user( $this->user );

		add_filter( 'bp_is_active', array( $this, 'deactivate_activity_component' ), 10, 2 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		remove_filter( 'bp_is_active', array( $this, 'deactivate_activity_component' ), 10, 2 );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$components_info = wp_list_filter(
			$all_data,
			array(
				'name'  => 'messages',
				'title' => 'Activity Streams',
			),
			'or'
		);
		$features        = wp_list_pluck( $components_info, 'features', 'name' );

		$this->assertTrue( $features['messages']['star'] );
		$this->assertEmpty( $features['activity'] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$this->markTestSkipped();
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
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url );
		$request->set_query_params(
			array(
				'name'   => 'blogs',
				'action' => 'deactivate',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->assertTrue( 'inactive' === $all_data['status'] );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_nonexistent_component() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url );
		$request->set_query_params(
			array(
				'name'   => 'blogssss',
				'action' => 'deactivate',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_component_nonexistent', $response, 404 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_empty_action() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url );
		$request->set_query_params(
			array(
				'name'   => 'blogs',
				'action' => '',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_invalid_action() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url );
		$request->set_query_params(
			array(
				'name'   => 'blogs',
				'action' => 'update',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_is_not_logged_in() {
		$request = new WP_REST_Request( 'PUT', $this->endpoint_url );
		$request->set_query_params(
			array(
				'name'   => 'core',
				'action' => 'activate',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, 401 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_without_permission() {
		$u = static::factory()->user->create();

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url );
		$request->set_query_params(
			array(
				'name'   => 'core',
				'action' => 'activate',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, 403 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$this->markTestSkipped();
	}

	public function test_prepare_item() {
		$this->markTestSkipped();
	}

	protected function check_component_data( $component, $data ) {
		$this->assertEquals( $component['name'], $data['name'] );
		$this->assertEquals( $component['status'], $data['status'] );
		$this->assertEquals( $component['title'], $data['title'] );
		$this->assertEquals( $component['description'], $data['description'] );
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 6, count( $properties ) );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'title', $properties );
		$this->assertArrayHasKey( 'description', $properties );
	}

	public function test_context_param() {
		// Collection.
		$request  = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}
}
