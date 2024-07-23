<?php
/**
 * BuddyPress Messages Classes.
 *
 * @package BuddyPress
 * @subpackage MessagesClasses
 * @since 1.0.0
 * @deprecated 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

_deprecated_file( basename( __FILE__ ), '15.0.0', '', __( 'BuddyPress Site-Wide Notices became Member Notices, please use the `BP_Members_Notice()` class instead.', 'buddypress' ) );

/**
 * BuddyPress Notices class.
 *
 * Use this class to create, activate, deactivate or delete notices.
 *
 * @since 1.0.0
 * @deprecated 15.0.0
 */
#[AllowDynamicProperties]
class BP_Messages_Notice {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @deprecated 15.0.0
	 */
	public function __construct( $id = null ) {
		_deprecated_function( __METHOD__, '15.0.0', 'BP_Members_Notice::__construct()' );
	}

	/**
	 * Populate method.
	 *
	 * Runs during constructor.
	 *
	 * @since 1.0.0
	 * @deprecated 15.0.0
	 */
	public function populate() {
		_deprecated_function( __METHOD__, '15.0.0', 'BP_Members_Notice::populate()' );
	}

	/**
	 * Saves a notice.
	 *
	 * @since 1.0.0
	 * @deprecated 15.0.0
	 */
	public function save() {
		_deprecated_function( __METHOD__, '15.0.0', 'BP_Members_Notice::save()' );
	}

	/**
	 * Activates a notice.
	 *
	 * @since 1.0.0
	 * @deprecated 15.0.0
	 */
	public function activate() {
		_deprecated_function( __METHOD__, '15.0.0', 'BP_Members_Notice::activate()' );
	}

	/**
	 * Deactivates a notice.
	 *
	 * @since 1.0.0
	 * @deprecated 15.0.0
	 */
	public function deactivate() {
		_deprecated_function( __METHOD__, '15.0.0', 'BP_Members_Notice::deactivate()' );
	}

	/**
	 * Deletes a notice.
	 *
	 * @since 1.0.0
	 * @deprecated 15.0.0
	 */
	public function delete() {
		_deprecated_function( __METHOD__, '15.0.0', 'BP_Members_Notice::delete()' );
	}

	/** Static Methods ********************************************************/

	/**
	 * Pulls up a list of notices.
	 *
	 * To get all notices, pass a value of -1 to pag_num.
	 *
	 * @since 1.0.0
	 * @deprecated 15.0.0
	 */
	public static function get_notices() {
		_deprecated_function( __METHOD__, '15.0.0', 'BP_Members_Notice::get()' );
	}

	/**
	 * Returns the total number of recorded notices.
	 *
	 * @since 1.0.0
	 * @deprecated 15.0.0
	 */
	public static function get_total_notice_count() {
		_deprecated_function( __METHOD__, '15.0.0', 'BP_Members_Notice::get_total_notice_count()' );
	}

	/**
	 * Returns the active notice that should be displayed on the front end.
	 *
	 * @since 1.0.0
	 * @deprecated 15.0.0
	 */
	public static function get_active() {
		_deprecated_function( __METHOD__, '15.0.0' );
	}
}
