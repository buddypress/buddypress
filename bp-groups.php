<?php

define ( 'BP_GROUPS_DB_VERSION', '1900' );

/* Define the slug for the component */
if ( !defined( 'BP_GROUPS_SLUG' ) )
	define ( 'BP_GROUPS_SLUG', 'groups' );

require ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-classes.php' );
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
			status varchar(10) NOT NULL DEFAULT 'public',
			enable_forum tinyint(1) NOT NULL DEFAULT '1',
			date_created datetime NOT NULL,
		    KEY creator_id (creator_id),
		    KEY status (status)
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

	do_action( 'groups_install' );

	update_site_option( 'bp-groups-db-version', BP_GROUPS_DB_VERSION );
}

function groups_setup_globals() {
	global $bp, $wpdb;

	/* For internal identification */
	$bp->groups->id = 'groups';

	$bp->groups->table_name = $wpdb->base_prefix . 'bp_groups';
	$bp->groups->table_name_members = $wpdb->base_prefix . 'bp_groups_members';
	$bp->groups->table_name_groupmeta = $wpdb->base_prefix . 'bp_groups_groupmeta';
	$bp->groups->format_notification_function = 'groups_format_notifications';
	$bp->groups->slug = BP_GROUPS_SLUG;

	/* Register this in the active components array */
	$bp->active_components[$bp->groups->slug] = $bp->groups->id;

	$bp->groups->forbidden_names = apply_filters( 'groups_forbidden_names', array( 'my-groups', 'create', 'invites', 'send-invites', 'forum', 'delete', 'add', 'admin', 'request-membership', 'members', 'settings', 'avatar', BP_GROUPS_SLUG ) );

	$bp->groups->group_creation_steps = apply_filters( 'groups_create_group_steps', array(
		'group-details' => array( 'name' => __( 'Details', 'buddypress' ), 'position' => 0 ),
		'group-settings' => array( 'name' => __( 'Settings', 'buddypress' ), 'position' => 10 ),
		'group-avatar' => array( 'name' => __( 'Avatar', 'buddypress' ), 'position' => 20 ),
	) );

	if ( bp_is_active( 'friends' ) )
		$bp->groups->group_creation_steps['group-invites'] = array( 'name' => __( 'Invites', 'buddypress' ), 'position' => 30 );

	$bp->groups->valid_status = apply_filters( 'groups_valid_status', array( 'public', 'private', 'hidden' ) );

	do_action( 'groups_setup_globals' );
}
add_action( 'bp_setup_globals', 'groups_setup_globals' );
add_action( 'admin_menu', 'groups_setup_globals', 2 );

function groups_setup_root_component() {
	/* Register 'groups' as a root component */
	bp_core_add_root_component( BP_GROUPS_SLUG );
}
add_action( 'bp_setup_root_components', 'groups_setup_root_component' );

function groups_check_installed() {
	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( get_site_option('bp-groups-db-version') < BP_GROUPS_DB_VERSION )
		groups_install();
}
add_action( 'admin_menu', 'groups_check_installed' );

function groups_setup_nav() {
	global $bp;

	if ( $group_id = BP_Groups_Group::group_exists($bp->current_action) ) {

		/* This is a single group page. */
		$bp->is_single_item = true;
		$bp->groups->current_group = new BP_Groups_Group( $group_id );

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
	}

	/* Add 'Groups' to the main navigation */
	bp_core_new_nav_item( array( 'name' => sprintf( __( 'Groups <span>(%d)</span>', 'buddypress' ), groups_total_groups_for_user() ), 'slug' => $bp->groups->slug, 'position' => 70, 'screen_function' => 'groups_screen_my_groups', 'default_subnav_slug' => 'my-groups', 'item_css_id' => $bp->groups->id ) );

	$groups_link = $bp->loggedin_user->domain . $bp->groups->slug . '/';

	/* Add the subnav items to the groups nav item */
	bp_core_new_subnav_item( array( 'name' => __( 'My Groups', 'buddypress' ), 'slug' => 'my-groups', 'parent_url' => $groups_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_my_groups', 'position' => 10, 'item_css_id' => 'groups-my-groups' ) );
	bp_core_new_subnav_item( array( 'name' => __( 'Invites', 'buddypress' ), 'slug' => 'invites', 'parent_url' => $groups_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_invites', 'position' => 30, 'user_has_access' => bp_is_my_profile() ) );

	if ( $bp->current_component == $bp->groups->slug ) {

		if ( bp_is_my_profile() && !$bp->is_single_item ) {

			$bp->bp_options_title = __( 'My Groups', 'buddypress' );

		} else if ( !bp_is_my_profile() && !$bp->is_single_item ) {

			$bp->bp_options_avatar = bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb' ) );
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

			if ( !$bp->bp_options_avatar = bp_core_fetch_avatar( array( 'item_id' => $bp->groups->current_group->id, 'object' => 'group', 'type' => 'thumb', 'avatar_dir' => 'group-avatars', 'alt' => __( 'Group Avatar', 'buddypress' ) ) ) )
				$bp->bp_options_avatar = '<img src="' . attribute_escape( $group->avatar_full ) . '" class="avatar" alt="' . attribute_escape( $group->name ) . '" />';

			$group_link = $bp->root_domain . '/' . $bp->groups->slug . '/' . $bp->groups->current_group->slug . '/';

			// If this is a private or hidden group, does the user have access?
			if ( 'private' == $bp->groups->current_group->status || 'hidden' == $bp->groups->current_group->status ) {
				if ( $bp->groups->current_group->is_user_member && is_user_logged_in() || is_site_admin() )
					$bp->groups->current_group->user_has_access = true;
				else
					$bp->groups->current_group->user_has_access = false;
			} else {
				$bp->groups->current_group->user_has_access = true;
			}

			/* Reset the existing subnav items */
			bp_core_reset_subnav_items($bp->groups->slug);

			/* Add a new default subnav item for when the groups nav is selected. */
			bp_core_new_nav_default( array( 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_home', 'subnav_slug' => 'home' ) );

			/* Add the "Home" subnav item, as this will always be present */
			bp_core_new_subnav_item( array( 'name' => __( 'Home', 'buddypress' ), 'slug' => 'home', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_home', 'position' => 10, 'item_css_id' => 'home' ) );

			/* If the user is a group mod or more, then show the group admin nav item */
			if ( $bp->is_item_mod || $bp->is_item_admin )
				bp_core_new_subnav_item( array( 'name' => __( 'Admin', 'buddypress' ), 'slug' => 'admin', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_admin', 'position' => 20, 'user_has_access' => ( $bp->is_item_admin + (int)$bp->is_item_mod ), 'item_css_id' => 'admin' ) );

			// If this is a private group, and the user is not a member, show a "Request Membership" nav item.
			if ( !is_site_admin() && is_user_logged_in() && !$bp->groups->current_group->is_user_member && !groups_check_for_membership_request( $bp->loggedin_user->id, $bp->groups->current_group->id ) && $bp->groups->current_group->status == 'private' )
				bp_core_new_subnav_item( array( 'name' => __( 'Request Membership', 'buddypress' ), 'slug' => 'request-membership', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_request_membership', 'position' => 30 ) );

			if ( $bp->groups->current_group->enable_forum && function_exists('bp_forums_setup') )
				bp_core_new_subnav_item( array( 'name' => __( 'Forum', 'buddypress' ), 'slug' => 'forum', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_forum', 'position' => 40, 'user_has_access' => $bp->groups->current_group->user_has_access, 'item_css_id' => 'forums' ) );

			bp_core_new_subnav_item( array( 'name' => sprintf( __( 'Members (%s)', 'buddypress' ), number_format( $bp->groups->current_group->total_member_count ) ), 'slug' => 'members', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_members', 'position' => 60, 'user_has_access' => $bp->groups->current_group->user_has_access, 'item_css_id' => 'members'  ) );

			if ( is_user_logged_in() && groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {
				if ( function_exists('friends_install') )
					bp_core_new_subnav_item( array( 'name' => __( 'Send Invites', 'buddypress' ), 'slug' => 'send-invites', 'parent_url' => $group_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'groups_screen_group_invite', 'item_css_id' => 'invite', 'position' => 70, 'user_has_access' => $bp->groups->current_group->user_has_access ) );
			}
		}
	}

	do_action( 'groups_setup_nav', $bp->groups->current_group->user_has_access );
}
add_action( 'bp_setup_nav', 'groups_setup_nav' );
add_action( 'admin_menu', 'groups_setup_nav' );

function groups_directory_groups_setup() {
	global $bp;

	if ( $bp->current_component == $bp->groups->slug && empty( $bp->current_action ) && empty( $bp->current_item ) ) {
		$bp->is_directory = true;

		do_action( 'groups_directory_groups_setup' );
		bp_core_load_template( apply_filters( 'groups_template_directory_groups', 'groups/index' ) );
	}
}
add_action( 'wp', 'groups_directory_groups_setup', 2 );

function groups_setup_adminbar_menu() {
	global $bp;

	if ( !$bp->groups->current_group )
		return false;

	/* Don't show this menu to non site admins or if you're viewing your own profile */
	if ( !is_site_admin() )
		return false;
	?>
	<li id="bp-adminbar-adminoptions-menu">
		<a href=""><?php _e( 'Admin Options', 'buddypress' ) ?></a>

		<ul>
			<li><a class="confirm" href="<?php echo wp_nonce_url( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/delete-group/', 'groups_delete_group' ) ?>&amp;delete-group-button=1&amp;delete-group-understand=1"><?php _e( "Delete Group", 'buddypress' ) ?></a></li>

			<?php do_action( 'groups_adminbar_menu_items' ) ?>
		</ul>
	</li>
	<?php
}
add_action( 'bp_adminbar_menus', 'groups_setup_adminbar_menu', 20 );


/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

function groups_screen_my_groups() {
	global $bp;

	if ( isset($_GET['n']) ) {
		// Delete group request notifications for the user
		bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'membership_request_accepted' );
		bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'membership_request_rejected' );
		bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'member_promoted_to_mod' );
		bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'member_promoted_to_admin' );
	}

	do_action( 'groups_screen_my_groups' );

	bp_core_load_template( apply_filters( 'groups_template_my_groups', 'members/single/home' ) );
}

function groups_screen_group_invites() {
	global $bp;

	$group_id = $bp->action_variables[1];

	if ( isset($bp->action_variables) && in_array( 'accept', (array)$bp->action_variables ) && is_numeric($group_id) ) {
		/* Check the nonce */
		if ( !check_admin_referer( 'groups_accept_invite' ) )
			return false;

		if ( !groups_accept_invite( $bp->loggedin_user->id, $group_id ) ) {
			bp_core_add_message( __('Group invite could not be accepted', 'buddypress'), 'error' );
		} else {
			bp_core_add_message( __('Group invite accepted', 'buddypress') );

			/* Record this in activity streams */
			$group = new BP_Groups_Group( $group_id );

			groups_record_activity( array(
				'action' => apply_filters( 'groups_activity_accepted_invite_action', sprintf( __( '%s joined the group %s', 'buddypress'), bp_core_get_userlink( $bp->loggedin_user->id ), '<a href="' . bp_get_group_permalink( $group ) . '">' . attribute_escape( $group->name ) . '</a>' ), $bp->loggedin_user->id, &$group ),
				'type' => 'joined_group',
				'item_id' => $group->id
			) );
		}

		bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component . '/' . $bp->current_action );

	} else if ( isset($bp->action_variables) && in_array( 'reject', (array)$bp->action_variables ) && is_numeric($group_id) ) {
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

	bp_core_load_template( apply_filters( 'groups_template_group_invites', 'members/single/home' ) );
}

function groups_screen_group_home() {
	global $bp;

	if ( $bp->is_single_item ) {
		if ( isset($_GET['n']) ) {
			// Delete group request notifications for the user
			bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'membership_request_accepted' );
			bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'membership_request_rejected' );
			bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'member_promoted_to_mod' );
			bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->groups->slug, 'member_promoted_to_admin' );
		}

		do_action( 'groups_screen_group_home' );

		bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
	}
}

function groups_screen_group_forum() {
	global $bp;

	if ( $bp->is_single_item && $bp->groups->current_group->user_has_access ) {

		/* Fetch the details we need */
		$topic_slug = $bp->action_variables[1];
		$topic_id = bp_forums_get_topic_id_from_slug( $topic_slug );
		$forum_id = groups_get_groupmeta( $bp->groups->current_group->id, 'forum_id' );

		if ( $topic_slug && $topic_id ) {

			/* Posting a reply */
			if ( !$bp->action_variables[2] && isset( $_POST['submit_reply'] ) ) {
				/* Check the nonce */
				check_admin_referer( 'bp_forums_new_reply' );

				/* Auto join this user if they are not yet a member of this group */
				if ( !is_site_admin() && 'public' == $bp->groups->current_group->status && !groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) )
					groups_join_group( $bp->groups->current_group->id, $bp->loggedin_user->id );

				if ( !$post_id = groups_new_group_forum_post( $_POST['reply_text'], $topic_id, $_GET['topic_page'] ) )
					bp_core_add_message( __( 'There was an error when replying to that topic', 'buddypress'), 'error' );
				else
					bp_core_add_message( __( 'Your reply was posted successfully', 'buddypress') );

				if ( $_SERVER['QUERY_STRING'] )
					$query_vars = '?' . $_SERVER['QUERY_STRING'];

				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic_slug . '/' . $query_vars . '#post-' . $post_id );
			}

			/* Sticky a topic */
			else if ( 'stick' == $bp->action_variables[2] && ( $bp->is_item_admin || $bp->is_item_mod ) ) {
				/* Check the nonce */
				check_admin_referer( 'bp_forums_stick_topic' );

				if ( !bp_forums_sticky_topic( array( 'topic_id' => $topic_id ) ) )
					bp_core_add_message( __( 'There was an error when making that topic a sticky', 'buddypress' ), 'error' );
				else
					bp_core_add_message( __( 'The topic was made sticky successfully', 'buddypress' ) );

				do_action( 'groups_stick_forum_topic', $topic_id );
				bp_core_redirect( wp_get_referer() );
			}

			/* Un-Sticky a topic */
			else if ( 'unstick' == $bp->action_variables[2] && ( $bp->is_item_admin || $bp->is_item_mod ) ) {
				/* Check the nonce */
				check_admin_referer( 'bp_forums_unstick_topic' );

				if ( !bp_forums_sticky_topic( array( 'topic_id' => $topic_id, 'mode' => 'unstick' ) ) )
					bp_core_add_message( __( 'There was an error when unsticking that topic', 'buddypress'), 'error' );
				else
					bp_core_add_message( __( 'The topic was unstuck successfully', 'buddypress') );

				do_action( 'groups_unstick_forum_topic', $topic_id );
				bp_core_redirect( wp_get_referer() );
			}

			/* Close a topic */
			else if ( 'close' == $bp->action_variables[2] && ( $bp->is_item_admin || $bp->is_item_mod ) ) {
				/* Check the nonce */
				check_admin_referer( 'bp_forums_close_topic' );

				if ( !bp_forums_openclose_topic( array( 'topic_id' => $topic_id ) ) )
					bp_core_add_message( __( 'There was an error when closing that topic', 'buddypress'), 'error' );
				else
					bp_core_add_message( __( 'The topic was closed successfully', 'buddypress') );

				do_action( 'groups_close_forum_topic', $topic_id );
				bp_core_redirect( wp_get_referer() );
			}

			/* Open a topic */
			else if ( 'open' == $bp->action_variables[2] && ( $bp->is_item_admin || $bp->is_item_mod ) ) {
				/* Check the nonce */
				check_admin_referer( 'bp_forums_open_topic' );

				if ( !bp_forums_openclose_topic( array( 'topic_id' => $topic_id, 'mode' => 'open' ) ) )
					bp_core_add_message( __( 'There was an error when opening that topic', 'buddypress'), 'error' );
				else
					bp_core_add_message( __( 'The topic was opened successfully', 'buddypress') );

				do_action( 'groups_open_forum_topic', $topic_id );
				bp_core_redirect( wp_get_referer() );
			}

			/* Delete a topic */
			else if ( 'delete' == $bp->action_variables[2] && empty( $bp->action_variables[3] ) ) {
				/* Fetch the topic */
				$topic = bp_forums_get_topic_details( $topic_id );

				/* Check the logged in user can delete this topic */
				if ( !$bp->is_item_admin && !$bp->is_item_mod && (int)$bp->loggedin_user->id != (int)$topic->topic_poster )
					bp_core_redirect( wp_get_referer() );

				/* Check the nonce */
				check_admin_referer( 'bp_forums_delete_topic' );

				if ( !groups_delete_group_forum_topic( $topic_id ) )
					bp_core_add_message( __( 'There was an error deleting the topic', 'buddypress'), 'error' );
				else
					bp_core_add_message( __( 'The topic was deleted successfully', 'buddypress') );

				do_action( 'groups_delete_forum_topic', $topic_id );
				bp_core_redirect( wp_get_referer() );
			}

			/* Editing a topic */
			else if ( 'edit' == $bp->action_variables[2] && empty( $bp->action_variables[3] ) ) {
				/* Fetch the topic */
				$topic = bp_forums_get_topic_details( $topic_id );

				/* Check the logged in user can edit this topic */
				if ( !$bp->is_item_admin && !$bp->is_item_mod && (int)$bp->loggedin_user->id != (int)$topic->topic_poster )
					bp_core_redirect( wp_get_referer() );

				if ( isset( $_POST['save_changes'] ) ) {
					/* Check the nonce */
					check_admin_referer( 'bp_forums_edit_topic' );

					if ( !groups_update_group_forum_topic( $topic_id, $_POST['topic_title'], $_POST['topic_text'] ) )
						bp_core_add_message( __( 'There was an error when editing that topic', 'buddypress'), 'error' );
					else
						bp_core_add_message( __( 'The topic was edited successfully', 'buddypress') );

					do_action( 'groups_edit_forum_topic', $topic_id );
					bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic_slug . '/' );
				}

				bp_core_load_template( apply_filters( 'groups_template_group_forum_topic_edit', 'groups/single/home' ) );
			}

			/* Delete a post */
			else if ( 'delete' == $bp->action_variables[2] && $post_id = $bp->action_variables[4] ) {
				/* Fetch the post */
				$post = bp_forums_get_post( $post_id );

				/* Check the logged in user can edit this topic */
				if ( !$bp->is_item_admin && !$bp->is_item_mod && (int)$bp->loggedin_user->id != (int)$post->poster_id )
					bp_core_redirect( wp_get_referer() );

				/* Check the nonce */
				check_admin_referer( 'bp_forums_delete_post' );

				if ( !groups_delete_group_forum_post( $bp->action_variables[4], $topic_id ) )
					bp_core_add_message( __( 'There was an error deleting that post', 'buddypress'), 'error' );
				else
					bp_core_add_message( __( 'The post was deleted successfully', 'buddypress') );

				do_action( 'groups_delete_forum_post', $post_id );
				bp_core_redirect( wp_get_referer() );
			}

			/* Editing a post */
			else if ( 'edit' == $bp->action_variables[2] && $post_id = $bp->action_variables[4] ) {
				/* Fetch the post */
				$post = bp_forums_get_post( $bp->action_variables[4] );

				/* Check the logged in user can edit this topic */
				if ( !$bp->is_item_admin && !$bp->is_item_mod && (int)$bp->loggedin_user->id != (int)$post->poster_id )
					bp_core_redirect( wp_get_referer() );

				if ( isset( $_POST['save_changes'] ) ) {
					/* Check the nonce */
					check_admin_referer( 'bp_forums_edit_post' );

					if ( !$post_id = groups_update_group_forum_post( $post_id, $_POST['post_text'], $topic_id, $_GET['topic_page'] ) )
						bp_core_add_message( __( 'There was an error when editing that post', 'buddypress'), 'error' );
					else
						bp_core_add_message( __( 'The post was edited successfully', 'buddypress') );

					if ( $_SERVER['QUERY_STRING'] )
						$query_vars = '?' . $_SERVER['QUERY_STRING'];

					do_action( 'groups_edit_forum_post', $post_id );
					bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic_slug . '/' . $query_vars . '#post-' . $post_id );
				}

				bp_core_load_template( apply_filters( 'groups_template_group_forum_topic_edit', 'groups/single/home' ) );
			}

			/* Standard topic display */
			else {
				bp_core_load_template( apply_filters( 'groups_template_group_forum_topic', 'groups/single/home' ) );
			}

		} else {

			/* Posting a topic */
			if ( isset( $_POST['submit_topic'] ) && function_exists( 'bp_forums_new_topic') ) {
				/* Check the nonce */
				check_admin_referer( 'bp_forums_new_topic' );

				/* Auto join this user if they are not yet a member of this group */
				if ( !is_site_admin() && 'public' == $bp->groups->current_group->status && !groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) )
					groups_join_group( $bp->groups->current_group->id, $bp->loggedin_user->id );

				if ( !$topic = groups_new_group_forum_topic( $_POST['topic_title'], $_POST['topic_text'], $_POST['topic_tags'], $forum_id ) )
					bp_core_add_message( __( 'There was an error when creating the topic', 'buddypress'), 'error' );
				else
					bp_core_add_message( __( 'The topic was created successfully', 'buddypress') );

				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic->topic_slug . '/' );
			}

			do_action( 'groups_screen_group_forum', $topic_id, $forum_id );

			bp_core_load_template( apply_filters( 'groups_template_group_forum', 'groups/single/home' ) );
		}
	}
}

function groups_screen_group_members() {
	global $bp;

	if ( $bp->is_single_item ) {
		do_action( 'groups_screen_group_members', $bp->groups->current_group->id );
		bp_core_load_template( apply_filters( 'groups_template_group_members', 'groups/single/home' ) );
	}
}

function groups_screen_group_invite() {
	global $bp;

	if ( $bp->is_single_item ) {
		if ( isset($bp->action_variables) && 'send' == $bp->action_variables[0] ) {

			if ( !check_admin_referer( 'groups_send_invites', '_wpnonce_send_invites' ) )
				return false;

			// Send the invites.
			groups_send_invites( $bp->loggedin_user->id, $bp->groups->current_group->id );

			bp_core_add_message( __('Group invites sent.', 'buddypress') );

			do_action( 'groups_screen_group_invite', $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
		} else {
			// Show send invite page
			bp_core_load_template( apply_filters( 'groups_template_group_invite', 'groups/single/home' ) );
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

		bp_core_load_template( apply_filters( 'groups_template_group_request_membership', 'groups/single/home' ) );
	}
}

function groups_screen_group_activity_permalink() {
	global $bp;

	if ( $bp->current_component != $bp->groups->slug || $bp->current_action != $bp->activity->slug || empty( $bp->action_variables[0] ) )
		return false;

	$bp->is_single_item = true;

	bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
}
add_action( 'wp', 'groups_screen_group_activity_permalink', 3 );

function groups_screen_group_admin() {
	global $bp;

	if ( $bp->current_component != BP_GROUPS_SLUG || 'admin' != $bp->current_action )
		return false;

	if ( !empty( $bp->action_variables[0] ) )
		return false;

	bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/edit-details/' );
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

				if ( !groups_edit_base_group_details( $_POST['group-id'], $_POST['group-name'], $_POST['group-desc'], (int)$_POST['group-notify-members'] ) ) {
					bp_core_add_message( __( 'There was an error updating group details, please try again.', 'buddypress' ), 'error' );
				} else {
					bp_core_add_message( __( 'Group details were successfully updated.', 'buddypress' ) );
				}

				do_action( 'groups_group_details_edited', $bp->groups->current_group->id );

				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/edit-details/' );
			}

			do_action( 'groups_screen_group_admin_edit_details', $bp->groups->current_group->id );

			bp_core_load_template( apply_filters( 'groups_template_group_admin', 'groups/single/home' ) );
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
			$enable_forum = ( isset($_POST['group-show-forum'] ) ) ? 1 : 0;

			$allowed_status = apply_filters( 'groups_allowed_status', array( 'public', 'private', 'hidden' ) );
			$status = ( in_array( $_POST['group-status'], (array)$allowed_status ) ) ? $_POST['group-status'] : 'public';

			/* Check the nonce first. */
			if ( !check_admin_referer( 'groups_edit_group_settings' ) )
				return false;

			if ( !groups_edit_group_settings( $_POST['group-id'], $enable_forum, $status ) ) {
				bp_core_add_message( __( 'There was an error updating group settings, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'Group settings were successfully updated.', 'buddypress' ) );
			}

			do_action( 'groups_group_settings_edited', $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/group-settings/' );
		}

		do_action( 'groups_screen_group_admin_settings', $bp->groups->current_group->id );

		bp_core_load_template( apply_filters( 'groups_template_group_admin_settings', 'groups/single/home' ) );
	}
}
add_action( 'wp', 'groups_screen_group_admin_settings', 4 );

function groups_screen_group_admin_avatar() {
	global $bp;

	if ( $bp->current_component == $bp->groups->slug && 'group-avatar' == $bp->action_variables[0] ) {

		if ( !$bp->is_item_admin )
			return false;

		/* If the group admin has deleted the admin avatar */
		if ( 'delete' == $bp->action_variables[1] ) {

			/* Check the nonce */
			check_admin_referer( 'bp_group_avatar_delete' );

			if ( bp_core_delete_existing_avatar( array( 'item_id' => $bp->groups->current_group->id, 'object' => 'group' ) ) )
				bp_core_add_message( __( 'Your avatar was deleted successfully!', 'buddypress' ) );
			else
				bp_core_add_message( __( 'There was a problem deleting that avatar, please try again.', 'buddypress' ), 'error' );

		}

		$bp->avatar_admin->step = 'upload-image';

		if ( !empty( $_FILES ) ) {

			/* Check the nonce */
			check_admin_referer( 'bp_avatar_upload' );

			/* Pass the file to the avatar upload handler */
			if ( bp_core_avatar_handle_upload( $_FILES, 'groups_avatar_upload_dir' ) ) {
				$bp->avatar_admin->step = 'crop-image';

				/* Make sure we include the jQuery jCrop file for image cropping */
				add_action( 'wp', 'bp_core_add_jquery_cropper' );
			}

		}

		/* If the image cropping is done, crop the image and save a full/thumb version */
		if ( isset( $_POST['avatar-crop-submit'] ) ) {

			/* Check the nonce */
			check_admin_referer( 'bp_avatar_cropstore' );

			if ( !bp_core_avatar_handle_crop( array( 'object' => 'group', 'avatar_dir' => 'group-avatars', 'item_id' => $bp->groups->current_group->id, 'original_file' => $_POST['image_src'], 'crop_x' => $_POST['x'], 'crop_y' => $_POST['y'], 'crop_w' => $_POST['w'], 'crop_h' => $_POST['h'] ) ) )
				bp_core_add_message( __( 'There was a problem cropping the avatar, please try uploading it again', 'buddypress' ) );
			else
				bp_core_add_message( __( 'The new group avatar was uploaded successfully!', 'buddypress' ) );

		}

		do_action( 'groups_screen_group_admin_avatar', $bp->groups->current_group->id );

		bp_core_load_template( apply_filters( 'groups_template_group_admin_avatar', 'groups/single/home' ) );
	}
}
add_action( 'wp', 'groups_screen_group_admin_avatar', 4 );

function groups_screen_group_admin_manage_members() {
	global $bp;

	if ( $bp->current_component == $bp->groups->slug && 'manage-members' == $bp->action_variables[0] ) {

		if ( !$bp->is_item_admin )
			return false;

		if ( 'promote' == $bp->action_variables[1] && ( 'mod' == $bp->action_variables[2] || 'admin' == $bp->action_variables[2] ) && is_numeric( $bp->action_variables[3] ) ) {
			$user_id = $bp->action_variables[3];
			$status = $bp->action_variables[2];

			/* Check the nonce first. */
			if ( !check_admin_referer( 'groups_promote_member' ) )
				return false;

			// Promote a user.
			if ( !groups_promote_member( $user_id, $bp->groups->current_group->id, $status ) ) {
				bp_core_add_message( __( 'There was an error when promoting that user, please try again', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'User promoted successfully', 'buddypress' ) );
			}

			do_action( 'groups_promoted_member', $user_id, $bp->groups->current_group->id );

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/manage-members/' );
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

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/manage-members/' );
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

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/manage-members/' );
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

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/manage-members/' );
		}

		do_action( 'groups_screen_group_admin_manage_members', $bp->groups->current_group->id );

		bp_core_load_template( apply_filters( 'groups_template_group_admin_manage_members', 'groups/single/home' ) );
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

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/membership-requests/' );
		}

		do_action( 'groups_screen_group_admin_requests', $bp->groups->current_group->id );

		bp_core_load_template( apply_filters( 'groups_template_group_admin_requests', 'groups/single/home' ) );
	}
}
add_action( 'wp', 'groups_screen_group_admin_requests', 4 );

function groups_screen_group_admin_delete_group() {
	global $bp;

	if ( $bp->current_component == $bp->groups->slug && 'delete-group' == $bp->action_variables[0] ) {

		if ( !$bp->is_item_admin && !is_site_admin() )
			return false;

		if ( isset( $_REQUEST['delete-group-button'] ) && isset( $_REQUEST['delete-group-understand'] ) ) {
			/* Check the nonce first. */
			if ( !check_admin_referer( 'groups_delete_group' ) )
				return false;

			// Group admin has deleted the group, now do it.
			if ( !groups_delete_group( $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error deleting the group, please try again.', 'buddypress' ), 'error' );
			} else {
				bp_core_add_message( __( 'The group was deleted successfully', 'buddypress' ) );

				do_action( 'groups_group_deleted', $bp->groups->current_group->id );

				bp_core_redirect( $bp->loggedin_user->domain . $bp->groups->slug . '/' );
			}

			bp_core_redirect( $bp->loggedin_user->domain . $bp->current_component );
		}

		do_action( 'groups_screen_group_admin_delete_group', $bp->groups->current_group->id );

		bp_core_load_template( apply_filters( 'groups_template_group_admin_delete_group', 'groups/single/home' ) );
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

function groups_action_create_group() {
	global $bp;

	/* If we're not at domain.org/groups/create/ then return false */
	if ( $bp->current_component != $bp->groups->slug || 'create' != $bp->current_action )
		return false;

	if ( !is_user_logged_in() )
		return false;

	/* Make sure creation steps are in the right order */
	groups_action_sort_creation_steps();

	/* If no current step is set, reset everything so we can start a fresh group creation */
	if ( !$bp->groups->current_create_step = $bp->action_variables[1] ) {

		unset( $bp->groups->current_create_step );
		unset( $bp->groups->completed_create_steps );

		setcookie( 'bp_new_group_id', false, time() - 1000, COOKIEPATH );
		setcookie( 'bp_completed_create_steps', false, time() - 1000, COOKIEPATH );

		$reset_steps = true;
		bp_core_redirect( $bp->root_domain . '/' . $bp->groups->slug . '/create/step/' . array_shift( array_keys( $bp->groups->group_creation_steps ) ) . '/' );
	}

	/* If this is a creation step that is not recognized, just redirect them back to the first screen */
	if ( $bp->action_variables[1] && !$bp->groups->group_creation_steps[$bp->action_variables[1]] ) {
		bp_core_add_message( __('There was an error saving group details. Please try again.', 'buddypress'), 'error' );
		bp_core_redirect( $bp->root_domain . '/' . $bp->groups->slug . '/create/' );
	}

	/* Fetch the currently completed steps variable */
	if ( isset( $_COOKIE['bp_completed_create_steps'] ) && !$reset_steps )
		$bp->groups->completed_create_steps = unserialize( stripslashes( $_COOKIE['bp_completed_create_steps'] ) );

	/* Set the ID of the new group, if it has already been created in a previous step */
	if ( isset( $_COOKIE['bp_new_group_id'] ) ) {
		$bp->groups->new_group_id = $_COOKIE['bp_new_group_id'];
		$bp->groups->current_group = new BP_Groups_Group( $bp->groups->new_group_id );
	}

	/* If the save, upload or skip button is hit, lets calculate what we need to save */
	if ( isset( $_POST['save'] ) ) {

		/* Check the nonce */
		check_admin_referer( 'groups_create_save_' . $bp->groups->current_create_step );

		if ( 'group-details' == $bp->groups->current_create_step ) {
			if ( empty( $_POST['group-name'] ) || empty( $_POST['group-desc'] ) || !strlen( trim( $_POST['group-name'] ) ) || !strlen( trim( $_POST['group-desc'] ) ) ) {
				bp_core_add_message( __( 'Please fill in all of the required fields', 'buddypress' ), 'error' );
				bp_core_redirect( $bp->root_domain . '/' . $bp->groups->slug . '/create/step/' . $bp->groups->current_create_step . '/' );
			}

			if ( !$bp->groups->new_group_id = groups_create_group( array( 'group_id' => $bp->groups->new_group_id, 'name' => $_POST['group-name'], 'description' => $_POST['group-desc'], 'slug' => groups_check_slug( sanitize_title($_POST['group-name']) ), 'date_created' => gmdate( "Y-m-d H:i:s" ) ) ) ) {
				bp_core_add_message( __( 'There was an error saving group details, please try again.', 'buddypress' ), 'error' );
				bp_core_redirect( $bp->root_domain . '/' . $bp->groups->slug . '/create/step/' . $bp->groups->current_create_step . '/' );
			}

			groups_update_groupmeta( $bp->groups->new_group_id, 'total_member_count', 1 );
			groups_update_groupmeta( $bp->groups->new_group_id, 'last_activity', gmdate( "Y-m-d H:i:s" ) );
		}

		if ( 'group-settings' == $bp->groups->current_create_step ) {
			$group_status = 'public';
			$group_enable_forum = 1;

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

			if ( !$bp->groups->new_group_id = groups_create_group( array( 'group_id' => $bp->groups->new_group_id, 'status' => $group_status, 'enable_forum' => $group_enable_forum ) ) ) {
				bp_core_add_message( __( 'There was an error saving group details, please try again.', 'buddypress' ), 'error' );
				bp_core_redirect( $bp->root_domain . '/' . $bp->groups->slug . '/create/step/' . $bp->groups->current_create_step . '/' );
			}
		}

		if ( 'group-invites' == $bp->groups->current_create_step ) {
			groups_send_invites( $bp->loggedin_user->id, $bp->groups->new_group_id );
		}

		do_action( 'groups_create_group_step_save_' . $bp->groups->current_create_step );
		do_action( 'groups_create_group_step_complete' ); // Mostly for clearing cache on a generic action name

		/**
		 * Once we have successfully saved the details for this step of the creation process
		 * we need to add the current step to the array of completed steps, then update the cookies
		 * holding the information
		 */
		if ( !in_array( $bp->groups->current_create_step, (array)$bp->groups->completed_create_steps ) )
			$bp->groups->completed_create_steps[] = $bp->groups->current_create_step;

		/* Reset cookie info */
		setcookie( 'bp_new_group_id', $bp->groups->new_group_id, time()+60*60*24, COOKIEPATH );
		setcookie( 'bp_completed_create_steps', serialize( $bp->groups->completed_create_steps ), time()+60*60*24, COOKIEPATH );

		/* If we have completed all steps and hit done on the final step we can redirect to the completed group */
		if ( count( $bp->groups->completed_create_steps ) == count( $bp->groups->group_creation_steps ) && $bp->groups->current_create_step == array_pop( array_keys( $bp->groups->group_creation_steps ) ) ) {
			unset( $bp->groups->current_create_step );
			unset( $bp->groups->completed_create_steps );

			/* Once we compelete all steps, record the group creation in the activity stream. */
			groups_record_activity( array(
				'action' => apply_filters( 'groups_activity_created_group_action', sprintf( __( '%s created the group %s', 'buddypress'), bp_core_get_userlink( $bp->loggedin_user->id ), '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . attribute_escape( $bp->groups->current_group->name ) . '</a>' ) ),
				'type' => 'created_group',
				'item_id' => $bp->groups->new_group_id
			) );

			do_action( 'groups_group_create_complete', $bp->groups->new_group_id );

			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
		} else {
			/**
			 * Since we don't know what the next step is going to be (any plugin can insert steps)
			 * we need to loop the step array and fetch the next step that way.
			 */
			foreach ( (array)$bp->groups->group_creation_steps as $key => $value ) {
				if ( $key == $bp->groups->current_create_step ) {
					$next = 1;
					continue;
				}

				if ( $next ) {
					$next_step = $key;
					break;
				}
			}

			bp_core_redirect( $bp->root_domain . '/' . $bp->groups->slug . '/create/step/' . $next_step . '/' );
		}
	}

	/* Group avatar is handled separately */
	if ( 'group-avatar' == $bp->groups->current_create_step && isset( $_POST['upload'] ) ) {
		if ( !empty( $_FILES ) && isset( $_POST['upload'] ) ) {
			/* Normally we would check a nonce here, but the group save nonce is used instead */

			/* Pass the file to the avatar upload handler */
			if ( bp_core_avatar_handle_upload( $_FILES, 'groups_avatar_upload_dir' ) ) {
				$bp->avatar_admin->step = 'crop-image';

				/* Make sure we include the jQuery jCrop file for image cropping */
				add_action( 'wp', 'bp_core_add_jquery_cropper' );
			}
		}

		/* If the image cropping is done, crop the image and save a full/thumb version */
		if ( isset( $_POST['avatar-crop-submit'] ) && isset( $_POST['upload'] ) ) {
			/* Normally we would check a nonce here, but the group save nonce is used instead */

			if ( !bp_core_avatar_handle_crop( array( 'object' => 'group', 'avatar_dir' => 'group-avatars', 'item_id' => $bp->groups->current_group->id, 'original_file' => $_POST['image_src'], 'crop_x' => $_POST['x'], 'crop_y' => $_POST['y'], 'crop_w' => $_POST['w'], 'crop_h' => $_POST['h'] ) ) )
				bp_core_add_message( __( 'There was an error saving the group avatar, please try uploading again.', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'The group avatar was uploaded successfully!', 'buddypress' ) );
		}
	}

 	bp_core_load_template( apply_filters( 'groups_template_create_group', 'groups/create' ) );
}
add_action( 'wp', 'groups_action_create_group', 3 );

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

	bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
}
add_action( 'wp', 'groups_action_join_group', 3 );

function groups_action_sort_creation_steps() {
	global $bp;

	if ( $bp->current_component != BP_GROUPS_SLUG && $bp->current_action != 'create' )
		return false;

	if ( !is_array( $bp->groups->group_creation_steps ) )
		return false;

	foreach ( (array)$bp->groups->group_creation_steps as $slug => $step ) {
		while ( !empty( $temp[$step['position']] ) )
			$step['position']++;

		$temp[$step['position']] = array( 'name' => $step['name'], 'slug' => $slug );
	}

	/* Sort the steps by their position key */
	ksort($temp);
	unset($bp->groups->group_creation_steps);

	foreach( (array)$temp as $position => $step )
		$bp->groups->group_creation_steps[$step['slug']] = array( 'name' => $step['name'], 'position' => $position );
}

function groups_action_redirect_to_random_group() {
	global $bp, $wpdb;

	if ( $bp->current_component == $bp->groups->slug && isset( $_GET['random-group'] ) ) {
		$group = groups_get_random_groups( 1, 1 );

		bp_core_redirect( $bp->root_domain . '/' . $bp->groups->slug . '/' . $group['groups'][0]->slug );
	}
}
add_action( 'wp', 'groups_action_redirect_to_random_group', 6 );

function groups_action_group_feed() {
	global $bp, $wp_query;

	if ( $bp->current_component != $bp->groups->slug || !$bp->groups->current_group || $bp->current_action != 'feed' )
		return false;

	$wp_query->is_404 = false;
	status_header( 200 );

	if ( 'public' != $bp->groups->current_group->status ) {
		if ( !groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) )
			return false;
	}

	include_once( 'bp-activity/feeds/bp-activity-group-feed.php' );
	die;
}
add_action( 'wp', 'groups_action_group_feed', 3 );


/********************************************************************************
 * Activity & Notification Functions
 *
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 */

function groups_register_activity_actions() {
	global $bp;

	if ( !function_exists( 'bp_activity_set_action' ) )
		return false;

	bp_activity_set_action( $bp->groups->id, 'created_group', __( 'Created a group', 'buddypress' ) );
	bp_activity_set_action( $bp->groups->id, 'joined_group', __( 'Joined a group', 'buddypress' ) );
	bp_activity_set_action( $bp->groups->id, 'new_forum_topic', __( 'New group forum topic', 'buddypress' ) );
	bp_activity_set_action( $bp->groups->id, 'new_forum_post', __( 'New group forum post', 'buddypress' ) );

	do_action( 'groups_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'groups_register_activity_actions' );

function groups_record_activity( $args = '' ) {
	global $bp;

	if ( !function_exists( 'bp_activity_add' ) )
		return false;

	/* If the group is not public, hide the activity sitewide. */
	if ( 'public' == $bp->groups->current_group->status )
		$hide_sitewide = false;
	else
		$hide_sitewide = true;

	$defaults = array(
		'id' => false,
		'user_id' => $bp->loggedin_user->id,
		'action' => '',
		'content' => '',
		'primary_link' => '',
		'component' => $bp->groups->id,
		'type' => false,
		'item_id' => false,
		'secondary_item_id' => false,
		'recorded_time' => gmdate( "Y-m-d H:i:s" ),
		'hide_sitewide' => $hide_sitewide
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	return bp_activity_add( array( 'id' => $id, 'user_id' => $user_id, 'action' => $action, 'content' => $content, 'primary_link' => $primary_link, 'component' => $component, 'type' => $type, 'item_id' => $item_id, 'secondary_item_id' => $secondary_item_id, 'recorded_time' => $recorded_time, 'hide_sitewide' => $hide_sitewide ) );
}

function groups_update_last_activity( $group_id ) {
	groups_update_groupmeta( $group_id, 'last_activity', gmdate( "Y-m-d H:i:s" ) );
}
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

			$group = new BP_Groups_Group( $group_id );

			$group_link = bp_get_group_permalink( $group );

			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_groups_multiple_new_membership_requests_notification', '<a href="' . $group_link . '/admin/membership-requests/?n=1" title="' . __( 'Group Membership Requests', 'buddypress' ) . '">' . sprintf( __('%d new membership requests for the group "%s"', 'buddypress' ), (int)$total_items, $group->name ) . '</a>', $group_link, $total_items, $group->name );
			} else {
				$user_fullname = bp_core_get_user_displayname( $requesting_user_id );
				return apply_filters( 'bp_groups_single_new_membership_request_notification', '<a href="' . $group_link . 'admin/membership-requests/?n=1" title="' . $user_fullname .' requests group membership">' . sprintf( __('%s requests membership for the group "%s"', 'buddypress' ), $user_fullname, $group->name ) . '</a>', $group_link, $user_fullname, $group->name );
			}
		break;

		case 'membership_request_accepted':
			$group_id = $item_id;

			$group = new BP_Groups_Group( $group_id );
			$group_link = bp_get_group_permalink( $group );

			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_groups_multiple_membership_request_accepted_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '/?n=1" title="' . __( 'Groups', 'buddypress' ) . '">' . sprintf( __('%d accepted group membership requests', 'buddypress' ), (int)$total_items, $group->name ) . '</a>', $total_items, $group_name );
			} else {
				return apply_filters( 'bp_groups_single_membership_request_accepted_notification', '<a href="' . $group_link . '?n=1">' . sprintf( __('Membership for group "%s" accepted'), $group->name ) . '</a>', $group_link, $group->name );
			}
		break;

		case 'membership_request_rejected':
			$group_id = $item_id;

			$group = new BP_Groups_Group( $group_id );
			$group_link = bp_get_group_permalink( $group );

			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_groups_multiple_membership_request_rejected_notification', '<a href="' . site_url() . '/' . BP_MEMBERS_SLUG . '/' . $bp->groups->slug . '/?n=1" title="' . __( 'Groups', 'buddypress' ) . '">' . sprintf( __('%d rejected group membership requests', 'buddypress' ), (int)$total_items, $group->name ) . '</a>', $total_items, $group->name );
			} else {
				return apply_filters( 'bp_groups_single_membership_request_rejected_notification', '<a href="' . $group_link . '?n=1">' . sprintf( __('Membership for group "%s" rejected'), $group->name ) . '</a>', $group_link, $group->name );
			}

		break;

		case 'member_promoted_to_admin':
			$group_id = $item_id;

			$group = new BP_Groups_Group( $group_id );
			$group_link = bp_get_group_permalink( $group );

			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_groups_multiple_member_promoted_to_admin_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '/?n=1" title="' . __( 'Groups', 'buddypress' ) . '">' . sprintf( __('You were promoted to an admin in %d groups', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );
			} else {
				return apply_filters( 'bp_groups_single_member_promoted_to_admin_notification', '<a href="' . $group_link . '?n=1">' . sprintf( __('You were promoted to an admin in the group %s'), $group->name ) . '</a>', $group_link, $group->name );
			}
		break;

		case 'member_promoted_to_mod':
			$group_id = $item_id;

			$group = new BP_Groups_Group( $group_id );
			$group_link = bp_get_group_permalink( $group );

			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_groups_multiple_member_promoted_to_mod_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '/?n=1" title="' . __( 'Groups', 'buddypress' ) . '">' . sprintf( __('You were promoted to a mod in %d groups', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );
			} else {
				return apply_filters( 'bp_groups_single_member_promoted_to_mod_notification', '<a href="' . $group_link . '?n=1">' . sprintf( __('You were promoted to a mod in the group %s'), $group->name ) . '</a>', $group_link, $group->name );
			}
		break;

		case 'group_invite':
			$group_id = $item_id;

			$group = new BP_Groups_Group( $group_id );
			$user_url = bp_core_get_user_domain( $user_id );

			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_groups_multiple_group_invite_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '/invites/?n=1" title="' . __( 'Group Invites', 'buddypress' ) . '">' . sprintf( __('You have %d new group invitations', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );
			} else {
				return apply_filters( 'bp_groups_single_group_invite_notification', '<a href="' . $bp->loggedin_user->domain . $bp->groups->slug . '/invites/?n=1" title="' . __( 'Group Invites', 'buddypress' ) . '">' . sprintf( __('You have an invitation to the group: %s', 'buddypress' ), $group->name ) . '</a>', $group->name );
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

function groups_get_group( $args = '' ) {
	$defaults = array(
		'group_id' => false,
		'load_users' => false
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	return apply_filters( 'groups_get_group', new BP_Groups_Group( $group_id, true, $load_users ) );
}

/*** Group Creation, Editing & Deletion *****************************************/

function groups_create_group( $args = '' ) {
	global $bp;

	extract( $args );

	/**
	 * Possible parameters (pass as assoc array):
	 *	'group_id'
	 *	'creator_id'
	 *	'name'
	 *	'description'
	 *	'slug'
	 *	'status'
	 *	'enable_forum'
	 *	'date_created'
	 */

	if ( $group_id )
		$group = new BP_Groups_Group( $group_id );
	else
		$group = new BP_Groups_Group;

	if ( $creator_id )
		$group->creator_id = $creator_id;
	else
		$group->creator_id = $bp->loggedin_user->id;

	if ( isset( $name ) )
		$group->name = $name;

	if ( isset( $description ) )
		$group->description = $description;

	if ( isset( $slug ) && groups_check_slug( $slug ) )
		$group->slug = $slug;

	if ( isset( $status ) ) {
		if ( groups_is_valid_status( $status ) )
			$group->status = $status;
	}

	if ( isset( $enable_forum ) )
		$group->enable_forum = $enable_forum;
	else if ( !$group_id && !isset( $enable_forum ) )
		$group->enable_forum = 1;

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
		$member->date_modified = gmdate( "Y-m-d H:i:s" );

		$member->save();
	}

	do_action( 'groups_created_group', $group->id );

	return $group->id;
}

function groups_edit_base_group_details( $group_id, $group_name, $group_desc, $notify_members ) {
	global $bp;

	if ( empty( $group_name ) || empty( $group_desc ) )
		return false;

	$group = new BP_Groups_Group( $group_id );
	$group->name = $group_name;
	$group->description = $group_desc;

	if ( !$group->save() )
		return false;

	if ( $notify_members ) {
		require_once ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-notifications.php' );
		groups_notification_group_updated( $group->id );
	}

	do_action( 'groups_details_updated', $group->id );

	return true;
}

function groups_edit_group_settings( $group_id, $enable_forum, $status ) {
	global $bp;

	$group = new BP_Groups_Group( $group_id );
	$group->enable_forum = $enable_forum;

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

	/* Delete all group activity from activity streams */
	if ( function_exists( 'bp_activity_delete_by_item_id' ) ) {
		bp_activity_delete_by_item_id( array( 'item_id' => $group_id, 'component' => $bp->groups->id ) );
	}

	// Remove all outstanding invites for this group
	groups_delete_all_group_invites( $group_id );

	// Remove all notifications for any user belonging to this group
	bp_core_delete_all_notifications_by_type( $group_id, $bp->groups->slug );

	do_action( 'groups_delete_group', $group_id );

	return true;
}

function groups_is_valid_status( $status ) {
	global $bp;

	return in_array( $status, (array)$bp->groups->valid_status );
}

function groups_check_slug( $slug ) {
	global $bp;

	if ( 'wp' == substr( $slug, 0, 2 ) )
		$slug = substr( $slug, 2, strlen( $slug ) - 2 );

	if ( in_array( $slug, (array)$bp->groups->forbidden_names ) ) {
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
	$group = new BP_Groups_Group( $group_id );
	return $group->slug;
}

/*** User Actions ***************************************************************/

function groups_leave_group( $group_id, $user_id = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	/* Don't let single admins leave the group. */
	if ( count( groups_get_group_admins( $group_id ) ) < 2 ) {
		if ( groups_is_user_admin( $user_id, $group_id ) ) {
			bp_core_add_message( __( 'As the only Admin, you cannot leave the group.', 'buddypress' ), 'error' );
			return false;
		}
	}

	$membership = new BP_Groups_Member( $user_id, $group_id );

	// This is exactly the same as deleting an invite, just is_confirmed = 1 NOT 0.
	if ( !groups_uninvite_user( $user_id, $group_id ) )
		return false;

	/* Modify group member count */
	groups_update_groupmeta( $group_id, 'total_member_count', (int) groups_get_groupmeta( $group_id, 'total_member_count') - 1 );

	/* Modify user's group memberhip count */
	update_usermeta( $user_id, 'total_group_count', (int) get_usermeta( $user_id, 'total_group_count') - 1 );

	/* If the user joined this group less than five minutes ago, remove the joined_group activity so
	 * users cannot flood the activity stream by joining/leaving the group in quick succession.
	 */
	if ( function_exists( 'bp_activity_delete' ) && gmmktime() <= strtotime( '+5 minutes', (int)strtotime( $membership->date_modified ) ) )
		bp_activity_delete( array( 'component' => $bp->groups->id, 'type' => 'joined_group', 'user_id' => $user_id, 'item_id' => $group_id ) );

	bp_core_add_message( __( 'You successfully left the group.', 'buddypress' ) );

	do_action( 'groups_leave_group', $group_id, $user_id );

	return true;
}

function groups_join_group( $group_id, $user_id = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	/* Check if the user has an outstanding invite, is so delete it. */
	if ( groups_check_user_has_invite( $user_id, $group_id ) )
		groups_delete_invite( $user_id, $group_id );

	/* Check if the user has an outstanding request, is so delete it. */
	if ( groups_check_for_membership_request( $user_id, $group_id ) )
		groups_delete_membership_request( $user_id, $group_id );

	/* User is already a member, just return true */
	if ( groups_is_user_member( $user_id, $group_id ) )
		return true;

	if ( !$bp->groups->current_group )
		$bp->groups->current_group = new BP_Groups_Group( $group_id );

	$new_member = new BP_Groups_Member;
	$new_member->group_id = $group_id;
	$new_member->user_id = $user_id;
	$new_member->inviter_id = 0;
	$new_member->is_admin = 0;
	$new_member->user_title = '';
	$new_member->date_modified = gmdate( "Y-m-d H:i:s" );
	$new_member->is_confirmed = 1;

	if ( !$new_member->save() )
		return false;

	/* Record this in activity streams */
	groups_record_activity( array(
		'action' => apply_filters( 'groups_activity_joined_group', sprintf( __( '%s joined the group %s', 'buddypress'), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . attribute_escape( $bp->groups->current_group->name ) . '</a>' ) ),
		'type' => 'joined_group',
		'item_id' => $group_id
	) );

	/* Modify group meta */
	groups_update_groupmeta( $group_id, 'total_member_count', (int) groups_get_groupmeta( $group_id, 'total_member_count') + 1 );
	groups_update_groupmeta( $group_id, 'last_activity', gmdate( "Y-m-d H:i:s" ) );

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

function groups_get_all( $limit = null, $page = 1, $only_public = false, $sort_by = false, $order = false ) {
	return BP_Groups_Group::get_all( $limit, $page, $only_public, $sort_by, $order );
}

/***
 * All of the following get_() functions will return groups for the site globally.
 * If you pass a $user_id then the groups will be restricted to only those that the
 * user has joined.
 */
function groups_get_newest( $limit = null, $page = 1, $user_id = false, $search_terms = false ) {
	return BP_Groups_Group::get_newest( $limit, $page, $user_id, $search_terms );
}

function groups_get_active( $limit = null, $page = 1, $user_id = false, $search_terms = false ) {
	return BP_Groups_Group::get_active( $limit, $page, $user_id, $search_terms );
}

function groups_get_popular( $limit = null, $page = 1, $user_id = false, $search_terms = false ) {
	return BP_Groups_Group::get_popular( $limit, $page, $user_id, $search_terms );
}

function groups_get_random_groups( $limit = null, $page = 1, $user_id = false, $search_terms = false ) {
	return BP_Groups_Group::get_random( $limit, $page, $user_id, $search_terms );
}

function groups_get_alphabetically( $limit = null, $page = 1, $user_id = false, $search_terms = false ) {
	return BP_Groups_Group::get_alphabetically( $limit, $page, $user_id, $search_terms );
}

function groups_get_by_most_forum_topics( $limit = null, $page = 1, $user_id = false, $search_terms = false ) {
	return BP_Groups_Group::get_by_most_forum_topics( $limit, $page, $user_id, $search_terms );
}

function groups_get_by_most_forum_posts( $limit = null, $page = 1, $user_id = false, $search_terms = false ) {
	return BP_Groups_Group::get_by_most_forum_posts( $limit, $page, $user_id, $search_terms );
}

function groups_get_total_group_count() {
	if ( !$count = wp_cache_get( 'bp_total_group_count', 'bp' ) ) {
		$count = BP_Groups_Group::get_total_group_count();
		wp_cache_set( 'bp_total_group_count', $count, 'bp' );
	}

	return $count;
}

function groups_get_user_groups( $user_id = false, $pag_num = false, $pag_page = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->displayed_user->id;

	return BP_Groups_Member::get_group_ids( $user_id, $pag_num, $pag_page );
}

function groups_get_recently_joined_for_user( $user_id = false, $pag_num = false, $pag_page = false, $filter = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->displayed_user->id;

	return BP_Groups_Member::get_recently_joined( $user_id, $pag_num, $pag_page, $filter );
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
		$user_id = ( $bp->displayed_user->id ) ? $bp->displayed_user->id : $bp->loggedin_user->id;

	if ( !$count = wp_cache_get( 'bp_total_groups_for_user_' . $user_id, 'bp' ) ) {
		$count = BP_Groups_Member::total_group_count( $user_id );
		wp_cache_set( 'bp_total_groups_for_user_' . $user_id, $count, 'bp' );
	}

	return $count;
}

function groups_search_groups( $search_terms, $pag_num_per_page = 5, $pag_page = 1, $sort_by = false, $order = false ) {
	return BP_Groups_Group::search_groups( $search_terms, $pag_num_per_page, $pag_page, $sort_by, $order );
}

function groups_filter_user_groups( $filter, $user_id = false, $order = false, $pag_num_per_page = 5, $pag_page = 1 ) {
	return BP_Groups_Group::filter_user_groups( $filter, $user_id, $order, $pag_num_per_page, $pag_page );
}

/*** Group Avatars *************************************************************/

function groups_avatar_upload_dir( $group_id = false ) {
	global $bp;

	if ( !$group_id )
		$group_id = $bp->groups->current_group->id;

	$path = BP_AVATAR_UPLOAD_PATH . '/group-avatars/' . $group_id;
	$newbdir = $path;

	if ( !file_exists( $path ) )
		@wp_mkdir_p( $path );

	$newurl = str_replace( BP_AVATAR_UPLOAD_PATH, BP_AVATAR_URL, $path );
	$newburl = $newurl;
	$newsubdir = '/group-avatars/' . $group_id;

	return apply_filters( 'groups_avatar_upload_dir', array( 'path' => $path, 'url' => $newurl, 'subdir' => $newsubdir, 'basedir' => $newbdir, 'baseurl' => $newburl, 'error' => false ) );
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

/*** Group Activity Posting **************************************************/

function groups_post_update( $args = '' ) {
	global $bp;

	$defaults = array(
		'content' => false,
		'user_id' => $bp->loggedin_user->id,
		'group_id' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( empty($content) || !strlen( trim( $content ) ) || empty($user_id) || empty($group_id) )
		return false;

	$bp->groups->current_group = new BP_Groups_Group( $group_id );

	/* Be sure the user is a member of the group before posting. */
	if ( !is_site_admin() && !groups_is_user_member( $user_id, $group_id ) )
		return false;

	/* Record this in activity streams */
	$activity_action = sprintf( __( '%s posted an update in the group %s:', 'buddypress'), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . attribute_escape( $bp->groups->current_group->name ) . '</a>' );
	$activity_content = $content;

	$activity_id = groups_record_activity( array(
		'user_id' => $user_id,
		'action' => apply_filters( 'groups_activity_new_update_action', $activity_action ),
		'content' => apply_filters( 'groups_activity_new_update_content', $activity_content ),
		'type' => 'activity_update',
		'item_id' => $bp->groups->current_group->id
	) );

 	/* Require the notifications code so email notifications can be set on the 'bp_activity_posted_update' action. */
	require_once( BP_PLUGIN_DIR . '/bp-groups/bp-groups-notifications.php' );

	groups_update_groupmeta( $group_id, 'last_activity', gmdate( "Y-m-d H:i:s" ) );
	do_action( 'bp_groups_posted_update', $content, $user_id, $group_id, $activity_id );

	return $activity_id;
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

	$forum_id = bp_forums_new_forum( array( 'forum_name' => $group_name, 'forum_desc' => $group_desc ) );

	groups_update_groupmeta( $group_id, 'forum_id', $forum_id );

	do_action( 'groups_new_group_forum', $forum, $group_id );
}

function groups_new_group_forum_post( $post_text, $topic_id, $page = false ) {
	global $bp;

	if ( empty( $post_text ) )
		return false;

	$post_text = apply_filters( 'group_forum_post_text_before_save', $post_text );
	$topic_id = apply_filters( 'group_forum_post_topic_id_before_save', $topic_id );

	if ( $post_id = bp_forums_insert_post( array( 'post_text' => $post_text, 'topic_id' => $topic_id ) ) ) {
		$topic = bp_forums_get_topic_details( $topic_id );

		$activity_action = sprintf( __( '%s posted on the forum topic %s in the group %s:', 'buddypress'), bp_core_get_userlink( $bp->loggedin_user->id ), '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic->topic_slug .'/">' . attribute_escape( $topic->topic_title ) . '</a>', '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . attribute_escape( $bp->groups->current_group->name ) . '</a>' );
		$activity_content = '<blockquote>' . bp_create_excerpt( $post_text ) . '</blockquote>';
		$primary_link = bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic->topic_slug . '/';

		if ( $page )
			$primary_link .= "?topic_page=" . $page;

		/* Record this in activity streams */
		groups_record_activity( array(
			'action' => apply_filters( 'groups_activity_new_forum_post_action', $activity_action, $post_id, $post_text, &$topic ),
			'content' => apply_filters( 'groups_activity_new_forum_post_content', $activity_content, $post_id, $post_text, &$topic ),
			'primary_link' => apply_filters( 'groups_activity_new_forum_post_primary_link', "{$primary_link}#post-{$post_id}" ),
			'type' => 'new_forum_post',
			'item_id' => $bp->groups->current_group->id,
			'secondary_item_id' => $post_id
		) );

		do_action( 'groups_new_forum_topic_post', $bp->groups->current_group->id, $post_id );

		return $post_id;
	}

	return false;
}

function groups_new_group_forum_topic( $topic_title, $topic_text, $topic_tags, $forum_id ) {
	global $bp;

	if ( empty( $topic_title ) || empty( $topic_text ) )
		return false;

	$topic_title = apply_filters( 'group_forum_topic_title_before_save', $topic_title );
	$topic_text = apply_filters( 'group_forum_topic_text_before_save', $topic_text );
	$topic_tags = apply_filters( 'group_forum_topic_tags_before_save', $topic_tags );
	$forum_id = apply_filters( 'group_forum_topic_forum_id_before_save', $forum_id );

	if ( $topic_id = bp_forums_new_topic( array( 'topic_title' => $topic_title, 'topic_text' => $topic_text, 'topic_tags' => $topic_tags, 'forum_id' => $forum_id ) ) ) {
		$topic = bp_forums_get_topic_details( $topic_id );

		$activity_action = sprintf( __( '%s started the forum topic %s in the group %s:', 'buddypress'), bp_core_get_userlink( $bp->loggedin_user->id ), '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic->topic_slug .'/">' . attribute_escape( $topic->topic_title ) . '</a>', '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . attribute_escape( $bp->groups->current_group->name ) . '</a>' );
		$activity_content = '<blockquote>' . bp_create_excerpt( $topic_text ) . '</blockquote>';

		/* Record this in activity streams */
		groups_record_activity( array(
			'action' => apply_filters( 'groups_activity_new_forum_topic_action', $activity_action, $topic_text, &$topic ),
			'content' => apply_filters( 'groups_activity_new_forum_topic_content', $activity_content, $topic_text, &$topic ),
			'primary_link' => apply_filters( 'groups_activity_new_forum_topic_primary_link', bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic->topic_slug . '/' ),
			'type' => 'new_forum_topic',
			'item_id' => $bp->groups->current_group->id,
			'secondary_item_id' => $topic->topic_id
		) );

		do_action( 'groups_new_forum_topic', $bp->groups->current_group->id, &$topic );

		return $topic;
	}

	return false;
}

function groups_update_group_forum_topic( $topic_id, $topic_title, $topic_text ) {
	global $bp;

	$topic_title = apply_filters( 'group_forum_topic_title_before_save', $topic_title );
	$topic_text = apply_filters( 'group_forum_topic_text_before_save', $topic_text );

	if ( $topic = bp_forums_update_topic( array( 'topic_title' => $topic_title, 'topic_text' => $topic_text, 'topic_id' => $topic_id ) ) ) {
		/* Update the activity stream item */
		if ( function_exists( 'bp_activity_delete_by_item_id' ) )
			bp_activity_delete_by_item_id( array( 'item_id' => $bp->groups->current_group->id, 'secondary_item_id' => $topic_id, 'component' => $bp->groups->id, 'type' => 'new_forum_topic' ) );

		$activity_action = sprintf( __( '%s started the forum topic %s in the group %s:', 'buddypress'), bp_core_get_userlink( $topic->topic_poster ), '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic->topic_slug .'/">' . attribute_escape( $topic->topic_title ) . '</a>', '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . attribute_escape( $bp->groups->current_group->name ) . '</a>' );
		$activity_content = '<blockquote>' . bp_create_excerpt( $topic_text ) . '</blockquote>';

		/* Record this in activity streams */
		groups_record_activity( array(
			'action' => apply_filters( 'groups_activity_new_forum_topic_action', $activity_action, $topic_text, &$topic ),
			'content' => apply_filters( 'groups_activity_new_forum_topic_content', $activity_content, $topic_text, &$topic ),
			'primary_link' => apply_filters( 'groups_activity_new_forum_topic_primary_link', bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic->topic_slug . '/' ),
			'type' => 'new_forum_topic',
			'item_id' => (int)$bp->groups->current_group->id,
			'user_id' => (int)$topic->topic_poster,
			'secondary_item_id' => $topic->topic_id,
			'recorded_time' => $topic->topic_time
		) );

		do_action( 'groups_update_group_forum_topic', &$topic );

		return $topic;
	}

	return false;
}

function groups_update_group_forum_post( $post_id, $post_text, $topic_id, $page = false ) {
	global $bp;

	$post_text = apply_filters( 'group_forum_post_text_before_save', $post_text );
	$topic_id = apply_filters( 'group_forum_post_topic_id_before_save', $topic_id );

	$post = bp_forums_get_post( $post_id );

	if ( $post_id = bp_forums_insert_post( array( 'post_id' => $post_id, 'post_text' => $post_text, 'post_time' => $post->post_time, 'topic_id' => $topic_id, 'poster_id' => $post->poster_id ) ) ) {
		$topic = bp_forums_get_topic_details( $topic_id );

		$activity_action = sprintf( __( '%s posted on the forum topic %s in the group %s:', 'buddypress'), bp_core_get_userlink( $post->poster_id ), '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic->topic_slug .'">' . attribute_escape( $topic->topic_title ) . '</a>', '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . attribute_escape( $bp->groups->current_group->name ) . '</a>' );
		$activity_content = '<blockquote>' . bp_create_excerpt( $post_text ) . '</blockquote>';
		$primary_link = bp_get_group_permalink( $bp->groups->current_group ) . 'forum/topic/' . $topic->topic_slug . '/';

		if ( $page )
			$primary_link .= "?topic_page=" . $page;

		/* Fetch an existing entry and update if one exists. */
		if ( function_exists( 'bp_activity_get_activity_id' ) )
			$id = bp_activity_get_activity_id( array( 'user_id' => $post->poster_id, 'component' => $bp->groups->id, 'type' => 'new_forum_post', 'item_id' => $bp->groups->current_group->id, 'secondary_item_id' => $post_id ) );

		/* Update the entry in activity streams */
		groups_record_activity( array(
			'id' => $id,
			'action' => apply_filters( 'groups_activity_new_forum_post_action', $activity_action, $post_text, &$topic, &$forum_post ),
			'content' => apply_filters( 'groups_activity_new_forum_post_content', $activity_content, $post_text, &$topic, &$forum_post ),
			'primary_link' => apply_filters( 'groups_activity_new_forum_post_primary_link', $primary_link . "#post-" . $post_id ),
			'type' => 'new_forum_post',
			'item_id' => (int)$bp->groups->current_group->id,
			'user_id' => (int)$post->poster_id,
			'secondary_item_id' => $post_id,
			'recorded_time' => $post->post_time
		) );

		do_action( 'groups_update_group_forum_post', &$post, &$topic );

		return $post_id;
	}

	return false;
}

function groups_delete_group_forum_topic( $topic_id ) {
	global $bp;

	if ( bp_forums_delete_topic( array( 'topic_id' => $topic_id ) ) ) {
		/* Delete the activity stream item */
		if ( function_exists( 'bp_activity_delete_by_item_id' ) ) {
			bp_activity_delete_by_item_id( array( 'item_id' => $topic_id, 'component' => $bp->groups->id, 'type' => 'new_forum_topic' ) );
			bp_activity_delete_by_item_id( array( 'item_id' => $topic_id, 'component' => $bp->groups->id, 'type' => 'new_forum_post' ) );
		}

		do_action( 'groups_delete_group_forum_topic', $topic_id );

		return true;
	}

	return false;
}

function groups_delete_group_forum_post( $post_id, $topic_id ) {
	global $bp;

	if ( bp_forums_delete_post( array( 'post_id' => $post_id ) ) ) {
		/* Delete the activity stream item */
		if ( function_exists( 'bp_activity_delete_by_item_id' ) ) {
			bp_activity_delete_by_item_id( array( 'item_id' => $bp->groups->current_group->id, 'secondary_item_id' => $post_id, 'component' => $bp->groups->id, 'type' => 'new_forum_post' ) );
		}

		do_action( 'groups_delete_group_forum_post', $post_id, $topic_id );

		return true;
	}

	return false;
}


function groups_total_public_forum_topic_count( $type = 'newest' ) {
	return apply_filters( 'groups_total_public_forum_topic_count', BP_Groups_Group::get_global_forum_topic_count( $type ) );
}

/*** Group Invitations *********************************************************/

function groups_get_invites_for_user( $user_id = false, $limit = false, $page = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	return BP_Groups_Member::get_invites( $user_id, $limit, $page );
}

function groups_invite_user( $args = '' ) {
	global $bp;

	$defaults = array(
		'user_id' => false,
		'group_id' => false,
		'inviter_id' => $bp->loggedin_user->id,
		'date_modified' => gmdate( "Y-m-d H:i:s" ),
		'is_confirmed' => 0
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	if ( !$user_id || !$group_id )
		return false;

	if ( !groups_is_user_member( $user_id, $group_id ) && !groups_check_user_has_invite( $user_id, $group_id ) ) {
		$invite = new BP_Groups_Member;
		$invite->group_id = $group_id;
		$invite->user_id = $user_id;
		$invite->date_modified = $date_modified;
		$invite->inviter_id = $inviter_id;
		$invite->is_confirmed = $is_confirmed;

		if ( !$invite->save() )
			return false;

		do_action( 'groups_invite_user', $args );
	}

	return true;
}

function groups_uninvite_user( $user_id, $group_id ) {
	global $bp;

	if ( !BP_Groups_Member::delete( $user_id, $group_id ) )
		return false;

	do_action( 'groups_uninvite_user', $group_id, $user_id );

	return true;
}

function groups_accept_invite( $user_id, $group_id ) {
	global $bp;

	if ( groups_is_user_member( $user_id, $group_id ) )
		return false;

	$member = new BP_Groups_Member( $user_id, $group_id );
	$member->accept_invite();

	if ( !$member->save() )
		return false;

	/* Modify group meta */
	groups_update_groupmeta( $group_id, 'total_member_count', (int) groups_get_groupmeta( $group_id, 'total_member_count') + 1 );
	groups_update_groupmeta( $group_id, 'last_activity', gmdate( "Y-m-d H:i:s" ) );

	bp_core_delete_notifications_for_user_by_item_id( $user_id, $group_id, $bp->groups->slug, 'group_invite' );

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
	$group = new BP_Groups_Group( $group_id );

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

function groups_promote_member( $user_id, $group_id, $status ) {
	global $bp;

	if ( !$bp->is_item_admin )
		return false;

	$member = new BP_Groups_Member( $user_id, $group_id );

	do_action( 'groups_premote_member', $group_id, $user_id, $status );

	return $member->promote( $status );
}

function groups_demote_member( $user_id, $group_id ) {
	global $bp;

	$member = new BP_Groups_Member( $user_id, $group_id );

	do_action( 'groups_demote_member', $group_id, $user_id );

	return $member->demote();
}

function groups_ban_member( $user_id, $group_id ) {
	global $bp;

	if ( !$bp->is_item_admin )
		return false;

	$member = new BP_Groups_Member( $user_id, $group_id );

	do_action( 'groups_ban_member', $group_id, $user_id );

	if ( !$member->ban() )
		return false;

	update_usermeta( $user_id, 'total_group_count', (int)$total_count - 1 );
}

function groups_unban_member( $user_id, $group_id ) {
	global $bp;

	if ( !$bp->is_item_admin )
		return false;

	$member = new BP_Groups_Member( $user_id, $group_id );

	do_action( 'groups_unban_member', $group_id, $user_id );

	return $member->unban();
}

/*** Group Membership ****************************************************/

function groups_send_membership_request( $requesting_user_id, $group_id ) {
	global $bp;

	/* Prevent duplicate requests */
	if ( groups_check_for_membership_request( $requesting_user_id, $group_id ) )
		return false;

	/* Check if the user is already a member or is banned */
	if ( groups_is_user_member( $requesting_user_id, $group_id ) || groups_is_user_banned( $requesting_user_id, $group_id ) )
		return false;

	$requesting_user = new BP_Groups_Member;
	$requesting_user->group_id = $group_id;
	$requesting_user->user_id = $requesting_user_id;
	$requesting_user->inviter_id = 0;
	$requesting_user->is_admin = 0;
	$requesting_user->user_title = '';
	$requesting_user->date_modified = gmdate( "Y-m-d H:i:s" );
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
	$group = new BP_Groups_Group( $group_id );

	groups_record_activity( array(
		'action' => apply_filters( 'groups_activity_membership_accepted_action', sprintf( __( '%s joined the group %s', 'buddypress'), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_group_permalink( $group ) . '">' . attribute_escape( $group->name ) . '</a>' ), $user_id, &$group ),
		'type' => 'joined_group',
		'item_id' => $group->id,
		'user_id' => $user_id
	) );

	/* Send a notification to the user. */
	require_once ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-notifications.php' );
	groups_notification_membership_request_completed( $membership->user_id, $membership->group_id, true );

	do_action( 'groups_membership_accepted', $membership->user_id, $membership->group_id );

	return true;
}

function groups_reject_membership_request( $membership_id, $user_id = false, $group_id = false ) {
	if ( !$membership = groups_delete_membership_request( $membership_id, $user_id, $group_id ) )
		return false;

	// Send a notification to the user.
	require_once ( BP_PLUGIN_DIR . '/bp-groups/bp-groups-notifications.php' );
	groups_notification_membership_request_completed( $membership->user_id, $membership->group_id, false );

	do_action( 'groups_membership_rejected', $membership->user_id, $membership->group_id );

	return true;
}

function groups_delete_membership_request( $membership_id, $user_id = false, $group_id = false ) {
	if ( $user_id && $group_id )
		$membership = new BP_Groups_Member( $user_id, $group_id );
	else
		$membership = new BP_Groups_Member( false, false, $membership_id );

	if ( !BP_Groups_Member::delete( $membership->user_id, $membership->group_id ) )
		return false;

	return $membership;
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

	/* Delete the cached object */
	wp_cache_delete( 'bp_groups_groupmeta_' . $group_id . '_' . $meta_key, 'bp' );

	return true;
}

function groups_get_groupmeta( $group_id, $meta_key = '') {
	global $wpdb, $bp;

	$group_id = (int) $group_id;

	if ( !$group_id )
		return false;

	if ( !empty($meta_key) ) {
		$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

		if ( !$metas = wp_cache_get( 'bp_groups_groupmeta_' . $group_id . '_' . $meta_key, 'bp' ) ) {
			$metas = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM " . $bp->groups->table_name_groupmeta . " WHERE group_id = %d AND meta_key = %s", $group_id, $meta_key) );
			wp_cache_set( 'bp_groups_groupmeta_' . $group_id . '_' . $meta_key, $metas, 'bp' );
		}
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

	/* Update the cached object and recache */
	wp_cache_set( 'bp_groups_groupmeta_' . $group_id . '_' . $meta_key, $meta_value, 'bp' );

	return true;
}

/*** Group Cleanup Functions ****************************************************/

function groups_remove_data_for_user( $user_id ) {
	BP_Groups_Member::delete_all_for_user($user_id);

	do_action( 'groups_remove_data_for_user', $user_id );
}
add_action( 'wpmu_delete_user', 'groups_remove_data_for_user', 1 );
add_action( 'delete_user', 'groups_remove_data_for_user', 1 );
add_action( 'make_spam_user', 'groups_remove_data_for_user', 1 );


/********************************************************************************
 * Caching
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 */

function groups_clear_group_object_cache( $group_id ) {
	wp_cache_delete( 'groups_group_nouserdata_' . $group_id, 'bp' );
	wp_cache_delete( 'groups_group_' . $group_id, 'bp' );
	wp_cache_delete( 'newest_groups', 'bp' );
	wp_cache_delete( 'active_groups', 'bp' );
	wp_cache_delete( 'popular_groups', 'bp' );
	wp_cache_delete( 'groups_random_groups', 'bp' );
	wp_cache_delete( 'bp_total_group_count', 'bp' );
}
add_action( 'groups_group_deleted', 'groups_clear_group_object_cache' );
add_action( 'groups_settings_updated', 'groups_clear_group_object_cache' );
add_action( 'groups_details_updated', 'groups_clear_group_object_cache' );
add_action( 'groups_group_avatar_updated', 'groups_clear_group_object_cache' );
add_action( 'groups_create_group_step_complete', 'groups_clear_group_object_cache' );

function groups_clear_group_user_object_cache( $group_id, $user_id ) {
	wp_cache_delete( 'bp_total_groups_for_user_' . $user_id );
}
add_action( 'groups_join_group', 'groups_clear_group_user_object_cache', 10, 2 );
add_action( 'groups_leave_group', 'groups_clear_group_user_object_cache', 10, 2 );
add_action( 'groups_ban_member', 'groups_clear_group_user_object_cache', 10, 2 );
add_action( 'groups_unban_member', 'groups_clear_group_user_object_cache', 10, 2 );

/* List actions to clear super cached pages on, if super cache is installed */
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