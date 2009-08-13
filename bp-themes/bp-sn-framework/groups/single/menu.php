<?php do_action( 'bp_before_group_menu_content' ) ?>

<?php bp_group_avatar() ?>

<?php do_action( 'bp_after_group_menu_avatar' ) ?>
<?php do_action( 'bp_before_group_menu_buttons' ) ?>

<div class="button-block">
	<?php bp_group_join_button() ?>

	<?php do_action( 'bp_group_menu_buttons' ) ?>
</div>

<?php do_action( 'bp_after_group_menu_buttons' ) ?>
<?php do_action( 'bp_before_group_menu_admins' ) ?>

<div class="bp-widget">
	<h4><?php _e( 'Admins', 'buddypress' ) ?></h4>
	<?php bp_group_list_admins() ?>
</div>

<?php do_action( 'bp_after_group_menu_admins' ) ?>
<?php do_action( 'bp_before_group_menu_mods' ) ?>

<?php if ( bp_group_has_moderators() ) : ?>
	<div class="bp-widget">
		<h4><?php _e( 'Mods' , 'buddypress' ) ?></h4>
		<?php bp_group_list_mods() ?>
	</div>
<?php endif; ?>

<?php do_action( 'bp_after_group_menu_mods' ) ?>
<?php do_action( 'bp_after_group_menu_content' ); /* Deprecated -> */ do_action( 'groups_sidebar_after' ); ?>