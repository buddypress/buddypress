<?php
/**
 * The following implementations of BP_Attachment act as dummy plugins
 * for our unit tests
 */
class BPTest_Invitation_Manager_Extension extends BP_Invitation_Manager {
	public function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	public function run_send_action( BP_Invitation $invitation ) {
		return true;
	}

	public function run_acceptance_action( $type, $r  ) {
		return true;
	}
}
