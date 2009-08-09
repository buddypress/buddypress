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

}



?>