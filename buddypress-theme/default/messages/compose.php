<?php get_header(); ?>

	<div class="content-header">
		Compose Message
	</div>

	<div id="content">
		<h2>Compose Message</h2>
		
		<?php do_action( 'template_notices' ) ?>

		<?php bp_compose_message_form() ?>
	</div>
	
<?php get_footer() ?>