<?php
/**
 * @group friends
 * @group routing
 */
class BP_Tests_Routing_Friends extends BP_UnitTestCase {
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();

		$this->permalink_structure = get_option( 'permalink_structure', '' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tear_down() {
		$this->set_permalink_structure( $this->permalink_structure );

		parent::tear_down();
	}

	public function test_member_friends() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_friends', bp_get_friends_slug() ),
				)
			)
		);
		$this->assertTrue( bp_is_user_friends() );
	}

	public function test_member_friends_requests() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_friends', bp_get_friends_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_friends_requests', 'requests' ),
				)
			)
		);
		$this->assertTrue( bp_is_user_friend_requests() );
	}
}
