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
}

