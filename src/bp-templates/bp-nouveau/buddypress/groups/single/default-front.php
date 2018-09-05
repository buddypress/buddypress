<?php
/**
 * BP Nouveau Default group's front template.
 *
 * @since 3.0.0
 * @version 3.2.0
 */
?>

<div class="group-front-page">

	<?php if ( ! is_active_sidebar( 'sidebar-buddypress-groups' ) || ! bp_nouveau_groups_do_group_boxes() ) : ?>
		<?php if ( ! is_customize_preview() && bp_current_user_can( 'bp_moderate' ) ) : ?>

			<div class="bp-feedback custom-homepage-info info no-icon">
				<strong><?php esc_html_e( 'Manage the Groups default front page', 'buddypress' ); ?></strong>

				<p>
				<?php
				printf(
					esc_html__( 'You can set your preferences for the %1$s or add %2$s to it.', 'buddypress' ),
					bp_nouveau_groups_get_customizer_option_link(),
					bp_nouveau_groups_get_customizer_widgets_link()
				);
				?>
				</p>

			</div>

		<?php endif; ?>
	<?php endif; ?>

	<?php if ( bp_nouveau_groups_front_page_description() && bp_nouveau_group_has_meta( 'description' ) ) : ?>
		<div class="group-description">

			<?php bp_group_description(); ?>

		</div><!-- .group-description -->
	<?php endif; ?>

	<?php if ( bp_nouveau_groups_do_group_boxes() ) : ?>
		<div class="bp-plugin-widgets">

			<?php bp_custom_group_boxes(); ?>

		</div><!-- .bp-plugin-widgets -->
	<?php endif; ?>

	<?php if ( is_active_sidebar( 'sidebar-buddypress-groups' ) ) : ?>
		<div id="group-front-widgets" class="bp-sidebar bp-widget-area" role="complementary">

			<?php dynamic_sidebar( 'sidebar-buddypress-groups' ); ?>

		</div><!-- .bp-sidebar.bp-widget-area -->
	<?php endif; ?>

</div>
