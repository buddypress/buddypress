<?php
/**
 * Interface for objects that have email address properties (address, name).
 *
 * @since 5.0.0
 */

interface BP_Email_Address {
	/**
	 * Gets the email address of the user.
	 *
	 * @since 5.0.0
	 */
	public function get_address();

	/**
	 * Gets the display name of the user.
	 *
	 * @since 5.0.0
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
