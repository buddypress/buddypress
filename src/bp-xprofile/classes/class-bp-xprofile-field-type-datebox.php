<?php
/**
 * BuddyPress XProfile Classes.
 *
 * @package BuddyPress
 * @subpackage XProfileClasses
 * @since 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Datebox xprofile field type.
 *
 * @since 2.0.0
 */
class BP_XProfile_Field_Type_Datebox extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the datebox field type.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Single Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'Date Selector', 'xprofile field type', 'buddypress' );

		$this->set_format( '/^\d{4}-\d{1,2}-\d{1,2} 00:00:00$/', 'replace' ); // "Y-m-d 00:00:00"

		$this->do_settings_section = true;

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Datebox class.
		 *
		 * @since 2.0.0
		 *
		 * @param BP_XProfile_Field_Type_Datebox $this Current instance of
		 *                                             the field type datebox.
		 */
		do_action( 'bp_xprofile_field_type_datebox', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param array $raw_properties Optional key/value array of
	 *                              {@link http://dev.w3.org/html5/markup/input.html permitted attributes}
	 *                              that you want to add.
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// User_id is a special optional parameter that we pass to.
		// {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			$user_id = (int) $raw_properties['user_id'];
			unset( $raw_properties['user_id'] );
		} else {
			$user_id = bp_displayed_user_id();
		}

		$day_r = bp_parse_args( $raw_properties, array(
			'id'   => bp_get_the_profile_field_input_name() . '_day',
			'name' => bp_get_the_profile_field_input_name() . '_day'
		) );

		$month_r = bp_parse_args( $raw_properties, array(
			'id'   => bp_get_the_profile_field_input_name() . '_month',
			'name' => bp_get_the_profile_field_input_name() . '_month'
		) );

		$year_r = bp_parse_args( $raw_properties, array(
			'id'   => bp_get_the_profile_field_input_name() . '_year',
			'name' => bp_get_the_profile_field_input_name() . '_year'
		) ); ?>

		<fieldset class="datebox">

			<legend>
				<?php bp_the_profile_field_name(); ?>
				<?php bp_the_profile_field_required_label(); ?>
			</legend>

			<div class="input-options datebox-selects">

				<?php

				/**
				 * Fires after field label and displays associated errors for the field.
				 *
				 * This is a dynamic hook that is dependent on the associated
				 * field ID. The hooks will be similar to `bp_field_12_errors`
				 * where the 12 is the field ID. Simply replace the 12 with
				 * your needed target ID.
				 *
				 * @since 1.8.0
				 */
				do_action( bp_get_the_profile_field_errors_action() ); ?>

				<label for="<?php bp_the_profile_field_input_name(); ?>_day" class="<?php echo is_admin() ? 'screen-reader-text' : 'bp-screen-reader-text' ;?>"><?php
					/* translators: accessibility text */
					esc_html_e( 'Select day', 'buddypress' );
				?></label>
				<select <?php echo $this->get_edit_field_html_elements( $day_r ); ?>>
					<?php bp_the_profile_field_options( array(
						'type'    => 'day',
						'user_id' => $user_id
					) ); ?>
				</select>

				<label for="<?php bp_the_profile_field_input_name(); ?>_month" class="<?php echo is_admin() ? 'screen-reader-text' : 'bp-screen-reader-text' ;?>"><?php
					/* translators: accessibility text */
					esc_html_e( 'Select month', 'buddypress' );
				?></label>
				<select <?php echo $this->get_edit_field_html_elements( $month_r ); ?>>
					<?php bp_the_profile_field_options( array(
						'type'    => 'month',
						'user_id' => $user_id
					) ); ?>
				</select>

				<label for="<?php bp_the_profile_field_input_name(); ?>_year" class="<?php echo is_admin() ? 'screen-reader-text' : 'bp-screen-reader-text' ;?>"><?php
					/* translators: accessibility text */
					esc_html_e( 'Select year', 'buddypress' );
				?></label>
				<select <?php echo $this->get_edit_field_html_elements( $year_r ); ?>>
					<?php bp_the_profile_field_options( array(
						'type'    => 'year',
						'user_id' => $user_id
					) ); ?>
				</select>

			</div>

		</fieldset>
	<?php
	}

	/**
	 * Output the edit field options HTML for this field type.
	 *
	 * BuddyPress considers a field's "options" to be, for example, the items in a selectbox.
	 * These are stored separately in the database, and their templating is handled separately.
	 *
	 * This templating is separate from {@link BP_XProfile_Field_Type::edit_field_html()} because
	 * it's also used in the wp-admin screens when creating new fields, and for backwards compatibility.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
	 */
	public function edit_field_options_html( array $args = array() ) {

		$date       = BP_XProfile_ProfileData::get_value_byid( $this->field_obj->id, $args['user_id'] );
		$day        = 0;
		$month      = 0;
		$year       = 0;
		$html       = '';
		$eng_months = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );

		// Set day, month, year defaults.
		if ( ! empty( $date ) ) {

			// If Unix timestamp.
			if ( is_numeric( $date ) ) {
				$day   = date( 'j', $date );
				$month = date( 'F', $date );
				$year  = date( 'Y', $date );

			// If MySQL timestamp.
			} else {
				$day   = mysql2date( 'j', $date );
				$month = mysql2date( 'F', $date, false ); // Not localized, so that selected() works below.
				$year  = mysql2date( 'Y', $date );
			}
		}

		// Check for updated posted values, and errors preventing them from
		// being saved first time.
		if ( ! empty( $_POST['field_' . $this->field_obj->id . '_day'] ) ) {
			$new_day = (int) $_POST['field_' . $this->field_obj->id . '_day'];
			$day     = ( $day != $new_day ) ? $new_day : $day;
		}

		if ( ! empty( $_POST['field_' . $this->field_obj->id . '_month'] ) ) {
			if ( in_array( $_POST['field_' . $this->field_obj->id . '_month'], $eng_months ) ) {
				$new_month = $_POST['field_' . $this->field_obj->id . '_month'];
			} else {
				$new_month = $month;
			}

			$month = ( $month !== $new_month ) ? $new_month : $month;
		}

		if ( ! empty( $_POST['field_' . $this->field_obj->id . '_year'] ) ) {
			$new_year = (int) $_POST['field_' . $this->field_obj->id . '_year'];
			$year     = ( $year != $new_year ) ? $new_year : $year;
		}

		// $type will be passed by calling function when needed.
		switch ( $args['type'] ) {
			case 'day':
				$html = sprintf( '<option value="" %1$s>%2$s</option>', selected( $day, 0, false ), /* translators: no option picked in select box */ __( '----', 'buddypress' ) );

				for ( $i = 1; $i < 32; ++$i ) {
					$html .= sprintf( '<option value="%1$s" %2$s>%3$s</option>', (int) $i, selected( $day, $i, false ), (int) $i );
				}
			break;

			case 'month':
				$months = array(
					__( 'January',   'buddypress' ),
					__( 'February',  'buddypress' ),
					__( 'March',     'buddypress' ),
					__( 'April',     'buddypress' ),
					__( 'May',       'buddypress' ),
					__( 'June',      'buddypress' ),
					__( 'July',      'buddypress' ),
					__( 'August',    'buddypress' ),
					__( 'September', 'buddypress' ),
					__( 'October',   'buddypress' ),
					__( 'November',  'buddypress' ),
					__( 'December',  'buddypress' )
				);

				$html = sprintf( '<option value="" %1$s>%2$s</option>', selected( $month, 0, false ), /* translators: no option picked in select box */ __( '----', 'buddypress' ) );

				for ( $i = 0; $i < 12; ++$i ) {
					$html .= sprintf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $eng_months[$i] ), selected( $month, $eng_months[$i], false ), $months[$i] );
				}
			break;

			case 'year':
				$html = sprintf( '<option value="" %1$s>%2$s</option>', selected( $year, 0, false ), /* translators: no option picked in select box */ __( '----', 'buddypress' ) );

				$settings = $this->get_field_settings( $this->field_obj->id );

				if ( 'relative' === $settings['range_type'] ) {
					$start = date( 'Y' ) + $settings['range_relative_start'];
					$end   = date( 'Y' ) + $settings['range_relative_end'];
				} else {
					$start = $settings['range_absolute_start'];
					$end   = $settings['range_absolute_end'];
				}

				for ( $i = $end; $i >= $start; $i-- ) {
					$html .= sprintf( '<option value="%1$s" %2$s>%3$s</option>', (int) $i, selected( $year, $i, false ), (int) $i );
				}
			break;
		}

		/**
		 * Filters the output for the profile field datebox.
		 *
		 * @since 1.1.0
		 *
		 * @param string $html  HTML output for the field.
		 * @param string $value Which date type is being rendered for.
		 * @param string $day   Date formatted for the current day.
		 * @param string $month Date formatted for the current month.
		 * @param string $year  Date formatted for the current year.
		 * @param int    $id    ID of the field object being rendered.
		 * @param string $date  Current date.
		 */
		echo apply_filters( 'bp_get_the_profile_field_datebox', $html, $args['type'], $day, $month, $year, $this->field_obj->id, $date );
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 */
	public function admin_field_html( array $raw_properties = array() ) {

		$day_r = bp_parse_args( $raw_properties, array(
			'id'   => bp_get_the_profile_field_input_name() . '_day',
			'name' => bp_get_the_profile_field_input_name() . '_day'
		) );

		$month_r = bp_parse_args( $raw_properties, array(
			'id'   => bp_get_the_profile_field_input_name() . '_month',
			'name' => bp_get_the_profile_field_input_name() . '_month'
		) );

		$year_r = bp_parse_args( $raw_properties, array(
			'id'   => bp_get_the_profile_field_input_name() . '_year',
			'name' => bp_get_the_profile_field_input_name() . '_year'
		) ); ?>

		<label for="<?php bp_the_profile_field_input_name(); ?>_day" class="screen-reader-text"><?php
			/* translators: accessibility text */
			esc_html_e( 'Select day', 'buddypress' );
		?></label>
		<select <?php echo $this->get_edit_field_html_elements( $day_r ); ?>>
			<?php bp_the_profile_field_options( array( 'type' => 'day' ) ); ?>
		</select>

		<label for="<?php bp_the_profile_field_input_name(); ?>_month" class="screen-reader-text"><?php
			/* translators: accessibility text */
			esc_html_e( 'Select month', 'buddypress' );
		?></label>
		<select <?php echo $this->get_edit_field_html_elements( $month_r ); ?>>
			<?php bp_the_profile_field_options( array( 'type' => 'month' ) ); ?>
		</select>

		<label for="<?php bp_the_profile_field_input_name(); ?>_year" class="screen-reader-text"><?php
			/* translators: accessibility text */
			esc_html_e( 'Select year', 'buddypress' );
		?></label>
		<select <?php echo $this->get_edit_field_html_elements( $year_r ); ?>>
			<?php bp_the_profile_field_options( array( 'type' => 'year' ) ); ?>
		</select>

	<?php
	}

	/**
	 * Get settings for a given date field.
	 *
	 * @since 2.7.0
	 *
	 * @param int $field_id ID of the field.
	 * @return array
	 */
	public static function get_field_settings( $field_id ) {
		$defaults = array(
			'date_format'          => 'Y-m-d',
			'date_format_custom'   => '',
			'range_type'           => 'absolute',
			'range_absolute_start' => date( 'Y' ) - 60,
			'range_absolute_end'   => date( 'Y' ) + 10,
			'range_relative_start' => '-10',
			'range_relative_end'   => '20',
		);

		$settings = array();
		foreach ( $defaults as $key => $value ) {
			$saved = bp_xprofile_get_meta( $field_id, 'field', $key, true );

			if ( $saved ) {
				$settings[ $key ] = $saved;
			} else {
				$settings[ $key ] = $value;
			}
		}

		$settings = self::validate_settings( $settings );

		return $settings;
	}

	/**
	 * Validate date field settings.
	 *
	 * @since 2.7.0
	 *
	 * @param array $settings Raw settings.
	 * @return array Validated settings.
	 */
	public static function validate_settings( $settings ) {
		foreach ( $settings as $key => &$value ) {
			switch ( $key ) {
				case 'range_type' :
					if ( $value !== 'absolute' ) {
						$value = 'relative';
					}
				break;

				// @todo More date restrictions?
				case 'range_absolute_start' :
				case 'range_absolute_end' :
					$value = absint( $value );
				break;

				case 'range_relative_start' :
				case 'range_relative_end' :
					$value = intval( $value );
				break;
			}
		}

		return $settings;
	}

	/**
	 * Save settings from the field edit screen in the Dashboard.
	 *
	 * @param int   $field_id ID of the field.
	 * @param array $settings Array of settings.
	 * @return bool True on success.
	 */
	public function admin_save_settings( $field_id, $settings ) {
		$existing_settings = self::get_field_settings( $field_id );

		$saved_settings = array();
		foreach ( array_keys( $existing_settings ) as $setting ) {
			switch ( $setting ) {
				case 'range_relative_start' :
				case 'range_relative_end' :
					$op_key = $setting . '_type';
					if ( isset( $settings[ $op_key ] ) && 'past' === $settings[ $op_key ] ) {
						$value = 0 - intval( $settings[ $setting ] );
					} else {
						$value = intval( $settings[ $setting ] );
					}

					$saved_settings[ $setting ] = $value;
				break;

				default :
					if ( isset( $settings[ $setting ] ) ) {
						$saved_settings[ $setting ] = $settings[ $setting ];
					}
				break;
			}
		}

		// Sanitize and validate saved settings.
		$saved_settings = self::validate_settings( $saved_settings );

		foreach ( $saved_settings as $setting_key => $setting_value ) {
			bp_xprofile_update_meta( $field_id, 'field', $setting_key, $setting_value );
		}

		return true;
	}

	/**
	 * Generate the settings markup for Date fields.
	 *
	 * @since 2.7.0
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string            $control_type  Optional. HTML input type used to render the current
	 *                                         field's child options.
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		$type = array_search( get_class( $this ), bp_xprofile_get_field_types() );

		if ( false === $type ) {
			return;
		}

		$class = $current_field->type != $type ? 'display: none;' : '';

		$settings = self::get_field_settings( $current_field->id );
		?>

<div id="<?php echo esc_attr( $type ); ?>" class="postbox bp-options-box" style="<?php echo esc_attr( $class ); ?> margin-top: 15px;">
	<table class="form-table bp-date-options">
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Date format', 'buddypress' ); ?>
			</th>

			<td>
				<fieldset>
					<legend class="screen-reader-text">
						<?php esc_html_e( 'Date format', 'buddypress' ); ?>
					</legend>

					<?php foreach ( $this->get_date_formats() as $format ): ?>
						<div class="bp-date-format-option">
							<label for="date-format-<?php echo esc_attr( $format ); ?>">
								<input type="radio" name="field-settings[date_format]" id="date-format-<?php echo esc_attr( $format ); ?>" value="<?php echo esc_attr( $format ); ?>" <?php checked( $format, $settings['date_format'] ); ?> />
								<span class="date-format-label"><?php echo date_i18n( $format ); ?></span>
								<code><?php echo esc_html( $format ); ?></code>
							</label>
						</div>
					<?php endforeach;?>

					<div class="bp-date-format-option">
						<label for="date-format-elapsed">
							<input type="radio" name="field-settings[date_format]" id="date-format-elapsed" <?php checked( 'elapsed', $settings['date_format'] ); ?> value="elapsed" aria-describedby="date-format-elapsed-setting" />
							<span class="date-format-label" id="date-format-elapsed-setting"><?php esc_html_e( 'Time elapsed', 'buddypress' ); ?></span> <?php _e( '<code>4 years ago</code>, <code>4 years from now</code>', 'buddypress' ); ?>
						</label>
					</div>

					<div class="bp-date-format-option">
						<label for="date-format-custom">
							<input type="radio" name="field-settings[date_format]" id="date-format-custom" <?php checked( 'custom', $settings['date_format'] ); ?> value="custom" />
							<span class="date-format-label"><?php esc_html_e( 'Custom:', 'buddypress' ); ?></span>
						</label>
						<label for="date-format-custom-value" class="screen-reader-text"><?php esc_html_e( 'Enter custom time format', 'buddypress' ); ?></label>
						<input type="text" name="field-settings[date_format_custom]" id="date-format-custom-value" class="date-format-custom-value" value="<?php echo esc_attr( $settings['date_format_custom'] ); ?>" aria-describedby="date-format-custom-example" /> <span class="screen-reader-text"><?php esc_html_e( 'Example:', 'buddypress' ); ?></span><span class="date-format-custom-example" id="date-format-custom-sample"><?php if ( $settings['date_format_custom'] ) : ?><?php echo esc_html( date( $settings['date_format_custom'] ) ); endif; ?></span><span class="spinner" id="date-format-custom-spinner" aria-hidden="true"></span>

						<p><a href="https://codex.wordpress.org/Formatting_Date_and_Time"><?php esc_html_e( 'Documentation on date and time formatting', 'buddypress' ); ?></a></p>
					</div>

				</fieldset>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'Range', 'buddypress' ); ?>
			</th>

			<td>
				<fieldset class="bp-range-types">
					<legend class="screen-reader-text">
						<?php esc_html_e( 'Range', 'buddypress' ); ?>
					</legend>

					<div class="bp-date-format-option">
						<div class="bp-date-range-type-label">
							<label for="range_type_absolute">
								<input type="radio" name="field-settings[range_type]" id="range_type_absolute" value="absolute" <?php checked( 'absolute', $settings['range_type'] ); ?> />
								<?php esc_html_e( 'Absolute', 'buddypress' ); ?>
							</label>
						</div>

						<div class="bp-date-range-type-values">
							<label for="field-settings[range_absolute_start]" aria-label="Year"><?php esc_html_e( 'Start:', 'buddypress' ); ?></label>
							<?php printf( '<input class="date-range-numeric" type="text" name="field-settings[range_absolute_start]" id="field-settings[range_absolute_start]" value="%s" />', esc_attr( $settings['range_absolute_start'] ) ); ?>
							<label for="field-settings[range_absolute_end]" aria-label="Year"><?php esc_html_e( 'End:', 'buddypress' ); ?></label>
							<?php printf( '<input class="date-range-numeric" type="text" name="field-settings[range_absolute_end]" id="field-settings[range_absolute_end]" value="%s" />', esc_attr( $settings['range_absolute_end'] ) ); ?>
						</div>
					</div>

					<div class="bp-date-format-option">
						<div class="bp-date-range-type-label">
							<label for="range_type_relative">
								<input type="radio" name="field-settings[range_type]" id="range_type_relative" value="relative" <?php checked( 'relative', $settings['range_type'] ); ?> />
								<?php esc_html_e( 'Relative', 'buddypress' ); ?>
							</label>
						</div>

						<div class="bp-date-range-type-values">
							<label for="field-settings[range_relative_start]"><?php esc_html_e( 'Start:', 'buddypress' ); ?></label>
							<?php printf( '<input type="text" class="date-range-numeric" name="field-settings[range_relative_start]" id="field-settings[range_relative_start]" value="%s" />',
								esc_attr( abs( $settings['range_relative_start'] ) )
								);
							?>

							<label class="screen-reader-text" for="field-settings[range_relative_start_type]"><?php esc_html_e( 'Select range', 'buddypress' ); ?></label>
							<?php printf( '<select name="field-settings[range_relative_start_type]" id="field-settings[range_relative_start_type]"><option value="past" %s>%s</option><option value="future" %s>%s</option></select>',
								selected( true, $settings['range_relative_start'] <= 0, false ),
								esc_attr__( 'years ago', 'buddypress' ),
								selected( true, $settings['range_relative_start'] > 0, false ),
								esc_attr__( 'years from now', 'buddypress' )
								);
							?>

							<label for="field-settings[range_relative_end]"><?php esc_html_e( 'End:', 'buddypress' ); ?></label>
							<?php printf( '<input type="text" class="date-range-numeric" name="field-settings[range_relative_end]" id="field-settings[range_relative_end]" value="%s" />',
								esc_attr( abs( $settings['range_relative_end'] ) )
								);
							?>
							<label class="screen-reader-text" for="field-settings[range_relative_end_type]"><?php esc_html_e( 'Select range', 'buddypress' ); ?></label>
							<?php printf( '<select name="field-settings[range_relative_end_type]" id="field-settings[range_relative_end_type]"><option value="past" %s>%s</option><option value="future" %s>%s</option></select>',
									selected( true, $settings['range_relative_end'] <= 0, false ),
									esc_attr__( 'years ago', 'buddypress' ),
									selected( true, $settings['range_relative_end'] > 0, false ),
									esc_attr__( 'years from now', 'buddypress' )
								);
							?>
						</div>
					</div>

				</fieldset>
			</td>
		</tr>
	</table>
</div>
		<?php
	}

	/**
	 * Format Date values for display.
	 *
	 * @since 2.1.0
	 * @since 2.4.0 Added the `$field_id` parameter.
	 *
	 * @param string     $field_value The date value, as saved in the database. Typically, this is a MySQL-formatted
	 *                                date string (Y-m-d H:i:s).
	 * @param string|int $field_id    Optional. ID of the field.
	 * @return string Date formatted by bp_format_time().
	 */
	public static function display_filter( $field_value, $field_id = '' ) {

		// If Unix timestamp.
		if ( ! is_numeric( $field_value ) ) {
			$field_value = strtotime( $field_value );
		}

		$settings = self::get_field_settings( $field_id );

		switch ( $settings['date_format'] ) {
			case 'elapsed' :
				$formatted = bp_core_time_since( $field_value );
			break;

			case 'custom' :
				$formatted = date( $settings['date_format_custom'], $field_value );
			break;

			default :
				$formatted = date( $settings['date_format'], $field_value );
			break;
		}

		return $formatted;
	}

	/**
	 * Gets the default date formats available when configuring a Date field.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	public function get_date_formats() {
		$date_formats = array_unique( apply_filters( 'date_formats', array( __( 'F j, Y', 'buddypress' ), 'Y-m-d', 'm/d/Y', 'd/m/Y' ) ) );


		/**
		 * Filters the available date formats for XProfile date fields.
		 *
		 * @since 2.7.0
		 *
		 * @param array $date_formats
		 */
		return apply_filters( 'bp_xprofile_date_field_date_formats', $date_formats );
	}
}
