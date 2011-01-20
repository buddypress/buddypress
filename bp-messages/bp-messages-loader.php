<?php

// Required Files
require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-cssjs.php'     );
require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-actions.php'   );
require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-screens.php'   );
require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-classes.php'   );
require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-filters.php'   );
require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-template.php'  );
require ( BP_PLUGIN_DIR . '/bp-messages/bp-messages-functions.php' );

function messages_setup_globals() {
	global $bp;

	if ( !defined( 'BP_MESSAGES_SLUG' ) )
		define ( 'BP_MESSAGES_SLUG', 'messages' );

	// For internal identification
	$bp->messages->id = 'messages';

	// Slug
	$bp->messages->slug = BP_MESSAGES_SLUG;

	// Tables
	$bp->messages->table_name_notices 		= $bp->table_prefix . 'bp_messages_notices';
	$bp->messages->table_name_messages 		= $bp->table_prefix . 'bp_messages_messages';
	$bp->messages->table_name_recipients 	= $bp->table_prefix . 'bp_messages_recipients';

	// Notifications
	$bp->messages->notification_callback = 'messages_format_notifications';

	// Register this in the active components array
	$bp->active_components[$bp->messages->slug] = $bp->messages->id;

	// Include all members in the To: autocomplete?
	$bp->messages->autocomplete_all = defined( 'BP_MESSAGES_AUTOCOMPLETE_ALL' ) ? true : false;

	do_action( 'messages_setup_globals' );
}
add_action( 'bp_setup_globals', 'messages_setup_globals' );

function messages_setup_nav() {
	global $bp;

	if ( $count = messages_get_unread_count() )
		$name = sprintf( __('Messages <strong>(%s)</strong>', 'buddypress'), $count );
	else
		$name = __('Messages <strong></strong>', 'buddypress');

	// Add 'Messages' to the main navigation
	bp_core_new_nav_item( array( 'name' => $name, 'slug' => $bp->messages->slug, 'position' => 50, 'show_for_displayed_user' => false, 'screen_function' => 'messages_screen_inbox', 'default_subnav_slug' => 'inbox', 'item_css_id' => $bp->messages->id ) );

	$messages_link = $bp->loggedin_user->domain . $bp->messages->slug . '/';

	// Add the subnav items to the profile
	bp_core_new_subnav_item( array( 'name' => __( 'Inbox', 'buddypress' ), 'slug' => 'inbox', 'parent_url' => $messages_link, 'parent_slug' => $bp->messages->slug, 'screen_function' => 'messages_screen_inbox', 'position' => 10, 'user_has_access' => bp_is_my_profile() ) );
	bp_core_new_subnav_item( array( 'name' => __( 'Sent Messages', 'buddypress' ), 'slug' => 'sentbox', 'parent_url' => $messages_link, 'parent_slug' => $bp->messages->slug, 'screen_function' => 'messages_screen_sentbox', 'position' => 20, 'user_has_access' => bp_is_my_profile() ) );
	bp_core_new_subnav_item( array( 'name' => __( 'Compose', 'buddypress' ), 'slug' => 'compose', 'parent_url' => $messages_link, 'parent_slug' => $bp->messages->slug, 'screen_function' => 'messages_screen_compose', 'position' => 30, 'user_has_access' => bp_is_my_profile() ) );

	if ( is_super_admin() )
		bp_core_new_subnav_item( array( 'name' => __( 'Notices', 'buddypress' ), 'slug' => 'notices', 'parent_url' => $messages_link, 'parent_slug' => $bp->messages->slug, 'screen_function' => 'messages_screen_notices', 'position' => 90, 'user_has_access' => is_super_admin() ) );

	if ( $bp->current_component == $bp->messages->slug ) {
		if ( bp_is_my_profile() ) {
			$bp->bp_options_title = __( 'My Messages', 'buddypress' );
		} else {
			$bp_options_avatar =  bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb' ) );
			$bp->bp_options_title = $bp->displayed_user->fullname;
		}
	}

	do_action( 'messages_setup_nav' );
}
add_action( 'bp_setup_nav', 'messages_setup_nav' );

/*******************************************************************************
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 */

function messages_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;

	if ( 'new_message' == $action ) {
		if ( (int)$total_items > 1 )
			return apply_filters( 'bp_messages_multiple_new_message_notification', '<a href="' . $bp->loggedin_user->domain . $bp->messages->slug . '/inbox" title="' . __( 'Inbox', 'buddypress' ) . '">' . sprintf( __('You have %d new messages', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );
		else
			return apply_filters( 'bp_messages_single_new_message_notification', '<a href="' . $bp->loggedin_user->domain . $bp->messages->slug . '/inbox" title="' . __( 'Inbox', 'buddypress' ) . '">' . sprintf( __('You have %d new message', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );
	}

	do_action( 'messages_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return false;
}

/*******************************************************************************
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 */

// List actions to clear super cached pages on, if super cache is installed
add_action( 'messages_delete_thread',  'bp_core_clear_cache' );
add_action( 'messages_send_notice',    'bp_core_clear_cache' );
add_action( 'messages_message_sent',   'bp_core_clear_cache' );

// Don't cache message inbox/sentbox/compose as it's too problematic
add_action( 'messages_screen_compose', 'bp_core_clear_cache' );
add_action( 'messages_screen_sentbox', 'bp_core_clear_cache' );
add_action( 'messages_screen_inbox',   'bp_core_clear_cache' );

?>