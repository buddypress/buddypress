<?php
/**
 * @group groups
 * @group routing
 */
class BP_Tests_Routing_Groups extends BP_UnitTestCase {
	protected $old_current_user = 0;
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();

		buddypress()->members->types = array();
		$this->old_current_user = get_current_user_id();
		$this->permalink_structure = get_option( 'permalink_structure', '' );
		$this->set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tear_down() {
		parent::tear_down();
		$this->set_current_user( $this->old_current_user );
		$this->set_permalink_structure( $this->permalink_structure );
	}

	function test_member_groups() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_groups', bp_get_groups_slug() ),
				)
			)
		);
		$this->assertTrue( bp_is_user_groups() );
	}

	function test_member_groups_invitations() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_groups', bp_get_groups_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_friends_invites', 'invites' ),
				)
			)
		);
		$this->assertTrue( bp_is_user_groups() && bp_is_current_action( 'invites' ) );
	}

	/**
	 * @group group_types
	 */
	public function test_group_directory_with_type() {
		$this->set_permalink_structure( '/%postname%/' );
		bp_groups_register_group_type( 'foo' );
		$this->go_to( bp_get_groups_directory_permalink() . 'type/foo/' );
		$this->assertTrue( bp_is_groups_component() && ! bp_is_group() && bp_is_current_action( bp_get_groups_group_type_base() ) && bp_is_action_variable( 'foo', 0 ) );
	}

	/**
	 * @group group_types
	 */
	public function test_group_directory_with_type_that_has_custom_directory_slug() {
		$this->set_permalink_structure( '/%postname%/' );
		bp_groups_register_group_type( 'foo', array( 'has_directory' => 'foos' ) );
		$this->go_to( bp_get_groups_directory_permalink() . 'type/foos/' );
		$this->assertTrue( bp_is_groups_component() && ! bp_is_group() && bp_is_current_action( bp_get_groups_group_type_base() ) && bp_is_action_variable( 'foos', 0 ) );
	}

	/**
	 * @group group_types
	 */
	public function test_group_directory_should_404_for_group_types_that_have_no_directory() {
		$this->set_permalink_structure( '/%postname%/' );
		bp_register_member_type( 'foo', array( 'has_directory' => false ) );
		$this->go_to( bp_get_members_directory_permalink() . 'type/foo/' );
		$this->assertTrue( is_404() );
	}

	/**
	 * @group group_types
	 */
	public function test_group_directory_should_404_for_invalid_group_types() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to( bp_get_members_directory_permalink() . 'type/foo/' );
		$this->assertTrue( is_404() );
	}

	/**
	 * @group group_previous_slug
	 */
	public function test_group_previous_slug_current_slug_should_resolve() {
		$this->set_permalink_structure( '/%postname%/' );
		$g1 = self::factory()->group->create( array(
			'slug' => 'george',
		) );
		groups_edit_base_group_details( array(
			'group_id' => $g1,
			'slug'     => 'ralph',
		) );

		$this->go_to( bp_get_groups_directory_permalink() . 'ralph' );

		$this->assertEquals( $g1, bp_get_current_group_id() );
	}

	/**
	 * @group group_previous_slug
	 */
	public function test_group_previous_slug_should_resolve() {
		$this->set_permalink_structure( '/%postname%/' );
		$g1 = self::factory()->group->create( array(
			'slug' => 'george',
		) );

		groups_edit_base_group_details( array(
			'group_id'       => $g1,
			'slug'           => 'sam!',
			'notify_members' => false,
		) );
		$this->go_to( bp_get_groups_directory_permalink() . 'george' );

		$this->assertEquals( $g1, bp_get_current_group_id() );
	}

	/**
	 * @group group_previous_slug
	 */
	public function test_group_previous_slug_most_recent_takes_precedence() {
		$this->set_permalink_structure( '/%postname%/' );
		$g1 = self::factory()->group->create( array(
			'slug' => 'george',
		) );
		groups_edit_base_group_details( array(
			'group_id'       => $g1,
			'slug'           => 'ralph',
			'notify_members' => false,
		) );
		$g2 = self::factory()->group->create( array(
			'slug' => 'george',
		) );
		groups_edit_base_group_details( array(
			'group_id'       => $g2,
			'slug'           => 'sam',
			'notify_members' => false,
		) );

		$this->go_to( bp_get_groups_directory_permalink() . 'george' );
		$this->assertEquals( $g2, bp_get_current_group_id() );
	}

}
