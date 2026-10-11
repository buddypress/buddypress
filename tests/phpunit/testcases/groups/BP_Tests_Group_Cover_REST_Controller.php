<?php
/**
 * Group Cover Controller Tests.
 *
 * @group groups
 * @group group-cover
 * @group attachments
 */
class BP_Tests_Group_Cover_REST_Controller extends BP_Test_REST_Controller_Testcase {
	protected $image_file;
	protected $group_id;
	protected $controller = 'BP_Groups_Cover_REST_Controller';
	protected $handle     = 'groups';

	public function set_up() {
		parent::set_up();

		$this->image_file = BP_TESTS_DIR . 'assets/test-image-large.jpg';
		$this->group_id   = $this->bp::factory()->group->create(
			array(
				'name'        => 'Group Test',
				'description' => 'Group Description',
				'creator_id'  => $this->user,
			)
		);
	}

	public function test_register_routes() {
		$routes   = $this->server->get_routes();
		$endpoint = $this->endpoint_url . '/(?P<group_id>[\d]+)/cover';

		// Single.
		$this->assertArrayHasKey( $endpoint, $routes );
		$this->assertCount( 3, $routes[ $endpoint ] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$this->markTestSkipped( 'This endpoint has no collection route or get_items() method.' );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$this->with_cover_upload_dir(
			function ( $upload_dir, $upload_url ) {
				$cover_dir = $upload_dir . '/groups/' . $this->group_id . '/cover-image';
				$cover_url = $upload_url . '/groups/' . $this->group_id . '/cover-image/test-cover-image.jpg';

				$this->assertTrue( wp_mkdir_p( $cover_dir ) );
				$this->assertTrue( copy( BP_TESTS_DIR . 'assets/test-image-large.jpg', $cover_dir . '/test-cover-image.jpg' ) );

				wp_set_current_user( $this->user );

				$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
				$response = $this->server->dispatch( $request );

				$this->assertSame( 200, $response->get_status() );
				$this->assertSame( array( 'image' => $cover_url ), $response->get_data() );
			}
		);
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_no_image() {
		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_attachments_group_cover_no_image', $response, 500 );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_invalid_group_id() {
		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/cover', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/jpeg' ) ) ) {
			$this->markTestSkipped( 'A JPEG-capable image editor is required to generate cover images.' );
		}

		$this->with_cover_upload_dir(
			function ( $upload_dir, $upload_url ) {
				$upload_file = $upload_dir . '/upload.jpg';
				$this->assertTrue( copy( BP_TESTS_DIR . 'assets/test-image-large.jpg', $upload_file ) );

				wp_set_current_user( $this->user );
				$_POST['action'] = 'bp_cover_image_upload';

				$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
				$request->set_file_params(
					array(
						'file' => array(
							'tmp_name' => $upload_file,
							'name'     => 'test-image-large.jpg',
							'type'     => 'image/jpeg',
							'error'    => 0,
							'size'     => filesize( $upload_file ),
						),
					)
				);
				$response = $this->server->dispatch( $request );

				$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

				$data       = $response->get_data();
				$cover_path = bp_attachments_get_attachment(
					'path',
					array(
						'object_dir' => 'groups',
						'item_id'    => $this->group_id,
					)
				);

				$this->assertSame( array( 'image' ), array_keys( $data ) );
				$this->assertNotFalse( $cover_path );
				$this->assertFileExists( $cover_path );
				$this->assertStringStartsWith( $upload_url . '/', $data['image'] );
				$this->assertStringEndsWith( '/groups/' . $this->group_id . '/cover-image/' . wp_basename( $cover_path ), $data['image'] );

				$dimensions = bp_attachments_get_cover_image_dimensions( 'groups' );
				$image_size = wp_getimagesize( $cover_path );

				$this->assertSame( (int) $dimensions['width'], $image_size[0] );
				$this->assertSame( (int) $dimensions['height'], $image_size[1] );
			}
		);
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_no_valid_image_directory() {
		wp_set_current_user( $this->user );
		$reset_files = $_FILES;

		$_FILES['file'] = array(
			'tmp_name' => $this->image_file,
			'name'     => 'test-image-large.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $this->image_file ),
		);

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
		$request->set_file_params( $_FILES );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_attachments_group_cover_upload_error', $response, 500 );

		$_FILES = $reset_files;
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_image_upload_disabled() {
		wp_set_current_user( $this->user );
		$reset_files = $_FILES;

		// Disabling group cover upload.
		add_filter( 'bp_disable_group_cover_image_uploads', '__return_true' );

		$_FILES['file'] = array(
			'tmp_name' => $this->image_file,
			'name'     => 'test-image-large.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $this->image_file ),
		);

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
		$request->set_file_params( $_FILES );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_attachments_group_cover_disabled', $response, 500 );

		remove_filter( 'bp_disable_group_cover_image_uploads', '__return_true' );
		$_FILES = $reset_files;
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_empty_image() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_attachments_group_cover_no_image_file', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_user_not_logged_in() {
		$request  = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_unauthorized_user() {
		$u1 = $this->bp::factory()->user->create();

		wp_set_current_user( $u1 );

		$request  = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_invalid_group_id() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/cover', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$this->markTestSkipped( 'This endpoint does not support updating group cover images.' );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$this->with_cover_upload_dir(
			function ( $upload_dir, $upload_url ) {
				$cover_dir  = $upload_dir . '/groups/' . $this->group_id . '/cover-image';
				$cover_file = $cover_dir . '/test-cover-image.jpg';
				$cover_url  = $upload_url . '/groups/' . $this->group_id . '/cover-image/test-cover-image.jpg';
				$args       = array(
					'object_dir' => 'groups',
					'item_id'    => $this->group_id,
				);

				$this->assertTrue( wp_mkdir_p( $cover_dir ) );
				$this->assertTrue( copy( BP_TESTS_DIR . 'assets/test-image-large.jpg', $cover_file ) );
				$this->assertSame( $cover_url, bp_attachments_get_attachment( 'url', $args ) );

				wp_set_current_user( $this->user );

				$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
				$response = $this->server->dispatch( $request );

				$this->assertSame( 200, $response->get_status() );
				$this->assertSame(
					array(
						'deleted'  => true,
						'previous' => $cover_url,
					),
					$response->get_data()
				);
				$this->assertFalse( file_exists( $cover_file ) );
				$this->assertFalse( bp_attachments_get_attachment( 'url', $args ) );
			}
		);
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_not_logged_in() {
		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_unauthorized_user() {
		$u1 = $this->bp::factory()->user->create();

		wp_set_current_user( $u1 );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_invalid_group_id() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d/cover', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_failed() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_attachments_group_cover_delete_failed', $response, 500 );
	}

	/**
	 * @group prepare_item
	 */
	public function test_prepare_item() {
		global $wp_rest_additional_fields;

		$original_fields = $wp_rest_additional_fields;
		$cover_url       = 'https://example.org/cover.jpg';

		register_rest_field(
			'bp_attachments_group_cover',
			'test_edit_field',
			array(
				'get_callback' => static function () {
					return 'Additional value';
				},
				'schema'       => array(
					'type'    => 'string',
					'context' => array( 'edit' ),
				),
			)
		);

		try {
			foreach ( array( null, 'view', 'edit' ) as $context ) {
				$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
				if ( null !== $context ) {
					$request->set_param( 'context', $context );
				}

				$response = $this->endpoint->prepare_item_for_response( $cover_url, $request );
				$expected = array( 'image' => $cover_url );

				if ( 'edit' === $context ) {
					$expected['test_edit_field'] = 'Additional value';
				}

				$this->assertInstanceOf( 'WP_REST_Response', $response );
				$this->assertSame( 200, $response->get_status() );
				$this->assertSame( $expected, $response->get_data() );
			}
		} finally {
			$wp_rest_additional_fields = $original_fields;
		}
	}

	/**
	 * Run a cover test with isolated storage and restore upload globals.
	 */
	protected function with_cover_upload_dir( $callback ) {
		$upload_dir = get_temp_dir() . 'bp-rest-group-cover-' . wp_generate_uuid4();
		$upload_url = set_url_scheme( 'http://example.org/bp-test-covers' );
		$bp         = buddypress();

		$original_post              = $_POST;
		$original_displayed_user    = $bp->displayed_user;
		$original_current_component = $bp->current_component;
		$original_current_group     = $bp->groups->current_group;

		$uploads_filter = static function ( $value, $data ) use ( $upload_dir, $upload_url ) {
			$uploads = array(
				'dir'     => 'buddypress',
				'basedir' => $upload_dir,
				'baseurl' => $upload_url,
			);

			return '' === $data ? $uploads : ( $uploads[ $data ] ?? $value );
		};
		$avatar_path_filter = static function () use ( $upload_dir ) {
			return $upload_dir;
		};

		add_filter( 'bp_attachments_uploads_dir_get', $uploads_filter, 10, 2 );
		// Keep the existing cleanup helper confined to this temporary root.
		add_filter( 'bp_core_avatar_upload_path', $avatar_path_filter );

		try {
			// REST requests must not inherit a previous group-screen context.
			$bp->current_component = '';
			$this->assertTrue( wp_mkdir_p( $upload_dir ) );
			$callback( $upload_dir, $upload_url );
		} finally {
			try {
				$this->bp->rrmdir( $upload_dir );
			} finally {
				remove_filter( 'bp_attachments_uploads_dir_get', $uploads_filter );
				remove_filter( 'bp_core_avatar_upload_path', $avatar_path_filter );
				$_POST                     = $original_post;
				$bp->displayed_user        = $original_displayed_user;
				$bp->current_component     = $original_current_component;
				$bp->groups->current_group = $original_current_group;
			}
		}

		$this->assertFalse( is_dir( $upload_dir ) );
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertCount( 1, $properties );
		$this->assertArrayHasKey( 'image', $properties );
	}

	public function test_context_param() {

		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d/cover', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertNotEmpty( $data );
	}
}
