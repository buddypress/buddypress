<?php

/**
 * @group cache
 * @group core
 */
class BP_Tests_Core_Cache extends BP_UnitTestCase {
	/**
	 * @group bp_update_meta_cache
	 */
	public function test_bp_update_meta_cache_with_cache_misses() {
		// Use activity just because
		$a1 = self::factory()->activity->create();

		// Confirm that all activitymeta is deleted
		global $wpdb;

		$bp = buddypress();

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->activity->table_name_meta} WHERE activity_id = %d", $a1 ) );

		bp_update_meta_cache( array(
			'object_ids' => array( $a1 ),
			'object_type' => 'activity',
			'cache_group' => 'activity_meta',
			'meta_table' => buddypress()->activity->table_name_meta,
			'object_column' => 'activity_id',
		) );

		$this->assertSame( array(), wp_cache_get( $a1, 'activity_meta' ) );
	}
}

