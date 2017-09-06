<?php
/**
 * BuddyPress - Groups Header item-actions.
 *
 * @since 1.0.0
 */
?>
<div id="item-actions" class="group-item-actions">

	<?php if ( bp_group_is_visible() ) : ?>

	<dl class="moderators-lists">
		<dt class="moderators-title"><?php _e( 'Group Admins', 'buddypress' ); ?></dt>
		<dd class="user-list admins"><?php bp_group_list_admins(); ?>
			<?php bp_nouveau_group_hook( 'after', 'menu_admins' ); ?>
		</dd>
	</dl>

	<?php		if ( bp_group_has_moderators() ) :
			bp_nouveau_group_hook( 'before', 'menu_mods' ); ?>

	<dl class="moderators-lists">
		<dt class="moderators-title"><?php _e( 'Group Mods' , 'buddypress' ); ?></dt>
		<dd class="user-list moderators">
				<?php bp_group_list_mods();
				bp_nouveau_group_hook( 'after', 'menu_mods' ); ?>
		</dd>
	</dl>


<?php	endif;

	endif; ?>

</div><!-- .item-actions -->
