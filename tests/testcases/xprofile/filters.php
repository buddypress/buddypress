<?php

/**
 * @group xprofile
 */
class BP_Tests_XProfile_Filters extends BP_UnitTestCase {
	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_filter_meta_query
	 */
	public function test_bp_xprofile_filter_meta_query_select() {
		global $wpdb;

		// update_meta_cache
		$q = "SELECT xprofile_group_id, meta_key, meta_value FROM {$wpdb->xprofile_groupmeta} WHERE xprofile_group_id IN (1,2,3) ORDER BY xprofile_group_id ASC";
		$this->assertSame( "SELECT object_id AS xprofile_group_id, meta_key, meta_value FROM {$wpdb->xprofile_groupmeta} WHERE object_type = 'group' AND object_id IN (1,2,3) ORDER BY object_id ASC", bp_xprofile_filter_meta_query( $q ) );

		// add_metadata
		$q = "SELECT COUNT(*) FROM {$wpdb->xprofile_groupmeta} WHERE meta_key = 'foo' AND xprofile_group_id = 5";
		$this->assertSame( "SELECT COUNT(*) FROM {$wpdb->xprofile_groupmeta} WHERE object_type = 'group' AND meta_key = 'foo' AND object_id = 5", bp_xprofile_filter_meta_query( $q ) );

		// delete_metadata
		$q = "SELECT meta_id FROM {$wpdb->xprofile_groupmeta} WHERE meta_key = 'foo' AND xprofile_group_id = 5 AND meta_value = 'bar'";
		$this->assertSame( "SELECT meta_id FROM {$wpdb->xprofile_groupmeta} WHERE object_type = 'group' AND meta_key = 'foo' AND object_id = 5 AND meta_value = 'bar'", bp_xprofile_filter_meta_query( $q ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_filter_meta_query
	 */
	public function test_bp_xprofile_filter_meta_query_insert() {
		global $wpdb;
		$q = "INSERT INTO `{$wpdb->xprofile_groupmeta}` (`xprofile_group_id`,`meta_key`,`meta_value`) VALUES (3,'foo','bar')";
		$this->assertSame( "INSERT INTO `{$wpdb->xprofile_groupmeta}` (`object_type`,`object_id`,`meta_key`,`meta_value`) VALUES ('group',3,'foo','bar')", bp_xprofile_filter_meta_query( $q ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_filter_meta_query
	 */
	public function test_bp_xprofile_filter_meta_query_update() {
		global $wpdb;

		$q = "UPDATE `{$wpdb->xprofile_groupmeta}` SET meta_value = 'bar' WHERE xprofile_group_id = 3 AND meta_key = 'foo'";
		$this->assertSame( "UPDATE `{$wpdb->xprofile_groupmeta}` SET meta_value = 'bar' WHERE object_type = 'group' AND object_id = 3 AND meta_key = 'foo'", bp_xprofile_filter_meta_query( $q ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_filter_meta_query
	 */
	public function test_bp_xprofile_filter_meta_query_delete() {
		global $wpdb;
		$q = "DELETE FROM {$wpdb->xprofile_groupmeta} WHERE xprofile_group_id IN(1,2,3)";
		$this->assertSame( "DELETE FROM {$wpdb->xprofile_groupmeta} WHERE object_type = 'group' AND object_id IN(1,2,3)", bp_xprofile_filter_meta_query( $q ) );
	}
}
