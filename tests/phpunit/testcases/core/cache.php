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

	public function is_active_filter( $is_active, $component ) {
		if ( ! $is_active && 'attachments' === $component ) {
			$is_active = true;
		}

		return $is_active;
	}

	/**
	 * @group bp_core_add_page_mappings
	 * @group bp_core_get_directory_page_ids
	 * @ticket BP9076
	 */
	public function test_object_cache_for_directory_pages() {
		$bp = buddypress();

		wp_cache_delete( 'directory_pages', 'bp_pages' );
		$bp_pages    = bp_core_get_directory_pages();
		$cache       = wp_cache_get( 'directory_pages', 'bp_pages' );
		$member_page = get_post( $bp->pages->members->id );

		$this->assertNotEmpty( $cache );
		$this->assertNotEmpty( $member_page->ID );

		$orphaned_component = array(
			'attachments' => array(
				'name'  => 'bp-attachments',
				'title' => 'Community Media',
			)
		);

		add_filter( 'bp_is_active', array( $this, 'is_active_filter' ), 10, 2 );

		bp_core_add_page_mappings( $orphaned_component, 'keep', true );

		$updated_cache       = wp_cache_get( 'directory_pages', 'bp_pages' );
		$updated_member_page = get_post( $bp->pages->members->id );

		$this->assertEmpty( $updated_cache );
		$this->assertEquals( $updated_member_page->ID, $member_page->ID );

		$bp_pages        = bp_core_get_directory_pages();
		$new_cache       = wp_cache_get( 'directory_pages', 'bp_pages' );
		$new_member_page = get_post( $bp->pages->members->id );

		remove_filter( 'bp_is_active', array( $this, 'is_active_filter' ), 10 );

		$this->assertNotEmpty( $new_cache->attachments );
		$this->assertEquals( $new_member_page->ID, $member_page->ID );
	}
}

