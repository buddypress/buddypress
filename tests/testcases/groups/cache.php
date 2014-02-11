<?php

/**
 * @group groups
 * @group cache
 */
class BP_Tests_Group_Cache extends BP_UnitTestCase {
	/**
	 * @group bp_groups_update_meta_cache
	 */
	public function test_bp_groups_update_meta_cache() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();

		$time = bp_core_current_time();

		// Set up some data
		groups_update_groupmeta( $g1, 'total_member_count', 4 );
		groups_update_groupmeta( $g1, 'last_activity', $time );
		groups_update_groupmeta( $g1, 'foo', 'bar' );

		groups_update_groupmeta( $g2, 'total_member_count', 81 );
		groups_update_groupmeta( $g2, 'last_activity', $time );
		groups_update_groupmeta( $g2, 'foo', 'baz' );

		// Prime the cache for $g1
		groups_update_groupmeta( $g1, 'foo', 'bar' );
		groups_get_groupmeta( $g1, 'foo' );

		// Ensure an empty cache for $g2
		wp_cache_delete( $g2, 'group_meta' );

		bp_groups_update_meta_cache( array( $g1, $g2 ) );

		$expected = array(
			$g1 => array(
				'total_member_count' => array(
					4,
				),
				'last_activity' => array(
					$time,
				),
				'foo' => array(
					'bar',
				),
			),
			$g2 => array(
				'total_member_count' => array(
					81,
				),
				'last_activity' => array(
					$time,
				),
				'foo' => array(
					'baz',
				),
			),
		);

		$found = array(
			$g1 => wp_cache_get( $g1, 'group_meta' ),
			$g2 => wp_cache_get( $g2, 'group_meta' ),
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
