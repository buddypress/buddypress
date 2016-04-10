<?php

/**
 * @group activity
 * @covers ::bp_activity_get_actions
 */
class BP_Tests_Activity_Functions_BpActivityGetActions extends BP_UnitTestCase {
	protected $reset_actions;
	protected $reset_actions_sorted;

	public function setUp() {
		$bp = buddypress();

		$this->reset_actions = clone $bp->activity->actions;
		$bp->activity->actions = new stdClass();

		$this->reset_actions_sorted = ! empty( $bp->activity->actions_sorted );
		unset( $bp->activity->actions_sorted );
	}

	public function tearDown() {
		$bp = buddypress();

		$bp->activity->actions = $this->reset_actions;

		if ( $this->reset_actions_sorted ) {
			$bp->activity->actions_sorted = true;
		} else {
			unset( $bp->activity->actions_sorted );
		}
	}

	/**
	 * @group activity_action
	 */
	public function test_bp_activity_get_actions_should_sort_by_position() {
		register_post_type( 'foo5', array(
			'public'      => true,
			'supports'    => array( 'buddypress-activity' ),
			'bp_activity' => array(
				'component_id' => 'foo',
				'action_id' => 'foo_bar_5',
				'position' => 5,
			),
		) );

		register_post_type( 'foo50', array(
			'public'      => true,
			'supports'    => array( 'buddypress-activity' ),
			'bp_activity' => array(
				'component_id' => 'foo',
				'action_id' => 'foo_bar_50',
				'position' => 50,
			),
		) );

		register_post_type( 'foo25', array(
			'public'      => true,
			'supports'    => array( 'buddypress-activity' ),
			'bp_activity' => array(
				'component_id' => 'foo',
				'action_id' => 'foo_bar_25',
				'position' => 25,
			),
		) );

		$actions = bp_activity_get_actions();

		_unregister_post_type( 'foo5' );
		_unregister_post_type( 'foo25' );
		_unregister_post_type( 'foo50' );

		$expected = array(
			'foo_bar_5',
			'foo_bar_25',
			'foo_bar_50',
		);
		$foo_actions = (array) $actions->foo;
		$this->assertEquals( $expected, array_values( wp_list_pluck( $foo_actions, 'key' ) ) );
	}

	/**
	 * @ticket BP6865
	 */
	public function test_bp_activity_get_actions_sort() {
		bp_activity_set_action(
			'foo',
			'new_foo',
			'Did a foo',
			'',
			'',
			array(),
			10
		);

		bp_activity_set_action(
			'foo',
			'new_bar',
			'Did a bar',
			'',
			'',
			array(),
			5
		);

		$actions = bp_activity_get_actions();

		$expected = array(
			'new_bar' => 'new_bar',
			'new_foo' => 'new_foo',
		);

		$this->assertSame( $expected, wp_list_pluck( (array) $actions->foo, 'key' ) );
	}

	/**
	 * @ticket BP6865
	 */
	public function test_sort_new_post_type_once() {
		register_post_type( 'foo', array(
			'label'   => 'foo',
			'public'   => true,
			'supports' => array( 'buddypress-activity' ),
			'bp_activity' => array(
				'component_id' => 'blogs',
				'action_id'    => 'new_foo',
				'position'     => 1,
			),
		) );

		$actions = bp_activity_get_actions();

		_unregister_post_type( 'foo' );

		$expected = array(
			'new_foo'          => 'new_foo',
			'new_blog_post'    => 'new_blog_post',
			'new_blog_comment' => 'new_blog_comment',
		);

		$this->assertSame( $expected, wp_list_pluck( (array) $actions->blogs, 'key' ) );
	}

	/**
	 * @ticket BP6865
	 */
	public function test_sort_new_post_type_twice() {
		$actions = bp_activity_get_actions();
		$expected = array(
			'new_blog_post'    => 'new_blog_post',
			'new_blog_comment' => 'new_blog_comment',
		);
		$this->assertSame( $expected, wp_list_pluck( (array) $actions->blogs, 'key' ) );

		register_post_type( 'foo', array(
			'label'   => 'foo',
			'public'   => true,
			'supports' => array( 'buddypress-activity' ),
			'bp_activity' => array(
				'component_id' => 'blogs',
				'action_id'    => 'new_foo',
				'position'     => 1,
			),
		) );

		$actions = bp_activity_get_actions();

		_unregister_post_type( 'foo' );

		$expected = array(
			'new_foo'          => 'new_foo',
			'new_blog_post'    => 'new_blog_post',
			'new_blog_comment' => 'new_blog_comment',
		);

		$this->assertSame( $expected, wp_list_pluck( (array) $actions->blogs, 'key' ) );
	}

	/**
	 * @ticket BP6865
	 */
	public function test_sort_no_post_type_registered() {
		bp_activity_set_action(
			'foo',
			'new_foo',
			'Did a foo',
			'',
			'',
			array(),
			10
		);

		bp_activity_set_action(
			'foo',
			'new_bar',
			'Did a bar',
			'',
			'',
			array(),
			5
		);

		remove_post_type_support( 'post', 'buddypress-activity' );

		$actions = bp_activity_get_actions();

		$expected = array(
			'new_bar' => 'new_bar',
			'new_foo' => 'new_foo',
		);

		$this->assertSame( $expected, wp_list_pluck( (array) $actions->foo, 'key' ) );

		// Clean up.
		add_post_type_support( 'post', 'buddypress-activity' );
	}
}
