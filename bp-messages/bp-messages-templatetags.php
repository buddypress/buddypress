<?php

Class BP_Messages_Template {
	var $current_thread = -1;
	var $thread_count;
	var $threads;
	var $thread;
	
	var $in_the_loop;
	var $user_id;
	var $box;

	function bp_messages_template($box, $user_id) {
		$this->threads = BP_Messages_Thread::get_threads_for_user( $box, $user_id, false, $user_id );
		$this->thread_count = count($this->threads);
		$this->user_id = $user_id;
		$this->box = $box;
	}
	
	function has_threads() {
		if ( $this->thread_count )
			return true;
		
		return false;
	}
	
	function next_thread() {
		$this->current_thread++;
		$this->thread = $this->threads[$this->current_thread];
		
		return $this->thread;
	}
	
	function rewind_threads() {
		$this->current_thread = -1;
		if ( $this->thread_count > 0 ) {
			$this->thread = $this->threads[0];
		}
	}
	
	function message_threads() { 
		if ( $this->current_thread + 1 < $this->thread_count ) {
			return true;
		} elseif ( $this->current_thread + 1 == $this->thread_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_threads();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_message_thread() {
		global $thread;

		$this->in_the_loop = true;
		$thread = $this->next_thread();

		if ( $this->current_thread == 0 ) // loop has just started
			do_action('loop_start');
	}
}

function bp_has_message_threads() {
	global $messages_template;
	return $messages_template->has_threads();
}

function bp_message_threads() { 
	global $messages_template;
	return $messages_template->message_threads();
}

function bp_message_thread() {
	global $messages_template;
	return $messages_template->the_message_thread();
}

function bp_message_thread_subject() {
	global $messages_template;
	echo $messages_template->thread->messages[0]->subject;
}

function bp_message_thread_excerpt() {
	global $messages_template;
	echo bp_create_excerpt($messages_template->thread->message, 20);
}

function bp_message_thread_from() {
	global $messages_template;
	echo bp_core_get_userlink($messages_template->thread->creator_id);
}

function bp_message_thread_to() {
	global $messages_template;
	echo $messages_template->$thread->recipients;
}

function bp_message_thread_view_link() {
	global $messages_template;
	global $loggedin_domain, $bp_messages_slug;
	echo $loggedin_domain . $bp_messages_slug . '/view/' . $messages_template->thread->thread_id;
}

function bp_message_thread_delete_link() {
	global $messages_template;
	global $loggedin_domain, $bp_messages_slug;
	echo $loggedin_domain . $bp_messages_slug . '/delete/' . $messages_template->thread->thread_id;
}

function bp_message_thread_has_unread() {
	global $messages_template;
	
	if ( $messages_template->thread->unread_count )
		return true;
	
	return false;
}

function bp_message_thread_unread_count() {
	global $messages_template;
	echo $messages_template->thread->unread_count;
}

function bp_message_thread_last_post_date() {
	global $messages_template;
	echo bp_format_time($messages_template->thread->last_post_date);
}

function bp_message_thread_avatar() {
	global $messages_template;
	if ( function_exists('xprofile_get_avatar') )
		echo xprofile_get_avatar($messages_template->thread->creator_id, 1);
}

function bp_message_thread_view() {
	global $thread_id;
	
	messages_view_thread($thread_id);
}

function bp_compose_message_form() {
	global $loggedin_domain, $bp_messages_slug;
	global $messages_write_new_action;
	
	$messages_write_new_action = $loggedin_domain . $bp_messages_slug . '/compose/';
	
	if ( isset($_POST['send_to']) ) {
		messages_send_message( $_POST['send_to'], $_POST['subject'], $_POST['content'], $_POST['thread_id'], false, true );
	} else {
		messages_write_new();
	}
}

function bp_get_callback_message() {
	global $message, $type;

	if ( $message != '' ) {
		$type = ( $type == 'error' ) ? 'error' : 'updated';
	?>
		<div id="message" class="<?php echo $type; ?> fade">
			<p><?php echo $message; ?></p>
		</div>
<?php }
}

?>