<?php
/**
 * BuddyPress messages component Site-wide Notices admin screen.
 *
 * @package BuddyPress
 * @subpackage MessagesClasses
 * @since 3.0.0
 * @deprecated 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

_deprecated_file( basename( __FILE__ ), '15.0.0', '/wp-content/plugins/buddypress/bp-members/classes/class-bp-members-notices-admin.php', esc_html__( 'Please use `BP_Members_Notices_Admin` instead.', 'buddypress' ) );

/**
 * BuddyPress Notices Admin class.
 *
 * @deprecated 15.0.0
 */
#[AllowDynamicProperties]
class BP_Messages_Notices_Admin extends BP_Members_Notices_Admin {

	/**
	 * The ID returned by `add_users_page()`.
	 *
	 * @since 3.0.0
	 * @deprecated 15.0.0
	 * @var string
	 */
	public $screen_id = '';

	/**
	 * The URL of the admin screen.
	 *
	 * @since 3.0.0
	 * @deprecated 15.0.0
	 * @var string
	 */
	public $url = '';

	/**
	 * The current instance of the BP_Members_Notices_List_Table class.
	 *
	 * @since 3.0.0
	 * @deprecated 15.0.0
	 * @var BP_Messages_Notices_List_Table|string
	 */
	public $list_table = '';

	/**
	 * Create a new instance or access the current instance of this class.
	 *
	 * @since 3.0.0
	 * @deprecated 15.0.0
	 *
	 * @return BP_Members_Notices_Admin
	 */
	public static function register_notices_admin() {
		_deprecated_function( __METHOD__, '15.0.0' );
		return parent::register_notices_admin();
	}

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 * @deprecated 15.0.0
	 */
	public function __construct() {
		_deprecated_function( __METHOD__, '15.0.0' );
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Populate the classs variables.
	 *
	 * @since 3.0.0
	 * @deprecated 15.0.0
	 */
	protected function setup_globals() {
		_deprecated_function( __METHOD__, '15.0.0' );
		parent::setup_globals();
	}

	/**
	 * Add action hooks.
	 *
	 * @since 3.0.0
	 * @deprecated 15.0.0
	 */
	protected function setup_actions() {
		_deprecated_function( __METHOD__, '15.0.0' );
		parent::setup_actions();
	}

	/**
	 * Add the 'Site Notices' admin menu item.
	 *
	 * @since 3.0.0
	 * @deprecated 15.0.0
	 */
	public function admin_menu() {
		_deprecated_function( __METHOD__, '15.0.0' );
		parent::admin_menu();
	}

	/**
	 * Catch save/update requests or load the screen.
	 *
	 * @since 3.0.0
	 * @deprecated 15.0.0
	 */
	public function admin_load() {
		_deprecated_function( __METHOD__, '15.0.0' );
		parent::admin_load();
	}

	/**
	 * Generate content for the bp-notices admin screen.
	 *
	 * @since 3.0.0
	 * @deprecated 15.0.0
	 */
	public function admin_index() {
		_deprecated_function( __METHOD__, '15.0.0' );
		parent::admin_index();
	}
}
