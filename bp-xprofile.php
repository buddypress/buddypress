<?php

require_once( 'bp-core.php' );

define ( 'BP_XPROFILE_IS_INSTALLED', 1 );
define ( 'BP_XPROFILE_VERSION', '0.3.5' );

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

	$sql[] = "CREATE TABLE " . $bp['xprofile']['table_name_groups'] . " (
			  id int(11) unsigned NOT NULL auto_increment,
			  name varchar(150) NOT NULL,
			  description mediumtext NOT NULL,
			  can_delete tinyint(1) NOT NULL,
			  PRIMARY KEY  (id)
	);";
	
	$sql[] = "CREATE TABLE " . $bp['xprofile']['table_name_fields'] . " (
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
			  PRIMARY KEY (id)
	);";
	
	$sql[] = "CREATE TABLE " . $bp['xprofile']['table_name_data'] . " (
			  id int(11) unsigned NOT NULL auto_increment,
			  field_id int(11) unsigned NOT NULL,
			  user_id int(11) unsigned NOT NULL,
			  value longtext NOT NULL,
			  last_updated datetime NOT NULL,
			  PRIMARY KEY (id)
	);";
	
	$sql[] = "INSERT INTO ". $bp['xprofile']['table_name_groups'] . " VALUES (1, 'Basic', '', 0);";
	
	$sql[] = "INSERT INTO ". $bp['xprofile']['table_name_fields'] . " ( 
				id, group_id, parent_id, type, name, description, is_required, field_order, option_order, order_by, is_public, can_delete
			  ) VALUES (
				1, 1, 0, 'textbox', 'First Name', '', 1, 1, 0, '', 1, 0
			  );";
			
	$sql[] = "INSERT INTO ". $bp['xprofile']['table_name_fields'] . " ( 
				id, group_id, parent_id, type, name, description, is_required, field_order, option_order, order_by, is_public, can_delete
			  ) VALUES (
				2, 1, 0, 'textbox', 'Last Name', '', 1, 2, 0, '', 1, 0
			  );";
	
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
	
	$bp['xprofile'] = array(
		'table_name_groups' => $wpdb->base_prefix . 'bp_xprofile_groups',
		'table_name_fields' => $wpdb->base_prefix . 'bp_xprofile_fields',
		'table_name_data' 	=> $wpdb->base_prefix . 'bp_xprofile_data',
		'image_base' 		=> get_option('siteurl') . '/wp-content/mu-plugins/bp-xprofile/images',
		'slug'		 		=> 'profile'
	);
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
	
	if ( $wpdb->blogid == get_usermeta( $bp['current_userid'], 'home_base' ) ) {
		add_menu_page( __('Profile'), __('Profile'), 1, basename(__FILE__), 'bp_core_avatar_admin' );
		add_submenu_page( basename(__FILE__), __('Profile &rsaquo; Avatar'), __('Avatar'), 1, basename(__FILE__), 'xprofile_avatar_admin' );		
		add_options_page( __('Profile'), __('Profile'), 1, basename(__FILE__), 'xprofile_add_settings' );		
		
		$groups = BP_XProfile_Group::get_all();

		for ( $i=0; $i < count($groups); $i++ ) {
			if ( $groups[$i]->fields ) {
				add_submenu_page( basename(__FILE__), __('Profile') . '  &rsaquo; ' . $groups[$i]->name, $groups[$i]->name, 1, "xprofile_" . $groups[$i]->name, "xprofile_edit" );		
			}
		}
	}				

	if ( is_site_admin() ) {
		wp_enqueue_script( 'jquery.tablednd', '/wp-content/mu-plugins/bp-core/js/jquery/jquery.tablednd.js', array( 'jquery' ), '0.4' );
	
		/* Add the administration tab under the "Site Admin" tab for site administrators */
		add_submenu_page( 'wpmu-admin.php', __("Profiles"), __("Profiles"), 1, "xprofile_settings", "xprofile_admin" );
	}

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( ( $wpdb->get_var("show tables like '%" . $bp['xprofile']['table_name_groups'] . "%'") == false ) || ( get_site_option('bp-xprofile-version') < BP_XPROFILE_VERSION )  )
		xprofile_install(BP_XPROFILE_VERSION);
	
}
add_action( 'admin_menu', 'xprofile_add_admin_menu' );


/**************************************************************************
 xprofile_setup_nav()
 
 Set up front end navigation.
 **************************************************************************/

function xprofile_setup_nav() {
	global $bp;

	$bp['bp_nav'][0] = array(
		'id'	=> $bp['xprofile']['slug'],
		'name'  => 'Profile', 
		'link'  => $bp['loggedin_domain'] . $bp['xprofile']['slug']
	);

	$bp['bp_users_nav'][0] = array(
		'id'	=> $bp['xprofile']['slug'],
		'name'  => 'Profile', 
		'link'  => $bp['current_domain'] . $bp['xprofile']['slug']
	);
	
	$bp['bp_options_nav'][$bp['xprofile']['slug']] = array(
		'public'    	=> array( 
			'name' => __('Public'),
			'link' => $bp['loggedin_domain'] . $bp['xprofile']['slug'] . '/' ),
		'edit'	  		=> array(
			'name' => __('Edit Profile'),
			'link' => $bp['loggedin_domain'] . $bp['xprofile']['slug'] . '/edit' ),
		'change-avatar' => array( 
			'name' => __('Change Avatar'),
			'link' => $bp['loggedin_domain'] . $bp['xprofile']['slug'] . '/change-avatar' )
	);
	
	if ( $bp['current_component'] == $bp['xprofile']['slug'] ) {
		if ( bp_is_home() ) {
			$bp['bp_options_title'] = __('My Profile');
		} else {
			$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = bp_user_fullname( $bp['current_userid'], false ); 
		}
	}
	
}
add_action( 'wp', 'xprofile_setup_nav', 2 );


/**************************************************************************
 xprofile_catch_action()
 
 Catch actions via pretty urls.
 **************************************************************************/

function xprofile_catch_action() {
	global $current_blog, $bp;
	
	if ( $bp['current_component'] == $bp['xprofile']['slug'] && $current_blog->blog_id > 1 ) {

		if ( $bp['current_action'] == 'public' ) {
			bp_catch_uri( 'profile/index' );
		} else if ( $bp['current_action'] == 'edit' && $bp['loggedin_userid'] == $bp['current_userid'] ) {
			bp_catch_uri( 'profile/edit' );
		} else if ( $bp['current_action'] == 'change-avatar' && $bp['loggedin_userid'] == $bp['current_userid'] ) {
			add_action( 'wp_head', 'bp_core_add_cropper_js' );
			bp_catch_uri( 'profile/change-avatar' );
		} else if ( $bp['current_action'] == 'delete-avatar' && $bp['loggedin_userid'] == $bp['current_userid'] ) {
			bp_core_delete_avatar();
			add_action( 'wp_head', 'bp_core_add_cropper_js' );
			bp_catch_uri( 'profile/change-avatar' );
		} else {
			$bp['current_action'] = 'public';
			bp_catch_uri( 'profile/index' );
		}
	}
}
add_action( 'wp', 'xprofile_catch_action', 3 );

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
		$group_id   = $wpdb->get_var( $wpdb->prepare("SELECT id FROM " . $bp['xprofile']['table_name_groups'] . " WHERE name = %s", $group_name) );
	}
	$group = new BP_XProfile_Group($group_id);
	
	if ( !$action )
		$action = $bp['loggedin_domain'] . 'wp-admin/admin.php?page=xprofile_' . $group->name . '&amp;mode=save';
?>
	<div class="wrap">
		
		<h2><?php echo $group->name ?> <?php _e("Information") ?></h2>
		
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
							$field->message = sprintf( __('%s cannot be left blank.'), $field->name );
							$errors[] = $field->message . "<br />";
						}
						else if ( !$field->is_required && ( $current_field == '' || is_null($current_field) ) ) {
							// data removed, so delete the field data from the DB.								
							$profile_data = new BP_Xprofile_ProfileData( $group->fields[$j]->id );
							$profile_data->delete();
							$field->data->value = null;
						}
						else {
							// Field validates, save.
							$profile_data = new BP_Xprofile_ProfileData;
							$profile_data->field_id = $group->fields[$j]->id;
							$profile_data->user_id = $userdata->ID;
							$profile_data->last_updated = time();

							if($post_field_string != null) {
								$date_value = $_POST['field_' . $group->fields[$j]->id . '_day'] . 
										      $_POST['field_' . $group->fields[$j]->id . '_month'] . 
											  $_POST['field_' . $group->fields[$j]->id . '_year'];

								$profile_data->value = strtotime($date_value);
							}
							else {
								if ( is_array($current_field) )
									$current_field = serialize($current_field);
									
								$profile_data->value = $current_field;
							}

							if(!$profile_data->save()) {
								$field->message = __('There was a problem saving changes to this field, please try again.');
							}
							else {
								$field->data->value = $profile_data->value;
							}
						}
					}
					
					$list_html .= '<li>' . $field->get_edit_html() . '</li>';
				}
				
				$list_html .= '</ul>';
				
				$list_html .= '<p class="submit">
								<input type="submit" name="save" id="save" value="'.__('Save Changes &raquo;').'" />
							   </p>';

				if ( $errors && isset($_POST['save']) ) {
					$type = 'error';
					$message = __('There were problems saving your information. Please fix the following:<br />');
					
					for ( $i = 0; $i < count($errors); $i++ ) {
						$message .= $errors[$i];
					}
				}
				else if ( !$errors && isset($_POST['save'] ) ) {
					$type = 'success';
					$message = __('Changes saved.');
				}
			}
			else { ?>
				<div id="message" class="error fade">
					<p><?php _e('That group does not exist.'); ?></p>
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
 xprofile_add_settings()
 
 Renders the profile tab under settings for each member.
 **************************************************************************/

function xprofile_add_settings() {
?>
	<div class="wrap">
		<h2><?php _e('Profile Settings'); ?></h2>
		<p>Member profile settings will appear here.</p>
	</div>
<?php
}

/**************************************************************************
 xprofile_remove_data_on_blog_deletion()
 
 Removes all profile data from the DB if the admin deletes a Home Base.
 **************************************************************************/

function xprofile_remove_data_on_blog_deletion( $blog_id ) {
	global $wpdb, $bp;

	/* Only delete profile data if we are removing a home base */
	if ( $user_id = bp_core_get_homebase_userid( $blog_id ) ) {
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
}
add_action( 'delete_blog', 'xprofile_remove_data_on_blog_deletion', 1 );

?>
