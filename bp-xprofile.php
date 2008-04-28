<?php

$bp_xprofile_table_name        = $wpdb->base_prefix . 'bp_xprofile';
$bp_xprofile_table_name_groups = $wpdb->base_prefix . 'bp_xprofile_groups';
$bp_xprofile_table_name_fields = $wpdb->base_prefix . 'bp_xprofile_fields';
$bp_xprofile_table_name_data   = $wpdb->base_prefix . 'bp_xprofile_data';

$image_base             = get_option('siteurl') . '/wp-content/mu-plugins/bp-xprofile/images';

require_once( 'bp-xprofile/bp-xprofile-classes.php' );
require_once( 'bp-xprofile/bp-xprofile-admin.php' );
require_once( 'bp-xprofile/bp-xprofile-signup.php' );
require_once( 'bp-xprofile/bp-xprofile-templatetags.php' );
require_once( 'bp-xprofile/bp-xprofile-avatars.php' );
require_once( 'bp-xprofile/bp-xprofile-cssjs.php' );


/**************************************************************************
 xprofile_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function xprofile_install() {
	global $bp_xprofile_table_name_groups, $bp_xprofile_table_name_fields, $bp_xprofile_table_name_data;

	$sql = array();
	
	$sql[] = "CREATE TABLE `". $bp_xprofile_table_name_groups . "` (
			  `id` int(11) unsigned NOT NULL auto_increment,
			  `name` varchar(150) NOT NULL,
			  `description` mediumtext NOT NULL,
			  `can_delete` tinyint(1) NOT NULL,
			  PRIMARY KEY  (`id`)
	);";
	
	$sql[] = "CREATE TABLE `". $bp_xprofile_table_name_fields ."` (
			  `id` int(11) unsigned NOT NULL auto_increment,
			  `group_id` int(11) unsigned NOT NULL,
			  `parent_id` int(11) unsigned NOT NULL,
			  `type` varchar(150) NOT NULL,
			  `name` varchar(150) NOT NULL,
			  `description` longtext NOT NULL,
			  `is_required` tinyint(1) NOT NULL,
			  `can_delete` tinyint(1) NOT NULL default '1',
			  PRIMARY KEY  (`id`)
	);";
	
	$sql[] = "CREATE TABLE `". $bp_xprofile_table_name_data ."` (
			  `id` int(11) unsigned NOT NULL auto_increment,
			  `field_id` int(11) NOT NULL,
			  `user_id` int(11) NOT NULL,
			  `value` longtext NOT NULL,
			  `last_updated` datetime NOT NULL,
			  PRIMARY KEY  (`id`)
	)";
	
	$sql[] = "INSERT INTO `". $bp_xprofile_table_name_groups . "` VALUES (1, 'Basic', '', 0);";
	$sql[] = "INSERT INTO `". $bp_xprofile_table_name_fields . "` VALUES (1, 1, 0, 'textbox', 'First Name', '', 1, 0);";
	$sql[] = "INSERT INTO `". $bp_xprofile_table_name_fields . "` VALUES (2, 1, 0, 'textbox', 'Last Name', '', 1, 0);";
	
	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	dbDelta($sql);
}

/**************************************************************************
 xprofile_add_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function xprofile_add_menu() {
	global $wpdb, $bp_xprofile_table_name, $bp_xprofile, $groups, $userdata;
	
	if ( $wpdb->blogid == $userdata->primary_blog ) {
		add_menu_page( __('Profile'), __('Profile'), 1, basename(__FILE__), 'xprofile_avatar_admin' );
		add_submenu_page( basename(__FILE__), __('Profile &rsaquo; Avatar'), __('Avatar'), 1, basename(__FILE__), 'xprofile_avatar_admin' );		
		add_options_page( __('Profile'), __('Profile'), 1, basename(__FILE__), 'xprofile_add_settings' );		
		
		$groups = BP_XProfile_Group::get_all();

		for ( $i=0; $i < count($groups); $i++ ) {
			if ( $groups[$i]->fields ) {
				add_submenu_page( basename(__FILE__), __('Profile') . '  &rsaquo; ' . $groups[$i]->name, $groups[$i]->name, 1, "xprofile_" . $groups[$i]->name, "xprofile_edit" );		
			}
		}
	
		/* Add the administration tab under the "Site Admin" tab for site administrators */
		add_submenu_page( 'wpmu-admin.php', __("Profiles"), __("Profiles"), 1, "xprofile_settings", "xprofile_admin" );
	}
	
	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( $wpdb->get_var("show tables like '%" . $bp_xprofile_table_name . "%'") == false )
		xprofile_install();
}
add_action( 'admin_menu', 'xprofile_add_menu' );


/**************************************************************************
 xprofile_setup()
 
 Setup CSS, JS and other things needed for the xprofile component.
**************************************************************************/

function xprofile_setup() {
	add_action( 'admin_print_scripts', 'xprofile_add_css' );
	add_action( 'admin_print_scripts', 'xprofile_add_js' );
}
add_action( 'admin_menu', 'xprofile_setup' );


/**************************************************************************
 xprofile_profile_template()
 
 Set up access to authordata and then set up template tags for use in
 templates.
 **************************************************************************/

function xprofile_profile_template() {	
	global $is_author, $userdata, $authordata, $profile_template;
	
	query_posts('showposts=1');
	if ( have_posts() ) : while ( have_posts() ) : the_post(); endwhile; endif;
	
	$profile_template = new BP_XProfile_Template;
}
add_action( 'wp_head', 'xprofile_profile_template' );

/**************************************************************************
 xprofile_edit()
 
 Renders the edit form for the profile fields within a group as well as
 handling the save action.
 **************************************************************************/

function xprofile_edit() {
	global $wpdb, $bp_xprofile_table_name_groups, $userdata;
		
	// Dynamic tabs mean that we have to assign the same function to all
	// profile group tabs but we still need to distinguish what information 
	// to display for the current tab. Thankfully the page get var holds the key.
	$group_name = explode( "_", $_GET['page'] );
	$group_name = $group_name[1]; // xprofile_XXXX <-- This X bit.
	$group_id   = $wpdb->get_var( $wpdb->prepare("SELECT id FROM $bp_xprofile_table_name_groups WHERE name = %s", $group_name) );

	$group = new BP_XProfile_Group($group_id);
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
					
					if ( isset($_GET['mode']) && $_GET['mode'] == "save" ) {
						$post_field_string = ( $group->fields[$j]->type == 'datebox' ) ? '_day' : null;
						$posted_fields = explode( ",", $_POST['field_ids'] );
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
			else {
				$list_html .= '<p>' . __('This group is currently empty. Please contact the site admin if this is incorrect.') . '</p>';
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

		<p><form action="admin.php?page=<?php echo $_GET['page'] ?>&amp;mode=save" method="post">
		<?php $field_ids = implode( ",", $field_ids ); ?>
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


?>