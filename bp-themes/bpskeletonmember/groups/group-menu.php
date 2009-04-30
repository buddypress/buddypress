<?php
/*
 * /groups/group-menu.php
 * Displays the group avatar, a join button, and a list of group admins and mods. This is
 * used as a kind of 'group sidebar', but it doesn't have to be a sidebar depending
 * on how you style it.
 * 
 * Loaded by: '/groups/group-home.php'
 *            '/groups/leave-group-confirm.php'
 *            '/groups/list-members.php'
 *            '/groups/request-membership.php'
 *            '/groups/wire.php'
 */
?>

<?php bp_group_avatar() ?>

<div class="button-block">
	<?php bp_group_join_button() ?>
</div>

<?php do_action( 'groups_sidebar_before' ) ?>

<div class="info-group">
	
	<h4><?php _e( 'Admins', 'buddypress' ) ?></h4>
	<?php bp_group_list_admins() ?>
	
</div>

<?php if ( bp_group_has_moderators() ) : ?>
	
	<div class="info-group">
		<h4><?php _e( 'Mods' , 'buddypress' ) ?></h4>
		<?php bp_group_list_mods() ?>
	</div>
	
<?php endif; ?>

<?php do_action( 'groups_sidebar_after' ) ?>