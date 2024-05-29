<?php
/**
 * BuddyPress - Members Single Profile WP
 *
 * @since 3.0.0
 * @version 3.1.0
 */

bp_nouveau_wp_profile_hooks( 'before' ); ?>

<div class="bp-widget wp-profile">

	<h2 class="screen-heading wp-profile-screen">
		<?php
		if ( bp_is_my_profile() ) {
			esc_html_e( 'My Profile', 'buddypress' );
		} else {
			printf(
				/* Translators: a member's profile, e.g. "Paul's profile". */
				esc_html__( "%s's Profile", 'buddypress' ),
				// phpcs:ignore WordPress.Security.EscapeOutput
				bp_get_displayed_user_fullname() // Escaped in `bp-members/bp-members-filters.php`.
			);
		}
		?>
	</h2>

	<?php if ( bp_nouveau_has_wp_profile_fields() ) : ?>

		<table class="wp-profile-fields">

			<?php
			while ( bp_nouveau_wp_profile_fields() ) :
				bp_nouveau_wp_profile_field();
			?>

				<tr id="<?php bp_nouveau_wp_profile_field_id(); ?>">
					<td class="label"><?php bp_nouveau_wp_profile_field_label(); ?></td>
					<td class="data"><?php bp_nouveau_wp_profile_field_data(); ?></td>
				</tr>

			<?php endwhile; ?>

		</table>

	<?php else : ?>

		<?php bp_nouveau_user_feedback( 'member-wp-profile-none' ); ?>

	<?php endif; ?>

</div>

<?php
bp_nouveau_wp_profile_hooks( 'after' );

