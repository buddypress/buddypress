<?php
/**
 * Main BuddyPress Admin Class.
 *
 * @package BuddyPress
 * @subpackage CoreAdministration
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! buddypress()->do_autoload ) {
	require dirname( __FILE__ ) . '/classes/class-bp-admin.php';
}

/**
 * Setup BuddyPress Admin.
 *
 * @since 1.6.0
 *
 */
function bp_admin() {
	buddypress()->admin = new BP_Admin();
	return;


	// These are strings we may use to describe maintenance/security releases, where we aim for no new strings.
	_n_noop( 'Maintenance Release', 'Maintenance Releases', 'buddypress' );
	_n_noop( 'Security Release', 'Security Releases', 'buddypress' );
	_n_noop( 'Maintenance and Security Release', 'Maintenance and Security Releases', 'buddypress' );

	/* translators: 1: WordPress version number. */
	_n_noop( '<strong>Version %1$s</strong> addressed a security issue.',
	         '<strong>Version %1$s</strong> addressed some security issues.',
	         'buddypress' );

	/* translators: 1: WordPress version number, 2: plural number of bugs. */
	_n_noop( '<strong>Version %1$s</strong> addressed %2$s bug.',
	         '<strong>Version %1$s</strong> addressed %2$s bugs.',
	         'buddypress' );

	/* translators: 1: WordPress version number, 2: plural number of bugs. Singular security issue. */
	_n_noop( '<strong>Version %1$s</strong> addressed a security issue and fixed %2$s bug.',
	         '<strong>Version %1$s</strong> addressed a security issue and fixed %2$s bugs.',
	         'buddypress' );

	/* translators: 1: WordPress version number, 2: plural number of bugs. More than one security issue. */
	_n_noop( '<strong>Version %1$s</strong> addressed some security issues and fixed %2$s bug.',
	         '<strong>Version %1$s</strong> addressed some security issues and fixed %2$s bugs.',
	         'buddypress' );

	__( 'For more information, see <a href="%s">the release notes</a>.', 'buddypress' );
}
