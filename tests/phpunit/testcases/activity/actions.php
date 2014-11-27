<?php
/**
 * @group activity
 */
class BP_Tests_Activity_Actions extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	/**
	 * @group bp_activity_catch_transition_post_type_status
	 * @group activity_tracking
	 */
	public function test_bp_activity_catch_transition_post_type_status_publish() {
		$bp = buddypress();

		register_post_type( 'foo', array(
			'label'   => 'foo',
			'public'   => true,
			'supports' => array( 'buddypress-activity' ),
		) );

		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'foo',
		) );

		$post = get_post( $post_id );

		// 'new' => 'publish'
		$this->assertTrue( $this->activity_exists_for_post( $post_id, 'new_foo' ), 'Published post type should have activity' );

		_unregister_post_type( 'foo' );

		// Reset globals
		unset( $bp->activity->actions->activity->new_foo );
		$bp->activity->track = array();
	}

	/**
	 * @group bp_activity_catch_transition_post_type_status
	 * @group activity_tracking
	 */
	public function test_bp_activity_catch_transition_post_type_status_publish_to_publish() {
		$bp = buddypress();

		register_post_type( 'foo', array(
			'label'   => 'foo',
			'public'   => true,
			'supports' => array( 'buddypress-activity' ),
		) );

		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'foo',
		) );

		$post = get_post( $post_id );

		// 'new' => 'publish'
		$this->assertTrue( $this->activity_exists_for_post( $post_id, 'new_foo' ), 'Published post type should have activity' );

		// Delete the activity
		bp_activity_post_type_unpublish( $post_id, $post );

		$post->post_status = 'publish';
		$post->post_content .= ' foo';

		wp_update_post( $post );

		$this->assertFalse( $this->activity_exists_for_post( $post_id, 'new_foo' ), 'Updating a post type should not create a new activity' );

		_unregister_post_type( 'foo' );

		// Reset globals
		unset( $bp->activity->actions->activity->new_foo );
		$bp->activity->track = array();
	}

	/**
	 * @group bp_activity_catch_transition_post_type_status
	 * @group activity_tracking
	 */
	public function test_bp_activity_catch_transition_post_type_status_publish_password() {
		$bp = buddypress();

		register_post_type( 'foo', array(
			'label'   => 'foo',
			'public'   => true,
			'supports' => array( 'buddypress-activity' ),
		) );

		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'foo',
		) );

		$post = get_post( $post_id );

		// 'new' => 'publish'
		$this->assertTrue( $this->activity_exists_for_post( $post_id, 'new_foo' ), 'Published post type should have activity' );

		$post->post_status   = 'publish';
		$post->post_password = 'foo';

		wp_update_post( $post );

		// 'publish' => 'publish' (password protected)
		$this->assertFalse( $this->activity_exists_for_post( $post_id, 'new_foo' ), 'Password protected post type should not have activity' );

		_unregister_post_type( 'foo' );

		// Reset globals
		unset( $bp->activity->actions->activity->new_foo );
		$bp->activity->track = array();
	}

	/**
	 * @group bp_activity_catch_transition_post_type_status
	 * @group activity_tracking
	 */
	public function test_bp_activity_catch_transition_post_type_status_publish_trash() {
		$bp = buddypress();

		register_post_type( 'foo', array(
			'label'   => 'foo',
			'public'   => true,
			'supports' => array( 'buddypress-activity' ),
		) );

		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'foo',
		) );

		$post = get_post( $post_id );

		// 'new' => 'publish'
		$this->assertTrue( $this->activity_exists_for_post( $post_id, 'new_foo' ), 'Published post type should have activity' );

		wp_trash_post( $post->ID );

		// 'publish' => 'publish' (password protected)
		$this->assertFalse( $this->activity_exists_for_post( $post_id, 'new_foo' ), 'Unpublished post type should not have activity' );

		_unregister_post_type( 'foo' );

		// Reset globals
		unset( $bp->activity->actions->activity->new_foo );
		$bp->activity->track = array();
	}

	protected function activity_exists_for_post( $post_id, $action ) {
		$a = bp_activity_get( array(
			'action'            => $action,
			'item_id'           => get_current_blog_id(),
			'secondary_item_id' => $post_id,
		) );

		return ! empty( $a['activities'] );
	}
}
