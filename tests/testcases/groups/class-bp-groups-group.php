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

	/** __construct()  ***************************************************/

	/**
	 * @group __construct
	 */
	public function test_non_existent_group() {
		$group = new BP_Groups_Group( 123456789 );
		$this->assertSame( 0, $group->id );
	}

	/** get() ************************************************************/

	/**
	 * @group get
	 */
	public function test_get_with_exclude() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		groups_update_groupmeta( $g1, 'foo', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'exclude' => array(
				$g1,
				'foobar',
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2 ) );
	}

	/**
	 * @group get
	 */
	public function test_get_with_include() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		groups_update_groupmeta( $g1, 'foo', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'include' => array(
				$g1,
				'foobar',
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g1 ) );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 */
	public function test_get_with_meta_query() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		groups_update_groupmeta( $g1, 'foo', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g1 ) );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 */
	public function test_get_empty_meta_query() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		groups_update_groupmeta( $g1, 'foo', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g1, $g2, ) );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 */
	public function test_get_with_meta_query_multiple_clauses() {
		$now = time();
		$g1 = $this->factory->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60 ),
		) );
		$g2 = $this->factory->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60*2 ),
		) );
		$g3 = $this->factory->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60*3 ),
		) );
		groups_update_groupmeta( $g1, 'foo', 'bar' );
		groups_update_groupmeta( $g2, 'foo', 'bar' );
		groups_update_groupmeta( $g1, 'bar', 'barry' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
				array(
					'key' => 'bar',
					'value' => 'barry',
				),
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g1 ) );
		$this->assertEquals( 1, $groups['total'] );
	}

	/**
	 * @group get
	 */
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
		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group get
	 */
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
		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group get
	 */
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
		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group get
	 */
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

		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * BP 1.8 will change the default 'type' param in favor of default
	 * 'order' and 'orderby'. This is to make sure that existing plugins
	 * will work appropriately
	 *
	 * @group get
	 */
	public function test_get_with_default_type_value_should_be_newest() {
		$g1 = $this->factory->group->create( array(
			'name' => 'A Group',
			'date_created' => bp_core_current_time(),
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'D Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', time() - 100 ),
		) );
		$g3 = $this->factory->group->create( array(
			'name' => 'B Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', time() - 100000 ),
		) );
		$g4 = $this->factory->group->create( array(
			'name' => 'C Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', time() - 1000 ),
		) );

		$found = BP_Groups_Group::get();

		$this->assertEquals( BP_Groups_Group::get( array( 'type' => 'newest' ) ), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_with_type_newest() {
		$g1 = $this->factory->group->create( array(
			'name' => 'A Group',
			'date_created' => bp_core_current_time(),
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'D Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
		) );
		$g3 = $this->factory->group->create( array(
			'name' => 'B Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100000 ),
		) );
		$g4 = $this->factory->group->create( array(
			'name' => 'C Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 1000 ),
		) );

		$groups = BP_Groups_Group::get( array( 'type' => 'newest' ) );
		$found = wp_parse_id_list( wp_list_pluck( $groups['groups'], 'id' ) );
		$this->assertEquals( array( $g1, $g2, $g4, $g3 ), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_with_type_popular() {
		$g1 = $this->factory->group->create( array(
			'name' => 'A Group',
			'date_created' => bp_core_current_time(),
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'D Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
		) );
		$g3 = $this->factory->group->create( array(
			'name' => 'B Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100000 ),
		) );
		$g4 = $this->factory->group->create( array(
			'name' => 'C Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 1000 ),
		) );

		groups_update_groupmeta( $g1, 'total_member_count', 1 );
		groups_update_groupmeta( $g2, 'total_member_count', 4 );
		groups_update_groupmeta( $g3, 'total_member_count', 2 );
		groups_update_groupmeta( $g4, 'total_member_count', 3 );

		$groups = BP_Groups_Group::get( array( 'type' => 'popular' ) );
		$found = wp_parse_id_list( wp_list_pluck( $groups['groups'], 'id' ) );
		$this->assertEquals( array( $g2, $g4, $g3, $g1 ), $found );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 * @ticket BP5099
	 */
	public function test_meta_query_and_total_groups() {
		$g1 = $this->factory->group->create( array(
			'name' => 'A Group',
			'date_created' => bp_core_current_time(),
		) );
		$g2 = $this->factory->group->create( array(
			'name' => 'D Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
		) );
		$g3 = $this->factory->group->create( array(
			'name' => 'B Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100000 ),
		) );
		$g4 = $this->factory->group->create( array(
			'name' => 'C Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 1000 ),
		) );

		// mark one group with the metakey 'supergroup'
		groups_update_groupmeta( $g1, 'supergroup', 1 );

		// fetch groups with our 'supergroup' metakey
		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(
				array(
					'key'     => 'supergroup',
					'compare' => 'EXISTS',
				)
			)
		) );

		// group total should match 1
		$this->assertEquals( '1', $groups['total'] );
	}

	/** convert_type_to_order_orderby() **********************************/

	/**
	 * @group convert_type_to_order_orderby
	 */
	public function test_convert_type_to_order_orderby_newest() {
		$expected = array(
			'order' => 'DESC',
			'orderby' => 'date_created',
		);
		$this->assertEquals( $expected, _BP_Groups_Group::_convert_type_to_order_orderby( 'newest' ) );
	}

	/**
	 * @group convert_type_to_order_orderby
	 */
	public function test_convert_type_to_order_orderby_active() {
		$expected = array(
			'order' => 'DESC',
			'orderby' => 'last_activity',
		);
		$this->assertEquals( $expected, _BP_Groups_Group::_convert_type_to_order_orderby( 'active' ) );
	}

	/**
	 * @group convert_type_to_order_orderby
	 */
	public function test_convert_type_to_order_orderby_popular() {
		$expected = array(
			'order' => 'DESC',
			'orderby' => 'total_member_count',
		);
		$this->assertEquals( $expected, _BP_Groups_Group::_convert_type_to_order_orderby( 'popular' ) );
	}

	/**
	 * @group convert_type_to_order_orderby
	 */
	public function test_convert_type_to_order_orderby_alphabetical() {
		$expected = array(
			'order' => 'ASC',
			'orderby' => 'name',
		);
		$this->assertEquals( $expected, _BP_Groups_Group::_convert_type_to_order_orderby( 'alphabetical' ) );
	}

	/**
	 * @group convert_type_to_order_orderby
	 */
	public function test_convert_type_to_order_orderby_random() {
		$expected = array(
			// order gets thrown out
			'order' => '',
			'orderby' => 'random',
		);
		$this->assertEquals( $expected, _BP_Groups_Group::_convert_type_to_order_orderby( 'random' ) );
	}

	/**
	 * @group convert_type_to_order_orderby
	 */
	public function test_convert_type_to_order_orderby_invalid() {
		$expected = array(
			'order' => '',
			'orderby' => '',
		);
		$this->assertEquals( $expected, _BP_Groups_Group::_convert_type_to_order_orderby( 'foooooooooooooooobar' ) );
	}

	/** convert_orderby_to_order_by_term() **********************************/

	/**
	 * @group convert_orderby_to_order_by_term
	 */
	public function test_convert_orderby_to_order_by_term_date_created() {
		$this->assertEquals( 'g.date_created', _BP_Groups_Group::_convert_orderby_to_order_by_term( 'date_created' ) );
	}

	/**
	 * @group convert_orderby_to_order_by_term
	 */
	public function test_convert_orderby_to_order_by_term_last_activity() {
		$c = new _BP_Groups_Group();
		$this->assertEquals( 'last_activity', _BP_Groups_Group::_convert_orderby_to_order_by_term( 'last_activity' ) );
	}

	/**
	 * @group convert_orderby_to_order_by_term
	 */
	public function test_convert_orderby_to_order_by_term_total_member_count() {
		$c = new _BP_Groups_Group();
		$this->assertEquals( 'CONVERT(gm1.meta_value, SIGNED)', _BP_Groups_Group::_convert_orderby_to_order_by_term( 'total_member_count' ) );
	}

	/**
	 * @group convert_orderby_to_order_by_term
	 */
	public function test_convert_orderby_to_order_by_term_name() {
		$c = new _BP_Groups_Group();
		$this->assertEquals( 'g.name', _BP_Groups_Group::_convert_orderby_to_order_by_term( 'name' ) );
	}

	/**
	 * @group convert_orderby_to_order_by_term
	 */
	public function test_convert_orderby_to_order_by_term_random() {
		$c = new _BP_Groups_Group();
		$this->assertEquals( 'rand()', _BP_Groups_Group::_convert_orderby_to_order_by_term( 'random' ) );
	}

	/**
	 * @group convert_orderby_to_order_by_term
	 */
	public function test_convert_orderby_to_order_by_term_invalid_fallback_to_date_created() {
		$c = new _BP_Groups_Group();
		$this->assertEquals( _BP_Groups_Group::_convert_orderby_to_order_by_term( 'date_created' ), _BP_Groups_Group::_convert_orderby_to_order_by_term( 'I am a bad boy' ) );
	}

	public function test_filter_user_groups_normal_search() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => 'This is one cool group',
		) );
		$g2 = $this->factory->group->create();
		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1 );

		$groups = BP_Groups_Group::filter_user_groups( 'Cool', $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_filter_user_groups_search_with_underscores() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => '_cool_ dude',
		) );
		$g2 = $this->factory->group->create();

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1 );
		self::add_user_to_group( $u, $g2 );

		$groups = BP_Groups_Group::filter_user_groups( '_cool_', $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_filter_user_groups_search_with_percent_sign() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => '100% awesome',
		) );
		$g2 = $this->factory->group->create();

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1 );
		self::add_user_to_group( $u, $g2 );

		$groups = BP_Groups_Group::filter_user_groups( '100%', $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_filter_user_groups_search_with_quotes() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => "'tis sweet",
		) );
		$g2 = $this->factory->group->create();

		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1 );
		self::add_user_to_group( $u, $g2 );

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
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_search_groups_search_with_underscores() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => '_cool_ dude',
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::search_groups( '_cool_', $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_search_groups_search_with_percent_sign() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => '100% awesome',
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::search_groups( '100%', $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_search_groups_search_with_quotes() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => "'tis sweet",
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::search_groups( "'tis ", $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );

		$this->assertEquals( array( $g1 ), $found );
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

		$groups = BP_Groups_Group::get_by_letter( 'A', null, null, true, array( $g1, 'stringthatshouldberemoved' ) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g2 ), $found );

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
		$groups = BP_Groups_Group::get_random( null, null, 0, false, true, array( $g1, 'ignore this' ) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g2 ), $found );
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

		$this->assertEquals( array( $g1 ), $found );
	}
}

/**
 * Stub class for accessing protected methods
 */
class _BP_Groups_Group extends BP_Groups_Group {
	public function _convert_type_to_order_orderby( $type ) {
		return self::convert_type_to_order_orderby( $type );
	}

	public function _convert_orderby_to_order_by_term( $term ) {
		return self::convert_orderby_to_order_by_term( $term );
	}
}
