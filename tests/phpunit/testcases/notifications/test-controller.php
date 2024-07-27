<?php
/**
 * Notifications Controller Tests.
 *
 * @package BuddyPress
 * @group notifications
 */
class BP_Test_REST_Notifications_Controller extends WP_Test_REST_Controller_Testcase {
	protected $endpoint;
	protected $bp;
	protected $endpoint_url;
	protected $user;
	protected $notification_id;
	protected $server;

	public function set_up() {
		parent::set_up();

		$this->endpoint        = new BP_REST_Notifications_Controller();
		$this->bp              = new BP_UnitTestCase();
		$this->endpoint_url    = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/' . buddypress()->notifications->id;
		$this->notification_id = $this->bp::factory()->notification->create();
		$this->user            = static::factory()->user->create( array(
			'role'       => 'administrator',
			'user_email' => 'admin@example.com',
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
		$notification_id = $this->bp::factory()->notification->create( array( 'user_id' => $this->user ) );
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array( 'user_id' => $this->user ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();

		$this->assertNotEmpty( $all_data );
		$this->assertSame( $notification_id, $all_data[0]['id'] );
	}

	/**
	 * @group get_items
	 */
	public function test_admin_can_get_items_from_multiple_users() {
		$u1 = static::factory()->user->create( array( 'role' => 'subscriber' ) );
		$u2 = static::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->bp::factory()->notification->create( array( 'user_id' => $u1, ) );
		$this->bp::factory()->notification->create( array( 'user_id' => $u2, ) );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array( 'user_ids' => array( $u1, $u2 ) ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->assertEqualSets(
			array( $u1, $u2 ),
			wp_list_pluck( $all_data, 'user_id' )
		);
	}

	/**
	 * @group get_items
	 */
	public function test_user_can_not_get_items_from_multiple_users() {
		$u1 = static::factory()->user->create( array( 'role' => 'subscriber' ) );
		$u2 = static::factory()->user->create( array( 'role' => 'subscriber' ) );
		$u3 = static::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->bp::factory()->notification->create( array( 'user_id' => $u1 ) );
		$this->bp::factory()->notification->create( array( 'user_id' => $u2 ) );

		$this->bp::set_current_user( $u3 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_query_params( array( 'user_ids' => array( $u1, $u2 ) ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
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
	public function test_get_items_user_cannot_see_notifications_from_others() {
		$u = static::factory()->user->create( array( 'role' => 'subscriber' ) );
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

		$notification = $this->endpoint->get_notification_object( $this->notification_id );
		$this->assertEquals( $this->notification_id, $notification->id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $notification->id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->check_notification_data( $notification, $all_data[0] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_embedded_user_from_notification_item() {
		$this->bp::set_current_user( $this->user );

		$notification_id = $this->bp::factory()->notification->create( array( 'user_id' => $this->user ) );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $notification_id ) );
		$request->set_query_params( array( '_embed' => 'user' ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $this->server->response_to_data( $response, true )[0];

		$this->assertNotEmpty( $data['_embedded']['user'] );

		$embedded_user = current( $data['_embedded']['user'] );

		$this->assertNotEmpty( $embedded_user );
		$this->assertSame( $notification_id, $data['id'] );
		$this->assertSame( $this->user, $embedded_user['id'] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_embedded_group_from_notification_item() {
		$group_id        = $this->bp::factory()->group->create();
		$notification_id = $this->bp::factory()->notification->create(
			$this->set_notification_data(
				array(
					'component_name' => buddypress()->groups->id,
					'item_id'        => $group_id
				)
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $notification_id ) );
		$request->set_query_params( array( '_embed' => 'group' ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $this->server->response_to_data( $response, true )[0];

		$this->assertNotEmpty( $data['_embedded']['group'] );

		// Group single endpoint returns an array.
		// @todo update this when we change the endpoint to return an object only in v2.
		$embedded_group = current( current( $data['_embedded']['group'] ) );

		$this->assertNotEmpty( $embedded_group );
		$this->assertSame( $notification_id, $data['id'] );
		$this->assertSame( $group_id, $embedded_group['id'] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_embedded_activity_from_notification_item() {
		$activity_id     = $this->bp::factory()->activity->create();
		$notification_id = $this->bp::factory()->notification->create(
			$this->set_notification_data(
				array(
					'component_name' => buddypress()->activity->id,
					'item_id'        => $activity_id
				)
			)
		);

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $notification_id ) );
		$request->set_query_params( array( '_embed' => 'activity' ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $this->server->response_to_data( $response, true )[0];

		$this->assertNotEmpty( $data['_embedded']['activity'] );

		// Activity single endpoint returns an array.
		// @todo update this when we change the endpoint to return an object only in v2.
		$embedded_activity = current( current( $data['_embedded']['activity'] ) );

		$this->assertNotEmpty( $embedded_activity );
		$this->assertSame( $notification_id, $data['id'] );
		$this->assertSame( $activity_id, $embedded_activity['id'] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_embedded_blog_from_notification_item() {

		// @todo investigate why bp_is_active( 'blogs' ) is failing for this test only
		// when testing on MU.
		$this->markTestSkipped();

		$blog_title = 'The Foo Bar Blog';

		$this->bp::set_current_user( $this->user );

		$blog_id = self::factory()->blog->create(
			array( 'title' => $blog_title )
		);

		$notification_id = $this->bp::factory()->notification->create(
			$this->set_notification_data(
				array(
					'component_name' => buddypress()->blogs->id,
					'item_id'        => $blog_id
				)
			)
		);

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $notification_id ) );
		$request->set_query_params( array( '_embed' => 'blog' ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $this->server->response_to_data( $response, true )[0];

		$this->assertNotEmpty( $data['_embedded']['blog'] );

		// Blog single endpoint returns an array.
		// @todo update this when we change the endpoint to return an object only in v2.
		$embedded_blog = current( current( $data['_embedded']['blog'] ) );

		$this->assertNotEmpty( $embedded_blog );
		$this->assertSame( $notification_id, $data['id'] );
		$this->assertSame( $blog_id, $embedded_blog['id'] );
		$this->assertSame( $blog_title, $embedded_blog['name'] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_user_not_logged_in() {
		$notification_id = $this->bp::factory()->notification->create( $this->set_notification_data() );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $notification_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_user_cannot_see_notification() {
		$notification_id = $this->bp::factory()->notification->create( $this->set_notification_data() );
		$u               = static::factory()->user->create();

		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $notification_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );

		$params = $this->set_notification_data();
		$request->set_body_params( $params );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->check_create_notification_response( $response );
	}

	/**
	 * @group create_item
	 */
	public function test_rest_create_item() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_notification_data();
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->check_create_notification_response( $response );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_user_not_logged_in() {
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_notification_data();
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_user_cannot_create() {
		$u = static::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_notification_data();
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$notification_id = $this->bp::factory()->notification->create( $this->set_notification_data() );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $notification_id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_notification_data( [ 'is_new' => 0 ] );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$new_data = $response->get_data();
		$this->assertNotEmpty( $new_data );

		$n = $this->endpoint->get_notification_object( $new_data[0]['id'] );
		$this->assertEquals( $params['is_new'], $n->is_new );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_notification_data( [ 'is_new' => 0 ] );
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_notification_invalid_id', $response, 404 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_not_logged_in() {
		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $this->notification_id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_user_without_access() {
		$notification_id = $this->bp::factory()->notification->create( $this->set_notification_data() );

		$u = static::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $notification_id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_same_status() {
		$notification_id = $this->bp::factory()->notification->create( $this->set_notification_data() );

		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $notification_id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_notification_data( [ 'is_new' => 1 ] );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_user_cannot_update_notification_status', $response, 500 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$notification_id = $this->bp::factory()->notification->create( $this->set_notification_data() );
		$notification    = $this->endpoint->get_notification_object( $notification_id );

		$this->assertEquals( $notification_id, $notification->id );
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $notification_id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertNotInstanceOf( 'WP_Error', $response );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->check_notification_data( $notification, $all_data['previous'] );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_notification_invalid_id', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_not_logged_in() {
		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $this->notification_id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_without_access() {
		$notification_id = $this->bp::factory()->notification->create( $this->set_notification_data() );

		$u = static::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->bp::set_current_user( $u );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d', $notification_id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group prepare_item
	 */
	public function test_prepare_item() {
		$this->bp::set_current_user( $this->user );

		$notification = $this->endpoint->get_notification_object( $this->notification_id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d', $notification->id ) );
		$request->set_query_params( array( 'context' => 'edit' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->check_notification_data( $notification, $all_data[0] );
	}

	protected function check_notification_data( $notification, $data ) {
		$this->assertEquals( $notification->id, $data['id'] );
		$this->assertEquals( $notification->user_id, $data['user_id'] );
		$this->assertEquals( $notification->item_id, $data['item_id'] );
		$this->assertEquals( $notification->secondary_item_id, $data['secondary_item_id'] );
		$this->assertEquals( $notification->component_name, $data['component'] );
		$this->assertEquals( $notification->component_action, $data['action'] );
		$this->assertEquals(
			bp_rest_prepare_date_response( $notification->date_notified, get_date_from_gmt( $notification->date_notified ) ),
			$data['date']
		);
		$this->assertEquals( bp_rest_prepare_date_response( $notification->date_notified ), $data['date_gmt'] );
		$this->assertEquals( $notification->is_new, $data['is_new'] );
	}

	protected function set_notification_data( $args = array() ) {
		return wp_parse_args( $args, array(
			'user_id' => $this->user,
			'is_new'  => 1,
		) );
	}

	protected function check_update_notification_response( $response ) {
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = rest_ensure_response( $response );

		$this->assertEquals( 200, $response->get_status() );

		$headers = $response->get_headers();
		$this->assertArrayNotHasKey( 'Location', $headers );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$group = $this->endpoint->get_notification_object( $data['id'] );
		$this->check_notification_data( $group, $data );
	}

	protected function check_create_notification_response( $response ) {
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = rest_ensure_response( $response );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );

		$notification = $this->endpoint->get_notification_object( $data['id'] );
		$this->check_notification_data( $notification, $data );
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 9, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'item_id', $properties );
		$this->assertArrayHasKey( 'secondary_item_id', $properties );
		$this->assertArrayHasKey( 'user_id', $properties );
		$this->assertArrayHasKey( 'component', $properties );
		$this->assertArrayHasKey( 'action', $properties );
		$this->assertArrayHasKey( 'date', $properties );
		$this->assertArrayHasKey( 'date_gmt', $properties );
		$this->assertArrayHasKey( 'is_new', $properties );
	}

	public function test_context_param() {

		// Collection.
		$request  = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );

		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d', $this->notification_id ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}

	public function update_additional_field( $value, $data, $attribute ) {
		return bp_notifications_update_meta( $data->id, '_' . $attribute, $value );
	}

	public function get_additional_field( $data, $attribute )  {
		return bp_notifications_get_meta( $data['id'], '_' . $attribute );
	}

	/**
	 * @group additional_fields
	 */
	public function test_additional_fields() {
		$registered_fields = $GLOBALS['wp_rest_additional_fields'];

		bp_rest_register_field( 'notifications', 'foo_field', array(
			'get_callback'    => array( $this, 'get_additional_field' ),
			'update_callback' => array( $this, 'update_additional_field' ),
			'schema'          => array(
				'description' => 'Notification Meta Field',
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		) );

		$this->bp::set_current_user( $this->user );
		$expected = 'bar_value';

		// POST
		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );

		$params = $this->set_notification_data( array( 'foo_field' => $expected ) );
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

		bp_rest_register_field( 'notifications', 'bar_field', array(
			'get_callback'    => array( $this, 'get_additional_field' ),
			'update_callback' => array( $this, 'update_additional_field' ),
			'schema'          => array(
				'description' => 'Notification Meta Field',
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		) );

		$notification_id = $this->bp::factory()->notification->create( $this->set_notification_data() );
		$this->bp::set_current_user( $this->user );
		$expected = 'foo_value';

		// Put
		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $notification_id ) );
		$request->add_header( 'content-type', 'application/json' );

		$params = $this->set_notification_data( array( 'is_new' => 0, 'bar_field' => 'foo_value' ) );
		$request->set_body( wp_json_encode( $params ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$update_data = $response->get_data();
		$this->assertTrue( $expected === $update_data['bar_field'] );

		$GLOBALS['wp_rest_additional_fields'] = $registered_fields;
	}
}
