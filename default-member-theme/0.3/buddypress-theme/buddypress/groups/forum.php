<div class="content-header">
	
</div>

<div id="content">	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
		
	<h2><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a> &raquo; Forum</h2>
	
	<h3>A bbPress forum for the group will go here in the next version.</h3>
	
	<?php endwhile; endif; ?>
	
	</div>
</div>