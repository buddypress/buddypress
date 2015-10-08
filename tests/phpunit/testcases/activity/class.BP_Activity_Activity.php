<?php
/**
 * @group activity
 */
class BP_Tests_Activity_Class extends BP_UnitTestCase {

	/**
	 * @group check_exists_by_content
	 */
	public function test_check_exists_by_content() {
		$content  = 'A classy girl who know how to enjoy the freedom of a cup of coffee';
		$activity = $this->factory->activity->create( array(
			'content' => $content,
			'type'    => 'activity_update',
		) );

		$result = BP_Activity_Activity::check_exists_by_content( $content );
		$this->assertEquals( $activity, $result );
	}

	/**
	 * @group delete_activity_item_comments
	 */
	public function test_delete_activity_item_comments() {
		$parent_activity = $this->factory->activity->create( array(
			'type' => 'activity_update',
		) );

		$comments = $this->factory->activity->create_many( 3, array(
			'item_id' => $parent_activity,
			'type'    => 'activity_comment',
		) );

		BP_Activity_Activity::delete_activity_item_comments( $parent_activity );

		$result = BP_Activity_Activity::get( array( 'in' => wp_list_pluck( $comments, 'id' ), ) );
		$this->assertEmpty( $result['activities'] );
	}

	/**
	 * @ticket BP4804
	 * @group delete_activity_meta_entries
	 * @group activitymeta
	 */
	public function test_delete_activity_meta_entries() {
		$activity = $this->factory->activity->create( array(
			'type' => 'activity_update',
		) );

		bp_activity_update_meta( $activity, 'Paul', 'is cool' );
		BP_Activity_Activity::delete_activity_meta_entries( $activity );

		$meta = bp_activity_get_meta( $activity, 'Paul' );
		$this->assertSame( '', $meta );
	}

	/**
	 * @group get
	 * @group fields
	 * @ticket BP6426
	 */
	public function test_get_with_fields_parameter_by_id() {
		$a = $this->factory->activity->create_many( 3, array(
			'type' => 'activity_update',
		) );

		$result = BP_Activity_Activity::get( array(
			'fields' => 'ids',
		) );
		$this->assertEqualSets( $a, $result['activities'] );
	}

	/**
	 * @group get
	 */
	public function test_hide_all_for_user() {
		$activity = $this->factory->activity->create( array(
			'type' => 'activity_update',
		) );

		BP_Activity_Activity::hide_all_for_user( get_current_user_id() );

		$activity = BP_Activity_Activity::get( array(
			'in'          => $activity,
			'show_hidden' => true,
		) );
		$this->assertEquals( $activity['activities'][0]->hide_sitewide, 1 );
	}

	/**
	 * @group get
	 * @group meta_query
	 */
	public function test_get_with_meta_query() {
		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create();
		bp_activity_update_meta( $a1, 'foo', 'bar' );

		$activity = BP_Activity_Activity::get( array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		) );
		$ids = wp_list_pluck( $activity['activities'], 'id' );
		$this->assertEquals( $ids, array( $a1 ) );
	}

	/**
	 * @group get
	 */
	public function test_get_with_meta_query_two_clauses_with_or_relation() {
		$now = time();
		$a1 = $this->factory->activity->create( array(
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );
		$a2 = $this->factory->activity->create( array(
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 60 ),
		) );
		$a3 = $this->factory->activity->create( array(
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 120 ),
		) );
		bp_activity_update_meta( $a1, 'foo', 'bar' );
		bp_activity_update_meta( $a2, 'foo', 'bar' );
		bp_activity_update_meta( $a1, 'baz', 'barry' );

		$activity = BP_Activity_Activity::get( array(
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
				array(
					'key' => 'baz',
					'value' => 'barry',
				),
			),
			'count_total' => 'count_query',
		) );

		$ids = wp_list_pluck( $activity['activities'], 'id' );
		$this->assertEquals( array( $a1, $a2 ), $ids );
		$this->assertEquals( 2, $activity['total'] );
	}

	/**
	 * @group get
	 * @group date_query
	 * @requires PHP 5.3
	 */
	public function test_get_with_date_query_before() {
		if ( ! class_exists( 'WP_Date_Query' ) ) {
			return;
		}

		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create( array(
			'recorded_time' => '2001-01-01 12:00'
		) );
		$a3 = $this->factory->activity->create( array(
			'recorded_time' => '2005-01-01 12:00'
		) );

		// 'date_query' before test
		$query = BP_Activity_Activity::get( array(
			'date_query' => array( array(
				'before' => array(
					'year'  => 2004,
					'month' => 1,
					'day'   => 1,
				),
			) )
		) );
		$this->assertEquals( array( $a2 ), wp_list_pluck( $query['activities'], 'id' ) );
	}

	/**
	 * @group get
	 * @group date_query
	 * @requires PHP 5.3
	 */
	public function test_get_with_date_query_range() {
		if ( ! class_exists( 'WP_Date_Query' ) ) {
			return;
		}

		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create( array(
			'recorded_time' => '2001-01-01 12:00'
		) );
		$a3 = $this->factory->activity->create( array(
			'recorded_time' => '2005-01-01 12:00'
		) );

		// 'date_query' range test
		$query = BP_Activity_Activity::get( array(
			'date_query' => array( array(
				'after'  => 'January 2nd, 2001',
				'before' => array(
					'year'  => 2013,
					'month' => 1,
					'day'   => 1,
				),
				'inclusive' => true,
			) )
		) );
		$this->assertEquals( array( $a3 ), wp_list_pluck( $query['activities'], 'id' ) );
	}

	/**
	 * @group get
	 * @group date_query
	 * @requires PHP 5.3
	 */
	public function test_get_with_date_query_after() {
		if ( ! class_exists( 'WP_Date_Query' ) ) {
			return;
		}

		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create( array(
			'recorded_time' => '2001-01-01 12:00'
		) );
		$a3 = $this->factory->activity->create( array(
			'recorded_time' => '2005-01-01 12:00'
		) );

		// 'date_query' after and relative test
		$query = BP_Activity_Activity::get( array(
			'date_query' => array( array(
				'after' => '1 day ago'
			) )
		) );
		$this->assertEquals( array( $a1 ), wp_list_pluck( $query['activities'], 'id' ) );
	}

	/**
	 * @group get
	 */
	public function test_get_with_search_terms() {
		$a1 = $this->factory->activity->create( array(
			'content' => 'Boone is a cool guy',
		) );
		$a2 = $this->factory->activity->create( array(
			'content' => 'No he isn\'t',
		) );

		$activity = BP_Activity_Activity::get( array(
			'search_terms' => 'cool',
		) );
		$ids = wp_list_pluck( $activity['activities'], 'id' );
		$this->assertEquals( $ids, array( $a1 ) );
	}

	/**
	 * @group get
	 */
	public function test_get_with_display_comments_threaded() {
		$u = $this->factory->user->create();

		$now = time();
		$a1 = $this->factory->activity->create( array(
			'user_id' => $u,
			'content' => 'Life Rules',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );
		$a2 = $this->factory->activity->create( array(
			'user_id' => $u,
			'content' => 'Life Drools',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a3 = bp_activity_new_comment( array(
			'user_id' => $u,
			'activity_id' => $a1,
			'content' => 'Candy is good',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 50 ),
		) );

		$activity = BP_Activity_Activity::get( array(
			'display_comments' => 'threaded',
		) );

		// Kinda crummy, but let's construct a skeleton
		$expected = array(
			$a1 => array( $a3 ),
			$a2 => array(),
		);

		$found = array();
		foreach ( $activity['activities'] as $a ) {
			$found[ $a->id ] = ! empty( $a->children ) ? array_keys( $a->children ) : array();
		}

		$this->assertEquals( $expected, $found );
	}

	/**
	 * @group get
	 */
	public function test_get_with_display_comments_stream() {
		$now = time();
		$a1 = $this->factory->activity->create( array(
			'content' => 'Life Rules',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );
		$a2 = $this->factory->activity->create( array(
			'content' => 'Life Drools',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );

		// bp_activity_new_comment() doesn't allow date_recorded
		$a3 = bp_activity_add( array(
			'action'            => sprintf( __( '%s posted a new activity comment', 'buddypress' ), bp_get_loggedin_user_link() ) ,
			'content'           => 'Candy is good',
			'component'         => buddypress()->activity->id,
			'type'              => 'activity_comment',
			'user_id'           => bp_loggedin_user_id(),
			'item_id'           => $a1,
			'secondary_item_id' => $a1,
			'recorded_time'     => date( 'Y-m-d H:i:s', $now - 50 ),
		) );

		$activity = BP_Activity_Activity::get( array(
			'display_comments' => 'stream',
		) );
		$ids = wp_list_pluck( $activity['activities'], 'id' );
		$this->assertEquals( array( $a1, $a3, $a2 ), $ids );
	}

	/**
	 * @group get
	 */
	public function test_get_with_display_comments_false() {
		$now = time();
		$a1 = $this->factory->activity->create( array(
			'content' => 'Life Rules',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
		) );
		$a2 = $this->factory->activity->create( array(
			'content' => 'Life Drools',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a3 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'content' => 'Candy is good',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 50 ),
		) );

		$activity = BP_Activity_Activity::get( array(
			'display_comments' => false,
		) );
		$ids = wp_list_pluck( $activity['activities'], 'id' );
		$this->assertEquals( array( $a1, $a2 ), $ids );
	}

	/**
	 * @group get
	 */
	public function test_get_with_offset() {
		$now = time();
		$a1 = $this->factory->activity->create( array(
			'content' => 'Life Rules',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a2 = $this->factory->activity->create( array(
			'content' => 'Life Drools',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 50 ),
		) );
		$a3 = $this->factory->activity->create( array(
			'content' => 'Life Drools',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 10 ),
		) );

		$activity = BP_Activity_Activity::get( array(
			'filter' => array(
				'offset' => $a2,
			),
		) );
		$ids = wp_list_pluck( $activity['activities'], 'id' );
		$this->assertEquals( array( $a3, $a2 ), $ids );
	}

	/**
	 * @group get
	 */
	public function test_get_with_since() {
		$now = time();
		$a1 = $this->factory->activity->create( array(
			'content' => 'Life Rules',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a2 = $this->factory->activity->create( array(
			'content' => 'Life Drools',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 50 ),
		) );
		$a3 = $this->factory->activity->create( array(
			'content' => 'Life Drools',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 10 ),
		) );

		$activity = BP_Activity_Activity::get( array(
			'filter' => array(
				'since' => date( 'Y-m-d H:i:s', $now - 70 ),
			),
		) );
		$ids = wp_list_pluck( $activity['activities'], 'id' );
		$this->assertEquals( array( $a3, $a2 ), $ids );
	}

	/**
	 * @group get
	 * @group count_total
	 */
	public function test_get_with_count_total() {
		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create();

		$activity = BP_Activity_Activity::get( array(
			'count_total' => 'count_query',
		) );

		$this->assertEquals( 2, $activity['total'] );
	}

	/**
	 * @group get
	 * @group count_total
	 */
	public function test_get_with_count_total_false() {
		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create();

		$activity = BP_Activity_Activity::get( array(
			'count_total' => false,
		) );

		$this->assertSame( null, $activity['total'] );
	}

	/**
	 * @group get
	 * @group count_total
	 */
	public function test_get_with_count_total_default_to_false() {
		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create();

		$activity = BP_Activity_Activity::get();

		$this->assertSame( null, $activity['total'] );
	}

	/**
	 * @group get_id
	 */
	public function test_get_id_with_item_id() {
		$a1 = $this->factory->activity->create( array(
			'item_id' => 523,
		) );
		$a2 = $this->factory->activity->create( array(
			'item_id' => 1888,
		) );

		$activity = BP_Activity_Activity::get_id( false, false, false, 523, false, false, false, false );
		$this->assertEquals( $a1, $activity );
	}

	/**
	 * @group get_id
	 */
	public function test_get_id_with_secondary_item_id() {
		$a1 = $this->factory->activity->create( array(
			'secondary_item_id' => 523,
		) );
		$a2 = $this->factory->activity->create( array(
			'secondary_content' => 1888,
		) );

		$activity = BP_Activity_Activity::get_id( false, false, false, false, 523, false, false, false );
		$this->assertEquals( $a1, $activity );
	}

	/**
	 * @group delete
	 */
	public function test_delete_with_item_id() {
		$a1 = $this->factory->activity->create( array(
			'item_id' => 523,
		) );
		$a2 = $this->factory->activity->create( array(
			'item_id' => 1888,
		) );

		$activity = BP_Activity_Activity::delete( array(
			'item_id' => 523,
		) );
		$this->assertEquals( array( $a1 ), $activity );
	}

	/**
	 * @group delete
	 */
	public function test_delete_with_secondary_item_id() {
		$a1 = $this->factory->activity->create( array(
			'secondary_item_id' => 523,
		) );
		$a2 = $this->factory->activity->create( array(
			'secondary_item_id' => 1888,
		) );

		$activity = BP_Activity_Activity::delete( array(
			'secondary_item_id' => 523,
		) );
		$this->assertEquals( array( $a1 ), $activity );
	}

	/**
	 * @group get_activity_comments
	 *
	 * Verify the format of the activity comments array, for internal
	 * refactoring
	 */
	public function test_get_activity_comments_format() {
		$now = time();

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$a1 = $this->factory->activity->create( array(
			'content' => 'Life Rules',
			'recorded_time' => date( 'Y-m-d H:i:s', $now ),
			'user_id' => $u1,
		) );
		$a2 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'content' => 'Candy is good',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 50 ),
			'user_id' => $u1,
		) );
		$a3 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'content' => 'Bread is good',
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 25 ),
			'user_id' => $u2,
		) );

		$keys = array( 'id', 'item_id', 'secondary_item_id', 'user_id', 'primary_link', 'component', 'type', 'action', 'content', 'date_recorded', 'hide_sitewide', 'mptt_left', 'mptt_right', 'is_spam' );

		$a2_obj = new BP_Activity_Activity( $a2 );

		$e2 = new stdClass;

		foreach ( $keys as $key ) {
			$e2->{$key} = $a2_obj->{$key};
		}

		$e2_user = new WP_User( $a2_obj->user_id );

		$e2->user_email = $e2_user->user_email;
		$e2->user_nicename = $e2_user->user_nicename;
		$e2->user_login = $e2_user->user_login;
		$e2->display_name = $e2_user->display_name;
		$e2->user_fullname = bp_core_get_user_displayname( $e2->user_id );
		$e2->children = array();
		$e2->depth = 1;

		$a3_obj = new BP_Activity_Activity( $a3 );

		$e3 = new stdClass;

		foreach ( $keys as $key ) {
			$e3->{$key} = $a3_obj->{$key};
		}

		$e3_user = new WP_User( $e3->user_id );

		$e3->user_email = $e3_user->user_email;
		$e3->user_nicename = $e3_user->user_nicename;
		$e3->user_login = $e3_user->user_login;
		$e3->display_name = $e3_user->display_name;
		$e3->user_fullname = bp_core_get_user_displayname( $e3->user_id );
		$e3->children = array();
		$e3->depth = 1;

		$expected = array(
			$a2 => $e2,
			$a3 => $e3,
		);

		$a1_obj = new BP_Activity_Activity( $a1 );
		$comments = BP_Activity_Activity::get_activity_comments( $a1, $a1_obj->mptt_left, $a1_obj->mptt_right, 'ham_only', $a1 );

		$this->assertEquals( $expected, $comments );
	}

	/**
	 * @group get_last_updated
	 */
	public function test_get_last_updated() {
		$now = time();
		$a1 = $this->factory->activity->create( array(
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 500 ),
		) );
		$a2 = $this->factory->activity->create( array(
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 100 ),
		) );
		$a3 = $this->factory->activity->create( array(
			'recorded_time' => date( 'Y-m-d H:i:s', $now - 300 ),
		) );

		$this->assertSame( date( 'Y-m-d H:i:s', $now - 100 ), BP_Activity_Activity::get_last_updated() );
	}

	/**
	 * @group get_recorded_components
	 */
	public function test_get_recorded_components_skip_last_activity_false() {
		$a1 = $this->factory->activity->create( array(
			'component' => 'members',
			'action' => 'last_activity',
		) );
		$a2 = $this->factory->activity->create( array(
			'component' => 'groups',
			'action' => 'created_group',
		) );
		$a3 = $this->factory->activity->create( array(
			'component' => 'friends',
			'action' => 'friendship_accepted',
		) );

		$found = BP_Activity_Activity::get_recorded_components( false );
		sort( $found );

		$this->assertSame( array( 'friends', 'groups', 'members' ), BP_Activity_Activity::get_recorded_components( false ) );
	}

	/**
	 * @group get_recorded_components
	 */
	public function test_get_recorded_components_skip_last_activity_true_filter_empty_component() {
		$a1 = $this->factory->activity->create( array(
			'component' => 'members',
			'action' => 'last_activity',
		) );
		$a2 = $this->factory->activity->create( array(
			'component' => 'groups',
			'action' => 'created_group',
		) );
		$a3 = $this->factory->activity->create( array(
			'component' => 'friends',
			'action' => 'friendship_accepted',
		) );

		$found = BP_Activity_Activity::get_recorded_components( true );
		sort( $found );

		$this->assertSame( array( 'friends', 'groups' ), BP_Activity_Activity::get_recorded_components() );
	}

	/**
	 * @group get_recorded_components
	 */
	public function test_get_recorded_components_skip_last_activity_true_members_component_not_empty() {
		$a1 = $this->factory->activity->create( array(
			'component' => 'members',
			'action' => 'last_activity',
		) );
		$a2 = $this->factory->activity->create( array(
			'component' => 'groups',
			'action' => 'created_group',
		) );
		$a3 = $this->factory->activity->create( array(
			'component' => 'friends',
			'action' => 'friendship_accepted',
		) );
		$a4 = $this->factory->activity->create( array(
			'component' => 'members',
			'action' => 'foo',
		) );

		$found = BP_Activity_Activity::get_recorded_components( true );
		sort( $found );

		$this->assertSame( array( 'friends', 'groups', 'members' ), BP_Activity_Activity::get_recorded_components() );
	}

	/**
	 * @group get_recorded_components
	 */
	public function test_get_recorded_components_skip_last_activity_true_la_in_multiple_components() {
		$a1 = $this->factory->activity->create( array(
			'component' => 'members',
			'action' => 'last_activity',
		) );
		$a2 = $this->factory->activity->create( array(
			'component' => 'groups',
			'action' => 'created_group',
		) );
		$a3 = $this->factory->activity->create( array(
			'component' => 'friends',
			'action' => 'friendship_accepted',
		) );
		$a4 = $this->factory->activity->create( array(
			'component' => 'groups',
			'action' => 'last_activity',
		) );

		$found = BP_Activity_Activity::get_recorded_components( true );
		sort( $found );

		$this->assertSame( array( 'friends', 'groups', ), BP_Activity_Activity::get_recorded_components() );
	}
	/**
	 * @group activity_action
	 */
	public function test_instantiated_action_with_dynamic_callback() {
		bp_activity_set_action(
			'foo',
			'bar',
			'Foo Bar',
			array( $this, 'action_cb' )
		);

		// Create the activity item with a manual activity string
		$a = $this->factory->activity->create( array(
			'component' => 'foo',
			'type' => 'bar',
			'action' => 'baz',
		) );

		$a_obj = new BP_Activity_Activity( $a );

		$this->assertSame( 'Woo Hoo!', $a_obj->action );
	}

	/**
	 * @group activity_action
	 */
	public function test_instantiated_action_without_dynamic_callback_but_with_stored_action() {
		// No callback is registered - this mimics a legacy plugin

		// Create the activity item with a manual activity string
		$a = $this->factory->activity->create( array(
			'component' => 'foo',
			'type' => 'bar1',
			'action' => 'baz',
		) );

		$a_obj = new BP_Activity_Activity( $a );

		$this->assertSame( 'baz', $a_obj->action );
	}

	/**
	 * @group activity_action
	 */
	public function test_instantiated_action_without_dynamic_callback_but_with_no_stored_action() {
		// No callback is registered - this mimics a legacy plugin

		// Create the activity item with a manual activity string
		$a = $this->factory->activity->create( array(
			'component' => 'foo',
			'type' => 'bar2',
		) );

		$a_obj = new BP_Activity_Activity( $a );

		$this->assertSame( '', $a_obj->action );
	}

	public function action_cb( $activity ) {
		return 'Woo Hoo!';
	}
}
