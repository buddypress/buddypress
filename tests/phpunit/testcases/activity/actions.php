<?php
/**
 * @group activity
 */
class BP_Tests_Activity_Actions extends BP_UnitTestCase {

	/**
	 * @group bp_activity_catch_transition_post_type_status
	 * @group activity_tracking
	 */
	public function test_bp_activity_catch_transition_post_type_status_publish() {
		register_post_type( 'foo', array(
			'label'   => 'foo',
			'public'   => true,
			'supports' => array( 'buddypress-activity' ),
		) );

		$post_id = self::factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'foo',
		) );

		$post = get_post( $post_id );

		// 'new' => 'publish'
		$this->assertTrue( $this->activity_exists_for_post( $post_id, 'new_foo' ), 'Published post type should have activity' );

		_unregister_post_type( 'foo' );
	}

	/**
	 * @group bp_activity_catch_transition_post_type_status
	 * @group activity_tracking
	 */
	public function test_bp_activity_catch_transition_post_type_status_publish_to_publish() {
		register_post_type( 'foo', array(
			'label'   => 'foo',
			'public'   => true,
			'supports' => array( 'buddypress-activity' ),
		) );

		$post_id = self::factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'foo',
		) );

		$post = get_post( $post_id );

		// 'new' => 'publish'
		$this->assertTrue( $this->activity_exists_for_post( $post_id, 'new_foo' ), 'Published post type should have activity' );

		_unregister_post_type( 'foo' );
	}

	/**
	 * @group bp_activity_catch_transition_post_type_status
	 * @group activity_tracking
	 */
	public function test_bp_activity_catch_transition_post_type_status_publish_existing_post() {
		$u = self::factory()->user->create();

		$labels = array(
			'bp_activity_admin_filter' => 'New Foo',
			'bp_activity_front_filter' => 'Foos',
		        'bp_activity_new_post'    => '%1$s posted a new <a href="%2$s">foo</a>',
		        'bp_activity_new_post_ms' => '%1$s posted a new <a href="%2$s">foo</a>, on the site %3$s',
		);

		/**
		 * 'public' must be set to true, otherwise bp_activity_get_post_types_tracking_args() fails.
		 */
		register_post_type( 'foo', array(
			'labels'      => $labels,
			'public'      => true,
			'supports'    => array( 'buddypress-activity' ),
			'bp_activity' => array(
				'action_id'    => 'new_foo',
				'contexts'     => array( 'activity' ),
				'position'     => 40,
			)
		) );

		// Temporarily remove post type activity hook so activity item isn't created.
		remove_action( 'transition_post_status', 'bp_activity_catch_transition_post_type_status', 10 );

		// Create the initial post.
		$p = self::factory()->post->create( array(
			'post_author' => $u,
			'post_type'   => 'foo',
		) );

		$this->assertEmpty( bp_activity_get_activity_id( array( 'type' => 'new_foo' ) ) );

		// Add the post type activity hook back.
		add_action( 'transition_post_status', 'bp_activity_catch_transition_post_type_status', 10, 3 );

		// Emulate updating a post; this should create an activity item.
		wp_update_post( array(
			'ID'     => $p,
			'post_title' => 'This is an edit',
		) );

		// Assert!
		$this->assertNotEmpty( bp_activity_get_activity_id( array( 'type' => 'new_foo' ) ), 'Activity item was not created during an edit of an existing WordPress post.' );

		// Clean up.
		_unregister_post_type( 'foo' );
	}

	/**
	 * @group bp_activity_catch_transition_post_type_status
	 * @group activity_tracking
	 */
	public function test_bp_activity_catch_transition_post_type_status_publish_password() {
		register_post_type( 'foo', array(
			'label'   => 'foo',
			'public'   => true,
			'supports' => array( 'buddypress-activity' ),
		) );

		$post_id = self::factory()->post->create( array(
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
	}

	/**
	 * @group bp_activity_catch_transition_post_type_status
	 * @group activity_tracking
	 */
	public function test_bp_activity_catch_transition_post_type_status_publish_trash() {
		register_post_type( 'foo', array(
			'label'   => 'foo',
			'public'   => true,
			'supports' => array( 'buddypress-activity' ),
		) );

		$post_id = self::factory()->post->create( array(
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
	}

	/**
	 * @ticket BP8579
	 * @group bp_activity_catch_transition_post_type_status
	 * @group activity_tracking
	 */
	public function test_bp_activity_catch_transition_post_type_status_publish_publish_author_changed() {
		$u1 = self::factory()->user->create(
			array(
				'role' => 'auhor',
			)
		);

		$u2 = self::factory()->user->create(
			array(
				'role' => 'auhor',
			)
		);

		$post_id = self::factory()->post->create( array(
			'post_status' => 'publish',
			'post_author' => $u1,
			'post_type'   => 'post',
		) );

		$post = get_post( $post_id );

		// 'new' => 'publish'
		$this->assertTrue( $this->activity_exists_for_post( $post_id, 'new_post' ), 'Published post type should have activity' );

		$post->post_author = $u2;

		wp_update_post( $post );

		$a = $this->activity_exists_for_post( $post_id, 'new_post', true );

		$this->assertSame( $u2, $a->user_id, 'The Activity about a published post type should be updated when the post author has changed.' );
	}

	/**
	 * @ticket BP8579
	 * @group bp_activity_catch_transition_post_type_status
	 * @group activity_tracking
	 */
	public function test_bp_activity_catch_transition_post_type_status_publish_publish_content_changed() {
		$u1 = self::factory()->user->create(
			array(
				'role' => 'auhor',
			)
		);

		$u2 = self::factory()->user->create(
			array(
				'role' => 'auhor',
			)
		);

		$post_id = self::factory()->post->create( array(
			'post_status' => 'publish',
			'post_author' => $u1,
			'post_type'   => 'post',
		) );

		$post = get_post( $post_id );

		// 'new' => 'publish'
		$this->assertTrue( $this->activity_exists_for_post( $post_id, 'new_post' ), 'Published post type should have activity' );

		$post->post_content = "This time {$u2} has not edited the post";

		wp_update_post( $post );

		$a = $this->activity_exists_for_post( $post_id, 'new_post', true );

		$this->assertSame( bp_activity_create_summary( $post->post_content, (array) $a ), $a->content, 'The Activity about a published post type should be updated when the post content has changed.' );
	}

	protected function activity_exists_for_post( $post_id, $action, $get_object = false ) {
		$a = bp_activity_get( array(
			'action'            => $action,
			'item_id'           => get_current_blog_id(),
			'secondary_item_id' => $post_id,
		) );

		if ( $get_object ) {
			return reset( $a['activities'] );
		}

		return ! empty( $a['activities'] );
	}
}
