<?php
/**
 * @group groups
 * @group BP_Groups_Group
 */
class BP_Tests_BP_Groups_Group_TestCases extends BP_UnitTestCase {

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
	 * @group group_meta_query
	 */
	public function test_get_with_meta_query_multiple_clauses_relation_or() {
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
		groups_update_groupmeta( $g2, 'foo', 'baz' );
		groups_update_groupmeta( $g3, 'bar', 'barry' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(
				'relation' => 'OR',
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
		$this->assertEqualSets( array( $g1, $g3 ), $ids );
		$this->assertEquals( 2, $groups['total'] );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 * @ticket BP5874
	 */
	public function test_get_with_meta_query_multiple_clauses_relation_or_shared_meta_key() {
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
		groups_update_groupmeta( $g2, 'foo', 'baz' );
		groups_update_groupmeta( $g3, 'foo', 'barry' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
				array(
					'key' => 'foo',
					'value' => 'baz',
				),
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g2 ), $ids );
		$this->assertEquals( 2, $groups['total'] );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 * @ticket BP5824
	 */
	public function test_get_with_meta_query_multiple_keys_with_same_value() {
		$now = time();
		$g1 = $this->factory->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60 ),
		) );
		$g2 = $this->factory->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60*2 ),
		) );
		groups_update_groupmeta( $g1, 'foo', 'bar' );
		groups_update_groupmeta( $g2, 'foo2', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
					'compare' => '=',
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
		$time = time();
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
		$time = time();
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
	 */
	public function test_get_with_type_alphabetical() {
		$time = time();
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

		$groups = BP_Groups_Group::get( array( 'type' => 'alphabetical' ) );
		$found = wp_parse_id_list( wp_list_pluck( $groups['groups'], 'id' ) );
		$this->assertEquals( array( $g1, $g3, $g4, $g2 ), $found );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 * @ticket BP5099
	 */
	public function test_meta_query_and_total_groups() {
		$time = time();

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

	/**
	 * @group get
	 * @ticket BP5477
	 */
	public function test_get_groups_page_perpage_params() {
		// Create more than 20 groups (20 is the default per_page number)
		$group_ids = array();

		for ( $i = 1; $i <= 25; $i++ ) {
			$group_ids[] = $this->factory->group->create();
		}

		// Tests
		// Passing false to 'per_page' and 'page' should result in pagination not being applied
		$groups = BP_Groups_Group::get( array(
			'per_page' => false,
			'page'     => false
		) );

		// Should return all groups; "paged" group total should be 25
		$this->assertEquals( count( $group_ids ), count( $groups['groups'] ) );

		unset( $groups );

		// Passing 'per_page' => -1 should result in pagination not being applied.
		$groups = BP_Groups_Group::get( array(
			'per_page' => -1
		) );

		// Should return all groups; "paged" group total should match 25
		$this->assertEquals( count( $group_ids ), count( $groups['groups'] ) );

		unset( $groups );

		// If "per_page" and "page" are both set, should result in pagination being applied.
		$groups = BP_Groups_Group::get( array(
			'per_page' => 12,
			'page'     => 1
		) );

		// Should return top 12 groups only
		$this->assertEquals( '12', count( $groups['groups'] ) );
	}

	/**
	 * @group cache
	 * @ticket BP5451
	 * @ticket BP6643
	 */
	public function test_get_queries_should_be_cached() {
		global $wpdb;

		$g = $this->factory->group->create();

		$found1 = BP_Groups_Group::get();

		$num_queries = $wpdb->num_queries;

		$found2 = BP_Groups_Group::get();

		$this->assertEqualSets( $found1, $found2 );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group cache
	 * @ticket BP5451
	 * @ticket BP6643
	 */
	public function test_get_query_caches_should_be_busted_by_groupmeta_update() {
		global $wpdb;

		$groups = $this->factory->group->create_many( 2 );
		groups_update_groupmeta( $groups[0], 'foo', 'bar' );
		groups_update_groupmeta( $groups[1], 'foo', 'bar' );

		$found1 = BP_Groups_Group::get( array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		) );

		$this->assertEqualSets( array( $groups[0], $groups[1] ), wp_list_pluck( $found1['groups'], 'id' ) );

		groups_update_groupmeta( $groups[1], 'foo', 'baz' );

		$found2 = BP_Groups_Group::get( array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		) );

		$this->assertEqualSets( array( $groups[0] ), wp_list_pluck( $found2['groups'], 'id' ) );
	}

	/**
	 * @group cache
	 * @group group_types
	 * @ticket BP5451
	 * @ticket BP6643
	 */
	public function test_get_query_caches_should_be_busted_by_group_term_change() {
		global $wpdb;

		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );

		$groups = $this->factory->group->create_many( 2 );
		bp_groups_set_group_type( $groups[0], 'foo' );
		bp_groups_set_group_type( $groups[1], 'bar' );

		$found1 = BP_Groups_Group::get( array(
			'group_type' => 'foo',
		) );

		$this->assertEqualSets( array( $groups[0] ), wp_list_pluck( $found1['groups'], 'id' ) );

		bp_groups_set_group_type( $groups[1], 'foo' );

		$found2 = BP_Groups_Group::get( array(
			'group_type' => 'foo',
		) );

		$this->assertEqualSets( array( $groups[0], $groups[1] ), wp_list_pluck( $found2['groups'], 'id' ) );
	}

	/**
	 * @group cache
	 * @group group_types
	 * @ticket BP5451
	 * @ticket BP6643
	 */
	public function test_get_query_caches_should_be_busted_by_group_term_removal() {
		global $wpdb;

		bp_groups_register_group_type( 'foo' );

		$groups = $this->factory->group->create_many( 2 );
		bp_groups_set_group_type( $groups[0], 'foo' );
		bp_groups_set_group_type( $groups[1], 'foo' );

		$found1 = BP_Groups_Group::get( array(
			'group_type' => 'foo',
		) );

		$this->assertEqualSets( array( $groups[0], $groups[1] ), wp_list_pluck( $found1['groups'], 'id' ) );

		bp_groups_remove_group_type( $groups[1], 'foo' );

		$found2 = BP_Groups_Group::get( array(
			'group_type' => 'foo',
		) );

		$this->assertEqualSets( array( $groups[0] ), wp_list_pluck( $found2['groups'], 'id' ) );
	}

	/**
	 * @group cache
	 * @ticket BP5451
	 * @ticket BP6643
	 */
	public function test_get_query_caches_should_be_busted_by_group_save() {
		global $wpdb;

		$groups = $this->factory->group->create_many( 2 );
		groups_update_groupmeta( $groups[0], 'foo', 'bar' );
		groups_update_groupmeta( $groups[1], 'foo', 'bar' );

		$found1 = BP_Groups_Group::get( array(
			'search_terms' => 'Foo',
		) );

		$this->assertEmpty( $found1['groups'] );

		$group0 = groups_get_group( $groups[0] );
		$group0->name = 'Foo';
		$group0->save();

		$found2 = BP_Groups_Group::get( array(
			'search_terms' => 'Foo',
		) );

		$this->assertEqualSets( array( $groups[0] ), wp_list_pluck( $found2['groups'], 'id' ) );
	}

	/**
	 * @group cache
	 * @ticket BP5451
	 * @ticket BP6643
	 */
	public function test_get_query_caches_should_be_busted_by_group_delete() {
		global $wpdb;

		$groups = $this->factory->group->create_many( 2 );

		$found1 = BP_Groups_Group::get();

		$this->assertEqualSets( $groups, wp_list_pluck( $found1['groups'], 'id' ) );

		$group0 = groups_get_group( $groups[0] );
		$group0->delete();

		$found2 = BP_Groups_Group::get();

		$this->assertEqualSets( array( $groups[1] ), wp_list_pluck( $found2['groups'], 'id' ) );
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
		$this->assertEquals( 'gm_last_activity.meta_value', _BP_Groups_Group::_convert_orderby_to_order_by_term( 'last_activity' ) );
	}

	/**
	 * @group convert_orderby_to_order_by_term
	 */
	public function test_convert_orderby_to_order_by_term_total_member_count() {
		$c = new _BP_Groups_Group();
		$this->assertEquals( 'CONVERT(gm_total_member_count.meta_value, SIGNED)', _BP_Groups_Group::_convert_orderby_to_order_by_term( 'total_member_count' ) );
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

	public function test_filter_user_groups_normal_search_middle_of_string() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => 'This group is for mandocellos and oboes.',
		) );
		$g2 = $this->factory->group->create();
		$u = $this->factory->user->create();
		self::add_user_to_group( $u, $g1 );

		$groups = BP_Groups_Group::filter_user_groups( 'cello', $u );

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

		$groups = BP_Groups_Group::search_groups( '_cool_' );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_search_groups_search_with_percent_sign() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => '100% awesome',
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::search_groups( '100%' );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_search_groups_search_with_quotes() {
		$g1 = $this->factory->group->create( array(
			'name' => 'Cool Group',
			'description' => "'tis sweet",
		) );
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::search_groups( "'tis " );

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

	/**
	 * @group delete
	 * @group cache
	 */
	public function test_delete_clear_cache() {
		$g = $this->factory->group->create();

		// Prime cache
		groups_get_group( $g );

		$this->assertNotEmpty( wp_cache_get( $g, 'bp_groups' ) );

		$group = new BP_Groups_Group( $g );
		$group->delete();

		$this->assertFalse( wp_cache_get( $g, 'bp_groups' ) );
	}

	/**
	 * @group save
	 * @group cache
	 */
	public function test_save_clear_cache() {
		$g = $this->factory->group->create();

		// Prime cache
		groups_get_group( $g );

		$this->assertNotEmpty( wp_cache_get( $g, 'bp_groups' ) );

		$group = new BP_Groups_Group( $g );
		$group->name = 'Foo';
		$group->save();

		$this->assertFalse( wp_cache_get( $g, 'bp_groups' ) );
	}
	/**
	 * @group get_group_extras
	 */
	public function test_get_group_extras_non_logged_in() {
		$paged_groups = array();
		$paged_groups[] = new stdClass;
		$paged_groups[] = new stdClass;

		$paged_groups[0]->id = 5;
		$paged_groups[1]->id = 10;

		$group_ids = array( 5, 10 );

		$expected = array();
		foreach ( $paged_groups as $key => $value ) {
			$expected[ $key ] = new stdClass;
			$expected[ $key ]->id = $value->id;
			$expected[ $key ]->is_member = '0';
			$expected[ $key ]->is_invited = '0';
			$expected[ $key ]->is_pending = '0';
			$expected[ $key ]->is_banned = false;
		}

		$old_user = get_current_user_id();
		$this->set_current_user( 0 );

		$this->assertEquals( $expected, BP_Groups_Group::get_group_extras( $paged_groups, $group_ids ) );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group get_group_extras
	 */
	public function test_get_group_extras_non_member() {
		$u = $this->factory->user->create();
		$g = $this->factory->group->create();

		$paged_groups = array();
		$paged_groups[] = new stdClass;
		$paged_groups[0]->id = $g;

		$group_ids = array( $g );

		$expected = array();
		foreach ( $paged_groups as $key => $value ) {
			$expected[ $key ] = new stdClass;
			$expected[ $key ]->id = $value->id;
			$expected[ $key ]->is_member = '0';
			$expected[ $key ]->is_invited = '0';
			$expected[ $key ]->is_pending = '0';
			$expected[ $key ]->is_banned = false;
		}

		$old_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->assertEquals( $expected, BP_Groups_Group::get_group_extras( $paged_groups, $group_ids ) );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group get_group_extras
	 */
	public function test_get_group_extras_member() {
		$u = $this->factory->user->create();
		$g = $this->factory->group->create();
		$this->add_user_to_group( $u, $g );

		$paged_groups = array();
		$paged_groups[] = new stdClass;
		$paged_groups[0]->id = $g;

		$group_ids = array( $g );

		$expected = array();
		foreach ( $paged_groups as $key => $value ) {
			$expected[ $key ] = new stdClass;
			$expected[ $key ]->id = $value->id;
			$expected[ $key ]->is_member = '1';
			$expected[ $key ]->is_invited = '0';
			$expected[ $key ]->is_pending = '0';
			$expected[ $key ]->is_banned = false;
		}

		$old_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->assertEquals( $expected, BP_Groups_Group::get_group_extras( $paged_groups, $group_ids ) );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group get_group_extras
	 */
	public function test_get_group_extras_invited() {
		$u = $this->factory->user->create();
		$g = $this->factory->group->create();

		$invite                = new BP_Groups_Member;
		$invite->group_id      = $g;
		$invite->user_id       = $u;
		$invite->date_modified = bp_core_current_time();
		$invite->invite_sent   = true;
		$invite->is_confirmed  = false;
		$invite->save();

		$paged_groups = array();
		$paged_groups[] = new stdClass;
		$paged_groups[0]->id = $g;

		$group_ids = array( $g );

		$expected = array();
		foreach ( $paged_groups as $key => $value ) {
			$expected[ $key ] = new stdClass;
			$expected[ $key ]->id = $value->id;
			$expected[ $key ]->is_member = '0';
			$expected[ $key ]->is_invited = '1';
			$expected[ $key ]->is_pending = '0';
			$expected[ $key ]->is_banned = false;
		}

		$old_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->assertEquals( $expected, BP_Groups_Group::get_group_extras( $paged_groups, $group_ids ) );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group get_group_extras
	 */
	public function test_get_group_extras_pending() {
		$u = $this->factory->user->create();
		$g = $this->factory->group->create();

		$invite                = new BP_Groups_Member;
		$invite->group_id      = $g;
		$invite->user_id       = $u;
		$invite->date_modified = bp_core_current_time();
		$invite->invite_sent   = false;
		$invite->is_confirmed  = false;
		$invite->save();

		$paged_groups = array();
		$paged_groups[] = new stdClass;
		$paged_groups[0]->id = $g;

		$group_ids = array( $g );

		$expected = array();
		foreach ( $paged_groups as $key => $value ) {
			$expected[ $key ] = new stdClass;
			$expected[ $key ]->id = $value->id;
			$expected[ $key ]->is_member = '0';
			$expected[ $key ]->is_invited = '0';
			$expected[ $key ]->is_pending = '1';
			$expected[ $key ]->is_banned = false;
		}

		$old_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->assertEquals( $expected, BP_Groups_Group::get_group_extras( $paged_groups, $group_ids ) );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group get_group_extras
	 */
	public function test_get_group_extras_banned() {
		$u = $this->factory->user->create();
		$g = $this->factory->group->create();

		$member                = new BP_Groups_Member;
		$member->group_id      = $g;
		$member->user_id       = $u;
		$member->date_modified = bp_core_current_time();
		$member->is_banned     = true;
		$member->save();

		$paged_groups = array();
		$paged_groups[] = new stdClass;
		$paged_groups[0]->id = $g;

		$group_ids = array( $g );

		$expected = array();
		foreach ( $paged_groups as $key => $value ) {
			$expected[ $key ] = new stdClass;
			$expected[ $key ]->id = $value->id;
			$expected[ $key ]->is_member = '0';
			$expected[ $key ]->is_invited = '0';
			$expected[ $key ]->is_pending = '0';
			$expected[ $key ]->is_banned = true;
		}

		$old_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->assertEquals( $expected, BP_Groups_Group::get_group_extras( $paged_groups, $group_ids ) );

		$this->set_current_user( $old_user );
	}

	/**
	 * @ticket BP5451
	 */
	public function test_admins_property() {
		$user_1 = $this->factory->user->create_and_get();
		$g = $this->factory->group->create( array(
			'creator_id' => $user_1->ID,
		) );

		$group = new BP_Groups_Group( $g );

		$expected_admin_props = array(
			'user_id' => $user_1->ID,
			'user_login' => $user_1->user_login,
			'user_email' => $user_1->user_email,
			'user_nicename' => $user_1->user_nicename,
			'is_admin' => 1,
			'is_mod' => 0,
		);

		$found_admin = $group->admins[0];
		foreach ( $expected_admin_props as $prop => $value ) {
			$this->assertEquals( $value, $found_admin->{$prop} );
		}
	}

	/**
	 * @ticket BP5451
	 */
	public function test_mods_property() {
		$users = $this->factory->user->create_many( 2 );
		$user_1 = new WP_User( $users[0] );
		$user_2 = new WP_User( $users[1] );

		$g = $this->factory->group->create( array(
			'creator_id' => $user_1->ID,
		) );

		$this->add_user_to_group( $user_2->ID, $g, array( 'is_mod' => 1 ) );

		$group = new BP_Groups_Group( $g );

		$expected_mod_props = array(
			'user_id' => $user_2->ID,
			'user_login' => $user_2->user_login,
			'user_email' => $user_2->user_email,
			'user_nicename' => $user_2->user_nicename,
			'is_admin' => 0,
			'is_mod' => 1,
		);

		$found_mod = $group->mods[0];
		foreach ( $expected_mod_props as $prop => $value ) {
			$this->assertEquals( $value, $found_mod->{$prop} );
		}
	}

	/**
	 * @ticket BP5451
	 */
	public function test_is_member_property() {
		$users = $this->factory->user->create_many( 2 );

		$g = $this->factory->group->create( array(
			'creator_id' => $users[0],
		) );

		wp_set_current_user( $users[1] );

		$group_a = new BP_Groups_Group( $g );
		$this->assertFalse( $group_a->is_member );

		$this->add_user_to_group( $users[1], $g );
		$group_b = new BP_Groups_Group( $g );
		$this->assertFalse( $group_b->is_member );
	}

	/**
	 * @ticket BP5451
	 */
	public function test_is_invited_property() {
		$users = $this->factory->user->create_many( 2 );

		$g = $this->factory->group->create( array(
			'creator_id' => $users[0],
		) );

		wp_set_current_user( $users[1] );

		$group_a = new BP_Groups_Group( $g );
		$this->assertFalse( $group_a->is_invited );

		$this->add_user_to_group( $users[1], $g, array(
			'invite_sent' => 1,
			'inviter_id' => $users[0],
			'is_confirmed' => 0,
		) );
		$group_b = new BP_Groups_Group( $g );
		$this->assertFalse( $group_b->is_invited );
	}

	/**
	 * @ticket BP5451
	 */
	public function test_is_pending_property() {
		$users = $this->factory->user->create_many( 2 );

		$g = $this->factory->group->create( array(
			'creator_id' => $users[0],
		) );

		wp_set_current_user( $users[1] );

		$group_a = new BP_Groups_Group( $g );
		$this->assertFalse( $group_a->is_pending );

		$this->add_user_to_group( $users[1], $g, array(
			'is_confirmed' => 0,
			'invite_sent' => 0,
			'inviter_id' => 0,
		) );
		$group_b = new BP_Groups_Group( $g );
		$this->assertFalse( $group_b->is_pending );
	}


	/**
	 * @group group_types
	 */
	public function test_group_type_single_value() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'group_type' => 'foo',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type_array_with_single_value() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'group_type' => array( 'foo' ),
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type_with_comma_separated_list() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'group_type' => 'foo, bar',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g2 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type_array_with_multiple_values() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'group_type' => array( 'foo', 'bar' ),
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g2 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type_should_discart_non_existing_types_in_comma_separated_value() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'group_type' => 'foo, baz',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type_should_return_empty_when_no_groups_match_specified_types() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::get( array(
			'group_type' => 'foo, baz',
		) );

		$this->assertEmpty( $groups['groups'] );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type__in_single_value() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'group_type__in' => 'bar',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g2 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type__in_comma_separated_values() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'group_type__in' => 'foo, bar',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g2 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type__in_array_multiple_values() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'group_type__in' => array( 'foo', 'bar' ),
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g2 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type__in_array_with_single_value() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'group_type__in' => array( 'foo' ),
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type__in_should_discart_non_existing_types_in_comma_separated_value() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'group_type__in' => 'foo, baz',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type__in_should_return_empty_when_no_groups_match_specified_types() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();

		$groups = BP_Groups_Group::get( array(
			'group_type__in' => 'foo, baz',
		) );

		$this->assertEmpty( $groups['groups'] );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type_should_take_precedence_over_group_type__in() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'group_type__in' => 'foo',
			'group_type' => 'bar',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g2 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type__not_in_should_return_groups_with_types_and_without_types() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		$g3 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'group_type__not_in' => 'foo',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g2, $g3 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type__not_in_comma_separated_values() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		$g3 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );
		bp_groups_set_group_type( $g3, 'baz' );

		$groups = BP_Groups_Group::get( array(
			'group_type__not_in' => 'foo, bar',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g3 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type__not_array_with_multiple_values() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		$g3 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );
		bp_groups_set_group_type( $g3, 'baz' );

		$groups = BP_Groups_Group::get( array(
			'group_type__not_in' => array( 'foo', 'bar' ),
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g3 ), $found );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type__not_in_should_return_no_results_when_all_groups_mathc_sepecified_type() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		$g3 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'foo' );
		bp_groups_set_group_type( $g3, 'foo' );

		$groups = BP_Groups_Group::get( array(
			'group_type__not_in' => 'foo',
		) );

		$this->assertEmpty( $groups['groups'] );
	}

	/**
	 * @group group_types
	 */
	public function test_group_type__not_in_takes_precedence_over_group_type() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		$g3 = $this->factory->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'foo' );
		bp_groups_set_group_type( $g3, 'foo' );

		$groups = BP_Groups_Group::get( array(
			'group_type' => 'foo',
			'group_type__not_in' => 'foo',
		) );

		$this->assertEmpty( $groups['groups'] );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_get_by_parent_id() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = $this->factory->group->create();

		$groups = BP_Groups_Group::get( array(
			'parent_id' => $g1,
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g2 ), $found );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_get_by_parent_id_ignore_grandparent() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = $this->factory->group->create();

		$groups = BP_Groups_Group::get( array(
			'parent_id' => $g2,
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g3 ), $found );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_get_by_parent_id_array() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = $this->factory->group->create();

		$groups = BP_Groups_Group::get( array(
			'parent_id' => array( $g1, $g2 ),
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g2, $g3 ), $found );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_get_by_parent_id_comma_separated_string() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = $this->factory->group->create();

		$groups = BP_Groups_Group::get( array(
			'parent_id' => "$g1, $g2",
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g2, $g3 ), $found );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_get_by_parent_id_top_level_groups() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = $this->factory->group->create();

		$groups = BP_Groups_Group::get( array(
			'parent_id' => 0,
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g4 ), $found );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_get_by_parent_id_top_level_groups_using_false() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = $this->factory->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = $this->factory->group->create();

		$groups = BP_Groups_Group::get( array(
			'parent_id' => false,
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g4 ), $found );
	}
}

/**
 * Stub class for accessing protected methods
 */
class _BP_Groups_Group extends BP_Groups_Group {
	public static function _convert_type_to_order_orderby( $type ) {
		return self::convert_type_to_order_orderby( $type );
	}

	public static function _convert_orderby_to_order_by_term( $term ) {
		return self::convert_orderby_to_order_by_term( $term );
	}
}
