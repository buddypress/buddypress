<?php
/**
 * Friends Controller Tests.
 *
 * @group friends
 */
class BP_Tests_Friends_REST_Controller extends BP_Test_REST_Controller_Testcase {
	protected $friend;
	protected $friendship_id;
	protected $controller = 'BP_Friends_REST_Controller';
	protected $handle     = 'friends';

	public function set_up() {
		parent::set_up();

		$this->friend        = static::factory()->user->create();
		$this->friendship_id = $this->create_friendship();
	}

	public function test_register_routes() {
		$routes = $this->server->get_routes();

		// Main.
		$this->assertArrayHasKey( $this->endpoint_url, $routes );
		$this->assertCount( 2, $routes[ $this->endpoint_url ] );

		// Single.
		$this->assertArrayHasKey( $this->endpoint_url . '/(?P<id>[\w-]+)', $routes );
		$this->assertCount( 3, $routes[ $this->endpoint_url . '/(?P<id>[\w-]+)' ] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$this->create_friendship();
		$this->create_friendship();
		$this->create_friendship();
		$this->create_friendship();

		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_query_params(
			array(
				'user_id'      => $this->friend,
				'per_page'     => 2,
				'is_confirmed' => 0,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$headers = $response->get_headers();
		$this->assertNotEmpty( $headers );

		$this->assertEquals( 2, $headers['X-WP-Total'] );
		$this->assertEquals( 1, $headers['X-WP-TotalPages'] );
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
	 * @group get_item
	 */
	public function test_get_item() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $this->friend ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$signup = $response->get_data();
		$this->assertNotEmpty( $signup );

		$this->check_friendship_data(
			$this->endpoint->get_friendship_object( $this->friendship_id ),
			$signup
		);
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_invalid_friend_id() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_friends_get_item_failed', $response, 404 );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_user_not_logged_in() {
		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $this->friend ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );

		$params = $this->set_friendship_data( array( 'initiator_id' => $this->user ) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$friendship = $response->get_data();

		$this->assertNotEmpty( $friendship );
		$this->assertSame( $friendship['initiator_id'], $this->user );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_to_myself_from_someone_else() {
		$u1 = static::factory()->user->create();
		$u2 = static::factory()->user->create();

		wp_set_current_user( $u2 );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$params = $this->set_friendship_data(
			array(
				'initiator_id' => $u1,
				'friend_id'    => $u2,
			)
		);
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_friends_create_item_failed', $response, 403 );
	}

	/**
	 * @group create_item
	 */
	public function test_admins_create_item_to_myself_from_someone_else() {
		$u = static::factory()->user->create();

		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$params = $this->set_friendship_data(
			array(
				'initiator_id' => $u,
				'friend_id'    => $this->user,
			)
		);
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_friends_create_item_failed', $response, 403 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_already_friends() {
		$user = static::factory()->user->create();

		$this->create_friendship( $user );
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$params = $this->set_friendship_data(
			array(
				'initiator_id' => $this->user,
				'friend_id'    => $user,
			)
		);
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_friends_create_item_failed', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_user_not_logged_in() {
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );

		$params = $this->set_friendship_data();
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_regular_user_can_not_create_friendship_to_others() {
		wp_set_current_user( static::factory()->user->create() );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );

		$params = $this->set_friendship_data();
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_friends_create_item_failed', $response, 403 );
	}

	/**
	 * @group create_item
	 */
	public function test_admins_can_create_friendship_to_others() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );

		$params = $this->set_friendship_data();
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$friendship = $response->get_data();

		$this->assertNotEmpty( $friendship['initiator_id'] );
	}

	/**
	 * @group create_item
	 */
	public function test_admins_can_force_friendship_creation() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );

		$params = $this->set_friendship_data();
		$params = array_merge( $params, array( 'force' => true ) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$friendship = $response->get_data();

		$this->assertTrue( $friendship['is_confirmed'] );
	}

	/**
	 * @group create_item
	 */
	public function test_regular_users_can_not_force_friendship_creation() {
		$u = static::factory()->user->create();
		wp_set_current_user( $u );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );

		$params = $this->set_friendship_data(
			array(
				'initiator_id' => $u,
				'force'        => true,
			)
		);
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$friendship = $response->get_data();

		$this->assertFalse( $friendship['is_confirmed'] );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_without_initiator_id() {
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );

		$params = $this->set_friendship_data();
		unset( $params['initiator_id'] );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_missing_callback_param', $response, 400 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_without_friend_id() {
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_missing_callback_param', $response, 400 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$this->create_friendship();

		wp_set_current_user( $this->friend );

		$request  = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $this->user ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$friendship = $response->get_data();

		$this->assertTrue( $friendship['is_confirmed'] );
	}

	/**
	 * @group update_item
	 */
	public function test_initiator_can_not_accept_its_own_friendship_request() {
		$this->create_friendship();

		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $this->friend ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_friends_cannot_update_item', $response, 500 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_invalid_friend_id() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_friends_update_item_failed', $response, 404 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_not_logged_in() {
		$request  = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $this->friend ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$this->create_friendship();

		wp_set_current_user( $this->friend );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->user ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$deleted = $response->get_data();

		$this->assertTrue( $deleted['deleted'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_using_the_initiator() {
		$this->create_friendship();

		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->friend ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$deleted = $response->get_data();

		$this->assertTrue( $deleted['deleted'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_reject_and_remove_item_from_database() {
		$this->create_friendship();

		wp_set_current_user( $this->friend );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->user ) );
		$request->set_body_params( array( 'force' => true ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$deleted = $response->get_data();

		$this->assertTrue( $deleted['deleted'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_reject_and_remove_item_from_database_using_initiator() {
		$this->create_friendship();

		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->friend ) );
		$request->set_body_params( array( 'force' => true ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$deleted = $response->get_data();

		$this->assertTrue( $deleted['deleted'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_reject_and_remove_item_from_database_using_initiator_and_testing_force() {
		$this->create_friendship();

		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->friend ) );
		$request->set_body_params( array( 'force' => 'true' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$deleted = $response->get_data();

		$this->assertTrue( $deleted['deleted'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_reject_friendship_request() {
		$this->create_friendship();

		wp_set_current_user( $this->friend );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->user ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$deleted = $response->get_data();

		$this->assertTrue( $deleted['deleted'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_reject_friendship_with_invalid_friendship_id() {
		$this->create_friendship();

		wp_set_current_user( $this->friend );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_friends_delete_item_failed', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_reject_friendship_with_user_not_logged_in() {
		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->friend ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group prepare_item
	 */
	public function test_prepare_item() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $this->friend ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$friendship = $response->get_data();

		$this->assertNotEmpty( $friendship );
		$this->check_friendship_data(
			$this->endpoint->get_friendship_object( $this->friendship_id ),
			$friendship
		);
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d', $this->friend ) );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 6, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'initiator_id', $properties );
		$this->assertArrayHasKey( 'friend_id', $properties );
		$this->assertArrayHasKey( 'is_confirmed', $properties );
		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'date_created_gmt', $properties );
	}

	public function test_context_param() {
		// Collection.
		$request  = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );

		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d', $this->friend ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}

	protected function set_friendship_data( $args = array() ) {
		return wp_parse_args(
			$args,
			array(
				'initiator_id' => static::factory()->user->create(),
				'friend_id'    => static::factory()->user->create(),
			)
		);
	}

	protected function create_friendship( $u = 0 ) {
		if ( empty( $u ) ) {
			$u = $this->friend;
		}

		$friendship                    = new BP_Friends_Friendship();
		$friendship->initiator_user_id = $this->user;
		$friendship->friend_user_id    = $u;
		$friendship->is_confirmed      = 0;
		$friendship->is_limited        = 0;
		$friendship->date_created      = bp_core_current_time();
		$friendship->save();

		return $friendship->id;
	}

	protected function check_friendship_data( $friend, $data ) {
		$this->assertEquals( $friend->id, $data['id'] );
		$this->assertEquals( $friend->initiator_user_id, $data['initiator_id'] );
		$this->assertEquals( $friend->friend_user_id, $data['friend_id'] );
		$this->assertEquals( $friend->is_confirmed, $data['is_confirmed'] );
		$this->assertEquals(
			bp_rest_prepare_date_response( $friend->date_created, get_date_from_gmt( $friend->date_created ) ),
			$data['date_created']
		);
		$this->assertEquals( bp_rest_prepare_date_response( $friend->date_created ), $data['date_created_gmt'] );
	}
}
