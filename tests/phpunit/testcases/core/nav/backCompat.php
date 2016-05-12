<?php

/**
 * @group core
 * @group nav
 * @ticket BP6534
 * @expectedIncorrectUsage bp_nav
 */
class BP_Core_Nav_BackCompat extends BP_UnitTestCase {
	protected $bp_nav;
	protected $bp_options_nav;

	public function setUp() {
		parent::setUp();
		$this->bp_nav = buddypress()->bp_nav;
		$this->bp_options_nav = buddypress()->bp_options_nav;
	}

	public function tearDown() {
		buddypress()->bp_nav = $this->bp_nav;
		buddypress()->bp_options_nav = $this->bp_options_nav;
		parent::tearDown();
	}

	protected function create_nav_items() {
		bp_core_new_nav_item( array(
			'name'                => 'Foo',
			'slug'                => 'foo',
			'position'            => 25,
			'screen_function'     => 'foo_screen_function',
			'default_subnav_slug' => 'foo-subnav'
		) );

		bp_core_new_subnav_item( array(
			'name'            => 'Foo Subnav',
			'slug'            => 'foo-subnav',
			'parent_url'      => 'example.com/foo',
			'parent_slug'     => 'foo',
			'screen_function' => 'foo_screen_function',
			'position'        => 10
		) );
	}

	/**
	 * Create a group, set up nav item, and go to the group.
	 */
	protected function set_up_group() {
		$g = $this->factory->group->create( array(
			'slug' => 'testgroup',
		) );

		$group = groups_get_group( array( 'group_id' => $g ) );
		$group_permalink = bp_get_group_permalink( $group );

		$this->go_to( $group_permalink );

		bp_core_new_subnav_item( array(
			'name'            => 'Foo',
			'slug'            => 'foo',
			'parent_url'      => $group_permalink,
			'parent_slug'     => 'testgroup',
			'screen_function' => 'foo_screen_function',
			'position'        => 10
		), 'groups' );
	}

	public function test_bp_nav_isset() {
		$this->create_nav_items();

		$bp = buddypress();

		$this->assertTrue( isset( $bp->bp_nav ) );
		$this->assertTrue( isset( $bp->bp_nav['foo'] ) );
		$this->assertTrue( isset( $bp->bp_nav['foo']['name'] ) );
	}

	public function test_bp_nav_unset() {
		$this->create_nav_items();

		$bp = buddypress();

		// No support for this - it would create a malformed nav item.
		/*
		unset( $bp->bp_nav['foo']['css_id'] );
		$this->assertFalse( isset( $bp->bp_nav['foo']['css_id'] ) );
		*/

		unset( $bp->bp_nav['foo'] );
		$this->assertFalse( isset( $bp->bp_nav['foo'] ) );
	}

	public function test_bp_nav_get() {
		$this->create_nav_items();

		$bp = buddypress();

		$foo = $bp->bp_nav['foo'];
		$this->assertSame( 'Foo', $foo['name'] );

		$this->assertSame( 'Foo', $bp->bp_nav['foo']['name'] );
	}

	public function test_bp_nav_set() {
		$this->create_nav_items();

		$bp = buddypress();

		$bp->bp_nav['foo']['name'] = 'Bar';

		$nav = bp_get_nav_menu_items();

		foreach ( $nav as $_nav ) {
			if ( 'foo' === $_nav->css_id ) {
				$found = $_nav;
				break;
			}
		}

		$this->assertSame( 'Bar', $found->name );
	}

	public function test_bp_options_nav_isset() {
		$this->create_nav_items();

		$bp = buddypress();

		$this->assertTrue( isset( $bp->bp_options_nav ) );
		$this->assertTrue( isset( $bp->bp_options_nav['foo'] ) );
		$this->assertTrue( isset( $bp->bp_options_nav['foo']['foo-subnav'] ) );
		$this->assertTrue( isset( $bp->bp_options_nav['foo']['foo-subnav']['name'] ) );
	}

	public function test_bp_options_nav_unset() {
		$this->create_nav_items();

		$bp = buddypress();

		// No support for this - it would create a malformed nav item.
		/*
		unset( $bp->bp_options_nav['foo']['foo-subnav']['user_has_access'] );
		$this->assertFalse( isset( $bp->bp_options_nav['foo']['foo-subnav']['user_has_access'] ) );
		*/

		unset( $bp->bp_options_nav['foo']['foo-subnav'] );
		$this->assertFalse( isset( $bp->bp_options_nav['foo']['foo-subnav'] ) );

		// Make sure the parent nav hasn't been wiped out.
		$this->assertTrue( isset( $bp->bp_options_nav['foo'] ) );

		unset( $bp->bp_options_nav['foo'] );
		$this->assertFalse( isset( $bp->bp_options_nav['foo'] ) );
	}

	public function test_bp_options_nav_get() {
		$this->create_nav_items();

		$bp = buddypress();

		$foo_subnav = $bp->bp_options_nav['foo']['foo-subnav'];
		$this->assertSame( 'Foo Subnav', $foo_subnav['name'] );

		$this->assertSame( 'Foo Subnav', $bp->bp_options_nav['foo']['foo-subnav']['name'] );
	}

	public function test_bp_options_nav_set() {
		$this->create_nav_items();

		$bp = buddypress();

		$bp->bp_options_nav['foo']['foo-subnav']['name'] = 'Bar';
		$nav = bp_get_nav_menu_items();

		foreach ( $nav as $_nav ) {
			if ( 'foo-subnav' === $_nav->css_id ) {
				$found = $_nav;
				break;
			}
		}

		$this->assertSame( 'Bar', $found->name );

		$subnav = array(
			'name' => 'Bar',
			'css_id' => 'bar-id',
			'link' => 'bar-link',
			'slug' => 'bar-slug',
			'user_has_access' => true,
		);
		$bp->bp_options_nav['foo']['foo-subnav'] = $subnav;
		$nav = bp_get_nav_menu_items();

		foreach ( $nav as $_nav ) {
			if ( 'bar-id' === $_nav->css_id ) {
				$found = $_nav;
				break;
			}
		}

		$this->assertSame( 'Bar', $found->name );
	}

	/**
	 * @group groups
	 */
	public function test_bp_options_nav_isset_group_nav() {
		$this->set_up_group();

		$bp = buddypress();

		$this->assertTrue( isset( $bp->bp_options_nav ) );
		$this->assertTrue( isset( $bp->bp_options_nav['testgroup'] ) );
		$this->assertTrue( isset( $bp->bp_options_nav['testgroup']['foo'] ) );
		$this->assertTrue( isset( $bp->bp_options_nav['testgroup']['foo']['name'] ) );
	}

	/**
	 * @group groups
	 */
	public function test_bp_options_nav_unset_group_nav() {
		$this->set_up_group();

		$bp = buddypress();

		// No support for this - it would create a malformed nav item.
		/*
		unset( $bp->bp_options_nav['testgroup']['foo']['user_has_access'] );
		$this->assertFalse( isset( $bp->bp_options_nav['testgroup']['foo']['user_has_access'] ) );
		*/

		unset( $bp->bp_options_nav['testgroup']['foo'] );
		$this->assertFalse( isset( $bp->bp_options_nav['testgroup']['foo'] ) );

		unset( $bp->bp_options_nav['testgroup'] );
		$this->assertFalse( isset( $bp->bp_options_nav['testgroup'] ) );
	}

	/**
	 * @group groups
	 */
	public function test_bp_options_nav_get_group_nav() {
		$this->set_up_group();

		$bp = buddypress();

		$foo = $bp->bp_options_nav['testgroup']['foo'];
		$this->assertSame( 'Foo', $foo['name'] );

		$this->assertSame( 'Foo', $bp->bp_options_nav['testgroup']['foo']['name'] );
	}

	/**
	 * @group groups
	 */
	public function test_bp_options_nav_set_group_nav() {
		$this->set_up_group();

		$bp = buddypress();

		$bp->bp_options_nav['testgroup']['foo']['name'] = 'Bar';
		$nav = bp_get_nav_menu_items( 'groups' );

		foreach ( $nav as $_nav ) {
			if ( 'foo' === $_nav->css_id ) {
				$found = $_nav;
				break;
			}
		}

		$this->assertSame( 'Bar', $found->name );

		$subnav = array(
			'name' => 'Bar',
			'css_id' => 'bar-id',
			'link' => 'bar-link',
			'slug' => 'bar-slug',
			'user_has_access' => true,
		);
		$bp->bp_options_nav['testgroup']['foo'] = $subnav;
		$nav = bp_get_nav_menu_items( 'groups' );

		foreach ( $nav as $_nav ) {
			if ( 'bar-id' === $_nav->css_id ) {
				$found = $_nav;
				break;
			}
		}

		$this->assertSame( 'Bar', $found->name );
	}

	/**
	 * @group groups
	 */
	public function test_bp_core_new_subnav_item_should_work_in_group_context() {
		$this->set_up_group();

		bp_core_new_subnav_item( array(
			'name' => 'Foo Subnav',
			'slug' => 'foo-subnav',
			'parent_slug' => bp_get_current_group_slug(),
			'parent_url' => bp_get_group_permalink( groups_get_current_group() ),
			'screen_function' => 'foo_subnav',
		) );

		$bp = buddypress();

		// Touch bp_nav since we told PHPUnit it was expectedDeprecated.
		$f = $bp->bp_options_nav[ bp_get_current_group_slug() ];

		$nav = bp_get_nav_menu_items( 'groups' );

		foreach ( $nav as $_nav ) {
			if ( 'foo-subnav' === $_nav->css_id ) {
				$found = $_nav;
				break;
			}
		}

		$this->assertSame( 'Foo Subnav', $found->name );
	}
}
