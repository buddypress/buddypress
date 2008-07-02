<?php

Class BP_Messages_Thread { 
	var $thread_id;
	var $message_ids;
	var $first_post_date;
	
	var $last_post_date;
	var $last_sender_id;
	var $last_message_id;
	var $last_message_subject;
	var $last_message_message;
	var $last_message_date_sent;
	
	var $messages = null;
	var $has_access = false;
	var $unread_count = 0;
	var $recipients = null;
	
	var $box;

	function bp_messages_thread( $id = null, $get_all_messages = false, $box = 'inbox' ) {
		$this->box = $box;
		
		if ( $id ) {
			$this->populate( $id, $box );
		}
		
		if ( $get_all_messages ) {
			$this->messages = $this->get_messages();
			$this->recipients = $this->get_recipients();
		}
	}
	
	function populate( $id ) {	
		global $wpdb, $bp_messages_table_name_threads, $bp_messages_table_name_messages;
		global $userdata;

		$sql = $wpdb->prepare( "SELECT * FROM $bp_messages_table_name_threads WHERE id = %d", $id );
		$thread = $wpdb->get_row($sql);
		
		if ( $thread ) {
			
			// If we're only viewing a thread in a list of threads, don't bother using
			// up resources checking if we have access. Only check if we're viewing the full
			// thread.
			if ( $get_all_messages )
				$this->has_access = $this->check_access($id);
			else
				$this->has_access = true;

			if ( $this->has_access ) {
				$this->thread_id = $thread->id;
				$this->message_ids = unserialize($thread->message_ids);
				$this->message_ids = implode( ',', $this->message_ids );
				$this->first_post_date = $thread->first_post_date;
				$this->last_post_date = $thread->last_post_date;
				$this->last_message_id = $thread->last_message_id;
				$this->last_sender_id = $thread->last_sender_id;
				$this->unread_count = $this->get_unread();
				
				// If this is for the sentbox, we need to use the message id of the last
				// sent message for the current user, if it is not the last one set for this thread.

				if ( $this->box == 'sentbox' ) {
					$this->recipients = $this->get_recipients();
					
					if ( $this->last_sender_id != $userdata->ID ) {
						$this->last_message_id = BP_Messages_Message::get_last_sent_for_user($this->thread_id);
						$this->last_sender_id = $userdata->ID;
					}
				}
				
				$sql = $wpdb->prepare( "SELECT * FROM $bp_messages_table_name_messages WHERE id = %d", $this->last_message_id );	
				$last_message = $wpdb->get_row($sql);
				
				if ( $last_message ) {
					$this->last_message_subject = $last_message->subject;
					$this->last_message_message = $last_message->message;
					$this->last_message_date_sent = $last_message->date_sent;
				}
			}
		}
	}
	
	function get_messages() {
		global $wpdb, $userdata;
		global $bp_messages_table_name_messages;
			
		$sql = "SELECT * FROM $bp_messages_table_name_messages WHERE id IN (" . $wpdb->escape($this->message_ids) . ")";
		
		if ( !$messages = $wpdb->get_results($sql) )
			return false;

		return $messages;
	}

	function get_unread() {
		global $wpdb, $bp_messages_table_name_recipients, $userdata;

		$sql = $wpdb->prepare( "SELECT unread_count FROM $bp_messages_table_name_recipients WHERE thread_id = %d AND user_id = %d", $this->thread_id, $userdata->ID );
		$unread_count = $wpdb->get_var($sql);
		
		return $unread_count;
	}
	
	function mark_read() {
		BP_Messages_Thread::mark_as_read($this->thread_id);
	}
	
	function mark_unread() {
		BP_Messages_Thread::mark_as_unread($this->thread_id);
	}
	
	function get_recipients() {
		global $wpdb, $userdata;
		global $bp_messages_table_name_recipients;

		$recipients = $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM $bp_messages_table_name_recipients WHERE thread_id = %d", $this->thread_id ) );

		for ( $i = 0; $i < count($recipients); $i++ ) {
			$recipient = $recipients[$i]->user_id;
			
			if ( count($recipients) == 1 ||$recipient != $userdata->ID )
				$recipient_ids[] = $recipient;
		}
		
		return $recipient_ids;
	}
	
	/** Static Functions **/
	
	function get_current_threads_for_user( $user_id, $box = 'inbox', $limit = null, $page = null ) {
		global $wpdb, $userdata;
		global $bp_messages_table_name_recipients;
		global $bp_messages_table_name_threads;

		$exclude_sender = '';
		if ( $box != 'sentbox' )
			$exclude_sender = ' AND sender_only != 1';
		
		// If we have pagination values set we want to pass those to the query
		if ( $limit && $page )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
			
		$sql = $wpdb->prepare( "SELECT r.thread_id FROM $bp_messages_table_name_recipients r, $bp_messages_table_name_threads t WHERE t.id = r.thread_id AND r.user_id = %d$exclude_sender ORDER BY t.last_post_date DESC$pag_sql", $userdata->ID);

		if ( !$thread_ids = $wpdb->get_results($sql) )
			return false;
		
		for ( $i = 0; $i < count($thread_ids); $i++ ) {
			if ( $box == 'sentbox' ) {
				if ( BP_Messages_Thread::user_is_sender($thread_ids[$i]->thread_id) )
					$threads[] = new BP_Messages_Thread( $thread_ids[$i]->thread_id, false, $box );	
			} else {
				$threads[] = new BP_Messages_Thread( $thread_ids[$i]->thread_id, false, $box );
			}
		}
		
		return $threads;
	}
	
	function mark_as_read( $thread_id ) {
		global $wpdb, $userdata;
		global $bp_messages_table_name_recipients;
		
		$sql = $wpdb->prepare( "UPDATE $bp_messages_table_name_recipients SET unread_count = 0 WHERE user_id = %d AND thread_id = %d", $userdata->ID, $thread_id );
		$wpdb->query($sql);
	}
	
	function mark_as_unread( $thread_id ) {
		global $wpdb, $userdata;
		global $bp_messages_table_name_recipients;
		
		$sql = $wpdb->prepare( "UPDATE $bp_messages_table_name_recipients SET unread_count = 1 WHERE user_id = %d AND thread_id = %d", $userdata->ID, $thread_id );
		$wpdb->query($sql);
	}
	
	function get_total_threads_for_user( $user_id, $box = 'inbox' ) {
		global $wpdb, $userdata;
		global $bp_messages_table_name_recipients;
		global $bp_messages_table_name_threads;

		$exclude_sender = '';
		if ( $box != 'sentbox' )
			$exclude_sender = ' AND sender_only != 1';

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT count(thread_id) FROM $bp_messages_table_name_recipients WHERE user_id = %d$exclude_sender", $user_id ) );
	}
	
	function user_is_sender($thread_id) {
		global $wpdb, $bp_messages_table_name_threads;
		global $userdata;
		
		$sender_ids = $wpdb->get_var( $wpdb->prepare( "SELECT sender_ids FROM $bp_messages_table_name_threads WHERE id = %d", $thread_id ) );	
		
		if ( !$sender_ids )
			return false;
			
		$sender_ids = unserialize($sender_ids);
		
		return in_array( $userdata->ID, $sender_ids );
	}

	function get_last_sender($thread_id) {
		global $wpdb, $bp_messages_table_name_threads;
		global $userdata;

		$sql = $wpdb->prepare("SELECT last_sender_id FROM $bp_messages_table_name_threads WHERE id = %d", $thread_id);

		if ( !$sender_id = $wpdb->get_var($sql) )
			return false;

		return bp_core_get_userlink( $sender_id, true );
	}
	
	function delete($thread_id) {
		global $wpdb, $userdata;
		global $bp_messages_table_name_recipients, $bp_messages_table_name_messages, $bp_messages_table_name_threads;	
		
		$sql = $wpdb->prepare( "DELETE FROM $bp_messages_table_name_recipients WHERE thread_id = %d AND user_id = %d", $thread_id, $userdata->ID);
		
		if ( !$result = $wpdb->query($sql) )
			return false;
			
		// Check to see if any more recipients remain for this message
		// if not, then delete the message from the database.
		$recipients =  $wpdb->get_results( $wpdb->prepare( "SELECT id FROM $bp_messages_table_name_recipients WHERE thread_id = %d", $thread_id ) );

		if ( !$recipients ) {
			// Get message ids:
			$message_ids = $wpdb->get_var( $wpdb->prepare( "SELECT message_ids FROM $bp_messages_table_name_threads WHERE id = %d", $thread_id ) );
			$message_ids = unserialize($message_ids);
			
			// delete thread:
			$wpdb->query( $wpdb->prepare( "DELETE FROM $bp_messages_table_name_threads WHERE id = %d", $thread_id ) );
			
			// delete messages:
			for ( $i = 0; $i < count($message_ids); $i++ ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM $bp_messages_table_name_messages WHERE id = %d", $message_ids[$i] ) );
			}
		}
		return true;
	}
	
	function get_inbox_count() {
		global $wpdb, $bp_messages_table_name_recipients, $userdata;

		$sql = $wpdb->prepare( "SELECT unread_count FROM $bp_messages_table_name_recipients WHERE user_id = %d", $userdata->ID);

		if ( !$unread_counts = $wpdb->get_results($sql) )
			return false;
		
		$count = 0;
		for ( $i = 0; $i < count($unread_counts); $i++ ) {
			$count += $unread_counts[$i]->unread_count;
		}
		
		return $count;
	}
	
	function check_access($id) {
		global $wpdb, $bp_messages_table_name_recipients, $userdata;
		
		$sql = $wpdb->prepare("SELECT id FROM $bp_messages_table_name_recipients WHERE thread_id = %d AND user_id = %d", $id, $userdata->ID );
		$has_access = $wpdb->get_var($sql);
		
		if ( $has_access )
			return true;

		return false;
	}
	
	function get_recipient_links($recipients) {
		if ( count($recipients) >= 5 )
			return count($recipients) . __(' Recipients');
			
		for ( $i = 0; $i < count($recipients); $i++ ) {
			$recipient_links[] = bp_core_get_userlink( $recipients[$i] );
		}

		return implode( ', ', $recipient_links);
	}
}

Class BP_Messages_Message {
	var $id = null;
	var $sender_id;
	var $subject;
	var $message;
	var $date_sent;
	var $message_order;
	var $sender_is_group;
	
	var $thread_id;
	var $recipients;

	function bp_messages_message( $id = null ) {
		global $userdata;

		$this->date_sent = time();
		$this->sender_id = $userdata->ID;

		if ( $id ) {
			$this->populate($id);
		}
	}
	
	function populate( $id ) {
		global $wpdb, $bp_messages_table_name_messages;
		
		$sql = $wpdb->prepare("SELECT * FROM $bp_messages_table_name_messages WHERE id = %d", $id);

		if ( $message = $wpdb->get_row($sql) ) {
			$this->id = $message->id;
			$this->sender_id = $message->sender_id;
			$this->subject = $message->subject;
			$this->message = $message->message;
			$this->date_sent = $message->date_sent;
			$this->message_order = $message->message_order;
			$this->sender_is_group = $message->sender_is_group;
		}

	}
	
	function send() {	
		global $wpdb, $userdata;
		global $bp_messages_table_name_threads;
		global $bp_messages_table_name_messages;
		global $bp_messages_table_name_recipients;

		// First insert the message into the messages table
		$sql = $wpdb->prepare( "INSERT INTO $bp_messages_table_name_messages ( sender_id, subject, message, date_sent, message_order, sender_is_group ) VALUES ( %d, %s, %s, FROM_UNIXTIME(%d), %d, %d )", $userdata->ID, $this->subject, $this->message, $this->date_sent, $this->message_order, $this->sender_is_group );

		if ( $wpdb->query($sql) === false )
			return false;

		// Next, if thread_id is set, we are adding to an existing thread, if not, start a new one.
		if ( $this->thread_id ) {
			// Select and update the current message ids for the thread.
			$the_ids = $wpdb->get_row( $wpdb->prepare( "SELECT message_ids, sender_ids FROM $bp_messages_table_name_threads WHERE id = %d", $this->thread_id ) );
			$message_ids = unserialize($the_ids->message_ids);
			$message_ids[] = $wpdb->insert_id;
			$message_ids = serialize($message_ids);
			
			// Update the sender ids for the thread
			$sender_ids = unserialize($the_ids->sender_ids);

			if ( !in_array( $userdata->ID, $sender_ids ) || !$sender_ids )
				$sender_ids[] = $userdata->ID;
				
			$sender_ids = serialize($sender_ids);			
			
			$sql = $wpdb->prepare( "UPDATE $bp_messages_table_name_threads SET message_ids = %s, sender_ids = %s, last_message_id = %d, last_sender_id = %d WHERE id = %d", $message_ids, $sender_ids, $wpdb->insert_id, $this->sender_id, $this->thread_id );

			if ( $wpdb->query($sql) === false )
				return false;
			
			// Find the recipients and update the unread counts for each
			$recipients = $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM $bp_messages_table_name_recipients WHERE thread_id = %d", $this->thread_id ) );
			
			for ( $i = 0; $i < count($recipients); $i++ ) {
				if ( $recipients[$i]->user_id != $userdata->ID )
					$wpdb->query( $wpdb->prepare( "UPDATE $bp_messages_table_name_recipients SET unread_count = unread_count + 1, sender_only = 0 WHERE thread_id = %d AND user_id = %d", $this->thread_id, $recipients[$i]->user_id ) );
			}
		} else {
			// Create a new thread.
			$message_id = $wpdb->insert_id;
			$serialized_message_id = serialize( array( (int)$message_id ) );
			$serialized_sender_id = serialize( array( (int)$userdata->ID ) );
			
			$sql = $wpdb->prepare( "INSERT INTO $bp_messages_table_name_threads ( message_ids, sender_ids, first_post_date, last_post_date, last_message_id, last_sender_id ) VALUES ( %s, %s, FROM_UNIXTIME(%d), FROM_UNIXTIME(%d), %d, %d )", $serialized_message_id, $serialized_sender_id, $this->date_sent, $this->date_sent, $message_id, $this->sender_id ); 
			
			if ( $wpdb->query($sql) === false )
				return false;
			
			$this->thread_id = $wpdb->insert_id;
			
			// Add a new entry for each recipient;
			for ( $i = 0; $i < count($this->recipients); $i++ ) {
				$wpdb->query( $wpdb->prepare( "INSERT INTO $bp_messages_table_name_recipients ( user_id, thread_id, unread_count ) VALUES ( %d, %d, 1 )", $this->recipients[$i], $this->thread_id ) );
			}
			
			if ( !in_array( $this->sender_id, $this->recipients ) ) {
				// Finally, add a recipient entry for the sender, as replies need to go to this person too.
				$wpdb->query( $wpdb->prepare( "INSERT INTO $bp_messages_table_name_recipients ( user_id, thread_id, unread_count, sender_only ) VALUES ( %d, %d, 0, 1 )", $this->sender_id, $this->thread_id ) );
			}
		}
		
		return true;
	}
	
	// Static Functions
	
	function get_recipient_ids( $recipient_usernames ) {
		if ( !$recipient_usernames )
			return false;
	
		if ( is_array($recipient_usernames) ) {
			for ( $i = 0; $i < count($recipient_usernames); $i++ ) {
				if ( $rid = bp_core_get_userid( trim($recipient_usernames[$i]) ) )
					if ( $i ) {
						if ( !in_array( $rid, $recipient_ids ) )
							$recipient_ids[] = $rid;
					} else {
						$recipient_ids[] = $rid;	
					}
			}
		}
		
		return $recipient_ids;
	}
	
	function get_last_sent_for_user( $thread_id ) {
		global $wpdb, $userdata;
		global $bp_messages_table_name_messages, $bp_messages_table_name_threads;
		
		$message_ids = $wpdb->get_var( $wpdb->prepare( "SELECT message_ids FROM $bp_messages_table_name_threads WHERE id = %d", $thread_id ) );
		$message_ids = implode( ',', unserialize($message_ids));

		$sql = "SELECT id FROM $bp_messages_table_name_messages WHERE sender_id = $userdata->ID AND id IN ($message_ids) ORDER BY date_sent DESC LIMIT 1";
		return $wpdb->get_var($sql);
	}
	
}

Class BP_Messages_Notice {
	var $id = null;
	var $subject;
	var $message;
	var $date_sent;
	var $is_active;
	
	function bp_messages_notice($id = null) {
		if ( $id ) {
			$this->id = $id;
			$this->populate($id);
		}
	}
	
	function populate() {
		global $wpdb, $bp_messages_table_name_notices;
		
		$notice = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $bp_messages_table_name_notices WHERE id = %d", $this->id ) );
		
		if ( $notice ) {
			$this->subject = $notice->subject;
			$this->message = $notice->message;
			$this->date_sent = $notice->date_sent;
			$this->is_active = $notice->is_active;
		}
	}
	
	function save() {
		global $wpdb, $bp_messages_table_name_notices;
		
		if ( !$this->id ) {
			$sql = $wpdb->prepare( "INSERT INTO $bp_messages_table_name_notices (subject, message, date_sent, is_active) VALUES (%s, %s, FROM_UNIXTIME(%d), %d)", $this->subject, $this->message, $this->date_sent, $this->is_active );	
		} else {
			$sql = $wpdb->prepare( "UPDATE $bp_messages_table_name_notices SET subject = %s, message = %s, date_sent = FROM_UNIXTIME(%d), is_active = %d WHERE id = %d", $this->subject, $this->message, $this->date_sent, $this->is_active, $this->id );				
		}
	
		if ( !$wpdb->query($sql) )
			return false;
		
		if ( !$id = $this->id )
			$id = $wpdb->insert_id;
			
		// Now deactivate all notices apart from the new one.
		$wpdb->query( $wpdb->prepare( "UPDATE $bp_messages_table_name_notices SET is_active = 0 WHERE id != %d", $id ) );
		
		return true;
	}
	
	function activate() {
		$this->is_active = 1;
		if ( !$this->save() )
			return false;
			
		return true;
	}
	
	function deactivate() {
		$this->is_active = 0;
		if ( !$this->save() )
			return false;
		
		return true;
	}
	
	function delete() {
		global $wpdb, $bp_messages_table_name_notices;
		
		$sql = $wpdb->prepare( "DELETE FROM $bp_messages_table_name_notices WHERE id = %d", $this->id );
		
		if ( !$wpdb->query($sql) )
			return false;
		
		return true;	
	}
	
	// Static Functions
	
	function get_notices() {
		global $wpdb, $bp_messages_table_name_notices;
		
		$notices = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $bp_messages_table_name_notices ORDER BY date_sent DESC" ) );
		return $notices;
	}
	
	function get_total_notice_count() {
		global $wpdb, $bp_messages_table_name_notices;
		
		$notice_count = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM $bp_messages_table_name_notices" ) );
		return $notice_count;
	}
	
	function get_active() {
		global $wpdb, $bp_messages_table_name_notices;
		
		$notice_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $bp_messages_table_name_notices WHERE is_active = 1") );
		return new BP_Messages_Notice($notice_id);
	}
}
?>