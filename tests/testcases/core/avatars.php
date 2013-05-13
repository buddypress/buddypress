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
			switch_to_blog( get_current_blog_id() );
		}

		// get BP root blog's upload directory data
		$upload_dir = wp_upload_dir();

		restore_current_blog();

		// create new subsite
		$blog_id = $this->factory->blog->create( array(
			'user_id' => $this->administrator,
			'path'    => PATH_CURRENT_SITE . 'test_blogname/',
			'title'   => 'Test Title'
		) );

		// emulate a page load on the new sub-site
		$this->go_to( get_blogaddress_by_name('test_blogname') );;

		// test to see if the upload dir is correct
		$this->assertEquals( bp_core_avatar_url(), $upload_dir['baseurl'] );

		// reset globals
		$this->go_to_root();
	}
}
