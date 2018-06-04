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
 *
 * @param array $exporters  An array of personal data exporters.
 * @return array An array of personal data exporters.
 */
function bp_settings_register_personal_data_exporter( $exporters ) {
	$exporters['buddypress-settings'] = array(
		'exporter_friendly_name' => __( 'BuddyPress Settings Data', 'buddypress' ),
		'callback'               => 'bp_settings_personal_data_exporter',
	);

	return $exporters;
}
