<?php
/**
 * @group url
 */
class BP_Tests_URL extends BP_UnitTestCase {
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

	function test_bp_core_ajax_url() {
		$forced = force_ssl_admin();

		// (1) HTTPS off
		force_ssl_admin( false );
		$_SERVER['HTTPS'] = 'off';

		// (1a) Front-end
		$this->go_to( '/' );
		$this->assertEquals( bp_core_ajax_url(), get_site_url( bp_get_root_blog_id(), '/wp-admin/admin-ajax.php', 'http' ) );

		// (1b) Dashboard
		$this->go_to( '/wp-admin' );
		$this->assertEquals( bp_core_ajax_url(), get_site_url( bp_get_root_blog_id(), '/wp-admin/admin-ajax.php', 'http' ) );

		// (2) FORCE_SSL_ADMIN
		force_ssl_admin( true );

		// (2a) Front-end
		$this->go_to( '/' );
		$this->assertEquals( bp_core_ajax_url(), get_site_url( bp_get_root_blog_id(), '/wp-admin/admin-ajax.php', 'http' ) );

		// (2b) Dashboard
		$this->go_to( '/wp-admin' );
		$this->assertEquals( bp_core_ajax_url(), get_site_url( bp_get_root_blog_id(), '/wp-admin/admin-ajax.php', 'https' ) );

		force_ssl_admin( $forced );

		// (3) Multisite, root blog other than 1
		if ( is_multisite() ) {
			$original_root_blog = bp_get_root_blog_id();
			$blog_id = $this->factory->blog->create();
			buddypress()->root_blog_id = $blog_id;

			switch_to_blog( $blog_id );
			$blog_details = get_blog_details();

			$this->go_to( $blog_details->path );
			$this->assertEquals( $blog_details->siteurl . '/wp-admin/admin-ajax.php', bp_core_ajax_url() );

			restore_current_blog();
			buddypress()->root_blog_id = $original_root_blog;
		}

	}
}
