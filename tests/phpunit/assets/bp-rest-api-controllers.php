<?php
/**
 * BP REST Controllers' mocks
 */

class BP_REST_Mock_Class {
	public function __construct() {}

	public function register_routes( $controller = '' ) {
		array_push( buddypress()->unit_test_rest->controllers, $controller );
	}
}

/**
 * BP Member Cover Image REST Controller's mock.
 */
class BP_REST_Attachments_Member_Cover_Endpoint extends BP_REST_Mock_Class {
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'members';
	}

	public function register_routes( $controller = '' ) {
		parent::register_routes( 'BP_REST_Attachments_Member_Cover_Endpoint' );
	}
}

/**
 * BP Member Avatar REST Controller's mock.
 */
class BP_REST_Attachments_Member_Avatar_Endpoint extends BP_REST_Mock_Class {
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'members';
	}

	public function register_routes( $controller = '' ) {
		parent::register_routes( 'BP_REST_Attachments_Member_Avatar_Endpoint' );
	}
}

/**
 * BP Components REST Controller's mock.
 */
class BP_REST_Components_Endpoint extends BP_REST_Mock_Class {
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'components';
	}

	public function register_routes( $controller = '' ) {
		parent::register_routes( 'BP_REST_Components_Endpoint' );
	}
}

/**
 * BP Members REST Controller's mock.
 */
class BP_REST_Members_Endpoint extends BP_REST_Mock_Class {
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'members';
	}

	public function register_routes( $controller = '' ) {
		parent::register_routes( 'BP_REST_Members_Endpoint' );
	}
}
