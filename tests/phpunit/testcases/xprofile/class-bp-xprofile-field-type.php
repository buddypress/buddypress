<?php
// Include the xProfile Test Type
include_once BP_TESTS_DIR . 'assets/bptest-xprofile-field-type.php';

/**
 * @group xprofile
 * @group BP_XProfile_Field_Type
 */
class BP_Tests_XProfile_Field_Type extends BP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		add_filter( 'bp_xprofile_get_field_types', array( $this, 'get_field_types' ) );
	}

	public function tearDown() {
		parent::tearDown();

		remove_filter( 'bp_xprofile_get_field_types', array( $this, 'get_field_types' ) );
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

	public function test_textbox_validate_allowed_string() {
		$field = bp_xprofile_create_field_type( 'textbox' );

		$this->assertTrue( $field->is_valid( 'a string' ) );
		$this->assertFalse( $field->set_allowed_values( 'pizza' )->is_valid( 'pasta' ) );
		$this->assertTrue( $field->is_valid( 'pizza' ) );
	}

	public function test_multiselectbox_validate_allowed_array() {
		$field = bp_xprofile_create_field_type( 'multiselectbox' );
		$field->set_allowed_values( array( 'cheese', 'pepporoni' ) );

		$this->assertTrue( $field->is_valid( array( 'cheese', 'pepporoni' ) ) );
		$this->assertTrue( $field->is_valid( array( 'cheese' ) ) );
		$this->assertFalse( $field->is_valid( array( 'cheese', 'pinapple', 'pepporoni' ) ) );
		$this->assertFalse( $field->is_valid( array( 'pinapple' ) ) );
	}

	public function test_multiselectbox_validate_null_value() {
		$field = bp_xprofile_create_field_type( 'multiselectbox' );
		$field->set_allowed_values( array( 'cheese', 'pepporoni' ) );

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
		$this->assertFalse( $field->is_valid( 'number fields only accept integers' ) );
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

	public function test_number_validate_allowed_array() {
		$field = bp_xprofile_create_field_type( 'number' );
		$field->set_allowed_values( array( 123, 456 ) );

		$this->assertTrue( $field->is_valid( array( 123 ) ) );
		$this->assertTrue( $field->is_valid( array( 456 ) ) );
		$this->assertFalse( $field->is_valid( array( 789, 456, 123 ) ) );
		$this->assertFalse( $field->is_valid( array( 789 ) ) );
	}

	public function test_radiobutton_validate_allowed_array() {
		$field = bp_xprofile_create_field_type( 'radio' );
		$field->set_allowed_values( array( 'cheese', 'pepporoni' ) );

		$this->assertTrue( $field->is_valid( array( 'cheese', 'pepporoni' ) ) );
		$this->assertTrue( $field->is_valid( array( 'cheese' ) ) );
		$this->assertFalse( $field->is_valid( array(  'pinapple', 'cheese', 'pepporoni' ) ) );
		$this->assertFalse( $field->is_valid( array( 'pinapple' ) ) );
		$this->assertFalse( $field->is_valid( '' ) );
	}

	public function test_radiobutton_do_not_validate_empty_items_in_allowed_list() {
		$field = bp_xprofile_create_field_type( 'radio' );
		$field->set_allowed_values( array( '' ) );

		$this->assertFalse( $field->is_valid( array( '' ) ) );
	}

	public function test_checkbox_validate_allowed_array() {
		$field = bp_xprofile_create_field_type( 'checkbox' );
		$field->set_allowed_values( array( 'cheese', 'pepporoni' ) );

		$this->assertTrue( $field->is_valid( array( 'cheese', 'pepporoni' ) ) );
		$this->assertTrue( $field->is_valid( array( 'cheese' ) ) );
		$this->assertFalse( $field->is_valid( array( 'pepporoni', 'cheese', 'pinapple' ) ) );
		$this->assertFalse( $field->is_valid( array( 'pinapple' ) ) );
		$this->assertFalse( $field->is_valid( '' ) );
	}

	public function test_checkbox_validate_null_value() {
		$field = bp_xprofile_create_field_type( 'checkbox' );
		$field->set_allowed_values( array( 'cheese', 'pepporoni' ) );

		$this->assertFalse( $field->is_valid( array( '' ) ) );
		$this->assertFalse( $field->is_valid( '' ) );
		$this->assertTrue( $field->is_valid( array() ) );
	}

	/**
	 * @group url
	 */
	public function test_url_validate_url() {
		$field = bp_xprofile_create_field_type( 'url' );

		$this->assertTrue( $field->is_valid( 'http://foo.com' ) );
		$this->assertTrue( $field->is_valid( 'https://foo.com' ) );
		$this->assertTrue( $field->is_valid( 'http://foo.bar.com' ) );
		$this->assertTrue( $field->is_valid( 'http://foo.bar.com/' ) );
		$this->assertTrue( $field->is_valid( 'http://foo.com/bar' ) );
		$this->assertTrue( $field->is_valid( 'http://foo.com/index.php' ) );
		$this->assertTrue( $field->is_valid( 'http://foo.com/?bar=baz' ) );

		$this->assertFalse( $field->is_valid( 'htp://foo.com' ) );
		$this->assertFalse( $field->is_valid( 'http:/foo.com' ) );
		$this->assertFalse( $field->is_valid( 'http//foo.com' ) );
		$this->assertFalse( $field->is_valid( 'http://foo' ) );
		$this->assertFalse( $field->is_valid( 'foo.com' ) );
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

	public function test_telephone_validate_number_formats() {
		$field = bp_xprofile_create_field_type( 'telephone' );
		$this->assertTrue( $field->is_valid( '07700 900461' ) );
		$this->assertTrue( $field->is_valid( '555-2368' ) );
		$this->assertTrue( $field->is_valid( '(212) 664-7665' ) );
	}

	/**
	 * @ticket BP7162
	 */
	public function test_xprofile_field_type_test_supports() {
		$group_id = self::factory()->xprofile_group->create();
		$field_id = self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $group_id,
				'type'           => 'test-field-type',
				'name'           => 'Test Supports',
			)
		);

		$field = xprofile_get_field( $field_id, null, false );

		$this->assertTrue( $field->field_type_supports( 'switch_fieldtype' ) );
		$this->assertFalse( $field->field_type_supports( 'do_autolink' ) );
		$this->assertFalse( $field->field_type_supports( 'allow_custom_visibility' ) );
		$this->assertTrue( $field->field_type_supports( 'required' ) );
		$this->assertTrue( $field->field_type_supports( 'member_types' ) );
		$this->assertEquals( 'adminsonly', $field->get_default_visibility() );
	}

	public function get_field_types( $types ) {
		$types['test-field-type'] = 'BPTest_XProfile_Field_Type';
		return $types;
	}
}
