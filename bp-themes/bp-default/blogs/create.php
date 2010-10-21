<?php get_header() ?>

	<?php do_action( 'bp_before_directory_blogs_content' ) ?>

	<div id="content">
		<div class="padder">

		<?php do_action( 'template_notices' ) ?>

		<h3><?php _e( 'Create a Blog', 'buddypress' ) ?> &nbsp;<a class="button" href="<?php echo bp_get_root_domain() . '/' . BP_BLOGS_SLUG . '/' ?>"><?php _e( 'Blogs Directory', 'buddypress' ) ?></a></h3>

		<?php do_action( 'bp_before_create_blog_content' ) ?>

		<?php if ( bp_blog_signup_enabled() ) : ?>

			<?php bp_show_blog_signup_form() ?>

		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e( 'Blog registration is currently disabled', 'buddypress' ); ?></p>
			</div>

		<?php endif; ?>

		<?php do_action( 'bp_after_create_blog_content' ) ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

	<?php do_action( 'bp_after_directory_blogs_content' ) ?>

<?php get_footer() ?>

