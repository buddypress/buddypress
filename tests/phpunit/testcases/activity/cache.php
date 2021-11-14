<?php

/**
 * @group activity
 * @group cache
 */
class BP_Tests_Activity_Cache extends BP_UnitTestCase {
	/**
	 * @group bp_activity_update_meta_cache
	 */
	public function test_bp_activity_update_meta_cache() {
		$a1 = self::factory()->activity->create();
		$a2 = self::factory()->activity->create();

		// Set up some data
		bp_activity_update_meta( $a1, 'foo', 'bar' );
		bp_activity_update_meta( $a1, 'Boone', 'Rules' );

		bp_activity_update_meta( $a2, 'foo', 'baz' );
		bp_activity_update_meta( $a2, 'BuddyPress', 'Is Cool' );

		// Prime the cache for $a1
		bp_activity_get_meta( $a1, 'foo' );

		// Ensure an empty cache for $a2
		wp_cache_delete( $a2, 'activity_meta' );

		bp_activity_update_meta_cache( array( $a1, $a2 ) );

		$expected = array(
			$a1 => array(
				'foo' => array(
					'bar',
				),
				'Boone' => array(
					'Rules',
				),
			),
			$a2 => array(
				'foo' => array(
					'baz',
				),
				'BuddyPress' => array(
					'Is Cool',
				),
			),
		);

		$found = array(
			$a1 => wp_cache_get( $a1, 'activity_meta' ),
			$a2 => wp_cache_get( $a2, 'activity_meta' ),
		);

		$this->assertEquals( $expected, $found );
	}

	/**
	 * @group bp_activity_clear_cache_for_activity
	 */
	public function test_bp_activity_clear_cache_for_activity() {
		$u = self::factory()->user->create();

		$a = self::factory()->activity->create( array(
			'component'     => buddypress()->activity->id,
			'type'          => 'activity_update',
			'user_id'       => $u,
			'content'       => 'foo bar',
		) );

		$a_fp = bp_activity_get( array(
			'type' => 'activity_update',
			'user' => array( 'filter' => array( 'user_id' => $u ) ),
		) );

		$activity_updated = new BP_Activity_Activity( $a );
		$activity_updated->content = 'bar foo';
		$activity_updated->save();

		$a_fp = bp_activity_get( array(
			'type' => 'activity_update',
			'user' => array( 'filter' => array( 'user_id' => $u ) ),
		) );

		$this->assertSame( 'bar foo', $a_fp['activities'][0]->content );
	}

	/**
	 * @ticket BP7237
	 * @ticket BP6643
	 */
	public function test_query_should_be_cached() {
		global $wpdb;

		$u = self::factory()->user->create();
		$a = self::factory()->activity->create( array(
			'component'     => buddypress()->activity->id,
			'type'          => 'activity_update',
			'user_id'       => $u,
			'content'       => 'foo bar',
		) );

		$activity_args = array(
			'per_page' => 10,
			'fields' => 'ids',
			'count_total' => true,
		);

		$a1 = bp_activity_get( $activity_args );

		$num_queries = $wpdb->num_queries;

		$a2 = bp_activity_get( $activity_args );

		$this->assertEqualSets( $a1, $a2 );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @ticket BP7237
	 * @ticket BP6643
	 */
	public function test_query_cache_should_be_skipped_for_different_query_params() {
		global $wpdb;

		$u = self::factory()->user->create();
		$a = self::factory()->activity->create( array(
			'component'     => buddypress()->activity->id,
			'type'          => 'activity_update',
			'user_id'       => $u,
			'content'       => 'foo bar',
		) );

		$activity_args = array(
			'per_page' => 10,
			'fields' => 'ids',
			'count_total' => true,
			'filter' => array(
				'component' => buddypress()->activity->id,
			),
		);

		$a1 = bp_activity_get( $activity_args );

		$num_queries = $wpdb->num_queries;

		// This is enough to make the ID and COUNT clause miss the cache.
		$activity_args['filter']['action'] = 'activity_update';
		$a2 = bp_activity_get( $activity_args );

		$this->assertEqualSets( $a1, $a2 );

		// Two extra queries: one for the IDs, one for the count.
		$n = $num_queries + 2;
		$this->assertSame( $num_queries + 2, $wpdb->num_queries );
	}

	/**
	 * @ticket BP7237
	 * @ticket BP6643
	 */
	public function test_query_cache_should_be_invalidated_by_activity_add() {
		global $wpdb;

		$u = self::factory()->user->create();
		$a1 = self::factory()->activity->create( array(
			'component'     => buddypress()->activity->id,
			'type'          => 'activity_update',
			'user_id'       => $u,
			'content'       => 'foo bar',
		) );

		$activity_args = array(
			'per_page' => 10,
			'fields' => 'ids',
			'count_total' => true,
		);

		$q1 = bp_activity_get( $activity_args );

		// Bust the cache.
		$a2 = self::factory()->activity->create( array(
			'component'     => buddypress()->activity->id,
			'type'          => 'activity_update',
			'user_id'       => $u,
			'content'       => 'foo bar',
		) );

		$num_queries = $wpdb->num_queries;

		$q2 = bp_activity_get( $activity_args );

		$expected = array( $a1, $a2 );

		$this->assertEqualSets( $expected, $q2['activities'] );
		$this->assertEquals( 2, $q2['total'] );
		$this->assertSame( $num_queries + 2, $wpdb->num_queries );
	}

	/**
	 * @ticket BP7237
	 * @ticket BP6643
	 */
	public function test_query_cache_should_be_invalidated_by_activity_edit() {
		global $wpdb;

		$u = self::factory()->user->create();
		$a = self::factory()->activity->create( array(
			'component'     => buddypress()->activity->id,
			'type'          => 'activity_update',
			'user_id'       => $u,
			'content'       => 'foo bar',
		) );

		$activity_args = array(
			'per_page' => 10,
			'fields' => 'ids',
			'count_total' => true,
		);

		$q1 = bp_activity_get( $activity_args );

		// Bust the cache.
		self::factory()->activity->create( array(
			'id'            => $a,
			'component'     => buddypress()->activity->id,
			'type'          => 'activity_update',
			'user_id'       => $u,
			'content'       => 'foo bar baz',
		) );

		$num_queries = $wpdb->num_queries;

		$q2 = bp_activity_get( $activity_args );

		$this->assertEqualSets( $q1, $q2 );
		$this->assertSame( $num_queries + 2, $wpdb->num_queries );
	}

	/**
	 * @ticket BP7237
	 * @ticket BP6643
	 */
	public function test_query_cache_should_be_invalidated_by_activity_delete() {
		global $wpdb;

		$u = self::factory()->user->create();
		$a = self::factory()->activity->create( array(
			'component'     => buddypress()->activity->id,
			'type'          => 'activity_update',
			'user_id'       => $u,
			'content'       => 'foo bar',
		) );

		$activity_args = array(
			'per_page' => 10,
			'fields' => 'ids',
			'count_total' => true,
		);

		$q1 = bp_activity_get( $activity_args );

		// Bust the cache.
		bp_activity_delete( array(
			'id' => $a,
		) );

		$num_queries = $wpdb->num_queries;

		$q2 = bp_activity_get( $activity_args );

		$this->assertEqualSets( array(), $q2['activities'] );
		$this->assertEquals( 0, $q2['total'] );
		$this->assertSame( $num_queries + 2, $wpdb->num_queries );
	}

	/**
	 * @ticket BP7237
	 * @ticket BP6643
	 */
	public function test_query_cache_should_be_invalidated_by_activitymeta_add() {
		global $wpdb;

		$activities = self::factory()->activity->create_many( 2 );
		bp_activity_add_meta( $activities[0], 'foo', 'bar' );

		$activity_args = array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		);

		$q1 = bp_activity_get( $activity_args );
		$this->assertEqualSets( array( $activities[0] ), wp_list_pluck( $q1['activities'], 'id' ) );

		bp_activity_add_meta( $activities[1], 'foo', 'bar' );

		$q2 = bp_activity_get( $activity_args );
		$this->assertEqualSets( array( $activities[0], $activities[1] ), wp_list_pluck( $q2['activities'], 'id' ) );
	}

	/**
	 * @ticket BP7237
	 * @ticket BP6643
	 */
	public function test_query_cache_should_be_invalidated_by_activitymeta_update() {
		global $wpdb;

		$activities = self::factory()->activity->create_many( 2 );
		bp_activity_add_meta( $activities[0], 'foo', 'bar' );
		bp_activity_add_meta( $activities[1], 'foo', 'baz' );

		$activity_args = array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		);

		$q1 = bp_activity_get( $activity_args );
		$this->assertEqualSets( array( $activities[0] ), wp_list_pluck( $q1['activities'], 'id' ) );

		bp_activity_update_meta( $activities[1], 'foo', 'bar' );

		$q2 = bp_activity_get( $activity_args );
		$this->assertEqualSets( array( $activities[0], $activities[1] ), wp_list_pluck( $q2['activities'], 'id' ) );
	}

	/**
	 * @ticket BP7237
	 * @ticket BP6643
	 */
	public function test_query_cache_should_be_invalidated_by_activitymeta_delete() {
		global $wpdb;

		$activities = self::factory()->activity->create_many( 2 );
		bp_activity_add_meta( $activities[0], 'foo', 'bar' );
		bp_activity_add_meta( $activities[1], 'foo', 'bar' );

		$activity_args = array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		);

		$q1 = bp_activity_get( $activity_args );
		$this->assertEqualSets( array( $activities[0], $activities[1] ), wp_list_pluck( $q1['activities'], 'id' ) );

		bp_activity_delete_meta( $activities[1], 'foo', 'bar' );

		$q2 = bp_activity_get( $activity_args );
		$this->assertEqualSets( array( $activities[0] ), wp_list_pluck( $q2['activities'], 'id' ) );
	}

	/**
	 * @ticket BP8296
	 */
	public function test_activity_comments_cache_should_be_cleared_when_parent_activity_marked_as_spam() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$activity = self::factory()->activity->create(
			array(
				'component' => buddypress()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $u1,
				'content'   => 'bar',
			)
		);

		$comment = bp_activity_new_comment(
			array(
				'activity_id'       => $activity,
				'skip_notification' => true,
				'user_id'           => $u2,
				'content'           => 'foo',
			)
		);

		$activities = bp_activity_get(
			array(
				'display_comments' => true,
			)
		);

		$cached_ids = wp_list_pluck( wp_cache_get( $activity, 'bp_activity_comments' ), 'content', 'id' );
		$this->assertTrue( 'foo' === $cached_ids[ $comment ] );

		$activity_obj = new BP_Activity_Activity( $activity );

		// An activity is always saved after being marked as spam.
		bp_activity_mark_as_spam( $activity_obj );
		$activity_obj->save();

		$this->assertFalse( wp_cache_get( $activity, 'bp_activity_comments' ), 'The activity comments cache should be cleared when an activity has been marked as spam' );
	}

	/**
	 * @ticket BP8296
	 */
	public function test_activity_comments_cache_should_be_cleared_when_activity_comment_marked_as_spam() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$activity = self::factory()->activity->create(
			array(
				'component' => buddypress()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $u1,
				'content'   => 'foo',
			)
		);

		$comment = bp_activity_new_comment(
			array(
				'activity_id'       => $activity,
				'skip_notification' => true,
				'user_id'           => $u2,
				'content'           => 'bar',
			)
		);

		$activities = bp_activity_get(
			array(
				'display_comments' => true,
			)
		);

		$cached_ids = wp_list_pluck( wp_cache_get( $activity, 'bp_activity_comments' ), 'content', 'id' );
		$this->assertTrue( 'bar' === $cached_ids[ $comment ] );

		$comment_obj = new BP_Activity_Activity( $comment );

		// An activity is always saved after being marked as spam.
		bp_activity_mark_as_spam( $comment_obj );
		$comment_obj->save();

		$this->assertFalse( wp_cache_get( $activity, 'bp_activity_comments' ), 'The parent activity comments cache should be cleared when an activity comment has been marked as spam' );
	}

	/**
	 * @ticket BP8296
	 */
	public function test_activity_comments_cache_should_be_cleared_when_parent_activity_marked_as_ham() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$activity = self::factory()->activity->create(
			array(
				'component' => buddypress()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $u1,
				'content'   => 'bar',
			)
		);

		$comment = bp_activity_new_comment(
			array(
				'activity_id'       => $activity,
				'skip_notification' => true,
				'user_id'           => $u2,
				'content'           => 'foo',
			)
		);

		$activities = bp_activity_get(
			array(
				'display_comments' => true,
			)
		);

		$activity_obj          = new BP_Activity_Activity( $activity );
		$activity_obj->is_spam = 1;
		$activity_obj->save();

		// An activity is always saved after being marked as spam.
		bp_activity_mark_as_ham( $activity_obj );
		$activity_obj->save();

		$this->assertFalse( wp_cache_get( $activity, 'bp_activity_comments' ), 'The activity comments cache should be cleared when an activity has been marked as ham' );
	}

	/**
	 * @ticket BP8296
	 */
	public function test_activity_comments_cache_should_be_cleared_when_activity_comment_marked_as_ham() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$activity = self::factory()->activity->create(
			array(
				'component' => buddypress()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $u1,
				'content'   => 'foo',
			)
		);

		$comment = bp_activity_new_comment(
			array(
				'activity_id'       => $activity,
				'skip_notification' => true,
				'user_id'           => $u2,
				'content'           => 'bar',
			)
		);

		$activities = bp_activity_get(
			array(
				'display_comments' => true,
			)
		);

		$comment_obj          = new BP_Activity_Activity( $comment );
		$comment_obj->is_spam = 1;
		$comment_obj->save();

		// An activity is always saved after being marked as spam.
		bp_activity_mark_as_ham( $comment_obj );
		$comment_obj->save();

		$this->assertFalse( wp_cache_get( $activity, 'bp_activity_comments' ), 'The parent activity comments cache should be cleared when an activity comment has been marked as ham' );
	}

	/**
	 * @ticket BP8296
	 */
	public function test_activity_comments_cache_should_be_cleared_when_activity_comment_has_been_updated() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$activity = self::factory()->activity->create(
			array(
				'component' => buddypress()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $u1,
				'content'   => 'foo',
			)
		);

		$comment = bp_activity_new_comment(
			array(
				'activity_id'       => $activity,
				'skip_notification' => true,
				'user_id'           => $u2,
				'content'           => 'bar',
			)
		);

		$activities = bp_activity_get(
			array(
				'display_comments' => true,
			)
		);

		$comment_obj          = new BP_Activity_Activity( $comment );
		$comment_obj->content = 'foo';
		$comment_obj->save();

		$this->assertFalse( wp_cache_get( $activity, 'bp_activity_comments' ), 'The parent activity comments cache should be cleared when an activity comment has been updated' );
	}

	/**
	 * @ticket BP8296
	 */
	public function test_activity_comments_cache_should_be_cleared_when_activity_comment_has_been_deleted() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$activity = self::factory()->activity->create(
			array(
				'component' => buddypress()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $u1,
				'content'   => 'foo',
			)
		);

		$comment = bp_activity_new_comment(
			array(
				'activity_id'       => $activity,
				'skip_notification' => true,
				'user_id'           => $u2,
				'content'           => 'bar',
			)
		);

		$activities = bp_activity_get(
			array(
				'display_comments' => true,
			)
		);

		bp_activity_delete_comment( $activity, $comment );

		$this->assertFalse( wp_cache_get( $activity, 'bp_activity_comments' ), 'The parent activity comments cache should be cleared when an activity comment has been deleted' );
	}

	/**
	 * @ticket BP8296
	 */
	public function test_activity_comments_cache_should_be_cleared_when_activity_comment_has_been_added() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$activity = self::factory()->activity->create(
			array(
				'component' => buddypress()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $u1,
				'content'   => 'foo',
			)
		);

		$c1 = bp_activity_new_comment(
			array(
				'activity_id'       => $activity,
				'skip_notification' => true,
				'user_id'           => $u2,
				'content'           => 'bar',
			)
		);

		$activities = bp_activity_get(
			array(
				'display_comments' => true,
			)
		);

		$c2 = bp_activity_new_comment(
			array(
				'activity_id'       => $activity,
				'skip_notification' => true,
				'user_id'           => $u1,
				'content'           => 'taz',
			)
		);

		$this->assertFalse( wp_cache_get( $activity, 'bp_activity_comments' ), 'The parent activity comments cache should be cleared when a new activity comment has been added' );
	}
}
