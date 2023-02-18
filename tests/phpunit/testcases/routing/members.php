<?php
/**
 * @group members
 * @group routing
 */
class BP_Tests_Routing_Members extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function set_up() {
		parent::set_up();

		buddypress()->members->types = array();
		$this->old_current_user = get_current_user_id();
		$this->set_current_user( self::factory()->user->create( array( 'user_login' => 'paulgibbs', 'role' => 'subscriber' ) ) );
	}

	public function tear_down() {
		$this->set_current_user( $this->old_current_user );
		parent::tear_down();
	}

	function test_members_directory() {
		$this->go_to( bp_get_members_directory_permalink() );

		$pages        = bp_core_get_directory_pages();
		$component_id = bp_current_component();

		$this->assertEquals( bp_get_members_root_slug(), $pages->{$component_id}->slug );
	}

	function test_member_permalink() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) );
		$this->assertTrue( bp_is_my_profile() );
	}

	/**
	 * @ticket BP6286
	 * @group member_types
	 */
	public function test_member_directory_with_member_type() {
		bp_register_member_type( 'foo' );
		$this->go_to( bp_get_members_directory_permalink() . 'type/foo/' );
		$this->assertTrue( bp_is_members_component() );
	}

	/**
	 * @ticket BP6286
	 * @group member_types
	 */
	public function test_member_directory_with_member_type_should_obey_filtered_type_slug() {
		bp_register_member_type( 'foo' );

		add_filter( 'bp_members_member_type_base', array( $this, 'filter_member_type_base' ) );
		$this->go_to( bp_get_members_directory_permalink() . 'buddypress-member-type/foo/' );
		remove_filter( 'bp_members_member_type_base', array( $this, 'filter_member_type_base' ) );
		$this->assertTrue( bp_is_members_component() );
	}

	public function filter_member_type_base( $base ) {
		return 'buddypress-member-type';
	}

	/**
	 * @ticket BP6286
	 * @group member_types
	 */
	public function test_member_directory_with_member_type_that_has_custom_directory_slug() {
		bp_register_member_type( 'foo', array( 'has_directory' => 'foos' ) );
		$this->go_to( bp_get_members_directory_permalink() . 'type/foos/' );
		$this->assertTrue( bp_is_members_component() );
	}

	/**
	 * @ticket BP6286
	 * @group member_types
	 */
	public function test_member_directory_with_member_type_should_be_overridden_by_member_with_same_nicename() {
		$u = self::factory()->user->create( array( 'user_nicename' => 'foo' ) );
		bp_register_member_type( 'foo' );
		$this->go_to( bp_get_members_directory_permalink() . 'type/foo/' );

		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @ticket BP6286
	 * @group member_types
	 */
	public function test_member_directory_should_404_for_member_types_that_have_no_directory() {
		bp_register_member_type( 'foo', array( 'has_directory' => false ) );
		$this->go_to( bp_get_members_directory_permalink() . 'type/foo/' );
		$this->assertTrue( is_404() );
	}

	/**
	 * @ticket BP6325
	 */
	function test_members_shortlink_redirector() {
		$shortlink_member_slug = 'me';

		$this->go_to( bp_get_members_directory_permalink() . $shortlink_member_slug );

		$this->assertSame( get_current_user_id(), bp_displayed_user_id() );
	}
}
