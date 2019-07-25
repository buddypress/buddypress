<?php

/**
 * Filters related to the Settings component.
 *
 * @since 4.0.0
 */

// Personal data export.
add_filter( 'wp_privacy_personal_data_exporters', 'bp_settings_register_personal_data_exporter' );

/**
 * Registers Settings personal data exporter.
 *
 * @since 4.0.0
 * @since 5.0.0 adds an `exporter_bp_friendly_name` param to exporters.
 *
 * @param array $exporters  An array of personal data exporters.
 * @return array An array of personal data exporters.
 */
function bp_settings_register_personal_data_exporter( $exporters ) {
	$exporters['buddypress-settings'] = array(
		'exporter_friendly_name'    => __( 'BuddyPress Settings Data', 'buddypress' ),
		'callback'                  => 'bp_settings_personal_data_exporter',
		'exporter_bp_friendly_name' => _x( 'Personal settings', 'BuddyPress Settings Data data exporter friendly name', 'buddypress' ),
	);

	return $exporters;
}
