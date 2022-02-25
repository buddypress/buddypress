<?php

/**
 * @group notifications
 * @group cache
 */
class BP_Tests_Notifications_Cache extends BP_UnitTestCase {

	/**
	 * @group cache
	 */
	public function test_cache_invalidation_all_for_user_on_save() {
		$u = self::factory()->user->create();

		self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );
		self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u,
			'item_id'        => 1
		) );

		// prime cache
		$count = bp_notifications_get_unread_notification_count( $u );

		// just to be sure...
		$this->assertEquals( 2, $count, 'Cache count should be 2 before invalidation.' );

		// Trigger invalidation via save
		self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u,
			'item_id'        => 2
		) );

		$this->assertFalse( wp_cache_get( 'all_for_user_' . $u, 'bp_notifications' ) );
		$this->assertFalse( wp_cache_get( $u, 'bp_notifications_unread_count' ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_invalidation_all_for_user_on_delete() {
		$u  = self::factory()->user->create();
		$n1 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );
		self::factory()->notification->create( array(
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
		$this->assertFalse( wp_cache_get( $u, 'bp_notifications_unread_count' ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_invalidation_all_for_user_on_update_user_id() {
		$u = self::factory()->user->create();

		self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );
		self::factory()->notification->create( array(
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
		$this->assertFalse( wp_cache_get( $u, 'bp_notifications_unread_count' ) );
	}

	/**
	 * @group cache
	 */
	public function test_cache_invalidation_all_for_user_on_update_id() {
		$u  = self::factory()->user->create();
		$n1 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );

		self::factory()->notification->create( array(
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
		$this->assertFalse( wp_cache_get( $u, 'bp_notifications_unread_count' ) );
	}

	/**
	 * @group bp_notifications_update_meta_cache
	 */
	public function test_bp_notifications_update_meta_cache() {
		$u = self::factory()->user->create();

		$n1 = self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u
		) );

		$n2 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );

		// Add cache for each notification.
		bp_notifications_update_meta( $n1, 'meta', 'data' );
		bp_notifications_update_meta( $n1, 'data', 'meta' );
		bp_notifications_update_meta( $n2, 'meta', 'human' );

		// Prime cache.
		bp_notifications_get_meta( $n1, 'meta' );

		// Ensure an empty cache for second notification.
		wp_cache_delete( $n2, 'notification_meta' );

		// Update notification meta cache.
		bp_notifications_update_meta_cache( array( $n1, $n2 ) );

		$expected = array(
			$n1 => array(
				'meta' => array(
					'data',
				),
				'data' => array(
					'meta',
				),
			),
			$n2 => array(
				'meta' => array(
					'human',
				),
			),
		);

		$found = array(
			$n1 => wp_cache_get( $n1, 'notification_meta' ),
			$n2 => wp_cache_get( $n2, 'notification_meta' ),
		);

		$this->assertEquals( $expected, $found );
	}

	/**
	 * @group cache
	 * @ticket BP8637
	 */
	public function test_bp_notifications_clear_all_for_user_cache_before_update() {
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create();

		$notification_ids = self::factory()->notification->create_many(
			4,
			array(
				'component_name'    => 'activity',
				'component_action'  => 'at_mentions',
				'user_id'           => $u,
				'item_id'           => $a,
				'allow_duplicate'   => true,
			)
		);

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $u );
		$this->assertEquals( $notification_ids, wp_list_pluck( $all_for_user_notifications, 'id' ) );

		// Mark as read.
		$amount = bp_notifications_mark_notifications_by_ids( $notification_ids );
		$this->assertTrue( $amount === count( $notification_ids ) );

		// Add a new one.
		$notification_id = self::factory()->notification->create(
			array(
				'component_name'    => 'activity',
				'component_action'  => 'at_mentions',
				'user_id'           => $u,
				'item_id'           => $a,
				'allow_duplicate'   => true,
			)
		);

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $u );
		$all_ids = wp_list_pluck( $all_for_user_notifications, 'id' );

		$this->assertEmpty( array_intersect( $notification_ids, $all_ids ) );
		$this->assertContains( $notification_id, $all_ids );
	}

	/**
	 * @group cache
	 * @ticket BP8642
	 */
	public function test_bp_notifications_clear_all_for_user_cache_before_update_when_marked_unread() {
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create();

		$notification_ids = self::factory()->notification->create_many(
			4,
			array(
				'component_name'    => 'activity',
				'component_action'  => 'at_mentions',
				'user_id'           => $u,
				'item_id'           => $a,
				'is_new'            => 0,
				'allow_duplicate'   => true,
			)
		);

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $u );
		$this->assertEmpty( $all_for_user_notifications );

		// Mark as unread.
		$amount = bp_notifications_mark_notifications_by_ids( $notification_ids, 1 );
		$this->assertTrue( $amount === count( $notification_ids ) );

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $u );
		$this->assertEquals( $notification_ids, wp_list_pluck( $all_for_user_notifications, 'id' ) );
	}

	/**
	 * @group cache
	 * @ticket BP8637
	 */
	public function test_bp_notifications_clear_all_for_user_cache_before_delete() {
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create();

		$notification_ids = self::factory()->notification->create_many(
			4,
			array(
				'component_name'    => 'activity',
				'component_action'  => 'at_mentions',
				'user_id'           => $u,
				'item_id'           => $a,
				'allow_duplicate'   => true,
			)
		);

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $u );
		$this->assertEquals( $notification_ids, wp_list_pluck( $all_for_user_notifications, 'id' ) );

		$u2 = self::factory()->user->create();
		$a2 = self::factory()->activity->create();

		// Check this one is not deleted.
		$notification_id = self::factory()->notification->create(
			array(
				'component_name'    => 'activity',
				'component_action'  => 'at_mentions',
				'user_id'           => $u2,
				'item_id'           => $a2,
				'allow_duplicate'   => true,
			)
		);

		// Delete.
		$amount = bp_notifications_delete_notifications_by_ids( $notification_ids );
		$this->assertTrue( $amount === count( $notification_ids ) );

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $u );
		$all_ids = wp_list_pluck( $all_for_user_notifications, 'id' );

		$this->assertEmpty( array_intersect( $notification_ids, $all_ids ) );

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $u2 );
		$this->assertSame( $all_for_user_notifications[0]->id, $notification_id );
	}

	/**
	 * @group cache
	 * @ticket BP8637
	 */
	public function test_bp_notifications_clear_all_for_user_cache_before_update_when_item_ids() {
		$s = self::factory()->user->create();
		$r = self::factory()->user->create();

		$message_ids = self::factory()->message->create_many(
			4,
			array(
				'sender_id'  => $s,
				'recipients' => array( $r ),
				'content'    => 'testing notification all for user cache',
			)
		);

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $r );
		$this->assertEquals( $message_ids, wp_list_pluck( $all_for_user_notifications, 'item_id' ) );

		// Mark read.
		$amount = bp_notifications_mark_notifications_by_item_ids( $r, $message_ids, 'messages', 'new_message', false );
		$this->assertTrue( $amount === count( $message_ids ) );

		$message_id = self::factory()->message->create(
			array(
				'sender_id'  => $s,
				'recipients' => array( $r ),
				'content'    => 'testing notification all for user cache',
			)
		);

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $r );
		$all_ids = wp_list_pluck( $all_for_user_notifications, 'item_id' );

		$this->assertEmpty( array_intersect( $message_ids, $all_ids ) );
		$this->assertContains( $message_id, $all_ids );
	}

	/**
	 * @group cache
	 * @ticket BP8642
	 */
	public function test_bp_notifications_clear_all_for_user_cache_before_update_when_item_ids_and_marked_unread() {
		$s                = self::factory()->user->create();
		$r                = self::factory()->user->create();
		$notification_ids = array();

		remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

		$message_ids = self::factory()->message->create_many(
			4,
			array(
				'sender_id'  => $s,
				'recipients' => array( $r ),
				'content'    => 'testing notification all for user cache',
			)
		);

		foreach ( $message_ids as $message_id ) {
			$notification_ids[] = self::factory()->notification->create(
				array(
					'component_name'    => 'messages',
					'component_action'  => 'new_message',
					'user_id'           => $r,
					'item_id'           => $message_id,
					'is_new'            => 0,
				)
			);
		}

		add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $r );
		$this->assertEmpty( $all_for_user_notifications );

		// Mark unread.
		$amount = bp_notifications_mark_notifications_by_item_ids( $r, $message_ids, 'messages', 'new_message', 1 );
		$this->assertTrue( $amount === count( $message_ids ) );

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $r );
		$this->assertEquals( $message_ids, wp_list_pluck( $all_for_user_notifications, 'item_id' ) );
	}

	/**
	 * @group cache
	 * @ticket BP8637
	 */
	public function test_bp_notifications_clear_all_for_user_cache_before_delete_when_item_ids() {
		$s = self::factory()->user->create();
		$r = self::factory()->user->create();

		$message_ids = self::factory()->message->create_many(
			4,
			array(
				'sender_id'  => $s,
				'recipients' => array( $r ),
				'content'    => 'testing notification all for user cache',
			)
		);

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $r );
		$this->assertEquals( $message_ids, wp_list_pluck( $all_for_user_notifications, 'item_id' ) );

		$message_id = self::factory()->message->create(
			array(
				'sender_id'  => $r,
				'recipients' => array( $s ),
				'content'    => 'testing notification all for user cache',
			)
		);

		// Delete.
		$amount = bp_notifications_delete_notifications_by_item_ids( $r, $message_ids, 'messages', 'new_message' );
		$this->assertTrue( $amount === count( $message_ids ) );

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $r );
		$all_ids = wp_list_pluck( $all_for_user_notifications, 'item_id' );

		$this->assertEmpty( array_intersect( $message_ids, $all_ids ) );

		$all_for_user_notifications = bp_notifications_get_all_notifications_for_user( $s );
		$this->assertSame( $all_for_user_notifications[0]->item_id, $message_id );
	}
}
