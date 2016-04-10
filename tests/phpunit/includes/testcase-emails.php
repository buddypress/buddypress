<?php
require_once dirname( __FILE__ ) . '/testcase.php';

class BP_UnitTestCase_Emails extends BP_UnitTestCase {

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		require_once( buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php' );

		/*
		 * WP's test suite wipes out BP's email posts.
		 * We must reestablish them before our tests can be successfully run.
		 */
		bp_core_install_emails();
	}

	public static function tearDownAfterClass() {
		$emails = get_posts( array(
			'fields'           => 'ids',
			'post_status'      => 'any',
			'post_type'        => bp_get_email_post_type(),
			'posts_per_page'   => 200,
			'suppress_filters' => false,
		) );

		if ( $emails ) {
			foreach ( $emails as $email_id ) {
				wp_delete_post( $email_id, true );
			}
		}

		parent::tearDownAfterClass();
	}
}
