<div class="content-header">

</div>

<div id="content">
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
		
	<div class="left-menu">
		<?php bp_group_avatar() ?>
		
		<?php bp_group_join_button() ?>
		
		<div class="info-group">
			<h4>Admins</h4>
			<?php bp_group_list_admins() ?>
		</div>
	</div>

	<div class="main-column">
		
		<div id="group-name">
			<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
			<p class="status"><?php bp_group_type() ?></p>
		</div>
		
		<div class="info-group">
			<h4>Description</h4>
			<p><?php bp_group_description() ?></p>
		</div>
		
		<div class="info-group">
			<h4>News</h4>
			<p><?php bp_group_news() ?></p>
		</div>
		
		<div class="info-group">
			<h4>Members <a href="<?php bp_group_all_members_permalink() ?>">See All &raquo;</a></h4>
			<p><?php bp_group_random_members() ?></p>
		</div>				
		
	<?php endwhile; else: ?>
		<div id="message" class="error">
			<p><?php _e('Sorry, the group does not exist.'); ?></p>
		</div>
	<?php endif;?>
	
	</div>
</div>