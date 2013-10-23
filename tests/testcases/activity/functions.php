<?php
/**
 * @group activity
 */
class BP_Tests_Activity_Functions extends BP_UnitTestCase {
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
	 * @ticket BP4488
	 */
	public function test_thumbnail_content_images() {
		// No images
		$post_content = 'foo bar';
		$this->assertEquals( bp_activity_thumbnail_content_images( $post_content ), 'foo bar' );

		// Image first, no caption. See #BP4488
		$post_content = '<img src="http://example.com/foo.jpg" alt="foo" width="40" height="40" class="alignnone size-full wp-image-236" /> foo bar';
		$this->assertEquals( bp_activity_thumbnail_content_images( $post_content ), '<img src="http://example.com/foo.jpg" width="40" height="40" alt="Thumbnail" class="align-left thumbnail" /> foo bar' );

		// Image first, caption. See #BP4488
		$post_content = '[caption id="attachment_236" align="alignnone" width="40"]<img src="http://example.com/foo.jpg" alt="FOO!" width="40" height="40" class="size-full wp-image-236" /> FOO![/caption] Awesome.';
		$this->assertEquals( bp_activity_thumbnail_content_images( $post_content ), '<img src="http://example.com/foo.jpg" width="40" height="40" alt="Thumbnail" class="align-left thumbnail" /> Awesome.' );
	}

	/**
	 * @group delete
	 */
	public function test_delete_activity_and_meta() {
		// create an activity update
		$parent_activity = $this->factory->activity->create( array(
			'type' => 'activity_update',
		) );

		// create some activity comments
		$comment_one = $this->factory->activity->create( array(
			'type'              => 'activity_comment',
			'item_id'           => $parent_activity,
			'secondary_item_id' => $parent_activity,
		) );

		$comment_two = $this->factory->activity->create( array(
			'type'              => 'activity_comment',
			'item_id'           => $parent_activity,
			'secondary_item_id' => $parent_activity,
		) );

		// add some meta to the activity items
		bp_activity_update_meta( $parent_activity, 'foo', 'bar' );
		bp_activity_update_meta( $comment_one,     'foo', 'bar' );
		bp_activity_update_meta( $comment_two,     'foo', 'bar' );

		// now delete the parent activity item
		// this should hopefully delete the associated comments and meta entries
		bp_activity_delete( array(
			'id' => $parent_activity
		) );

		// now fetch the deleted activity entries
		$get = bp_activity_get( array(
			'in'               => array( $parent_activity, $comment_one, $comment_two ),
			'display_comments' => 'stream'
		) );

		// activities should equal zero
		$this->assertEquals( 0, $get['total'] );

		// now fetch activity meta for the deleted activity entries
		$m1 = bp_activity_get_meta( $parent_activity );
		$m2 = bp_activity_get_meta( $comment_one );
		$m3 = bp_activity_get_meta( $comment_two );

		// test if activity meta entries still exist
		$this->assertEquals( false, $m1 );
		$this->assertEquals( false, $m2 );
		$this->assertEquals( false, $m3 );
	}

	/**
	 * @group bp_activity_update_meta
	 * @ticket BP5180
	 */
	public function test_bp_activity_update_meta_with_line_breaks() {
		$a = $this->factory->activity->create();
		$meta_value = 'Foo!


Bar!';
		bp_activity_update_meta( $a, 'linebreak_test', $meta_value );
		$this->assertEquals( $meta_value, bp_activity_get_meta( $a, 'linebreak_test' ) );
	}
}
