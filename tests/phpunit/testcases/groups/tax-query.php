<?php

/**
 * @group groups
 * @group query
 * @group taxonomy
 * @group BP_Groups_Group
 */
class BP_Tests_BP_Groups_Group_Query_TaxQuery extends BP_UnitTestCase {

	/**
	 * Test support for tax_query to the BP_Groups_Group getter.
	 *
	 * @param array $group_args The tax query arguments.
	 * @param array $term_args The term arguments.
	 *
	 * @dataProvider provider_for_tax_query_single_query
	 */
	public function test_for_tax_query_single_query( $group_args, $term_args = array() ) {
		$taxonomy = 'category';

		if ( empty( $term_args ) ) {
			$term_args = array(
				'taxonomy' => $taxonomy,
				'slug'     => 'foo',
				'name'     => 'Foo',
			);
		}

		$term = self::factory()->term->create( $term_args );
		$g1   = self::factory()->group->create();
		self::factory()->group->create();

		bp_set_object_terms( $g1, $term, $taxonomy );

		$groups = BP_Groups_Group::get(
			array(
				'fields'    => 'ids',
				'tax_query' => $group_args,
			)
		);

		$this->assertEquals( array( $g1 ), $groups['groups'] );
	}

	/**
	 * Data provider for the test_for_tax_query_single_query() test.
	 *
	 * @return array[]
	 */
	public function provider_for_tax_query_single_query() {
		return array(
			'single_query_single_term_field_slug' => array(
				array(
					array(
						'taxonomy' => 'category',
						'field'    => 'slug',
						'terms'    => 'foo',
					),
				),
			),
			'single_query_single_term_field_name' => array(
				array(
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'Foo' ),
						'field'    => 'name',
					),
				),
			),
			'single_query_field_name_with_spaces' => array(
				array(
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'Foo Bar' ),
						'field'    => 'name',
					),
				),
				array(
					'taxonomy' => 'category',
					'slug'     => 'foo',
					'name'     => 'Foo Bar',
				)
			),
			'single_query_single_term_operator_in' => array(
				array(
					array(
						'taxonomy' => 'category',
						'field'    => 'slug',
						'terms'    => 'foo',
						'operator' => 'IN',
					),
				),
			),
			'single_query_single_term_operator_and' => array(
				array(
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'foo' ),
						'field'    => 'slug',
						'operator' => 'AND',
					),
				),
			),
		);
	}

	public function test_tax_query_single_query_single_term_field_term_taxonomy_id() {
		$taxonomy = 'category';
		$term     = self::factory()->term->create(
			array(
				'taxonomy' => $taxonomy,
				'slug'     => 'foo',
				'name'     => 'Foo',
			)
		);

		$g1 = self::factory()->group->create();
		self::factory()->group->create();

		$tt_ids = bp_set_object_terms( $g1, $term, $taxonomy );

		$groups = BP_Groups_Group::get(
			array(
				'fields'    => 'ids',
				'tax_query' => array(
					array(
						'taxonomy' => $taxonomy,
						'terms'    => $tt_ids,
						'field'    => 'term_taxonomy_id',
					),
				),
			)
		);

		$this->assertEqualSets( array( $g1 ), $groups['groups'] );
	}

	public function test_tax_query_single_query_single_term_field_term_id() {
		$taxonomy = 'category';
		$term     = self::factory()->term->create(
			array(
				'taxonomy' => $taxonomy,
				'slug'     => 'foo',
				'name'     => 'Foo',
			)
		);

		$g1 = self::factory()->group->create();
		self::factory()->group->create();

		bp_set_object_terms( $g1, $term, $taxonomy );

		$groups = BP_Groups_Group::get(
			array(
				'fields'    => 'ids',
				'tax_query' => array(
					array(
						'taxonomy' => $taxonomy,
						'terms'    => array( $term ),
						'field'    => 'term_id',
					),
				),
			)
		);

		$this->assertEqualSets( array( $g1 ), $groups['groups'] );
	}

	public function test_tax_query_single_query_single_term_operator_not_in() {
		$taxonomy = 'category';
		$term     = self::factory()->term->create(
			array(
				'taxonomy' => $taxonomy,
				'slug'     => 'foo',
				'name'     => 'Foo',
			)
		);

		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();

		bp_set_object_terms( $g1, $term, $taxonomy );

		$groups = BP_Groups_Group::get(
			array(
				'fields'    => 'ids',
				'tax_query' => array(
					array(
						'taxonomy' => $taxonomy,
						'terms'    => array( 'foo' ),
						'field'    => 'slug',
						'operator' => 'NOT IN',
					),
				),
			)
		);

		$this->assertEqualSets( array( $g2 ), $groups['groups'] );
	}

	/**
	 * Test support for tax_query to the BP_Groups_Group getter.
	 *
	 * @param array $group_args The tax query arguments.
	 * @param array $term_args The term arguments.
	 *
	 * @dataProvider provider_for_tax_query_single_query_multiples_terms
	 */
	public function test_for_tax_query_single_query_multiples_terms( $group_args, $term_args = array() ) {
		$taxonomy = 'category';

		$t1 = self::factory()->term->create(
			wp_parse_args(
				$term_args[0] ?? array(),
				array(
					'taxonomy' => $taxonomy,
					'slug'     => 'foo',
					'name'     => 'Foo'
				)
			)
		);

		$t2 = self::factory()->term->create(
			wp_parse_args(
				$term_args[1] ?? array(),
				array(
					'taxonomy' => $taxonomy,
					'slug'     => 'bar',
					'name'     => 'Bar',
				)
			)
		);

		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create();

		bp_set_object_terms( $g1, $t1, $taxonomy );

		if ( str_contains( $group_args[0]['operator'], 'AND' ) ) {
			bp_set_object_terms( $g2, array( $t1, $t2 ), $taxonomy );
		} else {
			bp_set_object_terms( $g2, $t2, $taxonomy );
		}

		$groups = BP_Groups_Group::get(
			array(
				'fields'    => 'ids',
				'tax_query' => $group_args,
			)
		);

		if ( str_contains( $group_args[0]['operator'], 'NOT IN' ) ) {
			$this->assertSameSets( array( $g3 ), $groups['groups'] );
		} elseif ( str_contains( $group_args[0]['operator'], 'AND' ) ) {
			$this->assertSameSets( array( $g2 ), $groups['groups'] );
		} else {
			$this->assertSameSets( array( $g1, $g2 ), $groups['groups'] );
		}
	}

	/**
	 * Data provider for the test_for_tax_query_single_query_multiples_terms() test.
	 *
	 * @return array[]
	 */
	public function provider_for_tax_query_single_query_multiples_terms() {
		return array(
			'single_query_multiples_terms_operator_in' => array(
				array(
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'foo', 'bar' ),
						'field'    => 'slug',
						'operator' => 'IN',
					),
				),
			),
			'single_query_multiples_terms_operator_not_in' => array(
				array(
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'foo', 'bar' ),
						'field'    => 'slug',
						'operator' => 'NOT IN',
					),
				),
			),
			'single_query_multiples_queries_operator_not_in' => array(
				array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'foo' ),
						'field'    => 'slug',
						'operator' => 'NOT IN',
					),
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'bar' ),
						'field'    => 'slug',
						'operator' => 'NOT IN',
					),
				),
			),
			'single_query_multiples_terms_operator_and' => array(
				array(
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'foo', 'bar' ),
						'field'    => 'slug',
						'operator' => 'AND',
					),
				),
			),
		);
	}

	public function test_tax_query_single_multiple_terms_operator_not_exists() {
		register_taxonomy( 'wptests_tax1', 'group' );
		register_taxonomy( 'wptests_tax2', 'group' );

		$t1 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax1' ) );
		$t2 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax2' ) );

		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create();

		bp_set_object_terms( $g1, $t1, 'wptests_tax1' );
		bp_set_object_terms( $g2, $t2, 'wptests_tax2' );

		$groups = BP_Groups_Group::get(
			array(
				'fields'    => 'ids',
				'tax_query' => array(
					array(
						'taxonomy' => 'wptests_tax2',
						'operator' => 'NOT EXISTS',
					),
				),
			)
		);

		$this->assertSameSets( array( $g1, $g3 ), $groups['groups'] );
	}

	public function test_tax_query_single_multiple_terms_operator_exists() {
		register_taxonomy( 'wptests_tax1', 'group' );
		register_taxonomy( 'wptests_tax2', 'group' );

		$t1 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax1' ) );
		$t2 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax2' ) );

		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		self::factory()->group->create();

		bp_set_object_terms( $g1, $t1, 'wptests_tax1' );
		bp_set_object_terms( $g2, $t2, 'wptests_tax2' );

		$groups = BP_Groups_Group::get(
			array(
				'fields'    => 'ids',
				'tax_query' => array(
					array(
						'taxonomy' => 'wptests_tax2',
						'operator' => 'EXISTS',
					),
				),
			)
		);

		$this->assertSameSets( array( $g2 ), $groups['groups'] );
	}

	public function test_tax_query_single_multiple_terms_operator_exists_should_ignore_terms() {
		register_taxonomy( 'wptests_tax1', 'group' );
		register_taxonomy( 'wptests_tax2', 'group' );

		$t1 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax1' ) );
		$t2 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax2' ) );

		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		self::factory()->group->create();

		bp_set_object_terms( $g1, $t1, 'wptests_tax1' );
		bp_set_object_terms( $g2, $t2, 'wptests_tax2' );

		$groups = BP_Groups_Group::get(
			array(
				'fields'    => 'ids',
				'tax_query' => array(
					array(
						'taxonomy' => 'wptests_tax2',
						'operator' => 'EXISTS',
						'terms'    => array( 'foo', 'bar' ),
					),
				),
			)
		);

		$this->assertSameSets( array( $g2 ), $groups['groups'] );
	}

	public function test_tax_query_single_multiple_terms_operator_exists_with_no_taxonomy() {
		register_taxonomy( 'wptests_tax1', 'group' );
		register_taxonomy( 'wptests_tax2', 'group' );

		$t1 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax1' ) );
		$t2 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax2' ) );

		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		self::factory()->group->create();

		bp_set_object_terms( $g1, $t1, 'wptests_tax1' );
		bp_set_object_terms( $g2, $t2, 'wptests_tax2' );

		$groups = BP_Groups_Group::get(
			array(
				'fields'    => 'ids',
				'tax_query' => array(
					array(
						'operator' => 'EXISTS',
					),
				),
			)
		);

		$this->assertEmpty( $groups['groups'] );
	}

	public function test_tax_query_single_multiple_terms_operator_not_exists_combined() {
		register_taxonomy( 'wptests_tax1', 'group' );
		register_taxonomy( 'wptests_tax2', 'group' );

		$t1 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax1' ) );
		$t2 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax1' ) );
		$t3 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax2' ) );

		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create();
		$g4 = self::factory()->group->create();

		bp_set_object_terms( $g1, $t1, 'wptests_tax1' );
		bp_set_object_terms( $g2, $t2, 'wptests_tax1' );
		bp_set_object_terms( $g3, $t3, 'wptests_tax2' );

		$groups = BP_Groups_Group::get(
			array(
				'fields'    => 'ids',
				'tax_query' => array(
					'relation' => 'OR',
					array(
						'taxonomy' => 'wptests_tax1',
						'operator' => 'NOT EXISTS',
					),
					array(
						'taxonomy' => 'wptests_tax1',
						'field'    => 'slug',
						'terms'    => get_term_field( 'slug', $t1 ),
					),
				),
			)
		);

		$this->assertSameSets( array( $g1, $g3, $g4 ), $groups['groups'] );
	}

	public function test_tax_query_single_multiple_terms_relation_and() {
		$taxonomy = 'category';

		$t1 = self::factory()->term->create(
			array(
				'taxonomy' => $taxonomy,
				'slug'     => 'foo',
				'name'     => 'Foo',
			)
		);

		$t2 = self::factory()->term->create(
			array(
				'taxonomy' => $taxonomy,
				'slug'     => 'bar',
				'name'     => 'Bar',
			)
		);

		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();

		self::factory()->group->create();

		bp_set_object_terms( $g1, $g1, $taxonomy );
		bp_set_object_terms( $g2, array( $t1, $t2 ), $taxonomy );

		$groups = BP_Groups_Group::get(
			array(
				'fields'    => 'ids',
				'tax_query' => array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'foo' ),
						'field'    => 'slug',
					),
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'bar' ),
						'field'    => 'slug',
					),
				),
			)
		);

		$this->assertEqualSets( array( $g2 ), $groups['groups'] );

	}

	public function test_tax_query_single_multiple_terms_relation_or() {
		$taxonomy = 'category';

		$t1 = self::factory()->term->create(
			array(
				'taxonomy' => $taxonomy,
				'slug'     => 'foo',
				'name'     => 'Foo',
			)
		);

		$t2 = self::factory()->term->create(
			array(
				'taxonomy' => $taxonomy,
				'slug'     => 'bar',
				'name'     => 'Bar',
			)
		);

		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();

		self::factory()->group->create();

		bp_set_object_terms( $g1, $t1, $taxonomy );
		bp_set_object_terms( $g2, array( $t1, $t2 ), $taxonomy );

		$groups = BP_Groups_Group::get(
			array(
				'fields'    => 'ids',
				'tax_query'              => array(
					'relation' => 'OR',
					array(
						'taxonomy' => $taxonomy,
						'terms'    => array( 'foo' ),
						'field'    => 'slug',
					),
					array(
						'taxonomy' => $taxonomy,
						'terms'    => array( 'bar' ),
						'field'    => 'slug',
					),
				),
			)
		);

		$this->assertEqualSets( array( $g1, $g2 ), $groups['groups'] );
	}

	public function test_tax_query_single_multiple_terms_different_taxonomies() {
		$t1 = self::factory()->term->create(
			array(
				'taxonomy' => 'post_tag',
				'slug'     => 'foo',
				'name'     => 'Foo',
			)
		);

		$t2 = self::factory()->term->create(
			array(
				'taxonomy' => 'category',
				'slug'     => 'bar',
				'name'     => 'Bar',
			)
		);

		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();

		self::factory()->group->create();

		bp_set_object_terms( $g1, $t1, 'post_tag' );
		bp_set_object_terms( $g2, $t2, 'category' );

		$groups = BP_Groups_Group::get(
			array(
				'fields' => 'ids',
				'tax_query' => array(
					'relation' => 'OR',
					array(
						'taxonomy' => 'post_tag',
						'terms'    => array( 'foo' ),
						'field'    => 'slug',
					),
					array(
						'taxonomy' => 'category',
						'terms'    => array( 'bar' ),
						'field'    => 'slug',
					),
				),
			)
		);

		$this->assertEqualSets( array( $g1, $g2 ), $groups['groups'] );
	}
}
