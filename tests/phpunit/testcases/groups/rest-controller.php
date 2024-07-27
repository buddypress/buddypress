<?php
/**
 * Group Controller Tests.
 *
 * @package BuddyPress
 * @group group
 */
class BP_Test_REST_Group_Controller extends WP_Test_REST_Controller_Testcase {
	protected $endpoint;
	protected $bp;
	protected $endpoint_url;
	protected $user;
	protected $group_id;
	protected $server;

	public function set_up() {
		parent::set_up();

		$this->endpoint     = new BP_REST_Groups_Controller();
		$this->bp           = new BP_UnitTestCase();
		$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/' . buddypress()->groups->id;
		$this->user         = static::factory()->user->create( array(
			'role'       => 'administrator',
			'user_email' => 'admin@example.com',
		) );

		$this->group_id = $this->bp::factory()->group->create( array(
			'name'        => 'Group Test',
			'description' => 'Group Description',
			'creator_id'  => $this->user,
		) );

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

		$this->bp::factory()->group->create_many( 3 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$this->assertCount( 4, wp_list_pluck( $response->get_data(), 'id' ) );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$this->bp::factory()->group->create_many( 3 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_including_hidden_groups() {
		$u  = static::factory()->user->create();
		$g1 = $this->bp::factory()->group->create();
		$g2 = $this->bp::factory()->group->create( array(
			'status' => 'hidden',
		) );

		$this->bp::add_user_to_group( $u, $g1 );
		$this->bp::add_user_to_group( $u, $g2 );

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array(
			'show_hidden' => true,
			'user_id'     => $u,
		) );

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$data     = $all_data;
		$status   = wp_list_pluck( $data, 'status' );

		$this->assertCount( 2, wp_list_pluck( $data, 'id' ) );
		$this->assertTrue( in_array( 'public', $status, true ) );
		$this->assertTrue( in_array( 'hidden', $status, true ) );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_including_private_groups() {
		$u  = static::factory()->user->create();
		$g1 = $this->bp::factory()->group->create( array(
			'status' => 'private',
		) );
		$g2 = $this->bp::factory()->group->create( array(
			'status' => 'hidden',
		) );

		$this->bp::add_user_to_group( $u, $g1 );
		$this->bp::add_user_to_group( $u, $g2 );

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array(
			'show_hidden' => true,
			'user_id'     => $u,
		) );

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$data     = $all_data;
		$status   = wp_list_pluck( $data, 'status' );

		$this->assertCount( 2, wp_list_pluck( $data, 'id' ) );
		$this->assertTrue( in_array( 'hidden', $status, true ) );
		$this->assertTrue( in_array( 'private', $status, true ) );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_not_including_hidden_groups_when_not_using_user_id_param() {
		$u = static::factory()->user->create();
		$g = $this->bp::factory()->group->create( array(
			'status' => 'hidden',
		) );

		$this->bp::add_user_to_group( $u, $g );
		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array(
			'show_hidden' => true,
		) );

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$data     = $all_data;

		$this->assertCount( 1, wp_list_pluck( $data, 'id' ) );
		$this->assertSame( [ 'public' ], wp_list_pluck( $data, 'status' ) );
		$this->assertSame( [ $this->group_id ], wp_list_pluck( $data, 'id' ) );
	}

	/**
	 * @group get_items
	 */
	public function test_get_paginated_items() {
		$u  = static::factory()->user->create();
		$g1 = $this->bp::factory()->group->create();
		$g2 = $this->bp::factory()->group->create();
		$g3 = $this->bp::factory()->group->create();
		$g4 = $this->bp::factory()->group->create();
		$g5 = $this->bp::factory()->group->create();
		$g6 = $this->bp::factory()->group->create();

		$this->bp::add_user_to_group( $u, $g1 );
		$this->bp::add_user_to_group( $u, $g2 );
		$this->bp::add_user_to_group( $u, $g3 );
		$this->bp::add_user_to_group( $u, $g4 );
		$this->bp::add_user_to_group( $u, $g5 );
		$this->bp::add_user_to_group( $u, $g6 );

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array(
			'per_page' => 5,
			'user_id'  => $u,
		) );

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$headers = $response->get_headers();
		$this->assertEquals( 6, $headers['X-WP-Total'] );
		$this->assertEquals( 2, $headers['X-WP-TotalPages'] );
		$this->assertCount( 5, $response->get_data() );

		// Get results from page 2.
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array(
			'page'     => 2,
			'per_page' => 5,
			'user_id'  => $u,
		) );

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$headers = $response->get_headers();
		$this->assertEquals( 6, $headers['X-WP-Total'] );
		$this->assertEquals( 2, $headers['X-WP-TotalPages'] );
		$this->assertCount( 1, $response->get_data() );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_edit_context() {
		$this->bp::factory()->group->create();
		$this->bp::factory()->group->create();
		$this->bp::factory()->group->create();

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$admins = array();
		$groups = $response->get_data();
		foreach ( $groups as $group ) {
			if ( isset( $group['admins'] ) ) {
				$admins = array_merge( $admins, $group['admins'] );
			}
		}

		$this->assertEmpty( $admins, 'Listing Admins should not be possible for unauthenticated users' );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_edit_context_users_private_data() {
		$this->bp::set_current_user( $this->user );

		$this->bp::factory()->group->create();
		$this->bp::factory()->group->create();
		$this->bp::factory()->group->create();

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$has_private_datas = false;
		$admins = wp_list_pluck( $response->get_data(), 'admins' );

		foreach ( $admins as $admin ) {
			if ( isset( $admin['user_pass'] ) || isset( $admin['user_email'] ) || isset( $admin['user_activation_key'] ) ) {
				$has_private_datas = true;
			}
		}

		$this->assertFalse( $has_private_datas, 'Listing private data should not be possible for any user' );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_extra() {
		$u1 = $this->bp::factory()->user->create();
		$u2 = $this->bp::factory()->user->create();

		$this->bp::set_current_user( $u1 );

		$now = time();

		$d1 = gmdate( 'Y-m-d H:i:s', $now - 10000 );
		$d2 = gmdate( 'Y-m-d H:i:s', $now - 1000 );
		$d3 = gmdate( 'Y-m-d H:i:s', $now - 100 );
		$d4 = gmdate( 'Y-m-d H:i:s', $now - 500 );

		$a1 = $this->bp::factory()->group->create( array( 'date_created' => $d1 ) );
		$a2 = $this->bp::factory()->group->create( array( 'date_created' => $d2 ) );
		$a3 = $this->bp::factory()->group->create( array( 'date_created' => $d3 ) );

		$this->bp::add_user_to_group( $u2, $a3 );

		groups_update_groupmeta( $a1, 'last_activity', $d4 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'populate_extras', true );
		$request->set_param( 'type', 'newest' );
		$request->set_param( 'exclude', $this->group_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$data     = $all_data;

		// Check order.
		$this->assertSame( array( $a3, $a2, $a1 ), array_map( 'intval', wp_list_pluck( $data, 'id' ) ) );

		// Check member count.
		$this->assertEquals( array( $a3 ), array_values( wp_filter_object_list( $all_data, array( 'total_member_count' => 2 ), 'AND', 'id' ) ) );

		// check time diff.
		$not_right_now = wp_filter_object_list( $all_data, array( 'id' => $a1 ), 'AND', 'last_activity_diff' );
		$not_right_now = reset( $not_right_now );
		$this->assertEquals( bp_core_time_since( $d4 ), $not_right_now );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_with_group_types() {
		$this->bp::set_current_user( $this->user );

		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );

		$a1 = $this->bp::factory()->group->create();
		$a2 = $this->bp::factory()->group->create();
		$a3 = $this->bp::factory()->group->create();

		$expected_types = array(
			$a1 => array( 'foo' ),
			$a2 => array( 'bar', 'foo' ),
			$a3 => array( 'bar' ),
		);

		foreach ( $expected_types as $group => $type ) {
			bp_groups_set_group_type( $group, $type );
		}

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'exclude', $this->group_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->assertSame( $expected_types, wp_list_pluck( $all_data, 'types', 'id' ) );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$group = $this->endpoint->get_group_object( $this->group_id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $group->id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->check_group_data( $group, $all_data[0], 'view' );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_unauthenticated_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$group = $this->endpoint->get_group_object( $this->group_id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $group->id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_unauthenticated() {
		$group = $this->endpoint->get_group_object( $this->group_id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $group->id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->check_group_data( $group, $all_data[0], 'view' );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_invalid_group_id() {
		$this->bp::set_current_user( $this->user );

		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group get_item
	 */
	public function test_get_hidden_group() {
		$u = static::factory()->user->create();
		$g = $this->bp::factory()->group->create( array(
			'status' => 'hidden',
		) );

		$group = $this->endpoint->get_group_object( $g );

		$this->bp::add_user_to_group( $u, $group->id );
		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $group->id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->check_group_data( $group, $all_data[0], 'view' );
	}

	/**
	 * @group get_item
	 */
	public function test_get_hidden_group_without_being_from_group() {
		$u = static::factory()->user->create();
		$g = $this->bp::factory()->group->create( array(
			'status' => 'hidden',
		) );

		$group = $this->endpoint->get_group_object( $g );

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $group->id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 * @group avatar
	 */
	public function test_get_item_with_avatar() {
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$group = $this->endpoint->get_group_object( $this->group_id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $group->id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$all_data = $response->get_data();

		$this->assertArrayHasKey( 'thumb', $all_data[0]['avatar_urls'] );
		$this->assertArrayHasKey( 'full', $all_data[0]['avatar_urls'] );
	}

	/**
	 * @group get_item
	 * @group avatar
	 */
	public function test_get_item_without_avatar() {
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$group = $this->endpoint->get_group_object( $this->group_id );

		add_filter( 'bp_disable_group_avatar_uploads', '__return_true' );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $group->id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$all_data = $response->get_data();

		remove_filter( 'bp_disable_group_avatar_uploads', '__return_true' );

		$this->assertArrayNotHasKey( 'avatar_urls', $all_data[0] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_extra() {
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$group = $this->endpoint->get_group_object( $this->group_id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $group->id ) );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'populate_extras', true );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$group = reset( $all_data );

		$this->assertTrue( ! is_null( $group['total_member_count'] ) );
		$this->assertTrue( ! is_null( $group['last_activity'] ) );
		$this->assertTrue( ! is_null( $group['last_activity_diff'] ) );
	}

	/**
	 * @group render_item
	 */
	public function test_render_item() {
		$this->bp::set_current_user( $this->user );

		$g = $this->bp::factory()->group->create( array(
			'name'        => 'Group Test',
			'description' => 'links should be clickable: https://buddypress.org',
		) );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $g ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$all_data = $response->get_data();
		$a_data   = reset( $all_data );

		$this->assertTrue( false !== strpos( $a_data['description']['rendered'], '</a>' ) );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );

		$params = $this->set_group_data();
		$request->set_body_params( $params );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->check_create_group_response( $response );
	}

	/**
	 * @group create_item
	 */
	public function test_rest_create_item() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data();
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->check_create_group_response( $response );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_with_group_type() {
		bp_groups_register_group_type( 'foo' );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data( array( 'types' => 'foo' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_data()[0]['types'], array( 'foo' ) );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_with_no_name() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data( array( 'name' => '' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_create_group_empty_name', $response, 400 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_user_not_logged_in() {
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data();
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, 401 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_invalid_status() {
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data( array( 'status' => 'foo' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$group = $this->endpoint->get_group_object( $this->group_id );
		$this->assertEquals( $this->group_id, $group->id );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $group->id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data( array( 'description' => 'Updated Description' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->check_update_group_response( $response );

		$new_data = $response->get_data();
		$new_data = $new_data[0];

		$this->assertEquals( $this->group_id, $new_data['id'] );
		$this->assertEquals( $params['description'], $new_data['description']['raw'] );

		$group = $this->endpoint->get_group_object( $new_data['id'] );
		$this->assertEquals( $params['description'], $group->description );
	}

	/**
	 * @group update_item
	 */
	public function test_update_group_type() {
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );

		bp_groups_set_group_type( $this->group_id, 'bar' );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $this->group_id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data( array( 'types' => 'foo' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_data()[0]['types'], array( 'foo' ) );
	}

	/**
	 * @group update_item
	 */
	public function test_remove_group_type() {
		bp_groups_register_group_type( 'bar' );

		bp_groups_set_group_type( $this->group_id, 'bar' );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $this->group_id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data( array( 'remove_types' => 'bar' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_data()[0]['types'], array() );
	}

	/**
	 * @group update_item
	 */
	public function test_append_group_type() {
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );

		bp_groups_set_group_type( $this->group_id, 'bar' );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $this->group_id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data( array( 'append_types' => 'foo' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_data()[0]['types'], array( 'bar', 'foo' ) );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request  = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_not_logged_in() {
		$group = $this->endpoint->get_group_object( $this->group_id );

		$this->assertEquals( $this->group_id, $group->id );

		$request  = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $group->id ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, 401 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_without_permission() {
		$u = static::factory()->user->create( array( 'role' => 'subscriber' ) );
		$a = $this->bp::factory()->group->create( array( 'creator_id' => $u ) );

		$u2 = static::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->bp::set_current_user( $u2 );

		$group = $this->endpoint->get_group_object( $a );
		$this->assertEquals( $a, $group->id );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $group->id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data();
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_site_admins_can_update_item() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $this->group_id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data( array( 'description' => 'Updated Description' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->check_update_group_response( $response );

		$new_data = $response->get_data();
		$new_data = $new_data[0];

		$this->assertEquals( $this->group_id, $new_data['id'] );
		$this->assertEquals( $params['description'], $new_data['description']['raw'] );

		$group = $this->endpoint->get_group_object( $new_data['id'] );
		$this->assertEquals( $params['description'], $group->description );
	}

	/**
	 * @group update_item
	 */
	public function test_group_admins_can_update_item() {
		$u = static::factory()->user->create();

		// Add user to group as an group admin.
		$this->bp::add_user_to_group( $u, $this->group_id, [ 'is_admin' => true ] );

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $this->group_id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data( array( 'description' => 'Group Admin Updated Group' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->check_update_group_response( $response );

		$new_data = $response->get_data();
		$new_data = $new_data[0];

		$this->assertEquals( $this->group_id, $new_data['id'] );
		$this->assertEquals( $params['description'], $new_data['description']['raw'] );

		$group = $this->endpoint->get_group_object( $new_data['id'] );
		$this->assertEquals( $params['description'], $group->description );
	}

	/**
	 * @group update_item
	 */
	public function test_group_moderators_can_not_update_item() {
		$u = static::factory()->user->create();

		// Add user to group as a moderator.
		$this->bp::add_user_to_group( $u, $this->group_id, [ 'is_mod' => true ] );

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $this->group_id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data( array( 'description' => 'Moderator Updated' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_invalid_status() {
		$group = $this->endpoint->get_group_object( $this->group_id );
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $group->id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data( array( 'status' => 'bar' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->group_id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'Group Description', $data['previous']['description']['raw'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_not_logged_in() {
		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->group_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_without_permission() {
		$this->bp::set_current_user( static::factory()->user->create() );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->group_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_group_moderators_can_not_delete_group() {
		$u = static::factory()->user->create();

		// Add user to group as a moderator.
		$this->bp::add_user_to_group( $u, $this->group_id, [ 'is_mod' => true ] );

		$this->bp::set_current_user( $u );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->group_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_site_admins_can_delete_group() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->group_id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'Group Description', $data['previous']['description']['raw'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_group_admins_can_delete_group() {
		$u = static::factory()->user->create();

		// Add user to group as a group admin.
		$this->bp::add_user_to_group( $u, $this->group_id, [ 'is_admin' => true ] );

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->group_id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'Group Description', $data['previous']['description']['raw'] );
	}

	/**
	 * @group get_current_user_groups
	 */
	public function test_get_current_user_groups() {
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$groups = array();
		foreach ( array( 'public', 'private', 'hidden' ) as $status ) {
			$groups[ $status ] = $this->bp::factory()->group->create( array(
				'status'      => $status,
				'creator_id'  => $u,
			) );
		}

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/me' );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertEquals( $groups, wp_list_pluck( $all_data, 'id', 'status' ) );
	}

	/**
	 * @group get_current_user_groups
	 */
	public function test_get_current_user_groups_max_one() {
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$groups = array();
		foreach ( array( 'public', 'private', 'hidden' ) as $status ) {
			$groups[ $status ] = $this->bp::factory()->group->create( array(
				'status'      => $status,
				'creator_id'  => $u,
			) );
		}

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/me' );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'max', 1 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$found_groups = wp_list_pluck( $all_data, 'id' );

		$this->assertEquals( 1, count( $found_groups ) );
		$this->assertTrue( in_array( $found_groups[0], $groups, true ) );
	}

	/**
	 * @group get_current_user_groups
	 */
	public function test_get_current_user_groups_not_loggedin() {
		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/me' );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	public function test_prepare_item() {
		$this->bp::set_current_user( $this->user );

		$group = $this->endpoint->get_group_object( $this->group_id );
		$this->assertEquals( $this->group_id, $group->id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $group->id ) );
		$request->set_query_params( array( 'context' => 'edit' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->check_group_data( $group, $all_data[0], 'edit' );
	}

	protected function check_group_data( $group, $data, $context ) {
		$this->assertEquals( $group->id, $data['id'] );
		$this->assertEquals( $group->creator_id, $data['creator_id'] );
		$this->assertEquals(
			bp_rest_prepare_date_response( $group->date_created, get_date_from_gmt( $group->date_created ) ),
			$data['date_created']
		);
		$this->assertEquals( bp_rest_prepare_date_response( $group->date_created ), $data['date_created_gmt'] );
		$this->assertEquals( $group->enable_forum, $data['enable_forum'] );
		$this->assertEquals( bp_get_group_url( $group ), $data['link'] );
		$this->assertEquals( $group->name, $data['name'] );
		$this->assertEquals( $group->slug, $data['slug'] );
		$this->assertEquals( $group->status, $data['status'] );
		$this->assertEquals( $group->parent_id, $data['parent_id'] );
		$this->assertEquals( [], $data['types'] );

		if ( 'view' === $context ) {
			$this->assertEquals( wpautop( $group->description ), $data['description']['rendered'] );
		} else {
			$this->assertEquals( $group->description, $data['description']['raw'] );
			$this->assertEquals( $group->total_member_count, $data['total_member_count'] );
			$this->assertEquals(
				bp_rest_prepare_date_response( $group->last_activity, get_date_from_gmt( $group->last_activity ) ),
				$data['last_activity']
			);
			$this->assertEquals( bp_rest_prepare_date_response( $group->last_activity ), $data['last_activity_gmt'] );
		}
	}

	protected function check_add_edit_group( $response, $update = false ) {
		if ( $update ) {
			$this->assertEquals( 200, $response->get_status() );
		} else {
			$this->assertEquals( 201, $response->get_status() );
		}

		$data  = $response->get_data();
		$group = $this->endpoint->get_group_object( $data['id'] );

		$this->check_group_data( $group, $data, 'edit' );
	}

	protected function set_group_data( $args = array() ) {
		return wp_parse_args( $args, array(
			'name'        => 'Group Name',
			'slug'        => 'group-name',
			'description' => 'Group Description',
			'creator_id'  => $this->user,
		) );
	}

	protected function check_update_group_response( $response ) {
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = rest_ensure_response( $response );

		$this->assertEquals( 200, $response->get_status() );
		$headers = $response->get_headers();
		$this->assertArrayNotHasKey( 'Location', $headers );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$group = $this->endpoint->get_group_object( $data['id'] );
		$this->check_group_data( $group, $data, 'edit' );
	}

	protected function check_create_group_response( $response ) {
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = rest_ensure_response( $response );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$group = $this->endpoint->get_group_object( $data['id'] );
		$this->check_group_data( $group, $data, 'edit' );
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 20, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'creator_id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'link', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'enable_forum', $properties );
		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'date_created_gmt', $properties );
		$this->assertArrayHasKey( 'created_since', $properties );
		$this->assertArrayHasKey( 'admins', $properties );
		$this->assertArrayHasKey( 'mods', $properties );
		$this->assertArrayHasKey( 'types', $properties );
		$this->assertArrayHasKey( 'parent_id', $properties );
		$this->assertArrayHasKey( 'total_member_count', $properties );
		$this->assertArrayHasKey( 'last_activity', $properties );
		$this->assertArrayHasKey( 'last_activity_gmt', $properties );
		$this->assertArrayHasKey( 'last_activity_diff', $properties );
	}

	/**
	 * @group item_schema
	 */
	public function test_get_item_schema_group_types_enum() {
		$expected = array( 'foo', 'bar' );

		foreach ( $expected as $type ) {
			bp_groups_register_group_type( $type );
		}

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertArrayHasKey( 'types', $properties );
		$this->assertEquals( array_values( $properties['types']['enum'] ), $expected );
	}

	public function test_context_param() {
		// Collection.
		$request  = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );

		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}

	public function update_additional_field( $value, $data, $attribute ) {
		return groups_update_groupmeta( $data->id, '_' . $attribute, $value );
	}

	public function get_additional_field( $data, $attribute )  {
		return groups_get_groupmeta( $data['id'], '_' . $attribute );
	}

	/**
	 * @group additional_fields
	 */
	public function test_additional_fields() {
		$registered_fields = $GLOBALS['wp_rest_additional_fields'];

		bp_rest_register_field( 'groups', 'foo_field', array(
			'get_callback'    => array( $this, 'get_additional_field' ),
			'update_callback' => array( $this, 'update_additional_field' ),
			'schema'          => array(
				'description' => 'Groups single item Meta Field',
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		) );

		$this->bp::set_current_user( $this->user );
		$expected = 'bar_value';

		// POST
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$params = $this->set_group_data( array( 'foo_field' => $expected ) );
		$request->set_body_params( $params );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$create_data = $response->get_data();
		$this->assertTrue( $expected === $create_data[0]['foo_field'] );

		// GET
		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $create_data[0]['id'] ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$get_data = $response->get_data();
		$this->assertTrue( $expected === $get_data[0]['foo_field'] );

		$GLOBALS['wp_rest_additional_fields'] = $registered_fields;
	}

	/**
	 * @group additional_fields
	 */
	public function test_update_additional_fields() {
		$registered_fields = $GLOBALS['wp_rest_additional_fields'];

		bp_rest_register_field( 'groups', 'bar_field', array(
			'get_callback'    => array( $this, 'get_additional_field' ),
			'update_callback' => array( $this, 'update_additional_field' ),
			'schema'          => array(
				'description' => 'Groups single item Meta Field',
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		) );

		$this->bp::set_current_user( $this->user );
		$expected = 'foo_value';
		$g_id     = $this->bp::factory()->group->create();

		// PUT
		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $g_id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_group_data( array( 'bar_field' => 'foo_value' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$update_data = $response->get_data();
		$this->assertTrue( $expected === $update_data['bar_field'] );

		$GLOBALS['wp_rest_additional_fields'] = $registered_fields;
	}
}
