<?php
/**
 * Filters related to the Activity component.
 *
 * @package BuddyPress
 * @subpackage Notifications
 * @since 4.0.0
 */

// Privacy data export.
add_filter( 'wp_privacy_personal_data_exporters', 'bp_register_notifications_personal_data_exporter' );

/**
 * Register Notifications personal data exporter.
 *
 * @since 4.0.0
 *
 * @param array $exporters  An array of personal data exporters.
 * @return array An array of personal data exporters.
 */
function bp_register_notifications_personal_data_exporter( $exporters ) {
	$exporters['buddypress-notifications'] = array(
		'exporter_friendly_name' => __( 'BuddyPress Notifications Data', 'buddypress' ),
		'callback'               => 'bp_notifications_personal_data_exporter',
	);

	return $exporters;
}
