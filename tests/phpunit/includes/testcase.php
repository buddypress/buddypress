<?php

/**
 * WP's test suite wipes out BP's directory page mappings with _delete_all_posts()
 * We must reestablish them before our tests can be successfully run
 */
bp_core_add_page_mappings( bp_get_option( 'bp-active-components' ), 'delete' );

require_once dirname( __FILE__ ) . '/factory.php';

class BP_UnitTestCase extends WP_UnitTestCase {

	protected $temp_has_bp_moderate = array();
	protected static $cached_SERVER_NAME = null;

	public static function setUpBeforeClass() {
		// Fake WP mail globals, to avoid errors
		add_filter( 'wp_mail', array( 'BP_UnitTestCase', 'setUp_wp_mail' ) );
		add_filter( 'wp_mail_from', array( 'BP_UnitTestCase', 'tearDown_wp_mail' ) );
	}

	public function setUp() {
		parent::setUp();

		// There's a bug in the multisite tests that causes the
		// transaction rollback to fail for the first user created,
		// which busts every other attempt to create users. This is a
		// hack workaround.
		global $wpdb;
		if ( is_multisite() ) {
			$user_1 = get_user_by( 'login', 'user 1' );
			if ( $user_1 ) {
				$wpdb->delete( $wpdb->users, array( 'ID' => $user_1->ID ) );
				clean_user_cache( $user_1 );
			}
		}


		$this->factory = new BP_UnitTest_Factory;

		// Fixes warnings in multisite functions
		$_SERVER['REMOTE_ADDR'] = '';
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

	function tearDown() {
		parent::tearDown();

		/**
		 * Sites created by the WP_UnitTest_Factory_For_Blog sometimes are not removed when the current
		 * transaction is rolled back. This requires further investigation to understand the root cause
		 * but for now, we'll empty out the blogs table manually.
		 */
		global $wpdb;

		if ( is_multisite() ) {
			$blogs = wp_get_sites();
			foreach ( $blogs as $blog ) {
				if ( (int) $blog['blog_id'] !== 1 ) {
					wpmu_delete_blog( $blog['blog_id'], true );
				}
			}
		}
	}

	function assertPreConditions() {
		parent::assertPreConditions();

		// Reinit some of the globals that might have been cleared by BP_UnitTestCase::clean_up_global_scope().
		// This is here because it didn't work in clean_up_global_scope(); I don't know why.
		do_action( 'bp_setup_globals' );
	}

	function go_to( $url ) {
		global $wpdb;
		global $current_site, $current_blog, $blog_id, $switched, $_wp_switched_stack, $public, $table_prefix, $current_user, $wp_roles;

		// note: the WP and WP_Query classes like to silently fetch parameters
		// from all over the place (globals, GET, etc), which makes it tricky
		// to run them more than once without very carefully clearing everything
		$_GET = $_POST = array();
		foreach (array('query_string', 'id', 'postdata', 'authordata', 'day', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'pagenow') as $v) {
			if ( isset( $GLOBALS[$v] ) ) unset( $GLOBALS[$v] );
		}
		$parts = parse_url($url);
		if (isset($parts['scheme'])) {
			// set the HTTP_HOST
			$GLOBALS['_SERVER']['HTTP_HOST'] = $parts['host'];

			$req = $parts['path'];
			if (isset($parts['query'])) {
				$req .= '?' . $parts['query'];
				// parse the url query vars into $_GET
				parse_str($parts['query'], $_GET);
			}
		} else {
			$req = $url;
		}
		if ( ! isset( $parts['query'] ) ) {
			$parts['query'] = '';
		}

		// Scheme
		if ( 0 === strpos( $req, '/wp-admin' ) && force_ssl_admin() ) {
			$_SERVER['HTTPS'] = 'on';
		} else {
			unset( $_SERVER['HTTPS'] );
		}

		// Set this for bp_core_set_uri_globals()
		$GLOBALS['_SERVER']['REQUEST_URI'] = $req;
		unset($_SERVER['PATH_INFO']);

		// setup $current_site and $current_blog globals for multisite based on
		// REQUEST_URI; mostly copied from /wp-includes/ms-settings.php
		if ( is_multisite() ) {
			$current_blog = $current_site = $blog_id = null;

			$domain = addslashes( $_SERVER['HTTP_HOST'] );
			if ( false !== strpos( $domain, ':' ) ) {
				if ( substr( $domain, -3 ) == ':80' ) {
					$domain = substr( $domain, 0, -3 );
					$_SERVER['HTTP_HOST'] = substr( $_SERVER['HTTP_HOST'], 0, -3 );
				} elseif ( substr( $domain, -4 ) == ':443' ) {
					$domain = substr( $domain, 0, -4 );
					$_SERVER['HTTP_HOST'] = substr( $_SERVER['HTTP_HOST'], 0, -4 );
				}
			}
			$path = stripslashes( $_SERVER['REQUEST_URI'] );

			// Get a cleaned-up version of the wp_version string
			// (strip -src, -alpha, etc which may trip up version_compare())
			$wp_version = (float) $GLOBALS['wp_version'];
			if ( version_compare( $wp_version, '3.9', '>=' ) ) {

				if ( is_admin() ) {
					$path = preg_replace( '#(.*)/wp-admin/.*#', '$1/', $path );
				}
				list( $path ) = explode( '?', $path );

				// Are there even two networks installed?
				$one_network = $wpdb->get_row( "SELECT * FROM $wpdb->site LIMIT 2" ); // [sic]
				if ( 1 === $wpdb->num_rows ) {
					$current_site = wp_get_network( $one_network );
				} elseif ( 0 === $wpdb->num_rows ) {
					ms_not_installed();
				}

				if ( empty( $current_site ) ) {
					$current_site = get_network_by_path( $domain, $path, 1 );
				}

				if ( empty( $current_site ) ) {
					ms_not_installed();
				} elseif ( $path === $current_site->path ) {
					$current_blog = get_site_by_path( $domain, $path );
				} else {
					// Search the network path + one more path segment (on top of the network path).
					$current_blog = get_site_by_path( $domain, $path, substr_count( $current_site->path, '/' ) );
				}

				// The network declared by the site trumps any constants.
				if ( $current_blog && $current_blog->site_id != $current_site->id ) {
					$current_site = wp_get_network( $current_blog->site_id );
				}

				// If we don't have a network by now, we have a problem.
				if ( empty( $current_site ) ) {
					ms_not_installed();
				}

				// @todo What if the domain of the network doesn't match the current site?
				$current_site->cookie_domain = $current_site->domain;
				if ( 'www.' === substr( $current_site->cookie_domain, 0, 4 ) ) {
					$current_site->cookie_domain = substr( $current_site->cookie_domain, 4 );
				}

				// Figure out the current network's main site.
				if ( ! isset( $current_site->blog_id ) ) {
					if ( $current_blog && $current_blog->domain === $current_site->domain && $current_blog->path === $current_site->path ) {
						$current_site->blog_id = $current_blog->blog_id;
					} else {
						// @todo we should be able to cache the blog ID of a network's main site easily.
						$current_site->blog_id = $wpdb->get_var( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE domain = %s AND path = %s",
							$current_site->domain, $current_site->path ) );
					}
				}

				$blog_id = $current_blog->blog_id;
				$public  = $current_blog->public;

				if ( empty( $current_blog->site_id ) ) {
					// This dates to [MU134] and shouldn't be relevant anymore,
					// but it could be possible for arguments passed to insert_blog() etc.
					$current_blog->site_id = 1;
				}

				$site_id = $current_blog->site_id;
				wp_load_core_site_options( $site_id );


			// Pre WP 3.9
			} else {

				$domain = rtrim( $domain, '.' );
				$cookie_domain = $domain;
				if ( substr( $cookie_domain, 0, 4 ) == 'www.' )
					$cookie_domain = substr( $cookie_domain, 4 );

				$path = preg_replace( '|([a-z0-9-]+.php.*)|', '', $GLOBALS['_SERVER']['REQUEST_URI'] );
				$path = str_replace ( '/wp-admin/', '/', $path );
				$path = preg_replace( '|(/[a-z0-9-]+?/).*|', '$1', $path );

				$GLOBALS['current_site'] = wpmu_current_site();
				if ( ! isset( $GLOBALS['current_site']->blog_id ) && ! empty( $GLOBALS['current_site'] ) )
					$GLOBALS['current_site']->blog_id = $wpdb->get_var( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE domain = %s AND path = %s", $GLOBALS['current_site']->domain, $GLOBALS['current_site']->path ) );

				$blogname = htmlspecialchars( substr( $GLOBALS['_SERVER']['REQUEST_URI'], strlen( $path ) ) );
				if ( false !== strpos( $blogname, '/' ) )
					$blogname = substr( $blogname, 0, strpos( $blogname, '/' ) );
				if ( false !== strpos( $blogname, '?' ) )
					$blogname = substr( $blogname, 0, strpos( $blogname, '?' ) );
				$reserved_blognames = array( 'page', 'comments', 'blog', 'wp-admin', 'wp-includes', 'wp-content', 'files', 'feed' );
				if ( $blogname != '' && ! in_array( $blogname, $reserved_blognames ) && ! is_file( $blogname ) )
					$path .= $blogname . '/';

				$GLOBALS['current_blog'] = get_blog_details( array( 'domain' => $domain, 'path' => $path ), false );

				unset($reserved_blognames);

				if ( $GLOBALS['current_site'] && ! $GLOBALS['current_blog'] ) {
					$GLOBALS['current_blog'] = get_blog_details( array( 'domain' => $GLOBALS['current_site']->domain, 'path' => $GLOBALS['current_site']->path ), false );
				}

				$GLOBALS['blog_id'] = $GLOBALS['current_blog']->blog_id;
			}

			// Emulate a switch_to_blog()
			$table_prefix = $wpdb->get_blog_prefix( $current_blog->blog_id );
			$wpdb->set_blog_id( $current_blog->blog_id, $current_blog->site_id );
			$_wp_switched_stack = array();
			$switched = false;

			if ( ! isset( $current_site->site_name ) ) {
				$current_site->site_name = get_site_option( 'site_name' );
				if ( ! $current_site->site_name ) {
					$current_site->site_name = ucfirst( $current_site->domain );
				}
			}
		}

		$this->flush_cache();
		unset($GLOBALS['wp_query'], $GLOBALS['wp_the_query']);
		$GLOBALS['wp_the_query'] = new WP_Query();
		$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];
		$GLOBALS['wp'] = new WP();

		// clean out globals to stop them polluting wp and wp_query
		foreach ($GLOBALS['wp']->public_query_vars as $v) {
			unset($GLOBALS[$v]);
		}
		foreach ($GLOBALS['wp']->private_query_vars as $v) {
			unset($GLOBALS[$v]);
		}

		$GLOBALS['wp']->main($parts['query']);

		$wp_roles->reinit();
		$current_user = wp_get_current_user();
		$current_user->for_blog( $blog_id );

		// For BuddyPress, James.
		$this->clean_up_global_scope();
		$GLOBALS['bp']->loggedin_user = NULL;
		$GLOBALS['bp']->pages = bp_core_get_directory_pages();
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
		remove_filter( 'bp_current_user_can', array( $this, 'grant_bp_moderate_cb' ), 10, 2 );
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
			unset( $this->cached_SERVER_NAME );
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
}
