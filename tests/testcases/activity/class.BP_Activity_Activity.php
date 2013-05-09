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

	public function test_get_meta_query() {
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
}
