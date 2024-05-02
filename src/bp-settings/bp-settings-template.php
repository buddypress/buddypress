<?php
/**
 * BuddyPress Settings Template Functions.
 *
 * @package BuddyPress
 * @subpackage SettingsTemplate
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the settings component slug.
 *
 * @since 1.5.0
 */
function bp_settings_slug() {
	echo esc_attr( bp_get_settings_slug() );
}

/**
 * Return the settings component slug.
 *
 * @since 1.5.0
 *
 * @return string
 */
function bp_get_settings_slug() {

	/**
	 * Filters the Settings component slug.
	 *
	 * @since 1.5.0
	 *
	 * @param string $slug Settings component slug.
	 */
	return apply_filters( 'bp_get_settings_slug', buddypress()->settings->slug );
}

/**
 * Output the settings component root slug.
 *
 * @since 1.5.0
 */
function bp_settings_root_slug() {
	echo esc_attr( bp_get_settings_root_slug() );
}

/**
 * Return the settings component root slug.
 *
 * @since 1.5.0
 *
 * @return string
 */
function bp_get_settings_root_slug() {

	/**
	 * Filters the Settings component root slug.
	 *
	 * @since 1.5.0
	 *
	 * @param string $root_slug Settings component root slug.
	 */
	return apply_filters( 'bp_get_settings_root_slug', buddypress()->settings->root_slug );
}

/**
 * Add the 'pending email change' message to the settings page.
 *
 * @since 2.1.0
 */
function bp_settings_pending_email_notice() {
	$pending_email = bp_get_user_meta( bp_displayed_user_id(), 'pending_email_change', true );

	if ( empty( $pending_email['newemail'] ) ) {
		return;
	}

	if ( bp_get_displayed_user_email() == $pending_email['newemail'] ) {
		return;
	}

	$dismiss_url = wp_nonce_url(
		add_query_arg(
			'dismiss_email_change',
			1,
			bp_displayed_user_url(
				bp_members_get_path_chunks( array( bp_get_settings_slug() ) )
			)
		),
		'bp_dismiss_email_change'
	);
	?>

	<div id="message" class="bp-template-notice error">
		<p>
			<?php
			printf(
				/* translators: %s: new email address */
				esc_html__( 'There is a pending change of your email address to %s.', 'buddypress' ),
				'<code>' . esc_html( $pending_email['newemail'] ) . '</code>'
			);
			?>
			<br />
			<?php
			printf(
				/* translators: 1: email address. 2: cancel email change url. */
				esc_html__( 'Check your email (%1$s) for the verification link, or %2$s.', 'buddypress' ),
				'<code>' . esc_html( $pending_email['newemail'] ) . '</code>',
				'<a href="' . esc_url( $dismiss_url ) . '">' . esc_html__( 'cancel the pending change', 'buddypress' ) . '</a>'
			);
			?>
		</p>
	</div>

	<?php
}
add_action( 'bp_before_member_settings_template', 'bp_settings_pending_email_notice' );
