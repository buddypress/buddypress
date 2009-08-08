<?php
/***
 * Deprecated Messaging Functionality
 *
 * This file contains functions that are deprecated.
 * You should not under any circumstance use these functions as they are 
 * either no longer valid, or have been replaced with something much more awesome.
 *
 * If you are using functions in this file you should slap the back of your head
 * and then use the functions or solutions that have replaced them.
 * Most functions contain a note telling you what you should be doing or using instead.
 *
 * Of course, things will still work if you use these functions but you will
 * be the laughing stock of the BuddyPress community. We will all point and laugh at
 * you. You'll also be making things harder for yourself in the long run, 
 * and you will miss out on lovely performance and functionality improvements.
 * 
 * If you've checked you are not using any deprecated functions and finished your little
 * dance, you can add the following line to your wp-config.php file to prevent any of
 * these old functions from being loaded:
 *
 * define( 'BP_IGNORE_DEPRECATED', true );
 */
function messages_deprecated_globals() {
	global $bp;
	
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;

	$bp->groups->image_base = BP_PLUGIN_URL . '/bp-messages/deprecated/images';
}
add_action( 'plugins_loaded', 'messages_deprecated_globals', 5 );	
add_action( 'admin_menu', 'messages_deprecated_globals', 2 );

function messages_add_js() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;

	if ( $bp->current_component == $bp->messages->slug )
		wp_enqueue_script( 'bp-messages-js', BP_PLUGIN_URL . '/bp-messages/deprecated/js/general.js' );
}
add_action( 'wp', 'messages_add_js' );

function messages_add_structure_css() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;
		
	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-messages-structure', BP_PLUGIN_URL . '/bp-messages/deprecated/css/structure.css' );	
}
add_action( 'bp_styles', 'messages_add_structure_css' );

function messages_ajax_send_reply() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	check_ajax_referer( 'messages_send_message' );
	
	$result = messages_send_message($_REQUEST['send_to'], $_REQUEST['subject'], $_REQUEST['content'], $_REQUEST['thread_id'], true, false, true); 

	if ( $result['status'] ) { ?>
			<div class="avatar-box">
				<?php echo bp_core_fetch_avatar( array( 'item_id' => $result['reply']->sender_id, 'type' => 'thumb' ) ); ?>
	
				<h3><?php echo bp_core_get_userlink($result['reply']->sender_id) ?></h3>
				<small><?php echo bp_format_time($result['reply']->date_sent) ?></small>
			</div>
			<?php echo stripslashes( apply_filters( 'bp_get_message_content', $result['reply']->message ) ) ?>
			<div class="clear"></div>
		<?php
	} else {
		$result['message'] = '<img src="' . $bp->messages->image_base . '/warning.gif" alt="Warning" /> &nbsp;' . $result['message'];
		echo "-1[[split]]" . $result['message'];
	}
}
add_action( 'wp_ajax_messages_send_reply', 'messages_ajax_send_reply' );

function messages_ajax_markunread() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	if ( !isset($_POST['thread_ids']) ) {
		echo "-1[[split]]" . __('There was a problem marking messages as unread.', 'buddypress');
	} else {
		$thread_ids = explode( ',', $_POST['thread_ids'] );
		
		for ( $i = 0; $i < count($thread_ids); $i++ ) {
			BP_Messages_Thread::mark_as_unread($thread_ids[$i]);
		}
	}
}
add_action( 'wp_ajax_messages_markunread', 'messages_ajax_markunread' );

function messages_ajax_markread() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;	
		
	if ( !isset($_POST['thread_ids']) ) {
		echo "-1[[split]]" . __('There was a problem marking messages as read.', 'buddypress');
	} else {
		$thread_ids = explode( ',', $_POST['thread_ids'] );

		for ( $i = 0; $i < count($thread_ids); $i++ ) {
			BP_Messages_Thread::mark_as_read($thread_ids[$i]);
		}
	}
}
add_action( 'wp_ajax_messages_markread', 'messages_ajax_markread' );

function messages_ajax_delete() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	if ( !isset($_POST['thread_ids']) ) {
		echo "-1[[split]]" . __( 'There was a problem deleting messages.', 'buddypress' );
	} else {
		$thread_ids = explode( ',', $_POST['thread_ids'] );

		for ( $i = 0; $i < count($thread_ids); $i++ ) {
			BP_Messages_Thread::delete($thread_ids[$i]);
		}
		
		_e('Messages deleted.', 'buddypress');
	}
}
add_action( 'wp_ajax_messages_delete', 'messages_ajax_delete' );

function messages_ajax_close_notice() {
	global $userdata;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	if ( !isset($_POST['notice_id']) ) {
		echo "-1[[split]]" . __('There was a problem closing the notice.', 'buddypress');
	} else {
		$notice_ids = get_usermeta( $userdata->ID, 'closed_notices' );
	
		$notice_ids[] = (int) $_POST['notice_id'];
		
		update_usermeta( $userdata->ID, 'closed_notices', $notice_ids );
	}
}
add_action( 'wp_ajax_messages_close_notice', 'messages_ajax_close_notice' );

?>