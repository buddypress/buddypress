<?php

define ( 'BP_GROUPS_VERSION', '1.0' );
define ( 'BP_GROUPS_DB_VERSION', '1300' );

/* Define the slug for the component */
if ( !defined( 'BP_GROUPS_SLUG' ) )
	define ( 'BP_GROUPS_SLUG', 'groups' );

require ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-classes.php' );
require ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-ajax.php' );
require ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-cssjs.php' );
require ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-templatetags.php' );
require ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-widgets.php' );
require ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-filters.php' );


/**************************************************************************
 groups_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function groups_install() {
	global $wpdb, $bp;
	
	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	$sql[] = "CREATE TABLE {$bp->groups->table_name} (
	  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			creator_id bigint(20) NOT NULL,
	  		name varchar(100) NOT NULL,
	  		slug varchar(100) NOT NULL,
	  		description longtext NOT NULL,
			news longtext NOT NULL,
			status varchar(10) NOT NULL DEFAULT 'open',
			is_invitation_only tinyint(1) NOT NULL DEFAULT '0',
			enable_wire tinyint(1) NOT NULL DEFAULT '1',
			enable_forum tinyint(1) NOT NULL DEFAULT '1',
			enable_photos tinyint(1) NOT NULL DEFAULT '1',
			photos_admin_only tinyint(1) NOT NULL DEFAULT '0',
			date_created datetime NOT NULL,
			avatar_thumb varchar(250) NOT NULL,
			avatar_full varchar(250) NOT NULL,
		    KEY creator_id (creator_id),
		    KEY status (status),
		    KEY is_invitation_only (is_invitation_only)
	 	   ) {$charset_collate};";
	
	$sql[] = "CREATE TABLE {$bp->groups->table_name_members} (
	  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			group_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			inviter_id bigint(20) NOT NULL,
			is_admin tinyint(1) NOT NULL DEFAULT '0',
			is_mod tinyint(1) NOT NULL DEFAULT '0',
			user_title varchar(100) NOT NULL,
			date_modified datetime NOT NULL,
			comments longtext NOT NULL,
			is_confirmed tinyint(1) NOT NULL DEFAULT '0',
			is_banned tinyint(1) NOT NULL DEFAULT '0',
			invite_sent tinyint(1) NOT NULL DEFAULT '0',
			KEY group_id (group_id),
			KEY is_admin (is_admin),
			KEY is_mod (is_mod),
		 	KEY user_id (user_id),
			KEY inviter_id (inviter_id),
			KEY is_confirmed (is_confirmed)
	 	   ) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp->groups->table_name_groupmeta} (
			id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			group_id bigint(20) NOT NULL,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext DEFAULT NULL,
			KEY group_id (group_id),
			KEY meta_key (meta_key)
		   ) {$charset_collate};";
	
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
	
	if ( function_exists('bp_wire_install') )
		groups_wire_install();
	
	update_site_option( 'bp-groups-db-version', BP_GROUPS_DB_VERSION );
}

function groups_wire_install() {
	global $wpdb, $bp;
	
	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	$sql[] = "CREATE TABLE {$bp->groups->table_name_wire} (
	  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			item_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			content longtext NOT NULL,
			date_posted datetime NOT NULL,
			KEY item_id (item_id),
			KEY user_id (user_id)
	 	   ) {$charset_collate};";

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
}


/**************************************************************************
 groups_setup_globals()
 
 Set up and add all global variables for this component, and add them to 
 the $bp global variable array.
 **************************************************************************/

function groups_setup_globals( $no_global = false ) {
	global $wpdb;
	
	if ( !$no_global )
		global $bp;

	$bp->groups->table_name = $wpdb->base_prefix . 'bp_groups';
	$bp->groups->table_name_members = $wpdb->base_prefix . 'bp_groups_members';
	$bp->groups->table_name_groupmeta = $wpdb->base_prefix . 'bp_groups_groupmeta';
	$bp->groups->image_base = BP_PLUGIN_URL . '/bp-groups/images';
	$bp->groups->format_activity_function = 'groups_format_activity';
	$bp->groups->format_notification_function = 'groups_format_notifications';
	$bp->groups->slug = BP_GROUPS_SLUG;

	if ( function_exists('bp_wire_install') )
		$bp->groups->table_name_wire = $wpdb->base_prefix . 'bp_groups_wire';
	
	$bp->groups->forbidden_names = apply_filters( 'groups_forbidden_names', array( 'my-groups', 'group-finder', 'create', 'invites', 'delete', 'add', 'admin', 'request-membership' ) );
	$bp->version_numbers->groups = BP_GROUPS_VERSION;
	
	return $bp;
}
add_action( 'plugins_loaded', 'groups_setup_globals', 5 );	
add_action( 'admin_menu', 'groups_setup_globals', 1 );

function groups_setup_root_component() {
	/* Register 'groups' as a root component */
	bp_core_add_root_component( BP_GROUPS_SLUG );
}
add_action( 'plugins_loaded', 'groups_setup_root_component', 1 );

function groups_check_installed() {	
	global $wpdb, $bp;
	
	require ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-admin.php' );

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( get_site_option('bp-groups-db-version') < BP_GROUPS_DB_VERSION )
		groups_install();
}
add_action( 'admin_menu', 'groups_check_installed' );

function groups_add_admin_menu() {
	global $wpdb, $bp;
	
	if ( !is_site_admin() )
		return false;
		
	/* Add the administration tab under the "Site Admin" tab for site administrators */
	add_submenu_page( 'wpmu-admin.php', __("Groups", 'buddypress'), __("Groups", 'buddypress'), 1, "groups_admin_settings", "groups_admin_settings" );
}
add_action( 'admin_menu', 'groups_add_admin_menu' );

/**************************************************************************
 groups_setup_nav()
 
 Set up front end navigation.
 **************************************************************************/

function groups_setup_nav() {
	global $bp, $current_blog;
	global $group_obj;
	
	if ( $group_id = BP_Groups_Group::group_exists($bp->current_action) ) {
		
		/* This is a single group page. */
		$bp->is_single_item = true;
		$group_obj = new BP_Groups_Group( $group_id );
	
		/* Using "item" not "group" for generic support in other components. */
		if ( is_site_admin() )
			$bp->is_item_admin = 1;
		else
			$bp->is_item_admin = groups_is_user_admin( $bp->loggedin_user->id, $group_obj->id );
		
		/* If the user is not an admin, check if they are a moderator */
		if ( !$bp->is_item_admin )
			$bp->is_item_mod = groups_is_user_mod( $bp->loggedin_user->id, $group_obj->id );
		
		/* Is the logged in user a member of the group? */
		$is_member = ( groups_is_user_member( $bp->loggedin_user->id, $group_obj->id ) ) ? true : false;
	
		/* Should this group be visible to the logged in user? */
		$is_visible = ( 'public' == $group_obj->status || $is_member ) ? true : false;
	}

	/* Add 'Groups' to the main navigation */
	bp_core_add_nav_item( __('Groups', 'buddypress'), $bp->groups->slug );
	
	if ( $bp->displayed_user->id )
		bp_core_add_nav_default( $bp->groups->slug, 'groups_screen_my_groups', 'my-groups' );
		
	$groups_link = $bp->loggedin_user->domain . $bp->groups->slug . '/';
	
	/* Add the subnav items to the groups nav item */
	bp_core_add_subnav_item( $bp->groups->slug, 'my-groups', __('My Groups', 'buddypress'), $groups_link, 'groups_screen_my_groups', 'my-groups-list' );
	bp_core_add_subnav_item( $bp->groups->slug, 'create', __('Create a Group', 'buddypress'), $groups_link, 'groups_screen_create_group', false, bp_is_home() );
	bp_core_add_subnav_item( $bp->groups->slug, 'invites', __('Invites', 'buddypress'), $groups_link, 'groups_screen_group_invites', false, bp_is_home() );
	
	if ( $bp->current_component == $bp->groups->slug ) {
		
		if ( bp_is_home() && !$bp->is_single_item ) {
			
			$bp->bp_options_title = __('My Groups', 'buddypress');
			
		} else if ( !bp_is_home() && !$bp->is_single_item ) {

			$bp->bp_options_avatar = bp_core_get_avatar( $bp->displayed_user->id, 1 );
			$bp->bp_options_title = $bp->displayed_user->fullname;
			
		} else if ( $bp->is_single_item ) {
			// We are viewing a single group, so set up the
			// group navigation menu using the $group_obj global.
			
			/* When in a single group, the first action is bumped down one because of the
			   group name, so we need to adjust this and set the group name to current_item. */
			$bp->current_item = $bp->current_action;
			$bp->current_action = $bp->action_variables[0];
			array_shift($bp->action_variables);
									
			$bp->bp_options_title = $group_obj->name;
			$bp->bp_options_avatar = '<img src="' . $group_obj->avatar_thumb . '" alt="Group Avatar Thumbnail" />';
			
			$group_link = $bp->root_domain . '/' . $bp->groups->slug . '/' . $group_obj->slug . '/';
			
			// If this is a private or hidden group, does the user have access?
			if ( 'private' == $group_obj->status || 'hidden' == $group_obj->status ) {
				if ( groups_is_user_member( $bp->loggedin_user->id, $group_obj->id ) && is_user_logged_in() )
					$has_access = true;
				else
					$has_access = false;
			} else {
				$has_access = true;
			}

			// Reset the existing subnav items
			bp_core_reset_subnav_items($bp->groups->slug);
			
			bp_core_add_nav_default( $bp->groups->slug, 'groups_screen_group_home', 'home' );
			bp_core_add_subnav_item( $bp->groups->slug, 'home', __('Home', 'buddypress'), $group_link, 'groups_screen_group_home', 'group-home' );
			
			// If the user is a group mod or more, then show the group admin nav item */
			if ( $bp->is_item_mod || $bp->is_item_admin )
				bp_core_add_subnav_item( $bp->groups->slug, 'admin', __('Admin', 'buddypress'), $group_link , 'groups_screen_group_admin', 'group-admin', ( $bp->is_item_admin + (int)$bp->is_item_mod ) );

			// If this is a private group, and the user is not a member, show a "Request Membership" nav item.
			if ( !$has_access && !groups_check_for_membership_request( $bp->loggedin_user->id, $group_obj->id ) && $group_obj->status == 'private' )
				bp_core_add_subnav_item( $bp->groups->slug, 'request-membership', __('Request Membership', 'buddypress'), $group_link , 'groups_screen_group_request_membership', 'request-membership' );
			
			if ( $has_access && $group_obj->enable_forum && function_exists('bp_forums_setup') )
				bp_core_add_subnav_item( $bp->groups->slug, 'forum', __('Forum', 'buddypress'), $group_link , 'groups_screen_group_forum', 'group-forum', $is_visible);

			if ( $has_access && $group_obj->enable_wire && function_exists('bp_wire_install') )
				bp_core_add_subnav_item( $bp->groups->slug, 'wire', __('Wire', 'buddypress'), $group_link, 'groups_screen_group_wire', 'group-wire', $is_visible );

			if ( $has_access && $group_obj->enable_photos && function_exists('bp_gallery_install') )
				bp_core_add_subnav_item( $bp->groups->slug, 'photos', __('Photos', 'buddypress'), $group_link, 'groups_screen_group_photos', 'group-photos', $is_visible );

			if ( $has_access )
				bp_core_add_subnav_item( $bp->groups->slug, 'members', __('Members', 'buddypress'), $group_link, 'groups_screen_group_members', 'group-members', $is_visible );
			
			if ( is_user_logged_in() && groups_is_user_member( $bp->loggedin_user->id, $group_obj->id ) ) {
				if ( function_exists('friends_install') )
					bp_core_add_subnav_item( $bp->groups->slug, 'send-invites', __('Send Invites', 'buddypress'), $group_link, 'groups_screen_group_invite', 'group-invite', $is_member );
				
				bp_core_add_subnav_item( $bp->groups->slug, 'leave-group', __('Leave Group', 'buddypress'), $group_link, 'groups_screen_group_leave', 'group-leave', $is_member );
			}
		}
	}
}
add_action( 'wp', 'groups_setup_nav', 2 );
add_action( 'admin_menu', 'groups_setup_nav', 2 );

function groups_directory_groups_setup() {
	global $bp;
	
	if ( $bp->current_component == $bp->groups->slug && empty( $bp->current_action ) ) {
		$bp->is_directory = true;

		wp_enqueue_script( 'bp-groups-directory-groups', BP_PLUGIN_URL . '/bp-groups/js/directory-groups.js', array( 'jquery', 'jquery-livequery-pack' ) );
		bp_core_load_template( apply_filters( 'groups_template_directory_groups', 'directories/groups/index' ) );
	}
}
add_action( 'wp', 'groups_directory_groups_setup', 5 );

/***** Screens **********/

function groups_screen_my_groups() {
	global $bp;
	
	bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'member_promoted_to_mod' );
	bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'member_promoted_to_admin' );

	do_action( 'groups_screen_my_groups' );
	
	bp_core_load_template( apply_filters( 'groups_template_my_groups', 'groups/index' ) );
}

function groups_screen_group_invites() {
	global $bp;
	
	$group_id = $bp->action_variables[1];
	
	if ( isset($bp->action_variables) && in_array( 'accept', $bp->action_variables ) && is_numeric($group_id) ) {
		
		if ( !groups_accept_invite( $bp->loggedin_user->id, $group_id ) ) {
			bp_core_add_message( __('Group invite could not be accepted', 'buddypress'), 'error' );				
		} else {
			bp_core_add_message( __('Group invite accepted', 'buddypress') );
			
			/* Record this in activity streams */
			groups_record_activity( array( 'item_id' => $group_id, 'component_name' => $bp->groups->slug, 'component_action' => 'joined_group', 'is_private' => 0 ) );
		}

		bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component . '/' . $bp->current_action );
		
	} else if ( isset($bp->action_variables) && in_array( 'reject', $bp->action_variables ) && is_numeric($group_id) ) {
		
		if ( !groups_reject_invite( $bp->loggedin_user->id, $group_id ) ) {
			bp_core_add_message( __('Group invite could not be rejected', 'buddypress'), 'error' );							
		} else {			
			bp_core_add_message( __('Group invite rejected', 'buddypress') );			
		}

		bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component . '/' . $bp->current_action );
	}
	
	// Remove notifications
	bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'group_invite' );

	do_action( 'groups_screen_group_invites', $group_id );
	
	bp_core_load_template( apply_filters( 'groups_template_group_invites', 'groups/list-invites' ) );	
}

function groups_screen_create_group() {
	global $bp;
	global $create_group_step, $group_obj, $completed_to_step;
	
	$no_instantiate = false;
	$reset_steps = false;
	
	if ( !$create_group_step = $bp->action_variables[1] ) {
		$create_group_step = 1;
		$completed_to_step = 0;
		
		unset($_SESSION['group_obj_id']);
		unset($_SESSION['completed_to_step']);
		
		$no_instantiate = true;
		$reset_steps = true;
	}
	
	if ( isset($_SESSION['completed_to_step']) && !$reset_steps ) {
		$completed_to_step = $_SESSION['completed_to_step'];
	}
	
	if ( isset( $_POST['save'] ) || isset( $_POST['skip'] ) ) {
		$group_obj = new BP_Groups_Group( $_SESSION['group_obj_id'] );

		if ( !$group_id = groups_create_group( $create_group_step, $_SESSION['group_obj_id'] ) ) {
			bp_core_add_message( __('There was an error saving group details. Please try again.', 'buddypress'), 'error' );
			bp_core_redirect( $bp->loggedin_user->domain . $bp->groups->slug . '/create/step/' . $create_group_step );
		} else {
			$create_group_step++;
			$completed_to_step++;
			$_SESSION['completed_to_step'] = $completed_to_step;
			$_SESSION['group_obj_id'] = $group_id;
		}
		
		if ( $completed_to_step == 4 )
			bp_core_redirect( bp_get_group_permalink( $group_obj ) );
	}

	if ( isset($_SESSION['group_obj_id']) && !$group_obj && !$no_instantiate )
		$group_obj = new BP_Groups_Group( $_SESSION['group_obj_id'] );
	
 	bp_core_load_template( apply_filters( 'groups_template_create_group', 'groups/create' ) );
}

function groups_screen_group_home() {
	global $bp;
	
	if ( $bp->is_single_item ) {
		
		if ( isset($_GET['new']) ) {
			// Delete group request notifications for the user
			bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'membership_request_accepted' );
			bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'membership_request_rejected' );
			bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'member_promoted_to_mod' );
			bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'member_promoted_to_admin' );
		}	

		do_action( 'groups_screen_group_home' );	
		
		bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/group-home' ) );		
	}
}

function groups_screen_group_forum() {
	global $bp, $group_obj;
	
	if ( $bp->is_single_item ) {
		$topic_id = $bp->action_variables[1];
		$forum_id = groups_get_groupmeta( $group_obj->id, 'forum_id' );
		
		if ( $topic_id ) {
			
			/* Posting a reply */
			if ( isset( $_POST['submit_reply'] ) && function_exists( 'bp_forums_new_post') ) {
				groups_new_group_forum_post( $_POST['reply_text'], $topic_id );
				bp_core_redirect( bp_get_group_permalink( $group_obj ) . '/forum/topic/' . $topic_id );
			}
			
			do_action( 'groups_screen_group_forum_topic' );
			
			// If we are viewing a topic, load it.
 			bp_core_load_template( apply_filters( 'groups_template_group_forum', 'groups/forum/topic' ) );
		} else {

			/* Posting a topic */
			if ( isset( $_POST['submit_topic'] ) && function_exists( 'bp_forums_new_topic') ) {
				groups_new_group_forum_topic( $_POST['topic_title'], $_POST['topic_text'], $_POST['topic_tags'], $forum_id );
				bp_core_redirect( bp_get_group_permalink( $group_obj ) . '/forum/' );
			}
			
			do_action( 'groups_screen_group_forum', $topic_id, $forum_id );
			
			// Load the forum home.
			bp_core_load_template( apply_filters( 'groups_template_group_forum', 'groups/forum/index' ) );				
		}
	}
}

function groups_screen_group_wire() {
	global $bp;
	global $group_obj;
	
	$wire_action = $bp->action_variables[0];
		
	if ( $bp->is_single_item ) {
		if ( 'post' == $wire_action && BP_Groups_Member::check_is_member( $bp->loggedin_user->id, $group_obj->id ) ) {

			if ( !groups_new_wire_post( $group_obj->id, $_POST['wire-post-textarea'] ) ) {
				bp_core_add_message( __('Wire message could not be posted.', 'buddypress'), 'error' );
			} else {
				bp_core_add_message( __('Wire message successfully posted.', 'buddypress') );
			}

			if ( !strpos( $_SERVER['HTTP_REFERER'], $bp->wire->slug ) ) {
				bp_core_redirect( bp_get_group_permalink( $group_obj ) );
			} else {
				bp_core_redirect( bp_get_group_permalink( $group_obj ) . '/' . $bp->wire->slug );
			}
	
		} else if ( 'delete' == $wire_action && BP_Groups_Member::check_is_member( $bp->loggedin_user->id, $group_obj->id ) ) {
			$wire_message_id = $bp->action_variables[1];

			if ( !groups_delete_wire_post( $wire_message_id, $bp->groups->table_name_wire ) ) {
				bp_core_add_message( __('There was an error deleting the wire message.', 'buddypress'), 'error' );
			} else {
				bp_core_add_message( __('Wire message successfully deleted.', 'buddypress') );
			}
			
			if ( !strpos( $_SERVER['HTTP_REFERER'], $bp->wire->slug ) ) {
				bp_core_redirect( bp_get_group_permalink( $group_obj ) );
			} else {
				bp_core_redirect( bp_get_group_permalink( $group_obj ) . '/' . $bp->wire->slug );
			}
		
		} else if ( ( !$wire_action || 'latest' == $bp->action_variables[1] ) ) {
			bp_core_load_template( apply_filters( 'groups_template_group_wire', 'groups/wire' ) );
		} else {
			bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/group-home' ) );
		}
	}
}

function groups_screen_group_members() {
	global $bp;
	global $group_obj;
	
	if ( $bp->is_single_item ) {
		do_action( 'groups_screen_group_members', $group_obj->id );
		
		bp_core_load_template( apply_filters( 'groups_template_group_members', 'groups/list-members' ) );
	}
}

function groups_screen_group_invite() {
	global $bp;
	global $group_obj;
	
	if ( $bp->is_single_item ) {
		if ( isset($bp->action_variables) && 'send' == $bp->action_variables[0] ) {
			// Send the invites.
			groups_send_invites($group_obj);
			
			bp_core_add_message( __('Group invites sent.', 'buddypress') );

			do_action( 'groups_screen_group_invite', $group_obj->id );

			bp_core_redirect( bp_get_group_permalink( $group_obj ) );
		} else {
			// Show send invite page
			bp_core_load_template( apply_filters( 'groups_template_group_invite', 'groups/send-invite' ) );	
		}
	}
}

function groups_screen_group_leave() {
	global $bp;
	global $group_obj;
	
	if ( $bp->is_single_item ) {
		if ( isset($bp->action_variables) && 'yes' == $bp->action_variables[0] ) {
			
			// Check if the user is the group admin first.
			if ( groups_is_group_admin( $bp->loggedin_user->id, $group_obj->id ) ) {
				bp_core_add_message(  __('As the only group administrator, you cannot leave this group.', 'buddypress'), 'error' );
				bp_core_redirect( bp_get_group_permalink( $group_obj ) );
			}
			
			// remove the user from the group.
			if ( !groups_leave_group( $group_obj->id ) ) {
				bp_core_add_message(  __('There was an error leaving the group. Please try again.', 'buddypress'), 'error' );
				bp_core_redirect( bp_get_group_permalink( $group_obj ) );
			} else {
				bp_core_add_message( __('You left the group successfully.', 'buddypress') );
				bp_core_redirect( $bp->loggedin_user->domain . $bp->groups->slug );
			}
			
		} else if ( isset($bp->action_variables) && 'no' == $bp->action_variables[0] ) {
			
			bp_core_redirect( bp_get_group_permalink( $group_obj ) );
		
		} else {
		
			do_action( 'groups_screen_group_leave', $group_obj->id );
			
			// Show leave group page
			bp_core_load_template( apply_filters( 'groups_template_group_leave', 'groups/leave-group-confirm' ) );
		
		}
	}
}

function groups_screen_group_request_membership() {
	global $bp, $group_obj;
	
	if ( !is_user_logged_in() )
		return false;
	
	if ( 'private' == $group_obj->status ) {
		// If the user has submitted a request, send it.
		if ( isset( $_POST['group-request-send']) ) {
			if ( !groups_send_membership_request( $bp->loggedin_user->id, $group_obj->id ) ) {
				bp_core_add_message( __( 'There was an error sending your group membership request, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Your membership request was sent to the group administrator successfully. You will be notified when the group administrator responds to your request.', 'buddypress' ) );
			}
			bp_core_redirect( bp_get_group_permalink( $group_obj ) );
		}
		
		do_action( 'groups_screen_group_request_membership', $group_obj->id );
		
		bp_core_load_template( apply_filters( 'groups_template_group_request_membership', 'groups/request-membership' ) );
	}
}

function groups_screen_group_admin() {
	global $bp, $group_obj;
	
	if ( $bp->current_component == $bp->groups->slug && !$bp->action_variables ) {
		
		do_action( 'groups_screen_group_admin', $group_obj->id );
		
		bp_core_load_template( apply_filters( 'groups_template_group_admin', 'groups/admin/edit-details' ) );		
	}

}

function groups_screen_group_admin_edit_details() {
	global $bp, $group_obj;
	
	if ( $bp->current_component == $bp->groups->slug && 'edit-details' == $bp->action_variables[0] ) {
	
		if ( $bp->is_item_admin || $bp->is_item_mod  ) {
		
			// If the edit form has been submitted, save the edited details
			if ( isset( $_POST['save'] ) ) {
				if ( !groups_edit_base_group_details( $_POST['group-id'], $_POST['group-name'], $_POST['group-desc'], $_POST['group-news'], (int)$_POST['group-notify-members'] ) ) {
					bp_core_add_message( __( 'There was an error updating group details, please try again.', 'buddypress' ), 'error' );
				} else {
					bp_core_add_message( __( 'Group details were successfully updated.', 'buddypress' ) );
				}
				
				do_action( 'groups_group_details_edited', $group_obj->id );
				
				bp_core_redirect( site_url() . '/' . $bp->current_component . '/' . $bp->current_item . '/admin/edit-details' );
			}

			do_action( 'groups_screen_group_admin_edit_details', $group_obj->id );

			bp_core_load_template( apply_filters( 'groups_template_group_admin_edit_details', 'groups/admin/edit-details' ) );
			
		}
	}
}
add_action( 'wp', 'groups_screen_group_admin_edit_details', 4 );


function groups_screen_group_admin_settings() {
	global $bp, $group_obj;
	
	if ( $bp->current_component == $bp->groups->slug && 'group-settings' == $bp->action_variables[0] ) {
		
		if ( !$bp->is_item_admin )
			return false;
		
		// If the edit form has been submitted, save the edited details
		if ( isset( $_POST['save'] ) ) {
			$enable_wire = ( isset($_POST['group-show-wire'] ) ) ? 1 : 0;
			$enable_forum = ( isset($_POST['group-show-forum'] ) ) ? 1 : 0;
			$enable_photos = ( isset($_POST['group-show-photos'] ) ) ? 1 : 0;
			$photos_admin_only = ( $_POST['group-photos-status'] != 'all' ) ? 1 : 0;
			$status = $_POST['group-status'];
			
			if ( !groups_edit_group_settings( $_POST['group-id'], $enable_wire, $enable_forum, $enable_photos, $photos_admin_only, $status ) ) {
				bp_core_add_message( __( 'There was an error updating group settings, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Group settings were successfully updated.', 'buddypress' ) );
			}

			do_action( 'groups_group_settings_edited', $group_obj->id );
			
			bp_core_redirect( site_url() . '/' . $bp->current_component . '/' . $bp->current_item . '/admin/group-settings' );
		}

		do_action( 'groups_screen_group_admin_settings', $group_obj->id );
		
		bp_core_load_template( apply_filters( 'groups_template_group_admin_settings', 'groups/admin/group-settings' ) );
	}
}
add_action( 'wp', 'groups_screen_group_admin_settings', 4 );

function groups_screen_group_admin_avatar() {
	global $bp, $group_obj;
	
	if ( $bp->current_component == $bp->groups->slug && 'group-avatar' == $bp->action_variables[0] ) {
		
		if ( !$bp->is_item_admin )
			return false;
		
		if ( isset( $_POST['save'] ) ) {
			
			// Image already cropped and uploaded, lets store a reference in the DB.
			if ( !wp_verify_nonce($_POST['nonce'], 'slick_avatars') || !$result = bp_core_avatar_cropstore( $_POST['orig'], $_POST['canvas'], $_POST['v1_x1'], $_POST['v1_y1'], $_POST['v1_w'], $_POST['v1_h'], $_POST['v2_x1'], $_POST['v2_y1'], $_POST['v2_w'], $_POST['v2_h'], false, 'groupavatar', $group_obj->id ) )
				return false;

			// Success on group avatar cropping, now save the results.
			$avatar_hrefs = groups_get_avatar_hrefs($result);
			
			// Delete the old group avatars first
			$avatar_thumb_path = groups_get_avatar_path( $group_obj->avatar_thumb );
			$avatar_full_path = groups_get_avatar_path( $group_obj->avatar_full );
			
			@unlink($avatar_thumb_path);
			@unlink($avatar_full_path);

			$group_obj->avatar_thumb = stripslashes( $avatar_hrefs['thumb_href'] );
			$group_obj->avatar_full = stripslashes( $avatar_hrefs['full_href'] );

			if ( !$group_obj->save() ) {
				bp_core_add_message( __( 'There was an error updating the group avatar, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'The group avatar was successfully updated.', 'buddypress' ) );
			}

			do_action( 'groups_group_avatar_updated', $group_obj->id );

			bp_core_redirect( site_url() . '/' . $bp->current_component . '/' . $bp->current_item . '/admin/group-avatar' );
		}
		
		do_action( 'groups_screen_group_admin_avatar', $group_obj->id );
		
		bp_core_load_template( apply_filters( 'groups_template_group_admin_avatar', 'groups/admin/group-avatar' ) );
	}
}
add_action( 'wp', 'groups_screen_group_admin_avatar', 4 );

function groups_screen_group_admin_manage_members() {
	global $bp, $group_obj;

	if ( $bp->current_component == $bp->groups->slug && 'manage-members' == $bp->action_variables[0] ) {
		
		if ( !$bp->is_item_admin )
			return false;
		
		if ( 'promote' == $bp->action_variables[1] && is_numeric( $bp->action_variables[2] ) ) {
			$user_id = $bp->action_variables[2];
			
			// Promote a user.
			if ( !groups_promote_member( $user_id, $group_obj->id ) ) {
				bp_core_add_message( __( 'There was an error when promoting that user, please try again', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'User promoted successfully', 'buddypress' ) );
			}
			
			do_action( 'groups_promoted_member', $user_id, $group_obj->id );
			
			bp_core_redirect( site_url() . '/' . $bp->current_component . '/' . $bp->current_item . '/admin/manage-members' );
		}
		
		if ( 'demote' == $bp->action_variables[1] && is_numeric( $bp->action_variables[2] ) ) {
			$user_id = $bp->action_variables[2];
			
			// Demote a user.
			if ( !groups_demote_member( $user_id, $group_obj->id ) ) {
				bp_core_add_message( __( 'There was an error when demoting that user, please try again', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'User demoted successfully', 'buddypress' ) );
			}

			do_action( 'groups_demoted_member', $user_id, $group_obj->id );
			
			bp_core_redirect( site_url() . '/' . $bp->current_component . '/' . $bp->current_item . '/admin/manage-members' );
		}
		
		if ( 'ban' == $bp->action_variables[1] && is_numeric( $bp->action_variables[2] ) ) {
			$user_id = $bp->action_variables[2];
			
			// Ban a user.
			if ( !groups_ban_member( $user_id, $group_obj->id ) ) {
				bp_core_add_message( __( 'There was an error when banning that user, please try again', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'User banned successfully', 'buddypress' ) );
			}

			do_action( 'groups_banned_member', $user_id, $group_obj->id );
			
			bp_core_redirect( site_url() . '/' . $bp->current_component . '/' . $bp->current_item . '/admin/manage-members' );
		}
		
		if ( 'unban' == $bp->action_variables[1] && is_numeric( $bp->action_variables[2] ) ) {
			$user_id = $bp->action_variables[2];
			
			// Remove a ban for user.
			if ( !groups_unban_member( $user_id, $group_obj->id ) ) {
				bp_core_add_message( __( 'There was an error when unbanning that user, please try again', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'User ban removed successfully', 'buddypress' ) );
			}

			do_action( 'groups_unbanned_member', $user_id, $group_obj->id );
			
			bp_core_redirect( site_url() . '/' . $bp->current_component . '/' . $bp->current_item . '/admin/manage-members' );
		}

		do_action( 'groups_screen_group_admin_manage_members', $group_obj->id );
		
		bp_core_load_template( apply_filters( 'groups_template_group_admin_manage_members', 'groups/admin/manage-members' ) );
	}
}
add_action( 'wp', 'groups_screen_group_admin_manage_members', 4 );


function groups_screen_group_admin_requests() {
	global $bp, $group_obj;
	
	if ( $bp->current_component == $bp->groups->slug && 'membership-requests' == $bp->action_variables[0] ) {
		
		if ( !$bp->is_item_admin || 'public' == $group_obj->status )
			return false;
		
		// Remove any screen notifications
		bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'new_membership_request' );
		
		$request_action = $bp->action_variables[1];
		$membership_id = $bp->action_variables[2];

		if ( isset($request_action) && isset($membership_id) ) {
			if ( 'accept' == $request_action && is_numeric($membership_id) ) {

				// Accept the membership request
				if ( !groups_accept_membership_request( $membership_id ) ) {
					bp_core_add_message( __( 'There was an error accepting the membership request, please try again.', 'buddypress' ), 'error' );
				} else {
					bp_core_add_message( __( 'Group membership request accepted', 'buddypress' ) );
				}

			} else if ( 'reject' == $request_action && is_numeric($membership_id) ) {

				// Reject the membership request
				if ( !groups_reject_membership_request( $membership_id ) ) {
					bp_core_add_message( __( 'There was an error rejecting the membership request, please try again.', 'buddypress' ), 'error' );
				} else {
					bp_core_add_message( __( 'Group membership request rejected', 'buddypress' ) );
				}	

			}
			
			do_action( 'groups_group_request_managed', $group_obj->id, $request_action, $membership_id );
			
			bp_core_redirect( site_url() . '/' . $bp->current_component . '/' . $bp->current_item . '/admin/membership-requests' );
		}

		do_action( 'groups_screen_group_admin_requests', $group_obj->id );
		
		bp_core_load_template( apply_filters( 'groups_template_group_admin_requests', 'groups/admin/membership-requests' ) );
	}
}
add_action( 'wp', 'groups_screen_group_admin_requests', 4 );

function groups_screen_group_admin_delete_group() {
	global $bp, $group_obj;
	
	if ( $bp->current_component == $bp->groups->slug && 'delete-group' == $bp->action_variables[0] ) {
		
		if ( !$bp->is_item_admin )
			return false;
		
		if ( isset( $_POST['delete-group-button'] ) && isset( $_POST['delete-group-understand'] ) ) {
			// Group admin has deleted the group, now do it.
			if ( !groups_delete_group( $_POST['group-id']) ) {
				bp_core_add_message( __( 'There was an error deleting the group, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'The group was deleted successfully', 'buddypress' ) );

				do_action( 'groups_group_deleted', $_POST['group-id'] );

				bp_core_redirect( $bp->loggedin_user->domain . $bp->groups->slug . '/' );
			}

			bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component );
		} else {
			do_action( 'groups_screen_group_admin_delete_group', $group_obj->id );
			
			bp_core_load_template( apply_filters( 'groups_template_group_admin_delete_group', 'groups/admin/delete-group' ) );
		}
	}
}
add_action( 'wp', 'groups_screen_group_admin_delete_group', 4 );

function groups_screen_notification_settings() { 
	global $current_user; ?>
	<table class="notification-settings" id="groups-notification-settings">
		<tr>
			<th class="icon"></th>
			<th class="title"><?php _e( 'Groups', 'buddypress' ) ?></th>
			<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
			<th class="no"><?php _e( 'No', 'buddypress' )?></th>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'A member invites you to join a group', 'buddypress' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_groups_invite]" value="yes" <?php if ( !get_usermeta( $current_user->id, 'notification_groups_invite') || 'yes' == get_usermeta( $current_user->id, 'notification_groups_invite') ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_groups_invite]" value="no" <?php if ( 'no' == get_usermeta( $current_user->id, 'notification_groups_invite') ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'Group information is updated', 'buddypress' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_groups_group_updated]" value="yes" <?php if ( !get_usermeta( $current_user->id, 'notification_groups_group_updated') || 'yes' == get_usermeta( $current_user->id, 'notification_groups_group_updated') ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_groups_group_updated]" value="no" <?php if ( 'no' == get_usermeta( $current_user->id, 'notification_groups_group_updated') ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		<?php if ( function_exists('bp_wire_install') ) { ?>
		<tr>
			<td></td>
			<td><?php _e( 'A member posts on the wire of a group you belong to', 'buddypress' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_groups_wire_post]" value="yes" <?php if ( !get_usermeta( $current_user->id, 'notification_groups_wire_post') || 'yes' == get_usermeta( $current_user->id, 'notification_groups_wire_post') ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_groups_wire_post]" value="no" <?php if ( 'no' == get_usermeta( $current_user->id, 'notification_groups_wire_post') ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		<?php } ?>
		<tr>
			<td></td>
			<td><?php _e( 'You are promoted to a group administrator or moderator', 'buddypress' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_groups_admin_promotion]" value="yes" <?php if ( !get_usermeta( $current_user->id, 'notification_groups_admin_promotion') || 'yes' == get_usermeta( $current_user->id, 'notification_groups_admin_promotion') ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_groups_admin_promotion]" value="no" <?php if ( 'no' == get_usermeta( $current_user->id, 'notification_groups_admin_promotion') ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'A member requests to join a private group for which you are an admin', 'buddypress' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_groups_membership_request]" value="yes" <?php if ( !get_usermeta( $current_user->id, 'notification_groups_membership_request') || 'yes' == get_usermeta( $current_user->id, 'notification_groups_membership_request') ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_groups_membership_request]" value="no" <?php if ( 'no' == get_usermeta( $current_user->id, 'notification_groups_membership_request') ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
		
		<?php do_action( 'groups_screen_notification_settings' ); ?>
	</table>
<?php	
}
add_action( 'bp_notification_settings', 'groups_screen_notification_settings' );


/***** Actions **********/

function groups_action_join_group() {
	global $bp;
	global $group_obj;	

	if ( !$bp->is_single_item || $bp->current_component != $bp->groups->slug || $bp->current_action != 'join' )
		return false;
		
	// user wants to join a group
	if ( !groups_is_user_member( $bp->loggedin_user->id, $group_obj->id ) && !groups_is_user_banned( $bp->loggedin_user->id, $group_obj->id ) ) {
		if ( !groups_join_group($group_obj->id) ) {
			bp_core_add_message( __('There was an error joining the group.', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('You joined the group!', 'buddypress') );
		}
		bp_core_redirect( bp_get_group_permalink( $group_obj ) );
	}

	bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/group-home' ) );
}
add_action( 'wp', 'groups_action_join_group', 3 );


/**************************************************************************
 groups_record_activity()
 
 Records activity for the logged in user within the friends component so that
 it will show in the users activity stream (if installed)
 **************************************************************************/

function groups_record_activity( $args = true ) {
	global $group_obj;
	
	if ( function_exists('bp_activity_record') ) {
		extract($args);

		if ( !$group_obj ) {
			if ( !$group_obj = wp_cache_get( 'groups_group_nouserdata_' . $item_id, 'bp' ) ) {
				$group_obj = new BP_Groups_Group( $group_obj->id, false, false );
				wp_cache_set( 'groups_group_nouserdata_' . $item_id, $group_obj, 'bp' );
			}
		}

		if ( 'public' == $group_obj->status )
			bp_activity_record( $item_id, $component_name, $component_action, $is_private, $secondary_item_id, $user_id, $secondary_user_id );
	}
}

function groups_delete_activity( $args = true ) {
	if ( function_exists('bp_activity_delete') ) {
		extract($args);
		bp_activity_delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id );
	}
}


/**************************************************************************
 groups_format_activity()
 
 Selects and formats recorded groups component activity.
 Example: Selects the groups details for a joined group, then
          formats it to read "Andy Peatling joined the group 'A Cool Group'"
 **************************************************************************/

function groups_format_activity( $item_id, $user_id, $action, $secondary_item_id = false, $for_secondary_user = false  ) {
	global $bp;
	
	switch( $action ) {
		case 'joined_group':
			$group = new BP_Groups_Group( $item_id, false, false );
			
			if ( !$group )
				return false;
				
			$user_link = bp_core_get_userlink( $user_id );
			$group_link = bp_get_group_permalink( $group );
			
			return array( 
				'primary_link' => $group_link,
				'content' => apply_filters( 'bp_groups_joined_group_activity', sprintf( __('%s joined the group %s', 'buddypress'), $user_link,  '<a href="' . $group_link . '">' . $group->name . '</a>' ) . ' <span class="time-since">%s</span>', $user_link, $group_link, $group->name )
			);				
		break;
		case 'created_group':
			$group = new BP_Groups_Group( $item_id, false, false );

			if ( !$group )
				return false;
			
			$user_link = bp_core_get_userlink( $user_id );
			$group_link = bp_get_group_permalink( $group );
			
			return array( 
				'primary_link' => $group_link,
				'content' => apply_filters( 'bp_groups_created_group_activity', sprintf( __('%s created the group %s', 'buddypress'), $user_link, '<a href="' . $group_link . '">' . $group->name . '</a>') . ' <span class="time-since">%s</span>', $user_link, $group_link, $group->name )
			);
		break;
		case 'new_wire_post':
			$wire_post = new BP_Wire_Post( $bp->groups->table_name_wire, $item_id );
			$group = new BP_Groups_Group( $wire_post->item_id, false, false );

			if ( !$group || !$wire_post || !$wire_post->content )
				return false;		

			$user_link = bp_core_get_userlink( $user_id );
			$group_link = bp_get_group_permalink( $group );
			$post_excerpt = bp_create_excerpt( $wire_post->content );
					
			$content = sprintf ( __('%s wrote on the wire of the group %s', 'buddypress'), $user_link, '<a href="' . $group_link . '">' . $group->name . '</a>' ) . ' <span class="time-since">%s</span>';			
			$content .= '<blockquote>' . $post_excerpt . '</blockquote>';
			
			$content = apply_filters( 'bp_groups_new_wire_post_activity', $content, $user_link, $group_link, $group->name, $post_excerpt );
			
			return array( 
				'primary_link' => $group_link,
				'content' => $content
			);
		break;
		case 'new_forum_post':
			if ( function_exists('bp_forums_setup') ) {
				$group = new BP_Groups_Group( $item_id, false, false );
				$forum_post = bp_forums_get_post( $secondary_item_id );
				$forum_topic = bp_forums_get_topic_details( $forum_post['topic_id'] );

				if ( !$group || !$forum_post || !$forum_topic )
					return false;

				$user_link = bp_core_get_userlink($user_id);
				$group_link = bp_get_group_permalink( $group );

				$post_content = apply_filters( 'bp_the_topic_post_content', bp_create_excerpt( stripslashes( $forum_post['post_text'] ), 55, false ) );
			
				$content = sprintf ( __('%s posted on the forum topic %s in the group %s:', 'buddypress'), $user_link, '<a href="' . $group_link . '/forum/topic/' . $forum_topic['topic_id'] . '">' . $forum_topic['topic_title'] . '</a>', '<a href="' . $group_link . '">' . $group->name . '</a>' ) . ' <span class="time-since">%s</span>';			
				$content .= '<blockquote>' . $post_content . '</blockquote>';
				
				$content = apply_filters( 'bp_groups_new_forum_post_activity', $content, $user_link, $group_link, $forum_topic['topic_id'], $forum_topic['topic_title'], $group_link, $group->name, $post_content );

				return array( 
					'primary_link' => $group_link,
					'content' => $content
				);
			}
		break;
		case 'new_forum_topic':
			if ( function_exists('bp_forums_setup') ) {
				$group = new BP_Groups_Group( $item_id, false, false );
				$forum_topic = bp_forums_get_topic_details( $secondary_item_id );
				$forum_post = bp_forums_get_post( $forum_topic['topic_last_post_id'] );

				if ( !$group || !$forum_post || !$forum_topic )
					return false;
					
				$user_link = bp_core_get_userlink($user_id);
				$group_link = bp_get_group_permalink( $group );
				
				$post_content = apply_filters( 'bp_the_topic_post_content', bp_create_excerpt( stripslashes( $forum_post['post_text'] ), 55, false ) );
				
				$content = sprintf ( __('%s created the forum topic %s in the group %s:', 'buddypress'), $user_link, '<a href="' . $group_link . '/forum/topic/' . $forum_topic['topic_id'] . '">' . $forum_topic['topic_title'] . '</a>', '<a href="' . $group_link . '">' . $group->name . '</a>' ) . ' <span class="time-since">%s</span>';			
				$content .= '<blockquote>' . $post_content . '</blockquote>';
				
				$content = apply_filters( 'bp_groups_new_forum_topic_activity', $content, $user_link, $group_link, $forum_topic['topic_id'], $forum_topic['topic_title'], $group_link, $group->name, $post_content );
				
				return array( 
					'primary_link' => $group_link,
					'content' => $content
				);
			}
		break;		
	}
	
	do_action( 'groups_format_activity', $action, $item_id, $user_id, $action, $secondary_item_id, $for_secondary_user );
	
	return false;
}

function groups_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;
	
	switch ( $action ) {
		case 'new_membership_request':
			$group_id = $secondary_item_id;
			$requesting_user_id = $item_id;
			
			$group = new BP_Groups_Group( $group_id, false, false );
			
			$group_link = bp_get_group_permalink( $group );
			
			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_groups_multiple_new_membership_requests_notification', '<a href="' . $group_link . '/admin/membership-requests/" title="' . __( 'Group Membership Requests', 'buddypress' ) . '">' . sprintf( __('%d new membership requests for the group "%s"', 'buddypress' ), (int)$total_items, $group->name ) . '</a>', $group_link, $total_items, $group->name );		
			} else {
				$user_fullname = bp_core_global_user_fullname( $requesting_user_id );
				return apply_filters( 'bp_groups_single_new_membership_request_notification', '<a href="' . $group_link . '/admin/membership-requests/" title="' . $user_fullname .' requests group membership">' . sprintf( __('%s requests membership for the group "%s"', 'buddypress' ), $user_fullname, $group->name ) . '</a>', $group_link, $user_fullname, $group->name );
			}	
		break;
		
		case 'membership_request_accepted':
			$group_id = $item_id;
			
			$group = new BP_Groups_Group( $group_id, false, false );
			$group_link = bp_get_group_permalink( $group )  . '/?new';
			
			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_groups_multiple_membership_request_accepted_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '" title="' . __( 'Groups', 'buddypress' ) . '">' . sprintf( __('%d accepted group membership requests', 'buddypress' ), (int)$total_items, $group->name ) . '</a>', $total_items, $group_name );		
			} else {
				return apply_filters( 'bp_groups_single_membership_request_accepted_notification', '<a href="' . $group_link . '">' . sprintf( __('Membership for group "%s" accepted'), $group->name ) . '</a>', $group_link, $group->name );
			}	
		break;
		
		case 'membership_request_rejected':
			$group_id = $item_id;
			
			$group = new BP_Groups_Group( $group_id, false, false );
			$group_link = bp_get_group_permalink( $group )  . '/?new';
			
			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_groups_multiple_membership_request_rejected_notification', '<a href="' . site_url() . '/' . BP_MEMBERS_SLUG . '/' . $bp->groups->slug . '" title="' . __( 'Groups', 'buddypress' ) . '">' . sprintf( __('%d rejected group membership requests', 'buddypress' ), (int)$total_items, $group->name ) . '</a>', $total_items, $group->name );		
			} else {
				return apply_filters( 'bp_groups_single_membership_request_rejected_notification', '<a href="' . $group_link . '">' . sprintf( __('Membership for group "%s" rejected'), $group->name ) . '</a>', $group_link, $group->name );
			}	
		
		break;
		
		case 'member_promoted_to_admin':
			$group_id = $item_id;
		
			$group = new BP_Groups_Group( $group_id, false, false );
			$group_link = bp_get_group_permalink( $group )  . '/?new';
			
			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_groups_multiple_member_promoted_to_admin_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '" title="' . __( 'Groups', 'buddypress' ) . '">' . sprintf( __('You were promoted to an admin in %d groups', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );		
			} else {
				return apply_filters( 'bp_groups_single_member_promoted_to_admin_notification', '<a href="' . $group_link . '">' . sprintf( __('You were promoted to an admin in the group %s'), $group->name ) . '</a>', $group_link, $group->name );
			}	
		break;
		
		case 'member_promoted_to_mod':
			$group_id = $item_id;
	
			$group = new BP_Groups_Group( $group_id, false, false );
			$group_link = bp_get_group_permalink( $group )  . '/?new';
			
			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_groups_multiple_member_promoted_to_mod_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '" title="' . __( 'Groups', 'buddypress' ) . '">' . sprintf( __('You were promoted to a mod in %d groups', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );		
			} else {
				return apply_filters( 'bp_groups_single_member_promoted_to_mod_notification', '<a href="' . $group_link . '">' . sprintf( __('You were promoted to a mod in the group %s'), $group->name ) . '</a>', $group_link, $group->name );
			}	
		break;
		
		case 'group_invite':
			$group_id = $item_id;

			$group = new BP_Groups_Group( $group_id, false, false );
			$user_url = bp_core_get_userurl( $user_id );
			
			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_groups_multiple_group_invite_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '/invites" title="' . __( 'Group Invites', 'buddypress' ) . '">' . sprintf( __('You have %d new group invitations', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );		
			} else {
				return apply_filters( 'bp_groups_single_group_invite_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '/invites" title="' . __( 'Group Invites', 'buddypress' ) . '">' . sprintf( __('You have an invitation to the group: %s', 'buddypress' ), $group->name ) . '</a>', $group->name );
			}	
		break;
	}

	do_action( 'groups_format_notifications', $action, $item_id, $secondary_item_id, $total_items );
	
	return false;
}


/**************************************************************************
 groups_update_last_activity()
 
 Sets groupmeta for the group with the last activity date for the group based
 on specific group activities.
 **************************************************************************/

function groups_update_last_activity( $group_id ) {
	groups_update_groupmeta( $group_id, 'last_activity', time() );
}
add_action( 'groups_deleted_wire_post', 'groups_update_last_activity' );
add_action( 'groups_new_wire_post', 'groups_update_last_activity' );
add_action( 'groups_joined_group', 'groups_update_last_activity' );
add_action( 'groups_leave_group', 'groups_update_last_activity' );
add_action( 'groups_created_group', 'groups_update_last_activity' );
add_action( 'groups_new_forum_topic', 'groups_update_last_activity' );
add_action( 'groups_new_forum_topic_post', 'groups_update_last_activity' );


/**************************************************************************
 groups_get_user_groups()
 
 Fetch the groups the current user is a member of.
 **************************************************************************/

function groups_get_user_groups( $pag_num, $pag_page ) {
	global $bp;
	
	$groups = BP_Groups_Member::get_group_ids( $bp->displayed_user->id, $pag_num, $pag_page );

	return array( 'groups' => $groups['ids'], 'total' => $groups['total'] );
}

function groups_get_recently_joined_for_user( $user_id = false, $pag_num = false, $pag_page = false, $filter = false ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->displayed_user->id;

	return BP_Groups_Member::get_recently_joined( $user_id, $pag_num, $pag_page, $filter );
}

function groups_get_most_popular_for_user( $user_id = false, $pag_num = false, $pag_page = false, $filter = false ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->displayed_user->id;

	return BP_Groups_Member::get_most_popular( $user_id, $pag_num, $pag_page, $filter );	
}

function groups_get_recently_active_for_user( $user_id = false, $pag_num = false, $pag_page = false, $filter = false ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->displayed_user->id;
		
	return BP_Groups_Member::get_recently_active( $user_id, $pag_num, $pag_page, $filter );
}

function groups_get_alphabetically_for_user( $user_id = false, $pag_num = false, $pag_page = false, $filter = false ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->displayed_user->id;

	return BP_Groups_Member::get_alphabetically( $user_id, $pag_num, $pag_page, $filter );	
}

function groups_get_user_is_admin_of( $user_id = false, $pag_num = false, $pag_page = false, $filter = false ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->displayed_user->id;
		
	return BP_Groups_Member::get_is_admin_of( $user_id, $pag_num, $pag_page, $filter );	
}

function groups_get_user_is_mod_of( $user_id = false, $pag_num = false, $pag_page = false, $filter = false ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->displayed_user->id;
		
	return BP_Groups_Member::get_is_mod_of( $user_id, $pag_num, $pag_page, $filter );	
}

function groups_total_groups_for_user( $user_id = false ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->displayed_user->id;
		
	return BP_Groups_Member::total_group_count( $user_id );
}

function groups_get_random_groups_for_user( $user_id = false, $total_groups = 5 ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->displayed_user->id;
		
	return BP_Groups_Member::get_random_groups( $user_id, $total_groups );
}


/**************************************************************************
 groups_avatar_upload()
 
 Handle uploading of a group avatar
**************************************************************************/

function groups_avatar_upload( $file ) {
	// validate the group avatar upload if there is one.
	$avatar_error = false;

	// Set friendly error feedback.
	$uploadErrors = array(
	        0 => __("There is no error, the file uploaded with success", 'buddypress'), 
	        1 => __("The uploaded file exceeds the upload_max_filesize directive in php.ini", 'buddypress'), 
	        2 => __("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form", 'buddypress'),
	        3 => __("The uploaded file was only partially uploaded", 'buddypress'),
	        4 => __("No file was uploaded", 'buddypress'),
	        6 => __("Missing a temporary folder", 'buddypress')
	);

	if ( !bp_core_check_avatar_upload($file) ) {
		$avatar_error = true;
		$avatar_error_msg = __('Your group avatar upload failed, please try again. Error was: ' . $uploadErrors[$file['file']['error']] , 'buddypress');
	}

	else if ( !bp_core_check_avatar_size($file) ) {
		$avatar_error = true;
		$avatar_size = size_format(1024 * CORE_MAX_FILE_SIZE);
		$avatar_error_msg = __('The file you uploaded is too big. Please upload a file under', 'buddypress') . size_format(CORE_MAX_FILE_SIZE);
	}
	
	else if ( !bp_core_check_avatar_type($file) ) {
		$avatar_error = true;
		$avatar_error_msg = __('Please upload only JPG, GIF or PNG photos.', 'buddypress');		
	}

	// "Handle" upload into temporary location
	else if ( !$original = bp_core_handle_avatar_upload($file) ) {
		$avatar_error = true;
		$avatar_error_msg = __('Upload Failed! Please check the permissions on the group avatar upload directory.', 'buddypress');						
	}
	
	if ( !$canvas = bp_core_resize_avatar($original) )
		$canvas = $original;
	
	if ( $avatar_error ) { ?>
		<div id="message" class="error">
			<p><?php echo $avatar_error_msg ?></p>
		</div>
		<?php
		bp_core_render_avatar_upload_form( '', true );
	} else {
		bp_core_render_avatar_cropper( $original, $canvas, null, null, false, $bp->loggedin_user->domain );
	}
}


/**************************************************************************
 groups_save_avatar()
 
 Save the avatar location urls into the DB for the group.
**************************************************************************/

function groups_get_avatar_hrefs( $avatars ) {
	global $bp;
	
	$src = $bp->root_domain . '/';

	$thumb_href = str_replace( ABSPATH, $src, stripslashes( $avatars['v1_out'] ) );
	$full_href = str_replace( ABSPATH, $src, stripslashes ( $avatars['v2_out'] ) );
	
	return array( 'thumb_href' => $thumb_href, 'full_href' => $full_href );
}

function groups_get_avatar_path( $avatar ) {
	global $bp;

	$src = $bp->root_domain . '/';

	$path = str_replace( $src, ABSPATH, stripslashes( $avatar ) );
	return $path;
}

function groups_search_groups( $search_terms, $pag_num_per_page = 5, $pag_page = 1, $sort_by = false, $order = false ) {
	return BP_Groups_Group::search_groups( $search_terms, $pag_num_per_page, $pag_page, $sort_by, $order );
}

function groups_filter_user_groups( $filter, $user_id = false, $order = false, $pag_num_per_page = 5, $pag_page = 1 ) {
	return BP_Groups_Group::filter_user_groups( $filter, $user_id, $order, $pag_num_per_page, $pag_page );
}

/**************************************************************************
 groups_create_group()
 
 Manage the creation of a group via the step by step wizard.
**************************************************************************/

function groups_create_group( $step, $group_id ) {
	global $bp, $create_group_step, $group_obj, $bbpress_live;

	if ( is_numeric( $step ) && ( 1 == (int)$step || 2 == (int)$step || 3 == (int)$step || 4 == (int)$step ) ) {
		
		if ( !$group_obj )
			$group_obj = new BP_Groups_Group( $group_id );		
			
		switch ( $step ) {
			case '1':		
				if ( !check_admin_referer( 'groups_step1_save' ) )
					return false;
				
				if ( $_POST['group-name'] != '' && $_POST['group-desc'] != '' ) {
					$group_obj->creator_id = $bp->loggedin_user->id;
					$group_obj->name = stripslashes($_POST['group-name']);
					$group_obj->description = stripslashes($_POST['group-desc']);
					$group_obj->news = stripslashes($_POST['group-news']);
					
					$slug = groups_check_slug( sanitize_title($_POST['group-name']) );

					$group_obj->slug = $slug;
					$group_obj->status = 'public';
					$group_obj->is_invitation_only = 0;
					$group_obj->enable_wire = 1;
					$group_obj->enable_forum = 1;
					$group_obj->enable_photos = 1;
					$group_obj->photos_admin_only = 0;
					$group_obj->date_created = time();
					
					if ( !$group_obj->save() )
						return false;

					// Save the creator as the group administrator
					$admin = new BP_Groups_Member( $bp->loggedin_user->id, $group_obj->id );
					$admin->is_admin = 1;
					$admin->user_title = __('Group Admin', 'buddypress');
					$admin->date_modified = time();
					$admin->inviter_id = 0;
					$admin->is_confirmed = 1;
					
					if ( !$admin->save() )
						return false;
					
					do_action( 'groups_create_group_step1_save' );
					
					/* Set groupmeta */
					groups_update_groupmeta( $group_obj->id, 'total_member_count', 1 );
					groups_update_groupmeta( $group_obj->id, 'last_activity', time() );
					groups_update_groupmeta( $group_obj->id, 'theme', 'buddypress' );
					groups_update_groupmeta( $group_obj->id, 'stylesheet', 'buddypress' );
															
					return $group_obj->id;
				}
				
				return false;
			break;
			
			case '2':
				if ( !check_admin_referer( 'groups_step2_save' ) )
					return false;

				$group_obj->status = 'public';
				$group_obj->is_invitation_only = 0;
				$group_obj->enable_wire = 1;
				$group_obj->enable_forum = 1;
				$group_obj->enable_photos = 1;
				$group_obj->photos_admin_only = 0;
				
				if ( !isset($_POST['group-show-wire']) )
					$group_obj->enable_wire = 0;
				
				if ( !isset($_POST['group-show-forum']) ) {
					$group_obj->enable_forum = 0;
				} else {
					/* Create the forum if enable_forum = 1 */
					if ( function_exists( 'bp_forums_setup' ) && '' == groups_get_groupmeta( $group_obj->id, 'forum_id' ) ) {
						groups_new_group_forum();
					}
				}
				
				if ( !isset($_POST['group-show-photos']) )
					$group_obj->enable_photos = 0;				
				
				if ( $_POST['group-photos-status'] != 'all' )
					$group_obj->photos_admin_only = 1;
				
				if ( 'private' == $_POST['group-status'] ) {
					$group_obj->status = 'private';
				} else if ( 'hidden' == $_POST['group-status'] ) {
					$group_obj->status = 'hidden';
				}
				
				if ( !$group_obj->save() )
					return false;

				/* Record in activity streams */
				groups_record_activity( array( 'item_id' => $group_obj->id, 'component_name' => $bp->groups->slug, 'component_action' => 'created_group', 'is_private' => 0 ) );
					
				do_action( 'groups_create_group_step2_save' );
					
				return $group_obj->id;
			break;
			
			case '3':
				if ( !check_admin_referer( 'groups_step3_save' ) )
					return false;
				
				if ( isset( $_POST['skip'] ) )
					return $group_obj->id;
				
				// Image already cropped and uploaded, lets store a reference in the DB.
				if ( !wp_verify_nonce($_POST['nonce'], 'slick_avatars') || !$result = bp_core_avatar_cropstore( $_POST['orig'], $_POST['canvas'], $_POST['v1_x1'], $_POST['v1_y1'], $_POST['v1_w'], $_POST['v1_h'], $_POST['v2_x1'], $_POST['v2_y1'], $_POST['v2_w'], $_POST['v2_h'], false, 'groupavatar', $group_obj->id ) )
					return false;

				// Success on group avatar cropping, now save the results.
				$avatar_hrefs = groups_get_avatar_hrefs($result);
				
				$group_obj->avatar_thumb = stripslashes( $avatar_hrefs['thumb_href'] );
				$group_obj->avatar_full = stripslashes( $avatar_hrefs['full_href'] );
				
				if ( !$group_obj->save() )
					return false;
				
				do_action( 'groups_create_group_step3_save' );
				
				return $group_obj->id;
			break;
			
			case '4':
				if ( !check_admin_referer( 'groups_step4_save' ) )
					return false;
					
				groups_send_invites( $group_obj, true );
				
				do_action( 'groups_created_group', $group_obj->id );
				
				return $group_obj->id;
			break;
		}
	}
	
	return false;
}

function groups_check_slug( $slug ) {
	global $bp;
	
	if ( in_array( $slug, $bp->groups->forbidden_names ) ) {
		$slug = $slug . '-' . rand();
	}
	
	if ( BP_Groups_Group::check_slug( $slug ) ) {
		do {
			$slug = $slug . '-' . rand();
		}
		while ( BP_Groups_Group::check_slug( $slug ) );
	}
	
	if ( 'wp' == substr( $slug, 0, 2 ) )
		$slug = substr( $slug, 2, strlen( $slug ) - 2 );
	
	return $slug;
}

function groups_get_slug( $group_id ) {
	$group = new BP_Groups_Group( $group_id, false, false );
	return $group->slug;
}

function groups_is_user_admin( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_admin( $user_id, $group_id );
}

function groups_is_user_mod( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_mod( $user_id, $group_id );
}

function groups_is_user_member( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_member( $user_id, $group_id );
}

function groups_is_user_banned( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_banned( $user_id, $group_id );
}

function groups_new_group_forum( $group_id = false, $group_name = false, $group_desc = false ) {
	global $group_obj;
	
	if ( !$group_id )
		$group_id = $group_obj->id;
	
	if ( !$group_name )
		$group_name = $group_obj->name;
	
	if ( !$group_desc )
		$group_desc = $group_obj->description;
	
	$forum = bp_forums_new_forum( apply_filters( 'groups_new_group_forum_name', $group_name . ' - ' . __( 'Forum', 'buddypress' ), $group_name ), apply_filters( 'groups_new_group_forum_desc', $group_desc ) );
	
	groups_update_groupmeta( $group_id, 'forum_id', $forum['forum_id'] );
	
	do_action( 'groups_new_group_forum', $forum, $group_id );
}

function groups_new_group_forum_post( $post_text, $topic_id ) {
	global $group_obj;

	/* Check the nonce */
	if ( !check_admin_referer( 'bp_forums_new_reply' ) ) 
		return false;
	
	if ( $forum_post = bp_forums_new_post( $post_text, $topic_id ) ) {
		bp_core_add_message( __( 'Reply posted successfully!', 'buddypress') );

		/* Record in activity streams */
		groups_record_activity( array( 'item_id' => $group_obj->id, 'component_name' => $bp->groups->slug, 'component_action' => 'new_forum_post', 'is_private' => 0, 'secondary_item_id' => $forum_post['post_id'] ) );
		
		do_action( 'groups_new_forum_topic_post', $group_obj->id, $forum_post );
		
		return $forum_post;
	}
	
	bp_core_add_message( __( 'There was an error posting that reply.', 'buddypress'), 'error' );					
	return false;
}

function groups_new_group_forum_topic( $topic_title, $topic_text, $topic_tags, $forum_id ) {
	global $group_obj;

	/* Check the nonce */	
	if ( !check_admin_referer( 'bp_forums_new_topic' ) ) 
		return false;
	
	if ( $topic = bp_forums_new_topic( $topic_title, $topic_text, $topic_tags, $forum_id ) ) {
		bp_core_add_message( __( 'Topic posted successfully!', 'buddypress') );

		/* Record in activity streams */
		groups_record_activity( array( 'item_id' => $group_obj->id, 'component_name' => $bp->groups->slug, 'component_action' => 'new_forum_topic', 'is_private' => 0, 'secondary_item_id' => $topic['topic_id'] ) );
		
		do_action( 'groups_new_forum_topic', $group_obj->id, $topic );
		
		return $topic;
	}
	
	bp_core_add_message( __( 'There was an error posting that topic.', 'buddypress'), 'error' );					
	return false;
}

function groups_invite_user( $user_id, $group_id ) {
	global $bp;

	/* Check the nonce */
	if ( !check_admin_referer( 'groups_invite_uninvite_user' ) )
		return false;
	
	if ( groups_is_user_member( $user_id, $group_id ) )
		return false;
	
	$invite = new BP_Groups_Member;
	$invite->group_id = $group_id;
	$invite->user_id = $user_id;
	$invite->date_modified = time();
	$invite->inviter_id = $bp->loggedin_user->id;
	$invite->is_confirmed = 0;
	
	if ( !$invite->save() )
		return false;
	
	do_action( 'groups_invite_user', $group_id, $user_id );
		
	return true;
}

function groups_uninvite_user( $user_id, $group_id, $skip_check = false ) {
	global $bp;

	/* Because this is called on groups_leave_group() and a nonce has already been
	 * checked, we need a way of overriding a double check.
	 */
	if ( !$skip_check ) {
		if ( !check_admin_referer( 'groups_invite_uninvite_user' ) )
			return false;
	}
	
	if ( !BP_Groups_Member::delete( $user_id, $group_id ) )
		return false;

	do_action( 'groups_uninvite_user', $group_id, $user_id );

	return true;
}

function groups_accept_invite( $user_id, $group_id ) {
	global $group_obj;

	/* Check the nonce */
	if ( !check_admin_referer( 'groups_accept_invite' ) )
		return false;
	
	if ( groups_is_user_member( $user_id, $group_id ) )
		return false;
	
	$member = new BP_Groups_Member( $user_id, $group_id );
	$member->accept_invite();

	if ( !$member->save() ) 
		return false;
	
	do_action( 'groups_accept_invite', $user_id, $group_id );
	return true;
}

function groups_reject_invite( $user_id, $group_id ) {
	if ( !BP_Groups_Member::delete( $user_id, $group_id ) )
		return false;
	
	do_action( 'groups_reject_invite', $user_id, $group_id );
	
	return true;
}

function groups_get_invites_for_group( $user_id, $group_id ) {
	return BP_Groups_Group::get_invites( $user_id, $group_id );
}

function groups_check_user_has_invite( $user_id, $group_id ) {
	return BP_Groups_Member::check_has_invite( $user_id, $group_id );
}

function groups_delete_invite( $user_id, $group_id ) {
	global $bp;
	
	$delete = BP_Groups_Member::delete_invite( $user_id, $group_id );
	
	if ( $delete )
		bp_core_delete_notifications_for_user_by_item_id( $user_id, $group_id, $bp->groups->slug, 'group_invite' );
	
	return $delete;
}

function groups_get_invites_for_user( $user_id = false ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;
	
	return BP_Groups_Member::get_invites( $user_id );
}

function groups_send_invites( $group_obj, $skip_check = false ) {
	global $bp;

	if ( !$skip_check ) {
		if ( !check_admin_referer( 'groups_send_invites', '_wpnonce_send_invites' ) )
			return false;
	}
	
	require_once ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-notifications.php' );

	// Send friend invites.
	$invited_users = groups_get_invites_for_group( $bp->loggedin_user->id, $group_obj->id );
	
	for ( $i = 0; $i < count( $invited_users ); $i++ ) {
		$member = new BP_Groups_Member( $invited_users[$i], $group_obj->id );
		
		// Send the actual invite
		groups_notification_group_invites( $group_obj, $member, $bp->loggedin_user->id );
		
		$member->invite_sent = 1;
		$member->save();
	}
	
	do_action( 'groups_send_invites', $group_obj->id, $invited_users );
}

function groups_delete_all_group_invites( $group_id ) {
	return BP_Groups_Group::delete_all_invites( $group_id );
}

function groups_check_group_exists( $group_id ) {
	return BP_Groups_Group::group_exists( $group_id );
}

function groups_leave_group( $group_id, $user_id = false ) {
	global $bp;
	
	/* Check the nonce */	
	if ( !check_admin_referer( 'groups_leave_group' ) )
		return false;
	
	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;
	
	// Admins cannot leave a group, that is until promotion to admin support is implemented.
	if ( groups_is_group_admin( $user_id, $group_id ) )
		return false;
		
	// This is exactly the same as deleting and invite, just is_confirmed = 1 NOT 0.
	if ( !groups_uninvite_user( $user_id, $group_id, true ) )
		return false;

	do_action( 'groups_leave_group', $group_id, $bp->loggedin_user->id );

	/* Modify group member count */
	groups_update_groupmeta( $group_id, 'total_member_count', (int) groups_get_groupmeta( $group_id, 'total_member_count') - 1 );
	
	return true;
}

function groups_join_group( $group_id, $user_id = false ) {
	global $bp;
	
	/* Check the nonce */
	if ( !check_admin_referer( 'groups_join_group' ) ) 
		return false;
		
	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	if ( groups_check_user_has_invite( $user_id, $group_id ) )
		groups_delete_invite( $user_id, $group_id );
	
	$new_member = new BP_Groups_Member;
	$new_member->group_id = $group_id;
	$new_member->user_id = $user_id;
	$new_member->inviter_id = 0;
	$new_member->is_admin = 0;
	$new_member->user_title = '';
	$new_member->date_modified = time();
	$new_member->is_confirmed = 1;
	
	if ( !$new_member->save() )
		return false;

	/* Record this in activity streams */
	groups_record_activity( array( 'item_id' => $new_member->group_id, 'component_name' => $bp->groups->slug, 'component_action' => 'joined_group', 'is_private' => 0 ) );
	
	/* Modify group meta */
	groups_update_groupmeta( $group_id, 'total_member_count', (int) groups_get_groupmeta( $group_id, 'total_member_count') + 1 );
	groups_update_groupmeta( $group_id, 'last_activity', time() );

	do_action( 'groups_join_group', $group_id, $bp->loggedin_user->id );

	return true;
}

function groups_get_group_admins( $group_id ) {
	return BP_Groups_Member::get_group_administrator_ids( $group_id );
}

function groups_get_group_mods( $group_id ) {
	return BP_Groups_Member::get_group_moderator_ids( $group_id );
}

function groups_get_group_members( $group_id, $limit = false, $page = false ) {
	return BP_Groups_Member::get_all_for_group( $group_id, $limit, $page );
}
	function groups_get_group_users( $group_id, $limit = false, $page = false, $deprecated_function = true ) {
		return groups_get_group_members( $group_id, $limit, $page );
	}

function groups_is_group_admin( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_admin( $user_id, $group_id );
}

function groups_is_group_mod( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_mod( $user_id, $group_id );
}

function groups_new_wire_post( $group_id, $content ) {
	global $group_obj, $bp;

	/* Check the nonce first. */
	if ( !check_admin_referer( 'bp_wire_post' ) ) 
		return false;

	$private = false;
	if ( $group_obj->status != 'public' )
		$private = true;
	
	if ( $wire_post_id = bp_wire_new_post( $group_id, $content, $bp->groups->slug, $private ) ) {
		do_action( 'groups_new_wire_post', $group_id, $wire_post_id );
		
		return true;
	}
	
	return false;
}

function groups_delete_wire_post( $wire_post_id, $table_name ) {
	global $bp;
	
	/* Check the nonce first. */
	if ( !check_admin_referer( 'bp_wire_delete_link' ) )
		return false;
	
	if ( bp_wire_delete_post( $wire_post_id, $bp->groups->slug, $table_name ) ) {		
		do_action( 'groups_deleted_wire_post', $wire_post_id );
		return true;
	}
	
	return false;
}

function groups_edit_base_group_details( $group_id, $group_name, $group_desc, $group_news, $notify_members ) {
	global $bp;
	
	/* Check the nonce first. */
	if ( !check_admin_referer( 'groups_edit_group_details' ) )
		return false;
	
	if ( empty( $group_name ) || empty( $group_desc ) )
		return false;
	
	$group = new BP_Groups_Group( $group_id, false, false );
	$group->name = $group_name;
	$group->description = $group_desc;
	$group->news = $group_news;

	if ( !$group->save() )
		return false;

	if ( $notify_members ) {
		require_once ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-notifications.php' );
		groups_notification_group_updated( $group->id );
	}
		
	do_action( 'groups_details_updated', $group->id );
	
	return true;
}

function groups_edit_group_settings( $group_id, $enable_wire, $enable_forum, $enable_photos, $photos_admin_only, $status ) {
	global $bp;
	
	/* Check the nonce first. */
	if ( !check_admin_referer( 'groups_edit_group_settings' ) )
		return false;
	
	$group = new BP_Groups_Group( $group_id, false, false );
	$group->enable_wire = $enable_wire;
	$group->enable_forum = $enable_forum;
	$group->enable_photos = $enable_photos;
	$group->photos_admin_only = $photos_admin_only;
	$group->status = $status;
	
	if ( !$group->save() )
		return false;
	
	/* If forums have been enabled, and a forum does not yet exist, we need to create one. */
	if ( $group->enable_forum ) {
		if ( function_exists( 'bp_forums_setup' ) && '' == groups_get_groupmeta( $group->id, 'forum_id' ) ) {
			groups_new_group_forum( $group->id, $group->name, $group->description );
		}
	}
	
	do_action( 'groups_settings_updated', $group->id );
	
	return true;
}

function groups_promote_member( $user_id, $group_id ) {
	global $bp;

	/* Check the nonce first. */
	if ( !check_admin_referer( 'groups_promote_member' ) )
		return false;
	
	if ( !$bp->is_item_admin )
		return false;
		
	$member = new BP_Groups_Member( $user_id, $group_id );

	do_action( 'groups_premote_member', $user_id, $group_id );
	
	return $member->promote();
}

function groups_demote_member( $user_id, $group_id ) {
	global $bp;

	/* Check the nonce first. */
	if ( !check_admin_referer( 'groups_demote_member' ) )
		return false;
	
	if ( !$bp->is_item_admin )
		return false;
		
	$member = new BP_Groups_Member( $user_id, $group_id );
	
	do_action( 'groups_demote_member', $user_id, $group_id );

	return $member->demote();
}

function groups_ban_member( $user_id, $group_id ) {
	global $bp;

	/* Check the nonce first. */
	if ( !check_admin_referer( 'groups_ban_member' ) )
		return false;
	
	if ( !$bp->is_item_admin )
		return false;
		
	$member = new BP_Groups_Member( $user_id, $group_id );

	do_action( 'groups_ban_member', $user_id, $group_id );
	
	return $member->ban();
}

function groups_unban_member( $user_id, $group_id ) {
	global $bp;

	/* Check the nonce first. */
	if ( !check_admin_referer( 'groups_unban_member' ) )
		return false;
	
	if ( !$bp->is_item_admin )
		return false;
		
	$member = new BP_Groups_Member( $user_id, $group_id );
	
	do_action( 'groups_unban_member', $user_id, $group_id );
	
	return $member->unban();
}

function groups_send_membership_request( $requesting_user_id, $group_id ) {
	global $bp;

	/* Check the nonce first. */
	if ( !check_admin_referer( 'groups_request_membership' ) )
		return false;

	$requesting_user = new BP_Groups_Member;
	$requesting_user->group_id = $group_id;
	$requesting_user->user_id = $requesting_user_id;
	$requesting_user->inviter_id = 0;
	$requesting_user->is_admin = 0;
	$requesting_user->user_title = '';
	$requesting_user->date_modified = time();
	$requesting_user->is_confirmed = 0;
	$requesting_user->comments = $_POST['group-request-membership-comments'];
	
	if ( $requesting_user->save() ) {
		$admins = groups_get_group_admins( $group_id );

		require_once ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-notifications.php' );

		for ( $i = 0; $i < count( $admins ); $i++ ) {
			// Saved okay, now send the email notification
			groups_notification_new_membership_request( $requesting_user_id, $admins[$i]->user_id, $group_id, $requesting_user->id );
		}
		
		do_action( 'groups_membership_requested', $requesting_user_id, $admins, $group_id, $requesting_user->id );
	
		return true;
	}
	
	return false;
}

function groups_accept_membership_request( $membership_id ) {
	global $bp;
	
	/* Check the nonce first. */
	if ( !check_admin_referer( 'groups_accept_membership_request' ) )
		return false;

	$membership = new BP_Groups_Member( false, false, $membership_id );
	$membership->accept_request();
	
	if ( !$membership->save() )
		return false;
		
	/* Modify group member count */
	groups_update_groupmeta( $membership->group_id, 'total_member_count', (int) groups_get_groupmeta( $membership->group_id, 'total_member_count') + 1 );
	
	/* Record this in activity streams */
	groups_record_activity( array( 'item_id' => $membership->group_id, 'component_name' => $bp->groups->slug, 'component_action' => 'joined_group', 'is_private' => 0 ) );

	/* Send a notification to the user. */
	require_once ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-notifications.php' );
	groups_notification_membership_request_completed( $membership->user_id, $membership->group_id, true );
	
	do_action( 'groups_membership_accepted', $membership->user_id, $membership->group_id );
	
	return true;
}

function groups_reject_membership_request( $membership_id ) {

	/* Check the nonce first. */
	if ( !check_admin_referer( 'groups_reject_membership_request' ) )
		return false;
		
	$membership = new BP_Groups_Member( false, false, $membership_id );
	
	if ( !BP_Groups_Member::delete( $membership->user_id, $membership->group_id ) )
		return false;
	
	// Send a notification to the user.
	require_once ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-notifications.php' );
	groups_notification_membership_request_completed( $membership->user_id, $membership->group_id, false );
	
	do_action( 'groups_membership_rejected', $membership->user_id, $membership->group_id );
	
	return true;
}

function groups_redirect_to_random_group() {
	global $bp, $wpdb;
	
	if ( $bp->current_component == $bp->groups->slug && isset( $_GET['random'] ) ) {
		$group = groups_get_random_group();

		bp_core_redirect( $bp->root_domain . '/' . $bp->groups->slug . '/' . $group['groups'][0]->slug );
	}
}
add_action( 'wp', 'groups_redirect_to_random_group', 6 );

function groups_delete_group( $group_id ) {
	global $bp;

	/* Check the nonce first. */
	if ( !check_admin_referer( 'groups_delete_group' ) )
		return false;
	
	// Check the user is the group admin.
	if ( !$bp->is_item_admin )
		return false;
	
	// Get the group object
	$group = new BP_Groups_Group( $group_id );
	
	if ( !$group->delete() )
		return false;
	
	// Remove the activity stream item
	groups_delete_activity( array( 'item_id' => $group_id, 'component_name' => $bp->groups->slug, 'component_action' => 'created_group', 'user_id' => $bp->loggedin_user->id ) );
 
	// Remove all outstanding invites for this group
	groups_delete_all_group_invites( $group_id );

	// Remove all notifications for any user belonging to this group
	bp_core_delete_all_notifications_by_type( $group_id, $bp->groups->slug );
	
	do_action( 'groups_delete_group', $group_id );
	
	return true;
}

function groups_check_for_membership_request( $user_id, $group_id ) {
	return BP_Groups_Member::check_for_membership_request( $user_id, $group_id );
}

function groups_get_newest( $limit = null, $page = 1 ) {
	return BP_Groups_Group::get_newest( $limit, $page );
}

function groups_get_active( $limit = null, $page = 1 ) {
	return BP_Groups_Group::get_active( $limit, $page );
}

function groups_get_popular( $limit = null, $page = 1 ) {
	return BP_Groups_Group::get_popular( $limit, $page );
}

function groups_get_all( $limit = null, $page = 1, $only_public = true, $sort_by = false, $order = false ) {
	return BP_Groups_Group::get_all( $limit, $page, $only_public, $sort_by, $order );
}

function groups_get_random_group() {
	return BP_Groups_Group::get_random();
}

//
// Group meta functions
//

function groups_delete_groupmeta( $group_id, $meta_key = false, $meta_value = false ) {
	global $wpdb, $bp;
	
	if ( !is_numeric( $group_id ) )
		return false;
		
	$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

	if ( is_array($meta_value) || is_object($meta_value) )
		$meta_value = serialize($meta_value);
		
	$meta_value = trim( $meta_value );

	if ( !$meta_key ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp->groups->table_name_groupmeta . " WHERE group_id = %d", $group_id ) );		
	} else if ( $meta_value ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp->groups->table_name_groupmeta . " WHERE group_id = %d AND meta_key = %s AND meta_value = %s", $group_id, $meta_key, $meta_value ) );
	} else {
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $bp->groups->table_name_groupmeta . " WHERE group_id = %d AND meta_key = %s", $group_id, $meta_key ) );
	}
	
	// TODO need to look into using this.
	// wp_cache_delete($group_id, 'groups');

	return true;
}

function groups_get_groupmeta( $group_id, $meta_key = '') {
	global $wpdb, $bp;
	
	$group_id = (int) $group_id;

	if ( !$group_id )
		return false;

	if ( !empty($meta_key) ) {
		$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);
		
		// TODO need to look into using this.
		//$user = wp_cache_get($user_id, 'users');
		
		// Check the cached user object
		//if ( false !== $user && isset($user->$meta_key) )
		//	$metas = array($user->$meta_key);
		//else
		$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM " . $bp->groups->table_name_groupmeta . " WHERE group_id = %d AND meta_key = %s", $group_id, $meta_key) );
	} else {
		$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM " . $bp->groups->table_name_groupmeta . " WHERE group_id = %d", $group_id) );
	}

	if ( empty($metas) ) {
		if ( empty($meta_key) )
			return array();
		else
			return '';
	}

	$metas = array_map('maybe_unserialize', $metas);

	if ( 1 == count($metas) )
		return $metas[0];
	else
		return $metas;
}

function groups_update_groupmeta( $group_id, $meta_key, $meta_value ) {
	global $wpdb, $bp;
	
	if ( !is_numeric( $group_id ) )
		return false;
	
	$meta_key = preg_replace( '|[^a-z0-9_]|i', '', $meta_key );
	$meta_value = (string)$meta_value;

	if ( is_string($meta_value) )
		$meta_value = stripslashes($wpdb->escape($meta_value));
	
	$meta_value = maybe_serialize($meta_value);
	
	if (empty($meta_value)) {
		return groups_delete_groupmeta( $group_id, $meta_key );
	}

	$cur = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $bp->groups->table_name_groupmeta . " WHERE group_id = %d AND meta_key = %s", $group_id, $meta_key ) );

	if ( !$cur ) {
		$wpdb->query( $wpdb->prepare( "INSERT INTO " . $bp->groups->table_name_groupmeta . " ( group_id, meta_key, meta_value ) VALUES ( %d, %s, %s )", $group_id, $meta_key, $meta_value ) );
	} else if ( $cur->meta_value != $meta_value ) {
		$wpdb->query( $wpdb->prepare( "UPDATE " . $bp->groups->table_name_groupmeta . " SET meta_value = %s WHERE group_id = %d AND meta_key = %s", $meta_value, $group_id, $meta_key ) );
	} else {
		return false;
	}

	// TODO need to look into using this.
	// wp_cache_delete($user_id, 'users');

	return true;
}

// The following two functions will force the active member theme for
// groups pages, even though they are technically under the root "home" blog
// from a WordPress point of view.

function groups_force_buddypress_theme( $template ) {
	global $bp;
	
	if ( $bp->current_component != $bp->groups->slug )
		return $template;
	
	$member_theme = get_site_option('active-member-theme');
	
	if ( empty($member_theme) )
		$member_theme = 'bpmember';

	add_filter( 'theme_root', 'bp_core_set_member_theme_root' );
	add_filter( 'theme_root_uri', 'bp_core_set_member_theme_root_uri' );

	return $member_theme;
}
add_filter( 'template', 'groups_force_buddypress_theme' );

function groups_force_buddypress_stylesheet( $stylesheet ) {
	global $bp;

	if ( $bp->current_component != $bp->groups->slug )
		return $stylesheet;

	$member_theme = get_site_option('active-member-theme');
	
	if ( empty( $member_theme ) )
		$member_theme = 'bpmember';
	
	add_filter( 'theme_root', 'bp_core_set_member_theme_root' );
	add_filter( 'theme_root_uri', 'bp_core_set_member_theme_root_uri' );
	
	return $member_theme;
}
add_filter( 'stylesheet', 'groups_force_buddypress_stylesheet', 1, 1 );

function groups_remove_data( $user_id ) {
	BP_Groups_Member::delete_all_for_user($user_id);
	
	do_action( 'groups_remove_data', $user_id );
}
add_action( 'wpmu_delete_user', 'groups_remove_data', 1 );
add_action( 'delete_user', 'groups_remove_data', 1 );


function groups_clear_group_object_cache( $group_id ) {
	wp_cache_delete( 'groups_group_nouserdata_' . $group_id, 'bp' );
	wp_cache_delete( 'groups_group_' . $group_id, 'bp' );
	wp_cache_delete( 'newest_groups', 'bp' );
	wp_cache_delete( 'active_groups', 'bp' );
	wp_cache_delete( 'popular_groups', 'bp' );
	wp_cache_delete( 'groups_random_groups', 'bp' );
}

// List actions to clear object caches on
add_action( 'groups_group_deleted', 'groups_clear_group_object_cache' );
add_action( 'groups_settings_updated', 'groups_clear_group_object_cache' );
add_action( 'groups_details_updated', 'groups_clear_group_object_cache' );
add_action( 'groups_group_avatar_updated', 'groups_clear_group_object_cache' );

// List actions to clear super cached pages on, if super cache is installed
add_action( 'groups_new_wire_post', 'bp_core_clear_cache' );
add_action( 'groups_deleted_wire_post', 'bp_core_clear_cache' );
add_action( 'groups_join_group', 'bp_core_clear_cache' );
add_action( 'groups_leave_group', 'bp_core_clear_cache' );
add_action( 'groups_accept_invite', 'bp_core_clear_cache' );
add_action( 'groups_reject_invite', 'bp_core_clear_cache' );
add_action( 'groups_invite_user', 'bp_core_clear_cache' );
add_action( 'groups_uninvite_user', 'bp_core_clear_cache' );
add_action( 'groups_details_updated', 'bp_core_clear_cache' );
add_action( 'groups_settings_updated', 'bp_core_clear_cache' );
add_action( 'groups_unban_member', 'bp_core_clear_cache' );
add_action( 'groups_ban_member', 'bp_core_clear_cache' );
add_action( 'groups_demote_member', 'bp_core_clear_cache' );
add_action( 'groups_premote_member', 'bp_core_clear_cache' );
add_action( 'groups_membership_rejected', 'bp_core_clear_cache' );
add_action( 'groups_membership_accepted', 'bp_core_clear_cache' );
add_action( 'groups_membership_requested', 'bp_core_clear_cache' );
add_action( 'groups_create_group_step1_save', 'bp_core_clear_cache' );
add_action( 'groups_create_group_step2_save', 'bp_core_clear_cache' );
add_action( 'groups_create_group_step3_save', 'bp_core_clear_cache' );
add_action( 'groups_created_group', 'bp_core_clear_cache' );
add_action( 'groups_group_avatar_updated', 'bp_core_clear_cache' );

?>