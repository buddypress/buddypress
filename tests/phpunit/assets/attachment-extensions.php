<?php
/**
 * The following implementations of BP_Attachment act as dummy plugins
 * for our unit tests
 */
class BPTest_Attachment_Extension extends BP_Attachment {
	public function __construct( $args = array() ) {
		return parent::__construct( $args );
	}

	public function upload_dir_filter( $upload_dir = array() ) {
		$this->original_upload_dir = $upload_dir;

		return parent::upload_dir_filter( $upload_dir );
	}
}
