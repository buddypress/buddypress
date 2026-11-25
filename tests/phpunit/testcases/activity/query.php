<?php

/**
 * @group activity
 */
class BP_Tests_Activity_Query extends BP_UnitTestCase {

	public function test_empty_value_clause_sanitizes_relation() {
		$relations = array(
			array( 'AND (1=1) AND', 'AND' ),
			array( array( 'OR' ), 'AND' ),
			array( 'or', 'OR' ),
		);

		foreach ( $relations as $relation ) {
			$q = new BP_Activity_Query( array(
				array(
					'value'    => array(),
					'relation' => $relation[0],
					array( 'column' => 'component', 'value' => 'activity' ),
					array( 'column' => 'type', 'value' => 'activity_update' ),
				),
			) );

			$sql = $q->get_sql();

			$this->assertSame( $relation[1], $q->queries[0]['relation'] );
			$this->assertSame( false, strpos( $sql, '1=1' ) );
			$this->assertSame( 1, preg_match( '/a\.component = [^\n]+\s+' . $relation[1] . '\s+a\.type = /', $sql ) );
		}
	}

	public function test_get_sql_sanitizes_relation_after_query_is_modified() {
		$q = new BP_Activity_Query( array(
			array(
				'relation' => 'OR',
				array( 'column' => 'component', 'value' => 'activity' ),
				array( 'column' => 'type', 'value' => 'activity_update' ),
			),
		) );

		$q->queries[0]['relation'] = 'AND (1=1) AND';
		$sql = $q->get_sql();

		$this->assertSame( false, strpos( $sql, '1=1' ) );
		$this->assertSame( 1, preg_match( '/a\.component = [^\n]+\s+AND\s+a\.type = /', $sql ) );
	}
}
