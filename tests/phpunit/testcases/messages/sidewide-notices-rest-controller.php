<?php
/**
 * Sitewide Notices Controller Tests.
 *
 * @group notices
 * @group messages
 */
class BP_Test_REST_Sitewide_Notices_Controller extends WP_Test_REST_Controller_Testcase {
	protected $endpoint;
	protected $bp;
	protected $endpoint_url;
	protected $user;
	protected $server;
	protected $last_inserted_notice_id;

	public function set_up() {
		parent::set_up();

		$this->endpoint     = new BP_REST_Sitewide_Notices_Controller();
		$this->bp           = new BP_UnitTestCase();
		$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/sitewide-notices';
		$this->user         = static::factory()->user->create(
			array(
				'role'       => 'administrator',
				'user_email' => 'admin@example.com',
			)
		);

		if ( ! $this->server ) {
			$this->server = rest_get_server();
		}

		add_action( 'messages_notice_before_save', array( $this, 'add_filter_update_last_active_query' ), 10, 0 );
		add_action( 'messages_notice_after_save', array( $this, 'set_last_inserted_notice_id' ) );
	}

	public function tear_down() {
		remove_action( 'messages_notice_before_save', array( $this, 'add_filter_update_last_active_query' ) );
		remove_action( 'messages_notice_after_save', array( $this, 'set_last_inserted_notice_id' ) );

		parent::tear_down();
	}

	public function catch_inserted_id( $query ) {
		preg_match( '/SET is_active = 0 WHERE id != (.*)/', $query, $matches );
		if ( isset( $matches[1] ) && $matches[1] ) {
			$this->last_inserted_notice_id = (int) $matches[1];
		}

		return $query;
	}

	public function add_filter_update_last_active_query() {
		add_filter( 'query', array( $this, 'catch_inserted_id' ) );
	}

	public function set_last_inserted_notice_id( $notice_obj ) {
		remove_filter( 'query', array( $this, 'catch_inserted_id' ) );

		if ( $this->last_inserted_notice_id ) {
			$notice_obj->id                = $this->last_inserted_notice_id;
			$this->last_inserted_notice_id = null;
		}
	}

	public function test_register_routes() {
		$routes   = $this->server->get_routes();
		$endpoint = $this->endpoint_url;

		// Main.
		$this->assertArrayHasKey( $endpoint, $routes );
		$this->assertCount( 2, $routes[ $endpoint ] );

		// Single.
		$single_endpoint = $endpoint . '/(?P<id>[\d]+)';

		$this->assertArrayHasKey( $single_endpoint, $routes );
		$this->assertCount( 3, $routes[ $single_endpoint ] );

		// Dismiss.
		$dismiss_endpoint = $endpoint . '/dismiss';

		$this->assertArrayHasKey( $dismiss_endpoint, $routes );
		$this->assertCount( 1, $routes[ $dismiss_endpoint ] );
	}

	public function create_notice( $notices = array() ) {
		$created = array();

		foreach ( $notices as $notice ) {
			$props = wp_parse_args(
				$notice,
				array(
					'subject'   => 'example subject',
					'message'   => 'example message',
					'date_sent' => bp_core_current_time(),
					'is_active' => 1,
				)
			);

			$new_notice = new BP_Messages_Notice();

			foreach ( $props as $key => $prop ) {
				$new_notice->{$key} = $prop;
			}

			if ( $new_notice->save() ) {
				$created[] = $new_notice;
			}
		}

		return $created;
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject' => 'foo',
			),
			'n2' => array(
				'subject' => 'bar',
			),
		);

		$created = $this->create_notice( $tested );
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );
		$this->assertTrue( 2 === count( $all_data ) );

		$data   = wp_list_filter( $all_data, array( 'is_active' => true ) );
		$data_n = reset( $data );

		$n   = wp_filter_object_list( $created, array( 'id' => $data_n['id'] ), 'and', 'id' );
		$key = key( $n );

		$this->check_notice_data( $created[ $key ], $data_n, 'edit' );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_no_edit_access() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject' => 'foo',
			),
			'n2' => array(
				'subject' => 'bar',
			),
		);

		$this->create_notice( $tested );

		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_forbidden', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_view_active() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject'   => 'foo',
				'is_active' => 0,
			),
			'n2' => array(
				'subject' => 'bar',
			),
		);

		$created = $this->create_notice( $tested );
		$n       = wp_filter_object_list( $created, array( 'is_active' => 1 ), 'and', 'id' );
		$key     = key( $n );

		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$this->assertTrue( 1 === count( $data ), 'There should only be one active notice in the view context' );

		$this->check_notice_data( $created[ $key ], $data[0] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_no_active() {
		$this->bp::set_current_user( $this->user );

		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertTrue( 0 === count( $data ), 'There should be no active notices available' );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject'   => 'foo',
				'is_active' => 0,
			),
			'n2' => array(
				'subject' => 'bar',
			),
		);

		$created = $this->create_notice( $tested );

		$n   = wp_filter_object_list( $created, array( 'is_active' => 1 ), 'and', 'id' );
		$id  = current( $n );
		$key = key( $n );

		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $id );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->check_notice_data( $created[ $key ], $data );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_admin_access() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject'   => 'foo',
				'is_active' => 0,
			),
			'n2' => array(
				'subject' => 'bar',
			),
		);

		$created = $this->create_notice( $tested );

		$n   = wp_filter_object_list( $created, array( 'is_active' => 0 ), 'and', 'id' );
		$id  = current( $n );
		$key = key( $n );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->check_notice_data( $created[ $key ], $data, 'edit' );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_no_access() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject'   => 'foo',
				'is_active' => 0,
			),
			'n2' => array(
				'subject' => 'bar',
			),
		);

		$created = $this->create_notice( $tested );

		$n  = wp_filter_object_list( $created, array( 'is_active' => 0 ), 'and', 'id' );
		$id = current( $n );

		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $id );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_view_active() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject'   => 'bar',
				'is_active' => 0,
			),
			'n2' => array(
				'subject' => 'foo',
			),
		);

		$created = $this->create_notice( $tested );

		$n   = wp_filter_object_list( $created, array( 'is_active' => 1 ), 'and', 'id' );
		$id  = current( $n );
		$key = key( $n );

		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $id );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->check_notice_data( $created[ $key ], $data );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . REST_TESTS_IMPOSSIBLY_HIGH_NUMBER );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'subject' => 'Foo Bar',
				'message' => 'Content',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertTrue( $data['is_active'] );
		$this->assertSame( 'Content', $data['message']['raw'] );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_no_access() {
		$this->bp::set_current_user( static::factory()->user->create() );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'subject' => 'Foo Bar',
				'message' => 'Ouch!',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_no_subject() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'subject' => '',
				'message' => 'Aïe!',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_user_cannot_create_sitewide_notice', $response, 500 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject'   => 'Foo Bar',
				'is_active' => 0,
			),
		);

		$created = $this->create_notice( $tested );
		$n       = current( $created );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $n->id ) );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'is_active', true );
		$request->set_param( 'message', 'Yeah!' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->assertTrue( $data['is_active'] );
		$this->assertSame( 'Yeah!', $data['message']['raw'] );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_no_access() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject' => 'Foo Bar',
			),
		);

		$created = $this->create_notice( $tested );
		$n       = current( $created );

		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $n->id ) );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'message', 'Ouch!' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_no_message() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject' => 'Foo Bar',
			),
		);

		$created = $this->create_notice( $tested );
		$n       = current( $created );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $n->id ) );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'message', '' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_sitewide_notices_update_failed', $response, 500 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_with_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'message', 'Ouch!' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject' => 'Foo Bar',
			),
		);

		$created = $this->create_notice( $tested );
		$n       = current( $created );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $n->id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->assertTrue( $data['deleted'] );
		$this->assertTrue( $data['previous']['subject']['raw'] === 'Foo Bar' );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_no_access() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject' => 'Ouch!',
			),
		);

		$created = $this->create_notice( $tested );
		$n       = current( $created );

		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $n->id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_with_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	/**
	 * @group dismiss_item
	 */
	public function test_dismiss_item() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject' => 'Taz',
			),
		);

		$created = $this->create_notice( $tested );
		$n       = current( $created );

		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/dismiss' );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->assertTrue( $data['dismissed'] );
		$this->assertTrue( $data['previous']['subject']['rendered'] === 'Taz' );
		$this->assertContains( $n->id, bp_get_user_meta( $u1, 'closed_notices', true ) );
	}

	/**
	 * @group dismiss_item
	 */
	public function test_dismiss_item_no_actives() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject'   => 'Taz',
				'is_active' => 0,
			),
		);

		$this->create_notice( $tested );

		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/dismiss' );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	/**
	 * @group dismiss_item
	 */
	public function test_dismiss_item_not_logged_in() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'subject' => 'Taz',
			),
		);

		$this->create_notice( $tested );

		$this->bp::set_current_user( 0 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/dismiss' );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group prepare_item
	 */
	public function test_prepare_item() {
		$this->markTestSkipped();
	}

	protected function check_notice_data( $notice, $data, $context = 'view' ) {
		$this->assertEquals( $notice->id, $data['id'] );

		if ( 'edit' === $context ) {
			$this->assertEquals( $notice->subject, $data['subject']['raw'] );
			$this->assertEquals( $notice->message, $data['message']['raw'] );
			$this->assertEquals( (bool) $notice->is_active, $data['is_active'] );
		}

		$this->assertEquals( apply_filters( 'bp_get_message_notice_subject', wp_staticize_emoji( $notice->subject ) ), $data['subject']['rendered'] );
		$this->assertEquals( apply_filters( 'bp_get_message_notice_text', wp_staticize_emoji( $notice->message ) ), $data['message']['rendered'] );
		$this->assertEquals(
			bp_rest_prepare_date_response( $notice->date_sent, get_date_from_gmt( $notice->date_sent ) ),
			$data['date']
		);
		$this->assertEquals( bp_rest_prepare_date_response( $notice->date_sent ), $data['date_gmt'] );
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 6, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'subject', $properties );
		$this->assertArrayHasKey( 'message', $properties );
		$this->assertArrayHasKey( 'date', $properties );
		$this->assertArrayHasKey( 'date_gmt', $properties );
		$this->assertArrayHasKey( 'is_active', $properties );
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
