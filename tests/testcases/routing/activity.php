<?php
/**
 * @group activity
 * @group routing
 */
class BP_Tests_Routing_Activity extends BP_UnitTestCase {
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

	function test_activity_directory() {
		$this->go_to( bp_get_activity_directory_permalink() );
		$this->assertEquals( bp_get_activity_root_slug(), bp_current_component() );
	}

	/**
	 * Can't test using bp_activity_get_permalink(); see bp_activity_action_permalink_router().
	 */
	function test_activity_permalink() {
		$activity = $this->factory->activity->create();

		$url = bp_core_get_user_domain( $activity->user_id, $activity->user_nicename, $activity->user_login ) . bp_get_activity_slug() . '/' . $activity->id . '/';
		$this->go_to( $url );
		$this->assertTrue( bp_is_single_activity() );
	}

	function test_member_activity() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_activity_slug() );
		$this->assertTrue( bp_is_user_activity() );
	}

	function test_member_activity_mentions() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_activity_slug() . '/mentions'  );
		$this->assertTrue( bp_is_user_activity() );
	}

	function test_member_activity_favourites() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_activity_slug() . '/favorites'  );
		$this->assertTrue( bp_is_user_activity() );
	}

	/**
	 * @group friends
	 */
	function test_member_activity_friends() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_activity_slug() . '/' . bp_get_friends_slug() );
		$this->assertTrue( bp_is_user_friends_activity() );
	}

	/**
	 * @group groups
	 */
	function test_member_activity_groups() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_activity_slug() . '/' . bp_get_groups_slug() );
		$this->assertTrue( bp_is_user_groups_activity() );
	}
}
