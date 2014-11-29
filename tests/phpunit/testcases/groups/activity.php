<?php

/**
 * @group groups
 * @group activity
 */
class BP_Tests_Groups_Activity extends BP_UnitTestCase {
	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_created_group
	 */
	public function test_bp_groups_format_activity_action_created_group() {
		$u = $this->factory->user->create();
		$g = $this->factory->group->create();
		$a = $this->factory->activity->create( array(
			'component' => buddypress()->groups->id,
			'type' => 'created_group',
			'user_id' => $u,
			'item_id' => $g,
		) );

		$a_obj = new BP_Activity_Activity( $a );
		$g_obj = groups_get_group( array( 'group_id' => $g, ) );

		$expected = sprintf( __( '%s created the group %s', 'buddypress' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $g_obj ) . '">' . $g_obj->name . '</a>' );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_joined_group
	 */
	public function test_bp_groups_format_activity_action_joined_group() {
		$u = $this->factory->user->create();
		$g = $this->factory->group->create();
		$a = $this->factory->activity->create( array(
			'component' => buddypress()->groups->id,
			'type' => 'joined_group',
			'user_id' => $u,
			'item_id' => $g,
		) );

		$a_obj = new BP_Activity_Activity( $a );
		$g_obj = groups_get_group( array( 'group_id' => $g, ) );

		$expected = sprintf( __( '%s joined the group %s', 'buddypress' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $g_obj ) . '">' . $g_obj->name . '</a>' );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_no_change() {
		$group = $this->factory->group->create_and_get();
		groups_edit_base_group_details( $group->id, $group->name, $group->description, true );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertTrue( empty( $a['activities'] ) );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_notify_members_false() {
		$group = $this->factory->group->create_and_get();
		groups_edit_base_group_details( $group->id, 'Foo', $group->description, false );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertTrue( empty( $a['activities'] ) );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_updated_name() {
		$old_user = get_current_user_id();
		$u = $this->factory->user->create();
		$this->set_current_user( $u );

		$group = $this->factory->group->create_and_get();
		groups_edit_base_group_details( $group->id, 'Foo', $group->description, true );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertNotEmpty( $a['activities'] );

		$expected = sprintf( __( '%s changed the name of the group %s from "%s" to "%s"', 'buddypress' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $group ) . '">Foo</a>', $group->name, 'Foo' );
		$this->assertSame( $expected, $a['activities'][0]->action );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_updated_description() {
		$old_user = get_current_user_id();
		$u = $this->factory->user->create();
		$this->set_current_user( $u );

		$group = $this->factory->group->create_and_get();
		groups_edit_base_group_details( $group->id, $group->name, 'Bar', true );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertNotEmpty( $a['activities'] );

		$expected = sprintf( __( '%s changed the description of the group %s from "%s" to "%s"', 'buddypress' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $group ) . '">' . $group->name . '</a>', $group->description, 'Bar' );
		$this->assertSame( $expected, $a['activities'][0]->action );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_updated_name_and_description() {
		$old_user = get_current_user_id();
		$u = $this->factory->user->create();
		$this->set_current_user( $u );

		$group = $this->factory->group->create_and_get();
		groups_edit_base_group_details( $group->id, 'Foo', 'Bar', true );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertNotEmpty( $a['activities'] );

		$expected = sprintf( __( '%s changed the name and description of the group %s', 'buddypress' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $group ) . '">Foo</a>' );
		$this->assertSame( $expected, $a['activities'][0]->action );

		$this->set_current_user( $old_user );
	}
}
