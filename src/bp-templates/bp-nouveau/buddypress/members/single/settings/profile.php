<?php
/**
 * BuddyPress - Members Settings ( Profile )
 *
 * @since 3.0.0
 * @version 8.0.0
 */

bp_nouveau_member_hook( 'before', 'settings_template' ); ?>

<h2 class="screen-heading profile-settings-screen">
	<?php esc_html_e( 'Profile Visibility Settings', 'buddypress' ); ?>
</h2>

<p class="bp-help-text profile-visibility-info">
	<?php esc_html_e( 'Select who may see your profile details.', 'buddypress' ); ?>
</p>

<form action="<?php bp_displayed_user_link( array( bp_nouveau_get_component_slug( 'settings' ), 'profile' ) ); ?>" method="post" class="standard-form" id="settings-form">

	<?php if ( bp_xprofile_get_settings_fields() ) : ?>

		<?php
		while ( bp_profile_groups() ) :
			bp_the_profile_group();
		?>

			<?php if ( bp_profile_fields() ) : ?>

				<table class="profile-settings bp-tables-user" id="<?php echo esc_attr( 'xprofile-settings-' . bp_get_the_profile_group_slug() ); ?>">
					<thead>
						<tr>
							<th class="title field-group-name"><?php bp_the_profile_group_name(); ?></th>
							<th class="title"><?php esc_html_e( 'Visibility', 'buddypress' ); ?></th>
						</tr>
					</thead>

					<tbody>

						<?php
						while ( bp_profile_fields() ) :
							bp_the_profile_field();
						?>

							<tr <?php bp_field_css_class(); ?>>
								<td class="field-name"><?php bp_the_profile_field_name(); ?></td>
								<td class="field-visibility"><?php bp_profile_settings_visibility_select(); ?></td>
							</tr>

						<?php endwhile; ?>

					</tbody>
				</table>

			<?php endif; ?>

		<?php endwhile; ?>

	<?php endif; ?>

	<input type="hidden" name="field_ids" id="field_ids" value="<?php bp_the_profile_field_ids(); ?>" />

	<?php bp_nouveau_submit_button( 'members-profile-settings' ); ?>

</form>

<?php
bp_nouveau_member_hook( 'after', 'settings_template' );
