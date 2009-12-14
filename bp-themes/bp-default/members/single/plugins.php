<?php get_header() ?>

	<?php do_action( 'bp_before_directory_members_content' ) ?>

	<div id="content">
		<div class="padder">

			<?php locate_template( array( 'members/single/member-header.php' ), true ) ?>

			<div class="item-list-tabs no-ajax" id="user-subnav">
				<ul>
					<?php bp_get_options_nav() ?>
				</ul>
			</div>

			<?php do_action('bp_template_content') ?>

			<?php do_action( 'bp_directory_members_content' ) ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

	<?php do_action( 'bp_after_directory_members_content' ) ?>

<?php get_footer() ?>
