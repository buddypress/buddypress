<div class="content-header">
	You belong to <?php bp_total_group_count() ?> groups.
</div>

<div id="content">
	<h2><?php bp_word_or_name( __( "My Groups", 'buddypress' ), __( "%s's Groups", 'buddypress' ) ) ?></h2>
	
	<div class="left-menu">
		<?php bp_group_search_form() ?>
	</div>
	
	<div class="main-column">
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>
		
 		<?php load_template( TEMPLATEPATH . '/groups/group-loop.php') ?>
	
	</div>
</div>