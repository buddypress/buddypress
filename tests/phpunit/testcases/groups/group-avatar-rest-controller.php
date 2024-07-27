<?php
/**
 * Group Avatar Controller Tests.
 *
 * @package BuddyPress
 * @group groups
 * @group group-avatar
 */
class BP_Test_REST_Attachments_Group_Avatar_Controller extends WP_Test_REST_Controller_Testcase {
	protected $endpoint;
	protected $bp;
	protected $endpoint_url;
	protected $image_file;
	protected $user_id;
	protected $group_id;
	protected $server;

	public function set_up() {
		parent::set_up();

		$this->endpoint     = new BP_REST_Attachments_Group_Avatar_Controller();
		$this->bp           = new BP_UnitTestCase();
		$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/' . buddypress()->groups->id . '/';
		$this->image_file    = BP_TESTS_DIR . 'assets/test-image-large.jpg';

		$this->user_id = $this->bp::factory()->user->create( array(
			'role' => 'administrator',
		) );

		$this->group_id = $this->bp::factory()->group->create( array(
			'name'        => 'Group Test',
			'description' => 'Group Description',
			'creator_id'  => $this->user_id,
		) );

		if ( ! $this->server ) {
			$this->server = rest_get_server();
		}
	}

	public function test_register_routes() {
		$routes   = $this->server->get_routes();
		$endpoint = $this->endpoint_url . '(?P<group_id>[\d]+)/avatar';

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
		$this->bp::set_current_user( $this->user_id );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '%d/avatar', $this->group_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->assertTrue( isset( $all_data[0]['full'] ) && isset( $all_data[0]['thumb'] ) );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '%d/avatar', $this->group_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_invalid_group_id() {
		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '%d/avatar', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_no_image() {

		// Disable default url.
		add_filter( 'bp_core_fetch_avatar_url', '__return_false' );

		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '%d/avatar', $this->group_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_attachments_group_avatar_no_image', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		if ( 4.9 > (float) $GLOBALS['wp_version'] ) {
			$this->markTestSkipped();
		}

		$reset_files = $_FILES;
		$reset_post = $_POST;

		$this->bp::set_current_user( $this->user_id );

		add_filter( 'pre_move_uploaded_file', array( $this, 'copy_file' ), 10, 3 );
		add_filter( 'bp_core_avatar_dimension', array( $this, 'return_100' ), 10, 1 );

		$_FILES['file'] = array(
			'tmp_name' => $this->image_file,
			'name'     => 'test-image-large.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $this->image_file ),
		);

		$_POST['action'] = 'bp_avatar_upload';

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/avatar', $this->group_id ) );
		$request->set_file_params( $_FILES );
		$response = $this->server->dispatch( $request );

		remove_filter( 'pre_move_uploaded_file', array( $this, 'copy_file' ), 10, 3 );
		remove_filter( 'bp_core_avatar_dimension', array( $this, 'return_100' ), 10, 1 );

		$all_data = $response->get_data();
		$avatar   = reset( $all_data );

		$this->assertSame( $avatar, array(
			'full'  => bp_core_fetch_avatar(
				array(
					'object'  => 'group',
					'type'    => 'full',
					'item_id' => $this->group_id,
					'html'    => false,
				)
			),
			'thumb' => bp_core_fetch_avatar(
				array(
					'object'  => 'group',
					'type'    => 'thumb',
					'item_id' => $this->group_id,
					'html'    => false,
				)
			),
		) );

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
	public function test_create_item_with_image_upload_disabled() {
		if ( 4.9 > (float) $GLOBALS['wp_version'] ) {
			$this->markTestSkipped();
		}

		$reset_files = $_FILES;
		$reset_post  = $_POST;

		$this->bp::set_current_user( $this->user_id );

		// Disabling group avatar upload.
		add_filter( 'bp_disable_group_avatar_uploads', '__return_true' );

		$_FILES['file'] = array(
			'tmp_name' => $this->image_file,
			'name'     => 'test-image-large.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $this->image_file ),
		);

		$_POST['action'] = 'bp_avatar_upload';

		$request  = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/avatar', $this->group_id ) );
		$request->set_file_params( $_FILES );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_attachments_group_avatar_disabled', $response, 500 );

		remove_filter( 'bp_disable_group_avatar_uploads', '__return_true' );
		$_FILES = $reset_files;
		$_POST  = $reset_post;
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_empty_image() {
		$this->bp::set_current_user( $this->user_id );

		$request  = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/avatar', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_attachments_group_avatar_no_image_file', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_user_not_logged_in() {
		$request  = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/avatar', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_invalid_group() {
		$u1 = $this->bp::factory()->user->create();

		$this->bp::set_current_user( $u1 );

		$request  = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '%d/avatar', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
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
		$this->bp::set_current_user( $this->user_id );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '%d/avatar', $this->group_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_attachments_group_avatar_no_uploaded_avatar', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_not_logged_in() {
		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '%d/avatar', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_invalid_group() {
		$this->bp::set_current_user( $this->user_id );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '%d/avatar', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group prepare_item
	 */
	public function test_prepare_item() {
		$this->markTestSkipped();
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '%d/avatar', $this->group_id ) );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 2, count( $properties ) );
		$this->assertArrayHasKey( 'full', $properties );
		$this->assertArrayHasKey( 'thumb', $properties );
	}

	public function test_context_param() {

		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '%d/avatar', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertNotEmpty( $data );
	}
}
