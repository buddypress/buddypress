<?php

/**
 * @group activity
 * @group cache
 */
class BP_Tests_Activity_Cache extends BP_UnitTestCase {
	/**
	 * @group bp_activity_update_meta_cache
	 */
	public function test_bp_activity_update_meta_cache() {
		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create();

		// Set up some data
		bp_activity_update_meta( $a1, 'foo', 'bar' );
		bp_activity_update_meta( $a1, 'Boone', 'Rules' );

		bp_activity_update_meta( $a2, 'foo', 'baz' );
		bp_activity_update_meta( $a2, 'BuddyPress', 'Is Cool' );

		// Prime the cache for $a1
		bp_activity_get_meta( $a1, 'foo' );

		// Ensure an empty cache for $a2
		wp_cache_delete( $a2, 'activity_meta' );

		bp_activity_update_meta_cache( array( $a1, $a2 ) );

		$expected = array(
			$a1 => array(
				'foo' => array(
					'bar',
				),
				'Boone' => array(
					'Rules',
				),
			),
			$a2 => array(
				'foo' => array(
					'baz',
				),
				'BuddyPress' => array(
					'Is Cool',
				),
			),
		);

		$found = array(
			$a1 => wp_cache_get( $a1, 'activity_meta' ),
			$a2 => wp_cache_get( $a2, 'activity_meta' ),
		);

		$this->assertEquals( $expected, $found );
	}

	/**
	 * @group groups_update_groupmeta
	 * @group groups_delete_group_cache_on_metadata_change
	 */
	public function test_bp_groups_delete_group_cache_on_metadata_add() {
		$g = $this->factory->group->create();

		// Prime cache
		groups_get_group( array( 'group_id' => $g ) );

		$this->assertNotEmpty( wp_cache_get( 'bp_groups_group_' . $g . '_noload_users', 'bp' ) );

		// Trigger flush
		groups_update_groupmeta( $g, 'foo', 'bar' );

		$this->assertFalse( wp_cache_get( 'bp_groups_group_' . $g . '_noload_users', 'bp' ) );
	}

	/**
	 * @group groups_update_groupmeta
	 * @group groups_delete_group_cache_on_metadata_change
	 */
	public function test_bp_groups_delete_group_cache_on_metadata_change() {
		$g = $this->factory->group->create();

		// Prime cache
		groups_update_groupmeta( $g, 'foo', 'bar' );
		groups_get_group( array( 'group_id' => $g ) );

		$this->assertNotEmpty( wp_cache_get( 'bp_groups_group_' . $g . '_noload_users', 'bp' ) );

		// Trigger flush
		groups_update_groupmeta( $g, 'foo', 'baz' );
		$this->assertFalse( wp_cache_get( 'bp_groups_group_' . $g . '_noload_users', 'bp' ) );
	}

}
