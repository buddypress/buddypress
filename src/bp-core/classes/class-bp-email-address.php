<?php
/**
 * Interface for objects that have email address properties (address, name).
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP_Email_Address Interface.
 *
 * @since 2.5.0
 */
interface BP_Email_Address {

	/**
	 * Gets the email address of the user.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_address();

	/**
	 * Gets the display name of the user.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Sets the email address of the user.
	 *
	 * @since 5.0.0
	 *
	 * @param string $email_address Email address.
	 */
	public function set_address( $email_address );

	/**
	 * Sets the name of the user.
	 *
	 * @since 5.0.0
	 *
	 * @param string $name Name.
	 */
	public function set_name( $name );
}
