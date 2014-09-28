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
		groups_get_group( array( 'group_id' => $g, ) );

		$this->assertNotEmpty( wp_cache_get( $g, 'bp_groups' ) );

		// Trigger flush
		groups_update_groupmeta( $g, 'foo', 'bar' );

		$this->assertFalse( wp_cache_get( $g, 'bp_groups' ) );
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

		$this->assertNotEmpty( wp_cache_get( $g, 'bp_groups' ) );

		// Trigger flush
		groups_update_groupmeta( $g, 'foo', 'baz' );
		$this->assertFalse( wp_cache_get( $g, 'bp_groups' ) );
	}

	/**
	 * @group bp_groups_prefetch_activity_object_data
	 */
	public function test_bp_groups_prefetch_activity_object_data_all_cached() {
		$g = $this->factory->group->create();

		// Prime cache
		groups_get_group( array( 'group_id' => $g ) );

		// fake an activity
		$a = new stdClass;
		$a->component = buddypress()->groups->id;
		$a->item_id = $g;
		$activities = array(
			$a,
		);

		bp_groups_prefetch_activity_object_data( $activities );

		// This assertion is not really necessary - just checks to see
		// whether a fatal error has occurred above
		$this->assertNotEmpty( wp_cache_get( $g, 'bp_groups' ) );
	}

	/**
	 * @group bp_groups_prefetch_activity_object_data
	 */
	public function test_bp_groups_prefetch_activity_object_data_some_cached() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();

		// Prime cache
		groups_get_group( array( 'group_id' => $g1 ) );

		// fake activities
		$a1 = new stdClass;
		$a1->component = buddypress()->groups->id;
		$a1->item_id = $g1;

		$a2 = new stdClass;
		$a2->component = buddypress()->groups->id;
		$a2->item_id = $g2;

		$activities = array(
			$a1,
			$a2,
		);

		bp_groups_prefetch_activity_object_data( $activities );

		$this->assertNotEmpty( wp_cache_get( $g1, 'bp_groups' ) );
		$this->assertNotEmpty( wp_cache_get( $g2, 'bp_groups' ) );
	}

	/**
	 * @group groups_get_group_admins
	 */
	public function test_groups_get_group_admins_cache() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$g = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		// prime cache
		groups_get_group_admins( $g );

		// promote user 2 to an admin
		bp_update_is_item_admin( true );
		groups_promote_member( $u2, $g, 'admin' );

		// assert that cache is invalidated
		$this->assertEmpty( wp_cache_get( $g, 'bp_group_admins' ) );

		// assert new cached value
		$this->assertEquals( 2, count( groups_get_group_admins( $g ) ) );
	}

	/**
	 * @group groups_get_group_admins
	 */
	public function test_groups_get_group_admins_cache_on_member_save() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();
		$g = $this->factory->group->create( array( 'creator_id' => $u1 ) );

		// prime cache
		groups_get_group_admins( $g );

		// promote user 2 to an admin via BP_Groups_Member::save()
		self::add_user_to_group( $u2, $g, array( 'is_admin' => 1 ) );

		// assert that cache is invalidated
		$this->assertEmpty( wp_cache_get( $g, 'bp_group_admins' ) );

		// assert new cached value
		$this->assertEquals( 2, count( groups_get_group_admins( $g ) ) );
	}
}
