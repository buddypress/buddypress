<?php
/**
 * @group xprofile
 */
class BP_Tests_xProfile_Template extends BP_UnitTestCase {

	/**
	 * @group bp_has_profile
	 */
	public function test_bp_has_profile_default() {
		global $profile_template;
		$reset_profile_template = $profile_template;
		$prev_user = get_current_user_id();

		$u1 = self::factory()->user->create(
			array(
				'display_name' => 'Foo Bar',
			)
		);

		$this->set_current_user( $u1 );

		bp_has_profile(
			array(
				'profile_group_id' => 1,
				'user_id'          => $u1,
			)
		);

		$group = reset( $profile_template->groups );
		$field = reset( $group->fields );
		$this->assertEquals( 'Foo Bar', $field->data->value, 'The primary field should be the Name one and its value should be the same than the display name, by default' );

		$this->set_current_user( $prev_user );
		$profile_template = $reset_profile_template;
	}

	/**
	 * @group bp_has_profile
	 * @group bp_xprofile_signup_args
	 */
	public function test_bp_has_profile_signup_from_same_group() {
		global $profile_template;
		$reset_profile_template = $profile_template;
		add_filter( 'bp_get_signup_allowed', '__return_true' );

		$field_not_in = self::factory()->xprofile_field->create(
			array(
				'field_group_id' => 1,
				'type' => 'textbox',
				'name' => 'NotInSignupForm'
			)
		);

		$field_in = self::factory()->xprofile_field->create(
			array(
				'field_group_id' => 1,
				'type' => 'textbox',
				'name' => 'InSignupForm'
			)
		);

		// Add the field to signup ones.
		bp_xprofile_update_field_meta( $field_in, 'signup_position', 2 );

		bp_has_profile( bp_xprofile_signup_args() );

		$group          = reset( $profile_template->groups );
		$names          = wp_list_pluck( $group->fields, 'name' );
		$expected_names = array( 'Name', 'InSignupForm' );

		$this->assertSame( $expected_names, $names );

		xprofile_delete_field( $field_in );
		xprofile_delete_field( $field_not_in );

		remove_filter( 'bp_get_signup_allowed', '__return_true' );
		$profile_template = $reset_profile_template;
	}

	/**
	 * @group bp_has_profile
	 * @group bp_xprofile_signup_args
	 */
	public function test_bp_has_profile_signup_from_different_group() {
		global $profile_template;
		$reset_profile_template = $profile_template;
		add_filter( 'bp_get_signup_allowed', '__return_true' );

		$group_1 = self::factory()->xprofile_group->create();
		$group_2 = self::factory()->xprofile_group->create();

		$field_in_1 = self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $group_1,
				'type' => 'textbox',
				'name' => 'InSignupForm1'
			)
		);

		// Put the field at the last position
		bp_xprofile_update_field_meta( $field_in_1, 'signup_position', 3 );

		$field_in_2 = self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $group_2,
				'type' => 'textbox',
				'name' => 'InSignupForm2'
			)
		);

		// Put the field at the second position
		bp_xprofile_update_field_meta( $field_in_2, 'signup_position', 2 );

		bp_has_profile( bp_xprofile_signup_args() );

		$group          = reset( $profile_template->groups );
		$names          = wp_list_pluck( $group->fields, 'name' );
		$expected_names = array( 'Name', 'InSignupForm2', 'InSignupForm1' );

		$this->assertSame( $expected_names, $names );

		xprofile_delete_field_group( $group_1 );
		xprofile_delete_field_group( $group_2 );

		remove_filter( 'bp_get_signup_allowed', '__return_true' );
		$profile_template = $reset_profile_template;
	}
}
