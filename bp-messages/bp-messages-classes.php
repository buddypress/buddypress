<?php

/**
 * BuddyPress Messages Classes
 *
 * @package BuddyPress
 * @subpackage MessagesClasses
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Messages_Thread {
	public $thread_id;
	public $messages;
	public $recipients;
	public $sender_ids;

	public $unread_count;

	/**
	 * The content of the last message in this thread
	 *
	 * @since BuddyPress (1.2)
	 * @var string
	 */
	public $last_message_content;

	/**
	 * The date of the last message in this thread
	 *
	 * @since BuddyPress (1.2)
	 * @var string
	 */
	public $last_message_date;

	/**
	 * The ID of the last message in this thread
	 *
	 * @since BuddyPress (1.2)
	 * @var int
	 */
	public $last_message_id;

	/**
	 * The subject of the last message in this thread
	 *
	 * @since BuddyPress (1.2)
	 * @var string
	 */
	public $last_message_subject;

	/**
	 * The user ID of the author of the last message in this thread
	 *
	 * @since BuddyPress (1.2)
	 * @var int
	 */
	public $last_sender_id;

	/**
	 * Sort order of the messages in this thread (ASC or DESC).
	 *
	 * @since BuddyPress (1.5)
	 * @var string
	 */
	public $messages_order;

	public function __construct( $thread_id = false, $order = 'ASC' ) {
		if ( $thread_id )
			$this->populate( $thread_id, $order );
	}

	public function populate( $thread_id, $order ) {
		global $wpdb, $bp;

		if( 'ASC' != $order && 'DESC' != $order )
			$order= 'ASC';

		$this->messages_order = $order;
		$this->thread_id      = $thread_id;

		if ( !$this->messages = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_messages} WHERE thread_id = %d ORDER BY date_sent " . $order, $this->thread_id ) ) )
			return false;

		foreach ( (array) $this->messages as $key => $message )
			$this->sender_ids[$message->sender_id] = $message->sender_id;

		// Fetch the recipients
		$this->recipients = $this->get_recipients();

		// Get the unread count for the logged in user
		if ( isset( $this->recipients[bp_loggedin_user_id()] ) )
			$this->unread_count = $this->recipients[bp_loggedin_user_id()]->unread_count;
	}

	public function mark_read() {
		BP_Messages_Thread::mark_as_read( $this->thread_id );
	}

	public function mark_unread() {
		BP_Messages_Thread::mark_as_unread( $this->thread_id );
	}

	public function get_recipients() {
		global $wpdb, $bp;

		$recipients = array();
		$results    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $this->thread_id ) );

		foreach ( (array) $results as $recipient )
			$recipients[$recipient->user_id] = $recipient;

		return $recipients;
	}

	/** Static Functions ******************************************************/

	public static function delete( $thread_id ) {
		global $wpdb, $bp;

		// Mark messages as deleted
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_deleted = 1 WHERE thread_id = %d AND user_id = %d", $thread_id, bp_loggedin_user_id() ) );

		// Check to see if any more recipients remain for this message
		// if not, then delete the message from the database.
		$recipients = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND is_deleted = 0", $thread_id ) );

		if ( empty( $recipients ) ) {
			// Delete all the messages
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ) );

			// Delete all the recipients
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread_id ) );
		}

		return true;
	}

	public static function get_current_threads_for_user( $user_id, $box = 'inbox', $type = 'all', $limit = null, $page = null, $search_terms = '' ) {
		global $wpdb, $bp;

		$pag_sql = $type_sql = $search_sql = '';
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( $type == 'unread' )
			$type_sql = " AND r.unread_count != 0 ";
		elseif ( $type == 'read' )
			$type_sql = " AND r.unread_count = 0 ";

		if ( !empty( $search_terms ) ) {
			$search_terms = like_escape( esc_sql( $search_terms ) );
			$search_sql   = "AND ( subject LIKE '%%$search_terms%%' OR message LIKE '%%$search_terms%%' )";
		}

		if ( 'sentbox' == $box ) {
			$thread_ids    = $wpdb->get_results( $wpdb->prepare( "SELECT m.thread_id, MAX(m.date_sent) AS date_sent FROM {$bp->messages->table_name_recipients} r, {$bp->messages->table_name_messages} m WHERE m.thread_id = r.thread_id AND m.sender_id = r.user_id AND m.sender_id = %d AND r.is_deleted = 0 {$search_sql} GROUP BY m.thread_id ORDER BY date_sent DESC {$pag_sql}", $user_id ) );
			$total_threads = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( DISTINCT m.thread_id ) FROM {$bp->messages->table_name_recipients} r, {$bp->messages->table_name_messages} m WHERE m.thread_id = r.thread_id AND m.sender_id = r.user_id AND m.sender_id = %d AND r.is_deleted = 0 {$search_sql} ", $user_id ) );
		} else {
			$thread_ids = $wpdb->get_results( $wpdb->prepare( "SELECT m.thread_id, MAX(m.date_sent) AS date_sent FROM {$bp->messages->table_name_recipients} r, {$bp->messages->table_name_messages} m WHERE m.thread_id = r.thread_id AND r.is_deleted = 0 AND r.user_id = %d AND r.sender_only = 0 {$type_sql} {$search_sql} GROUP BY m.thread_id ORDER BY date_sent DESC {$pag_sql}", $user_id ) );
			$total_threads = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( DISTINCT m.thread_id ) FROM {$bp->messages->table_name_recipients} r, {$bp->messages->table_name_messages} m WHERE m.thread_id = r.thread_id AND r.is_deleted = 0 AND r.user_id = %d AND r.sender_only = 0 {$type_sql} {$search_sql} ", $user_id ) );
		}

		if ( empty( $thread_ids ) )
			return false;

		// Sort threads by date_sent
		foreach( (array) $thread_ids as $thread )
			$sorted_threads[$thread->thread_id] = strtotime( $thread->date_sent );

		arsort( $sorted_threads );

		$threads = false;
		foreach ( (array) $sorted_threads as $thread_id => $date_sent )
			$threads[] = new BP_Messages_Thread( $thread_id );

		return array( 'threads' => &$threads, 'total' => (int) $total_threads );
	}

	public static function mark_as_read( $thread_id ) {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 0 WHERE user_id = %d AND thread_id = %d", bp_loggedin_user_id(), $thread_id );
		$wpdb->query($sql);
	}

	public static function mark_as_unread( $thread_id ) {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET unread_count = 1 WHERE user_id = %d AND thread_id = %d", bp_loggedin_user_id(), $thread_id );
		$wpdb->query($sql);
	}

	public static function get_total_threads_for_user( $user_id, $box = 'inbox', $type = 'all' ) {
		global $wpdb, $bp;

		$exclude_sender = '';
		if ( $box != 'sentbox' )
			$exclude_sender = ' AND sender_only != 1';

		if ( $type == 'unread' )
			$type_sql = " AND unread_count != 0 ";
		else if ( $type == 'read' )
			$type_sql = " AND unread_count = 0 ";

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(thread_id) FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND is_deleted = 0{$exclude_sender} {$type_sql}", $user_id ) );
	}

	public static function user_is_sender( $thread_id ) {
		global $wpdb, $bp;

		$sender_ids = $wpdb->get_col( $wpdb->prepare( "SELECT sender_id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ) );

		if ( !$sender_ids )
			return false;

		return in_array( bp_loggedin_user_id(), $sender_ids );
	}

	public static function get_last_sender( $thread_id ) {
		global $wpdb, $bp;

		if ( !$sender_id = $wpdb->get_var( $wpdb->prepare( "SELECT sender_id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d GROUP BY sender_id ORDER BY date_sent LIMIT 1", $thread_id ) ) )
			return false;

		return bp_core_get_userlink( $sender_id, true );
	}

	public static function get_inbox_count( $user_id = 0 ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			$user_id = bp_loggedin_user_id();

		$sql = $wpdb->prepare( "SELECT SUM(unread_count) FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND is_deleted = 0 AND sender_only = 0", $user_id );
		$unread_count = $wpdb->get_var( $sql );

		if ( empty( $unread_count ) || is_wp_error( $unread_count ) )
			return 0;

		return (int) $unread_count;
	}

	public static function check_access( $thread_id, $user_id = 0 ) {
		global $wpdb, $bp;

		if ( empty( $user_id ) )
			$user_id = bp_loggedin_user_id();

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id = %d", $thread_id, $user_id ) );
	}

	public static function is_valid( $thread_id ) {
		global $wpdb, $bp;

		return $wpdb->get_var( $wpdb->prepare( "SELECT thread_id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d LIMIT 1", $thread_id ) );
	}

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

	public static function update_tables() {
		global $wpdb, $bp;

		$bp_prefix = bp_core_get_table_prefix();
		$errors    = false;
		$threads   = $wpdb->get_results( "SELECT * FROM {$bp_prefix}bp_messages_threads" );

		// Nothing to update, just return true to remove the table
		if ( empty( $threads ) )
			return true;

		foreach( (array) $threads as $thread ) {
			$message_ids = maybe_unserialize( $thread->message_ids );

			if ( !empty( $message_ids ) ) {
				$message_ids = implode( ',', $message_ids );

				// Add the thread_id to the messages table
				if ( !$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET thread_id = %d WHERE id IN ({$message_ids})", $thread->id ) ) )
					$errors = true;
			}
		}

		if ( $errors )
			return false;

		return true;
	}
}

class BP_Messages_Message {
	public $id;
	public $thread_id;
	public $sender_id;
	public $subject;
	public $message;
	public $date_sent;

	public $recipients = false;

	public function __construct( $id = null ) {
		$this->date_sent = bp_core_current_time();
		$this->sender_id = bp_loggedin_user_id();

		if ( !empty( $id ) ) {
			$this->populate( $id );
		}
	}

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

	public function send() {
		global $wpdb, $bp;

		$this->sender_id = apply_filters( 'messages_message_sender_id_before_save', $this->sender_id, $this->id );
		$this->thread_id = apply_filters( 'messages_message_thread_id_before_save', $this->thread_id, $this->id );
		$this->subject   = apply_filters( 'messages_message_subject_before_save',   $this->subject,   $this->id );
		$this->message   = apply_filters( 'messages_message_content_before_save',   $this->message,   $this->id );
		$this->date_sent = apply_filters( 'messages_message_date_sent_before_save', $this->date_sent, $this->id );

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

		do_action_ref_array( 'messages_message_after_save', array( &$this ) );

		return $this->id;
	}

	public function get_recipients() {
		global $bp, $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $this->thread_id ) );
	}

	/** Static Functions ******************************************************/

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

	public static function get_last_sent_for_user( $thread_id ) {
		global $wpdb, $bp;
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE sender_id = %d AND thread_id = %d ORDER BY date_sent DESC LIMIT 1", bp_loggedin_user_id(), $thread_id ) );
	}

	public static function is_user_sender( $user_id, $message_id ) {
		global $wpdb, $bp;
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE sender_id = %d AND id = %d", $user_id, $message_id ) );
	}

	public static function get_message_sender( $message_id ) {
		global $wpdb, $bp;
		return $wpdb->get_var( $wpdb->prepare( "SELECT sender_id FROM {$bp->messages->table_name_messages} WHERE id = %d", $message_id ) );
	}
}

class BP_Messages_Notice {
	public $id = null;
	public $subject;
	public $message;
	public $date_sent;
	public $is_active;

	public function __construct( $id = null ) {
		if ( $id ) {
			$this->id = $id;
			$this->populate($id);
		}
	}

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

	public function save() {
		global $wpdb, $bp;

		$this->subject = apply_filters( 'messages_notice_subject_before_save', $this->subject, $this->id );
		$this->message = apply_filters( 'messages_notice_message_before_save', $this->message, $this->id );

		do_action_ref_array( 'messages_notice_before_save', array( &$this ) );

		if ( empty( $this->id ) )
			$sql = $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_notices} (subject, message, date_sent, is_active) VALUES (%s, %s, %s, %d)", $this->subject, $this->message, $this->date_sent, $this->is_active );
		else
			$sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_notices} SET subject = %s, message = %s, is_active = %d WHERE id = %d", $this->subject, $this->message, $this->is_active, $this->id );

		if ( !$wpdb->query( $sql ) )
			return false;

		if ( !$id = $this->id )
			$id = $wpdb->insert_id;

		// Now deactivate all notices apart from the new one.
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_notices} SET is_active = 0 WHERE id != %d", $id ) );

		bp_update_user_last_activity( bp_loggedin_user_id(), bp_core_current_time() );

		do_action_ref_array( 'messages_notice_after_save', array( &$this ) );

		return true;
	}

	public function activate() {
		$this->is_active = 1;
		return (bool) $this->save();
	}

	public function deactivate() {
		$this->is_active = 0;
		return (bool) $this->save();
	}

	public function delete() {
		global $wpdb, $bp;

		$sql = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_notices} WHERE id = %d", $this->id );

		if ( !$wpdb->query( $sql ) )
			return false;

		return true;
	}

	/** Static Methods ********************************************************/

	/**
	 * Pulls up a list of notices
	 *
	 * To get all notices, pass a value of -1 to pag_num
	 *
	 * @param array $args See $defaults for explanation of accepted arguments
	 * @return array $notices
	 */
	public static function get_notices( $args = array() ) {
		global $wpdb, $bp;

		$defaults = array(
			'pag_num'  => 20, // Number of notices per page
			'pag_page' => 1   // Page number
		);
		$r = wp_parse_args( $args, $defaults );
		extract( $r );

		$limit_sql = '';
		if ( (int) $pag_num >= 0 ) {
			$limit_sql = $wpdb->prepare( "LIMIT %d, %d", (int) ( ( $pag_page - 1 ) * $pag_num ), (int) $pag_num );
		}

		$notices = $wpdb->get_results( "SELECT * FROM {$bp->messages->table_name_notices} ORDER BY date_sent DESC {$limit_sql}" );

		return $notices;
	}

	public static function get_total_notice_count() {
		global $wpdb, $bp;

		$notice_count = $wpdb->get_var( "SELECT COUNT(id) FROM " . $bp->messages->table_name_notices );

		return $notice_count;
	}

	public static function get_active() {
		global $wpdb, $bp;

		$notice_id = $wpdb->get_var( "SELECT id FROM {$bp->messages->table_name_notices} WHERE is_active = 1" );

		return new BP_Messages_Notice( $notice_id );
	}
}
