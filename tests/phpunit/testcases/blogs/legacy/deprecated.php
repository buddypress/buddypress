<?php

/**
 * @group blogs
 * @group deprecated
 */
class BP_Tests_Blogs_Legacy_Deprecated extends BP_UnitTestCase {

	/**
	 * @expectedDeprecated bp_legacy_theme_blog_create_button
	 */
	public function test_legacy_blog_create_button_keeps_the_title_for_guests() {
		$old_user = get_current_user_id();
		self::set_current_user( 0 );

		try {
			$this->assertSame( 'Sites ', bp_legacy_theme_blog_create_button( 'Sites' ) );
		} finally {
			self::set_current_user( $old_user );
		}
	}
}
