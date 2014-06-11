<?php
/**
 * @group core
 * @group avatars
 */
class BP_Tests_Avatars extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		$this->administrator    = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->administrator );
	}

	public function tearDown() {
		parent::tearDown();
		wp_set_current_user( $this->old_current_user );
	}

	private function clean_existing_avatars( $type = 'user' ) {
		if ( 'user' === $type ) {
			$avatar_dir = 'avatars';
		} else if ( 'group' === $object ) {
			$avatar_dir = 'group-avatars';
		}

		$this->rrmdir( bp_core_avatar_upload_path() . '/' . $avatar_dir );
	}

	private function rrmdir( $dir ) {
		$d = glob( $dir . '/*' );

		if ( empty( $d ) ) {
			return;
		}

		foreach ( $d as $file ) {
			if ( is_dir( $file ) ) {
				$this->rrmdir( $file );
			} else {
				@unlink( $file );
			}
		}

		@rmdir( $dir );
	}

	/**
	 * @ticket 4948
	 */
	function test_avatars_on_non_root_blog() {
		// Do not pass 'Go', do not collect $200
		if ( ! is_multisite() ) {
			return;
		}

		// switch to BP root blog if necessary
		if ( bp_get_root_blog_id() != get_current_blog_id() ) {
			$this->go_to( '/' );
		}

		// get BP root blog's upload directory data
		$upload_dir = wp_upload_dir();

		restore_current_blog();

		// create new subsite
		$blog_id = $this->factory->blog->create( array(
			'user_id' => $this->administrator,
			'title'   => 'Test Title'
		) );

		// emulate a page load on the new sub-site
		$this->go_to( get_blog_option( $blog_id, 'siteurl' ) );

		// test to see if the upload dir is correct
		$this->assertEquals( $upload_dir['baseurl'], bp_core_avatar_url() );

		// reset globals
		$this->go_to( '/' );
	}

	/**
	 * @group bp_get_user_has_avatar
	 */
	public function test_bp_get_user_has_avatar_no_avatar_uploaded() {
		$this->clean_existing_avatars();

		$u = $this->create_user();
		$this->assertFalse( bp_get_user_has_avatar( $u ) );
	}

	/**
	 * @group bp_get_user_has_avatar
	 */
	public function test_bp_get_user_has_avatar_has_avatar_uploaded() {
		$u = $this->create_user();

		// Fake it
		add_filter( 'bp_core_fetch_avatar_url', array( $this, 'avatar_cb' ) );

		$this->assertTrue( bp_get_user_has_avatar( $u ) );

		remove_filter( 'bp_core_fetch_avatar_url', array( $this, 'avatar_cb' ) );
	}

	public function avatar_cb() {
		return 'foo';
	}
}
