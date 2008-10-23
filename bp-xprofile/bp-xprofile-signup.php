<?php

/**************************************************************************
 xprofile_add_signup_fields()
 
 Render custom signup fields on the signup form.
 **************************************************************************/

function xprofile_add_signup_fields() {
	global $bp_xprofile_callback, $avatar_error, $avatar_error_msg;

	/* Fetch the fields needed for the signup form */
	$fields = BP_XProfile_Field::get_signup_fields();

	if ( $fields ) {
	?>
	<div id="extraFields">
		<div id="breaker">
			<h3><?php _e('Additional Information', 'buddypress'); ?></h3>
			<p><?php _e('Please fill in the following fields to start up your member profile. Fields
				marked with a star are required.', 'buddypress'); ?></p>
		</div>
			<?php
			for ( $i = 0; $i < count($fields); $i++ ) {
				if ( $bp_xprofile_callback[$i]['field_id'] == $fields[$i]->id && isset($bp_xprofile_callback[$i]['error_msg']) ) {
					$css_class = ' class="error"';
				} else {
					$css_class = '';
				}
				?>
				<div>
					<?php if ( $css_class != '' ) { echo '<div class="error">' . $bp_xprofile_callback[$i]['error_msg'] . '</div>'; } ?>
					<?php echo $fields[$i]->get_edit_html($bp_xprofile_callback[$i]['value']); ?>
				</div>
				<?php
				$field_ids .= $fields[$i]->id . ",";
			}
			?>
	<input type="hidden" name="xprofile_ids" value="<?php echo $field_ids; ?>" />	
	<?php
	}
	
	?>
		<div id="breaker">
			<h3><?php _e('Profile Picture (Avatar)', 'buddypress'); ?></h3>
			<p><?php _e('You can upload an image from your computer to use as an avatar. This avatar will appear on your profile page.', 'buddypress'); ?></p>
		</div>
			<?php
			if ( $avatar_error ) {
				$css_class = ' class="error"';
			} else {
				$css_class = '';
			}
			?>
			
			<div<?php echo $css_class; ?>
				<?php if ( $css_class != '' ) { echo '<div class="error">' . $avatar_error_msg . '</div>'; } ?>
				
				<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo get_site_option('fileupload_maxk') * 1024; ?>" />
				<input type="hidden" name="slick_avatars_action" value="upload" />
				<input type="hidden" name="action" value="slick_avatars" />
				<input type="file" name="file" id="file" />
			</div>
	<script type="text/javascript">
		document.getElementById('setupform').setAttribute('enctype', 'multipart/form-data');
	</script>
	</div>
	<?php
}
add_action( 'signup_extra_fields', 'xprofile_add_signup_fields' );

/**************************************************************************
 xprofile_validate_signup_fields()
 
 Custom validation function to validate both core Wordpress signup
 fields and custom signup fields at the same time.

 I'm basically doing some serious bypassing of the validate_blog_signup
 function to achieve this. It saves hacking any of the core.
 **************************************************************************/

function xprofile_validate_signup_fields( $result ) {
	global $bp_xprofile_callback, $avatar_error, $avatar_error_msg, $has_errors;
	global $canvas, $original;
	global $current_site, $active_signup;
	
	if ( isset( $_POST['validate_custom'] ) ) {
		// form has been submitted, let's validate the form
		// using the built in Wordpress functions and our own.

		extract($result);
		
		$counter = 0;
		$has_errors = false;
		$prev_field_id = -1;
		
		// Validate all sign up fields
		$fields = BP_XProfile_Field::get_signup_fields();
		foreach ( $fields as $field ) {
			
			$value = $_POST['field_' . $field->id];
			
			// Need to check if the previous field had
			// the same ID, as to not validate individual
			// day/month/year dropdowns individually.
			if ( $prev_field_id != $field->id ) {
				$field = new BP_XProfile_Field($field->id);
				
					if ( $field->type == "datebox" ) {
						if ( $_POST['field_' . $field->id . '_day'] != "" && $_POST['field_' . $field->id . '_month'] != "" && $_POST['field_' . $field->id . '_year'] != "") {
							$value = strtotime( $_POST['field_' . $field->id . '_day'] . " " . 
								     			$_POST['field_' . $field->id . '_month'] . " " .
								     			$_POST['field_' . $field->id . '_year']);								
						}
				}
				
				if ( is_array($value) ) {
					$value = serialize( $value );
				}
				
				$bp_xprofile_callback[$counter] = array(
					"field_id" => $field->id,
					"type" => $field->type,
					"value" => $value
				);
				
				if ( $field->is_required && $value == '' ) {
					$bp_xprofile_callback[$counter]["error_msg"] = $field->name . ' cannot be left blank.';
					$has_errors = true;
				}
				
				$counter++;
			}
			
			$prev_field_id = $field->id;
		}

		// validate the avatar upload if there is one.
		$avatar_error = false;
		
		if ( bp_core_check_avatar_upload($_FILES) ) {
			if ( !bp_core_check_avatar_upload($_FILES) ) {
				$avatar_error = true;
				$avatar_error_msg = __('Your avatar upload failed, please try again.', 'buddypress');
			}

			if ( !bp_core_check_avatar_size($_FILES) ) {
				$avatar_error = true;
				$avatar_size = size_format(CORE_MAX_FILE_SIZE);
				$avatar_error_msg = sprintf( __('The file you uploaded is too big. Please upload a file under %s', 'buddypress'), $avatar_size);
			}

			if ( !bp_core_check_avatar_type($_FILES) ) {
				$avatar_error = true;
				$avatar_error_msg = __('Please upload only JPG, GIF or PNG photos.', 'buddypress');		
			}

			// "Handle" upload into temporary location
			if ( !$original = bp_core_handle_avatar_upload($_FILES) ) {
				$avatar_error = true;
				$avatar_error_msg = __('Upload Failed! Your photo dimensions are likely too big.', 'buddypress');						
			}

			if ( !bp_core_check_avatar_dimensions($original) ) {
				$avatar_error = true;
				$avatar_error_msg = sprintf( __('The image you upload must have dimensions of %d x %d pixels or larger.', 'buddypress'), CORE_AVATAR_V2_W, CORE_AVATAR_V2_W );
			}
			
			if ( !$canvas = bp_core_resize_avatar($original) )
				$canvas = $original;
		}
		
		if ( !$has_errors && !$avatar_error ) {
			$public = (int) $_POST['blog_public'];
			
			// put the user profile meta in a session ready to store.
			for ( $i = 0; $i < count($bp_xprofile_callback); $i++ ) {
				$meta['field_' . $bp_xprofile_callback[$i]['field_id']] .= $bp_xprofile_callback[$i]['value'];
			}

			$meta['xprofile_field_ids'] = $_POST['xprofile_ids'];
			$meta['avatar_image_resized'] = $canvas;
			$meta['avatar_image_original'] = $original;
			$meta['public'] = $public;
			$meta['lang_id'] = 1;
			
			$_SESSION['xprofile_meta'] = $meta;
		} else {
			$errors->add( 'bp_xprofile_errors', '' );
		}
	}
	
	return array('user_name' => $user_name, 'user_email' => $user_email, 'errors' => $errors);
}
add_filter( 'wpmu_validate_user_signup', 'xprofile_validate_signup_fields', 10, 1 );


function xprofile_add_profile_meta( $meta ) {
	return $_SESSION['xprofile_meta'];
}
add_filter( 'add_signup_meta', 'xprofile_add_profile_meta' );

/**************************************************************************
 xprofile_hidden_signup_fields()
 
 Adds hidden fields to the signup page to bypass the built in Wordpress
 validation functionality.
 **************************************************************************/

function xprofile_hidden_signup_fields() {
	?><input type="hidden" name="validate_custom" value="1" /><?php
}
add_action( 'signup_hidden_fields', 'xprofile_hidden_signup_fields' );


/**************************************************************************
 xprofile_on_activate_user()
 
 When a user activates their account, move the extra field data to the 
 correct tables.
 **************************************************************************/

function xprofile_on_activate_blog( $blog_id, $user_id, $password, $title, $meta ) {
	xprofile_extract_signup_meta( $user_id, $meta );
	
	// move and set the avatar if one has been provided.
	xprofile_handle_signup_avatar( $user_id, $meta );
}
add_action( 'wpmu_activate_blog', 'xprofile_on_activate_blog', 1, 5 );


function xprofile_on_activate_user( $user_id, $password, $meta ) {
	xprofile_extract_signup_meta( $user_id, $meta );

	// move and set the avatar if one has been provided.
	xprofile_handle_signup_avatar( $user_id, $meta );
}
add_action( 'wpmu_activate_user', 'xprofile_on_activate_user', 1, 3 );


function xprofile_extract_signup_meta( $user_id, $meta ) {
	// Extract signup meta fields to fill out profile
	$field_ids = $meta['xprofile_field_ids'];
	$field_ids = explode( ',', $field_ids );

	// Loop through each bit of profile data and save it to profile.
	for ( $i = 0; $i < count($field_ids); $i++ ) {
		if ( $field_ids[$i] == '' ) continue;
		
		$field_value = $meta["field_{$field_ids[$i]}"];
		
		$field 				 = new BP_XProfile_ProfileData();
		$field->user_id      = $user_id;
		$field->value        = $field_value;
		$field->field_id     = $field_ids[$i];
		$field->last_updated = time();	

		$field->save();
	}

	update_usermeta( $user_id, 'last_activity', time() );
}

function xprofile_handle_signup_avatar( $user_id, $meta ) {
	$resized = $meta['avatar_image_resized'];
	$original = $meta['avatar_image_original'];	
	
	if ( !empty($resized) && !empty($original) ) {
		$upload_dir = bp_avatar_upload_dir( $user_id );
		
		if ( $upload_dir ) {
			$resized_strip_path = explode( '/', $resized );
			$original_strip_path = explode( '/', $original );

			$resized_filename = $resized_strip_path[count($resized_strip_path) - 1];
			$original_filename = $original_strip_path[count($original_strip_path) - 1];

			$resized_new = $upload_dir['path'] . '/' . $resized_filename;
			$original_new = $upload_dir['path'] . '/' . $original_filename;

			@copy( $resized, $resized_new );
			@copy( $original, $original_new );

			@unlink($resized);
			@unlink($original);

			$resized = $resized_new;
			$original = $original_new;
		}
	
		// Render the cropper UI
		$action = site_url() . '/wp-activate.php?key=' . $_GET['key'] . '&amp;cropped=true';
		bp_core_render_avatar_cropper($original, $resized, $action, $user_id);
	}
}

function xprofile_catch_activate_crop() {
	if ( isset( $_GET['cropped'] ) ) {
		// The user has cropped their avatar after activating account
		
		// Confirm that the nonce is valid
		if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'slick_avatars' ) )
			wp_redirect( get_option('home') );
		
		$user_id = xprofile_get_user_by_key($_GET['key']);

		if ( $user_id && isset( $_POST['orig'] ) && isset( $_POST['canvas'] ) ) {
			bp_core_check_crop( $_POST['orig'], $_POST['canvas'] );
			$result = bp_core_avatar_cropstore( $_POST['orig'], $_POST['canvas'], $_POST['v1_x1'], $_POST['v1_y1'], $_POST['v1_w'], $_POST['v1_h'], $_POST['v2_x1'], $_POST['v2_y1'], $_POST['v2_w'], $_POST['v2_h'] );
			bp_core_avatar_save( $result, $user_id );
		}
		
		$ud = get_userdata($user_id);
		$url = site_url() . '/members/' . $ud->user_login;
		
		wp_redirect( $url );
	}
}
add_action( 'activate_header', 'xprofile_catch_activate_crop' );


function xprofile_get_user_by_key($key) {
	global $wpdb;
	
	$users_table = $wpdb->base_prefix . 'users';
	$signup_table = $wpdb->base_prefix . 'signups';
	
	$sql = $wpdb->prepare("SELECT ID FROM $users_table u, $signup_table s WHERE u.user_login = s.user_login AND s.activation_key = %s", $key);

	$user_id = $wpdb->get_var($sql);

	return $user_id;
}


?>
