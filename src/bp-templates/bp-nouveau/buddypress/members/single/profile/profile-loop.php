<?php
/**
 * BuddyPress - Members Profile Loop
 *
 * @since 3.0.0
 * @version 3.0.0
 */

?>

<h2 class="screen-heading view-profile-screen"><?php _e( 'View Profile', 'buddypress' ); ?></h2>

<?php bp_nouveau_xprofile_hook( 'before', 'loop_content' ); ?>

<?php if ( bp_has_profile() ) : ?>

	<?php
	while ( bp_profile_groups() ) :
		bp_the_profile_group();
	?>

		<?php if ( bp_profile_group_has_fields() ) : ?>

			<?php bp_nouveau_xprofile_hook( 'before', 'field_content' ); ?>

			<div class="bp-widget <?php bp_the_profile_group_slug(); ?>">

				<h3 class="screen-heading profile-group-title">
					<?php bp_the_profile_group_name(); ?>
				</h3>

				<table class="profile-fields bp-tables-user">

					<?php
					while ( bp_profile_fields() ) :
						bp_the_profile_field();
					?>

						<?php if ( bp_field_has_data() ) : ?>

							<tr<?php bp_field_css_class(); ?>>

								<td class="label"><?php bp_the_profile_field_name(); ?></td>

								<td class="data"><?php bp_the_profile_field_value(); ?></td>

							</tr>

						<?php endif; ?>

						<?php bp_nouveau_xprofile_hook( '', 'field_item' ); ?>

					<?php endwhile; ?>

				</table>
			</div>

			<?php bp_nouveau_xprofile_hook( 'after', 'field_content' ); ?>

		<?php endif; ?>

	<?php endwhile; ?>

	<?php bp_nouveau_xprofile_hook( '', 'field_buttons' ); ?>

<?php endif; ?>

<?php
bp_nouveau_xprofile_hook( 'after', 'loop_content' );
