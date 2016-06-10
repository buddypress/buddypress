<?php
/**
 * @group xprofile
 * @group BP_XProfile_Field
 */
class BP_Tests_BP_XProfile_Field_TestCases extends BP_UnitTestCase {

	/**
	 * @group xprofile_field_save
	 */
	public function test_can_delete_save() {
		$group = $this->factory->xprofile_group->create();
		$field = $this->factory->xprofile_field->create( array(
			'field_group_id' => $group,
		) );

		$f = new BP_XProfile_Field( $field );
		$f->can_delete = 0;
		$f->save();

		$f2 = new BP_XProfile_Field( $field );
		$this->assertEquals( '0', $f2->can_delete );
	}

	/**
	 * @group xprofile_get_field_id_from_name
	 */
	public function test_get_id_from_name_field_name_option_value_conflict() {
		$group = $this->factory->xprofile_group->create();

		// force some checkbox options for our profile field
		$_POST['checkbox_option'] = array(
			1 => 'BuddyPress',
			2 => 'WordPress'
		);

		// checkbox field
		$f1 = $this->factory->xprofile_field->create( array(
			'field_group_id' => $group,
			'type' => 'checkbox',
			'name' => 'Interests'
		) );

		// textbox field with the same name as our checkbox value
		$f2 = $this->factory->xprofile_field->create( array(
			'field_group_id' => $group,
			'type' => 'textbox',
			'name' => 'BuddyPress'
		) );

		$this->assertEquals( $f2, xprofile_get_field_id_from_name( 'BuddyPress' ) );

		// cleanup!
		unset( $_POST['checkbox_option'] );
	}

	/**
	 * @group xprofile_field_admin_validate
	 */
	public function test_admin_validate_zero_field_name() {

		// Mock POST global values
		$_POST['title']     = '0';
		$_POST['required']  = false;
		$_POST['fieldtype'] = 'textbox';

		// Validate the mocked POST radio button options
		$result = BP_XProfile_Field::admin_validate();

		// Assert valid
		$this->assertEquals( $result, true );

		// cleanup
		unset(
			$_POST['title'],
			$_POST['required'],
			$_POST['fieldtype']
		);
	}

	/**
	 * @group xprofile_field_admin_validate
	 */
	public function test_admin_validate_field_options() {

		// Mock POST global values
		$_POST['title']        = 'Foo';
		$_POST['required']     = false;
		$_POST['fieldtype']    = 'radio';
		$_POST['radio_option'] = array(
			1 => '0',
			2 => '1',
			3 => '4',
		);

		// Validate the mocked POST radio button options
		$result = BP_XProfile_Field::admin_validate();

		// Assert valid
		$this->assertEquals( $result, true );

		// cleanup
		unset(
			$_POST['title'],
			$_POST['required'],
			$_POST['fieldtype'],
			$_POST['radio_option' ]
		);
	}

	/**
	 * @ticket BP6545
	 */
	public function test_newly_created_field_should_have_field_id_property_set() {
		$field = new BP_XProfile_Field();
		$field->group_id = 1;
		$field->name = 'Foo';

		$new_field_id = $field->save();

		$this->assertSame( $new_field_id, $field->id );
	}

	/**
	 * @ticket BP6638
	 */
	public function test_default_visibility_should_be_lazy_loaded() {
		global $wpdb;

		$group = $this->factory->xprofile_group->create();
		$field = $this->factory->xprofile_field->create( array(
			'field_group_id' => $group,
		) );

		bp_xprofile_update_meta( $field, 'field', 'default_visibility', 'loggedin' );

		// Initial setup takes just one query.
		$num_queries = $wpdb->num_queries;
		$field_obj = new BP_XProfile_Field( $field );
		$num_queries++;

		$this->assertSame( $num_queries, $wpdb->num_queries );

		// Fetching the default_visibility should cause another query.
		$this->assertSame( 'loggedin', $field_obj->default_visibility );
		$num_queries++;

		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @ticket BP6638
	 */
	public function test_allow_custom_visibility_should_be_lazy_loaded() {
		global $wpdb;

		$group = $this->factory->xprofile_group->create();
		$field = $this->factory->xprofile_field->create( array(
			'field_group_id' => $group,
		) );

		bp_xprofile_update_meta( $field, 'field', 'allow_custom_visibility', 'disabled' );

		// Initial setup takes just one query.
		$num_queries = $wpdb->num_queries;
		$field_obj = new BP_XProfile_Field( $field );
		$num_queries++;

		$this->assertSame( $num_queries, $wpdb->num_queries );

		// Fetching the allow_custom_visibility should cause another query.
		$this->assertSame( 'disabled', $field_obj->allow_custom_visibility );
		$num_queries++;

		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @ticket BP7073
	 */
	public function test_bad_field_id_should_not_be_cached() {
		BP_XProfile_Field::get_instance( 12345 );

		$this->assertFalse( wp_cache_get( 12345, 'bp_xprofile_fields' ) );
	}

	/**
	 * @ticket BP7112
	 */
	public function test_update_position_should_invalidate_cache() {
		$group = $this->factory->xprofile_group->create();
		$field = $this->factory->xprofile_field->create( array(
			'field_group_id' => $group,
		) );

		// Prime cache.
		$fetched_field = xprofile_get_field( $field );
		$new_field_order = 12345;

		// Update field position.
		BP_XProfile_Field::update_position( $field, $new_field_order, $group );

		// Cache call should miss; fresh data should be fetched.
		$updated_fetched_field = xprofile_get_field( $field );
		$this->assertEquals( $new_field_order, $updated_fetched_field->field_order );
	}
}
