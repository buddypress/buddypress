<?php
/**
 * @group messages
 * @group routing
 */
class BP_Tests_Routing_Messages extends BP_UnitTestCase {
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();

		$this->permalink_structure = get_option( 'permalink_structure', '' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->set_permalink_structure( '/%postname%/' );
	}

	public function tear_down() {
		$this->set_permalink_structure( $this->permalink_structure );

		parent::tear_down();
	}

	/**
	 * @dataProvider provider_member_messages_actions
	 *
	 * @param string   $action_key  Rewrite action key, or an empty string for the default screen.
	 * @param string   $action_slug Fallback action slug.
	 * @param callable $predicate   Routing condition to assert.
	 */
	public function test_member_messages( $action_key, $action_slug, $predicate ) {
		$path_chunks = array(
			'single_item_component' => bp_rewrites_get_slug( 'members', 'member_messages', bp_get_messages_slug() ),
		);

		if ( '' !== $action_key ) {
			$path_chunks['single_item_action'] = bp_rewrites_get_slug( 'members', $action_key, $action_slug );
		}

		$this->go_to( bp_members_get_user_url( bp_loggedin_user_id(), $path_chunks ) );
		$this->assertTrue( call_user_func( $predicate ) );
	}

	/**
	 * Provides the member messages actions and their routing conditions.
	 *
	 * @return array
	 */
	public function provider_member_messages_actions() {
		return array(
			'inbox'   => array( '', '', 'bp_is_messages_inbox' ),
			'sentbox' => array( 'member_messages_sentbox', 'sentbox', 'bp_is_messages_sentbox' ),
			'compose' => array( 'member_messages_compose', 'compose', 'bp_is_messages_compose_screen' ),
			'notices' => array( 'member_messages_notices', 'notices', 'bp_is_notices' ),
		);
	}
}
