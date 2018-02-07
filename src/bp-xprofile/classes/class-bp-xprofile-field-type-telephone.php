<?php
/**
 * BuddyPress XProfile Classes.
 *
 * @package BuddyPress
 * @subpackage XProfileClasses
 * @since 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Telephone number xprofile field type.
 *
 * @since 3.0.0
 */
class BP_XProfile_Field_Type_Telephone extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the telephone number field type.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Single Fields', 'xprofile field type category', 'buddypress' );
		$this->name     = _x( 'Phone Number', 'xprofile field type', 'buddypress' );

		$this->set_format( '/^.*$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Telephone class.
		 *
		 * @since 3.0.0
		 *
		 * @param BP_XProfile_Field_Type_Telephone $this Current instance of the field type.
		 */
		do_action( 'bp_xprofile_field_type_telephone', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 3.0.0
	 *
	 * @param array $raw_properties Optional key/value array of
	 *                              {@link http://dev.w3.org/html5/markup/input.text.html permitted attributes}
	 *                              that you want to add.
	 */
	public function edit_field_html( array $raw_properties = array() ) {
		/*
		 * User_id is a special optional parameter that certain other fields
		 * types pass to {@link bp_the_profile_field_options()}.
		 */
		if ( isset( $raw_properties['user_id'] ) ) {
			unset( $raw_properties['user_id'] );
		}

		$r = bp_parse_args( $raw_properties, array(
			'type'  => 'tel',
			'value' => bp_get_the_profile_field_edit_value(),
		) ); ?>

		<legend id="<?php bp_the_profile_field_input_name(); ?>-1">
			<?php bp_the_profile_field_name(); ?>
			<?php bp_the_profile_field_required_label(); ?>
		</legend>

		<?php

		/** This action is documented in bp-xprofile/bp-xprofile-classes */
		do_action( bp_get_the_profile_field_errors_action() ); ?>

		<input <?php echo $this->get_edit_field_html_elements( $r ); ?> aria-labelledby="<?php bp_the_profile_field_input_name(); ?>-1" aria-describedby="<?php bp_the_profile_field_input_name(); ?>-3">

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
	 * @since 3.0.0
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		$r = bp_parse_args( $raw_properties, array(
			'type' => 'tel',
		) ); ?>

		<label for="<?php bp_the_profile_field_input_name(); ?>" class="screen-reader-text"><?php
			/* translators: accessibility text */
			esc_html_e( 'Phone Number', 'buddypress' );
		?></label>
		<input <?php echo $this->get_edit_field_html_elements( $r ); ?>>

		<?php
	}

	/**
	 * This method usually outputs HTML for this field type's children options on the wp-admin Profile Fields
	 * "Add Field" and "Edit Field" screens, but for this field type, we don't want it, so it's stubbed out.
	 *
	 * @since 3.0.0
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string            $control_type  Optional. HTML input type used to render the
	 *                                         current field's child options.
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {}

	/**
	 * Format URL values for display.
	 *
	 * @since 3.0.0
	 *
	 * @param string     $field_value The URL value, as saved in the database.
	 * @param string|int $field_id    Optional. ID of the field.
	 *
	 * @return string URL converted to a link.
	 */
	public static function display_filter( $field_value, $field_id = '' ) {
		$url   = wp_strip_all_tags( $field_value );
		$parts = parse_url( $url );

		// Add the tel:// protocol to the field value.
		if ( isset( $parts['scheme'] ) ) {
			if ( strtolower( $parts['scheme'] ) !== 'tel' ) {
				$scheme = preg_quote( $parts['scheme'], '#' );
				$url    = preg_replace( '#^' . $scheme . '#i', 'tel', $url );
			}

			$url_text = preg_replace( '#^tel://#i', '', $url );

		} else {
			$url_text = $url;
			$url      = 'tel://' . $url;
		}

		return sprintf(
			'<a href="%1$s" rel="nofollow">%2$s</a>',
			esc_url( $url, array( 'tel' ) ),
			esc_html( $url_text )
		);
	}
}
