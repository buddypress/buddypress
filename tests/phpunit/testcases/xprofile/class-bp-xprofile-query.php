<?php

/**
 * @group xprofile
 * @group BP_XProfile_Query
 */
class BP_Tests_BP_XProfile_Query extends BP_UnitTestCase {
	protected $group;
	protected $fields = array();
	protected $users = array();

	public function tearDown() {
		parent::tearDown();
		$this->group = '';
		$this->fields = array();
		$this->users = array();
	}

	public function test_no_field() {
		$this->create_fields( 2 );
		$this->create_users( 3 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'foo' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'bar' );
		xprofile_set_field_data( $this->fields[1], $this->users[2], 'foo');

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'value' => 'foo',
				),
			),
		) );

		$expected = array( $this->users[0], $this->users[2] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_no_value() {
		$this->create_fields( 2 );
		$this->create_users( 3 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'foo' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'bar' );
		xprofile_set_field_data( $this->fields[1], $this->users[2], 'foo');

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[1],
				),
			),
		) );

		$expected = array( $this->users[2] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_translate_field_name_to_field_id() {
		$this->create_fields( 0 );
		$f = $this->factory->xprofile_field->create( array(
			'field_group_id' => $this->group,
			'type' => 'textbox',
			'name' => 'Foo Field',
		) );
		$this->create_users( 2 );

		xprofile_set_field_data( $f, $this->users[0], 'foo' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => 'Foo Field',
				),
			),
		) );

		$expected = array( $this->users[0] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_single_clause_compare_default() {
		$this->create_fields( 2 );
		$this->create_users( 3 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'foo' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'bar' );
		xprofile_set_field_data( $this->fields[1], $this->users[2], 'foo');

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => 'foo',
				),
			),
		) );

		$expected = array( $this->users[0] );
		$this->assertEquals( $expected, array_keys( $q->results ) );
	}

	public function test_single_clause_compare_equals() {
		$this->create_fields( 2 );
		$this->create_users( 3 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'foo' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'bar' );
		xprofile_set_field_data( $this->fields[1], $this->users[2], 'foo');

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => 'foo',
					'compare' => '=',
				),
			),
		) );

		$expected = array( $this->users[0] );
		$this->assertEquals( $expected, array_keys( $q->results ) );
	}

	public function test_single_clause_compare_not_equals() {
		$this->create_fields( 1 );
		$this->create_users( 2 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'foo' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'bar' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => 'foo',
					'compare' => '!=',
				),
			),
		) );

		$expected = array( $this->users[1] );
		$this->assertEquals( $expected, array_keys( $q->results ) );
	}

	public function test_single_clause_compare_arithmetic_comparisons() {
		$this->create_fields( 1 );
		$this->create_users( 3 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], '1' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], '2' );
		xprofile_set_field_data( $this->fields[0], $this->users[2], '3' );

		// <
		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => 2,
					'compare' => '<',
				),
			),
		) );

		$expected = array( $this->users[0] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );

		// <=
		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => 2,
					'compare' => '<=',
				),
			),
		) );

		$expected = array( $this->users[0], $this->users[1] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );

		// >=
		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => 2,
					'compare' => '>=',
				),
			),
		) );

		$expected = array( $this->users[1], $this->users[2] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );

		// >
		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => 2,
					'compare' => '>',
				),
			),
		) );

		$expected = array( $this->users[2] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_single_clause_compare_like() {
		$this->create_fields( 1 );
		$this->create_users( 2 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'bar' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => 'ba',
					'compare' => 'LIKE',
				),
			),
		) );

		$expected = array( $this->users[0] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_single_clause_compare_not_like() {
		$this->create_fields( 1 );
		$this->create_users( 3 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'bar' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'rab' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => 'ba',
					'compare' => 'NOT LIKE',
				),
			),
		) );

		$expected = array( $this->users[1] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_single_clause_compare_between_not_between() {
		$this->create_fields( 1 );
		$this->create_users( 3 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], '1' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], '10' );
		xprofile_set_field_data( $this->fields[0], $this->users[2], '100' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => array( 9, 12 ),
					'compare' => 'BETWEEN',
					'type' => 'NUMERIC',
				),
			),
		) );

		$expected = array( $this->users[1] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => array( 9, 12 ),
					'compare' => 'NOT BETWEEN',
					'type' => 'NUMERIC',
				),
			),
		) );

		$expected = array( $this->users[0], $this->users[2] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_single_clause_compare_regexp_rlike() {
		$this->create_fields( 1 );
		$this->create_users( 3 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'bar' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'baz' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => 'z$',
					'compare' => 'REGEXP',
				),
			),
		) );

		$expected = array( $this->users[1] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );

		// RLIKE is a synonym for REGEXP.
		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => 'z$',
					'compare' => 'RLIKE',
				),
			),
		) );

		$expected = array( $this->users[1] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_single_clause_compare_not_regexp() {
		$this->create_fields( 1 );
		$this->create_users( 3 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'bar' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'baz' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => 'z$',
					'compare' => 'NOT REGEXP',
				),
			),
		) );

		$expected = array( $this->users[0] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_single_clause_compare_not_exists() {
		$this->create_fields( 2 );
		$this->create_users( 2 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'bar' );
		xprofile_set_field_data( $this->fields[1], $this->users[1], 'bar' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'compare' => 'NOT EXISTS',
				),
			),
		) );

		$expected = array( $this->users[1] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_relation_default_to_and() {
		$this->create_fields( 2 );
		$this->create_users( 4 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'foo' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'bar' );
		xprofile_set_field_data( $this->fields[1], $this->users[2], 'foo' );
		xprofile_set_field_data( $this->fields[1], $this->users[3], 'bar' );
		xprofile_set_field_data( $this->fields[0], $this->users[3], 'foo' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				array(
					'field' => $this->fields[0],
					'value' => 'foo',
				),
				array(
					'field' => $this->fields[1],
					'value' => 'bar',
				),
			),
		) );

		$expected = array( $this->users[3] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_relation_and() {
		$this->create_fields( 2 );
		$this->create_users( 4 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'foo' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'bar' );
		xprofile_set_field_data( $this->fields[1], $this->users[2], 'foo' );
		xprofile_set_field_data( $this->fields[1], $this->users[3], 'bar' );
		xprofile_set_field_data( $this->fields[0], $this->users[3], 'foo' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				'relation' => 'AND',
				array(
					'field' => $this->fields[0],
					'value' => 'foo',
				),
				array(
					'field' => $this->fields[1],
					'value' => 'bar',
				),
			),
		) );

		$expected = array( $this->users[3] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_relation_or() {
		$this->create_fields( 2 );
		$this->create_users( 4 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'foo' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'bar' );
		xprofile_set_field_data( $this->fields[1], $this->users[2], 'foo' );
		xprofile_set_field_data( $this->fields[1], $this->users[3], 'bar' );
		xprofile_set_field_data( $this->fields[0], $this->users[3], 'foo' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				'relation' => 'OR',
				array(
					'field' => $this->fields[0],
					'value' => 'foo',
				),
				array(
					'field' => $this->fields[1],
					'value' => 'bar',
				),
			),
		) );

		$expected = array( $this->users[0], $this->users[3] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_relation_and_with_compare_not_exists() {
		$this->create_fields( 2 );
		$this->create_users( 4 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'foo' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'bar' );
		xprofile_set_field_data( $this->fields[1], $this->users[2], 'foo' );
		xprofile_set_field_data( $this->fields[1], $this->users[3], 'bar' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				'relation' => 'AND',
				array(
					'field' => $this->fields[0],
					'compare' => 'NOT EXISTS',
				),
				array(
					'field' => $this->fields[1],
					'value' => 'bar',
				),
			),
		) );

		$expected = array( $this->users[3] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_relation_or_with_compare_not_exists() {
		$this->create_fields( 2 );
		$this->create_users( 4 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'foo' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'bar' );
		xprofile_set_field_data( $this->fields[1], $this->users[2], 'foo' );
		xprofile_set_field_data( $this->fields[1], $this->users[3], 'bar' );
		xprofile_set_field_data( $this->fields[0], $this->users[3], 'bar' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				'relation' => 'OR',
				array(
					'field' => $this->fields[0],
					'compare' => 'NOT EXISTS',
				),
				array(
					'field' => $this->fields[1],
					'value' => 'bar',
				),
			),
		) );

		$expected = array( $this->users[2], $this->users[3] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	/**
	 * Tests for table join logic.
	 */
	public function test_relation_or_compare_equals_and_like() {
		$this->create_fields( 2 );
		$this->create_users( 4 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'foo' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'bar' );
		xprofile_set_field_data( $this->fields[1], $this->users[2], 'foo' );
		xprofile_set_field_data( $this->fields[1], $this->users[3], 'barry' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				'relation' => 'OR',
				array(
					'field' => $this->fields[0],
					'compare' => '=',
					'value' => 'foo',
				),
				array(
					'field' => $this->fields[1],
					'value' => 'bar',
					'compare' => 'LIKE',
				),
			),
		) );

		$expected = array( $this->users[0], $this->users[3] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	public function test_nested() {
		$this->create_fields( 2 );
		$this->create_users( 3 );

		xprofile_set_field_data( $this->fields[0], $this->users[0], 'foo' );
		xprofile_set_field_data( $this->fields[0], $this->users[1], 'bar' );
		xprofile_set_field_data( $this->fields[1], $this->users[2], 'foo' );
		xprofile_set_field_data( $this->fields[1], $this->users[1], 'foo' );

		$q = new BP_User_Query( array(
			'xprofile_query' => array(
				'relation' => 'OR',
				array(
					'field' => $this->fields[0],
					'compare' => '=',
					'value' => 'foo',
				),
				array(
					'relation' => 'AND',
					array(
						'field' => $this->fields[0],
						'value' => 'bar',
					),
					array(
						'field' => $this->fields[1],
						'value' => 'foo',
					),
				),
			),
		) );

		$expected = array( $this->users[0], $this->users[1] );
		$this->assertEqualSets( $expected, array_keys( $q->results ) );
	}

	/** Helpers **********************************************************/

	protected function create_fields( $count ) {
		$this->group = $this->factory->xprofile_group->create();
		for ( $i = 0; $i < $count; $i++ ) {
			$this->fields[] = $this->factory->xprofile_field->create( array(
				'field_group_id' => $this->group,
				'type' => 'textbox',
			) );
		}
	}

	protected function create_users( $count ) {
		for ( $i = 0; $i < $count; $i++ ) {
			$this->users[] = $this->factory->user->create();
		}
	}
}
