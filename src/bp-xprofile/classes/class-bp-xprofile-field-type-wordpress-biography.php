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
 * WordPress Biography xProfile field type.
 *
 * @since 8.0.0
 */
class BP_XProfile_Field_Type_WordPress_Biography extends BP_XProfile_Field_Type_WordPress {

	/**
	 * Constructor for the WordPress biography field type.
	 *
	 * @since 8.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category           = _x( 'WordPress Fields', 'xprofile field type category', 'buddypress' );
		$this->name               = _x( 'Biography', 'xprofile field type', 'buddypress' );
		$this->accepts_null_value = true;
		$this->wp_user_key        = 'description';

		$this->set_format( '/^.*$/m', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_WordPress_Biography class.
		 *
		 * @since 8.0.0
		 *
		 * @param BP_XProfile_Field_Type_WordPress_Biography $field_type Current instance of the field type class.
		 */
		do_action( 'bp_xprofile_field_type_wordpress_biography', $this );
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

		return trim( $value );
	}

	/**
	 * Sanitize the user field before displaying it as an attribute.
	 *
	 * @since 8.0.0
	 *
	 * @param string $value The user field value.
	 * @param integer $user_id The user ID.
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
		?>

		<label for="<?php bp_the_profile_field_input_name(); ?>">
			<?php bp_the_profile_field_name(); ?>
			<?php bp_the_profile_field_required_label(); ?>
		</label>

		<?php
		/** This action is documented in bp-xprofile/bp-xprofile-classes */
		do_action( bp_get_the_profile_field_errors_action() );

		$r = bp_parse_args(
			$raw_properties,
			array(
				'cols' => 40,
				'rows' => 5,
			)
		);

		// phpcs:disable WordPress.Security.EscapeOutput
		?>

		<textarea <?php $this->output_edit_field_html_elements( $r ); ?>><?php
			echo $this->sanitize_for_output( bp_get_user_meta( $user_id, $this->wp_user_key, true ), $user_id );
		?></textarea>

		<?php
		// phpcs:enable
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
				'cols' => 40,
				'rows' => 5,
			)
		);
		?>

		<textarea <?php $this->output_edit_field_html_elements( $r ); ?>></textarea>

		<?php
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
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {}

	/**
	 * Format WordPress Biography for display.
	 *
	 * @since 8.0.0
	 *
	 * @param string     $field_value The field value, as saved in the database.
	 * @param string|int $field_id    Optional. ID of the field.
	 * @return string The sanitized WordPress field.
	 */
	public static function display_filter( $field_value, $field_id = '' ) {
		return sanitize_user_field( 'description', $field_value, bp_displayed_user_id(), 'display' );
	}
}
