<?php

/**
 * @group groups
 * @group query
 * @group taxonomy
 * @group BP_Groups_Group
 */
#[AllowDynamicProperties]
class BP_Tests_BP_Groups_Group_Query_TaxQuery extends BP_UnitTestCase {

	/**
	 * Test support for tax_query to the BP_Groups_Group getter.
	 *
	 * @param array $group_args The tax query arguments.
	 * @param array $term_args The term arguments.
	 *
	 * @dataProvider provider_groups_tax_query
	 */
	public function test_group_tax_query( $group_args, $term_args = array() ) {
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
		$g2   = self::factory()->group->create();

		bp_set_object_terms( $g1, $term, $taxonomy );

		$groups = BP_Groups_Group::get(
			array(
				'fields'    => 'ids',
				'tax_query' => $group_args,
			)
		);

		$this->assertEquals( array( $g1 ), $groups['groups'] );
	}

	public function provider_groups_tax_query() {
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

		$this->assertSame( array( $g1 ), $groups['groups'] );
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

		$this->assertSame( array( $g1 ), $groups['groups'] );
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
		$g2 =self::factory()->group->create();

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

		$this->assertSame( array( $g2 ), $groups['groups'] );
	}
}
