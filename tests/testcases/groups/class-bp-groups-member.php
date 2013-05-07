<?php
/**
 * @group groups
 */
class BP_Tests_BP_Groups_Member_TestCases extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public static function add_user_to_group( $user_id, $group_id ) {
		$new_member                = new BP_Groups_Member;
		$new_member->group_id      = $group_id;
		$new_member->user_id       = $user_id;
		$new_member->inviter_id    = 0;
		$new_member->is_admin      = 0;
		$new_member->user_title    = '';
		$new_member->date_modified = bp_core_current_time();
		$new_member->is_confirmed  = 1;

		$new_member->save();
		return $new_member->id;
	}

	public static function invite_user_to_group( $user_id, $group_id, $inviter_id ) {
		$invite                = new BP_Groups_Member;
		$invite->group_id      = $group_id;
		$invite->user_id       = $user_id;
		$invite->date_modified = bp_core_current_time();
		$invite->inviter_id    = $inviter_id;
		$invite->is_confirmed  = 0;
		$invite->invite_sent   = 1;

		$invite->save();
		return $invite->id;
	}

	public function test_get_recently_joined_with_filter() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Tab',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Diet Rite',
		) );

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1->id );
		self::add_user_to_group( $u, $g2->id );

		$groups = BP_Groups_Member::get_recently_joined( $u, false, false, 'Rite' );

		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2->id ) );
	}

	public function test_get_is_admin_of_with_filter() {
		$g1 = $this->factory->group->create( array(
			'name' => 'RC Cola',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Pepsi',
		) );

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1->id );
		self::add_user_to_group( $u, $g2->id );

		$m1 = new BP_Groups_Member( $u, $g1->id );
		$m1->promote( 'admin' );
		$m2 = new BP_Groups_Member( $u, $g2->id );
		$m2->promote( 'admin' );

		$groups = BP_Groups_Member::get_is_admin_of( $u, false, false, 'eps' );

		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2->id ) );
	}

	public function test_get_is_mod_of_with_filter() {
		$g1 = $this->factory->group->create( array(
			'name' => 'RC Cola',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Pepsi',
		) );

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1->id );
		self::add_user_to_group( $u, $g2->id );

		$m1 = new BP_Groups_Member( $u, $g1->id );
		$m1->promote( 'mod' );
		$m2 = new BP_Groups_Member( $u, $g2->id );
		$m2->promote( 'mod' );

		$groups = BP_Groups_Member::get_is_mod_of( $u, false, false, 'eps' );

		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2->id ) );
	}

	public function test_get_invites_with_exclude() {
		$g1 = $this->factory->group->create( array(
			'name' => 'RC Cola',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Pepsi',
		) );

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		self::add_user_to_group( $u1, $g1->id );
		self::add_user_to_group( $u1, $g2->id );
		self::invite_user_to_group( $u2, $g1->id, $u1 );
		self::invite_user_to_group( $u2, $g2->id, $u1 );

		$groups = BP_Groups_Member::get_invites( $u2, false, false, array( 'awesome', $g1->id ) );

		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2->id ) );
	}

	public function test_get_all_for_group_with_exclude() {
		$g1 = $this->factory->group->create();

		$u1 = $this->create_user();
		$u2 = $this->create_user();
		self::add_user_to_group( $u1, $g1->id );
		self::add_user_to_group( $u2, $g1->id );

		$members = BP_Groups_Member::get_all_for_group( $g1->id, false, false, true, true, array( $u1 ) );

		$mm = (array) $members['members'];
		$ids = wp_list_pluck( $mm, 'user_id' );
		$this->assertEquals( array( $u2 ), $ids );
	}
}

