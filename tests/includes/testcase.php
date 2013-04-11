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
		$GLOBALS['_SERVER']['REQUEST_URI'] = $url = str_replace( network_home_url(), '', $url );

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
		wp_set_current_user( $user_id );
	}
}
