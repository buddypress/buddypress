<?php

/**
 * @group groups
 * @group taxonomy
 * @group group_types
 * @group BP_Groups_Group
 */
class BP_Tests_BP_Groups_Group_Query_Group_Types extends BP_UnitTestCase {

	public function test_group_type_single_value_multisite() {
		$this->skipWithoutMultisite();

		$b1 = self::factory()->blog->create();
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		$t1 = self::factory()->term->create(
			array(
				'taxonomy' => 'post_tag',
				'slug'     => 'fooo',
				'name'     => 'Fooo',
			)
		);

		bp_groups_register_group_type( 'foo' );
		bp_set_object_terms( $g1, $t1, 'post_tag' );
		bp_set_object_terms( $g2, $t1, 'post_tag' );

		$callback = function( $site_id, $taxonomy ) use ( $b1 ) {
			if ( $taxonomy === bp_get_group_type_tax_name() ) {
				return $b1;
			}

			return $site_id;
		};

		add_filter( 'bp_get_taxonomy_term_site_id', $callback, 10, 2 );

		// Simulate the group type on a different blog.
		switch_to_blog( $b1 );
		bp_groups_set_group_type( $g1, 'foo' );
		restore_current_blog();

		/**
		 * The group type is actually being performed in a different
		 * database table.
		 */
		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type' => 'foo',
			'tax_query' => array(
				array(
					'taxonomy' => 'post_tag',
					'terms'    => array( 'fooo' ),
					'field'    => 'slug',
				),
			),
		) );

		$this->assertEquals( array( $g1 ), $groups['groups'] );

		remove_filter( 'bp_get_taxonomy_term_site_id', $callback );
	}

	public function test_group_type_single_value() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type' => 'foo',
		) );

		$this->assertEquals( array( $g1 ), $groups['groups'] );
	}

	public function test_group_type_array_with_single_value() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type' => 'foo',
		) );

		$this->assertEquals( array( $g1 ), $groups['groups'] );
	}

	public function test_group_type_with_comma_separated_list() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type' => 'foo, bar',
		) );

		$this->assertEqualSets( array( $g1, $g2 ), $groups['groups'] );
	}

	public function test_group_type_array_with_multiple_values() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type' => array( 'foo', 'bar' ),
		) );

		$this->assertEqualSets( array( $g1, $g2 ), $groups['groups'] );
	}

	public function test_group_type_should_discard_non_existing_types_in_comma_separated_value() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type' => 'foo, baz',
		) );

		$this->assertEquals( array( $g1 ), $groups['groups'] );
	}

	public function test_group_type_should_return_empty_when_no_groups_match_specified_types() {
		self::factory()->group->create();
		self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type' => 'foo, baz',
		) );

		$this->assertEmpty( $groups['groups'] );
	}

	public function test_group_type__in_single_value() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type__in' => 'bar',
		) );

		$this->assertEquals( array( $g2 ), $groups['groups'] );
	}

	public function test_group_type__in_comma_separated_values() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type__in' => 'foo, bar',
		) );

		$this->assertEqualSets( array( $g1, $g2 ), $groups['groups'] );
	}

	public function test_group_type__in_array_multiple_values() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type__in' => array( 'foo', 'bar' ),
		) );

		$this->assertEqualSets( array( $g1, $g2 ), $groups['groups'] );
	}

	public function test_group_type__in_array_with_single_value() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type__in' => array( 'foo' ),
		) );

		$this->assertEquals( array( $g1 ), $groups['groups'] );
	}

	public function test_group_type__in_should_discard_non_existing_types_in_comma_separated_value() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type__in' => 'foo, baz',
		) );

		$this->assertEquals( array( $g1 ), $groups['groups'] );
	}

	public function test_group_type__in_should_return_empty_when_no_groups_match_specified_types() {
		self::factory()->group->create();
		self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type__in' => 'foo, baz',
		) );

		$this->assertEmpty( $groups['groups'] );
	}

	public function test_group_type_should_take_precedence_over_group_type__in() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
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

	public function test_group_type__not_in_should_return_groups_with_types_and_without_types() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type__not_in' => 'foo',
		) );

		$this->assertEquals( array( $g2, $g3 ), $groups['groups'] );
	}

	public function test_group_type__not_in_comma_separated_values() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );
		bp_groups_set_group_type( $g3, 'baz' );

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type__not_in' => 'foo, bar',
		) );

		$this->assertEquals( array( $g3 ), $groups['groups'] );
	}

	public function test_group_type__not_array_with_multiple_values() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'bar' );
		bp_groups_set_group_type( $g3, 'baz' );

		$groups = BP_Groups_Group::get(
			array(
				'fields' => 'ids',
				'group_type__not_in' => array( 'foo', 'bar' ),
			)
		);

		$this->assertEquals( array( $g3 ), $groups['groups'] );
	}

	public function test_group_type__not_in_should_return_no_results_when_all_groups_match_specified_type() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create();
		bp_groups_register_group_type( 'foo' );
		bp_groups_set_group_type( $g1, 'foo' );
		bp_groups_set_group_type( $g2, 'foo' );
		bp_groups_set_group_type( $g3, 'foo' );

		$groups = BP_Groups_Group::get( array(
			'group_type__not_in' => 'foo',
		) );

		$this->assertEmpty( $groups['groups'] );
	}

	public function test_group_type__not_in_takes_precedence_over_group_type() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create();
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
	 * @group cache
	 * @ticket BP5451
	 * @ticket BP6643
	 */
	public function test_get_query_caches_should_be_busted_by_group_term_change() {
		bp_groups_register_group_type( 'foo' );
		bp_groups_register_group_type( 'bar' );

		$groups = self::factory()->group->create_many( 2 );
		bp_groups_set_group_type( $groups[0], 'foo' );
		bp_groups_set_group_type( $groups[1], 'bar' );

		$found1 = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type' => 'foo',
		) );

		$this->assertEqualSets( array( $groups[0] ), $found1['groups'] );

		bp_groups_set_group_type( $groups[1], 'foo' );

		$found2 = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type' => 'foo',
		) );

		$this->assertEqualSets( array( $groups[0], $groups[1] ), $found2['groups'] );
	}

	/**
	 * @group cache
	 * @ticket BP5451
	 * @ticket BP6643
	 */
	public function test_get_query_caches_should_be_busted_by_group_term_removal() {
		bp_groups_register_group_type( 'foo' );

		$groups = self::factory()->group->create_many( 2 );
		bp_groups_set_group_type( $groups[0], 'foo' );
		bp_groups_set_group_type( $groups[1], 'foo' );

		$found1 = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type' => 'foo',
		) );

		$this->assertEqualSets( array( $groups[0], $groups[1] ), $found1['groups'] );

		bp_groups_remove_group_type( $groups[1], 'foo' );

		$found2 = BP_Groups_Group::get( array(
			'fields' => 'ids',
			'group_type' => 'foo',
		) );

		$this->assertEqualSets( array( $groups[0] ), $found2['groups'] );
	}

	public function test_group_type_with_other_with_tax_query_with_group_type() {
		bp_groups_register_group_type( 'foo' );

		$t1 = self::factory()->term->create(
			array(
				'taxonomy' => 'post_tag',
				'slug'     => 'fooo',
				'name'     => 'Fooo',
			)
		);

		$t2 = self::factory()->term->create(
			array(
				'taxonomy' => 'category',
				'slug'     => 'barr',
				'name'     => 'Barr',
			)
		);

		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create();

		bp_set_object_terms( $g1, $t1, 'post_tag' );
		bp_set_object_terms( $g3, $t2, 'category' );
		bp_groups_set_group_type( $g3, 'foo' );

		$groups = BP_Groups_Group::get(
			array(
				'fields' => 'ids',
				'group_type' => 'foo',
				'tax_query' => array(
					'relation' => 'OR',
					array(
						'taxonomy' => 'post_tag',
						'terms'    => array( 'fooo' ),
						'field'    => 'slug',
					),
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'barr' ),
						'field'    => 'slug',
					),
				),
			)
		);

		$this->assertEqualSets( array( $g3 ), $groups['groups'] );
	}

	public function test_group_type_with_other_with_tax_query_with_group_type__in() {
		bp_groups_register_group_type( 'foo' );

		$t1 = self::factory()->term->create(
			array(
				'taxonomy' => 'post_tag',
				'slug'     => 'fooo',
				'name'     => 'Fooo',
			)
		);

		$t2 = self::factory()->term->create(
			array(
				'taxonomy' => 'category',
				'slug'     => 'barr',
				'name'     => 'Barr',
			)
		);

		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create();

		bp_set_object_terms( $g1, $t1, 'post_tag' );
		bp_set_object_terms( $g3, $t2, 'category' );
		bp_groups_set_group_type( $g3, 'foo' );

		$groups = BP_Groups_Group::get(
			array(
				'fields' => 'ids',
				'group_type__in' => 'foo',
				'tax_query' => array(
					'relation' => 'OR',
					array(
						'taxonomy' => 'post_tag',
						'terms'    => array( 'fooo' ),
						'field'    => 'slug',
					),
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'barr' ),
						'field'    => 'slug',
					),
				),
			)
		);

		$this->assertEqualSets( array( $g3 ), $groups['groups'] );
	}

	public function test_group_type_with_other_with_tax_query_with_group_type__not_in() {
		bp_groups_register_group_type( 'foo' );

		$t1 = self::factory()->term->create(
			array(
				'taxonomy' => 'post_tag',
				'slug'     => 'fooo',
				'name'     => 'Fooo',
			)
		);

		$t2 = self::factory()->term->create(
			array(
				'taxonomy' => 'category',
				'slug'     => 'barr',
				'name'     => 'Barr',
			)
		);

		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create();

		bp_set_object_terms( $g1, $t1, 'post_tag' );
		bp_set_object_terms( $g3, $t2, 'category' );
		bp_groups_set_group_type( $g3, 'foo' );

		$groups = BP_Groups_Group::get(
			array(
				'fields' => 'ids',
				'group_type__not_in' => 'foo',
				'tax_query' => array(
					'relation' => 'OR',
					array(
						'taxonomy' => 'post_tag',
						'terms'    => array( 'fooo' ),
						'field'    => 'slug',
					),
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'barr' ),
						'field'    => 'slug',
					),
				),
			)
		);

		$this->assertEqualSets( array( $g1 ), $groups['groups'] );
	}
}
