<?php
/**
 * Members V1 Controller Tests.
 *
 * @package BuddyPress
 * @group members
 */
class BP_Test_REST_Members_V1_Controller extends WP_Test_REST_Controller_Testcase {
	protected $endpoint;
	protected $bp;
	protected $endpoint_url;
	protected $server;

	protected static $user;
	protected static $site;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$user = $factory->user->create( array(
			'role'          => 'administrator',
			'user_login'    => 'administrator',
			'user_nicename' => 'administrator',
			'user_email'    => 'admin@example.com',
		) );

		if ( is_multisite() ) {
			self::$site = $factory->blog->create( array(
				'domain' => 'rest.wordpress.org',
				'path'   => '/',
			) );

			update_site_option( 'site_admins', array( 'superadmin' ) );
		}
	}

	public static function wpTearDownAfterClass() {
		self::delete_user( self::$user );

		if ( is_multisite() ) {
			wpmu_delete_blog( self::$site, true );
		}
	}

	public function set_up() {
		parent::set_up();

		buddypress()->members->types = array();

		$this->endpoint     = new BP_REST_Members_V1_Controller();
		$this->bp           = new BP_UnitTestCase();
		$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/members';

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
		$this->assertArrayHasKey( $this->endpoint_url . '/me', $routes );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array(
			'user_ids' => [ $u1, $u2, $u3 ],
		) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->assertTrue( 3 === count( $all_data ) );

		foreach ( $all_data as $data ) {
			$this->check_user_data( get_userdata( $data['id'] ), $data, 'view' );
		}
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array(
			'user_ids' => [ $u1, $u2, $u3 ],
		) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_extra() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		// Set current user.
		$current_user = get_current_user_id();
		$this->bp::set_current_user( $u2 );

		// u2 is the only one to have a latest_update.
		$a1 = bp_activity_post_update(
			array(
				'type'    => 'activity_update',
				'user_id' => $u2,
				'content' => 'The Joshua Tree',
			)
		);

		$date_last_activity = date( 'Y-m-d H:i:s', bp_core_current_time( true, 'timestamp' ) );

		// u1 is the only one to have a last activity
		bp_update_user_last_activity( $u1, $date_last_activity );

		$this->bp::set_current_user( $current_user );

		// u1 and u3 are friends.
		friends_add_friend( $u1, $u3, true );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'populate_extras' => true,
				'user_ids'        => [ $u1, $u2, $u3 ],
			)
		);
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$members = $response->get_data();
		$this->assertNotEmpty( $members );

		$this->assertTrue( 3 === count( $members ) );

		$latest_activities = wp_list_pluck( $members, 'last_activity', 'id' );
		$this->assertEquals( bp_rest_prepare_date_response( $date_last_activity ), $latest_activities[ $u1 ]['date'] );

		$this->assertEquals( array( $u1, $u3 ), array_values( wp_filter_object_list( $members, array( 'total_friend_count' => 1 ), 'AND', 'id' ) ) );

		$latest_updates = wp_list_pluck( $members, 'latest_update', 'id' );
		$this->assertEquals( $a1, $latest_updates[ $u2 ]['id'] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_paginated_items() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();
		$u4 = static::factory()->user->create();

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array(
			'user_ids' => [ $u1, $u2, $u3, $u4 ],
			'page'     => 2,
			'per_page' => 2,
		) );

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$headers = $response->get_headers();
		$this->assertEquals( 4, $headers['X-WP-Total'] );
		$this->assertEquals( 2, $headers['X-WP-TotalPages'] );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		foreach ( $all_data as $data ) {
			$this->check_user_data( get_userdata( $data['id'] ), $data, 'view' );
		}
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_with_types() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );

		$expected_types = array(
			$u1 => array( 'foo' ),
			$u2 => array( 'bar', 'foo' ),
			$u3 => array( 'bar' ),
		);

		foreach ( $expected_types as $user => $type ) {
			bp_set_member_type( $user, $type );
		}

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array(
			'user_ids' => [ $u1, $u2, $u3 ],
		) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->assertTrue( 3 === count( $all_data ) );
		$this->assertSame( $expected_types, wp_list_pluck( $all_data, 'member_types', 'id' ) );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_filtered_by_xprofile() {
		$u  = $this->bp::factory()->user->create();
		$u2 = $this->bp::factory()->user->create();
		$g  = $this->bp::factory()->xprofile_group->create();
		$f  = $this->bp::factory()->xprofile_field->create( [
			'field_group_id' => $g,
			'type'           => 'textbox',
			'name'           => 'foo',
		] );

		xprofile_set_field_data( $f, $u, 'bar' );
		xprofile_set_field_data( $f, $u2, 'bar2' );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array(
			'xprofile' => [
				'args' => [
					[
						'field' => $f,
						'value' => 'bar',
					]
				]
			],
		) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );
		$this->assertTrue( 1 === count( $all_data ) );

		$user_ids = wp_list_pluck( $all_data, 'id' );

		$this->assertFalse( in_array( $u2, $user_ids, true ) );
		$this->assertSame( array( $u ), $user_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_filtered_by_xprofile_with_and_relation() {
		$u  = $this->bp::factory()->user->create();
		$u2 = $this->bp::factory()->user->create();

		$g = $this->bp::factory()->xprofile_group->create();

		$f  = $this->bp::factory()->xprofile_field->create( [
			'field_group_id' => $g,
			'type'           => 'textbox',
			'name'           => 'foo',
		] );
		$f2 = $this->bp::factory()->xprofile_field->create( [
			'field_group_id' => $g,
			'type'           => 'textbox',
			'name'           => 'bar',
		] );

		xprofile_set_field_data( $f, $u, 'foo1' );
		xprofile_set_field_data( $f2, $u, 'bar1' );

		xprofile_set_field_data( $f, $u2, 'foo1' );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array(
			'xprofile' => [
				'relation' => 'and',
				'args' => [
					[
						'field' => $f,
						'value' => 'foo1',
					],
					[
						'field' => $f2,
						'value' => 'bar1',
					]
				]
			],
		) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$user_ids = wp_list_pluck( $all_data, 'id' );

		$this->assertNotEmpty( $all_data );
		$this->assertTrue( 1 === count( $all_data ) );
		$this->assertFalse( in_array( $u2, $user_ids, true ) );
		$this->assertSame( array( $u ), $user_ids );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_filtered_by_xprofile_with_or_relation() {
		$u  = $this->bp::factory()->user->create();
		$u2 = $this->bp::factory()->user->create();
		$u3 = $this->bp::factory()->user->create();

		$g = $this->bp::factory()->xprofile_group->create();

		$f  = $this->bp::factory()->xprofile_field->create( [
			'field_group_id' => $g,
			'type'           => 'textbox',
			'name'           => 'foo',
		] );
		$f2 = $this->bp::factory()->xprofile_field->create( [
			'field_group_id' => $g,
			'type'           => 'textbox',
			'name'           => 'bar',
		] );

		xprofile_set_field_data( $f, $u, 'foo1' );
		xprofile_set_field_data( $f2, $u, 'bar1' );

		xprofile_set_field_data( $f, $u2, 'foo2' );
		xprofile_set_field_data( $f2, $u2, 'bar2' );

		xprofile_set_field_data( $f, $u3, 'foo3' );
		xprofile_set_field_data( $f2, $u3, 'bar3' );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array(
			'xprofile' => [
				'relation' => 'or',
				'args' => [
					[
						'field' => $f,
						'value' => 'foo1',
					],
					[
						'field' => $f2,
						'value' => 'bar3',
					]
				]
			],
		) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$user_ids = wp_list_pluck( $all_data, 'id' );

		$this->assertNotEmpty( $all_data );
		$this->assertTrue( 2 === count( $all_data ) );
		$this->assertFalse( in_array( $u2, $user_ids, true ) );
		$this->assertSame( array( $u3, $u ), $user_ids );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$u = static::factory()->user->create();

		// Register and set member types.
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		bp_set_member_type( $u, 'foo' );
		bp_set_member_type( $u, 'bar', true );

		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $u ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->check_get_user_response( $response );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$u = static::factory()->user->create();

		// Register and set member types.
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		bp_set_member_type( $u, 'foo' );
		bp_set_member_type( $u, 'bar', true );

		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $u ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_extras() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		// Set current user.
		$current_user = get_current_user_id();
		$this->bp::set_current_user( $u1 );

		$a1 = bp_activity_post_update(
			array(
				'type'    => 'activity_update',
				'user_id' => $u1,
				'content' => '<a href="https://buddypress.org">BuddyPress</a> is awesome!',
			)
		);

		friends_add_friend( $u1, $u2, true );

		$date_last_activity = date( 'Y-m-d H:i:s', bp_core_current_time( true, 'timestamp' ) );
		bp_update_user_last_activity( $u1, $date_last_activity );

		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $u1 ) );
		$request->set_query_params(
			array(
				'populate_extras' => true,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$member = $response->get_data();
		$this->assertNotEmpty( $member );

		$this->assertEquals(
			bp_rest_prepare_date_response( $date_last_activity, get_date_from_gmt( $date_last_activity ) ),
			$member['last_activity']['date']
		);
		$this->assertEquals( $member['latest_update']['id'], $a1 );
		$this->assertEquals( 1, $member['total_friend_count'] );

		$this->bp::set_current_user( $current_user );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_me_extras() {
		// Set current user.
		$current_user = get_current_user_id();
		$this->bp::set_current_user( self::$user );

		$request  = new WP_REST_Request( 'GET', $this->endpoint_url . '/me' );
		$request->set_query_params(
			array(
				'populate_extras' => true,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$me = $response->get_data();
		$this->assertNotEmpty( $me );

		$this->assertEquals( 'right now', $me['last_activity']['timediff'] );

		$this->bp::set_current_user( $current_user );
	}

	/**
	 * @group get_item
	 * @group avatar
	 */
	public function test_get_item_without_avatar() {
		$u = static::factory()->user->create();

		// Register and set member types.
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		bp_set_member_type( $u, 'foo' );
		bp_set_member_type( $u, 'bar', true );

		buddypress()->avatar->show_avatars = false;

		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $u ) );
		$response = $this->server->dispatch( $request );

		buddypress()->avatar->show_avatars = true;

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'avatar_urls', $response->get_data() );
	}

	/**
	 * @group get_item
	 * @group avatar
	 */
	function test_get_item_schema_show_avatar() {
		buddypress()->avatar->show_avatars = false;

		// Re-initialize the controller to cache-bust schemas from prior test runs.
		$GLOBALS['wp_rest_server']->override_by_default = true;
		$controller                                     = new BP_REST_Members_V1_Controller();
		$controller->register_routes();
		$GLOBALS['wp_rest_server']->override_by_default = false;

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		buddypress()->avatar->show_avatars = true;

		$this->assertArrayNotHasKey( 'avatar_urls', $properties );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_invalid_id() {
		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_member_invalid_id', $response, 404 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		$this->skipWithMultisite();

		$this->allow_user_to_manage_multisite();

		$params = array(
			'password'   => 'testpassword',
			'email'      => 'test@example.com',
			'user_login' => 'testuser',
			'name'       => 'Test User',
		);

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertEquals( 'Test User', $data['name'] );
		$this->check_add_edit_user_response( $response );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_without_permission() {
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$params = array(
			'password'   => 'testpassword',
			'email'      => 'test@example.com',
			'user_login' => 'testuser',
			'name'       => 'Test User',
		);

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_cannot_create_user', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$u = static::factory()->user->create( array(
			'email' => 'test@example.com',
			'name'  => 'User Name',
		) );

		$this->allow_user_to_manage_multisite();

		$userdata      = get_userdata( $u );
		$pw_before     = $userdata->user_pass;
		$_POST['name'] = 'New User Name';

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $u ) );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$request->set_body_params( $_POST );

		$response = $this->server->dispatch( $request );
		$this->check_add_edit_user_response( $response, true );

		$new_data = $response->get_data();
		$this->assertNotEmpty( $new_data );

		$this->assertEquals( $pw_before, $userdata->user_pass );
		$this->assertEquals( 'New User Name', $new_data['name'] );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_not_logged_in() {
		$u = static::factory()->user->create();

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $u ) );
		$request->set_param( 'username', 'test_json_user' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_invalid_id() {
		$this->bp::set_current_user( self::$user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$request->set_body_params( array(
			'id'       => '156',
			'username' => 'test_user',
			'password' => 'reallysimplepassword',
			'email'    => 'reallydumbguy@example.com',
		) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_member_invalid_id', $response, 404 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_without_permission() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $u2 ) );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$request->set_body_params( $_POST );

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * This test is there to make sure we are handling the `types` parameter
	 * that was used before BP REST 0.3.0 and BuddyPress 7.0.0.
	 *
	 * @group update_item
	 */
	public function test_update_item_types() {
		$this->skipWithMultisite();

		$u = static::factory()->user->create( array(
			'email' => 'member@type.com',
			'name'  => 'User Name',
		) );

		$this->bp::set_current_user( self::$user );
		bp_register_member_type( 'membertypeone' );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $u ) );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$request->set_body_params( array(
			'types' => 'membertypeone',
		) );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 'membertypeone', reset( $data['member_types'] ) );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_member_type_as_regular_user() {
		$u = static::factory()->user->create( array(
			'email' => 'member@type.com',
			'name'  => 'User Name',
		) );

		$this->bp::set_current_user( $u );
		bp_register_member_type( 'membertypeone' );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $u ) );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$request->set_body_params( array(
			'member_type' => 'membertypeone',
		) );

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_member_type_as_admin_user() {
		$this->bp::set_current_user( self::$user );
		bp_register_member_type( 'membertypeone' );
		bp_register_member_type( 'membertypetwo' );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', self::$user ) );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$request->set_body_params( array(
			'member_type' => 'membertypeone,membertypetwo',
		) );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$member_types = array( 'membertypeone' ,'membertypetwo' );

		$this->assertSame( $data['member_types'], $member_types );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$this->skipWithMultisite();

		$u = static::factory()->user->create( array( 'display_name' => 'Deleted User' ) );

		$this->allow_user_to_manage_multisite();

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $u ) );
		$request->set_param( 'force', true );
		$request->set_param( 'reassign', false );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertTrue( $data['deleted'] );
		$this->assertEquals( 'Deleted User', $data['previous']['name'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_invalid_id() {
		$this->bp::set_current_user( self::$user );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'force', true );
		$request->set_param( 'reassign', false );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_member_invalid_id', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_not_logged_in() {
		$u = static::factory()->user->create();

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $u ) );
		$request->set_param( 'force', true );
		$request->set_param( 'reassign', false );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_without_permission() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $u2 ) );
		$request->set_param( 'force', true );
		$request->set_param( 'reassign', false );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_current_item() {
		$u = static::factory()->user->create( array( 'display_name' => 'Deleted User' ) );
		$current_user = get_current_user_id();
		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/me' );
		$request->set_param( 'force', true );
		$request->set_param( 'reassign', false );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertTrue( $data['deleted'] );
		$this->assertEquals( 'Deleted User', $data['previous']['name'] );

		$this->bp::set_current_user( $u );
	}

	public function test_prepare_item() {
		$this->bp::set_current_user( self::$user );

		$request = new WP_REST_Request();
		$request->set_param( 'context', 'view' );
		$user = get_user_by( 'id', get_current_user_id() );
		$data = $this->endpoint->prepare_item_for_response( $user, $request );

		$this->check_get_user_response( $data, 'view' );
	}

	protected function check_get_user_response( $response, $context = 'view' ) {
		$data = $response->get_data();
		$user = get_userdata( $data['id'] );

		$this->check_user_data( $user, $data, $context );
	}

	protected function check_add_edit_user_response( $response, $update = false ) {
		if ( $update ) {
			$this->assertEquals( 200, $response->get_status() );
		} else {
			$this->assertEquals( 201, $response->get_status() );
		}

		$data = $response->get_data();
		$this->check_user_data( get_userdata( $data['id'] ), $data, 'edit' );
	}

	protected function check_user_data( $user, $data, $context ) {
		$this->assertEquals( $user->ID, $data['id'] );
		$this->assertEquals( $user->display_name, $data['name'] );
		$this->assertEquals( $user->user_login, $data['user_login'] );
		$this->assertArrayHasKey( 'avatar_urls', $data );
		$this->assertArrayHasKey( 'thumb', $data['avatar_urls'] );
		$this->assertArrayHasKey( 'full', $data['avatar_urls'] );
		$this->assertArrayHasKey( 'member_types', $data );
		$this->assertArrayHasKey( 'xprofile', $data );
		$this->assertArrayHasKey( 'friendship_status', $data );
		$this->assertArrayHasKey( 'friendship_status_slug', $data );
		$this->assertEquals(
			bp_members_get_user_url( $data['id'] ),
			$data['link']
		);

		if ( 'edit' === $context ) {
			$this->assertEquals( (array) array_keys( $user->allcaps ), $data['capabilities'] );
			$this->assertEquals( (array) array_keys( $user->caps ), $data['extra_capabilities'] );
			$this->assertEquals( (array) array_values( $user->roles ), $data['roles'] );
			$this->assertEquals(
				bp_rest_prepare_date_response( $user->user_registered, get_date_from_gmt( $user->user_registered ) ),
				$data['registered_date']
			);
			$this->assertEquals( bp_rest_prepare_date_response( $user->user_registered ), $data['registered_date_gmt'] );
		} else {
			$this->assertArrayNotHasKey( 'roles', $data );
			$this->assertArrayNotHasKey( 'capabilities', $data );
			$this->assertArrayNotHasKey( 'extra_capabilities', $data );
		}
	}

	protected function allow_user_to_manage_multisite() {
		$this->bp::set_current_user( self::$user );

		if ( is_multisite() ) {
			update_site_option( 'site_admins', array( wp_get_current_user()->user_login ) );
		}
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 20, count( $properties ) );
		$this->assertArrayHasKey( 'avatar_urls', $properties );
		$this->assertArrayHasKey( 'capabilities', $properties );
		$this->assertArrayHasKey( 'extra_capabilities', $properties );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'link', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'mention_name', $properties );
		$this->assertArrayHasKey( 'registered_date', $properties );
		$this->assertArrayHasKey( 'registered_date_gmt', $properties );
		$this->assertArrayHasKey( 'registered_since', $properties );
		$this->assertArrayHasKey( 'password', $properties );
		$this->assertArrayHasKey( 'roles', $properties );
		$this->assertArrayHasKey( 'member_types', $properties );
		$this->assertArrayHasKey( 'xprofile', $properties );
		$this->assertArrayHasKey( 'friendship_status', $properties );
		$this->assertArrayHasKey( 'friendship_status_slug', $properties );
		$this->assertArrayHasKey( 'last_activity', $properties );
		$this->assertArrayHasKey( 'latest_update', $properties );
		$this->assertArrayHasKey( 'total_friend_count', $properties );
	}

	/**
	 * @group get_item
	 * @group item_schema
	 */
	public function test_get_item_schema_member_types_enum() {
		$expected = array( 'foo', 'bar' );

		foreach ( $expected as $type ) {
			bp_register_member_type( $type );
		}

		// Re-initialize the controller to cache-bust schemas from prior test runs.
		$GLOBALS['wp_rest_server']->override_by_default = true;
		$controller                                     = new BP_REST_Members_V1_Controller();
		$controller->register_routes();
		$GLOBALS['wp_rest_server']->override_by_default = false;

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertArrayHasKey( 'member_types', $properties );
		$this->assertEquals( array_values( $properties['member_types']['enum'] ), $expected );
	}

	public function test_context_param() {
		// Collection.
		$request  = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );

		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d', self::$user ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}

	public function update_additional_field( $value, $data, $attribute ) {
		return bp_update_user_meta( $data->id, '_' . $attribute, $value );
	}

	public function get_additional_field( $data, $attribute )  {
		return bp_get_user_meta( $data['id'], '_' . $attribute, true );
	}

	/**
	 * @group additional_fields
	 */
	public function test_additional_fields() {
		$this->skipWithMultisite();

		$registered_fields = $GLOBALS['wp_rest_additional_fields'];

		bp_rest_register_field( 'members', 'foo_field', array(
			'get_callback'    => array( $this, 'get_additional_field' ),
			'update_callback' => array( $this, 'update_additional_field' ),
			'schema'          => array(
				'description' => 'Members single item Meta Field',
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		) );

		$this->allow_user_to_manage_multisite();
		$expected = 'bar_value';

		$params = array(
			'password'   => 'testpassword',
			'email'      => 'test@example.com',
			'user_login' => 'testuser',
			'name'       => 'Test User',
			'foo_field'  => $expected,
		);

		// POST
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$create_data = $response->get_data();
		$this->assertTrue( $expected === $create_data['foo_field'] );

		// GET
		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $create_data['id'] ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$get_data = $response->get_data();
		$this->assertTrue( $expected === $get_data['foo_field'] );

		$GLOBALS['wp_rest_additional_fields'] = $registered_fields;
	}

	/**
	 * @group additional_fields
	 */
	public function test_update_additional_fields() {
		$registered_fields = $GLOBALS['wp_rest_additional_fields'];

		bp_rest_register_field( 'members', 'bar_field', array(
			'get_callback'    => array( $this, 'get_additional_field' ),
			'update_callback' => array( $this, 'update_additional_field' ),
			'schema'          => array(
				'description' => 'Members single item Meta Field',
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		) );

		$u = static::factory()->user->create( array(
			'email' => 'test@example.com',
			'name'  => 'User Name',
		) );
		$expected = 'foo_value';

		$this->allow_user_to_manage_multisite();

		// PUT
		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $u ) );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$request->set_body_params( array( 'bar_field' => 'foo_value' ) );

		$response = $this->server->dispatch( $request );

		$update_data = $response->get_data();
		$this->assertTrue( $expected === $update_data['bar_field'] );

		$GLOBALS['wp_rest_additional_fields'] = $registered_fields;
	}
}
