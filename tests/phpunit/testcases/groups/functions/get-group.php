<?php

/**
 * @group groups
 * @group functions
 */
class BP_Tests_Get_Groups_Param extends BP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		if ( isset( $GLOBALS['groups_template'] ) ) {
			$this->groups_template = $GLOBALS['groups_template'];
		}
	}

	public function tear_down() {
		if ( $this->groups_template ) {
			$GLOBALS['groups_template'] = $this->groups_template;
		}

		parent::tear_down();
	}

	/**
	 * @group bp_get_group
	 */
	public function test_bp_get_group_with_no_group() {
		$this->assertFalse( bp_get_group() );
		$this->assertFalse( bp_get_group_by( 'id', 0 ) );
	}

	/**
	 * @group bp_get_group
	 */
	public function test_bp_get_group_with_id() {
		$g = $this->factory->group->create();

		$this->assertSame( $g, bp_get_group( $g )->id );
		$this->assertSame( $g, bp_get_group_by( 'id', $g )->id );
		$this->assertSame( $g, bp_get_group_by( 'ID', $g )->id );
	}

	/**
	 * @group bp_get_group
	 */
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

	/**
	 * @group bp_get_group
	 */
	public function test_bp_get_group_with_object() {
		$g = $this->factory->group->create_and_get();

		$this->assertSame( $g->id, bp_get_group( $g )->id );
	}

	/**
	 * @group bp_get_group
	 */
	public function test_bp_get_group_from_groups_template() {
		$g = $this->factory->group->create( array( 'status' => 'private' ) );

		if ( bp_has_groups( array( 'include' => array( $g ) ) ) ) {
			while ( bp_groups() ) {
				bp_the_group();
				$group = bp_get_group();
			}
		}

		$this->assertSame( $g, $group->id );
	}

	/**
	 * @group bp_get_group
	 */
	public function test_bp_get_group_from_current_group() {
		$bp = buddypress();
		$g  = $this->factory->group->create_and_get( array( 'name' => 'foo' ) );

		// Set the current group.
		$bp->groups->current_group = $g;

		// Change the name to check the current group was used.
		$bp->groups->current_group->name = 'bar';

		// Override the name
		do_action( 'bp_groups_set_current_group' );

		$this->assertSame( 'bar', bp_get_group( $g->id )->name );
	}
}
