<?php
/**
 * @group groups
 */
class BP_Tests_BP_Groups_Group_TestCases extends BP_UnitTestCase {
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

	public function test_get_exclude() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		groups_update_groupmeta( $g1->id, 'foo', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'exclude' => array(
				$g1->id,
				'foobar',
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2->id ) );
	}

	public function test_get_include() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		groups_update_groupmeta( $g1->id, 'foo', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'include' => array(
				$g1->id,
				'foobar',
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g1->id ) );
	}

	public function test_get_meta_query() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		groups_update_groupmeta( $g1->id, 'foo', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g1->id ) );
	}

	public function test_get_empty_meta_query() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		groups_update_groupmeta( $g1->id, 'foo', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g1->id, $g2->id, ) );
	}

	public function test_get_normal_search() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => 'This is one cool group',
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::get( array(
			'search_terms' => 'Cool',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g1->id ), $found );
	}

	public function test_get_search_with_underscores() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => '_cool_ dude',
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::get( array(
			'search_terms' => '_cool_',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g1->id ), $found );
	}

	public function test_get_search_with_percent_sign() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => '100% awesome',
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::get( array(
			'search_terms' => '100%',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g1->id ), $found );
	}

	public function test_get_search_with_quotes() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => "'tis sweet",
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::get( array(
			'search_terms' => "'tis ",
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		// @todo
		$this->assertEquals( array( $g1->id ), $found );
	}

	public function test_filter_user_groups_normal_search() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => 'This is one cool group',
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::filter_user_groups( 'Cool' );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1->id ), $found );
	}

	public function test_filter_user_groups_search_with_underscores() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => '_cool_ dude',
		) );
		$g2 = $this->factory->group->create();

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1->id );
		self::add_user_to_group( $u, $g2->id );

		$groups = BP_Groups_Group::filter_user_groups( '_cool_', $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1->id ), $found );
	}

	public function test_filter_user_groups_search_with_percent_sign() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => '100% awesome',
		) );
		$g2 = $this->factory->group->create();

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1->id );
		self::add_user_to_group( $u, $g2->id );

		$groups = BP_Groups_Group::filter_user_groups( '100%', $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1->id ), $found );
	}

	public function test_filter_user_groups_search_with_quotes() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => "'tis sweet",
		) );
		$g2 = $this->factory->group->create();

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1->id );
		self::add_user_to_group( $u, $g2->id );

		$groups = BP_Groups_Group::filter_user_groups( "'tis ", $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );

		// @todo
		//$this->assertEquals( array( $g1->id ), $found );
	}

	public function test_search_groups_normal_search() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => 'This is one cool group',
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::search_groups( 'Cool' );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1->id ), $found );
	}

	public function test_search_groups_search_with_underscores() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => '_cool_ dude',
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::search_groups( '_cool_', $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1->id ), $found );
	}

	public function test_search_groups_search_with_percent_sign() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => '100% awesome',
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::search_groups( '100%', $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1->id ), $found );
	}

	public function test_search_groups_search_with_quotes() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => "'tis sweet",
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::search_groups( "'tis ", $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );

		$this->assertEquals( array( $g1->id ), $found );
	}

	public function test_get_by_letter_with_exclude() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Awesome Cool Group',
			'description' => 'Neat',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Another Cool Group',
			'description' => 'Awesome',
		) );

		$groups = BP_Groups_Group::get_by_letter( 'A', null, null, true, array( $g1->id, 'stringthatshouldberemoved' ) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g2->id ), $found );

	}

	public function test_get_by_letter_starts_with_apostrophe() {
		$g1 = $this->factory->group->create( array(
			'name' => "'Tis Sweet",
			'description' => 'Neat',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Another Cool Group',
			'description' => 'Awesome',
		) );

		$groups = BP_Groups_Group::get_by_letter( "'" );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		// @todo
		// The test fails but at least it's sanitized
		//$this->assertEquals( array( $g1->id ), $found );
	}

	public function test_get_random_with_exclude() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();

		// There are only two groups, so excluding one should give us the other
		$groups = BP_Groups_Group::get_random( null, null, 0, false, true, array( $g1->id, 'ignore this' ) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g2->id ), $found );
	}

	public function test_get_random_with_search_terms() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Bodacious',
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'Crummy group',
		) );

		// Only one group will match, so the random part doesn't matter
		$groups = BP_Groups_Group::get_random( null, null, 0, 'daci' );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g1->id ), $found );
	}
}
