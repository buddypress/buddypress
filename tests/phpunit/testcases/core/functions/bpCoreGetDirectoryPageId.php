<?php

/**
 * @group core
 * @group BP7025
 * @covers ::bp_core_get_directory_page_id
 */
class BP_Tests_Core_BpCoreGetDirectoryPageId extends BP_UnitTestCase {
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();
		$this->permalink_structure = get_option( 'permalink_structure', '' );
	}

	public function tear_down() {
		parent::tear_down();
		$this->set_permalink_structure( $this->permalink_structure );
	}

	public function test_should_fall_back_on_current_component() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to( bp_get_activity_directory_permalink() );

		$found = bp_core_get_directory_page_id();

		$pages = bp_core_get_directory_page_ids();
		$this->assertSame( $pages['activity'], $found );
	}

	public function test_should_accept_component_override() {
		$this->set_permalink_structure( '/%postname%/' );
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
