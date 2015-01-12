<?php

/**
 * BuddyPress Messages Classes
 *
 * @package BuddyPress
 * @subpackage MessagesClasses
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Message Thread class.
 *
 * @since BuddyPress (1.0.0)
 */
class BP_Messages_Thread {
	/**
	 * The message thread ID.
	 *
	 * @since BuddyPress (1.0.0)
	 * @var int
	 */
	public $thread_id;

	/**
	 * The current messages.
	 *
	 * @since BuddyPress (1.0.0)
	 * @var object
	 */
	public $messages;

	/**
	 * The current recipients in the message thread.
	 *
	 * @since BuddyPress (1.0.0)
	 * @var object
	 */
	public $recipients;

	/**
	 * The user IDs of all messages in the message thread.
	 *
	 * @since BuddyPress (1.2.0)
	 * @var array
	 */
	public $sender_ids;

	/**
	 * The unread count for the logged-in user.
	 *
	 * @since BuddyPress (1.2.0)
	 * @var int
	 */
	public $unread_count;

	/**
	 * The content of the last message in this thread
	 *
	 * @since BuddyPress (1.2.0)
	 * @var string
	 */
	public $last_message_content;

	/**
	 * The date of the last message in this thread
	 *
	 * @since BuddyPress (1.2.0)
	 * @var string
	 */
	public $last_message_date;

	/**
	 * The ID of the last message in this thread
	 *
	 * @since BuddyPress (1.2.0)
	 * @var int
	 */
	public $last_message_id;

	/**
	 * The subject of the last message in this thread
	 *
	 * @since BuddyPress (1.2.0)
	 * @var string
	 */
	public $last_message_subject;

	/**
	 * The user ID of the author of the last message in this thread
	 *
	 * @since BuddyPress (1.2.0)
	 * @var int
	 */
	public $last_sender_id;

	/**
	 * Sort order of the messages in this thread (ASC or DESC).
	 *
	 * @since BuddyPress (1.5.0)
	 * @var string
	 */
	public $messages_order;

	/**
	 * Constructor.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @see BP_Messages_Thread::populate() for full description of parameters
	 */
	public function __construct( $thread_id = false, $order = 'ASC', $args = array() ) {
		if ( $thread_id ) {
			$this->populate( $thread_id, $order, $args );
		}
	}

	/**
	 * Populate method.
	 *
	 * Used in constructor.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param int $thread_id The message thread ID.
	 * @param string $order The order to sort the messages. Either 'ASC' or 'DESC'.
	 * @param array $args {
	 *     Array of arguments.
	 *     @type bool $update_meta_cache Whether to pre-fetch metadata for
	 *           queried message items. Default: true.
	 * }
	 * @return bool False on failure.
	 */
	public function populate( $thread_id = 0, $order = 'ASC', $args = array() ) {
		global $wpdb, $bp;

		if( 'ASC' != $order && 'DESC' != $order ) {
			$order = 'ASC';
		}

		// merge $args with our defaults
		$r = wp_parse_args( $args, array(
			'update_meta_cache' => true
		) );

		$this->messages_order = $order;
		$this->thread_id      = $thread_id;

		if ( !$this->messages = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_messages} WHERE thread_id = %d ORDER BY date_sent " . $order, $this->thread_id ) ) ) {
			return false;
		}

		foreach ( (array) $this->messages as $key => $message ) {
			$this->sender_ids[$message->sender_id] = $message->sender_id;
		}

		// Fetch the recipients
		$this->recipients = $this->get_recipients();

		// Get the unread count for the logged in user
		if ( isset( $this->recipients[bp_loggedin_user_id()] ) ) {
			$this->unread_count = $this->recipients[bp_loggedin_user_id()]->unread_count;
		}

		// Grab all message meta
		if ( true === (bool) $r['update_meta_cache'] ) {
			bp_messages_update_meta_cache( wp_list_pluck( $this->messages, 'id' ) );
		}

		/**
		 * Fires after a BP_Messages_Thread object has been populated.
		 *
		 * @since BuddyPress (2.2.0)
		 *
		 * @param BP_Messages_Thread Message thread object.
		 */
		do_action( 'bp_messages_thread_post_populate', $this );
	}

	/**
	 * Mark a thread initialized in this class as read.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @see BP_Messages_Thread::mark_as_read()
	 */
	public function mark_read() {
		BP_Messages_Thread::mark_as_read( $this->thread_id );
	}

	/**
	 * Mark a thread initialized in this class as unread.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @see BP_Messages_Thread::mark_as_unread()
	 */
	public function mark_unread() {
		BP_Messages_Thread::mark_as_unread( $this->thread_id );
	}

	/**
	 * Returns recipients for a message thread.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @return array
	 */
	public function get_recipients() {
		global $wpdb, $bp;

		$recipients = array();
		$results    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $this->thread_id ) );

		foreach ( (array) $results as $recipient ) {
			$recipients[$recipient->user_id] = $recipient;
		}

		/**
		 * Filters the recipients of a message thread.
		 *
		 * @since BuddyPress (2.2.0)
		 *
		 * @param array $recipients Array of recipient objects.
		 * @param int   $thread_id  ID of the current thread.
		 */
		return apply_filters( 'bp_messages_thread_get_recipients', $recipients, $this->thread_id );
	}

	/** Static Functions ******************************************************/

	/**
	 * Mark messages in a thread as deleted or delete all messages in a thread.
	 *
	 * Note: All messages in a thread are deleted once every recipient in a thread
	 * has marked the thread as deleted.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param int $thread_id The message thread ID
	 * @return bool
	 */
	public static function delete( $thread_id ) {
		global $wpdb, $bp;

		/**
		 * Fires before a message thread is marked as deleted.
		 *
		 * @since BuddyPress (2.2.0)
		 *
		 * @param int $thread_id ID of the thread being deleted.
		 */
		do_action( 'bp_messages_thread_before_mark_delete', $thread_id );

		// Mark messages as deleted
		//
		// @todo the reliance on bp_loggedin_user_id() sucks for plugins
		//       refactor this method to accept a $user_id parameter
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_deleted = 1 WHERE thread_id = %d AND user_id = %d", $thread_id, bp_loggedin_user_id() ) );

		// Get the message ids in order to pass to the action
		$message_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ) );

		// Check to see if any more recipients remain for this message
		$recipients = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND is_deleted = 0", $thread_id ) );

		// No more recipients so delete all messages associated with the thread
		if ( empty( $recipients ) ) {

			/**
			 * Fires before an entire message thread is deleted.
			 *
			 * @since BuddyPress (2.2.0)
			 *
			 * @param int   $thread_id   ID of the thread being deleted.
			 * @param array $message_ids IDs of messages being deleted.
			 */
			do_action( 'bp_messages_thread_before_delete', $thread_id, $message_ids );

			// Delete all the messages
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ) );

			// Do something for each message ID
			foreach ( $message_ids as $message_id ) {
				// Delete message meta
				bp_messages_delete_meta( $message_id );

				/**
				 * Fires after a message is deleted. This hook is poorly named.
				 *
				 * @since BuddyPress (1.0.0)
				 *
				 * @param int $message_id ID of the message
				 */
				do_action( 'messages_thread_deleted_thread', $message_id );
			}

			// Delete all the recipients
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread_id ) );
		}

		/**
		 * Fires after a message thread is either marked as deleted or deleted
		 *
		 * @since BuddyPress (2.2.0)
		 *
		 * @param int   $thread_id   ID of the thread being deleted.
		 * @param array $message_ids IDs of messages being deleted.
		 */
		do_action( 'bp_messages_thread_after_delete', $thread_id, $message_ids );

		return true;
	}

	/**
	 * Get current message threads for a user.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *     @type int    $user_id      The user ID.
	 *     @type string $box          The type of mailbox to get. Either 'inbox' or 'sentbox'.
	 *                                Defaults to 'inbox'.
	 *     @type string $type         The type of messages to get. Either 'all' or 'unread'
	 *                                or 'read'. Defaults to 'all'.
	 *     @type int    $limit        The number of messages to get. Defaults to null.
	 *     @type int    $page         The page number to get. Defaults to null.
	 *     @type string $search_terms The search term to use. Defaults to ''.
	 *     @type array  $meta_query   Meta query arguments. See WP_Meta_Query for more details.
	 * }
	 * @return array|bool Array on success. Boolean false on failure.
	 */
	public static function get_current_threads_for_user( $args = array() ) {
		global $wpdb, $bp;

		// Backward compatibility with old method of passing arguments
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '2.2.0', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'user_id',
				1 => 'box',
				2 => 'type',
				3 => 'limit',
				4 => 'page',
				5 => 'search_terms',
			);

			$func_args = func_get_args();
			$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$defaults = array(
			'user_id'      => false,
			'box'          => 'inbox',
			'type'         => 'all',
			'limit'        => null,
			'page'         => null,
			'search_terms' => '',
			'meta_query'   => array()
		);
		$r = wp_parse_args( $args, $defaults );

		$pag_sql = $type_sql = $search_sql = $user_id_sql = $sender_sql = '';
		$meta_query_sql = array(
			'join'  => '',
			'where' => ''
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

		if ( ! empty( $r['user_id'] ) ) {
			if ( 'sentbox' == $r['box'] ) {
				$user_id_sql = 'AND ' . $wpdb->prepare( 'm.sender_id = %d', $r['user_id'] );
				$sender_sql  = ' AND m.sender_id = r.user_id';
			} else {
				$user_id_sql = 'AND ' . $wpdb->prepare( 'r.user_id = %d', $r['user_id'] );
				$sender_sql  = ' AND r.sender_only = 0';
			}
		}

		// Process meta query into SQL
		$meta_query = self::get_meta_query_sql( $r['meta_query'] );
		if ( ! empty( $meta_query['join'] ) ) {
			$meta_query_sql['join'] = $meta_query['join'];
		}
		if ( ! empty( $meta_query['where'] ) ) {
			$meta_query_sql['where'] = $meta_query['where'];
		}

		// set up SQL array
		$sql = array();
		$sql['select'] = 'SELECT m.thread_id, MAX(m.date_sent) AS date_sent';
		$sql['from']   = "FROM {$bp->messages->table_name_recipients} r INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id {$meta_query_sql['join']}";
		$sql['where']  = "WHERE r.is_deleted = 0 {$user_id_sql} {$sender_sql} {$type_sql} {$search_sql} {$meta_query_sql['where']}";
		$sql['misc']   = "GROUP BY m.thread_id ORDER BY date_sent DESC {$pag_sql}";

		// get thread IDs
		$thread_ids = $wpdb->get_results( implode( ' ', $sql ) );
		if ( empty( $thread_ids ) ) {
			return false;
		}

		// adjust $sql to work for thread total
		$sql['select'] = 'SELECT COUNT( DISTINCT m.thread_id )';
		unset( $sql['misc'] );
		$total_threads = $wpdb->get_var( implode( ' ', $sql ) );

		// Sort threads by date_sent
		foreach( (array) $thread_ids as $thread ) {
			$sorted_threads[$thread->thread_id] = strtotime( $thread->date_sent );
		}

		arsort( $sorted_threads );

		$threads = false;
		foreach ( (array) $sorted_threads as $thread_id => $date_sent ) {
			$threads[] = new BP_Messages_Thread( $thread_id, 'ASC', array(
				'update_meta_cache' => false
			) );
		}

		/**
		 * Filters the results of the query for a user's message threads.
		 *
		 * @since BuddyPress (2.2.0)
		 *
		 * @param array $value {
		 *     @type array $threads       Array of threads. Passed by reference.
		 *     @type int   $total_threads Number of threads found by the query.
		 * }
		 */
		return apply_filters( 'bp_messages_thread_current_threads', array( 'threads' => &$threads, 'total' => (int) $total_threads ) );
	}

	/**
	 * Get the SQL for the 'meta_query' param in BP_Messages_Thread::get_current_threads_for_user().
	 *
	 * We use WP_Meta_Query to do the heavy lifting of parsing the meta_query array
	 * and creating the necessary SQL clauses.
	 *
	 * @since BuddyPress (2.2.0)
	 *
	 * @param array $meta_query An array of meta_query filters. See the
	 *   documentation for WP_Meta_Query for details.
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
			// $wpdb->messagemeta
			$wpdb->messagemeta = buddypress()->messages->table_name_meta;

			return $meta_query->get_sql( 'message', 'm', 'id' );
		}

		return $sql_array;
	}

	/**
	 * Mark a thread as read.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param int $thread_id The message thread ID.
	 */
	public static function mark_as_read( $thread_id ) {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 0 WHERE user_id = %d AND thread_id = %d", bp_loggedin_user_id(), $thread_id );
		$wpdb->query($sql);

		wp_cache_delete( bp_loggedin_user_id(), 'bp_messages_unread_count' );
	}

	/**
	 * Mark a thread as unread.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param int $thread_id The message thread ID.
	 */
	public static function mark_as_unread( $thread_id ) {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 1 WHERE user_id = %d AND thread_id = %d", bp_loggedin_user_id(), $thread_id );
		$wpdb->query($sql);

		wp_cache_delete( bp_loggedin_user_id(), 'bp_messages_unread_count' );
	}

	/**
	 * Returns the total number of message threads for a user.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param int    $user_id The user ID.
	 * @param string $box  The type of mailbox to get. Either 'inbox' or 'sentbox'.
	 *                     Defaults to 'inbox'.
	 * @param string $type The type of messages to get. Either 'all' or 'unread'
	 *                     or 'read'. Defaults to 'all'.
	 * @return int
	 */
	public static function get_total_threads_for_user( $user_id, $box = 'inbox', $type = 'all' ) {
		global $wpdb, $bp;

		$exclude_sender = '';
		if ( $box != 'sentbox' )
			$exclude_sender = ' AND sender_only != 1';

		if ( $type == 'unread' )
			$type_sql = " AND unread_count != 0 ";
		elseif ( $type == 'read' )
			$type_sql = " AND unread_count = 0 ";

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(thread_id) FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND is_deleted = 0{$exclude_sender} {$type_sql}", $user_id ) );
	}

	/**
	 * Determine if the logged-in user is a sender of any message in a thread.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param int $thread_id The message thread ID.
	 * @return bool
	 */
	public static function user_is_sender( $thread_id ) {
		global $wpdb, $bp;

		$sender_ids = $wpdb->get_col( $wpdb->prepare( "SELECT sender_id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ) );

		if ( ! $sender_ids ) {
			return false;
		}

		return in_array( bp_loggedin_user_id(), $sender_ids );
	}

	/**
	 * Returns the userlink of the last sender in a message thread.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param int $thread_id The message thread ID.
	 * @return string|bool The user link on success. Boolean false on failure.
	 */
	public static function get_last_sender( $thread_id ) {
		global $wpdb, $bp;

		if ( ! $sender_id = $wpdb->get_var( $wpdb->prepare( "SELECT sender_id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d GROUP BY sender_id ORDER BY date_sent LIMIT 1", $thread_id ) ) ) {
			return false;
		}

		return bp_core_get_userlink( $sender_id, true );
	}

	/**
	 * Gets the unread message count for a user.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param int $user_id The user ID.
	 * @return int
	 */
	public static function get_inbox_count( $user_id = 0 ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		$unread_count = wp_cache_get( $user_id, 'bp_messages_unread_count' );

		if ( false === $unread_count ) {
			$unread_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(unread_count) FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND is_deleted = 0 AND sender_only = 0", $user_id ) );

			wp_cache_set( $user_id, $unread_count, 'bp_messages_unread_count' );
		}

		/**
		 * Filters a user's unread message count.
		 *
		 * @since BuddyPress (2.2.0)
		 *
		 * @param int $unread_count Unread message count.
		 * @param int $user_id      ID of the user.
		 */
		return apply_filters( 'messages_thread_get_inbox_count', (int) $unread_count, $user_id );
	}

	/**
	 * Checks whether a user is a part of a message thread discussion.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param int $thread_id The message thread ID.
	 * @param int $user_id The user ID.
	 * @return int The message ID on success.
	 */
	public static function check_access( $thread_id, $user_id = 0 ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			$user_id = bp_loggedin_user_id();

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND is_deleted = 0 AND user_id = %d", $thread_id, $user_id ) );
	}

	/**
	 * Checks whether a message thread exists.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param int $thread_id The message thread ID.
	 * @return int The message thread ID on success.
	 */
	public static function is_valid( $thread_id = 0 ) {
		global $wpdb;

		// Bail if no thread ID is passed
		if ( empty( $thread_id ) ) {
			return false;
		}

		$bp = buddypress();

		return $wpdb->get_var( $wpdb->prepare( "SELECT thread_id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d LIMIT 1", $thread_id ) );
	}

	/**
	 * Returns a string containing all the message recipient userlinks.
	 *
	 * String is comma-delimited.
	 *
	 * If a message thread has more than four users, the returned string is simply
	 * "X Recipients" where "X" is the number of recipients in the message thread.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param array $recipients Array containing the message recipients (array of objects).
	 * @return string
	 */
	public static function get_recipient_links( $recipients ) {
		if ( count( $recipients ) >= 5 )
			return sprintf( __( '%s Recipients', 'buddypress' ), number_format_i18n( count( $recipients ) ) );

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
	 * @since BuddyPress (1.2.0)
	 *
	 * @todo We should remove this.  No one is going to upgrade from v1.1, right?
	 * @return bool
	 */
	public static function update_tables() {
		global $wpdb, $bp;

		$bp_prefix = bp_core_get_table_prefix();
		$errors    = false;
		$threads   = $wpdb->get_results( "SELECT * FROM {$bp_prefix}bp_messages_threads" );

		// Nothing to update, just return true to remove the table
		if ( empty( $threads ) ) {
			return true;
		}

		foreach( (array) $threads as $thread ) {
			$message_ids = maybe_unserialize( $thread->message_ids );

			if ( !empty( $message_ids ) ) {
				$message_ids = implode( ',', $message_ids );

				// Add the thread_id to the messages table
				if ( ! $wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET thread_id = %d WHERE id IN ({$message_ids})", $thread->id ) ) )
					$errors = true;
			}
		}

		if ( $errors ) {
			return false;
		}

		return true;
	}
}

/**
 * Single message class.
 */
class BP_Messages_Message {
	/**
	 * ID of the message.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * ID of the message thread.
	 *
	 * @var int
	 */
	public $thread_id;

	/**
	 * ID of the sender.
	 *
	 * @var int
	 */
	public $sender_id;

	/**
	 * Subject line of the message.
	 *
	 * @var string
	 */
	public $subject;

	/**
	 * Content of the message.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * Date the message was sent.
	 *
	 * @var string
	 */
	public $date_sent;

	/**
	 * Message recipients.
	 *
	 * @var bool|array
	 */
	public $recipients = false;

	/**
	 * Constructor.
	 *
	 * @param int $id Optional. ID of the message.
	 */
	public function __construct( $id = null ) {
		$this->date_sent = bp_core_current_time();
		$this->sender_id = bp_loggedin_user_id();

		if ( !empty( $id ) ) {
			$this->populate( $id );
		}
	}

	/**
	 * Set up data related to a specific message object.
	 *
	 * @param int $id ID of the message.
	 */
	public function populate( $id ) {
		global $wpdb, $bp;

		if ( $message = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_messages} WHERE id = %d", $id ) ) ) {
			$this->id        = $message->id;
			$this->thread_id = $message->thread_id;
			$this->sender_id = $message->sender_id;
			$this->subject   = $message->subject;
			$this->message   = $message->message;
			$this->date_sent = $message->date_sent;
		}
	}

	/**
	 * Send a message.
	 *
	 * @return int|bool ID of the newly created message on success, false
	 *         on failure.
	 */
	public function send() {
		global $wpdb, $bp;

		$this->sender_id = apply_filters( 'messages_message_sender_id_before_save', $this->sender_id, $this->id );
		$this->thread_id = apply_filters( 'messages_message_thread_id_before_save', $this->thread_id, $this->id );
		$this->subject   = apply_filters( 'messages_message_subject_before_save',   $this->subject,   $this->id );
		$this->message   = apply_filters( 'messages_message_content_before_save',   $this->message,   $this->id );
		$this->date_sent = apply_filters( 'messages_message_date_sent_before_save', $this->date_sent, $this->id );

		/**
		 * Fires before the current message item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyPress (1.0.0)
		 *
		 * @param BP_Messages_Message Current instance of the message item being saved. Passed by reference.
		 */
		do_action_ref_array( 'messages_message_before_save', array( &$this ) );

		// Make sure we have at least one recipient before sending.
		if ( empty( $this->recipients ) )
			return false;

		$new_thread = false;

		// If we have no thread_id then this is the first message of a new thread.
		if ( empty( $this->thread_id ) ) {
			$this->thread_id = (int) $wpdb->get_var( "SELECT MAX(thread_id) FROM {$bp->messages->table_name_messages}" ) + 1;
			$new_thread = true;
		}

		// First insert the message into the messages table
		if ( !$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_messages} ( thread_id, sender_id, subject, message, date_sent ) VALUES ( %d, %d, %s, %s, %s )", $this->thread_id, $this->sender_id, $this->subject, $this->message, $this->date_sent ) ) )
			return false;

		$this->id = $wpdb->insert_id;

		$recipient_ids = array();

		if ( $new_thread ) {
			// Add an recipient entry for all recipients
			foreach ( (array) $this->recipients as $recipient ) {
				$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( user_id, thread_id, unread_count ) VALUES ( %d, %d, 1 )", $recipient->user_id, $this->thread_id ) );
				$recipient_ids[] = $recipient->user_id;
			}

			// Add a sender recipient entry if the sender is not in the list of recipients
			if ( !in_array( $this->sender_id, $recipient_ids ) )
				$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( user_id, thread_id, sender_only ) VALUES ( %d, %d, 1 )", $this->sender_id, $this->thread_id ) );
		} else {
			// Update the unread count for all recipients
			$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = unread_count + 1, sender_only = 0, is_deleted = 0 WHERE thread_id = %d AND user_id != %d", $this->thread_id, $this->sender_id ) );
		}

		messages_remove_callback_values();

		/**
		 * Fires after the current message item has been saved.
		 *
		 * @since BuddyPress (1.0.0)
		 *
		 * @param BP_Messages_Message Current instance of the message item being saved. Passed by reference.
		 */
		do_action_ref_array( 'messages_message_after_save', array( &$this ) );

		return $this->id;
	}

	/**
	 * Get a list of recipients for a message.
	 *
	 * @return array
	 */
	public function get_recipients() {
		global $bp, $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $this->thread_id ) );
	}

	/** Static Functions **************************************************/

	/**
	 * Get list of recipient IDs from their usernames.
	 *
	 * @param array $recipient_usernames Usernames of recipients.
	 * @return array
	 */
	public static function get_recipient_ids( $recipient_usernames ) {
		if ( !$recipient_usernames )
			return false;

		if ( is_array( $recipient_usernames ) ) {
			for ( $i = 0, $count = count( $recipient_usernames ); $i < $count; ++$i ) {
				if ( $rid = bp_core_get_userid( trim($recipient_usernames[$i]) ) ) {
					$recipient_ids[] = $rid;
				}
			}
		}

		return $recipient_ids;
	}

	/**
	 * Get the ID of the message last sent by the logged-in user for a given thread.
	 *
	 * @param int $thread_id ID of the thread.
	 * @return int|null ID of the message if found, otherwise null.
	 */
	public static function get_last_sent_for_user( $thread_id ) {
		global $wpdb, $bp;
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE sender_id = %d AND thread_id = %d ORDER BY date_sent DESC LIMIT 1", bp_loggedin_user_id(), $thread_id ) );
	}

	/**
	 * Check whether a user is the sender of a message.
	 *
	 * @param int $user_id ID of the user.
	 * @param int $message_id ID of the message.
	 * @return int|null Returns the ID of the message if the user is the
	 *         sender, otherwise null.
	 */
	public static function is_user_sender( $user_id, $message_id ) {
		global $wpdb, $bp;
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE sender_id = %d AND id = %d", $user_id, $message_id ) );
	}

	/**
	 * Get the ID of the sender of a message.
	 *
	 * @param int $message_id ID of the message.
	 * @return int|null The ID of the sender if found, otherwise null.
	 */
	public static function get_message_sender( $message_id ) {
		global $wpdb, $bp;
		return $wpdb->get_var( $wpdb->prepare( "SELECT sender_id FROM {$bp->messages->table_name_messages} WHERE id = %d", $message_id ) );
	}
}

/**
 * BuddyPress Notices class.
 *
 * Use this class to create, activate, deactivate or delete notices.
 *
 * @since BuddyPress (1.0.0)
 */
class BP_Messages_Notice {
	/**
	 * The notice ID.
	 *
	 * @var int
	 */
	public $id = null;

	/**
	 * The subject line for the notice.
	 *
	 * @var string
	 */
	public $subject;

	/**
	 * The content of the notice.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * The date the notice was created.
	 *
	 * @var string
	 */
	public $date_sent;

	/**
	 * Whether the notice is active or not.
	 *
	 * @var int
	 */
	public $is_active;

	/**
	 * Constructor.
	 *
	 * @since BuddyPress (1.0.0)
	 * @param int $id Optional. The ID of the current notice.
	 */
	public function __construct( $id = null ) {
		if ( $id ) {
			$this->id = $id;
			$this->populate();
		}
	}

	/**
	 * Populate method.
	 *
	 * Runs during constructor.
	 *
	 * @since BuddyPress (1.0.0)
	 */
	public function populate() {
		global $wpdb, $bp;

		$notice = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_notices} WHERE id = %d", $this->id ) );

		if ( $notice ) {
			$this->subject   = $notice->subject;
			$this->message   = $notice->message;
			$this->date_sent = $notice->date_sent;
			$this->is_active = $notice->is_active;
		}
	}

	/**
	 * Saves a notice.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb, $bp;

		$this->subject = apply_filters( 'messages_notice_subject_before_save', $this->subject, $this->id );
		$this->message = apply_filters( 'messages_notice_message_before_save', $this->message, $this->id );

		/**
		 * Fires before the current message notice item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyPress (1.0.0)
		 *
		 * @param BP_Messages_Notice Current instance of the message notice item being saved. Passed by reference.
		 */
		do_action_ref_array( 'messages_notice_before_save', array( &$this ) );

		if ( empty( $this->id ) ) {
			$sql = $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_notices} (subject, message, date_sent, is_active) VALUES (%s, %s, %s, %d)", $this->subject, $this->message, $this->date_sent, $this->is_active );
		} else {
			$sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_notices} SET subject = %s, message = %s, is_active = %d WHERE id = %d", $this->subject, $this->message, $this->is_active, $this->id );
		}

		if ( ! $wpdb->query( $sql ) ) {
			return false;
		}

		if ( ! $id = $this->id ) {
			$id = $wpdb->insert_id;
		}

		// Now deactivate all notices apart from the new one.
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_notices} SET is_active = 0 WHERE id != %d", $id ) );

		bp_update_user_last_activity( bp_loggedin_user_id(), bp_core_current_time() );

		/**
		 * Fires after the current message notice item has been saved.
		 *
		 * @since BuddyPress (1.0.0)
		 *
		 * @param BP_Messages_Notice Current instance of the message item being saved. Passed by reference.
		 */
		do_action_ref_array( 'messages_notice_after_save', array( &$this ) );

		return true;
	}

	/**
	 * Activates a notice.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @return bool
	 */
	public function activate() {
		$this->is_active = 1;
		return (bool) $this->save();
	}

	/**
	 * Deactivates a notice.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @return bool
	 */
	public function deactivate() {
		$this->is_active = 0;
		return (bool) $this->save();
	}

	/**
	 * Deletes a notice.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @return bool
	 */
	public function delete() {
		global $wpdb, $bp;

		/**
		 * Fires before the current message item has been deleted.
		 *
		 * @since BuddyPress (1.0.0)
		 *
		 * @param BP_Messages_Notice Current instance of the message notice item being deleted.
		 */
		do_action( 'messages_notice_before_delete', $this );

		$sql = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_notices} WHERE id = %d", $this->id );

		if ( ! $wpdb->query( $sql ) ) {
			return false;
		}

		return true;
	}

	/** Static Methods ********************************************************/

	/**
	 * Pulls up a list of notices.
	 *
	 * To get all notices, pass a value of -1 to pag_num
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @param array $args {
	 *     Array of parameters.
	 *     @type int $pag_num Number of notices per page. Defaults to 20.
	 *     @type int $pag_page The page number.  Defaults to 1.
	 * }
	 * @return array
	 */
	public static function get_notices( $args = array() ) {
		global $wpdb, $bp;

		$r = wp_parse_args( $args, array(
			'pag_num'  => 20, // Number of notices per page
			'pag_page' => 1   // Page number
		) );

		$limit_sql = '';
		if ( (int) $r['pag_num'] >= 0 ) {
			$limit_sql = $wpdb->prepare( "LIMIT %d, %d", (int) ( ( $r['pag_page'] - 1 ) * $r['pag_num'] ), (int) $r['pag_num'] );
		}

		$notices = $wpdb->get_results( "SELECT * FROM {$bp->messages->table_name_notices} ORDER BY date_sent DESC {$limit_sql}" );

		return $notices;
	}

	/**
	 * Returns the total number of recorded notices.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @return int
	 */
	public static function get_total_notice_count() {
		global $wpdb, $bp;

		$notice_count = $wpdb->get_var( "SELECT COUNT(id) FROM " . $bp->messages->table_name_notices );

		return $notice_count;
	}

	/**
	 * Returns the active notice that should be displayed on the frontend.
	 *
	 * @since BuddyPress (1.0.0)
	 *
	 * @return object The BP_Messages_Notice object
	 */
	public static function get_active() {
		$notice = wp_cache_get( 'active_notice', 'bp_messages' );

		if ( false === $notice ) {
			global $wpdb, $bp;

			$notice_id = $wpdb->get_var( "SELECT id FROM {$bp->messages->table_name_notices} WHERE is_active = 1" );
			$notice    = new BP_Messages_Notice( $notice_id );

			wp_cache_set( 'active_notice', $notice, 'bp_messages' );
		}

		return $notice;
	}
}
