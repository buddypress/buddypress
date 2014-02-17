<?php

/**
 * @group BP_XProfile_Group
 * @group xprofile
 */
class BP_Tests_BP_XProfile_Group extends BP_UnitTestCase {
	/**
	 * @group fetch_visibility_level
	 */
	public function test_fetch_visibility_level() {
		$u = $this->create_user();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( array(
			'type' => 'textbox',
			'field_group_id' => $g,
		) );

		$f_obj = new BP_XProfile_Field( $f );

		$fields = array(
			0 => new stdClass,
		);

		$fields[0]->id = $f;
		$fields[0]->name = $f_obj->name;
		$fields[0]->description = $f_obj->description;
		$fields[0]->type = $f_obj->type;
		$fields[0]->group_id = $f_obj->group_id;
		$fields[0]->is_required = $f_obj->is_required;
		$fields[0]->data = new stdClass;
		$fields[0]->data->value = 'foo';
		$fields[0]->data->id = 123;

		// custom visibility enabled, but no fallback
		bp_xprofile_update_meta( $f, 'field', 'default_visibility', 'adminsonly' );
		bp_xprofile_update_meta( $f, 'field', 'allow_custom_visibility', 'enabled' );

		$found = BP_XProfile_Group::fetch_visibility_level( $u, $fields );

		$expected = $fields;
		$expected[0]->visibility_level = 'adminsonly';

		$this->assertSame( $expected, $found );

		// custom visibility enabled, with user-provided value
		bp_xprofile_update_meta( $f, 'field', 'default_visibility', 'adminsonly' );
		bp_xprofile_update_meta( $f, 'field', 'allow_custom_visibility', 'enabled' );
		xprofile_set_field_visibility_level( $f, $u, 'public' );

		$found = BP_XProfile_Group::fetch_visibility_level( $u, $fields );

		$expected = $fields;
		$expected[0]->visibility_level = 'public';

		$this->assertSame( $expected, $found );

		// custom visibility disabled
		bp_xprofile_update_meta( $f, 'field', 'default_visibility', 'adminsonly' );
		bp_xprofile_update_meta( $f, 'field', 'allow_custom_visibility', 'disabled' );
		xprofile_set_field_visibility_level( $f, $u, 'public' );

		$found = BP_XProfile_Group::fetch_visibility_level( $u, $fields );

		$expected = $fields;
		$expected[0]->visibility_level = 'adminsonly';

		$this->assertSame( $expected, $found );
	}
}
