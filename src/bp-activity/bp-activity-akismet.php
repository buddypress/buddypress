<?php
/**
 * Akismet support for BuddyPress' Activity Stream.
 *
 * @package BuddyPress
 * @subpackage ActivityAkismet
 * @since 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! buddypress()->do_autoload ) {
	require dirname( __FILE__ ) . '/classes/class-bp-akismet.php';
}

/**
 * Loads Akismet filtering for activity.
 *
 * @since 1.6.0
 * @since 2.3.0 We only support Akismet 3+.
 */
function bp_activity_setup_akismet() {
	/**
	 * Filters if BuddyPress Activity Akismet support has been disabled by another plugin.
	 *
	 * @since 1.6.0
	 *
	 * @param bool $value Return value of bp_is_akismet_active boolean function.
	 */
	if ( ! apply_filters( 'bp_activity_use_akismet', bp_is_akismet_active() ) ) {
		return;
	}

	// Instantiate Akismet for BuddyPress.
	buddypress()->activity->akismet = new BP_Akismet();
}
add_action( 'bp_activity_setup_globals', 'bp_activity_setup_akismet' );

/**
 * Delete old spam activity meta data.
 *
 * This is done as a clean-up mechanism, as _bp_akismet_submission meta can
 * grow to be quite large.
 *
 * @since 1.6.0
 *
 * @global wpdb $wpdb WordPress database object.
 */
function bp_activity_akismet_delete_old_metadata() {
	global $wpdb;

	$bp = buddypress();

	/**
	 * Filters the threshold for how many days old Akismet metadata needs to be before being automatically deleted.
	 *
	 * @since 1.6.0
	 *
	 * @param integer 15 How many days old metadata needs to be.
	 */
	$interval = apply_filters( 'bp_activity_akismet_delete_meta_interval', 15 );

	// Enforce a minimum of 1 day.
	$interval = max( 1, absint( $interval ) );

	// _bp_akismet_submission meta values are large, so expire them after $interval days regardless of the activity status
	$sql          = $wpdb->prepare( "SELECT a.id FROM {$bp->activity->table_name} a LEFT JOIN {$bp->activity->table_name_meta} m ON a.id = m.activity_id WHERE m.meta_key = %s AND DATE_SUB(%s, INTERVAL {$interval} DAY) > a.date_recorded LIMIT 10000", '_bp_akismet_submission', current_time( 'mysql', 1 ) );
	$activity_ids = $wpdb->get_col( $sql );

	if ( ! empty( $activity_ids ) ) {
		foreach ( $activity_ids as $activity_id )
			bp_activity_delete_meta( $activity_id, '_bp_akismet_submission' );
	}
}
add_action( 'bp_activity_akismet_delete_old_metadata', 'bp_activity_akismet_delete_old_metadata' );
