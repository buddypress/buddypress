<?php
/**
 * @group members
 * @group routing
 */
class BP_Tests_Routing_Members extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		buddypress()->members->types = array();
		$this->old_current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create( array( 'user_login' => 'paulgibbs', 'role' => 'subscriber' ) ) );
	}

	public function tearDown() {
		$this->set_current_user( $this->old_current_user );
		parent::tearDown();
	}

	function test_members_directory() {
		$this->go_to( bp_get_members_directory_permalink() );
		$this->assertEquals( bp_get_members_root_slug(), bp_current_component() );
	}

	function test_member_permalink() {
		$this->go_to( bp_core_get_user_domain( bp_loggedin_user_id() ) );
		$this->assertTrue( bp_is_my_profile() );
	}

	/**
	 * @ticket BP6475
	 */
	public function test_member_directory_when_nested_under_wp_page() {
		$p = $this->factory->post->create( array(
			'post_type' => 'page',
			'post_name' => 'foo',
		) );

		$members_page = get_page_by_path( 'members' );

		wp_update_post( array(
			'ID' => $members_page->ID,
			'post_parent' => $p,
		) );

		$members_page_permalink = bp_get_root_domain() . '/foo/members/';
		$this->go_to( $members_page_permalink );

		$this->assertTrue( bp_is_members_component() );
		$this->assertEquals( '', bp_current_action() );
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
		$u = $this->factory->user->create( array( 'user_nicename' => 'foo' ) );
		bp_register_member_type( 'foo' );
		$this->go_to( bp_get_members_directory_permalink() . 'type/foo/' );
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
}
