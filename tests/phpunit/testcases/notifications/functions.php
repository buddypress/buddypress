<?php

/**
 * @group notifications
 */
class BP_Tests_Notifications_Functions extends BP_UnitTestCase {

	/**
	 * @group cache
	 */
	public function test_cache_invalidation_all_for_user_on_save() {
		$u = $this->factory->user->create();

		$this->factory->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );
		$this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u,
			'item_id'        => 1
		) );

		// prime cache
		$count = bp_notifications_get_unread_notification_count( $u );

		// just to be sure...
		$this->assertEquals( 2, $count, 'Cache count should be 2 before invalidation.' );

		// Trigger invalidation via save
		$this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u,
			'item_id'        => 2
		) );

		$this->assertFalse( wp_cache_get( 'all_for_user_' . $u, 'bp_notifications' ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_invalidation_all_for_user_on_delete() {
		$u  = $this->factory->user->create();
		$n1 = $this->factory->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );
		$this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u
		) );

		// prime cache
		$count = bp_notifications_get_unread_notification_count( $u );

		// just to be sure...
		$this->assertEquals( 2, $count, 'Cache count should be 2 before invalidation.' );

		// delete
		BP_Notifications_Notification::delete( array( 'id' => $n1, ) );

		$this->assertFalse( wp_cache_get( 'all_for_user_' . $u, 'bp_notifications' ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_invalidation_all_for_user_on_update_user_id() {
		$u = $this->factory->user->create();

		$this->factory->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );
		$this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u
		) );

		// prime cache
		$count = bp_notifications_get_unread_notification_count( $u );

		// just to be sure...
		$this->assertEquals( 2, $count, 'Cache count should be 2 before invalidation.' );

		// mark all notifications by user as read
		BP_Notifications_Notification::update(
			array( 'is_new'  => false ),
			array( 'user_id' => $u    )
		);

		$this->assertFalse( wp_cache_get( 'all_for_user_' . $u, 'bp_notifications' ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_invalidation_all_for_user_on_update_id() {
		$u  = $this->factory->user->create();
		$n1 = $this->factory->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );

		$this->factory->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u
		) );

		// prime cache
		$count = bp_notifications_get_unread_notification_count( $u );

		// just to be sure...
		$this->assertEquals( 2, $count, 'Cache count should be 2 before invalidation.' );

		// mark one notification as read
		BP_Notifications_Notification::update(
			array( 'is_new' => false ),
			array( 'id'     => $n1   )
		);

		$this->assertFalse( wp_cache_get( 'all_for_user_' . $u, 'bp_notifications' ) );
	}

	/**
	 * @group bp_notifications_add_notification
	 */
	public function test_bp_notifications_add_notification_no_dupes() {
		$args = array(
			'user_id'           => 5,
			'item_id'           => 10,
			'secondary_item_id' => 25,
			'component_name'    => 'messages',
			'component_action'  => 'new_message'
		);

		$this->factory->notification->create( $args );

		$this->assertFalse( bp_notifications_add_notification( $args ) );
	}

	/**
	 * @group bp_notifications_add_notification
	 */
	public function test_bp_notifications_add_notification_allow_duplicate() {
		$args = array(
			'user_id'           => 5,
			'item_id'           => 10,
			'secondary_item_id' => 25,
			'component_name'    => 'messages',
			'component_action'  => 'new_message'
		);

		$this->factory->notification->create( $args );

		$args['allow_duplicate'] = true;

		$this->assertNotEmpty( bp_notifications_add_notification( $args ) );
	}

	/**
	 * @group bp_notifications_get_unread_notification_count
	 * @group cache
	 */
	public function test_bp_notifications_get_unread_notification_count_cache() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$this->factory->notification->create( array(
			'component_name'    => 'messages',
			'component_action'  => 'new_message',
			'item_id'           => 99,
			'user_id'           => $u2,
			'secondary_item_id' => $u1,
			'is_new'            => true
		) );

		// prime cache
		bp_notifications_get_unread_notification_count( $u2 );

		// mark the created notification as read
		bp_notifications_mark_notifications_by_item_id( $u2, 99, 'messages', 'new_message', $u1 );

		// now grab the updated notification count
		$n = bp_notifications_get_unread_notification_count( $u2 );

		// assert
		$this->assertEquals( 0, $n );
	}

	/**
	 * @group bp_has_notifications
	 */
	public function test_bp_has_notifications_filtering() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		// create a mixture of different notifications
		$this->factory->notification->create( array(
			'component_name'    => 'messages',
			'component_action'  => 'new_message',
			'item_id'           => 99,
			'user_id'           => $u2,
			'secondary_item_id' => $u1,
			'is_new'            => true
		) );

		$this->factory->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 99,
			'user_id'           => $u2,
			'secondary_item_id' => $u1,
			'is_new'            => true
		) );

		$this->factory->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 100,
			'user_id'           => $u2,
			'secondary_item_id' => $u1,
			'is_new'            => true
		) );

		// now fetch only activity notifications
		bp_has_notifications( array(
			'component_name' => 'activity',
			'user_id'        => $u2
		) );

		// assert
		$this->assertEquals( 2, buddypress()->notifications->query_loop->total_notification_count );
	}
}
