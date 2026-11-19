<?php
/**
 * Members: Change Cover Image screen handler
 *
 * @package BuddyPress
 * @subpackage Members
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

	/**
	 * Fires before loading the member Change Cover Image screen template.
	 *
	 * @deprecated 6.0.0 Use the `bp_members_screen_change_cover_image` action instead.
	 */
	do_action_deprecated(
		'xprofile_screen_change_cover_image',
		array(),
		'6.0.0',
		'bp_members_screen_change_cover_image'
	);

	/**
	 * Fires right before the loading of the Member Change Cover Image screen template file.
	 *
	 * @since 6.0.0
	 */
	do_action( 'bp_members_screen_change_cover_image' );

	$templates = array(
		/**
		 * Filters the first template candidate for the Change Cover Image screen.
		 *
		 * @deprecated 6.0.0 Use the `bp_members_template_change_cover_image` filter instead.
		 *                    The replacement receives the full list of template candidates.
		 *
		 * @param string $template Template path.
		 */
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
