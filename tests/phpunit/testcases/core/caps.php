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

	public function grant_cap_foo( $allcaps, $caps ) {
		if ( bp_is_root_blog() ) {
			$allcaps['foo'] = 1;
		}

		return $allcaps;
	}
}
