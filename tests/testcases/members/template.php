<?php
/**
 * @group members
 */
class BP_Tests_Members_Template extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		$new_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->set_current_user( $new_user );

	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	public function test_bp_user_query_include_on_user_page() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		$this->go_to( bp_core_get_user_domain( $u1 ) );

		global $members_template;
		bp_has_members( array(
			'include' => array( $u1, $u2 ),
		) );

		$users = is_array( $members_template->members ) ? array_values( $members_template->members ) : array();
		$user_ids = wp_list_pluck( $users, 'ID' );
		sort( $user_ids );

		$shouldbe = array( $u1, $u2 );
		sort( $shouldbe );

		$this->assertEquals( $user_ids, $shouldbe );
	}

	public function test_bp_user_query_friendship_requests() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		friends_add_friend( $u1, $u2 );

		$this->go_to( bp_core_get_user_domain( $u2 ) . bp_get_friends_slug() . '/requests' );

		global $members_template;
		bp_has_members( array(
			'include' => bp_get_friendship_requests( $u2 ),
		) );

		$requests = is_array( $members_template->members ) ? array_values( $members_template->members ) : array();
		$request_ids = wp_list_pluck( $requests, 'ID' );
		$this->assertEquals( $request_ids, array( $u1 ) );
	}

}
