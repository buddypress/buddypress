<?php

define ( 'BP_MESSAGES_DB_VERSION', '1300' );

/* Define the slug for the component */
if ( !defined( 'BP_MESSAGES_SLUG' ) )
	define ( 'BP_MESSAGES_SLUG', 'messages' );

require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-classes.php' );
require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-cssjs.php' );
require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-templatetags.php' );
require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-filters.php' );

/* Include deprecated functions if settings allow */
if ( !defined( 'BP_IGNORE_DEPRECATED' ) )
	require ( BP_PLUGIN_DIR . '/bp-messages/deprecated/bp-messages-deprecated.php' );
	
function messages_install() {
	global $wpdb, $bp;
	
	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	$sql[] = "CREATE TABLE {$bp->messages->table_name_threads} (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		message_ids longtext NOT NULL,
				sender_ids longtext NOT NULL,
		  		first_post_date datetime NOT NULL,
		  		last_post_date datetime NOT NULL,
		  		last_message_id bigint(20) NOT NULL,
				last_sender_id bigint(20) NOT NULL,
			    KEY last_message_id (last_message_id),
			    KEY last_sender_id (last_sender_id)
		 	   ) {$charset_collate};";
	
	$sql[] = "CREATE TABLE {$bp->messages->table_name_recipients} (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		user_id bigint(20) NOT NULL,
		  		thread_id bigint(20) NOT NULL,
				sender_only tinyint(1) NOT NULL DEFAULT '0',
		  		unread_count int(10) NOT NULL DEFAULT '0',
				is_deleted tinyint(1) NOT NULL DEFAULT '0',
			    KEY user_id (user_id),
			    KEY thread_id (thread_id),
				KEY is_deleted (is_deleted),
			    KEY sender_only (sender_only),
			    KEY unread_count (unread_count)
		 	   ) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp->messages->table_name_messages} (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		sender_id bigint(20) NOT NULL,
		  		subject varchar(200) NOT NULL,
		  		message longtext NOT NULL,
		  		date_sent datetime NOT NULL,
				message_order int(10) NOT NULL,
				sender_is_group tinyint(1) NOT NULL DEFAULT '0',
			    KEY sender_id (sender_id),
			    KEY message_order (message_order),
			    KEY sender_is_group (sender_is_group)
		 	   ) {$charset_collate};";
	
	$sql[] = "CREATE TABLE {$bp->messages->table_name_notices} (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		subject varchar(200) NOT NULL,
		  		message longtext NOT NULL,
		  		date_sent datetime NOT NULL,
				is_active tinyint(1) NOT NULL DEFAULT '0',
			    KEY is_active (is_active)
		 	   ) {$charset_collate};";
	
	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	dbDelta($sql);
	
	add_site_option( 'bp-messages-db-version', BP_MESSAGES_DB_VERSION );
}

function messages_setup_globals() {
	global $bp, $wpdb;
	
	$bp->messages->table_name_threads = $wpdb->base_prefix . 'bp_messages_threads';
	$bp->messages->table_name_messages = $wpdb->base_prefix . 'bp_messages_messages';
	$bp->messages->table_name_recipients = $wpdb->base_prefix . 'bp_messages_recipients';
	$bp->messages->table_name_notices = $wpdb->base_prefix . 'bp_messages_notices';
	$bp->messages->format_activity_function = 'messages_format_activity';
	$bp->messages->format_notification_function = 'messages_format_notifications';
	$bp->messages->image_base = BP_PLUGIN_URL . '/bp-messages/images';
	$bp->messages->slug = BP_MESSAGES_SLUG;

	$bp->version_numbers->messages = BP_MESSAGES_VERSION;
}
add_action( 'plugins_loaded', 'messages_setup_globals', 5 );	
add_action( 'admin_menu', 'messages_setup_globals', 1 );

function messages_check_installed() {	
	global $wpdb, $bp;

	if ( !is_site_admin() )
		return false;
	
	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( get_site_option('bp-messages-db-version') < BP_MESSAGES_DB_VERSION )
		messages_install();
}
add_action( 'admin_menu', 'messages_check_installed', 1 );

function messages_setup_nav() {
	global $bp;

	if ( $bp->current_component == $bp->messages->slug ) {
		$inbox_count = messages_get_unread_count();
		$inbox_display = ( $inbox_count ) ? ' style="display:inline;"' : ' style="display:none;"';
		$count_indicator = '&nbsp; <span' . $inbox_display . ' class="unread-count inbox-count">' . $inbox_count . '</span>';
	}

	/* Add 'Messages' to the main navigation */
	bp_core_new_nav_item( array( 'name' => __('Messages', 'buddypress'), 'slug' => $bp->messages->slug, 'position' => 50, 'show_for_displayed_user' => false, 'screen_function' => 'messages_screen_inbox', 'default_subnav_slug' => 'inbox'  ) );
	
	$messages_link = $bp->loggedin_user->domain . $bp->messages->slug . '/';
	
	/* Add the subnav items to the profile */
	bp_core_new_subnav_item( array( 'name' => __( 'Inbox', 'buddypress' ) . $count_indicator, 'slug' => 'inbox', 'parent_url' => $messages_link, 'parent_slug' => $bp->messages->slug, 'screen_function' => 'messages_screen_inbox', 'position' => 10, 'user_has_access' => bp_is_home() ) );
	bp_core_new_subnav_item( array( 'name' => __( 'Sent Messages', 'buddypress' ), 'slug' => 'sentbox', 'parent_url' => $messages_link, 'parent_slug' => $bp->messages->slug, 'screen_function' => 'messages_screen_sentbox', 'position' => 20, 'user_has_access' => bp_is_home() ) );
	bp_core_new_subnav_item( array( 'name' => __( 'Compose', 'buddypress' ), 'slug' => 'compose', 'parent_url' => $messages_link, 'parent_slug' => $bp->messages->slug, 'screen_function' => 'messages_screen_compose', 'position' => 30, 'user_has_access' => bp_is_home() ) );
	
	if ( is_site_admin() )
		bp_core_new_subnav_item( array( 'name' => __( 'Notices', 'buddypress' ), 'slug' => 'notices', 'parent_url' => $messages_link, 'parent_slug' => $bp->messages->slug, 'screen_function' => 'messages_screen_notices', 'position' => 90, 'user_has_access' => is_site_admin() ) );

	if ( $bp->current_component == $bp->messages->slug ) {
		if ( bp_is_home() ) {
			$bp->bp_options_title = __( 'My Messages', 'buddypress' );			
		} else {
			$bp_options_avatar =  bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb' ) );
			$bp->bp_options_title = $bp->displayed_user->fullname;
		}
	}
	
	do_action( 'messages_setup_nav' );
}
add_action( 'wp', 'messages_setup_nav', 2 );
add_action( 'admin_menu', 'messages_setup_nav', 2 );


/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

function messages_screen_inbox() {
	do_action( 'messages_screen_inbox' );
	bp_core_load_template( apply_filters( 'messages_template_inbox', 'messages/index' ) );	
}

function messages_screen_sentbox() {
	do_action( 'messages_screen_sentbox' );
	bp_core_load_template( apply_filters( 'messages_template_sentbox', 'messages/sentbox' ) );
}

function messages_screen_compose() {
	// Remove any saved message data from a previous session.
	messages_remove_callback_values();

	// Require the auto 
	$recipients = false;
	if ( empty( $_POST['send_to_usernames'] ) ) {
		if ( !empty( $_POST['send-to-input'] ) ) {
			// Replace commas with places
			$recipients = str_replace( ',', ' ', $_POST['send-to-input'] );
			$recipients = str_replace( '  ', ' ', $recipients );
		}
	} else {
		$recipients = $_POST['send_to_usernames'];
	}
	
	if ( $recipients || ( isset($_POST['send-notice']) && is_site_admin() ) ) {
		if ( !check_admin_referer( 'messages_send_message' ) )
			return false;
			
		messages_send_message( $recipients, $_POST['subject'], $_POST['content'], $_POST['thread_id'], false, true );
	}
	
	do_action( 'messages_screen_compose' );
	
	bp_core_load_template( apply_filters( 'messages_template_compose', 'messages/compose' ) );
}

function messages_screen_notices() {
	global $bp, $notice_id;
		
	if ( !is_site_admin() )
		return false;
		
	$notice_id = $bp->action_variables[1];

	if ( $notice_id && is_numeric($notice_id) ) {
		$notice = new BP_Messages_Notice($notice_id);

		if ( 'deactivate' == $bp->action_variables[0] ) {
			if ( !$notice->deactivate() ) {
				bp_core_add_message( __('There was a problem deactivating that notice.', 'buddypress'), 'error' );	
			} else {
				bp_core_add_message( __('Notice deactivated.', 'buddypress') );
			}
		} else if ( 'activate' == $bp->action_variables[0] ) {
			if ( !$notice->activate() ) {
				bp_core_add_message( __('There was a problem activating that notice.', 'buddypress'), 'error' );
			} else {
				bp_core_add_message( __('Notice activated.', 'buddypress') );
			}
		} else if ( 'delete' == $bp->action_variables[0] ) {
			if ( !$notice->delete() ) {
				bp_core_add_message( __('There was a problem deleting that notice.', 'buddypress'), 'buddypress' );
			} else {
				bp_core_add_message( __('Notice deleted.', 'buddypress') );
			}
		}
		bp_core_redirect( $bp->loggedin_user->domain . $bp->messages->slug . '/notices' );
	}
	
	do_action( 'messages_screen_notices' );
	
	bp_core_load_template( apply_filters( 'messages_template_notices', 'messages/notices' ) );	
}

function messages_screen_notification_settings() { 
	global $current_user; ?>
	<table class="notification-settings" id="messages-notification-settings">
		<tr>
			<th class="icon"></th>
			<th class="title"><?php _e( 'Messages', 'buddypress' ) ?></th>
			<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
			<th class="no"><?php _e( 'No', 'buddypress' )?></th>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'A member sends you a new message', 'buddypress' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_messages_new_message]" value="yes" <?php if ( !get_usermeta( $current_user->id, 'notification_messages_new_message' ) || 'yes' == get_usermeta( $current_user->id, 'notification_messages_new_message' ) ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_messages_new_message]" value="no" <?php if ( 'no' == get_usermeta( $current_user->id, 'notification_messages_new_message' ) ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'A new site notice is posted', 'buddypress' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_messages_new_notice]" value="yes" <?php if ( !get_usermeta( $current_user->id, 'notification_messages_new_notice' ) || 'yes' == get_usermeta( $current_user->id, 'notification_messages_new_notice' ) ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_messages_new_notice]" value="no" <?php if ( 'no' == get_usermeta( $current_user->id, 'notification_messages_new_notice' ) ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		
		<?php do_action( 'messages_screen_notification_settings' ) ?>
	</table>
<?php	
}
add_action( 'bp_notification_settings', 'messages_screen_notification_settings', 2 );


/********************************************************************************
 * Action Functions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 */

function messages_action_view_message() {
	global $bp, $thread_id;
	
	if ( $bp->current_component != $bp->messages->slug || $bp->current_action != 'view' )
		return false;
		
	$thread_id = $bp->action_variables[0];

	if ( !$thread_id || !is_numeric($thread_id) || !BP_Messages_Thread::check_access($thread_id) ) {
		bp_core_redirect( $bp->displayed_user->domain . $bp->current_component );
	} else {

		bp_core_new_subnav_item( array( 'name' => sprintf( __( 'From: %s', 'buddypress'), BP_Messages_Thread::get_last_sender($thread_id) ), 'slug' => 'view', 'parent_url' => $bp->loggedin_user->domain . $bp->messages->slug . '/', 'parent_slug' => $bp->messages->slug, 'screen_function' => true, 'position' => 40, 'user_has_access' => bp_is_home() ) );

		bp_core_load_template( apply_filters( 'messages_template_view_message', 'messages/view' ) );
	}
}
add_action( 'wp', 'messages_action_view_message', 3 );

function messages_action_delete_message() {
	global $bp, $thread_id;
	
	if ( $bp->current_component != $bp->messages->slug || $bp->action_variables[0] != 'delete' )
		return false;
	
	$thread_id = $bp->action_variables[1];

	if ( !$thread_id || !is_numeric($thread_id) || !BP_Messages_Thread::check_access($thread_id) ) {
		bp_core_redirect( $bp->displayed_user->domain . $bp->current_component . '/' . $bp->current_action );
	} else {
		if ( !check_admin_referer( 'messages_delete_thread' ) )
			return false;

		// delete message
		if ( !messages_delete_thread($thread_id) ) {
			bp_core_add_message( __('There was an error deleting that message.', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('Message deleted.', 'buddypress') );
		}
		bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component . '/' . $bp->current_action );
	}
}
add_action( 'wp', 'messages_action_delete_message', 3 );

function messages_action_bulk_delete() {
	global $bp, $thread_ids;
	
	if ( $bp->current_component != $bp->messages->slug || $bp->action_variables[0] != 'bulk-delete' )
		return false;
	
	$thread_ids = $_POST['thread_ids'];

	if ( !$thread_ids || !BP_Messages_Thread::check_access($thread_ids) ) {
		bp_core_redirect( $bp->displayed_user->domain . $bp->current_component . '/' . $bp->current_action );			
	} else {
		if ( !check_admin_referer( 'messages_delete_thread' ) )
			return false;
			
		if ( !messages_delete_thread( $thread_ids ) ) {
			bp_core_add_message( __('There was an error deleting messages.', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('Messages deleted.', 'buddypress') );
		}
		bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component . '/' . $bp->current_action );
	}
}
add_action( 'wp', 'messages_action_bulk_delete', 3 );


/********************************************************************************
 * Activity & Notification Functions
 *
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 */

function messages_record_activity( $args = true ) {
	if ( function_exists('bp_activity_record') ) {
		extract($args);
		bp_activity_record( $item_id, $component_name, $component_action, $is_private );
	} 
}

function messages_delete_activity( $args = true ) {
	if ( function_exists('bp_activity_delete') ) {
		extract($args);
		bp_activity_delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id );
	}
}

function messages_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;
	
	if ( 'new_message' == $action ) {
		if ( (int)$total_items > 1 )
			return apply_filters( 'bp_messages_multiple_new_message_notification', '<a href="' . $bp->loggedin_user->domain . $bp->messages->slug . '/inbox" title="Inbox">' . sprintf( __('You have %d new messages', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );		
		else
			return apply_filters( 'bp_messages_single_new_message_notification', '<a href="' . $bp->loggedin_user->domain . $bp->messages->slug . '/inbox" title="Inbox">' . sprintf( __('You have %d new message', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );
	}
	
	do_action( 'messages_format_notifications', $action, $item_id, $secondary_item_id, $total_items );
	
	return false;
}


/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

function messages_send_message( $recipients, $subject, $content, $thread_id, $from_ajax = false, $from_template = false, $is_reply = false ) {
	global $pmessage;
	global $message, $type;
	global $bp, $current_user;
		
	messages_add_callback_values( $recipients, $subject, $content );
	
	if ( isset( $_POST['send-notice'] ) ) {
		if ( messages_send_notice( $subject, $content, $from_template ) ) {
			bp_core_add_message( __('Notice posted successfully.', 'buddypress') );
		} else {
			bp_core_add_message( __('There was an error posting that notice.', 'buddypress'), 'error' );			
		}
		bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component . '/notices' );
		return true;
	}
	
	$recipients = explode( ' ', $recipients );
	
	// If there are no recipients
	if ( count( $recipients ) < 1 ) {
		if ( !$from_ajax ) {	
			bp_core_add_message( __('Please enter at least one valid user to send this message to.', 'buddypress'), 'error' );
			bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component . '/compose' );
		} else {
			return array('status' => 0, 'message' => __('There was an error sending the reply, please try again.', 'buddypress'));
		}
		
	// If there is only 1 recipient and it is the logged in user.
	} else if ( 1 == count( $recipients ) && $recipients[0] == $current_user->user_login ) {
		bp_core_add_message( __('You must send your message to one or more users not including yourself.', 'buddypress'), 'error' );
		bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component . '/compose' );	
	
	// If the subject or content boxes are empty.
	} else if ( empty( $subject ) || empty( $content ) ) {
		if ( !$from_ajax ) {
			bp_core_add_message( __('Please make sure you fill in all the fields.', 'buddypress'), 'error' );
			bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component . '/compose' );
		} else {
			return array('status' => 0, 'message' => __('Please make sure you have typed a message before sending a reply.', 'buddypress'));
		}
		
	// Passed validation continue.
	} else {

		// Strip the logged in user from the recipient list if they exist
		if ( $key = array_search( $current_user->user_login, $recipients ) )
			unset( $recipients[$key] );
		
		$pmessage = new BP_Messages_Message;

		$pmessage->sender_id = $bp->loggedin_user->id;
		$pmessage->subject = $subject;
		$pmessage->message = $content;
		$pmessage->thread_id = $thread_id;
		$pmessage->date_sent = time();
		$pmessage->message_order = 0; // TODO
		$pmessage->sender_is_group = 0;
		
		if ( $is_reply ) {
			$thread = new BP_Messages_Thread($thread_id);
			$pmessage->recipients = $thread->get_recipients();
		} else {
			$pmessage->recipients = BP_Messages_Message::get_recipient_ids( $recipients );
		}

		if ( !is_null( $pmessage->recipients ) ) {
			if ( !$pmessage->send() ) {
				$message = __('Message could not be sent, please try again.', 'buddypress');
				$type = 'error';
		
				if ( $from_ajax ) {
					return array('status' => 0, 'message' => $message);
				} else {
					bp_core_add_message( $message, $type );
					bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component . '/compose' );
				} 
			} else {
				$message = __('Message sent successfully!', 'buddypress');
				$type = 'success';
				
				// Send screen notifications to the recipients
				for ( $i = 0; $i < count($pmessage->recipients); $i++ ) {
					if ( $pmessage->recipients[$i] != $bp->loggedin_user->id ) {
						bp_core_add_notification( $pmessage->id, $pmessage->recipients[$i], 'messages', 'new_message' );	
					}
				}
				
				// Send email notifications to the recipients
				require_once( BP_PLUGIN_DIR . '/bp-messages/bp-messages-notifications.php' );
				messages_notification_new_message( array( 'item_id' => $pmessage->id, 'recipient_ids' => $pmessage->recipients, 'thread_id' => $pmessage->thread_id, 'component_name' => $bp->messages->slug, 'component_action' => 'message_sent', 'is_private' => 1 ) );

				do_action( 'messages_send_message', array( 'item_id' => $pmessage->id, 'recipient_ids' => $pmessage->recipients, 'thread_id' => $pmessage->thread_id, 'component_name' => $bp->messages->slug, 'component_action' => 'message_sent', 'is_private' => 1 ) );
		
				if ( $from_ajax ) {
					return array('status' => 1, 'message' => $message, 'reply' => $pmessage);
				} else {
					bp_core_add_message( $message );
					bp_core_redirect( $bp->loggedin_user->domain . $bp->messages->slug . '/view/' . $pmessage->thread_id );
				}
			}
		} else {
			$message = __('Message could not be sent, please try again.', 'buddypress');
			$type = 'error';
		
			if ( $from_ajax ) {
				return array('status' => 0, 'message' => $message);
			} else {
				bp_core_add_message( $message, $type );
				bp_core_redirect( $bp->loggedin_user->domain . $bp->messages->slug . '/compose' );
			}
		}
	}

}

function messages_add_callback_values( $recipients, $subject, $content ) {
	setcookie( 'bp_messages_send_to', $recipients, time()+60*60*24, COOKIEPATH );
	setcookie( 'bp_messages_subject', $subject, time()+60*60*24, COOKIEPATH );
	setcookie( 'bp_messages_content', $content, time()+60*60*24, COOKIEPATH );
}

function messages_remove_callback_values() {
	setcookie( 'bp_messages_send_to', false, time()-1000, COOKIEPATH );
	setcookie( 'bp_messages_subject', false, time()-1000, COOKIEPATH );
	setcookie( 'bp_messages_content', false, time()-1000, COOKIEPATH );
}

function messages_send_notice( $subject, $message, $from_template ) {
	if ( !is_site_admin() || empty( $subject ) || empty( $message ) ) {
		return false;
	} else {
		// Has access to send notices, lets do it.
		$notice = new BP_Messages_Notice;
		$notice->subject = $subject;
		$notice->message = $message;
		$notice->date_sent = time();
		$notice->is_active = 1;
		$notice->save(); // send it.
		
		do_action( 'messages_send_notice', $subject, $message );
		
		return true;
	}
}

function messages_delete_thread( $thread_ids ) {
	if ( is_array($thread_ids) ) {
		$error = 0;
		for ( $i = 0; $i < count($thread_ids); $i++ ) {
			if ( !$status = BP_Messages_Thread::delete($thread_ids[$i]) )
				$error = 1;
		}
		
		if ( $error )
			return false;
		
		do_action( 'messages_delete_thread', $thread_ids );
		
		return true;
	} else {
		if ( !BP_Messages_Thread::delete($thread_ids) )
			return false;
		
		do_action( 'messages_delete_thread', $thread_ids );
		
		return true;
	}
}

function messages_get_unread_count( $user_id = false ) {
	global $bp;
	
	if ( !$user_id ) 
		$user_id = $bp->loggedin_user->id;
		
	return BP_Messages_Thread::get_inbox_count( $user_id );
}

function messages_is_user_sender( $user_id, $message_id ) {
	return BP_Messages_Message::is_user_sender( $user_id, $message_id );
}

function messages_get_message_sender( $message_id ) {
	return BP_Messages_Message::get_message_sender( $message_id );
}

function messages_ajax_autocomplete_results() {
	global $bp;
	
	$friends = false;

	// Get the friend ids based on the search terms
	if ( function_exists( 'friends_search_friends' ) )
		$friends = friends_search_friends( $_GET['q'], $bp->loggedin_user->id, $_GET['limit'], 1 );
	
	$friends = apply_filters( 'bp_friends_autocomplete_list', $friends, $_GET['q'], $_GET['limit'] );

	if ( $friends['friends'] ) {
		foreach ( $friends['friends'] as $user_id ) {
			$ud = get_userdata($user_id);
			$username = $ud->user_login;
			echo  bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'width' => 15, 'height' => 15 ) )  . ' ' . bp_core_get_user_displayname( $user_id ) . ' (' . $username . ')
			';
		}		
	}
}
add_action( 'wp_ajax_messages_autocomplete_results', 'messages_ajax_autocomplete_results' );


// List actions to clear super cached pages on, if super cache is installed
add_action( 'messages_delete_thread', 'bp_core_clear_cache' );
add_action( 'messages_send_notice', 'bp_core_clear_cache' );
add_action( 'messages_message_sent', 'bp_core_clear_cache' );

// Don't cache message inbox/sentbox/compose as it's too problematic
add_action( 'messages_screen_compose', 'bp_core_clear_cache' );
add_action( 'messages_screen_sentbox', 'bp_core_clear_cache' );
add_action( 'messages_screen_inbox', 'bp_core_clear_cache' );

?>