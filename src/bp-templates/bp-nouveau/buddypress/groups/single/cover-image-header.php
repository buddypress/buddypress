<?php
/**
 * BuddyPress - Groups Cover Image Header.
 *
 * @since 3.0.0
 * @version 12.0.0
 */
?>

<div id="cover-image-container">
	<div id="header-cover-image"></div>

	<div id="item-header-cover-image">
		<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
			<div id="item-header-avatar">
				<a href="<?php bp_group_url(); ?>" title="<?php echo esc_attr( bp_get_group_name() ); ?>">

					<?php bp_group_avatar(); ?>

				</a>
			</div><!-- #item-header-avatar -->
		<?php endif; ?>

		<?php if ( ! bp_nouveau_groups_front_page_description() ) : ?>
			<div id="item-header-content">

				<?php if ( bp_nouveau_group_has_meta( 'status' ) ) : ?>
					<p class="highlight group-status"><strong><?php bp_nouveau_the_group_meta( array( 'keys' => 'status' ) ); ?></strong></p>
				<?php endif; ?>

				<p class="activity">
					<?php
						printf(
							/* translators: %s: last activity timestamp (e.g. "Active 1 hour ago") */
							esc_html__( 'Active %s', 'buddypress' ),
							sprintf(
								'<span data-livestamp="%1$s">%2$s</span>',
								esc_attr( bp_core_get_iso8601_date( bp_get_group_last_active( 0, array( 'relative' => false ) ) ) ),
								esc_html( bp_get_group_last_active() )
							)
						);
					?>
				</p>

				<?php
				bp_group_type_list(
					bp_get_group_id(),
					array(
						'label'        => array(
							'plural'   => __( 'Group Types', 'buddypress' ),
							'singular' => __( 'Group Type', 'buddypress' ),
						),
						'list_element' => 'span',
					)
				);
				?>

				<?php bp_nouveau_group_hook( 'before', 'header_meta' ); ?>

				<?php if ( bp_nouveau_group_has_meta_extra() ) : ?>
					<div class="item-meta">

						<?php bp_nouveau_the_group_meta( array( 'keys' => 'extra' ) ); ?>

					</div><!-- .item-meta -->
				<?php endif; ?>

				<?php bp_nouveau_group_header_buttons(); ?>

			</div><!-- #item-header-content -->
		<?php endif; ?>

		<?php bp_get_template_part( 'groups/single/parts/header-item-actions' ); ?>

	</div><!-- #item-header-cover-image -->


</div><!-- #cover-image-container -->

<?php if ( ! bp_nouveau_groups_front_page_description() && bp_nouveau_group_has_meta( 'description' ) ) : ?>
	<div class="desc-wrap">
		<div class="group-description">
			<?php bp_group_description(); ?>
		</div><!-- //.group_description -->
	</div>
<?php endif; ?>
