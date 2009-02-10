<?php get_header() ?>

<div class="content-header">

</div>

<div id="content">
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
	
	<div class="left-menu">
		<?php load_template( TEMPLATEPATH . '/groups/group-menu.php' ) ?>
	</div>

	<div class="main-column">
		<div class="inner-tube">
			
			<div id="group-name">
				<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
				<p class="status"><?php bp_group_type() ?></p>
			</div>
		
			<?php if ( !bp_group_is_visible() ) : ?>
				<div id="message" class="info">
					<p><?php bp_group_status_message() ?></p>
				</div>
			<?php endif;?>
		
			<div class="info-group">
				<h4><?php _e( 'Description', 'buddypress' ); ?></h4>
				<p><?php bp_group_description() ?></p>
			</div>
		
			<?php if ( bp_group_is_visible() && bp_group_has_news() ) : ?>
				<div class="info-group">
					<h4><?php _e( 'News', 'buddypress' ); ?></h4>
					<p><?php bp_group_news() ?></p>
				</div>
			<?php endif; ?>
			
			<?php do_action( 'groups_custom_group_fields' ) ?>
			
			<?php if ( bp_group_is_visible() && bp_group_is_forum_enabled() && function_exists( 'bp_forums_setup') ) : ?>
				<div class="info-group">
					<h4><?php _e( 'Active Forum Topics', 'buddypress' ); ?> <a href="<?php bp_group_forum_permalink() ?>"><?php _e( 'See All &raquo;', 'buddypress' ) ?></a></h4>
					<p><?php bp_group_active_forum_topics() ?></p>
				</div>
			<?php endif; ?>
		
			<?php if ( bp_group_is_visible() ) : ?>
				<div class="info-group">
					<h4><?php printf( __( 'Members (%d) <a href="%s">See All &raquo;</a>', 'buddypress' ), bp_group_total_members( false ), bp_group_all_members_permalink( false ) ); ?></h4>
					<?php bp_group_random_members() ?>
				</div>
			<?php endif; ?>
			
			<?php do_action( 'groups_custom_group_boxes' ) ?>
		
			<?php if ( bp_group_is_visible() && bp_group_is_wire_enabled() ) : ?>
				<?php if ( function_exists('bp_wire_get_post_list') ) : ?>
					<?php bp_wire_get_post_list( bp_group_id( false ), __( 'Group Wire', 'buddypress' ), sprintf( __( 'The are no wire posts for %s', 'buddypress' ), bp_group_name( false ) ), bp_group_is_member(), true ) ?>
				<?php endif; ?>
			<?php endif; ?>
		
		</div>

	<?php endwhile; else: ?>
		<div id="message" class="error">
			<p><?php _e("Sorry, the group does not exist.", "buddypress"); ?></p>
		</div>
	<?php endif;?>
	
	</div>
</div>

<?php get_footer() ?>