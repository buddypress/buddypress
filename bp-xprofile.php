<?php

require_once( 'bp-core.php' );

define ( 'BP_XPROFILE_IS_INSTALLED', 1 );
define ( 'BP_XPROFILE_VERSION', '0.3.10' );

require_once( 'bp-xprofile/admin-mods/bp-xprofile-admin-mods.php' );
require_once( 'bp-xprofile/bp-xprofile-classes.php' );
require_once( 'bp-xprofile/bp-xprofile-admin.php' );
require_once( 'bp-xprofile/bp-xprofile-signup.php' );
require_once( 'bp-xprofile/bp-xprofile-templatetags.php' );
require_once( 'bp-xprofile/bp-xprofile-cssjs.php' );


/**************************************************************************
 xprofile_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function xprofile_install( $version ) {
	global $bp;
	
	$sql = array();

	$sql[] = "CREATE TABLE " . $bp['profile']['table_name_groups'] . " (
			  id int(11) unsigned NOT NULL auto_increment,
			  name varchar(150) NOT NULL,
			  description mediumtext NOT NULL,
			  can_delete tinyint(1) NOT NULL,
			  PRIMARY KEY  (id),
			  KEY can_delete (can_delete)
	);";
	
	$sql[] = "CREATE TABLE " . $bp['profile']['table_name_fields'] . " (
			  id int(11) unsigned NOT NULL auto_increment,
			  group_id int(11) unsigned NOT NULL,
			  parent_id int(11) unsigned NOT NULL,
			  type varchar(150) NOT NULL,
			  name varchar(150) NOT NULL,
			  description longtext NOT NULL,
			  is_required tinyint(1) NOT NULL DEFAULT '0',
			  is_default_option tinyint(1) NOT NULL DEFAULT '0',
			  field_order int(11) NOT NULL DEFAULT '0',
			  option_order int(11) NOT NULL DEFAULT '0',
			  order_by varchar(15) NOT NULL,
			  is_public int(2) NOT NULL DEFAULT '1',
			  can_delete tinyint(1) NOT NULL DEFAULT '1',
			  PRIMARY KEY (id),
			  KEY group_id (group_id),
			  KEY parent_id (parent_id),
			  KEY is_public (is_public),
			  KEY can_delete (can_delete),
			  KEY is_required (is_required)
	);";
	
	$sql[] = "CREATE TABLE " . $bp['profile']['table_name_data'] . " (
			  id int(11) unsigned NOT NULL auto_increment,
			  field_id int(11) unsigned NOT NULL,
			  user_id int(11) unsigned NOT NULL,
			  value longtext NOT NULL,
			  last_updated datetime NOT NULL,
			  PRIMARY KEY (id),
			  KEY field_id (field_id),
			  KEY user_id (user_id)
	);";
	
	$sql[] = "INSERT INTO ". $bp['profile']['table_name_groups'] . " VALUES (1, 'Basic', '', 0);";
	
	$sql[] = "INSERT INTO ". $bp['profile']['table_name_fields'] . " ( 
				id, group_id, parent_id, type, name, description, is_required, field_order, option_order, order_by, is_public, can_delete
			  ) VALUES (
				1, 1, 0, 'textbox', '" . __( 'First Name', 'buddypress') . "', '', 1, 1, 0, '', 1, 0
			  );";
			
	$sql[] = "INSERT INTO ". $bp['profile']['table_name_fields'] . " ( 
				id, group_id, parent_id, type, name, description, is_required, field_order, option_order, order_by, is_public, can_delete
			  ) VALUES (
				2, 1, 0, 'textbox', '" . __( 'Last Name', 'buddypress') . "', '', 1, 2, 0, '', 1, 0
			  );";
	
	if ( function_exists('bp_wire_install') ) {
		$sql[] = "CREATE TABLE ". $bp['profile']['table_name_wire'] ." (
		  		id int(11) NOT NULL AUTO_INCREMENT,
				item_id int(11) NOT NULL,
				user_id int(11) NOT NULL,
				content longtext NOT NULL,
				date_posted datetime NOT NULL,
				PRIMARY KEY id (id),
				KEY item_id (item_id),
			    KEY user_id (user_id)
		 	   );";
	}
	
	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );

	dbDelta($sql);
	add_site_option('bp-xprofile-version', $version);
}


/**************************************************************************
 xprofile_setup_globals()
 
 Set up and add all global variables for this component, and add them to 
 the $bp global variable array.
 **************************************************************************/

function xprofile_setup_globals() {
	global $bp, $wpdb;
	
	/* Need to start a session for signup metadata purposes */
	session_start();
	
	$bp['profile'] = array(
		'table_name_groups' => $wpdb->base_prefix . 'bp_xprofile_groups',
		'table_name_fields' => $wpdb->base_prefix . 'bp_xprofile_fields',
		'table_name_data' 	=> $wpdb->base_prefix . 'bp_xprofile_data',
		'format_activity_function' => 'xprofile_format_activity',
		'image_base' 		=> site_url() . '/wp-content/mu-plugins/bp-xprofile/images',
		'slug'		 		=> 'profile'
	);
	
	if ( function_exists('bp_wire_install') )
		$bp['profile']['table_name_wire'] = $wpdb->base_prefix . 'bp_xprofile_wire';
	
	
}
add_action( 'wp', 'xprofile_setup_globals', 1 );	
add_action( '_admin_menu', 'xprofile_setup_globals', 1 );


/**************************************************************************
 xprofile_add_admin_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function xprofile_add_admin_menu() {
	global $wpdb, $bp, $groups, $userdata;
	
	if ( $wpdb->blogid == $bp['current_homebase_id'] ) {
		add_menu_page( __('Profile', 'buddypress'), __('Profile', 'buddypress'), 1, basename(__FILE__), 'bp_core_avatar_admin' );
		add_submenu_page( basename(__FILE__), __('Profile &rsaquo; Avatar', 'buddypress'), __('Avatar', 'buddypress'), 1, basename(__FILE__), 'xprofile_avatar_admin' );		
		
		$groups = BP_XProfile_Group::get_all();

		for ( $i=0; $i < count($groups); $i++ ) {
			if ( $groups[$i]->fields ) {
				add_submenu_page( basename(__FILE__), __('Profile', 'buddypress') . '  &rsaquo; ' . $groups[$i]->name, $groups[$i]->name, 1, "xprofile_" . $groups[$i]->name, "xprofile_edit" );		
			}
		}
	}				

	if ( is_site_admin() ) {
		wp_enqueue_script( 'jquery.tablednd', '/wp-content/mu-plugins/bp-core/js/jquery/jquery.tablednd.js', array( 'jquery' ), '0.4' );
	
		/* Add the administration tab under the "Site Admin" tab for site administrators */
		add_submenu_page( 'wpmu-admin.php', __("Profiles", 'buddypress'), __("Profiles", 'buddypress'), 1, "xprofile_settings", "xprofile_admin" );
	}

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( ( $wpdb->get_var("show tables like '%" . $bp['profile']['table_name_groups'] . "%'") == false ) || ( get_site_option('bp-xprofile-version') < BP_XPROFILE_VERSION )  )
		xprofile_install(BP_XPROFILE_VERSION);
	
}
add_action( 'admin_menu', 'xprofile_add_admin_menu' );


/**************************************************************************
 xprofile_setup_nav()
 
 Set up front end navigation.
 **************************************************************************/

function xprofile_setup_nav() {
	global $bp;
	
	/* Add 'Profile' to the main navigation */
	bp_core_add_nav_item( __('Profile', 'buddypress'), $bp['profile']['slug'] );
	bp_core_add_nav_default( $bp['profile']['slug'], 'xprofile_screen_display_profile', 'public' );
	
	$profile_link = $bp['loggedin_domain'] . $bp['profile']['slug'] . '/';
	
	/* Add the subnav items to the profile */
	bp_core_add_subnav_item( $bp['profile']['slug'], 'public', __('Public', 'buddypress'), $profile_link, 'xprofile_screen_display_profile' );
	bp_core_add_subnav_item( $bp['profile']['slug'], 'edit', __('Edit Profile', 'buddypress'), $profile_link, 'xprofile_screen_edit_profile' );
	bp_core_add_subnav_item( $bp['profile']['slug'], 'change-avatar', __('Change Avatar', 'buddypress'), $profile_link, 'xprofile_screen_change_avatar' );

	if ( $bp['current_component'] == $bp['profile']['slug'] ) {
		if ( bp_is_home() ) {
			$bp['bp_options_title'] = __('My Profile', 'buddypress');
		} else {
			$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = $bp['current_fullname']; 
		}
	}
}
add_action( 'wp', 'xprofile_setup_nav', 2 );

function xprofile_screen_display_profile() {
	bp_catch_uri( 'profile/index' );
}

function xprofile_screen_edit_profile() {
	if ( bp_is_home() )
		bp_catch_uri( 'profile/edit' );
}

function xprofile_screen_change_avatar() {
	if ( bp_is_home() ) {
		add_action( 'wp_head', 'bp_core_add_cropper_js' );
		bp_catch_uri( 'profile/change-avatar' );
	}
}

function xprofile_action_delete_avatar() {
	global $bp;
	
	if ( $bp['current_action'] != 'delete-avatar' )
		return false;
	
	if ( bp_is_home() ) {
		bp_core_delete_avatar();
		add_action( 'wp_head', 'bp_core_add_cropper_js' );
		bp_catch_uri( 'profile/change-avatar' );
	}
}
add_action( 'wp', 'xprofile_action_delete_avatar', 3 );


/**************************************************************************
 xprofile_record_activity()
 
 Records activity for the logged in user within the profile component so that
 it will show in the users activity stream (if installed)
 **************************************************************************/

function xprofile_record_activity( $args = true ) {
	global $bp;

	if ( function_exists('bp_activity_record') ) {
		extract($args);
		bp_activity_record( $item_id, $component_name, $component_action, $is_private );
	}
}
add_action( 'bp_xprofile_new_wire_post', 'xprofile_record_activity' );
add_action( 'bp_xprofile_updated_profile', 'xprofile_record_activity' );


/**************************************************************************
 xprofile_format_activity()
 
 Selects and formats recorded xprofile component activity.
 **************************************************************************/

function xprofile_format_activity( $item_id, $action, $for_secondary_user = false  ) {
	global $bp;
	
	switch( $action ) {
		case 'new_wire_post':
			$wire_post = new BP_Wire_Post( $bp['profile']['table_name_wire'], $item_id );
			
			if ( !$wire_post )
				return false;

			if ( ( $wire_post->item_id == $bp['loggedin_userid'] && $wire_post->user_id == $bp['loggedin_userid'] ) || ( $wire_post->item_id == $bp['current_userid'] && $wire_post->user_id == $bp['current_userid'] ) ) {
				$content = bp_core_get_userlink($wire_post->user_id) . ' ' . __('wrote on their own wire', 'buddypress') . ': <span class="time-since">%s</span>';				
			} else if ( ( $wire_post->item_id != $bp['loggedin_userid'] && $wire_post->user_id == $bp['loggedin_userid'] ) || ( $wire_post->item_id != $bp['current_userid'] && $wire_post->user_id == $bp['current_userid'] ) ) {
				$content = bp_core_get_userlink($wire_post->user_id) . ' ' . __('wrote on ', 'buddypress') . bp_core_get_userlink( $wire_post->item_id, false, false, true, true ) . ' wire: <span class="time-since">%s</span>';				
			} 
			
			$content .= '<blockquote>' . bp_create_excerpt($wire_post->content) . '</blockquote>';
			return $content;
		break;
		case 'updated_profile':
			$profile_group = new BP_XProfile_Group( $item_id );
			
			if ( !$profile_group )
				return false;
				
			return bp_core_get_userlink($bp['current_userid']) . ' ' . __('updated the', 'buddypress') . ' "<a href="' . $bp['current_domain'] . $bp['profile']['slug'] . '">' . $profile_group->name . '</a>" ' . __('information on', 'buddypress') . ' ' . bp_your_or_their() . ' ' . __('profile', 'buddypress') . '. <span class="time-since">%s</span>';
		break;
	}
	
	return false;
}

/**************************************************************************
 xprofile_edit()
 
 Renders the edit form for the profile fields within a group as well as
 handling the save action.
 **************************************************************************/

function xprofile_edit( $group_id = null, $action = null ) {
	global $wpdb, $userdata, $bp;
	
	if ( !$group_id ) {	
		// Dynamic tabs mean that we have to assign the same function to all
		// profile group tabs but we still need to distinguish what information 
		// to display for the current tab. Thankfully the page get var holds the key.
		$group_name = explode( "_", $_GET['page'] );
		$group_name = $group_name[1]; // xprofile_XXXX <-- This X bit.
		$group_id   = $wpdb->get_var( $wpdb->prepare("SELECT id FROM " . $bp['profile']['table_name_groups'] . " WHERE name = %s", $group_name) );
	}
	$group = new BP_XProfile_Group($group_id);
	
	if ( !$action )
		$action = $bp['loggedin_domain'] . 'wp-admin/admin.php?page=xprofile_' . $group->name . '&amp;mode=save';
?>
	<div class="wrap">
		
		<h2><?php echo $group->name ?> <?php _e("Information", 'buddypress') ?></h2>
		
		<?php
			if ( $group->fields ) {
				$errors    = null;
				$list_html = '<ul class="forTab" id="' . strtolower($group_name) . '">';
					
				for ( $j = 0; $j < count($group->fields); $j++ ) {	
					$field = new BP_XProfile_Field( $group->fields[$j]->id );	
					$field_ids[] = $group->fields[$j]->id;
					
					if ( isset($_GET['mode']) && $_GET['mode'] == 'save' ) {
						$post_field_string = ( $group->fields[$j]->type == 'datebox' ) ? '_day' : null;
						$posted_fields = explode( ',', $_POST['field_ids'] );
						$current_field = $_POST['field_' . $posted_fields[$j] . $post_field_string];
						
						if ( ( $field->is_required && !isset($current_field) ) ||
						     ( $field->is_required && $current_field == '' ) ) {
							
							// Validate the field.
							$field->message = sprintf( __('%s cannot be left blank.', 'buddypress'), $field->name );
							$errors[] = $field->message . "<br />";
						} else if ( !$field->is_required && ( $current_field == '' || is_null($current_field) ) ) {
							// data removed, so delete the field data from the DB.								
							$profile_data = new BP_Xprofile_ProfileData( $group->fields[$j]->id );
							$profile_data->delete();
							$field->data->value = null;
						} else {
							// Field validates, save.
							$profile_data = new BP_Xprofile_ProfileData;
							$profile_data->field_id = $group->fields[$j]->id;
							$profile_data->user_id = $userdata->ID;
							$profile_data->last_updated = time();

							if ( $post_field_string != null ) {
								$date_value = $_POST['field_' . $group->fields[$j]->id . '_day'] . 
										      $_POST['field_' . $group->fields[$j]->id . '_month'] . 
											  $_POST['field_' . $group->fields[$j]->id . '_year'];

								$profile_data->value = strtotime($date_value);
							} else {
								if ( is_array($current_field) )
									$current_field = serialize($current_field);
									
								$profile_data->value = $current_field;
							}

							if( !$profile_data->save() ) {
								$field->message = __('There was a problem saving changes to this field, please try again.', 'buddypress');
							} else {
								$field->data->value = $profile_data->value;
							}
						}
					}
					
					$list_html .= '<li>' . $field->get_edit_html() . '</li>';
				}
				
				$list_html .= '</ul>';
				
				$list_html .= '<p class="submit">
								<input type="submit" name="save" id="save" value="'.__('Save Changes &raquo;', 'buddypress').'" />
							   </p>';

				if ( $errors && isset($_POST['save']) ) {
					$type = 'error';
					$message = __('There were problems saving your information. Please fix the following:<br />', 'buddypress');
					
					for ( $i = 0; $i < count($errors); $i++ ) {
						$message .= $errors[$i];
					}
				} else if ( !$errors && isset($_POST['save'] ) ) {
					$type = 'success';
					$message = __('Changes saved.', 'buddypress');
					
					do_action( 'bp_xprofile_updated_profile', array( 'item_id' => $group->id, 'component_name' => 'profile', 'component_action' => 'updated_profile', 'is_private' => 0 ) );
					update_usermeta( $bp['loggedin_userid'], 'profile_last_updated', date("Y-m-d H:i:s") );
				}
			}
			else { ?>
				<div id="message" class="error fade">
					<p><?php _e('That group does not exist.', 'buddypress'); ?></p>
				</div>
			<?php
			}

		?>

		<?php
			if ( $message != '' ) {
				$type = ( $type == 'error' ) ? 'error' : 'updated';
		?>
			<div id="message" class="<?php echo $type; ?> fade">
				<p><?php echo $message; ?></p>
			</div>
		<?php } ?>

		<p><form action="<?php echo $action ?>" method="post">
		<?php 
			if ( $field_ids )
				$field_ids = implode( ",", $field_ids );
		?>
		<input type="hidden" name="field_ids" id="field_ids" value="<?php echo $field_ids; ?>" />
		
		<?php echo $list_html; ?>

		</form>
		</p>
		
	</div> 
<?php
}

/**************************************************************************
 xprofile_remove_data_on_blog_deletion()
 
 Removes all profile data from the DB if the admin deletes a Home Base.
 **************************************************************************/

function xprofile_remove_data_on_user_deletion( $user_id ) {
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
add_action( 'delete_user', 'xprofile_remove_data_on_user_deletion', 1 );

?>
