<?php

require_once dirname( __FILE__ ) . '/factory.php';

class BP_UnitTestCase extends WP_UnitTestCase {

	protected $temp_has_bp_moderate = array();
	protected static $cached_SERVER_NAME = null;

	/**
	 * A flag indicating whether an autocommit has been detected inside of a test.
	 *
	 * @since 2.4.0
	 *
	 * @var bool
	 */
	protected $autocommitted = false;

	/**
	 * A list of components that have been deactivated during a test.
	 *
	 * @since 2.4.0
	 *
	 * @var array
	 */
	protected $deactivated_components = array();

	/**
	 * Cribbed from WP so that the self::factory() call comes from this class.
	 *
	 * @since 3.0.0
	 */
	public static function setUpBeforeClass() {
		global $wpdb;

		// Fake WP mail globals, to avoid errors
		add_filter( 'wp_mail', array( 'BP_UnitTestCase', 'setUp_wp_mail' ) );
		add_filter( 'wp_mail_from', array( 'BP_UnitTestCase', 'tearDown_wp_mail' ) );

		$c = self::get_called_class();
		if ( ! method_exists( $c, 'wpSetUpBeforeClass' ) ) {
			self::commit_transaction();
			return;
		}

		call_user_func( array( $c, 'wpSetUpBeforeClass' ), self::factory() );

		self::commit_transaction();
	}

	public function setUp() {
		parent::setUp();

		/*
		 * WP's test suite wipes out BP's directory page mappings with `_delete_all_posts()`.
		 * We must reestablish them before our tests can be successfully run.
		 */
		bp_core_add_page_mappings( bp_get_option( 'bp-active-components' ), 'delete' );


		$this->factory = new BP_UnitTest_Factory;

		// Fixes warnings in multisite functions
		$_SERVER['REMOTE_ADDR'] = '';
		global $wpdb;

		// Clean up after autocommits.
		add_action( 'bp_blogs_recorded_existing_blogs', array( $this, 'set_autocommit_flag' ) );

		// Make sure Activity actions are reset before each test
		$this->reset_bp_activity_actions();

		// Make sure all Post types activities globals are reset before each test
		$this->reset_bp_activity_post_types_globals();
	}

	public function tearDown() {
		global $wpdb;

		remove_action( 'bp_blogs_recorded_existing_blogs', array( $this, 'set_autocommit_flag' ) );

		parent::tearDown();

		// If we detect that a COMMIT has been triggered during the test, clean up blog and user fixtures.
		if ( $this->autocommitted ) {
			if ( is_multisite() ) {
				foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE blog_id != 1" ) as $blog_id ) {
					wpmu_delete_blog( $blog_id, true );
				}
			}

			foreach ( $wpdb->get_col( "SELECT ID FROM $wpdb->users WHERE ID != 1" ) as $user_id ) {
				if ( is_multisite() ) {
					wpmu_delete_user( $user_id );
				} else {
					wp_delete_user( $user_id );
				}
			}
		}

		$this->commit_transaction();

		// Reactivate any components that have been deactivated.
		foreach ( $this->deactivated_components as $component ) {
			buddypress()->active_components[ $component ] = 1;
		}
		$this->deactivated_components = array();
	}

	/**
	 * Multisite-agnostic way to delete a user from the database.
	 *
	 * @since 3.0.0
	 */
	public static function delete_user( $user_id ) {
		$deleted = parent::delete_user( $user_id );

		// When called in tearDownAfterClass(), BP's cleanup functions may no longer be hooked.
		if ( bp_is_active( 'activity' ) ) {
			bp_activity_remove_all_user_data( $user_id );
		}

		return $deleted;
	}

	function clean_up_global_scope() {
		buddypress()->bp_nav                = buddypress()->bp_options_nav = buddypress()->action_variables = buddypress()->canonical_stack = buddypress()->unfiltered_uri = $GLOBALS['bp_unfiltered_uri'] = array();
		buddypress()->current_component     = buddypress()->current_item = buddypress()->current_action = buddypress()->current_member_type = '';
		buddypress()->unfiltered_uri_offset = 0;
		buddypress()->is_single_item        = false;
		buddypress()->current_user          = new stdClass();
		buddypress()->displayed_user        = new stdClass();
		buddypress()->loggedin_user         = new stdClass();
		buddypress()->pages                 = array();

		parent::clean_up_global_scope();
	}

	/**
	 * Returns a factory that can be used across tests, even in static methods.
	 *
	 * @since 3.0
	 *
	 * @return BP_UnitTest_Factory
	 */
	protected static function factory() {
		static $factory = null;
		if ( ! $factory ) {
			$factory = new BP_UnitTest_Factory();
		}
		return $factory;
	}

	protected function reset_bp_activity_actions() {
		buddypress()->activity->actions = new stdClass();

		/**
		 * Populate the global with default activity actions only
		 * before each test.
		 */
		do_action( 'bp_register_activity_actions' );
	}

	protected function reset_bp_activity_post_types_globals() {
		global $wp_post_types;

		// Remove all remaining tracking arguments to each post type
		foreach ( $wp_post_types as $post_type => $post_type_arg ) {
			if ( post_type_supports( $post_type, 'buddypress-activity' ) ) {
				remove_post_type_support( $post_type, 'buddypress-activity' );
			}

			if ( isset( $post_type_arg->bp_activity ) ) {
				unset( $post_type_arg->bp_activity );
			}
		}

		buddypress()->activity->track = array();
	}

	function assertPreConditions() {
		parent::assertPreConditions();

		// Reinit some of the globals that might have been cleared by BP_UnitTestCase::clean_up_global_scope().
		// This is here because it didn't work in clean_up_global_scope(); I don't know why.
		do_action( 'bp_setup_globals' );
	}

	function go_to( $url ) {
		$GLOBALS['bp']->loggedin_user = NULL;
		$GLOBALS['bp']->pages = bp_core_get_directory_pages();

		parent::go_to( $url );

		do_action( 'bp_init' );
	}

	/**
	 * WP's core tests use wp_set_current_user() to change the current
	 * user during tests. BP caches the current user differently, so we
	 * have to do a bit more work to change it
	 */
	public static function set_current_user( $user_id ) {
		$bp = buddypress();

		$bp->loggedin_user->id = $user_id;
		$bp->loggedin_user->fullname       = bp_core_get_user_displayname( $user_id );
		$bp->loggedin_user->is_super_admin = $bp->loggedin_user->is_site_admin = is_super_admin( $user_id );
		$bp->loggedin_user->domain         = bp_core_get_user_domain( $user_id );
		$bp->loggedin_user->userdata       = bp_core_get_core_userdata( $user_id );

		wp_set_current_user( $user_id );
	}

	public static function add_user_to_group( $user_id, $group_id, $args = array() ) {
		$r = wp_parse_args( $args, array(
			'date_modified' => bp_core_current_time(),
			'is_confirmed'  => 1,
			'is_admin'      => 0,
			'is_mod'        => 0,
			'invite_sent'   => 0,
			'inviter_id'    => 0,
		) );

		$new_member                = new BP_Groups_Member;
		$new_member->group_id      = $group_id;
		$new_member->user_id       = $user_id;
		$new_member->inviter_id    = 0;
		$new_member->is_admin      = $r['is_admin'];
		$new_member->is_mod        = $r['is_mod'];
		$new_member->user_title    = '';
		$new_member->date_modified = $r['date_modified'];
		$new_member->is_confirmed  = $r['is_confirmed'];
		$new_member->invite_sent   = $r['invite_sent'];
		$new_member->inviter_id    = $r['inviter_id'];

		$new_member->save();
		return $new_member->id;
	}

	/**
	 * We can't use grant_super_admin() because we will need to modify
	 * the list more than once, and grant_super_admin() can only be run
	 * once because of its global check
	 */
	public function grant_super_admin( $user_id ) {
		global $super_admins;
		if ( ! is_multisite() ) {
			return;
		}

		$user = get_userdata( $user_id );
		$super_admins[] = $user->user_login;
	}

	public function restore_admins() {
		// We assume that the global can be wiped out
		// @see grant_super_admin()
		unset( $GLOBALS['super_admins'] );
	}

	public function grant_bp_moderate( $user_id ) {
		if ( ! isset( $this->temp_has_bp_moderate[ $user_id ] ) ) {
			$this->temp_has_bp_moderate[ $user_id ] = 1;
		}
		add_filter( 'bp_current_user_can', array( $this, 'grant_bp_moderate_cb' ), 10, 2 );
	}

	public function revoke_bp_moderate( $user_id ) {
		if ( isset( $this->temp_has_bp_moderate[ $user_id ] ) ) {
			unset( $this->temp_has_bp_moderate[ $user_id ] );
		}
		remove_filter( 'bp_current_user_can', array( $this, 'grant_bp_moderate_cb' ), 10 );
	}

	public function grant_bp_moderate_cb( $retval, $capability ) {
		$current_user = bp_loggedin_user_id();
		if ( ! isset( $this->temp_has_bp_moderate[ $current_user ] ) ) {
			return $retval;
		}

		if ( 'bp_moderate' == $capability ) {
			$retval = true;
		}

		return $retval;
	}

	/**
	 * Go to the root blog. This helps reset globals after moving between
	 * blogs.
	 */
	public function go_to_root() {
		$blog_1_url = get_blog_option( 1, 'home' );
		$this->go_to( str_replace( $blog_1_url, '', trailingslashit( bp_get_root_domain() ) ) );
	}

	/**
	 * Set up globals necessary to avoid errors when using wp_mail()
	 */
	public static function setUp_wp_mail( $args ) {
		if ( isset( $_SERVER['SERVER_NAME'] ) ) {
			self::$cached_SERVER_NAME = $_SERVER['SERVER_NAME'];
		}

		$_SERVER['SERVER_NAME'] = 'example.com';

		// passthrough
		return $args;
	}

	/**
	 * Tear down globals set up in setUp_wp_mail()
	 */
	public static function tearDown_wp_mail( $args ) {
		if ( ! empty( self::$cached_SERVER_NAME ) ) {
			$_SERVER['SERVER_NAME'] = self::$cached_SERVER_NAME;
			self::$cached_SERVER_NAME = '';
		} else {
			unset( $_SERVER['SERVER_NAME'] );
		}

		// passthrough
		return $args;
	}

	/**
	 * Commit a MySQL transaction.
	 */
	public static function commit_transaction() {
		global $wpdb;
		$wpdb->query( 'COMMIT;' );
	}

	/**
	 * Clean up created directories/files
	 */
	public function rrmdir( $dir ) {
		// Make sure we are only removing files/dir from uploads
		if ( 0 !== strpos( $dir, bp_core_avatar_upload_path() ) ) {
			return;
		}

		$d = glob( $dir . '/*' );

		if ( ! empty( $d ) ) {
			foreach ( $d as $file ) {
				if ( is_dir( $file ) ) {
					$this->rrmdir( $file );
				} else {
					@unlink( $file );
				}
			}
		}

		@rmdir( $dir );
	}

	/**
	 * Set a flag that an autocommit has taken place inside of a test method.
	 *
	 * @since 2.4.0
	 */
	public function set_autocommit_flag() {
		$this->autocommitted = true;
	}

	/**
	 * Deactivate a component for the duration of a test.
	 *
	 * @since 2.4.0
	 *
	 * @param string $component Component name.
	 */
	public function deactivate_component( $component ) {
		$is_active = isset( buddypress()->active_components[ $component ] );

		if ( ! isset( $component ) ) {
			return false;
		}

		unset( buddypress()->active_components[ $component ] );
		$this->deactivated_components[] = $component;
	}

	/**
	 * Fake an attachment upload (doesn't actually upload a file).
	 *
	 * @param string $file Absolute path to valid file.
	 * @param int $parent Optional. Post ID to attach the new post to.
	 * @return int Attachment post ID.
	 */
	public function fake_attachment_upload( $file, $parent = 0 ) {
		$mime = wp_check_filetype( $file );
		if ( $mime ) {
			$type = $mime['type'];
		} else {
			$type = '';
		}

		$url = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . basename( $file );
		$attachment = array(
			'guid'           => 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . $url,
			'post_content'   => '',
			'post_mime_type' => $type,
			'post_parent'    => $parent,
			'post_title'     => basename( $file ),
			'post_type'      => 'attachment',
		);

		$id = wp_insert_attachment( $attachment, $url, $parent );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $url ) );

		return $id;
	}
}
