<?php get_header() ?>

<div class="content-header">
	<ul class="content-header-nav">
		<?php bp_group_creation_tabs(); ?>
	</ul>
</div>

<div id="content">	
	<h2>Create a Group <?php bp_group_creation_stage_title() ?></h2>
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>
	
	<?php bp_group_create_form() ?>
	
</div>

<?php get_footer() ?>