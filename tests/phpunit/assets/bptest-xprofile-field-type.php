<?php

// Testing Field Type class.
class BPTest_XProfile_Field_Type extends BP_XProfile_Field_Type {
	public $visibility = 'adminsonly';

	public static $supported_features = array(
		'required'                => true,
		'do_autolink'             => false,
		'allow_custom_visibility' => false,
		'member_types'            => true,
	);

	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Single Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'Test Field', 'xprofile field type', 'buddypress' );

		$this->set_format( '/^.*$/', 'replace' );
	}

	public function edit_field_html( array $raw_properties = array() ) {
		return;
	}

	public function admin_field_html( array $raw_properties = array() ) {
		return;
	}
}
