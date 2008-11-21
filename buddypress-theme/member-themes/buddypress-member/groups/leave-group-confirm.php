<div class="content-header">
	
</div>

<div id="content">	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
	
	<div class="left-menu">
		<?php bp_group_avatar() ?>

		<?php bp_group_join_button() ?>
		
		<div class="info-group">
			<h4><?php _e( 'Admins', 'buddypress' ) ?></h4>
			<?php bp_group_list_admins() ?>
		</div>
		
		<?php if ( bp_group_has_moderators() ) : ?>
		<div class="info-group">
			<h4><?php _e( 'Mods', 'buddypress' ) ?></h4>
			<?php bp_group_list_mods() ?>
		</div>
		<?php endif; ?>
	</div>

	<div class="main-column">
		<div class="inner-tube">

			<div id="group-name">
				<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
				<p class="status"><?php bp_group_type() ?></p>
			</div>

			<div class="info-group">
				<h4><?php _e( 'Confirm Leave Group', 'buddypress' ); ?></h4>
				<h3><?php _e( 'Are you sure you want to leave this group?', 'buddypress' ); ?></h3>
	
				<p>
					<a href="<?php bp_group_leave_confirm_link() ?>"><?php _e( "Yes, I'd like to leave this group.", 'buddypress' ) ?></a> | 
					<a href="<?php bp_group_leave_reject_link() ?>"><?php _e( "No, I'll stay!", 'buddypress' ) ?></a>
				</p>
			</div>
		
		</div>
	</div>
	
	<?php endwhile; endif; ?>
</div>