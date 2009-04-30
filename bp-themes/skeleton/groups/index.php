<?php
/*
 * /groups/index.php
 * Displays a list of groups the user has joined. Also sets up and displays the
 * ordering tabs and the group filter search form.
 * 
 * Loads: '/groups/group-loop.php' (the loop for showing user groups)
 *
 * Loaded on URL:
 * 'http://example.org/groups/[username]/groups/
 * 'http://example.org/groups/[username]/groups/my-groups/
 */
?>

<?php get_header() ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_groups_header_tabs() ?>
	</ul>
</div>

<div id="main">
	<h2><?php bp_word_or_name( __( "My Groups", 'buddypress' ), __( "%s's Groups", 'buddypress' ) ) ?> &raquo; <?php bp_groups_filter_title() ?></h2>
	
	<div class="page-menu">
		<?php bp_group_search_form() ?>
	</div>
	
	<div class="main-column">
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>
		
 		<?php load_template( TEMPLATEPATH . '/groups/group-loop.php') ?>
	</div>
</div>

<?php get_footer() ?>