<?php
/**
 * @group activity
 * @group routing
 */
class BP_Tests_Routing_Activity extends BP_UnitTestCase {
	protected $old_current_user = 0;
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();

		$this->old_current_user = get_current_user_id();
		$this->permalink_structure = get_option( 'permalink_structure', '' );
		$this->set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tear_down() {
		parent::tear_down();
		$this->set_current_user( $this->old_current_user );
		$this->set_permalink_structure( $this->permalink_structure );
	}

	function test_activity_directory() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to( bp_get_activity_directory_permalink() );

		$pages        = bp_core_get_directory_pages();
		$component_id = bp_current_component();

		$this->assertEquals( bp_get_activity_root_slug(), $pages->{$component_id}->slug );
	}

	/**
	 * Can't test using bp_activity_get_permalink(); see bp_activity_action_permalink_router().
	 */
	function test_activity_permalink() {
		$this->set_permalink_structure( '/%postname%/' );
		$a = self::factory()->activity->create();
		$activity = self::factory()->activity->get_object_by_id( $a );

		$url = bp_members_get_user_url(
			$activity->user_id,
			array(
				'single_item_component' => bp_rewrites_get_slug( 'members', 'member_activity', bp_get_activity_slug() ),
				'single_item_action'    => $activity->id,
			)
		);
		$this->go_to( $url );
		$this->assertTrue( bp_is_single_activity() );
	}

	function test_member_activity() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_activity', bp_get_activity_slug() ),
				)
			)
		);
		$this->assertTrue( bp_is_user_activity() );
	}

	function test_member_activity_mentions() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_activity', bp_get_activity_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_activity_mentions', 'mentions' ),
				)
			)
		);
		$this->assertTrue( bp_is_user_activity() );
	}

	function test_member_activity_favourites() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_activity', bp_get_activity_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_activity_favorites', 'favorites' ),
				)
			)
		);
		$this->assertTrue( bp_is_user_activity() );
	}

	/**
	 * @group friends
	 */
	function test_member_activity_friends() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_activity', bp_get_activity_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_activity_friends', bp_get_friends_slug() ),
				)
			)
		);
		$this->assertTrue( bp_is_user_friends_activity() );
	}

	/**
	 * @group groups
	 */
	function test_member_activity_groups() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_activity', bp_get_activity_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_activity_groups', bp_get_groups_slug() ),
				)
			)
		);
		$this->assertTrue( bp_is_user_groups_activity() );
	}
}
