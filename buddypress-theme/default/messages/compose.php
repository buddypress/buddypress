<?php get_header(); ?>

	<div class="content-header">
		Compose Message
	</div>

	<div id="content">
		<?php bp_get_callback_message() ?>
		<?php bp_compose_message_form() ?>
	</div>
	
<?php get_footer() ?>