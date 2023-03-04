<?php
/**
 * @group xprofile
 * @group routing
 */
class BP_Tests_Routing_XProfile extends BP_UnitTestCase {
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

	function test_member_profile() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_profile', bp_get_profile_slug() ),
				)
			)
		);
		$this->assertTrue( bp_is_user_profile() );
	}

	function test_member_profile_edit() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_profile', bp_get_profile_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_profile_edit', 'edit' ),
				)
			)
		);
		$this->assertTrue( bp_is_user_profile_edit() );
	}

	function test_member_profile_change_avatar() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_profile', bp_get_profile_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_profile_change_avatar', 'change-avatar' ),
				)
			)
		);
		$this->assertTrue( bp_is_user_change_avatar() );
	}
}
