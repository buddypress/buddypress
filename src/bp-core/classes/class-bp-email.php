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
 * Represents an email that will be sent to member(s).
 *
 * @since 2.5.0
 */
class BP_Email {
	/**
	 * Addressee details (BCC).
	 *
	 * @since 2.5.0
	 *
	 * @var BP_Email_Recipient[] BCC recipients.
	 */
	protected $bcc = array();

	/**
	 * Addressee details (CC).
	 *
	 * @since 2.5.0
	 *
	 * @var BP_Email_Recipient[] CC recipients.
	 */
	protected $cc = array();

	/**
	 * Email content (HTML).
	 *
	 * @since 2.5.0
	 *
	 * @var string
	 */
	protected $content_html = '';

	/**
	 * Email content (plain text).
	 *
	 * @since 2.5.0
	 *
	 * @var string
	 */
	protected $content_plaintext = '';

	/**
	 * The content type to send the email in ("html" or "plaintext").
	 *
	 * @since 2.5.0
	 *
	 * @var string
	 */
	protected $content_type = 'html';

	/**
	 * Sender details.
	 *
	 * @since 2.5.0
	 *
	 * @var BP_Email_Recipient Sender details.
	 */
	protected $from = null;

	/**
	 * Email preheader.
	 *
	 * @since 4.0.0
	 *
	 * @var string
	 */
	protected $preheader = null;

	/**
	 * Email headers.
	 *
	 * @since 2.5.0
	 *
	 * @var string[] Associative pairing of email header name/value.
	 */
	protected $headers = array();

	/**
	 * The Post object (the source of the email's content and subject).
	 *
	 * @since 2.5.0
	 *
	 * @var WP_Post
	 */
	protected $post_object = null;

	/**
	 * Reply To details.
	 *
	 * @since 2.5.0
	 *
	 * @var BP_Email_Recipient "Reply to" details.
	 */
	protected $reply_to = null;

	/**
	 * Email subject.
	 *
	 * @since 2.5.0
	 *
	 * @var string
	 */
	protected $subject = '';

	/**
	 * Email template (the HTML wrapper around the email content).
	 *
	 * @since 2.5.0
	 *
	 * @var string
	 */
	protected $template = '{{{content}}}';

	/**
	 * Addressee details (to).
	 *
	 * @since 2.5.0
	 *
	 * @var BP_Email_Recipient[] Email recipients.
	 * }
	 */
	protected $to = array();

	/**
	 * Unique identifier for this particular type of email.
	 *
	 * @since 2.5.0
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * Token names and replacement values for this email.
	 *
	 * @since 2.5.0
	 *
	 * @var string[] Associative pairing of token name (key) and replacement value (value).
	 */
	protected $tokens = array();

	/**
	 * Constructor.
	 *
	 * Set the email type and default "from" and "reply to" name and address.
	 *
	 * @since 2.5.0
	 *
	 * @param string $email_type Unique identifier for a particular type of email.
	 */
	public function __construct( $email_type ) {
		$this->type = $email_type;

		// SERVER_NAME isn't always set (e.g CLI).
		if ( ! empty( $_SERVER['SERVER_NAME'] ) ) {
			$domain = strtolower( $_SERVER['SERVER_NAME'] );
			if ( substr( $domain, 0, 4 ) === 'www.' ) {
				$domain = substr( $domain, 4 );
			}

		} elseif ( function_exists( 'gethostname' ) && gethostname() !== false ) {
			$domain = gethostname();

		} elseif ( php_uname( 'n' ) !== false ) {
			$domain = php_uname( 'n' );

		} else {
			$domain = 'localhost.localdomain';
		}

		// This was escaped with esc_html on the way into the database in sanitize_option().
		$from_name    = wp_specialchars_decode( bp_get_option( 'blogname' ), ENT_QUOTES );
		$from_address = "wordpress@$domain";

		/** This filter is documented in wp-includes/pluggable.php */
		$from_address = apply_filters( 'wp_mail_from', $from_address );

		/** This filter is documented in wp-includes/pluggable.php */
		$from_name = apply_filters( 'wp_mail_from_name', $from_name );

		$this->set_from( $from_address, $from_name );
		$this->set_reply_to( bp_get_option( 'admin_email' ), $from_name );

		/**
		 * Fires inside __construct() method for BP_Email class.
		 *
		 * @since 2.5.0
		 *
		 * @param string $email_type Unique identifier for this type of email.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		do_action( 'bp_email', $email_type, $this );
	}


	/*
	 * Setters/getters.
	 */

	/**
	 * Getter function to expose object properties.
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $property_name Property to access.
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return mixed Returns null if property does not exist, otherwise the value.
	 */
	public function get( $property_name, $transform = 'raw' ) {

		// "content" is replaced by HTML or plain text depending on $content_type.
		if ( $property_name === 'content' ) {
			$property_name = 'content_' . $this->get_content_type();

			if ( ! in_array( $property_name, array( 'content_html', 'content_plaintext', ), true ) ) {
				$property_name = 'content_html';
			}
		}

		if ( ! property_exists( $this, $property_name ) ) {
			return null;
		}


		/**
		 * Filters the value of the specified email property before transformation.
		 *
		 * This is a dynamic filter dependent on the specified key.
		 *
		 * @since 2.5.0
		 *
		 * @param mixed $property_value Property value.
		 * @param string $property_name
		 * @param string $transform How to transform the return value.
		 *                          Accepts 'raw' (default) or 'replace-tokens'.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		$retval = apply_filters( "bp_email_get_{$property_name}", $this->$property_name, $property_name, $transform, $this );

		switch ( $transform ) {
			// Special-case to fill the $template with the email $content.
			case 'add-content':
				$retval = str_replace( '{{{content}}}', wpautop( $this->get_content( 'replace-tokens' ) ), $retval );
				// Fall through.

			case 'replace-tokens':
				$retval = bp_core_replace_tokens_in_text( $retval, $this->get_tokens( 'raw' ) );
				// Fall through.

			case 'raw':
			default:
				// Do nothing.
		}

		/**
		 * Filters the value of the specified email $property after transformation.
		 *
		 * @since 2.5.0
		 *
		 * @param string $retval Property value.
		 * @param string $property_name
		 * @param string $transform How to transform the return value.
		 *                          Accepts 'raw' (default) or 'replace-tokens'.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		return apply_filters( 'bp_email_get_property', $retval, $property_name, $transform, $this );
	}

	/**
	 * Get email preheader.
	 *
	 * @since 4.0.0
	 */
	public function get_preheader() {
		if ( null !== $this->preheader ) {
			return $this->preheader;
		}

		$preheader = '';

		$post = $this->get_post_object();
		if ( $post ) {
			$switched = false;

			// Switch to the root blog, where the email post lives.
			if ( ! bp_is_root_blog() ) {
				switch_to_blog( bp_get_root_blog_id() );
				$switched = true;
			}

			$preheader = sanitize_text_field( get_post_meta( $post->ID, 'bp_email_preheader', true ) );

			if ( $switched ) {
				restore_current_blog();
			}
		}

		$this->preheader = $preheader;

		return $this->preheader;
	}

	/**
	 * Get email headers.
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return string[] Associative pairing of email header name/value.
	 */
	public function get_headers( $transform = 'raw' ) {
		return $this->get( 'headers', $transform );
	}

	/**
	 * Get the email's "bcc" address and name.
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return BP_Email_Recipient[] BCC recipients.
	 */
	public function get_bcc( $transform = 'raw' ) {
		return $this->get( 'bcc', $transform );
	}

	/**
	 * Get the email's "cc" address and name.
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return BP_Email_Recipient[] CC recipients.
	 */
	public function get_cc( $transform = 'raw' ) {
		return $this->get( 'cc', $transform );
	}

	/**
	 * Get the email content.
	 *
	 * HTML or plaintext is returned, depending on the email's $content_type.
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return string HTML or plaintext, depending on $content_type.
	 */
	public function get_content( $transform = 'raw' ) {
		return $this->get( 'content', $transform );
	}

	/**
	 * Get the email content (in HTML).
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return string HTML email content.
	 */
	public function get_content_html( $transform = 'raw' ) {
		return $this->get( 'content_html', $transform );
	}

	/**
	 * Get the email content (in plaintext).
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return string Plain text email content.
	 */
	public function get_content_plaintext( $transform = 'raw' ) {
		return $this->get( 'content_plaintext', $transform );
	}

	/**
	 * Get the email content type (HTML or plain text) that the email will be sent in.
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return string Email content type ("html" or "plaintext").
	 */
	public function get_content_type( $transform = 'raw' ) {
		return $this->get( 'content_type', $transform );
	}

	/**
	 * Get the email's "from" address and name.
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return BP_Email_Recipient "From" recipient.
	 */
	public function get_from( $transform = 'raw' ) {
		return $this->get( 'from', $transform );
	}

	/**
	 * Get the Post associated with the email.
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @return WP_Post The post.
	 */
	public function get_post_object( $transform = 'raw' ) {
		return $this->get( 'post_object', $transform );
	}

	/**
	 * Get the email's "reply to" address and name.
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return BP_Email_Recipient "Reply to" recipient.
	 */
	public function get_reply_to( $transform = 'raw' ) {
		return $this->get( 'reply_to', $transform );
	}

	/**
	 * Get the email subject.
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return string Email subject.
	 */
	public function get_subject( $transform = 'raw' ) {
		return $this->get( 'subject', $transform );
	}

	/**
	 * Get the email template (the HTML wrapper around the email content).
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return string Email template. Assumed to be HTML.
	 */
	public function get_template( $transform = 'raw' ) {
		return $this->get( 'template', $transform );
	}

	/**
	 * Get the email's "to" address and name.
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return BP_Email_Recipient[] "To" recipients.
	 */
	public function get_to( $transform = 'raw' ) {
		return $this->get( 'to', $transform );
	}

	/**
	 * Get token names and replacement values for this email.
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @param string $transform Optional. How to transform the return value.
	 *                          Accepts 'raw' (default) or 'replace-tokens'.
	 * @return string[] Associative pairing of token name (key) and replacement value (value).
	 */
	public function get_tokens( $transform = 'raw' ) {
		return $this->get( 'tokens', $transform );
	}

	/**
	 * Set email headers.
	 *
	 * Does NOT let you override to/from, etc. Use the methods provided to set those.
	 *
	 * @since 2.5.0
	 *
	 * @param string[] $headers Key/value pairs of header name/values (strings).
	 * @return BP_Email
	 */
	public function set_headers( array $headers ) {
		$new_headers = array();

		foreach ( $headers as $name => $content ) {
			$content = str_replace( ':', '', $content );
			$name    = str_replace( ':', '', $name );

			$new_headers[ sanitize_key( $name ) ] = sanitize_text_field( $content );
		}

		/**
		 * Filters the new value of the email's "headers" property.
		 *
		 * @since 2.5.0
		 *
		 * @param string[] $new_headers Key/value pairs of new header name/values (strings).
		 * @param BP_Email $this Current instance of the email type class.
		 */
		$this->headers = apply_filters( 'bp_email_set_headers', $new_headers, $this );

		return $this;
	}

	/**
	 * Set the email's "bcc" address and name.
	 *
	 * To set a single address, the first parameter is the address and the second the name.
	 * You can also pass a user ID or a WP_User object.
	 *
	 * To set multiple addresses, for each array item, the key is the email address and
	 * the value is the name.
	 *
	 * @since 2.5.0
	 *
	 * @param string|array|int|WP_User $bcc_address Either a email address, user ID, WP_User object,
	 *                                              or an array containing any combination of the above.
	 * @param string $name Optional. If $bcc_address is a string, this is the recipient's name.
	 * @param string $operation Optional. If "replace", $to_address replaces current setting (default).
	 *                          If "add", $to_address is added to the current setting.
	 * @return BP_Email
	 */
	public function set_bcc( $bcc_address, $name = '', $operation = 'replace' ) {
		$bcc = ( $operation !== 'replace' ) ? $this->bcc : array();

		if ( is_array( $bcc_address ) ) {
			foreach ( $bcc_address as $address ) {
				$bcc[] = new BP_Email_Recipient( $address );
			}

		} else {
			$bcc[] = new BP_Email_Recipient( $bcc_address, $name );
		}

		/**
		 * Filters the new value of the email's "BCC" property.
		 *
		 * @since 2.5.0
		 *
		 * @param BP_Email_Recipient[] $bcc BCC recipients.
		 * @param string|array|int|WP_User $bcc_address Either a email address, user ID, WP_User object,
		 *                                              or an array containing any combination of the above.
		 * @param string $name Optional. If $bcc_address is a string, this is the recipient's name.
		 * @param string $operation If "replace", $to_address replaced previous recipients. If "add",
		 *                          $to_address was added to the array of recipients.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		$this->bcc = apply_filters( 'bp_email_set_bcc', $bcc, $bcc_address, $name, $operation, $this );

		return $this;
	}

	/**
	 * Set the email's "cc" address and name.
	 *
	 * To set a single address, the first parameter is the address and the second the name.
	 * You can also pass a user ID or a WP_User object.
	 *
	 * To set multiple addresses, for each array item, the key is the email address and
	 * the value is the name.
	 *
	 * @since 2.5.0
	 *
	 * @param string|array|int|WP_User $cc_address Either a email address, user ID, WP_User object,
	 *                                             or an array containing any combination of the above.
	 * @param string $name Optional. If $cc_address is a string, this is the recipient's name.
	 * @param string $operation Optional. If "replace", $to_address replaces current setting (default).
	 *                          If "add", $to_address is added to the current setting.
	 * @return BP_Email
	 */
	public function set_cc( $cc_address, $name = '', $operation = 'replace' ) {
		$cc = ( $operation !== 'replace' ) ? $this->cc : array();

		if ( is_array( $cc_address ) ) {
			foreach ( $cc_address as $address ) {
				$cc[] = new BP_Email_Recipient( $address );
			}

		} else {
			$cc[] = new BP_Email_Recipient( $cc_address, $name );
		}

		/**
		 * Filters the new value of the email's "CC" property.
		 *
		 * @since 2.5.0
		 *
		 * @param BP_Email_Recipient[] $cc CC recipients.
		 * @param string|array|int|WP_User $cc_address Either a email address, user ID, WP_User object,
		 *                                             or an array containing any combination of the above.
		 * @param string $name Optional. If $cc_address is a string, this is the recipient's name.
		 * @param string $operation If "replace", $to_address replaced previous recipients. If "add",
		 *                          $to_address was added to the array of recipients.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		$this->cc = apply_filters( 'bp_email_set_cc', $cc, $cc_address, $name, $operation, $this );

		return $this;
	}

	/**
	 * Set the email content (HTML).
	 *
	 * @since 2.5.0
	 *
	 * @param string $content HTML email content.
	 * @return BP_Email
	 */
	public function set_content_html( $content ) {

		/**
		 * Filters the new value of the email's "content" property (HTML).
		 *
		 * @since 2.5.0
		 *
		 * @param string $content HTML email content.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		$this->content_html = apply_filters( 'bp_email_set_content_html', $content, $this );

		return $this;
	}

	/**
	 * Set the email content (plain text).
	 *
	 * @since 2.5.0
	 *
	 * @param string $content Plain text email content.
	 * @return BP_Email
	 */
	public function set_content_plaintext( $content ) {

		/**
		 * Filters the new value of the email's "content" property (plain text).
		 *
		 * @since 2.5.0
		 *
		 * @param string $content Plain text email content.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		$this->content_plaintext = apply_filters( 'bp_email_set_content_plaintext', $content, $this );

		return $this;
	}

	/**
	 * Set the content type (HTML or plain text) to send the email in.
	 *
	 * @since 2.5.0
	 *
	 * @param string $content_type Email content type ("html" or "plaintext").
	 * @return BP_Email
	 */
	public function set_content_type( $content_type ) {
		if ( ! in_array( $content_type, array( 'html', 'plaintext', ), true ) ) {
			$class        = get_class_vars( get_class() );
			$content_type = $class['content_type'];
		}

		/**
		 * Filters the new value of the email's "content type" property.
		 *
		 * The content type (HTML or plain text) to send the email in.
		 *
		 * @since 2.5.0
		 *
		 * @param string $content_type Email content type ("html" or "plaintext").
		 * @param BP_Email $this Current instance of the email type class.
		 */
		$this->content_type = apply_filters( 'bp_email_set_content_type', $content_type, $this );

		return $this;
	}

	/**
	 * Set the email's "from" address and name.
	 *
	 * @since 2.5.0
	 *
	 * @param string|array|int|WP_User $email_address Either a email address, user ID, or WP_User object.
	 * @param string $name Optional. If $email_address is a string, this is the recipient's name.
	 * @return BP_Email
	 */
	public function set_from( $email_address, $name = '' ) {
		$from = new BP_Email_Recipient( $email_address, $name );

		/**
		 * Filters the new value of the email's "from" property.
		 *
		 * @since 2.5.0
		 *
		 * @param BP_Email_Recipient $from Sender details.
		 * @param string|array|int|WP_User $email_address Either a email address, user ID, or WP_User object.
		 * @param string $name Optional. If $email_address is a string, this is the recipient's name.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		$this->from = apply_filters( 'bp_email_set_from', $from, $email_address, $name, $this );

		return $this;
	}

	/**
	 * Set the Post object containing the email content template.
	 *
	 * Also sets the email's subject, content, and template from the Post, for convenience.
	 *
	 * @since 2.5.0
	 *
	 * @param WP_Post $post
	 * @return BP_Email
	 */
	public function set_post_object( WP_Post $post ) {

		/**
		 * Filters the new value of the email's "post object" property.
		 *
		 * @since 2.5.0
		 *
		 * @param WP_Post $post A Post.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		$this->post_object = apply_filters( 'bp_email_set_post_object', $post, $this );

		if ( is_a( $this->post_object, 'WP_Post' ) ) {
			$this->set_subject( $this->post_object->post_title )
				->set_content_html( $this->post_object->post_content )
				->set_content_plaintext( $this->post_object->post_excerpt );

			ob_start();

			// Load the template.
			add_filter( 'bp_locate_template_and_load', '__return_true' );

			bp_locate_template( bp_email_get_template( $this->post_object ), true, false );

			remove_filter( 'bp_locate_template_and_load', '__return_true' );

			$this->set_template( ob_get_contents() );

			ob_end_clean();
		}

		return $this;
	}

	/**
	 * Set the email's "reply to" address and name.
	 *
	 * @since 2.5.0
	 *
	 * @param string|array|int|WP_User $email_address Either a email address, user ID, WP_User object,
	 *                                                or an array containing any combination of the above.
	 * @param string $name Optional. If $email_address is a string, this is the recipient's name.
	 * @return BP_Email
	 */
	public function set_reply_to( $email_address, $name = '' ) {
		$reply_to = new BP_Email_Recipient( $email_address, $name );

		/**
		 * Filters the new value of the email's "reply to" property.
		 *
		 * @since 2.5.0
		 *
		 * @param BP_Email_Recipient $reply_to "Reply to" recipient.
		 * @param string|array|int|WP_User $email_address Either a email address, user ID, WP_User object,
		 *                                                or an array containing any combination of the above.
		 * @param string $name Optional. If $email_address is a string, this is the recipient's name.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		$this->reply_to = apply_filters( 'bp_email_set_reply_to', $reply_to, $email_address, $name, $this );

		return $this;
	}

	/**
	 * Set the email subject.
	 *
	 * @since 2.5.0
	 *
	 * @param string $subject Email subject.
	 * @return BP_Email
	 */
	public function set_subject( $subject ) {

		/**
		 * Filters the new value of the subject email property.
		 *
		 * @since 2.5.0
		 *
		 * @param string $subject Email subject.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		$this->subject = apply_filters( 'bp_email_set_subject', $subject, $this );

		return $this;
	}

	/**
	 * Set the email template (the HTML wrapper around the email content).
	 *
	 * This needs to include the string "{{{content}}}" to have the post content added
	 * when the email template is rendered.
	 *
	 * @since 2.5.0
	 *
	 * @param string $template Email template. Assumed to be HTML.
	 * @return BP_Email
	 */
	public function set_template( $template ) {

		/**
		 * Filters the new value of the template email property.
		 *
		 * @since 2.5.0
		 *
		 * @param string $template Email template. Assumed to be HTML.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		$this->template = apply_filters( 'bp_email_set_template', $template, $this );

		return $this;
	}

	/**
	 * Set the email's "to" address and name.
	 *
	 * IMPORTANT NOTE: the assumption with all emails sent by (and belonging to) BuddyPress itself
	 * is that there will only be a single `$to_address`. This is to simplify token and templating
	 * logic (for example, if multiple recipients, the "unsubscribe" link in the emails will all
	 * only link to the first recipient).
	 *
	 * To set a single address, the first parameter is the address and the second the name.
	 * You can also pass a user ID or a WP_User object.
	 *
	 * To set multiple addresses, for each array item, the key is the email address and
	 * the value is the name.
	 *
	 * @since 2.5.0
	 *
	 * @param string|array|int|WP_User $to_address Either a email address, user ID, WP_User object,
	 *                                             or an array containing any combination of the above.
	 * @param string $name Optional. If $to_address is a string, this is the recipient's name.
	 * @param string $operation Optional. If "replace", $to_address replaces current setting (default).
	 *                          If "add", $to_address is added to the current setting.
	 * @return BP_Email
	 */
	public function set_to( $to_address, $name = '', $operation = 'replace' ) {
		$to = ( $operation !== 'replace' ) ? $this->to : array();

		if ( is_array( $to_address ) ) {
			foreach ( $to_address as $address ) {
				$to[] = new BP_Email_Recipient( $address );
			}

		} else {
			$to[] = new BP_Email_Recipient( $to_address, $name );
		}

		/**
		 * Filters the new value of the email's "to" property.
		 *
		 * @since 2.5.0
		 *
		 * @param BP_Email_Recipient[] "To" recipients.
		 * @param string $to_address "To" address.
		 * @param string $name "To" name.
		 * @param string $operation If "replace", $to_address replaced previous recipients. If "add",
		 *                          $to_address was added to the array of recipients.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		$this->to = apply_filters( 'bp_email_set_to', $to, $to_address, $name, $operation, $this );

		return $this;
	}

	/**
	 * Set token names and replacement values for this email.
	 *
	 * In templates, tokens are inserted with a Handlebars-like syntax, e.g. `{{token_name}}`.
	 * { and } are reserved characters. There's no need to specify these brackets in your token names.
	 *
	 * @since 2.5.0
	 *
	 * @param string[] $tokens Associative array, contains key/value pairs of token name/value.
	 *                         Values are a string or a callable function.
	 * @return BP_Email
	 */
	public function set_tokens( array $tokens ) {
		$formatted_tokens = array();

		foreach ( $tokens as $name => $value ) {
			$name                      = str_replace( array( '{', '}' ), '', sanitize_text_field( $name ) );
			$formatted_tokens[ $name ] = $value;
		}

		/**
		 * Filters the new value of the email's "tokens" property.
		 *
		 * @since 2.5.0
		 *
		 * @param string[] $formatted_tokens Associative pairing of token names (key)
		 *                                   and replacement values (value).
		 * @param string[] $tokens           Associative pairing of unformatted token
		 *                                   names (key) and replacement values (value).
		 * @param BP_Email $this             Current instance of the email type class.
		 */
		$this->tokens = apply_filters( 'bp_email_set_tokens', $formatted_tokens, $tokens, $this );

		return $this;
	}


	/*
	 * Sanitisation and validation logic.
	 */

	/**
	 * Check that we'd be able to send this email.
	 *
	 * Unlike most other methods in this class, this one is not chainable.
	 *
	 * @since 2.5.0
	 *
	 * @return bool|WP_Error Returns true if validation succesful, else a descriptive WP_Error.
	 */
	public function validate() {
		$retval = true;

		// BCC, CC, and token properties are optional.
		if (
			! $this->get_from() ||
			! $this->get_to() ||
			! $this->get_subject() ||
			! $this->get_content() ||
			! $this->get_template()
		) {
			$retval = new WP_Error( 'missing_parameter', __CLASS__, $this );
		}

		/**
		 * Filters whether the email passes basic validation checks.
		 *
		 * @since 2.5.0
		 *
		 * @param bool|WP_Error $retval Returns true if validation succesful, else a descriptive WP_Error.
		 * @param BP_Email $this Current instance of the email type class.
		 */
		return apply_filters( 'bp_email_validate', $retval, $this );
	}
}
