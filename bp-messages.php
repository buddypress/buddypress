<?php

define ( 'BP_MESSAGES_DB_VERSION', '1800' );

/* Define the slug for the component */
if ( !defined( 'BP_MESSAGES_SLUG' ) )
	define ( 'BP_MESSAGES_SLUG', 'messages' );

require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-classes.php' );
require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-cssjs.php' );
require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-templatetags.php' );
require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-filters.php' );

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
			    KEY sender_id (sender_id)
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

	/* For internal identification */
	$bp->messages->id = 'messages';

	$bp->messages->table_name_threads = $wpdb->base_prefix . 'bp_messages_threads';
	$bp->messages->table_name_messages = $wpdb->base_prefix . 'bp_messages_messages';
	$bp->messages->table_name_recipients = $wpdb->base_prefix . 'bp_messages_recipients';
	$bp->messages->table_name_notices = $wpdb->base_prefix . 'bp_messages_notices';
	$bp->messages->format_activity_function = 'messages_format_activity';
	$bp->messages->format_notification_function = 'messages_format_notifications';
	$bp->messages->slug = BP_MESSAGES_SLUG;

	/* Register this in the active components array */
	$bp->active_components[$bp->messages->slug] = $bp->messages->id;

	do_action( 'messages_setup_globals' );
}
add_action( 'plugins_loaded', 'messages_setup_globals', 5 );
add_action( 'admin_menu', 'messages_setup_globals', 2 );

function messages_check_installed() {
	global $wpdb, $bp;

	if ( !is_site_admin() )
		return false;

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( get_site_option('bp-messages-db-version') < BP_MESSAGES_DB_VERSION )
		messages_install();
}
add_action( 'admin_menu', 'messages_check_installed' );

function messages_setup_nav() {
	global $bp;

	if ( $bp->current_component == $bp->messages->slug ) {
		$inbox_count = messages_get_unread_count();
		$inbox_display = ( $inbox_count ) ? ' style="display:inline;"' : ' style="display:none;"';
		$count_indicator = '&nbsp; <span' . $inbox_display . ' class="unread-count inbox-count">' . $inbox_count . '</span>';
	}

	/* Add 'Messages' to the main navigation */
	bp_core_new_nav_item( array( 'name' => __('Messages', 'buddypress'), 'slug' => $bp->messages->slug, 'position' => 50, 'show_for_displayed_user' => false, 'screen_function' => 'messages_screen_inbox', 'default_subnav_slug' => 'inbox', 'item_css_id' => $bp->messages->id ) );

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
add_action( 'plugins_loaded', 'messages_setup_nav' );
add_action( 'admin_menu', 'messages_setup_nav' );


/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

function messages_screen_inbox() {
	do_action( 'messages_screen_inbox' );
	bp_core_load_template( apply_filters( 'messages_template_inbox', 'members/single/home' ) );
}

function messages_screen_sentbox() {
	do_action( 'messages_screen_sentbox' );
	bp_core_load_template( apply_filters( 'messages_template_sentbox', 'members/single/home' ) );
}

function messages_screen_compose() {
	global $bp;

	// Remove any saved message data from a previous session.
	messages_remove_callback_values();

	/* Check if the message form has been submitted */
	if ( isset( $_POST['send'] ) ) {

		/* Check the nonce */
		check_admin_referer( 'messages_send_message' );

		/* Check we have what we need */
		if ( empty( $_POST['subject'] ) || empty( $_POST['content'] ) ) {
			bp_core_add_message( __( 'There was an error sending that message, please try again', 'buddypress' ), 'error' );
		} else {
			/* If this is a notice, send it */
			if ( isset($_POST['send-notice']) ) {
				messages_send_notice( $_POST['subject'], $_POST['content'] );
			} else {
				/* Filter recipients into the format we need - array( 'username/userid', 'username/userid' ) */
				$autocomplete_recipients = explode( ',', $_POST['send-to-input'] );
				$typed_recipients = explode( ' ', $_POST['send_to_usernames'] );
				$recipients = array_merge( (array) $autocomplete_recipients, (array) $typed_recipients );

				/* Send the message */
				if ( $thread_id = messages_new_message( array( 'recipients' => $recipients, 'subject' => $_POST['subject'], 'content' => $_POST['content'] ) ) ) {
					bp_core_add_message( __( 'Message sent successfully!', 'buddypress' ) );
					bp_core_redirect( $bp->loggedin_user->domain . $bp->messages->slug . '/view/' . $thread_id . '/' );
				} else {
					bp_core_add_message( __( 'There was an error sending that message, please try again', 'buddypress' ), 'error' );
				}
			}
		}

	}

	do_action( 'messages_screen_compose' );

	bp_core_load_template( apply_filters( 'messages_template_compose', 'members/single/home' ) );
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

	bp_core_load_template( apply_filters( 'messages_template_notices', 'members/single/home' ) );
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

	if ( !$thread_id || !messages_is_valid_thread( $thread_id ) || ( !messages_check_thread_access($thread_id) && !is_site_admin() ) )
		bp_core_redirect( $bp->displayed_user->domain . $bp->current_component );

	/* Check if a new reply has been submitted */
	if ( isset( $_POST['send'] ) ) {

		/* Check the nonce */
		check_admin_referer( 'messages_send_message', 'send_message_nonce' );

		/* Send the reply */
		if ( messages_new_message( array( 'thread_id' => $thread_id, 'subject' => $_POST['subject'], 'content' => $_POST['content'] ) ) )
			bp_core_add_message( __( 'Your reply was sent successfully', 'buddypress' ) );
		else
			bp_core_add_message( __( 'There was a problem sending your reply, please try again', 'buddypress' ), 'error' );

		bp_core_redirect( $bp->displayed_user->domain . $bp->current_component . '/view/' . $thread_id . '/' );
	}

	/* Mark message read */
	messages_mark_thread_read( $thread_id );

	do_action( 'messages_action_view_message' );

	bp_core_new_subnav_item( array( 'name' => sprintf( __( 'From: %s', 'buddypress'), BP_Messages_Thread::get_last_sender($thread_id) ), 'slug' => 'view', 'parent_url' => $bp->loggedin_user->domain . $bp->messages->slug . '/', 'parent_slug' => $bp->messages->slug, 'screen_function' => true, 'position' => 40, 'user_has_access' => bp_is_home() ) );
	bp_core_load_template( apply_filters( 'messages_template_view_message', 'members/single/home' ) );
}
add_action( 'wp', 'messages_action_view_message', 3 );

function messages_action_delete_message() {
	global $bp, $thread_id;

	if ( $bp->current_component != $bp->messages->slug || 'notices' == $bp->current_action || $bp->action_variables[0] != 'delete' )
		return false;

	$thread_id = $bp->action_variables[1];

	if ( !$thread_id || !is_numeric($thread_id) || !messages_check_thread_access($thread_id) ) {
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

	if ( !$thread_ids || !messages_check_thread_access($thread_ids) ) {
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

function messages_new_message( $args = '' ) {
	global $bp;

	$defaults = array(
		'thread_id' => false, // false for a new message, thread id for a reply to a thread.
		'sender_id' => $bp->loggedin_user->id,
		'recipients' => false, // Can be an array of usernames, user_ids or mixed.
		'subject' => false,
		'content' => false,
		'date_sent' => time()
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( !$sender_id || !$subject || !$content )
		return false;

	/* Create a new message object */
	$message = new BP_Messages_Message;
	$message->thread_id = $thread_id;
	$message->sender_id = $sender_id;
	$message->subject = $subject;
	$message->message = $content;
	$message->date_sent = $date_sent;

	/* If we have a thread ID, use the existing recipients, otherwise use the recipients passed */
	if ( $thread_id ) {
		$thread = new BP_Messages_Thread($thread_id);
		$message->recipients = $thread->get_recipients();
	} else {
		if ( empty( $recipients ) )
			return false;

		/* Loop the recipients and convert all usernames to user_ids where needed */
		foreach( (array) $recipients as $recipient ) {
			if ( is_numeric( trim( $recipient ) ) )
				$recipient_ids[] = (int)trim( $recipient );

			if ( $recipient_id = bp_core_get_userid( trim( $recipient ) ) )
				$recipient_ids[] = (int)$recipient_id;
		}

		/* Strip the sender from the recipient list if they exist */
		if ( $key = array_search( $sender_id, (array)$recipient_ids ) )
			unset( $recipient_ids[$key] );

		/* Remove duplicates */
		$recipient_ids = array_unique( (array)$recipient_ids );

		if ( empty( $recipient_ids ) )
			return false;

		$message->recipients = $recipient_ids;
	}

	if ( $message->send() ) {
		require_once( BP_PLUGIN_DIR . '/bp-messages/bp-messages-notifications.php' );

		// Send screen notifications to the recipients
		foreach ( (array)$message->recipients as $recipient ) {
			bp_core_add_notification( $message->id, $recipient, 'messages', 'new_message' );
		}

		// Send email notifications to the recipients
		messages_notification_new_message( array( 'item_id' => $message->id, 'recipient_ids' => $message->recipients, 'thread_id' => $message->thread_id, 'component_name' => $bp->messages->slug, 'component_action' => 'message_sent', 'is_private' => 1 ) );

		do_action( 'messages_message_sent', &$message );

		return $message->thread_id;
	}

	return false;
}


function messages_send_notice( $subject, $message ) {
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

function messages_check_thread_access( $thread_id, $user_id = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	return BP_Messages_Thread::check_access( $thread_id, $user_id );
}

function messages_mark_thread_read( $thread_id ) {
	return BP_Messages_Thread::mark_as_read( $thread_id );
}

function messages_mark_thread_unread( $thread_id ) {
	return BP_Messages_Thread::mark_as_unread( $thread_id );
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

function messages_is_valid_thread( $thread_id ) {
	return BP_Messages_Thread::is_valid( $thread_id );
}

/**
 * messages_filter_template_paths()
 *
 * Add fallback for the bp-sn-parent theme template locations used in BuddyPress versions
 * older than 1.2.
 *
 * @package BuddyPress Core
 */
function messages_filter_template_paths() {
	if ( 'bp-sn-parent' != basename( TEMPLATEPATH ) && !defined( 'BP_CLASSIC_TEMPLATE_STRUCTURE' ) )
		return false;

	add_filter( 'messages_template_compose', create_function( '', 'return "messages/compose";' ) );
	add_filter( 'messages_template_sentbox', create_function( '', 'return "messages/sentbox";' ) );
	add_filter( 'messages_template_inbox', create_function( '', 'return "messages/index";' ) );
	add_filter( 'messages_template_notices', create_function( '', 'return "messages/notices";' ) );
	add_filter( 'messages_template_view_message', create_function( '', 'return "messages/view";' ) );
}
add_action( 'init', 'messages_filter_template_paths' );


// List actions to clear super cached pages on, if super cache is installed
add_action( 'messages_delete_thread', 'bp_core_clear_cache' );
add_action( 'messages_send_notice', 'bp_core_clear_cache' );
add_action( 'messages_message_sent', 'bp_core_clear_cache' );

// Don't cache message inbox/sentbox/compose as it's too problematic
add_action( 'messages_screen_compose', 'bp_core_clear_cache' );
add_action( 'messages_screen_sentbox', 'bp_core_clear_cache' );
add_action( 'messages_screen_inbox', 'bp_core_clear_cache' );

?>