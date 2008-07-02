<?php get_header(); ?>

	<div class="content-header">
		Compose Message
	</div>

	<div id="content">
		<?php do_action( 'template_notices' ) ?>

		<?php bp_compose_message_form() ?>
	</div>
	
<?php get_footer() ?>