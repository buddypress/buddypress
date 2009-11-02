<?php get_header() ?>

	<div class="content-header">
		<ul class="content-header-nav">
			<?php bp_groups_header_tabs() ?>
		</ul>
	</div>

	<div id="content">

		<h2><?php bp_word_or_name( __( "My Groups", 'buddypress' ), __( "%s's Groups", 'buddypress' ) ) ?> &raquo; <?php bp_groups_filter_title() ?></h2>

		<?php do_action( 'bp_before_my_groups_content' ) ?>

		<div class="left-menu">
			<?php bp_group_search_form() ?>
		</div>

		<div class="main-column">
			<?php do_action( 'template_notices' ) // (error/success feedback) ?>

			<?php locate_template( array( 'groups/group-loop.php' ), true ) ?>
		</div>

		<?php do_action( 'bp_after_my_groups_content' ) ?>

	</div>

<?php get_footer() ?>