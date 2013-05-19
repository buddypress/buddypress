<?php
/**
 * @group groups
 */
class BP_Tests_Groups_Template extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Integration test to make sure meta_query is getting passed through
	 *
	 * @group bp_has_groups
	 */
	public function test_bp_has_groups_with_meta_query() {
		$g1 = $this->factory->group->create();
		$g2 = $this->factory->group->create();
		groups_update_groupmeta( $g1, 'foo', 'bar' );

		global $groups_template;
		bp_has_groups( array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		) );

		$ids = wp_list_pluck( $groups_template->groups, 'id' );
		$this->assertEquals( $ids, array( $g1, ) );
	}
}
