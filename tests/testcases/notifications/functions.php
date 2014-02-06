<?php

/**
 * @group notifications
 */
class BP_Tests_Notifications_Functions extends BP_UnitTestCase {
	public function test_cache_invalidation_all_for_user_on_save() {
		$u = $this->create_user();
		$n1 = $this->factory->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
		) );
		$n2 = $this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
		) );

		// prime cache
		$count = bp_notifications_get_unread_notification_count( $u );

		// just to be sure...
		$this->assertEquals( 2, $count, 'Cache count should be 2 before invalidation.' );

		// Trigger invalidation via save
		$n3 = $this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
		) );

		$this->assertFalse( wp_cache_get( 'all_for_user_' . $u, 'bp_notifications' ) );
	}

	public function test_cache_invalidation_all_for_user_on_delete() {
		$u = $this->create_user();
		$n1 = $this->factory->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
		) );
		$n2 = $this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
		) );

		// prime cache
		$count = bp_notifications_get_unread_notification_count( $u );

		// just to be sure...
		$this->assertEquals( 2, $count, 'Cache count should be 2 before invalidation.' );

		// delete
		BP_Notifications_Notification::delete( array( 'id' => $n1, ) );

		$this->assertFalse( wp_cache_get( 'all_for_user_' . $u, 'bp_notifications' ) );
	}
}
