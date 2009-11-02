<?php get_header() ?>

	<div class="content-header">
		<?php _e( 'Create a Blog', 'buddypress' ) ?>
	</div>

	<div id="content">

		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<h2><?php _e( 'Create a Blog', 'buddypress' ) ?></h2>

		<?php do_action( 'bp_before_create_blog_content' ) ?>

		<?php if ( bp_blog_signup_enabled() ) : ?>

			<?php bp_show_blog_signup_form() ?>

		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e( 'Blog registration is currently disabled', 'buddypress' ); ?></p>
			</div>

		<?php endif; ?>

		<?php do_action( 'bp_after_create_blog_content' ) ?>

	</div>

<?php get_footer() ?>

