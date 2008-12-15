<div id="userbar">
	<h3><?php _e( 'Me', 'buddypress' ) ?></h3>
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
			<img src="<?php echo site_url( 'wp-content/mu-plugins/bp-core/images/mystery-man.jpg' ) ?>" alt="No User" width="50" height="50" />
		</p>
		
		<p id="login-text"><?php _e( 'You must log in to access your account.', 'buddypress' ) ?></p>
	
		<form name="loginform" id="loginform" action="<?php echo site_url('wp-login.php') ?>" method="post">
			<p>
				<label><?php _e( 'Username', 'buddypress' ) ?><br />
				<input type="text" name="log" id="user_login" class="input" value="<?php echo attribute_escape(stripslashes($user_login)); ?>" /></label>
			</p>
			<p>
				<label><?php _e( 'Password', 'buddypress' ) ?><br />
				<input type="password" name="pwd" id="user_pass" class="input" value="" /></label>
			</p>
			<p class="forgetmenot"><label><input name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php _e( 'Remember Me', 'buddypress' ) ?></label></p>
			<p class="submit">
				<input type="submit" name="wp-submit" id="wp-submit" value="<?php _e('Log In'); ?>" tabindex="100" />
				<input type="hidden" name="redirect_to" value="http://<?php echo $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] ?>?nr" />
				<input type="hidden" name="testcookie" value="1" />
			</p>
		</form>
	
	<?php endif ?>

	<div class="clear"></div>
</div>