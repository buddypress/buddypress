<?php
/**
 * BP Nouveau Default user's front template.
 *
 * @since 3.0.0
 * @version 3.1.0
 */
?>

<div class="member-front-page">

	<?php if ( ! is_customize_preview() && bp_current_user_can( 'bp_moderate' ) && ! is_active_sidebar( 'sidebar-buddypress-members' ) ) : ?>

		<div class="bp-feedback custom-homepage-info info">
			<strong><?php esc_html_e( 'Manage the members default front page', 'buddypress' ); ?></strong>
			<button type="button" class="bp-tooltip" data-bp-tooltip="<?php echo esc_attr_x( 'Close', 'button', 'buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Close this notice', 'buddypress' ); ?>" data-bp-close="remove"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span></button><br/>
			<?php
			printf(
				/* translators: 1: link to the customizer option. 2: link to the customizer widgets section. */
				esc_html__( 'You can set the preferences of the %1$s or add %2$s to it.', 'buddypress' ),
				// phpcs:disable WordPress.Security.EscapeOutput
				bp_nouveau_members_get_customizer_option_link(), // Escaped in `bp_nouveau_get_customizer_link()`.
				bp_nouveau_members_get_customizer_widgets_link() // Escaped in `bp_nouveau_get_customizer_link()`.
				// phpcs:enable
			);
			?>
		</div>

	<?php endif; ?>

	<?php if ( bp_nouveau_members_wp_bio_info() ) : ?>

		<div class="member-description">

			<?php if ( get_the_author_meta( 'description', bp_displayed_user_id() ) ) : ?>
				<blockquote class="member-bio">
					<?php bp_nouveau_member_description( bp_displayed_user_id() ); ?>
				</blockquote><!-- .member-bio -->
			<?php endif; ?>

			<?php
			if ( bp_is_my_profile() ) :

				bp_nouveau_member_description_edit_link();

			endif;
			?>

		</div><!-- .member-description -->

	<?php endif; ?>

	<?php if ( is_active_sidebar( 'sidebar-buddypress-members' ) ) : ?>

		<div id="member-front-widgets" class="bp-sidebar bp-widget-area" role="complementary">
			<?php dynamic_sidebar( 'sidebar-buddypress-members' ); ?>
		</div><!-- .bp-sidebar.bp-widget-area -->

	<?php endif; ?>

</div>
