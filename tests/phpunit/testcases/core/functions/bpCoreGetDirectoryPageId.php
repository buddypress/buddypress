<?php

/**
 * @group core
 * @group BP7025
 */
class BP_Tests_Core_BpCoreGetDirectoryPageId extends BP_UnitTestCase {
	public function test_should_fall_back_on_current_component() {
		$this->go_to( bp_get_activity_directory_permalink() );

		$found = bp_core_get_directory_page_id();

		$pages = bp_core_get_directory_page_ids();
		$this->assertSame( $pages['activity'], $found );
	}

	public function test_should_accept_component_override() {
		$this->go_to( bp_get_activity_directory_permalink() );

		$found = bp_core_get_directory_page_id( 'members' );

		$pages = bp_core_get_directory_page_ids();
		$this->assertSame( $pages['members'], $found );
	}

	public function test_should_return_false_for_invalid_component() {
		$found = bp_core_get_directory_page_id( 'foo' );

		$pages = bp_core_get_directory_page_ids();
		$this->assertFalse( $found );
	}
}
