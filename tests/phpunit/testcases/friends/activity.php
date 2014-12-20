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
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

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
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

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

	/**
	 * @group friends_delete_activity
	 */
	public function test_friends_delete_activity() {
		$old_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		friends_add_friend( $u2, $u1 );
		$friendship_id = friends_get_friendship_id( $u2, $u1 );

		// Set current user to u1 to accepte the friendship
		$this->set_current_user( $u1 );
		friends_accept_friendship( $friendship_id );

		// Reset the current user
		$this->set_current_user( $old_user );

		// Random activities
		$au1 = $this->factory->activity->create( array( 'user_id' => $u1 ) );
		$au2 = $this->factory->activity->create( array( 'user_id' => $u2 ) );

		$fc_act = bp_activity_get( array(
			'component'   => buddypress()->friends->id,
			'item_id'     => $friendship_id,
			'filter'      => array( 'action' => array( 'friendship_created' ) ),
			'show_hidden' => false
		) );

		$this->assertTrue( count( $fc_act['activities'] ) == 1, '1 public activity should be created when a friendship is confirmed' );

		// Remove the friendship
		friends_remove_friend( $u2, $u1 );

		$this->assertFalse( friends_check_friendship( $u2, $u1 ), '2 users should not be friend once the friendship is removed' );

		$fd_act = bp_activity_get( array(
			'component'   => buddypress()->friends->id,
			'item_id'     => $friendship_id,
			'filter'      => array( 'action' => array( 'friendship_created' ) ),
			'show_hidden' => true
		) );

		$this->assertTrue( count( $fd_act['activities'] ) == 0, 'friends_delete_activity() should remove "friendship_created" activities about a deleted friendship' );
	}
}

