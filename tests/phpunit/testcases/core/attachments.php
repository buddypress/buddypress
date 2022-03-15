<?php

/**
 * @group core
 */

class BP_Tests_Core_Attachments extends BP_UnitTestCase {
	/**
	 * @group bp_attachments_list_directory_files
	 */
	public function test_bp_attachments_list_directory_files() {
		$files = bp_attachments_list_directory_files( BP_TESTS_DIR . 'assets' );

		$expected_keys = array( 'name', 'path', 'size', 'type', 'mime_type', 'last_modified', 'latest_access_date', 'id' );

		$directories = wp_filter_object_list( $files, array( 'mime_type' => 'directory' ) );
		$directory   = reset( $directories );

		$keys = array_keys( get_object_vars( $directory ) );
		sort( $keys );
		sort( $expected_keys );

		$this->assertSame( $keys, $expected_keys );

		$images = wp_filter_object_list( $files, array( 'mime_type' => 'image/jpeg' ) );
		$this->assertNotEmpty( $images );

		$scripts = wp_filter_object_list( $files, array( 'mime_type' => 'text/x-php' ) );
		$this->assertEmpty( $scripts );
	}

	/**
	 * @group bp_attachments_list_directory_files_recursively
	 */
	public function test_bp_attachments_list_directory_files_recursively() {
		add_filter( 'mime_types', array( $this, 'filter_mime_types' ) );
		$files = bp_attachments_list_directory_files_recursively( BP_TESTS_DIR . 'assets', 'index' );
		remove_filter( 'mime_types', array( $this, 'filter_mime_types' ) );

		$this->assertTrue( 1 === count( $files ) );
		$this->assertTrue( isset( $files['templates/index'] ) );
	}

	public function filter_mime_types( $mime_types ) {
		$mime_types['php'] = 'text/x-php';
		return $mime_types;
	}
}
