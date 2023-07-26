<?php
/**
 * BuddyPress - Blogs Confirm
 *
 * @since 12.0.0
 * @version 12.0.0
 */

bp_nouveau_template_notices(); ?>

<?php bp_nouveau_blogs_confirm_hook( 'before', 'content' ); ?>

<?php if ( bp_blog_signup_enabled() ) : ?>

	<p class="success"><?php esc_html_e( 'Congratulations! You have successfully registered a new site.', 'buddypress' ) ?></p>
	<p>
		<?php printf(
			'%s %s',
			sprintf(
				/* translators: %s: the link of the new site */
				__( '%s is your new site.', 'buddypress' ),
				sprintf( '<a href="%s">%s</a>', esc_url( $args['blog_url'] ), esc_url( $args['blog_url'] ) )
			),
			sprintf(
				/* translators: 1: Login URL, 2: User name */
				__( '<a href="%1$s">Log in</a> as "%2$s" using your existing password.', 'buddypress' ),
				esc_url( $args['login_url'] ),
				esc_html( $args['user_name'] )
			)
		); ?>
	</p>

<?php
else :

	bp_nouveau_user_feedback( 'blogs-no-signup' );

endif;
?>

<?php
bp_nouveau_blogs_confirm_hook( 'after', 'content' );
