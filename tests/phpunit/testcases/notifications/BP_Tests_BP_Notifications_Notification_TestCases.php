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
		self::factory()->notification->create( array(
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
		$this->assertSame( $expected, $actual );

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
		self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
		) );
		self::factory()->notification->create( array(
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
		$this->assertSame( 1, $n );

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
		self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
			'item_id' => 1,
		) );
		self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
			'item_id' => 2,
		) );
		self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
		) );

		$n = BP_Notifications_Notification::get_total_count( array(
			'user_id' => $u,
			'component_name' => array( 'messages' ),
		) );

		$this->assertSame( 1, $n );

		$n = BP_Notifications_Notification::get_total_count( array(
			'user_id' => $u,
			'component_name' => array( 'groups' ),
		) );

		$this->assertSame( 2, $n );
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
		$this->assertSame( $expected, $actual );
	}

	/**
	 * @group is_new
	 */
	public function test_is_new_true() {
		$u = self::factory()->user->create();
		self::factory()->notification->create( array(
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
		sort( $expected );
		sort( $actual );
		$this->assertSame( $expected, $actual );
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
		self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
			'is_new' => true,
		) );
		self::factory()->notification->create( array(
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
		$this->assertSame( $expected, $actual );
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
		sort( $expected );
		sort( $actual );
		$this->assertSame( $expected, $actual );
	}

	/**
	 * @group get
	 * @group search_terms
	 */
	public function test_get_with_search_terms() {
		$u = self::factory()->user->create();
		self::factory()->notification->create( array(
			'component_name' => 'friends',
			'user_id' => $u,
			'is_new' => false,
		) );
		$n2 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id' => $u,
			'is_new' => true,
		) );
		self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id' => $u,
			'is_new' => true,
		) );

		$n = BP_Notifications_Notification::get( array(
			'user_id' => $u,
			'search_terms' => 'roup',
		) );

		// Check that the correct items are pulled up
		$this->assertSame( [ $n2 ], wp_list_pluck( $n, 'id' ) );
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
		$this->assertSame(
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

		$n1 = self::factory()->notification->create( $args );

		bp_notifications_add_meta( $n1, $meta_key, 'bar' );

		$n2 = self::factory()->notification->create( $args );

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

		$this->assertSame( [ $n1 ], wp_list_pluck( $found_1, 'id' ) );

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

		$this->assertSame( [ $n2 ], wp_list_pluck( $found_2, 'id' ) );
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

		$n1 = self::factory()->notification->create( $args );
		$n2 = self::factory()->notification->create( $args );
		$n3 = self::factory()->notification->create( $args );
		$n4 = self::factory()->notification->create( $args );

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

		$this->assertSame( [ $n2, $n1 ], wp_list_pluck( $found_1, 'id' ) );

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

		$this->assertSame( [ $n1, $n2 ], wp_list_pluck( $found_2, 'id' ) );

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

		$this->assertSame( [ $n4, $n3 ], wp_list_pluck( $found_3, 'id' ) );

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

		$this->assertSame( [ $n3, $n4 ], wp_list_pluck( $found_4, 'id' ) );
	}

	/**
	 * @group get
	 */
	public function test_self_removing_sql_clauses_filter_at_priority_zero() {
		$user_id = self::factory()->user->create();
		$allowed = self::factory()->notification->create( array( 'user_id' => $user_id, 'component_name' => 'groups' ) );
		self::factory()->notification->create( array( 'user_id' => $user_id, 'component_name' => 'messages' ) );

		$calls  = 0;
		$filter = static function( $clauses ) use ( $allowed, &$calls, &$filter ) {
			remove_filter( 'bp_notifications_get_sql_clauses', $filter, 0 );
			++$calls;
			$clauses['where_conditions']['allowed'] = "n.id = {$allowed}";

			return $clauses;
		};

		$args = array( 'user_id' => $user_id );

		add_filter( 'bp_notifications_get_sql_clauses', $filter, 0 );

		try {
			$found = BP_Notifications_Notification::get( $args );

			// Each public method filters its own query independently.

			add_filter( 'bp_notifications_get_sql_clauses', $filter, 0 );
			$total = BP_Notifications_Notification::get_total_count( $args );
		} finally {
			remove_filter( 'bp_notifications_get_sql_clauses', $filter, 0 );
		}

		$this->assertSame( array( $allowed ), wp_list_pluck( $found, 'id' ) );
		$this->assertSame( 1, $total );
		$this->assertSame( 2, $calls );
	}

	public function test_get_sql_clauses_filters_paged_results_and_total() {
		$user_id = self::factory()->user->create();
		$first   = self::factory()->notification->create( array( 'user_id' => $user_id, 'component_name' => 'groups' ) );
		self::factory()->notification->create( array( 'user_id' => $user_id, 'component_name' => 'messages' ) );

		$args = array( 'user_id' => $user_id, 'component_name' => array( 'groups', 'messages' ), 'per_page' => 2, 'page' => 1 );

		$this->assertSame( 2, BP_Notifications_Notification::get_total_count( $args ) );

		$calls  = array();
		$filter = static function( $clauses, $parsed_args ) use ( $first, &$calls ) {
			$calls[] = $parsed_args;
			$clauses['join'] .= " INNER JOIN (SELECT {$first} AS notification_id UNION ALL SELECT {$first}) access ON access.notification_id = n.id";
			$clauses['where_conditions'][] = "access.notification_id = {$first}";

			// A one-to-many JOIN needs distinct rows in both queries.
			$clauses['select'] = 'SELECT COUNT(*)' === $clauses['select'] ? 'SELECT COUNT(DISTINCT n.id)' : 'SELECT DISTINCT n.*';

			return $clauses;
		};

		add_filter( 'bp_notifications_get_sql_clauses', $filter, 10, 2 );

		try {
			$found = BP_Notifications_Notification::get( $args );
			$total = BP_Notifications_Notification::get_total_count( $args );
		} finally {
			remove_filter( 'bp_notifications_get_sql_clauses', $filter );
		}

		$this->assertCount( 2, $calls );
		$this->assertSame( $user_id, $calls[0]['user_id'] );
		$this->assertSame( $calls[0], $calls[1] );

		$this->assertSame( array( $first ), wp_list_pluck( $found, 'id' ) );
		$this->assertSame( 1, $total );
	}

	/**
	 * @group get
	 */
	public function test_get_sql_filters_receive_paged_and_count_contexts() {
		$user_id    = self::factory()->user->create();
		$query_type = array();
		$contexts   = array();
		$filter     = static function( $sql, $type, $args, $clauses ) use ( &$query_type, &$contexts ) {
			$query_type[]     = $type;
			$contexts[ $type ] = array( $args, $clauses );

			return 'count' === $type ? 'SELECT 42' : str_replace( 'WHERE', 'WHERE 1 = 0 AND', $sql );
		};

		add_filter( 'bp_notifications_get_sql', $filter, 10, 4 );

		try {
			$found = BP_Notifications_Notification::get( array( 'user_id' => $user_id ) );
			$total = BP_Notifications_Notification::get_total_count( array( 'user_id' => $user_id ) );
		} finally {
			remove_filter( 'bp_notifications_get_sql', $filter );
		}

		$this->assertSame( array( 'paged', 'count' ), $query_type );
		$this->assertSame( array(), $found );
		$this->assertSame( 42, $total );
		$this->assertSame( $user_id, $contexts['paged'][0]['user_id'] );
		$this->assertSame( $contexts['paged'][0], $contexts['count'][0] );
		$this->assertArrayHasKey( 'where_conditions', $contexts['count'][1] );
	}

	/**
	 * @group get
	 * @expectedDeprecated bp_notifications_get_where_conditions
	 */
	public function test_get_where_conditions_filter_is_deprecated() {
		$user_id = self::factory()->user->create();
		self::factory()->notification->create( array( 'user_id' => $user_id, 'component_name' => 'groups' ) );

		$calls  = array();
		$filter = static function( $conditions, $args, $select, $from, $join, $meta_query ) use ( &$calls ) {
			$calls[] = array( $args, $select, $from, $join, $meta_query );
			$conditions['legacy'] = '1 = 0';

			return $conditions;
		};

		add_filter( 'bp_notifications_get_where_conditions', $filter, 10, 6 );

		try {
			$found = BP_Notifications_Notification::get( array( 'user_id' => $user_id, 'component_name' => 'groups' ) );
			$total = BP_Notifications_Notification::get_total_count( array( 'user_id' => $user_id, 'component_name' => 'groups' ) );
		} finally {
			remove_filter( 'bp_notifications_get_where_conditions', $filter );
		}

		$this->assertSame( array(), $found );
		$this->assertSame( 0, $total );
		$this->assertCount( 2, $calls );
		$this->assertSame( $user_id, $calls[0][0]['user_id'] );
		$this->assertSame( 'SELECT n.*', $calls[0][1] );
		$this->assertSame( 'SELECT COUNT(*)', $calls[1][1] );
		$this->assertSame( $calls[0][2], $calls[1][2] );
		$this->assertSame( '', $calls[0][3] );
		$this->assertSame( array( 'join' => '', 'where' => '' ), $calls[0][4] );
	}
}
