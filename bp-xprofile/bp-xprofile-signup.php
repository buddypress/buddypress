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
			<h3><?php _e('Additional Information'); ?></h3>
			<p><?php _e('Please fill in the following fields to start up your member profile. Fields
				marked with a star are required.'); ?></p>
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
			<h3><?php _e('Profile Picture (Avatar)'); ?></h3>
			<p><?php _e('You can upload an image from your computer to use as an avatar. This avatar will appear on your profile page.'); ?></p>
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

function xprofile_validate_signup_fields() {
	global $bp_xprofile_callback, $avatar_error, $avatar_error_msg, $has_errors;
	global $canvas, $original;

	if ( isset( $_POST['validate_custom'] ) ) {
		// form has been submitted, let's validate the form
		// using the built in Wordpress functions and our own.

		$active_signup = 'all';
		$active_signup = apply_filters( 'wpmu_active_signup', $active_signup ); // return "all", "none", "blog" or "user"

		$newblogname = isset( $_GET['new'] ) ? strtolower(preg_replace('/^-|-$|[^-a-zA-Z0-9]/', '', $_GET['new'])) : null;
		if ( $_POST['blog_public'] != 1 )
			$_POST['blog_public'] = 0;

		if ( $active_signup == "none" ) {
			_e( "Registration has been disabled." );
		} else {
			if ( $active_signup == 'all' || $active_signup == "blog" ) {
				$_POST['blog_id'] = $_POST['user_name'];
				$_POST['blog_title'] = $_POST['field_1'] . " " . $_POST['field_2']; // The core name fields.

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
						$avatar_error_msg = __('Your avatar upload failed, please try again.');
					}

					if ( !bp_core_check_avatar_size($_FILES) ) {
						$avatar_error = true;
						$avatar_size = size_format(1024 * CORE_MAX_FILE_SIZE);
						$avatar_error_msg = sprintf( __('The file you uploaded is too big. Please upload a file under %d'), $avatar_size);
					}

					if ( !bp_core_check_avatar_type($_FILES) ) {
						$avatar_error = true;
						$avatar_error_msg = __('Please upload only JPG, GIF or PNG photos.');		
					}

					// "Handle" upload into temporary location
					if ( !$original = bp_core_handle_avatar_upload($_FILES) ) {
						$avatar_error = true;
						$avatar_error_msg = __('Upload Failed! Your photo dimensions are likely too big.');						
					}

					if ( !bp_core_check_avatar_dimensions($original) ) {
						$avatar_error = true;
						$avatar_error_msg = sprintf( __('The image you upload must have dimensions of %d x %d pixels or larger.'), CORE_AVATAR_V2_W, CORE_AVATAR_V2_W );
					}
					
					if ( !$canvas = bp_core_resize_avatar($original) )
						$canvas = $original;
				}
				
				if ( !is_user_logged_in() ) {
					$result = wpmu_validate_user_signup( $_POST['user_name'], $_POST['user_email'] );
					extract($result);
				
					if ( $errors->get_error_code() || $has_errors || $avatar_error ) {
						signup_user($user_name, $user_email, $errors);
					
						echo '</div>';
						get_footer();
						die;
					}
				}
				
				if ( !$has_errors ) {
					if ( !is_user_logged_in() ) {
						// This is a new user signing up, not an existing user creating a home base.
						$result = wpmu_validate_blog_signup( $_POST['blog_id'], $_POST['blog_title'] );
						extract($result);

						if ( $errors->get_error_code() ) {
							signup_user( $user_name, $user_email, $errors );
							return;
						}
						
						$public = (int) $_POST['blog_public'];
						$meta = array( 'lang_id' => 1, 'public' => $public );

						for ( $i = 0; $i < count($bp_xprofile_callback); $i++ ) {
							$meta['field_' . $bp_xprofile_callback[$i]['field_id']] .= $bp_xprofile_callback[$i]['value'];
						}

						$meta['xprofile_field_ids'] = $_POST['xprofile_ids'];
						$meta['avatar_image_resized'] = $canvas;
						$meta['avatar_image_original'] = $original;

						$meta = apply_filters( "add_signup_meta", $meta );

						wpmu_signup_blog( $domain, $path, $blog_title, $user_name, $user_email, $meta );
						confirm_blog_signup( $domain, $path, $blog_title, $user_name, $user_email, $meta );

						echo '</div></div>';
						get_footer();
						die;
					} else {
						bp_core_validate_homebase_form_secondary();
					}
				} else {
					if ( !is_user_logged_in() ) {
						signup_user( $user_name, $user_email, $errors );
					
						echo '</div>';
						get_footer();
						die;
					} else {
						bp_core_validate_homebase_form_secondary( $user_name, $user_email, $errors );
					}
				}

			} else {
				_e( "Registration has been disabled." );
			}
		}
	}	
}

add_action( 'preprocess_signup_form', 'xprofile_validate_signup_fields' );


/**************************************************************************
 xprofile_hidden_signup_fields()
 
 Adds hidden fields to the signup page to bypass the built in Wordpress
 validation functionality.
 **************************************************************************/

function xprofile_hidden_signup_fields() {
	global $active_signup;
	
	// Override the stage variable so we can redirect the validation of the form
	// to our own custom validation function.
	
	if( !is_user_logged_in() ) {
	?>
	<input type="hidden" name="stage" value="" />
	<?php } ?>
	<input type="hidden" name="validate_custom" value="1" />
	<?php
	
	if ( $active_signup != 'none' )
		$active_signup = 'blog';
}
add_action( 'signup_hidden_fields', 'xprofile_hidden_signup_fields' );


/**************************************************************************
 xprofile_on_activate()
 
 When a user activates their account, move the extra field data to the 
 correct tables, and then remove the WP options table entries.
 **************************************************************************/

function xprofile_on_activate( $blog_id = null, $user_id = null ) {
	global $wpdb, $profile_picture_path;
	
	if ( WP_INSTALLING )
		return false;
	
	/* Only do this if this is a new user, and not a user creating a home base */
	if ( !is_user_logged_in() ) {

		// Extract signup meta fields to fill out profile
		$field_ids = get_blog_option( $blog_id, 'xprofile_field_ids' );
		$field_ids = explode( ",", $field_ids );
			
		// Get the new user ID.
		$sql = "SELECT u.ID from " . $wpdb->base_prefix . "users u, 
				" . $wpdb->base_prefix . "usermeta um
				WHERE u.ID = um.user_id
				AND um.meta_key = 'primary_blog'
				AND um.meta_value = " . $blog_id;

		$user_id = $wpdb->get_var($sql); 

		// Loop through each bit of profile data and save it to profile.
		for ( $i = 0; $i < count($field_ids); $i++ ) {
			$field_value = get_blog_option( $blog_id, 'field_' . $field_ids[$i] );
		
			$field 				 = new BP_XProfile_ProfileData();
			$field->user_id      = $user_id;
			$field->value        = $field_value;
			$field->field_id     = $field_ids[$i];
			$field->last_updated = time();	

			$field->save();
			delete_blog_option( $blog_id, 'field_' . $field_ids[$i] );
		}
		delete_blog_option( $blog_id, 'xprofile_field_ids' );
		
		/* Make this blog the "home base" for the new user */
		update_usermeta( $user_id, 'home_base', $blog_id );
		update_usermeta( $user_id, 'last_activity', time() );
		
		/* Set the BuddyPress theme as the theme for this blog */
		$wpdb->set_blog_id($blog_id);		
		switch_theme( 'buddypress', 'buddypress' );
		
		// move and set the avatar if one has been provided.
		$resized = get_blog_option( $blog_id, 'avatar_image_resized' );
		$original = get_blog_option( $blog_id, 'avatar_image_original' );	
	
		if ( $resized && $original ) {
			$upload_dir = bp_upload_dir(NULL, $blog_id);

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
			$action = get_blog_option( $blog_id, 'siteurl' ) . '/wp-activate.php?key=' . $_GET['key'] . '&amp;cropped=true';
			bp_core_render_avatar_cropper($original, $resized, $action, $user_id);
		}
	}
	
}
add_action( 'wpmu_new_blog', 'xprofile_on_activate' );


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
		
		$blog_id = get_usermeta( $user_id, 'home_base' );
		$url = get_blog_option( $blog_id, 'siteurl' );
		
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


// function xprofile_replace_blog_references() {
// 	if ( strpos( $_SERVER['SCRIPT_NAME'], 'wp-signup.php' ) ) {
// 		add_action( 'wp_head', 'xprofile_start_blog_reference_replacement' );
// 	}	
// }
// add_action( 'wp', 'xprofile_replace_blog_references' );

// function xprofile_start_blog_reference_replacement( $contents ) {	
// 	ob_start();
// 	add_action('wp_footer', 'xprofile_end_blog_reference_replacement');
// }
// 
// function xprofile_blog_reference_replacement( $contents ) {
// 	echo str_replace( 'blog', 'account', $contents );
// }
// 
// function xprofile_end_blog_reference_replacement() {
// 	$contents = ob_get_contents();
// 	ob_end_clean();
// 	xprofile_blog_reference_replacement($contents);
// }



?>
