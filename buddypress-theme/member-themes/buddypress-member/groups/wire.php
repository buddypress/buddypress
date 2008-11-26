<?php get_header() ?>

<div class="content-header">
	
</div>

<div id="content">	
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
	
	<div class="left-menu">
		<?php bp_group_avatar() ?>

		<?php bp_group_join_button() ?>

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
	</div>

	<div class="main-column">
		<div class="inner-tube">
			
			<div id="group-name">
				<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
				<p class="status"><?php bp_group_type() ?></p>
			</div>

			<div class="info-group">
				<?php if ( function_exists('bp_wire_get_post_list') ) : ?>
					<?php bp_wire_get_post_list( bp_group_id(false), __( 'Group Wire', 'buddypress' ), sprintf( __( 'The are no wire posts for %s', 'buddypress' ), bp_group_name(false) ), bp_group_is_member(), true ) ?>
				<?php endif; ?>
			</div>
			
		</div>
	</div>
	
	<?php endwhile; endif; ?>

</div>

<?php get_footer() ?>