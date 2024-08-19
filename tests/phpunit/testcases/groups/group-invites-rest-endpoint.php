<?php
/**
 * Group Invites Controller Tests.
 *
 * @group group-invites
 */
class BP_Test_REST_Group_Invites_Endpoint extends BP_Test_REST_Controller_Testcase {
	protected $group_id;
	protected $g1admin;
	protected $g1;
	protected $controller = 'BP_REST_Group_Invites_Endpoint';
	protected $handle     = 'groups/invites';

	public function set_up() {
		parent::set_up();

		$this->group_id = $this->bp::factory()->group->create(
			array(
				'name'        => 'Group Test',
				'description' => 'Group Description',
				'status'      => 'private',
				'creator_id'  => $this->user,
			)
		);

		// Create a group with a group admin that is not a site admin.
		$this->g1admin = static::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'user_email' => 'sub@example.com',
			)
		);
		$this->g1      = $this->bp::factory()->group->create(
			array(
				'name'        => 'Group Test 1',
				'description' => 'Group Description 1',
				'status'      => 'private',
				'creator_id'  => $this->g1admin,
			)
		);
		groups_update_groupmeta( $this->g1, 'invite_status', 'members' );
	}

	public function test_register_routes() {
		$routes = $this->server->get_routes();

		// GET and CREATE.
		$this->assertArrayHasKey( $this->endpoint_url, $routes );
		$this->assertCount( 2, $routes[ $this->endpoint_url ] );

		// PUT, etc.
		$put_endpoint = $this->endpoint_url . '/(?P<invite_id>[\d]+)';

		$this->assertArrayHasKey( $put_endpoint, $routes );
		$this->assertCount( 3, $routes[ $put_endpoint ] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();
		$u4 = static::factory()->user->create();
		$u5 = static::factory()->user->create();

		$this->populate_group_with_invites( array( $u1, $u2, $u3, $u4 ), $this->group_id );

		// As site admin
		$this->bp::set_current_user( $this->user );
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

		$u_ids = wp_list_pluck( $all_data, 'user_id' );

		// Check results.
		$this->assertEqualSets( array( $u1, $u2, $u3, $u4 ), $u_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();
		$u4 = static::factory()->user->create();

		$this->populate_group_with_invites( array( $u1, $u2, $u3, $u4 ), $this->group_id );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'group_id' => $this->group_id,
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invites_cannot_get_items', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_as_group_admin() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();
		$u4 = static::factory()->user->create();
		$u5 = static::factory()->user->create();

		$this->populate_group_with_invites( array( $u1, $u2, $u3, $u5 ), $this->g1 );
		// Red herring
		$this->populate_group_with_invites( array( $u4 ), $this->group_id );

		$this->bp::set_current_user( $this->g1admin );
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

		$u_ids = wp_list_pluck( $all_data, 'user_id' );

		// Check results.
		$this->assertEqualSets( array( $u1, $u2, $u3, $u5 ), $u_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_as_user() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$inv1 = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->group_id,
				'inviter_id'  => $this->user,
				'send_invite' => 1,
			)
		);
		// Red herring
		$inv2 = groups_invite_user(
			array(
				'user_id'     => $u2,
				'group_id'    => $this->group_id,
				'inviter_id'  => $this->user,
				'send_invite' => 1,
			)
		);
		$inv3 = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->g1,
				'inviter_id'  => $this->user,
				'send_invite' => 1,
			)
		);

		$this->bp::set_current_user( $u1 );
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'user_id' => $u1,
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$inv_ids = wp_list_pluck( $all_data, 'id' );

		// Check results.
		$this->assertEqualSets( array( $inv1, $inv3 ), $inv_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_as_inviter() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();
		$u4 = static::factory()->user->create();

		$this->bp::add_user_to_group( $u4, $this->g1 );

		groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->g1,
				'inviter_id'  => $u4,
				'send_invite' => 1,
			)
		);
		// Red herring
		groups_invite_user(
			array(
				'user_id'     => $u2,
				'group_id'    => $this->g1,
				'inviter_id'  => $this->g1admin,
				'send_invite' => 1,
			)
		);
		groups_invite_user(
			array(
				'user_id'     => $u3,
				'group_id'    => $this->g1,
				'inviter_id'  => $u4,
				'send_invite' => 1,
			)
		);

		$this->bp::set_current_user( $u4 );
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'inviter_id' => $u4,
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$u_ids = wp_list_pluck( $all_data, 'user_id' );

		// Check results.
		$this->assertEqualSets( array( $u1, $u3 ), $u_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_user_not_logged_in() {
		$this->bp::set_current_user( 0 );

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
	public function test_get_items_without_permission() {
		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'group_id' => $this->group_id,
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invites_cannot_get_items', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$u1 = static::factory()->user->create();

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->group_id,
				'inviter_id'  => $this->user,
				'send_invite' => 1,
			)
		);

		$this->bp::set_current_user( $this->user );
		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $invite_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$all_data = $response->get_data();

		$this->assertEquals( $u1, $all_data['user_id'] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$u1 = static::factory()->user->create();

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->group_id,
				'inviter_id'  => $this->user,
				'send_invite' => 1,
			)
		);

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $invite_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_as_user() {
		$u1 = static::factory()->user->create();

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->group_id,
				'inviter_id'  => $this->user,
				'send_invite' => 1,
			)
		);

		$this->bp::set_current_user( $u1 );
		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $invite_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$all_data = $response->get_data();

		$this->assertEquals( $u1, $all_data['user_id'] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_as_inviter() {
		$u1 = static::factory()->user->create();

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->g1,
				'inviter_id'  => $this->g1admin,
				'send_invite' => 1,
			)
		);

		$this->bp::set_current_user( $this->g1admin );
		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $invite_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$all_data = $response->get_data();

		$this->assertEquals( $u1, $all_data['user_id'] );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		$u1 = static::factory()->user->create();

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id'    => $u1,
				'inviter_id' => $this->user,
				'group_id'   => $this->group_id,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->assertEquals( $u1, $all_data['user_id'] );
		$this->assertEquals( $this->user, $all_data['inviter_id'] );
		$this->assertTrue( (bool) $all_data['invite_sent'] );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_as_group_admin() {
		$u1 = static::factory()->user->create();

		$this->bp::set_current_user( $this->g1admin );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id'    => $u1,
				'inviter_id' => $this->g1admin,
				'group_id'   => $this->g1,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->assertEquals( $u1, $all_data['user_id'] );
		$this->assertEquals( $this->g1admin, $all_data['inviter_id'] );
		$this->assertTrue( (bool) $all_data['invite_sent'] );
	}

	/**
	 * @group create_item
	 */
	public function test_inviter_cannot_invite_member_to_group_if_already_member() {
		// $this->user is a creator of the group.
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id'    => $this->g1admin,
				'inviter_id' => $this->user,
				'group_id'   => $this->g1,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invite_cannot_create_item', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_user_not_logged_in() {
		$u = static::factory()->user->create();

		$this->bp::set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id'    => $u,
				'inviter_id' => $this->user,
				'group_id'   => $this->group_id,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_invalid_member_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id'    => REST_TESTS_IMPOSSIBLY_HIGH_NUMBER,
				'inviter_id' => $this->user,
				'group_id'   => $this->group_id,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_member_invalid_id', $response, 404 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_invalid_inviter_id() {
		$u = static::factory()->user->create();

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_query_params(
			array(
				'user_id'    => $u,
				'inviter_id' => REST_TESTS_IMPOSSIBLY_HIGH_NUMBER,
				'group_id'   => $this->group_id,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_member_invalid_id', $response, 404 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_invalid_group_id() {
		$u = static::factory()->user->create();

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_query_params(
			array(
				'user_id'    => $u,
				'inviter_id' => $this->user,
				'group_id'   => REST_TESTS_IMPOSSIBLY_HIGH_NUMBER,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_without_permission() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_query_params(
			array(
				'user_id'    => $u1,
				'inviter_id' => $this->user,
				'group_id'   => $this->group_id,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invite_cannot_create_item', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$u1 = static::factory()->user->create();

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->group_id,
				'inviter_id'  => $this->user,
				'send_invite' => 1,
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . $invite_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->assertEquals( $u1, $all_data['id'] );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_as_invitee() {
		$u1        = static::factory()->user->create();
		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->group_id,
				'inviter_id'  => $this->user,
				'send_invite' => 1,
			)
		);

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . $invite_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->assertEquals( $u1, $all_data['id'] );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request  = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . REST_TESTS_IMPOSSIBLY_HIGH_NUMBER );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invite_invalid_id', $response, 404 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_not_logged_in() {
		$u1 = static::factory()->user->create();

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->group_id,
				'inviter_id'  => $this->user,
				'send_invite' => 1,
			)
		);
		$this->bp::set_current_user( 0 );
		$request  = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . $invite_id );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_without_permission() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->group_id,
				'inviter_id'  => $this->user,
				'send_invite' => 1,
			)
		);

		$this->bp::set_current_user( $u2 );

		$request  = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . $invite_id );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invite_cannot_update_item', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$u1        = static::factory()->user->create();
		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->group_id,
				'inviter_id'  => $this->user,
				'send_invite' => 1,
			)
		);

		// Delete as site admin.
		$this->bp::set_current_user( $this->user );

		$request  = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $invite_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertTrue( $all_data['deleted'] );
		$this->assertEquals( $invite_id, $all_data['previous']['id'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_as_user() {
		$u1 = static::factory()->user->create();

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->g1,
				'inviter_id'  => $this->g1admin,
				'send_invite' => 1,
			)
		);

		$this->bp::set_current_user( $u1 );

		$request  = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $invite_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertTrue( $all_data['deleted'] );
		$this->assertEquals( $invite_id, $all_data['previous']['id'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_as_inviter() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$this->bp::add_user_to_group( $u2, $this->g1 );

		$this->bp::set_current_user( $u2 );

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->g1,
				'inviter_id'  => $u2,
				'send_invite' => 1,
			)
		);

		$request  = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $invite_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertTrue( $all_data['deleted'] );
		$this->assertEquals( $invite_id, $all_data['previous']['id'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_as_group_admin() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$this->bp::add_user_to_group( $u2, $this->g1 );

		$this->bp::set_current_user( $u2 );

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->g1,
				'inviter_id'  => $u2,
				'send_invite' => 1,
			)
		);

		$this->bp::set_current_user( $this->g1admin );

		$request  = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $invite_id );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertTrue( $all_data['deleted'] );
		$this->assertEquals( $invite_id, $all_data['previous']['id'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_not_logged_in() {
		$u1 = static::factory()->user->create();

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->group_id,
				'inviter_id'  => $this->user,
				'send_invite' => 1,
			)
		);
		$this->bp::set_current_user( 0 );
		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $invite_id );
		$request->set_query_params(
			array(
				'user_id'  => $u1,
				'group_id' => $this->group_id,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_without_permission() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $u1,
				'group_id'    => $this->group_id,
				'inviter_id'  => $this->user,
				'send_invite' => 1,
			)
		);
		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $invite_id );
		$request->set_query_params(
			array(
				'user_id'  => $u1,
				'group_id' => $this->group_id,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invite_cannot_delete_item', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_delete_item_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request  = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . REST_TESTS_IMPOSSIBLY_HIGH_NUMBER );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invite_invalid_id', $response, 404 );
	}

	/**
	 * @group get_item
	 */
	public function test_prepare_item() {
		$this->markTestSkipped();
	}

	protected function check_invited_user_data( $user, $data ) {
		$this->assertEquals( $user->ID, $data['user_id'] );
		$this->assertEquals( $user->invite_sent, $data['invite_sent'] );
		$this->assertEquals( $user->inviter_id, $data['inviter_id'] );
	}

	protected function populate_group_with_invites( $users, $group_id ) {
		foreach ( $users as $user_id ) {
			groups_invite_user(
				array(
					'user_id'     => $user_id,
					'group_id'    => $group_id,
					'inviter_id'  => $this->user,
					'send_invite' => 1,
				)
			);
		}
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 9, count( $properties ) );
		$this->assertArrayHasKey( 'user_id', $properties );
		$this->assertArrayHasKey( 'invite_sent', $properties );
		$this->assertArrayHasKey( 'inviter_id', $properties );
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
