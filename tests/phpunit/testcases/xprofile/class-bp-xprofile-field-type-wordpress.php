<?php
/**
 * @group xprofile
 * @group BP_Tests_XProfile_Field_Type_WordPress
 * @ticket BP7162
 */
class BP_Tests_XProfile_Field_Type_WordPress extends BP_UnitTestCase {

	public function test_wp_textbox_validate_string() {
		$field = bp_xprofile_create_field_type( 'wp-textbox' );
		$this->assertTrue( $field->is_valid( 'Hello WordPress Fields!' ) );
	}

	/**
	 * @group xprofile_set_field_data
	 */
	public function test_wp_textbox_set_last_name_field_data() {
		$user_id  = self::factory()->user->create();
		$group_id = self::factory()->xprofile_group->create();
		$field_id = self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $group_id,
				'type'           => 'wp-textbox',
				'name'           => 'WP Last Name',
			)
		);

		// Set the WP User Key.
		bp_xprofile_update_meta( $field_id, 'field', 'wp_user_key', 'last_name' );

		$field_data = xprofile_set_field_data( $field_id, $user_id, 'bar' );

		$user = get_user_by( 'id', $user_id );

		$this->assertEquals( 'bar', $user->last_name );
	}

	/**
	 * @group bp_xprofile_get_groups
	 */
	public function test_wp_textbox_get_user_url_field_data() {
		$user_id  = self::factory()->user->create(
			array(
				'user_url' => 'https://buddypress.org',
			)
		);
		$group_id = self::factory()->xprofile_group->create();
		$field_id = self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $group_id,
				'type'           => 'wp-textbox',
				'name'           => 'WP User URL',
			)
		);

		// Set the WP User Key.
		bp_xprofile_update_meta( $field_id, 'field', 'wp_user_key', 'user_url' );

		$groups = bp_xprofile_get_groups(
			array(
				'profile_group_id'       => $group_id,
				'user_id'                => $user_id,
				'fetch_fields'           => true,
				'fetch_field_data'       => true,
			)
		);
		$group  = reset( $groups );
		$field  = reset( $group->fields );

		$this->assertEquals( 'https://buddypress.org', $field->data->value );
	}

	/**
	 * @group xprofile_get_field_data
	 */
	public function test_wp_textbox_get_first_name_field_value() {
		$user_id  = self::factory()->user->create(
			array(
				'first_name' => 'foo',
			)
		);
		$group_id = self::factory()->xprofile_group->create();
		$field_id = self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $group_id,
				'type'           => 'wp-textbox',
				'name'           => 'WP First Name',
			)
		);

		// Set the WP User Key.
		bp_xprofile_update_meta( $field_id, 'field', 'wp_user_key', 'first_name' );

		$field_data = xprofile_get_field_data( $field_id, $user_id );

		$this->assertEquals( 'foo', $field_data );
	}

	/**
	 * @group bp_member_profile_data
	 * @group bp_get_member_profile_data
	 */
	public function test_wp_biography_get_field_value() {
		global $members_template;
		$reset_members_template = $members_template;

		$user_id  = self::factory()->user->create(
			array(
				'description' => 'The BuddyPress community is awesome!',
			)
		);
		$group_id = self::factory()->xprofile_group->create();
		$field_id = self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $group_id,
				'type'           => 'wp-biography',
				'name'           => 'About Me',
			)
		);

		$members_template = new BP_Core_Members_Template(
			array(
				'include'  => $user_id,
				'type'     => 'alphabetical',
				'page'     => 1,
				'per_page' => 1,
			)
		);

		bp_the_member();

		$profile_data = bp_get_member_profile_data(
			array(
				'field' => 'About Me'
			)
		);

		$members_template = $reset_members_template;

		$this->assertEquals( 'The BuddyPress community is awesome!', $profile_data );
	}
}
