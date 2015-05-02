<?php
/**
 * The following implementations of BP_Attachment act as dummy plugins
 * for our unit tests
 */
class BPTest_Attachment_Extension extends BP_Attachment {
	public function __construct( $args = array() ) {
		return parent::__construct( $args );
	}
}
