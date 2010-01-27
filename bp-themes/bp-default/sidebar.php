<?php do_action( 'bp_before_sidebar' ) ?>

<div id="sidebar">
	<div class="padder">

	<?php do_action( 'bp_inside_before_sidebar' ) ?>

	<?php if ( is_user_logged_in() ) : ?>

		<?php do_action( 'bp_before_sidebar_me' ) ?>

		<div id="sidebar-me">
			<a href="<?php echo bp_loggedin_user_domain() ?>">
				<?php bp_loggedin_user_avatar( 'type=thumb&width=40&height=40' ) ?>
			</a>

			<h4><?php bp_loggedinuser_link() ?></h4>
			<a class="button" href="<?php echo wp_logout_url(wp_get_referer()) ?>"><?php _e( 'Log Out', 'buddypress' ) ?></a>

			<?php do_action( 'bp_sidebar_me' ) ?>
		</div>

		<?php do_action( 'bp_after_sidebar_me' ) ?>

		<?php if ( function_exists( 'bp_message_get_notices' ) ) : ?>
			<?php bp_message_get_notices(); /* Site wide notices to all users */ ?>
		<?php endif; ?>

	<?php else : ?>

		<?php do_action( 'bp_before_sidebar_login_form' ) ?>

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

			<?php do_action( 'bp_sidebar_login_form' ) ?>

			<input type="submit" name="wp-submit" id="userbar_wp-submit" value="<?php _e('Log In'); ?>" tabindex="100" />
			<input type="hidden" name="redirect_to" value="<?php bp_root_domain() ?>" />
			<input type="hidden" name="testcookie" value="1" />
		</form>

		<?php do_action( 'bp_after_sidebar_login_form' ) ?>

	<?php endif; ?>

	<?php /* Show forum tags on the forums directory */
	if ( BP_FORUMS_SLUG == bp_current_component() && bp_is_directory() ) : ?>
		<div id="forum-directory-tags" class="widget tags">

			<h3 class="widgettitle"><?php _e( 'Forum Topic Tags', 'buddypress' ) ?></h3>
			<?php if ( function_exists('bp_forums_tag_heat_map') ) : ?>
				<div id="tag-text"><?php bp_forums_tag_heat_map(); ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php dynamic_sidebar( 'sidebar' ) ?>

	<?php do_action( 'bp_inside_after_sidebar' ) ?>

	</div><!-- .padder -->
</div><!-- #sidebar -->

<?php do_action( 'bp_after_sidebar' ) ?>
