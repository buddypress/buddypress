<?php
/**
 * Signup Controller Tests.
 *
 * @group signup
 * @group signups
 * @group members
 */
class BP_Test_REST_Signup_Endpoint extends BP_Test_REST_Controller_Testcase {
	protected $signup_id;
	protected $signup_allowed;
	protected $handle     = 'signup';
	protected $controller = 'BP_REST_Signup_Endpoint';

	public function set_up() {
		if ( is_multisite() ) {
			$this->signup_allowed = get_site_option( 'registration' );
			update_site_option( 'registration', 'all' );
			bp_update_option( 'users_can_register', 1 );
		} else {
			$this->signup_allowed = bp_get_option( 'users_can_register' );
			bp_update_option( 'users_can_register', 1 );
		}

		parent::set_up();

		$this->signup_id = $this->create_signup();
	}

	public function tear_down() {
		if ( is_multisite() ) {
			update_site_option( 'registration', $this->signup_allowed );
		} else {
			bp_update_option( 'users_can_register', $this->signup_allowed );
		}

		parent::tear_down();
	}

	public function test_register_routes() {
		$routes = $this->server->get_routes();

		// Main.
		$this->assertArrayHasKey( $this->endpoint_url, $routes );
		$this->assertCount( 2, $routes[ $this->endpoint_url ] );

		// Single.
		$this->assertArrayHasKey( $this->endpoint_url . '/(?P<id>[\w-]+)', $routes );
		$this->assertCount( 2, $routes[ $this->endpoint_url . '/(?P<id>[\w-]+)' ] );
		$this->assertCount( 1, $routes[ $this->endpoint_url . '/activate/(?P<activation_key>[\w-]+)' ] );
		$this->assertCount( 1, $routes[ $this->endpoint_url . '/resend' ] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$this->bp::set_current_user( $this->user );

		$s1     = $this->create_signup();
		$signup = $this->endpoint->get_signup_object( $s1 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_query_params( array( 'include' => $s1 ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->check_signup_data( $signup, $all_data[0] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_paginated_items() {
		$this->bp::set_current_user( $this->user );

		$s1 = $this->create_signup();
		$s2 = $this->create_signup();
		$s3 = $this->create_signup();
		$s4 = $this->create_signup();

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params(
			array(
				'include' => array( $s1, $s2, $s3, $s4 ),
				'number'  => 2,
			)
		);

		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$headers = $response->get_headers();

		$this->assertEquals( 4, $headers['X-WP-Total'] );
		$this->assertEquals( 2, $headers['X-WP-TotalPages'] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_user_not_logged_in() {
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_unauthorized_user() {
		$u = static::factory()->user->create();

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$this->bp::set_current_user( $this->user );

		$signup = $this->endpoint->get_signup_object( $this->signup_id );
		$this->assertEquals( $this->signup_id, $signup->id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $this->signup_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->check_signup_data( $signup, $all_data );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_invalid_signup_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_user_not_logged_in() {
		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%s', $this->signup_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_unauthorized_user() {
		$u = static::factory()->user->create();

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%s', $this->signup_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );

		$params = $this->set_signup_data();
		$request->set_body_params( $params );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$signup = $response->get_data();

		$this->assertSame( $signup['user_login'], $params['user_login'] );
		$this->assertSame( $signup['user_email'], $params['user_email'] );
		$this->assertTrue( ! isset( $signup['activation_key'] ) );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_with_signup_fields() {
		$g1 = $this->bp::factory()->xprofile_group->create();

		$f1 = $this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'type'           => 'textbox',
				'name'           => 'field1',
			)
		);

		$f2 = $this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'type'           => 'checkbox',
				'name'           => 'field2',
			)
		);

		bp_xprofile_update_field_meta( $f1, 'signup_position', 2 );
		bp_xprofile_update_field_meta( $f2, 'signup_position', 3 );

		$this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'parent_id'      => $f2,
				'type'           => 'option',
				'name'           => 'Option 1',
			)
		);

		$this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'parent_id'      => $f2,
				'type'           => 'option',
				'name'           => 'Option 2',
			)
		);

		$fullname_field_id = bp_xprofile_fullname_field_id();

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$params  = $this->set_signup_data(
			array(
				'signup_field_data' => array(
					array(
						'field_id'   => $f1,
						'value'      => 'Field 1 Value',
						'visibility' => 'public',
					),
					array(
						'field_id'   => $f2,
						'value'      => 'Option 2, Option 1,',
						'visibility' => 'public',
					),
					array(
						'field_id'   => $fullname_field_id,
						'value'      => 'New User',
						'visibility' => 'public',
					),
				),
			)
		);
		$request->set_body_params( $params );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$signup = $response->get_data();

		$this->assertSame( $signup['user_email'], $params['user_email'] );

		// Check the textbox field.
		$this->assertSame( $signup['meta'][ 'field_' . $f1 ], $params['signup_field_data'][0]['value'] );
		$this->assertSame( $signup['meta'][ 'field_' . $f1 . '_visibility' ], $params['signup_field_data'][0]['visibility'] );

		// Check the checkbox field.
		$this->assertSame( $signup['meta'][ 'field_' . $f2 ], array_map( 'trim', explode( ', ', $params['signup_field_data'][1]['value'] ) ) );
		$this->assertSame( $signup['meta'][ 'field_' . $f2 . '_visibility' ], $params['signup_field_data'][1]['visibility'] );

		$field_ids = wp_parse_id_list( explode( ',', $signup['meta']['profile_field_ids'] ) );

		$this->assertCount( 3, $field_ids );
		$this->assertContains( $fullname_field_id, $field_ids );
		$this->assertContains( $f1, $field_ids );
		$this->assertContains( $f2, $field_ids );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_without_the_required_field_name_field() {
		$g1 = $this->bp::factory()->xprofile_group->create();

		$f1 = $this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'type'           => 'textbox',
				'name'           => 'field1',
			)
		);

		$f2 = $this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'type'           => 'checkbox',
				'name'           => 'field2',
			)
		);

		bp_xprofile_update_field_meta( $f1, 'signup_position', 2 );
		bp_xprofile_update_field_meta( $f2, 'signup_position', 3 );

		$this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'parent_id'      => $f2,
				'type'           => 'option',
				'name'           => 'Option 1',
			)
		);

		$this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'parent_id'      => $f2,
				'type'           => 'option',
				'name'           => 'Option 2',
			)
		);

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$params = $this->set_signup_data(
			array(
				'signup_field_data' => array(
					array(
						'field_id'   => $f1,
						'value'      => 'Field 1 Value',
						'visibility' => 'public',
					),
					array(
						'field_id'   => $f2,
						'value'      => 'Option 1, Option 2',
						'visibility' => 'public',
					),
				),
			)
		);

		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_signup_field_required', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_without_a_custom_required_field_name_field() {
		$g1 = $this->bp::factory()->xprofile_group->create();

		$f1 = $this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'type'           => 'textbox',
				'name'           => 'field1',
				'is_required'    => true,
			)
		);

		$f2 = $this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'type'           => 'checkbox',
				'name'           => 'field2',
			)
		);

		bp_xprofile_update_field_meta( $f1, 'signup_position', 2 );
		bp_xprofile_update_field_meta( $f2, 'signup_position', 3 );

		$this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'parent_id'      => $f2,
				'type'           => 'option',
				'name'           => 'Option 1',
			)
		);

		$this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'parent_id'      => $f2,
				'type'           => 'option',
				'name'           => 'Option 2',
			)
		);

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$params = $this->set_signup_data(
			array(
				'signup_field_data' => array(
					// Missing the required field.
					array(
						'field_id'   => $f2,
						'value'      => 'Option 1, Option 2',
						'visibility' => 'public',
					),
					array(
						'field_id'   => bp_xprofile_fullname_field_id(),
						'value'      => 'Test User',
						'visibility' => 'public',
					),
				),
			)
		);

		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_signup_field_required', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_a_custom_required_field_name_field_value_missing() {
		$g1 = $this->bp::factory()->xprofile_group->create();

		$f1 = $this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'type'           => 'textbox',
				'name'           => 'field1',
				'is_required'    => true,
			)
		);

		$f2 = $this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'type'           => 'checkbox',
				'name'           => 'field2',
			)
		);

		bp_xprofile_update_field_meta( $f1, 'signup_position', 2 );
		bp_xprofile_update_field_meta( $f2, 'signup_position', 3 );

		$this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'parent_id'      => $f2,
				'type'           => 'option',
				'name'           => 'Option 1',
			)
		);

		$this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g1,
				'parent_id'      => $f2,
				'type'           => 'option',
				'name'           => 'Option 2',
			)
		);

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );

		$params = $this->set_signup_data(
			array(
				'signup_field_data' => array(
					array(
						'field_id' => $f1,
						'value'    => '', // <-- missing value.
					),
					array(
						'field_id' => $f2,
						'value'    => 'Option 1, Option 2',
					),
					array(
						'field_id' => bp_xprofile_fullname_field_id(),
						'value'    => 'Test User',
					),
				),
			)
		);

		$request->set_body_params( $params );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_signup_field_required', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_without_the_default_required_field_value() {
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$data   = array(
			array(
				'field_id'   => bp_xprofile_fullname_field_id(),
				'value'      => '',
				'visibility' => 'public',
			),
		);
		$params = $this->set_signup_data( array( 'signup_field_data' => $data ) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_signup_field_required', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_without_the_signup_field_data_param() {
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$params = $this->set_signup_data( array( 'signup_field_data' => array() ) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_unauthorized_password() {
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$params = $this->set_signup_data( array( 'password' => '\\Antislash' ) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_user_invalid_password', $response, 400 );
	}

	/**
	 * @group activate_item
	 */
	public function test_update_item() {
		$s1     = $this->create_signup();
		$signup = new BP_Signup( $s1 );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/activate/%s', $signup->activation_key ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->check_signup_data( $signup, $all_data );
	}

	/**
	 * @group activate_item
	 */
	public function test_update_item_invalid_invalid_activation_key() {
		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/activate/%s', 'randomkey' ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_activation_key', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$this->bp::set_current_user( $this->user );

		$signup = $this->endpoint->get_signup_object( $this->signup_id );
		$this->assertEquals( $this->signup_id, $signup->id );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->signup_id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$deleted = $response->get_data();

		$this->assertTrue( $deleted['deleted'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_invalid_signup_id() {
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
		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->signup_id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_unauthorized_user() {
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->signup_id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group resend_item
	 */
	public function test_resend_activation_email() {
		$signup_id = $this->create_signup();

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/resend' );
		$request->set_param( 'id', $signup_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->assertTrue( $all_data['sent'] );
	}

	/**
	 * @group resend_item
	 */
	public function test_resend_acivation_email_to_active_signup() {
		$signup_id = $this->create_signup();
		$signup    = new BP_Signup( $signup_id );

		bp_core_activate_signup( $signup->activation_key );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/resend' );
		$request->set_param( 'id', $signup_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		if ( is_multisite() ) {
			$this->assertEquals( 200, $response->get_status() );

			$all_data = $response->get_data();

			$this->assertTrue( $all_data['sent'] );
		} else {
			$this->assertErrorResponse( 'bp_rest_signup_resend_activation_email_fail', $response, 500 );
		}
	}

	/**
	 * @group resend_item
	 */
	public function test_resend_activation_email_invalid_signup_id() {
		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/resend' );
		$request->set_param( 'id', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	public function test_prepare_item() {
		$this->bp::set_current_user( $this->user );

		$signup = $this->endpoint->get_signup_object( $this->signup_id );
		$this->assertEquals( $this->signup_id, $signup->id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $this->signup_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->check_signup_data( $signup, $all_data );
	}

	protected function set_signup_data( $args = array() ) {
		return wp_parse_args(
			$args,
			array(
				'user_login'        => 'newnuser',
				'user_email'        => 'new.user@example.com',
				'password'          => 'password',
				'signup_field_data' => array(
					array(
						'field_id'   => bp_xprofile_fullname_field_id(),
						'value'      => 'New User',
						'visibility' => 'public',
					),
				),
			)
		);
	}

	protected function create_signup() {
		return BP_Signup::add(
			array(
				'user_login'     => 'user' . wp_rand( 1, 20 ),
				'user_email'     => sprintf( 'user%d@example.com', wp_rand( 1, 20 ) ),
				'registered'     => bp_core_current_time(),
				'activation_key' => wp_generate_password( 32, false ),
				'meta'           => array(
					'field_1' => 'Foo Bar',
				),
			)
		);
	}

	protected function check_signup_data( $signup, $data ) {
		$this->assertEquals( $signup->id, $data['id'] );
		$this->assertEquals( $signup->user_login, $data['user_login'] );
		$this->assertEquals(
			bp_rest_prepare_date_response( $signup->registered, get_date_from_gmt( $signup->registered ) ),
			$data['registered']
		);
		$this->assertEquals( bp_rest_prepare_date_response( $signup->registered ), $data['registered_gmt'] );
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d', $this->signup_id ) );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		if ( is_multisite() ) {
			$this->assertEquals( 15, count( $properties ) );
		} else {
			$this->assertEquals( 11, count( $properties ) );
		}

		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'user_login', $properties );
		$this->assertArrayHasKey( 'registered', $properties );
		$this->assertArrayHasKey( 'registered_gmt', $properties );
		$this->assertArrayHasKey( 'activation_key', $properties );
		$this->assertArrayHasKey( 'user_email', $properties );
		$this->assertArrayHasKey( 'date_sent', $properties );
		$this->assertArrayHasKey( 'date_sent_gmt', $properties );
		$this->assertArrayHasKey( 'count_sent', $properties );
		$this->assertArrayHasKey( 'meta', $properties );

		if ( is_multisite() ) {
			$this->assertArrayHasKey( 'site_language', $properties );
			$this->assertArrayHasKey( 'site_public', $properties );
			$this->assertArrayHasKey( 'site_title', $properties );
			$this->assertArrayHasKey( 'site_name', $properties );
		}
	}

	public function test_context_param() {
		// Collection.
		$request  = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );

		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d', $this->signup_id ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}
}
