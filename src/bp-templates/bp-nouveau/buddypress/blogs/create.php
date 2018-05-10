<?php
/**
 * BuddyPress - Blogs Create
 *
 * @since 3.0.0
 * @version 3.0.0
 */

bp_nouveau_blogs_create_hook( 'before', 'content_template' ); ?>

<?php bp_nouveau_template_notices(); ?>

<?php bp_nouveau_blogs_create_hook( 'before', 'content' ); ?>

<?php if ( bp_blog_signup_enabled() ) : ?>

	<?php bp_show_blog_signup_form(); ?>

<?php
else :

	bp_nouveau_user_feedback( 'blogs-no-signup' );

endif;
?>

<?php
bp_nouveau_blogs_create_hook( 'after', 'content' );

bp_nouveau_blogs_create_hook( 'after', 'content_template' );
