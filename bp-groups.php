<?php

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
			enable_wire tinyint(1) NOT NULL DEFAULT '1',
			enable_forum tinyint(1) NOT NULL DEFAULT '1',
			date_created datetime NOT NULL,
			avatar_thumb varchar(250) NOT NULL,
			avatar_full varchar(250) NOT NULL,
		    KEY creator_id (creator_id),
		    KEY status (status),
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
	
	$bp->groups->group_creation_steps = apply_filters( 'groups_create_group_steps', array( 
		'group-details' => array( 'name' => __( 'Group Details', 'buddypress' ), 'position' => 0 ), 
		'group-settings' => array( 'name' => __( 'Group Settings', 'buddypress' ), 'position' => 10 ),
		'group-avatar' => array( 'name' => __( 'Group Avatar', 'buddypress' ), 'position' => 20 ),
		'group-invites' => array( 'name' => __( 'Group Invites', 'buddypress' ), 'position' => 30 )
	) );
	
	$bp->groups->valid_status = apply_filters( 'groups_valid_status', array( 'public', 'private', 'hidden' ) );
	
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

function groups_setup_nav() {
	global $bp, $current_blog, $group_obj;
	
	if ( $group_id = BP_Groups_Group::group_exists($bp->current_action) ) {
		
		/* This is a single group page. */
		$bp->is_single_item = true;
		$bp->groups->current_group = &new BP_Groups_Group( $group_id );
	
		/* Using "item" not "group" for generic support in other components. */
		if ( is_site_admin() )
			$bp->is_item_admin = 1;
		else
			$bp->is_item_admin = groups_is_user_admin( $bp->loggedin_user->id, $bp->groups->current_group->id );
		
		/* If the user is not an admin, check if they are a moderator */
		if ( !$bp->is_item_admin )
			$bp->is_item_mod = groups_is_user_mod( $bp->loggedin_user->id, $bp->groups->current_group->id );
		
		/* Is the logged in user a member of the group? */
		$bp->groups->current_group->is_user_member = ( is_user_logged_in() && groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) ? true : false;
	
		/* Should this group be visible to the logged in user? */
		$bp->groups->current_group->is_group_visible_to_member = ( 'public' == $bp->groups->current_group->status || $is_member ) ? true : false;
		
		/* Pre 1.1 backwards compatibility - use $bp->groups->current_group instead */
		$group_obj = &$bp->groups->current_group;
	}

	/* Add 'Groups' to the main navigation */
	bp_core_new_nav_item( array( 'name' => __('Groups', 'buddypress'), 'slug' => $bp->groups->slug, 'position' => 70, 'screen_function' => 'groups_screen_my_groups', 'default_subnav_slug' => 'my-groups' ) );
	
	$groups_link = $bp->loggedin_user->domain . $bp->groups->slug . '/';
	
	/* Add the subnav items to the groups nav item */
	bp_core_new_subnav_item( array( 'name' => __( 'My Groups', 'buddypress' ), 'slug' => 'my-groups', 'parent_url' => $groups_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_my_groups', 'position' => 10, 'item_css_id' => 'groups-my-groups' ) );
	bp_core_new_subnav_item( array( 'name' => __( 'Create a Group', 'buddypress' ), 'slug' => 'create', 'parent_url' => $groups_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_create_group', 'position' => 20, 'user_has_access' => bp_is_home() ) );
	bp_core_new_subnav_item( array( 'name' => __( 'Invites', 'buddypress' ), 'slug' => 'invites', 'parent_url' => $groups_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_invites', 'position' => 30, 'user_has_access' => bp_is_home() ) );

	if ( $bp->current_component == $bp->groups->slug ) {
		
		if ( bp_is_home() && !$bp->is_single_item ) {
			
			$bp->bp_options_title = __( 'My Groups', 'buddypress' );
			
		} else if ( !bp_is_home() && !$bp->is_single_item ) {

			$bp->bp_options_avatar = bp_core_get_avatar( $bp->displayed_user->id, 1 );
			$bp->bp_options_title = $bp->displayed_user->fullname;
			
		} else if ( $bp->is_single_item ) {
			// We are viewing a single group, so set up the
			// group navigation menu using the $bp->groups->current_group global.
			
			/* When in a single group, the first action is bumped down one because of the
			   group name, so we need to adjust this and set the group name to current_item. */
			$bp->current_item = $bp->current_action;
			$bp->current_action = $bp->action_variables[0];
			array_shift($bp->action_variables);
									
			$bp->bp_options_title = $bp->groups->current_group->name;
			$bp->bp_options_avatar = '<img src="' . $bp->groups->current_group->avatar_thumb . '" alt="Group Avatar Thumbnail" />';
			
			$group_link = $bp->root_domain . '/' . $bp->groups->slug . '/' . $bp->groups->current_group->slug . '/';
			
			// If this is a private or hidden group, does the user have access?
			if ( 'private' == $bp->groups->current_group->status || 'hidden' == $bp->groups->current_group->status ) {
				if ( $bp->groups->current_group->is_user_member && is_user_logged_in() )
					$bp->groups->current_group->user_has_access = true;
				else
					$bp->groups->current_group->user_has_access = false;
			} else {
				$bp->groups->current_group->user_has_access = true;
			}

			/* Reset the existing subnav items */
			bp_core_reset_subnav_items($bp->groups->slug);
			
			/* Add a new default subnav item for when the groups nav is selected. */
			bp_core_add_nav_default( $bp->groups->slug, 'groups_screen_group_home', 'home' );
			
			/* Add the "Home" subnav item, as this will always be present */
			bp_core_new_subnav_item( array( 'name' => __( 'Home', 'buddypress' ), 'slug' => 'home', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_home', 'position' => 10, 'item_css_id' => 'group-home' ) );
			
			/* If the user is a group mod or more, then show the group admin nav item */
			if ( $bp->is_item_mod || $bp->is_item_admin )
				bp_core_new_subnav_item( array( 'name' => __( 'Admin', 'buddypress' ), 'slug' => 'admin', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_admin', 'position' => 20, 'user_has_access' => ( $bp->is_item_admin + (int)$bp->is_item_mod ), 'item_css_id' => 'group-admin' ) );

			// If this is a private group, and the user is not a member, show a "Request Membership" nav item.
			if ( is_user_logged_in() && !$bp->groups->current_group->is_user_member && !groups_check_for_membership_request( $bp->loggedin_user->id, $bp->groups->current_group->id ) && $bp->groups->current_group->status == 'private' )
				bp_core_new_subnav_item( array( 'name' => __( 'Request Membership', 'buddypress' ), 'slug' => 'request-membership', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_request_membership', 'position' => 30 ) );

			if ( $bp->groups->current_group->enable_forum && function_exists('bp_forums_setup') )
				bp_core_new_subnav_item( array( 'name' => __( 'Forum', 'buddypress' ), 'slug' => 'group-forum', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_forum', 'position' => 40, 'user_has_access' => $bp->groups->current_group->user_has_access ) );

			if ( $bp->groups->current_group->enable_wire && function_exists('bp_wire_install') )
				bp_core_new_subnav_item( array( 'name' => __( 'Wire', 'buddypress' ), 'slug' => 'group-wire', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_wire', 'position' => 50, 'user_has_access' => $bp->groups->current_group->user_has_access ) );

			bp_core_new_subnav_item( array( 'name' => __( 'Members', 'buddypress' ), 'slug' => 'group-members', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_members', 'position' => 60, 'user_has_access' => $bp->groups->current_group->user_has_access ) );
			
			if ( is_user_logged_in() && groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {
				if ( function_exists('friends_install') )
					bp_core_new_subnav_item( array( 'name' => __( 'Send Invites', 'buddypress' ), 'slug' => 'send-invites', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_invite', 'item_css_id' => 'group-invite', 'position' => 70, 'user_has_access' => $bp->groups->current_group->user_has_access ) );

				bp_core_new_subnav_item( array( 'name' => __( 'Leave Group', 'buddypress' ), 'slug' => 'leave-group', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_leave', 'item_css_id' => 'group-leave', 'position' => 110, 'user_has_access' => $bp->groups->current_group->user_has_access ) );
			}
		}
	}
	
	do_action( 'groups_setup_nav', $bp->groups->current_group->user_has_access );
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
add_action( 'wp', 'groups_directory_groups_setup', 2 );


/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

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
		/* Check the nonce */
		if ( !check_admin_referer( 'groups_accept_invite' ) )
			return false;
		
		if ( !groups_accept_invite( $bp->loggedin_user->id, $group_id ) ) {
			bp_core_add_message( __('Group invite could not be accepted', 'buddypress'), 'error' );				
		} else {
			bp_core_add_message( __('Group invite accepted', 'buddypress') );
			
			/* Record this in activity streams */
			groups_record_activity( array( 'item_id' => $group_id, 'component_name' => $bp->groups->slug, 'component_action' => 'joined_group', 'is_private' => 0 ) );
		}

		bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component . '/' . $bp->current_action );
		
	} else if ( isset($bp->action_variables) && in_array( 'reject', $bp->action_variables ) && is_numeric($group_id) ) {
		/* Check the nonce */
		if ( !check_admin_referer( 'groups_reject_invite' ) )
			return false;
					
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
	
	if ( file_exists( TEMPLATEPATH . '/groups/invites.php' ) )
		bp_core_load_template( apply_filters( 'groups_template_group_invites', 'groups/invites' ) );
	else
		bp_core_load_template( apply_filters( 'groups_template_group_invites', 'groups/list-invites' ) );		
}

function groups_screen_create_group() {
	global $bp;
	
	$bp->groups->completed_create_steps = array();

	/* If no current step is set, reset everything so we can start a fresh group creation */
	if ( !$bp->groups->current_create_step = $bp->action_variables[1] ) {
		unset( $bp->groups->current_create_step );
		unset( $bp->groups->completed_create_steps );
		
		setcookie( 'bp_new_group_id', false, time() - 1000, COOKIEPATH );
		setcookie( 'bp_completed_create_steps', false, time() - 1000, COOKIEPATH );
		
		$reset_steps = true;
		bp_core_redirect( $bp->loggedin_user->domain . $bp->groups->slug . '/create/step/' . array_shift( array_keys( $bp->groups->group_creation_steps )  ) );
	}
	
	/* If this is a creation step that is not recognized, just redirect them back to the first screen */
	if ( $bp->action_variables[1] && !$bp->groups->group_creation_steps[$bp->action_variables[1]] ) {
		bp_core_add_message( __('There was an error saving group details. Please try again.', 'buddypress'), 'error' );
		bp_core_redirect( $bp->loggedin_user->domain . $bp->groups->slug . '/create' );
	}

	/* Fetch the currently completed steps variable */
	if ( isset( $_COOKIE['bp_completed_create_steps'] ) && !$reset_steps )
		$bp->groups->completed_create_steps = unserialize( stripslashes( $_COOKIE['bp_completed_create_steps'] ) );

	/* Set the ID of the new group, if it has already been created in a previous step */
	if ( isset( $_COOKIE['bp_new_group_id'] ) )
		$bp->groups->new_group_id = $_COOKIE['bp_new_group_id'];
	
	/* If the save button is hit, lets calculate what we need to save */
	if ( isset( $_POST['save'] ) || isset( $_POST['skip'] ) ) {
		
		/* Check the nonce */
		check_admin_referer( 'groups_create_save_' . $bp->groups->current_create_step );
		
		if ( 'group-details' == $bp->groups->current_create_step ) {
			if ( empty( $_POST['group-name'] ) || empty( $_POST['group-desc'] ) ) {
				bp_core_add_message( __( 'Please fill in all of the required fields', 'buddypress' ), 'error' );
				bp_core_redirect( $bp->loggedin_user->domain . $bp->groups->slug . '/create/step/' . $bp->groups->current_create_step );
			}
			
			if ( !$bp->groups->new_group_id = groups_create_group( array( 'group_id' => $bp->groups->new_group_id, 'name' => $_POST['group-name'], 'description' => $_POST['group-desc'], 'news' => $_POST['group-news'], 'slug' => groups_check_slug( sanitize_title($_POST['group-name']) ) ) ) ) {
				bp_core_add_message( __( 'There was an error saving group details, please try again.', 'buddypress' ), 'error' );
				bp_core_redirect( $bp->loggedin_user->domain . $bp->groups->slug . '/create/step/' . $bp->groups->current_create_step );				
			}
			
			groups_update_groupmeta( $bp->groups->new_group_id, 'total_member_count', 1 );
			groups_update_groupmeta( $bp->groups->new_group_id, 'last_activity', time() );
			groups_update_groupmeta( $bp->groups->new_group_id, 'theme', 'buddypress' );
			groups_update_groupmeta( $bp->groups->new_group_id, 'stylesheet', 'buddypress' );
		}
		
		if ( 'group-settings' == $bp->groups->current_create_step ) {
			$group_status = 'public';
			$group_enable_wire = 1;
			$group_enable_forum = 1;
			
			if ( !isset($_POST['group-show-wire']) )
				$group_enable_wire = 0;
			
			if ( !isset($_POST['group-show-forum']) ) {
				$group_enable_forum = 0;
			} else {
				/* Create the forum if enable_forum = 1 */
				if ( function_exists( 'bp_forums_setup' ) && '' == groups_get_groupmeta( $bp->groups->new_group_id, 'forum_id' ) ) {
					groups_new_group_forum();
				}
			}
			
			if ( 'private' == $_POST['group-status'] )
				$group_status = 'private';
			else if ( 'hidden' == $_POST['group-status'] )
				$group_status = 'hidden';

			if ( !$bp->groups->new_group_id = groups_create_group( array( 'group_id' => $bp->groups->new_group_id, 'status' => $group_status, 'enable_wire' => $group_enable_wire, 'enable_forum' => $group_enable_forum ) ) ) {
				bp_core_add_message( __( 'There was an error saving group details, please try again.', 'buddypress' ), 'error' );
				bp_core_redirect( $bp->loggedin_user->domain . $bp->groups->slug . '/create/step/' . $bp->groups->current_create_step );				
			}
		}
		
		if ( 'group-avatar' == $bp->groups->current_create_step ) {
			if ( !isset( $_POST['skip'] ) && $_POST['orig'] ) {
				// Image already cropped and uploaded, lets store a reference in the DB.
				if ( !wp_verify_nonce($_POST['nonce'], 'slick_avatars') || !$result = bp_core_avatar_cropstore( $_POST['orig'], $_POST['canvas'], $_POST['v1_x1'], $_POST['v1_y1'], $_POST['v1_w'], $_POST['v1_h'], $_POST['v2_x1'], $_POST['v2_y1'], $_POST['v2_w'], $_POST['v2_h'], false, 'groupavatar', $bp->groups->new_group_id ) )
					return false;

				// Success on group avatar cropping, now save the results.
				$avatar_hrefs = groups_get_avatar_hrefs($result);

				if ( !$bp->groups->new_group_id = groups_create_group( array( 'group_id' => $bp->groups->new_group_id, 'avatar_thumb' => stripslashes( $avatar_hrefs['thumb_href'] ), 'avatar_full' => stripslashes( $avatar_hrefs['full_href'] ) ) ) ) {
					bp_core_add_message( __( 'There was an error saving group details, please try again.', 'buddypress' ), 'error' );
					bp_core_redirect( $bp->loggedin_user->domain . $bp->groups->slug . '/create/step/' . $bp->groups->current_create_step );				
				}
			}
		}

		if ( 'group-invites' == $bp->groups->current_create_step ) {
			groups_send_invites( $bp->groups->new_group_id, $bp->loggedin_user->id );
		}
		
		do_action( 'groups_create_group_step_save_' . $bp->groups->current_create_step );
		do_action( 'groups_create_group_step_complete' ); // Mostly for clearing cache on a generic action name
		
		/**
		 * Once we have successfully saved the details for this step of the creation process
		 * we need to add the current step to the array of completed steps, then update the cookies
		 * holding the information
		 */
		if ( !in_array( $bp->groups->current_create_step, $bp->groups->completed_create_steps ) )
			$bp->groups->completed_create_steps[] = $bp->groups->current_create_step;
		
		/* Unset cookie info */
		setcookie( 'bp_new_group_id', false, time() - 1000, COOKIEPATH );
		setcookie( 'bp_completed_create_steps', false, time() - 1000, COOKIEPATH );
		
		/* Reset cookie info */
		setcookie( 'bp_new_group_id', $bp->groups->new_group_id, time()+60*60*24, COOKIEPATH );
		setcookie( 'bp_completed_create_steps', serialize( $bp->groups->completed_create_steps ), time()+60*60*24, COOKIEPATH );	

		/* If we have completed all steps and hit done on the final step we can redirect to the completed group */
		if ( count( $bp->groups->completed_create_steps ) == count( $bp->groups->group_creation_steps ) && $bp->groups->current_create_step == array_pop( array_keys( $bp->groups->group_creation_steps ) ) ) {
			unset( $bp->groups->current_create_step );
			unset( $bp->groups->completed_create_steps );
			
			$group = new BP_Groups_Group( $bp->groups->new_group_id, false, false );

			bp_core_redirect( bp_get_group_permalink( $group ) );
		} else {
			/**
			 * Since we don't know what the next step is going to be (any plugin can insert steps)
			 * we need to loop the step array and fetch the next step that way.
			 */
			foreach ( $bp->groups->group_creation_steps as $key => $value ) {
				if ( $key == $bp->groups->current_create_step ) {
					$next = 1; 
					continue;
				}
				
				if ( $next ) {
					$next_step = $key; 
					break;
				}
			}

			/* Move onto the next step */
			bp_core_redirect( $bp->loggedin_user->domain . $bp->groups->slug . '/create/step/' . $next_step );
		}
	}

	$bp->groups->new_group = new BP_Groups_Group( $bp->groups->new_group_id, false, false );
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
		
		if ( file_exists( TEMPLATEPATH . '/groups/single/home.php' ) )
			bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
		else
			bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/group-home' ) );
	}
}

function groups_screen_group_forum() {
	global $bp;
	
	if ( $bp->is_single_item ) {
		$topic_id = $bp->action_variables[1];
		$forum_id = groups_get_groupmeta( $bp->groups->current_group->id, 'forum_id' );
		
		if ( $topic_id ) {
			
			/* Posting a reply */
			if ( isset( $_POST['submit_reply'] ) && function_exists( 'bp_forums_new_post') ) {
				/* Check the nonce */
				if ( !check_admin_referer( 'bp_forums_new_reply' ) ) 
					return false;
		
				groups_new_group_forum_post( $_POST['reply_text'], $topic_id );
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/forum/topic/' . $topic_id );
			}
			
			do_action( 'groups_screen_group_forum_topic' );
			
			// If we are viewing a topic, load it.
 			bp_core_load_template( apply_filters( 'groups_template_group_forum', 'groups/forum/topic' ) );
		} else {

			/* Posting a topic */
			if ( isset( $_POST['submit_topic'] ) && function_exists( 'bp_forums_new_topic') ) {
				/* Check the nonce */	
				if ( !check_admin_referer( 'bp_forums_new_topic' ) ) 
					return false;
				
				groups_new_group_forum_topic( $_POST['topic_title'], $_POST['topic_text'], $_POST['topic_tags'], $forum_id );
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/forum/' );
			}
			
			do_action( 'groups_screen_group_forum', $topic_id, $forum_id );
			
			if ( file_exists( TEMPLATEPATH . '/groups/single/forum/index.php' ) )
				bp_core_load_template( apply_filters( 'groups_template_group_forum', 'groups/single/forum/index' ) );
			else
				bp_core_load_template( apply_filters( 'groups_template_group_forum', 'groups/forum/index' ) );				
		}
	}
}

function groups_screen_group_wire() {
	global $bp;
	
	$wire_action = $bp->action_variables[0];
		
	if ( $bp->is_single_item ) {
		if ( 'post' == $wire_action && groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {
			/* Check the nonce first. */
			if ( !check_admin_referer( 'bp_wire_post' ) ) 
				return false;
		
			if ( !groups_new_wire_post( $bp->groups->current_group->id, $_POST['wire-post-textarea'] ) ) {
				bp_core_add_message( __('Wire message could not be posted.', 'buddypress'), 'error' );
			} else {
				bp_core_add_message( __('Wire message successfully posted.', 'buddypress') );
			}

			if ( !strpos( $_SERVER['HTTP_REFERER'], $bp->wire->slug ) ) {
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
			} else {
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/' . $bp->wire->slug );
			}
	
		} else if ( 'delete' == $wire_action && groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {
			$wire_message_id = $bp->action_variables[1];

			/* Check the nonce first. */
			if ( !check_admin_referer( 'bp_wire_delete_link' ) )
				return false;
		
			if ( !groups_delete_wire_post( $wire_message_id, $bp->groups->table_name_wire ) ) {
				bp_core_add_message( __('There was an error deleting the wire message.', 'buddypress'), 'error' );
			} else {
				bp_core_add_message( __('Wire message successfully deleted.', 'buddypress') );
			}
			
			if ( !strpos( $_SERVER['HTTP_REFERER'], $bp->wire->slug ) ) {
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
			} else {
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/' . $bp->wire->slug );
			}
		
		} else if ( ( !$wire_action || 'latest' == $bp->action_variables[1] ) ) {
			if ( file_exists( TEMPLATEPATH . '/groups/single/wire.php' ) )
				bp_core_load_template( apply_filters( 'groups_template_group_wire', 'groups/single/wire' ) );
			else	
				bp_core_load_template( apply_filters( 'groups_template_group_wire', 'groups/wire' ) );
		} else {
			if ( file_exists( TEMPLATEPATH . '/groups/single/home.php' ) )
				bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
			else	
				bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/group-home' ) );
		}
	}
}

function groups_screen_group_members() {
	global $bp;
	
	if ( $bp->is_single_item ) {
		do_action( 'groups_screen_group_members', $bp->groups->current_group->id );

		if ( file_exists( TEMPLATEPATH . '/groups/single/members.php' ) )
			bp_core_load_template( apply_filters( 'groups_template_group_forum', 'groups/single/members' ) );
		else
			bp_core_load_template( apply_filters( 'groups_template_group_forum', 'groups/list-members' ) );		
	}
}

function groups_screen_group_invite() {
	global $bp;
	
	if ( $bp->is_single_item ) {
		if ( isset($bp->action_variables) && 'send' == $bp->action_variables[0] ) {
			
			if ( !check_admin_referer( 'groups_send_invites', '_wpnonce_send_invites' ) )
				return false;
		
			// Send the invites.
			groups_send_invites($bp->groups->current_group);
			
			bp_core_add_message( __('Group invites sent.', 'buddypress') );

			do_action( 'groups_screen_group_invite', $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
		} else {
			// Show send invite page
			if ( file_exists( TEMPLATEPATH . '/groups/single/send-invite.php' ) )
				bp_core_load_template( apply_filters( 'groups_template_group_invite', 'groups/single/send-invite' ) );	
			else
				bp_core_load_template( apply_filters( 'groups_template_group_invite', 'groups/send-invite' ) );	
		}
	}
}

function groups_screen_group_leave() {
	global $bp;
	
	if ( $bp->is_single_item ) {
		if ( isset($bp->action_variables) && 'yes' == $bp->action_variables[0] ) {
			
			// Check if the user is the group admin first.
			if ( groups_is_user_admin( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {
				bp_core_add_message(  __('As the only group administrator, you cannot leave this group.', 'buddypress'), 'error' );
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
			}
			
			// remove the user from the group.
			if ( !groups_leave_group( $bp->groups->current_group->id ) ) {
				bp_core_add_message(  __('There was an error leaving the group. Please try again.', 'buddypress'), 'error' );
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
			} else {
				bp_core_add_message( __('You left the group successfully.', 'buddypress') );
				bp_core_redirect( $bp->loggedin_user->domain . $bp->groups->slug );
			}
			
		} else if ( isset($bp->action_variables) && 'no' == $bp->action_variables[0] ) {
			
			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
		
		} else {
		
			do_action( 'groups_screen_group_leave', $bp->groups->current_group->id );
			
			// Show leave group page
			if ( file_exists( TEMPLATEPATH . '/groups/single/leave-confirm.php' ) )
				bp_core_load_template( apply_filters( 'groups_template_group_leave', 'groups/single/leave-confirm' ) );
			else
				bp_core_load_template( apply_filters( 'groups_template_group_leave', 'groups/leave-group-confirm' ) );				
		}
	}
}

function groups_screen_group_request_membership() {
	global $bp;
	
	if ( !is_user_logged_in() )
		return false;
	
	if ( 'private' == $bp->groups->current_group->status ) {
		// If the user has submitted a request, send it.
		if ( isset( $_POST['group-request-send']) ) {
			/* Check the nonce first. */
			if ( !check_admin_referer( 'groups_request_membership' ) )
				return false;
		
			if ( !groups_send_membership_request( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error sending your group membership request, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Your membership request was sent to the group administrator successfully. You will be notified when the group administrator responds to your request.', 'buddypress' ) );
			}
			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
		}
		
		do_action( 'groups_screen_group_request_membership', $bp->groups->current_group->id );
		
		if ( file_exists( TEMPLATEPATH . '/groups/single/request-membership.php' ) )
			bp_core_load_template( apply_filters( 'groups_template_group_request_membership', 'groups/single/request-membership' ) );
		else
			bp_core_load_template( apply_filters( 'groups_template_group_request_membership', 'groups/request-membership' ) );			
	}
}

function groups_screen_group_admin() {
	global $bp;
	
	if ( $bp->current_component != BP_GROUPS_SLUG || 'admin' != $bp->current_action )
		return false;
	
	if ( !empty( $bp->action_variables[0] ) )
		return false;
	
	bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/edit-details' );
}

function groups_screen_group_admin_edit_details() {
	global $bp;
	
	if ( $bp->current_component == $bp->groups->slug && 'edit-details' == $bp->action_variables[0] ) {
	
		if ( $bp->is_item_admin || $bp->is_item_mod  ) {
		
			// If the edit form has been submitted, save the edited details
			if ( isset( $_POST['save'] ) ) {
				/* Check the nonce first. */
				if ( !check_admin_referer( 'groups_edit_group_details' ) )
					return false;
		
				if ( !groups_edit_base_group_details( $_POST['group-id'], $_POST['group-name'], $_POST['group-desc'], $_POST['group-news'], (int)$_POST['group-notify-members'] ) ) {
					bp_core_add_message( __( 'There was an error updating group details, please try again.', 'buddypress' ), 'error' );
				} else {
					bp_core_add_message( __( 'Group details were successfully updated.', 'buddypress' ) );
				}
				
				do_action( 'groups_group_details_edited', $bp->groups->current_group->id );
				
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/edit-details' );
			}

			do_action( 'groups_screen_group_admin_edit_details', $bp->groups->current_group->id );

			if ( file_exists( TEMPLATEPATH . '/groups/single/admin.php' ) )
				bp_core_load_template( apply_filters( 'groups_template_group_admin', 'groups/single/admin' ) );		
			else
				bp_core_load_template( apply_filters( 'groups_template_group_admin', 'groups/admin/edit-details' ) );			
		}
	}
}
add_action( 'wp', 'groups_screen_group_admin_edit_details', 4 );

function groups_screen_group_admin_settings() {
	global $bp;
	
	if ( $bp->current_component == $bp->groups->slug && 'group-settings' == $bp->action_variables[0] ) {
		
		if ( !$bp->is_item_admin )
			return false;
		
		// If the edit form has been submitted, save the edited details
		if ( isset( $_POST['save'] ) ) {
			$enable_wire = ( isset($_POST['group-show-wire'] ) ) ? 1 : 0;
			$enable_forum = ( isset($_POST['group-show-forum'] ) ) ? 1 : 0;
			$enable_photos = ( isset($_POST['group-show-photos'] ) ) ? 1 : 0;
			$photos_admin_only = ( $_POST['group-photos-status'] != 'all' ) ? 1 : 0;
			
			$allowed_status = apply_filters( 'groups_allowed_status', array( 'public', 'private', 'hidden' ) );
			$status = ( in_array( $_POST['group-status'], $allowed_status ) ) ? $_POST['group-status'] : 'public';
			
			/* Check the nonce first. */
			if ( !check_admin_referer( 'groups_edit_group_settings' ) )
				return false;
			
			if ( !groups_edit_group_settings( $_POST['group-id'], $enable_wire, $enable_forum, $enable_photos, $photos_admin_only, $status ) ) {
				bp_core_add_message( __( 'There was an error updating group settings, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Group settings were successfully updated.', 'buddypress' ) );
			}

			do_action( 'groups_group_settings_edited', $bp->groups->current_group->id );
			
			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/group-settings' );
		}

		do_action( 'groups_screen_group_admin_settings', $bp->groups->current_group->id );
		
		if ( file_exists( TEMPLATEPATH . '/groups/single/admin.php' ) )
			bp_core_load_template( apply_filters( 'groups_template_group_admin_settings', 'groups/single/admin' ) );		
		else
			bp_core_load_template( apply_filters( 'groups_template_group_admin_settings', 'groups/admin/group-settings' ) );
	}
}
add_action( 'wp', 'groups_screen_group_admin_settings', 4 );

function groups_screen_group_admin_avatar() {
	global $bp;
	
	if ( $bp->current_component == $bp->groups->slug && 'group-avatar' == $bp->action_variables[0] ) {
		
		if ( !$bp->is_item_admin )
			return false;
		
		if ( isset( $_POST['save'] ) ) {
			
			// Image already cropped and uploaded, lets store a reference in the DB.
			if ( !wp_verify_nonce($_POST['nonce'], 'slick_avatars') || !$result = bp_core_avatar_cropstore( $_POST['orig'], $_POST['canvas'], $_POST['v1_x1'], $_POST['v1_y1'], $_POST['v1_w'], $_POST['v1_h'], $_POST['v2_x1'], $_POST['v2_y1'], $_POST['v2_w'], $_POST['v2_h'], false, 'groupavatar', $bp->groups->current_group->id ) )
				return false;

			// Success on group avatar cropping, now save the results.
			$avatar_hrefs = groups_get_avatar_hrefs($result);
			
			// Delete the old group avatars first
			$avatar_thumb_path = groups_get_avatar_path( $bp->groups->current_group->avatar_thumb );
			$avatar_full_path = groups_get_avatar_path( $bp->groups->current_group->avatar_full );
			
			@unlink($avatar_thumb_path);
			@unlink($avatar_full_path);

			$bp->groups->current_group->avatar_thumb = stripslashes( $avatar_hrefs['thumb_href'] );
			$bp->groups->current_group->avatar_full = stripslashes( $avatar_hrefs['full_href'] );

			if ( !$bp->groups->current_group->save() ) {
				bp_core_add_message( __( 'There was an error updating the group avatar, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'The group avatar was successfully updated.', 'buddypress' ) );
			}

			do_action( 'groups_group_avatar_updated', $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/group-avatar' );
		}
		
		do_action( 'groups_screen_group_admin_avatar', $bp->groups->current_group->id );	
		
		if ( file_exists( TEMPLATEPATH . '/groups/single/admin.php' ) )
			bp_core_load_template( apply_filters( 'groups_template_group_admin_avatar', 'groups/single/admin' ) );		
		else
			bp_core_load_template( apply_filters( 'groups_template_group_admin_avatar', 'groups/admin/group-avatar' ) );		
	}
}
add_action( 'wp', 'groups_screen_group_admin_avatar', 4 );

function groups_screen_group_admin_manage_members() {
	global $bp;

	if ( $bp->current_component == $bp->groups->slug && 'manage-members' == $bp->action_variables[0] ) {
		
		if ( !$bp->is_item_admin )
			return false;
		
		if ( 'promote' == $bp->action_variables[1] && is_numeric( $bp->action_variables[2] ) ) {
			$user_id = $bp->action_variables[2];
			
			/* Check the nonce first. */
			if ( !check_admin_referer( 'groups_promote_member' ) )
				return false;
		
			// Promote a user.
			if ( !groups_promote_member( $user_id, $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error when promoting that user, please try again', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'User promoted successfully', 'buddypress' ) );
			}
			
			do_action( 'groups_promoted_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/manage-members' );
		}
		
		if ( 'demote' == $bp->action_variables[1] && is_numeric( $bp->action_variables[2] ) ) {
			$user_id = $bp->action_variables[2];

			/* Check the nonce first. */
			if ( !check_admin_referer( 'groups_demote_member' ) )
				return false;
					
			// Demote a user.
			if ( !groups_demote_member( $user_id, $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error when demoting that user, please try again', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'User demoted successfully', 'buddypress' ) );
			}

			do_action( 'groups_demoted_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/manage-members' );
		}
		
		if ( 'ban' == $bp->action_variables[1] && is_numeric( $bp->action_variables[2] ) ) {
			$user_id = $bp->action_variables[2];

			/* Check the nonce first. */
			if ( !check_admin_referer( 'groups_ban_member' ) )
				return false;
					
			// Ban a user.
			if ( !groups_ban_member( $user_id, $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error when banning that user, please try again', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'User banned successfully', 'buddypress' ) );
			}

			do_action( 'groups_banned_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/manage-members' );
		}
		
		if ( 'unban' == $bp->action_variables[1] && is_numeric( $bp->action_variables[2] ) ) {
			$user_id = $bp->action_variables[2];

			/* Check the nonce first. */
			if ( !check_admin_referer( 'groups_unban_member' ) )
				return false;
					
			// Remove a ban for user.
			if ( !groups_unban_member( $user_id, $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error when unbanning that user, please try again', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'User ban removed successfully', 'buddypress' ) );
			}

			do_action( 'groups_unbanned_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/manage-members' );
		}

		do_action( 'groups_screen_group_admin_manage_members', $bp->groups->current_group->id );
		
		if ( file_exists( TEMPLATEPATH . '/groups/single/admin.php' ) )
			bp_core_load_template( apply_filters( 'groups_template_group_admin_manage_members', 'groups/single/admin' ) );		
		else
			bp_core_load_template( apply_filters( 'groups_template_group_admin_manage_members', 'groups/admin/manage-members' ) );
	}
}
add_action( 'wp', 'groups_screen_group_admin_manage_members', 4 );

function groups_screen_group_admin_requests() {
	global $bp;
	
	if ( $bp->current_component == $bp->groups->slug && 'membership-requests' == $bp->action_variables[0] ) {
		
		if ( !$bp->is_item_admin || 'public' == $bp->groups->current_group->status )
			return false;
		
		// Remove any screen notifications
		bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'new_membership_request' );
		
		$request_action = $bp->action_variables[1];
		$membership_id = $bp->action_variables[2];

		if ( isset($request_action) && isset($membership_id) ) {
			if ( 'accept' == $request_action && is_numeric($membership_id) ) {

				/* Check the nonce first. */
				if ( !check_admin_referer( 'groups_accept_membership_request' ) )
					return false;
		
				// Accept the membership request
				if ( !groups_accept_membership_request( $membership_id ) ) {
					bp_core_add_message( __( 'There was an error accepting the membership request, please try again.', 'buddypress' ), 'error' );
				} else {
					bp_core_add_message( __( 'Group membership request accepted', 'buddypress' ) );
				}

			} else if ( 'reject' == $request_action && is_numeric($membership_id) ) {
				/* Check the nonce first. */
				if ( !check_admin_referer( 'groups_reject_membership_request' ) )
					return false;
		
				// Reject the membership request
				if ( !groups_reject_membership_request( $membership_id ) ) {
					bp_core_add_message( __( 'There was an error rejecting the membership request, please try again.', 'buddypress' ), 'error' );
				} else {
					bp_core_add_message( __( 'Group membership request rejected', 'buddypress' ) );
				}	

			}
			
			do_action( 'groups_group_request_managed', $bp->groups->current_group->id, $request_action, $membership_id );

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/membership-requests' );
		}

		do_action( 'groups_screen_group_admin_requests', $bp->groups->current_group->id );

		if ( file_exists( TEMPLATEPATH . '/groups/single/admin.php' ) )
			bp_core_load_template( apply_filters( 'groups_template_group_admin_requests', 'groups/single/admin' ) );		
		else
			bp_core_load_template( apply_filters( 'groups_template_group_admin_requests', 'groups/admin/membership-requests' ) );		
	}
}
add_action( 'wp', 'groups_screen_group_admin_requests', 4 );

function groups_screen_group_admin_delete_group() {
	global $bp;
	
	if ( $bp->current_component == $bp->groups->slug && 'delete-group' == $bp->action_variables[0] ) {
		
		if ( !$bp->is_item_admin )
			return false;
		
		if ( isset( $_POST['delete-group-button'] ) && isset( $_POST['delete-group-understand'] ) ) {
			/* Check the nonce first. */
			if ( !check_admin_referer( 'groups_delete_group' ) )
				return false;
		
			// Group admin has deleted the group, now do it.
			if ( !groups_delete_group( $_POST['group-id']) ) {
				bp_core_add_message( __( 'There was an error deleting the group, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'The group was deleted successfully', 'buddypress' ) );

				do_action( 'groups_group_deleted', $_POST['group-id'] );

				bp_core_redirect( $bp->loggedin_user->domain . $bp->groups->slug . '/' );
			}

			bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component );
		}

		do_action( 'groups_screen_group_admin_delete_group', $bp->groups->current_group->id );

		if ( file_exists( TEMPLATEPATH . '/groups/single/admin.php' ) )
			bp_core_load_template( apply_filters( 'groups_template_group_admin_delete_group', 'groups/single/admin' ) );		
		else
			bp_core_load_template( apply_filters( 'groups_template_group_admin_delete_group', 'groups/admin/delete-group' ) );		
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


/********************************************************************************
 * Action Functions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 */

function groups_action_join_group() {
	global $bp;
		
	if ( !$bp->is_single_item || $bp->current_component != $bp->groups->slug || $bp->current_action != 'join' )
		return false;
		
	// user wants to join a group
	if ( !groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) && !groups_is_user_banned( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {
		if ( !groups_join_group($bp->groups->current_group->id) ) {
			bp_core_add_message( __('There was an error joining the group.', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('You joined the group!', 'buddypress') );
		}
		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
	}

	if ( file_exists( TEMPLATEPATH . '/groups/single/admin.php' ) )
		bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );		
	else
		bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/group-home' ) );
}
add_action( 'wp', 'groups_action_join_group', 3 );

function groups_action_sort_creation_steps() {
	global $bp;
	
	if ( $bp->current_component != BP_GROUPS_SLUG && $bp->current_action != 'create' )
		return false;

	if ( !is_array( $bp->groups->group_creation_steps ) )
		return false;
		
	foreach ( $bp->groups->group_creation_steps as $slug => $step )
		$temp[$step['position']] = array( 'name' => $step['name'], 'slug' => $slug );

	/* Sort the steps by their position key */
	ksort($temp);
	unset($bp->groups->group_creation_steps);
	
	foreach( $temp as $position => $step )
		$bp->groups->group_creation_steps[$step['slug']] = array( 'name' => $step['name'], 'position' => $position );
}
add_action( 'wp', 'groups_action_sort_creation_steps', 3 );

function groups_aciton_redirect_to_random_group() {
	global $bp, $wpdb;
	
	if ( $bp->current_component == $bp->groups->slug && isset( $_GET['random-group'] ) ) {
		$group = groups_get_random_group();

		bp_core_redirect( $bp->root_domain . '/' . $bp->groups->slug . '/' . $group['groups'][0]->slug );
	}
}
add_action( 'wp', 'groups_aciton_redirect_to_random_group', 6 );


/********************************************************************************
 * Activity & Notification Functions
 *
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 */

function groups_record_activity( $args = true ) {
	global $bp;
	
	if ( function_exists('bp_activity_record') ) {
		extract($args);

		if ( !$bp->groups->current_group ) {
			if ( !$bp->groups->current_group = wp_cache_get( 'groups_group_nouserdata_' . $item_id, 'bp' ) ) {
				$bp->groups->current_group = new BP_Groups_Group( $item_id, false, false );
				wp_cache_set( 'groups_group_nouserdata_' . $item_id, $bp->groups->current_group, 'bp' );
			}
		}

		if ( 'public' == $bp->groups->current_group->status )
			bp_activity_record( $item_id, $component_name, $component_action, $is_private, $secondary_item_id, $user_id, $secondary_user_id );
	}
}

function groups_delete_activity( $args = true ) {
	if ( function_exists('bp_activity_delete') ) {
		extract($args);
		bp_activity_delete( $item_id, $component_name, $component_action, $user_id, $secondary_item_id );
	}
}

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
				$user_fullname = bp_core_get_user_displayname( $requesting_user_id );
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


/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

/*** Group Creation, Editing & Deletion *****************************************/

function groups_create_group( $args = '' ) {
	global $bp;
	
	extract( $args );
	
	/**
	 * Possible parameters:
	 *	'group_id'
	 *	'creator_id'
	 *	'name'
	 *	'description'
	 *	'news'
	 *	'slug'
	 *	'status'
	 *	'enable_wire'
	 *	'enable_forum'
	 *	'avatar_thumb'
	 *	'avatar_full'
	 *	'date_created'
	 */

	if ( $group_id )
		$group = new BP_Groups_Group( $group_id );
	else
		$group = new BP_Groups_Group;
	
	if ( $creator_id ) {
		$group->creator_id = $creator_id;
	} else {
		$group->creator_id = $bp->loggedin_user->id;
	}
	
	if ( isset( $name ) )
		$group->name = $name;
	
	if ( isset( $description ) )
		$group->description = $description;
	
	if ( isset( $news ) )
		$group->news = $news;
	
	if ( isset( $slug ) && groups_check_slug( $slug ) )
		$group->slug = $slug;
	
	if ( isset( $status ) ) {
		if ( groups_is_valid_status( $status ) )
			$group->status = $status;
	}
	
	if ( isset( $enable_wire ) )
		$group->enable_wire = $enable_wire;
	else if ( !$group_id && !isset( $enable_wire ) )
		$group->enable_wire = 1;
	
	if ( isset( $enable_forum ) )
		$group->enable_forum = $enable_forum;
	else if ( !$group_id && !isset( $enable_forum ) )
		$group->enable_forum = 1;
			
	if ( isset( $avatar_thumb ) )
		$group->avatar_thumb = $avatar_thumb;
	
	if ( isset( $avatar_full ) )
		$group->avatar_full = $avatar_full;
	
	if ( isset( $date_created ) )
		$group->date_created = $date_created;
	
	if ( !$group->save() )
		return false;

	if ( !$group_id ) {
		/* If this is a new group, set up the creator as the first member and admin */
		$member = new BP_Groups_Member;
		$member->group_id = $group->id;
		$member->user_id = $group->creator_id;
		$member->is_admin = 1;
		$member->user_title = __( 'Group Admin', 'buddypress' );
		$member->is_confirmed = 1;
		
		$member->save();
	}

	return $group->id;
}

function groups_edit_base_group_details( $group_id, $group_name, $group_desc, $group_news, $notify_members ) {
	global $bp;

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
	
	$group = new BP_Groups_Group( $group_id, false, false );
	$group->enable_wire = $enable_wire;
	$group->enable_forum = $enable_forum;
	$group->enable_photos = $enable_photos;
	$group->photos_admin_only = $photos_admin_only;
	
	/*** 
	 * Before we potentially switch the group status, if it has been changed to public
	 * from private and there are outstanding membership requests, auto-accept those requests.
	 */
	if ( 'private' == $group->status && 'public' == $status )
		groups_accept_all_pending_membership_requests( $group->id );
	
	/* Now update the status */
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

function groups_delete_group( $group_id ) {
	global $bp;
	
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

function groups_is_valid_status( $status ) {
	global $bp;
	
	return in_array( $status, $bp->groups->valid_status );
}

function groups_check_slug( $slug ) {
	global $bp;

	if ( 'wp' == substr( $slug, 0, 2 ) )
		$slug = substr( $slug, 2, strlen( $slug ) - 2 );
			
	if ( in_array( $slug, $bp->groups->forbidden_names ) ) {
		$slug = $slug . '-' . rand();
	}
	
	if ( BP_Groups_Group::check_slug( $slug ) ) {
		do {
			$slug = $slug . '-' . rand();
		}
		while ( BP_Groups_Group::check_slug( $slug ) );
	}
	
	return $slug;
}

function groups_get_slug( $group_id ) {
	$group = new BP_Groups_Group( $group_id, false, false );
	return $group->slug;
}

/*** User Actions ***************************************************************/

function groups_leave_group( $group_id, $user_id = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;
	
	// Admins cannot leave a group, that is until promotion to admin support is implemented.
	if ( groups_is_user_admin( $user_id, $group_id ) )
		return false;
		
	// This is exactly the same as deleting and invite, just is_confirmed = 1 NOT 0.
	if ( !groups_uninvite_user( $user_id, $group_id, true ) )
		return false;

	do_action( 'groups_leave_group', $group_id, $user_id );

	/* Modify group member count */
	groups_update_groupmeta( $group_id, 'total_member_count', (int) groups_get_groupmeta( $group_id, 'total_member_count') - 1 );
	
	return true;
}

function groups_join_group( $group_id, $user_id = false ) {
	global $bp;
		
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

	do_action( 'groups_join_group', $group_id, $user_id );

	return true;
}

/*** General Group Functions ****************************************************/

function groups_check_group_exists( $group_id ) {
	return BP_Groups_Group::group_exists( $group_id );
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

/*** Group Fetching, Filtering & Searching  *************************************/

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

function groups_search_groups( $search_terms, $pag_num_per_page = 5, $pag_page = 1, $sort_by = false, $order = false ) {
	return BP_Groups_Group::search_groups( $search_terms, $pag_num_per_page, $pag_page, $sort_by, $order );
}

function groups_filter_user_groups( $filter, $user_id = false, $order = false, $pag_num_per_page = 5, $pag_page = 1 ) {
	return BP_Groups_Group::filter_user_groups( $filter, $user_id, $order, $pag_num_per_page, $pag_page );
}

/*** Group Avatars *************************************************************/

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

/*** Group Member Status Checks ************************************************/

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

/*** Group Wire ****************************************************************/

function groups_new_wire_post( $group_id, $content ) {
	 $bp;

	$private = false;
	if ( $bp->groups->current_group->status != 'public' )
		$private = true;
	
	if ( $wire_post_id = bp_wire_new_post( $group_id, $content, $bp->groups->slug, $private ) ) {
		do_action( 'groups_new_wire_post', $group_id, $wire_post_id );
		
		return true;
	}
	
	return false;
}

function groups_delete_wire_post( $wire_post_id, $table_name ) {
	global $bp;
		
	if ( bp_wire_delete_post( $wire_post_id, $bp->groups->slug, $table_name ) ) {		
		do_action( 'groups_deleted_wire_post', $wire_post_id );
		return true;
	}
	
	return false;
}

/*** Group Forums **************************************************************/

function groups_new_group_forum( $group_id = false, $group_name = false, $group_desc = false ) {
	global $bp;
	
	if ( !$group_id )
		$group_id = $bp->groups->current_group->id;
	
	if ( !$group_name )
		$group_name = $bp->groups->current_group->name;
	
	if ( !$group_desc )
		$group_desc = $bp->groups->current_group->description;
	
	$forum = bp_forums_new_forum( apply_filters( 'groups_new_group_forum_name', $group_name . ' - ' . __( 'Forum', 'buddypress' ), $group_name ), apply_filters( 'groups_new_group_forum_desc', $group_desc ) );
	
	groups_update_groupmeta( $group_id, 'forum_id', $forum['forum_id'] );
	
	do_action( 'groups_new_group_forum', $forum, $group_id );
}

function groups_new_group_forum_post( $post_text, $topic_id ) {
	global $bp;
	
	if ( $forum_post = bp_forums_new_post( $post_text, $topic_id ) ) {
		bp_core_add_message( __( 'Reply posted successfully!', 'buddypress') );

		/* Record in activity streams */
		groups_record_activity( array( 'item_id' => $bp->groups->current_group->id, 'component_name' => $bp->groups->slug, 'component_action' => 'new_forum_post', 'is_private' => 0, 'secondary_item_id' => $forum_post['post_id'] ) );
		
		do_action( 'groups_new_forum_topic_post', $bp->groups->current_group->id, $forum_post );
		
		return $forum_post;
	}
	
	bp_core_add_message( __( 'There was an error posting that reply.', 'buddypress'), 'error' );					
	return false;
}

function groups_new_group_forum_topic( $topic_title, $topic_text, $topic_tags, $forum_id ) {
	global $bp;
	
	if ( $topic = bp_forums_new_topic( $topic_title, $topic_text, $topic_tags, $forum_id ) ) {
		bp_core_add_message( __( 'Topic posted successfully!', 'buddypress') );

		/* Record in activity streams */
		groups_record_activity( array( 'item_id' => $bp->groups->current_group->id, 'component_name' => $bp->groups->slug, 'component_action' => 'new_forum_topic', 'is_private' => 0, 'secondary_item_id' => $topic['topic_id'] ) );
		
		do_action( 'groups_new_forum_topic', $bp->groups->current_group->id, $topic );
		
		return $topic;
	}
	
	bp_core_add_message( __( 'There was an error posting that topic.', 'buddypress'), 'error' );					
	return false;
}

/*** Group Invitations *********************************************************/

function groups_get_invites_for_user( $user_id = false ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;
	
	return BP_Groups_Member::get_invites( $user_id );
}

function groups_invite_user( $user_id, $group_id ) {
	global $bp;
	
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

function groups_uninvite_user( $user_id, $group_id, $deprecated = true ) {
	global $bp;
	
	if ( !BP_Groups_Member::delete( $user_id, $group_id ) )
		return false;

	do_action( 'groups_uninvite_user', $group_id, $user_id );

	return true;
}

function groups_accept_invite( $user_id, $group_id ) {
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

function groups_delete_invite( $user_id, $group_id ) {
	global $bp;
	
	$delete = BP_Groups_Member::delete_invite( $user_id, $group_id );
	
	if ( $delete )
		bp_core_delete_notifications_for_user_by_item_id( $user_id, $group_id, $bp->groups->slug, 'group_invite' );
	
	return $delete;
}

function groups_send_invites( $user_id, $group_id ) {
	global $bp;
	
	require_once ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-notifications.php' );
	
	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	// Send friend invites.
	$invited_users = groups_get_invites_for_group( $user_id, $group_id );
	$group = new BP_Groups_Group( $group_id, false, false );

	for ( $i = 0; $i < count( $invited_users ); $i++ ) {
		$member = new BP_Groups_Member( $invited_users[$i], $group_id );

		// Send the actual invite
		groups_notification_group_invites( $group, $member, $user_id );
		
		$member->invite_sent = 1;
		$member->save();
	}
	
	do_action( 'groups_send_invites', $bp->groups->current_group->id, $invited_users );
}

function groups_get_invites_for_group( $user_id, $group_id ) {
	return BP_Groups_Group::get_invites( $user_id, $group_id );
}

function groups_check_user_has_invite( $user_id, $group_id ) {
	return BP_Groups_Member::check_has_invite( $user_id, $group_id );
}

function groups_delete_all_group_invites( $group_id ) {
	return BP_Groups_Group::delete_all_invites( $group_id );
}

/*** Group Promotion & Banning *************************************************/

function groups_promote_member( $user_id, $group_id ) {
	global $bp;
	
	if ( !$bp->is_item_admin )
		return false;
		
	$member = new BP_Groups_Member( $user_id, $group_id );

	do_action( 'groups_premote_member', $user_id, $group_id );
	
	return $member->promote();
}

function groups_demote_member( $user_id, $group_id ) {
	global $bp;
	
	if ( !$bp->is_item_admin )
		return false;
		
	$member = new BP_Groups_Member( $user_id, $group_id );
	
	do_action( 'groups_demote_member', $user_id, $group_id );

	return $member->demote();
}

function groups_ban_member( $user_id, $group_id ) {
	global $bp;

	if ( !$bp->is_item_admin )
		return false;
		
	$member = new BP_Groups_Member( $user_id, $group_id );

	do_action( 'groups_ban_member', $user_id, $group_id );
	
	return $member->ban();
}

function groups_unban_member( $user_id, $group_id ) {
	global $bp;
	
	if ( !$bp->is_item_admin )
		return false;
		
	$member = new BP_Groups_Member( $user_id, $group_id );
	
	do_action( 'groups_unban_member', $user_id, $group_id );
	
	return $member->unban();
}

/*** Group Membership ****************************************************/

function groups_send_membership_request( $requesting_user_id, $group_id ) {
	global $bp;

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

function groups_accept_membership_request( $membership_id, $user_id = false, $group_id = false ) {
	global $bp;
	
	if ( $user_id && $group_id )
		$membership = new BP_Groups_Member( $user_id, $group_id );
	else
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

function groups_reject_membership_request( $membership_id, $user_id = false, $group_id = false ) {		
	if ( $user_id && $group_id )
		$membership = new BP_Groups_Member( $user_id, $group_id );
	else
		$membership = new BP_Groups_Member( false, false, $membership_id );
	
	if ( !BP_Groups_Member::delete( $membership->user_id, $membership->group_id ) )
		return false;
	
	// Send a notification to the user.
	require_once ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-notifications.php' );
	groups_notification_membership_request_completed( $membership->user_id, $membership->group_id, false );
	
	do_action( 'groups_membership_rejected', $membership->user_id, $membership->group_id );
	
	return true;
}

function groups_check_for_membership_request( $user_id, $group_id ) {
	return BP_Groups_Member::check_for_membership_request( $user_id, $group_id );
}

function groups_accept_all_pending_membership_requests( $group_id ) {
	$user_ids = BP_Groups_Member::get_all_membership_request_user_ids( $group_id );

	if ( !$user_ids )
		return false;
	
	foreach ( (array) $user_ids as $user_id ) {
		groups_accept_membership_request( false, $user_id, $group_id );
	}
	
	do_action( 'groups_accept_all_pending_membership_requests', $group_id );
	
	return true;
}

/*** Group Meta ****************************************************/

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

/*** Group Theme Handling ****************************************************/

/**
 * The following two functions will force the active member theme for
 * groups pages, even though they are technically under the root "home" blog
 * from a WordPress point of view.
 */

function groups_force_buddypress_theme( $template ) {
	global $bp;
	
	if ( $bp->current_component != $bp->groups->slug )
		return $template;
	
	$member_theme = get_site_option('active-member-theme');
	
	if ( empty($member_theme) )
		$member_theme = 'bpmember';

	add_filter( 'theme_root', 'bp_core_filter_buddypress_theme_root' );
	add_filter( 'theme_root_uri', 'bp_core_filter_buddypress_theme_root_uri' );

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
	
	add_filter( 'theme_root', 'bp_core_filter_buddypress_theme_root' );
	add_filter( 'theme_root_uri', 'bp_core_filter_buddypress_theme_root_uri' );
	
	return $member_theme;
}
add_filter( 'stylesheet', 'groups_force_buddypress_stylesheet', 1, 1 );


/*** Group Cleanup Functions ****************************************************/

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
add_action( 'groups_create_group_step_complete', 'bp_core_clear_cache' );
add_action( 'groups_created_group', 'bp_core_clear_cache' );
add_action( 'groups_group_avatar_updated', 'bp_core_clear_cache' );

?>