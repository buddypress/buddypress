<?php
require_once( 'bp-core.php' );

define ( 'BP_FRIENDS_IS_INSTALLED', 1 );
define ( 'BP_FRIENDS_VERSION', '0.1.1' );

$bp_friends_table_name 			= $wpdb->base_prefix . 'bp_friends';
$bp_friends_image_base 			= get_option('siteurl') . '/wp-content/mu-plugins/bp-friends/images';
$bp_friends_slug 				= 'friends';

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
	global $wpdb, $bp_friends_table_name;
	
	$sql[] = "CREATE TABLE ". $bp_friends_table_name ." (
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
 friends_add_admin_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function friends_add_admin_menu() {	
	global $wpdb, $bp_friends_table_name, $userdata;

	if ( $wpdb->blogid == $userdata->primary_blog ) {
		//add_menu_page( __("Friends"), __("Friends"), 1, basename(__FILE__), "friends_list" );
		//add_submenu_page( basename(__FILE__), __("My Friends"), __("My Friends"), 1, basename(__FILE__), "friends_list" );
		//add_submenu_page( basename(__FILE__), __("Friend Finder"), __("Friend Finder"), 1, "friend_finder", "friends_find" );	
		
		/* Add the administration tab under the "Site Admin" tab for site administrators */
		//add_submenu_page( 'wpmu-admin.php', __("Friends"), __("Friends"), 1, basename(__FILE__), "friends_settings" );
	}

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( ( $wpdb->get_var("show tables like '%" . $bp_friends_table_name . "%'") == false ) || ( get_site_option('bp-friends-version') < BP_FRIENDS_VERSION )  )
		friends_install(BP_FRIENDS_VERSION);
		
}
add_action( 'admin_menu', 'friends_add_admin_menu' );

/**************************************************************************
 friends_setup_nav()
 
 Set up front end navigation.
 **************************************************************************/

function friends_setup_nav() {
	global $loggedin_userid, $loggedin_domain;
	global $current_userid, $current_domain;
	global $bp_nav, $bp_options_nav, $bp_users_nav;
	global $bp_friends_slug, $bp_options_avatar, $bp_options_title;;
	global $current_component, $action_variables;

	$bp_nav[3] = array(
		'id'	=> $bp_friends_slug,
		'name'  => __('Friends'), 
		'link'  => $loggedin_domain . $bp_friends_slug . '/'
	);
	
	$bp_users_nav[2] = array(
		'id'	=> $bp_friends_slug,
		'name'  => __('Friends'), 
		'link'  => $current_domain . $bp_friends_slug . '/'
	);
	
	if ( $current_component == $bp_friends_slug ) {
		if ( bp_is_home() ) {
			$bp_options_title = __('My Friends');
			$bp_options_nav[$bp_friends_slug] = array(
				'my-friends'    => array( 
					'name'      => __('My Friends'),
					'link'      => $loggedin_domain . $bp_friends_slug . '/my-friends' ),
				'requests'      => array(
					'name'      => __('Requests'),
					'link'      => $loggedin_domain . $bp_friends_slug . '/requests' ),
				'friend-finder' => array( 
					'name'      => __('Friend Finder'),
					'link'      => $loggedin_domain . $bp_friends_slug . '/friend-finder' ),
				'invite-friend' => array( 
					'name'      => __('Invite Friends'),
					'link'      => $loggedin_domain . $bp_friends_slug . '/invite-friend' )
			);		
		} else {
			$bp_options_avatar = xprofile_get_avatar( $current_userid, 1 );
			$bp_options_title = bp_user_fullname( $current_userid, false ); 
		}
	}
}
add_action( 'wp', 'friends_setup_nav' );


/**************************************************************************
 friends_catch_action()
 
 Catch actions via pretty urls.
 **************************************************************************/

function friends_catch_action() {
	global $bp_friends_slug, $current_component, $current_blog;
	global $loggedin_userid, $current_userid, $current_action;
	global $bp_options_nav, $action_variables, $thread_id;
	global $message, $type;
	
	if ( $current_component == $bp_friends_slug && $current_blog->blog_id > 1 ) {

		switch ( $current_action ) {
			case 'my-friends':
				bp_catch_uri( 'friends/index' );
			break;
			
			case 'friend-finder':
				bp_catch_uri( 'friends/friend-finder' );
			break;
			
			case 'requests':
				if ( isset($action_variables) && in_array( 'accept', $action_variables ) && is_numeric($action_variables[1]) ) {
					if ( BP_Friends_Friendship::accept( $action_variables[1] ) ) {
						$message = __('Friendship accepted');
						$type = 'success';
					} else {
						$message = __('Friendship could not be accepted');
						$type = 'error';					
					}
					add_action( 'template_notices', 'bp_render_notice' );
				} else if ( isset($action_variables) && in_array( 'reject', $action_variables ) && is_numeric($action_variables[1]) ) {
					if ( BP_Friends_Friendship::reject( $action_variables[1] ) ) {
						$message = __('Friendship rejected');
						$type = 'success';
					} else {
						$message = __('Friendship could not be rejected');
						$type = 'error';				
					}
					add_action( 'template_notices', 'bp_render_notice' );
				}
				bp_catch_uri( 'friends/requests' );
			break; 
			
			default:
				$current_action = 'my-friends';
				bp_catch_uri( 'friends/index' );				
			break;
		}
	}
}
add_action( 'wp', 'friends_catch_action' );

/**************************************************************************
 friends_template()
 
 Set up template tags for use in templates.
 **************************************************************************/

function friends_template() {
	global $friends_template, $loggedin_userid;
	global $current_component, $bp_friends_slug;
	global $current_action, $loggedin_domain;
	
	if ( $current_component == $bp_friends_slug ) {
		if ( $current_action != 'friend-finder' || $current_action != 'invite-friends' )
			$friends_template = new BP_Friendship_Template( $current_userid );
	}
	
}
add_action( 'wp_head', 'friends_template' );


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
	global $current_userid;

	if ( !$user_id )
		$user_id = $current_userid;
	
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

/**************************************************************************
 friends_search_users()
 
 Return an array of user objects based on the users search terms
**************************************************************************/

function friends_search_users( $search_terms, $user_id, $pag_num = 5, $pag_page = 1 ) {
	global $loggedin_userid;
	
	if ( !$user_id )
		$user_id = $loggedin_userid;

	$user_ids = BP_Friends_Friendship::search_users( $search_terms, $user_id, $pag_num, $pag_page );
	
	if ( !$user_ids )
		return false;

	for ( $i = 0; $i < count($user_ids); $i++ ) {
		$users[] = new BP_Friends_Friend($user_ids[$i]);
	}
	
	return array( 'users' => $users, 'count' => BP_Friends_Friendship::search_users_count($search_terms) );
}

/**************************************************************************
 friends_check_friendship()
 
 Check to see if the user is already a confirmed friend with this user.
**************************************************************************/

function friends_check_friendship() {
	global $current_userid, $loggedin_userid;
	
	if ( BP_Friends_Friendship::check_is_friend( $loggedin_userid, $current_userid ) )
		return true;
	
	return false;
}

/**************************************************************************
 friends_add_friend()
 
 Create a new friend relationship
**************************************************************************/

function friends_add_friend( $initiator_userid = null, $friend_userid = null ) {
	global $loggedin_userid, $current_userid;
	
	if ( !$initiator_userid )
		$initiator_userid = $loggedin_userid;
	
	if ( !$friend_userid )
		$friend_userid = $current_userid;
	
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
	global $loggedin_userid, $current_userid;

	if ( !$initiator_userid )
		$initiator_userid = $loggedin_userid;
	
	if ( !$friend_userid )
		$friend_userid = $current_userid;
		
	$friendship_id = BP_Friends_Friendship::get_friendship_ids( $initiator_userid, $only_confirmed, false, null, null, $friend_userid );

	$friendship = new BP_Friends_Friendship( $friendship_id[0]->id );
	
	return $friendship->delete();
}


?>