<?php
/*
 * /profile/profile-loop.php
 * This file loops through the profile field groups, and then each profile field to
 * display the profile information that a user has entered.
 *
 * Loaded by: 'profile/index.php' (via the xprofile_get_profile() template tag)
 */
?>
<?php if ( bp_has_profile() ) : ?>	
	
	<?php while ( bp_profile_groups() ) : bp_the_profile_group(); ?>

		<?php if ( bp_group_has_fields() ) : ?>
			
			<div class="info-group">
				
				<h4><?php bp_the_profile_group_name() ?></h4>
				
				<table class="profile-fields">
				<?php while ( bp_profile_fields() ) : bp_the_profile_field(); ?>

					<?php if ( bp_field_has_data() ) : ?>
						
						<tr<?php bp_field_css_class() ?>>
							<td class="label">
								<?php bp_the_profile_field_name() ?>
							</td>
							<td class="data">
								<?php bp_the_profile_field_value() ?>
							</td>
						</tr>
						
					<?php endif; ?>

				<?php endwhile; ?>
				</table>
				
			</div>
			
		<?php endif; ?>	
		
	<?php endwhile; ?>
	
	<div class="button-block">
		
		<?php if ( bp_is_home() ) : ?>
			
			<?php bp_edit_profile_button() ?>
			
		<?php endif; ?>
		
	</div>
	
<?php else: ?>
	
	<div id="message" class="info">
		<p><?php _e( 'Sorry, this person does not have a public profile.', 'buddypress' ) ?></p>
	</div>
	
<?php endif;?>
