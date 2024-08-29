<?php

/**
 * @group notices
 */
class BP_Tests_BP_Members_Notice_TestCases extends BP_UnitTestCase {

	protected $old_current_user = 0;

	public function set_up() {
		parent::set_up();

		$this->old_current_user = get_current_user_id();
	}

	public function tear_down() {
		self::set_current_user( $this->old_current_user );

		parent::tear_down();
	}

	/**
	 * @group cache
	 * group bp_member_first_active_notice
	 * @ticket BP9098
	 */
	public function test_get_active_notice() {
		// send notice
		$subject = 'Test notice';
		$message = 'This is a notice';

		bp_members_save_notice(
			array(
				'title'   => $subject,
				'content' => $message,
			)
		);

		$u1 = self::factory()->user->create();
		self::set_current_user( $u1 );

		// now get the active notice and assert
		$notice = bp_get_active_notice_for_user();
		$this->assertEquals( $subject, bp_get_notice_title( $notice ) );
		$this->assertEquals( $message, bp_get_notice_content( $notice, true ) );

		// deactivate notice and make sure cache is invalidated
		$test = $notice->deactivate();
		$this->assertFalse( wp_cache_get( $u1, 'bp_member_first_active_notice' ) );

		// create a new notice
		$subject2 = 'Another notice';
		$message2 = 'Say what?';

		bp_members_save_notice(
			array(
				'title'   => $subject2,
				'content' => $message2,
			)
		);

		// now get the new active notice
		BP_Members_Notice::get_active();

		// grab the cache and make sure it equals our new notice
		$cache = wp_cache_get( $u1, 'bp_member_first_active_notice' );
		$this->assertEquals( $subject2, bp_get_notice_title( $cache ) );
		$this->assertEquals( $message2, bp_get_notice_content( $cache, true ) );
	}

	public function get_notices() {
		return array(
			'dismissed' => array(
				bp_members_save_notice(
					array(
						'title'   => 'Dismissed Notice #1',
						'content' => 'Content for first dismissed notice',
					)
				),
				bp_members_save_notice(
					array(
						'title'   => 'Dismissed Notice #2',
						'content' => 'Content for second dismissed notice',
					)
				),
			),
			'unread' => bp_members_save_notice(
				array(
					'title'   => 'Unread Notice',
					'content' => 'Content for unread notice',
				)
			),
		);
	}

	/**
	 * @group cache
	 * @group bp_member_dismissed_notices
	 * @ticket BP9098
	 */
	public function test_get_dismissed_notices() {
		$notices = $this->get_notices();

		$u1 = self::factory()->user->create();
		foreach ( $notices['dismissed'] as $notice_id ) {
			bp_members_dismiss_notice( $u1, $notice_id );
		}

		$dismissed = bp_members_get_dismissed_notices_for_user( $u1 );

		$this->assertSame( $notices['dismissed'], wp_cache_get( $u1, 'bp_member_dismissed_notices' ) );

		bp_members_dismiss_notice( $u1, $notices['unread'] );
		$this->assertFalse( wp_cache_get( $u1, 'bp_member_dismissed_notices' ) );
	}

	/**
	 * @group cache
	 * @group bp_member_notices_count
	 * @ticket BP9098
	 */
	public function test_get_user_notices_count() {
		$notices = $this->get_notices();

		$u1 = self::factory()->user->create();

		$notices_count = bp_members_get_notices_count(
			array(
				'user_id'  => $u1,
				'exclude'  => array(),
			)
		);

		$this->assertEquals( 3, wp_cache_get( $u1, 'bp_member_notices_count' ) );

		foreach ( $notices['dismissed'] as $notice_id ) {
			bp_members_dismiss_notice( $u1, $notice_id );
		}

		$this->assertFalse( wp_cache_get( $u1, 'bp_member_notices_count' ) );
	}

	/**
	 * @group cache
	 * @group bp_member_top_priority_notices
	 * @ticket BP9098
	 */
	public function test_get_user_notices() {
		$notices = $this->get_notices();

		$u1 = self::factory()->user->create();

		$user_notices = bp_members_get_notices_for_user( $u1 );

		$this->assertEquals( 3, count( wp_cache_get( $u1, 'bp_member_top_priority_notices' ) ) );

		foreach ( $notices['dismissed'] as $notice_id ) {
			bp_members_dismiss_notice( $u1, $notice_id );
		}

		$this->assertFalse( wp_cache_get( $u1, 'bp_member_top_priority_notices' ) );

		bp_members_get_notices_for_user( $u1 );
		$cache = wp_cache_get( $u1, 'bp_member_top_priority_notices' );

		$this->assertEquals( $notices['unread'], bp_get_notice_id( $cache[0] ) );
	}
}
