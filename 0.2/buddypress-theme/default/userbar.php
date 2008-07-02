<div id="userbar">
	<h3>Me</h3>
	<?php if ( is_user_logged_in() ) : ?>
		
		<?php if ( function_exists('bp_loggedinuser_avatar_thumbnail') ) : ?>
		<p class="avatar">
			<?php bp_loggedinuser_avatar_thumbnail() ?>
		</p>
		<?php endif; ?>
		
		<ul id="bp-nav">
			<?php bp_get_nav() ?>
		</ul>
	
	<?php else : ?>
		
		<p class="avatar">
			<img src="<?php get_option('home') ?>/wp-content/mu-plugins/bp-xprofile/images/none-thumbnail.gif" alt="No User" />
		</p>
		
		<p id="login-text">You must log in to access your account.</p>
	
		<form name="loginform" id="loginform" action="<?php get_option('home') ?>/wp-login.php" method="post">
			<p>
				<label><?php _e('Username') ?><br />
				<input type="text" name="log" id="user_login" class="input" value="<?php echo attribute_escape(stripslashes($user_login)); ?>" /></label>
			</p>
			<p>
				<label><?php _e('Password') ?><br />
				<input type="password" name="pwd" id="user_pass" class="input" value="" /></label>
			</p>
			<p class="forgetmenot"><label><input name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php _e('Remember Me'); ?></label></p>
			<p class="submit">
				<input type="submit" name="wp-submit" id="wp-submit" value="<?php _e('Log In'); ?>" tabindex="100" />
				<input type="hidden" name="redirect_to" value="<?php echo $_SERVER['PHP_SELF'] ?>" />
				<input type="hidden" name="testcookie" value="1" />
			</p>
		</form>
	
	<?php endif ?>

</div>