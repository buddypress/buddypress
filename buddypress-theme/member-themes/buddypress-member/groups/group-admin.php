<?php get_header() ?>

<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_group_admin_tabs(); ?>
	</ul>
</div>

<div id="content">	
	
		<h2><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a> &raquo; <?php _e( 'Group Admin', 'buddypress' ); ?></h2>
		
		<ul>
			<li><a href="<?php bp_group_admin_permalink() ?>/edit-details"><?php _e( 'Edit Details', 'buddypress' ); ?></a></li>
			<li><a href="<?php bp_group_admin_permalink() ?>/group-settings"><?php _e( 'Group Settings', 'buddypress' ); ?></a></li>
			<li><a href="<?php bp_group_admin_permalink() ?>/manage-members"><?php _e( 'Manage Members', 'buddypress' ); ?></a></li>
			<li><a href="<?php bp_group_admin_permalink() ?>/delete-group"><?php _e( 'Delete Group', 'buddypress' ); ?></a></li>
		</ul>
</div>

<?php endwhile; endif; ?>

<?php get_footer() ?>