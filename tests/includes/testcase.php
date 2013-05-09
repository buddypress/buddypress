<?php

/**
 * WP's test suite wipes out BP's directory page mappings with _delete_all_posts()
 * We must reestablish them before our tests can be successfully run
 */
bp_core_add_page_mappings( bp_get_option( 'bp-active-components' ), 'delete' );

require_once dirname( __FILE__ ) . '/factory.php';

class BP_UnitTestCase extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		$this->factory = new BP_UnitTest_Factory;
	}

	function clean_up_global_scope() {
		buddypress()->bp_nav                = buddypress()->bp_options_nav = buddypress()->action_variables = buddypress()->canonical_stack = buddypress()->unfiltered_uri = $GLOBALS['bp_unfiltered_uri'] = array();
		buddypress()->current_component     = buddypress()->current_item = buddypress()->current_action = '';
		buddypress()->unfiltered_uri_offset = 0;
		buddypress()->is_single_item        = false;
		buddypress()->current_user          = new stdClass();
		buddypress()->displayed_user        = new stdClass();
		buddypress()->loggedin_user         = new stdClass();

		parent::clean_up_global_scope();
	}

	function assertPreConditions() {
		parent::assertPreConditions();

		// Reinit some of the globals that might have been cleared by BP_UnitTestCase::clean_up_global_scope().
		// This is here because it didn't work in clean_up_global_scope(); I don't know why.
		do_action( 'bp_setup_globals' );
	}

	function go_to( $url ) {
		// Set this for bp_core_set_uri_globals()
		$GLOBALS['_SERVER']['REQUEST_URI'] = $url = str_replace( untrailingslashit( network_home_url() ), '', $url );

		// note: the WP and WP_Query classes like to silently fetch parameters
		// from all over the place (globals, GET, etc), which makes it tricky
		// to run them more than once without very carefully clearing everything
		$_GET = $_POST = array();
		foreach (array('query_string', 'id', 'postdata', 'authordata', 'day', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'pagenow') as $v) {
			if ( isset( $GLOBALS[$v] ) ) unset( $GLOBALS[$v] );
		}
		$parts = parse_url($url);
		if (isset($parts['scheme'])) {
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

		$_SERVER['REQUEST_URI'] = $req;
		unset($_SERVER['PATH_INFO']);

		$this->flush_cache();
		unset($GLOBALS['wp_query'], $GLOBALS['wp_the_query']);
		$GLOBALS['wp_the_query'] =& new WP_Query();
		$GLOBALS['wp_query'] =& $GLOBALS['wp_the_query'];
		$GLOBALS['wp'] =& new WP();

		// clean out globals to stop them polluting wp and wp_query
		foreach ($GLOBALS['wp']->public_query_vars as $v) {
			unset($GLOBALS[$v]);
		}
		foreach ($GLOBALS['wp']->private_query_vars as $v) {
			unset($GLOBALS[$v]);
		}

		$GLOBALS['wp']->main($parts['query']);

		// For BuddyPress, James.
		do_action( 'bp_init' );
	}

	protected function checkRequirements() {
		if ( WP_TESTS_FORCE_KNOWN_BUGS )
			return;

		parent::checkRequirements();

		$tickets = PHPUnit_Util_Test::getTickets( get_class( $this ), $this->getName( false ) );
		foreach ( $tickets as $ticket ) {
			if ( 'BP' == substr( $ticket, 0, 2 ) ) {
				$ticket = substr( $ticket, 2 );
				if ( $ticket && is_numeric( $ticket ) )
					$this->knownBPBug( $ticket );
			}
		}
	}

	/**
	 * Skips the current test if there is an open BuddyPress ticket with id $ticket_id
	 */
	function knownBPBug( $ticket_id ) {
		if ( WP_TESTS_FORCE_KNOWN_BUGS || in_array( $ticket_id, self::$forced_tickets ) )
			return;

		if ( ! TracTickets::isTracTicketClosed( 'http://buddypress.trac.wordpress.org', $ticket_id ) )
			$this->markTestSkipped( sprintf( 'BuddyPress Ticket #%d is not fixed', $ticket_id ) );
	}

	/**
	 * WP's core tests use wp_set_current_user() to change the current
	 * user during tests. BP caches the current user differently, so we
	 * have to do a bit more work to change it
	 *
	 * @global BuddyPres $bp
	 */
	function set_current_user( $user_id ) {
		global $bp;
		$bp->loggedin_user->id = $user_id;
		$bp->loggedin_user->fullname       = bp_core_get_user_displayname( $user_id );
		$bp->loggedin_user->is_super_admin = $bp->loggedin_user->is_site_admin = is_super_admin( $user_id );
		$bp->loggedin_user->domain         = bp_core_get_user_domain( $user_id );
		$bp->loggedin_user->userdata       = bp_core_get_core_userdata( $user_id );

		wp_set_current_user( $user_id );
	}

	/**
	 * When creating a new user, it's almost always necessary to have the
	 * last_activity usermeta set right away, so that the user shows up in
	 * directory queries. This is a shorthand wrapper for the user factory
	 * create() method.
	 *
	 * Also set a display name
	 */
	function create_user( $args = array() ) {
		$r = wp_parse_args( $args, array(
			'role' => 'subscriber',
			'last_activity' => bp_core_current_time(),
		) );

		$last_activity = $r['last_activity'];
		unset( $r['last_activity'] );

		$user_id = $this->factory->user->create( $args );

		update_user_meta( $user_id, 'last_activity', $last_activity );

		if ( bp_is_active( 'xprofile' ) ) {
			$user = new WP_User( $user_id );
			xprofile_set_field_data( 1, $user_id, $user->display_name );
		}

		return $user_id;
	}
}
