<?php
/**
 * Members: Change Cover Image screen handler
 *
 * @package BuddyPress
 * @subpackage MembersScreens
 * @since 6.0.0
 */

/**
 * Handle the display of the profile Change Cover Image page by loading the correct template file.
 *
 * @since 6.0.0
 */
function bp_members_screen_change_cover_image() {
	// Bail if not the correct screen.
	if ( ! bp_is_my_profile() && ! bp_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	/** This action is documented in wp-includes/deprecated.php */
	do_action_deprecated( 'xprofile_screen_change_cover_image', array(), '6.0.0', 'bp_members_screen_change_cover_image' );

	/**
	 * Fires right before the loading of the Member Change Cover Image screen template file.
	 *
	 * @since 6.0.0
	 */
	do_action( 'bp_members_screen_change_cover_image' );

	$templates = array(
		/** This filter is documented in wp-includes/deprecated.php */
		apply_filters_deprecated( 'xprofile_template_cover_image', array( 'members/single/home' ), '6.0.0', 'bp_members_template_change_cover_image' ),
		'members/single/index',
	);

	/**
	 * Filters the template to load for the Member Change Cover Image page screen.
	 *
	 * @since 6.0.0
	 *
	 * @param string[] $templates Path to the list of Member templates to load.
	 */
	bp_core_load_template( apply_filters( 'bp_members_template_change_cover_image', $templates ) );
}
