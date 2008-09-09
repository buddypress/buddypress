<?php
require_once( 'bp-core.php' );

define ( 'BP_FRIENDS_IS_INSTALLED', 1 );
define ( 'BP_FRIENDS_VERSION', '0.1.2' );

include_once( 'bp-friends/bp-friends-classes.php' );
include_once( 'bp-friends/bp-friends-ajax.php' );
include_once( 'bp-friends/bp-friends-cssjs.php' );
/*include_once( 'bp-messages/bp-friends-admin.php' );*/
include_once( 'bp-friends/bp-friends-templatetags.php' );


/**************************************************************************
 friends_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function friends_install( $version ) {
	global $wpdb, $bp;
	
	$sql[] = "CREATE TABLE ". $bp['friends']['table_name'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT,
		  		initiator_user_id int(11) NOT NULL,
		  		friend_user_id int(11) NOT NULL,
		  		is_confirmed bool DEFAULT 0,
				is_limited bool DEFAULT 0,
		  		date_created datetime NOT NULL,
		    	PRIMARY KEY id (id)
		 	   );";

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
	
	add_site_option( 'bp-friends-version', $version );
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
		'image_base' => get_option('siteurl') . '/wp-content/mu-plugins/bp-friends/images',
		'slug'		 => 'friends'
	);
}
add_action( 'wp', 'friends_setup_globals', 1 );	
add_action( '_admin_menu', 'friends_setup_globals', 1 );


/**************************************************************************
 friends_add_admin_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function friends_add_admin_menu() {	
	global $wpdb, $bp, $userdata;

	if ( $wpdb->blogid == get_usermeta( $bp['current_userid'], 'home_base' ) ) {
		/* Add the administration tab under the "Site Admin" tab for site administrators */
		//add_submenu_page( 'wpmu-admin.php', __("Friends"), __("Friends"), 1, basename(__FILE__), "friends_settings" );
	}

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( ( $wpdb->get_var("show tables like '%" . $bp['friends']['table_name'] . "%'") == false ) || ( get_site_option('bp-friends-version') < BP_FRIENDS_VERSION )  )
		friends_install(BP_FRIENDS_VERSION);
}
add_action( 'admin_menu', 'friends_add_admin_menu' );

/**************************************************************************
 friends_setup_nav()
 
 Set up front end navigation.
 **************************************************************************/

function friends_setup_nav() {
	global $bp;

	$bp['bp_nav'][3] = array(
		'id'	=> $bp['friends']['slug'],
		'name'  => __('Friends'), 
		'link'  => $bp['loggedin_domain'] . $bp['friends']['slug'] . '/'
	);
	
	$bp['bp_users_nav'][2] = array(
		'id'	=> $bp['friends']['slug'],
		'name'  => __('Friends'), 
		'link'  => $bp['current_domain'] . $bp['friends']['slug'] . '/'
	);

	$bp['bp_options_nav'][$bp['friends']['slug']] = array(
		'my-friends'    => array( 
			'name'      => __('My Friends'),
			'link'      => $bp['loggedin_domain'] . $bp['friends']['slug'] . '/my-friends' ),
		'requests'      => array(
			'name'      => __('Requests'),
			'link'      => $bp['loggedin_domain'] . $bp['friends']['slug'] . '/requests' ),
		'friend-finder' => array( 
			'name'      => __('Friend Finder'),
			'link'      => $bp['loggedin_domain'] . $bp['friends']['slug'] . '/friend-finder' ),
		'invite-friend' => array( 
			'name'      => __('Invite Friends'),
			'link'      => $bp['loggedin_domain'] . $bp['friends']['slug'] . '/invite-friend' )
	);		
	
	if ( $bp['current_component'] == $bp['friends']['slug'] ) {
		if ( bp_is_home() ) {
			$bp['bp_options_title'] = __('My Friends');
		} else {
			$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = bp_user_fullname( $bp['current_userid'], false ); 
		}
	}
}
add_action( 'wp', 'friends_setup_nav', 2 );


/**************************************************************************
 friends_catch_action()
 
 Catch actions via pretty urls.
 **************************************************************************/

function friends_catch_action() {
	global $bp, $thread_id, $current_blog;
	
	if ( $bp['current_component'] == $bp['friends']['slug'] && $current_blog->blog_id > 1 ) {
		
		if ( $bp['current_action'] == '' )
			$bp['current_action'] = 'my-friends';
		
		switch ( $bp['current_action'] ) {
			case 'my-friends':
				bp_catch_uri( 'friends/index' );
			break;
			
			case 'friend-finder':
				bp_catch_uri( 'friends/friend-finder' );
			break;
			
			case 'requests':
				if ( isset($bp['action_variables']) && in_array( 'accept', $bp['action_variables'] ) && is_numeric($bp['action_variables'][1]) ) {
					if ( BP_Friends_Friendship::accept( $bp['action_variables'][1] ) ) {
						$bp['message'] = __('Friendship accepted');
						$bp['message_type'] = 'success';
					} else {
						$bp['message'] = __('Friendship could not be accepted');
						$bp['message_type'] = 'error';					
					}
					add_action( 'template_notices', 'bp_core_render_notice' );
				} else if ( isset($bp['action_variables']) && in_array( 'reject', $bp['action_variables'] ) && is_numeric($bp['action_variables'][1]) ) {
					if ( BP_Friends_Friendship::reject( $bp['action_variables'][1] ) ) {
						$bp['message'] = __('Friendship rejected');
						$bp['message_type'] = 'success';
					} else {
						$bp['message'] = __('Friendship could not be rejected');
						$bp['message_type'] = 'error';				
					}
					add_action( 'template_notices', 'bp_core_render_notice' );
				}
				bp_catch_uri( 'friends/requests' );
			break; 
			
			default:
				$bp['current_action'] = 'my-friends';
				bp_catch_uri( 'friends/index' );				
			break;
		}
	}
}
add_action( 'wp', 'friends_catch_action', 3 );


/**************************************************************************
 friends_admin_setup()
 
 Setup CSS, JS and other things needed for the xprofile component.
**************************************************************************/

function friends_admin_setup() {
}
add_action( 'admin_menu', 'friends_admin_setup' );


/**************************************************************************
 friends_get_friends()
 
 Return an array of friend objects for the current user.
**************************************************************************/

function friends_get_friendships( $user_id = false, $friendship_ids = false, $pag_num = 5, $pag_page = 1, $get_requests = false, $count = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp['current_userid'];
	
	if ( !$friendship_ids )
		$friendship_ids = BP_Friends_Friendship::get_friendship_ids( $user_id, false, $pag_num, $pag_page, $get_requests );

	if ( $friendship_ids[0]->id == 0 )
		return false;

	for ( $i = 0; $i < count($friendship_ids); $i++ ) {
		$friends[] = new BP_Friends_Friendship( $friendship_ids[$i]->id, $get_requests );
	}
	
	if ( !$count )
		$count = BP_Friends_Friendship::total_friend_count($user_id);
		
	return array( 'friendships' => $friends, 'count' => $count );
}

function friends_get_friends_list( $user_id = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp['current_userid'];
	
	$friend_ids = BP_Friends_Friendship::get_friend_ids( $user_id );

	for ( $i = 0; $i < count($friend_ids); $i++ ) {
		if ( function_exists('bp_user_fullname') )
			$display_name = bp_user_fullname($friend_ids[$i], false);
		
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

/**************************************************************************
 friends_search_users()
 
 Return an array of user objects based on the users search terms
**************************************************************************/

function friends_search_users( $search_terms, $user_id, $pag_num = 5, $pag_page = 1 ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp['loggedin_userid'];

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

function friends_check_friendship( $user_id = null, $possible_friend_id = null ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp['loggedin_userid'];
	
	if ( !$possible_friend_id )
		$possible_friend_id = $bp['current_userid'];
		
	if ( BP_Friends_Friendship::check_is_friend( $user_id, $possible_friend_id ) )
		return true;
	
	return false;
}

/**************************************************************************
 friends_add_friend()
 
 Create a new friend relationship
**************************************************************************/

function friends_add_friend( $initiator_userid = null, $friend_userid = null ) {
	global $bp;
	
	if ( !$initiator_userid )
		$initiator_userid = $bp['loggedin_userid'];
	
	if ( !$friend_userid )
		$friend_userid = $bp['current_userid'];
	
	$friendship = new BP_Friends_Friendship;
	
	$friendship->initiator_user_id = $initiator_userid;
	$friendship->friend_user_id = $friend_userid;
	$friendship->is_confirmed = 0;
	$friendship->is_limited = 0;
	$friendship->date_created = time();

	return $friendship->save();
}

/**************************************************************************
 friends_remove_friend()
 
 Remove a friend relationship
**************************************************************************/

function friends_remove_friend( $initiator_userid = null, $friend_userid = null, $only_confirmed = false ) {
	global $bp;

	if ( !$initiator_userid )
		$initiator_userid = $bp['loggedin_userid'];
	
	if ( !$friend_userid )
		$friend_userid = $bp['current_userid'];
		
	$friendship_id = BP_Friends_Friendship::get_friendship_ids( $initiator_userid, $only_confirmed, false, null, null, $friend_userid );

	$friendship = new BP_Friends_Friendship( $friendship_id[0]->id );
	
	return $friendship->delete();
}


?>