<?php
/***
 * Deprecated Extended Profile Functionality
 *
 * This file contains functions that are deprecated.
 * You should not under any circumstance use these functions as they are 
 * either no longer valid, or have been replaced with something much more awesome.
 *
 * If you are using functions in this file you should slap the back of your head
 * and then use the functions or solutions that have replaced them.
 * Most functions contain a note telling you what you should be doing or using instead.
 *
 * Of course, things will still work if you use these functions but you will
 * be the laughing stock of the BuddyPress community. We will all point and laugh at
 * you. You'll also be making things harder for yourself in the long run, 
 * and you will miss out on lovely performance and functionality improvements.
 * 
 * If you've checked you are not using any deprecated functions and finished your little
 * dance, you can add the following line to your wp-config.php file to prevent any of
 * these old functions from being loaded:
 *
 * define( 'BP_IGNORE_DEPRECATED', true );
 */

/* DEPRECATED - cropper now uses jQuery in BuddyPress 1.1+ */
function xprofile_add_cropper_js() {
	global $bp;
	
	if ( $_SERVER['SCRIPT_NAME'] == '/wp-activate.php' || $bp->current_component == BP_ACTIVATION_SLUG || $bp->current_action == 'change-avatar' ) {
		wp_enqueue_script('scriptaculous-root');
		wp_enqueue_script('cropper');
		add_action( 'wp_head', 'bp_core_add_cropper_js' );
	}
}

/* DEPRECATED - this is now all in the template - profile/edit.php in BuddyPress 1.1+ */
function bp_edit_profile_form() {
	global $bp; ?>
	
	<?php if ( !$bp->action_variables[1] ) $bp->action_variables[1] = 1; ?>

	<?php do_action( 'template_notices' ) ?>
	
	<?php do_action( 'bp_before_profile_edit_content' ) ?>
	
	<?php if ( bp_has_profile( 'profile_group_id='. $bp->action_variables[1] ) ) : while ( bp_profile_groups() ) : bp_the_profile_group(); ?>

	<form action="<?php bp_the_profile_group_edit_form_action() ?>" method="post" id="profile-edit-form" class="generic-form <?php bp_the_profile_group_slug() ?>">

		<?php do_action( 'bp_before_profile_field_content' ) ?>
	
			<?php while ( bp_profile_fields() ) : bp_the_profile_field(); ?>
			
				<div class="editfield">
				
					<?php if ( 'textbox' == bp_get_the_profile_field_type() ) : ?>
				
						<label for="<?php bp_the_profile_field_input_name() ?>"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></label>
						<input type="text" name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>" value="<?php bp_the_profile_field_edit_value() ?>" />
					
					<?php endif; ?>
			
					<?php if ( 'textarea' == bp_get_the_profile_field_type() ) : ?>
					
						<label for="<?php bp_the_profile_field_input_name() ?>"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></label>
						<textarea rows="5" cols="40" name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>"><?php bp_the_profile_field_edit_value() ?></textarea>
					
					<?php endif; ?>

					<?php if ( 'selectbox' == bp_get_the_profile_field_type() ) : ?>
				
						<label for="<?php bp_the_profile_field_input_name() ?>"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></label>
						<select name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>">
							<?php bp_the_profile_field_options() ?>
						</select>
					
					<?php endif; ?>

					<?php if ( 'multiselectbox' == bp_get_the_profile_field_type() ) : ?>
				
						<label for="<?php bp_the_profile_field_input_name() ?>"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></label>
						<select name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>" multiple="multiple">
							<?php bp_the_profile_field_options() ?>
						</select>
				
					<?php endif; ?>

					<?php if ( 'radio' == bp_get_the_profile_field_type() ) : ?>
				
						<div class="radio">
							<span class="label"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></span>
						
							<?php bp_the_profile_field_options() ?>
						
							<?php if ( !bp_get_the_profile_field_is_required() ) : ?>
								<a class="clear-value" href="javascript:clear( '<?php bp_the_profile_field_input_name() ?>' );"><?php _e( 'Clear', 'buddypress' ) ?></a>
							<?php endif; ?>
						</div>
				
					<?php endif; ?>	
			
					<?php if ( 'checkbox' == bp_get_the_profile_field_type() ) : ?>
				
						<div class="checkbox">
							<span class="label"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></span>
						
							<?php bp_the_profile_field_options() ?>
						</div>	
				
					<?php endif; ?>					

					<?php if ( 'datebox' == bp_get_the_profile_field_type() ) : ?>
				
						<div class="datebox">
							<label for="<?php bp_the_profile_field_input_name() ?>_day"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></label>
						
							<select name="<?php bp_the_profile_field_input_name() ?>_day" id="<?php bp_the_profile_field_input_name() ?>_day">
								<?php bp_the_profile_field_options( 'type=day' ) ?>
							</select>
						
							<select name="<?php bp_the_profile_field_input_name() ?>_month" id="<?php bp_the_profile_field_input_name() ?>_month">
								<?php bp_the_profile_field_options( 'type=month' ) ?>
							</select>
						
							<select name="<?php bp_the_profile_field_input_name() ?>_year" id="<?php bp_the_profile_field_input_name() ?>_year">
								<?php bp_the_profile_field_options( 'type=year' ) ?>
							</select>								
						</div>
				
					<?php endif; ?>	
				
					<?php do_action( 'bp_custom_profile_edit_fields' ) ?>
			
					<p class="description"><?php bp_the_profile_field_description() ?></p>
				</div>

			<?php endwhile; ?>

		<?php do_action( 'bp_after_profile_field_content' ) ?>
		
		<input type="submit" name="profile-group-edit-submit" id="profile-group-edit-submit" value="<?php _e( 'Save Changes', 'buddypress' ) ?> " />
		
		<input type="hidden" name="field_ids" id="field_ids" value="<?php bp_the_profile_group_field_ids() ?>" />
		<?php wp_nonce_field( 'bp_xprofile_edit' ) ?>
		
	</form>
	
	<?php endwhile; endif; ?>
	
	<?php do_action( 'bp_after_profile_edit_content' ) ?>
<?php
}

/*** DEPRECATED CSS AND JS - NOW IN THEME **********/

function xprofile_add_js() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
	
	if ( $_SERVER['SCRIPT_NAME'] == '/wp-signup.php' )
		wp_enqueue_script( 'jquery' );
}
add_action( 'wp', 'xprofile_add_js' );

function xprofile_add_css() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	if ( $_SERVER['SCRIPT_NAME'] == '/wp-signup.php' )
		wp_enqueue_style( 'bp-xprofile-signup', BP_PLUGIN_URL . '/bp-xprofile/deprecated/css/signup.css' );	
	
	wp_print_styles();
}
add_action( 'wp_head', 'xprofile_add_css' );

function xprofile_add_structure_css() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	/* Enqueue the structure CSS file to give basic positional formatting for xprofile pages */
	wp_enqueue_style( 'bp-xprofile-structure', BP_PLUGIN_URL . '/bp-xprofile/deprecated/css/structure.css' );	
}
add_action( 'bp_styles', 'xprofile_add_structure_css' );

// DEPRECATED BP_XProfile_Field class methods

class BP_XProfile_Field_Deprecated extends BP_XProfile_Field {
	function get_edit_html( $value = null ) {
		global $bp;
		
		$asterisk = '';
		if ( $this->is_required ) {
			$asterisk = '* ';
		}
		
		$error_class = '';
		if ( $this->message ) {
			$this->message = '<p class="' . $this->message_type . '">' . $this->message . '</p>';
			$message_class = ' class="' . $this->message_type . '"';
		}
		
		if ( !is_null($value) ) {
			$this->data->value = $value;
		}
		
		$this->data->value = stripslashes( wp_filter_kses( $this->data->value ) );
		
		switch ( $this->type ) {
			case 'textbox':
				$html .= '<div class="signup-field">';
				$html .= '<label class="signup-label" for="field_' . $this->id . '">' . $asterisk . $this->name . ':</label>';
				$html .= $this->message . '<input type="text" name="field_' . $this->id . '" id="field_' . $this->id . '" value="' . attribute_escape( $this->data->value ) . '" />';
				$html .= '<span class="signup-description">' . $this->desc . '</span>';
				$html .= '</div>';
			break;
			
			case 'textarea':
				$html .= '<div class="signup-field">';
				$html .= '<label class="signup-label" for="field_' . $this->id . '">' . $asterisk . $this->name . ':</label>';
				$html .= $this->message . '<textarea rows="5" cols="40" name="field_' . $this->id . '" id="field_' . $this->id . '">' . htmlspecialchars( $this->data->value ) . '</textarea>';
				$html .= '<span class="signup-description">' . $this->desc . '</span>';
				$html .= '</div>';
			break;
			
			case 'selectbox':
				$options = $this->get_children();
				
				$html .= '<div class="signup-field">';
				$html .= '<label class="signup-label" for="field_' . $this->id . '">' . $asterisk . $this->name . ':</label>';
				$html .= $this->message . '<select name="field_' . $this->id . '" id="field_' . $this->id . '">';
				
				$html .= '<option value="">--------</option>';	
				for ( $k = 0; $k < count($options); $k++ ) {
					$option_value = BP_XProfile_ProfileData::get_value_byid($options[$k]->parent_id);

					if ( $option_value == $options[$k]->name || $value == $options[$k]->name || $options[$k]->is_default_option ) {
						$selected = ' selected="selected"';
					} else {
						$selected = '';
					}
					
					$html .= '<option' . $selected . ' value="' . attribute_escape( $options[$k]->name ) . '">' . $options[$k]->name . '</option>';
				}
				
				$html .= '</select>';
				$html .= '<span class="signup-description">' . $this->desc . '</span>';
				$html .= '</div>';
			break;
			
			case 'multiselectbox':
				$options = $this->get_children();
				
				$html .= '<div class="signup-field">';
				$html .= '<label class="signup-label" for="field_' . $this->id . '">' . $asterisk . $this->name . ':</label>';
				$html .= $this->message . '<select class="multi-select" multiple="multiple" name="field_' . $this->id . '[]" id="field_' . $this->id . '">';

				if ( $value ) {
					$option_values = maybe_unserialize($value);
				} else {
					$option_values = BP_XProfile_ProfileData::get_value_byid($options[0]->parent_id);
					$option_values = maybe_unserialize($option_values);
				}

				for ( $k = 0; $k < count($options); $k++ ) {
					if ( @in_array( $options[$k]->name, $option_values ) ) {
						$selected = ' selected="selected"';
					} else {
						$selected = '';
					}
					
					$html .= '<option' . $selected . ' value="' . attribute_escape( $options[$k]->name ) . '">' . $options[$k]->name . '</option>';
				}

				$html .= '</select>';
				$html .= '<span class="signup-description">' . $this->desc . '</span>';
				$html .= '</div>';
			break;
			
			case 'radio':
				$options = $this->get_children();
				
				$html .= '<div class="radio signup-field" id="field_' . $this->id . '"><span class="signup-label">' . $asterisk . $this->name . ':</span>' . $this->message;
				for ( $k = 0; $k < count($options); $k++ ) {
					
					$option_value = BP_XProfile_ProfileData::get_value_byid($options[$k]->parent_id);
				
					if ( $option_value == $options[$k]->name || $value == $options[$k]->name || $options[$k]->is_default_option ) {
						$selected = ' checked="checked"';
					} else {
						$selected = '';
					}
					
					$html .= '<label><input' . $selected . ' type="radio" name="field_' . $this->id . '" id="option_' . $options[$k]->id . '" value="' . attribute_escape( $options[$k]->name ) . '"> ' . $options[$k]->name . '</label>';
				}
				
				if ( !$this->is_required ) {
					$html .= '<a class="clear-value" style="text-decoration: none;" href="javascript:clear(\'field_' . $this->id . '\');"><img src="' . $bp->profile->image_base . '/cross.gif" alt="' . __( 'Clear', 'buddypress' ) . '" /> ' . __( 'Clear', 'buddypress' ) . '</a>';
				}
				
				$html .= '<span class="signup-description">' . $this->desc . '</span>';	
				$html .= '<div class="clear"></div></div>';
				
			break;
			
			case 'checkbox':
				$options = $this->get_children();
		
				$html .= '<div class="checkbox signup-field" id="field_' . $this->id . '"><span class="signup-label">' . $asterisk . $this->name . ':</span>' . $this->message;
				
				if ( $value ) {
					$option_values = maybe_unserialize($value);
				} else {
					$option_values = BP_XProfile_ProfileData::get_value_byid($options[0]->parent_id);
					$option_values = maybe_unserialize($option_values);
				}

				for ( $k = 0; $k < count($options); $k++ ) {	
					for ( $j = 0; $j < count($option_values); $j++ ) {
						if ( $option_values[$j] == $options[$k]->name || @in_array( $options[$k]->name, $value ) || $options[$k]->is_default_option ) {
							$selected = ' checked="checked"';
							break;
						}
					}
					
					$html .= '<label><input' . $selected . ' type="checkbox" name="field_' . $this->id . '[]" id="field_' . $options[$k]->id . '_' . $k . '" value="' . attribute_escape( $options[$k]->name ) . '"> ' . $options[$k]->name . '</label>';
					$selected = '';
				}
				
				$html .= '<span class="signup-description">' . $this->desc . '</span>';				
				$html .= '<div class="clear"></div></div>';
				
			break;
			
			case 'datebox':
				if ( $this->data->value != '' ) {
					$day = date("j", $this->data->value);
					$month = date("F", $this->data->value);
					$year = date("Y", $this->data->value);
					$default_select = ' selected="selected"';
				}
				
				$html .= '<div id="field_' . $this->id . '" class="datefield signup-field">';
				$html .= '<label class="signup-label" for="field_' . $this->id . '_day">' . $asterisk . $this->name . ':</label>';
				
				$html .= $this->message . '
				<select name="field_' . $this->id . '_day" id="field_' . $this->id . '_day">';
				$html .= '<option value=""' . attribute_escape( $default_select ) . '>--</option>';
				
				for ( $i = 1; $i < 32; $i++ ) {
					if ( $day == $i ) { 
						$selected = ' selected = "selected"'; 
					} else {
						$selected = '';
					}
					$html .= '<option value="' . $i .'"' . $selected . '>' . $i . '</option>';
				}
				
				$html .= '</select>';
				
				$months = array( __( 'January', 'buddypress' ), __( 'February', 'buddypress' ), __( 'March', 'buddypress' ), 
								 __( 'April', 'buddypress' ), __( 'May', 'buddypress' ), __( 'June', 'buddypress' ),
								 __( 'July', 'buddypress' ), __( 'August', 'buddypress' ), __( 'September', 'buddypress' ),
								 __( 'October', 'buddypress' ), __( 'November', 'buddypress' ), __( 'December', 'buddypress' )
								);

				$html .= '
				<select name="field_' . $this->id . '_month" id="field_' . $this->id . '_month">';
				$html .= '<option value=""' . attribute_escape( $default_select ) . '>------</option>';
				
				for ( $i = 0; $i < 12; $i++ ) {
					if ( $month == $months[$i] ) {
						$selected = ' selected = "selected"';
					} else {
						$selected = '';
					}
					
					$html .= '<option value="' . $months[$i] . '"' . $selected . '>' . $months[$i] . '</option>';
				}

				$html .= '</select>';
				
				$html .= '
				<select name="field_' . $this->id . '_year" id="field_' . $this->id . '_year">';
				$html .= '<option value=""' . attribute_escape( $default_select ) . '>----</option>';
								
				for ( $i = date( 'Y', time() ); $i > 1899; $i-- ) {
					if ( $year == $i ) {
						$selected = ' selected = "selected"'; 
					} else {
						$selected = '';
					}
				
					$html .= '<option value="' . $i .'"' . $selected . '>' . $i . '</option>';
				}
				
				$html .= '</select>';
				$html .= '<span class="signup-description">' . $this->desc . '</span>';
				$html .= '</div>';
				
			break;
		}
		
		return $html;
	}
	
	/* Deprecated - Signup fields are now in the template */
	function get_signup_fields() {
		global $wpdb, $bp;
		
		$sql = $wpdb->prepare( "SELECT f.id FROM {$bp->profile->table_name_fields} AS f, {$bp->profile->table_name_groups} AS g WHERE g.name = %s AND f.parent_id = 0	AND g.id = f.group_id ORDER BY f.id", get_site_option('bp-xprofile-base-group-name') );

		if ( !$temp_fields = $wpdb->get_results($sql) )
			return false;
		
		for ( $i = 0; $i < count($temp_fields); $i++ ) {
			$fields[] = new BP_XProfile_Field_Deprecated( $temp_fields[$i]->id, null, false );
		}
		
		return $fields;
	}


}

/*** DEPRECATED SIGNUP FUNCTIONS *******/

function xprofile_clear_signup_cookie() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
		
	if ( !isset( $_REQUEST['action'] ) && $_POST['stage'] != 'validate-blog-signup' && $_POST['stage'] != 'validate-user-signup' )
		setcookie( 'bp_xprofile_meta', false, time()-1000, COOKIEPATH );
}
add_action( 'init', 'xprofile_clear_signup_cookie' );

function xprofile_add_signup_fields() {
	global $bp_xprofile_callback, $avatar_error, $avatar_error_msg;

	/* Fetch the fields needed for the signup form */
	$fields = BP_XProfile_Field_Deprecated::get_signup_fields();

	if ( $fields ) {
	?>
		<h3><?php _e('Your Profile Details', 'buddypress'); ?></h3>
		<p id="extra-fields-help"><?php _e('Please fill in the following fields to start up your member profile. Fields
			marked with a star are required.', 'buddypress'); ?></p>
		
		<div id="extra-form-fields">
		<?php
		for ( $i = 0; $i < count($fields); $i++ ) {
			if ( $bp_xprofile_callback[$i]['field_id'] == $fields[$i]->id && isset($bp_xprofile_callback[$i]['error_msg']) ) {
				$css_class = ' class="error"';
			} else {
				$css_class = '';
			}
			?>
			<div class="extra-field">
				<?php if ( $css_class != '' ) { echo '<div class="error">' . $bp_xprofile_callback[$i]['error_msg'] . '</div>'; } ?>
				<?php echo $fields[$i]->get_edit_html($bp_xprofile_callback[$i]['value']); ?>
			</div>
			<?php
			$field_ids .= $fields[$i]->id . ",";
		}
		?>
		</div>
	<input type="hidden" name="xprofile_ids" value="<?php echo attribute_escape( $field_ids ); ?>" />	
	<?php
	}
	
	if ( !(int) get_site_option( 'bp-disable-avatar-uploads' ) ) {
	?>
		<div id="avatar-form-fields">
			<h3><?php _e('Profile Picture (Avatar)', 'buddypress'); ?></h3>
			<p id="avatar-help-text"><?php _e('You can upload an image from your computer to use as an avatar. This avatar will appear on your profile page.', 'buddypress'); ?></p>
			<?php
			if ( $avatar_error ) {
				$css_class = ' error';
			} else {
				$css_class = '';
			}
			?>
			
			<div class="avatar-field<?php echo $css_class; ?>">
				<?php if ( $css_class != '' ) { echo '<div class="error">' . $avatar_error_msg . '</div>'; } ?>
				
				<label for="file"><?php _e( 'Select a file:', 'buddypress' ) ?></label>
				<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo get_site_option('fileupload_maxk') * 1024; ?>" />
				<input type="hidden" name="slick_avatars_action" value="upload" />
				<input type="hidden" name="action" value="slick_avatars" />
				<input type="file" name="file" id="file" />
			</div>
		<script type="text/javascript">
			jQuery(document).ready( function() {
				jQuery('form#setupform').attr( 'enctype', 'multipart/form-data' );
				jQuery('form#setupform').attr( 'encoding', 'multipart/form-data' );
			});
		</script>
		</div>
	<?php
	}
}
add_action( 'signup_extra_fields', 'xprofile_add_signup_fields' );

function xprofile_validate_signup_fields( $result ) {
	global $bp_xprofile_callback, $avatar_error_msg;
	global $canvas, $original;
	global $current_site, $active_signup;
	global $wp_upload_error;
	global $bp_signup_has_errors, $bp_signup_avatar_has_errors;
	
	if ( $_POST['stage'] != 'validate-user-signup' ) return $result;
	
	extract($result);

	if ( $bp_signup_has_errors || $bp_signup_avatar_has_errors )
		$errors->add( 'bp_xprofile_errors', '' );
		
	return array('user_name' => $user_name, 'user_email' => $user_email, 'errors' => $errors);
}
add_filter( 'wpmu_validate_user_signup', 'xprofile_validate_signup_fields', 10, 1 );

function xprofile_add_profile_meta( $meta ) {
	global $bp, $bp_blog_signup_meta, $bp_user_signup_meta;
	
	if ( $_POST['stage'] == 'validate-blog-signup' ) {
		$bp_meta = $bp_blog_signup_meta;
	} else if ( $_POST['stage'] == 'validate-user-signup' ) {
		$bp_meta = $bp_user_signup_meta;
	} else {
		$bp_meta = $meta;
	}

	return $bp_meta;
}
add_filter( 'add_signup_meta', 'xprofile_add_profile_meta' );

function xprofile_load_signup_meta() {
	global $bp_signup_has_errors, $bp_signup_avatar_has_errors;
	global $bp_xprofile_callback, $avatar_error_msg;
	global $canvas, $original;
	global $current_site, $active_signup;
	global $wp_upload_error;
	global $bp_user_signup_meta;

	if ( $_POST['stage'] != 'validate-user-signup' ) return;

	$counter = 0;
	$bp_signup_has_errors = false;
	$prev_field_id = -1;
	
	// Validate all sign up fields
	$fields = BP_XProfile_Field_Deprecated::get_signup_fields();

	if ( $fields ) {
		foreach ( $fields as $field ) {
		
			$value = $_POST['field_' . $field->id];

			// Need to check if the previous field had
			// the same ID, as to not validate individual
			// day/month/year dropdowns individually.
			if ( $prev_field_id != $field->id ) {
				$field = new BP_XProfile_Field($field->id);
			
				if ( 'datebox' == $field->type ) {
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

				if ( $field->is_required && empty( $value ) ) {
					$bp_xprofile_callback[$counter]["error_msg"] = sprintf( __( '%s cannot be left blank', 'buddypress' ), $field->name );
					$bp_signup_has_errors = true;
				}
			
				$counter++;
			}
		
			$prev_field_id = $field->id;
		}
	}
	
	// validate the avatar upload if there is one.
	$bp_signup_avatar_has_errors = false;
	$checked_upload = false;
	$checked_size = false;
	$checked_type = false;
	$original = false;
	$canvas = false;
	
	// Set friendly error feedback.
	$uploadErrors = array(
	        0 => __("There is no error, the file uploaded with success", 'buddypress'), 
	        1 => __("Your image was bigger than the maximum allowed file size of: ", 'buddypress') . size_format(CORE_MAX_FILE_SIZE), 
	        2 => __("Your image was bigger than the maximum allowed file size of: ", 'buddypress') . size_format(CORE_MAX_FILE_SIZE),
	        3 => __("The uploaded file was only partially uploaded", 'buddypress'),
	        6 => __("Missing a temporary folder", 'buddypress')
	);
	
	if ( isset($_FILES['file']) ) {

		if ( 4 !== $_FILES['file']['error'] ) {
			if ( !$checked_upload = bp_core_check_avatar_upload($_FILES) ) {
				$bp_signup_avatar_has_errors = true;
				$avatar_error_msg = $uploadErrors[$_FILES['file']['error']];
			}

			if ( $checked_upload && !$checked_size = bp_core_check_avatar_size($_FILES) ) {
				$bp_signup_avatar_has_errors = true;
				$avatar_size = size_format(CORE_MAX_FILE_SIZE);
				$avatar_error_msg = sprintf( __('The file you uploaded is too big. Please upload a file under %s', 'buddypress'), $avatar_size);
			}

			if ( $checked_upload && $checked_size && !$checked_type = bp_core_check_avatar_type($_FILES) ) {
				$bp_signup_avatar_has_errors = true;
				$avatar_error_msg = __('Please upload only JPG, GIF or PNG photos.', 'buddypress');		
			}

			// "Handle" upload into temporary location
			if ( $checked_upload && $checked_size && $checked_type && !$original = bp_core_handle_avatar_upload($_FILES) ) {
				$bp_signup_avatar_has_errors = true;
				$avatar_error_msg = sprintf( __('Upload Failed! Error was: %s', 'buddypress'), $wp_upload_error );	
				die;					
			}
	
			if ( $checked_upload && $checked_size && $checked_type && $original && !$canvas = bp_core_resize_avatar($original) )
				$canvas = $original;
		}
	}

	if ( !$bp_signup_has_errors && !$bp_signup_avatar_has_errors ) {		
		$public = (int) $_POST['blog_public'];
		
		// put the user profile meta in a session ready to store.
		for ( $i = 0; $i < count($bp_xprofile_callback); $i++ ) {
			$bp_user_signup_meta['field_' . $bp_xprofile_callback[$i]['field_id']] .= $bp_xprofile_callback[$i]['value'];
		}

		$bp_user_signup_meta['xprofile_field_ids'] = $_POST['xprofile_ids'];
		$bp_user_signup_meta['avatar_image_resized'] = $canvas;
		$bp_user_signup_meta['avatar_image_original'] = $original;
		
		$bp_user_signup_meta = serialize( $bp_user_signup_meta );
	}
}
add_action( 'init', 'xprofile_load_signup_meta' );

function xprofile_render_user_signup_meta() {
	global $bp_user_signup_meta;
	
	echo '<input type="hidden" name="bp_xprofile_meta" id="bp_xprofile_meta" value="' . attribute_escape( $bp_user_signup_meta ) . '" />';
}
add_action( 'signup_blogform', 'xprofile_render_user_signup_meta' );

function xprofile_load_blog_signup_meta() {
	global $bp_blog_signup_meta;
	
	if ( $_POST['stage'] != 'validate-blog-signup' ) return;
	
	$blog_meta = array( 
		'public' => $_POST['blog_public'],
		'lang_id' => 1, // deprecated
	);
	
	$bp_meta = unserialize( stripslashes( $_POST['bp_xprofile_meta'] ) );
	$bp_blog_signup_meta = array_merge( (array)$bp_meta, (array)$blog_meta );
}
add_action( 'init', 'xprofile_load_blog_signup_meta' );

function xprofile_on_activate_blog( $blog_id, $user_id, $password, $title, $meta ) {
	xprofile_extract_signup_meta( $user_id, $meta );
	
	if ( bp_has_custom_activation_page() )
		add_action( 'bp_activation_extras', 'xprofile_handle_signup_avatar', 1, 2 );
	else 
		xprofile_handle_signup_avatar( $user_id, $meta );
}
add_action( 'wpmu_activate_blog', 'xprofile_on_activate_blog', 1, 5 );

function xprofile_on_activate_user( $user_id, $password, $meta ) {	
	xprofile_extract_signup_meta( $user_id, $meta );
	
	if ( bp_has_custom_activation_page() )
		add_action( 'bp_activation_extras', 'xprofile_handle_signup_avatar', 1, 2 );
	else 
		xprofile_handle_signup_avatar( $user_id, $meta );
}
add_action( 'wpmu_activate_user', 'xprofile_on_activate_user', 1, 5 );

function xprofile_extract_signup_meta( $user_id, $meta ) {
	// Extract signup meta fields to fill out profile
	$field_ids = $meta['xprofile_field_ids'];
	$field_ids = explode( ',', $field_ids );

	// Loop through each bit of profile data and save it to profile.
	for ( $i = 0; $i < count($field_ids); $i++ ) {
		if ( empty( $field_ids[$i] ) ) continue;
		
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
	global $bp;
	
	$meta = maybe_unserialize( $meta );
	
	$resized = $meta['avatar_image_resized'];
	$original = $meta['avatar_image_original'];	

	if ( !empty($resized) && !empty($original) ) {
		// Create and set up the upload dir first.
		$upload_dir = xprofile_avatar_upload_dir( false, $user_id );
		
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

		$bp->avatar_admin->image = new stdClass;
		$bp->avatar_admin->image->dir = $resized;
		
		/* Set the url value for the image */
		$bp->avatar_admin->image->url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $bp->avatar_admin->image->dir );

		?>
		<form action="<?php echo $bp->root_domain . '/' . BP_ACTIVATION_SLUG ?>" method="post">
			<h3><?php _e( 'Crop Your New Avatar', 'buddypress' ) ?></h3>

			<img src="<?php echo attribute_escape( $bp->avatar_admin->image->url ) ?>" id="avatar-to-crop" class="avatar" alt="<?php _e( 'Avatar to crop', 'buddypress' ) ?>" />

			<input type="submit" name="avatar-crop-submit" id="avatar-crop-submit" value="<?php _e( 'Crop Image', 'buddypress' ) ?>" />

			<input type="hidden" name="image_src" id="image_src" value="<?php echo attribute_escape( $bp->avatar_admin->image->dir ) ?>" />
			<input type="hidden" id="x" name="x" />
			<input type="hidden" id="y" name="y" />
			<input type="hidden" id="w" name="w" />
			<input type="hidden" id="h" name="h" />
			<input type="hidden" id="cropped" name="cropped" />
			<input type="hidden" id="key" name="key" value="<?php echo attribute_escape( $_GET['key'] ) ?>"/>

			<?php wp_nonce_field( 'bp_avatar_cropstore' ); ?>
		</form><?php
	}
}

function xprofile_deprecated_add_cropper_js() {	
	global $bp;
	
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;
	
	if ( $bp->current_component == BP_ACTIVATION_SLUG )
		add_action( 'wp', 'bp_core_add_jquery_cropper' );
}
add_action( 'init', 'xprofile_deprecated_add_cropper_js' );

function xprofile_catch_activate_crop() {
	if ( isset( $_POST['cropped'] ) ) {

		// The user has cropped their avatar after activating account

		// Confirm that the nonce is valid
		check_admin_referer( 'bp_avatar_cropstore' );

		$user_id = xprofile_get_user_by_key( $_POST['key'] );

		bp_core_avatar_handle_crop( array( 'item_id' => $user_id, 'original_file' => str_replace( WP_CONTENT_DIR, '', $_POST['image_src'] ), 'crop_x' => $_POST['x'], 'crop_y' => $_POST['y'], 'crop_w' => $_POST['w'], 'crop_h' => $_POST['h'] ) );

		$ud = get_userdata($user_id);
		$url = site_url( BP_MEMBERS_SLUG . '/' . $ud->user_login );

		bp_core_redirect( $url );
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

/*** END DEPRECATED SIGNUP FUNCTIONS *****/

?>