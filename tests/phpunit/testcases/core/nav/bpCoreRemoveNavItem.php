<?php

/**
 * @group core
 * @group nav
 */
class BP_Tests_Core_Nav_BpCoreRemoveNavItem extends BP_UnitTestCase {
	/**
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_should_remove_subnav_items() {
		$bp = buddypress();

		$_bp_nav = $bp->bp_nav;
		$_bp_options_nav = $bp->bp_options_nav;

		bp_core_new_nav_item( array(
			'name' => 'Foo',
			'slug' => 'foo',
			'screen_function' => 'foo',
		) );

		bp_core_new_subnav_item( array(
			'name' => 'Bar',
			'slug' => 'bar',
			'parent_slug' => 'foo',
			'parent_url' => 'foo',
			'screen_function' => 'bar',
		) );

		$this->assertTrue( isset( $bp->bp_nav['foo'] ) );
		$this->assertTrue( isset( $bp->bp_options_nav['foo'] ) );
		$this->assertTrue( isset( $bp->bp_options_nav['foo']['bar'] ) );

		bp_core_remove_nav_item( 'foo' );

		$this->assertFalse( isset( $bp->bp_options_nav['foo']['bar'] ) );

		$bp->bp_nav = $_bp_nav;
		$bp->bp_options_nav = $_bp_options_nav;
	}

	/**
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_should_remove_nav_item() {
		$bp = buddypress();

		$_bp_nav = $bp->bp_nav;
		$_bp_options_nav = $bp->bp_options_nav;

		bp_core_new_nav_item( array(
			'name' => 'Foo',
			'slug' => 'foo',
			'screen_function' => 'foo',
		) );

		$this->assertTrue( isset( $bp->bp_nav['foo'] ) );

		bp_core_remove_nav_item( 'foo' );

		$this->assertFalse( isset( $bp->bp_nav['foo'] ) );

		$bp->bp_nav = $_bp_nav;
		$bp->bp_options_nav = $_bp_options_nav;
	}
}
