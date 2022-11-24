<?php
/**
 * BuddyPress Messages Classes.
 *
 * @package BuddyPress
 * @subpackage MessagesClasses
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Message Thread class.
 *
 * @since 1.0.0
 */
class BP_Messages_Thread {

	/**
	 * The message thread ID.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $thread_id;

	/**
	 * The current messages.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $messages;

	/**
	 * The current recipients in the message thread.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $recipients;

	/**
	 * The user IDs of all messages in the message thread.
	 *
	 * @since 1.2.0
	 * @var array
	 */
	public $sender_ids;

	/**
	 * The unread count for the logged-in user.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	public $unread_count;

	/**
	 * The content of the last message in this thread.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	public $last_message_content;

	/**
	 * The date of the last message in this thread.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	public $last_message_date;

	/**
	 * The ID of the last message in this thread.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	public $last_message_id;

	/**
	 * The subject of the last message in this thread.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	public $last_message_subject;

	/**
	 * The user ID of the author of the last message in this thread.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	public $last_sender_id;

	/**
	 * Sort order of the messages in this thread (ASC or DESC).
	 *
	 * @since 1.5.0
	 * @var string
	 */
	public $messages_order;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated the `$args` with new paremeters.
	 *
	 * @param int    $thread_id          The message thread ID.
	 * @param string $order              The order to sort the messages. Either 'ASC' or 'DESC'.
	 *                                   Defaults to 'ASC'.
	 * @param array  $args               {
	 *     Array of arguments.
	 *     @type int         $user_id             ID of the user to get the unread count.
	 *     @type bool        $update_meta_cache   Whether to pre-fetch metadata for
	 *                                            queried message items. Default: true.
	 *     @type int|null    $page                Page of messages being requested. Default to null, meaning all.
	 *     @type int|null    $per_page            Messages to return per page. Default to null, meaning all.
	 *     @type string      $order               Optional. The order to sort the messages. Either 'ASC' or 'DESC'.
	 *                                            Defaults to 'ASC'.
	 *     @type int|null    $recipients_page     Page of recipients being requested. Default to null, meaning all.
	 *     @type int|null    $recipients_per_page Recipients to return per page. Defaults to null, meaning all.
	 * }
	 */
	public function __construct( $thread_id = 0, $order = 'ASC', $args = array() ) {
		if ( ! empty( $thread_id ) ) {
			$this->populate( $thread_id, $order, $args );
		}
	}

	/**
	 * Populate method.
	 *
	 * Used in the constructor.
	 *
	 * @since 1.0.0
	 * @since 10.0.0 Updated the `$args` with new paremeters.
	 *
	 * @param int    $thread_id                   The message thread ID.
	 * @param string $order                       The order to sort the messages. Either 'ASC' or 'DESC'.
	 *                                            Defaults to 'ASC'.
	 * @param array  $args                        {
	 *     Array of arguments.
	 *     @type int         $user_id             ID of the user to get the unread count.
	 *     @type bool        $update_meta_cache   Whether to pre-fetch metadata for
	 *                                            queried message items. Default: true.
	 *     @type int|null    $page                Page of messages being requested. Default to null, meaning all.
	 *     @type int|null    $per_page            Messages to return per page. Default to null, meaning all.
	 *     @type string      $order               The order to sort the messages. Either 'ASC' or 'DESC'.
	 *                                            Defaults to 'ASC'.
	 *     @type int|null    $recipients_page     Page of recipients being requested. Default to null, meaning all.
	 *     @type int|null    $recipients_per_page Recipients to return per page. Defaults to null, meaning all.
	 * }
	 * @return bool False if there are no messages.
	 */
	public function populate( $thread_id = 0, $order = 'ASC', $args = array() ) {

		$user_id =
			bp_displayed_user_id() ?
			bp_displayed_user_id() :
			bp_loggedin_user_id();

		// Merge $args with our defaults.
		$r = bp_parse_args(
			$args,
			array(
				'user_id'             => $user_id,
				'update_meta_cache'   => true,
				'page'                => null,
				'per_page'            => null,
				'order'               => bp_esc_sql_order( $order ),
				'recipients_page'     => null,
				'recipients_per_page' => null,
			)
		);

		$this->messages_order = $r['order'];
		$this->thread_id      = (int) $thread_id;

		// Get messages for thread.
		$this->messages = self::get_messages( $this->thread_id, $r );

		if ( empty( $this->messages ) ) {
			return false;
		}

		$last_message_index         = count( $this->messages ) - 1;
		$this->last_message_id      = $this->messages[ $last_message_index ]->id;
		$this->last_message_date    = $this->messages[ $last_message_index ]->date_sent;
		$this->last_sender_id       = $this->messages[ $last_message_index ]->sender_id;
		$this->last_message_subject = $this->messages[ $last_message_index ]->subject;
		$this->last_message_content = $this->messages[ $last_message_index ]->message;

		foreach ( (array) $this->messages as $key => $message ) {
			$this->sender_ids[ $message->sender_id ] = $message->sender_id;
		}

		// Fetch the recipients and set the displayed/logged in user's unread count.
		$this->recipients = $this->get_recipients( $thread_id, $r );

		// Grab all message meta.
		if ( true === (bool) $r['update_meta_cache'] ) {
			bp_messages_update_meta_cache( wp_list_pluck( $this->messages, 'id' ) );
		}

		/**
		 * Fires after a BP_Messages_Thread object has been populated.
		 *
		 * @since 2.2.0
		 * @since 10.0.0 Added `$r` as a parameter.
		 *
		 * @param BP_Messages_Thread $thread Current messages thread class.
		 * @param array              $r      Array of paremeters.
		 */
		do_action( 'bp_messages_thread_post_populate', $this, $r );
	}

	/**
	 * Mark a thread initialized in this class as read.
	 *
	 * @since 1.0.0
	 *
	 * @see BP_Messages_Thread::mark_as_read()
	 */
	public function mark_read() {
		self::mark_as_read( $this->thread_id );
	}

	/**
	 * Mark a thread initialized in this class as unread.
	 *
	 * @since 1.0.0
	 *
	 * @see BP_Messages_Thread::mark_as_unread()
	 */
	public function mark_unread() {
		self::mark_as_unread( $this->thread_id );
	}

	/**
	 * Returns recipients for a message thread.
	 *
	 * @since 1.0.0
	 * @since 2.3.0  Added `$thread_id` as a parameter.
	 * @since 10.0.0 Added `$args` as a parameter.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int   $thread_id Message thread ID.
	 * @param array $args      {
	 *     Array of arguments.
	 *     @type int|null $recipients_page     Page of recipients being requested. Default to all.
	 *     @type int|null $recipients_per_page Recipients to return per page. Defaults to all.
	 * }
	 * @return array
	 */
	public function get_recipients( $thread_id = 0, $args = array() ) {
		global $wpdb;

		if ( empty( $thread_id ) ) {
			$thread_id = $this->thread_id;
		}

		$thread_id = (int) $thread_id;

		if ( empty( $thread_id ) ) {
			return array();
		}

		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'recipients_page'     => null,
				'recipients_per_page' => null,
			)
		);

		// Get recipients from cache if available.
		$recipients = wp_cache_get( 'thread_recipients_' . $thread_id, 'bp_messages' );

		// Get recipients and cache it.
		if ( empty( $recipients ) ) {

			// Query recipients.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d",
					$thread_id
				)
			);

			$recipients = array();
			foreach ( (array) $results as $recipient ) {
				$recipient_properties              = get_object_vars( $recipient );
				$recipients[ $recipient->user_id ] = (object) array_map( 'intval', $recipient_properties );
			}

			// Cache recipients.
			wp_cache_set( 'thread_recipients_' . $thread_id, (array) $recipients, 'bp_messages' );
		}

		// Set the unread count for the user.
		if ( isset( $r['user_id'] ) && $r['user_id'] && isset( $recipients[ $r['user_id'] ]->unread_count ) ) {
			$this->unread_count = (int) $recipients[ $r['user_id'] ]->unread_count;
		}

		// Paginate the results.
		if ( ! empty( $recipients ) && $r['recipients_per_page'] && $r['recipients_page'] ) {
			$start      = ( $r['recipients_page'] - 1 ) * ( $r['recipients_per_page'] );
			$recipients = array_slice( $recipients, $start, $r['recipients_per_page'], true );
		}

		/**
		 * Filters the recipients of a message thread.
		 *
		 * @since 2.2.0
		 * @since 10.0.0 Added `$r` as a parameter.
		 *
		 * @param array $recipients Array of recipient objects.
		 * @param int   $thread_id  ID of the thread.
		 * @param array $r          An array of parameters.
		 */
		return apply_filters( 'bp_messages_thread_get_recipients', (array) $recipients, (int) $thread_id, (array) $r );
	}

	/** Static Functions ******************************************************/

	/**
	 * Get messages associated with a thread.
	 *
	 * @since 2.3.0
	 * @since 10.0.0 Added `$args` as a parameter.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int   $thread_id The message thread ID.
	 * @param array $args      {
	 *     Array of arguments.
	 *     @type int|null    $page     Page of messages being requested. Default to all.
	 *     @type int|null    $per_page Messages to return per page. Default to all.
	 *     @type string      $order    The order to sort the messages. Either 'ASC' or 'DESC'.
	 *                                 Defaults to 'ASC'.
	 * }
	 * @return array
	 */
	public static function get_messages( $thread_id = 0, $args = array() ) {
		global $wpdb;

		$thread_id = (int) $thread_id;
		if ( empty( $thread_id ) ) {
			return array();
		}

		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'page'     => null,
				'per_page' => null,
				'order'    => 'ASC',
			)
		);

		// Sanitize 'order'.
		$r['order'] = bp_esc_sql_order( $r['order'] );

		// Get messages from cache if available.
		$messages = wp_cache_get( $thread_id, 'bp_messages_threads' );

		// Get messages and cache it.
		if ( empty( $messages ) ) {

			// Query messages.
			$messages = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$bp->messages->table_name_messages} WHERE thread_id = %d ORDER BY date_sent ASC",
					$thread_id
				)
			);

			foreach ( $messages as $key => $data ) {
				$messages[ $key ]->id        = (int) $messages[ $key ]->id;
				$messages[ $key ]->thread_id = (int) $messages[ $key ]->thread_id;
				$messages[ $key ]->sender_id = (int) $messages[ $key ]->sender_id;
			}

			// Cache messages.
			wp_cache_set( $thread_id, (array) $messages, 'bp_messages_threads' );
		}

		// Flip if order is DESC.
		if ( 'DESC' === $r['order'] ) {
			$messages = array_reverse( $messages );
		}

		// Paginate the results.
		if ( ! empty( $messages ) && $r['per_page'] && $r['page'] ) {
			$start    = ( $r['page'] - 1 ) * ( $r['per_page'] );
			$messages = array_slice( $messages, $start, $r['per_page'] );
		}

		/**
		 * Filters the messages associated with a thread.
		 *
		 * @since 10.0.0
		 *
		 * @param array $messages   Array of message objects.
		 * @param int   $thread_id  ID of the thread.
		 * @param array $r          An array of parameters.
		 */
		return apply_filters( 'bp_messages_thread_get_messages', (array) $messages, (int) $thread_id, (array) $r );
	}

	/**
	 * Static method to get message recipients by thread ID.
	 *
	 * @since 2.3.0
	 *
	 * @param int $thread_id The thread ID.
	 * @return array
	 */
	public static function get_recipients_for_thread( $thread_id = 0 ) {
		$thread = new self( false );
		return $thread->get_recipients( $thread_id );
	}

	/**
	 * Mark messages in a thread as deleted or delete all messages in a thread.
	 *
	 * Note: All messages in a thread are deleted once every recipient in a thread
	 * has marked the thread as deleted.
	 *
	 * @since 1.0.0
	 * @since 2.7.0 The $user_id parameter was added. Previously the current user
	 *              was always assumed.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $thread_id The message thread ID.
	 * @param int $user_id The ID of the user in the thread to mark messages as
	 *                     deleted for. Defaults to the current logged-in user.
	 *
	 * @return bool
	 */
	public static function delete( $thread_id = 0, $user_id = 0 ) {
		global $wpdb;

		$thread_id = (int) $thread_id;
		$user_id = (int) $user_id;

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		/**
		 * Fires before a message thread is marked as deleted.
		 *
		 * @since 2.2.0
		 * @since 2.7.0 The $user_id parameter was added.
		 *
		 * @param int $thread_id ID of the thread being deleted.
		 * @param int $user_id   ID of the user that the thread is being deleted for.
		 */
		do_action( 'bp_messages_thread_before_mark_delete', $thread_id, $user_id );

		$bp = buddypress();

		// Mark messages as deleted
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_deleted = 1 WHERE thread_id = %d AND user_id = %d", $thread_id, $user_id ) );

		// Get the message ids in order to pass to the action.
		$message_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ) );

		// Check to see if any more recipients remain for this message.
		$recipients = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND is_deleted = 0", $thread_id ) );

		// No more recipients so delete all messages associated with the thread.
		if ( empty( $recipients ) ) {

			/**
			 * Fires before an entire message thread is deleted.
			 *
			 * @since 2.2.0
			 *
			 * @param int   $thread_id   ID of the thread being deleted.
			 * @param array $message_ids IDs of messages being deleted.
			 */
			do_action( 'bp_messages_thread_before_delete', $thread_id, $message_ids );

			// Delete all the messages.
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ) );

			// Do something for each message ID.
			foreach ( $message_ids as $message_id ) {

				// Delete message meta.
				bp_messages_delete_meta( $message_id );

				/**
				 * Fires after a message is deleted. This hook is poorly named.
				 *
				 * @since 1.0.0
				 *
				 * @param int $message_id ID of the message.
				 */
				do_action( 'messages_thread_deleted_thread', $message_id );
			}

			// Delete all the recipients.
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread_id ) );
		}

		/**
		 * Fires after a message thread is either marked as deleted or deleted.
		 *
		 * @since 2.2.0
		 * @since 2.7.0 The $user_id parameter was added.
		 *
		 * @param int   $thread_id   ID of the thread being deleted.
		 * @param array $message_ids IDs of messages being deleted.
		 * @param int   $user_id     ID of the user the threads were deleted for.
		 */
		do_action( 'bp_messages_thread_after_delete', $thread_id, $message_ids, $user_id );

		return true;
	}

	/**
	 * Exit a user from a thread.
	 *
	 * @since 10.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $thread_id The message thread ID.
	 * @param int $user_id The ID of the user in the thread.
	 *                     Defaults to the current logged-in user.
	 *
	 * @return bool
	 */
	public static function exit_thread( $thread_id = 0, $user_id = 0 ) {
		global $wpdb;

		$thread_id = (int) $thread_id;
		$user_id   = (int) $user_id;

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		// Check the user is a recipient of the thread and recipients count > 2.
		$recipients    = self::get_recipients_for_thread( $thread_id );
		$recipient_ids = wp_list_pluck( $recipients, 'user_id' );

		if ( 2 >= count( $recipient_ids ) || ! in_array( $user_id, $recipient_ids, true ) ) {
			return false;
		}

		/**
		 * Fires before a user exits a thread.
		 *
		 * @since 10.0.0
		 *
		 * @param int $thread_id ID of the thread being deleted.
		 * @param int $user_id   ID of the user that the thread is being deleted for.
		 */
		do_action( 'bp_messages_thread_before_exit', $thread_id, $user_id );

		$bp = buddypress();

		// Delete the user from messages recipients
		$exited = $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id = %d", $thread_id, $user_id ) );

		// Bail if the user wasn't removed from the recipients list.
		if ( empty( $exited ) ) {
			return false;
		}

		/**
		 * Fires after a user exits a thread.
		 *
		 * @since 10.0.0
		 *
		 * @param int   $thread_id ID of the thread being deleted.
		 * @param int   $user_id   ID of the user the threads were deleted for.
		 */
		do_action( 'bp_messages_thread_after_exit', $thread_id, $user_id );

		return true;
	}

	/**
	 * Get current message threads for a user.
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *     @type int      $user_id             The user ID.
	 *     @type string   $box                 The type of mailbox to get. Either 'inbox' or 'sentbox'.
	 *                                         Defaults to 'inbox'.
	 *     @type string   $type                The type of messages to get. Either 'all' or 'unread'
	 *                                         or 'read'. Defaults to 'all'.
	 *     @type int      $limit               The number of messages to get. Defaults to null.
	 *     @type int      $page                The page number to get. Defaults to null.
	 *     @type string   $search_terms        The search term to use. Defaults to ''.
	 *     @type array    $meta_query          Meta query arguments. See WP_Meta_Query for more details.
	 *     @type int|null $recipients_page     Page of recipients being requested. Default to null, meaning all.
	 *     @type int|null $recipients_per_page Recipients to return per page. Defaults to null, meaning all.
	 *     @type int|null $messages_page       Page of messages being requested. Default to null, meaning all.
	 *     @type int|null $messages_per_page   Messages to return per page. Defaults to null, meaning all.
	 * }
	 * @return array|bool Array on success. False on failure.
	 */
	public static function get_current_threads_for_user( $args = array() ) {
		global $wpdb;

		$function_args = func_get_args();

		// Backward compatibility with old method of passing arguments.
		if ( ! is_array( $args ) || count( $function_args ) > 1 ) {
			_deprecated_argument(
				__METHOD__,
				'2.2.0',
				sprintf(
					/* translators: 1: the name of the method. 2: the name of the file. */
					esc_html__( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ),
					__METHOD__,
					__FILE__
				)
			);

			$old_args_keys = array(
				0 => 'user_id',
				1 => 'box',
				2 => 'type',
				3 => 'limit',
				4 => 'page',
				5 => 'search_terms',
			);

			$args = bp_core_parse_args_array( $old_args_keys, $function_args );
		}

		$r = bp_parse_args(
			$args,
			array(
				'user_id'             => false,
				'box'                 => 'inbox',
				'type'                => 'all',
				'limit'               => null,
				'page'                => null,
				'recipients_page'     => null,
				'recipients_per_page' => null,
				'messages_page'       => null,
				'messages_per_page'   => null,
				'search_terms'        => '',
				'meta_query'          => array(),
			)
		);

		$pag_sql = $type_sql = $search_sql = $user_id_sql = $sender_sql = '';
		$meta_query_sql = array(
			'join'  => '',
			'where' => '',
		);

		if ( $r['limit'] && $r['page'] ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $r['page'] - 1 ) * $r['limit'] ), intval( $r['limit'] ) );
		}

		if ( $r['type'] == 'unread' ) {
			$type_sql = " AND r.unread_count != 0 ";
		} elseif ( $r['type'] == 'read' ) {
			$type_sql = " AND r.unread_count = 0 ";
		}

		if ( ! empty( $r['search_terms'] ) ) {
			$search_terms_like = '%' . bp_esc_like( $r['search_terms'] ) . '%';
			$search_sql        = $wpdb->prepare( "AND ( subject LIKE %s OR message LIKE %s )", $search_terms_like, $search_terms_like );
		}

		$r['user_id'] = (int) $r['user_id'];

		// Default deleted SQL.
		$deleted_sql = 'r.is_deleted = 0';

		switch ( $r['box'] ) {
			case 'sentbox':
				$user_id_sql = 'AND ' . $wpdb->prepare( 'm.sender_id = %d', $r['user_id'] );
				$sender_sql  = 'AND m.sender_id = r.user_id';
				break;

			case 'inbox':
				$user_id_sql = 'AND ' . $wpdb->prepare( 'r.user_id = %d', $r['user_id'] );
				$sender_sql  = 'AND r.sender_only = 0';
				break;

			default:
				// Omit user-deleted threads from all other custom message boxes.
				$deleted_sql = $wpdb->prepare( '( r.user_id = %d AND r.is_deleted = 0 )', $r['user_id'] );
				break;
		}

		// Process meta query into SQL.
		$meta_query = self::get_meta_query_sql( $r['meta_query'] );
		if ( ! empty( $meta_query['join'] ) ) {
			$meta_query_sql['join'] = $meta_query['join'];
		}
		if ( ! empty( $meta_query['where'] ) ) {
			$meta_query_sql['where'] = $meta_query['where'];
		}

		$bp = buddypress();

		// Set up SQL array.
		$sql = array();
		$sql['select'] = 'SELECT m.thread_id, MAX(m.date_sent) AS date_sent';
		$sql['from']   = "FROM {$bp->messages->table_name_recipients} r INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id {$meta_query_sql['join']}";
		$sql['where']  = "WHERE {$deleted_sql} {$user_id_sql} {$sender_sql} {$type_sql} {$search_sql} {$meta_query_sql['where']}";
		$sql['misc']   = "GROUP BY m.thread_id ORDER BY date_sent DESC {$pag_sql}";

		// Get thread IDs.
		$thread_ids = $wpdb->get_results( implode( ' ', $sql ) );
		if ( empty( $thread_ids ) ) {
			return false;
		}

		// Adjust $sql to work for thread total.
		$sql['select'] = 'SELECT COUNT( DISTINCT m.thread_id )';
		unset( $sql['misc'] );
		$total_threads = $wpdb->get_var( implode( ' ', $sql ) );

		// Sort threads by date_sent.
		foreach( (array) $thread_ids as $thread ) {
			$sorted_threads[ $thread->thread_id ] = strtotime( $thread->date_sent );
		}

		arsort( $sorted_threads );

		$threads = array();
		foreach ( (array) $sorted_threads as $thread_id => $date_sent ) {
			$threads[] = new BP_Messages_Thread(
				$thread_id,
				'ASC',
				array(
					'user_id'             => $r['user_id'],
					'update_meta_cache'   => false,
					'recipients_page'     => $r['recipients_page'],
					'recipients_per_page' => $r['recipients_per_page'],
					'page'                => $r['messages_page'],
					'per_page'            => $r['messages_per_page'],
				)
			);
		}

		/**
		 * Filters the results of the query for a user's message threads.
		 *
		 * @since 2.2.0
		 *
		 * @param array $value {
		 *     @type array $threads       Array of threads. Passed by reference.
		 *     @type int   $total_threads Number of threads found by the query.
		 * }
		 *  @param array $r    Array of paremeters.
		 */
		return apply_filters(
			'bp_messages_thread_current_threads',
			array(
				'threads' => &$threads,
				'total'   => (int) $total_threads,
			),
			$r
		);
	}

	/**
	 * Get the SQL for the 'meta_query' param in BP_Messages_Thread::get_current_threads_for_user().
	 *
	 * We use WP_Meta_Query to do the heavy lifting of parsing the meta_query array
	 * and creating the necessary SQL clauses.
	 *
	 * @since 2.2.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param array $meta_query An array of meta_query filters. See the
	 *                          documentation for WP_Meta_Query for details.
	 * @return array $sql_array 'join' and 'where' clauses.
	 */
	public static function get_meta_query_sql( $meta_query = array() ) {
		global $wpdb;

		$sql_array = array(
			'join'  => '',
			'where' => '',
		);

		if ( ! empty( $meta_query ) ) {
			$meta_query = new WP_Meta_Query( $meta_query );

			// WP_Meta_Query expects the table name at
			// $wpdb->messagemeta.
			$wpdb->messagemeta = buddypress()->messages->table_name_meta;

			return $meta_query->get_sql( 'message', 'm', 'id' );
		}

		return $sql_array;
	}

	/**
	 * Mark a thread as read.
	 *
	 * @since 1.0.0
	 * @since 9.0.0 Added the `user_id` parameter.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $thread_id The message thread ID.
	 * @param int $user_id   The user the thread will be marked as read.
	 *
	 * @return bool|int Number of threads marked as read or false on error.
	 */
	public static function mark_as_read( $thread_id = 0, $user_id = 0 ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			$user_id =
				bp_displayed_user_id() ?
				bp_displayed_user_id() :
				bp_loggedin_user_id();
		}

		$bp       = buddypress();
		$num_rows = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 0 WHERE user_id = %d AND thread_id = %d", $user_id, $thread_id ) );

		wp_cache_delete( 'thread_recipients_' . $thread_id, 'bp_messages' );
		wp_cache_delete( $user_id, 'bp_messages_unread_count' );

		/**
		 * Fires when messages thread was marked as read.
		 *
		 * @since 2.8.0
		 * @since 9.0.0 Added the `user_id` parameter.
		 * @since 10.0.0 Added the `$num_rows` parameter.
		 *
		 * @param int $thread_id The message thread ID.
		 * @param int $user_id   The user the thread will be marked as read.
		 * @param bool|int $num_rows    Number of threads marked as unread or false on error.
		 */
		do_action( 'messages_thread_mark_as_read', $thread_id, $user_id, $num_rows );

		return $num_rows;
	}

	/**
	 * Mark a thread as unread.
	 *
	 * @since 1.0.0
	 * @since 9.0.0 Added the `user_id` parameter.
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $thread_id The message thread ID.
	 * @param int $user_id   The user the thread will be marked as unread.
	 *
	 * @return bool|int Number of threads marked as unread or false on error.
	 */
	public static function mark_as_unread( $thread_id = 0, $user_id = 0 ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			$user_id =
				bp_displayed_user_id() ?
				bp_displayed_user_id() :
				bp_loggedin_user_id();
		}

		$bp       = buddypress();
		$num_rows = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 1 WHERE user_id = %d AND thread_id = %d", $user_id, $thread_id ) );

		wp_cache_delete( 'thread_recipients_' . $thread_id, 'bp_messages' );
		wp_cache_delete( $user_id, 'bp_messages_unread_count' );

		/**
		 * Fires when messages thread was marked as unread.
		 *
		 * @since 2.8.0
		 * @since 9.0.0  Added the `$user_id` parameter.
		 * @since 10.0.0 Added the `$num_rows` parameter.
		 *
		 * @param int      $thread_id The message thread ID.
		 * @param int      $user_id   The user the thread will be marked as unread.
		 * @param bool|int $num_rows  Number of threads marked as unread or false on error.
		 */
		do_action( 'messages_thread_mark_as_unread', $thread_id, $user_id, $num_rows );

		return $num_rows;
	}

	/**
	 * Returns the total number of message threads for a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $user_id The user ID.
	 * @param string $box     The type of mailbox to get. Either 'inbox' or 'sentbox'.
	 *                        Defaults to 'inbox'.
	 * @param string $type    The type of messages to get. Either 'all' or 'unread'.
	 *                        or 'read'. Defaults to 'all'.
	 * @return int Total thread count for the provided user.
	 */
	public static function get_total_threads_for_user( $user_id, $box = 'inbox', $type = 'all' ) {
		global $wpdb;

		$exclude_sender = $type_sql = '';
		if ( $box !== 'sentbox' ) {
			$exclude_sender = 'AND sender_only != 1';
		}

		if ( $type === 'unread' ) {
			$type_sql = 'AND unread_count != 0';
		} elseif ( $type === 'read' ) {
			$type_sql = 'AND unread_count = 0';
		}

		$bp = buddypress();

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(thread_id) FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND is_deleted = 0 {$exclude_sender} {$type_sql}", $user_id ) );
	}

	/**
	 * Determine if the logged-in user is a sender of any message in a thread.
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $thread_id The message thread ID.
	 * @return bool
	 */
	public static function user_is_sender( $thread_id ) {
		global $wpdb;

		$bp = buddypress();

		$sender_ids = $wpdb->get_col( $wpdb->prepare( "SELECT sender_id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ) );

		if ( empty( $sender_ids ) ) {
			return false;
		}

		return in_array( bp_loggedin_user_id(), $sender_ids, true );
	}

	/**
	 * Returns the userlink of the last sender in a message thread.
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $thread_id The message thread ID.
	 * @return string|bool The user link on success. Boolean false on failure.
	 */
	public static function get_last_sender( $thread_id ) {
		global $wpdb;

		$bp = buddypress();

		if ( ! $sender_id = $wpdb->get_var( $wpdb->prepare( "SELECT sender_id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d GROUP BY sender_id ORDER BY date_sent LIMIT 1", $thread_id ) ) ) {
			return false;
		}

		return bp_core_get_userlink( $sender_id, true );
	}

	/**
	 * Gets the unread message count for a user.
	 *
	 * @since 1.0.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $user_id The user ID.
	 * @return int Total inbox unread count for user.
	 */
	public static function get_inbox_count( $user_id = 0 ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		$unread_count = wp_cache_get( $user_id, 'bp_messages_unread_count' );

		if ( false === $unread_count ) {
			$bp = buddypress();

			$unread_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(unread_count) FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND is_deleted = 0 AND sender_only = 0", $user_id ) );

			wp_cache_set( $user_id, $unread_count, 'bp_messages_unread_count' );
		}

		/**
		 * Filters a user's unread message count.
		 *
		 * @since 2.2.0
		 *
		 * @param int $unread_count Unread message count.
		 * @param int $user_id      ID of the user.
		 */
		return apply_filters( 'messages_thread_get_inbox_count', (int) $unread_count, $user_id );
	}

	/**
	 * Checks whether a user is a part of a message thread discussion.
	 *
	 * @since 1.0.0
	 *
	 * @param int $thread_id The message thread ID.
	 * @param int $user_id   The user ID. Default: ID of the logged-in user.
	 * @return int|null The recorded recipient ID on success, null on failure.
	 */
	public static function check_access( $thread_id, $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		$recipients = self::get_recipients_for_thread( $thread_id );

		if ( isset( $recipients[ $user_id ] ) && 0 === $recipients[ $user_id ]->is_deleted ) {
			return $recipients[ $user_id ]->id;
		}

		return null;
	}

	/**
	 * Checks whether a message thread exists.
	 *
	 * @since 1.0.0
	 *
	 * @param int $thread_id The message thread ID.
	 * @return bool|int|null The message thread ID on success, null on failure.
	 */
	public static function is_valid( $thread_id = 0 ) {

		// Bail if no thread ID is passed.
		if ( empty( $thread_id ) ) {
			return false;
		}

		$thread = self::get_messages( $thread_id );

		if ( ! empty( $thread ) ) {
			return $thread_id;
		} else {
			return null;
		}
	}

	/**
	 * Returns a string containing all the message recipient userlinks.
	 *
	 * String is comma-delimited.
	 *
	 * If a message thread has more than four users, the returned string is simply
	 * "X Recipients" where "X" is the number of recipients in the message thread.
	 *
	 * @since 1.0.0
	 *
	 * @param array $recipients Array containing the message recipients (array of objects).
	 * @return string String of message recipent userlinks.
	 */
	public static function get_recipient_links( $recipients ) {

		if ( count( $recipients ) >= 5 ) {
			/* translators: %s: number of message recipients */
			return sprintf( __( '%s Recipients', 'buddypress' ), number_format_i18n( count( $recipients ) ) );
		}

		$recipient_links = array();

		foreach ( (array) $recipients as $recipient ) {
			$recipient_link = bp_core_get_userlink( $recipient->user_id );

			if ( empty( $recipient_link ) ) {
				$recipient_link = __( 'Deleted User', 'buddypress' );
			}

			$recipient_links[] = $recipient_link;
		}

		return implode( ', ', (array) $recipient_links );
	}

	/**
	 * Upgrade method for the older BP message thread DB table.
	 *
	 * @todo We should remove this. No one is going to upgrade from v1.1, right?
	 *
	 * @since 1.2.0
	 *
	 * @global BuddyPress $bp The one true BuddyPress instance.
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return bool
	 */
	public static function update_tables() {
		global $wpdb;

		$bp_prefix = bp_core_get_table_prefix();
		$errors    = false;
		$threads   = $wpdb->get_results( "SELECT * FROM {$bp_prefix}bp_messages_threads" );

		// Nothing to update, just return true to remove the table.
		if ( empty( $threads ) ) {
			return true;
		}

		$bp = buddypress();

		foreach( (array) $threads as $thread ) {
			$message_ids = maybe_unserialize( $thread->message_ids );

			if ( ! empty( $message_ids ) ) {
				$message_ids = implode( ',', $message_ids );

				// Add the thread_id to the messages table.
				if ( ! $wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET thread_id = %d WHERE id IN ({$message_ids})", $thread->id ) ) ) {
					$errors = true;
				}
			}
		}

		return (bool) ! $errors;
	}
}
