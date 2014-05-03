<?php
/**
 * @group xprofile
 * @group BP_XProfile_Field_Type
 */
class BP_Tests_XProfile_Field_Type extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_unregistered_field_type_returns_textbox() {
		$field = bp_xprofile_create_field_type( 'fakeyfield' );
		$this->assertEquals( get_class( $field ), 'BP_XProfile_Field_Type_Placeholder' );
	}


	public function test_textbox_validate_empty_string() {
		$field = bp_xprofile_create_field_type( 'textbox' );
		$this->assertTrue( $field->is_valid( '' ) );
	}

	public function test_textbox_validate_string() {
		$field = bp_xprofile_create_field_type( 'textbox' );
		$this->assertTrue( $field->is_valid( 'my 117th input string!' ) );
	}

	public function test_textbox_validate_integer() {
		$field = bp_xprofile_create_field_type( 'textbox' );
		$this->assertTrue( $field->is_valid( 123 ) );
	}

	public function test_textbox_validate_whitelisted_string() {
		$field = bp_xprofile_create_field_type( 'textbox' );

		$this->assertTrue( $field->is_valid( 'a string' ) );
		$this->assertFalse( $field->set_whitelist_values( 'pizza' )->is_valid( 'pasta' ) );
		$this->assertTrue( $field->is_valid( 'pizza' ) );
	}


	public function test_multiselectbox_validate_whitelisted_array() {
		$field = bp_xprofile_create_field_type( 'multiselectbox' );
		$field->set_whitelist_values( array( 'cheese', 'pepporoni' ) );

		$this->assertTrue( $field->is_valid( array( 'cheese', 'pepporoni' ) ) );
		$this->assertTrue( $field->is_valid( array( 'cheese' ) ) );
		$this->assertFalse( $field->is_valid( array( 'cheese', 'pepporoni', 'pinapple' ) ) );
		$this->assertFalse( $field->is_valid( array( 'pinapple' ) ) );
	}

	public function test_multiselectbox_validate_null_value() {
		$field = bp_xprofile_create_field_type( 'multiselectbox' );
		$field->set_whitelist_values( array( 'cheese', 'pepporoni' ) );

		$this->assertFalse( $field->is_valid( array( '' ) ) );
		$this->assertFalse( $field->is_valid( '' ) );
		$this->assertTrue( $field->is_valid( array() ) );
	}


	public function test_datebox_do_not_validate_string() {
		$field = bp_xprofile_create_field_type( 'datebox' );
		$this->assertFalse( $field->is_valid( 'datebox fields only accept strings in: Y-m-d 00:00:00' ) );
		$this->assertFalse( $field->is_valid( '' ) );
	}

	public function test_datebox_do_not_validate_integer() {
		$field = bp_xprofile_create_field_type( 'datebox' );
		$this->assertFalse( $field->is_valid( 221213 ) );
	}

	public function test_datebox_validate_date() {
		$field = bp_xprofile_create_field_type( 'datebox' );
		$this->assertTrue( $field->is_valid( '2013-12-22 00:00:00' ) );
	}

	public function test_datebox_do_not_validate_date_with_timestamp() {
		$field = bp_xprofile_create_field_type( 'datebox' );
		$this->assertFalse( $field->is_valid( '2013-12-22 19:11:30' ) );
		$this->assertFalse( $field->is_valid( '2013-12-22' ) );
	}


	public function test_number_do_not_validate_string() {
		$field = bp_xprofile_create_field_type( 'number' );
		$this->assertFalse( $field->is_valid( 'telephone fields only accept integers' ) );
		$this->assertFalse( $field->is_valid( '' ) );
	}

	public function test_number_validate_positive_integer() {
		$field = bp_xprofile_create_field_type( 'number' );
		$this->assertTrue( $field->is_valid( 12345678901 ) );
		$this->assertTrue( $field->is_valid( '12345678901' ) );
	}

	public function test_number_validate_negative_integer() {
		$field = bp_xprofile_create_field_type( 'number' );
		$this->assertTrue( $field->is_valid( -987654321 ) );
		$this->assertTrue( $field->is_valid( '-987654321' ) );
	}

	public function test_number_validate_null_value() {
		$field = bp_xprofile_create_field_type( 'number' );
		$this->assertTrue( $field->is_valid( '0' ) );
		$this->assertFalse( $field->is_valid( '-' ) );
		$this->assertFalse( $field->is_valid( '' ) );
	}

	public function test_number_validate_whitelisted_array() {
		$field = bp_xprofile_create_field_type( 'number' );
		$field->set_whitelist_values( array( 123, 456 ) );

		$this->assertTrue( $field->is_valid( array( 123 ) ) );
		$this->assertTrue( $field->is_valid( array( 456 ) ) );
		$this->assertFalse( $field->is_valid( array( 123, 456, 789 ) ) );
		$this->assertFalse( $field->is_valid( array( 789 ) ) );
	}


	public function test_radiobutton_validate_whitelisted_array() {
		$field = bp_xprofile_create_field_type( 'radio' );
		$field->set_whitelist_values( array( 'cheese', 'pepporoni' ) );

		$this->assertTrue( $field->is_valid( array( 'cheese', 'pepporoni' ) ) );
		$this->assertTrue( $field->is_valid( array( 'cheese' ) ) );
		$this->assertFalse( $field->is_valid( array( 'cheese', 'pepporoni', 'pinapple' ) ) );
		$this->assertFalse( $field->is_valid( array( 'pinapple' ) ) );
		$this->assertFalse( $field->is_valid( '' ) );
	}

	public function test_radiobutton_do_not_validate_empty_items_in_whitelist() {
		$field = bp_xprofile_create_field_type( 'radio' );
		$field->set_whitelist_values( array( '' ) );

		$this->assertFalse( $field->is_valid( array( '' ) ) );
	}


	public function test_checkbox_validate_whitelisted_array() {
		$field = bp_xprofile_create_field_type( 'checkbox' );
		$field->set_whitelist_values( array( 'cheese', 'pepporoni' ) );

		$this->assertTrue( $field->is_valid( array( 'cheese', 'pepporoni' ) ) );
		$this->assertTrue( $field->is_valid( array( 'cheese' ) ) );
		$this->assertFalse( $field->is_valid( array( 'cheese', 'pepporoni', 'pinapple' ) ) );
		$this->assertFalse( $field->is_valid( array( 'pinapple' ) ) );
		$this->assertFalse( $field->is_valid( '' ) );
	}

	public function test_checkbox_validate_null_value() {
		$field = bp_xprofile_create_field_type( 'checkbox' );
		$field->set_whitelist_values( array( 'cheese', 'pepporoni' ) );

		$this->assertFalse( $field->is_valid( array( '' ) ) );
		$this->assertFalse( $field->is_valid( '' ) );
		$this->assertTrue( $field->is_valid( array() ) );
	}

	/**
	 * @group BP_XProfile_Field_Group_Type_Placeholder
	 */
	public function test_placeholder_validate_any_value() {
		$field = bp_xprofile_create_field_type( 'foo' );
		$this->assertTrue( $field->is_valid( '' ) );
		$this->assertTrue( $field->is_valid( 'bar' ) );
		$this->assertTrue( $field->is_valid( array( 'bar' ) ) );
	}
}
