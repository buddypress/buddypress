<?php

/**************************************************************************
 messages_add_admin_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function messages_add_admin_menu() {	
	global $wpdb, $bp_messages_table_name, $bp_messages, $userdata;

	if ( $wpdb->blogid == $userdata->primary_blog ) {	
		if ( $inbox_count = BP_Messages_Thread::get_inbox_count() ) {
			$count_indicator = ' <span id="awaiting-mod" class="count-1"><span class="message-count">' . $inbox_count . '</span></span>';
		}
		
		add_menu_page    ( __('Messages'), sprintf( __('Messages%s'), $count_indicator ), 1, basename(__FILE__), "messages_inbox" );
		add_submenu_page ( basename(__FILE__), __('Messages &rsaquo; Inbox'), __('Inbox'), 1, basename(__FILE__), "messages_inbox" );	
		add_submenu_page ( basename(__FILE__), __('Messages &rsaquo; Sent Messages'), __('Sent Messages'), 1, "messages_sentbox", "messages_sentbox" );	
		add_submenu_page ( basename(__FILE__), __('Messages &rsaquo; Compose'), __('Compose'), 1, "messages_write_new", "messages_write_new" );

		// Add the administration tab under the "Site Admin" tab for site administrators
		add_submenu_page ( 'wpmu-admin.php', __('Messages'), __('Messages'), 1, basename(__FILE__), "messages_settings" );
	}
	
	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( ( $wpdb->get_var( "show tables like '%" . $bp_messages_table_name . "%'" ) == false ) || ( get_site_option('bp-messages-version') < BP_MESSAGES_VERSION ) )
		messages_install(BP_MESSAGES_VERSION);
}
add_action( 'admin_menu', 'messages_add_admin_menu' );


/**************************************************************************
 messages_setup()
 
 Setup CSS, JS and other things needed for the messaging component.
 **************************************************************************/

function messages_admin_setup() {		
	add_action( 'admin_head', 'messages_add_css' );
	add_action( 'admin_head', 'messages_add_js' );
}
add_action( 'admin_menu', 'messages_admin_setup' );


?>