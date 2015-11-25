<?php

/**
 * @group core
 * @group caps
 */
class BP_Tests_Core_Caps extends BP_UnitTestCase {
	public function test_bp_current_user_can_should_interpret_integer_second_param_as_a_blog_id() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires multisite.' );
		}

		$b = $this->factory->blog->create();
		$u = $this->factory->user->create();

		$this->set_current_user( $u );

		add_filter( 'user_has_cap', array( $this, 'grant_cap_foo' ), 10, 2 );
		$can  = bp_current_user_can( 'foo', bp_get_root_blog_id() );
		$cant = bp_current_user_can( 'foo', $b );
		remove_filter( 'user_has_cap', array( $this, 'grant_cap_foo' ), 10, 2 );

		$this->assertTrue( $can );
		$this->assertFalse( $cant );
	}

	/**
	 * @ticket BP6501
	 */
	public function test_bp_current_user_can_should_respect_blog_id_passed_in_args_array() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires multisite.' );
		}

		$b = $this->factory->blog->create();
		$u = $this->factory->user->create();

		$this->set_current_user( $u );

		add_filter( 'user_has_cap', array( $this, 'grant_cap_foo' ), 10, 2 );
		$can  = bp_current_user_can( 'foo', array( 'blog_id' => bp_get_root_blog_id() ) );
		$cant = bp_current_user_can( 'foo', array( 'blog_id' => $b ) );
		remove_filter( 'user_has_cap', array( $this, 'grant_cap_foo' ), 10, 2 );

		$this->assertTrue( $can );
		$this->assertFalse( $cant );
	}

	/**
	 * @group bp_xprofile_change_field_visibility
	 */
	public function test_bp_current_user_can_should_pass_null_in_args_parameter_if_empty() {
		$u = $this->factory->user->create();
		$this->set_current_user( $u );

		/**
		 * Fake bp_get_the_profile_field_id() to pretend we're in the field loop and
		 * to avoid notices when checking 'bp_xprofile_change_field_visibility' cap
		 */
		$GLOBALS['field'] = new stdClass;
		$GLOBALS['field']->id = 1;

		// Capture the cap's $args
		add_filter( 'bp_xprofile_map_meta_caps', array( $this, 'check_cap_args' ), 10, 4 );

		// Use a cap check that depends on a null value for a cap's args
		bp_current_user_can( 'bp_xprofile_change_field_visibility' );

		// Assert!
		$this->assertEquals( null, $this->test_args[0] );

		// Reset
		remove_filter( 'bp_xprofile_map_meta_caps', array( $this, 'check_cap_args' ), 10, 4 );
		unset( $GLOBALS['field'], $this->test_args );
	}

	public function grant_cap_foo( $allcaps, $caps ) {
		if ( bp_is_root_blog() ) {
			$allcaps['foo'] = 1;
		}

		return $allcaps;
	}

	public function check_cap_args( $caps, $cap, $user_id, $args ) {
		$this->test_args = $args;
		return $caps;
	}
}
