<?php

/**
 * @group core
 * @group nav
 */
class BP_Tests_Core_Nav extends BP_UnitTestCase {
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
}
