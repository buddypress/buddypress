<?php do_action( 'bp_before_group_menu_content' ) ?>

<?php bp_group_avatar() ?>

<?php do_action( 'bp_after_group_menu_avatar' ) ?>
<?php do_action( 'bp_before_group_menu_buttons' ) ?>

<div class="button-block">
	<?php bp_group_join_button() ?>

	<?php do_action( 'bp_group_menu_buttons' ) ?>
</div>

<?php do_action( 'bp_after_group_menu_buttons' ) ?>
<?php do_action( 'bp_before_group_description' ) ?>

<div class="bp-widget">
	<h4><?php _e( 'Description', 'buddypress' ); ?></h4>
	<p><?php bp_group_description() ?></p>
</div>

<?php do_action( 'bp_after_group_description' ) ?>
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

<?php if ( bp_group_is_visible() ) : ?>

	<?php do_action( 'bp_before_group_member_widget' ) ?>

	<div class="bp-widget">
		<h4><?php printf( __( 'Members (%d)', 'buddypress' ), bp_get_group_total_members() ); ?> <span><a href="<?php bp_group_all_members_permalink() ?>"><?php _e( 'See All', 'buddypress' ) ?> &rarr;</a></span></h4>

		<?php if ( bp_group_has_members( 'max=5&exclude_admins_mods=0' ) ) : ?>

			<ul class="horiz-gallery">
				<?php while ( bp_group_members() ) : bp_group_the_member(); ?>

					<li>
						<a href="<?php bp_group_member_url() ?>"><?php bp_group_member_avatar_mini() ?></a>
					</li>
				<?php endwhile; ?>
			</ul>

		<?php endif; ?>

	</div>

	<?php do_action( 'bp_after_group_member_widget' ) ?>

<?php endif; ?>


<?php do_action( 'bp_after_group_menu_mods' ) ?>
<?php do_action( 'bp_after_group_menu_content' ); /* Deprecated -> */ do_action( 'groups_sidebar_after' ); ?>