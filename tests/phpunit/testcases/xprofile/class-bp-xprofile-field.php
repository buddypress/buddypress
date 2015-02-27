<?php
/**
 * @group xprofile
 * @group BP_XProfile_Field
 */
class BP_Tests_BP_XProfile_Field_TestCases extends BP_UnitTestCase {
	public function test_can_delete_save() {
		$group = $this->factory->xprofile_group->create();
		$field = $this->factory->xprofile_field->create( array(
			'field_group_id' => $group,
			'type' => 'textbox',
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
}
