<?php
/**
 * BP Nouveau Messages
 *
 * @since 3.0.0
 * @version 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Messages Loader class
 *
 * @since 3.0.0
 */
#[AllowDynamicProperties]
class BP_Nouveau_Messages {
	/**
	 * Constructor
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
		$this->setup_filters();
	}

	/**
	 * Globals
	 *
	 * @since 3.0.0
	 */
	protected function setup_globals() {
		$this->dir = trailingslashit( dirname( __FILE__ ) );
	}

	/**
	 * Include needed files
	 *
	 * @since 3.0.0
	 */
	protected function includes() {
		require $this->dir . 'functions.php';
		require $this->dir . 'template-tags.php';

		// Test suite requires the AJAX functions early.
		if ( function_exists( 'tests_add_filter' ) ) {
			require $this->dir . 'ajax.php';

		// Load AJAX code only on AJAX requests.
		} else {
			add_action( 'admin_init', function() {
				if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX && 0 === strpos( $_REQUEST['action'], 'messages_' ) ) {
					require bp_nouveau()->messages->dir . 'ajax.php';
				}
			} );
		}
	}

	/**
	 * Register do_action() hooks
	 *
	 * @since 3.0.0
	 */
	protected function setup_actions() {
		add_action( 'bp_init', 'bp_nouveau_register_messages_ajax_actions' );

		$hook = 'bp_parse_query';
		if ( 'rewrites' !== bp_core_get_query_parser() ) {
			$hook = 'bp_init';
		}

		add_action( $hook, 'bp_nouveau_push_sitewide_notices', 99 );

		// Messages
		add_action( 'bp_messages_setup_nav', 'bp_nouveau_messages_adjust_nav' );

		// Remove deprecated scripts
		remove_action( 'bp_enqueue_scripts', 'messages_add_autocomplete_js' );

		// Enqueue the scripts for the new UI
		add_action( 'bp_nouveau_enqueue_scripts', 'bp_nouveau_messages_enqueue_scripts' );

		// Register the Messages Notifications filters
		add_action( 'bp_nouveau_notifications_init_filters', 'bp_nouveau_messages_notification_filters' );
	}

	/**
	 * Register add_filter() hooks
	 *
	 * @since 3.0.0
	 */
	protected function setup_filters() {
		// Enqueue specific styles.
		add_filter( 'bp_nouveau_enqueue_styles', 'bp_nouveau_messages_enqueue_styles', 10, 1 );

		// Register messages scripts.
		add_filter( 'bp_nouveau_register_scripts', 'bp_nouveau_messages_register_scripts', 10, 1 );

		// Localize Scripts.
		add_filter( 'bp_core_get_js_strings', 'bp_nouveau_messages_localize_scripts', 10, 1 );

		// Notices.
		add_filter( 'bp_core_get_notifications_for_user', 'bp_nouveau_add_notice_notification_for_user', 10, 2 );

		// Messages.
		add_filter( 'bp_messages_admin_nav', 'bp_nouveau_messages_adjust_admin_nav', 10, 1 );
	}
}

/**
 * Launch the Messages loader class.
 *
 * @since 3.0.0
 */
function bp_nouveau_messages( $bp_nouveau = null ) {
	if ( is_null( $bp_nouveau ) ) {
		return;
	}

	$bp_nouveau->messages = new BP_Nouveau_Messages();
}
add_action( 'bp_nouveau_includes', 'bp_nouveau_messages', 10, 1 );
