<?php get_header() ?>

	<div class="content-header">
		<?php do_action('bp_template_content_header') ?>
	</div>

	<div id="content">
		<h2><?php do_action('bp_template_title') ?></h2>
	
		<?php do_action('bp_template_content') ?>
	</div>

<?php get_footer() ?>