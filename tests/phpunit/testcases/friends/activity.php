<?php

/**
 * @group friends
 * @group activity
 */
class BP_Tests_Friends_Activity extends BP_UnitTestCase {
	/**
	 * @group activity_action
	 * @group bp_friends_format_activity_action_friendship_accepted
	 */
	public function test_bp_friends_format_activity_action_friendship_accepted() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		$a = $this->factory->activity->create( array(
			'component' => buddypress()->friends->id,
			'type' => 'friendship_accepted',
			'user_id' => $u1,
			'secondary_item_id' => $u2,
		) );

		$expected = sprintf( __( '%1$s and %2$s are now friends', 'buddypress' ), bp_core_get_userlink( $u1 ), bp_core_get_userlink( $u2 ) );

		$a_obj = new BP_Activity_Activity( $a );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_friends_format_activity_action_friendship_created
	 */
	public function test_bp_friends_format_activity_action_friendship_created() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		$a = $this->factory->activity->create( array(
			'component' => buddypress()->friends->id,
			'type' => 'friendship_created',
			'user_id' => $u1,
			'secondary_item_id' => $u2,
		) );

		$expected = sprintf( __( '%1$s and %2$s are now friends', 'buddypress' ), bp_core_get_userlink( $u1 ), bp_core_get_userlink( $u2 ) );

		$a_obj = new BP_Activity_Activity( $a );

		$this->assertSame( $expected, $a_obj->action );
	}
}

