<?php
/**
 * BuddyPress XProfile CSS and JS.
 *
 * @package BuddyPress
 * @subpackage XProfileScripts
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the CSS for XProfile admin styling.
 *
 * @since 1.1.0
 */
function xprofile_add_admin_css() {
	if ( ! empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {
		$min = bp_core_get_minified_asset_suffix();

		wp_enqueue_style( 'xprofile-admin-css', buddypress()->plugin_url . "bp-xprofile/admin/css/admin{$min}.css", array(), bp_get_version() );

		wp_style_add_data( 'xprofile-admin-css', 'rtl', 'replace' );
		if ( $min ) {
			wp_style_add_data( 'xprofile-admin-css', 'suffix', $min );
		}
	}
}
add_action( 'bp_admin_enqueue_scripts', 'xprofile_add_admin_css' );

/**
 * Enqueue the jQuery libraries for handling drag/drop/sort.
 *
 * @since 1.5.0
 */
function xprofile_add_admin_js() {
	if ( ! empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {
		wp_enqueue_script( 'jquery-ui-core'      );
		wp_enqueue_script( 'jquery-ui-tabs'      );
		wp_enqueue_script( 'jquery-ui-mouse'     );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-sortable'  );

		$min = bp_core_get_minified_asset_suffix();
		wp_enqueue_script( 'xprofile-admin-js', buddypress()->plugin_url . "bp-xprofile/admin/js/admin{$min}.js", array( 'jquery', 'jquery-ui-sortable' ), bp_get_version() );

		// Localize strings.
		// supports_options_field_types is a dynamic list of field
		// types that support options, for use in showing/hiding the
		// "please enter options for this field" section.
		$strings = array(
			'do_settings_section_field_types'      => array(),
			'do_autolink'                          => '',
			'hide_do_autolink_metabox'             => array(),
			'hide_allow_custom_visibility_metabox' => array(),
			'hide_required_metabox'                => array(),
			'hide_member_types_metabox'            => array(),
			'hide_signup_position_metabox'         => array(),
			'text'                                 => array(
				'defaultValue' => __( 'Default Value', 'buddypress' ),
				'deleteLabel'  => __( 'Delete', 'buddypress' ),
			),
			'signup_info'                          => _x( '(Sign-up)', 'xProfile Group Admin Screen Signup field information', 'buddypress' ),
		);

		foreach ( bp_xprofile_get_field_types() as $field_type => $field_type_class ) {
			$field = new $field_type_class();
			if ( $field->do_settings_section() ) {
				$strings['do_settings_section_field_types'][] = $field_type;
			}

			if ( isset( $field::$supported_features ) && is_array( $field::$supported_features ) ) {
				foreach ( $field::$supported_features as $feature => $support ) {
					if ( isset( $strings[ 'hide_' . $feature . '_metabox' ] ) && ! $support ) {
						$strings[ 'hide_' . $feature . '_metabox' ][] = $field_type;
					}
				}
			}
		}

		// Load 'autolink' setting into JS so that we can provide smart defaults when switching field type.
		if ( ! empty( $_GET['field_id'] ) ) {
			$field_id = intval( $_GET['field_id'] );

			// Pull the raw data from the DB so we can tell whether the admin has saved a value yet.
			$strings['do_autolink'] = bp_xprofile_get_meta( $field_id, 'field', 'do_autolink' );
		}

		wp_localize_script( 'xprofile-admin-js', 'XProfileAdmin', $strings );
	}
}
add_action( 'bp_admin_enqueue_scripts', 'xprofile_add_admin_js', 1 );
