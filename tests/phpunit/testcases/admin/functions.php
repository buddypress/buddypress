<?php
/**
 * @group admin
 */
class BP_Tests_Admin_Functions extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();
		$this->old_current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		if ( ! function_exists( 'bp_admin' ) ) {
			require_once( BP_PLUGIN_DIR . 'bp-core/bp-core-admin.php' );
		}

		if ( ! function_exists( 'bp_new_site' ) ) {
			bp_admin();
		}
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	public function test_bp_admin_list_table_current_bulk_action() {
		$_REQUEST['action'] = 'foo';
		$_REQUEST['action2'] = '-1';
		$this->assertEquals( bp_admin_list_table_current_bulk_action(), 'foo' );

		$_REQUEST['action'] = '-1';
		$_REQUEST['action2'] = 'foo';
		$this->assertEquals( bp_admin_list_table_current_bulk_action(), 'foo' );

		$_REQUEST['action'] = 'bar';
		$_REQUEST['action2'] = 'foo';
		$this->assertEquals( bp_admin_list_table_current_bulk_action(), 'foo' );
	}

	/**
	 * @group bp_core_admin_get_active_components_from_submitted_settings
	 */
	public function test_bp_core_admin_get_active_components_from_submitted_settings() {
		$get_action = isset( $_GET['action'] ) ? $_GET['action'] : null;
		$ac = buddypress()->active_components;

		// Standard deactivation from All screen
		unset( $_GET['action'] );
		buddypress()->active_components = array(
			'activity' => 1,
			'friends' => 1,
			'groups' => 1,
			'members' => 1,
			'messages' => 1,
			'settings' => 1,
			'xprofile' => 1,
		);

		$submitted = array(
			'groups' => 1,
			'members' => 1,
			'messages' => 1,
			'settings' => 1,
			'xprofile' => 1,
		);

		$this->assertEquals( bp_core_admin_get_active_components_from_submitted_settings( $submitted ), array( 'groups' => 1, 'members' => 1, 'messages' => 1, 'settings' => 1, 'xprofile' => 1 ) );

		// Activating deactivated components from the Inactive screen
		$_GET['action'] = 'inactive';
		buddypress()->active_components = array(
			'activity' => 1,
			'members' => 1,
			'messages' => 1,
			'settings' => 1,
			'xprofile' => 1,
		);

		$submitted2 = array(
			'groups' => 1,
		);

		$this->assertEquals( bp_core_admin_get_active_components_from_submitted_settings( $submitted2 ), array( 'activity' => 1, 'groups' => 1, 'members' => 1, 'messages' => 1, 'settings' => 1, 'xprofile' => 1 ) );

		// Activating from the Retired screen
		$_GET['action'] = 'retired';
		buddypress()->active_components = array(
			'activity' => 1,
			'members' => 1,
			'messages' => 1,
			'settings' => 1,
			'xprofile' => 1,
		);

		$submitted3 = array(
			'forums' => 1,
		);

		$this->assertEquals( bp_core_admin_get_active_components_from_submitted_settings( $submitted3 ), array( 'activity' => 1, 'forums' => 1, 'members' => 1, 'messages' => 1, 'settings' => 1, 'xprofile' => 1 ) );

		// Deactivating from the Retired screen
		$_GET['action'] = 'retired';
		buddypress()->active_components = array(
			'activity' => 1,
			'forums' => 1,
			'members' => 1,
			'messages' => 1,
			'settings' => 1,
			'xprofile' => 1,
		);

		$submitted4 = array();

		$this->assertEquals( bp_core_admin_get_active_components_from_submitted_settings( $submitted4 ), array( 'activity' => 1, 'members' => 1, 'messages' => 1, 'settings' => 1, 'xprofile' => 1 ) );

		// reset
		if ( $get_action ) {
			$_GET['action'] = $get_action;
		} else {
			unset( $_GET['action'] );
		}

		buddypress()->active_components = $ac;
	}

	/**
	 * @group BP6244
	 * @group bp_core_admin_get_active_components_from_submitted_settings
	 */
	public function test_bp_core_admin_get_active_components_from_submitted_settings_should_keep_custom_component_directory_page() {
		$bp = buddypress();
		$reset_active_components = $bp->active_components;

		// Create and activate the foo component
		$bp->foo = new BP_Component;
		$bp->foo->id   = 'foo';
		$bp->foo->slug = 'foo';
		$bp->foo->name = 'Foo';
		$bp->active_components[ $bp->foo->id ] = 1;
		$new_page_ids = array( $bp->foo->id => $this->factory->post->create( array(
			'post_type'  => 'page',
			'post_title' => $bp->foo->name,
			'post_name'  => $bp->foo->slug,
		) ) );

		$page_ids = array_merge( $new_page_ids, (array) bp_core_get_directory_page_ids( 'all' ) );
		bp_core_update_directory_page_ids( $page_ids );

		$bp->active_components = bp_core_admin_get_active_components_from_submitted_settings( $reset_active_components );
		bp_core_add_page_mappings( $bp->active_components );

		$this->assertContains( $bp->foo->id, array_keys( bp_core_get_directory_page_ids( 'all' ) ) );

		// Reset buddypress() vars
		$bp->active_components = $reset_active_components;
	}

	/**
	 * @group bp_core_activation_notice
	 */
	public function test_bp_core_activation_notice_register_activate_pages_notcreated_signup_allowed() {
		$bp = buddypress();
		$reset_bp_pages = $bp->pages;
		$reset_admin_notices = $bp->admin->notices;

		// Reset pages
		$bp->pages = bp_core_get_directory_pages();

		add_filter( 'bp_get_signup_allowed', '__return_true', 999 );

		bp_core_activation_notice();

		remove_filter( 'bp_get_signup_allowed', '__return_true', 999 );

		$missing_pages = array();
		foreach( buddypress()->admin->notices as $notice ) {
			preg_match_all( '/<strong>(.+?)<\/strong>/', $notice['message'], $missing_pages );
		}

		$this->assertContains( 'Register', $missing_pages[1] );
		$this->assertContains( 'Activate', $missing_pages[1] );

		// Reset buddypress() vars
		$bp->pages = $reset_bp_pages;
		$bp->admin->notices = $reset_admin_notices;
	}

	/**
	 * @group bp_core_activation_notice
	 */
	public function test_bp_core_activation_notice_register_activate_pages_created_signup_allowed() {
		$bp = buddypress();
		$reset_bp_pages = $bp->pages;
		$reset_admin_notices = $bp->admin->notices;

		add_filter( 'bp_get_signup_allowed', '__return_true', 999 );

		$ac = buddypress()->active_components;
		bp_core_add_page_mappings( array_keys( $ac ) );

		// Reset pages
		$bp->pages = bp_core_get_directory_pages();

		bp_core_activation_notice();

		remove_filter( 'bp_get_signup_allowed', '__return_true', 999 );

		$missing_pages = array();
		foreach( buddypress()->admin->notices as $notice ) {
			preg_match_all( '/<strong>(.+?)<\/strong>/', $notice['message'], $missing_pages );
		}

		$this->assertNotContains( 'Register', $missing_pages[1] );
		$this->assertNotContains( 'Activate', $missing_pages[1] );

		// Reset buddypress() vars
		$bp->pages = $reset_bp_pages;
		$bp->admin->notices = $reset_admin_notices;
	}

	/**
	 * @ticket BP6936
	 */
	public function test_email_type_descriptions_should_match_when_split_terms_exist() {
		global $wpdb;

		// Delete all existing email types and descriptions.
		$emails = get_posts( array(
			'fields' => 'ids',
			'post_type' => bp_get_email_post_type(),
		) );
		foreach ( $emails as $email ) {
			wp_delete_post( $email, true );
		}

		$descriptions = get_terms( bp_get_email_tax_type(), array(
			'fields' => 'ids',
			'hide_empty' => false,
		) );
		foreach ( $descriptions as $description ) {
			wp_delete_term( (int) $description, bp_get_email_tax_type() );
		}

		// Fake the existence of split terms by offsetting the term_taxonomy table.
		$wpdb->insert( $wpdb->term_taxonomy, array( 'term_id' => 9999, 'taxonomy' => 'post_tag', 'description' => 'foo description', 'parent' => 0, 'count' => 0 ) );

		require_once( BP_PLUGIN_DIR . '/bp-core/admin/bp-core-admin-schema.php' );
		bp_core_install_emails();

		$d_terms = get_terms( bp_get_email_tax_type(), array(
			'hide_empty' => false,
		) );

		$correct_descriptions = bp_email_get_type_schema();
		foreach ( $d_terms as $d_term ) {
			$correct_description = $correct_descriptions[ $d_term->slug ];
			$this->assertSame( $correct_description, $d_term->description );
		}
	}
}
