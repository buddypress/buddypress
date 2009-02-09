<?php bp_group_avatar() ?>

<div class="button-block">
	<?php bp_group_join_button() ?>
</div>

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