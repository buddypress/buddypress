<div class="content-header">
	
</div>

<div id="content">	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
	
	<div class="left-menu">
		<?php bp_group_avatar() ?>

		<?php bp_group_join_button() ?>
		
		<div class="info-group">
			<h4><?php _e( 'Admins', 'buddypress' ); ?></h4>
			<?php bp_group_list_admins() ?>
		</div>
		
		<?php if ( bp_group_has_moderators() ) : ?>
		<div class="info-group">
			<h4><?php _e( 'Mods', 'buddypress' ); ?></h4>
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
				<h4><?php _e( 'Forum', 'buddypress' ); ?></h4>
				<h3>[Placeholder]</h3>
			</div>
		
		</div>
	</div>
	
	<?php endwhile; endif; ?>
</div>