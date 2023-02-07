<?php

/**
 * @group groups
 * @group template
 */
#[AllowDynamicProperties]
class BP_Tests_Groups_Template_Is_Visible extends BP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		if ( isset( $GLOBALS['groups_template'] ) ) {
			$this->groups_template = $GLOBALS['groups_template'];
		}
	}

	public function tear_down() {
		if ( $this->groups_template ) {
			$GLOBALS['groups_template'] = $this->groups_template;
		}

		parent::tear_down();
	}

	public function test_bp_group_is_visible_no_member() {
		$g = $this->factory->group->create( array( 'status' => 'private' ) );

		$this->assertFalse( bp_group_is_visible( $g ) );
	}

	public function test_bp_group_is_visible_regular_member() {
		$g = $this->factory->group->create( array( 'status' => 'private' ) );
		$u = $this->factory->user->create();

		$this->set_current_user( $u );

		$this->assertFalse( bp_group_is_visible( $g ) );
	}

	public function test_bp_group_is_visible_regular_member_from_group() {
		$g = $this->factory->group->create( array( 'status' => 'private' ) );
		$u = $this->factory->user->create();

		$this->set_current_user( $u );

		$this->add_user_to_group( $u, $g );

		$this->assertTrue( bp_group_is_visible( $g ) );
	}

	public function test_bp_group_is_visible_invalid_group() {
		$u = $this->factory->user->create();

		// Empty the current group.
		$GLOBALS['groups_template'] = new stdClass;
		$GLOBALS['groups_template']->group = null;

		$this->set_current_user( $u );

		$this->assertFalse( bp_group_is_visible() );
	}

	public function test_bp_group_is_visible_admin() {
		$g = $this->factory->group->create( array( 'status' => 'private' ) );
		$u = $this->factory->user->create( array( 'role' => 'administrator' ) );

		$this->set_current_user( $u );

		$this->assertTrue( bp_group_is_visible( $g ) );
	}

	public function test_bp_group_is_visible_using_user_id() {
		$g = $this->factory->group->create( array( 'status' => 'hidden' ) );
		$u = $this->factory->user->create();

		$this->add_user_to_group( $u, $g );

		$this->assertTrue( bp_group_is_visible( $g, $u ) );
	}

	public function test_bp_group_is_not_visible_using_user_id() {
		$g = $this->factory->group->create( array( 'status' => 'private' ) );
		$u = $this->factory->user->create();

		$this->assertFalse( bp_group_is_visible( $g, $u ) );
	}

	public function test_bp_group_is_visible_with_group_slug() {
		$slug = 'test-group';

		$this->factory->group->create(
			array(
				'status' => 'private',
				'slug'   => $slug,
			)
		);

		$u = $this->factory->user->create( array( 'role' => 'administrator' ) );

		$this->set_current_user( $u );

		$this->assertTrue( bp_group_is_visible( $slug ) );
	}

	public function test_bp_group_is_visible_from_current_group() {
		$g = $this->factory->group->create( array( 'status' => 'private' ) );
		$u = $this->factory->user->create( array( 'role' => 'administrator' ) );

		// Fake the current group.
		$GLOBALS['groups_template'] = new stdClass;
		$GLOBALS['groups_template']->group = groups_get_group( $g );

		$this->set_current_user( $u );

		$this->assertTrue( bp_group_is_visible() );
	}
}
