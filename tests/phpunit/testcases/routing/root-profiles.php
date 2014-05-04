<?php
/**
 * @group members
 * @group routing
 * @group root_profiles
 */
class BP_Tests_Routing_Members_Root_Profiles extends BP_UnitTestCase {
	protected $old_current_user = 0;
	protected $u;

	public function setUp() {
		parent::setUp();

		add_filter( 'bp_core_enable_root_profiles', '__return_true' );

		$this->old_current_user = get_current_user_id();
		$uid = $this->factory->user->create( array(
			'user_login' => 'boone',
			'user_nicename' => 'boone',
		) );
		$this->u = new WP_User( $uid );
		$this->set_current_user( $uid );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
		remove_filter( 'bp_core_enable_root_profiles', '__return_true' );
	}

	public function test_members_directory() {
		$this->go_to( home_url( bp_get_members_root_slug() ) );
		$this->assertEquals( bp_get_members_root_slug(), bp_current_component() );
	}

	public function test_member_permalink() {
		$domain = home_url( $this->u->user_nicename );
		$this->go_to( $domain );

		$this->assertTrue( bp_is_user() );
		$this->assertTrue( bp_is_my_profile() );
		$this->assertEquals( $this->u->ID, bp_displayed_user_id() );
	}

	public function test_member_activity_page() {
		$url = home_url( $this->u->user_nicename ) . '/' . bp_get_activity_slug();
		$this->go_to( $url );

		$this->assertTrue( bp_is_user() );
		$this->assertTrue( bp_is_my_profile() );
		$this->assertEquals( $this->u->ID, bp_displayed_user_id() );

		$this->assertTrue( bp_is_activity_component() );
	}
}
