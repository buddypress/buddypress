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
 * Checkbox Acceptance xProfile field type.
 *
 * @since 8.0.0
 */
class BP_XProfile_Field_Type_Checkbox_Acceptance extends BP_XProfile_Field_Type {

	/**
	 * Checkbox Acceptance field's visibility setting.
	 *
	 * Defaults to 'adminsonly'. This property enforces Field's default visibility.
	 *
	 * @since 8.0.0
	 *
	 * @return string The Checkbox Acceptance field's visibility setting.
	 */
	public $visibility = 'adminsonly';

	/**
	 * Supported features for the Checkbox Acceptance field type.
	 *
	 * @since 8.0.0
	 * @var bool[] The WordPress field supported features.
	 */
	public static $supported_features = array(
		'switch_fieldtype'        => false,
		'required'                => true,
		'do_autolink'             => false,
		'allow_custom_visibility' => false,
		'member_types'            => false,
	);

	/**
	 * Constructor for the Checkbox Acceptance field type.
	 *
	 * @since 8.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name     = _x( 'Checkbox Acceptance', 'xprofile field type', 'buddypress' );
		$this->category = _x( 'Single Fields', 'xprofile field type category', 'buddypress' );

		$this->supports_options    = false;
		$this->do_settings_section = true;
		$this->accepts_null_value  = false;

		$this->set_format( '/^.+$/', 'replace' );

		/**
		 * Fires inside __construct() method for bp_xprofile_field_type_checkbox_acceptance class.
		 *
		 * @since 8.0.0
		 *
		 * @param BP_XProfile_Field_Type_Checkbox_Acceptance $this Current instance of the Checkbox Acceptance field type.
		 */
		do_action( 'bp_xprofile_field_type_checkbox_acceptance', $this );

		// Make sure it's not possible to edit an accepted Checkbox Acceptance field.
		add_filter( 'bp_xprofile_set_field_data_pre_validate', array( $this, 'enforce_field_value' ), 10, 2 );
	}


	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since 8.0.0
	 *
	 * @param array $raw_properties Optional key/value array of
	 * {@link http://dev.w3.org/html5/markup/textarea.html permitted attributes}
	 *  that you want to add.
	 */
	public function edit_field_html( array $raw_properties = array() ) {
		$user_id   = bp_displayed_user_id();
		$required  = false;
		$default_r = array();

		if ( isset( $raw_properties['user_id'] ) ) {
			$user_id = (int) $raw_properties['user_id'];
			unset( $raw_properties['user_id'] );
		}

		if ( bp_get_the_profile_field_is_required() ) {
			$default_r['required'] = 'required'; // HTML5 required attribute.
			$required              = true;
		}

		$r = bp_parse_args(
			$raw_properties,
			$default_r,
			'checkbox_acceptance'
		);
		?>
		<legend>
			<?php bp_the_profile_field_name(); ?>
			<?php bp_the_profile_field_required_label(); ?>
		</legend>

		<?php
		/** This action is documented in bp-xprofile/bp-xprofile-classes */
		do_action( bp_get_the_profile_field_errors_action() );

		$r['user_id'] = $user_id;
		bp_the_profile_field_options( $r );
		?>

		<?php if ( bp_get_the_profile_field_description() ) : ?>
			<p class="description" tabindex="0"><?php bp_the_profile_field_description(); ?></p>
		<?php endif;
	}

	/**
	 * Field html for Admin-> User->Profile Fields screen.
	 *
	 * @since 8.0.0
	 *
	 * @param array $raw_properties properties.
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		$page_id   = bp_xprofile_get_meta( bp_get_the_profile_field_id(), 'field', 'bp_xprofile_checkbox_acceptance_page', true );
		$page      = null;
		$default_r = array( 'type' => 'checkbox' );

		if ( bp_get_the_profile_field_is_required() ) {
			$default_r['required'] = 'required'; // HTML5 required attribute.
		}

		$r = bp_parse_args(
			$raw_properties,
			$default_r,
			'checkbox_acceptance'
		);

		if ( $page_id ) {
			$page = get_post( $page_id );
		}
		?>

		<?php if ( $page instanceof WP_Post ) : ?>
			<label for="<?php bp_the_profile_field_input_name(); ?>">
				<input <?php echo $this->get_edit_field_html_elements( $r ); ?>>
				<?php
				printf(
					/* translators: %s: link to the page the user needs to accept the terms of. */
					esc_html__( 'I agree to %s.', 'buddypress' ),
					'<a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( get_the_title( $page ) ) . '</a>'
				);
				?>
			</label>
		<?php endif;
	}

	/**
	 * Admin new field screen.
	 *
	 * @since 8.0.0
	 *
	 * @param BP_XProfile_Field $current_field Profile field object.
	 * @param string            $control_type  Control type.
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		$type = array_search( get_class( $this ), bp_xprofile_get_field_types() );

		if ( false === $type ) {
			return;
		}

		$class   = $current_field->type != $type ? 'display: none;' : '';
		$page_id = bp_xprofile_get_meta( $current_field->id, 'field', 'bp_xprofile_checkbox_acceptance_page', true );
		?>

		<div id="<?php echo esc_attr( $type ); ?>" class="postbox bp-options-box" style="<?php echo esc_attr( $class ); ?> margin-top: 15px;">
			<h3><?php esc_html_e( 'Select the page the user needs to accept the terms of:', 'buddypress' ); ?></h3>
			<div class="inside">
				<p>
					<?php
					echo wp_dropdown_pages(
						array(
							'name'             => 'bp_xprofile_checkbox_acceptance_page',
							'echo'             => false,
							'show_option_none' => __( '&mdash; Select &mdash;', 'buddypress' ),
							'selected'         => $page_id ? $page_id : false,
						)
					);

					$page = null;
					if ( $page_id ) {
						$page = get_post( $page_id );
					}
					?>

					<?php if ( $page instanceof WP_Post ) : ?>

						<a href="<?php echo esc_url( get_permalink( $page ) ); ?>" class="button-secondary" target="_bp">
							<?php esc_html_e( 'View', 'buddypress' ); ?> <span class="dashicons dashicons-external" aria-hidden="true" style="vertical-align: text-bottom;"></span>
							<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'buddypress' ); ?></span>
						</a>

					<?php endif; ?>
				</p>
			</div>
		</div>
		<?php
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
		if ( isset( $_POST['bp_xprofile_checkbox_acceptance_page'] ) ) {
			bp_xprofile_update_meta( $field_id, 'field', 'bp_xprofile_checkbox_acceptance_page', absint( wp_unslash( $_POST['bp_xprofile_checkbox_acceptance_page'] ) ) );
		}

		return true;
	}

	/**
	 * Profile edit/register options html.
	 *
	 * @since 8.0.0
	 *
	 * @param array $args args.
	 */
	public function edit_field_options_html( array $args = array() ) {
		$field_id            = (int) $this->field_obj->id;
		$params              = bp_parse_args(
			$args,
			array(
				'user_id' => bp_displayed_user_id(),
			)
		);
		$checkbox_acceptance = (int) maybe_unserialize( \BP_XProfile_ProfileData::get_value_byid( $field_id, $params['user_id'] ) );

		if ( ! empty( $_POST[ 'field_' . $field_id ] ) ) {
			$new_checkbox_acceptance = (int) wp_unslash( $_POST[ 'field_' . $field_id ] );

			if ( $checkbox_acceptance !== $new_checkbox_acceptance ) {
				$checkbox_acceptance = $new_checkbox_acceptance;
			}
		}

		$r = array(
			'type'     => 'checkbox',
			'name'     => bp_get_the_profile_field_input_name(),
			'id'       => bp_get_the_profile_field_input_name(),
			'value'    => 1,
			'class'    => 'checkbox-acceptance',
		);

		if ( bp_get_the_profile_field_is_required() ) {
			$r['required'] = 'required'; // HTML5 required attribute.
		}

		if ( 1 === $checkbox_acceptance ) {
			$r['checked']  = 'checked';
			$r['readonly'] = 'readonly';
			$r['onclick']  = 'return false;';
		}

		$page_id = bp_xprofile_get_meta( $field_id, 'field', 'bp_xprofile_checkbox_acceptance_page', true );
		$page    = null;
		$html    = '';

		if ( $page_id ) {
			$page = get_post( $page_id );
		}

		if ( $page instanceof WP_Post ) {
			$html = sprintf(
				'<div class="bp-xprofile-checkbox-acceptance-field"><input %1$s />%2$s</div>',
				$this->get_edit_field_html_elements( $r ),
				sprintf(
					/* translators: %s: link to the page the user needs to accept the terms of. */
					esc_html__( 'I agree to %s.', 'buddypress' ),
					'<a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( get_the_title( $page ) ) . '</a>'
				)
			);
		}

		/**
		 * Filter here to edit the HTML output.
		 *
		 * @since 8.0.0
		 *
		 * @param string $html                The HTML output.
		 * @param int    $field_id            The field ID.
		 * @param array  $r                   The edit field HTML elements data.
		 * @param int    $checkbox_acceptance The field value.
		 */
		echo apply_filters( 'bp_get_the_profile_field_checkbox_acceptance', $html, $field_id, $checkbox_acceptance );
	}

	/**
	 * Enforces the field value if it has been already accepted.
	 *
	 * As it's always possible to edit HTML source and remove the `readonly="readonly"` attribute
	 * of the checkbox, we may need to enforce the field value.
	 *
	 * @since 8.0.0
	 *
	 * @param mixed             $value Value passed to xprofile_set_field_data().
	 * @param BP_XProfile_Field $field Field object.
	 * @return mixed The field value.
	 */
	public function enforce_field_value( $value, BP_XProfile_Field $field ) {
		if ( 'checkbox_acceptance' === $field->type && 1 !== (int) $value && 1 === (int) $field->data->value ) {
			$value = 1;
		}

		return $value;
	}

	/**
	 * Check if field is valid?
	 *
	 * @since 8.0.0
	 *
	 * @param string|int $values value.
	 * @return bool
	 */
	public function is_valid( $value ) {
		if ( empty( $value ) || 1 === (int) $value ) {
			return true;
		}

		return false;
	}

	/**
	 * Modify the appearance of value.
	 *
	 * @since 8.0.0
	 *
	 * @param string $field_value Original value of field.
	 * @param int    $field_id field id.
	 *
	 * @return string   Value formatted
	 */
	public static function display_filter( $field_value, $field_id = 0 ) {
		$page_id = bp_xprofile_get_meta( $field_id, 'field', 'bp_xprofile_checkbox_acceptance_page', true );
		$page    = null;
		$html    = esc_html__( 'No', 'buddypress' );

		/* translators: %s: link to the page the user needs to accept the terms of. */
		$acceptance_text = esc_html__( 'I did not agree to %s', 'buddypress' );

		if ( $page_id ) {
			$page = get_post( $page_id );
		}

		if ( ! empty( $field_value ) ) {
			$html = esc_html__( 'Yes', 'buddypress' );

			/* translators: %s: link to the page the user needs to accept the terms of. */
			$acceptance_text = esc_html__( 'I agreed to %s.', 'buddypress' );
		}

		if ( $page instanceof WP_Post ) {
			$html = sprintf(
				$acceptance_text,
				'<a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( get_the_title( $page ) ) . '</a>'
			);
		}

		return $html;
	}
}
