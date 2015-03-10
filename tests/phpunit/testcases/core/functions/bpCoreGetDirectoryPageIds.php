<?php

/**
 * @group core
 * @covers ::bp_core_get_directory_page_ids
 */
class BP_Tests_Core_Functions_BpCoreGetDirectoryPageIds extends BP_UnitTestCase {
	public function test_bp_core_get_directory_page_ids_on_directory_page_to_trash() {
		$old_page_ids = bp_core_get_directory_page_ids();

		// Grab the and remove the first page.
		foreach ( $old_page_ids as $component => $page_id ) {
			$p = $page_id;
			unset( $old_page_ids[ $component ] );
			break;
		}

		// Move page to trash.
		wp_delete_post( $p, false );

		$new_page_ids = bp_core_get_directory_page_ids();

		$this->assertEquals( $old_page_ids, $new_page_ids );
	}

	public function test_bp_core_get_directory_page_ids_on_directory_page_delete() {
		$old_page_ids = bp_core_get_directory_page_ids();

		// Grab the and remove the first page.
		foreach ( $old_page_ids as $component => $page_id ) {
			$p = $page_id;
			unset( $old_page_ids[ $component ] );
			break;
		}

		// Force delete page.
		wp_delete_post( $p, true );

		$new_page_ids = bp_core_get_directory_page_ids();

		$this->assertEquals( $old_page_ids, $new_page_ids );
	}

	public function test_bp_core_get_directory_page_ids_on_non_directory_page_delete() {
		$old_page_ids = bp_core_get_directory_page_ids();

		$p = $this->factory->post->create( array(
			'post_status' => 'publish',
			'post_type' => 'page',
		) );

		// Force delete page.
		wp_delete_post( $p, true );

		$new_page_ids = bp_core_get_directory_page_ids();

		$this->assertEquals( $old_page_ids, $new_page_ids );
	}

	public function test_bp_core_get_directory_page_ids_non_active_component() {
		$old_page_ids = bp_core_get_directory_page_ids();
		$bp = buddypress();

		// Grab the and remove the first page.
		foreach ( $old_page_ids as $component => $page_id ) {
			$p = $page_id;
			$c = $component;
			unset( $old_page_ids[ $component ] );
			break;
		}

		// Deactivate component.
		unset( $bp->active_components[ $c ] );

		$new_page_ids = bp_core_get_directory_page_ids();

		// Restore components.
		$bp->active_components[ $c ] = 1;

		$this->assertEquals( $old_page_ids, $new_page_ids );
	}

	/**
	 * @ticket BP6280
	 */
	public function test_inactive_components_should_not_be_removed_if_status_is_all() {
		$old_page_ids = bp_core_get_directory_page_ids( 'all' );

		$page_ids = $old_page_ids;
		$page_ids['foo'] = 12345;

		bp_core_update_directory_page_ids( $page_ids );
		$found = bp_core_get_directory_page_ids( 'all' );

		$this->assertEquals( 12345, $found['foo'] );
	}

	/**
	 * @ticket BP6280
	 */
	public function test_inactive_components_should_be_removed_if_status_is_active() {
		$old_page_ids = bp_core_get_directory_page_ids( 'all' );

		$page_ids = $old_page_ids;
		$page_ids['foo'] = 12345;

		bp_core_update_directory_page_ids( $page_ids );
		$found = bp_core_get_directory_page_ids( 'active' );

		$this->assertFalse( isset( $found['foo'] ) );
	}

	/**
	 * @ticket BP6280
	 */
	public function test_inactive_components_should_be_removed_if_status_is_unspecified() {
		$old_page_ids = bp_core_get_directory_page_ids( 'all' );

		$page_ids = $old_page_ids;
		$page_ids['foo'] = 12345;

		bp_core_update_directory_page_ids( $page_ids );
		$found = bp_core_get_directory_page_ids( 'active' );

		$this->assertFalse( isset( $found['foo'] ) );
	}

	public function test_bp_core_get_directory_page_ids_should_contain_register_and_activet_pages_when_registration_is_open() {
		add_filter( 'bp_get_signup_allowed', '__return_true', 999 );

		$ac = buddypress()->active_components;
		bp_core_add_page_mappings( array_keys( $ac ) );

		$page_ids = bp_core_get_directory_page_ids();
		$page_names = array_keys( $page_ids );

		$this->assertContains( 'register', $page_names );
		$this->assertContains( 'activate', $page_names );

		remove_filter( 'bp_get_signup_allowed', '__return_true', 999 );
	}

	public function test_bp_core_get_directory_page_ids_should_not_contain_register_and_activet_pages_when_registration_is_closed() {

		// Make sure the pages exist, to verify they're filtered out.
		add_filter( 'bp_get_signup_allowed', '__return_true', 999 );
		$ac = buddypress()->active_components;
		bp_core_add_page_mappings( array_keys( $ac ) );
		remove_filter( 'bp_get_signup_allowed', '__return_true', 999 );

		// Get page ids
		$page_ids = bp_core_get_directory_page_ids();

		// Need to delete these pages as previously created.
		wp_delete_post( $page_ids['register'], true );
		wp_delete_post( $page_ids['activate'], true );

		add_filter( 'bp_get_signup_allowed', '__return_false', 999 );
		bp_core_add_page_mappings( array_keys( $ac ) );
		$page_ids = bp_core_get_directory_page_ids();
		remove_filter( 'bp_get_signup_allowed', '__return_false', 999 );

		$page_names = array_keys( $page_ids );

		$this->assertNotContains( 'register', $page_names );
		$this->assertNotContains( 'activate', $page_names );
	}

	public function test_bp_core_get_directory_pages_register_activate_page_created_signups_allowed() {
		add_filter( 'bp_get_signup_allowed', '__return_true', 999 );

		$ac = buddypress()->active_components;
		bp_core_add_page_mappings( array_keys( $ac ) );
		$directory_pages = bp_core_get_directory_pages();

		remove_filter( 'bp_get_signup_allowed', '__return_true', 999 );

		$this->assertTrue( isset( $directory_pages->register ) );
		$this->assertTrue( isset( $directory_pages->activate ) );

		$r = get_post( $directory_pages->register->id );
		$this->assertTrue( 'publish' == $r->post_status );

		$a = get_post( $directory_pages->activate->id );
		$this->assertTrue( 'publish' == $a->post_status );
	}

	public function test_bp_core_get_directory_pages_register_activate_page_notcreated_signups_allowed() {
		add_filter( 'bp_get_signup_allowed', '__return_false', 999 );

		$ac = buddypress()->active_components;
		bp_core_add_page_mappings( array_keys( $ac ) );

		remove_filter( 'bp_get_signup_allowed', '__return_false', 999 );

		add_filter( 'bp_get_signup_allowed', '__return_true', 999 );

		$directory_pages = bp_core_get_directory_pages();

		remove_filter( 'bp_get_signup_allowed', '__return_true', 999 );

		$this->assertFalse( isset( $directory_pages->register ) );
		$this->assertFalse( isset( $directory_pages->activate ) );
	}

	public function test_bp_core_get_directory_pages_register_activate_page_created_signups_notallowed() {
		add_filter( 'bp_get_signup_allowed', '__return_true', 999 );

		$ac = buddypress()->active_components;
		bp_core_add_page_mappings( array_keys( $ac ) );

		remove_filter( 'bp_get_signup_allowed', '__return_true', 999 );

		add_filter( 'bp_get_signup_allowed', '__return_false', 999 );

		$directory_pages = bp_core_get_directory_pages();

		remove_filter( 'bp_get_signup_allowed', '__return_false', 999 );

		$this->assertTrue( isset( $directory_pages->register ) );
		$this->assertTrue( isset( $directory_pages->activate ) );

		$r = get_post( $directory_pages->register->id );
		$this->assertTrue( 'publish' == $r->post_status );

		$a = get_post( $directory_pages->activate->id );
		$this->assertTrue( 'publish' == $a->post_status );
	}

	public function test_bp_core_get_directory_pages_register_activate_page_notcreated_signups_notallowed() {

		add_filter( 'bp_get_signup_allowed', '__return_false', 999 );

		$ac = buddypress()->active_components;
		bp_core_add_page_mappings( array_keys( $ac ) );
		$directory_pages = bp_core_get_directory_pages();

		remove_filter( 'bp_get_signup_allowed', '__return_false', 999 );

		$this->assertFalse( isset( $directory_pages->register ) );
		$this->assertFalse( isset( $directory_pages->activate ) );
	}

	public function test_bp_core_get_directory_pages_pages_settings_update() {
		// Set the cache
		$pages = bp_core_get_directory_pages();

		// Mess with it but put it back
		$v = bp_get_option( 'bp-pages' );
		bp_update_option( 'bp-pages', 'foo' );

		$this->assertFalse( wp_cache_get( 'directory_pages', 'bp' ) );

		bp_update_option( 'bp-pages', $v );
	}

	public function test_bp_core_get_directory_pages_multisite_delete_post_with_same_bp_page_id() {
		if ( ! is_multisite() ) {
			return;
		}

		$dir_pages = bp_core_get_directory_pages();

		// create a blog
		$u = $this->factory->user->create();
		$b1 = $this->factory->blog->create( array( 'user_id' => $u ) );

		// switch to blog and create some dummy posts until we reach a post ID that
		// matches our BP activity page ID
		switch_to_blog( $b1 );
		$p = $this->factory->post->create();
		while( $p <= $dir_pages->activity->id ) {
			$p = $this->factory->post->create();
		}

		// delete the post that matches the BP activity page ID on this sub-site
		wp_delete_post( $dir_pages->activity->id, true );

		// restore blog
		restore_current_blog();

		// refetch BP directory pages
		$dir_pages = bp_core_get_directory_pages();

		// Now verify that our BP activity page was not wiped out
		$this->assertNotEmpty( $dir_pages->activity );
	}
}
