<?php

/**
 * @group cache
 * @group members
 */
class BP_Tests_Members_Cache extends BP_UnitTestCase {
	/**
	 * @group bp_core_get_total_member_count
	 * @group cache
	 */
	public function test_bp_core_get_total_member_count_should_respect_cached_value_of_0() {
		global $wpdb;

		// set cached value to zero
		wp_cache_set( 'bp_total_member_count', 0, 'bp' );
		$num_queries = $wpdb->num_queries;

		// run function
		bp_core_get_total_member_count();

		// check if function references cache or hits the DB by comparing query count
		$this->assertEquals( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @ticket BP7237
	 * @ticket BP6643
	 * @ticket BP7245
	 */
	public function test_last_activity_should_bust_activity_with_last_activity_cache() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$time_1 = date( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS );
		$time_2 = date( 'Y-m-d H:i:s', time() - ( HOUR_IN_SECONDS * 2 ) );
		bp_update_user_last_activity( $u1, $time_1 );
		bp_update_user_last_activity( $u2, $time_2 );

		$activity_args_a = array(
			'filter' => array(
				'object' => buddypress()->members->id,
				'action' => 'last_activity',
			),
			'max' => 1,
		);

		$activity_args_b = array(
			'filter' => array(
				'action' => 'new_member',
			),
			'fields' => 'ids',
		);

		// Prime bp_activity and bp_activity_with_last_activity caches.
		$a1 = bp_activity_get( $activity_args_a );
		$expected = array( $u1, $u2 );
		$found = array_map( 'intval', wp_list_pluck( $a1['activities'], 'user_id' ) );
		$this->assertSame( $expected, $found );

		$b1 = bp_activity_get( $activity_args_b );

		// Bump u2 activity so it should appear first.
		$new_time = date( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS );
		bp_update_user_last_activity( $u2, $new_time );

		$a2 = bp_activity_get( $activity_args_a );
		$expected = array( $u2, $u1 );
		$found = array_map( 'intval', wp_list_pluck( $a2['activities'], 'user_id' ) );
		$this->assertSame( $expected, $found );

		$num_queries = $wpdb->num_queries;

		// bp_activity cache should not have been touched.
		$b2 = bp_activity_get( $activity_args_b );
		$this->assertEqualSets( $b1, $b2 );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}
}

