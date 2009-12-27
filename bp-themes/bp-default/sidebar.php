<?php do_action( 'bp_before_sidebar' ) ?>

<div id="sidebar">
	<div class="padder">

	<?php do_action( 'bp_inside_before_sidebar' ) ?>

	<?php if ( is_user_logged_in() ) : ?>

		<div id="sidebar-me">
			<?php bp_loggedin_user_avatar( 'type=thumb&width=40&height=40' ) ?>
			<h3><?php bp_loggedinuser_link() ?></h3>
			<a class="button" href="<?php echo wp_logout_url(wp_get_referer()) ?>"><?php _e( 'Log Out', 'buddypress' ) ?></a>
		</div>

	<?php else : ?>

		<p id="login-text">
			<?php _e( 'To start connecting please log in first.', 'buddypress' ) ?>
			<?php if ( bp_get_signup_allowed() ) : ?>
				<?php printf( __( ' You can also <a href="%s" title="Create an account">create an account</a>.', 'buddypress' ), site_url( BP_REGISTER_SLUG . '/' ) ) ?>
			<?php endif; ?>
		</p>

		<form name="login-form" id="login-form" class="standard-form" action="<?php echo site_url( 'wp-login.php', 'login' ) ?>" method="post">
			<label><?php _e( 'Username', 'buddypress' ) ?><br />
			<input type="text" name="log" id="userbar_user_login" class="input" value="<?php echo attribute_escape(stripslashes($user_login)); ?>" /></label>

			<label><?php _e( 'Password', 'buddypress' ) ?><br />
			<input type="password" name="pwd" id="userbar_user_pass" class="input" value="" /></label>

			<p class="forgetmenot"><label><input name="rememberme" type="checkbox" id="userbar_rememberme" value="forever" /> <?php _e( 'Remember Me', 'buddypress' ) ?></label></p>

			<input type="submit" name="wp-submit" id="userbar_wp-submit" value="<?php _e('Log In'); ?>" tabindex="100" />
			<input type="hidden" name="redirect_to" value="<?php echo bp_root_domain() ?>" />
			<input type="hidden" name="testcookie" value="1" />
		</form>

	<?php endif; ?>


	<?php if ( !dynamic_sidebar( 'sidebar' ) ) : ?>


	<?php endif; ?>

	<?php do_action( 'bp_inside_after_sidebar' ) ?>

	</div><!-- .padder -->
</div><!-- #sidebar -->

<?php do_action( 'bp_after_sidebar' ) ?>
