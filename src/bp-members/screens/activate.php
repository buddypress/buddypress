<?php
/**
 * Members: Activate screen handler
 *
 * @package BuddyPress
 * @subpackage MembersScreens
 * @since 3.0.0
 */

/**
 * Handle the loading of the Activate screen.
 *
 * @since 1.1.0
 */
function bp_core_screen_activation() {

	// Bail if not viewing the activation page.
	if ( ! bp_is_current_component( 'activate' ) ) {
		return false;
	}

	// If the user is already logged in, redirect away from here.
	if ( is_user_logged_in() ) {

		// If activation page is also front page, set to members directory to
		// avoid an infinite loop. Otherwise, set to root domain.
		$redirect_to = bp_is_component_front_page( 'activate' )
			? bp_get_members_directory_permalink()
			: bp_get_root_url();

		// Trailing slash it, as we expect these URL's to be.
		$redirect_to = trailingslashit( $redirect_to );

		/**
		 * Filters the URL to redirect logged in users to when visiting activation page.
		 *
		 * @since 1.9.0
		 *
		 * @param string $redirect_to URL to redirect user to.
		 */
		$redirect_to = apply_filters( 'bp_loggedin_activate_page_redirect_to', $redirect_to );

		// Redirect away from the activation page.
		bp_core_redirect( $redirect_to );
	}

	// Get BuddyPress.
	$bp = buddypress();

	/**
	 * Filters the template to load for the Member activation page screen.
	 *
	 * @since 1.1.1
	 *
	 * @param string $value Path to the Member activation template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_core_template_activate', array( 'activate', 'registration/activate' ) ) );
}
add_action( 'bp_screens', 'bp_core_screen_activation' );


/**
 * Catches and processes account activation requests.
 *
 * @since 3.0.0
 */
function bp_members_action_activate_account() {
	if ( ! bp_is_current_component( 'activate' ) ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}

	if ( ! empty( $_POST['key'] ) ) {
		$key = wp_unslash( $_POST['key'] );

	// Backward compatibility with templates using `method="get"` in their activation forms.
	} elseif ( ! empty( $_GET['key'] ) ) {
		$key = wp_unslash( $_GET['key'] );
	}

	if ( empty( $key ) ) {
		return;
	}

	$bp       = buddypress();
	$redirect = bp_get_activation_page();

	/**
	 * Filters the activation signup.
	 *
	 * @since 1.1.0
	 *
	 * @param bool|int $value Value returned by activation.
	 *                        Integer on success, boolean on failure.
	 */
	$user = apply_filters( 'bp_core_activate_account', bp_core_activate_signup( $key ) );

	// If there were errors, add a message and redirect.
	if ( ! empty( $user->errors ) ) {
		/**
		 * Filter here to redirect the User to a different URL than the activation page.
		 *
		 * @since 10.0.0
		 *
		 * @param string   $redirect The URL to use to redirect the user.
		 * @param WP_Error $user     The WP Error object.
		 */
		$redirect = apply_filters( 'bp_members_action_activate_errored_redirect', $redirect, $user );

		bp_core_add_message( $user->get_error_message(), 'error' );
		bp_core_redirect( $redirect );
	}

	/**
	 * Filter here to redirect the User to a different URL than the activation page.
	 *
	 * @since 10.0.0
	 *
	 * @param string $redirect The URL to use to redirect the user.
	 */
	$redirect = apply_filters( 'bp_members_action_activate_successed_redirect', $redirect );

	bp_core_add_message( __( 'Your account is now active!', 'buddypress' ) );
	bp_core_redirect( add_query_arg( 'activated', '1', $redirect ) );

}
add_action( 'bp_actions', 'bp_members_action_activate_account' );
