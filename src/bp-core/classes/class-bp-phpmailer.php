<?php
/**
 * Email delivery implementation using PHPMailer.
 *
 * @package BuddyPress
 * @subpackage Core
 *
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 * @phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
 * @phpcs:disable Squiz.Commenting.EmptyCatchComment.Missing
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP_PHPMailer class.
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

		if ( ! ( $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer ) ) {
			if ( ! class_exists( 'PHPMailer\\PHPMailer\\PHPMailer' ) ) {
				require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
				require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
				require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			}

			$phpmailer = new PHPMailer\PHPMailer\PHPMailer( true );
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
		$content_plaintext  = PHPMailer\PHPMailer\PHPMailer::normalizeBreaks( $email->get_content_plaintext( 'replace-tokens' ) );

		if ( $email->get( 'content_type' ) === 'html' ) {
			$phpmailer->msgHTML( $email->get_template( 'add-content' ) );
			$phpmailer->AltBody = $content_plaintext;

		} else {
			$phpmailer->IsHTML( false );
			$phpmailer->Body = $content_plaintext;
		}

		$recipient = $email->get_from();
		try {
			$phpmailer->setFrom( $recipient->get_address(), $recipient->get_name(), false );
		} catch ( PHPMailer\PHPMailer\Exception $e ) {
		}

		$recipient = $email->get_reply_to();
		try {
			$phpmailer->addReplyTo( $recipient->get_address(), $recipient->get_name() );
		} catch ( PHPMailer\PHPMailer\Exception $e ) {
		}

		$recipients = $email->get_to();
		foreach ( $recipients as $recipient ) {
			try {
				$phpmailer->AddAddress( $recipient->get_address(), $recipient->get_name() );
			} catch ( PHPMailer\PHPMailer\Exception $e ) {
			}
		}

		$recipients = $email->get_cc();
		foreach ( $recipients as $recipient ) {
			try {
				$phpmailer->AddCc( $recipient->get_address(), $recipient->get_name() );
			} catch ( PHPMailer\PHPMailer\Exception $e ) {
			}
		}

		$recipients = $email->get_bcc();
		foreach ( $recipients as $recipient ) {
			try {
				$phpmailer->AddBcc( $recipient->get_address(), $recipient->get_name() );
			} catch ( PHPMailer\PHPMailer\Exception $e ) {
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

		try {
			return $phpmailer->Send();
		} catch ( PHPMailer\PHPMailer\Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage(), $email );
		}
	}

	/**
	 * Get an appropriate hostname for the email. Varies depending on site configuration.
	 *
	 * @since 2.5.0
	 *
	 * @deprecated 2.5.3 No longer used.
	 *
	 * @return string
	 */
	public static function get_hostname() {
		return '';
	}
}
