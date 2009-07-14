<?php get_header() ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_groups_header_tabs() ?>
	</ul>
</div>

<div id="content">
	<h2><?php bp_word_or_name( __( "My Groups", 'buddypress' ), __( "%s's Groups", 'buddypress' ) ) ?> &raquo; <?php bp_groups_filter_title() ?></h2>
	
	<div class="left-menu">
		<?php bp_group_search_form() ?>
	</div>
	
	<div class="main-column">
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>
		
		<?php load_template( TEMPLATEPATH . '/groups/group-loop.php' )?>
	</div>
</div>

<?php get_footer() ?>