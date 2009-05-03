<?php

Class BP_Messages_Thread { 
	var $thread_id;
	var $subject;
	var $sender_id;
	var $recipients;
	var $last_post_date;
	var $messages;
	
	var $has_access;
	var $unread_count;
	var $is_deleted = false;
	
	var $box;
	var $last_sent_by_user;
	var $last_received_by_user;
	
	var $table_name;

	function bp_messages_thread( $id = null, $get_all_messages = false, $exclude_user_id = null, $box = 'inbox' ) {
		global $bp_messages_table_name;

		$this->table_name = $bp_messages_table_name;
		$this->box = $box;
		
		if ( $id ) {
			$this->populate( $id, $box );
		}
	}
	
	function populate( $id ) {		
		$this->thread_id = $id;

		// Check to make sure the user hasn't deleted this thread.
		$this->check_status();
		
		if ( !$this->is_deleted ) { 
			$this->messages = $this->get_messages($get_all_messages, $exclude_user_id);
			$this->has_access = $this->get_access();
			$this->unread_count = $this->get_unread();

			if ( $this->messages ) {
				$thread_msg_id = ( $this->box == 'sentbox' ) ? $this->last_sent_by_user : $this->last_received_by_user;

				for ( $i = 0; $i < count($this->messages); $i++ ) {
					if ( $this->messages[$i]->id == $thread_msg_id ) {
						// Set thread details to the details of the last sent or received message in the thread.
						$this->subject = $this->messages[$i]->subject;
						$this->last_post_date = $this->messages[$i]->date_sent;
						$this->creator_id = $this->messages[$i]->sender_id;
						$this->message = $this->messages[$i]->message;
						$this->recipients = $this->get_recipients();
					}
				}
			}
		} else {
			$this->has_access = false;
		}
	}
	
	function check_status() {
		global $wpdb, $userdata, $bp_messages_table_name_deleted;
		
		$sql = $wpdb->prepare("SELECT is_deleted FROM $bp_messages_table_name_deleted WHERE thread_id = %d AND user_id = %d", $this->thread_id, $userdata->ID);
		$is_deleted = $wpdb->get_var($sql);
		
		if ( $is_deleted )
			$this->is_deleted = true;
	}
	
	function get_messages( $get_all_messages, $exclude_user_id) {
		global $wpdb, $userdata;
		
		// if ( !$get_all_messages )
		// 		$limit = $wpdb->prepare(" ORDER BY date_sent DESC LIMIT 1");
		
		switch ( $this->box ) {
			case 'inbox':
				$boxclause = $wpdb->prepare(" AND recipient_id = %d", $userdata->ID);
			break;
			case 'sentbox':
				$boxclause = $wpdb->prepare(" AND sender_id = %d", $userdata->ID);
				$exclude_user_id = null;
			break;
			default:
				$boxclause = '';
			break;
		}
			
		$sql = $wpdb->prepare( "SELECT id, sender_id, recipient_id FROM $this->table_name WHERE thread_id = %d$boxclause$limit", $this->thread_id);

		if ( !$results = $wpdb->get_results($sql) )
			return false;
		
		for ( $i = 0; $i < count($results); $i++ ) {
			if ( $results[$i]->sender_id == $userdata->ID )
				$this->last_sent_by_user = $results[$i]->id;
			
			if ( $results[$i]->recipient_id == $userdata->ID )
				$this->last_received_by_user = $results[$i]->id;
			
			if ( $results[$i]->sender_id != $exclude_user_id ) {
				$messages[] = new BP_Messages_Message( $results[$i]->id );
			} else if ( $results[$i]->sender_id == $results[$i]->recipient_id ) {
				$messages[] = new BP_Messages_Message($results[$i]->id);				
			}
		}
		
		return $messages;
	}
	
	function get_access() {
		global $userdata;
		
		$has_access = true;
		
		for ( $i = 0; $i < count($this->messages); $i++ ) {
			$message = $this->messages[$i];
			
			if ( !( $message->sender_id == $userdata->ID || $message->recipient_id == $userdata->ID ) )
				$has_access = false;
		}
		
		return $has_access;
	}
	
	function get_unread() {
		$unread_count = 0;
		
		for ( $i = 0; $i < count($this->messages); $i++ ) {
			$message = $this->messages[$i];
			
			if ( $message->is_read == 0 )
				$unread_count++;
		}
		
		return $unread_count;
	}
	
	function get_recipients() {
		$recipients = array();
		
		for ( $i = 0; $i < count($this->messages); $i++ ) {
			$message = $this->messages[$i];
			
			if ( !in_array($message->recipient_id, $recipients) && $message->recipient_id != $this->sender_id ) 
				$recipients[] = $message->recipient_id;
		}
		
		for ( $i = 0; $i < count($recipients); $i++ ) {
			$recipients[$i] = bp_core_get_userlink($recipients[$i]);
		}
		
		return implode( ', ', $recipients);
	}
	
	/** Static Functions **/
	
	function get_threads_for_user( $box, $user_id, $get_all_messages, $exclude_user_id ) {
		global $wpdb, $bp_messages_table_name, $bp_messages_table_name_deleted, $userdata;
		
		switch ( $box ) {
			case 'inbox':
			default:
				$whereclause = $wpdb->prepare("ON t.recipient_id = td.user_id AND t.thread_id = td.thread_id WHERE t.recipient_id = %d", $user_id);
			break;
			case 'sentbox':
				$whereclause = $wpdb->prepare("ON t.sender_id = td.user_id AND t.thread_id = td.thread_id WHERE t.sender_id = %d", $user_id);
				$exclude_user_id = null;
			break;
		}
		
		$sql = $wpdb->prepare("SELECT DISTINCT t.thread_id, td.is_deleted FROM $bp_messages_table_name t LEFT JOIN $bp_messages_table_name_deleted td $whereclause ORDER BY t.date_sent DESC");

		if ( !$thread_ids = $wpdb->get_results($sql) )
			return false;

		for ( $i = 0; $i < count($thread_ids); $i++ ) {
			if ( !$thread_ids[$i]->is_deleted )
				$threads[] = new BP_Messages_Thread( $thread_ids[$i]->thread_id, $get_all_messages, $exclude_user_id, $box );
		}
		
		$have_messages = 0;
		for ( $i = 0; $i < count($threads); $i++ ) {
			if ( $threads[$i]->messages ) {
				$have_messages = 1;
			}
		}
		
		$threads['have_messages'] = $have_messages;

		return $threads;
	}
	
	function get_new_thread_id() {
		global $wpdb, $bp_messages_table_name;
		
		$sql = $wpdb->prepare("SELECT MAX(thread_id) FROM $bp_messages_table_name");
		
		$thread_id = $wpdb->get_var($sql);
		
		echo $thread_id + 1;
	}
	
	function delete($thread_id) {
		global $wpdb, $bp_messages_table_name_deleted, $userdata;	
		
		$sql = $wpdb->prepare("INSERT INTO $bp_messages_table_name_deleted ( thread_id, user_id, is_deleted ) VALUES ( %d, %d, 1 )", $thread_id, $userdata->ID);
		
		if ( !$result = $wpdb->query($sql) )
			return false;
		
		return true;
	}
	
	function get_inbox_count() {
		global $wpdb, $bp_messages_table_name, $bp_messages_table_name_deleted, $userdata;

		$count = 0;
		$sql = $wpdb->prepare( "SELECT t.id, td.is_deleted FROM $bp_messages_table_name t LEFT JOIN $bp_messages_table_name_deleted td ON t.recipient_id = td.user_id AND t.thread_id = td.thread_id WHERE t.recipient_id = %d AND t.is_read = 0", $userdata->ID);

		if ( !$messages = $wpdb->get_results($sql) )
			return false;
		
		for ( $i = 0; $i < count($messages); $i++ ) {
			if ( !$messages[$i]->is_deleted ) {
				$count++;
			}
		}
		
		return $count;
	}
}

Class BP_Messages_Message {
	var $id = null;
	var $sender_id;
	var $recipient_id;
	var $thread_id;
	var $subject;
	var $message;
	var $is_read;
	var $date_sent;
	
	var $recipient_username;
	var $table_name;
	

	function bp_messages_message( $id = null ) {
		global $bp_messages_table_name, $userdata;
 
		$this->table_name = $bp_messages_table_name;
		$this->date_sent = time();
		$this->sender_id = $userdata->ID;

		if ( $id ) {
			if ( bp_core_validate($id) ) {
				$this->populate($id);
			}
		}
	}
	
	function populate( $id ) {
		global $wpdb;
		
		$sql = $wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id);

		if ( $message = $wpdb->get_row($sql) ) {
			$this->id = $message->id;
			$this->sender_id = $message->sender_id;
			$this->recipient_id = $message->recipient_id;
			$this->thread_id = $message->thread_id;
			$this->subject = $message->subject;
			$this->message = $message->message;
			$this->is_read = $message->is_read;
			$this->date_sent = $message->date_sent;
			
			$this->recipient_username = bp_core_get_username($this->recipient_id);
		}

	}
	
	function send() {	
		global $wpdb, $userdata;

		$sql = $wpdb->prepare("INSERT INTO $this->table_name ( sender_id, recipient_id, thread_id, subject, message, date_sent ) VALUES ( %d, %d, %d, %s, %s, %d )", $userdata->ID, $this->recipient_id, $this->thread_id, $this->subject, $this->message, $this->date_sent);

		if ( $wpdb->query($sql) === false )
			return false;
		
		return true;
	}
	
	function delete() {
		global $wpdb;
		
		$sql = $wpdb->prepare("DELETE FROM $this->table_name WHERE id = %d LIMIT 1", $message_id);
		
		if($this->wpdb->query($sql) == false)
			return false;
			
		return true;
	}
	
	function archive() {
		
	}
	
	function mark_as_read() {
		global $wpdb;
		
		$sql = $wpdb->prepare( "UPDATE $this->table_name SET is_read = 1 WHERE id = %d", $this->id );		
		$wpdb->query($sql);
	}
	
	function user_can_view() {
		global $wpdb, $userdata;
		
		$sql = $wpdb->prepare( "SELECT id FROM $this->table_name WHERE id = %d AND ( recipient_id = %d OR sender_id = %d ) LIMIT 1", $this->id, $userdata->ID, $userdata->ID);
		return $wpdb->get_row($sql);
	}
	
	// Static Functions
	
	function get_messages() {
		global $wpdb, $bp_messages_table_name, $userdata;
		
		$sql = $wpdb->prepare( "SELECT * FROM $bp_messages_table_name WHERE recipient_id = %d ORDER BY date_sent DESC", $userdata->ID );			

		$messages = $wpdb->get_results($sql);
		
		return $messages;
	}
	
	function get_message_ids( $thread_id ) {
		global $wpdb, $bp_messages_table_name;
		
		$sql = $wpdb->prepare( "SELECT id FROM $bp_messages_table_name WHERE thread_id = %d", $thread_id);
		
		return $wpdb->get_results($sql);
	}
}

?>