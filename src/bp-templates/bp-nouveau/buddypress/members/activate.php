<?php
/**
 * BuddyPress - Members Activate
 *
 * @since 3.0.0
 * @version 3.2.0
 */
?>

	<?php bp_nouveau_activation_hook( 'before', 'page' ); ?>

	<div class="page" id="activate-page">

		<?php bp_nouveau_template_notices(); ?>

		<?php bp_nouveau_activation_hook( 'before', 'content' ); ?>

		<?php if ( bp_account_was_activated() ) : ?>

			<?php if ( isset( $_GET['e'] ) ) : ?>
				<p><?php esc_html_e( 'Your account was activated successfully! Your account details have been sent to you in a separate email.', 'buddypress' ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'Your account was activated successfully! You can now log in with the username and password you provided when you signed up.', 'buddypress' ); ?></p>
			<?php endif; ?>

			<?php
			printf(
				'<p><a href="%1$s">%2$s</a></p>',
				esc_url( wp_login_url( bp_get_root_domain() ) ),
				esc_html__( 'Log In', 'buddypress' )
			);
			?>

		<?php else : ?>

			<p><?php esc_html_e( 'Please provide a valid activation key.', 'buddypress' ); ?></p>

			<form action="" method="post" class="standard-form" id="activation-form">

				<label for="key"><?php esc_html_e( 'Activation Key:', 'buddypress' ); ?></label>
				<input type="text" name="key" id="key" value="<?php echo esc_attr( bp_get_current_activation_key() ); ?>" />

				<p class="submit">
					<input type="submit" name="submit" value="<?php echo esc_attr_x( 'Activate', 'button', 'buddypress' ); ?>" />
				</p>

			</form>

		<?php endif; ?>

		<?php bp_nouveau_activation_hook( 'after', 'content' ); ?>

	</div><!-- .page -->

	<?php bp_nouveau_activation_hook( 'after', 'page' ); ?>
