<?php
/**
 * BuddyPress XProfile Classes.
 *
 * @package BuddyPress
 * @subpackage XProfileClasses
 * @since 8.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WordPress xProfile regular field type.
 *
 * @since 8.0.0
 */
class BP_XProfile_Field_Type_WordPress_Textbox extends BP_XProfile_Field_Type_WordPress {

	/**
	 * Constructor for the WordPress regular field type.
	 *
	 * @since 8.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category            = _x( 'WordPress Fields', 'xprofile field type category', 'buddypress' );
		$this->name                = _x( 'Text field', 'xprofile field type', 'buddypress' );
		$this->accepts_null_value  = true;
		$this->do_settings_section = true;
		$this->wp_user_key         = '';

		$this->set_format( '/^.*$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_WordPress_Textbox class.
		 *
		 * @since 8.0.0
		 *
		 * @param BP_XProfile_Field_Type_WordPress_Textbox $field_type Current instance of the field type class.
		 */
		do_action( 'bp_xprofile_field_type_wordpress_textbox', $this );

		/*
		 * As we are using an xProfile field meta to store the WordPress field meta key we need
		 * to make sure $this->wp_user_key is set before once the field has been populated.
		 */
		add_action( 'bp_xprofile_field', array( $this, 'set_wp_user_key' ), 10 );
	}

	/**
	 * Sets the WordPress field wp_user_key property before saving the xProfile field.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_XProfile_Field $field Field object.
	 */
	public function set_wp_user_key( $field ) {
		if ( ! $this->wp_user_key && 'wp-textbox' === $field->type ) {
			$this->wp_user_key = self::get_field_settings( $field->id );
		}
	}

	/**
	 * Gets the WordPress field value during an xProfile fields loop.
	 *
	 * This function is used inside `BP_XProfile_ProfileData::get_data_for_user()`
	 * to include the WordPress field value into the xProfile fields loop.
	 *
	 * @since 8.0.0
	 *
	 * @param integer $user_id The user ID.
	 * @param integer $field_id The xProfile field ID.
	 * @return array An array containing the metadata `id`, `value` and `table_name`.
	 */
	public function get_field_value( $user_id, $field_id = 0 ) {
		if ( ! $this->wp_user_key ) {
			$this->wp_user_key = self::get_field_settings( $field_id );
		}

		return parent::get_field_value( $user_id, $field_id );
	}

	/**
	 * Sanitize the user field before saving it to db.
	 *
	 * @since 8.0.0
	 *
	 * @param string $value The user field value.
	 * @return string The sanitized field value.
	 */
	public function sanitize_for_db( $value ) {
		if ( ! $value ) {
			return '';
		}

		if ( 'user_url' === $this->wp_user_key ) {
			return esc_url_raw( $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize the user field before displaying it as an attribute.
	 *
	 * @since 8.0.0
	 *
	 * @param string $value The user field value.
	 * @return string The sanitized field value.
	 */
	public function sanitize_for_output( $value, $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = bp_displayed_user_id();
		}

		return sanitize_user_field( $this->wp_user_key, $value, $user_id, 'attribute' );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 8.0.0
	 *
	 * @param array $raw_properties Optional key/value array of
	 *                              {@link http://dev.w3.org/html5/markup/textarea.html permitted attributes}
	 *                              that you want to add.
	 */
	public function edit_field_html( array $raw_properties = array() ) {
		/*
		 * User_id is a special optional parameter that certain other fields
		 * types pass to {@link bp_the_profile_field_options()}.
		 */
		if ( ! is_admin() && isset( $raw_properties['user_id'] ) ) {
			unset( $raw_properties['user_id'] );
		}

		$user_id = bp_displayed_user_id();
		if ( isset( $raw_properties['user_id'] ) && $raw_properties['user_id'] ) {
			$user_id = (int) $raw_properties['user_id'];
			unset( $raw_properties['user_id'] );
		}

		if ( ! $this->wp_user_key ) {
			$this->wp_user_key = self::get_field_settings( bp_get_the_profile_field_id() );
		}

		$field_value = '';
		if ( 'user_url' === $this->wp_user_key ) {
			if ( bp_displayed_user_id() ) {
				$field_value = bp_get_displayed_user()->userdata->{$this->wp_user_key};
			} elseif ( $user_id ) {
				$user = get_user_by( 'id', $user_id );
				$field_value = $user->{$this->wp_user_key};
			}
		} else {
			$field_value = bp_get_user_meta( $user_id, $this->wp_user_key, true );
		}

		$r = bp_parse_args(
			$raw_properties,
			array(
				'type'  => 'text',
				'value' => $this->sanitize_for_output( $field_value, $user_id ),
			)
		);
		?>

		<legend id="<?php bp_the_profile_field_input_name(); ?>-1">
			<?php bp_the_profile_field_name(); ?>
			<?php bp_the_profile_field_required_label(); ?>
		</legend>

		<?php

		/** This action is documented in bp-xprofile/bp-xprofile-classes */
		do_action( bp_get_the_profile_field_errors_action() ); ?>

		<input <?php $this->output_edit_field_html_elements( $r ); ?> aria-labelledby="<?php bp_the_profile_field_input_name(); ?>-1" aria-describedby="<?php bp_the_profile_field_input_name(); ?>-3">

		<?php if ( bp_get_the_profile_field_description() ) : ?>
			<p class="description" id="<?php bp_the_profile_field_input_name(); ?>-3"><?php bp_the_profile_field_description(); ?></p>
		<?php endif; ?>

		<?php
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 8.0.0
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		$r = bp_parse_args(
			$raw_properties,
			array(
				'type' => 'text',
			)
		);
		?>

		<label for="<?php bp_the_profile_field_input_name(); ?>" class="screen-reader-text"><?php
			/* translators: accessibility text */
			esc_html_e( 'WordPress field', 'buddypress' );
		?></label>
		<input <?php $this->output_edit_field_html_elements( $r ); ?>>

		<?php
	}

	/**
	 * Get settings for a given WordPress field.
	 *
	 * @since 8.0.0
	 *
	 * @param int $field_id ID of the field.
	 * @return string The meta_key used for this field.
	 */
	public static function get_field_settings( $field_id ) {
		$wp_user_key = bp_xprofile_get_meta( $field_id, 'field', 'wp_user_key', true );

		return sanitize_key( $wp_user_key );
	}

	/**
	 * Save settings from the field edit screen in the Dashboard.
	 *
	 * @since 8.0.0
	 *
	 * @param int   $field_id ID of the field.
	 * @param array $settings Array of settings.
	 * @return bool True on success.
	 */
	public function admin_save_settings( $field_id, $settings ) {
		$existing_setting = self::get_field_settings( $field_id );
		$setting = '';

		if ( isset( $settings['wp_user_key'] ) ) {
			$setting = sanitize_key( $settings['wp_user_key'] );
		}

		if ( $setting && $setting !== $existing_setting ) {
			bp_xprofile_update_meta( $field_id, 'field', 'wp_user_key', $setting );
		}

		return true;
	}

	/**
	 * This method usually outputs HTML for this field type's children options on the wp-admin Profile Fields
	 * "Add Field" and "Edit Field" screens, but for this field type, we don't want it, so it's stubbed out.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string            $control_type  Optional. HTML input type used to render the
	 *                                         current field's child options.
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		$type = array_search( get_class( $this ), bp_xprofile_get_field_types() );

		if ( false === $type ) {
			return;
		}

		$style = 'margin-top: 15px;';
		if ( $current_field->type !== $type ) {
			$style .= ' display: none;';
		};

		$setting = self::get_field_settings( $current_field->id );

		$wp_labels = array_merge(
			array(
				'first_name' => _x( 'First Name', 'xpofile wp-textbox field type label', 'buddypress' ),
				'last_name'  => _x( 'Last Name', 'xpofile wp-textbox field type label', 'buddypress' ),
				'user_url'   => _x( 'Website', 'xpofile wp-textbox field type label', 'buddypress' ),
			),
			wp_get_user_contact_methods()
		);
		?>
		<div id="<?php echo esc_attr( $type ); ?>" class="postbox bp-options-box" style="<?php echo esc_attr( $style ); ?>">
			<h3><?php esc_html_e( 'Select the information you want to use for your WordPress field.', 'buddypress' ); ?></h3>

			<div class="inside" aria-live="polite" aria-atomic="true" aria-relevant="all">
				<div class="bp-option">
					<ul>
						<?php
						foreach ( $this->supported_keys as $key ) {
							if ( 'description' === $key || ! isset( $wp_labels[ $key ] ) ) {
								continue;
							}

							printf(
								'<li>
									<label for="wp-textbox-wp_user_key-%1$s">
										<input type="radio" id="wp-textbox-wp_user_key-%1$s" name="field-settings[wp_user_key]" value="%1$s" %2$s/>
										%3$s
									</label>
								</li>',
								esc_attr( $key ),
								checked( $key, $setting, false ),
								esc_html( $wp_labels[ $key ] )
							);
						}
						?>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Format WordPress field values for display.
	 *
	 * @since 8.0.0
	 *
	 * @param string     $field_value The field value, as saved in the database.
	 * @param string|int $field_id    Optional. ID of the field.
	 * @return string The sanitized WordPress field.
	 */
	public static function display_filter( $field_value, $field_id = '' ) {
		$wp_user_key = self::get_field_settings( $field_id );

		if ( ! $wp_user_key ) {
			return '';
		}

		if ( 'user_url' === $wp_user_key ) {
			$sanitized_website = sanitize_user_field( $wp_user_key, $field_value, bp_displayed_user_id(), 'attribute' );
			return sprintf( '<a href="%1$s" rel="nofollow">%1$s</a>', $sanitized_website );
		}

		return sanitize_user_field( $wp_user_key, $field_value, bp_displayed_user_id(), 'display' );
	}
}
