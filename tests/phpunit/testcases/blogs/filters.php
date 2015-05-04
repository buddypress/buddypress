<?php
/**
 * @group blogs
 * @ticket BP6306
 */
class BP_Tests_Blogs_Filters extends BP_UnitTestCase {
	protected $activity_actions;
	protected $custom_post_types;

	public function setUp() {
		parent::setUp();

		$bp = buddypress();

		$this->activity_actions = $bp->activity->actions;
		$bp->activity->actions = new stdClass();

		$this->custom_post_types = array( 'using_old_filter' );

		register_post_type( 'using_old_filter', array(
			'label'   => 'using_old_filter',
			'public'   => true,
			'supports' => array( 'comments' ),
		) );

		add_filter( 'bp_blogs_record_post_post_types',    array( $this, 'filter_post_types'), 10, 1 );
		add_filter( 'bp_blogs_record_comment_post_types', array( $this, 'filter_post_types'), 10, 1 );
	}

	function tearDown() {
		parent::tearDown();

		$bp = buddypress();

		_unregister_post_type( 'using_old_filter' );
		remove_filter( 'bp_blogs_record_post_post_types',    array( $this, 'filter_post_types'), 10, 1 );
		remove_filter( 'bp_blogs_record_comment_post_types', array( $this, 'filter_post_types'), 10, 1 );

		// Reset activity actions
		$bp->activity->actions = $this->activity_actions;
		$bp->activity->track = array();
	}

	/**
	 * @goup bp_activity_get_actions
	 */
	public function test_bp_activity_get_actions() {
		$activity_actions = bp_activity_get_actions();

		$this->assertTrue( ! isset( $activity_actions->activity->new_using_old_filter ), 'Post types registering using the bp_blogs_record_post_post_types filter should not have a specific action' );
	}

	/**
	 * @goup bp_activity_catch_transition_post_type_status
	 */
	public function test_bp_activity_catch_transition_post_type_status() {
		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'using_old_filter',
		) );

		$this->assertTrue( $this->activity_exists_for_post_type( get_current_blog_id(), $post_id, 'new_blog_post' ), 'Generated activity for a post type registering using the bp_blogs_record_post_post_types filter should have a new_blog_post action' );
	}

	/**
	 * @goup bp_blogs_record_comment
	 */
	public function test_bp_blogs_record_comment() {
		$u = $this->factory->user->create();
		$user = $this->factory->user->get_object_by_id( $u );

		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'using_old_filter',
			'post_author' => $u,
		) );

		$comment_id = $this->factory->comment->create( array(
			'user_id'              => $u,
			'comment_author_email' => $user->user_email,
			'comment_post_ID'      => $post_id,
		) );

		// Approve the comment
		$this->factory->comment->update_object( $comment_id, array( 'comment_approved' => 1 ) );

		$this->assertTrue( $this->activity_exists_for_post_type( get_current_blog_id(), $comment_id, 'new_blog_comment' ), 'Generated activity for comments about a post type registering using the bp_blogs_record_post_post_types filter should have a new_blog_comment action' );
	}

	/**
	 * @goup bp_blogs_record_comment_sync_activity_comment
	 */
	public function test_bp_blogs_record_comment_sync_activity_comment() {
		$u = $this->factory->user->create();
		$user = $this->factory->user->get_object_by_id( $u );

		add_filter( 'bp_disable_blogforum_comments', '__return_false' );

		$post_id = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'using_old_filter',
			'post_author' => $u,
		) );

		$parent_activity_id = bp_activity_get_activity_id( array(
			'component'         => 'blogs',
			'type'              => 'new_blog_post',
			'item_id'           => get_current_blog_id(),
			'secondary_item_id' => $post_id
		) );

		$comment_id = $this->factory->comment->create( array(
			'user_id'              => $u,
			'comment_author_email' => $user->user_email,
			'comment_post_ID'      => $post_id,
		) );

		// Approve the comment
		$this->factory->comment->update_object( $comment_id, array( 'comment_approved' => 1 ) );

		$this->assertTrue( $this->activity_exists_for_post_type( $parent_activity_id, '', 'activity_comment', 'stream' ), 'Generated activity for comments about a post type registering using the bp_blogs_record_post_post_types filter having sync on should have a activity_comment action' );

		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );
	}

	public function filter_post_types( $post_types ) {
		$post_types = array_merge( $post_types, $this->custom_post_types );
		return $post_types;
	}

	protected function activity_exists_for_post_type( $item_id, $secondary_item_id, $action, $display_comments = false ) {
		$a = bp_activity_get( array(
			'display_comments'  => $display_comments,
			'filter'            => array(
				'action'        => $action,
				'primary_id'    => $item_id,
				'secondary_id'  => $secondary_item_id,
		) ) );

		return ! empty( $a['activities'] );
	}
}
