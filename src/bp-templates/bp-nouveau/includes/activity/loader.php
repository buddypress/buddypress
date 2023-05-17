<?php
/**
 * BP Nouveau Activity
 *
 * @since 3.0.0
 * @version 12.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Activity Loader class
 *
 * @since 3.0.0
 */
#[AllowDynamicProperties]
class BP_Nouveau_Activity {
	/**
	 * Nouveau Activity directory.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $dir = '';

	/**
	 * RSS feed link data.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	public $current_rss_feed = array();

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
				// AJAX condtion.
				if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX &&
					// Check to see if action is activity-specific.
					( false !== strpos( $_REQUEST['action'], 'activity' ) || ( 'post_update' === $_REQUEST['action'] ) )
				) {
					require bp_nouveau()->activity->dir . 'ajax.php';
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
		add_action( 'bp_init', 'bp_nouveau_register_activity_ajax_actions' );
		add_action( 'bp_nouveau_enqueue_scripts', 'bp_nouveau_activity_enqueue_scripts' );
		add_action( 'bp_nouveau_notifications_init_filters', 'bp_nouveau_activity_notification_filters' );

		$bp = buddypress();

		if ( bp_is_akismet_active() && isset( $bp->activity->akismet ) ) {
			remove_action( 'bp_activity_entry_meta', array( $bp->activity->akismet, 'add_activity_spam_button' ) );
			remove_action( 'bp_activity_comment_options', array( $bp->activity->akismet, 'add_activity_comment_spam_button' ) );
		}
	}

	/**
	 * Register add_filter() hooks
	 *
	 * @since 3.0.0
	 */
	protected function setup_filters() {
		// Register customizer controls.
		add_filter( 'bp_nouveau_customizer_controls', 'bp_nouveau_activity_customizer_controls', 10, 1 );

		// Register activity scripts
		add_filter( 'bp_nouveau_register_scripts', 'bp_nouveau_activity_register_scripts', 10, 1 );

		// Localize Scripts
		add_filter( 'bp_core_get_js_strings', 'bp_nouveau_activity_localize_scripts', 10, 1 );

		add_filter( 'bp_get_activity_action_pre_meta', 'bp_nouveau_activity_secondary_avatars', 10, 2 );
		add_filter( 'bp_get_activity_css_class', 'bp_nouveau_activity_scope_newest_class', 10, 1 );
	}
}

/**
 * Launch the Activity loader class.
 *
 * @since 3.0.0
 */
function bp_nouveau_activity( $bp_nouveau = null ) {
	if ( is_null( $bp_nouveau ) ) {
		return;
	}

	$bp_nouveau->activity = new BP_Nouveau_Activity();
}
add_action( 'bp_nouveau_includes', 'bp_nouveau_activity', 10, 1 );
