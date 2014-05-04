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
		$u = $this->create_user();
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
		$u = $this->create_user();
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
}
