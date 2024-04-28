<?php
/**
 * BuddyPress - Members Single Profile Edit
 *
 * @since 3.0.0
 * @version 3.1.0
 */

bp_nouveau_xprofile_hook( 'before', 'edit_content' ); ?>

<h2 class="screen-heading edit-profile-screen"><?php esc_html_e( 'Edit Profile', 'buddypress' ); ?></h2>

<?php if ( bp_has_profile( 'profile_group_id=' . bp_get_current_profile_group_id() ) ) :
	while ( bp_profile_groups() ) :
		bp_the_profile_group();
	?>

		<form action="<?php bp_the_profile_group_edit_form_action(); ?>" method="post" id="profile-edit-form" class="standard-form profile-edit <?php bp_the_profile_group_slug(); ?>">

			<?php bp_nouveau_xprofile_hook( 'before', 'field_content' ); ?>

				<?php if ( bp_profile_has_multiple_groups() ) : ?>
					<ul class="button-tabs button-nav">

						<?php bp_profile_group_tabs(); ?>

					</ul>
				<?php endif; ?>

				<h3 class="screen-heading profile-group-title edit">
					<?php
					printf(
						/* translators: %s = profile field group name */
						esc_html__( 'Editing "%s" Profile Group', 'buddypress' ),
						esc_html( bp_get_the_profile_group_name() )
					);
					?>
				</h3>

				<?php
				while ( bp_profile_fields() ) :
					bp_the_profile_field();
				?>

					<div<?php bp_field_css_class( 'editfield' ); ?>>
						<fieldset>

						<?php
						$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
						$field_type->edit_field_html();
						?>

						<?php bp_nouveau_xprofile_edit_visibilty(); ?>

						</fieldset>
					</div>

				<?php endwhile; ?>

			<?php bp_nouveau_xprofile_hook( 'after', 'field_content' ); ?>

			<input type="hidden" name="field_ids" id="field_ids" value="<?php bp_the_profile_field_ids(); ?>" />

			<?php bp_nouveau_submit_button( 'member-profile-edit' ); ?>

		</form>

	<?php endwhile; ?>

<?php endif; ?>

<?php
bp_nouveau_xprofile_hook( 'after', 'edit_content' );
