<?php
/**
 * BuddyPress Member's notice feature Class.
 *
 * @package BuddyPress
 * @subpackage Members
 * @since 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The BP Signups feature is now optional.
 *
 * If you want to disable it, you can use:
 * `add_filter( 'bp_is_members_signups_active', '__return_false' );`
 *
 * @since 15.0.0
 */
class BP_Members_Signups_Feature extends BP_Component_Feature {

	/**
	 * Signups Feature initialization.
	 *
	 * @since 15.0.0
	 */
	public function __construct() {
		parent::init( 'signups', 'members' );
	}

	/**
	 * Set up Signups feature global variables.
	 *
	 * @since 15.0.0
	 */
	public function globals() {
		buddypress()->signup = new stdClass();

		parent::globals();
	}

	/**
	 * Include Signups feature files.
	 *
	 * @since 15.0.0
	 *
	 * @see `BP_Component_Feature::includes()` for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component_Feature::includes()}.
	 */
	public function includes( $includes = array() ) {
		parent::includes( array( 'bp-members-signups' ) );
	}

	/**
	 * Include screen/action files later & when on specific pages.
	 *
	 * @since 15.0.0
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		// Registration / Activation.
		if ( bp_is_register_page() || bp_is_activation_page() ) {
			if ( bp_is_register_page() ) {
				require_once buddypress()->members->path . 'bp-members/screens/register.php';
			} else {
				require_once buddypress()->members->path . 'bp-members/screens/activate.php';
			}

			// Theme compatibility.
			new BP_Registration_Theme_Compat();
		}
	}

	/**
	 * Setup cache groups.
	 *
	 * @since 15.0.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bp_signups',
			)
		);

		parent::setup_cache_groups();
	}

	/**
	 * Register the BP REST API Controller.
	 *
	 * @since 15.0.0
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for
	 *                           description.
	 */
	public function rest_api_init( $controllers = array() ) {
		$controllers = array();

		if ( bp_get_signup_allowed() ) {
			$controllers[] = 'BP_Members_Signup_REST_Controller';
		}

		parent::rest_api_init( $controllers );
	}
}
