<?php

/**
 * Messages Controller Tests.
 *
 * @group messages
 */
class BP_Test_REST_Messages_Endpoint extends WP_Test_REST_Controller_Testcase {
	protected $endpoint;
	protected $bp;
	protected $endpoint_url;
	protected $user;
	protected $server;

	public function set_up() {
		parent::set_up();

		$this->endpoint     = new BP_REST_Messages_Endpoint();
		$this->bp           = new BP_UnitTestCase();
		$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/' . buddypress()->messages->id;
		$this->user         = static::factory()->user->create( array( 'role' => 'administrator' ) );

		if ( ! $this->server ) {
			$this->server = rest_get_server();
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

		// Starred.
		$starred_endpoint = $endpoint . '/' . bp_get_messages_starred_slug() . '/(?P<id>[\d]+)';

		$this->assertArrayHasKey( $starred_endpoint, $routes );
		$this->assertCount( 1, $routes[ $starred_endpoint ] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		$this->bp::factory()->message->create(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		$this->bp::factory()->message->create(
			array(
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
				'subject'    => 'Fooo',
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'user_id', $u1 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$a_ids = wp_list_pluck( $data, 'id' );

		$this->assertCount( 1, $a_ids );
		$this->assertCount( 1, $data[0]['messages'] );

		// Check the thread data for the requested user id => `$u1`.
		$this->check_thread_data( $this->endpoint->get_thread_object( $data[0]['id'], $u1 ), $data[0] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_and_paginate_messages_recipients() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		$thread = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		$this->bp::factory()->message->create(
			array(
				'thread_id'  => $thread->thread_id,
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
				'subject'    => 'Fooo',
			)
		);

		$thread2 = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u3 ),
				'subject'    => 'Foo',
			)
		);

		$this->bp::factory()->message->create(
			array(
				'thread_id'  => $thread2->thread_id,
				'sender_id'  => $u3,
				'recipients' => array( $u1 ),
				'subject'    => 'Fooo',
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_query_params(
			array(
				'user_id'             => $u1,
				'messages_per_page'   => 1,
				'recipients_per_page' => 1,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		// Messages.
		$this->assertCount( 1, $all_data[0]['messages'] );
		$this->assertCount( 1, $all_data[1]['messages'] );

		// Recipients.
		$this->assertCount( 1, $all_data[0]['recipients'] );
		$this->assertCount( 1, $all_data[1]['recipients'] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
				'content'    => 'Content',
			)
		);

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $m->thread_id );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->check_thread_data( $this->endpoint->get_thread_object( $data['id'], $u2 ), $data );
	}

	/**
	 * @group get_item
	 */
	public function test_get_thread_messages_paginated() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
				'content'    => 'Content',
			)
		);

		// create several messages.
		$this->bp::factory()->message->create_many(
			10,
			array(
				'thread_id'  => $m->thread_id,
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
				'content'    => 'Bar',
			)
		);

		// create a reply.
		$message_id = $this->bp::factory()->message->create(
			array(
				'sender_id'  => $u2,
				'thread_id'  => $m->thread_id,
				'recipients' => array( $u1 ),
				'content'    => 'Bar',
			)
		);

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $m->thread_id );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'messages_per_page', 1 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$headers = $response->get_headers();
		$this->assertEquals( 12, $headers['X-WP-Total'] );
		$this->assertEquals( 12, $headers['X-WP-TotalPages'] );

		$thread = $response->get_data();

		$this->assertCount( 1, $thread['messages'] );
		$this->assertSame( $m->thread_id, $thread['messages'][0]['thread_id'] );
		$this->assertSame( $message_id, $thread['messages'][0]['id'] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_admin_access() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $m->thread_id );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'user_id', $u2 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertFalse( isset( $data['message']['raw'] ) );
		$this->assertFalse( isset( $data['excerpt']['raw'] ) );
		$this->assertFalse( isset( $data['subject']['raw'] ) );
		$this->assertSame( 'Foo', $data['subject']['rendered'] );

		$message = $data['messages'][0];

		$this->assertSame( $m->id, $message['id'] );
		$this->assertFalse( isset( $message['message']['raw'] ) );
		$this->assertFalse( isset( $message['subject']['raw'] ) );

		$this->assertTrue( isset( $message['message']['rendered'] ) );
		$this->assertTrue( isset( $message['subject']['rendered'] ) );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_edit_context() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $m->thread_id );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'user_id', $u2 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertTrue( isset( $data['message']['raw'] ) );
		$this->assertTrue( isset( $data['excerpt']['raw'] ) );
		$this->assertTrue( isset( $data['subject']['raw'] ) );
		$this->assertSame( 'Foo', $data['subject']['raw'] );

		$message = $data['messages'][0];

		$this->assertSame( $m->id, $message['id'] );
		$this->assertTrue( isset( $message['message']['raw'] ) );
		$this->assertTrue( isset( $message['subject']['raw'] ) );

		$this->assertTrue( isset( $message['message']['rendered'] ) );
		$this->assertTrue( isset( $message['subject']['rendered'] ) );
		$this->assertSame( 'Foo', $message['subject']['raw'] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_user_with_no_access() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		$this->bp::set_current_user( $u3 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $m->thread_id );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_user_is_not_logged_in() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		static::factory()->user->create();

		$m = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $m->thread_id );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_invalid_id() {
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
		$u = static::factory()->user->create();

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'sender_id'  => $this->user,
				'recipients' => array( $u ),
				'subject'    => 'Foo',
				'message'    => 'Content',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->check_thread_data( $this->endpoint->get_thread_object( $data['id'] ), $data );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_user_is_not_logged_in() {
		$u = static::factory()->user->create();

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'sender_id'  => $this->user,
				'recipients' => array( $u ),
				'subject'    => 'Foo',
				'message'    => 'Content',
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_with_no_content() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'sender_id'  => $this->user,
				'recipients' => array( static::factory()->user->create() ),
				'subject'    => 'Foo',
			)
		);

		$this->assertErrorResponse(
			'rest_missing_callback_param',
			$this->server->dispatch( $request ),
			400
		);
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_with_no_receipts() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'sender_id' => $this->user,
				'subject'   => 'Foo',
				'message'   => 'Content',
			)
		);

		$this->assertErrorResponse(
			'rest_missing_callback_param',
			$this->server->dispatch( $request ),
			400
		);
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
				'content'    => 'Content',
			)
		);

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $m->thread_id ) );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'read', true );
		$request->set_param( 'user_id', $u2 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->assertFalse( (bool) $data['unread_count'] );
		$this->assertFalse( (bool) $data['recipients'][0]['unread_count'] );
		$this->assertFalse( (bool) $data['recipients'][1]['unread_count'] );
	}

	/**
	 * @group update_item
	 */
	public function test_update_thread_to_unread() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
				'content'    => 'Content',
			)
		);

		// Update to read.
		messages_mark_thread_read( $m->thread_id, $u2 );

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $m->thread_id ) );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'unread', true );
		$request->set_param( 'user_id', $u2 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->assertTrue( (bool) $data['unread_count'] );

		foreach ( $data['recipients'] as $recipient ) {
			if ( $recipient['user_id'] === $u1 ) {
				$this->assertFalse( (bool) $recipient['unread_count'] );
			} else {
				$this->assertTrue( (bool) $recipient['unread_count'] );
			}
		}
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_is_not_logged_in() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
				'content'    => 'Content',
			)
		);

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $m->thread_id ) );
		$request->set_param( 'context', 'edit' );

		$this->assertErrorResponse(
			'bp_rest_authorization_required',
			$this->server->dispatch( $request ),
			rest_authorization_required_code()
		);
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_with_user_with_no_access() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
				'content'    => 'Content',
			)
		);

		$this->bp::set_current_user( $u3 );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $m->thread_id ) );
		$request->set_param( 'context', 'edit' );

		$this->assertErrorResponse(
			'bp_rest_authorization_required',
			$this->server->dispatch( $request ),
			rest_authorization_required_code()
		);
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
				'content'    => 'Content',
			)
		);

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $m->thread_id ) );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'user_id' => $u2 ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->assertTrue( $data['deleted'] );
		$this->assertTrue( $data['previous']['subject']['rendered'] === 'Foo' );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_admin_access() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $m->thread_id );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'user_id' => $u2 ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->assertTrue( $data['deleted'] );
		$this->assertTrue( $data['previous']['subject']['rendered'] === 'Foo' );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_with_user_with_no_access() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		$this->bp::set_current_user( $u3 );

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $m->thread_id );
		$request->set_param( 'context', 'edit' );

		$this->assertErrorResponse(
			'bp_rest_authorization_required',
			$this->server->dispatch( $request ),
			rest_authorization_required_code()
		);
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_is_not_logged_in() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $m );
		$request->set_param( 'context', 'edit' );

		$this->assertErrorResponse(
			'bp_rest_authorization_required',
			$this->server->dispatch( $request ),
			rest_authorization_required_code()
		);
	}

	/**
	 * @group starred
	 */
	public function test_get_starred_items() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		// Init a thread.
		$m1 = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		// Init another thread.
		$m2_id = $this->bp::factory()->message->create(
			array(
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
				'subject'    => 'Taz',
			)
		);

		// Create a reply.
		$r1 = $this->bp::factory()->message->create_and_get(
			array(
				'thread_id'  => $m1->thread_id,
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
				'content'    => 'Bar',
			)
		);

		$this->bp::set_current_user( $u1 );

		bp_messages_star_set_action(
			array(
				'user_id'    => $u1,
				'message_id' => $r1->id,
			)
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_query_params(
			array(
				'user_id' => $u1,
				'box'     => 'starred',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$threads = wp_list_pluck( $data, 'id' );
		$this->assertNotContains( $m2_id, $threads );
		$this->assertContains( $m1->thread_id, $threads );

		$result = reset( $data );
		$this->assertNotEmpty( $result['starred_message_ids'] );
		$this->assertContains( $r1->id, $result['starred_message_ids'] );
	}

	/**
	 * @group starred
	 */
	public function test_update_starred_add_star() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		// Init a thread.
		$m1 = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		// Create a reply.
		$r1 = $this->bp::factory()->message->create_and_get(
			array(
				'thread_id'  => $m1->thread_id,
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
				'content'    => 'Bar',
			)
		);

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . bp_get_messages_starred_slug() . '/' . $r1->id );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$this->assertTrue( $data['is_starred'] );
	}

	/**
	 * @group starred
	 */
	public function test_update_starred_remove_star() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		// Init a thread.
		$m = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		bp_messages_star_set_action(
			array(
				'user_id'    => $u2,
				'message_id' => $m->id,
			)
		);

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . bp_get_messages_starred_slug() . '/' . $m->id );
		$request->set_param( 'context', 'edit' );
		$request->add_header( 'content-type', 'application/json' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$this->assertFalse( $data['is_starred'] );
	}

	/**
	 * @group starred
	 */
	public function test_update_starred_user_is_not_logged_in() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		// Init a thread.
		$m = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . bp_get_messages_starred_slug() . '/' . $m->id );
		$request->set_param( 'context', 'edit' );

		$this->assertErrorResponse(
			'bp_rest_authorization_required',
			$this->server->dispatch( $request ),
			rest_authorization_required_code()
		);
	}

	/**
	 * @group starred
	 */
	public function test_update_starred_user_with_no_access() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();

		// Init a thread.
		$m = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		$this->bp::set_current_user( $u3 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . bp_get_messages_starred_slug() . '/' . $m->id );
		$request->set_param( 'context', 'edit' );

		$this->assertErrorResponse(
			'bp_rest_authorization_required',
			$this->server->dispatch( $request ),
			rest_authorization_required_code()
		);
	}

	/**
	 * @group starred
	 */
	public function test_update_starred_using_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . bp_get_messages_starred_slug() . '/' . REST_TESTS_IMPOSSIBLY_HIGH_NUMBER );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_invalid_id', $response, 404 );
	}

	public function update_additional_field( $value, $data, $attribute ) {
		return bp_messages_update_meta( $data->id, '_' . $attribute, $value );
	}

	public function get_additional_field( $data, $attribute ) {
		return bp_messages_get_meta( $data['id'], '_' . $attribute );
	}

	/**
	 * @group additional_fields
	 */
	public function test_additional_fields_for_get_item() {
		$registered_fields = $GLOBALS['wp_rest_additional_fields'];

		bp_rest_register_field(
			'messages',
			'taz_field',
			array(
				'get_callback'    => array( $this, 'get_additional_field' ),
				'update_callback' => array( $this, 'update_additional_field' ),
				'schema'          => array(
					'description' => 'Message Meta Field',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		// Init a thread.
		$m1 = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Foo',
			)
		);

		$expected = 'boz_value';
		bp_messages_update_meta( $m1->id, '_taz_field', $expected );
		$this->bp::set_current_user( $u2 );

		// GET
		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $m1->thread_id );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$get_data = $response->get_data();

		$this->assertNotEmpty( $get_data );

		$last_message = wp_list_filter( $get_data['messages'], array( 'id' => $get_data['message_id'] ) );
		$last_message = reset( $last_message );
		$this->assertTrue( $expected === $last_message['taz_field'] );

		$GLOBALS['wp_rest_additional_fields'] = $registered_fields;
	}

	/**
	 * @group additional_fields
	 */
	public function test_additional_fields_for_created_thread() {
		$registered_fields = $GLOBALS['wp_rest_additional_fields'];

		bp_rest_register_field(
			'messages',
			'foo_field',
			array(
				'get_callback'    => array( $this, 'get_additional_field' ),
				'update_callback' => array( $this, 'update_additional_field' ),
				'schema'          => array(
					'description' => 'Message Meta Field',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		$u = static::factory()->user->create();
		$this->bp::set_current_user( $this->user );
		$expected = 'bar_value';

		// POST
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'sender_id'  => $this->user,
				'recipients' => array( $u ),
				'subject'    => 'Foo',
				'message'    => 'Bar',
				'foo_field'  => $expected,
			)
		);
		$response = $this->server->dispatch( $request );

		$create_data = $response->get_data();

		$this->assertNotEmpty( $create_data );

		$last_message = wp_list_filter( $create_data['messages'], array( 'id' => $create_data['message_id'] ) );
		$last_message = reset( $last_message );
		$this->assertTrue( $expected === $last_message['foo_field'] );

		$GLOBALS['wp_rest_additional_fields'] = $registered_fields;
	}

	/**
	 * @group additional_fields
	 */
	public function test_additional_fields_for_created_reply() {
		$registered_fields = $GLOBALS['wp_rest_additional_fields'];

		bp_rest_register_field(
			'messages',
			'bar_field',
			array(
				'get_callback'    => array( $this, 'get_additional_field' ),
				'update_callback' => array( $this, 'update_additional_field' ),
				'schema'          => array(
					'description' => 'Message Meta Field',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		// Init a thread.
		$m1 = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
				'subject'    => 'Foo',
			)
		);

		$this->bp::set_current_user( $u1 );
		$expected = 'foo_value';

		// POST a reply.
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'id'         => $m1->thread_id,
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'message'    => 'Taz',
				'bar_field'  => $expected,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$create_data = $response->get_data();

		$this->assertNotEmpty( $create_data );

		$last_message = wp_list_filter( $create_data['messages'], array( 'id' => $create_data['message_id'] ) );
		$last_message = reset( $last_message );
		$this->assertTrue( $expected === $last_message['bar_field'] );

		$GLOBALS['wp_rest_additional_fields'] = $registered_fields;
	}

	/**
	 * @group additional_fields
	 */
	public function test_additional_fields_for_last_message_updated() {
		$registered_fields = $GLOBALS['wp_rest_additional_fields'];

		bp_rest_register_field(
			'messages',
			'boz_field',
			array(
				'get_callback'    => array( $this, 'get_additional_field' ),
				'update_callback' => array( $this, 'update_additional_field' ),
				'schema'          => array(
					'description' => 'Message Meta Field',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		// Init a thread.
		$m1 = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
				'subject'    => 'Foo',
			)
		);

		$this->bp::factory()->message->create_and_get(
			array(
				'thread_id'  => $m1->thread_id,
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Bar',
			)
		);

		$this->bp::factory()->message->create_and_get(
			array(
				'thread_id'  => $m1->thread_id,
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
				'subject'    => 'Taz',
			)
		);

		$this->bp::set_current_user( $u2 );
		$expected = 'taz_value';

		// Update the last message.
		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . $m1->thread_id );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params( array( 'boz_field' => $expected ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$update_data = $response->get_data();
		$this->assertNotEmpty( $update_data );

		$last_message = wp_list_filter( $update_data['messages'], array( 'id' => $update_data['message_id'] ) );
		$last_message = reset( $last_message );
		$this->assertTrue( $expected === $last_message['boz_field'] );

		$GLOBALS['wp_rest_additional_fields'] = $registered_fields;
	}

	/**
	 * @group additional_fields
	 */
	public function test_additional_fields_for_specific_message_updated() {
		$registered_fields = $GLOBALS['wp_rest_additional_fields'];

		bp_rest_register_field(
			'messages',
			'top_field',
			array(
				'get_callback'    => array( $this, 'get_additional_field' ),
				'update_callback' => array( $this, 'update_additional_field' ),
				'schema'          => array(
					'description' => 'Message Meta Field',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		// Init a thread.
		$m1 = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
				'subject'    => 'Top',
			)
		);

		$r1 = $this->bp::factory()->message->create_and_get(
			array(
				'thread_id'  => $m1->thread_id,
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Up',
			)
		);

		$r1 = $this->bp::factory()->message->create_and_get(
			array(
				'thread_id'  => $m1->thread_id,
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
				'subject'    => 'Upper',
			)
		);

		$this->bp::set_current_user( $u2 );
		$expected = 'up_value';

		// Update the last message.
		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/' . $m1->thread_id );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'message_id' => $r1->id,
				'top_field'  => $expected,
			)
		);
		$response = $this->server->dispatch( $request );

		$update_data = $response->get_data();

		$this->assertNotEmpty( $update_data );

		$specific_message = wp_list_filter( $update_data['messages'], array( 'id' => $r1->id ) );
		$specific_message = reset( $specific_message );
		$this->assertTrue( $expected === $specific_message['top_field'] );

		$GLOBALS['wp_rest_additional_fields'] = $registered_fields;
	}

	/**
	 * @group prepare_recipient_for_response
	 */
	public function test_prepare_prepare_recipient_for_response() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$u3 = static::factory()->user->create();
		$m  = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2, $u3 ),
				'subject'    => 'Foo',
				'content'    => 'Content',
			)
		);

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $m->thread_id );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$get_data = $response->get_data();

		$this->assertNotEmpty( $get_data );

		$recipients = $get_data['recipients'];

		foreach ( $recipients as $recipient ) {
			$user_id = $recipient['user_id'];
			$this->assertEquals( esc_url( bp_members_get_user_url( $user_id ) ), $recipient['user_link'] );

			foreach ( array( 'full', 'thumb' ) as $type ) {
				$expected['user_avatars'][ $type ] = bp_core_fetch_avatar(
					array(
						'item_id' => $user_id,
						'html'    => false,
						'type'    => $type,
					)
				);

				$this->assertEquals( $expected['user_avatars'][ $type ], $recipient['user_avatars'][ $type ] );
			}
		}
	}

	/**
	 * @group prepare_links
	 */
	public function test_prepare_add_links_to_response() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();
		$m1 = $this->bp::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Bar',
				'content'    => 'Content',
			)
		);

		$r1 = $this->bp::factory()->message->create_and_get(
			array(
				'thread_id' => $m1->thread_id,
				'sender_id' => $u2,
				'content'   => 'Reply',
			)
		);

		$this->bp::set_current_user( $u2 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $m1->thread_id );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$get_links = $response->get_data();

		$this->assertNotEmpty( $get_links );

		var_dump( $get_links );

		$links = $get_links['_links'];

		$this->assertEquals( rest_url( $this->endpoint_url . '/' ), $links['collection'][0]['href'] );
		$this->assertEquals( rest_url( $this->endpoint_url . '/' . $m1->thread_id ), $links['self'][0]['href'] );
		$this->assertEquals( rest_url( $this->endpoint_url . '/' . bp_get_messages_starred_slug() . '/' . $m1->id ), $links[ $m1->id ][0]['href'] );
		$this->assertEquals( rest_url( $this->endpoint_url . '/' . bp_get_messages_starred_slug() . '/' . $r1->id ), $links[ $r1->id ][0]['href'] );
	}

	/**
	 * @group get_item
	 */
	public function test_prepare_item() {
		$this->markTestSkipped();
	}

	protected function check_thread_data( $thread, $data ) {
		$this->assertEquals( $thread->thread_id, $data['id'] );
		$this->assertEquals( $thread->last_message_id, $data['message_id'] );
		$this->assertEquals( $thread->last_sender_id, $data['last_sender_id'] );
		$this->assertEquals( apply_filters( 'bp_get_message_thread_subject', $thread->last_message_subject ), $data['subject']['rendered'] );
		$this->assertEquals( apply_filters( 'bp_get_message_thread_content', $thread->last_message_content ), $data['message']['rendered'] );
		$this->assertEquals(
			bp_rest_prepare_date_response( $thread->last_message_date, get_date_from_gmt( $thread->last_message_date ) ),
			$data['date']
		);
		$this->assertEquals( bp_rest_prepare_date_response( $thread->last_message_date ), $data['date_gmt'] );
		$this->assertEquals( $thread->unread_count, $data['unread_count'] );
		$this->assertEquals( $thread->sender_ids, $data['sender_ids'] );
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 13, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'message_id', $properties );
		$this->assertArrayHasKey( 'last_sender_id', $properties );
		$this->assertArrayHasKey( 'subject', $properties );
		$this->assertArrayHasKey( 'date', $properties );
		$this->assertArrayHasKey( 'date_gmt', $properties );
		$this->assertArrayHasKey( 'unread_count', $properties );
		$this->assertArrayHasKey( 'sender_ids', $properties );
		$this->assertArrayHasKey( 'messages', $properties );
		$this->assertArrayHasKey( 'starred_message_ids', $properties );
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
