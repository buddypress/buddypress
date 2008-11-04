<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_group_admin_tabs(); ?>
	</ul>
</div>

<div id="content">	
	
		<h2><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a> &raquo; Group Admin</h2>
		
		<ul>
			<li><a href="<?php bp_group_admin_permalink() ?>/edit-details">Edit Details</a></li>
			<li><a href="<?php bp_group_admin_permalink() ?>/group-settings">Group Settings</a></li>
			<li><a href="<?php bp_group_admin_permalink() ?>/manage-members">Manage Members</a></li>
			<li><a href="<?php bp_group_admin_permalink() ?>/delete-group">Delete Group</a></li>
		</ul>
</div>

<?php endwhile; endif; ?>