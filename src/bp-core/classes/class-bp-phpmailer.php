<?php
/**
 * Core component classes.
 *
 * @package BuddyPress
 * @subpackage Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Email delivery implementation using PHPMailer.
 *
 * @since 2.5.0
 */
class BP_PHPMailer implements BP_Email_Delivery {

	/**
	 * Send email(s).
	 *
	 * @since 2.5.0
	 *
	 * @param BP_Email $email Email to send.
	 * @return bool|WP_Error Returns true if email send, else a descriptive WP_Error.
	 */
	public function bp_email( BP_Email $email ) {
		static $phpmailer = null;
		$phpmailer_is_6_0 = false;

		/**
		 * Filter PHPMailer object to use.
		 *
		 * Specify an alternative version of PHPMailer to use instead of WordPress' default.
		 *
		 * @since 2.8.0
		 *
		 * @param null|PHPMailer $phpmailer The phpmailer class.
		 */
		$phpmailer = apply_filters( 'bp_phpmailer_object', $phpmailer );

		/**
		 * WordPress 5.5 deprecated version 5.2 of PHPMailer
		 * and is now using version 6.0 of PHPMailer.
		 */
		if ( bp_get_major_wp_version() >= 5.5 ) {
			$phpmailer_is_6_0 = true;

			if ( ! ( $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer ) ) {
				if ( ! class_exists( 'PHPMailer\\PHPMailer\\PHPMailer' ) ) {
					require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
					require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
					require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
				}

				$phpmailer = new PHPMailer\PHPMailer\PHPMailer( true );
			}
		} else {
			if ( ! ( $phpmailer instanceof PHPMailer ) ) {
				if ( ! class_exists( 'PHPMailer' ) ) {
					require_once ABSPATH . WPINC . '/class-phpmailer.php';
					require_once ABSPATH . WPINC . '/class-smtp.php';
				}

				$phpmailer = new PHPMailer( true );
			}
		}

		/*
		 * Resets.
		 */
		$phpmailer->MessageDate = date( 'D, j M Y H:i:s O' );
		$phpmailer->clearAllRecipients();
		$phpmailer->clearAttachments();
		$phpmailer->clearCustomHeaders();
		$phpmailer->clearReplyTos();
		$phpmailer->Sender = '';

		/*
		 * Set up.
		 */
		$phpmailer->IsMail();
		$phpmailer->CharSet = bp_get_option( 'blog_charset' );

		/*
		 * Content.
		 */
		$phpmailer->Subject = $email->get_subject( 'replace-tokens' );
		if ( $phpmailer_is_6_0 ) {
			$content_plaintext = PHPMailer\PHPMailer\PHPMailer::normalizeBreaks( $email->get_content_plaintext( 'replace-tokens' ) );
		} else {
			$content_plaintext = PHPMailer::normalizeBreaks( $email->get_content_plaintext( 'replace-tokens' ) );
		}

		if ( $email->get( 'content_type' ) === 'html' ) {
			$phpmailer->msgHTML( $email->get_template( 'add-content' ) );
			$phpmailer->AltBody = $content_plaintext;

		} else {
			$phpmailer->IsHTML( false );
			$phpmailer->Body = $content_plaintext;
		}

		$recipient = $email->get_from();
		if ( $phpmailer_is_6_0 ) {
			try {
				$phpmailer->setFrom( $recipient->get_address(), $recipient->get_name(), false );
			} catch ( PHPMailer\PHPMailer\Exception $e ) {
			}
		} else {
			try {
				$phpmailer->SetFrom( $recipient->get_address(), $recipient->get_name(), false );
			} catch ( phpmailerException $e ) {
			}
		}

		$recipient = $email->get_reply_to();
		if ( $phpmailer_is_6_0 ) {
			try {
				$phpmailer->addReplyTo( $recipient->get_address(), $recipient->get_name() );
			} catch ( PHPMailer\PHPMailer\Exception $e ) {
			}
		} else {
			try {
				$phpmailer->addReplyTo( $recipient->get_address(), $recipient->get_name() );
			} catch ( phpmailerException $e ) {
			}
		}

		$recipients = $email->get_to();
		if ( $phpmailer_is_6_0 ) {
			foreach ( $recipients as $recipient ) {
				try {
					$phpmailer->AddAddress( $recipient->get_address(), $recipient->get_name() );
				} catch ( PHPMailer\PHPMailer\Exception $e ) {
				}
			}
		} else {
			foreach ( $recipients as $recipient ) {
				try {
					$phpmailer->AddAddress( $recipient->get_address(), $recipient->get_name() );
				} catch ( phpmailerException $e ) {
				}
			}
		}

		$recipients = $email->get_cc();
		if ( $phpmailer_is_6_0 ) {
			foreach ( $recipients as $recipient ) {
				try {
					$phpmailer->AddCc( $recipient->get_address(), $recipient->get_name() );
				} catch ( PHPMailer\PHPMailer\Exception $e ) {
				}
			}
		} else {
			foreach ( $recipients as $recipient ) {
				try {
					$phpmailer->AddCc( $recipient->get_address(), $recipient->get_name() );
				} catch ( phpmailerException $e ) {
				}
			}
		}

		$recipients = $email->get_bcc();
		if ( $phpmailer_is_6_0 ) {
			foreach ( $recipients as $recipient ) {
				try {
					$phpmailer->AddBcc( $recipient->get_address(), $recipient->get_name() );
				} catch ( PHPMailer\PHPMailer\Exception $e ) {
				}
			}
		} else {
			foreach ( $recipients as $recipient ) {
				try {
					$phpmailer->AddBcc( $recipient->get_address(), $recipient->get_name() );
				} catch ( phpmailerException $e ) {
				}
			}
		}

		$headers = $email->get_headers();
		foreach ( $headers as $name => $content ) {
			$phpmailer->AddCustomHeader( $name, $content );
		}

		/**
		 * Fires after PHPMailer is initialised.
		 *
		 * @since 2.5.0
		 *
		 * @param PHPMailer $phpmailer The PHPMailer instance.
		 */
		do_action( 'bp_phpmailer_init', $phpmailer );

		/** This filter is documented in wp-includes/pluggable.php */
		do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );

		if ( $phpmailer_is_6_0 ) {
			try {
				return $phpmailer->Send();
			} catch ( PHPMailer\PHPMailer\Exception $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage(), $email );
			}
		} else {
			try {
				return $phpmailer->Send();
			} catch ( phpmailerException $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage(), $email );
			}
		}
	}

	/*
	 * Utility/helper functions.
	 */

	/**
	 * Get an appropriate hostname for the email. Varies depending on site configuration.
	 *
	 * @since 2.5.0
	 * @deprecated 2.5.3 No longer used.
	 *
	 * @return string
	 */
	public static function get_hostname() {
		return '';
	}
}
