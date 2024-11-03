<?php
/**
 * Group Membership Request Controller Tests.
 *
 * @group group-membership-request
 * @group groups
 */
class BP_Tests_Group_Membership_Request_REST_Controller extends BP_Test_REST_Controller_Testcase {
	protected $group_id;
	protected $g1admin;
	protected $g1;
	protected $controller = 'BP_Groups_Membership_Request_REST_Controller';
	protected $handle     = 'groups/membership-requests';

	public function set_up() {
		parent::set_up();

		$this->group_id = $this->bp::factory()->group->create(
			array(
				'name'        => 'Group Test',
				'description' => 'Group Description',
				'creator_id'  => $this->user,
				'status'      => 'private',
			)
		);

		// Create a group with a group admin that is not a site admin.
		$this->g1admin = static::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'user_email' => 'sub@example.com',
			)
		);

		$this->g1 = $this->bp::factory()->group->create(
			array(
				'name'        => 'Group Test 1',
				'description' => 'Group Description 1',
				'status'      => 'private',
				'creator_id'  => $this->g1admin,
			)
		);
	}

	public function test_register_routes() {
		$routes = $this->server->get_routes();

		// GET and CREATE.
		$this->assertArrayHasKey( $this->endpoint_url, $routes );
		$this->assertCount( 2, $routes[ $this->endpoint_url ] );

		// PUT, etc.
		$put_endpoint = $this->endpoint_url . '/(?P<request_id>[\d]+)';

		$this->assertArrayHasKey( $put_endpoint, $routes );
		$this->assertCount( 3, $routes[ $put_endpoint ] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$u  = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);
		groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u2,
			)
		);
		groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u3,
			)
		);

		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'group_id' => $this->group_id,
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertTrue( 3 === count( $all_data ) );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$u  = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);
		groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u2,
			)
		);
		groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u3,
			)
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'group_id' => $this->group_id,
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_as_group_admin() {
		$u  = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		groups_send_membership_request(
			array(
				'group_id' => $this->g1,
				'user_id'  => $u,
			)
		);
		groups_send_membership_request(
			array(
				'group_id' => $this->g1,
				'user_id'  => $u2,
			)
		);

		wp_set_current_user( $this->g1admin );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'group_id' => $this->g1,
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertTrue( 2 === count( $all_data ) );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_as_requestor() {
		$u = static::factory()->user->create();

		groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		wp_set_current_user( $u );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'user_id' => $u,
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertTrue( 1 === count( $all_data ) );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_user_is_not_logged_in() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'group_id' => $this->group_id,
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_user_has_no_access_to_group() {
		$u  = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		wp_set_current_user( $u2 );
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'group_id' => $this->group_id,
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_membership_requests_cannot_get_items', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_user_has_no_access_to_user() {
		$u  = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		wp_set_current_user( $u2 );
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'user_id' => $u,
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_membership_requests_cannot_get_items', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_invalid_group() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'group_id' => REST_TESTS_IMPOSSIBLY_HIGH_NUMBER,
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$u = static::factory()->user->create();

		$request_id = groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $request_id );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$accepted = groups_is_user_member( $u, $this->group_id );
		$this->assertFalse( $accepted );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$u          = static::factory()->user->create();
		$request_id = groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $request_id );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_user_is_not_logged_in() {
		$u = static::factory()->user->create();

		$request_id = groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $request_id );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_invalid_membership_request() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . REST_TESTS_IMPOSSIBLY_HIGH_NUMBER );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_membership_requests_invalid_id', $response, 404 );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_no_access() {
		$u  = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$request_id = groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		wp_set_current_user( $u2 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $request_id );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_membership_requests_cannot_get_item', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		$u = static::factory()->user->create();
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id'  => $u,
				'group_id' => $this->group_id,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertSame( $this->group_id, $data['group_id'] );
		$this->assertSame( $u, $data['user_id'] );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_as_subscriber() {
		$u = static::factory()->user->create();

		wp_set_current_user( $u );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertSame( $this->group_id, $data['group_id'] );
		$this->assertSame( $u, $data['user_id'] );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_user_is_not_logged_in() {
		$u = static::factory()->user->create();

		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id'  => $u,
				'group_id' => $this->group_id,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_invalid_member() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id'  => REST_TESTS_IMPOSSIBLY_HIGH_NUMBER,
				'group_id' => $this->group_id,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_invalid_id', $response, 404 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_invalid_group() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id'  => static::factory()->user->create(),
				'group_id' => REST_TESTS_IMPOSSIBLY_HIGH_NUMBER,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_with_an_already_group_member() {
		$u = static::factory()->user->create();

		$this->bp::add_user_to_group( $u, $this->group_id );
		wp_set_current_user( $u );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id'  => $u,
				'group_id' => $this->group_id,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_membership_requests_cannot_create_item', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_fails_with_pending_request() {
		$u = static::factory()->user->create();

		// Create a membership request.
		groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		wp_set_current_user( $u );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id'  => $u,
				'group_id' => $this->group_id,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_membership_requests_duplicate_request', $response, 500 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$u = static::factory()->user->create();
		wp_set_current_user( $this->user );

		$request_id = groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . $request_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$status = groups_is_user_member( $u, $this->group_id );
		$this->assertTrue( is_int( $status ) && $status > 0 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_as_group_admin() {
		$u = static::factory()->user->create();
		wp_set_current_user( $u );
		$request_id = groups_send_membership_request(
			array(
				'group_id' => $this->g1,
				'user_id'  => $u,
			)
		);

		wp_set_current_user( $this->g1admin );
		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . $request_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$status = groups_is_user_member( $u, $this->g1 );
		$this->assertTrue( is_int( $status ) && $status > 0 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_is_not_logged_in() {
		$u = static::factory()->user->create();

		$request_id = groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . $request_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_has_no_access() {
		$u  = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$request_id = groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		wp_set_current_user( $u2 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . $request_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_request_cannot_update_item', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_invalid_id() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . REST_TESTS_IMPOSSIBLY_HIGH_NUMBER );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_membership_requests_invalid_id', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$u = static::factory()->user->create();
		wp_set_current_user( $this->user );

		$request_id = groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $request_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertTrue( $all_data['deleted'] );
		$this->assertEquals( $request_id, $all_data['previous']['id'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_as_requestor() {
		$u = static::factory()->user->create();
		wp_set_current_user( $u );

		$request_id = groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $request_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertTrue( $all_data['deleted'] );
		$this->assertEquals( $request_id, $all_data['previous']['id'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_as_group_admin() {
		$u = static::factory()->user->create();
		wp_set_current_user( $u );
		$request_id = groups_send_membership_request(
			array(
				'group_id' => $this->g1,
				'user_id'  => $u,
			)
		);

		wp_set_current_user( $this->g1admin );
		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $request_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertTrue( $all_data['deleted'] );
		$this->assertEquals( $request_id, $all_data['previous']['id'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_is_not_logged_in() {
		$u = static::factory()->user->create();

		$request_id = groups_send_membership_request(
			array(
				'group_id' => $this->g1,
				'user_id'  => $u,
			)
		);

		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $request_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_has_no_access() {
		$u  = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$request_id = groups_send_membership_request(
			array(
				'group_id' => $this->group_id,
				'user_id'  => $u,
			)
		);

		wp_set_current_user( $u2 );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $request_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_membership_requests_cannot_delete_item', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_prepare_item() {
		$this->markTestSkipped();
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 7, count( $properties ) );
		$this->assertArrayHasKey( 'user_id', $properties );
		$this->assertArrayHasKey( 'date_modified', $properties );
		$this->assertArrayHasKey( 'date_modified_gmt', $properties );
	}

	public function test_context_param() {
		// Collection.
		$request  = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( array( 'view', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}
}
