<?php
/**
 * XProfile Data Controller Tests.
 *
 * @group xprofile
 * @group xprofile-data
 */
class BP_Test_REST_XProfile_Data_Controller extends WP_Test_REST_Controller_Testcase {
	protected $endpoint;
	protected $bp;
	protected $endpoint_url;
	protected $user;
	protected $server;
	protected $group_id;
	protected $field;
	protected $field_id;

	public function set_up() {
		parent::set_up();

		$this->endpoint     = new BP_REST_XProfile_Data_Controller();
		$this->field        = new BP_REST_XProfile_Fields_Controller();
		$this->bp           = new BP_UnitTestCase();
		$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/' . buddypress()->profile->id . '/';
		$this->group_id     = $this->bp::factory()->xprofile_group->create();
		$this->field_id     = $this->bp::factory()->xprofile_field->create( array( 'field_group_id' => $this->group_id ) );

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

		$endpoint = $this->endpoint_url . '(?P<field_id>[\d]+)/data/(?P<user_id>[\d]+)';

		$this->assertArrayHasKey( $endpoint, $routes );
		$this->assertCount( 3, $routes[ $endpoint ] );
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
		$this->bp::set_current_user( $this->user );
		xprofile_set_field_data( $this->field_id, $this->user, 'foo' );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '%d/data/%d', $this->field_id, $this->user ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );
		$this->assertNotInstanceOf( 'WP_Error', $response );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->assertEquals( $all_data['value']['unserialized'], array( 'foo' ) );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		xprofile_set_field_data( $this->field_id, $this->user, 'foo' );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '%d/data/%d', $this->field_id, $this->user ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_hidden_for_user() {
		$f = $this->bp::factory()->xprofile_field->create( array( 'field_group_id' => $this->group_id ) );
		xprofile_set_field_data( $f, $this->user, 'bar' );
		xprofile_set_field_visibility_level( $f, $this->user, 'adminsonly' );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '%d/data/%d', $f, $this->user ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $this->field_id, $this->user ) );
		$request->add_header( 'content-type', 'application/json' );
		$params = $this->set_field_data();
		$request->set_param( 'context', 'edit' );
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->check_create_field_response( $response );
	}

	/**
	 * @group update_item
	 */
	public function test_update_checkbox() {
		$field_id = $this->bp::factory()->xprofile_field->create(
			array(
				'type'           => 'checkbox',
				'field_group_id' => $this->group_id,
			)
		);

		xprofile_insert_field(
			array(
				'field_group_id' => $this->group_id,
				'parent_id'      => $field_id,
				'type'           => 'option',
				'name'           => 'Field',
			)
		);

		xprofile_insert_field(
			array(
				'field_group_id' => $this->group_id,
				'parent_id'      => $field_id,
				'type'           => 'option',
				'name'           => 'Value',
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $field_id, $this->user ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_field_data();
		$request->set_param( 'context', 'edit' );
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertNotInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertEquals( $data['value']['unserialized'], array( 'Field', 'Value' ) );
	}

	/**
	 * @group update_item
	 */
	public function test_update_multiselectbox() {
		$field_id = $this->bp::factory()->xprofile_field->create(
			array(
				'type'           => 'multiselectbox',
				'field_group_id' => $this->group_id,
			)
		);

		xprofile_insert_field(
			array(
				'field_group_id' => $this->group_id,
				'parent_id'      => $field_id,
				'type'           => 'option',
				'name'           => 'Option 1',
			)
		);

		xprofile_insert_field(
			array(
				'field_group_id' => $this->group_id,
				'parent_id'      => $field_id,
				'type'           => 'option',
				'name'           => 'Option 2',
			)
		);

		xprofile_insert_field(
			array(
				'field_group_id' => $this->group_id,
				'parent_id'      => $field_id,
				'type'           => 'option',
				'name'           => 'Option 3',
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $field_id, $this->user ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_field_data( array( 'value' => 'Option 1,Option 2' ) );
		$request->set_param( 'context', 'edit' );
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertNotInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertEquals( $data['value']['unserialized'], array( 'Option 1', 'Option 2' ) );
	}

	/**
	 * @group update_item
	 */
	public function test_update_multiselectbox_with_invalid_item() {
		$field_id = $this->bp::factory()->xprofile_field->create(
			array(
				'type'           => 'multiselectbox',
				'field_group_id' => $this->group_id,
			)
		);

		xprofile_insert_field(
			array(
				'field_group_id' => $this->group_id,
				'parent_id'      => $field_id,
				'type'           => 'option',
				'name'           => 'Option 1',
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $field_id, $this->user ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_field_data( array( 'value' => 'option 1' ) );
		$request->set_param( 'context', 'edit' );
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_user_cannot_save_xprofile_data', $response, 500 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_multiselectbox_with_empty_value() {
		$field_id = $this->bp::factory()->xprofile_field->create(
			array(
				'type'           => 'multiselectbox',
				'field_group_id' => $this->group_id,
			)
		);

		xprofile_insert_field(
			array(
				'field_group_id' => $this->group_id,
				'parent_id'      => $field_id,
				'type'           => 'option',
				'name'           => 'Option 1',
			)
		);

		xprofile_set_field_data( $field_id, $this->user, 'Option 1' );

		$this->bp::set_current_user( $this->user );

		// Clear selected options.
		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $field_id, $this->user ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_field_data( array( 'value' => '' ) );
		$request->set_param( 'context', 'edit' );
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertEquals( $data['value']['unserialized'], array() );
	}

	/**
	 * @group update_item
	 *
	 * @ticket https://buddypress.trac.wordpress.org/ticket/9127
	 */
	public function test_update_multiselectbox_with_apostrophe_value() {
		$field_id = $this->bp::factory()->xprofile_field->create(
			array(
				'type'           => 'multiselectbox',
				'field_group_id' => $this->group_id,
			)
		);
		xprofile_insert_field(
			array(
				'field_group_id' => $this->group_id,
				'parent_id'      => $field_id,
				'type'           => 'option',
				'name'           => 'Option 1',
			)
		);

		xprofile_insert_field(
			array(
				'field_group_id' => $this->group_id,
				'parent_id'      => $field_id,
				'type'           => 'option',
				'name'           => "I don't travel often",
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $field_id, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_field_data( array( 'value' => "I don\'t travel often" ) );

		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertNotInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertEquals( $data['value']['unserialized'], array( "I don\'t travel often" ) );
		$this->assertEquals( $data['value']['raw'], "a:1:{i:0;s:21:\"I don\\'t travel often\";}" );
	}

	/**
	 * @group update_item
	 *
	 * @ticket https://buddypress.trac.wordpress.org/ticket/9127
	 */
	public function test_update_checkbox_with_apostrophe_value() {
		$field_id = $this->bp::factory()->xprofile_field->create(
			array(
				'type'           => 'checkbox',
				'field_group_id' => $this->group_id,
			)
		);

		$this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $this->group_id,
				'parent_id'      => $field_id,
				'type'           => 'option',
				'name'           => 'Option 1',
			)
		);

		$this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $this->group_id,
				'parent_id'      => $field_id,
				'type'           => 'option',
				'name'           => "I don't travel often",
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $field_id, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_field_data( array( 'value' => "I don\'t travel often" ) );

		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertNotInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertEquals( $data['value']['unserialized'], array( "I don\'t travel often" ) );
		$this->assertEquals( $data['value']['raw'], "a:1:{i:0;s:21:\"I don\\'t travel often\";}" );
	}

	/**
	 * @group update_item
	 */
	public function test_update_textbox() {
		$field_id = $this->bp::factory()->xprofile_field->create(
			array(
				'type'           => 'textbox',
				'field_group_id' => $this->group_id,
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $field_id, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_field_data( array( 'value' => 'textbox field' ) );
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertNotInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertEquals( $data['value']['unserialized'][0], $params['value'] );
		$this->assertEquals( $data['value']['raw'], 'textbox field' );
	}

	/**
	 * @group update_item
	 *
	 * @ticket https://buddypress.trac.wordpress.org/ticket/9127
	 */
	public function test_update_textbox_with_apostrophe_value() {
		$field_id = $this->bp::factory()->xprofile_field->create(
			array(
				'type'           => 'textbox',
				'field_group_id' => $this->group_id,
				'value'          => 'textbox field',
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $field_id, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_field_data( array( 'value' => "I don't travel often" ) );
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->assertEquals( $data['value']['raw'], "I don't travel often" );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $field_id, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_field_data( array( 'value' => "I don\\'t travel often" ) );
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->assertEquals( $data['value']['raw'], "I don't travel often" );
	}

	/**
	 * @group update_item
	 */
	public function test_update_selectbox() {
		$field_id = $this->bp::factory()->xprofile_field->create(
			array(
				'type'           => 'selectbox',
				'name'           => 'Test Field Name',
				'field_group_id' => $this->group_id,
			)
		);

		$this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $this->group_id,
				'parent_id'      => $field_id,
				'type'           => 'option',
				'name'           => 'select box',
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $field_id, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_field_data( array( 'value' => 'select box' ) );
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertNotInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertEquals( $data['value']['unserialized'][0], 'select box' );
		$this->assertEquals( $data['value']['raw'], 'select box' );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_not_logged_in() {
		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $this->field_id, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$request->set_body( wp_json_encode( $this->set_field_data() ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_without_permission() {
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $this->field_id, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$request->set_body( wp_json_encode( $this->set_field_data() ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_without_permission_with_param() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $this->field_id, $u2 ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$request->set_body( wp_json_encode( $this->set_field_data() ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_invalid_field_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_field_data();
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_invalid_member_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $this->field_id, REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_field_data();
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		$this->markTestSkipped();
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$g = $this->bp::factory()->xprofile_group->create();
		$f = $this->bp::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );

		xprofile_set_field_data( $f, $this->user, 'foo' );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '%d/data/%d', $f, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertNotInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertTrue( 'foo' === $data['previous']['value']['raw'] );

		$field_data = $this->endpoint->get_xprofile_field_data_object( $data['previous']['field_id'], $data['previous']['user_id'] );

		$this->assertEmpty( $field_data->value );
		$this->assertEmpty( $field_data->last_updated );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_field_owner_can_delete() {
		$u = $this->bp::factory()->user->create();
		$g = $this->bp::factory()->xprofile_group->create();
		$f = $this->bp::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g,
			)
		);

		xprofile_set_field_data( $f, $u, 'bar' );

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '%d/data/%d', $f, $u ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertNotInstanceOf( 'WP_Error', $response );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertTrue( 'bar' === $data['previous']['value']['raw'] );

		$field_data = $this->endpoint->get_xprofile_field_data_object( $data['previous']['field_id'], $data['previous']['user_id'] );

		$this->assertEmpty( $field_data->value );
		$this->assertEmpty( $field_data->last_updated );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_invalid_field_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '%d/data/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_invalid_user_id() {
		$this->bp::set_current_user( $this->user );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '%d/data/%d', $this->field_id, REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_not_logged_in() {
		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '%d/data/%d', $this->field_id, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_without_permission() {
		$u = static::factory()->user->create();
		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '%d/data/%d', $this->field_id, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group prepare_item
	 */
	public function test_prepare_item() {
		$this->bp::set_current_user( $this->user );
		xprofile_set_field_data( $this->field_id, $this->user, 'foo' );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '%d/data/%d', $this->field_id, $this->user ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );
		$this->assertNotInstanceOf( 'WP_Error', $response );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->assertEquals( $all_data['value']['unserialized'], array( 'foo' ) );
	}

	/**
	 * @group additional_fields
	 */
	public function test_additional_fields() {
		bp_rest_register_field(
			'xprofile',
			'foo_metadata_key',
			array(
				'get_callback'    => array( $this, 'get_additional_field' ),
				'update_callback' => array( $this, 'update_additional_field' ),
				'schema'          => array(
					'description' => 'xProfile data Meta Field',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
			'data'
		);

		$this->bp::set_current_user( $this->user );
		$expected = 'bar_metadata_value';

		// POST
		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/data/%d', $this->field_id, $this->user ) );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_field_data(
			array(
				'foo_metadata_key' => $expected,
			)
		);

		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$create_data = $response->get_data();
		$this->assertNotEmpty( $create_data );
		$this->assertTrue( $expected === $create_data['foo_metadata_key'] );
	}

	public function update_additional_field( $value, $data, $attribute ) {
		return bp_xprofile_update_meta( $data->id, 'data', '_' . $attribute, $value );
	}

	public function get_additional_field( $data, $attribute ) {
		return bp_xprofile_get_meta( $data['id'], 'data', '_' . $attribute );
	}

	protected function check_create_field_response( $response ) {
		$this->assertNotInstanceOf( 'WP_Error', $response );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$field_data = $this->endpoint->get_xprofile_field_data_object( $data['field_id'], $data['user_id'] );
		$this->check_field_data( $field_data, $data );
	}

	protected function set_field_data( $args = array() ) {
		return wp_parse_args(
			$args,
			array(
				'value' => 'Field,Value',
			)
		);
	}

	protected function check_field_data( $field_data, $data ) {
		$this->assertEquals( $field_data->field_id, $data['field_id'] );
		$this->assertEquals( $field_data->user_id, $data['user_id'] );
		$this->assertEquals( (array) $field_data->value, $data['value']['unserialized'] );
		$this->assertEquals(
			bp_rest_prepare_date_response( $field_data->last_updated, get_date_from_gmt( $field_data->last_updated ) ),
			$data['last_updated']
		);
		$this->assertEquals( bp_rest_prepare_date_response( $field_data->last_updated ), $data['last_updated_gmt'] );
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '%d/data/%d', $this->field_id, $this->user ) );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 7, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'field_id', $properties );
		$this->assertArrayHasKey( 'user_id', $properties );
		$this->assertArrayHasKey( 'value', $properties );
		$this->assertArrayHasKey( 'last_updated', $properties );
		$this->assertArrayHasKey( 'last_updated_gmt', $properties );
	}

	public function test_context_param() {
		$this->markTestSkipped();
	}
}
