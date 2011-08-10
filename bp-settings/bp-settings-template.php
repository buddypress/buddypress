<?php
/**
 * BuddyPress Settings Template Functions
 *
 * @package BuddyPress
 * @subpackage Settings Template
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Output the settings component slug
 *
 * @package BuddyPress
 * @subpackage Settings Template
 * @since 1.5
 *
 * @uses bp_get_settings_slug()
 */
function bp_settings_slug() {
	echo bp_get_settings_slug();
}
	/**
	 * Return the settings component slug
	 *
	 * @package BuddyPress
	 * @subpackage Settings Template
	 * @since 1.5
	 */
	function bp_get_settings_slug() {
		global $bp;
		return apply_filters( 'bp_get_settings_slug', $bp->settings->slug );
	}

/**
 * Output the settings component root slug
 *
 * @package BuddyPress
 * @subpackage Settings Template
 * @since 1.5
 *
 * @uses bp_get_settings_root_slug()
 */
function bp_settings_root_slug() {
	echo bp_get_settings_root_slug();
}
	/**
	 * Return the settings component root slug
	 *
	 * @package BuddyPress
	 * @subpackage Settings Template
	 * @since 1.5
	 */
	function bp_get_settings_root_slug() {
		global $bp;
		return apply_filters( 'bp_get_settings_root_slug', $bp->settings->root_slug );
	}

?>