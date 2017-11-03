<?php

/**
 * @group activity
 * @group notifications
 */
class BP_Tests_Activity_Notifications extends BP_UnitTestCase {
	protected $current_user;
	protected $u1;
	protected $u2;
	protected $a1;
	protected $a2;

	public function setUp() {
		parent::setUp();
		$this->current_user = get_current_user_id();
		$this->u1 = self::factory()->user->create();
		$this->u2 = self::factory()->user->create();
		$this->set_current_user( $this->u1 );

		/**
		 * Tests suite in WP < 4.0 does not include the WP_UnitTestCase->_restore_hooks() function
		 * When updating an activity, the following filter is fired to prevent sending more than one
		 * notification. Once we've reached this filter all at_mentions tests fails so we need to
		 * temporarly remove it and restore it in $this->tearDown()
		 */
		remove_filter( 'bp_activity_at_name_do_notifications', '__return_false' );
	}

	public function tearDown() {
		$this->set_current_user( $this->current_user );
		parent::tearDown();

		// Restore the filter
		add_filter( 'bp_activity_at_name_do_notifications', '__return_false' );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 */
	public function test_bp_activity_remove_screen_notifications_on_single_activity_permalink() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// Go to the activity permalink page
		$this->go_to( bp_activity_get_permalink( $this->a1 ) );
		$activity = bp_activity_get_specific( array( 'activity_ids' => $this->a1, 'show_hidden' => true, 'spam' => 'ham_only', ) );
		do_action( 'bp_activity_screen_single_activity_permalink', $activity['activities'][0] );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Should be empty
		$this->assertEquals( array(), $notifications );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 */
	public function test_bp_activity_remove_screen_notifications_on_single_activity_permalink_logged_out() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// Log out
		$this->set_current_user( 0 );

		// Go to the activity permalink page
		$this->go_to( bp_activity_get_permalink( $this->a1 ) );
		$activity = bp_activity_get_specific( array( 'activity_ids' => $this->a1, 'show_hidden' => true, 'spam' => 'ham_only', ) );
		do_action( 'bp_activity_screen_single_activity_permalink', $activity['activities'][0] );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Should be untouched
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		$this->set_current_user( $this->u1 );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 */
	public function test_bp_activity_remove_screen_notifications_on_single_activity_permalink_wrong_user() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// Switch user
		$this->set_current_user( $this->u2 );

		// Go to the activity permalink page
		$this->go_to( bp_activity_get_permalink( $this->a1 ) );
		$activity = bp_activity_get_specific( array( 'activity_ids' => $this->a1, 'show_hidden' => true, 'spam' => 'ham_only', ) );
		do_action( 'bp_activity_screen_single_activity_permalink', $activity['activities'][0] );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Should be untouched
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		$this->set_current_user( $this->u1 );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 */
	public function test_bp_activity_remove_screen_notifications_on_mentions() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// Go to the My Activity page
		$this->go_to( bp_core_get_user_domain( $this->u1 ) . bp_get_activity_slug() . '/mentions/' );
		do_action( 'bp_activity_screen_mentions' );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Should be empty
		$this->assertEquals( array(), $notifications );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 */
	public function test_bp_activity_remove_screen_notifications_on_mentions_logged_out() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// Log out
		$this->set_current_user( 0 );

		// Go to the My Activity page
		$this->go_to( bp_core_get_user_domain( $this->u1 ) . bp_get_activity_slug() . '/mentions/' );
		do_action( 'bp_activity_screen_mentions' );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Should be untouched
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// clean up
		$this->set_current_user( $this->u1 );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 */
	public function test_bp_activity_remove_screen_notifications_on_mentions_wrong_user() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// Log out
		$this->set_current_user( $this->u2 );

		// Go to the My Activity page
		$this->go_to( bp_core_get_user_domain( $this->u1 ) . bp_get_activity_slug() . '/mentions/' );
		do_action( 'bp_activity_screen_mentions' );

		$notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		// Should be untouched
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		// clean up
		$this->set_current_user( $this->u1 );
	}

	/**
	 * @group bp_notifications_delete_all_notifications_by_type
	 * @group bp_activity_at_mention_delete_notification
	 */
	public function test_bp_activity_at_mention_delete_notification() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'item_id' => $this->a1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );

		bp_activity_delete( array(
			'id' => $this->a1,
		) );

		$notifications = BP_Notifications_Notification::get( array(
			'item_id' => $this->a1,
		) );

		$this->assertEmpty( $notifications );
	}

	/**
	 * @group bp_activity_remove_screen_notifications
	 * @group mentions
	 * @ticket BP6687
	 */
	public function test_bp_activity_remove_screen_notifications_on_new_mentions_cleared() {
		$this->create_notifications();

		$notifications = BP_Notifications_Notification::get( array(
			'item_id' => $this->a1,
		) );

		// Double check it's there
		$this->assertEquals( array( $this->a1 ), wp_list_pluck( $notifications, 'item_id' ) );
		$this->assertEquals( 1, bp_get_total_mention_count_for_user( $this->u1 ) );

		// Clear notifications for $this->u1
		bp_activity_clear_new_mentions( $this->u1 );

		$notifications = BP_Notifications_Notification::get( array(
			'item_id' => $this->a1,
		) );

		$this->assertEmpty( $notifications, 'Notifications should be cleared when new mention metas are removed' );
		$this->assertEmpty( bp_get_total_mention_count_for_user( $this->u1 ) );
	}

	/**
	 * Creates two notifications for $u1, one of which is for mentions
	 */
	protected function create_notifications() {
		$u1_mentionname = bp_activity_get_user_mentionname( $this->u1 );
		$this->a1 = self::factory()->activity->create( array(
			'user_id' => $this->u2,
			'component' => buddypress()->activity->id,
			'type' => 'activity_update',
			'content' => sprintf( 'Hello! @%s', $u1_mentionname ),
		) );
		$u2_mentionname = bp_activity_get_user_mentionname( $this->u2 );
		$this->a2 = self::factory()->activity->create( array(
			'user_id' => $this->u1,
			'component' => buddypress()->activity->id,
			'type' => 'activity_update',
			'content' => sprintf( 'Hello! @%s', $u2_mentionname ),
		) );
	}

	/**
	 * @group bp_activity_format_notifications
	 */
	public function test_bp_activity_format_notifications_new_at_mention() {
		$this->test_format_filter = array();

		// Current user is $this->u1, so $this->u2 posted the mention
		$a = self::factory()->activity->create( array(
			'user_id' => $this->u2,
			'component' => buddypress()->activity->id,
			'type' => 'activity_update',
			'content' => 'fake new_at_mention activity',
		) );

		add_filter( 'bp_activity_single_at_mentions_notification', array( $this, 'format_notification_filter' ), 10, 1 );
		add_filter( 'bp_activity_multiple_at_mentions_notification', array( $this, 'format_notification_filter' ), 10, 1 );

		$format_tests = array(
			'array_single'    => bp_activity_format_notifications( 'new_at_mention', $a, $this->u2, 1, 'array' ),
			'string_single'   => bp_activity_format_notifications( 'new_at_mention', $a, $this->u2, 1 ),
			'array_multiple'  => bp_activity_format_notifications( 'new_at_mention', $a, $this->u2, 2, 'array' ),
			'string_multiple' => bp_activity_format_notifications( 'new_at_mention', $a, $this->u2, 2 ),
		);

		remove_filter( 'bp_activity_single_at_mentions_notification', array( $this, 'format_notification_filter' ), 10 );
		remove_filter( 'bp_activity_multiple_at_mentions_notification', array( $this, 'format_notification_filter' ), 10 );

		$single = sprintf( __( '%1$s mentioned you', 'buddypress' ), bp_core_get_user_displayname( $this->u2 ) );
		$multiple = 'You have 2 new mentions';

		$this->assertContains( $single, $format_tests['string_single'] );
		$this->assertContains( $single, $format_tests['array_single']['text'] );
		$this->assertContains( $multiple, $format_tests['string_multiple'] );
		$this->assertContains( $multiple, $format_tests['array_multiple']['text'] );

		// Check filters
		$this->assertTrue( 4 === count( $this->test_format_filter ) );
	}

	public function format_notification_filter( $return ) {
		$this->test_format_filter[] = current_filter();
		return $return;
	}

	/**
	 * @group bp_activity_update_reply_add_notification
	 * @group bp_activity_comment_reply_add_notification
	 */
	public function test_bp_activity_comment_add_notification() {
		$a = self::factory()->activity->create( array(
			'user_id' => $this->u1,
			'component' => buddypress()->activity->id,
			'type' => 'activity_update',
			'content' => 'Please comment this activity.',
		) );

		$c = bp_activity_new_comment( array(
			'content'     => 'this is the comment',
			'user_id'     => $this->u2,
			'activity_id' => $a, // ID of the root activity item.
			'parent_id'   => false  // ID of a parent comment (optional).
		) );

		$u3 = self::factory()->user->create();

		$r3 = bp_activity_new_comment( array(
			'content'     => 'this is a reply to a comment',
			'user_id'     => $u3,
			'activity_id' => $a, // ID of the root activity item.
			'parent_id'   => $c  // ID of a parent comment (optional).
		) );

		$u1_notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u1,
		) );

		$expected_commenters = array( $this->u2, $u3 );
		$this->assertEquals( $expected_commenters, wp_list_pluck( $u1_notifications, 'secondary_item_id' ) );

		$u2_notifications = BP_Notifications_Notification::get( array(
			'user_id' => $this->u2,
		) );

		$expected_commenter = array( $u3 );
		$this->assertEquals( $expected_commenter, wp_list_pluck( $u2_notifications, 'secondary_item_id' ) );
	}

	/**
	 * @ticket BP7135
	 */
	public function test_activity_reply_notifications_for_blog_comment_to_activity_comment_sync() {
		$old_user = get_current_user_id();
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$this->set_current_user( $u1 );
		$userdata = get_userdata( $u1 );

		// let's use activity comments instead of single "new_blog_comment" activity items
		add_filter( 'bp_disable_blogforum_comments', '__return_false' );

		// Silence comment flood errors.
		add_filter( 'comment_flood_filter', '__return_false' );

		// create the blog post
		$post_id = self::factory()->post->create( array(
			'post_status' => 'publish',
			'post_type'   => 'post',
			'post_title'  => 'Test post',
		) );

		$this->set_current_user( $u2 );
		$userdata = get_userdata( $u2 );

		$c1 = wp_new_comment( array(
			'comment_post_ID'      => $post_id,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a blog comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u2,
		) );
		// Approve the comment
		self::factory()->comment->update_object( $c1, array( 'comment_approved' => 1 ) );

		$this->set_current_user( $u3 );
		$userdata = get_userdata( $u3 );

		$c2 = wp_new_comment( array(
			'comment_post_ID'      => $post_id,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a blog comment',
			'comment_type'         => '',
			'comment_parent'       => $c1,
			'user_id'              => $u3,
		) );
		// Approve the comment
		self::factory()->comment->update_object( $c2, array( 'comment_approved' => 1 ) );

		// Get activity IDs.
		$ac1 = get_comment_meta( $c1, 'bp_activity_comment_id', true );
		$ac2 = get_comment_meta( $c2, 'bp_activity_comment_id', true );

		// Check if notifications exists for user 1.
		$n1 = BP_Notifications_Notification::get( array(
			'component_name' => 'activity',
			'user_id'        => $u1
		) );
		$this->assertEquals( 2, count( $n1 ) );
		$this->assertEquals(
			array( $ac1, $ac2 ),
			wp_list_pluck( $n1, 'item_id' )
		);

		// Check if notification exists for user 2.
		$n2 = BP_Notifications_Notification::get( array(
			'component_action' => 'comment_reply',
			'item_id'          => $ac2,
			'user_id'          => $u2
		) );
		$this->assertNotEmpty( $n2 );

		// Reset.
		$this->set_current_user( $old_user );
		remove_filter( 'bp_disable_blogforum_comments', '__return_false' );
		remove_filter( 'comment_flood_filter', '__return_false' );
	}

}
