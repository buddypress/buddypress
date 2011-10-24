<?php
/**
 * Akismet support for BuddyPress' Activity Stream
 *
 * @package BuddyPress
 * @since 1.6
 * @subpackage Activity
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Akismet {
	/**
	 * The activity last marked as spam
	 *
	 * @access protected
	 * @var BP_Activity_Activity
	 * @since 1.6
	 */
	protected $last_activity = null;

	/**
	 * Constructor
	 *
	 * @since 1.6
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Hook Akismet into the activity stream
	 *
	 * @since 1.6
	 */
	protected function setup_actions() {
		// Add nonces to activity stream lists
		add_action( 'bp_after_activity_post_form', array( $this, 'add_activity_stream_nonce' ) );
		add_action( 'bp_activity_entry_comments',  array( $this, 'add_activity_stream_nonce' ) );

		// Add a "mark as spam" button to individual activity items
		add_action( 'bp_activity_entry_meta',      array( $this, 'add_activity_spam_button' ) );
		add_action( 'bp_activity_comment_options', array( $this, 'add_activity_comment_spam_button' ) );

		// Check activity for spam
		add_action( 'bp_activity_before_save',     array( $this, 'check_activity' ), 1, 1 );

		// Update activity meta after a spam check
		add_action( 'bp_activity_after_save',      array( $this, 'update_activity_meta' ), 1, 1 );

		// Tidy up member's latest (activity) update
		add_action( 'bp_activity_posted_update',   array( $this, 'check_member_activity_update' ), 1, 3 );
	}

	/**
	 * Adds a nonce to the member profile status form, and to the reply form of each activity stream item.
	 * This is used by Akismet to help detect spam activity.
	 *
	 * @see http://plugins.trac.wordpress.org/ticket/1232
	 * @since 1.6
	 */
	public function add_activity_stream_nonce() {
		$form_id = '_bp_as_nonce'; 
		$value   = '_bp_as_nonce_' . bp_loggedin_user_id();

		// If we're in the activity stream loop, we can use the current item's ID to make the nonce unique
		if ( 'bp_activity_entry_comments' == current_filter() ) {
			$form_id .= '_' . bp_get_activity_id();
			$value   .= '_' . bp_get_activity_id();
		}

		wp_nonce_field( $value, $form_id, false );
	}

	/**
	 * Check the member's latest (activity) update to see if it's the item that was (just) marked as spam.
	 *
	 * This can't be done in BP_Akismet::check_activity() due to BP-Default's AJAX implementation; see bp_dtheme_post_update().
	 *
	 * @param string $content Activity update text
	 * @param int $user_id User ID
	 * @param int $activity_id Activity ID
	 * @see bp_dtheme_post_update()
	 * @since 1.6
	 */
	public function check_member_activity_update( $content, $user_id, $activity_id ) {
		// By default, only handle activity updates and activity comments.
		if ( empty( $this->last_activity ) || !in_array( $this->last_activity->type, BP_Akismet::get_activity_types() ) )
			return;

		// Was this $activity_id just marked as spam?
		if ( !$this->last_activity->id || $activity_id != $this->last_activity->id )
			return;

		// It was, so delete the member's latest activity update.
		bp_delete_user_meta( $user_id, 'bp_latest_update' );
	}

	/**
	 * Adds a "mark as spam" button to each activity item for site admins.
	 *
	 * This function is intended to be used inside the activity stream loop.
	 *
	 * @since 1.2
	 */
	public function add_activity_spam_button() {
		if ( !BP_Akismet::user_can_mark_spam() )
			return;

		// By default, only handle activity updates and activity comments.
		if ( !in_array( bp_get_activity_type(), BP_Akismet::get_activity_types() ) )
			return;

		bp_button(
			array(
				'block_self' => false,
				'component'  => 'activity',
				'id'         => 'activity_make_spam_' . bp_get_activity_id(),
				'link_class' => 'bp-secondary-action spam-activity confirm button item-button',
				'link_href'  => wp_nonce_url( bp_get_root_domain() . '/' . bp_get_activity_slug() . '/spam/' . bp_get_activity_id(), 'bp_activity_akismet_spam_' . bp_get_activity_id() ),
				'link_text'  => __( 'Spam This!', 'buddypress' ),
				'wrapper'    => false,
			)
		);
	}

	/**
	 * Adds a "mark as spam" button to each activity COMMENT item for site admins.
	 *
	 * This function is intended to be used inside the activity stream loop.
	 *
	 * @since 1.2
	 */
	public function add_activity_comment_spam_button() {
		if ( !BP_Akismet::user_can_mark_spam() )
			return;

		// By default, only handle activity updates and activity comments.
		$current_comment = bp_activity_current_comment();
		if ( empty( $current_comment ) || !in_array( $current_comment->type, BP_Akismet::get_activity_types() ) )
			return;

		bp_button(
			array(
				'block_self' => false,
				'component'  => 'activity',
				'id'         => 'activity_make_spam_' . bp_get_activity_comment_id(),
				'link_class' => 'bp-secondary-action spam-activity-comment confirm',
				'link_href'  => wp_nonce_url( bp_get_root_domain() . '/' . bp_get_activity_slug() . '/spam/' . bp_get_activity_comment_id() . '?cid=' . bp_get_activity_comment_id(), 'bp_activity_akismet_spam_' . bp_get_activity_comment_id() ),
				'link_text'  => __( 'Spam This!', 'buddypress' ),
				'wrapper'    => false,
			)
		);
	}

	/**
	 * Convenience function to control whether the current user is allowed to mark activity items as spam
	 *
	 * @global object $bp BuddyPress global settings
	 * @return bool True if user is allowed to mark activity items as spam
	 * @since 1.6
	 * @static
	 */
	public static function user_can_mark_spam() {
		global $bp;
		return apply_filters( 'bp_activity_akismet_user_can_mark_spam', $bp->loggedin_user->is_site_admin );
	}

	/**
	 * Get a list of filterable types of activity item that we want Akismet to automatically check for spam.
	 *
	 * @return array List of activity types
	 * @since 1.6
	 * @static
	 */
	public static function get_activity_types() {
		return apply_filters( 'bp_akismet_get_activity_types', array( 'activity_comment', 'activity_update' ) );
	}

	/**
	 * Mark activity item as spam
	 *
	 * @param BP_Activity_Activity $activity
	 * @since 1.6
	 */
	public function mark_as_spam( &$activity ) {
		$activity->is_spam = 1;

		// Record this item so we can do some tidyup in BP_Akismet::check_member_activity_update()
		$this->last_activity = $activity;

		// Clear the activity stream first page cache
		wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );

		// Clear the activity comment cache for this activity item
		wp_cache_delete( 'bp_activity_comments_' . $activity->id, 'bp' );

		do_action( 'bp_activity_akismet_mark_as_spam', $activity );
	}

	/**
	 * Mark activity item as ham
	 *
	 * @param BP_Activity_Activity $activity
	 * @since 1.6
	 */
	public function mark_as_ham( &$activity ) {
		$activity->is_spam = 0;

		// Clear the activity stream first page cache
		wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );

		// Clear the activity comment cache for this activity item
		wp_cache_delete( 'bp_activity_comments_' . $activity->id, 'bp' );

		//bp_activity_delete_meta( $activity->id, 'bpla_spam' );
		do_action( 'bp_activity_akismet_mark_as_ham', $activity );

		//DJPAULTODO: Run bp_activity_at_name_filter() somehow... but not twice, if we can help it. Maybe check if it was auto-spammed by Akismet?
	} 

	/**
	 * Check if the activity item is spam or ham
	 *
	 * @param BP_Activity_Activity $activity The activity item to check
	 * @see http://akismet.com/development/api/
	 * @since 1.6
	 * @todo Spam counter?
	 * @todo Auto-delete old spam?
	 */
	public function check_activity( $activity ) {
		// By default, only handle activity updates and activity comments.
		if ( !in_array( $activity->type, BP_Akismet::get_activity_types() ) )
			return;

		$this->last_activity = null;
		$userdata            = get_userdata( $activity->user_id );

		// Build up a data package for the Akismet service to inspect
		$activity_data                          = array();
		$activity_data['akismet_comment_nonce'] = 'inactive';
		$activity_data['comment_author']        = $userdata->display_name;
		$activity_data['comment_author_email']  = $userdata->user_email;
		$activity_data['comment_author_url']    = bp_core_get_userlink( $userdata->ID, false, true);
		$activity_data['comment_content']       = $activity->content;
		$activity_data['comment_type']          = $activity->type;
		$activity_data['permalink']             = bp_activity_get_permalink( $activity->id, $activity );
		$activity_data['user_ID']               = $userdata->ID;
		$activity_data['user_role']             = akismet_get_user_roles( $userdata->ID );

		/**
		 * Get the nonce if the new activity was submitted through the "what's up, Paul?" form.
		 * This helps Akismet ensure that the update was a valid form submission.
		 */
		if ( !empty( $_POST['_bp_as_nonce'] ) )
			$activity_data['akismet_comment_nonce'] = wp_verify_nonce( $_POST['_bp_as_nonce'], "_bp_as_nonce_{$userdata->ID}" ) ? 'passed' : 'failed';

		/**
		 * If the new activity was a reply to an existing item, check the nonce with the activity parent ID.
		 * This helps Akismet ensure that the update was a valid form submission.
		 */
		elseif ( !empty( $activity->secondary_item_id ) && !empty( $_POST['_bp_as_nonce_' . $activity->secondary_item_id] ) )
			$activity_data['akismet_comment_nonce'] = wp_verify_nonce( $_POST["_bp_as_nonce_{$activity->secondary_item_id}"], "_bp_as_nonce_{$userdata->ID}_{$activity->secondary_item_id}" ) ? 'passed' : 'failed';

		// Check with Akismet to see if this is spam
		$activity_data = $this->maybe_spam( $activity_data );

		// Record this item
		$this->last_activity = $activity;

		// Store a copy of the data that was submitted to Akismet
		$this->last_activity->akismet_submission = $activity_data;

		// Spam
		if ( 'true' == $activity_data['bp_as_result'] ) {
			// Action for plugin authors
			do_action_ref_array( 'bp_activity_akismet_spam_caught', array( &$activity, $activity_data ) );

			// Mark as spam
			$this->mark_as_spam( $activity );
		}
	}

	/**
	 * Update activity meta after a spam check
	 *
	 * @param BP_Activity_Activity $activity The activity to check
	 * @since 1.6
	 */
	public function update_activity_meta( $activity ) {
		// Check we're dealing with what was last updated by Akismet
		if ( empty( $this->last_activity ) || !empty( $this->last_activity ) && $activity->id != $this->last_activity->id )
			return;

		// By default, only handle activity updates and activity comments.
		if ( !in_array( $this->last_activity->type, BP_Akismet::get_activity_types() ) )
			return;

		// Spam
		if ( 'true' == $this->last_activity->akismet_submission['bp_as_result'] ) {
			bp_activity_update_meta( $activity->id, '_bp_akismet_result', 'true' );
			$this->update_activity_history( $activity->id, __( 'Akismet caught this item as spam', 'buddypress' ), 'check-spam' );

		// Not spam
		} elseif ( 'false' == $this->last_activity->akismet_submission['bp_as_result'] ) {
			bp_activity_update_meta( $activity->id, '_bp_akismet_result', 'false' );
			$this->update_activity_history( $activity->id, __( 'Akismet cleared this item', 'buddypress' ), 'check-ham' );

		// Uh oh, something's gone horribly wrong. Unexpected result.
		} else {
			bp_activity_update_meta( $activity->id, '_bp_akismet_error', bp_core_current_time() );
			$this->update_activity_history( $activity->id, sprintf( __( 'Akismet was unable to check this item (response: %s), will automatically retry again later.', 'buddypress' ), $this->last_activity->akismet_submission['bp_as_result'] ), 'check-error' );
		}

		// Record the original data which was submitted to Akismet for checking
		bp_activity_update_meta( $activity->id, '_bp_akismet_submission', $this->last_activity->akismet_submission );
	}

	/**
	 * Contact Akismet to check if this is spam or ham
	 *
	 * Props to WordPress core Akismet plugin for alot of this
	 *
	 * @global string $akismet_api_host
	 * @global string $akismet_api_port
	 * @param array $activity_data Packet of information to submit to Akismet
	 * @param string $check "check" or "submit"
	 * @param string $spam "spam" or "ham"
	 * @since 1.6
	 */
	protected function maybe_spam( $activity_data, $check = 'check', $spam = 'spam' ) {
		global $akismet_api_host, $akismet_api_port;

		$query_string = $path = $response = '';

		$activity_data['blog']         = bp_get_option( 'home' );
		$activity_data['blog_charset'] = bp_get_option( 'blog_charset' );
		$activity_data['blog_lang']    = get_locale();
		$activity_data['referrer']     = $_SERVER['HTTP_REFERER'];
		$activity_data['user_agent']   = $_SERVER['HTTP_USER_AGENT'];
		$activity_data['user_ip']      = $_SERVER['REMOTE_ADDR'];

		if ( akismet_test_mode() )
			$activity_data['is_test'] = 'true';

		// Loop through _POST args and rekey strings
		foreach ( $_POST as $key => $value )
			if ( is_string( $value ) && 'cookie' != $key )
				$activity_data['POST_' . $key] = $value;

		// Keys to ignore
		$ignore = array( 'HTTP_COOKIE', 'HTTP_COOKIE2', 'PHP_AUTH_PW' );

		// Loop through _SERVER args and remove whitelisted keys
		foreach ( $_SERVER as $key => $value ) {

			// Key should not be ignored
			if ( !in_array( $key, $ignore ) && is_string( $value ) ) {
				$activity_data[$key] = $value;

			// Key should be ignored
			} else {
				$activity_data[$key] = '';
			}
		}

		foreach ( $activity_data as $key => $data )
			$query_string .= $key . '=' . urlencode( stripslashes( $data ) ) . '&';

		if ( 'check' == $check )
			$path = '/1.1/comment-check';
		elseif ( 'submit' == $check )
			$path = '/1.1/submit-' . $spam;

		// Send to Akismet
		$response                      = $this->http_post( $query_string, $akismet_api_host, $path, $akismet_api_port );
		$activity_data['bp_as_result'] = $response[1];

		return $activity_data;
	}

	/**
	 * Submit data to the Akismet service with a unique user agent.
	 *
	 * Props to WordPress core Akismet plugin, and bbPress, for alot of this
	 *
	 * @param string $request The request we are sending
	 * @param string $host The host to send our request to
	 * @param string $path The path from the host
	 * @param string $port The port to use
	 * @param string $ip Optional Override $host with an IP address
	 * @return mixed WP_Error on error, array on success, empty on failure
	 * @since 1.6
	 */
	private function http_post( $request, $host, $path, $port = 80, $ip = '' ) {
		$blog_charset   = bp_get_option( 'blog_charset' );
		$content_length = strlen( $request );
		$errno          = null;
		$errstr         = null;
		$http_host      = $host;
		$response       = '';

		// Unique User Agent
		$akismet_ua     = 'BuddyPress/' . constant( 'BP_VERSION' ) . ' | Akismet/'. constant( 'AKISMET_VERSION' );

		// Use specific IP (if provided)
		if ( !empty( $ip ) && long2ip( ip2long( $ip ) ) )
			$http_host = $ip;

		// WP HTTP class is available
		if ( function_exists( 'wp_remote_post' ) ) {

			// Setup the arguments
			$http_args = array(
				'body'             => $request,
				'headers'          => array(
					'Content-Type' => 'application/x-www-form-urlencoded; charset=' . $blog_charset,
					'Host'         => $host,
					'User-Agent'   => $akismet_ua
				),
				'httpversion'      => '1.0',
				'timeout'          => 15
			);

			// Where we are sending our request
			$akismet_url = 'http://' . $http_host . $path;

			// Send the request
			$response    = wp_remote_post( $akismet_url, $http_args );

			// Bail if the response is an error
			if ( is_wp_error( $response ) )
				return '';

			// No errors so return response
			return array( $response['headers'], $response['body'] );

		// WP HTTP class is not available (Why not?)
		} else {

			// Header info to use with our socket
			$http_request  = "POST {$path} HTTP/1.0\r\n";
			$http_request .= "Host: {$host}\r\n";
			$http_request .= "Content-Type: application/x-www-form-urlencoded; charset={$blog_charset}\r\n";
			$http_request .= "Content-Length: {$content_length}\r\n";
			$http_request .= "User-Agent: {$akismet_ua}\r\n";
			$http_request .= "\r\n";
			$http_request .= $request;

			// Open a socket connection
			if ( false != ( $fs = @fsockopen( $http_host, $port, $errno, $errstr, 10 ) ) ) {

				// Write our request to the pointer
				fwrite( $fs, $http_request );

				// Loop through pointer and compile a response
				while ( !feof( $fs ) ) {
					// One TCP-IP packet at a time
					$response .= fgets( $fs, 1160 );
				}

				// Close our socket
				fclose( $fs );

				// Explode the response into usable data
				$response = explode( "\r\n\r\n", $response, 2 );
			}

			// Return the response ('' if error/empty)
			return $response;
		}
	}

	/**
	 * Update an activity item's Akismet history
	 *
	 * @param int $activity_id Activity item ID
	 * @param string $message Human-readable description of what's changed
	 * @param string $event The type of check we were carrying out
	 * @since 1.6
	 */
	private function update_activity_history( $activity_id = 0, $message = '', $event = '' ) {
		$event = array(
			'event'   => $event,
			'message' => $message,
			'time'    => akismet_microtime(),
			'user'    => bp_loggedin_user_id(),
		);

		// Save the history data
		bp_activity_update_meta( $activity_id, '_bp_akismet_history', $event );
	}

	/**
	 * Get an activity item's Akismet history
	 *
	 * @param int $activity_id Activity item ID
	 * @return array The activity item's Akismet history
	 * @since 1.6
	 */
	public function get_activity_history( $activity_id = 0 ) {
		$history = bp_activity_get_meta( $activity_id, '_bp_akismet_history' );

		// Sort it by the time recorded
		usort( $history, 'akismet_cmp_time' );

		return $history;
	}
}
?>