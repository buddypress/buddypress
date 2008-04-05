<?php

/**************************************************************************
 xprofile_add_signup_fields()
 
 Render custom signup fields on the signup form.
 **************************************************************************/

function xprofile_add_signup_fields()
{
	global $bp_xprofile_callback;

	/* Fetch the fields needed for the signup form */
	$fields = BP_XProfile_Field::get_signup_fields();
	
	if($fields) {
	?>

	<table width="100%" cellspacing="4" cellpadding="9" border="0" id="extraFields">
		<tbody>
			<tr>
				<td>
				<div id="breaker">
					<h3><?php _e('Additional Information'); ?></h3>
					<p><?php _e('Please fill in the following fields to start up your member profile. Fields
						marked with a star are required.'); ?></p>
				</div>
				</td>
			</tr>
			
			<?php
			for($i=0; $i<count($fields); $i++)
			{
				if($bp_xprofile_callback[$i]['field_id'] == $fields[$i]->id && isset($bp_xprofile_callback[$i]['error_msg'])) {
					$css_class = ' class="error"';
				}
				else {
					$css_class = '';
				}
				?>
				<tr<?php echo $css_class; ?>>
					<td>
					<?php if($css_class != '') { echo '<div class="error">' . $bp_xprofile_callback[$i]['error_msg'] . '</div>'; } ?>
					<?php echo $fields[$i]->get_edit_html($bp_xprofile_callback[$i]['value']); ?>
					</td>
				</tr>
				<?php
				$field_ids .= $fields[$i]->id . ",";
			}
			?>
				
		</tbody>
	</table>
	<input type="hidden" name="xprofile_ids" value="<?php echo $field_ids; ?>" />	
	<?php
	}
}
add_action('signup_extra_fields', 'xprofile_add_signup_fields');

/**************************************************************************
 xprofile_validate_signup_fields()
 
 Custom validation function to validate both core Wordpress signup
 fields and custom signup fields at the same time.

 I'm basically doing some serious bypassing of the validate_blog_signup
 function to achive this. It saves hacking any of the core.
 **************************************************************************/

function xprofile_validate_signup_fields()
{
	global $bp_xprofile_callback;
	
	if(isset($_POST['validate_custom']))
	{
		// form has been submitted, let's validate the form
		// using the built in Wordpress functions and our own.

		$active_signup = 'all';
		$active_signup = apply_filters( 'wpmu_active_signup', $active_signup ); // return "all", "none", "blog" or "user"

		$newblogname = isset($_GET['new']) ? strtolower(preg_replace('/^-|-$|[^-a-zA-Z0-9]/', '', $_GET['new'])) : null;
		if( $_POST['blog_public'] != 1 )
			$_POST['blog_public'] = 0;

		if( $active_signup == "none" ) {
			_e( "Registration has been disabled." );
		} else {
			if( $active_signup == 'all' || $active_signup == "blog" )
			{
				$_POST['blog_id'] = $_POST['user_name'];
				$_POST['blog_title'] = $_POST['field_1']; // The core name fields.

				$counter = 0;
				$hasErrors = false;
				$prev_field_id = 0;
				foreach($_POST as $key => $value)
				{
					if(strpos($key, "field_") !== false)
					{
						$field_id = explode("_", $key);
						$field_id = $field_id[1];
						$field_type = BP_XProfile_Field::get_type($field_id);
						
						// Need to check if the previous field had
						// the same ID, as to not validate individual
						// day/month/year dropdowns individually.
						if($prev_field_id != $field_id) {
							$field = new BP_XProfile_Field($field_id);
							
							if($field_type == "datebox") {
								$value = strtotime($_POST['field_' . $field_id . '_day'] . " " . 
									     $_POST['field_' . $field_id . '_month'] . " " .
									     $_POST['field_' . $field_id . '_year']);
							}
							
							$bp_xprofile_callback[$counter] = array(
								"field_id" => $field->id,
								"type" => $field->type,
								"value" => $value
							);
							
							if($field->is_required && $_POST[$key] == '')
							{
								$bp_xprofile_callback[$counter]["error_msg"] = $field->name . ' cannot be left blank.';
								$hasErrors = true;
							}	
							
							$counter++;
						}
						
						$prev_field_id = $field_id;
					}
				}
				
				$result = wpmu_validate_user_signup($_POST['user_name'], $_POST['user_email']);
				extract($result);
				
				if ( $errors->get_error_code() || $hasErrors ) {

					signup_user($user_name, $user_email, $errors);
					
					echo '</div>';
					get_footer();
					die;
				}

				if(!$has_errors)
				{
					$result = wpmu_validate_blog_signup($_POST['blog_id'], $_POST['blog_title']);
					extract($result);

					if ( $errors->get_error_code() ) {
						signup_user($user_name, $user_email, $errors);
						//signup_blog($user_name, $user_email, $blog_id, $blog_title, $errors);
						return;
					}

					$public = (int) $_POST['blog_public'];
					$meta = array('lang_id' => 1, 'public' => $public);
					
					for($i=0; $i<count($bp_xprofile_callback); $i++)
					{
						$meta['field_' . $bp_xprofile_callback[$i]['field_id']] .= $bp_xprofile_callback[$i]['value'];
					}
					
					$meta['xprofile_field_ids'] = $_POST['xprofile_ids'];
					$meta = apply_filters( "add_signup_meta", $meta );

					wpmu_signup_blog($domain, $path, $blog_title, $user_name, $user_email, $meta);
					confirm_blog_signup($domain, $path, $blog_title, $user_name, $user_email, $meta);
					
					echo '</div>';
					get_footer();
					die;
				}
				else
				{
					signup_user($user_name, $user_email, $errors);
					
					echo '</div>';
					get_footer();
					die;
				}

			}
			else
			{
				_e( "Registration has been disabled." );
			}
		}

	}	
}

add_action('preprocess_signup_form', 'xprofile_validate_signup_fields');


/**************************************************************************
 xprofile_hidden_signup_fields()
 
 Adds hidden fields to the signup page to bypass the built in Wordpress
 validation functionality.
 **************************************************************************/

function xprofile_hidden_signup_fields()
{
	// Override the stage variable so we can redirect the validation of the form
	// to our own custom validation function.
	?>
	<input type="hidden" name="stage" value="" />
	<input type="hidden" name="validate_custom" value="1" />
	<?php
}
add_action('signup_hidden_fields', 'xprofile_hidden_signup_fields');


/**************************************************************************
 xprofile_on_activate()
 
 When a user activates their account, move the extra field data to the 
 correct tables, and then remove the WP options table entries.
 **************************************************************************/

function xprofile_on_activate($blog_id = null, $user_id = null)
{
	global $wpdb, $wpmuBaseTablePrefix, $profile_picture_path;
	
	// Extract signup meta fields to fill out profile
	$field_ids = get_blog_option($blog_id, 'xprofile_field_ids');
	$field_ids = explode(",", $field_ids);
		
	// Get the new user ID.
	$sql = "SELECT u.ID from " . $wpmuBaseTablePrefix . "users u, 
			" . $wpmuBaseTablePrefix . "usermeta um
			WHERE u.ID = um.user_id
			AND um.meta_key = 'primary_blog'
			AND um.meta_value = " . $blog_id;
			
	$user_id = $wpdb->get_var($sql); 

	// Loop through each bit of profile data and save it to profile.
	for($i=0; $i<count($field_ids); $i++)
	{
		if(bp_core_validate($field_ids[$i]))
		{
			$field_value = get_blog_option($blog_id, 'field_' . $field_ids[$i]);
			
			$field = new BP_XProfile_ProfileData();
			$field->user_id = $user_id;
			$field->value = $field_value;
			$field->field_id = $field_ids[$i];
			$field->last_updated = time();	
	
			$field->save();
			delete_blog_option($blog_id, 'field_' . $field_ids[$i]);
		}
	}
	delete_blog_option($blog_id, 'xprofile_field_ids');	
	
	// Set up profile pictures and create a directory to store them for the user.
	$profile_picture_path = trim(get_blog_option($blog_id, 'upload_path')) . '/profilepics';

	if(!wp_mkdir_p(ABSPATH . $profile_picture_path))
	{
		_e("The profile picture directory could not be created. Please contact the administrator.");
	}
	else
	{
		copy(ABSPATH . 'wp-content/mu-plugins/bp_xprofile/images/none.gif', ABSPATH . $profile_picture_path . "/none.gif");
		
		$pic = new BP_XProfile_Picture("none.gif");
		$pic->create_thumb();
		
		$thumb = explode("/", $pic->thumb_filename);
		$thumb = $thumb[count($thumb)-1];
		
		update_blog_option($blog_id, "profile_picture", "none.gif");		
		update_blog_option($blog_id, "profile_picture_thumbnail", $thumb);		
		
	}

}

add_action('wpmu_new_blog', 'xprofile_on_activate');


?>