<?php
/**
 * Groups: Single group "Manage > Cover Image" screen handler
 *
 * @package BuddyPress
 * @subpackage GroupsScreens
 * @since 3.0.0
 */

/**
 * Handle the display of a group's Change cover image page.
 *
 * @since 2.4.0
 */
function groups_screen_group_admin_cover_image() {
	if ( 'group-cover-image' != bp_get_group_current_admin_tab() ) {
		return;
	}

	// If the logged-in user doesn't have permission or if cover image uploads are disabled, then stop here.
	if ( ! bp_is_item_admin() || ! bp_group_use_cover_image_header() ) {
		return;
	}

	/**
	 * Fires before the loading of the group Change cover image page template.
	 *
	 * @since 2.4.0
	 *
	 * @param int $id ID of the group that is being displayed.
	 */
	do_action( 'groups_screen_group_admin_cover_image', bp_get_current_group_id() );

	$templates = array(
		/**
		 * Filters the template to load for a group's Change cover image page.
		 *
		 * @since 2.4.0
		 *
		 * @param string $value Path to a group's Change cover image template.
		 */
		apply_filters( 'groups_template_group_admin_cover_image', 'groups/single/home' ),
		'groups/single/index',
	);

	bp_core_load_template( $templates );
}
add_action( 'bp_screens', 'groups_screen_group_admin_cover_image' );
