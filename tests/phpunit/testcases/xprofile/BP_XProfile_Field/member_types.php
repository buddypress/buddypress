<?php

/**
 * @group member_types
 * @group xprofile
 * @ticket BP5192
 */
class BP_Tests_XProfile_BpXprofileField_MemberTypes extends BP_UnitTestCase {
	protected $field_group_id;
	protected $field_id;
	protected $field;

	public function setUp() {
		parent::setUp();
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );

		$this->field_group_id = $this->factory->xprofile_group->create();
		$this->field_id = $this->factory->xprofile_field->create( array( 'field_group_id' => $this->field_group_id ) );
		$this->field = new BP_XProfile_Field( $this->field_id );
	}

	public function tearDown() {
		buddypress()->members->types = array();
		parent::tearDown();
	}

	public function test_get_single_member_type() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'foo' );
		$this->assertEqualSets( array( 'foo' ), $this->field->get_member_types() );
	}

	public function test_get_multiple_member_types() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'foo' );
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'bar' );
		$this->assertEqualSets( array( 'foo', 'bar' ), $this->field->get_member_types() );
	}

	public function test_invalid_member_types_should_not_be_returned() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'foo' );
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'phony' );
		$this->assertEquals( array( 'foo' ), $this->field->get_member_types() );
	}

	public function test_when_no_stored_types_are_found_all_registered_member_types_as_well_as_null_type_should_be_returned() {
		$this->assertEqualSets( array( 'null', 'foo', 'bar' ), $this->field->get_member_types() );
	}

	public function test__none_meta_should_result_in_empty_array() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', '_none' );
		$this->assertEquals( array(), $this->field->get_member_types() );
	}

	public function test__none_meta_should_override_other_values() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', '_none' );
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'foo' );
		$this->assertEquals( array(), $this->field->get_member_types() );
	}

	public function test_set_should_not_append_by_default() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'foo' );
		$this->assertEquals( array( 'bar' ), $this->field->set_member_types( array( 'bar' ) ) );
	}

	public function test_set_should_not_append_when_append_is_set_to_false() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'foo' );
		$this->assertEquals( array( 'bar' ), $this->field->set_member_types( array( 'bar', false ) ) );
	}

	public function test_set_should_append_when_append_is_set_to_true() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'foo' );
		$this->assertEqualSets( array( 'foo', 'bar' ), $this->field->set_member_types( array( 'bar' ), true ) );
	}

	public function test_set_empty_array_with_append_true_should_have_no_effect_on_saved_types() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'foo' );
		$this->assertEqualSets( array( 'foo' ), $this->field->set_member_types( array(), true ) );
	}

	public function test_set_empty_array_with_append_false_should_result_in_field_being_associated_with_no_member_types() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'foo' );
		$this->assertEqualSets( array(), $this->field->set_member_types( array() ) );
	}

	public function test_set_should_interpret_null_flag_properly() {
		$this->assertEqualSets( array( 'null' ), $this->field->set_member_types( array( 'null' ) ) );
	}

	public function test_set_all_types_plus_null_should_result_in_nothing_stored_in_db() {
		$types = array( 'null', 'foo', 'bar' );
		$this->assertEqualSets( $types, $this->field->set_member_types( $types ) );

		$types_db = bp_xprofile_get_meta( $this->field_id, 'field', 'member_type', false );
		$this->assertEqualSets( array(), $types_db );
	}

	public function test_get_fields_for_member_type_should_get_field_with_explicit_member_type() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'foo' );
		$found = BP_XProfile_Field::get_fields_for_member_type( 'foo' );
		$this->assertEqualSets( array( 1, $this->field_id ), array_keys( $found ) );
	}

	public function test_get_fields_for_member_type_should_ignore_field_that_applies_to_no_member_types() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', '_none' );
		$found = BP_XProfile_Field::get_fields_for_member_type( 'foo' );
		$this->assertEqualSets( array( 1 ), array_keys( $found ) );
	}

	public function test_get_fields_for_member_type_should_ignore_field_that_applies_to_different_member_types() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'bar' );
		$found = BP_XProfile_Field::get_fields_for_member_type( 'foo' );
		$this->assertEqualSets( array( 1 ), array_keys( $found ) );
	}

	public function test_get_fields_for_member_type_should_include_fields_with_no_member_type_restrictions() {
		// A field with no member_type metadata applies to all member types.
		$found = BP_XProfile_Field::get_fields_for_member_type( 'foo' );
		$this->assertEqualSets( array( 1, $this->field_id ), array_keys( $found ) );
	}

	public function test_passing_member_type_any_to_get_fields_for_member_type_should_return_unrestricted_fields() {
		$f2 = $this->factory->xprofile_field->create( array( 'field_group_id' => $this->field_group_id ) );

		$field_2 = new BP_XProfile_Field( $f2 );
		$field_2->set_member_types( array( 'foo' ) );

		$found = BP_XProfile_Field::get_fields_for_member_type( 'any' );
		$this->assertEqualSets( array( 1, $this->field_id ), array_keys( $found ) );
	}

	public function test_passing_empty_member_type_to_get_fields_for_member_type_should_return_unrestricted_fields() {
		$f2 = $this->factory->xprofile_field->create( array( 'field_group_id' => $this->field_group_id ) );

		$field_2 = new BP_XProfile_Field( $f2 );
		$field_2->set_member_types( array( 'foo' ) );

		$found = BP_XProfile_Field::get_fields_for_member_type( '' );
		$this->assertEqualSets( array( 1, $this->field_id ), array_keys( $found ) );
	}

	public function test_passing_member_type_none_to_get_fields_for_member_type_should_return_fields_unavailable_to_all_member_types() {
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', '_none' );
		$found = BP_XProfile_Field::get_fields_for_member_type( '_none' );
		$this->assertEqualSets( array( $this->field_id ), array_keys( $found ) );
	}

	public function test_get_fields_for_member_type_should_accept_an_array_of_member_types() {
		$f2 = $this->factory->xprofile_field->create( array( 'field_group_id' => $this->field_group_id ) );
		bp_xprofile_add_meta( $this->field_id, 'field', 'member_type', 'foo' );
		bp_xprofile_add_meta( $f2, 'field', 'member_type', 'bar' );

		$found = BP_XProfile_Field::get_fields_for_member_type( array( 'foo', 'bar' ) );
		$this->assertEqualSets( array( 1, $this->field_id, $f2 ), array_keys( $found ) );
	}

	/**
	 * @group cache
	 */
	public function test_get_fields_for_member_type_should_fetch_restricted_fields_from_cache() {
		global $wpdb;

		$this->field->set_member_types( array( 'foo' ) );

		// Prime cache.
		BP_XProfile_Field::get_fields_for_member_type( 'foo' );

		$num_queries = $wpdb->num_queries;

		BP_XProfile_Field::get_fields_for_member_type( 'foo' );

		// get_fields_for_member_type() always does at least one query.
		$num_queries++;
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group cache
	 */
	public function test_get_fields_for_member_type_should_skip_cache_after_a_fields_member_type_is_modified() {
		global $wpdb;

		$this->field->set_member_types( array( 'foo' ) );

		// Prime cache.
		BP_XProfile_Field::get_fields_for_member_type( 'foo' );

		$num_queries = $wpdb->num_queries;

		$this->field->set_member_types( array( 'none' ) );

		$found = BP_XProfile_Field::get_fields_for_member_type( 'foo' );

		$this->assertTrue( $num_queries + 2 <= $wpdb->num_queries );
		$this->assertEqualSets( array( 1 ), array_keys( $found ) );
	}

	public function test_get_member_type_label_order() {
		// Should be alphabetical, with the null option last.
		bp_register_member_type( 'aaa', array(
			'labels' => array(
				'name' => 'AAA',
			),
		) );
		bp_register_member_type( 'zzz', array(
			'labels' => array(
				'name' => 'ZZZ',
			),
		) );

		$this->field->set_member_types( array( 'aaa', 'zzz', 'null' ) );

		$expected = '(Member types: AAA, ZZZ, Users with no member type)';
		$this->assertSame( $expected, $this->field->get_member_type_label() );
	}
}
