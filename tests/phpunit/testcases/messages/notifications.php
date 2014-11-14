<?php

/**
 * @group notifications
 * @group messages
 */
class BP_Tests_Messages_Notifications extends BP_UnitTestCase {
	/**
	 * @group bp_messages_message_delete_notifications
	 */
	public function test_bp_messages_message_delete_notifications() {
		$current_user = get_current_user_id();
		$u = $this->factory->user->create();
		$this->set_current_user( $u );

		// Dummy thread ID
		$t = 12;

		// Admin
		$n = $this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
			'item_id' => $t,
			'component_action' => 'new_message',
		) );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $u,
		) );

		// Double check it's there
		$this->assertEquals( array( $n ), wp_list_pluck( $notifications, 'id' ) );

		// fire the hook
		do_action( 'messages_thread_deleted_thread', $t );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $u,
		) );

		$this->assertEmpty( $notifications );

		$this->set_current_user( $current_user );
	}
}
