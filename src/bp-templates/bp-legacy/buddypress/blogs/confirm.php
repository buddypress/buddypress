<?php
/**
 * BuddyPress - Blogs Confirm
 *
 * @since 12.0.0
 * @version 12.0.0
 */
?>

<div id="buddypress">

	<div id="template-notices" role="alert" aria-atomic="true">
		<?php

		/** This action is documented in bp-templates/bp-legacy/buddypress/activity/index.php */
		do_action( 'template_notices' ); ?>

	</div>

	<?php
	/**
	 * Fires before the display of the blog creation confirmation message.
	 *
	 * @since 12.0.0
	 */
	do_action( 'bp_before_blog_confirmed_content' ); ?>

	<?php if ( bp_blog_signup_enabled() ) : ?>

		<p class="success"><?php esc_html_e( 'Congratulations! You have successfully registered a new site.', 'buddypress' ) ?></p>
		<p>
			<?php printf(
				'%s %s',
				sprintf(
					/* translators: %s: the link of the new site */
					esc_html__( '%s is your new site.', 'buddypress' ),
					sprintf( '<a href="%s">%s</a>', esc_url( $args['blog_url'] ), esc_url( $args['blog_url'] ) )
				),
				sprintf(
					/* translators: 1: Login link, 2: User name */
					esc_html__( '%1$s as "%2$s" using your existing password.', 'buddypress' ),
					'<a href="' . esc_url( $args['login_url'] ) . '">' . esc_html__( 'Log in', 'buddypress' ) . '</a>',
					esc_html( $args['user_name'] )
				)
			); ?>
		</p>

	<?php else : ?>

		<div id="message" class="info">
			<p><?php esc_html_e( 'Site registration is currently disabled', 'buddypress' ); ?></p>
		</div>

	<?php endif; ?>

	<?php
	/**
	 * Fires before the display of the blog creation confirmation message.
	 *
	 * @since 12.0.0
	 */
	do_action( 'bp_after_blog_confirmed_content' ); ?>

</div>
