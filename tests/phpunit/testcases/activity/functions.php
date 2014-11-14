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
		$this->assertEmpty( $m1 );
		$this->assertEmpty( $m2 );
		$this->assertEmpty( $m3 );
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

	/**
	 * @group bp_activity_update_meta
	 * @ticket BP5083
	 */
	public function test_bp_activity_update_meta_with_0() {
		$a = $this->factory->activity->create();
		$meta_value = 0;

		bp_activity_update_meta( $a, '0_test', $meta_value );

		$this->assertNotSame( false, bp_activity_get_meta( $a, '0_test' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_update_meta
	 */
	public function test_bp_activity_update_meta_non_numeric_id() {
		$this->assertFalse( bp_activity_update_meta( 'foo', 'bar', 'baz' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_update_meta
	 * @ticket BP5399
	 */
	public function test_bp_activity_update_meta_with_illegal_key_characters() {
		$a = $this->factory->activity->create();
		$krazy_key = ' f!@#$%^o *(){}o?+';
		bp_activity_update_meta( $a, $krazy_key, 'bar' );

		$this->assertSame( '', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_update_meta
	 */
	public function test_bp_activity_update_meta_stripslashes() {
		$a = $this->factory->activity->create();
		$value = "This string is totally slashin\'!";
		bp_activity_update_meta( $a, 'foo', $value );

		$this->assertSame( stripslashes( $value ), bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_update_meta
	 */
	public function test_bp_activity_update_meta_false_value_deletes() {
		$a = $this->factory->activity->create();
		bp_activity_update_meta( $a, 'foo', false );
		$this->assertSame( '', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_update_meta
	 */
	public function test_bp_activity_update_meta_new() {
		$a = $this->factory->activity->create();
		$this->assertSame( '', bp_activity_get_meta( $a, 'foo' ), '"foo" meta should be empty for this activity item.' );
		$this->assertNotEmpty( bp_activity_update_meta( $a, 'foo', 'bar' ) );
		$this->assertSame( 'bar', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_update_meta
	 */
	public function test_bp_activity_update_meta_existing() {
		$a = $this->factory->activity->create();
		bp_activity_update_meta( $a, 'foo', 'bar' );
		$this->assertSame( 'bar', bp_activity_get_meta( $a, 'foo' ) );
		$this->assertTrue( bp_activity_update_meta( $a, 'foo', 'baz' ) );
		$this->assertSame( 'baz', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_update_meta
	 */
	public function test_bp_activity_update_meta_same_value() {
		$a = $this->factory->activity->create();
		bp_activity_update_meta( $a, 'foo', 'bar' );
		$this->assertSame( 'bar', bp_activity_get_meta( $a, 'foo' ) );
		$this->assertFalse( bp_activity_update_meta( $a, 'foo', 'bar' ) );
		$this->assertSame( 'bar', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_update_meta
	 */
	public function test_bp_activity_update_meta_prev_value() {
		$a = $this->factory->activity->create();
		bp_activity_add_meta( $a, 'foo', 'bar' );

		// In earlier versions of WordPress, bp_activity_update_meta()
		// returns true even on failure. However, we know that in these
		// cases the update is failing as expected, so we skip this
		// assertion just to keep our tests passing
		// See https://core.trac.wordpress.org/ticket/24933
		if ( version_compare( $GLOBALS['wp_version'], '3.7', '>=' ) ) {
			$this->assertFalse( bp_activity_update_meta( $a, 'foo', 'bar2', 'baz' ) );
		}

		$this->assertTrue( bp_activity_update_meta( $a, 'foo', 'bar2', 'bar' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_get_meta
	 */
	public function test_bp_activity_get_meta_empty_activity_id() {
		$this->assertFalse( bp_activity_get_meta( 0 ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_get_meta
	 */
	public function test_bp_activity_get_meta_non_numeric_activity_id() {
		$this->assertFalse( bp_activity_get_meta( 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_get_meta
	 * @ticket BP5399
	 */
	public function test_bp_activity_get_meta_with_illegal_characters() {
		$a = $this->factory->activity->create();
		bp_activity_update_meta( $a, 'foo', 'bar' );

		$krazy_key = ' f!@#$%^o *(){}o?+';
		$this->assertSame( '', bp_activity_get_meta( $a, $krazy_key ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_get_meta
	 */
	public function test_bp_activity_get_meta_multiple() {
		$a = $this->factory->activity->create();
		bp_activity_update_meta( $a, 'foo', 'bar' );
		bp_activity_update_meta( $a, 'foo1', 'bar1' );

		$expected = array(
			'foo' => array(
				'bar',
			),
			'foo1' => array(
				'bar1',
			),
		);

		$this->assertEquals( $expected, bp_activity_get_meta( $a ) );
	}

	/**
	 * @group bp_activity_get_meta
	 * @group activitymeta
	 * @ticket BP5399
	 */
	public function test_bp_activity_get_meta_no_results_returns_false() {
		$a = $this->factory->activity->create();

		$this->assertSame( '', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_get_meta
	 */
	public function test_bp_activity_get_meta_single_true() {
		$a = $this->factory->activity->create();
		bp_activity_add_meta( $a, 'foo', 'bar' );
		bp_activity_add_meta( $a, 'foo', 'baz' );
		$this->assertSame( 'bar', bp_activity_get_meta( $a, 'foo' ) ); // default is true
		$this->assertSame( 'bar', bp_activity_get_meta( $a, 'foo', true ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_get_meta
	 */
	public function test_bp_activity_get_meta_single_false() {
		$a = $this->factory->activity->create();
		bp_activity_add_meta( $a, 'foo', 'bar' );
		bp_activity_add_meta( $a, 'foo', 'baz' );
		$this->assertSame( array( 'bar', 'baz' ), bp_activity_get_meta( $a, 'foo', false ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_get_meta
	 * @group cache
	 */
	public function test_bp_activity_get_meta_cache_all_on_get() {
		$a = $this->factory->activity->create();
		bp_activity_add_meta( $a, 'foo', 'bar' );
		bp_activity_add_meta( $a, 'foo1', 'baz' );
		$this->assertFalse( wp_cache_get( $a, 'activity_meta' ) );

		// A single query should prime the whole meta cache
		bp_activity_get_meta( $a, 'foo' );

		$c = wp_cache_get( $a, 'activity_meta' );
		$this->assertNotEmpty( $c['foo1'] );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_delete_meta
	 */
	public function test_bp_activity_delete_meta_non_numeric_activity_id() {
		$this->assertFalse( bp_activity_delete_meta( 'foo', 'bar' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_delete_meta
	 * @ticket BP5399
	 */
	public function test_bp_activity_delete_meta_trim_meta_value() {
		$a = $this->factory->activity->create();
		bp_activity_update_meta( $a, 'foo', 'bar' );
		bp_activity_delete_meta( $a, 'foo', ' bar ' );
		$this->assertSame( 'bar', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_delete_meta
	 */
	public function test_bp_activity_delete_meta_single() {
		$a = $this->factory->activity->create();
		bp_activity_update_meta( $a, 'foo', 'bar' );
		$this->assertTrue( bp_activity_delete_meta( $a, 'foo', 'bar' ) );
		$this->assertSame( '', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_delete_meta
	 */
	public function test_bp_activity_delete_meta_all_for_activity() {
		$a = $this->factory->activity->create();
		bp_activity_update_meta( $a, 'foo', 'bar' );
		bp_activity_update_meta( $a, 'foo1', 'bar1' );
		$this->assertTrue( bp_activity_delete_meta( $a ) );
		$this->assertSame( '', bp_activity_get_meta( $a, 'foo' ) );
		$this->assertSame( '', bp_activity_get_meta( $a, 'foo1' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_delete_meta
	 */
	public function test_bp_activity_delete_meta_with_meta_value() {
		$a = $this->factory->activity->create();
		bp_activity_update_meta( $a, 'foo', 'bar' );
		$this->assertTrue( bp_activity_delete_meta( $a, 'foo', 'bar' ) );
		$this->assertSame( '', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_delete_meta
	 */
	public function test_bp_activity_delete_meta_with_delete_all_but_no_meta_key() {
		// With no meta key, don't delete for all items - just delete
		// all for a single item
		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create();
		bp_activity_update_meta( $a1, 'foo', 'bar' );
		bp_activity_update_meta( $a1, 'foo1', 'bar1' );
		bp_activity_update_meta( $a2, 'foo', 'bar' );
		bp_activity_update_meta( $a2, 'foo1', 'bar1' );

		$this->assertTrue( bp_activity_delete_meta( $a1, '', '', true ) );
		$this->assertEmpty( bp_activity_get_meta( $a1 ) );
		$this->assertSame( 'bar', bp_activity_get_meta( $a2, 'foo' ) );
		$this->assertSame( 'bar1', bp_activity_get_meta( $a2, 'foo1' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_delete_meta
	 */
	public function test_bp_activity_delete_meta_with_delete_all() {
		// With no meta key, don't delete for all items - just delete
		// all for a single item
		$a1 = $this->factory->activity->create();
		$a2 = $this->factory->activity->create();
		bp_activity_update_meta( $a1, 'foo', 'bar' );
		bp_activity_update_meta( $a1, 'foo1', 'bar1' );
		bp_activity_update_meta( $a2, 'foo', 'bar' );
		bp_activity_update_meta( $a2, 'foo1', 'bar1' );

		$this->assertTrue( bp_activity_delete_meta( $a1, 'foo', '', true ) );
		$this->assertSame( '', bp_activity_get_meta( $a1, 'foo' ) );
		$this->assertSame( '', bp_activity_get_meta( $a2, 'foo' ) );
		$this->assertSame( 'bar1', bp_activity_get_meta( $a1, 'foo1' ) );
		$this->assertSame( 'bar1', bp_activity_get_meta( $a2, 'foo1' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_add_meta
	 */
	public function test_bp_activity_add_meta_no_meta_key() {
		$this->assertFalse( bp_activity_add_meta( 1, '', 'bar' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_add_meta
	 */
	public function test_bp_activity_add_meta_empty_object_id() {
		$this->assertFalse( bp_activity_add_meta( 0, 'foo', 'bar' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_add_meta
	 */
	public function test_bp_activity_add_meta_existing_unique() {
		$a = $this->factory->activity->create();
		bp_activity_add_meta( $a, 'foo', 'bar' );
		$this->assertFalse( bp_activity_add_meta( $a, 'foo', 'baz', true ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_add_meta
	 */
	public function test_bp_activity_add_meta_existing_not_unique() {
		$a = $this->factory->activity->create();
		bp_activity_add_meta( $a, 'foo', 'bar' );
		$this->assertNotEmpty( bp_activity_add_meta( $a, 'foo', 'baz' ) );
	}

	/**
	 * @group bp_activity_get_user_mentionname
	 */
	public function test_bp_activity_get_user_mentionname_compatibilitymode_off() {
		add_filter( 'bp_is_username_compatibility_mode', '__return_false' );

		$u = $this->factory->user->create( array(
			'user_login' => 'foo bar baz',
			'user_nicename' => 'foo-bar-baz',
		) );

		$this->assertEquals( 'foo-bar-baz', bp_activity_get_user_mentionname( $u ) );

		remove_filter( 'bp_is_username_compatibility_mode', '__return_false' );
	}

	/**
	 * @group bp_activity_get_user_mentionname
	 */
	public function test_bp_activity_get_user_mentionname_compatibilitymode_on() {
		add_filter( 'bp_is_username_compatibility_mode', '__return_true' );

		$u1 = $this->factory->user->create( array(
			'user_login' => 'foo bar baz',
			'user_nicename' => 'foo-bar-baz',
		) );

		$u2 = $this->factory->user->create( array(
			'user_login' => 'foo.bar.baz',
			'user_nicename' => 'foo-bar-baz',
		) );

		$this->assertEquals( 'foo-bar-baz', bp_activity_get_user_mentionname( $u1 ) );
		$this->assertEquals( 'foo.bar.baz', bp_activity_get_user_mentionname( $u2 ) );

		remove_filter( 'bp_is_username_compatibility_mode', '__return_true' );
	}

	/**
	 * @group bp_activity_get_userid_from_mentionname
	 */
	public function test_bp_activity_get_userid_from_mentionname_compatibilitymode_off() {
		add_filter( 'bp_is_username_compatibility_mode', '__return_false' );

		$u = $this->factory->user->create( array(
			'user_login' => 'foo bar baz',
			'user_nicename' => 'foo-bar-baz',
		) );

		$this->assertEquals( $u, bp_activity_get_userid_from_mentionname( 'foo-bar-baz' ) );

		remove_filter( 'bp_is_username_compatibility_mode', '__return_false' );
	}

	/**
	 * @group bp_activity_get_userid_from_mentionname
	 */
	public function test_bp_activity_get_userid_from_mentionname_compatibilitymode_on() {
		add_filter( 'bp_is_username_compatibility_mode', '__return_true' );

		// all spaces are hyphens
		$u1 = $this->factory->user->create( array(
			'user_login' => 'foo bar baz',
			'user_nicename' => 'foobarbaz',
		) );

		// no spaces are hyphens
		$u2 = $this->factory->user->create( array(
			'user_login' => 'foo-bar-baz-1',
			'user_nicename' => 'foobarbaz-1',
		) );

		// some spaces are hyphens
		$u3 = $this->factory->user->create( array(
			'user_login' => 'foo bar-baz 2',
			'user_nicename' => 'foobarbaz-2',
		) );

		$u4 = $this->factory->user->create( array(
			'user_login' => 'foo.bar.baz',
			'user_nicename' => 'foo-bar-baz',
		) );

		$this->assertEquals( $u1, bp_activity_get_userid_from_mentionname( 'foo-bar-baz' ) );
		$this->assertEquals( $u2, bp_activity_get_userid_from_mentionname( 'foo-bar-baz-1' ) );
		$this->assertEquals( $u3, bp_activity_get_userid_from_mentionname( 'foo-bar-baz-2' ) );
		$this->assertEquals( $u4, bp_activity_get_userid_from_mentionname( 'foo.bar.baz' ) );

		remove_filter( 'bp_is_username_compatibility_mode', '__return_true' );
	}

	/**
	 * @group activity_action
	 * @group bp_activity_format_activity_action_activity_update
	 */
	public function test_bp_activity_format_activity_action_activity_update() {
		$u = $this->factory->user->create();
		$a = $this->factory->activity->create( array(
			'component' => buddypress()->activity->id,
			'type' => 'activity_update',
			'user_id' => $u,
		) );

		$a_obj = new BP_Activity_Activity( $a );

		$expected = sprintf( '%s posted an update', bp_core_get_userlink( $u ) );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_activity_format_activity_action_activity_comment
	 */
	public function test_bp_activity_format_activity_action_activity_comment() {
		$u = $this->factory->user->create();
		$a = $this->factory->activity->create( array(
			'component' => buddypress()->activity->id,
			'type' => 'activity_comment',
			'user_id' => $u,
		) );

		$a_obj = new BP_Activity_Activity( $a );

		$expected = sprintf( '%s posted a new activity comment', bp_core_get_userlink( $u ) );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group bp_activity_new_comment
	 * @group cache
	 */
	public function test_bp_activity_new_comment_clear_comment_caches() {
		$a1 = $this->factory->activity->create();
		$a2 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a1,
			'content' => 'foo',
			'user_id' => 1,
		) );
		$a3 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a2,
			'content' => 'foo',
			'user_id' => 1,
		) );
		$a4 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a3,
			'content' => 'foo',
			'user_id' => 1,
		) );
		$a5 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a3,
			'content' => 'foo',
			'user_id' => 1,
		) );

		// prime caches
		bp_activity_get( array(
			'in' => array( $a1 ),
			'display_comments' => 'threaded',
		) );

		// should be populated
		$this->assertNotEmpty( wp_cache_get( $a1, 'bp_activity_comments' ) );

		bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a4,
			'content' => 'foo',
			'user_id' => 1,
		) );

		// should be empty
		$this->assertFalse( wp_cache_get( $a1, 'bp_activity_comments' ) );
	}

	/**
	 * @group bp_activity_new_comment
	 * @group cache
	 */
	public function test_bp_activity_new_comment_clear_activity_caches() {
		$a1 = $this->factory->activity->create();
		$a2 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a1,
			'content' => 'foo',
			'user_id' => 1,
		) );
		$a3 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a2,
			'content' => 'foo',
			'user_id' => 1,
		) );
		$a4 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a3,
			'content' => 'foo',
			'user_id' => 1,
		) );
		$a5 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a3,
			'content' => 'foo',
			'user_id' => 1,
		) );

		// prime caches
		bp_activity_get( array(
			'in' => array( $a1 ),
			'display_comments' => 'threaded',
		) );

		// should be populated
		$this->assertNotEmpty( wp_cache_get( $a1, 'bp_activity' ) );
		$this->assertNotEmpty( wp_cache_get( $a2, 'bp_activity' ) );
		$this->assertNotEmpty( wp_cache_get( $a3, 'bp_activity' ) );
		$this->assertNotEmpty( wp_cache_get( $a4, 'bp_activity' ) );
		$this->assertNotEmpty( wp_cache_get( $a5, 'bp_activity' ) );

		// Stuff may run on bp_activity_comment_posted that loads the
		// cache, so we use this dumb technique to check cache values
		// before any of that stuff gets a chance to run. WordPress
		// sure is neat sometimes
		$this->acaches = array(
			$a1 => '',
			$a2 => '',
			$a3 => '',
			$a4 => '',
		);
		add_action( 'bp_activity_comment_posted', array( $this, 'check_activity_caches' ), 0 );

		bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a4,
			'content' => 'foo',
			'user_id' => 1,
		) );

		// should be empty
		foreach ( $this->acaches as $k => $v ) {
			$this->assertFalse( $v, "Cache should be false for $k" );
		}
	}

	/**
	 * @group bp_activity_delete_comment
	 * @group cache
	 */
	public function test_bp_activity_delete_comment_clear_cache() {
		// add new activity update and comment to this update
		$a1 = $this->factory->activity->create();
		$a2 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a1,
			'content' => 'foo',
			'user_id' => 1,
		) );

		// prime cache
		bp_activity_get( array(
			'in' => array( $a1 ),
			'display_comments' => 'threaded',
		) );

		// delete activity comment
		bp_activity_delete_comment( $a1, $a2 );

		// assert comment cache as empty for $a1
		$this->assertEmpty( wp_cache_get( $a1, 'bp_activity_comments' ) );
	}

	/**
	 * @group bp_activity_new_comment
	 * @group BP5907
	 */
	public function test_bp_activity_comment_on_deleted_activity() {
		$a = $this->factory->activity->create();

		bp_activity_delete_by_activity_id( $a );

		$c = bp_activity_new_comment( array(
			'activity_id' => $a,
			'parent_id' => $a,
			'content' => 'foo',
			'user_id' => 1,
		) );

		$this->assertEmpty( $c );
	}

	/**
	 * @group favorites
	 * @group bp_activity_add_user_favorite
	 */
	public function test_add_user_favorite_already_favorited() {
		$u = $this->factory->user->create();
		$a = $this->factory->activity->create();

		$this->assertTrue( bp_activity_add_user_favorite( $a, $u ) );

		$this->assertFalse( bp_activity_add_user_favorite( $a, $u ) );
		$this->assertSame( array( $a ), bp_activity_get_user_favorites( $u ) );
		$this->assertEquals( 1, bp_activity_get_meta( $a, 'favorite_count' ) );
	}

	/**
	 * @group favorites
	 * @group bp_activity_add_user_favorite
	 */
	public function test_add_user_favorite_not_yet_favorited() {
		$u = $this->factory->user->create();
		$a = $this->factory->activity->create();
		$this->assertTrue( bp_activity_add_user_favorite( $a, $u ) );
	}

	/**
	 * @group favorites
	 * @group bp_activity_remove_user_favorite
	 */
	public function test_remove_user_favorite_bad_activity_id() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$a = $this->factory->activity->create();

		// Only favorite for user 1
		bp_activity_add_user_favorite( $a, $u1 );

		// Removing for user 2 should fail
		$this->assertFalse( bp_activity_remove_user_favorite( $a, $u2 ) );
		$this->assertEquals( 1, bp_activity_get_meta( $a, 'favorite_count' ) );
	}

	/**
	 * @group bp_activity_post_update
	 */
	public function test_bp_activity_post_update_empty_content() {
		$this->assertFalse( bp_activity_post_update( array( 'user_id' => 3, ) ) );
	}

	/**
	 * @group bp_activity_post_update
	 */
	public function test_bp_activity_post_update_inactive_user() {
		$this->assertFalse( bp_activity_post_update( array(
			'user_id' => 3456,
			'content' => 'foo',
		) ) );
	}

	/**
	 * @group bp_activity_post_update
	 */
	public function test_bp_activity_post_update_success() {
		$u = $this->factory->user->create();

		$a = bp_activity_post_update( array(
			'user_id' => $u,
			'content' => 'foo',
		) );

		$this->assertNotEmpty( $a );
	}

	/**
	 * @group bp_activity_get_activity_id
	 */
	public function test_bp_activity_get_activity_id() {
		$args = array(
			'user_id' => 5,
			'component' => 'foo',
			'type' => 'bar',
			'item_id' => 12,
			'secondary_item_id' => 44,
		);

		$a = $this->factory->activity->create( $args );

		$this->assertEquals( $a, bp_activity_get_activity_id( $args ) );
	}

	/**
	 * @group bp_activity_delete_by_item_id
	 */
	public function test_bp_activity_delete_by_item_id() {
		$args = array(
			'user_id' => 5,
			'component' => 'foo',
			'type' => 'bar',
			'item_id' => 12,
			'secondary_item_id' => 44,
		);

		$a = $this->factory->activity->create( $args );

		$this->assertTrue( bp_activity_delete_by_item_id( $args ) );

		$found = bp_activity_get_specific( array(
			'activity_ids' => array( $a ),
		) );

		$this->assertSame( array(), $found['activities'] );
	}

	public function check_activity_caches() {
		foreach ( $this->acaches as $k => $v ) {
			$this->acaches[ $k ] = wp_cache_get( $k, 'bp_activity' );
		}
	}
}
