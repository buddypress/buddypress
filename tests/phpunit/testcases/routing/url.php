<?php
/**
 * @group url
 */
class BP_Tests_URL extends BP_UnitTestCase {
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
			$blog_id = $this->factory->blog->create( array(
				'path' => '/path' . rand() . time() . '/',
			) );
			buddypress()->root_blog_id = $blog_id;

			$blog_url = get_blog_option( $blog_id, 'siteurl' );

			$this->go_to( trailingslashit( $blog_url ) );
			buddypress()->root_blog_id = $original_root_blog;
			$ajax_url = bp_core_ajax_url();
			$this->go_to( '/' );

			$this->assertEquals( $blog_url . '/wp-admin/admin-ajax.php', $ajax_url );
		}
	}
}
