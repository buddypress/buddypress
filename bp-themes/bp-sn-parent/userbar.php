<?php do_action( 'bp_before_user_bar' ) ?>

<div id="userbar">
	<h3><?php _e( 'Me', 'buddypress' ) ?></h3>

	<?php do_action( 'bp_inside_before_user_bar' ) ?>
	
	<?php if ( is_user_logged_in() ) : ?>
		
		<p class="avatar">
			<?php bp_loggedin_user_avatar( 'type=thumb' ) ?>
		</p>
		
		<ul id="bp-nav">
			<?php bp_get_loggedin_user_nav() ?>
		</ul>
		
	<?php else : ?>
		
		<p class="avatar">
			<img src="<?php echo get_template_directory_uri() . '/_inc/images/mystery-man.jpg' ?>" alt="No User" width="50" height="50" />
		</p>
		
		<p id="login-text"><?php _e( 'You must log in to access your account.', 'buddypress' ) ?></p>
	
		<form name="userbar_loginform" id="userbar_loginform" action="<?php echo site_url( 'wp-login.php', 'login' ) ?>" method="post">
			<p>
				<label><?php _e( 'Username', 'buddypress' ) ?><br />
				<input type="text" name="log" id="userbar_user_login" class="input" value="<?php echo attribute_escape(stripslashes($user_login)); ?>" /></label>
			</p>
			<p>
				<label><?php _e( 'Password', 'buddypress' ) ?><br />
				<input type="password" name="pwd" id="userbar_user_pass" class="input" value="" /></label>
			</p>
			<p class="forgetmenot"><label><input name="rememberme" type="checkbox" id="userbar_rememberme" value="forever" /> <?php _e( 'Remember Me', 'buddypress' ) ?></label></p>
			<p class="submit">
				<input type="submit" name="wp-submit" id="userbar_wp-submit" value="<?php _e('Log In'); ?>" tabindex="100" />
				<input type="hidden" name="redirect_to" value="<?php echo bp_root_domain() ?>" />
				<input type="hidden" name="testcookie" value="1" />
			</p>
		</form>
	
	<?php endif ?>
	
	<?php do_action( 'bp_inside_after_user_bar' ) ?>

</div>

<?php do_action( 'bp_after_user_bar' ) ?>
