<?php
/**
 * Base class for email "participants" (recipient, sender, Reply-To, etc).
 *
 * @since 5.0.0
 */

abstract class BP_Email_Participant implements BP_Email_Address {
	/**
	 * Recipient's email address.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	protected $address = '';

	/**
	 * Recipient's name.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Gets the email address of the user.
	 *
	 * @since 5.0.0
	 */
	public function get_address() {
		/**
		 * Filters an email user's address before it's returned.
		 *
		 * @since 5.0.0
		 *
		 * @param string        $address User's address.
		 * @param BP_Email_User $user    Current instance of the email user class.
		 */
		return apply_filters( 'bp_email_user_get_address', $this->address, $this );
	}

	/**
	 * Gets the email name of the user.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_name() {
		/**
		 * Filters an email user's name before it's returned.
		 *
		 * @since 5.0.0
		 *
		 * @param string        $name Recipient's name.
		 * @param BP_Email_User $user Current instance of the email user class.
		 */
		return apply_filters( 'bp_email_recipient_get_name', $this->name, $this );
	}

	/**
	 * Sets the email address of the user.
	 *
	 * @since 5.0.0
	 *
	 * @param string $email_address Email address.
	 */
	public function set_address( $email_address ) {
		$this->address = $email_address;
	}

	/**
	 * Sets the name of the user.
	 *
	 * @since 5.0.0
	 *
	 * @param string $name Name.
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}
}
