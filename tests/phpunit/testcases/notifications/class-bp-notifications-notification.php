<?php

/**
 * @group notifications
 */
class BP_Tests_BP_Notifications_Notification_TestCases extends BP_UnitTestCase {

	/**
	 * @group get
	 */
	public function test_get_null_component_name() {
		$u = self::factory()->user->create();
		$n1 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
		) );
		$n2 = self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
		) );

		// temporarily turn on groups, shut off messages
		$groups_toggle = isset( buddypress()->active_components['groups'] );
		$messages_toggle = isset( buddypress()->active_components['messages'] );
		buddypress()->active_components['groups'] = 1;
		unset( buddypress()->active_components['messages'] );

		$n = BP_Notifications_Notification::get( array(
			'user_id' => $u,
		) );

		// Check that the correct items are pulled up
		$expected = array( $n1 );
		$actual = wp_list_pluck( $n, 'id' );
		$this->assertEquals( $expected, $actual );

		// reset component toggles.
		if ( $groups_toggle ) {
			buddypress()->active_components['groups'] = 1;
		} else {
			unset( buddypress()->active_components['groups'] );
		}

		if ( $messages_toggle ) {
			buddypress()->active_components['messages'] = 1;
		} else {
			unset( buddypress()->active_components['messages'] );
		}
	}

	/**
	 * @group get_total_count
	 * @ticket BP5300
	 */
	public function test_get_total_count_null_component_name() {
		$u = self::factory()->user->create();
		$n1 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
		) );
		$n2 = self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
		) );

		// temporarily turn on groups, shut off messages
		$groups_toggle = isset( buddypress()->active_components['groups'] );
		$messages_toggle = isset( buddypress()->active_components['messages'] );
		buddypress()->active_components['groups'] = 1;
		unset( buddypress()->active_components['messages'] );

		$n = BP_Notifications_Notification::get_total_count( array(
			'user_id' => $u,
		) );

		// Check that the correct items are pulled up
		$this->assertEquals( 1, $n );

		// reset component toggles.
		if ( $groups_toggle ) {
			buddypress()->active_components['groups'] = 1;
		} else {
			unset( buddypress()->active_components['groups'] );
		}

		if ( $messages_toggle ) {
			buddypress()->active_components['messages'] = 1;
		} else {
			unset( buddypress()->active_components['messages'] );
		}
	}

	/**
	 * @group get_total_count
	 * @ticket BP5300
	 */
	public function test_get_total_count_with_component_name() {
		$u = self::factory()->user->create();
		$n1 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
		) );
		$n2 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
		) );
		$n3 = self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
		) );

		$n = BP_Notifications_Notification::get_total_count( array(
			'user_id' => $u,
			'component_name' => array( 'messages' ),
		) );

		$this->assertEquals( 1, $n );
	}

	/**
	 * @group order_by
	 * @group sort_order
	 */
	public function test_order_by_date() {
		$now = time();
		$u = self::factory()->user->create();
		$n1 = self::factory()->notification->create( array(
			'component_name' => 'friends',
			'user_id' => $u,
			'date_notified' => date( 'Y-m-d H:i:s', $now - 500 ),
		) );
		$n2 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
			'date_notified' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$n3 = self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
			'date_notified' => date( 'Y-m-d H:i:s', $now - 1000 ),
		) );

		$n = BP_Notifications_Notification::get( array(
			'user_id' => $u,
			'order_by' => 'date_notified',
			'sort_order' => 'DESC',
		) );

		// Check that the correct items are pulled up
		$expected = array( $n2, $n1, $n3 );
		$actual = wp_list_pluck( $n, 'id' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @group is_new
	 */
	public function test_is_new_true() {
		$u = self::factory()->user->create();
		$n1 = self::factory()->notification->create( array(
			'component_name' => 'friends',
			'user_id' => $u,
			'is_new' => false,
		) );
		$n2 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
			'is_new' => true,
		) );
		$n3 = self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
			'is_new' => true,
		) );

		$n = BP_Notifications_Notification::get( array(
			'user_id' => $u,
			'is_new' => true,
		) );

		// Check that the correct items are pulled up
		$expected = array( $n2, $n3 );
		$actual = wp_list_pluck( $n, 'id' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @group is_new
	 */
	public function test_is_new_false() {
		$u = self::factory()->user->create();
		$n1 = self::factory()->notification->create( array(
			'component_name' => 'friends',
			'user_id' => $u,
			'is_new' => false,
		) );
		$n2 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
			'is_new' => true,
		) );
		$n3 = self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
			'is_new' => true,
		) );

		$n = BP_Notifications_Notification::get( array(
			'user_id' => $u,
			'is_new' => false,
		) );

		// Check that the correct items are pulled up
		$expected = array( $n1 );
		$actual = wp_list_pluck( $n, 'id' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @group is_new
	 */
	public function test_is_new_both() {
		$u = self::factory()->user->create();
		$n1 = self::factory()->notification->create( array(
			'component_name' => 'friends',
			'user_id' => $u,
			'is_new' => false,
		) );
		$n2 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
			'is_new' => true,
		) );
		$n3 = self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
			'is_new' => true,
		) );

		$n = BP_Notifications_Notification::get( array(
			'user_id' => $u,
			'is_new' => 'both',
		) );

		// Check that the correct items are pulled up
		$expected = array( $n1, $n2, $n3 );
		$actual = wp_list_pluck( $n, 'id' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @group get
	 * @group search_terms
	 */
	public function test_get_with_search_terms() {
		$u = self::factory()->user->create();
		$n1 = self::factory()->notification->create( array(
			'component_name' => 'friends',
			'user_id' => $u,
			'is_new' => false,
		) );
		$n2 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
			'is_new' => true,
		) );
		$n3 = self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
			'is_new' => true,
		) );

		$n = BP_Notifications_Notification::get( array(
			'user_id' => $u,
			'search_terms' => 'roup',
		) );

		// Check that the correct items are pulled up
		$this->assertEquals( [ $n2 ], wp_list_pluck( $n, 'id' ) );
	}

	/**
	 * @group get
	 * @group pagination
	 * @group BP6229
	 */
	public function test_get_paged_sql() {
		$u = self::factory()->user->create();

		$notifications = array();
		for ( $i = 1; $i <= 6; $i++ ) {
			$notifications[] = self::factory()->notification->create( array(
				'component_name'    => 'activity',
				'secondary_item_id' => $i,
				'user_id'           => $u,
				'is_new'            => true,
			) );
		}

		$found = BP_Notifications_Notification::get( array(
			'user_id'  => $u,
			'is_new'   => true,
			'page'     => 2,
			'per_page' => 2,
			'order_by' => 'id',
		) );

		// Check that the correct number of items are pulled up
		$this->assertEquals(
			[ $notifications[2], $notifications[3] ],
			wp_list_pluck( $found, 'id' )
		);
	}

	/**
	 * @group get
	 * @group meta_query
	 */
	public function test_get_notifications_meta_query() {
		$u        = self::factory()->user->create();
		$meta_key = 'foo';
		$args     = [
			'user_id'         => $u,
			'component_name'  => 'activity',
			'allow_duplicate' => true,
		];

		$n1 = bp_notifications_add_notification( $args );

		bp_notifications_add_meta( $n1, $meta_key, 'bar' );

		$n2 = bp_notifications_add_notification( $args );

		$found_1 = BP_Notifications_Notification::get(
			[
				'user_id'    => $u,
				'meta_query' => [
					[
						'key'     => $meta_key,
						'compare' => 'EXISTS'
					]
				],
			]
		);

		$this->assertEquals( [ $n1 ], wp_list_pluck( $found_1, 'id' ) );

		$found_2 = BP_Notifications_Notification::get(
			[
				'user_id'    => $u,
				'meta_query' => [
					[
						'key'     => $meta_key,
						'compare' => 'NOT EXISTS'
					]
				],
			]
		);

		$this->assertEquals( [ $n2 ], wp_list_pluck( $found_2, 'id' ) );
	}

	/**
	 * @group get
	 * @group meta_query
	 */
	public function test_get_notifications_sorted_sql_with_meta_query() {
		$u        = self::factory()->user->create();
		$meta_key = 'foo';
		$args     = [
			'user_id'         => $u,
			'component_name'  => 'activity',
			'allow_duplicate' => true,
		];

		$n1 = bp_notifications_add_notification( $args );
		$n2 = bp_notifications_add_notification( $args );
		$n3 = bp_notifications_add_notification( $args );
		$n4 = bp_notifications_add_notification( $args );

		bp_notifications_add_meta( $n1, $meta_key, 'bar' );
		bp_notifications_add_meta( $n2, $meta_key, 'bar' );

		$found_1 = BP_Notifications_Notification::get(
			[
				'user_id'    => $u,
				'order_by'   => 'id',
				'sort_order' => 'DESC',
				'meta_query' => [
					[
						'key'     => $meta_key,
						'compare' => 'EXISTS'
					]
				],
			]
		);

		$this->assertEquals( [ $n2, $n1 ], wp_list_pluck( $found_1, 'id' ) );

		$found_2 = BP_Notifications_Notification::get(
			[
				'user_id'    => $u,
				'order_by'   => 'id',
				'sort_order' => 'ASC',
				'meta_query' => [
					[
						'key'     => $meta_key,
						'compare' => 'EXISTS'
					]
				],
			]
		);

		$this->assertEquals( [ $n1, $n2 ], wp_list_pluck( $found_2, 'id' ) );

		$found_3 = BP_Notifications_Notification::get(
			[
				'user_id'    => $u,
				'order_by'   => 'id',
				'sort_order' => 'DESC',
				'meta_query' => [
					[
						'key'     => $meta_key,
						'compare' => 'NOT EXISTS'
					]
				],
			]
		);

		$this->assertEquals( [ $n4, $n3 ], wp_list_pluck( $found_3, 'id' ) );

		$found_4 = BP_Notifications_Notification::get(
			[
				'user_id'    => $u,
				'order_by'   => 'id',
				'sort_order' => 'ASC',
				'meta_query' => [
					[
						'key'     => $meta_key,
						'compare' => 'NOT EXISTS'
					]
				],
			]
		);

		$this->assertEquals( [ $n3, $n4 ], wp_list_pluck( $found_4, 'id' ) );
	}
}
