<?php
/**
 * Member Avatar Controller Tests.
 *
 * @group members
 * @group member-avatar
 * @group attachments
 */
class BP_Tests_Member_Avatar_REST_Controller extends BP_Test_REST_Controller_Testcase {
	protected $image_file;
	protected $handle = 'members';

	public function set_up() {
		parent::set_up();

		$this->image_file = BP_TESTS_DIR . 'assets/test-image-large.jpg';
	}

	public function test_register_routes() {
		$routes   = $this->server->get_routes();
		$endpoint = $this->endpoint_url . '/(?P<user_id>[\d]+)/avatar';

		// Single.
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
		$u1 = $this->bp::factory()->user->create();

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', $u1 ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->assertTrue( isset( $all_data['full'] ) && isset( $all_data['thumb'] ) );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', $this->user ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_publicly() {
		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', $this->user ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->assertTrue( isset( $all_data['full'] ) && isset( $all_data['thumb'] ) );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_invalid_member_id() {
		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_member_invalid_id', $response, 404 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		if ( 4.9 > (float) $GLOBALS['wp_version'] ) {
			$this->markTestSkipped();
		}

		$reset_files = $_FILES;
		$reset_post  = $_POST;

		$this->bp::set_current_user( $this->user );

		add_filter( 'pre_move_uploaded_file', array( $this, 'copy_file' ), 10, 3 );
		add_filter( 'bp_core_avatar_dimension', array( $this, 'return_100' ) );

		$_FILES['file'] = array(
			'tmp_name' => $this->image_file,
			'name'     => 'test-image-large.jpg',
			'type'     => 'image/jpg',
			'error'    => 0,
			'size'     => filesize( $this->image_file ),
		);

		$_POST['action'] = 'bp_avatar_upload';

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/avatar', $this->user ) );
		$request->set_param( 'context', 'edit' );
		$request->set_file_params( $_FILES );
		$response = $this->server->dispatch( $request );

		remove_filter( 'pre_move_uploaded_file', array( $this, 'copy_file' ) );
		remove_filter( 'bp_core_avatar_dimension', array( $this, 'return_100' ) );

		$avatar = $response->get_data();

		$this->assertSame(
			$avatar,
			array(
				'full'  => bp_core_fetch_avatar(
					array(
						'object'  => 'user',
						'type'    => 'full',
						'item_id' => $this->user,
						'html'    => false,
					)
				),
				'thumb' => bp_core_fetch_avatar(
					array(
						'object'  => 'user',
						'type'    => 'thumb',
						'item_id' => $this->user,
						'html'    => false,
					)
				),
			)
		);

		$_FILES = $reset_files;
		$_POST  = $reset_post;
	}

	public function copy_file( $return, $file, $new_file ) {
		return @copy( $file['tmp_name'], $new_file );
	}

	public function return_100() {
		return 100;
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_with_upload_disabled() {
		$this->bp::set_current_user( $this->user );

		// Disabling member avatar upload.
		add_filter( 'bp_disable_avatar_uploads', '__return_true' );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/avatar', $this->user ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_attachments_member_avatar_disabled', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_empty_image() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/avatar', $this->user ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_attachments_member_avatar_no_image_file', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_user_not_logged_in() {
		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/avatar', $this->user ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_invalid_member_id() {
		$u1 = $this->bp::factory()->user->create();

		$this->bp::set_current_user( $u1 );

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/avatar', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_member_invalid_id', $response, 404 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$this->markTestSkipped();
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$this->markTestSkipped();
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_failed() {
		$this->bp::set_current_user( $this->user );

		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d/avatar', $this->user ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_attachments_member_avatar_no_uploaded_avatar', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_not_logged_in() {
		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d/avatar', $this->user ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_invalid_member_id() {
		$request = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d/avatar', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_member_invalid_id', $response, 404 );
	}

	/**
	 * @group prepare_item
	 */
	public function test_prepare_item() {
		$this->markTestSkipped();
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d/avatar', $this->user ) );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 2, count( $properties ) );
		$this->assertArrayHasKey( 'full', $properties );
		$this->assertArrayHasKey( 'thumb', $properties );
	}

	public function test_context_param() {
		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d/avatar', $this->user ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
	}
}
