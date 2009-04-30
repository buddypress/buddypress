<?php
/*
 * /blogs/create.php
 * Displays the blog signup form. The HTML not template editable just yet. The signup
 * form has an ID of 'setupform'.
 *
 * Loaded on URL:
 * 'http://example.org/members/[username]/blogs/create/
 */
?>

<?php get_header() ?>

<div id="main">
	
	<h2><?php _e( 'Create a Blog', 'buddypress' ) ?></h2>
	
	<?php do_action( 'template_notices' ) ?>

	<?php if ( bp_blog_signup_enabled() ) : ?>
		
		<?php bp_show_blog_signup_form() ?>
	
	<?php else: ?>

		<div id="message" class="info">
			<p><?php _e( 'Blog registration is currently disabled', 'buddypress' ); ?></p>
		</div>

	<?php endif; ?>

</div>

<?php get_footer() ?>

