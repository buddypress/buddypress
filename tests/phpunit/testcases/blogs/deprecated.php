<?php
/**
 * @group blogs
 * @group deprecated
 * @link https://buddypress.trac.wordpress.org/ticket/6306
 */
class BP_Tests_Blogs_Deprecated extends BP_UnitTestCase {
	protected $activity_actions;
	protected $custom_post_types;

	public function set_up() {
		parent::set_up();

		$this->custom_post_types = array( 'using_old_filter' );

		register_post_type( 'using_old_filter', array(
			'label'   => 'using_old_filter',
			'public'   => true,
			'supports' => array( 'comments' ),
		) );

		add_filter( 'bp_blogs_record_post_post_types', array( $this, 'filter_post_types' ) );
		add_filter( 'bp_blogs_record_comment_post_types', array( $this, 'filter_post_types' ) );
	}

	function tear_down() {
		remove_filter( 'bp_blogs_record_post_post_types', array( $this, 'filter_post_types' ) );
		remove_filter( 'bp_blogs_record_comment_post_types', array( $this, 'filter_post_types' ) );
		_unregister_post_type( 'using_old_filter' );

		parent::tear_down();
	}

	/**
	 * @expectedDeprecated bp_blogs_record_post_post_types
	 * @expectedDeprecated bp_blogs_record_comment_post_types
	 */
	public function test_bp_activity_get_actions() {
		$activity_actions = bp_activity_get_actions();

		$this->assertTrue( ! isset( $activity_actions->activity->new_using_old_filter ), 'Post types registering using the bp_blogs_record_post_post_types filter should not have a specific action' );
	}

	/**
	 * @expectedDeprecated bp_blogs_record_post_post_types
	 * @expectedDeprecated bp_blogs_record_comment_post_types
	 */
	public function test_bp_activity_catch_transition_post_type_status() {
		$post_id = self::factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'using_old_filter',
		) );

		$this->assertTrue( $this->activity_exists_for_post_type( get_current_blog_id(), $post_id, 'new_blog_post' ), 'Generated activity for a post type registering using the bp_blogs_record_post_post_types filter should have a new_blog_post action' );
	}

	/**
	 * @expectedDeprecated bp_blogs_record_post_post_types
	 * @expectedDeprecated bp_blogs_record_comment_post_types
	 */
	public function test_bp_blogs_record_comment() {
		$u = self::factory()->user->create();
		$user = self::factory()->user->get_object_by_id( $u );

		$post_id = self::factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'using_old_filter',
			'post_author' => $u,
		) );

		$comment_id = self::factory()->comment->create( array(
			'user_id'              => $u,
			'comment_author_email' => $user->user_email,
			'comment_post_ID'      => $post_id,
		) );

		// Approve the comment
		self::factory()->comment->update_object( $comment_id, array( 'comment_approved' => 1 ) );

		$this->assertTrue( $this->activity_exists_for_post_type( get_current_blog_id(), $comment_id, 'new_blog_comment' ), 'Generated activity for comments about a post type registering using the bp_blogs_record_post_post_types filter should have a new_blog_comment action' );
	}

	/**
	 * @expectedDeprecated bp_blogs_record_post_post_types
	 * @expectedDeprecated bp_blogs_record_comment_post_types
	 */
	public function test_bp_blogs_record_comment_sync_activity_comment() {
		$u = self::factory()->user->create();
		$user = self::factory()->user->get_object_by_id( $u );

		add_filter( 'bp_disable_blogforum_comments', '__return_false' );

		$post_id = self::factory()->post->create( array(
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

		$comment_id = self::factory()->comment->create( array(
			'user_id'              => $u,
			'comment_author_email' => $user->user_email,
			'comment_post_ID'      => $post_id,
		) );

		// Approve the comment
		self::factory()->comment->update_object( $comment_id, array( 'comment_approved' => 1 ) );

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

/**
 * @group blogs
 * @group deprecated
 */
class BP_Tests_Blogs_Deprecated_Defaults extends BP_UnitTestCase {

	public function test_legacy_post_type_filter_defaults_without_named_callbacks() {
		$seen   = array();
		$record = static function ( $hook ) use ( &$seen ) {
			if ( in_array( $hook, array( 'bp_blogs_record_post_post_types', 'bp_blogs_record_comment_post_types' ), true ) ) {
				$seen[] = $hook;
			}
		};

		add_action( 'all', $record );
		try {
			$params = bp_blogs_register_post_tracking_args( new stdClass(), 'post' );
		} finally {
			remove_action( 'all', $record );
		}

		$this->assertSame( array(), $seen );
		$this->assertSame( 'new_blog_post', $params->action_id );
		$this->assertSame( 'new_blog_comment', $params->comments_tracking->action_id );
	}
}
