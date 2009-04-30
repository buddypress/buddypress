<?php
/*
 * /groups/wire.php
 * Displays the group wire and all the messages that have been posted on it.
 * 
 * Loads: '/groups/group-menu.php' (displays group avatar, mod and admin list)
 *        '/wire/post-list.php' (via the bp_wire_get_post_list() template tag)
 *
 * Loaded on URL:
 * 'http://example.org/groups/[group-slug]/wire/
 */
?>

<?php get_header() ?>

<div id="main">	
	<?php do_action( 'template_notices' ) ?>
	
	<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>
	
	<div class="page-menu">
		<?php load_template( TEMPLATEPATH . '/groups/group-menu.php' ) ?>
	</div>

	<div class="main-column">
		<div class="inner-tube">
			
			<div id="group-name">
				<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
				<p class="status"><?php bp_group_type() ?></p>
			</div>

			<div class="info-group">
				<?php if ( function_exists('bp_wire_get_post_list') ) : ?>
					<?php bp_wire_get_post_list( bp_get_group_id(), __( 'Group Wire', 'buddypress' ), sprintf( __( 'The are no wire posts for %s', 'buddypress' ), bp_get_group_name() ), bp_group_is_member(), true ) ?>
				<?php endif; ?>
			</div>
			
		</div>
	</div>
	
	<?php endwhile; endif; ?>

</div>

<?php get_footer() ?>