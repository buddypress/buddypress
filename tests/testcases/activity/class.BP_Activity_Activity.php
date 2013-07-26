<?php
/**
 * @group activity
 */
class BP_Tests_Activity_Class extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	public function test_check_exists_by_content() {
		$content  = 'A classy girl who know how to enjoy the freedom of a cup of coffee';
		$activity = $this->factory->activity->create( array(
			'content' => $content,
			'type'    => 'activity_update',
		) );

		$result = BP_Activity_Activity::check_exists_by_content( $content );
		$this->assertEquals( $activity, $result );
	}

	public function test_delete_activity_item_comments() {
		$parent_activity = $this->factory->activity->create( array(
			'type' => 'activity_update',
		) );

		$comments = $this->factory->activity->create_many( 3, array(
			'item_id' => $parent_activity->id,
			'type'    => 'activity_comment',
		) );

		BP_Activity_Activity::delete_activity_item_comments( $parent_activity );

		$result = BP_Activity_Activity::get( array( 'in' => wp_list_pluck( $comments, 'id' ), ) );
		$this->assertEmpty( $result['activities'] );
	}

	/**
	 * @ticket BP4804
	 */
	public function test_delete_activity_meta_entries() {
		$activity = $this->factory->activity->create( array(
			'type' => 'activity_update',
		) );

		bp_activity_update_meta( $activity, 'Paul', 'is cool' );
		BP_Activity_Activity::delete_activity_meta_entries( $activity );

		$meta = bp_activity_get_meta( $activity, 'Paul' );
		$this->assertFalse( $meta );
	}

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
		) );

		$ids = wp_list_pluck( $activity['activities'], 'id' );
		$this->assertEquals( array( $a1, $a2 ), $ids );
		$this->assertEquals( 2, $activity['total'] );
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
			'user_id'           => bp_loggedin_user_id,
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
}
