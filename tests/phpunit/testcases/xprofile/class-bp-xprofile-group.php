<?php

/**
 * @group BP_XProfile_Group
 * @group xprofile
 */
class BP_Tests_BP_XProfile_Group extends BP_UnitTestCase {

	/**
	 * @ticket BP6552
	 */
	public function test_save_should_not_return_false_when_no_fields_have_been_altered() {
		$g = self::factory()->xprofile_group->create();
		$group = new BP_XProfile_Group( $g );

		$saved = $group->save();

		$this->assertEquals( $g, $saved );
	}

	/**
	 * @group fetch_visibility_level
	 */
	public function test_fetch_visibility_level() {
		$u = self::factory()->user->create();
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
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
		$g1 = self::factory()->xprofile_group->create();
		$g2 = self::factory()->xprofile_group->create();
		$g3 = self::factory()->xprofile_group->create();

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

	/**
	 * @group member_types
	 * @ticket BP5192
	 */
	public function test_member_type_restrictions_should_be_ignored_when_user_id_is_null_and_member_type_is_not_explicitly_provided() {
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		bp_register_member_type( 'foo' );

		$field = new BP_XProfile_Field( $f );
		$field->set_member_types( array( 'foo' ) );

		$found_groups = BP_XProfile_Group::get( array(
			'user_id' => false,
			'fetch_fields' => true,
		) );

		// The groups aren't indexed, so we have to go looking for it.
		foreach ( $found_groups as $fg ) {
			if ( $g == $fg->id ) {
				$the_group = $fg;
			}
		}

		$this->assertContains( $f, wp_list_pluck( $the_group->fields, 'id' ) );
	}

	/**
	 * @group member_types
	 * @ticket BP5192
	 */
	public function test_member_type_restrictions_should_be_ignored_when_user_id_is_0_and_member_type_is_false() {
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		bp_register_member_type( 'foo' );

		$field = new BP_XProfile_Field( $f );
		$field->set_member_types( array( 'foo' ) );

		$found_groups = BP_XProfile_Group::get( array(
			'user_id' => 0,
			'member_type' => false,
			'fetch_fields' => true,
		) );

		// The groups aren't indexed, so we have to go looking for it.
		foreach ( $found_groups as $fg ) {
			if ( $g == $fg->id ) {
				$the_group = $fg;
			}
		}

		$this->assertContains( $f, wp_list_pluck( $the_group->fields, 'id' ) );
	}

	/**
	 * @group member_types
	 * @ticket BP5192
	 */
	public function test_member_type_restrictions_should_be_obeyed_for_nonzero_user_id() {
		$g = self::factory()->xprofile_group->create();
		$f1 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		$f2 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		$f3 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		$f4 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );

		// Field 1 is visible only to 'foo' users.
		$field1 = new BP_XProfile_Field( $f1 );
		$field1->set_member_types( array( 'foo' ) );

		// Field 2 is visible only to 'bar' users.
		$field2 = new BP_XProfile_Field( $f2 );
		$field2->set_member_types( array( 'bar' ) );

		// Field 3 is visible to all users (no member type set).

		// Field 4 is visible to no one.
		$field4 = new BP_XProfile_Field( $f4 );
		$field4->set_member_types( array() );

		// User is in 'foo', so should have f1 and f3 only.
		$u = self::factory()->user->create();
		bp_set_member_type( $u, 'foo' );

		$found_groups = BP_XProfile_Group::get( array(
			'user_id' => $u,
			'fetch_fields' => true,
		) );

		// The groups aren't indexed, so we have to go looking for it.
		foreach ( $found_groups as $fg ) {
			if ( $g == $fg->id ) {
				$the_group = $fg;
			}
		}

		$this->assertContains( $f1, wp_list_pluck( $the_group->fields, 'id' ) );
		$this->assertContains( $f3, wp_list_pluck( $the_group->fields, 'id' ) );
		$this->assertNotContains( $f2, wp_list_pluck( $the_group->fields, 'id' ) );
		$this->assertNotContains( $f4, wp_list_pluck( $the_group->fields, 'id' ) );
	}

	/**
	 * @group member_types
	 * @ticket BP5192
	 */
	public function test_member_type_restrictions_should_be_obeyed_for_nonzero_user_id_with_no_member_types() {
		$g = self::factory()->xprofile_group->create();
		$f1 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		$f2 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		$f3 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );

		// Field 1 is visible only to 'foo' users.
		$field1 = new BP_XProfile_Field( $f1 );
		$field1->set_member_types( array( 'foo' ) );

		// Field 2 is visible only to 'null' users.
		$field2 = new BP_XProfile_Field( $f2 );
		$field2->set_member_types( array( 'null' ) );

		// Field 3 is visible to all users (no member type set).

		// User has no member types, so should see f2 and f3 .
		$u = self::factory()->user->create();

		$found_groups = BP_XProfile_Group::get( array(
			'user_id' => $u,
			'fetch_fields' => true,
		) );

		// The groups aren't indexed, so we have to go looking for it.
		foreach ( $found_groups as $fg ) {
			if ( $g == $fg->id ) {
				$the_group = $fg;
			}
		}

		$this->assertNotContains( $f1, wp_list_pluck( $the_group->fields, 'id' ) );
		$this->assertContains( $f2, wp_list_pluck( $the_group->fields, 'id' ) );
		$this->assertContains( $f3, wp_list_pluck( $the_group->fields, 'id' ) );
	}

	/**
	 * @group member_types
	 * @ticket BP5192
	 */
	public function test_member_types_of_provided_user_id_should_take_precedence_over_provided_member_type() {
		$g = self::factory()->xprofile_group->create();
		$f1 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		$f2 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );

		$field1 = new BP_XProfile_Field( $f1 );
		$field1->set_member_types( array( 'foo' ) );
		$field2 = new BP_XProfile_Field( $f2 );
		$field2->set_member_types( array( 'bar' ) );

		$u = self::factory()->user->create();
		bp_set_member_type( $u, 'foo' );

		$found_groups = BP_XProfile_Group::get( array(
			'user_id' => $u,
			'member_type' => 'bar',
			'fetch_fields' => true,
		) );

		// The groups aren't indexed, so we have to go looking for it.
		foreach ( $found_groups as $fg ) {
			if ( $g == $fg->id ) {
				$the_group = $fg;
			}
		}

		$this->assertContains( $f1, wp_list_pluck( $the_group->fields, 'id' ) );
	}

	/**
	 * @group member_types
	 * @ticket BP5192
	 */
	public function test_member_type_single_value_should_be_respected() {
		$g = self::factory()->xprofile_group->create();
		$f1 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		$f2 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );

		$field1 = new BP_XProfile_Field( $f1 );
		$field1->set_member_types( array( 'foo' ) );
		$field2 = new BP_XProfile_Field( $f2 );
		$field2->set_member_types( array( 'bar' ) );

		$found_groups = BP_XProfile_Group::get( array(
			'member_type' => 'bar',
			'fetch_fields' => true,
		) );

		// The groups aren't indexed, so we have to go looking for it.
		foreach ( $found_groups as $fg ) {
			if ( $g == $fg->id ) {
				$the_group = $fg;
			}
		}

		$this->assertNotContains( $f1, wp_list_pluck( $the_group->fields, 'id' ) );
		$this->assertContains( $f2, wp_list_pluck( $the_group->fields, 'id' ) );
	}

	/**
	 * @group member_types
	 * @ticket BP5192
	 */
	public function test_member_type_array_value_should_be_respected() {
		$g = self::factory()->xprofile_group->create();
		$f1 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		$f2 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );

		$field1 = new BP_XProfile_Field( $f1 );
		$field1->set_member_types( array( 'foo' ) );
		$field2 = new BP_XProfile_Field( $f2 );
		$field2->set_member_types( array( 'bar' ) );

		$found_groups = BP_XProfile_Group::get( array(
			'member_type' => array( 'bar' ),
			'fetch_fields' => true,
		) );

		// The groups aren't indexed, so we have to go looking for it.
		foreach ( $found_groups as $fg ) {
			if ( $g == $fg->id ) {
				$the_group = $fg;
			}
		}

		$this->assertNotContains( $f1, wp_list_pluck( $the_group->fields, 'id' ) );
		$this->assertContains( $f2, wp_list_pluck( $the_group->fields, 'id' ) );
	}

	/**
	 * @group member_types
	 * @ticket BP5192
	 */
	public function test_member_type_null_should_be_respected() {
		$g = self::factory()->xprofile_group->create();
		$f1 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		$f2 = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );

		$field1 = new BP_XProfile_Field( $f1 );
		$field1->set_member_types( array( 'foo' ) );

		$found_groups = BP_XProfile_Group::get( array(
			'member_type' => array( 'null' ),
			'fetch_fields' => true,
		) );

		// The groups aren't indexed, so we have to go looking for it.
		foreach ( $found_groups as $fg ) {
			if ( $g == $fg->id ) {
				$the_group = $fg;
			}
		}

		$this->assertNotContains( $f1, wp_list_pluck( $the_group->fields, 'id' ) );
		$this->assertContains( $f2, wp_list_pluck( $the_group->fields, 'id' ) );
	}

	/**
	 * @group save_xprofile_group_name
	 */
	public function test_save_xprofile_group_name() {
		$g1 = self::factory()->xprofile_group->create( array(
			'name' => "Test ' Name"
		) );

		$e1 = new BP_XProfile_Group( $g1 );
		$e1->save();

		wp_cache_delete( $g1, 'bp_xprofile_groups' );

		$e2 = new BP_XProfile_Group( $g1 );

		$this->assertSame( $e1->name, $e2->name );
	}

	/**
	 * @group save_xprofile_group_name
	 */
	public function test_save_xprofile_group_name_with_single_quote() {

		// Set the original group name with no slashes
		$pristine_name = "Test \' Name";

		// Create a group
		$g1 = self::factory()->xprofile_group->create( array(
			'name' => $pristine_name
		) );

		// Get the field
		$e1 = new BP_XProfile_Group( $g1 );

		$this->assertSame( "Test ' Name", $e1->name );
	}
}
