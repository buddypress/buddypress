<?php
/**
 * @group activity
 * @group routing
 */
class BP_Tests_Routing_Activity extends BP_UnitTestCase {
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

	public function test_activity_directory() {
		$this->go_to( bp_get_activity_directory_permalink() );

		$pages        = bp_core_get_directory_pages();
		$component_id = bp_current_component();

		$this->assertSame( bp_get_activity_root_slug(), $pages->{$component_id}->slug );
	}

	/**
	 * Can't test using bp_activity_get_permalink(); see bp_activity_action_permalink_router().
	 */
	public function test_activity_permalink() {
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

	/**
	 * @dataProvider provider_member_activity_actions
	 *
	 * @param string $action_key  Rewrite action key, or an empty string for the default screen.
	 * @param string $action_slug Fallback action slug.
	 */
	public function test_member_activity( $action_key, $action_slug ) {
		$path_chunks = array(
			'single_item_component' => bp_rewrites_get_slug( 'members', 'member_activity', bp_get_activity_slug() ),
		);

		if ( '' !== $action_key ) {
			$path_chunks['single_item_action'] = bp_rewrites_get_slug( 'members', $action_key, $action_slug );
		}

		$this->go_to( bp_members_get_user_url( bp_loggedin_user_id(), $path_chunks ) );
		$this->assertTrue( bp_is_user_activity() );
	}

	/**
	 * Provides the member activity actions.
	 *
	 * @return array
	 */
	public static function provider_member_activity_actions() {
		return array(
			'activity'   => array( '', '' ),
			'mentions'   => array( 'member_activity_mentions', 'mentions' ),
			'favourites' => array( 'member_activity_favorites', 'favorites' ),
		);
	}

	/**
	 * @group friends
	 */
	public function test_member_activity_friends() {
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
	public function test_member_activity_groups() {
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
