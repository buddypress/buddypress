<?php get_header(); ?>

	<div class="content-header">
		Header Content
	</div>

	<div id="content">
	
		<div class="left-menu">
			<?php bp_the_avatar() ?>
			
			<?php //bp_user_groups() ?>
		</div>
	
		<div class="main-column">
		<?php if ( bp_has_profile() ) : ?>
			<div id="profile-name">
				<h1><a href="<?php bp_user_link() ?>"><?php bp_user_fullname() ?></a></h1>
				<p class="status"><?php bp_user_status() ?></p>
			</div>
			
			<?php while ( bp_profile_groups() ) : bp_the_profile_group(); ?>

				<?php if ( bp_group_has_fields() ) : ?>
					<div class="profile-group">
						<h4><?php bp_the_profile_group_name() ?></h4>
						
						<table class="profile-fields">
						<?php while ( bp_profile_fields() ) : bp_the_profile_field(); ?>

							<?php if ( bp_field_has_data() ) : ?>
							<tr>
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
			
		<?php else: ?>
			
			<p><?php _e('Sorry, this person does not have a public profile.'); ?></p>
			
		<?php endif;?>
		</div>
	
	</div>
	
<?php get_footer(); ?>