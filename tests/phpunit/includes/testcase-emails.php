<?php
require_once dirname( __FILE__ ) . '/testcase.php';

class BP_UnitTestCase_Emails extends BP_UnitTestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();

		require_once( buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php' );

		/*
		 * WP's test suite wipes out BP's email posts.
		 * We must reestablish them before our tests can be successfully run.
		 */
		bp_core_install_emails();
	}
}
