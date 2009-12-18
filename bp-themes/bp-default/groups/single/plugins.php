<?php get_header() ?>

	<?php do_action( 'bp_before_directory_members_content' ) ?>

	<div id="content">
		<div class="padder">
			<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

			<div id="item-header">
				<?php locate_template( array( 'groups/single/group-header.php' ), true ) ?>
			</div>

			<div id="item-nav">
				<div class="item-list-tabs no-ajax" id="user-nav">
					<ul>
						<?php bp_get_options_nav() ?>

						<?php do_action( 'bp_members_directory_member_types' ) ?>
					</ul>
				</div>
			</div>

			<div id="item-body">

				<?php do_action( 'bp_template_content' ) ?>

				<?php do_action( 'bp_directory_members_content' ) ?>

			</div><!-- #item-body -->

			<?php endwhile; endif; ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

	<?php do_action( 'bp_after_directory_members_content' ) ?>

<?php get_footer() ?>