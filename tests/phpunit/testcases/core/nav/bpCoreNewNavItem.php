<?php
/**
 * @group core
 * @group nav
 */
class BP_Tests_Core_Nav_BpCoreNewNavItem extends BP_UnitTestCase {

	public function test_user_nav() {
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

		$this->assertSame( buddypress()->bp_nav['foo'], $expected );

		// Clean up
		buddypress()->bp_nav = $bp_nav;
		$this->set_current_user( $old_current_user );
	}

	public function test_group_nav() {
		$bp_nav = buddypress()->bp_nav;

		$u = $this->factory->user->create();
		$g = $this->factory->group->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$group = groups_get_group( array(
			'group_id' => $g,
		) );

		$this->go_to( bp_get_group_permalink( $group ) );

		$this->assertTrue( buddypress()->bp_nav[ $group->slug ]['position'] === -1 );

		// Clean up
		buddypress()->bp_nav = $bp_nav;
		$this->set_current_user( $old_current_user );
	}
}
