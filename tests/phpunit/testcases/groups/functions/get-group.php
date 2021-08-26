<?php

/**
 * @group groups
 * @group functions
 */
class BP_Tests_Get_Groups_Param extends BP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		if ( isset( $GLOBALS['groups_template'] ) ) {
			$this->groups_template = $GLOBALS['groups_template'];
		}
	}

	public function tearDown() {
		if ( $this->groups_template ) {
			$GLOBALS['groups_template'] = $this->groups_template;
		}

		parent::tearDown();
	}

	public function test_bp_get_group_with_no_group() {
		$this->assertFalse( bp_get_group() );
		$this->assertFalse( bp_get_group_by( 'id', 0 ) );
	}

	public function test_bp_get_group_with_id() {
		$g = $this->factory->group->create();

		$this->assertSame( $g, bp_get_group( $g )->id );
		$this->assertSame( $g, bp_get_group_by( 'id', $g )->id );
		$this->assertSame( $g, bp_get_group_by( 'ID', $g )->id );
	}

	public function test_bp_get_group_with_slug() {
		$slug = 'test-group';
		$g    = $this->factory->group->create( array( 'slug' => $slug ) );
		$g1   = bp_get_group( $slug );

		$this->assertSame( $g, $g1->id );
		$this->assertSame( $slug, $g1->slug );

		$g2 = bp_get_group_by( 'slug', $slug );

		$this->assertSame( $g, $g2->id );
		$this->assertSame( $slug, $g2->slug );
	}

	public function test_bp_get_group_with_object() {
		$g = $this->factory->group->create_and_get();

		$this->assertSame( $g->id, bp_get_group( $g )->id );
	}

	public function test_bp_get_group_from_groups_template() {
		$g = $this->factory->group->create( array( 'status' => 'private' ) );

		// Fake the current group.
		$GLOBALS['groups_template'] = new stdClass;
		$GLOBALS['groups_template']->group = groups_get_group( $g );

		$this->assertSame( $g, bp_get_group()->id );
	}
}
