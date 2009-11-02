<?php get_header() ?>

	<div class="content-header">

	</div>

	<div id="content">
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

			<?php do_action( 'bp_before_group_wire_content' ) ?>

			<div class="left-menu">
				<?php locate_template( array( 'groups/single/menu.php' ), true ) ?>
			</div>

			<div class="main-column">
				<div class="inner-tube">

					<div id="group-name">
						<h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>
						<p class="status"><?php bp_group_type() ?></p>
					</div>

					<div class="bp-widget">
						<?php if ( function_exists('bp_wire_get_post_list') ) : ?>

							<?php bp_wire_get_post_list( bp_group_id( false, false), __( 'Group Wire', 'buddypress' ), sprintf( __( 'There are no wire posts for %s', 'buddypress' ), bp_group_name(false) ), bp_group_is_member(), true ) ?>

						<?php endif; ?>
					</div>

				</div>
			</div>

		<?php endwhile; endif; ?>

	</div>

<?php get_footer() ?>