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

	public function test_bp_has_members_include_on_user_page() {
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

	/**
	 * @ticket BP5898
	 * @group bp_has_members
	 */
	public function test_bp_has_members_search_pagination_with_spaces() {
		$u1 = $this->create_user( array( 'display_name' => '~ tilde u1' ) );
		$u2 = $this->create_user( array( 'display_name' => '~ tilde u2' ) );

		$template_args = array(
			'search_terms' => '~ tilde',
			'per_page'     => 1,
		);

		global $members_template;
		$reset_members_template = $members_template;

		bp_has_members( $template_args );

		preg_match( '/&#038;s=(.*)\'/', $members_template->pag_links, $matches );

		$this->assertEquals( urldecode( $matches[1] ), urldecode( $template_args['search_terms'] ) );

		// reset the members template global
		$members_template = $reset_members_template;
	}

	public function test_bp_has_members_friendship_requests() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		friends_add_friend( $u1, $u2 );

		$old_user = get_current_user_id();
		$this->set_current_user( $u2 );

		$this->go_to( bp_core_get_user_domain( $u2 ) . bp_get_friends_slug() . '/requests/' );
		$this->restore_admins();

		global $members_template;
		bp_has_members( array(
			'include' => bp_get_friendship_requests( $u2 ),
		) );

		$requests = is_array( $members_template->members ) ? array_values( $members_template->members ) : array();
		$request_ids = wp_list_pluck( $requests, 'ID' );
		$this->assertEquals( $request_ids, array( $u1 ) );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_has_members
	 * @group friends
	 * @ticket BP5071
	 */
	public function test_bp_has_members_friendship_requests_with_no_requests() {
		$u1 = $this->create_user();
		$u2 = $this->create_user();

		$old_user = get_current_user_id();
		$this->set_current_user( $u2 );

		// For some reason, in all the user switching, the cache gets
		// confused. Never comes up when BP runs normally, because the
		// loggedin_user doesn't change on a pageload. @todo Fix for
		// real in BP
		wp_cache_delete( 'bp_user_domain_' . $u2, 'bp' );

		$this->go_to( bp_core_get_user_domain( $u2 ) . bp_get_friends_slug() . '/requests/' );
		$this->restore_admins();

		global $members_template;
		bp_has_members( array(
			'include' => bp_get_friendship_requests( $u2 ),
		) );

		$requests = is_array( $members_template->members ) ? array_values( $members_template->members ) : array();
		$request_ids = wp_list_pluck( $requests, 'ID' );
		$this->assertEquals( array(), $request_ids );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_get_member_last_active
	 */
	public function test_bp_get_member_last_active_default_params() {
		// Fake the global
		global $members_template;

		$time = date( 'Y-m-d H:i:s', time() - 24*60*60 );
		$members_template = new stdClass;
		$members_template->member = new stdClass;
		$members_template->member->last_activity = $time;

		$this->assertEquals( bp_core_get_last_activity( $time, __( 'active %s', 'buddypress' ) ), bp_get_member_last_active() );
	}

	/**
	 * @group bp_get_member_last_active
	 */
	public function test_bp_get_member_last_active_active_format_true() {
		// Fake the global
		global $members_template;

		$time = date( 'Y-m-d H:i:s', time() - 24*60*60 );
		$members_template = new stdClass;
		$members_template->member = new stdClass;
		$members_template->member->last_activity = $time;

		$this->assertEquals( bp_core_get_last_activity( $time, __( 'active %s', 'buddypress' ) ), bp_get_member_last_active( array( 'active_format' => true, ) ) );
	}

	/**
	 * @group bp_get_member_last_active
	 */
	public function test_bp_get_member_last_active_active_format_false() {
		// Fake the global
		global $members_template;

		$time = date( 'Y-m-d H:i:s', time() - 24*60*60 );
		$members_template = new stdClass;
		$members_template->member = new stdClass;
		$members_template->member->last_activity = $time;

		$this->assertEquals( bp_core_time_since( $time ), bp_get_member_last_active( array( 'active_format' => false, ) ) );
	}
}
