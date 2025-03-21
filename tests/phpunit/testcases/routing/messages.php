<?php
/**
 * @group messages
 * @group routing
 */
class BP_Tests_Routing_Messages extends BP_UnitTestCase {
	protected $old_current_user = 0;
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();

		$this->old_current_user = get_current_user_id();
		$this->permalink_structure = get_option( 'permalink_structure', '' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tear_down() {
		wp_set_current_user( $this->old_current_user );
		$this->set_permalink_structure( $this->permalink_structure );

		parent::tear_down();
	}

	function test_member_messages() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_messages', bp_get_messages_slug() ),
				)
			)
		);
		$this->assertTrue( bp_is_messages_inbox() );
	}

	function test_member_messages_sentbox() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_messages', bp_get_messages_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_messages_sentbox', 'sentbox' ),
				)
			)
		);
		$this->assertTrue( bp_is_messages_sentbox() );
	}

	function test_member_messages_compose() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_messages', bp_get_messages_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_messages_compose', 'compose' ),
				)
			)
		);
		$this->assertTrue( bp_is_messages_compose_screen() );
	}

	function test_member_messages_notices() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_messages', bp_get_messages_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_messages_notices', 'notices' ),
				)
			)
		);
		$this->assertTrue( bp_is_notices() );
	}
}
