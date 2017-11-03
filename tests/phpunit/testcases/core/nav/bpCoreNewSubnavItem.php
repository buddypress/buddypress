<?php
/**
 * @group core
 * @group nav
 */
class BP_Tests_Core_Nav_BpCoreNewSubnavItem extends BP_UnitTestCase {

	/**
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_user_subnav() {
		$bp_options_nav = buddypress()->bp_options_nav;

		$u = self::factory()->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$user_domain = bp_core_get_user_domain( $u );

		$this->go_to( $user_domain );

		bp_core_new_nav_item( array(
			'name'            => 'Foo Parent',
			'slug'            => 'foo-parent',
			'link'            => trailingslashit( $user_domain . 'foo-parent' ),
			'screen_function' => 'foo_screen_function',
			'position'        => 10,
		) );

		bp_core_new_subnav_item( array(
			'name'            => 'Foo',
			'slug'            => 'foo',
			'parent_url'      => trailingslashit( $user_domain . 'foo-parent' ),
			'parent_slug'     => 'foo-parent',
			'screen_function' => 'foo_screen_function',
			'position'        => 10
		) );

		$expected = array(
			'name'              => 'Foo',
			'link'              => trailingslashit( $user_domain . 'foo-parent/foo' ),
			'slug'              => 'foo',
			'css_id'            => 'foo',
			'position'          => 10,
			'user_has_access'   => true,
			'no_access_url'     => '',
			'screen_function'   => 'foo_screen_function',
			'show_in_admin_bar' => false,
		);

		foreach ( $expected as $k => $v ) {
			$this->assertSame( $v, buddypress()->bp_options_nav['foo-parent']['foo'][ $k ] );
		}

		// Clean up
		buddypress()->bp_options_nav = $bp_options_nav;
		$this->set_current_user( $old_current_user );
	}

	public function test_required_params() {
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

	public function test_site_admin_only() {
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
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_link_provided() {
		$bp_options_nav = buddypress()->bp_options_nav;

		bp_core_new_nav_item( array(
			'name' => 'Foo',
			'slug' => 'foo',
			'screen_function' => 'foo',
			'link' => 'https://buddypress.org/',
		) );

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
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_link_built_from_parent_url_and_slug() {
		$bp_options_nav = buddypress()->bp_options_nav;

		bp_core_new_nav_item( array(
			'name' => 'Foo',
			'slug' => 'foo',
			'screen_function' => 'foo',
			'link' => 'https://buddypress.org/',
		) );

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
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_link_built_from_parent_url_and_slug_where_slug_is_default() {
		$bp_nav = buddypress()->bp_nav;
		$bp_options_nav = buddypress()->bp_options_nav;

		bp_core_new_nav_item( array(
			'name' => 'Foo',
			'slug' => 'foo',
			'screen_function' => 'foo',
			'default_subnav_slug' => 'bar',
		) );

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
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_should_trailingslash_link_when_link_is_autogenerated_using_slug() {
		bp_core_new_nav_item( array(
			'name' => 'Foo',
			'slug' => 'foo',
			'screen_function' => 'foo',
			'link' => 'https://buddypress.org/',
		) );

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
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_should_trailingslash_link_when_link_is_autogenerated_not_using_slug() {
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
			'parent_url' => bp_get_root_domain() . '/foo-parent/',
			'screen_function' => 'bar',
		) );

		$expected = bp_get_root_domain() . '/foo-parent/';
		$this->assertSame( $expected, buddypress()->bp_options_nav['foo-parent']['bar']['link'] );
	}

	/**
	 * @ticket BP6353
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_link_should_not_trailingslash_link_explicit_link() {
		$link = 'http://example.com/foo/bar/blah/?action=edit&id=30';

		bp_core_new_nav_item( array(
			'name' => 'Foo',
			'slug' => 'foo',
			'screen_function' => 'foo',
			'link' => 'http://example.com/foo/',
		) );

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

	public function test_should_return_false_if_site_admin_only_and_current_user_cannot_bp_moderate() {
		bp_core_new_nav_item( array(
			'name' => 'Foo',
			'slug' => 'foo',
			'screen_function' => 'foo',
		) );

		// Should already be set to a 0 user.
		$this->assertFalse( bp_current_user_can( 'bp_moderate' ) );
		$args = array(
			'name' => 'Foo',
			'slug' => 'foo',
			'parent_slug' => 'parent',
			'parent_url' => bp_get_root_domain() . '/parent/',
			'screen_function' => 'foo',
			'site_admin_only' => true,
		);

		$this->assertFalse( bp_core_new_subnav_item( $args ) );
	}

	/**
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_css_id_should_fall_back_on_slug() {
		bp_core_new_nav_item( array(
			'name' => 'Parent',
			'slug' => 'parent',
			'screen_function' => 'foo',
		) );

		$args = array(
			'name' => 'Foo',
			'slug' => 'foo',
			'parent_slug' => 'parent',
			'parent_url' => bp_get_root_domain() . '/parent/',
			'screen_function' => 'foo',
		);
		bp_core_new_subnav_item( $args );

		$this->assertSame( 'foo', buddypress()->bp_options_nav['parent']['foo']['css_id'] );
	}

	/**
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_css_id_should_be_respected() {
		bp_core_new_nav_item( array(
			'name' => 'Parent',
			'slug' => 'parent',
			'screen_function' => 'foo',
		) );

		$args = array(
			'name' => 'Foo',
			'slug' => 'foo',
			'parent_slug' => 'parent',
			'parent_url' => bp_get_root_domain() . '/parent/',
			'screen_function' => 'foo',
			'item_css_id' => 'bar',
		);
		bp_core_new_subnav_item( $args );

		$this->assertSame( 'bar', buddypress()->bp_options_nav['parent']['foo']['css_id'] );
	}
}
