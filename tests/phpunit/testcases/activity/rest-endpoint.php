<?php
/**
 * Activity Controller Tests.
 *
 * @group activity
 */
class BP_Test_REST_Activity_Endpoint extends WP_Test_REST_Controller_Testcase {
	protected $endpoint;
	protected $bp;
	protected $endpoint_url;
	protected $activity_id;
	protected $user;
	protected $server;

	public function set_up() {
		parent::set_up();
		$this->endpoint     = new BP_REST_Activity_Endpoint();
		$this->bp           = new BP_UnitTestCase();
		$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/' . buddypress()->activity->id;
		$this->activity_id  = $this->bp::factory()->activity->create();

		$this->user = static::factory()->user->create(
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

		// Single.
		$this->assertArrayHasKey( $this->endpoint_url . '/(?P<id>[\d]+)', $routes );
		$this->assertCount( 3, $routes[ $this->endpoint_url . '/(?P<id>[\d]+)' ] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$this->bp::set_current_user( $this->user );

		$this->bp::factory()->activity->create_many( 3 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$a_ids = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertTrue( count( $a_ids ) === 4 );
		$this->assertContains( $this->activity_id, $a_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$this->bp::factory()->activity->create_many( 3 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_multiple_types() {
		$g1 = $this->bp::factory()->group->create(
			array(
				'status' => 'public',
			)
		);

		$this->bp::factory()->activity->create(
			array(
				'component'     => buddypress()->groups->id,
				'type'          => 'created_group',
				'user_id'       => $this->user,
				'item_id'       => $g1,
				'hide_sitewide' => true,
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'type' => array( 'activity_update', 'created_group' ),
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_type = wp_list_pluck( $response->get_data(), 'type' );

		$this->assertTrue( count( $all_type ) === 2 );
		$this->assertContains( 'created_group', $all_type );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_with_invalid_type() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'type' => array( 'invalid_type' ),
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * @group get_items
	 */
	public function test_get_public_groups_items() {
		$component = buddypress()->groups->id;

		// Current user is $this->user.
		$g1 = $this->bp::factory()->group->create(
			array(
				'status' => 'private',
			)
		);

		$g2 = $this->bp::factory()->group->create(
			array(
				'status' => 'public',
			)
		);

		$a1 = $this->bp::factory()->activity->create(
			array(
				'component'     => $component,
				'type'          => 'created_group',
				'user_id'       => $this->user,
				'item_id'       => $g1,
				'hide_sitewide' => true,
			)
		);

		$a2 = $this->bp::factory()->activity->create(
			array(
				'component' => $component,
				'type'      => 'created_group',
				'user_id'   => $this->user,
				'item_id'   => $g2,
			)
		);

		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'component' => $component,
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$a_ids = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertNotContains( $a1, $a_ids );
		$this->assertContains( $a2, $a_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_from_a_specific_group() {
		$component = buddypress()->groups->id;

		$g1 = $this->bp::factory()->group->create( array( 'status' => 'public' ) );
		$g2 = $this->bp::factory()->group->create( array( 'status' => 'public' ) );

		$a1 = $this->bp::factory()->activity->create(
			array(
				'component' => $component,
				'type'      => 'created_group',
				'user_id'   => $this->user,
				'item_id'   => $g2,
			)
		);

		$a2 = $this->bp::factory()->activity->create(
			array(
				'component' => $component,
				'type'      => 'created_group',
				'user_id'   => $this->user,
				'item_id'   => $g2,
			)
		);

		$a3 = $this->bp::factory()->activity->create(
			array(
				'component'     => $component,
				'type'          => 'created_group',
				'user_id'       => $this->user,
				'item_id'       => $g2,
				'hide_sitewide' => true,
			)
		);

		$a4 = $this->bp::factory()->activity->create(
			array(
				'component' => $component,
				'type'      => 'created_group',
				'user_id'   => $this->user,
				'item_id'   => $g1,
			)
		);

		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array( 'group_id' => $g2 ) );

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$a_ids = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertEqualSets( array( $a1, $a2 ), $a_ids );
		$this->assertNotContains( $a3, $a_ids );
		$this->assertNotContains( $a4, $a_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_private_group_items() {
		$component = buddypress()->groups->id;

		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		// Current user is $u.
		$g1 = $this->bp::factory()->group->create(
			array(
				'status'     => 'private',
				'creator_id' => $u,
			)
		);

		$g2 = $this->bp::factory()->group->create(
			array(
				'status'     => 'public',
				'creator_id' => $this->user,
			)
		);

		$a1 = $this->bp::factory()->activity->create(
			array(
				'component'     => $component,
				'type'          => 'created_group',
				'user_id'       => $u,
				'item_id'       => $g1,
				'hide_sitewide' => true,
			)
		);

		$a2 = $this->bp::factory()->activity->create(
			array(
				'component' => $component,
				'type'      => 'created_group',
				'user_id'   => $this->user,
				'item_id'   => $g2,
			)
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'component'  => $component,
				'primary_id' => $g1,
			)
		);

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$a_ids = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertNotContains( $a2, $a_ids );
		$this->assertContains( $a1, $a_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_private_group_items_without_access() {
		$component = buddypress()->groups->id;

		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		// Current user is $u.
		$g1 = $this->bp::factory()->group->create(
			array(
				'status'     => 'private',
				'creator_id' => $this->user,
			)
		);

		$a1 = $this->bp::factory()->activity->create(
			array(
				'component'     => $component,
				'type'          => 'created_group',
				'user_id'       => $this->user,
				'item_id'       => $g1,
				'hide_sitewide' => true,
			)
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'component'  => $component,
				'primary_id' => $g1,
			)
		);

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$a_ids = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertEmpty( $a_ids );
		$this->assertNotContains( $a1, $a_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_private_group_items_with_the_group_id_param() {
		$component = buddypress()->groups->id;

		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		// Current user is $u.
		$g1 = $this->bp::factory()->group->create(
			array(
				'status'     => 'private',
				'creator_id' => $u,
			)
		);

		$g2 = $this->bp::factory()->group->create(
			array(
				'status'     => 'public',
				'creator_id' => $this->user,
			)
		);

		$a1 = $this->bp::factory()->activity->create(
			array(
				'component'     => $component,
				'type'          => 'created_group',
				'user_id'       => $u,
				'item_id'       => $g1,
				'hide_sitewide' => true,
			)
		);

		$a2 = $this->bp::factory()->activity->create(
			array(
				'component' => $component,
				'type'      => 'created_group',
				'user_id'   => $this->user,
				'item_id'   => $g2,
			)
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'group_id' => $g1,
			)
		);

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$a_ids = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertNotContains( $a2, $a_ids );
		$this->assertContains( $a1, $a_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_private_group_items_for_mod() {
		$component = buddypress()->groups->id;

		$u = static::factory()->user->create();
		$this->bp::set_current_user( $this->user );

		$g1 = $this->bp::factory()->group->create(
			array(
				'status'     => 'hidden',
				'creator_id' => $this->user,
			)
		);

		groups_join_group( $g1, $u );
		groups_promote_member( $u, $g1, 'mod' );

		$a1 = $this->bp::factory()->activity->create(
			array(
				'component'     => $component,
				'type'          => 'activity_update',
				'user_id'       => $this->user,
				'item_id'       => $g1,
				'hide_sitewide' => true,
			)
		);

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'type'     => 'activity_update',
				'group_id' => $g1,
			)
		);

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$a_ids = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertContains( $a1, $a_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_private_group_items_for_admin() {
		$component = buddypress()->groups->id;

		$u = static::factory()->user->create();
		$this->bp::set_current_user( $this->user );

		$g2 = $this->bp::factory()->group->create(
			array(
				'status'     => 'private',
				'creator_id' => $this->user,
			)
		);

		groups_join_group( $g2, $u );
		groups_promote_member( $u, $g2, 'admin' );

		$a2 = $this->bp::factory()->activity->create(
			array(
				'component'     => $component,
				'type'          => 'activity_update',
				'user_id'       => $this->user,
				'item_id'       => $g2,
				'hide_sitewide' => true,
			)
		);

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'type'     => 'activity_update',
				'group_id' => $g2,
			)
		);

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$a_ids = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertContains( $a2, $a_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_private_group_items_with_group_id_param_without_access() {
		$component = buddypress()->groups->id;

		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		// Current user is $u.
		$g1 = $this->bp::factory()->group->create(
			array(
				'status'     => 'private',
				'creator_id' => $this->user,
			)
		);

		$a1 = $this->bp::factory()->activity->create(
			array(
				'component'     => $component,
				'type'          => 'created_group',
				'user_id'       => $this->user,
				'item_id'       => $g1,
				'hide_sitewide' => true,
			)
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'group_id' => $g1,
			)
		);

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$a_ids = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertEmpty( $a_ids );
		$this->assertNotContains( $a1, $a_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_paginated_items() {
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$a = $this->bp::factory()->activity->create( array( 'user_id' => $u ) );
		$this->bp::factory()->activity->create_many( 5, array( 'user_id' => $u ) );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'user_id'  => $u,
				'page'     => 2,
				'per_page' => 5,
			)
		);

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$headers = $response->get_headers();
		$this->assertEquals( 6, $headers['X-WP-Total'] );
		$this->assertEquals( 2, $headers['X-WP-TotalPages'] );

		$a_ids = wp_list_pluck( $response->get_data(), 'id' );
		$this->assertContains( $a, $a_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_with_the_groups_scope() {
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$component = buddypress()->groups->id;

		// Current user is $this->user.
		$g1 = $this->bp::factory()->group->create(
			array(
				'status' => 'public',
			)
		);

		$a2 = $this->bp::factory()->activity->create(
			array(
				'component' => $component,
				'type'      => 'created_group',
				'user_id'   => $u,
				'item_id'   => $g1,
			)
		);

		$a1 = $this->bp::factory()->activity->create(
			array(
				'component' => $component,
				'type'      => 'activity_update',
				'user_id'   => $u,
				'item_id'   => $g1,
			)
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'user_id' => $u,
				'scope'   => 'groups',
			)
		);

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$a_ids = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertContains( $a1, $a_ids );
		$this->assertContains( $a2, $a_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_with_favorite() {
		$this->bp::set_current_user( $this->user );

		$this->bp::factory()->activity->create_many( 2 );
		$a1 = $this->bp::factory()->activity->create();

		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		bp_activity_add_user_favorite( $a1, $u );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$f_ids = wp_filter_object_list( $response->get_data(), array( 'favorited' => true ), 'AND', 'id' );
		$f_id  = reset( $f_ids );
		$this->assertEquals( $a1, $f_id );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_with_no_favorite() {
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$this->bp::factory()->activity->create_many( 3, array( 'user_id' => $u ) );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_query_params(
			array(
				'user_id' => $u,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$f_ids = wp_filter_object_list( $response->get_data(), array( 'favorited' => false ), 'AND', 'id' );
		$this->assertTrue( 3 === count( $f_ids ) );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$this->bp::set_current_user( $this->user );

		$activity = $this->endpoint->get_activity_object( $this->activity_id );
		$this->assertEquals( $this->activity_id, $activity->id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $activity->id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->check_activity_data( $activity, $all_data );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$activity = $this->endpoint->get_activity_object( $this->activity_id );
		$this->assertEquals( $this->activity_id, $activity->id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $activity->id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_embedded_hidden_group_from_activity() {
		$component = buddypress()->groups->id;
		$u1        = static::factory()->user->create();

		$this->bp::set_current_user( $u1 );

		$g1 = $this->bp::factory()->group->create(
			array(
				'status'     => 'hidden',
				'creator_id' => $u1,
			)
		);

		$a1 = $this->bp::factory()->activity->create(
			array(
				'component' => $component,
				'type'      => 'created_group',
				'user_id'   => $u1,
				'item_id'   => $g1,
			)
		);

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $a1 ) );
		$request->set_query_params( array( '_embed' => 'group' ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$data           = $this->server->response_to_data( $response, true );
		$embedded_group = current( $data['_embedded']['group'] );

		$this->assertSame( $g1, $embedded_group['id'] );
		$this->assertSame( $u1, $embedded_group['creator_id'] );
		$this->assertSame( 'hidden', $embedded_group['status'] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_embedded_hidden_group_from_activity_without_permission() {
		$component = buddypress()->groups->id;
		$u1        = static::factory()->user->create();
		$u2        = static::factory()->user->create();
		$g1        = $this->bp::factory()->group->create(
			array(
				'status'     => 'hidden',
				'creator_id' => $u1,
			)
		);

		$this->bp::set_current_user( $u2 );

		$a1 = $this->bp::factory()->activity->create(
			array(
				'component' => $component,
				'type'      => 'created_group',
				'user_id'   => $u2,
				'item_id'   => $g1,
			)
		);

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $a1 ) );
		$request->set_query_params( array( '_embed' => 'group' ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_embedded_private_group_from_activity_without_permission() {
		$component = buddypress()->groups->id;
		$u1        = static::factory()->user->create();
		$u2        = static::factory()->user->create();
		$g1        = $this->bp::factory()->group->create(
			array(
				'status'     => 'private',
				'creator_id' => $u1,
			)
		);

		$this->bp::set_current_user( $u2 );

		$a1 = $this->bp::factory()->activity->create(
			array(
				'component' => $component,
				'type'      => 'created_group',
				'user_id'   => $u2,
				'item_id'   => $g1,
			)
		);

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $a1 ) );
		$request->set_query_params( array( '_embed' => 'group' ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_for_item_belonging_to_private_group() {
		$component = buddypress()->groups->id;

		// Current user is $this->user.
		$g1 = $this->bp::factory()->group->create(
			array(
				'status' => 'private',
			)
		);

		$a1 = $this->bp::factory()->activity->create(
			array(
				'component'     => $component,
				'type'          => 'created_group',
				'user_id'       => $this->user,
				'item_id'       => $g1,
				'hide_sitewide' => true,
			)
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $a1 );

		// Non-authenticated.
		$this->bp::set_current_user( 0 );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );

		// Not a member of the group.
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );

		// Member of the group.
		$new_member               = new BP_Groups_Member();
		$new_member->group_id     = $g1;
		$new_member->user_id      = $u;
		$new_member->is_confirmed = true;
		$new_member->save();

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * @group render_item
	 */
	public function test_render_item() {
		$this->bp::set_current_user( $this->user );

		$a = $this->bp::factory()->activity->create(
			array(
				'user_id' => $this->user,
				'content' => 'links should be clickable: https://buddypress.org',
			)
		);

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $a ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$all_data = $response->get_data();

		$this->assertTrue( str_contains( $all_data['content']['rendered'], '</a>' ) );
	}

	/**
	 * @group render_item
	 */
	public function test_render_item_with_embed_post() {
		$this->bp::set_current_user( $this->user );
		$p = static::factory()->post->create();

		$a = $this->bp::factory()->activity->create(
			array(
				'user_id' => $this->user,
				'content' => get_post_embed_url( $p ),
			)
		);

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $a ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$all_data = $response->get_data();

		$this->assertTrue( str_contains( $all_data['content']['rendered'], 'wp-embedded-content' ) );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );

		$params = $this->set_activity_data();
		$request->set_body_params( $params );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->check_create_activity_response( $response );
	}

	/**
	 * @group create_item
	 */
	public function test_rest_create_item() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data();
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->check_create_activity_response( $response );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_with_no_content() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data( array( 'content' => '' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_create_activity_empty_content', $response, 400 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_user_not_logged_in() {
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data();
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_in_a_group() {
		$this->bp::set_current_user( $this->user );
		$g = $this->bp::factory()->group->create(
			array(
				'creator_id' => $this->user,
			)
		);

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data(
			array(
				'component'       => buddypress()->groups->id,
				'primary_item_id' => $g,
			)
		);

		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->check_create_activity_response( $response );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_in_a_private_group() {
		$this->bp::set_current_user( $this->user );
		$g = $this->bp::factory()->group->create(
			array(
				'creator_id' => $this->user,
				'status'     => 'private',
			)
		);

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data(
			array(
				'component'       => buddypress()->groups->id,
				'primary_item_id' => $g,
			)
		);

		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->check_create_activity_response( $response );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_in_an_hidden_group() {
		$this->bp::set_current_user( $this->user );
		$g = $this->bp::factory()->group->create(
			array(
				'creator_id' => $this->user,
				'status'     => 'hidden',
			)
		);

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data(
			array(
				'component'       => buddypress()->groups->id,
				'primary_item_id' => $g,
			)
		);

		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->check_create_activity_response( $response );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_and_get_comment() {
		$this->bp::set_current_user( $this->user );

		$a = $this->bp::factory()->activity->create(
			array(
				'component' => 'activity',
				'type'      => 'activity_update',
				'user_id'   => $this->user,
			)
		);

		$u = static::factory()->user->create();

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data(
			array(
				'type'            => 'activity_comment',
				'primary_item_id' => $a,
				'content'         => 'Activity comment content',
			)
		);

		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertTrue( (int) $u === (int) $data['user_id'], 'The user should be able to comment an activity' );

		// Checks the comment is fetched.
		$expected = $data['id'];

		$get_request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$get_request->set_query_params(
			array(
				'include'          => $a,
				'display_comments' => 'threaded',
			)
		);

		$get_response = $this->server->dispatch( $get_request );
		$get_data     = $get_response->get_data();

		$this->assertSame( $expected, $get_data[0]['comments'][0]['id'] );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_and_get_reply() {
		$u = static::factory()->user->create();

		$a = $this->bp::factory()->activity->create(
			array(
				'component' => 'activity',
				'type'      => 'activity_update',
				'user_id'   => $u,
			)
		);

		$c = bp_activity_new_comment(
			array(
				'type'        => 'activity_comment',
				'user_id'     => $this->user,
				'activity_id' => $a, // Root activity
				'content'     => 'Activity comment',
			)
		);

		$this->bp::set_current_user( $u );

		// Add a reply to c
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data(
			array(
				'type'              => 'activity_comment',
				'primary_item_id'   => $a, // Root activity
				'secondary_item_id' => $c, // Comment Parent
				'content'           => 'Activity comment reply',
			)
		);

		$request->set_body( wp_json_encode( $params ) );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertTrue( (int) $u === (int) $data['user_id'], 'The user should be able to reply to an activity' );

		// Checks the comment is fetched.
		$expected = $data['id'];

		$get_request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$get_request->set_query_params(
			array(
				'include'          => $a,
				'display_comments' => 'threaded',
			)
		);

		$get_response = $this->server->dispatch( $get_request );
		$get_data     = $get_response->get_data();

		$this->assertTrue( (int) $expected === (int) $get_data[0]['comments'][0]['comments'][0]['id'] );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_and_get_comment_in_a_group() {
		$this->bp::set_current_user( $this->user );

		$g = $this->bp::factory()->group->create(
			array(
				'creator_id' => $this->user,
			)
		);

		$a = $this->bp::factory()->activity->create(
			array(
				'component' => buddypress()->groups->id,
				'type'      => 'activity_update',
				'user_id'   => $this->user,
				'item_id'   => $g,
			)
		);

		$u = static::factory()->user->create();

		$this->bp::set_current_user( $u );
		groups_join_group( $g, $u );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data(
			array(
				'type'            => 'activity_comment',
				'primary_item_id' => $a,
			)
		);

		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertSame( (int) $u, (int) $data['user_id'], 'The user should be able to comment a group activity' );

		// Check the comment is fetched in group's activities.
		$expected = $data['id'];

		$get_request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$get_request->set_query_params(
			array(
				'group_id'         => $g,
				'type'             => 'activity_update',
				'display_comments' => 'threaded',
			)
		);

		$get_response = $this->server->dispatch( $get_request );
		$get_data     = $get_response->get_data();

		$this->assertSame( (int) $expected, (int) $get_data[0]['comments'][0]['id'] );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_with_no_content_in_a_group() {
		$this->bp::set_current_user( $this->user );
		$g = $this->bp::factory()->group->create(
			array(
				'creator_id' => $this->user,
			)
		);

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data(
			array(
				'component'       => buddypress()->groups->id,
				'primary_item_id' => $g,
				'content'         => '',
			)
		);

		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_create_activity_empty_content', $response, 400 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_blog_post_item() {
		$this->bp::set_current_user( $this->user );
		$p = static::factory()->post->create();

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data(
			array(
				'component'         => buddypress()->blogs->id,
				'primary_item_id'   => get_current_blog_id(),
				'secondary_item_id' => $p,
				'type'              => 'new_blog_post',
				'hidden'            => true,
			)
		);

		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->check_create_activity_response( $response );

		$activity = bp_activity_get(
			array(
				'show_hidden'  => true,
				'search_terms' => $params['content'],
				'filter'       => array(
					'object'       => buddypress()->blogs->id,
					'primary_id'   => get_current_blog_id(),
					'secondary_id' => $p,
				),
			)
		);

		$activity = reset( $activity['activities'] );

		$this->assertSame( $activity->id, $response->get_data()['id'] );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$this->bp::set_current_user( $this->user );

		$activity = $this->endpoint->get_activity_object( $this->activity_id );
		$this->assertEquals( $this->activity_id, $activity->id );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $activity->id ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data();
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->check_update_activity_response( $response );

		$new_data = $response->get_data();
		$this->assertNotEmpty( $new_data );

		$this->assertEquals( $this->activity_id, $new_data['id'] );
		$this->assertEquals( $params['content'], $new_data['content']['raw'] );

		$activity = $this->endpoint->get_activity_object( $this->activity_id );
		$this->assertEquals( $params['content'], $activity->content );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_but_keep_date_the_same() {
		$activity_date = '1968-12-25 01:23:45';
		$activity_id   = $this->bp::factory()->activity->create( array( 'recorded_time' => $activity_date ) );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $activity_id ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data();
		$request->set_body( wp_json_encode( $params ) );

		$response         = $this->server->dispatch( $request );
		$new_data         = $response->get_data();
		$updated_activity = $this->endpoint->get_activity_object( $new_data['id'] );

		// Dates should match.
		$this->assertEquals( $activity_date, $updated_activity->date_recorded );
	}

	/**
	 * @group update_item
	 * @group PR448
	 */
	public function test_update_item_posted_in_a_group() {
		$this->bp::set_current_user( $this->user );

		$g = $this->bp::factory()->group->create(
			array(
				'creator_id' => $this->user,
				'status'     => 'hidden',
			)
		);

		$a = $this->bp::factory()->activity->create_and_get(
			array(
				'user_id'       => $this->user,
				'component'     => buddypress()->groups->id,
				'type'          => 'activity_update',
				'item_id'       => $g,
				'content'       => 'Random content',
				'hide_sitewide' => true, // Private and hidden Group activities are hidden.
			)
		);

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $a->id ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data(
			array(
				'content'         => 'Updated random content',
				'primary_item_id' => $g,
			)
		);
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->check_update_activity_response( $response );

		$new_data = $response->get_data();
		$this->assertNotEmpty( $new_data );

		$activity = $this->endpoint->get_activity_object( $a->id );

		$this->assertEquals( $g, $activity->item_id );
		$this->assertEquals( $g, $new_data['primary_item_id'] );
		$this->assertEquals( $a->id, $new_data['id'] );
		$this->assertSame( (bool) $a->hide_sitewide, $new_data['hidden'], 'Private and hidden group activities should remain hidden' );
		$this->assertEquals( $params['content'], $new_data['content']['raw'] );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'type' => 'activity_update' ) ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_not_logged_in() {
		$activity = $this->endpoint->get_activity_object( $this->activity_id );

		$this->assertEquals( $this->activity_id, $activity->id );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $activity->id ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'type' => $activity->type ) ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, 401 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_without_permission() {
		$u = static::factory()->user->create();
		$a = $this->bp::factory()->activity->create( array( 'user_id' => $u ) );

		$u2 = static::factory()->user->create();
		$this->bp::set_current_user( $u2 );

		$activity = $this->endpoint->get_activity_object( $a );
		$this->assertEquals( $a, $activity->id );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $activity->id ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data();
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_without_content() {
		$u = static::factory()->user->create();
		$a = $this->bp::factory()->activity->create( array( 'user_id' => $u ) );

		$this->bp::set_current_user( $u );

		$activity = $this->endpoint->get_activity_object( $a );
		$this->assertEquals( $a, $activity->id );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $activity->id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data( array( 'content' => '' ) );
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_update_activity_empty_content', $response, 400 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_without_type() {
		$u = static::factory()->user->create();
		$a = $this->bp::factory()->activity->create( array( 'user_id' => $u ) );

		$this->bp::set_current_user( $u );

		$activity = $this->endpoint->get_activity_object( $a );
		$this->assertEquals( $a, $activity->id );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $activity->id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = array(
			'content'   => 'Activity content',
			'component' => buddypress()->activity->id,
		);
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_missing_callback_param', $response, 400 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$this->bp::set_current_user( $this->user );

		$activity_id = $this->bp::factory()->activity->create(
			array(
				'content' => 'Deleted activity',
			)
		);

		$activity = $this->endpoint->get_activity_object( $activity_id );
		$this->assertEquals( $activity_id, $activity->id );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $activity->id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertNotInstanceOf( 'WP_Error', $response );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data['deleted'] );

		$this->assertEquals( 'Deleted activity', $data['previous']['content']['raw'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_not_logged_in() {
		$activity = $this->endpoint->get_activity_object( $this->activity_id );
		$this->assertEquals( $this->activity_id, $activity->id );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $activity->id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_without_permission() {
		$u           = static::factory()->user->create();
		$activity_id = $this->bp::factory()->activity->create( array( 'user_id' => $u ) );

		$u2 = static::factory()->user->create();
		$this->bp::set_current_user( $u2 );

		$activity = $this->endpoint->get_activity_object( $activity_id );
		$this->assertEquals( $activity_id, $activity->id );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $activity->id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_favorite
	 */
	public function test_update_favorite() {
		$a = $this->bp::factory()->activity->create(
			array(
				'user_id' => $this->user,
			)
		);

		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d/favorite', $a ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$this->assertTrue( $data['favorited'] );
		$this->assertSame( $a, $data['id'] );
	}

	/**
	 * @group update_favorite
	 */
	public function test_update_favorite_remove() {
		$a = $this->bp::factory()->activity->create(
			array(
				'user_id' => $this->user,
			)
		);

		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		bp_activity_add_user_favorite( $a, $u );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d/favorite', $a ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$f_ids = wp_filter_object_list( $response->get_data(), array( 'favorited' => true ), 'AND', 'id' );
		$this->assertEmpty( $f_ids );
	}

	/**
	 * @group update_favorite
	 */
	public function test_update_favorite_when_disabled() {
		$a = $this->bp::factory()->activity->create(
			array(
				'user_id' => $this->user,
			)
		);

		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		add_filter( 'bp_activity_can_favorite', '__return_false' );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d/favorite', $a ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		remove_filter( 'bp_activity_can_favorite', '__return_false' );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	public function test_prepare_item() {
		$this->bp::set_current_user( $this->user );

		$activity = $this->endpoint->get_activity_object( $this->activity_id );
		$this->assertEquals( $this->activity_id, $activity->id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $activity->id ) );
		$request->set_query_params( array( 'context' => 'edit' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->assertNotEmpty( $all_data );
		$this->check_activity_data( $activity, $all_data, 'edit' );
	}

	/**
	 * @group additional_fields
	 */
	public function test_additional_fields() {
		$registered_fields = $GLOBALS['wp_rest_additional_fields'];

		bp_rest_register_field(
			'activity',
			'foo_field',
			array(
				'get_callback'    => array( $this, 'get_additional_field' ),
				'update_callback' => array( $this, 'update_additional_field' ),
				'schema'          => array(
					'description' => 'Activity Meta Field',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		$this->bp::set_current_user( $this->user );
		$expected = 'bar_value';

		// POST
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$params = $this->set_activity_data( array( 'foo_field' => $expected ) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$create_data = $response->get_data();
		$this->assertSame( $expected, $create_data['foo_field'] );

		// GET
		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $create_data['id'] ) );
		$response = $this->server->dispatch( $request );

		$get_data = $response->get_data();
		$this->assertSame( $expected, $get_data['foo_field'] );

		$GLOBALS['wp_rest_additional_fields'] = $registered_fields;
	}

	/**
	 * @group additional_fields
	 */
	public function test_update_additional_fields() {
		$registered_fields = $GLOBALS['wp_rest_additional_fields'];

		bp_rest_register_field(
			'activity',
			'bar_field',
			array(
				'get_callback'    => array( $this, 'get_additional_field' ),
				'update_callback' => array( $this, 'update_additional_field' ),
				'schema'          => array(
					'description' => 'Activity Meta Field',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		$this->bp::set_current_user( $this->user );
		$expected = 'foo_value';
		$a_id     = $this->bp::factory()->activity->create();

		// PUT
		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $a_id ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_activity_data( array( 'bar_field' => 'foo_value' ) );
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$update_data = $response->get_data();
		$this->assertTrue( $expected === $update_data['bar_field'] );

		$GLOBALS['wp_rest_additional_fields'] = $registered_fields;
	}

	protected function check_activity_data( $activity, $data, $context = 'view' ) {
		$this->assertEquals( $activity->user_id, $data['user_id'] );
		$this->assertEquals( $activity->component, $data['component'] );

		if ( 'view' === $context ) {
			$this->assertEquals( wpautop( $activity->content ), $data['content']['rendered'] );
		} else {
			$this->assertEquals( $activity->content, $data['content']['raw'] );
			$this->assertEquals( bp_rest_prepare_date_response( $activity->date_recorded ), $data['date_gmt'] );
		}

		$this->assertEquals( $activity->type, $data['type'] );
		$this->assertEquals(
			bp_rest_prepare_date_response( $activity->date_recorded, get_date_from_gmt( $activity->date_recorded ) ),
			$data['date']
		);
		$this->assertEquals( $activity->id, $data['id'] );
		$this->assertEquals( bp_activity_get_permalink( $activity->id ), $data['link'] );
		$this->assertEquals( $activity->item_id, $data['primary_item_id'] );
		$this->assertEquals( $activity->secondary_item_id, $data['secondary_item_id'] );
		$this->assertEquals( $activity->action, $data['title'] );
		$this->assertEquals( $activity->type, $data['type'] );
		$this->assertEquals( $activity->is_spam ? 'spam' : 'published', $data['status'] );
	}

	protected function check_add_edit_activity( $response, $update = false ) {
		if ( $update ) {
			$this->assertEquals( 200, $response->get_status() );
		} else {
			$this->assertEquals( 201, $response->get_status() );
		}

		$data     = $response->get_data();
		$activity = $this->endpoint->get_activity_object( $data['id'] );
		$this->check_activity_data( $activity, $data, 'edit' );
	}

	protected function set_activity_data( $args = array() ) {
		return wp_parse_args(
			$args,
			array(
				'content'   => 'Activity content',
				'type'      => 'activity_update',
				'component' => buddypress()->activity->id,
			)
		);
	}

	protected function check_update_activity_response( $response ) {
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = rest_ensure_response( $response );

		$this->assertEquals( 200, $response->get_status() );
		$headers = $response->get_headers();
		$this->assertArrayNotHasKey( 'Location', $headers );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$activity = $this->endpoint->get_activity_object( $data['id'] );
		$this->check_activity_data( $activity, $data, 'edit' );
	}

	protected function check_create_activity_response( $response ) {
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = rest_ensure_response( $response );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$activity = $this->endpoint->get_activity_object( $data['id'] );
		$this->check_activity_data( $activity, $data, 'edit' );
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 17, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'primary_item_id', $properties );
		$this->assertArrayHasKey( 'secondary_item_id', $properties );
		$this->assertArrayHasKey( 'user_id', $properties );
		$this->assertArrayHasKey( 'link', $properties );
		$this->assertArrayHasKey( 'component', $properties );
		$this->assertArrayHasKey( 'type', $properties );
		$this->assertArrayHasKey( 'title', $properties );
		$this->assertArrayHasKey( 'content', $properties );
		$this->assertArrayHasKey( 'date', $properties );
		$this->assertArrayHasKey( 'date_gmt', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'comments', $properties );
		$this->assertArrayHasKey( 'comment_count', $properties );
		$this->assertArrayHasKey( 'user_avatar', $properties );
		$this->assertArrayHasKey( 'hidden', $properties );
		$this->assertArrayHasKey( 'favorited', $properties );
	}

	public function test_context_param() {
		// Collection.
		$request  = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );

		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d', $this->activity_id ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}

	public function update_additional_field( $value, $data, $attribute ) {
		return bp_activity_update_meta( $data->id, '_' . $attribute, $value );
	}

	public function get_additional_field( $data, $attribute ) {
		return bp_activity_get_meta( $data['id'], '_' . $attribute );
	}
}
