<?php
/**
 * @group blogs_functions
 * @group multisite
 */
class BP_Nouveau_Blogs_Functions extends Next_Template_Packs_TestCase {

	public function setUp() {
		parent::setUp();

		$this->current_user = get_current_user_id();
		$this->user_id      = $this->factory->user->create();
		$this->set_current_user( $this->user_id );

		$this->hooked_nav = array(
			'all_one' => sprintf( '<li id="blogs-%1$s"><a href="%2$s" title="%3$s">%4$s</a></li>', 'foo', 'http://example.org/blogs/foo', 'Foo', 'Foo' ),
			'all_two' => sprintf( '<li id="blogs-%1$s"><a href="%2$s" title="%3$s">%4$s <span>%5$s</span></a></li>', 'bar', 'http://example.org/blogs/bar', 'Bar', 'Bar', 5 ),
		);
	}

	public function tearDown() {
		parent::tearDown();

		$this->set_current_user( $this->current_user );

		// Reset the directory nav
		bp_nouveau()->directory_nav = new BP_Core_Nav();
	}

	public function __return_all( $option ) {
		return 'all';
	}

	public function do_dir_nav() {
		$nav = reset( $this->hooked_nav );
		unset( $this->hooked_nav['all_one'] );

		echo $nav;
	}

	public function filter_dir_nav( $nav_items ) {
		$nav_items['taz'] = array(
			'component' => 'blogs',
			'slug'      => 'taz',
			'link'      => 'http://example.org/blogs/taz',
			'title'     => 'Taz',
			'text'      => 'Taz',
			'count'     => false,
			'position'  => 0,
		);

		return $nav_items;
	}

	/**
	 * @group directory_nav
	 * @group do_actions
	 */
	public function test_add_action_get_blogs_directory_nav_items() {
		$this->go_to( bp_get_blogs_directory_permalink() );

		add_filter( 'wpmu_active_signup', array( $this, '__return_all' ) );

		add_action( 'bp_blogs_directory_blog_types', array( $this, 'do_dir_nav' ), 9 );
		add_action( 'bp_blogs_directory_blog_types', array( $this, 'do_dir_nav' ), 10 );

		do_action( 'bp_screens' );

		// Navs are before, this should be the order
		$expected = array( 'all', 'foo', 'bar', 'create' );

		// Init and sort the directory nav
		bp_nouveau_has_nav( array( 'object' => 'directory' ) );

		remove_filter( 'wpmu_active_signup', array( $this, '__return_all' ) );
		remove_action( 'bp_blogs_directory_blog_types', array( $this, 'do_dir_nav' ), 9 );
		remove_action( 'bp_blogs_directory_blog_types', array( $this, 'do_dir_nav' ), 10 );

		$this->assertSame( $expected, wp_list_pluck( bp_nouveau()->sorted_nav, 'slug' ) );
	}

	/**
	 * @group directory_nav
	 * @group apply_filters
	 */
	public function test_add_filter_get_blogs_directory_nav_items() {
		$this->go_to( bp_get_blogs_directory_permalink() );

		do_action( 'bp_screens' );

		add_filter( 'bp_nouveau_get_blogs_directory_nav_items', array( $this, 'filter_dir_nav' ), 10, 1 );

		do_action( 'bp_screens' );

		// Navs are before, this should be the order
		$expected = array( 'taz', 'all' );

		// Init and sort the directory nav
		bp_nouveau_has_nav( array( 'object' => 'directory' ) );

		remove_filter( 'bp_nouveau_get_blogs_directory_nav_items', array( $this, 'filter_dir_nav' ), 10, 1 );

		$this->assertSame( $expected, wp_list_pluck( bp_nouveau()->sorted_nav, 'slug' ) );
	}
}
