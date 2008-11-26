<?php get_header() ?>

<div class="content-header">
	
</div>

<div id="content">
	<?php do_action( 'template_notices' ) ?>

	<?php bp_message_thread_view() ?>
</div>

<?php get_footer() ?>