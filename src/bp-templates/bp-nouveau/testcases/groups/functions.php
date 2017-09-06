<?php
/**
 * @group groups_functions
 */
class BP_Nouveau_Groups_Functions extends Next_Template_Packs_TestCase {

	public function setUp() {
		parent::setUp();

		$this->current_user = get_current_user_id();
		$this->user_id      = $this->factory->user->create();
		$this->set_current_user( $this->user_id );
	}

	public function tearDown() {
		parent::tearDown();

		$this->set_current_user( $this->current_user );

		// Reset the directory nav
		bp_nouveau()->directory_nav = new BP_Core_Nav();
	}

	public function do_dir_nav() {
		printf( '<li id="groups-%1$s"><a href="%2$s" title="%3$s">%4$s</a></li>', 'foo', 'http://example.org/groups/foo', 'Foo', 'Foo' );
	}

	public function filter_dir_nav( $nav_items ) {
		$nav_items['bar'] = array(
			'component' => 'groups',
			'slug'      => 'bar',
			'link'      => 'http://example.org/groups/bar',
			'title'     => 'Bar',
			'text'      => 'Bar',
			'count'     => false,
			'position'  => 0,
		);

		return $nav_items;
	}

	/**
	 * @group directory_nav
	 * @group do_actions
	 */
	public function test_add_action_get_groups_directory_nav_items() {
		$this->go_to( bp_get_groups_directory_permalink() );

		add_action( 'bp_groups_directory_group_filter', array( $this, 'do_dir_nav' ), 10 );

		do_action( 'bp_screens' );

		// Navs are before, this should be the order
		$expected = array( 'all', 'foo', 'create' );

		// Init and sort the directory nav
		bp_nouveau_has_nav( array( 'object' => 'directory' ) );

		remove_action( 'bp_groups_directory_group_filter', array( $this, 'do_dir_nav' ), 10 );

		$this->assertSame( $expected, wp_list_pluck( bp_nouveau()->sorted_nav, 'slug' ) );
	}

	/**
	 * @group directory_nav
	 * @group apply_filters
	 */
	public function test_add_filter_get_groups_directory_nav_items() {
		$this->go_to( bp_get_groups_directory_permalink() );

		do_action( 'bp_screens' );

		add_filter( 'bp_nouveau_get_groups_directory_nav_items', array( $this, 'filter_dir_nav' ), 10, 1 );

		do_action( 'bp_screens' );

		// Navs are before, this should be the order
		$expected = array( 'bar', 'all', 'create' );

		// Init and sort the directory nav
		bp_nouveau_has_nav( array( 'object' => 'directory' ) );

		remove_filter( 'bp_nouveau_get_groups_directory_nav_items', array( $this, 'filter_dir_nav' ), 10, 1 );

		$this->assertSame( $expected, wp_list_pluck( bp_nouveau()->sorted_nav, 'slug' ) );
	}
}
