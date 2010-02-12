<?php
define ( 'BP_XPROFILE_DB_VERSION', '1850' );

/* Define the slug for the component */
if ( !defined( 'BP_XPROFILE_SLUG' ) )
	define ( 'BP_XPROFILE_SLUG', 'profile' );

require ( BP_PLUGIN_DIR . '/bp-xprofile/bp-xprofile-admin.php' );
require ( BP_PLUGIN_DIR . '/bp-xprofile/bp-xprofile-classes.php' );
require ( BP_PLUGIN_DIR . '/bp-xprofile/bp-xprofile-filters.php' );
require ( BP_PLUGIN_DIR . '/bp-xprofile/bp-xprofile-templatetags.php' );
require ( BP_PLUGIN_DIR . '/bp-xprofile/bp-xprofile-cssjs.php' );

/**
 * xprofile_install()
 *
 * Set up the database tables needed for the xprofile component.
 *
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses dbDelta() Takes SQL statements and compares them to any existing tables and creates/updates them.
 * @uses add_site_option() adds a value for a meta_key into the wp_sitemeta table
 */
function xprofile_install() {
	global $bp, $wpdb;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	if ( empty( $bp->option['bp-xprofile-base-group-name'] ) )
		update_site_option( 'bp-xprofile-base-group-name', 'Base' );

	if ( empty( $bp->option['bp-xprofile-fullname-field-name'] ) )
		update_site_option( 'bp-xprofile-fullname-field-name', 'Name' );

	$sql[] = "CREATE TABLE {$bp->profile->table_name_groups} (
			  id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  name varchar(150) NOT NULL,
			  description mediumtext NOT NULL,
			  can_delete tinyint(1) NOT NULL,
			  KEY can_delete (can_delete)
	) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp->profile->table_name_fields} (
			  id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  group_id bigint(20) unsigned NOT NULL,
			  parent_id bigint(20) unsigned NOT NULL,
			  type varchar(150) NOT NULL,
			  name varchar(150) NOT NULL,
			  description longtext NOT NULL,
			  is_required tinyint(1) NOT NULL DEFAULT '0',
			  is_default_option tinyint(1) NOT NULL DEFAULT '0',
			  field_order bigint(20) NOT NULL DEFAULT '0',
			  option_order bigint(20) NOT NULL DEFAULT '0',
			  order_by varchar(15) NOT NULL,
			  can_delete tinyint(1) NOT NULL DEFAULT '1',
			  KEY group_id (group_id),
			  KEY parent_id (parent_id),
			  KEY field_order (field_order),
			  KEY can_delete (can_delete),
			  KEY is_required (is_required)
	) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp->profile->table_name_data} (
			  id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  field_id bigint(20) unsigned NOT NULL,
			  user_id bigint(20) unsigned NOT NULL,
			  value longtext NOT NULL,
			  last_updated datetime NOT NULL,
			  KEY field_id (field_id),
			  KEY user_id (user_id)
	) {$charset_collate};";

	if ( empty( $bp->option['bp-xprofile-db-version'] ) ) {
		if ( !$wpdb->get_var( "SELECT id FROM {$bp->profile->table_name_groups} WHERE id = 1" ) )
			$sql[] = "INSERT INTO {$bp->profile->table_name_groups} VALUES ( 1, '" . $bp->option['bp-xprofile-base-group-name'] . "', '', 0 );";

		if ( !$wpdb->get_var( "SELECT id FROM {$bp->profile->table_name_fields} WHERE id = 1" ) ) {
			$sql[] = "INSERT INTO {$bp->profile->table_name_fields} (
						id, group_id, parent_id, type, name, is_required, can_delete
					  ) VALUES (
						1, 1, 0, 'textbox', '" . $bp->option['bp-xprofile-fullname-field-name'] . "', 1, 0
					  );";
		}
	}

	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	dbDelta($sql);

	do_action( 'xprofile_install' );

	update_site_option( 'bp-xprofile-db-version', BP_XPROFILE_DB_VERSION );
}

/**
 * xprofile_setup_globals()
 *
 * Add the profile globals to the $bp global for use across the installation
 *
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @uses site_url() Returns the site URL
 */
function xprofile_setup_globals() {
	global $bp, $wpdb;

	/* Assign the base group and fullname field names to constants to use in SQL statements */
	define ( 'BP_XPROFILE_BASE_GROUP_NAME', $bp->site_options['bp-xprofile-base-group-name'] );
	define ( 'BP_XPROFILE_FULLNAME_FIELD_NAME', $bp->site_options['bp-xprofile-fullname-field-name'] );

	/* For internal identification */
	$bp->profile->id = 'profile';

	$bp->profile->table_name_groups = $wpdb->base_prefix . 'bp_xprofile_groups';
	$bp->profile->table_name_fields = $wpdb->base_prefix . 'bp_xprofile_fields';
	$bp->profile->table_name_data = $wpdb->base_prefix . 'bp_xprofile_data';

	$bp->profile->format_notification_function = 'xprofile_format_notifications';
	$bp->profile->slug = BP_XPROFILE_SLUG;

	/* Register this in the active components array */
	$bp->active_components[$bp->profile->slug] = $bp->profile->id;

	/* Set the support field type ids */
	$bp->profile->field_types = apply_filters( 'xprofile_field_types', array( 'textbox', 'textarea', 'radio', 'checkbox', 'selectbox', 'multiselectbox', 'datebox' ) );

	do_action( 'xprofile_setup_globals' );
}
add_action( 'bp_setup_globals', 'xprofile_setup_globals' );

/**
 * xprofile_add_admin_menu()
 *
 * Creates the administration interface menus and checks to see if the DB
 * tables are set up.
 *
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @uses is_site_admin() returns true if the current user is a site admin, false if not
 * @uses bp_xprofile_install() runs the installation of DB tables for the xprofile component
 * @uses wp_enqueue_script() Adds a JS file to the JS queue ready for output
 * @uses add_submenu_page() Adds a submenu tab to a top level tab in the admin area
 * @uses xprofile_install() Runs the DB table installation function
 * @return
 */
function xprofile_add_admin_menu() {
	global $wpdb, $bp;

	if ( !is_site_admin() )
		return false;

	/* Add the administration tab under the "Site Admin" tab for site administrators */
	add_submenu_page( 'bp-general-settings', __("Profile Field Setup", 'buddypress'), __("Profile Field Setup", 'buddypress'), 'manage_options', 'bp-profile-setup', "xprofile_admin" );

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( $bp->option['bp-xprofile-db-version'] < BP_XPROFILE_DB_VERSION )
		xprofile_install();
}
add_action( 'admin_menu', 'xprofile_add_admin_menu' );

/**
 * xprofile_setup_nav()
 *
 * Sets up the navigation items for the xprofile component
 *
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_core_new_nav_item() Adds a navigation item to the top level buddypress navigation
 * @uses bp_core_new_subnav_item() Adds a sub navigation item to a nav item
 * @uses bp_is_my_profile() Returns true if the current user being viewed is equal the logged in user
 * @uses bp_core_fetch_avatar() Returns the either the thumb or full avatar URL for the user_id passed
 */
function xprofile_setup_nav() {
	global $bp;

	/* Add 'Profile' to the main navigation */
	bp_core_new_nav_item( array( 'name' => __( 'Profile', 'buddypress' ), 'slug' => $bp->profile->slug, 'position' => 20, 'screen_function' => 'xprofile_screen_display_profile', 'default_subnav_slug' => 'public', 'item_css_id' => $bp->profile->id ) );

	$profile_link = $bp->loggedin_user->domain . $bp->profile->slug . '/';

	/* Add the subnav items to the profile */
	bp_core_new_subnav_item( array( 'name' => __( 'Public', 'buddypress' ), 'slug' => 'public', 'parent_url' => $profile_link, 'parent_slug' => $bp->profile->slug, 'screen_function' => 'xprofile_screen_display_profile', 'position' => 10 ) );
	bp_core_new_subnav_item( array( 'name' => __( 'Edit Profile', 'buddypress' ), 'slug' => 'edit', 'parent_url' => $profile_link, 'parent_slug' => $bp->profile->slug, 'screen_function' => 'xprofile_screen_edit_profile', 'position' => 20 ) );
	bp_core_new_subnav_item( array( 'name' => __( 'Change Avatar', 'buddypress' ), 'slug' => 'change-avatar', 'parent_url' => $profile_link, 'parent_slug' => $bp->profile->slug, 'screen_function' => 'xprofile_screen_change_avatar', 'position' => 30 ) );

	if ( $bp->current_component == $bp->profile->slug ) {
		if ( bp_is_my_profile() ) {
			$bp->bp_options_title = __( 'My Profile', 'buddypress' );
		} else {
			$bp->bp_options_avatar = bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb' ) );
			$bp->bp_options_title = $bp->displayed_user->fullname;
		}
	}

	do_action( 'xprofile_setup_nav' );
}
add_action( 'bp_setup_nav', 'xprofile_setup_nav' );
add_action( 'admin_menu', 'xprofile_setup_nav' );


/**
 * xprofile_setup_adminbar_menu()
 *
 * Adds an admin bar menu to any profile page providing site admin options for that user.
 *
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 */
function xprofile_setup_adminbar_menu() {
	global $bp;

	if ( !$bp->displayed_user->id )
		return false;

	/* Don't show this menu to non site admins or if you're viewing your own profile */
	if ( !is_site_admin() || bp_is_my_profile() )
		return false;
	?>
	<li id="bp-adminbar-adminoptions-menu">
		<a href=""><?php _e( 'Admin Options', 'buddypress' ) ?></a>

		<ul>
			<li><a href="<?php echo $bp->displayed_user->domain . $bp->profile->slug ?>/edit/"><?php printf( __( "Edit %s's Profile", 'buddypress' ), attribute_escape( $bp->displayed_user->fullname ) ) ?></a></li>
			<li><a href="<?php echo $bp->displayed_user->domain . $bp->profile->slug ?>/change-avatar/"><?php printf( __( "Edit %s's Avatar", 'buddypress' ), attribute_escape( $bp->displayed_user->fullname ) ) ?></a></li>

			<?php if ( !bp_core_is_user_spammer( $bp->displayed_user->id ) ) : ?>
				<li><a href="<?php echo wp_nonce_url( $bp->displayed_user->domain . 'admin/mark-spammer/', 'mark-unmark-spammer' ) ?>" class="confirm"><?php _e( "Mark as Spammer", 'buddypress' ) ?></a></li>
			<?php else : ?>
				<li><a href="<?php echo wp_nonce_url( $bp->displayed_user->domain . 'admin/unmark-spammer/', 'mark-unmark-spammer' ) ?>" class="confirm"><?php _e( "Not a Spammer", 'buddypress' ) ?></a></li>
			<?php endif; ?>

			<li><a href="<?php echo wp_nonce_url( $bp->displayed_user->domain . 'admin/delete-user/', 'delete-user' ) ?>" class="confirm"><?php printf( __( "Delete %s", 'buddypress' ), attribute_escape( $bp->displayed_user->fullname ) ) ?></a></li>

			<?php do_action( 'xprofile_adminbar_menu_items' ) ?>
		</ul>
	</li>
	<?php
}
add_action( 'bp_adminbar_menus', 'xprofile_setup_adminbar_menu', 20 );

/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */

/**
 * xprofile_screen_display_profile()
 *
 * Handles the display of the profile page by loading the correct template file.
 *
 * @package BuddyPress Xprofile
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_screen_display_profile() {
	global $bp;

	do_action( 'xprofile_screen_display_profile', $_GET['new'] );
	bp_core_load_template( apply_filters( 'xprofile_template_display_profile', 'members/single/home' ) );
}

/**
 * xprofile_screen_edit_profile()
 *
 * Handles the display of the profile edit page by loading the correct template file.
 * Also checks to make sure this can only be accessed for the logged in users profile.
 *
 * @package BuddyPress Xprofile
 * @uses bp_is_my_profile() Checks to make sure the current user being viewed equals the logged in user
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_screen_edit_profile() {
	global $bp;

	if ( !bp_is_my_profile() && !is_site_admin() )
		return false;

	/* Make sure a group is set. */
	if ( empty( $bp->action_variables[1] ) )
		bp_core_redirect( $bp->displayed_user->domain . BP_XPROFILE_SLUG . '/edit/group/1' );

	/* Check the field group exists */
	if ( !xprofile_get_field_group( $bp->action_variables[1] ) )
		bp_core_redirect( $bp->root_domain );

	/* Check to see if any new information has been submitted */
	if ( isset( $_POST['field_ids'] ) ) {

		/* Check the nonce */
		check_admin_referer( 'bp_xprofile_edit' );

		/* Check we have field ID's */
		if ( empty( $_POST['field_ids'] ) )
			bp_core_redirect( $bp->displayed_user->domain . BP_XPROFILE_SLUG . '/edit/group/' . $bp->action_variables[1] . '/' );

		/* Explode the posted field IDs into an array so we know which fields have been submitted */
		$posted_field_ids = explode( ',', $_POST['field_ids'] );

		$is_required = array();

		/* Loop through the posted fields formatting any datebox values then validate the field */
		foreach ( (array)$posted_field_ids as $field_id ) {
			if ( !isset( $_POST['field_' . $field_id] ) ) {

				if ( is_numeric( $_POST['field_' . $field_id . '_day'] ) ) {
					/* Concatenate the values. */
					$date_value = $_POST['field_' . $field_id . '_day'] . ' ' .
							      $_POST['field_' . $field_id . '_month'] . ' ' .
								  $_POST['field_' . $field_id . '_year'];

					/* Turn the concatenated value into a timestamp */
					$_POST['field_' . $field_id] = strtotime( $date_value );
				}

			}

			$is_required[$field_id] = xprofile_check_is_required_field( $field_id );
			if ( $is_required[$field_id] && empty( $_POST['field_' . $field_id] ) )
				$errors = true;
		}

		if ( $errors )
			bp_core_add_message( __( 'Please make sure you fill in all required fields in this profile field group before saving.', 'buddypress' ), 'error' );
		else {
			/* Reset the errors var */
			$errors = false;

			/* Now we've checked for required fields, lets save the values. */
			foreach ( (array)$posted_field_ids as $field_id ) {
				if ( !xprofile_set_field_data( $field_id, $bp->displayed_user->id, $_POST['field_' . $field_id], $is_required[$field_id] ) )
					$errors = true;
				else
					do_action( 'xprofile_profile_field_data_updated', $field_id, $_POST['field_' . $field_id] );
			}

			do_action( 'xprofile_updated_profile', $posted_field_ids, $errors );

			/* Set the feedback messages */
			if ( $errors )
				bp_core_add_message( __( 'There was a problem updating some of your profile information, please try again.', 'buddypress' ), 'error' );
			else
				bp_core_add_message( __( 'Changes saved.', 'buddypress' ) );

			/* Redirect back to the edit screen to display the updates and message */
			bp_core_redirect( $bp->displayed_user->domain . BP_XPROFILE_SLUG . '/edit/group/' . $bp->action_variables[1] . '/' );
		}
	}

	do_action( 'xprofile_screen_edit_profile' );
	bp_core_load_template( apply_filters( 'xprofile_template_edit_profile', 'members/single/home' ) );
}

/**
 * xprofile_screen_change_avatar()
 *
 * Handles the uploading and cropping of a user avatar. Displays the change avatar page.
 *
 * @package BuddyPress Xprofile
 * @uses bp_is_my_profile() Checks to make sure the current user being viewed equals the logged in user
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_screen_change_avatar() {
	global $bp;

	if ( !bp_is_my_profile() && !is_site_admin() )
		return false;

	$bp->avatar_admin->step = 'upload-image';

	if ( !empty( $_FILES ) ) {

		/* Check the nonce */
		check_admin_referer( 'bp_avatar_upload' );

		/* Pass the file to the avatar upload handler */
		if ( bp_core_avatar_handle_upload( $_FILES, 'xprofile_avatar_upload_dir' ) ) {
			$bp->avatar_admin->step = 'crop-image';

			/* Make sure we include the jQuery jCrop file for image cropping */
			add_action( 'wp', 'bp_core_add_jquery_cropper' );
		}
	}

	/* If the image cropping is done, crop the image and save a full/thumb version */
	if ( isset( $_POST['avatar-crop-submit'] ) ) {

		/* Check the nonce */
		check_admin_referer( 'bp_avatar_cropstore' );

		if ( !bp_core_avatar_handle_crop( array( 'item_id' => $bp->displayed_user->id, 'original_file' => $_POST['image_src'], 'crop_x' => $_POST['x'], 'crop_y' => $_POST['y'], 'crop_w' => $_POST['w'], 'crop_h' => $_POST['h'] ) ) )
			bp_core_add_message( __( 'There was a problem cropping your avatar, please try uploading it again', 'buddypress' ), 'error' );
		else {
			bp_core_add_message( __( 'Your new avatar was uploaded successfully!', 'buddypress' ) );
			do_action( 'xprofile_avatar_uploaded' );
		}
	}

	do_action( 'xprofile_screen_change_avatar' );

	bp_core_load_template( apply_filters( 'xprofile_template_change_avatar', 'members/single/home' ) );
}

/********************************************************************************
 * Action Functions
 *
 * Action functions are exactly the same as screen functions, however they do not
 * have a template screen associated with them. Usually they will send the user
 * back to the default screen after execution.
 */

/**
 * xprofile_action_delete_avatar()
 *
 * This function runs when an action is set for a screen:
 * example.com/members/andy/profile/change-avatar/ [delete-avatar]
 *
 * The function will delete the active avatar for a user.
 *
 * @package BuddyPress Xprofile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_core_delete_avatar() Deletes the active avatar for the logged in user.
 * @uses add_action() Runs a specific function for an action when it fires.
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_action_delete_avatar() {
	global $bp;

	if ( $bp->profile->slug != $bp->current_component || 'change-avatar' != $bp->current_action || 'delete-avatar' != $bp->action_variables[0] )
		return false;

	/* Check the nonce */
	check_admin_referer( 'bp_delete_avatar_link' );

	if ( !bp_is_my_profile() && !is_site_admin() )
		return false;

	if ( bp_core_delete_existing_avatar( array( 'item_id' => $bp->displayed_user->id ) ) )
		bp_core_add_message( __( 'Your avatar was deleted successfully!', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was a problem deleting that avatar, please try again.', 'buddypress' ), 'error' );

	bp_core_redirect( wp_get_referer() );
}
add_action( 'wp', 'xprofile_action_delete_avatar', 3 );

/********************************************************************************
 * Activity & Notification Functions
 *
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 */

function xprofile_register_activity_actions() {
	global $bp;

	if ( !function_exists( 'bp_activity_set_action' ) )
		return false;

	/* Register the activity stream actions for this component */
	bp_activity_set_action( $bp->profile->id, 'new_member', __( 'New member registered', 'buddypress' ) );
	bp_activity_set_action( $bp->profile->id, 'updated_profile', __( 'Updated Profile', 'buddypress' ) );

	do_action( 'xprofile_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'xprofile_register_activity_actions' );

/**
 * xprofile_record_activity()
 *
 * Records activity for the logged in user within the profile component so that
 * it will show in the users activity stream (if installed)
 *
 * @package BuddyPress XProfile
 * @param $args Array containing all variables used after extract() call
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_activity_record() Adds an entry to the activity component tables for a specific activity
 */
function xprofile_record_activity( $args = true ) {
	global $bp;

	if ( !function_exists( 'bp_activity_add' ) )
		return false;

	$defaults = array(
		'user_id' => $bp->loggedin_user->id,
		'action' => '',
		'content' => '',
		'primary_link' => '',
		'component' => $bp->profile->id,
		'type' => false,
		'item_id' => false,
		'secondary_item_id' => false,
		'recorded_time' => gmdate( "Y-m-d H:i:s" ),
		'hide_sitewide' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	return bp_activity_add( array( 'user_id' => $user_id, 'action' => $action, 'content' => $content, 'primary_link' => $primary_link, 'component' => $component, 'type' => $type, 'item_id' => $item_id, 'secondary_item_id' => $secondary_item_id, 'recorded_time' => $recorded_time, 'hide_sitewide' => $hide_sitewide ) );
}

/**
 * xprofile_delete_activity()
 *
 * Deletes activity for a user within the profile component so that
 * it will be removed from the users activity stream and sitewide stream (if installed)
 *
 * @package BuddyPress XProfile
 * @param $args Array containing all variables used after extract() call
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_activity_delete() Deletes an entry to the activity component tables for a specific activity
 */
function xprofile_delete_activity( $args = '' ) {
	global $bp;

	if ( function_exists('bp_activity_delete_by_item_id') ) {
		extract($args);
		bp_activity_delete_by_item_id( array( 'item_id' => $item_id, 'component' => $bp->profile->id, 'type' => $type, 'user_id' => $user_id, 'secondary_item_id' => $secondary_item_id ) );
	}
}

function xprofile_register_activity_action( $key, $value ) {
	global $bp;

	if ( !function_exists( 'bp_activity_set_action' ) )
		return false;

	return apply_filters( 'xprofile_register_activity_action', bp_activity_set_action( $bp->profile->id, $key, $value ), $key, $value );
}


/********************************************************************************
 * Business Functions
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

/*** Field Group Management **************************************************/

function xprofile_insert_field_group( $args = '' ) {
	$defaults = array(
		'field_group_id' => false,
		'name' => false,
		'description' => '',
		'can_delete' => true
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( !$name )
		return false;

	$field_group = new BP_XProfile_Group( $field_group_id );
	$field_group->name = $name;
	$field_group->description = $description;
	$field_group->can_delete = $can_delete;

	return $field_group->save();
}

function xprofile_get_field_group( $field_group_id ) {
	$field_group = new BP_XProfile_Group( $field_group_id );

	if ( empty( $field_group->id ) )
		return false;

	return $field_group;
}

function xprofile_delete_field_group( $field_group_id ) {
	$field_group = new BP_XProfile_Group( $field_group_id );
	return $field_group->delete();
}


/*** Field Management *********************************************************/

function xprofile_insert_field( $args = '' ) {
	global $bp;

	extract( $args );

	/**
	 * Possible parameters (pass as assoc array):
	 *	'field_id'
	 *	'field_group_id'
	 *	'parent_id'
	 *	'type'
	 *	'name'
	 *	'description'
	 *	'is_required'
	 *	'can_delete'
	 *	'field_order'
	 *	'order_by'
	 *	'is_default_option'
	 *	'option_order'
	 */

	/* Check we have the minimum details */
	if ( !$field_group_id )
		return false;

	/* Check this is a valid field type */
	if ( !in_array( $type, (array) $bp->profile->field_types ) )
		return false;

	/* Instantiate a new field object */
	if ( $field_id )
		$field = new BP_XProfile_Field( $field_id );
	else
		$field = new BP_XProfile_Field;

	$field->group_id = $field_group_id;

	if ( !empty( $parent_id ) )
		$field->parent_id = $parent_id;

	if ( !empty( $type ) )
		$field->type = $type;

	if ( !empty( $name ) )
		$field->name = $name;

	if ( !empty( $description ) )
		$field->description = $description;

	if ( !empty( $is_required ) )
		$field->is_required = $is_required;

	if ( !empty( $can_delete ) )
		$field->can_delete = $can_delete;

	if ( !empty( $field_order ) )
		$field->field_order = $field_order;

	if ( !empty( $order_by ) )
		$field->order_by = $order_by;

	if ( !empty( $is_default_option ) )
		$field->is_default_option = $is_default_option;

	if ( !empty( $option_order ) )
		$field->option_order = $option_order;

	if ( !$field->save() )
		return false;

	return true;
}

function xprofile_get_field( $field_id ) {
	return new BP_XProfile_Field( $field_id );
}

function xprofile_delete_field( $field_id ) {
	$field = new BP_XProfile_Field( $field_id );
	return $field->delete();
}


/*** Field Data Management *****************************************************/

/**
 * xprofile_get_field_data()
 *
 * Fetches profile data for a specific field for the user.
 *
 * @package BuddyPress Core
 * @param $field The ID of the field, or the $name of the field.
 * @param $user_id The ID of the user
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses BP_XProfile_ProfileData::get_value_byfieldname() Fetches the value based on the params passed.
 * @return The profile field data.
 */
function xprofile_get_field_data( $field, $user_id = null ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->displayed_user->id;

	if ( !$user_id )
		return false;

	if ( is_numeric( $field ) )
		$field_id = $field;
	else
		$field_id = xprofile_get_field_id_from_name( $field );

	if ( !$field_id )
		return false;

	return apply_filters( 'xprofile_get_field_data', BP_XProfile_ProfileData::get_value_byid( $field_id, $user_id ) );
}

/**
 * xprofile_set_field_data()
 *
 * A simple function to set profile data for a specific field for a specific user.
 *
 * @package BuddyPress Core
 * @param $field The ID of the field, or the $name of the field.
 * @param $user_id The ID of the user
 * @param $value The value for the field you want to set for the user.
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses xprofile_get_field_id_from_name() Gets the ID for the field based on the name.
 * @return true on success, false on failure.
 */
function xprofile_set_field_data( $field, $user_id, $value, $is_required = false ) {
	if ( is_numeric( $field ) )
		$field_id = $field;
	else
		$field_id = xprofile_get_field_id_from_name( $field );

	if ( !$field_id )
		return false;

	if ( $is_required && ( empty( $value ) || !strlen( trim( $value ) ) ) )
		return false;

	$field = new BP_XProfile_ProfileData();
	$field->field_id = $field_id;
	$field->user_id = $user_id;
	$field->value = maybe_serialize( $value );

	return $field->save();
}

function xprofile_delete_field_data( $field, $user_id ) {
	if ( is_numeric( $field ) )
		$field_id = $field;
	else
		$field_id = xprofile_get_field_id_from_name( $field );

	if ( !$field_id )
		return false;

	$field = new BP_XProfile_ProfileData( $field_id );
	return $field->delete();
}

function xprofile_check_is_required_field( $field_id ) {
	$field = new BP_Xprofile_Field( $field_id );

	if ( (int)$field->is_required )
		return true;

	return false;
}

/**
 * xprofile_get_field_id_from_name()
 *
 * Returns the ID for the field based on the field name.
 *
 * @package BuddyPress Core
 * @param $field_name The name of the field to get the ID for.
 * @return int $field_id on success, false on failure.
 */
function xprofile_get_field_id_from_name( $field_name ) {
	return BP_Xprofile_Field::get_id_from_name( $field_name );
}

/**
 * xprofile_get_random_profile_data()
 *
 * Fetches a random piece of profile data for the user.
 *
 * @package BuddyPress Core
 * @param $user_id User ID of the user to get random data for
 * @param $exclude_fullname whether or not to exclude the full name field as random data.
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @global $current_user WordPress global variable containing current logged in user information
 * @uses xprofile_format_profile_field() Formats profile field data so it is suitable for display.
 * @return $field_data The fetched random data for the user.
 */
function xprofile_get_random_profile_data( $user_id, $exclude_fullname = true ) {
	$field_data = BP_XProfile_ProfileData::get_random( $user_id, $exclude_fullname );
	$field_data[0]->value = xprofile_format_profile_field( $field_data[0]->type, $field_data[0]->value );

	if ( !$field_data[0]->value || empty( $field_data[0]->value ) )
		return false;

	return apply_filters( 'xprofile_get_random_profile_data', $field_data );
}

/**
 * xprofile_format_profile_field()
 *
 * Formats a profile field according to its type. [ TODO: Should really be moved to filters ]
 *
 * @package BuddyPress Core
 * @param $field_type The type of field: datebox, selectbox, textbox etc
 * @param $field_value The actual value
 * @uses bp_format_time() Formats a time value based on the WordPress date format setting
 * @return $field_value The formatted value
 */
function xprofile_format_profile_field( $field_type, $field_value ) {
	if ( !isset($field_value) || empty( $field_value ) )
		return false;

	$field_value = bp_unserialize_profile_field( $field_value );

	if ( 'datebox' == $field_type ) {
		$field_value = bp_format_time( $field_value, true );
	} else {
		$content = $field_value;
		$field_value = str_replace(']]>', ']]&gt;', $content);
	}

	return stripslashes_deep( $field_value );
}

function xprofile_update_field_position( $field_id, $position ) {
	return BP_XProfile_Field::update_position( $field_id, $position);
}

/**
 * xprofile_avatar_upload_dir()
 *
 * Setup the avatar upload directory for a user.
 *
 * @package BuddyPress Core
 * @param $directory The root directory name
 * @param $user_id The user ID.
 * @return array() containing the path and URL plus some other settings.
 */
function xprofile_avatar_upload_dir( $directory = false, $user_id = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->displayed_user->id;

	if ( !$directory )
		$directory = 'avatars';

	$path  = BP_AVATAR_UPLOAD_PATH . '/avatars/' . $user_id;
	$newbdir = $path;

	if ( !file_exists( $path ) )
		@wp_mkdir_p( $path );

	$newurl = str_replace( BP_AVATAR_UPLOAD_PATH, BP_AVATAR_URL, $path );
	$newburl = $newurl;
	$newsubdir = '/avatars/' . $user_id;

	return apply_filters( 'xprofile_avatar_upload_dir', array( 'path' => $path, 'url' => $newurl, 'subdir' => $newsubdir, 'basedir' => $newbdir, 'baseurl' => $newburl, 'error' => false ) );
}

/**
 * xprofile_sync_wp_profile()
 *
 * Syncs Xprofile data to the standard built in WordPress profile data.
 *
 * @package BuddyPress Core
 */
function xprofile_sync_wp_profile() {
	global $bp, $wpdb;

	if ( (int)$bp->option['bp-disable-profile-sync'] )
		return true;

	$fullname = xprofile_get_field_data( BP_XPROFILE_FULLNAME_FIELD_NAME, $bp->loggedin_user->id );
	$space = strpos( $fullname, ' ' );

	if ( false === $space ) {
		$firstname = $fullname;
		$lastname = '';
	} else {
		$firstname = substr( $fullname, 0, $space );
		$lastname = trim( substr( $fullname, $space, strlen($fullname) ) );
	}

	update_usermeta( $bp->loggedin_user->id, 'nickname', $fullname );
	update_usermeta( $bp->loggedin_user->id, 'first_name', $firstname );
	update_usermeta( $bp->loggedin_user->id, 'last_name', $lastname );

	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET display_name = %s WHERE ID = %d", $fullname, $bp->loggedin_user->id ) );
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET user_url = %s WHERE ID = %d", bp_core_get_user_domain( $bp->loggedin_user->id ), $bp->loggedin_user->id ) );
}
add_action( 'xprofile_updated_profile', 'xprofile_sync_wp_profile' );


/**
 * xprofile_remove_screen_notifications()
 *
 * Removes notifications from the notification menu when a user clicks on them and
 * is taken to a specific screen.
 *
 * @package BuddyPress Core
 */
function xprofile_remove_screen_notifications() {
	global $bp;

	bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->profile->id, 'new_at_mention' );
}
add_action( 'bp_activity_screen_my_activity', 'xprofile_remove_screen_notifications' );
add_action( 'bp_activity_screen_single_activity_permalink', 'xprofile_remove_screen_notifications' );


/**
 * xprofile_remove_data_on_user_deletion()
 *
 * When a user is deleted, we need to clean up the database and remove all the
 * profile data from each table. Also we need to clean anything up in the usermeta table
 * that this component uses.
 *
 * @package BuddyPress XProfile
 * @param $user_id The ID of the deleted user
 * @uses get_usermeta() Get a user meta value based on meta key from wp_usermeta
 * @uses delete_usermeta() Delete user meta value based on meta key from wp_usermeta
 * @uses delete_data_for_user() Removes all profile data from the xprofile tables for the user
 */
function xprofile_remove_data( $user_id ) {
	BP_XProfile_ProfileData::delete_data_for_user( $user_id );

	// delete any avatar files.
	@unlink( get_usermeta( $user_id, 'bp_core_avatar_v1_path' ) );
	@unlink( get_usermeta( $user_id, 'bp_core_avatar_v2_path' ) );

	// unset the usermeta for avatars from the usermeta table.
	delete_usermeta( $user_id, 'bp_core_avatar_v1' );
	delete_usermeta( $user_id, 'bp_core_avatar_v1_path' );
	delete_usermeta( $user_id, 'bp_core_avatar_v2' );
	delete_usermeta( $user_id, 'bp_core_avatar_v2_path' );
}
add_action( 'wpmu_delete_user', 'xprofile_remove_data', 1 );
add_action( 'delete_user', 'xprofile_remove_data', 1 );


/********************************************************************************
 * Caching
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 */

function xprofile_clear_profile_groups_object_cache( $group_obj ) {
	wp_cache_delete( 'xprofile_groups', 'bp' );
	wp_cache_delete( 'xprofile_groups_inc_empty', 'bp' );
	wp_cache_delete( 'xprofile_group_' . $group_obj->id );

	/* Clear the sitewide activity cache */
	wp_cache_delete( 'sitewide_activity', 'bp' );
}

function xprofile_clear_profile_data_object_cache( $group_id ) {
	global $bp;
	wp_cache_delete( 'xprofile_fields_' . $group_id . '_' . $bp->loggedin_user->id, 'bp' );
	wp_cache_delete( 'bp_user_fullname_' . $bp->loggedin_user->id, 'bp' );
	wp_cache_delete( 'online_users', 'bp' );
	wp_cache_delete( 'newest_users', 'bp' );
	wp_cache_delete( 'popular_users', 'bp' );

	/* Clear the sitewide activity cache */
	wp_cache_delete( 'sitewide_activity', 'bp' );
}

// List actions to clear object caches on
add_action( 'xprofile_groups_deleted_group', 'xprofile_clear_profile_groups_object_cache' );
add_action( 'xprofile_groups_saved_group', 'xprofile_clear_profile_groups_object_cache' );
add_action( 'xprofile_updated_profile', 'xprofile_clear_profile_data_object_cache' );

// List actions to clear super cached pages on, if super cache is installed
add_action( 'xprofile_updated_profile', 'bp_core_clear_cache' );

?>
