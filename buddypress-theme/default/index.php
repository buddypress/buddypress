<?php get_header(); ?>

	<div id="content">
	
		<?php if ( has_profile() ) : ?>
			<div id="profile-name">
				<h2><a href="<?php echo get_option('home'); ?>/"><?php bloginfo('name'); ?></a></h2>
				<div class="description"><?php bloginfo('description'); ?></div>
			</div>		
			
			<?php while ( profile_groups() ) : the_profile_group(); ?>

				<?php if ( has_fields() ) : ?>
					<div class="profile-group">
						<h3><?php the_profile_group_name() ?></h3>
					
						<table class="profile-fields">
						<?php while ( profile_fields() ) : the_profile_field(); ?>
							<tr>
								<td class="label">
									<?php the_profile_field_name() ?>
								</td>
								<td class="data">
									<?php the_profile_field_value() ?>
								</td>
							</tr>
						<?php endwhile; ?>
						</table>
					</div>
				<?php endif; ?>	
				
			<?php endwhile; ?>
			
		<?php else: ?>
			
			<p><?php _e('Sorry, this person does not have a profile.'); ?></p>
			
		<?php endif;?>
	
	</div>
	
<?php get_footer(); ?>