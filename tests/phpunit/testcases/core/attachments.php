<?php

/**
 * @group core
 */

class BP_Tests_Core_Attachments extends BP_UnitTestCase {
	/**
	 * @group bp_attachments_list_directory_files_recursively
	 */
	public function test_bp_attachments_list_directory_files_recursively() {
		$files = bp_attachments_list_directory_files_recursively( BP_TESTS_DIR . 'assets', 'index' );

		$this->assertTrue( 1 === count( $files ) );
		$this->assertTrue( isset( $files['templates/index'] ) );
	}
}
