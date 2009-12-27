<?php
define ( 'BP_XPROFILE_DB_VERSION', '1850' );

/* Define the slug for the component */
if ( !defined( 'BP_XPROFILE_SLUG' ) )
	define ( 'BP_XPROFILE_SLUG', 'profile' );

require ( BP_PLUGIN_DIR . '/bp-xprofile/bp-xprofile-admin.php' );
require ( BP_PLUGIN_DIR . '/bp-xprofile/bp-xprofile-classes.php' );
require ( BP_PLUGIN_DIR . '/bp-xprofile/bp-xprofile-filters.php' );
require ( BP_PLUGIN_DIR . '/bp-xprofile/bp-xprofile-templatetags.php' );
require ( BP_PLUGIN_DIR . '/bp-xprofile/bp-xprofile-notifications.php' );
require ( BP_PLUGIN_DIR . '/bp-xprofile/bp-xprofile-cssjs.php' );

/* Assign the base group and fullname field names to constants to use in SQL statements */
define ( 'BP_XPROFILE_BASE_GROUP_NAME', get_site_option( 'bp-xprofile-base-group-name' ) );
define ( 'BP_XPROFILE_FULLNAME_FIELD_NAME', get_site_option( 'bp-xprofile-fullname-field-name' ) );

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

	if ( '' == get_site_option( 'bp-xprofile-base-group-name' ) )
		update_site_option( 'bp-xprofile-base-group-name', 'Base' );

	if ( '' == get_site_option( 'bp-xprofile-fullname-field-name' ) )
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

	if ( '' == get_site_option( 'bp-xprofile-db-version' ) ) {
		$sql[] = "INSERT INTO {$bp->profile->table_name_groups} VALUES ( 1, '" . get_site_option( 'bp-xprofile-base-group-name' ) . "', '', 0 );";

		$sql[] = "INSERT INTO {$bp->profile->table_name_fields} (
					id, group_id, parent_id, type, name, is_required, can_delete
				  ) VALUES (
					1, 1, 0, 'textbox', '" . get_site_option( 'bp-xprofile-fullname-field-name' ) . "', 1, 0
				  );";
	}

	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	dbDelta($sql);

	if ( function_exists('bp_wire_install') )
		xprofile_wire_install();

	update_site_option( 'bp-xprofile-db-version', BP_XPROFILE_DB_VERSION );
}

function xprofile_wire_install() {
	global $bp, $wpdb;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	$sql[] = "CREATE TABLE {$bp->profile->table_name_wire} (
	  		   id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			   item_id bigint(20) NOT NULL,
			   user_id bigint(20) NOT NULL,
			   parent_id bigint(20) NOT NULL,
			   content longtext NOT NULL,
			   date_posted datetime NOT NULL,
			   KEY item_id (item_id),
		       KEY user_id (user_id),
		       KEY parent_id (parent_id)
	 	       ) {$charset_collate};";

	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	dbDelta($sql);
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

	if ( function_exists( 'bp_wire_install' ) )
		$bp->profile->table_name_wire = $wpdb->base_prefix . 'bp_xprofile_wire';

	do_action( 'xprofile_setup_globals' );
}
add_action( 'plugins_loaded', 'xprofile_setup_globals', 5 );
add_action( 'admin_menu', 'xprofile_setup_globals', 2 );

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
	add_submenu_page( 'bp-general-settings', __("Profile Field Setup", 'buddypress'), __("Profile Field Setup", 'buddypress'), 'manage-options', 'bp-profile-setup', "xprofile_admin" );

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( get_site_option('bp-xprofile-db-version') < BP_XPROFILE_DB_VERSION )
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
 * @uses bp_core_add_nav_item() Adds a navigation item to the top level buddypress navigation
 * @uses bp_core_add_nav_default() Sets which sub navigation item is selected by default
 * @uses bp_core_add_subnav_item() Adds a sub navigation item to a nav item
 * @uses bp_is_home() Returns true if the current user being viewed is equal the logged in user
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
		if ( bp_is_home() ) {
			$bp->bp_options_title = __( 'My Profile', 'buddypress' );
		} else {
			$bp->bp_options_avatar = bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb' ) );
			$bp->bp_options_title = $bp->displayed_user->fullname;
		}
	}

	do_action( 'xprofile_setup_nav' );
}
add_action( 'plugins_loaded', 'xprofile_setup_nav' );
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
	if ( !is_site_admin() || bp_is_home() )
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

	// If this is a first visit to a new friends profile, delete the friend accepted notifications for the
	// logged in user, only if $_GET['new'] is set.
	if ( isset($_GET['new']) )
		bp_core_delete_notifications_for_user_by_item_id( $bp->loggedin_user->id, $bp->displayed_user->id, 'friends', 'friendship_accepted' );

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
 * @uses bp_is_home() Checks to make sure the current user being viewed equals the logged in user
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_screen_edit_profile() {
	global $bp;

	if ( !bp_is_home() && !is_site_admin() )
		return false;

	/* Check to see if any new information has been submitted */
	if ( isset($_POST['field_ids']) ) {

		/* Check the nonce */
		check_admin_referer( 'bp_xprofile_edit' );

		/* Check we have field ID's */
		if ( empty( $_POST['field_ids'] ) )
			bp_core_redirect( $bp->displayed_user->domain . BP_XPROFILE_SLUG . '/edit/group/' . $bp->action_variables[1] . '/' );

		/* Explode the posted field IDs into an array so we know which fields have been submitted */
		$posted_field_ids = explode( ',', $_POST['field_ids'] );

		/* Loop through the posted fields formatting any datebox values then validate the field */
		foreach ( $posted_field_ids as $field_id ) {
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

			if ( xprofile_check_is_required_field( $field_id ) && empty( $_POST['field_' . $field_id] ) )
				$errors = true;
		}

		if ( $errors )
			bp_core_add_message( __( 'Please make sure you fill in all required fields in this profile field group before saving.', 'buddypress' ), 'error' );
		else {
			/* Reset the errors var */
			$errors = false;

			/* Now we've checked for required fields, lets save the values. */
			foreach ( $posted_field_ids as $field_id ) {
				if ( !xprofile_set_field_data( $field_id, $bp->displayed_user->id, $_POST['field_' . $field_id] ) )
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
 * @uses bp_is_home() Checks to make sure the current user being viewed equals the logged in user
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function xprofile_screen_change_avatar() {
	global $bp;

	if ( !bp_is_home() && !is_site_admin() )
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

/**
 * xprofile_screen_notification_settings()
 *
 * Loads the notification settings for the xprofile component.
 * Settings are hooked into the function: bp_core_screen_notification_settings_content()
 * in bp-core/bp-core-settings.php
 *
 * @package BuddyPress Xprofile
 * @global $current_user WordPress global variable containing current logged in user information
 */
function xprofile_screen_notification_settings() {
	global $current_user; ?>
	<?php if ( function_exists('bp_wire_install') ) { ?>
	<table class="notification-settings" id="profile-notification-settings">
		<tr>
			<th class="icon"></th>
			<th class="title"><?php _e( 'Profile', 'buddypress' ) ?></th>
			<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
			<th class="no"><?php _e( 'No', 'buddypress' )?></th>
		</tr>

		<tr>
			<td></td>
			<td><?php _e( 'A member posts on your wire', 'buddypress' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_profile_wire_post]" value="yes" <?php if ( !get_usermeta( $current_user->id, 'notification_profile_wire_post' ) || 'yes' == get_usermeta( $current_user->id, 'notification_profile_wire_post' ) ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_profile_wire_post]" value="no" <?php if ( 'no' == get_usermeta( $current_user->id, 'notification_profile_wire_post' ) ) { ?>checked="checked" <?php } ?>/></td>
		</tr>

		<?php do_action( 'xprofile_screen_notification_settings' ) ?>
	</table>
	<?php } ?>
<?php
}
add_action( 'bp_notification_settings', 'xprofile_screen_notification_settings', 1 );


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

	if ( !bp_is_home() && !is_site_admin() )
		return false;

	if ( bp_core_delete_existing_avatar( array( 'item_id' => $bp->displayed_user->id ) ) )
		bp_core_add_message( __( 'Your avatar was deleted successfully!', 'buddypress' ) );
	else
		bp_core_add_message( __( 'There was a problem deleting that avatar, please try again.', 'buddypress' ), 'error' );

	bp_core_redirect( wp_get_referer() );
}
add_action( 'wp', 'xprofile_action_delete_avatar', 3 );

/**
 * xprofile_action_new_wire_post()
 *
 * Posts a new wire post to the users profile wire.
 *
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_wire_new_post() Adds a new wire post to a specific wire using the ID of the item passed and the table name.
 * @uses bp_core_add_message() Adds an error/success message to the session to be displayed on the next page load.
 * @uses bp_core_redirect() Safe redirects to a new page using the wp_redirect() function
 */
function xprofile_action_new_wire_post() {
	global $bp;

	if ( $bp->current_component != $bp->wire->slug )
		return false;

	if ( 'post' != $bp->current_action )
		return false;

	/* Check the nonce */
	if ( !check_admin_referer( 'bp_wire_post' ) )
		return false;

	if ( !$wire_post = bp_wire_new_post( $bp->displayed_user->id, $_POST['wire-post-textarea'], $bp->profile->id ) ) {
		bp_core_add_message( __( 'Wire message could not be posted. Please try again.', 'buddypress' ), 'error' );
	} else {
		bp_core_add_message( __( 'Wire message successfully posted.', 'buddypress' ) );

		/* Record the notification for the reciever if it's not on their own wire */
		if ( !bp_is_home() )
			bp_core_add_notification( $bp->loggedin_user->id, $bp->displayed_user->id, $bp->profile->id, 'new_wire_post' );

		/* Record this on the poster's activity screen */
		if ( ( $wire_post->item_id == $bp->loggedin_user->id && $wire_post->user_id == $bp->loggedin_user->id ) || ( $wire_post->item_id == $bp->displayed_user->id && $wire_post->user_id == $bp->displayed_user->id ) ) {
			$from_user_link = bp_core_get_userlink($wire_post->user_id);
			$content = sprintf( __('%s wrote on their own wire', 'buddypress'), $from_user_link ) . ': <span class="time-since">%s</span>';
			$primary_link = bp_core_get_userlink( $wire_post->user_id, false, true );
		} else if ( ( $wire_post->item_id != $bp->loggedin_user->id && $wire_post->user_id == $bp->loggedin_user->id ) || ( $wire_post->item_id != $bp->displayed_user->id && $wire_post->user_id == $bp->displayed_user->id ) ) {
			$from_user_link = bp_core_get_userlink($wire_post->user_id);
			$to_user_link = bp_core_get_userlink( $wire_post->item_id, false, false, true, true );
			$content = sprintf( __('%s wrote on %s wire', 'buddypress'), $from_user_link, $to_user_link ) . ': <span class="time-since">%s</span>';
			$primary_link = bp_core_get_userlink( $wire_post->item_id, false, true );
		}

		$content .= '<blockquote>' . bp_create_excerpt($wire_post->content) . '</blockquote>';

		/* Now write the values */
		xprofile_record_activity( array(
			'user_id' => $bp->loggedin_user->id,
			'content' => apply_filters( 'xprofile_activity_new_wire_post', $content, &$wire_post ),
			'primary_link' => apply_filters( 'xprofile_activity_new_wire_post_primary_link', $primary_link ),
			'component_action' => 'new_wire_post',
			'item_id' => $wire_post->id
		) );

		do_action( 'xprofile_new_wire_post', &$wire_post );
	}

	if ( !strpos( wp_get_referer(), $bp->wire->slug ) ) {
		bp_core_redirect( $bp->displayed_user->domain );
	} else {
		bp_core_redirect( $bp->displayed_user->domain . $bp->wire->slug );
	}
}
add_action( 'wp', 'xprofile_action_new_wire_post', 3 );

/**
 * xprofile_action_delete_wire_post()
 *
 * Deletes a wire post from the users profile wire.
 *
 * @package BuddyPress XProfile
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_wire_delete_post() Deletes a wire post for a specific wire using the ID of the item passed and the table name.
 * @uses xprofile_delete_activity() Deletes an activity item for the xprofile component and a particular user.
 * @uses bp_core_add_message() Adds an error/success message to the session to be displayed on the next page load.
 * @uses bp_core_redirect() Safe redirects to a new page using the wp_redirect() function
 */
function xprofile_action_delete_wire_post() {
	global $bp;

	if ( $bp->current_component != $bp->wire->slug )
		return false;

	if ( $bp->current_action != 'delete' )
		return false;

	if ( !check_admin_referer( 'bp_wire_delete_link' ) )
		return false;

	$wire_post_id = $bp->action_variables[0];

	if ( bp_wire_delete_post( $wire_post_id, $bp->profile->slug, $bp->profile->table_name_wire ) ) {
		bp_core_add_message( __('Wire message successfully deleted.', 'buddypress') );

		/* Delete the post from activity streams */
		xprofile_delete_activity( array( 'item_id' => $wire_post_id, 'component_action' => 'new_wire_post' ) );

		do_action( 'xprofile_delete_wire_post', $wire_post_id );
	} else {
		bp_core_add_message( __('Wire post could not be deleted, please try again.', 'buddypress'), 'error' );
	}

	if ( !strpos( wp_get_referer(), $bp->wire->slug ) ) {
		bp_core_redirect( $bp->displayed_user->domain );
	} else {
		bp_core_redirect( $bp->displayed_user->domain. $bp->wire->slug );
	}
}
add_action( 'wp', 'xprofile_action_delete_wire_post', 3 );


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
	bp_activity_set_action( $bp->profile->id, 'new_wire_post', __( 'New profile wire post', 'buddypress' ) );

	do_action( 'xprofile_register_activity_actions' );
}
add_action( 'plugins_loaded', 'xprofile_register_activity_actions' );

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
		'content' => false,
		'primary_link' => false,
		'component_name' => $bp->profile->id,
		'component_action' => false,
		'item_id' => false,
		'secondary_item_id' => false,
		'recorded_time' => time(),
		'hide_sitewide' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	return bp_activity_add( array( 'user_id' => $user_id, 'content' => $content, 'primary_link' => $primary_link, 'component_name' => $component_name, 'component_action' => $component_action, 'item_id' => $item_id, 'secondary_item_id' => $secondary_item_id, 'recorded_time' => $recorded_time, 'hide_sitewide' => $hide_sitewide ) );
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
		bp_activity_delete_by_item_id( array( 'item_id' => $item_id, 'component_name' => $bp->profile->id, 'component_action' => $component_action, 'user_id' => $user_id, 'secondary_item_id' => $secondary_item_id ) );
	}
}

function xprofile_register_activity_action( $key, $value ) {
	global $bp;

	if ( !function_exists( 'bp_activity_set_action' ) )
		return false;

	return apply_filters( 'xprofile_register_activity_action', bp_activity_set_action( $bp->profile->id, $key, $value ), $key, $value );
}

/**
 * xprofile_format_notifications()
 *
 * Format notifications into something that can be read and displayed
 *
 * @package BuddyPress Xprofile
 * @param $item_id The ID of the specific item for which the activity is recorded (could be a wire post id, user id etc)
 * @param $action The component action name e.g. 'new_wire_post' or 'updated_profile'
 * @param $total_items The total number of identical notification items (used for grouping)
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_core_global_user_fullname() Returns the display name for the user
 * @return The readable notification item
 */
function xprofile_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;

	if ( 'new_wire_post' == $action ) {
		if ( (int)$total_items > 1 ) {
			return apply_filters( 'bp_xprofile_multiple_new_wire_post_notification', '<a href="' . $bp->loggedin_user->domain . $bp->wire->slug . '" title="' . __( 'Wire', 'buddypress' ) . '">' . sprintf( __( 'You have %d new posts on your wire', 'buddypress' ), (int)$total_items ) . '</a>', $total_items );
		} else {
			$user_fullname = bp_core_get_user_displayname( $item_id );
			return apply_filters( 'bp_xprofile_single_new_wire_post_notification', '<a href="' . $bp->loggedin_user->domain . $bp->wire->slug . '" title="' . __( 'Wire', 'buddypress' ) . '">' . sprintf( __( '%s posted on your wire', 'buddypress' ), $user_fullname ) . '</a>', $user_fullname );
		}
	}

	do_action( 'xprofile_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

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

function xprofile_post_update( $content, $user_id = false ) {
	global $bp;

	if ( !$user_id )
		$user_id = $bp->loggedin_user->id;

	/* Record this on the user's profile */
	$from_user_link = bp_core_get_userlink( $user_id );
	$activity_content = sprintf( __('%s posted an update:', 'buddypress'), $from_user_link );
	$activity_content .= '<div class="activity-inner">' . $content . '</div>';

	$primary_link = bp_core_get_userlink( $user_id, false, true );

	/* Now write the values */
	$activity_id = xprofile_record_activity( array(
		'user_id' => $user_id,
		'content' => apply_filters( 'xprofile_activity_new_update', $activity_content ),
		'primary_link' => apply_filters( 'xprofile_activity_new_update_primary_link', $primary_link ),
		'component_action' => 'new_wire_post'
	) );

	/* Add this update to the "latest update" usermeta so it can be fetched anywhere. */
	update_usermeta( $bp->loggedin_user->id, 'bp_latest_update', array( 'id' => $activity_id, 'content' => wp_filter_kses( $content ) ) );

	return $activity_id;
}

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
	return new BP_XProfile_Group( $field_group_id );
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
function xprofile_set_field_data( $field, $user_id, $value ) {
	if ( is_numeric( $field ) )
		$field_id = $field;
	else
		$field_id = xprofile_get_field_id_from_name( $field );

	if ( !$field_id )
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
		$content = apply_filters('the_content', $content);
		$field_value = str_replace(']]>', ']]&gt;', $content);
	}

	return stripslashes( stripslashes( $field_value ) );
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

	$newurl = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $path );
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

	if ( (int)get_site_option( 'bp-disable-profile-sync' ) )
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

	bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->profile->id, 'new_wire_post' );
}
add_action( 'bp_wire_screen_latest', 'xprofile_remove_screen_notifications' );

/**
 * xprofile_filter_template_paths()
 *
 * Add fallback for the bp-sn-parent theme template locations used in BuddyPress versions
 * older than 1.2.
 *
 * @package BuddyPress Core
 */
function xprofile_filter_template_paths() {
	if ( 'bp-sn-parent' != basename( TEMPLATEPATH ) && !defined( 'BP_CLASSIC_TEMPLATE_STRUCTURE' ) )
		return false;

	add_filter( 'xprofile_template_display_profile', create_function( '', 'return "profile/index";' ) );
	add_filter( 'xprofile_template_edit_profile', create_function( '', 'return "profile/edit";' ) );
	add_filter( 'xprofile_template_change_avatar', create_function( '', 'return "profile/change-avatar";' ) );
}
add_action( 'init', 'xprofile_filter_template_paths' );

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
