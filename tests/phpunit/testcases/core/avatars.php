<?php
/**
 * @group core
 * @group avatars
 */
class BP_Tests_Avatars extends BP_UnitTestCase {
	protected $old_current_user = 0;

	private $params = array();

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
			'title'   => 'Test Title',
			'path'    => '/path' . rand() . time() . '/',
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

	/**
	 * @group bp_core_fetch_avatar
	 */
	public function test_bp_core_fetch_avatar_parameter_conservation() {
		// First, run the check with custom parameters, specifying no gravatar.
		$this->params = array(
			'item_id'    => 1406,
			'object'     => 'custom_object',
			'type'       => 'full',
			'avatar_dir' => 'custom-dir',
			'width'      => 48,
			'height'     => 54,
			'class'      => 'custom-class',
			'css_id'     => 'custom-css-id',
			'alt'        => 'custom alt',
			'email'      => 'avatar@avatar.org',
			'no_grav'    => true,
			'html'       => true,
			'title'      => 'custom-title',
		);

		// Check to make sure the custom parameters survived the function all the way up to output
		add_filter( 'bp_core_fetch_avatar', array( $this, 'bp_core_fetch_avatar_filter_check' ), 12, 2 );
		$avatar = bp_core_fetch_avatar( $this->params );

		// Re-run check, allowing gravatars.
		$this->params['no_grav'] = false;
		$avatar = bp_core_fetch_avatar( $this->params );

		remove_filter( 'bp_core_fetch_avatar', array( $this, 'bp_core_fetch_avatar_filter_check' ), 12, 2 );

		unset( $this->params );
	}

	public function bp_core_fetch_avatar_filter_check( $html, $params ) {
		// Check that the passed parameters match the original custom parameters.
		$this->assertEmpty( array_merge( array_diff( $params, $this->params ), array_diff( $this->params, $params ) ) );

		// Check the returned html to see that it matches an expected value.
		// Get the correct default avatar, based on whether gravatars are allowed.
		if ( $params['no_grav'] ) {
			$avatar_url = bp_core_avatar_default( 'local' );
		} else {
			// This test has the slight odor of hokum since it recreates so much code that could be changed at any time.
			$bp = buddypress();
			// Set host based on if using ssl
			$host = 'http://gravatar.com/avatar/';
			if ( is_ssl() ) {
				$host = 'https://secure.gravatar.com/avatar/';
			}
			// Set expected gravatar type
			if ( empty( $bp->grav_default->{$this->params['object']} ) ) {
				$default_grav = 'wavatar';
			} else if ( 'mystery' == $bp->grav_default->{$this->params['object']} ) {
				$default_grav = apply_filters( 'bp_core_mysteryman_src', 'mm', $this->params['width'] );
			} else {
				$default_grav = $bp->grav_default->{$this->params['object']};
			}

			$avatar_url = $host . md5( strtolower( $this->params['email'] ) ) . '?d=' . $default_grav . '&amp;s=' . $this->params['width'];

			// Gravatar rating; http://bit.ly/89QxZA
			$rating = get_option( 'avatar_rating' );
			if ( ! empty( $rating ) ) {
				$avatar_url .= "&amp;r={$rating}";
			}
		}

		$expected_html = '<img src="' . $avatar_url . '" id="' . $this->params['css_id'] . '" class="' . $this->params['class'] . ' ' . $this->params['object'] . '-' . $this->params['item_id'] . '-avatar avatar-' . $this->params['width'] . ' photo" width="' . $this->params['width'] . '" height="' . $this->params['height'] . '" alt="' . $this->params['alt'] . '" title="' . $this->params['title'] . '" />';

		$this->assertEquals( $html, $expected_html );
	}
}
