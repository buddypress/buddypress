<?php
/**
 * BuddyPress - Members Single Profile WP
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 3.0.0
 */

/**
 * Fires before the display of member profile loop content.
 *
 * @since 1.2.0
 */
do_action( 'bp_before_profile_loop_content' ); ?>

<?php $ud = get_userdata( bp_displayed_user_id() ); ?>

<?php

	/**
	 * Fires before the display of member profile field content.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_before_profile_field_content' ); ?>

	<div class="bp-widget wp-profile">
		<h2><?php bp_is_my_profile() ? esc_html_e( 'My Profile', 'buddypress' ) : printf( esc_html__( "%s's Profile", 'buddypress' ), esc_html( bp_get_displayed_user_fullname() ) ); ?></h2>

		<table class="wp-profile-fields">

			<?php if ( $ud->display_name ) : ?>

				<tr id="wp_displayname">
					<td class="label"><?php esc_html_e( 'Name', 'buddypress' ); ?></td>
					<td class="data"><?php echo esc_html( $ud->display_name ); ?></td>
				</tr>

			<?php endif; ?>

			<?php if ( $ud->user_description ) : ?>

				<tr id="wp_desc">
					<td class="label"><?php esc_html_e( 'About Me', 'buddypress' ); ?></td>
					<td class="data"><?php echo esc_html( $ud->user_description ); ?></td>
				</tr>

			<?php endif; ?>

			<?php if ( $ud->user_url ) : ?>

				<tr id="wp_website">
					<td class="label"><?php esc_html_e( 'Website', 'buddypress' ); ?></td>
					<td class="data">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput
						echo make_clickable( esc_url( $ud->user_url ) );
						?>
					</td>
				</tr>

			<?php endif; ?>

			<?php if ( $ud->jabber ) : ?>

				<tr id="wp_jabber">
					<td class="label"><?php esc_html_e( 'Jabber', 'buddypress' ); ?></td>
					<td class="data"><?php echo esc_html( $ud->jabber ); ?></td>
				</tr>

			<?php endif; ?>

			<?php if ( $ud->aim ) : ?>

				<tr id="wp_aim">
					<td class="label"><?php esc_html_e( 'AOL Messenger', 'buddypress' ); ?></td>
					<td class="data"><?php echo esc_html( $ud->aim ); ?></td>
				</tr>

			<?php endif; ?>

			<?php if ( $ud->yim ) : ?>

				<tr id="wp_yim">
					<td class="label"><?php esc_html_e( 'Yahoo Messenger', 'buddypress' ); ?></td>
					<td class="data"><?php echo esc_html( $ud->yim ); ?></td>
				</tr>

			<?php endif; ?>

		</table>
	</div>

<?php

/**
 * Fires after the display of member profile field content.
 *
 * @since 1.1.0
 */
do_action( 'bp_after_profile_field_content' ); ?>

<?php

/**
 * Fires and displays the profile field buttons.
 *
 * @since 1.1.0
 */
do_action( 'bp_profile_field_buttons' ); ?>

<?php

/**
 * Fires after the display of member profile loop content.
 *
 * @since 1.2.0
 */
do_action( 'bp_after_profile_loop_content' );
