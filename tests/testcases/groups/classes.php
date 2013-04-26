<?php
/**
 * @group groups
 */
class BP_Tests_Groups_Classes extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
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
}
