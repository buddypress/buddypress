<?php
/**
 * Sitewide Notices Controller Tests.
 *
 * @group notices
 * @group members
 * @group members_notices
 */
class BP_Tests_Members_Notices_REST_Controller extends BP_Test_REST_Controller_Testcase {
	protected $last_inserted_notice_id;
	protected $controller = 'BP_Members_Notices_REST_Controller';
	protected $handle     = 'notices';

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
		$dismiss_endpoint = $endpoint . '/dismiss/(?P<id>[\d]+)';

		$this->assertArrayHasKey( $dismiss_endpoint, $routes );
		$this->assertCount( 1, $routes[ $dismiss_endpoint ] );
	}

	public function create_notice( $notices = array() ) {
		$created = array();

		foreach ( $notices as $notice ) {
			$props = wp_parse_args(
				$notice,
				array(
					'title'   => 'example title',
					'content' => 'example content',
				)
			);

			$notice_id = bp_members_save_notice( $props );
			$created[] = bp_members_get_notice( $notice_id );
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
				'title'  => 'foo',
			),
			'n2' => array(
				'title'  => 'bar',
			),
		);

		$created = $this->create_notice( $tested );
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'target', 'community' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );
		$this->assertTrue( 2 === count( $all_data ) );

		$data_n = reset( $all_data );
		$n      = wp_filter_object_list( $created, array( 'id' => $data_n['id'] ), 'and', 'id' );
		$key    = key( $n );

		$this->check_notice_data( $created[ $key ], $data_n, 'edit' );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items_no_edit_access() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'title' => 'foo',
			),
			'n2' => array(
				'title' => 'bar',
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
	public function test_get_items_no_items() {
		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'user_id', $u1 );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertTrue( 0 === count( $data ), 'There should be no notices available' );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'title' => 'foo',
			),
			'n2' => array(
				'title' => 'bar',
			),
		);

		$created = $this->create_notice( $tested );
		$id      = current( $created )->id;
		$key     = key( $created );

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
				'title' => 'foo',
			),
			'n2' => array(
				'title' => 'bar',
			),
		);

		$created = $this->create_notice( $tested );
		$id      = current( $created )->id;
		$key     = key( $created );

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
				'title'  => 'foo',
				'target' => 'contributors',
			),
		);

		$created = $this->create_notice( $tested );
		$id      = current( $created )->id;

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
				'title'   => 'Foo Bar',
				'content' => 'Content',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		$this->assertSame( 'Content', $data['content']['raw'] );
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
				'title' => 'Foo Bar',
				'content' => 'Ouch!',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_no_title() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint_url );
		$request->set_param( 'context', 'edit' );
		$request->set_query_params(
			array(
				'title'   => '',
				'content' => 'Aïe!',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_notices_missing_data', $response, 500 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'title' => 'Foo Bar',
			),
		);

		$created = $this->create_notice( $tested );
		$n       = current( $created );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $n->id ) );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'content', 'Yeah!' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->assertSame( 'Yeah!', $data['content']['raw'] );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_no_access() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'title' => 'Foo Bar',
			),
		);

		$created = $this->create_notice( $tested );
		$n       = current( $created );

		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $n->id ) );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'content', 'Ouch!' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_no_content() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'title' => 'Foo Bar',
			),
		);

		$created = $this->create_notice( $tested );
		$n       = current( $created );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', $n->id ) );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'content', '' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_notices_missing_data', $response, 500 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item_with_invalid_id() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'PUT', sprintf( $this->endpoint_url . '/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'content', 'Ouch!' );
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
				'title' => 'Foo Bar',
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
		$this->assertTrue( $data['previous']['title']['raw'] === 'Foo Bar' );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_no_access() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'title' => 'Ouch!',
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
				'title' => 'Taz',
			),
		);

		$created = $this->create_notice( $tested );
		$n       = current( $created );

		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/dismiss/' . $n->id );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->assertTrue( $data['dismissed'] );
		$this->assertTrue( $data['previous']['title']['rendered'] === 'Taz' );
		$this->assertContains( $n->id, bp_members_get_dismissed_notices_for_user( $u1 ) );
	}

	/**
	 * @group dismiss_item
	 */
	public function test_dismiss_item_already_dismissed() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'title' => 'Taz',
			),
		);

		$notice    = $this->create_notice( $tested );
		$notice_id = current( $notice )->id;

		$u1 = static::factory()->user->create();
		$this->bp::set_current_user( $u1 );

		bp_members_dismiss_notice( $u1, $notice_id );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/dismiss/' . $notice_id  );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'notice_dismiss_already_dismissed', $response, 404 );
	}

	/**
	 * @group dismiss_item
	 */
	public function test_dismiss_item_not_logged_in() {
		$this->bp::set_current_user( $this->user );
		$tested = array(
			'n1' => array(
				'title' => 'Taz',
			),
		);

		$notice    = $this->create_notice( $tested );
		$notice_id = current( $notice )->id;

		$this->bp::set_current_user( 0 );

		$request = new WP_REST_Request( 'PUT', $this->endpoint_url . '/dismiss/' . $notice_id );
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
			$this->assertEquals( $notice->subject, $data['title']['raw'] );
			$this->assertEquals( bp_get_notice_content( $notice, true ), $data['content']['raw'] );
		}

		$this->assertEquals( apply_filters( 'bp_get_notice_title', wp_staticize_emoji( $notice->subject ) ), $data['title']['rendered'] );
		$this->assertEquals( wp_staticize_emoji( bp_get_notice_content( $notice ) ), $data['content']['rendered'] );
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

		$this->assertEquals( 9, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'title', $properties );
		$this->assertArrayHasKey( 'content', $properties );
		$this->assertArrayHasKey( 'target', $properties );
		$this->assertArrayHasKey( 'date', $properties );
		$this->assertArrayHasKey( 'date_gmt', $properties );
		$this->assertArrayHasKey( 'priority', $properties );
		$this->assertArrayHasKey( 'action_url', $properties );
		$this->assertArrayHasKey( 'action_text', $properties );
	}

	public function test_context_param() {
		// Collection.
		$request  = new WP_REST_Request( 'OPTIONS', $this->endpoint_url );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}
}
