<?php
/**
 * @group core
 * @group BP_Walker_Nav_Menu
 */
class BP_Tests_Walker_Nav_Menu extends BP_UnitTestCase {
	protected $reset_user_id;
	protected $user_id;

	public function setUp() {
		parent::setUp();

		$this->reset_user_id = get_current_user_id();

		$this->user_id = self::factory()->user->create();
		$this->set_current_user( $this->user_id );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->reset_user_id );
	}

	public function test_walk_method() {
		$expected = array( 'activity-class', 'xprofile-class' );
		$items    = array(
			(object) array(
				'component_id' => 'activity',
				'name'         => 'Activity',
				'slug'         => 'activity',
				'link'         => trailingslashit( bp_loggedin_user_domain() . bp_get_activity_slug() ),
				'css_id'       => 'activity',
				'class'        => array( $expected[0] ),
			),
			(object) array(
				'component_id' => 'xprofile',
				'name'         => 'Profile',
				'slug'         => 'profile',
				'link'         => trailingslashit( bp_loggedin_user_domain() . bp_get_profile_slug() ),
				'css_id'       => 'xprofile',
				'class'        => array( $expected[1] ),
			),
		);
		$args = (object) array( 'before' => '', 'link_before' => '', 'after' => '', 'link_after' => '' );
		$walker = new BP_Walker_Nav_Menu();
		$output = $walker->walk( $items, -1, $args );
		preg_match_all( '/class=["\']?([^"\']*)["\' ]/is', $output, $classes );
		$this->assertSame( $classes[1], $expected );
	}
}
