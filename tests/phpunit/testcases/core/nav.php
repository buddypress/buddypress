<?php

/**
 * @group core
 * @group nav
 */
class BP_Tests_Core_Nav extends BP_UnitTestCase {

	/**
	 * @group bp_core_new_nav_item
	 */
	public function test_bp_core_new_nav_item_user_nav() {
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

	/**
	 * @group bp_core_new_nav_item
	 */
	public function test_bp_core_new_nav_item_group_nav() {
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
	 * @group bp_core_new_subnav_item
	 */
	public function test_bp_core_new_subnav_item_user_subnav() {
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

		$this->assertSame( buddypress()->bp_options_nav['foo']['foo'], $expected );

		// Clean up
		buddypress()->bp_options_nav = $bp_options_nav;
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_core_new_subnav_item
	 */
	public function test_bp_core_new_subnav_item_required_params() {
		// 'name'
		$this->assertFalse( bp_core_new_subnav_item( array(
			'slug' => 'foo',
			'parent_slug' => 'foo',
			'parent_url' => 'foo',
			'screen_function' => 'foo',
		) ) );

		// 'slug'
		$this->assertFalse( bp_core_new_subnav_item( array(
			'name' => 'foo',
			'parent_slug' => 'foo',
			'parent_url' => 'foo',
			'screen_function' => 'foo',
		) ) );

		// 'parent_slug'
		$this->assertFalse( bp_core_new_subnav_item( array(
			'name' => 'foo',
			'slug' => 'foo',
			'parent_url' => 'foo',
			'screen_function' => 'foo',
		) ) );

		// 'parent_url'
		$this->assertFalse( bp_core_new_subnav_item( array(
			'name' => 'foo',
			'slug' => 'foo',
			'parent_slug' => 'foo',
			'screen_function' => 'foo',
		) ) );

		// 'screen_function'
		$this->assertFalse( bp_core_new_subnav_item( array(
			'name' => 'foo',
			'slug' => 'foo',
			'parent_slug' => 'foo',
			'parent_url' => 'foo',
		) ) );
	}

	/**
	 * @group bp_core_new_subnav_item
	 */
	public function test_bp_core_new_subnav_item_site_admin_only() {
		$old_current_user = get_current_user_id();
		$this->set_current_user( 0 );

		$this->assertFalse( bp_core_new_subnav_item( array(
			'name' => 'foo',
			'slug' => 'foo',
			'parent_slug' => 'foo',
			'parent_url' => 'foo',
			'screen_function' => 'foo',
			'site_admin_only' => true,
		) ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_core_new_subnav_item
	 */
	public function test_bp_core_new_subnav_item_link_provided() {
		$bp_options_nav = buddypress()->bp_options_nav;

		bp_core_new_subnav_item( array(
			'name' => 'bar',
			'slug' => 'bar',
			'parent_slug' => 'foo',
			'parent_url' => 'foo',
			'screen_function' => 'foo',
			'link' => 'https://buddypress.org/',
		) );

		$this->assertSame( 'https://buddypress.org/', buddypress()->bp_options_nav['foo']['bar']['link'] );

		buddypress()->bp_options_nav = $bp_options_nav;
	}

	/**
	 * @group bp_core_new_subnav_item
	 */
	public function test_bp_core_new_subnav_item_link_built_from_parent_url_and_slug() {
		$bp_options_nav = buddypress()->bp_options_nav;

		bp_core_new_subnav_item( array(
			'name' => 'bar',
			'slug' => 'bar',
			'parent_slug' => 'foo',
			'parent_url' => 'http://example.com/foo/',
			'screen_function' => 'foo',
		) );

		$this->assertSame( 'http://example.com/foo/bar/', buddypress()->bp_options_nav['foo']['bar']['link'] );

		buddypress()->bp_options_nav = $bp_options_nav;
	}

	/**
	 * @group bp_core_new_subnav_item
	 */
	public function test_bp_core_new_subnav_item_link_built_from_parent_url_and_slug_where_slug_is_default() {
		$bp_nav = buddypress()->bp_nav;
		$bp_options_nav = buddypress()->bp_options_nav;

		// fake the parent
		buddypress()->bp_nav = array(
			'foo' => array(
				'default_subnav_slug' => 'bar',
			),
		);

		bp_core_new_subnav_item( array(
			'name' => 'bar',
			'slug' => 'bar',
			'parent_slug' => 'foo',
			'parent_url' => 'http://example.com/foo/',
			'screen_function' => 'foo',
		) );

		$this->assertSame( 'http://example.com/foo/', buddypress()->bp_options_nav['foo']['bar']['link'] );

		// clean up
		buddypress()->bp_nav = $bp_nav;
		buddypress()->bp_options_nav = $bp_options_nav;
	}

	/**
	 * @group bp_core_maybe_hook_new_subnav_screen_function
	 */
	public function test_maybe_hook_new_subnav_screen_function_user_has_access_true_no_callable_function() {
		$subnav_item = array(
			'user_has_access' => true,
			'screen_function' => '123foo456',
		);

		$expected = array(
			'status' => 'failure',
		);

		$this->assertSame( $expected, bp_core_maybe_hook_new_subnav_screen_function( $subnav_item ) );
	}

	/**
	 * @group bp_core_maybe_hook_new_subnav_screen_function
	 */
	public function test_maybe_hook_new_subnav_screen_function_user_has_access_true_callable_function() {
		$subnav_item = array(
			'user_has_access' => true,
			'screen_function' => 'wptexturize', // any old callable function
		);

		$expected = array(
			'status' => 'success',
		);

		$this->assertSame( $expected, bp_core_maybe_hook_new_subnav_screen_function( $subnav_item ) );

		// clean up
		remove_action( 'bp_screens', 'wptexturize', 3 );
	}

	/**
	 * @group bp_core_maybe_hook_new_subnav_screen_function
	 */
	public function test_maybe_hook_new_subnav_screen_function_user_has_access_false_user_logged_out() {
		$old_current_user = get_current_user_id();
		$this->set_current_user( 0 );

		$subnav_item = array(
			'user_has_access' => false,
		);

		$expected = array(
			'status' => 'failure',
			'redirect_args' => array(),
		);

		$this->assertSame( $expected, bp_core_maybe_hook_new_subnav_screen_function( $subnav_item ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_core_maybe_hook_new_subnav_screen_function
	 */
	public function test_maybe_hook_new_subnav_screen_function_user_has_access_false_user_logged_in_my_profile() {
		$u = $this->factory->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->go_to( bp_core_get_user_domain( $u ) );

		$subnav_item = array(
			'user_has_access' => false,
		);

		// Just test relevant info
		$found = bp_core_maybe_hook_new_subnav_screen_function( $subnav_item );
		$this->assertSame( 'failure', $found['status'] );
		$this->assertSame( bp_core_get_user_domain( $u ), $found['redirect_args']['root'] );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_core_maybe_hook_new_subnav_screen_function
	 */
	public function test_maybe_hook_new_subnav_screen_function_user_has_access_false_user_logged_in_others_profile_default_component_accessible() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u1 );

		$this->go_to( bp_core_get_user_domain( $u2 ) );

		$old_bp_nav = buddypress()->bp_nav;
		$old_default_component = buddypress()->default_component;
		buddypress()->default_component = 'foo';
		buddypress()->bp_nav = array(
			'foo' => array(
				'show_for_displayed_user' => true,
			),
		);

		$subnav_item = array(
			'user_has_access' => false,
		);

		// Just test relevant info
		$found = bp_core_maybe_hook_new_subnav_screen_function( $subnav_item );
		$this->assertSame( 'failure', $found['status'] );
		$this->assertSame( bp_core_get_user_domain( $u2 ), $found['redirect_args']['root'] );

		// Clean up
		$this->set_current_user( $old_current_user );
		buddypress()->default_component = $old_default_component;
		buddypress()->bp_nav = $old_bp_nav;
	}

	/**
	 * @group bp_core_maybe_hook_new_subnav_screen_function
	 */
	public function test_maybe_hook_new_subnav_screen_function_user_has_access_false_user_logged_in_others_profile_default_component_not_accessible() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u1 );

		$this->go_to( bp_core_get_user_domain( $u2 ) );

		$old_bp_nav = buddypress()->bp_nav;
		$old_default_component = buddypress()->default_component;
		buddypress()->default_component = 'foo';
		buddypress()->bp_nav = array(
			'foo' => array(
				'show_for_displayed_user' => false,
			),
		);

		$subnav_item = array(
			'user_has_access' => false,
		);

		// Just test relevant info
		$found = bp_core_maybe_hook_new_subnav_screen_function( $subnav_item );
		$this->assertSame( 'failure', $found['status'] );
		$this->assertSame( bp_core_get_user_domain( $u2 ) . bp_get_activity_slug() . '/', $found['redirect_args']['root'] );

		// Clean up
		$this->set_current_user( $old_current_user );
		buddypress()->default_component = $old_default_component;
		buddypress()->bp_nav = $old_bp_nav;
	}

	/**
	 * @group bp_core_maybe_hook_new_subnav_screen_function
	 */
	public function test_maybe_hook_new_subnav_screen_function_user_has_access_false_user_logged_in_group() {
		$u = $this->factory->user->create();
		$g = $this->factory->group->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$group = groups_get_group( array(
			'group_id' => $g,
		) );

		$this->go_to( bp_get_group_permalink( $group ) );

		$subnav_item = array(
			'user_has_access' => false,
			'no_access_url' => bp_get_group_permalink( $group ),
		);

		// Just test relevant info
		$found = bp_core_maybe_hook_new_subnav_screen_function( $subnav_item );
		$this->assertSame( 'failure', $found['status'] );
		$this->assertSame( bp_get_group_permalink( $group ), $found['redirect_args']['root'] );

		// Clean up
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_core_maybe_hook_new_subnav_screen_function
	 */
	public function test_maybe_hook_new_subnav_screen_function_user_has_access_false_user_logged_in_group_no_redirect_url_provided() {
		$u = $this->factory->user->create();
		$g = $this->factory->group->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$group = groups_get_group( array(
			'group_id' => $g,
		) );

		$this->go_to( bp_get_group_permalink( $group ) );

		$subnav_item = array(
			'user_has_access' => false,
		);

		// Just test relevant info
		$found = bp_core_maybe_hook_new_subnav_screen_function( $subnav_item );
		$this->assertSame( 'failure', $found['status'] );
		$this->assertSame( bp_get_root_domain(), $found['redirect_args']['root'] );

		// Clean up
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_core_new_subnav_item()
	 */
	public function test_bp_core_new_subnav_item_should_trailingslash_link_when_link_is_autogenerated_using_slug() {
		bp_core_new_subnav_item( array(
			'name' => 'bar',
			'slug' => 'bar',
			'parent_slug' => 'foo',
			'parent_url' => bp_get_root_domain() . 'foo/',
			'screen_function' => 'foo',
		) );

		$expected = bp_get_root_domain() . 'foo/bar/';
		$this->assertSame( $expected, buddypress()->bp_options_nav['foo']['bar']['link'] );
	}

	/**
	 * @group bp_core_new_subnav_item()
	 */
	public function test_bp_core_new_subnav_item_should_trailingslash_link_when_link_is_autogenerated_not_using_slug() {
		bp_core_new_nav_item( array(
			'name' => 'foo',
			'slug' => 'foo-parent',
			'link' => bp_get_root_domain() . 'foo-parent/',
			'default_subnav_slug' => 'bar',
			'screen_function' => 'foo',
		) );

		bp_core_new_subnav_item( array(
			'name' => 'bar',
			'slug' => 'bar',
			'parent_slug' => 'foo-parent',
			'parent_url' => bp_get_root_domain() . 'foo-parent/',
			'screen_function' => 'bar',
		) );

		$expected = bp_get_root_domain() . 'foo-parent/';
		$this->assertSame( $expected, buddypress()->bp_options_nav['foo-parent']['bar']['link'] );
	}

	/**
	 * @group bp_core_new_subnav_item
	 * @ticket BP6353
	 */
	public function test_bp_core_new_subnav_item_link_should_not_trailingslash_link_explicit_link() {
		$link = 'http://example.com/foo/bar/blah/?action=edit&id=30';

		bp_core_new_subnav_item( array(
			'name' => 'bar',
			'slug' => 'bar',
			'parent_slug' => 'foo',
			'parent_url' => 'http://example.com/foo/',
			'screen_function' => 'foo',
			'link' => $link,
		) );

		$this->assertSame( $link, buddypress()->bp_options_nav['foo']['bar']['link'] );
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
