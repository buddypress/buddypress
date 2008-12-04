<?php
require_once( 'bp-core.php' );

define ( 'BP_FRIENDS_IS_INSTALLED', 1 );
define ( 'BP_FRIENDS_VERSION', '0.2' );

include_once( 'bp-friends/bp-friends-classes.php' );
include_once( 'bp-friends/bp-friends-ajax.php' );
include_once( 'bp-friends/bp-friends-cssjs.php' );
include_once( 'bp-friends/bp-friends-templatetags.php' );
include_once( 'bp-friends/bp-friends-widgets.php' );
include_once( 'bp-friends/bp-friends-notifications.php' );
/*include_once( 'bp-messages/bp-friends-admin.php' );*/


/**************************************************************************
 friends_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function friends_install() {
	global $wpdb, $bp;
	
	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		
	$sql[] = "CREATE TABLE ". $bp['friends']['table_name'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		initiator_user_id int(11) NOT NULL,
		  		friend_user_id int(11) NOT NULL,
		  		is_confirmed bool DEFAULT 0,
				is_limited bool DEFAULT 0,
		  		date_created datetime NOT NULL,
			    KEY initiator_user_id (initiator_user_id),
			    KEY friend_user_id (friend_user_id)
		 	   ) {$charset_collate};";

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
	
	// dbDelta won't change character sets, so we need to do this seperately.
	// This will only be in here pre v1.0
	$wpdb->query( $wpdb->prepare( "ALTER TABLE " . $bp['friends']['table_name'] . " DEFAULT CHARACTER SET %s", $wpdb->charset ) );
	
	add_site_option( 'bp-friends-version', BP_FRIENDS_VERSION );
}
	
	
/**************************************************************************
 friends_setup_globals()
 
 Set up and add all global variables for this component, and add them to 
 the $bp global variable array.
 **************************************************************************/

function friends_setup_globals() {
	global $bp, $wpdb;
	
	$bp['friends'] = array(
		'table_name' => $wpdb->base_prefix . 'bp_friends',
		'image_base' => site_url() . '/wp-content/mu-plugins/bp-friends/images',
		'format_activity_function' => 'friends_format_activity',
		'slug'		 => 'friends'
	);
}
add_action( 'wp', 'friends_setup_globals', 1 );	
add_action( '_admin_menu', 'friends_setup_globals', 1 );

function friends_check_installed() {	
	global $wpdb, $bp;

	if ( is_site_admin() ) {
		/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
		if ( ( $wpdb->get_var("show tables like '%" . $bp['friends']['table_name'] . "%'") == false ) || ( get_site_option('bp-friends-version') < BP_FRIENDS_VERSION )  )
			friends_install();
	}
}
add_action( 'admin_menu', 'friends_check_installed' );

/**************************************************************************
 friends_setup_nav()
 
 Set up front end navigation.
 **************************************************************************/

function friends_setup_nav() {
	global $bp;
	
	/* Add 'Friends' to the main navigation */
	bp_core_add_nav_item( __('Friends', 'buddypress'), $bp['friends']['slug'] );
	bp_core_add_nav_default( $bp['friends']['slug'], 'friends_screen_my_friends', 'my-friends' );
	
	$friends_link = $bp['loggedin_domain'] . $bp['friends']['slug'] . '/';
	
	/* Add the subnav items to the friends nav item */
	bp_core_add_subnav_item( $bp['friends']['slug'], 'my-friends', __('My Friends', 'buddypress'), $friends_link, 'friends_screen_my_friends' );
	bp_core_add_subnav_item( $bp['friends']['slug'], 'requests', __('Requests', 'buddypress'), $friends_link, 'friends_screen_requests', false, bp_is_home() );
	//bp_core_add_subnav_item( $bp['friends']['slug'], 'invite-friend', __('Invite Friends', 'buddypress'), $friends_link, 'friends_screen_invite_friends' );
	
	if ( $bp['current_component'] == $bp['friends']['slug'] ) {
		if ( bp_is_home() ) {
			$bp['bp_options_title'] = __('My Friends', 'buddypress');
		} else {
			$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = $bp['current_fullname']; 
		}
	}
}
add_action( 'wp', 'friends_setup_nav', 2 );

/***** Screens **********/

function friends_screen_my_friends() {
	global $bp;

	// Delete any friendship acceptance notifications for the user when viewing a profile
	bp_core_delete_notifications_for_user_by_type( $bp['loggedin_userid'], 'friends', 'friendship_accepted' );

	bp_catch_uri( 'friends/index' );	
}

function friends_screen_requests() {
	global $bp;
			
	if ( isset($bp['action_variables']) && $bp['action_variables'][0] == 'accept' && is_numeric($bp['action_variables'][1]) ) {
		
		if ( friends_accept_friendship( $bp['action_variables'][1] ) ) {
			bp_core_add_message( __('Friendship accepted', 'buddypress') );
		} else {
			bp_core_add_message( __('Friendship could not be accepted', 'buddypress'), 'error' );
		}
		bp_core_redirect( $bp['loggedin_domain'] . $bp['current_component'] . '/' . $bp['current_action'] );
		
	} else if ( isset($bp['action_variables']) && $bp['action_variables'][0] == 'reject' && is_numeric($bp['action_variables'][1]) ) {
		
		if ( friends_reject_friendship( $bp['action_variables'][1] ) ) {
			bp_core_add_message( __('Friendship rejected', 'buddypress') );
		} else {
			bp_core_add_message( __('Friendship could not be rejected', 'buddypress'), 'error' );
		}	
		bp_core_redirect( $bp['loggedin_domain'] . $bp['current_component'] . '/' . $bp['current_action'] );
	}
	
	bp_catch_uri( 'friends/requests' );
}

function friends_screen_friend_finder() {
	bp_catch_uri( 'friends/friend-finder' );
}

function friends_screen_invite_friends() {
	global $bp;
	$bp['current_action'] = 'my-friends';
	
	// Not implemented yet.
	bp_catch_uri( 'friends/index' );	
}

function friends_screen_notification_settings() { 
	global $current_user; ?>
	<table class="notification-settings" id="friends-notification-settings">
		<tr>
			<th class="icon"></th>
			<th class="title"><?php _e( 'Friends', 'buddypress' ) ?></th>
			<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
			<th class="no"><?php _e( 'No', 'buddypress' )?></th>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'A member sends you a friendship request', 'buddypress' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_friends_friendship_request]" value="yes" <?php if ( !get_usermeta( $current_user->id,'notification_friends_friendship_request') || get_usermeta( $current_user->id,'notification_friends_friendship_request') == 'yes' ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_friends_friendship_request]" value="no" <?php if ( get_usermeta( $current_user->id,'notification_friends_friendship_request') == 'no' ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'A member accepts your friendship request', 'buddypress' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_friends_friendship_accepted]" value="yes" <?php if ( !get_usermeta( $current_user->id,'notification_friends_friendship_accepted') || get_usermeta( $current_user->id,'notification_friends_friendship_accepted') == 'yes' ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_friends_friendship_accepted]" value="no" <?php if ( get_usermeta( $current_user->id,'notification_friends_friendship_accepted') == 'no' ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
	</table>
<?php	
}
add_action( 'bp_notification_settings', 'friends_screen_notification_settings' );


/**************************************************************************
 friends_record_activity()
 
 Records activity for the logged in user within the friends component so that
 it will show in the users activity stream (if installed)
 **************************************************************************/

function friends_record_activity( $args = true ) {
	if ( function_exists('bp_activity_record') ) {
		extract($args);
		bp_activity_record( $item_id, $component_name, $component_action, $is_private, $dual_record );
	} 
}
add_action( 'bp_friends_friendship_accepted', 'friends_record_activity' );


/**************************************************************************
 friends_format_activity()
 
 Selects and formats recorded friends component activity.
 Example: Selects the friend details for an added connection, then
          formats it to read "Andy Peatling & John Smith are now friends"
 **************************************************************************/

function friends_format_activity( $friendship_id, $user_id, $action, $for_secondary_user = false ) {
	global $bp;
	
	switch( $action ) {
		case 'friendship_accepted':
			$friendship = new BP_Friends_Friendship( $friendship_id, false, false );

			if ( !$friendship->initiator_user_id || !$friendship->friend_user_id )
				return false;
			
			if ( $for_secondary_user ) {
				return array( 
					'primary_link' => bp_core_get_userlink( $friendship->friend_user_id, false, true ),
					'content' => sprintf( __( '%s and %s are now friends', 'buddypress' ), bp_core_get_userlink( $friendship->initiator_user_id ), bp_core_get_userlink($friendship->friend_user_id, false, false, true) ) . ' <span class="time-since">%s</span>'
				);				
			} else {
				return array( 
					'primary_link' => bp_core_get_userlink( $friendship->friend_user_id, false, true ),
					'content' => sprintf( __( '%s and %s are now friends', 'buddypress' ), bp_core_get_userlink( $friendship->friend_user_id ), bp_core_get_userlink($friendship->initiator_user_id) ) . ' <span class="time-since">%s</span>'
				);				
			}
			
		break;
	}
	
	return false;
}

function friends_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;
	
	switch ( $action ) {
		case 'friendship_accepted':
			if ( (int)$total_items > 1 ) {
				return '<a href="' . $bp['loggedin_domain'] . $bp['friends']['slug'] . '" title="' . __( 'My Friends', 'buddypress' ) . '">' . sprintf( __('%d friends accepted your friendship requests'), (int)$total_items ) . '</a>';		
			} else {
				$user_fullname = bp_core_global_user_fullname( $item_id );
				$user_url = bp_core_get_userurl( $item_id );
				return '<a href="' . $user_url . '?new" title="' . $user_fullname .'\'s profile">' . sprintf( __('%s accepted your friendship request'), $user_fullname ) . '</a>';
			}	
		break;
		
		case 'friendship_request':
			if ( (int)$total_items > 1 ) {
				return '<a href="' . $bp['loggedin_domain'] . $bp['friends']['slug'] . '/requests" title="' . __( 'Friendship requests', 'buddypress' ) . '">' . sprintf( __('You have %d pending friendship requests'), (int)$total_items ) . '</a>';		
			} else {
				$user_fullname = bp_core_global_user_fullname( $item_id );
				$user_url = bp_core_get_userurl( $item_id );
				return '<a href="' . $bp['loggedin_domain'] . $bp['friends']['slug'] . '/requests" title="' . __( 'Friendship requests', 'buddypress' ) . '">' . sprintf( __('You have a friendship request from %s'), $user_fullname ) . '</a>';
			}	
		break;
	}
	
	return false;
}

function friends_check_user_has_friends() {
	$friend_count = get_usermeta( $user_id, 'total_friend_count');
		
	if ( $friend_count == '' )
		return false;
	
	if ( !(int)$friend_count )
		return false;
	
	return true;
}

function friends_get_friend_user_ids( $user_id, $friend_requests_only = false, $assoc_arr = false ) {
	return BP_Friends_Friendship::get_friend_user_ids( $user_id, $friend_requests_only, $assoc_arr );
}

function friends_get_friendship_ids( $user_id, $friend_requests_only = false ) {
	return BP_Friends_Friendship::get_friendship_ids( $user_id, $friend_requests_only );
}

function friends_search_friends( $search_terms, $user_id, $pag_num = 10, $pag_page = 1 ) {
	return BP_Friends_Friendship::search_friends( $search_terms, $user_id, $pag_num, $pag_page );
}

function friends_get_friendship_requests( $user_id ) {
	$fship_ids = friends_get_friendship_ids( $user_id, true );
	
	return array( 'requests' => $fship_ids, 'total' => count($requests) );
}

function friends_get_recently_active( $user_id, $pag_num = 10, $pag_page = 1 ) {
	$friend_ids = friends_get_friend_user_ids( $user_id );
	$ids_and_activity = friends_get_bulk_last_active( implode( ',', $friend_ids ) );
	
	if ( !$ids_and_activity )
		return false;
	
	$total_friends = count( $ids_and_activity );
		
	return array( 'friends' => array_slice( $ids_and_activity, intval( ( $pag_page - 1 ) * $pag_num), intval( $pag_num ) ), 'total' => $total_friends );
}

function friends_get_alphabetically( $user_id, $pag_num = 10, $pag_page = 1 ) {
	$friend_ids = friends_get_friend_user_ids( $user_id );
	$sorted_ids = BP_Friends_Friendship::sort_by_name( implode( ',', $friend_ids ) );
	
	if ( !$sorted_ids )
		return false;
	
	$total_friends = count( $sorted_ids );
	
	return array( 'friends' => array_slice( $sorted_ids, intval( ( $pag_page - 1 ) * $pag_num), intval( $pag_num ) ), 'total' => $total_friends );
}

function friends_get_newest( $user_id, $pag_num = 10, $pag_page = 1 ) {
	$friend_ids = friends_get_friend_user_ids( $user_id, false, true );

	$total_friends = count( $sorted_ids );
	
	return array( 'friends' => array_slice( $friend_ids, intval( ( $pag_page - 1 ) * $pag_num), intval( $pag_num ) ), 'total' => $total_friends );	
}
	
function friends_get_bulk_last_active( $friend_ids ) {
	return BP_Friends_Friendship::get_bulk_last_active( $friend_ids );
}

function friends_get_friends_list( $user_id ) {
	global $bp;
	
	$friend_ids = BP_Friends_Friendship::get_friend_user_ids( $user_id );

	for ( $i = 0; $i < count($friend_ids); $i++ ) {
		if ( function_exists('bp_user_fullname') )
			$display_name = bp_fetch_user_fullname($friend_ids[$i], false);
		
		if ( $display_name != ' ' ) {
			$friends[] = array(
				'id' => $friend_ids[$i],
				'full_name' => $display_name
			);
		}
	}
	
	if ( $friends && is_array($friends) )
		usort($friends, 'friends_sort_by_name');

	if ( !$friends )
		return false;

	return $friends;
}

	function friends_sort_by_name($a, $b) {  
	    return strcasecmp($a['full_name'], $b['full_name']);
	}

function friends_get_friends_invite_list( $user_id = false, $group_id ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp['loggedin_userid'];
	
	$friend_ids = BP_Friends_Friendship::get_friend_user_ids( $user_id );

	for ( $i = 0; $i < count($friend_ids); $i++ ) {
		if ( groups_check_user_has_invite( $friend_ids[$i], $group_id ) || groups_is_user_member( $friend_ids[$i], $group_id ) )
			continue;
			
		$display_name = bp_fetch_user_fullname($friend_ids[$i], false);
		
		if ( $display_name != ' ' ) {
			$friends[] = array(
				'id' => $friend_ids[$i],
				'full_name' => $display_name
			);
		}
	}
	
	if ( $friends && is_array($friends) )
		usort($friends, 'friends_sort_by_name');

	if ( !$friends )
		return false;

	return $friends;
	
}

function friends_count_invitable_friends( $user_id, $group_id ) {
	return BP_Friends_Friendship::get_invitable_friend_count( $user_id, $group_id );
}

function friends_get_friend_count_for_user( $user_id ) {
	return BP_Friends_Friendship::total_friend_count( $user_id );
}

/**************************************************************************
 friends_search_users()
 
 Return an array of user objects based on the users search terms
**************************************************************************/

function friends_search_users( $search_terms, $user_id, $pag_num = 10, $pag_page = 1 ) {
	global $bp;

	$user_ids = BP_Friends_Friendship::search_users( $search_terms, $user_id, $pag_num, $pag_page );
	
	if ( !$user_ids )
		return false;

	for ( $i = 0; $i < count($user_ids); $i++ ) {
		$users[] = new BP_Core_User($user_ids[$i]);
	}
	
	return array( 'users' => $users, 'count' => BP_Friends_Friendship::search_users_count($search_terms) );
}

/**************************************************************************
 friends_check_friendship()
 
 Check to see if the user is already a confirmed friend with this user.
**************************************************************************/

function friends_check_friendship( $user_id, $possible_friend_id ) {
	global $bp;
		
	if ( BP_Friends_Friendship::check_is_friend( $user_id, $possible_friend_id ) == 'is_friend' )
		return true;
	
	return false;
}

/**************************************************************************
 friends_add_friend()
 
 Create a new friend relationship
**************************************************************************/

function friends_add_friend( $initiator_userid, $friend_userid ) {
	global $bp;
	
	$friendship = new BP_Friends_Friendship;
	
	if ( (int)$friendship->is_confirmed )
		return true;
		
	$friendship->initiator_user_id = $initiator_userid;
	$friendship->friend_user_id = $friend_userid;
	$friendship->is_confirmed = 0;
	$friendship->is_limited = 0;
	$friendship->date_created = time();
	
	if ( $friendship->save() ) {
		bp_core_add_notification( $friendship->initiator_user_id, $friendship->friend_user_id, 'friends', 'friendship_request' );	
		do_action( 'bp_friends_friendship_requested', $friendship->id, $friendship->initiator_user_id, $friendship->friend_user_id );	
		
		return true;
	}
	
	return false;
}

/**************************************************************************
 friends_remove_friend()
 
 Remove a friend relationship
**************************************************************************/

function friends_remove_friend( $initiator_userid, $friend_userid, $only_confirmed = false ) {
	global $bp;
		
	$friendship_id = BP_Friends_Friendship::get_friendship_ids( $initiator_userid, $only_confirmed, false, null, null, $friend_userid );
	$friendship = new BP_Friends_Friendship( $friendship_id[0]->id );
	
	do_action( 'bp_friends_friendship_deleted', $friendship_id, $initiator_userid, $friend_userid );
	
	if ( $friendship->delete() ) {
		friends_update_friend_totals( $initiator_userid, $friend_userid, 'remove' );
		return true;
	} else {
		return false;
	}
}

function friends_accept_friendship( $friendship_id ) {
	$friendship = new BP_Friends_Friendship( $friendship_id, true, false );
	
	if ( BP_Friends_Friendship::accept( $friendship_id ) ) {
		friends_update_friend_totals( $friendship->initiator_user_id, $friendship->friend_user_id );
		
		// Remove the friend request notice
		bp_core_delete_notifications_for_user_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, 'friends', 'friendship_request' );	
		
		// Add a friend accepted notice for the initiating user
		bp_core_add_notification( $friendship->friend_user_id, $friendship->initiator_user_id, 'friends', 'friendship_accepted' );
		
		do_action( 'friends_friendship_accepted', $friendship->id, $friendship->initiator_user_id, $friendship->friend_user_id );
		
		// notification action
		do_action( 'bp_friends_friendship_accepted', array( 'item_id' => $friendship_id, 'component_name' => 'friends', 'component_action' => 'friendship_accepted', 'is_private' => 0, 'dual_record' => true ) );
		
		return true;
	}
	
	return false;
}

function friends_reject_friendship( $friendship_id ) {
	$friendship = new BP_Friends_Friendship( $friendship_id, true, false );
	
	if ( BP_Friends_Friendship::reject( $friendship_id ) ) {
		// Remove the friend request notice
		bp_core_delete_notifications_for_user_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, 'friends', 'friendship_request' );	
		
		do_action( 'bp_friends_friendship_rejected', $friendship_id );
		return true;
	}
	
	return false;
}

function friends_update_friend_totals( $initiator_user_id, $friend_user_id, $status = 'add' ) {
	if ( $status == 'add' ) {
		update_usermeta( $initiator_user_id, 'total_friend_count', (int)get_usermeta( $initiator_user_id, 'total_friend_count' ) + 1 );
		update_usermeta( $friend_user_id, 'total_friend_count', (int)get_usermeta( $friend_user_id, 'total_friend_count' ) + 1 );
	} else {
		update_usermeta( $initiator_user_id, 'total_friend_count', (int)get_usermeta( $initiator_user_id, 'total_friend_count' ) - 1 );
		update_usermeta( $friend_user_id, 'total_friend_count', (int)get_usermeta( $friend_user_id, 'total_friend_count' ) - 1 );		
	}
}

function friends_remove_data( $user_id ) {
	BP_Friends_Friendship::delete_all_for_user($user_id);
	
	/* Remove usermeta */
	delete_usermeta( $user_id, 'total_friend_count' );
}
add_action( 'wpmu_delete_user', 'bp_core_remove_data', 1 );
add_action( 'delete_user', 'bp_core_remove_data', 1 );


?>