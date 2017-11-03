<?php

/**
 * @group activity
 */
class BP_Tests_Activity_Filters extends BP_UnitTestCase {
	/**
	 * @group mentions
	 * @group bp_activity_at_name_filter
	 */
	public function test_bp_activity_at_name_filter() {
		$u1 = self::factory()->user->create( array(
			'user_login' => 'foobarbaz',
			'user_nicename' => 'foobarbaz',
		) );

		$u2 = self::factory()->user->create( array(
			'user_login' => 'foo2',
			'user_nicename' => 'foo2',
		) );

		$u1_mention_name = bp_activity_get_user_mentionname( $u1 );
		$u1_domain = bp_core_get_user_domain( $u1 );
		$u2_mention_name = bp_activity_get_user_mentionname( $u2 );
		$u2_domain = bp_core_get_user_domain( $u2 );

		// mentions normal text should be replaced
		$at_name_in_text = sprintf( 'Hello @%s', $u1_mention_name );
		$at_name_in_text_final = "Hello <a class='bp-suggestions-mention' href='" . $u1_domain . "' rel='nofollow'>@$u1_mention_name</a>";
		$this->assertEquals( $at_name_in_text_final, bp_activity_at_name_filter( $at_name_in_text ) );

		// mentions inside links sholudn't be replaced
		// inside href
		$at_name_in_mailto = sprintf( "Send messages to <a href='mail@%s.com'>Foo Bar Baz</a>", $u1_mention_name );
		$at_name_in_mailto_final = sprintf( "Send messages to <a href='mail@%s.com'>Foo Bar Baz</a>", $u1_mention_name );
		$this->assertEquals( $at_name_in_mailto_final, bp_activity_at_name_filter( $at_name_in_mailto ) );

		// inside linked text
		$at_name_in_link = sprintf( '<a href="https://twitter.com/%1$s">@%1$s</a>', $u1_mention_name );
		$at_name_in_link_final = sprintf( '<a href="https://twitter.com/%1$s">@%1$s</a>', $u1_mention_name );
		$this->assertEquals( $at_name_in_link_final, bp_activity_at_name_filter( $at_name_in_link ) );

		// Don't link non-existent users
		$text = "Don't link @non @existent @users";
		$this->assertSame( $text, bp_activity_at_name_filter( $text ) );

		// Don't link the domain name of the site
		preg_match( '|https?://([^/]+)|', home_url(), $matches );
		if ( ! empty( $matches[1] ) ) {
			$text = $matches[1] . " Don't link the domain name " . $matches[1];
		}
		$this->assertSame( $text, bp_activity_at_name_filter( $text ) );

		// Multiples
		$at_name_in_mailto = sprintf( "Send messages to @%s <a href='mail@%s.com'>Foo Bar Baz</a>. Please CC <a href='http://twitter.com/foo2'>@foo2</a>.", $u1_mention_name, $u1_mention_name, $u2_mention_name, $u2_mention_name );
		$at_name_in_mailto_final = sprintf( 'Send messages to <a class=\'bp-suggestions-mention\' href=\'%s\' rel=\'nofollow\'>@%s</a> <a href=\'mail@%s.com\'>Foo Bar Baz</a>. Please CC <a href=\'http://twitter.com/%s\'>@%s</a>.', $u1_domain, $u1_mention_name, $u1_mention_name, $u2_mention_name, $u2_mention_name );
		$this->assertEquals( $at_name_in_mailto_final, bp_activity_at_name_filter( $at_name_in_mailto ) );
	}

}
