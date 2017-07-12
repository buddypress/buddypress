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

	/**
	 * @ticket BP7203
	 */
	public function test_remove_subnav_item_array_as_screen_function() {
		$bp = buddypress();

		add_filter( 'bp_is_active', array( $this, 'foo_is_active' ), 10, 2 );

		$bp->foo = new stdClass;
		$bp->foo->nav = new BP_Core_Nav( 0 );

		$expected = array( 'foo', 'bar' );

		bp_core_new_nav_item( array(
			'name' => 'Foo',
			'slug' => 'foo',
			'screen_function' => $expected,
		), 'foo' );

		bp_core_new_subnav_item( array(
			'name' => 'Bar',
			'slug' => 'bar',
			'parent_slug' => 'foo',
			'parent_url' => 'foo',
			'screen_function' => $expected,
		), 'foo' );

		remove_filter( 'bp_is_active', array( $this, 'foo_is_active' ), 10 );

		$this->assertNotEmpty( $bp->foo->nav->get_primary( array( 'slug' => 'foo' ), false ) );

		$tested = $bp->foo->nav->delete_nav( 'foo' );
		$this->assertSame( $expected, reset( $tested ) );

		$this->assertEmpty( $bp->foo->nav->get_primary( array( 'slug' => 'foo' ), false ) );
	}

	/**
	 * Helper method to filter 'bp_is_active' for unit tests.
	 */
	public function foo_is_active( $retval, $component ) {
		if ( 'foo' === $component ) {
			$retval = true;
		}

		return $retval;
	}
}
