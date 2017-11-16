<?php
/**
 * @group members
 */
class BP_Tests_Members_Functions extends BP_UnitTestCase {

	/**
	 * @ticket BP4915
	 * @group bp_core_delete_account
	 */
	public function test_bp_core_delete_account() {
		// Stash
		$current_user = get_current_user_id();
		$deletion_disabled = bp_disable_account_deletion();

		// Create an admin for testing
		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->grant_super_admin( $admin_user );

		// 1. Admin can delete user account
		$this->set_current_user( $admin_user );
		$user1 = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		bp_core_delete_account( $user1 );
		$maybe_user = new WP_User( $user1 );
		$this->assertEquals( 0, $maybe_user->ID );
		unset( $maybe_user );
		$this->restore_admins();

		// 2. Admin cannot delete superadmin account
		$user2 = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->grant_super_admin( $user2 );
		bp_core_delete_account( $user2 );
		$maybe_user = new WP_User( $user2 );
		$this->assertNotEquals( 0, $maybe_user->ID );
		unset( $maybe_user );

		// User cannot delete other's account
		$user3 = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$user4 = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->set_current_user( $user3 );
		bp_core_delete_account( $user4 );
		$maybe_user = new WP_User( $user4 );
		$this->assertNotEquals( 0, $maybe_user->ID );
		unset( $maybe_user );

		// Cleanup
		$this->set_current_user( $current_user );
		bp_update_option( 'bp-disable-account-deletion', $deletion_disabled );
	}

	/**
	 * @group object_cache
	 * @group bp_core_get_directory_pages
	 */
	public function test_bp_core_get_user_domain_after_directory_page_update() {
		// Generate user
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		// Set object cache first for user domain
		$user_domain = bp_core_get_user_domain( $user_id );

		// Now change the members directory slug
		$pages = bp_core_get_directory_pages();
		$members_page = get_post( $pages->members->id );
		$members_page->post_name = 'new-members-slug';
		wp_update_post( $members_page );

		// Go back to members directory page and recheck user domain
		$this->go_to( trailingslashit( home_url( 'new-members-slug' ) ) );
		$user = new WP_User( $user_id );

		$this->assertSame( home_url( 'new-members-slug' ) . '/' . $user->user_nicename . '/', bp_core_get_user_domain( $user_id ) );
	}

	/**
	 * @group bp_core_get_user_displayname
	 */
	public function test_bp_core_get_user_displayname_empty_username() {
		$this->assertFalse( bp_core_get_user_displayname( '' ) );
	}

	/**
	 * @group bp_core_get_user_displayname
	 */
	public function test_bp_core_get_user_displayname_translate_username() {
		$u = self::factory()->user->create();

		$user = new WP_User( $u );

		$found = bp_core_get_user_displayname( $u );
		$this->assertNotEmpty( $found );
		$this->assertSame( $found, bp_core_get_user_displayname( $user->user_login ) );
	}

	/**
	 * @group bp_core_get_user_displayname
	 */
	public function test_bp_core_get_user_displayname_bad_username() {
		$this->assertFalse( bp_core_get_user_displayname( 'i_dont_exist' ) );
	}

	/**
	 * @group bp_core_get_user_displayname
	 * @group cache
	 */
	public function test_bp_core_get_user_displayname_xprofile_populate_cache() {
		$xprofile_is_active = bp_is_active( 'xprofile' );
		buddypress()->active_components['xprofile'] = '1';

		$u = self::factory()->user->create( array(
			'display_name' => 'Foo',
		) );
		bp_core_get_user_displayname( $u );

		$this->assertSame( 'Foo', wp_cache_get( 'bp_user_fullname_' . $u, 'bp' ) );

		if ( ! $xprofile_is_active ) {
			unset( buddypress()->active_components['xprofile'] );
		}
	}

	/**
	 * @group bp_core_get_user_displayname
	 * @group cache
	 */
	public function test_bp_core_get_user_displayname_xprofile_bust_cache_after_xprofile_update() {
		$xprofile_is_active = bp_is_active( 'xprofile' );
		buddypress()->active_components['xprofile'] = '1';

		$u = self::factory()->user->create();
		xprofile_set_field_data( 1, $u, 'Foo Foo' );

		$this->assertFalse( wp_cache_get( 'bp_user_fullname_' . $u, 'bp' ) );

		if ( ! $xprofile_is_active ) {
			unset( buddypress()->active_components['xprofile'] );
		}
	}

	/**
	 * @group bp_core_get_user_displayname
	 */
	public function test_bp_core_get_user_displayname_xprofile_exists() {
		$xprofile_is_active = bp_is_active( 'xprofile' );
		buddypress()->active_components['xprofile'] = '1';

		$u = self::factory()->user->create();
		xprofile_set_field_data( 1, $u, 'Foo Foo' );

		$this->assertSame( 'Foo Foo', bp_core_get_user_displayname( $u ) );

		if ( ! $xprofile_is_active ) {
			unset( buddypress()->active_components['xprofile'] );
		}
	}

	/**
	 * @group bp_core_get_user_displayname
	 */
	public function test_bp_core_get_user_displayname_xprofile_does_not_exist() {
		$bp = buddypress();
		$xprofile_is_active = bp_is_active( 'xprofile' );
		$bp->active_components['xprofile'] = '1';

		$u = self::factory()->user->create( array(
			'display_name' => 'Foo Foo',
		) );

		// Delete directly because BP won't let you delete a required
		// field through the API
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_data} WHERE user_id = %d AND field_id = 1", $u ) );
		wp_cache_delete( 'bp_user_fullname_' . $u, 'bp' );
		wp_cache_delete( "{$u}:1", 'bp_xprofile_data' );

		$this->assertSame( '', xprofile_get_field_data( 1, $u ) );
		$this->assertSame( 'Foo Foo', bp_core_get_user_displayname( $u ) );
		$this->assertSame( 'Foo Foo', xprofile_get_field_data( 1, $u ) );

		if ( ! $xprofile_is_active ) {
			unset( $bp->active_components['xprofile'] );
		}
	}

	/**
	 * @group bp_core_get_user_displaynames
	 */
	public function test_bp_core_get_user_displayname_arrays_all_bad_entries() {
		$this->assertSame( array(), bp_core_get_user_displaynames( array( 0, 'foo', ) ) );
	}

	/**
	 * @group bp_core_get_user_displaynames
	 */
	public function test_bp_core_get_user_displaynames_all_uncached() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		xprofile_set_field_data( 1, $u1, 'Foo' );
		xprofile_set_field_data( 1, $u2, 'Bar' );

		$expected = array(
			$u1 => 'Foo',
			$u2 => 'Bar',
		);

		$this->assertSame( $expected, bp_core_get_user_displaynames( array( $u1, $u2, ) ) );
	}

	/**
	 * @group bp_core_get_user_displaynames
	 */
	public function test_bp_core_get_user_displaynames_one_not_in_xprofile() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create( array(
			'display_name' => 'Bar',
		) );

		xprofile_set_field_data( 1, $u1, 'Foo' );

		// Delete directly because BP won't let you delete a required
		// field through the API
		global $wpdb;
		$bp = buddypress();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_data} WHERE user_id = %d AND field_id = 1", $u2 ) );
		wp_cache_delete( 'bp_user_fullname_' . $u2, 'bp' );
		wp_cache_delete( "{$u2}:1", 'bp_xprofile_data' );

		$expected = array(
			$u1 => 'Foo',
			$u2 => 'Bar',
		);

		$this->assertSame( $expected, bp_core_get_user_displaynames( array( $u1, $u2, ) ) );
	}

	/**
	 * @group bp_core_get_user_displaynames
	 */
	public function test_bp_core_get_user_displaynames_one_in_cache() {
		$u1 = self::factory()->user->create();
		xprofile_set_field_data( 1, $u1, 'Foo' );

		// Fake the cache for $u2
		$u2 = 123;
		wp_cache_set( 'bp_user_fullname_' . $u2, 'Bar', 'bp' );

		$expected = array(
			$u1 => 'Foo',
			$u2 => 'Bar',
		);

		$this->assertSame( $expected, bp_core_get_user_displaynames( array( $u1, $u2, ) ) );
	}

	/**
	 * @group bp_members_migrate_signups
	 */
	public function test_bp_members_migrate_signups_standard() {
		$u = self::factory()->user->create();
		$u_obj = new WP_User( $u );

		// Fake an old-style registration
		$key = wp_generate_password( 32, false );
		update_user_meta( $u, 'activation_key', $key );

		global $wpdb;
		$wpdb->update(
			$wpdb->users,
			array( 'user_status' => '2', ),
			array( 'ID' => $u, ),
			array( '%d', ),
			array( '%d', )
		);
		clean_user_cache( $u );

		bp_members_migrate_signups();

		$found = BP_Signup::get();

		// Use email address as a sanity check
		$found_email = isset( $found['signups'][0]->user_email ) ? $found['signups'][0]->user_email : '';
		$this->assertSame( $u_obj->user_email, $found_email );

		// Check that activation keys match
		$found_key = isset( $found['signups'][0]->activation_key ) ? $found['signups'][0]->activation_key : '';
		$this->assertSame( $key, $found_key );
	}

	/**
	 * @group bp_members_migrate_signups
	 */
	public function test_bp_members_migrate_signups_activation_key_but_user_status_0() {
		$u = self::factory()->user->create();
		$u_obj = new WP_User( $u );

		// Fake an old-style registration
		$key = wp_generate_password( 32, false );
		update_user_meta( $u, 'activation_key', $key );

		// ...but ensure that user_status is 0. This mimics the
		// behavior of certain plugins that disrupt the BP registration
		// flow
		global $wpdb;
		$wpdb->update(
			$wpdb->users,
			array( 'user_status' => '0', ),
			array( 'ID' => $u, ),
			array( '%d', ),
			array( '%d', )
		);
		clean_user_cache( $u );

		bp_members_migrate_signups();

		// No migrations should have taken place
		$found = BP_Signup::get();
		$this->assertEmpty( $found['total'] );
	}

	/**
	 * @group bp_members_migrate_signups
	 */
	public function test_bp_members_migrate_signups_no_activation_key_but_user_status_2() {
		$u = self::factory()->user->create();
		$u_obj = new WP_User( $u );

		// Fake an old-style registration but without an activation key
		global $wpdb;
		$wpdb->update(
			$wpdb->users,
			array( 'user_status' => '2', ),
			array( 'ID' => $u, ),
			array( '%d', ),
			array( '%d', )
		);
		clean_user_cache( $u );

		bp_members_migrate_signups();

		// Use email address as a sanity check
		$found = BP_Signup::get();
		$found_email = isset( $found['signups'][0]->user_email ) ? $found['signups'][0]->user_email : '';
		$this->assertSame( $u_obj->user_email, $found_email );
	}

	/**
	 * @group bp_last_activity_migrate
	 * @expectedIncorrectUsage update_user_meta( $user_id, 'last_activity' )
	 * @expectedIncorrectUsage get_user_meta( $user_id, 'last_activity' )
	 */
	public function test_bp_last_activity_migrate() {
		// We explicitly do not want last_activity created, so use the
		// WP factory methods
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$time = time();
		$t1 = date( 'Y-m-d H:i:s', $time - 50 );
		$t2 = date( 'Y-m-d H:i:s', $time - 500 );
		$t3 = date( 'Y-m-d H:i:s', $time - 5000 );

		update_user_meta( $u1, 'last_activity', $t1 );
		update_user_meta( $u2, 'last_activity', $t2 );
		update_user_meta( $u3, 'last_activity', $t3 );

		// Create an existing entry in last_activity to test no dupes
		global $wpdb;
		$bp = buddypress();
		$wpdb->query( $wpdb->prepare(
			"INSERT INTO {$bp->members->table_name_last_activity}
				(`user_id`, `component`, `type`, `action`, `content`, `primary_link`, `item_id`, `date_recorded` ) VALUES
				( %d, %s, %s, %s, %s, %s, %d, %s )",
			$u2, $bp->members->id, 'last_activity', '', '', '', 0, $t1
		) );

		bp_last_activity_migrate();

		$expected = array(
			$u1 => $t1,
			$u2 => $t2,
			$u3 => $t3,
		);

		$found = array(
			$u1 => '',
			$u2 => '',
			$u3 => '',
		);

		foreach ( $found as $uid => $v ) {
			$found[ $uid ] = bp_get_user_last_activity( $uid );
		}

		$this->assertSame( $expected, $found );
	}

	/**
	 * @group bp_core_get_userid_from_nicename
	 */
	public function test_bp_core_get_userid_from_nicename_failure() {
		$this->assertSame( NULL, bp_core_get_userid_from_nicename( 'non_existent_user' ) );
	}

	/**
	 * @group bp_update_user_last_activity
	 */
	public function test_bp_last_activity_multi_network() {

		// Filter the usermeta key
		add_filter( 'bp_get_user_meta_key', array( $this, 'filter_usermeta_key' ) );

		// We explicitly do not want last_activity created, so use the
		// WP factory methods
		$user = self::factory()->user->create();
		$time = date( 'Y-m-d H:i:s', time() - 50 );

		// Update last user activity
		bp_update_user_last_activity( $user, $time );

		// Setup parameters to assert to be the same
		$expected = $time;
		$found    = bp_get_user_meta( $user, 'last_activity', true );

		$this->assertSame( $expected, $found );
	}

	/**
	 * @group bp_update_user_last_activity
	 * @global object $wpdb
	 * @param  string $key
	 * @return string
	 */
	public function filter_usermeta_key( $key ) {
		global $wpdb;
		return $wpdb->prefix . $key;
	}

	/**
	 * @group bp_core_process_spammer_status
	 */
	public function test_bp_core_process_spammer_status() {
		if ( is_multisite() ) {
			return;
		}

		$bp = buddypress();
		$displayed_user = $bp->displayed_user;

		$u1 = self::factory()->user->create();
		$bp->displayed_user->id = $u1;

		// Spam the user
		bp_core_process_spammer_status( $u1, 'spam' );

		$this->assertTrue( bp_is_user_spammer( $u1 ) );

		// Unspam the user
		bp_core_process_spammer_status( $u1, 'ham' );

		$this->assertFalse( bp_is_user_spammer( $u1 ) );

		// Reset displayed user
		$bp->displayed_user = $displayed_user;
	}

	/**
	 * @group bp_core_process_spammer_status
	 */
	public function test_bp_core_process_spammer_status_ms_bulk_spam() {
		if ( ! is_multisite() ) {
			return;
		}

		$bp = buddypress();
		$displayed_user = $bp->displayed_user;

		$u1 = self::factory()->user->create();
		$bp->displayed_user->id = $u1;

		// Bulk spam in network admin uses update_user_status
		update_user_status( $u1, 'spam', '1' );

		$this->assertTrue( bp_is_user_spammer( $u1 ) );

		// Unspam the user
		bp_core_process_spammer_status( $u1, 'ham' );

		$this->assertFalse( bp_is_user_spammer( $u1 ) );

		// Reset displayed user
		$bp->displayed_user = $displayed_user;
	}

	/**
	 * @group bp_core_process_spammer_status
	 */
	public function test_bp_core_process_spammer_status_ms_bulk_ham() {
		if ( ! is_multisite() ) {
			return;
		}

		$bp = buddypress();
		$displayed_user = $bp->displayed_user;

		$u1 = self::factory()->user->create();
		$bp->displayed_user->id = $u1;

		// Spam the user
		bp_core_process_spammer_status( $u1, 'spam' );

		$this->assertTrue( bp_is_user_spammer( $u1 ) );

		// Bulk unspam in network admin uses update_user_status
		update_user_status( $u1, 'spam', '0' );

		$this->assertFalse( bp_is_user_spammer( $u1 ) );

		// Reset displayed user
		$bp->displayed_user = $displayed_user;
	}

	/**
	 * @group bp_core_process_spammer_status
	 */
	public function test_bp_core_process_spammer_status_make_spam_user_filter() {
		add_filter( 'make_spam_user', array( $this, 'notification_filter_callback' ) );

		$u1 = self::factory()->user->create();
		$n = bp_core_process_spammer_status( $u1, 'spam' );

		remove_filter( 'make_spam_user', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'make_spam_user', $this->filter_fired );

	}

	public function test_bp_core_process_spammer_status_make_ham_user_filter() {
		add_filter( 'make_ham_user', array( $this, 'notification_filter_callback' ) );

		$u1 = self::factory()->user->create();
		$n = bp_core_process_spammer_status( $u1, 'ham' );

		remove_filter( 'make_ham_user', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'make_ham_user', $this->filter_fired );

	}

	public function test_bp_core_process_spammer_status_bp_make_spam_user_filter() {
		add_filter( 'bp_make_spam_user', array( $this, 'notification_filter_callback' ) );

		$u1 = self::factory()->user->create();
		$n = bp_core_process_spammer_status( $u1, 'spam' );

		remove_filter( 'bp_make_spam_user', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_make_spam_user', $this->filter_fired );

	}

	public function test_bp_core_process_spammer_status_bp_make_ham_user_filter() {
		add_filter( 'bp_make_ham_user', array( $this, 'notification_filter_callback' ) );

		$u1 = self::factory()->user->create();
		$n = bp_core_process_spammer_status( $u1, 'ham' );

		remove_filter( 'bp_make_ham_user', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_make_ham_user', $this->filter_fired );

	}

	public function notification_filter_callback( $value ) {
		$this->filter_fired = current_filter();
		return $value;
	}

	/**
	 * @ticket BP6208
	 *
	 * Note - it's not possible to test this when registration is not configured properly,
	 * because `bp_has_custom_signup_page()` stores its value in a static variable that cannot
	 * be toggled.
	 */
	public function test_wp_registration_url_should_return_bp_register_page_when_register_page_is_configured_properly() {
		$this->assertSame( bp_get_signup_page(), wp_registration_url() );
	}

	/**
	 * @group bp_core_activate_signup
	 */
	public function test_bp_core_activate_signup_password() {
		global $wpdb;


		$signups = array( 'no-blog' =>
			array( 'signup_id' => self::factory()->signup->create( array(
					'user_login'     => 'noblog',
					'user_email'     => 'noblog@example.com',
					'activation_key' => 'no-blog',
					'meta' => array(
						'field_1' => 'Foo Bar',
						'password' => 'foobar',
					),
			) ),
				'password' => 'foobar',
			),
		);

		if ( is_multisite() ) {
			$signups['ms-blog'] = array( 'signup_id' => self::factory()->signup->create( array(
					'user_login'     => 'msblog',
					'user_email'     => 'msblog@example.com',
					'domain'         => get_current_site()->domain,
					'path'           => get_current_site()->path . 'ms-blog',
					'title'          => 'Ding Dang',
					'activation_key' => 'ms-blog',
					'meta' => array(
						'field_1'  => 'Ding Dang',
						'password' => 'dingdang',
					),
				) ),
				'password' => 'dingdang',
			);
		}

		// Neutralize db errors
		$suppress = $wpdb->suppress_errors();

		foreach ( $signups as $key => $data ) {
			$u = bp_core_activate_signup( $key );

			$this->assertEquals( get_userdata( $u )->user_pass, $data['password'] );
		}

		$wpdb->suppress_errors( $suppress );
	}

	/**
	 * @ticket BP7461
	 *
	 * Test function before and after adding custom illegal names from WordPress.
	 */
	public function test_bp_core_get_illegal_names() {

		// Making sure BP custom illegals are in the array.
		$this->assertTrue( in_array( 'profile', bp_core_get_illegal_names(), true ) );
		$this->assertTrue( in_array( 'forums', bp_core_get_illegal_names(), true ) );

		add_filter( 'illegal_user_logins', array( $this, '_illegal_user_logins' ) );

		// Testing fake custom illegal names.
		$this->assertTrue( in_array( 'testuser', bp_core_get_illegal_names(), true ) );
		$this->assertTrue( in_array( 'admins', bp_core_get_illegal_names(), true ) );
		$this->assertFalse( in_array( 'buddypresss', bp_core_get_illegal_names(), true ) );

		// Making sure BP custom illegals are in the array after including the custom ones.
		$this->assertTrue( in_array( 'profile', bp_core_get_illegal_names(), true ) );
		$this->assertTrue( in_array( 'forums', bp_core_get_illegal_names(), true ) );

		remove_filter( 'illegal_user_logins', array( $this, '_illegal_user_logins' ) );
	}

	public function _illegal_user_logins() {
		return array(
			'testuser',
			'admins',
			'buddypress',
		);
	}
}
