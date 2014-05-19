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

	/**
	 * @group get_xprofile_groups
	 */
	public function test_get_xprofile_groups() {
		$g1 = $this->factory->xprofile_group->create();
		$g2 = $this->factory->xprofile_group->create();
		$g3 = $this->factory->xprofile_group->create();

		$all = BP_XProfile_Group::get();
		$all_results = array_map( 'absint', wp_list_pluck( $all, 'id' ) );

		$e1 = array( $g1, $g2 );
		$groups1 = BP_XProfile_Group::get( array(
			'exclude_groups' => implode( ',', $e1 ),
		) );

		$r_groups1 = array_map( 'absint', wp_list_pluck( $groups1, 'id' ) );
		$found1 = array_diff( $all_results, $r_groups1 );

		$this->assertSame( $e1, array_merge( $found1, array() ) );

		$e2 = array( $g2, $g3 );
		$groups2 = BP_XProfile_Group::get( array(
			'exclude_groups' => $e2,
		) );

		$r_groups2 = array_map( 'absint', wp_list_pluck( $groups2, 'id' ) );
		$found2 = array_diff( $all_results, $r_groups2 );

		$this->assertSame( $e2, array_merge( $found2, array() ) );
	}
}
