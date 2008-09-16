<div class="content-header">
	<?php bp_profile_last_updated() ?>
</div>

<div id="content">
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<div class="left-menu">
		<?php bp_the_avatar() ?>
		
		<?php if ( bp_exists('friends') ) : ?>
			<?php bp_add_friend_button() ?>
		<?php endif; ?>
		
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
				<div class="info-group">
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
		
		<?php if ( function_exists('bp_groups_random_groups') ) : ?>
			<?php bp_groups_random_groups() ?>
		<?php endif; ?>
		
		<?php if ( function_exists('bp_friends_random_friends') ) : ?>
			<?php bp_friends_random_friends() ?>
		<?php endif; ?>

		<?php if ( function_exists('bp_wire_get_post_list') ) : ?>
			<?php bp_wire_get_post_list( bp_core_get_current_userid(), bp_my_or_name( true, false ) . ' Wire', 'No one has posted to ' . bp_your_or_name( false, false ) . ' wire yet.' ) ?>
		<?php endif; ?>
		
	<?php else: ?>
		
		<div id="message" class="info">
			<p>Sorry, this person does not have a public profile.</p>
		</div>
		
	<?php endif;?>
	</div>

</div>