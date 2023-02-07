<?php

/**
 * @group notifications
 */
#[AllowDynamicProperties]
class BP_Tests_Notifications_Functions extends BP_UnitTestCase {

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

		self::factory()->notification->create( $args );

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

		self::factory()->notification->create( $args );

		$args['allow_duplicate'] = true;

		$this->assertNotEmpty( bp_notifications_add_notification( $args ) );
	}

	/**
	 * @group bp_notifications_get_unread_notification_count
	 * @group cache
	 */
	public function test_bp_notifications_get_unread_notification_count_cache() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		self::factory()->notification->create( array(
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
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		// create a mixture of different notifications
		self::factory()->notification->create( array(
			'component_name'    => 'messages',
			'component_action'  => 'new_message',
			'item_id'           => 99,
			'user_id'           => $u2,
			'secondary_item_id' => $u1,
			'is_new'            => true
		) );

		self::factory()->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 99,
			'user_id'           => $u2,
			'secondary_item_id' => $u1,
			'is_new'            => true
		) );

		self::factory()->notification->create( array(
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

	/**
	 * @group bp_notifications_delete_notifications_on_user_delete
	 * @ticket BP6681
	 */
	public function test_bp_notifications_delete_notifications_on_user_delete_should_delete_all_notifications() {
		$u = self::factory()->user->create();

		// Create notifications
		$n1 = self::factory()->notification->create( array(
			'component_name'    => 'messages',
			'component_action'  => 'new_message',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		$n2 = self::factory()->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		$n3 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u,
		) );

		$n4 = self::factory()->notification->create( array(
			'component_name'   => 'friends',
			'component_action' => 'friendship_request',
			'user_id'          => $u,
		) );

		// Create notification for non-core component
		$n5 = self::factory()->notification->create( array(
			'component_name'    => 'foo',
			'component_action'  => 'bar',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		global $wpdb, $bp;

		/**
		 * Can't use BP_Notifications_Notification::get(), because class::parse_args,
		 * checks against bp_notifications_get_registered_components()
		 * and if component is disabled it will be ignored.
		 */
		$query = $wpdb->prepare( "SELECT id FROM {$bp->notifications->table_name} WHERE user_id = %d and is_new = 1", $u );

		// Make sure notifications have been added.
		$found1 = $wpdb->get_col( $query );
		$this->assertEqualSets( array( $n1, $n2, $n3, $n4, $n5 ), $found1 );

		$this->delete_user( $u );

		// Check if notifications are deleted.
		$found2 = $wpdb->get_col( $query );
		$this->assertEmpty( $found2 );
	}

	/**
	 * @group  notification_callback
	 * @ticket BP7141
	 */
	public function test_notification_callback_parameter_integrity() {
		$u = self::factory()->user->create();

		$n = self::factory()->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		// Override activity notification callback so we can test integrity.
		buddypress()->activity->notification_callback = array( $this, 'dummy_notification_callback' );

		// Fetch notifications with string format.
		bp_notifications_get_notifications_for_user( $u, 'string' );

		// Assert!
		// @todo When we cast all numeric strings as integers, this needs to be changed.
		$expected = array(
			'action'            => 'new_at_mention',
			'item_id'           => '99',
			'secondary_item_id' => '0',
			'total_items'       => 1,
			'id'                => (string) $n,
			'format'            => 'string'
		);
		$this->assertEquals( $expected, $this->n_args );

		// Fetch notifications with object format this time.
		bp_notifications_get_notifications_for_user( $u, 'object' );

		// Assert!
		$expected['format'] = 'array';
		$this->assertEquals( $expected, $this->n_args );

		// Reset!
		buddypress()->activity->notification_callback = 'bp_activity_format_notifications';
		unset( $this->n_args );
	}

	/**
	 * Used in test_notification_callback_parameter_integrity() test.
	 */
	public function dummy_notification_callback( $action, $item_id, $secondary_item_id, $total_items, $format = 'string', $id = 0 ) {
		$this->n_args = compact( 'action', 'item_id', 'secondary_item_id', 'total_items', 'id', 'format' );
	}

	/**
	 * @group cache
	 * @ticket BP7130
	 */
	public function test_get_grouped_notifications_for_user_cache_invalidation() {
		$u = self::factory()->user->create();

		$n1 = self::factory()->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		// Prime cache.
		$found = bp_notifications_get_grouped_notifications_for_user( $u );
		$this->assertEquals( 1, $found[0]->total_count );

		$n2 = self::factory()->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 100,
			'user_id'           => $u,
		) );

		$found = bp_notifications_get_grouped_notifications_for_user( $u );
		$this->assertEquals( 2, $found[0]->total_count );
	}

	/**
	 * @ticket BP7827
	 */
	public function test_bp_notifications_personal_data_exporter() {
		$u = self::factory()->user->create();

		// Create notifications
		$n1 = self::factory()->notification->create( array(
			'component_name'    => 'messages',
			'component_action'  => 'new_message',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		$n2 = self::factory()->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		$test_user = new WP_User( $u );

		$actual = bp_notifications_personal_data_exporter( $test_user->user_email, 1 );

		$this->assertTrue( $actual['done'] );

		// Number of exported notification items.
		$this->assertSame( 2, count( $actual['data'] ) );
	}

	/**
	 * @ticket BP8175
	 */
	public function test_notifications_data_should_be_deleted_on_user_delete_non_multisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires non-multisite.' );
		}

		$u = self::factory()->user->create();

		$n1 = self::factory()->notification->create( array(
			'component_name'    => 'messages',
			'component_action'  => 'new_message',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		$found = bp_notifications_get_notifications_for_user( $u, 'object' );
		$this->assertEqualSets( [ $n1 ], wp_list_pluck( $found, 'id' ) );

		wp_delete_user( $u );

		$found = bp_notifications_get_notifications_for_user( $u, 'object' );
		$this->assertEmpty( '', $found );
	}

	/**
	 * @ticket BP8175
	 */
	public function test_notifications_data_should_be_deleted_on_user_delete_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires multisite.' );
		}

		$u = self::factory()->user->create();

		$n1 = self::factory()->notification->create( array(
			'component_name'    => 'messages',
			'component_action'  => 'new_message',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		$found = bp_notifications_get_notifications_for_user( $u, 'object' );
		$this->assertEqualSets( [ $n1 ], wp_list_pluck( $found, 'id' ) );

		wpmu_delete_user( $u );

		$found = bp_notifications_get_notifications_for_user( $u, 'object' );
		$this->assertEmpty( '', $found );
	}

	/**
	 * @ticket BP8175
	 */
	public function test_notifications_data_should_not_be_deleted_on_wp_delete_user_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires multisite.' );
		}

		$u = self::factory()->user->create();

		$n1 = self::factory()->notification->create( array(
			'component_name'    => 'messages',
			'component_action'  => 'new_message',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		$found = bp_notifications_get_notifications_for_user( $u, 'object' );
		$this->assertEqualSets( [ $n1 ], wp_list_pluck( $found, 'id' ) );

		wp_delete_user( $u );

		$found = bp_notifications_get_notifications_for_user( $u, 'object' );
		$this->assertEqualSets( [ $n1 ], wp_list_pluck( $found, 'id' ) );
	}

	/**
	 * @ticket BP8426
	 */
	public function test_bp_notifications_mark_notifications_by_ids() {
		$u = self::factory()->user->create();

		$n = self::factory()->notification->create(
			array(
				'component_name'    => 'barfoo',
				'component_action'  => 'new_bar',
				'item_id'           => 98,
				'user_id'           => $u,
			)
		);

		for ( $i = 101; $i < 111; ++$i ) {
			self::factory()->notification->create(
				array(
					'component_name'    => 'foobar',
					'component_action'  => 'new_foo',
					'item_id'           => $i,
					'user_id'           => $u,
				)
			);
		}

		$unread = wp_list_pluck(
			BP_Notifications_Notification::get(
				array(
					'user_id'           => $u,
					'component_name'    => 'foobar',
					'component_action'  => 'new_foo',
					'is_new'            => 1,
				)
			),
			'id'
		);

		bp_notifications_mark_notifications_by_ids( $unread );

		$read = wp_list_pluck(
			BP_Notifications_Notification::get(
				array(
					'user_id'           => $u,
					'component_name'    => 'foobar',
					'component_action'  => 'new_foo',
					'is_new'            => 0,
				)
			),
			'id'
		);

		$n_get = BP_Notifications_Notification::get(
			array(
				'id' => $n,
				'component_name'    => 'barfoo',
				'component_action'  => 'new_bar',
			)
		);

		$n_obj = reset( $n_get );

		$this->assertEquals( $unread, $read );
		$this->assertEquals( $n, $n_obj->id );
		$this->assertTrue( 1 === (int) $n_obj->is_new );
	}

	/**
	 * @ticket BP8426
	 * @group delete_notifications_by_ids
	 */
	public function test_bp_notifications_delete_notifications_by_ids() {
		$u = self::factory()->user->create();

		$n = self::factory()->notification->create(
			array(
				'component_name'    => 'barfoo',
				'component_action'  => 'new_bar',
				'item_id'           => 98,
				'user_id'           => $u,
			)
		);

		for ( $i = 101; $i < 111; ++$i ) {
			self::factory()->notification->create(
				array(
					'component_name'    => 'foobar',
					'component_action'  => 'new_foo',
					'item_id'           => $i,
					'user_id'           => $u,
				)
			);
		}

		$unread = wp_list_pluck(
			BP_Notifications_Notification::get(
				array(
					'user_id'           => $u,
					'component_name'    => 'foobar',
					'component_action'  => 'new_foo',
					'is_new'            => 1,
				)
			),
			'id'
		);

		bp_notifications_delete_notifications_by_ids( $unread );

		$deleted = wp_list_pluck(
			BP_Notifications_Notification::get(
				array(
					'user_id'           => $u,
					'component_name'    => 'foobar',
					'component_action'  => 'new_foo',
					'is_new'            => 1,
				)
			),
			'id'
		);

		$n_get = BP_Notifications_Notification::get(
			array(
				'id' => $n,
				'component_name'    => 'barfoo',
				'component_action'  => 'new_bar',
			)
		);

		$n_obj = reset( $n_get );

		$this->assertEmpty( $deleted );
		$this->assertEquals( $n, $n_obj->id );
		$this->assertTrue( 1 === (int) $n_obj->is_new );
	}
}
