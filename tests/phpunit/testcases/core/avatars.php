<?php
/**
 * @group core
 * @group avatars
 */
class BP_Tests_Avatars extends BP_UnitTestCase {
	private $params = array();

	private function clean_existing_avatars( $type = 'user' ) {
		if ( 'user' === $type ) {
			$avatar_dir = 'avatars';
		} elseif ( 'group' === $object ) {
			$avatar_dir = 'group-avatars';
		}

		$this->rrmdir( bp_core_avatar_upload_path() . '/' . $avatar_dir );
	}

	/**
	 * @ticket BP4948
	 */
	function test_avatars_on_non_root_blog() {
		// Do not pass 'Go', do not collect $200
		if ( ! is_multisite() ) {
			return;
		}

		$u = $this->factory->user->create();

		// get BP root blog's upload directory data
		$upload_dir = wp_upload_dir();

		// create new subsite
		$blog_id = $this->factory->blog->create( array(
			'user_id' => $u,
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

		$u = $this->factory->user->create();
		$this->assertFalse( bp_get_user_has_avatar( $u ) );
	}

	/**
	 * @group bp_get_user_has_avatar
	 */
	public function test_bp_get_user_has_avatar_has_avatar_uploaded() {
		$u = $this->factory->user->create();

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
			'item_id'       => 1406,
			'object'        => 'custom_object',
			'type'          => 'full',
			'avatar_dir'    => 'custom-dir',
			'width'         => 48,
			'height'        => 54,
			'class'         => 'custom-class',
			'css_id'        => 'custom-css-id',
			'alt'           => 'custom alt',
			'email'         => 'avatar@avatar.org',
			'no_grav'       => true,
			'html'          => true,
			'title'         => 'custom-title',
			'extra_attr'    => 'data-testing="buddypress"',
			'scheme'        => 'http',
			'rating'        => get_option( 'avatar_rating' ),
			'force_default' => false,
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
			$host = '//www.gravatar.com/avatar/';

			// Set expected gravatar type
			if ( empty( $bp->grav_default->{$this->params['object']} ) ) {
				$default_grav = 'wavatar';
			} elseif ( 'mystery' == $bp->grav_default->{$this->params['object']} ) {
				$default_grav = apply_filters( 'bp_core_mysteryman_src', 'mm', $this->params['width'] );
			} else {
				$default_grav = $bp->grav_default->{$this->params['object']};
			}

			$avatar_url = $host . md5( strtolower( $this->params['email'] ) );

			// Main Gravatar URL args.
			$url_args = array(
				's' => $this->params['width']
			);

			// Force default.
			if ( ! empty( $this->params['force_default'] ) ) {
				$url_args['f'] = 'y';
			}

			// Gravatar rating; http://bit.ly/89QxZA
			$rating = strtolower( get_option( 'avatar_rating' ) );
			if ( ! empty( $rating ) ) {
				$url_args['r'] = $rating;
			}

			// Default avatar.
			if ( 'gravatar_default' !== $default_grav ) {
				$url_args['d'] = $default_grav;
			}

			// Set up the Gravatar URL.
			$avatar_url = esc_url( add_query_arg(
				rawurlencode_deep( array_filter( $url_args ) ),
				$avatar_url
			) );

		}

		$expected_html = '<img src="' . $avatar_url . '" id="' . $this->params['css_id'] . '" class="' . $this->params['class'] . ' ' . $this->params['object'] . '-' . $this->params['item_id'] . '-avatar avatar-' . $this->params['width'] . ' photo" width="' . $this->params['width'] . '" height="' . $this->params['height'] . '" alt="' . $this->params['alt'] . '" title="' . $this->params['title'] . '" ' . $this->params['extra_attr'] . ' />';

		$this->assertEquals( $html, $expected_html );
	}

	/**
	 * @group bp_core_fetch_avatar
	 */
	public function test_bp_core_fetch_avatar_class_attribute() {
		$u = $this->factory->user->create();

		$hw = 100;
		$args = array(
			'item_id'    => $u,
			'object'     => 'user',
			'type'       => 'full',
			'width'      => $hw,
			'height'     => $hw,
			'class'      => '',
			'no_grav'    => true,
			'html'       => true,
		);

		// Class attribute is empty
		$avatar = bp_core_fetch_avatar( $args );
		$expected = array( 'avatar', 'user-' . $u . '-avatar', 'avatar-' . $hw );
		preg_match( '/class=["\']?([^"\']*)["\' ]/is', $avatar, $matches );
		$classes = explode( ' ', $matches[1] );
		$this->assertSame( $expected, array_intersect_key( $expected, $classes ) );

		// Class attribute is a String
		$args['class'] = 'custom-class class-custom';
		$avatar = bp_core_fetch_avatar( $args );
		$expected = array_merge( explode( ' ', $args['class'] ), array( 'user-' . $u . '-avatar', 'avatar-' . $hw ) );
		preg_match( '/class=["\']?([^"\']*)["\' ]/is', $avatar, $matches );
		$classes = explode( ' ', $matches[1] );
		$this->assertSame( $expected, array_intersect_key( $expected, $classes ) );

		// Class attribute is an Array
		$args['class'] = array( 'custom-class', 'class-custom' );
		$avatar = bp_core_fetch_avatar( $args );
		$expected = array_merge( $args['class'], array( 'user-' . $u . '-avatar', 'avatar-' . $hw ) );
		preg_match( '/class=["\']?([^"\']*)["\' ]/is', $avatar, $matches );
		$classes = explode( ' ', $matches[1] );
		$this->assertSame( $expected, array_intersect_key( $expected, $classes ) );
	}

	/**
	 * @group bp_core_check_avatar_type
	 */
	public function test_bp_core_check_avatar_type() {
		$plugin_dir = trailingslashit( buddypress()->plugin_dir );

		$file = array(
			'file' => array(
				'name' => 'humans.txt',
				'type' => 'text/plain',
				'tmp_name' => $plugin_dir . 'humans.txt',
			)
		);

		$this->assertFalse( bp_core_check_avatar_type( $file ) );

		$file = array(
			'file' => array(
				'name' => 'mystery-man.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => $plugin_dir . 'bp-core/images/mystery-man.jpg',
			)
		);

		$this->assertTrue( bp_core_check_avatar_type( $file ) );

		$file = array(
			'file' => array(
				'name' => 'mystery-man.jpg',
				'type' => 'application/octet-stream',
				'tmp_name' => $plugin_dir . 'bp-core/images/mystery-man.jpg',
			)
		);

		$this->assertTrue( bp_core_check_avatar_type( $file ), 'flash is using application/octet-stream for image uploads' );
	}

	/**
	 * @group bp_core_check_avatar_type
	 * @group bp_core_get_allowed_avatar_types
	 */
	public function test_bp_core_get_allowed_avatar_types_filter() {
		add_filter( 'bp_core_get_allowed_avatar_types', array( $this, 'avatar_types_filter_add_type' ) );

		$this->assertEquals( array( 'jpeg', 'gif', 'png' ), bp_core_get_allowed_avatar_types() );

		remove_filter( 'bp_core_get_allowed_avatar_types', array( $this, 'avatar_types_filter_add_type' ) );

		add_filter( 'bp_core_get_allowed_avatar_types', array( $this, 'avatar_types_filter_remove_type' ) );

		$this->assertEquals( array( 'gif', 'png' ), bp_core_get_allowed_avatar_types() );

		remove_filter( 'bp_core_get_allowed_avatar_types', array( $this, 'avatar_types_filter_remove_type' ) );

		add_filter( 'bp_core_get_allowed_avatar_types', '__return_empty_array' );

		$this->assertEquals( array( 'jpeg', 'gif', 'png' ), bp_core_get_allowed_avatar_types() );

		remove_filter( 'bp_core_get_allowed_avatar_types', '__return_empty_array' );
	}

	/**
	 * @group bp_core_check_avatar_type
	 * @group bp_core_get_allowed_avatar_mimes
	 */
	public function test_bp_core_get_allowed_avatar_mimes() {
		$mimes = bp_core_get_allowed_avatar_mimes();

		$this->assertEqualSets( array( 'jpeg', 'gif', 'png', 'jpg' ), array_keys( $mimes ) );
		$this->assertEqualSets( array( 'image/jpeg', 'image/gif', 'image/png', 'image/jpeg' ), array_values( $mimes ) );

		add_filter( 'bp_core_get_allowed_avatar_types', array( $this, 'avatar_types_filter_add_type' ) );

		$this->assertEqualSets( array( 'image/jpeg', 'image/gif', 'image/png', 'image/jpeg' ), array_values( bp_core_get_allowed_avatar_mimes() ) );

		remove_filter( 'bp_core_get_allowed_avatar_types', array( $this, 'avatar_types_filter_add_type' ) );

		add_filter( 'bp_core_get_allowed_avatar_types', array( $this, 'avatar_types_filter_remove_type' ) );

		$this->assertEqualSets( array( 'image/gif', 'image/png' ), array_values( bp_core_get_allowed_avatar_mimes() ) );

		remove_filter( 'bp_core_get_allowed_avatar_types', array( $this, 'avatar_types_filter_remove_type' ) );

		add_filter( 'bp_core_get_allowed_avatar_types', '__return_empty_array' );

		$this->assertEqualSets( array( 'image/jpeg', 'image/gif', 'image/png', 'image/jpeg' ), array_values( bp_core_get_allowed_avatar_mimes() ) );

		remove_filter( 'bp_core_get_allowed_avatar_types', '__return_empty_array' );
	}

	public function avatar_types_filter_add_type( $types ) {
		$types[] = 'bmp';

		return $types;
	}

	public function avatar_types_filter_remove_type( $types ) {
		$jpeg = array_shift( $types );

		return $types;
	}

	/**
	 * @group BP7056
	 */
	public function test_no_grav_default_should_respect_thumb_type() {
		$found = bp_core_fetch_avatar( array(
			'item_id' => 12345,
			'object' => 'user',
			'type' => 'thumb',
			'no_grav' => true,
			'html' => false,
		) );

		$this->assertContains( 'mystery-man-50.jpg', $found );
	}

	/**
	 * @group BP7056
	 */
	public function test_no_grav_default_should_return_thumb_avatar_for_small_enough_width() {
		$found = bp_core_fetch_avatar( array(
			'item_id' => 12345,
			'object' => 'user',
			'type' => 'full',
			'width' => '50',
			'no_grav' => true,
			'html' => false,
		) );

		$this->assertContains( 'mystery-man-50.jpg', $found );
	}

	/**
	 * @group BP7056
	 */
	public function test_no_grav_default_should_return_full_avatar_for_thumb_when_thumb_width_is_too_wide() {
		add_filter( 'bp_core_avatar_thumb_width', array( $this, 'filter_thumb_width' ) );
		$found = bp_core_fetch_avatar( array(
			'item_id' => 12345,
			'object' => 'user',
			'type' => 'thumb',
			'no_grav' => true,
			'html' => false,
		) );
		remove_filter( 'bp_core_avatar_thumb_width', array( $this, 'filter_thumb_width' ) );

		$this->assertContains( 'mystery-man.jpg', $found );
	}

	public function filter_thumb_width() {
		return 51;
	}
}
