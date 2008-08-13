<?php get_header(); ?>

	<div class="content-header">
		
	</div>

	<div id="content">
		<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
			
		<h2><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a> &raquo; Leave Group</h2>
		
		<h3>Are you sure you want to leave this group?</h3>
		
		<p>
			<a href="<?php bp_group_leave_confirm_link() ?>">Yes, I'd like to leave this group.</a> | 
			<a href="<?php bp_group_leave_reject_link() ?>">No, I'll stay!</a>
		</p>
		
		<?php endwhile; endif; ?>
		
		</div>
	</div>

<?php get_footer() ?>