<?php
/**
 * Core component classes.
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Represents a recipient that an email will be sent to.
 *
 * @since 2.5.0
 */
class BP_Email_Recipient extends BP_Email_Participant {

	/**
	 * Optional. A `WP_User` object relating to this recipient.
	 *
	 * @since 2.5.0
	 *
	 * @var WP_User
	 */
	protected $user_object = null;

	/**
	 * Constructor.
	 *
	 * @since 2.5.0
	 *
	 * @param string|array|int|WP_User $email_or_user Either a email address, user ID, WP_User object,
	 *                                                or an array containing any combination of the above.
	 * @param string                   $name          Optional. If $email_or_user is a string, this is the
	 *                                                recipient's name.
	 */
	public function __construct( $email_or_user, $name = '' ) {
		$name = sanitize_text_field( $name );

		// User ID, email address or WP_User object.
		if ( is_int( $email_or_user ) || ( is_string( $email_or_user ) && is_email( $email_or_user ) ) || is_object( $email_or_user ) ) {
			// We already have a WP user.
			if ( is_object( $email_or_user ) ) {
				$this->user_object = $email_or_user;

			// Query for WP user by user ID.
			} elseif ( is_int( $email_or_user ) ) {
				$this->user_object = get_user_by( 'id', $email_or_user );
			}

			// Set email address.
			if ( empty( $this->user_object ) && is_email( $email_or_user ) ) {
				$address = $email_or_user;
			}

		// Array or miscellaneous string.
		} else {
			if ( ! is_array( $email_or_user ) ) {
				$email_or_user = array( $email_or_user => $name );
			}

			// Handle numeric arrays.
			if ( is_int( key( $email_or_user ) ) ) {
				$address = current( $email_or_user );
			} else {
				$address = key( $email_or_user );
				$name    = current( $email_or_user );
			}
		}

		// Set address if we have one.
		if ( ! empty( $address ) ) {
			$this->set_address( sanitize_email( $address ) );
		}

		// Still no user object; try to query user by email address.
		if ( empty( $this->user_object ) ) {
			$this->get_user( 'search-email' );
		}

		// We have a user object; so set address and name from DB.
		if ( $this->user_object ) {
			// This is escaped with esc_html in bp_core_get_user_displayname()
			$wp_name = wp_specialchars_decode( bp_core_get_user_displayname( $this->user_object->ID ), ENT_QUOTES );

			$this->set_address( $this->user_object->user_email );
			$this->set_name( $wp_name );

		}

		// Custom name override.
		if ( $name ) {
			$this->set_name( $name );
		}

		/**
		 * Fires inside __construct() method for BP_Email_Recipient class.
		 *
		 * @since 2.5.0
		 *
		 * @param string|array|int|WP_User $email_or_user Either a email address, user ID, WP_User object,
		 *                                                or an array containing any combination of the above.
		 * @param string                   $name          If $email_or_user is a string, this is the recipient's name.
		 * @param BP_Email_Recipient       $recipient     Current instance of the email type class.
		 */
		do_action( 'bp_email_recipient', $email_or_user, $name, $this );
	}

	/**
	 * Get recipient's address.
	 *
	 * @since 2.5.0
	 *
	 * @return string
	 */
	public function get_address() {
		$address = parent::get_address();

		/**
		 * Filters the recipient's address before it's returned.
		 *
		 * @since 2.5.0
		 *
		 * @param string             $address   Recipient's address.
		 * @param BP_Email_Recipient $recipient Current instance of the email recipient class.
		 */
		return apply_filters( 'bp_email_recipient_get_address', $address, $this );
	}

	/**
	 * Get recipient's name.
	 *
	 * @since 2.5.0
	 *
	 * @return string
	 */
	public function get_name() {
		$name = parent::get_name();

		/**
		 * Filters the recipient's name before it's returned.
		 *
		 * @since 2.5.0
		 *
		 * @param string             $name      Recipient's name.
		 * @param BP_Email_Recipient $recipient Current instance of the email recipient class.
		 */
		return apply_filters( 'bp_email_recipient_get_name', $name, $this );
	}

	/**
	 * Get WP_User object for this recipient.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'search-email'.
	 * @return WP_User|null WP_User object, or null if not set.
	 */
	public function get_user( $transform = 'raw' ) {

		// If transform "search-email", find the WP_User if not already set.
		if ( $transform === 'search-email' && ! $this->user_object && $this->address ) {
			$this->user_object = get_user_by( 'email', $this->address );
		}

		/**
		 * Filters the WP_User object for this recipient before it's returned.
		 *
		 * @since 2.5.0
		 *
		 * @param WP_User            $user_object WP_User object for this recipient, or null if not set.
		 * @param string             $transform   Optional. How the return value was transformed.
		 *                                        Accepts 'raw' (default) or 'search-email'.
		 * @param BP_Email_Recipient $recipient   Current instance of the email recipient class.
		 */
		return apply_filters( 'bp_email_recipient_get_user', $this->user_object, $transform, $this );
	}
}
