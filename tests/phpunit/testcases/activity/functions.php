<?php
/**
 * @group activity
 */
class BP_Tests_Activity_Functions extends BP_UnitTestCase {

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
	public function test_delete_activity_by_id() {

		// create an activity update
		$activity = self::factory()->activity->create( array(
			'type' => 'activity_update'
		) );

		// now delete the activity item
		bp_activity_delete( array(
			'id' => $activity
		) );

		// now fetch the deleted activity entries
		$get = bp_activity_get( array(
			'id' => $activity
		) );

		// activities should equal zero
		$this->assertEquals( 0, $get['total'] );
	}

	/**
	 * @group delete
	 */
	public function test_delete_activity_by_type() {

		// Setup criteria
		$criteria = array(
			'type' => 'activity_update'
		);

		// create an activity update
		self::factory()->activity->create( $criteria );
		self::factory()->activity->create( $criteria );
		self::factory()->activity->create( $criteria );
		self::factory()->activity->create( $criteria );

		// now delete the activity items
		bp_activity_delete( $criteria );

		// now fetch the deleted activity entries
		$get = bp_activity_get( $criteria );

		// activities should equal zero
		$this->assertEquals( 0, $get['total'] );
	}

	/**
	 * @group delete
	 */
	public function test_delete_activity_by_component() {

		// Setup criteria
		$criteria = array(
			'component' => 'xprofile'
		);

		// create an activity update
		self::factory()->activity->create( $criteria );
		self::factory()->activity->create( $criteria );
		self::factory()->activity->create( $criteria );
		self::factory()->activity->create( $criteria );

		// now delete the activity items
		bp_activity_delete( $criteria );

		// now fetch the deleted activity entries
		$get = bp_activity_get( $criteria );

		// activities should equal zero
		$this->assertEquals( 0, $get['total'] );
	}

	/**
	 * @group delete
	 */
	public function test_delete_activity_by_user_id() {

		// Setup criteria
		$criteria = array(
			'user_id' => '1'
		);

		// create an activity update
		self::factory()->activity->create( $criteria );
		self::factory()->activity->create( $criteria );
		self::factory()->activity->create( $criteria );
		self::factory()->activity->create( $criteria );

		// now delete the activity items
		bp_activity_delete( $criteria );

		// now fetch the deleted activity entries
		$get = bp_activity_get( $criteria );

		// activities should equal zero
		$this->assertEquals( 0, $get['total'] );
	}

	/**
	 * @group delete
	 */
	public function test_delete_activity_meta() {

		// create an activity update
		$activity = self::factory()->activity->create( array(
			'type' => 'activity_update'
		) );

		// add some meta to the activity items
		bp_activity_update_meta( $activity, 'foo', 'bar' );

		// now delete the parent activity item meta entry
		bp_activity_delete_meta(  $activity, 'foo', 'bar' );

		// now fetch activity meta for the deleted activity entries
		$m1 = bp_activity_get_meta( $activity );

		// test if activity meta entries still exist
		$this->assertEmpty( $m1 );
	}

	/**
	 * @group delete
	 */
	public function test_delete_activity_all_meta() {

		// create an activity update
		$activity = self::factory()->activity->create( array(
			'type' => 'activity_update'
		) );

		// add some meta to the activity items
		bp_activity_update_meta( $activity, 'foo1', 'bar' );
		bp_activity_update_meta( $activity, 'foo2', 'bar' );
		bp_activity_update_meta( $activity, 'foo3', 'bar' );
		bp_activity_update_meta( $activity, 'foo4', 'bar' );
		bp_activity_update_meta( $activity, 'foo5', 'bar' );

		// now delete the parent activity item meta entry
		bp_activity_delete_meta( $activity );

		// now fetch activity meta for the deleted activity entries
		$m1 = bp_activity_get_meta( $activity );
		$m2 = bp_activity_get_meta( $activity );
		$m3 = bp_activity_get_meta( $activity );
		$m4 = bp_activity_get_meta( $activity );
		$m5 = bp_activity_get_meta( $activity );

		// test if activity meta entries still exist
		$this->assertEmpty( $m1 );
		$this->assertEmpty( $m2 );
		$this->assertEmpty( $m3 );
		$this->assertEmpty( $m4 );
		$this->assertEmpty( $m5 );
	}

	/**
	 * @group delete
	 */
	public function test_delete_activity_and_comments() {

		// create an activity update
		$parent_activity = self::factory()->activity->create( array(
			'type' => 'activity_update',
		) );

		// create some activity comments
		$comment_one = self::factory()->activity->create( array(
			'type'              => 'activity_comment',
			'item_id'           => $parent_activity,
			'secondary_item_id' => $parent_activity,
		) );

		$comment_two = self::factory()->activity->create( array(
			'type'              => 'activity_comment',
			'item_id'           => $parent_activity,
			'secondary_item_id' => $parent_activity,
		) );

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
	}

	/**
	 * @group delete
	 */
	public function test_delete_activity_meta_for_comments() {

		// create an activity update
		$parent_activity = self::factory()->activity->create( array(
			'type' => 'activity_update',
		) );

		// create some activity comments
		$comment_one = self::factory()->activity->create( array(
			'type'              => 'activity_comment',
			'item_id'           => $parent_activity,
			'secondary_item_id' => $parent_activity,
		) );

		$comment_two = self::factory()->activity->create( array(
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
		$a = self::factory()->activity->create();
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
		$a = self::factory()->activity->create();
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
		$a = self::factory()->activity->create();
		$krazy_key = ' f!@#$%^o *(){}o?+';
		bp_activity_update_meta( $a, $krazy_key, 'bar' );

		$this->assertSame( '', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_update_meta
	 */
	public function test_bp_activity_update_meta_stripslashes() {
		$a = self::factory()->activity->create();
		$value = "This string is totally slashin\'!";
		bp_activity_update_meta( $a, 'foo', $value );

		$this->assertSame( stripslashes( $value ), bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_update_meta
	 */
	public function test_bp_activity_update_meta_false_value_deletes() {
		$a = self::factory()->activity->create();
		bp_activity_update_meta( $a, 'foo', false );
		$this->assertSame( '', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_update_meta
	 */
	public function test_bp_activity_update_meta_new() {
		$a = self::factory()->activity->create();
		$this->assertSame( '', bp_activity_get_meta( $a, 'foo' ), '"foo" meta should be empty for this activity item.' );
		$this->assertNotEmpty( bp_activity_update_meta( $a, 'foo', 'bar' ) );
		$this->assertSame( 'bar', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_update_meta
	 */
	public function test_bp_activity_update_meta_existing() {
		$a = self::factory()->activity->create();
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
		$a = self::factory()->activity->create();
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
		$a = self::factory()->activity->create();
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
		$a = self::factory()->activity->create();
		bp_activity_update_meta( $a, 'foo', 'bar' );

		$krazy_key = ' f!@#$%^o *(){}o?+';
		$this->assertSame( '', bp_activity_get_meta( $a, $krazy_key ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_get_meta
	 */
	public function test_bp_activity_get_meta_multiple() {
		$a = self::factory()->activity->create();
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
		$a = self::factory()->activity->create();

		$this->assertSame( '', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_get_meta
	 */
	public function test_bp_activity_get_meta_single_true() {
		$a = self::factory()->activity->create();
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
		$a = self::factory()->activity->create();
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
		$a = self::factory()->activity->create();
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
		$a = self::factory()->activity->create();
		bp_activity_update_meta( $a, 'foo', 'bar' );
		bp_activity_delete_meta( $a, 'foo', ' bar ' );
		$this->assertSame( 'bar', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_delete_meta
	 */
	public function test_bp_activity_delete_meta_single() {
		$a = self::factory()->activity->create();
		bp_activity_update_meta( $a, 'foo', 'bar' );
		$this->assertTrue( bp_activity_delete_meta( $a, 'foo', 'bar' ) );
		$this->assertSame( '', bp_activity_get_meta( $a, 'foo' ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_delete_meta
	 */
	public function test_bp_activity_delete_meta_all_for_activity() {
		$a = self::factory()->activity->create();
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
		$a = self::factory()->activity->create();
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
		$a1 = self::factory()->activity->create();
		$a2 = self::factory()->activity->create();
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
		$a1 = self::factory()->activity->create();
		$a2 = self::factory()->activity->create();
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
		$a = self::factory()->activity->create();
		bp_activity_add_meta( $a, 'foo', 'bar' );
		$this->assertFalse( bp_activity_add_meta( $a, 'foo', 'baz', true ) );
	}

	/**
	 * @group activitymeta
	 * @group bp_activity_add_meta
	 */
	public function test_bp_activity_add_meta_existing_not_unique() {
		$a = self::factory()->activity->create();
		bp_activity_add_meta( $a, 'foo', 'bar' );
		$this->assertNotEmpty( bp_activity_add_meta( $a, 'foo', 'baz' ) );
	}

	/**
	 * @group bp_activity_get_user_mentionname
	 */
	public function test_bp_activity_get_user_mentionname_compatibilitymode_off() {
		add_filter( 'bp_is_username_compatibility_mode', '__return_false' );

		$u = self::factory()->user->create( array(
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

		$u1 = self::factory()->user->create( array(
			'user_login' => 'foo bar baz',
			'user_nicename' => 'foo-bar-baz',
		) );

		$u2 = self::factory()->user->create( array(
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

		$u = self::factory()->user->create( array(
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
		$u1 = self::factory()->user->create( array(
			'user_login' => 'foo bar baz',
			'user_nicename' => 'foobarbaz',
		) );

		// no spaces are hyphens
		$u2 = self::factory()->user->create( array(
			'user_login' => 'foo-bar-baz-1',
			'user_nicename' => 'foobarbaz-1',
		) );

		// some spaces are hyphens
		$u3 = self::factory()->user->create( array(
			'user_login' => 'foo bar-baz 2',
			'user_nicename' => 'foobarbaz-2',
		) );

		$u4 = self::factory()->user->create( array(
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
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create( array(
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
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create( array(
			'component' => buddypress()->activity->id,
			'type' => 'activity_comment',
			'user_id' => $u,
		) );

		$a_obj = new BP_Activity_Activity( $a );

		$expected = sprintf( '%s posted a new activity comment', bp_core_get_userlink( $u ) );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_activity_format_activity_action_custom_post_type_post
	 * @group activity_tracking
	 */
	public function test_bp_activity_format_activity_action_custom_post_type_post_nonms() {
		if ( is_multisite() ) {
			return;
		}

		register_post_type( 'foo', array(
			'label'   => 'foo',
			'public'   => true,
			'supports' => array( 'buddypress-activity' ),
		) );

		// Build the actions to fetch the tracking args
		bp_activity_get_actions();

		$u = self::factory()->user->create();
		$p = self::factory()->post->create( array(
			'post_author' => $u,
			'post_type'   => 'foo',
		) );

		$a = self::factory()->activity->create( array(
			'component'         => 'activity',
			'type'              => 'new_foo',
			'user_id'           => $u,
			'item_id'           => 1,
			'secondary_item_id' => $p,
		) );

		$a_obj = new BP_Activity_Activity( $a );

		$user_link = bp_core_get_userlink( $u );
		$blog_url = get_home_url();
		$post_url = add_query_arg( 'p', $p, trailingslashit( $blog_url ) );
		$post_link = '<a href="' . $post_url . '">item</a>';

		$expected = sprintf( '%s wrote a new %s', $user_link, $post_link );

		$this->assertSame( $expected, $a_obj->action );

		_unregister_post_type( 'foo' );
	}

	/**
	 * @group activity_action
	 * @group bp_activity_format_activity_action_custom_post_type_post_ms
	 * @group activity_tracking
	 */
	public function test_bp_activity_format_activity_action_custom_post_type_post_ms() {
		if ( ! is_multisite() ) {
			return;
		}

		$b = self::factory()->blog->create();
		$u = self::factory()->user->create();

		switch_to_blog( $b );

		register_post_type( 'foo', array(
			'label'   => 'foo',
			'public'   => true,
			'supports' => array( 'buddypress-activity' ),
		) );

		// Build the actions to fetch the tracking args
		bp_activity_get_actions();

		$p = self::factory()->post->create( array(
			'post_author' => $u,
			'post_type'   => 'foo',
		) );

		$activity_args = array(
			'component'         => 'activity',
			'type'              => 'new_foo',
			'user_id'           => $u,
			'item_id'           => $b,
			'secondary_item_id' => $p,
		);

		_unregister_post_type( 'foo' );
		bp_activity_get_actions();

		restore_current_blog();

		$a = self::factory()->activity->create( $activity_args );

		$a_obj = new BP_Activity_Activity( $a );

		$user_link = bp_core_get_userlink( $u );
		$blog_url = get_blog_option( $a_obj->item_id, 'home' );
		$post_url = add_query_arg( 'p', $p, trailingslashit( $blog_url ) );

		$post_link = '<a href="' . $post_url . '">item</a>';

		$expected = sprintf( '%s wrote a new %s, on the site %s', $user_link, $post_link, '<a href="' . $blog_url . '">' . get_blog_option( $a_obj->item_id, 'blogname' ) . '</a>' );

		$this->assertSame( $expected, $a_obj->action );
	}


	/**
	 * @group activity_action
	 * @group bp_activity_format_activity_action_custom_post_type_post
	 */
	public function test_bp_activity_format_activity_action_custom_string_post_type_post_nonms() {
		if ( is_multisite() ) {
			return;
		}

		$labels = array(
			'name'                 => 'bars',
			'singular_name'        => 'bar',
			'bp_activity_new_post' => '%1$s shared a new <a href="%2$s">bar</a>',
		);

		register_post_type( 'foo', array(
			'labels'      => $labels,
			'public'      => true,
			'supports'    => array( 'buddypress-activity' ),
			'bp_activity' => array(
				'action_id' => 'foo_bar',
			),
		) );

		// Build the actions to fetch the tracking args
		bp_activity_get_actions();

		$u = self::factory()->user->create();
		$p = self::factory()->post->create( array(
			'post_author' => $u,
			'post_type'   => 'foo',
		) );

		$a = self::factory()->activity->create( array(
			'component'         => 'activity',
			'type'              => 'foo_bar',
			'user_id'           => $u,
			'item_id'           => 1,
			'secondary_item_id' => $p,
		) );

		$a_obj = new BP_Activity_Activity( $a );

		$user_link = bp_core_get_userlink( $u );
		$blog_url = get_home_url();
		$post_url = add_query_arg( 'p', $p, trailingslashit( $blog_url ) );

		$expected = sprintf( '%1$s shared a new <a href="%2$s">bar</a>', $user_link, $post_url );

		$this->assertSame( $expected, $a_obj->action );

		_unregister_post_type( 'foo' );
	}

	/**
	 * @group activity_action
	 * @group bp_activity_format_activity_action_custom_post_type_post_ms
	 * @group activity_tracking
	 */
	public function test_bp_activity_format_activity_action_custom_string_post_type_post_ms() {
		if ( ! is_multisite() ) {
			return;
		}

		$b = self::factory()->blog->create();
		$u = self::factory()->user->create();

		switch_to_blog( $b );

		$labels = array(
			'name'                    => 'bars',
			'singular_name'           => 'bar',
			'bp_activity_new_post_ms' => '%1$s shared a new <a href="%2$s">bar</a>, on the site %3$s',
		);

		register_post_type( 'foo', array(
			'labels'   => $labels,
			'public'   => true,
			'supports' => array( 'buddypress-activity' ),
		) );

		// Build the actions to fetch the tracking args
		bp_activity_get_actions();

		$p = self::factory()->post->create( array(
			'post_author' => $u,
			'post_type'   => 'foo',
		) );

		$activity_args = array(
			'component'         => 'activity',
			'type'              => 'new_foo',
			'user_id'           => $u,
			'item_id'           => $b,
			'secondary_item_id' => $p,
		);

		_unregister_post_type( 'foo' );

		restore_current_blog();

		$a = self::factory()->activity->create( $activity_args );

		$a_obj = new BP_Activity_Activity( $a );

		$user_link = bp_core_get_userlink( $u );
		$blog_url = get_blog_option( $a_obj->item_id, 'home' );
		$post_url = add_query_arg( 'p', $p, trailingslashit( $blog_url ) );

		$expected = sprintf( '%1$s shared a new <a href="%2$s">bar</a>, on the site %3$s', $user_link, $post_url, '<a href="' . $blog_url . '">' . get_blog_option( $a_obj->item_id, 'blogname' ) . '</a>' );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group bp_activity_set_post_type_tracking_args
	 * @group activity_tracking
	 */
	public function test_bp_activity_set_post_type_tracking_args() {
		$bp = buddypress();

		add_post_type_support( 'page', 'buddypress-activity' );

		bp_activity_set_post_type_tracking_args( 'page', array(
			'component_id' => $bp->blogs->id,
			'dummy'        => 'dummy value',
		) );

		// Build the actions to fetch the tracking args
		bp_activity_get_actions();

		$u = self::factory()->user->create();

		$post_id = self::factory()->post->create( array(
			'post_author' => $u,
			'post_status' => 'publish',
			'post_type'   => 'page',
		) );

		$a = bp_activity_get( array(
			'action'            => 'new_page',
			'item_id'           => get_current_blog_id(),
			'secondary_item_id' => $post_id,
		) );

		$this->assertSame( $bp->blogs->id, $a['activities'][0]->component );

		remove_post_type_support( 'page', 'buddypress-activity' );
	}

	/**
	 * @group bp_activity_set_post_type_tracking_args
	 * @group activity_tracking
	 */
	public function test_bp_activity_set_post_type_tracking_args_check_post_type_global() {
		$labels = array(
			'bp_activity_admin_filter' => 'New Foo',
			'bp_activity_front_filter' => 'Foos',
		);

		$bp_activity_args = array(
			'action_id'    => 'new_foo',
			'contexts'     => array( 'activity' ),
			'position'     => 40,
		);

		register_post_type( 'foo', array(
			'labels'      => $labels,
			'supports'    => array( 'buddypress-activity' ),
			'bp_activity' => $bp_activity_args
		) );

		$register_bp_activity = get_post_type_object( 'foo' )->bp_activity;
		_unregister_post_type( 'foo' );

		register_post_type( 'foo', array(
			'label'       => 'foo',
			'supports'    => array( 'buddypress-activity' ),
		) );

		bp_activity_set_post_type_tracking_args( 'foo', $labels + $bp_activity_args );

		$set_bp_activity = get_post_type_object( 'foo' )->bp_activity;
		_unregister_post_type( 'foo' );

		$this->assertSame( $set_bp_activity, $register_bp_activity );
	}

	/**
	 * @group activity_action
	 * @group bp_activity_format_activity_action_custom_post_type_post_ms
	 * @group post_type_comment_activities
	 */
	public function test_bp_activity_format_activity_action_custom_post_type_comment() {
		if ( is_multisite() ) {
			$b = self::factory()->blog->create();
			switch_to_blog( $b );
			add_filter( 'comment_flood_filter', '__return_false' );
		} else {
			$b = get_current_blog_id();
		}

		$u = self::factory()->user->create();
		$userdata = get_userdata( $u );

		$labels = array(
			'name'                       => 'bars',
			'singular_name'              => 'bar',
			'bp_activity_new_comment'    => __( '%1$s commented on the <a href="%2$s">bar</a>', 'buddypress' ),
			'bp_activity_new_comment_ms' => __( '%1$s commented on the <a href="%2$s">bar</a>, on the site %3$s', 'buddypress' ),
		);

		register_post_type( 'foo', array(
			'labels'   => $labels,
			'public'   => true,
			'supports' => array( 'buddypress-activity', 'comments' ),
			'bp_activity' => array(
				'action_id'         => 'new_bar',
				'comment_action_id' => 'new_bar_comment',
			),
		) );

		// Build the actions to fetch the tracking args
		bp_activity_get_actions();

		$p = self::factory()->post->create( array(
			'post_author' => $u,
			'post_type'   => 'foo',
		) );

		$c = wp_new_comment( array(
			'comment_post_ID'      => $p,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a blog comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u,
		) );

		$a = bp_activity_get_activity_id( array( 'type' => 'new_bar_comment' ) );

		$a_obj = new BP_Activity_Activity( $a );

		$user_link    = bp_core_get_userlink( $u );
		$comment_url  = get_comment_link( $c );

		_unregister_post_type( 'foo' );

		if ( is_multisite() ) {
			$blog_url  = get_blog_option( $a_obj->item_id, 'home' );
			restore_current_blog();
			remove_filter( 'comment_flood_filter', '__return_false' );

			$expected = sprintf( $labels['bp_activity_new_comment_ms'], $user_link, $comment_url, '<a href="' . $blog_url . '">' . get_blog_option( $a_obj->item_id, 'blogname' ) . '</a>' );
		} else {
			$expected = sprintf( $labels['bp_activity_new_comment'], $user_link, $comment_url );
		}

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group bp_activity_new_comment
	 * @group cache
	 */
	public function test_bp_activity_new_comment_clear_comment_caches() {
		$u = self::factory()->user->create();
		$a1 = self::factory()->activity->create( array(
			'user_id' => $u,
		) );
		$a2 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a1,
			'content' => 'foo',
			'user_id' => $u,
		) );
		$a3 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a2,
			'content' => 'foo',
			'user_id' => $u,
		) );
		$a4 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a3,
			'content' => 'foo',
			'user_id' => $u,
		) );
		$a5 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a3,
			'content' => 'foo',
			'user_id' => $u,
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
			'user_id' => $u,
		) );

		// should be empty
		$this->assertFalse( wp_cache_get( $a1, 'bp_activity_comments' ) );
	}

	/**
	 * @group bp_activity_new_comment
	 * @group cache
	 */
	public function test_bp_activity_new_comment_clear_activity_caches() {
		$u = self::factory()->user->create();
		$a1 = self::factory()->activity->create( array(
			'user_id' => $u,
		) );
		$a2 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a1,
			'content' => 'foo',
			'user_id' => $u,
		) );
		$a3 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a2,
			'content' => 'foo',
			'user_id' => $u,
		) );
		$a4 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a3,
			'content' => 'foo',
			'user_id' => $u,
		) );
		$a5 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a3,
			'content' => 'foo',
			'user_id' => $u,
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
			'user_id' => $u,
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
		$u = self::factory()->user->create();
		// add new activity update and comment to this update
		$a1 = self::factory()->activity->create( array(
			'user_id' => $u,
		) );
		$a2 = bp_activity_new_comment( array(
			'activity_id' => $a1,
			'parent_id' => $a1,
			'content' => 'foo',
			'user_id' => $u,
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
	 * @group  bp_activity_delete_comment
	 * @ticket BP7450
	 */
	public function test_bp_activity_delete_comment_shouldnt_delete_all_comments_when_parameters_are_empty() {
		$u = self::factory()->user->create();

		// create an activity update
		$parent_activity = self::factory()->activity->create( array(
			'type'    => 'activity_update',
			'user_id' => $u
		) );

		// create some activity comments
		$comment_one = bp_activity_new_comment( array(
			'user_id'     => $u,
			'activity_id' => $parent_activity,
			'content'     => 'depth 1'
		) );

		$comment_one_one = bp_activity_new_comment( array(
			'user_id'     => $u,
			'activity_id' => $parent_activity,
			'parent_id'   => $comment_one,
			'content'     => 'depth 2'
		) );

		$comment_two = bp_activity_new_comment( array(
			'user_id'     => $u,
			'activity_id' => $parent_activity,
			'content'     => 'depth 1'
		) );

		// Pass empty values to bp_activity_delete_comment()
		$retval = bp_activity_delete_comment( 0, 0 );
		$this->assertFalse( $retval );

		// Instantiate activity loop, which also includes activity comments.
		bp_has_activities( 'display_comments=stream' );

		// Activity comments should not be deleted.
		$this->assertSame( 4, $GLOBALS['activities_template']->activity_count );

		// Clean up after ourselves!
		$GLOBALS['activities_template'] = null;
	}

	/**
	 * @group bp_activity_new_comment
	 * @group BP5907
	 */
	public function test_bp_activity_comment_on_deleted_activity() {
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create();

		bp_activity_delete_by_activity_id( $a );

		$c = bp_activity_new_comment( array(
			'activity_id' => $a,
			'parent_id' => $a,
			'content' => 'foo',
			'user_id' => $u,
		) );

		$this->assertEmpty( $c );
	}

	/**
	 * @group favorites
	 * @group bp_activity_add_user_favorite
	 */
	public function test_add_user_favorite_already_favorited() {
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create();

		// bp_activity_add_user_favorite() requires a logged-in user.
		$current_user = bp_loggedin_user_id();
		$this->set_current_user( $u );

		$this->assertTrue( bp_activity_add_user_favorite( $a, $u ) );

		$this->assertFalse( bp_activity_add_user_favorite( $a, $u ) );
		$this->assertSame( array( $a ), bp_activity_get_user_favorites( $u ) );
		$this->assertEquals( 1, bp_activity_get_meta( $a, 'favorite_count' ) );

		$this->set_current_user( $current_user );
	}

	/**
	 * @group favorites
	 * @group bp_activity_add_user_favorite
	 */
	public function test_add_user_favorite_not_yet_favorited() {
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create();

		// bp_activity_add_user_favorite() requires a logged-in user.
		$current_user = bp_loggedin_user_id();
		$this->set_current_user( $u );
		$this->assertTrue( bp_activity_add_user_favorite( $a, $u ) );

		$this->set_current_user( $current_user );
	}

	/**
	 * @group favorites
	 * @group bp_activity_remove_user_favorite
	 */
	public function test_remove_user_favorite_bad_activity_id() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$a = self::factory()->activity->create();

		// bp_activity_add_user_favorite() requires a logged-in user.
		$current_user = bp_loggedin_user_id();
		$this->set_current_user( $u1 );

		// Only favorite for user 1
		bp_activity_add_user_favorite( $a, $u1 );

		// Removing for user 2 should fail
		$this->assertFalse( bp_activity_remove_user_favorite( $a, $u2 ) );
		$this->assertEquals( 1, bp_activity_get_meta( $a, 'favorite_count' ) );

		$this->set_current_user( $current_user );
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
		$u = self::factory()->user->create();

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

		$a = self::factory()->activity->create( $args );

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

		$a = self::factory()->activity->create( $args );

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
