<?php

/**
 * @group core
 * @group nav
 */
class BP_Tests_Core_Nav extends BP_UnitTestCase {

	/**
	 * @group bp_core_sort_nav_items
	 */
	public function test_bp_core_sort_nav_items() {
		$bp_nav = buddypress()->bp_nav;

		$u = $this->factory->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->go_to( bp_core_get_user_domain( $u ) );

		bp_core_new_nav_item( array(
			'name'                    => 'Foo',
			'slug'                    => 'foo',
			'position'                => 25,
			'screen_function'         => 'foo_screen_function',
			'default_subnav_slug'     => 'foo-sub'
		) );

		$expected = array(
			'name'                    => 'Foo',
			'slug'                    => 'foo',
			'link'                    => trailingslashit( bp_core_get_user_domain( $u ) . 'foo' ),
			'css_id'                  => 'foo',
			'show_for_displayed_user' => true,
			'position'                => 25,
			'screen_function'         => 'foo_screen_function',
			'default_subnav_slug'     => 'foo-sub'
		);

		bp_core_sort_nav_items();

		$this->assertSame( buddypress()->bp_nav[25], $expected );

		// Clean up
		buddypress()->bp_nav = $bp_nav;
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_core_sort_subnav_items
	 */
	public function test_bp_core_sort_subnav_items() {
		$bp_options_nav = buddypress()->bp_options_nav;

		$u = $this->factory->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$user_domain = bp_core_get_user_domain( $u );

		$this->go_to( $user_domain );

		bp_core_new_subnav_item( array(
			'name'            => 'Foo',
			'slug'            => 'foo',
			'parent_url'      => trailingslashit( $user_domain . 'foo' ),
			'parent_slug'     => 'foo',
			'screen_function' => 'foo_screen_function',
			'position'        => 10
		) );

		bp_core_sort_subnav_items();

		$expected = array(
			'name'              => 'Foo',
			'link'              => trailingslashit( $user_domain . 'foo/foo' ),
			'slug'              => 'foo',
			'css_id'            => 'foo',
			'position'          => 10,
			'user_has_access'   => true,
			'no_access_url'     => '',
			'screen_function'   => 'foo_screen_function',
			'show_in_admin_bar' => false,
		);

		$this->assertSame( buddypress()->bp_options_nav['foo'][10], $expected );

		// Clean up
		buddypress()->bp_options_nav = $bp_options_nav;
		$this->set_current_user( $old_current_user );
	}
}
