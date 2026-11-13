<?php
/**
 * @group xprofile
 * @group routing
 */
class BP_Tests_Routing_XProfile extends BP_UnitTestCase {
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
	 * @dataProvider provider_member_profile_actions
	 *
	 * @param string   $action_key  Rewrite action key, or an empty string for the default screen.
	 * @param string   $action_slug Fallback action slug.
	 * @param callable $predicate   Routing condition to assert.
	 */
	public function test_member_profile( $action_key, $action_slug, $predicate ) {
		$path_chunks = array(
			'single_item_component' => bp_rewrites_get_slug( 'members', 'member_profile', bp_get_profile_slug() ),
		);

		if ( '' !== $action_key ) {
			$path_chunks['single_item_action'] = bp_rewrites_get_slug( 'members', $action_key, $action_slug );
		}

		$this->go_to( bp_members_get_user_url( bp_loggedin_user_id(), $path_chunks ) );
		$this->assertTrue( call_user_func( $predicate ) );
	}

	/**
	 * Provides the member profile actions and their routing conditions.
	 *
	 * @return array
	 */
	public static function provider_member_profile_actions() {
		return array(
			'profile'       => array( '', '', 'bp_is_user_profile' ),
			'edit'          => array( 'member_profile_edit', 'edit', 'bp_is_user_profile_edit' ),
			'change-avatar' => array( 'member_profile_change_avatar', 'change-avatar', 'bp_is_user_change_avatar' ),
		);
	}
}
