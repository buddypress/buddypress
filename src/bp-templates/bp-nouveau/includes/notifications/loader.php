<?php
/**
 * BP Nouveau Notifications
 *
 * @since 3.0.0
 * @version 6.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Notifications Loader class
 *
 * @since 3.0.0
 */
#[AllowDynamicProperties]
class BP_Nouveau_Notifications {
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
		$this->dir = dirname( __FILE__ );
	}

	/**
	 * Include needed files
	 *
	 * @since 3.0.0
	 */
	protected function includes() {
		$dir = trailingslashit( $this->dir );

		require "{$dir}functions.php";
		require "{$dir}template-tags.php";
	}

	/**
	 * Register do_action() hooks
	 *
	 * @since 3.0.0
	 */
	protected function setup_actions() {
		$hook = 'bp_parse_query';
		if ( 'rewrites' !== bp_core_get_query_parser() ) {
			$hook = 'bp_init';
		}

		add_action( $hook, 'bp_nouveau_notifications_init_filters', 20 );
		add_action( 'bp_nouveau_enqueue_scripts', 'bp_nouveau_notifications_enqueue_scripts' );

		$ajax_actions = array(
			array(
				'notifications_filter' => array(
					'function' => 'bp_nouveau_ajax_object_template_loader',
					'nopriv'   => false,
				),
			),
		);

		foreach ( $ajax_actions as $ajax_action ) {
			$action = key( $ajax_action );

			add_action( 'wp_ajax_' . $action, $ajax_action[ $action ]['function'] );

			if ( ! empty( $ajax_action[ $action ]['nopriv'] ) ) {
				add_action( 'wp_ajax_nopriv_' . $action, $ajax_action[ $action ]['function'] );
			}
		}
	}

	/**
	 * Register add_filter() hooks
	 *
	 * @since 3.0.0
	 */
	protected function setup_filters() {
		add_filter( 'bp_nouveau_register_scripts', 'bp_nouveau_notifications_register_scripts', 10, 1 );
		add_filter( 'bp_get_the_notification_mark_unread_link', 'bp_nouveau_notifications_mark_unread_link', 10, 1 );
		add_filter( 'bp_get_the_notification_mark_read_link', 'bp_nouveau_notifications_mark_read_link', 10, 1 );
		add_filter( 'bp_get_the_notification_delete_link', 'bp_nouveau_notifications_delete_link', 10, 1 );

		// The number formatting is done into the `bp_nouveau_nav_count()` template tag.
		remove_filter( 'bp_notifications_get_total_notification_count', 'bp_core_number_format' );
	}
}

/**
 * Launch the Notifications loader class.
 *
 * @since 3.0.0
 */
function bp_nouveau_notifications( $bp_nouveau = null ) {
	if ( is_null( $bp_nouveau ) ) {
		return;
	}

	$bp_nouveau->notifications = new BP_Nouveau_Notifications();
}
add_action( 'bp_nouveau_includes', 'bp_nouveau_notifications', 10, 1 );
