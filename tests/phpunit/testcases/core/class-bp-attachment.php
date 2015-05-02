<?php

include_once BP_TESTS_DIR . 'assets/attachment-extensions.php';

/**
 * @group bp_attachments
 * @group BP_Attachment
 */
class BP_Tests_BP_Attachment_TestCases extends BP_UnitTestCase {
	private $upload_results;
	private $image_file;

	public function setUp() {
		parent::setUp();
		add_filter( 'bp_attachment_upload_overrides', array( $this, 'filter_overrides' ),  10, 1 );
		add_filter( 'bp_attachment_upload_dir',       array( $this, 'filter_upload_dir' ), 10, 1 );
		add_filter( 'xprofile_avatar_upload_dir',     array( $this, 'filter_upload_dir' ), 10, 1 );
		add_filter( 'groups_avatar_upload_dir',       array( $this, 'filter_upload_dir' ), 10, 1 );
		$this->upload_results = array();
		$this->image_file = trailingslashit( buddypress()->plugin_dir ) . 'bp-core/images/mystery-man.jpg';
	}

	public function tearDown() {
		parent::tearDown();
		remove_filter( 'bp_attachment_upload_overrides', array( $this, 'filter_overrides' ),  10, 1 );
		remove_filter( 'bp_attachment_upload_dir',       array( $this, 'filter_upload_dir' ), 10, 1 );
		remove_filter( 'xprofile_avatar_upload_dir',     array( $this, 'filter_upload_dir' ), 10, 1 );
		remove_filter( 'groups_avatar_upload_dir',       array( $this, 'filter_upload_dir' ), 10, 1 );
		$this->upload_results = array();
		$this->image_file = '';
	}

	public function filter_overrides( $overrides ) {
		$overrides['upload_error_handler'] = array( $this, 'upload_error_handler' );

		// Don't test upload for WordPress < 4.0
		$overrides['test_upload'] = false;
		return $overrides;
	}

	public function filter_upload_dir( $upload_dir ) {
		$upload_dir['error'] = 'fake_upload_success';

		$this->upload_results = array(
			'new_file' => $upload_dir['path'] . '/mystery-man.jpg',
			'url'      => $upload_dir['url'] . '/mystery-man.jpg',
		);

		return $upload_dir;
	}

	/**
	 * To avoid copying files in tests, we're faking a succesfull uploads
	 * as soon as all the test_form have been executed in _wp_handle_upload
	 */
	public function upload_error_handler( &$file, $message ) {
		if ( 'fake_upload_success' !== $message ) {
			return array( 'error' => $message );
		} else {
			return array(
				'file' => $this->upload_results['new_file'],
				'url'  => $this->upload_results['url'],
				'type' => 'image/jpeg',
			);
		}
	}

	private function clean_files( $basedir = 'attachment_base_dir' ) {
		$upload_dir = bp_upload_dir();

		$this->rrmdir( $upload_dir['basedir'] . '/' . $basedir );
	}

	private function clean_avatars( $type = 'user' ) {
		if ( 'user' === $type ) {
			$avatar_dir = 'avatars';
		} elseif ( 'group' === $type ) {
			$avatar_dir = 'group-avatars';
		}

		$this->rrmdir( bp_core_avatar_upload_path() . '/' . $avatar_dir );
	}

	public function max_filesize() {
		return 1000;
	}

	public function test_bp_attachment_construct_missing_required_parameter() {
		$reset_files = $_FILES;
		$reset_post = $_POST;

		$_FILES['file'] = array(
			'name'     => 'mystery-man.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => 1000
		);

		$attachment_class = new BPTest_Attachment_Extension();
		$upload = $attachment_class->upload( $_FILES );

		$this->assertTrue( empty( $upload ) );

		$_FILES = $reset_files;
		$_POST = $reset_post;
	}

	public function test_bp_attachment_set_upload_dir() {
		$upload_dir = bp_upload_dir();

		$attachment_class = new BPTest_Attachment_Extension( array(
			'action'     => 'attachment_action',
			'file_input' => 'attachment_file_input'
		) );

		$this->assertSame( $attachment_class->upload_dir, bp_upload_dir() );

		$attachment_class = new BPTest_Attachment_Extension( array(
			'action'     => 'attachment_action',
			'file_input' => 'attachment_file_input',
			'base_dir'   => 'attachment_base_dir',
		) );

		$this->assertTrue( file_exists( $upload_dir['basedir'] . '/attachment_base_dir'  ) );

		// clean up
		$this->clean_files();
	}

	/**
	 * @group upload
	 */
	public function test_bp_attachment_upload() {
		$reset_files = $_FILES;
		$reset_post = $_POST;

		$attachment_class = new BPTest_Attachment_Extension( array(
			'action'                => 'attachment_action',
			'file_input'            => 'attachment_file_input',
			'base_dir'   		    => 'attachment_base_dir',
			'original_max_filesize' => 1000,
		) );

		$_POST['action'] = $attachment_class->action;
		$_FILES[ $attachment_class->file_input ] = array(
			'tmp_name' => $this->image_file,
			'name'     => 'mystery-man.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $this->image_file ),
		);

		// Error: file size
		$upload = $attachment_class->upload( $_FILES );
		$this->assertFalse( empty( $upload['error'] ) );

		$attachment_class->allowed_mime_types    = array( 'pdf' );
		$attachment_class->original_max_filesize = false;

		// Error: file type
		$upload = $attachment_class->upload( $_FILES );
		$this->assertFalse( empty( $upload['error'] ) );

		$attachment_class->allowed_mime_types = array();

		// Success
		$upload = $attachment_class->upload( $_FILES );
		$this->assertEquals( $upload['file'], $attachment_class->upload_path . '/mystery-man.jpg' );

		// clean up!
		$_FILES = $reset_files;
		$_POST = $reset_post;
		$this->clean_files();
	}

	/**
	 * @group upload
	 * @group avatar
	 */
	public function test_bp_attachment_avatar_user_upload() {
		$reset_files = $_FILES;
		$reset_post = $_POST;
		$bp = buddypress();
		$displayed_user = $bp->displayed_user;
		$bp->displayed_user = new stdClass;

		$u1 = $this->factory->user->create();
		$bp->displayed_user->id = $u1;

		// Upload the file
		$avatar_attachment = new BP_Attachment_Avatar();
		$_POST['action'] = $avatar_attachment->action;
		$_FILES[ $avatar_attachment->file_input ] = array(
			'tmp_name' => $this->image_file,
			'name'     => 'mystery-man.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $this->image_file )
		);

		/* No error */
		$user_avatar = $avatar_attachment->upload( $_FILES, 'xprofile_avatar_upload_dir' );
		$this->assertEquals( $user_avatar['file'], $bp->avatar->upload_path . '/avatars/' . $u1 .'/mystery-man.jpg' );

		/* File size error */
		add_filter( 'bp_core_avatar_original_max_filesize', array( $this, 'max_filesize' ) );

		$user_avatar = $avatar_attachment->upload( $_FILES, 'xprofile_avatar_upload_dir' );

		remove_filter( 'bp_core_avatar_original_max_filesize', array( $this, 'max_filesize' ) );
		$this->assertFalse( empty( $user_avatar['error'] ) );

		/* File type error */
		$_FILES[ $avatar_attachment->file_input ]['name'] = 'buddypress_logo.pdf';
		$_FILES[ $avatar_attachment->file_input ]['type'] = 'application/pdf';

		$user_avatar = $avatar_attachment->upload( $_FILES, 'xprofile_avatar_upload_dir' );
		$this->assertFalse( empty( $user_avatar['error'] ) );

		// clean up!
		$bp->displayed_user = $displayed_user;
		$this->clean_avatars();
		$_FILES = $reset_files;
		$_POST = $reset_post;
	}

	/**
	 * @group upload
	 * @group avatar
	 */
	public function test_bp_attachment_avatar_group_upload() {
		$bp = buddypress();
		$reset_files = $_FILES;
		$reset_post = $_POST;
		$reset_current_group = $bp->groups->current_group;

		$g = $this->factory->group->create();

		$bp->groups->current_group = groups_get_group( array(
			'group_id'        => $g,
			'populate_extras' => true,
		) );

		// Upload the file
		$avatar_attachment = new BP_Attachment_Avatar();
		$_POST['action'] = $avatar_attachment->action;
		$_FILES[ $avatar_attachment->file_input ] = array(
			'tmp_name' => $this->image_file,
			'name'     => 'mystery-man.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $this->image_file )
		);

		$group_avatar = $avatar_attachment->upload( $_FILES, 'groups_avatar_upload_dir' );
		$this->assertEquals( $group_avatar['file'], $bp->avatar->upload_path . '/group-avatars/' . $g .'/mystery-man.jpg' );

		// clean up!
		$this->clean_avatars( 'group' );
		$bp->groups->current_group = $reset_current_group;
		$_FILES = $reset_files;
		$_POST = $reset_post;
	}

	/**
	 * @group crop
	 */
	public function test_bp_attachment_crop() {
		$crop_args = array(
			'original_file' => $this->image_file,
			'crop_x'        => 0,
			'crop_y'        => 0,
			'crop_w'        => 150,
			'crop_h'        => 150,
			'dst_w'         => 150,
			'dst_h'         => 150,
		);

		$attachment_class = new BPTest_Attachment_Extension( array(
			'action'                => 'attachment_action',
			'file_input'            => 'attachment_file_input',
			'base_dir'   		    => 'attachment_base_dir',
		) );

		$cropped = $attachment_class->crop( $crop_args );

		// Image must come from the upload basedir
		$this->assertTrue( is_wp_error( $cropped ) );

		$crop_args['original_file'] = $attachment_class->upload_path . '/mystery-man.jpg';

		// Image must stay in the upload basedir
		$crop_args['dst_file'] = BP_TESTS_DIR . 'assets/error.jpg';
		$cropped = $attachment_class->crop( $crop_args );

		// Image must stay in the upload basedir
		$this->assertTrue( is_wp_error( $cropped ) );

		// clean up!
		$this->clean_files();
	}
}
