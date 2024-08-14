<?php
/**
 * Group Membership Controller Tests.
 *
 * @group group-membership
 * @group groups
 */
class BP_Test_REST_Group_Membership_Endpoint extends WP_Test_REST_Controller_Testcase {
	protected $endpoint;
	protected $bp;
	protected $endpoint_url;
	protected $user;
	protected $group_id;
	protected $server;
	protected $search_terms;

	public function set_up() {
		parent::set_up();

		$this->endpoint     = new BP_REST_Group_Membership_Endpoint();
		$this->bp           = new BP_UnitTestCase();
		$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/' . buddypress()->groups->id . '/';
		$this->user         = static::factory()->user->create(
			array(
				'role'       => 'administrator',
				'user_email' => 'admin@example.com',
			)
		);

		$this->group_id = $this->bp::factory()->group->create(
			array(
				'name'        => 'Group Test',
				'description' => 'Group Description',
				'creator_id'  => $this->user,
			)
		);

		if ( ! $this->server ) {
			$this->server = rest_get_server();
		}
	}

	public function test_register_routes() {
		$routes   = $this->server->get_routes();
		$endpoint = $this->endpoint_url . '(?P<group_id>[\d]+)/members';

		// Main.
		$this->assertArrayHasKey( $endpoint, $routes );
		$this->assertCount( 2, $routes[ $endpoint ] );

		// Single.
		$single_endpoint = $endpoint . '/(?P<user_id>[\d]+)';

		$this->assertArrayHasKey( $single_endpoint, $routes );
		$this->assertCount( 2, $routes[ $single_endpoint ] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'status' => 'hidden',
			)
		);

		$this->populate_group_with_members( array( $u1, $u2 ), $g1 );

		$this->bp::set_current_user( $u1 );

		$request  = new WP_REST_Request( 'GET', $this->endpoint_url . $g1 . '/members' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$headers = $response->get_headers();
		$this->assertEquals( 2, $headers['X-WP-Total'] );
		$this->assertEquals( 1, $headers['X-WP-TotalPages'] );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$u_ids = wp_list_pluck( $all_data, 'id' );

		// Check results.
		$this->assertCount( 2, $u_ids );
		$this->assertEqualSets( array( $u1, $u2 ), $u_ids );
		$this->assertNotContains( $u3, $u_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_by_specific_group_role() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$this->bp::set_current_user( $u1 );

		$g1 = $this->bp::factory()->group->create(
			array(
				'creator' => $u1,
			)
		);

		$this->populate_group_with_members( array( $u1, $u2 ), $g1 );

		// Promote $u2 to a moderator
		add_filter( 'bp_is_item_admin', '__return_true' );

		$member_object = new BP_Groups_Member( $u2, $g1 );
		$member_object->promote( 'mod' );

		remove_filter( 'bp_is_item_admin', '__return_true' );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . $g1 . '/members' );
		$request->set_query_params(
			array(
				'roles' => array( 'mod' ),
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$u_ids = wp_list_pluck( $all_data, 'id' );

		// Check results.
		$this->assertCount( 1, $u_ids );
		$this->assertEqualSets( array( $u2 ), $u_ids );
		$this->assertNotContains( $u1, $u_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'creator' => $u1,
			)
		);

		$this->populate_group_with_members( array( $u1, $u2 ), $g1 );

		$request  = new WP_REST_Request( 'GET', $this->endpoint_url . $g1 . '/members' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_items
	 */
	public function test_get_paginated_items() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();
		$u4 = static::factory()->user->create();
		$u5 = static::factory()->user->create();
		$u6 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'status' => 'hidden',
			)
		);

		$this->populate_group_with_members( array( $u1, $u2, $u3, $u4, $u5, $u6 ), $g1 );

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . $g1 . '/members' );
		$request->set_query_params(
			array(
				'page'     => 2,
				'per_page' => 3,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$headers = $response->get_headers();
		$this->assertEquals( 6, $headers['X-WP-Total'] );
		$this->assertEquals( 2, $headers['X-WP-TotalPages'] );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$u_ids = wp_list_pluck( $all_data, 'id' );

		// Check results.
		$this->assertCount( 3, $u_ids );
		$this->assertEqualSets( array( $u4, $u5, $u6 ), $u_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_no_search_terms() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$g1 = $this->bp::factory()->group->create();

		$this->populate_group_with_members( array( $u1, $u2 ), $g1 );

		$member_object = new BP_Groups_Member( $u1, $g1 );
		$member_object->promote( 'admin' );

		$this->bp::set_current_user( $u1 );

		add_filter( 'bp_rest_group_members_get_items_query_args', array( $this, 'group_members_query_args' ) );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . $g1 . '/members' );

		$this->server->dispatch( $request );

		remove_filter( 'bp_rest_group_members_get_items_query_args', array( $this, 'group_members_query_args' ) );

		$this->assertFalse( $this->search_terms );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_has_search_terms() {
		$u1 = static::factory()->user->create(
			array(
				'user_nicename' => 'foo',
			)
		);

		$u2 = static::factory()->user->create(
			array(
				'user_nicename' => 'foo bar',
			)
		);

		$g1 = $this->bp::factory()->group->create();

		$this->populate_group_with_members( array( $u1, $u2 ), $g1 );

		$member_object = new BP_Groups_Member( $u1, $g1 );
		$member_object->promote( 'admin' );

		$this->bp::set_current_user( $u1 );

		add_filter( 'bp_rest_group_members_get_items_query_args', array( $this, 'group_members_query_args' ) );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . $g1 . '/members' );
		$request->set_param( 'search', 'bar' );
		$response = $this->server->dispatch( $request );

		remove_filter( 'bp_rest_group_members_get_items_query_args', array( $this, 'group_members_query_args' ) );

		$this->assertTrue( 'bar' === $this->search_terms );

		$all_data = $response->get_data();
		$results  = wp_list_pluck( $all_data, 'id' );

		$this->assertCount( 1, $results );
		$this->assertSame( $u2, $results[0] );
	}

	// Filter used to catch the search_terms query argument.
	public function group_members_query_args( $args ) {
		if ( isset( $args['search_terms'] ) ) {
			$this->search_terms = $args['search_terms'];
		}

		return $args;
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$this->markTestSkipped( 'This endpoint does not have a get_item method.' );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		$u = static::factory()->user->create();
		$g = $this->bp::factory()->group->create();

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url . $g . '/members' );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'user_id' => $u ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertTrue( $data['is_confirmed'] );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_as_admin() {
		$u = static::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url . $this->group_id . '/members' );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id' => $u,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$user          = bp_rest_get_user( $data['id'] );
		$member_object = new BP_Groups_Member( $user->ID, $this->group_id );

		$this->check_user_data( $user, $data, $member_object, 'edit' );
	}

	/**
	 * @group create_item
	 */
	public function test_member_can_add_himself_to_public_group() {
		$u = static::factory()->user->create();

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url . $this->group_id . '/members' );

		// This usually would be 'edit', but we are testing a public group.

		$request->set_query_params( array( 'user_id' => $u ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$user          = bp_rest_get_user( $data['id'] );
		$member_object = new BP_Groups_Member( $user->ID, $this->group_id );

		$this->check_user_data( $user, $data, $member_object, 'edit' );
	}

	/**
	 * @group create_item
	 */
	public function test_member_can_not_add_himself_to_private_group() {
		$u = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'status' => 'private',
			)
		);

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url . $g1 . '/members' );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'user_id' => $u ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_failed_to_join', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_member_can_not_add_himself_to_hidden_group() {
		$u = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'status' => 'hidden',
			)
		);

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url . $g1 . '/members' );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id' => $u,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_failed_to_join', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_member_cannot_add_others_to_public_group() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url . $this->group_id . '/members' );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'user_id' => $u2,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_failed_to_join', $response, 500 );
	}

	/**
	 * Site admin can ban member.
	 *
	 * @group update_item
	 * @group ban_member
	 */
	public function test_update_item() {
		$u = static::factory()->user->create();

		$this->populate_group_with_members( array( $u ), $this->group_id );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $this->group_id . '/members/' . $u );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'ban' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$user          = bp_rest_get_user( $data['id'] );
		$member_object = new BP_Groups_Member( $u, $this->group_id );

		$this->assertSame( $u, $user->ID );
		$this->assertTrue( (bool) $member_object->is_banned );
	}

	/**
	 * @group update_item
	 * @group ban_member
	 */
	public function test_group_mod_can_not_ban_member() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		// Promote $u2 to a group mod.
		$member_object = new BP_Groups_Member( $u1, $g1 );
		$member_object->promote( 'mod' );

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u3 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'ban' ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_cannot_ban', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 * @group ban_member
	 */
	public function test_group_mod_can_not_ban_random_member() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();
		$u4 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		// Promote $u2 to a group mod.
		$member_object = new BP_Groups_Member( $u1, $g1 );
		$member_object->promote( 'mod' );

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u4 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'ban' ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_cannot_ban', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 * @group ban_member
	 */
	public function test_group_admin_can_ban_member() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		// Promote $u2 to a group admin.
		$member_object = new BP_Groups_Member( $u2, $g1 );
		$member_object->promote( 'admin' );

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'ban' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$user          = bp_rest_get_user( $data['id'] );
		$member_object = new BP_Groups_Member( $user->ID, $g1 );

		$this->assertSame( $u1, $user->ID );
		$this->assertTrue( (bool) $member_object->is_banned );
	}

	/**
	 * @group update_item
	 * @group ban_member
	 */
	public function test_group_admin_can_not_ban_random_member() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();
		$u4 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u4 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'ban' ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_not_member', $response, 500 );
	}

	/**
	 * @group update_item
	 */
	public function test_site_admin_can_unban_member_from_group() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		// Ban $u1.
		$member_object = new BP_Groups_Member( $u1, $g1 );
		$member_object->ban();

		$this->assertTrue( (bool) $member_object->is_banned );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'unban' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$user          = bp_rest_get_user( $data['id'] );
		$member_object = new BP_Groups_Member( $user->ID, $g1 );

		$this->assertTrue( $u1 === $user->ID );
		$this->assertFalse( (bool) $member_object->is_banned );
	}

	/**
	 * @group update_item
	 */
	public function test_site_admin_can_not_unban_random_member() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();
		$u4 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u4 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'unban' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$user          = bp_rest_get_user( $data['id'] );
		$member_object = new BP_Groups_Member( $user->ID, $g1 );

		$this->assertFalse( groups_is_user_member( $user->ID, $g1 ) );
		$this->assertTrue( $u4 === $user->ID );
		$this->assertFalse( (bool) $member_object->is_banned );
	}

	/**
	 * @group update_item
	 */
	public function test_group_admin_can_unban_member() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		// Promote $u2 to a group admin.
		$member_object = new BP_Groups_Member( $u2, $g1 );
		$member_object->promote( 'admin' );

		// Ban $u1.
		$member_object = new BP_Groups_Member( $u1, $g1 );
		$member_object->ban();

		$this->assertTrue( (bool) $member_object->is_banned );

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'unban' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$user          = bp_rest_get_user( $data['id'] );
		$member_object = new BP_Groups_Member( $user->ID, $g1 );

		$this->assertTrue( $u1 === $user->ID );
		$this->assertFalse( (bool) $member_object->is_banned );
	}

	/**
	 * @group update_item
	 */
	public function test_group_mod_can_not_unban_member() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		// Promote $u2 to a group mod.
		$member_object = new BP_Groups_Member( $u2, $g1 );
		$member_object->promote( 'mod' );

		// Ban $u1.
		$member_object = new BP_Groups_Member( $u1, $g1 );
		$member_object->ban();

		$this->assertTrue( (bool) $member_object->is_banned );

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'unban' ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_cannot_unban', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 * @group promote_member
	 */
	public function test_site_admin_can_promote_member_to_mod() {
		$u = static::factory()->user->create();

		$this->populate_group_with_members( array( $u ), $this->group_id );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $this->group_id . '/members/' . $u );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'action' => 'promote',
				'role'   => 'mod',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$user          = bp_rest_get_user( $data['id'] );
		$member_object = new BP_Groups_Member( $user->ID, $this->group_id );

		$this->assertTrue( $u === $user->ID );
		$this->assertTrue( (bool) $member_object->is_mod );
	}

	/**
	 * @group update_item
	 * @group promote_member
	 */
	public function test_group_admin_can_promote_member() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1 ), $g1 );

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'action' => 'promote',
				'role'   => 'mod',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$user          = bp_rest_get_user( $data['id'] );
		$member_object = new BP_Groups_Member( $user->ID, $g1 );

		$this->assertTrue( $u1 === $user->ID );
		$this->assertTrue( (bool) $member_object->is_mod );
	}

	/**
	 * @group update_item
	 * @group promote_member
	 */
	public function test_member_can_not_promote_other_member() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		$this->bp::set_current_user( $u3 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'action' => 'promote',
				'role'   => 'mod',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_cannot_promote', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 * @group promote_member
	 */
	public function test_group_mods_can_not_promote_members() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u1 ) );

		// Promote $u2 to a mod.
		$this->bp::add_user_to_group( $u2, $g1, array( 'is_mod' => true ) );

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'action' => 'promote',
				'role'   => 'mod',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_cannot_promote', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 * @group promote_member
	 */
	public function test_group_mod_can_not_promote_himself() {
		$u1 = static::factory()->user->create();
		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u1 ) );

		// Promote $u1 to a mod.
		$this->bp::add_user_to_group( $u1, $g1, array( 'is_mod' => true ) );

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'action' => 'promote',
				'role'   => 'admin',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_cannot_promote', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 * @group demote_member
	 */
	public function test_site_admin_can_demote_group_admin_to_member() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		// Promote $u3 to an admin.
		$member_object = new BP_Groups_Member( $u3, $g1 );
		$member_object->promote( 'admin' );

		$this->assertTrue( (bool) $member_object->is_admin );

		// Site admin.
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u2 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'demote' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$user          = bp_rest_get_user( $data['id'] );
		$member_object = new BP_Groups_Member( $user->ID, $g1 );

		$this->assertTrue( $u2 === $user->ID );
		$this->assertFalse( (bool) $member_object->is_mod );
		$this->assertFalse( (bool) $member_object->is_admin );
		$this->assertTrue( (bool) groups_is_user_member( $u2, $g1 ) );
	}

	/**
	 * @group update_item
	 * @group demote_member
	 */
	public function test_site_admin_can_not_demote_the_only_group_admin() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		// Site admin.
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u2 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'demote' ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 * @group demote_member
	 */
	public function test_site_admin_can_not_demote_himself() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		// Site admin.
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $this->user );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'demote' ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_cannot_demote', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 * @group demote_member
	 */
	public function test_site_admin_can_demote_group_admins() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		// Promote $u2 to an admin.
		$member_object = new BP_Groups_Member( $u2, $g1 );
		$member_object->promote( 'admin' );

		// Promote $u3 to an admin.
		$member_object = new BP_Groups_Member( $u3, $g1 );
		$member_object->promote( 'admin' );

		$this->assertTrue( (bool) $member_object->is_admin );

		// Site admin.
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u2 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'demote' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$user = bp_rest_get_user( $data['id'] );

		$member_object = new BP_Groups_Member( $user->ID, $g1 );

		$this->assertTrue( $u2 === $user->ID );
		$this->assertFalse( (bool) $member_object->is_mod );
		$this->assertFalse( (bool) $member_object->is_admin );
		$this->assertTrue( (bool) groups_is_user_member( $u2, $g1 ) );
	}

	/**
	 * @group update_item
	 * @group demote_member
	 */
	public function test_site_admin_can_demote_group_mods() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u3 ) );

		$this->populate_group_with_members( array( $u1, $u2, $u3 ), $g1 );

		// Promote $u2 to an admin.
		$member_object = new BP_Groups_Member( $u2, $g1 );
		$member_object->promote( 'mod' );

		$this->assertTrue( (bool) $member_object->is_mod );

		// Site admin.
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u2 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'demote' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$user = bp_rest_get_user( $data['id'] );

		$member_object = new BP_Groups_Member( $user->ID, $g1 );

		$this->assertTrue( $u2 === $user->ID );
		$this->assertFalse( (bool) $member_object->is_mod );
		$this->assertFalse( (bool) $member_object->is_admin );
		$this->assertTrue( (bool) groups_is_user_member( $u2, $g1 ) );
	}

	/**
	 * @group update_item
	 * @group demote_member
	 */
	public function test_site_admin_can_not_demote_already_group_members() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u3 ) );

		$this->populate_group_with_members( array( $u1, $u2, $u3 ), $g1 );

		// Promote $u2 to an admin.
		$member_object = new BP_Groups_Member( $u2, $g1 );
		$member_object->promote( 'mod' );

		$this->assertTrue( (bool) $member_object->is_mod );

		// Site admin.
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'demote' ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_failed_to_demote', $response, 500 );
	}

	/**
	 * @group update_item
	 * @group demote_member
	 */
	public function test_group_admin_can_demote_another_group_admin_to_mod() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u3 ) );

		$this->populate_group_with_members( array( $u1, $u2 ), $g1 );

		$group_member = new BP_Groups_Member( $u2, $g1 );
		$group_member->promote( 'admin' );

		$this->bp::set_current_user( $u3 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u2 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'action' => 'demote',
				'role'   => 'mod',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$user = bp_rest_get_user( $data['id'] );

		$member_object = new BP_Groups_Member( $user->ID, $g1 );

		$this->assertTrue( $u2 === $user->ID );
		$this->assertTrue( (bool) $member_object->is_mod );
		$this->assertFalse( (bool) $member_object->is_admin );
	}

	/**
	 * @group update_item
	 * @group demote_member
	 */
	public function test_group_admin_can_not_demote_himself() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u2 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'demote' ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_cannot_demote', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 * @group demote_member
	 */
	public function test_group_admin_can_not_demote_already_group_members() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'demote' ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_failed_to_demote', $response, 500 );
	}

	/**
	 * @group update_item
	 * @group demote_member
	 */
	public function test_member_can_not_demote_another_member() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		$this->bp::set_current_user( $u3 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'demote' ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_cannot_demote', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 * @group demote_member
	 */
	public function test_member_can_not_demote_himself() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create( array( 'creator_id' => $u2 ) );

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		$this->bp::set_current_user( $u3 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $g1 . '/members/' . $u3 );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'action' => 'demote' ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_cannot_demote', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 * @group promote_member
	 */
	public function test_update_item_invalid_group_id() {
		$u = static::factory()->user->create();

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . REST_TESTS_IMPOSSIBLY_HIGH_NUMBER . '/members/' . $u );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'action' => 'promote',
				'role'   => 'mod',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_not_logged_in() {
		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . $this->group_id . '/members/0' );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, 401 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'creator_id' => $u3,
			)
		);

		$this->populate_group_with_members( array( $u1, $u2 ), $g1 );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$user = bp_rest_get_user( $all_data['previous']['id'] );
		$this->assertFalse( groups_is_user_member( $user->ID, $g1 ) );
		$this->assertSame( $u1, $user->ID );
	}

	/**
	 * @group delete_item
	 */
	public function test_member_can_remove_himself_from_group() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'creator_id' => $u3,
			)
		);

		$this->populate_group_with_members( array( $u1, $u2 ), $g1 );

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$user = bp_rest_get_user( $all_data['previous']['id'] );
		$this->assertFalse( groups_is_user_member( $user->ID, $g1 ) );
	}

	/**
	 * @group delete_item
	 */
	public function test_banned_member_can_not_remove_himself_from_group() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'creator_id' => $u3,
			)
		);

		$this->populate_group_with_members( array( $u1, $u2 ), $g1 );

		$group_member = new BP_Groups_Member( $u1, $g1 );
		$group_member->ban();

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_member_can_not_remove_others_from_group() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'creator_id' => $u3,
			)
		);

		$this->populate_group_with_members( array( $u1, $u2 ), $g1 );

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . $g1 . '/members/' . $u2 );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_group_admin_can_remove_member_from_group() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'creator_id' => $u3, // <- group admin.
			)
		);

		$this->populate_group_with_members( array( $u1, $u2 ), $g1 );

		$this->bp::set_current_user( $u3 );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$user = bp_rest_get_user( $all_data['previous']['id'] );
		$this->assertFalse( groups_is_user_member( $user->ID, $g1 ) );
		$this->assertSame( $u1, $user->ID );
	}

	/**
	 * @group delete_item
	 */
	public function test_group_admin_can_remove_himself_from_group() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'creator_id' => $u2, // <- group admin.
			)
		);

		$this->populate_group_with_members( array( $u1, $u3 ), $g1 );

		// Another group admin.
		$group_member = new BP_Groups_Member( $u3, $g1 );
		$group_member->promote( 'admin' );

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . $g1 . '/members/' . $u2 );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$user = bp_rest_get_user( $all_data['previous']['id'] );
		$this->assertFalse( groups_is_user_member( $user->ID, $g1 ) );
		$this->assertSame( $u2, $user->ID );
	}

	/**
	 * @group delete_item
	 */
	public function test_last_group_admin_can_not_remove_himself_from_group() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'creator_id' => $u2, // <- group admin.
			)
		);

		$this->populate_group_with_members( array( $u1 ), $g1 );

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . $g1 . '/members/' . $u2 );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_group_admin_can_remove_another_group_admin() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'creator_id' => $u1, // <- group admin.
			)
		);

		$this->populate_group_with_members( array( $u2, $u3 ), $g1 );

		$group_member = new BP_Groups_Member( $u3, $g1 );
		$group_member->promote( 'admin' );

		$this->bp::set_current_user( $u3 );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$user = bp_rest_get_user( $all_data['previous']['id'] );
		$this->assertFalse( groups_is_user_member( $user->ID, $g1 ) );
		$this->assertSame( $u1, $user->ID );
	}

	/**
	 * @group delete_item
	 */
	public function test_site_admin_can_not_remove_himself_group_admin() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'creator_id' => $u1, // <- group admin.
			)
		);

		$this->populate_group_with_members( array( $u2, $u3 ), $g1 );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . $g1 . '/members/' . $this->user );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_member_failed_to_remove', $response, 500 );
	}

	/**
	 * @group delete_item
	 */
	public function test_site_admin_can_remove_group_admin() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'creator_id' => $u1, // <- group admin.
			)
		);

		$this->populate_group_with_members( array( $u2, $u3 ), $g1 );

		$group_member = new BP_Groups_Member( $u3, $g1 );
		$group_member->promote( 'admin' );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$user = bp_rest_get_user( $all_data['previous']['id'] );
		$this->assertFalse( groups_is_user_member( $user->ID, $g1 ) );
		$this->assertSame( $u1, $user->ID );
	}

	/**
	 * @group delete_item
	 */
	public function test_site_admin_can_not_remove_last_group_admin() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$g1 = $this->bp::factory()->group->create(
			array(
				'creator_id' => $u1, // <- group admin.
			)
		);

		$this->populate_group_with_members( array( $u1, $u2 ), $g1 );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . $g1 . '/members/' . $u1 );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_prepare_item() {
		$this->markTestSkipped();
	}

	/**
	 * Add member to the group.
	 */
	protected function populate_group_with_members( $members, $group_id ) {
		foreach ( $members as $member_id ) {
			$this->bp::add_user_to_group( $member_id, $group_id );
		}
	}

	protected function check_user_data( $user, $data, $member_object, $context = 'view' ) {
		$this->assertEquals( $user->ID, $data['id'] );
		$this->assertEquals( $user->display_name, $data['name'] );
		$this->assertEquals( $user->user_login, $data['user_login'] );
		$this->assertArrayHasKey( 'avatar_urls', $data );
		$this->assertArrayHasKey( 'thumb', $data['avatar_urls'] );
		$this->assertArrayHasKey( 'full', $data['avatar_urls'] );
		$this->assertArrayHasKey( 'member_types', $data );
		$this->assertEquals(
			bp_members_get_user_url( $data['id'] ),
			$data['link']
		);

		if ( 'view' === $context ) {
			$this->assertArrayNotHasKey( 'roles', $data );
			$this->assertArrayNotHasKey( 'capabilities', $data );
			$this->assertArrayNotHasKey( 'extra_capabilities', $data );
			$this->assertArrayNotHasKey( 'registered_date', $data );
		}

		$this->assertArrayHasKey( 'xprofile', $data );

		// Checking extra.
		$this->assertEquals( $member_object->is_mod, (bool) $data['is_mod'] );
		$this->assertEquals( $member_object->is_admin, (bool) $data['is_admin'] );
		$this->assertEquals( $member_object->is_banned, (bool) $data['is_banned'] );
		$this->assertEquals( $member_object->is_confirmed, (bool) $data['is_confirmed'] );
		$this->assertEquals( bp_rest_prepare_date_response( $member_object->date_modified ), $data['date_modified_gmt'] );
	}

	public function test_get_item_schema() {
		$this->skipWithMultisite();

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url . $this->group_id . '/members' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 27, count( $properties ) );
		$this->assertArrayHasKey( 'avatar_urls', $properties );
		$this->assertArrayHasKey( 'capabilities', $properties );
		$this->assertArrayHasKey( 'extra_capabilities', $properties );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'group_id', $properties );
		$this->assertArrayHasKey( 'link', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'mention_name', $properties );
		$this->assertArrayHasKey( 'registered_date', $properties );
		$this->assertArrayHasKey( 'registered_date_gmt', $properties );
		$this->assertArrayHasKey( 'registered_since', $properties );
		$this->assertArrayHasKey( 'password', $properties );
		$this->assertArrayHasKey( 'roles', $properties );
		$this->assertArrayHasKey( 'xprofile', $properties );
		$this->assertArrayHasKey( 'friendship_status', $properties );
		$this->assertArrayHasKey( 'friendship_status_slug', $properties );
		$this->assertArrayHasKey( 'last_activity', $properties );
		$this->assertArrayHasKey( 'latest_update', $properties );
		$this->assertArrayHasKey( 'total_friend_count', $properties );

		// Extra fields.
		$this->assertArrayHasKey( 'is_mod', $properties );
		$this->assertArrayHasKey( 'is_admin', $properties );
		$this->assertArrayHasKey( 'is_banned', $properties );
		$this->assertArrayHasKey( 'is_confirmed', $properties );
		$this->assertArrayHasKey( 'date_modified', $properties );
		$this->assertArrayHasKey( 'date_modified_gmt', $properties );
	}

	public function test_context_param() {
		// Collection.
		$request  = new WP_REST_Request( 'OPTIONS', $this->endpoint_url . $this->group_id . '/members' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}
}
