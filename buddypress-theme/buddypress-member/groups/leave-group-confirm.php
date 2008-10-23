<div class="content-header">
	
</div>

<div id="content">	
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
			<h4>Confirm Leave Group</h4>
			<h3>Are you sure you want to leave this group?</h3>
	
			<p>
				<a href="<?php bp_group_leave_confirm_link() ?>">Yes, I'd like to leave this group.</a> | 
				<a href="<?php bp_group_leave_reject_link() ?>">No, I'll stay!</a>
			</p>
		</div>
		
	</div>
	
	<?php endwhile; endif; ?>
</div>