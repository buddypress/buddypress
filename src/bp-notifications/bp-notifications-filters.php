<?php
/**
 * Filters related to the Notifications component.
 *
 * @package BuddyPress
 * @subpackage Notifications
 * @since 4.0.0
 */

// Format numerical output.
add_filter( 'bp_notifications_get_total_notification_count', 'bp_core_number_format' );

// Privacy data export.
add_filter( 'wp_privacy_personal_data_exporters', 'bp_register_notifications_personal_data_exporter' );

/**
 * Register Notifications personal data exporter.
 *
 * @since 4.0.0
 * @since 5.0.0 adds an `exporter_bp_friendly_name` param to exporters.
 *
 * @param array $exporters  An array of personal data exporters.
 * @return array An array of personal data exporters.
 */
function bp_register_notifications_personal_data_exporter( $exporters ) {
	$exporters['buddypress-notifications'] = array(
		'exporter_friendly_name'    => __( 'BuddyPress Notifications Data', 'buddypress' ),
		'callback'                  => 'bp_notifications_personal_data_exporter',
		'exporter_bp_friendly_name' => _x( 'Notifications Data', 'BuddyPress Notifications data exporter friendly name', 'buddypress' ),
	);

	return $exporters;
}
