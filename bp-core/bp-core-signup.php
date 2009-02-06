<?php

function bp_core_signup_set_headers() {
	add_action( 'wp_head', 'bp_core_signup_register_headers' ) ;
	require_once( ABSPATH . WPINC . '/registration.php' );

	if( is_array( get_site_option( 'illegal_names' )) && $_GET[ 'new' ] != '' && in_array( $_GET[ 'new' ], get_site_option( 'illegal_names' ) ) == true ) {
		wp_redirect( "http://{$current_site->domain}{$current_site->path}" );
		die();
	}
}

function bp_core_signup_do_headers() {
	do_action("signup_header");
}
add_action( 'wp_head', 'bp_core_signup_do_headers' );

function bp_core_signup_register_headers() {
	echo "<meta name='robots' content='noindex,nofollow' />\n";
}

function bp_core_signup_show_blog_form( $blogname = '', $blog_title = '', $errors = '' ) {
	global $current_site;
	
	?>
	<h3><?php _e( 'Blog Details', 'buddypress' ) ?></h3>
	<p id="blog-details-help">
		<?php _e( "To register your first blog, just fill in the details below and your registration is complete.", 'buddypress' ) ?>
	</p>
	
	<div id="blog-details-fields">
		<?php
	
		// Blog name
		if ( 'no' == constant( "VHOST" ) )
			echo '<label for="blogname">' . __('Blog Name:', 'buddypress') . '</label>';
		else
			echo '<label for="blogname">' . __('Blog Domain:', 'buddypress') . '</label>';

		if ( $errmsg = $errors->get_error_message('blogname') ) { ?>
			<p class="error"><?php echo $errmsg ?></p>
		<?php }

		if ( 'no' == constant( "VHOST" ) ) {
			echo '<span class="prefix_address">' . $current_site->domain . $current_site->path . '</span><input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /><br />';
		} else {
			echo '<input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /><span class="suffix_address">.' . $current_site->domain . $current_site->path . '</span><br />';
		}
		
		if ( !is_user_logged_in() ) {
			echo '<p class="help-text">';
			print '(<strong>' . __( 'Your address will be ', 'buddypress'  );
			if( 'no' == constant( "VHOST" ) ) {
				print $current_site->domain . $current_site->path . __( 'blogname', 'buddypress'  );
			} else {
				print __( 'domain.', 'buddypress'  ) . $current_site->domain . $current_site->path;
			}
			echo '</strong>. ' . __( 'Must be at least 4 characters, letters and numbers only. It cannot be changed so choose carefully!)', 'buddypress'  ) . '</p>';
			echo '</p>';
		}

		// Blog Title
		?>
		<label for="blog_title"><?php _e( 'Blog Title:', 'buddypress' ) ?></label>	
		<?php if ( $errmsg = $errors->get_error_message('blog_title') ) { ?>
			<p class="error"><?php echo $errmsg ?></p>
		<?php }
		echo '<input name="blog_title" type="text" id="blog_title" value="'.wp_specialchars($blog_title, 1).'" /></p>';
		?>

		<p>
			<label for="blog_public_on"><?php _e( 'Privacy:', 'buddypress' ) ?></label>
			<?php _e( 'I would like my blog to appear in search engines like Google and Technorati, and in public listings around this site.', 'buddypress' ); ?> 
			<label class="checkbox" for="blog_public_on">
				<input type="radio" id="blog_public_on" name="blog_public" value="1" <?php if( !isset( $_POST['blog_public'] ) || '1' == $_POST['blog_public'] ) { ?>checked="checked"<?php } ?> />
				 &nbsp;<?php _e( 'Yes', 'buddypress' ); ?>
			</label>
			<label class="checkbox" for="blog_public_off">
				<input type="radio" id="blog_public_off" name="blog_public" value="0" <?php if( isset( $_POST['blog_public'] ) && '0' == $_POST['blog_public'] ) { ?>checked="checked"<?php } ?> />
				 &nbsp;<?php _e( 'No', 'buddypress' ); ?>
			</label>
		</p>
	</div>
	<?php
	do_action('signup_blogform', $errors);
}

function bp_core_signup_validate_blog_form() {
	$user = '';
	if ( is_user_logged_in() )
		$user = wp_get_current_user();

	return wpmu_validate_blog_signup($_POST['blogname'], $_POST['blog_title'], $user);
}

function bp_core_signup_show_user_form($user_name = '', $user_email = '', $errors = '') {
	// User name
	echo '<div id="account-fields">';
	echo '<label for="user_name">' . __( 'Username:', 'buddypress' ) . '</label>';
	if ( $errmsg = $errors->get_error_message('user_name') ) {
		echo '<p class="error">'.$errmsg.'</p>';
	}
	echo '<input name="user_name" type="text" id="user_name" value="'.$user_name.'" maxlength="50" />';
	echo '<p class="help-text">';
	_e( '(Must be at least 4 characters, letters and numbers only.)', 'buddypress' );
	echo '</p>'
	?>

	<label for="user_email"><?php _e( 'Email&nbsp;Address:', 'buddypress' ) ?></label>
	<?php if ( $errmsg = $errors->get_error_message('user_email') ) { ?>
		<p class="error"><?php echo $errmsg ?></p>
	<?php } ?>		
	<input name="user_email" type="text" id="user_email" value="<?php  echo wp_specialchars($user_email, 1) ?>" maxlength="200" /><p class="help-text"><?php _e( '(We&#8217;ll send your password to this address, so <strong>triple-check it</strong>.)', 'buddypress' ) ?></p>
	<?php
	if ( $errmsg = $errors->get_error_message('generic') ) {
		echo '<p class="error">'.$errmsg.'</p>';
	}
	echo '</div>';
	
	echo '<div id="extra-fields">';
	do_action( 'signup_extra_fields', $errors );
	echo '</div>';
}

function bp_core_signup_validate_user_form() {
	return wpmu_validate_user_signup($_POST['user_name'], $_POST['user_email']);
}

function bp_core_signup_signup_another_blog($blogname = '', $blog_title = '', $errors = '') {
	global $current_user, $current_site;
	
	if ( ! is_wp_error($errors) ) {
		$errors = new WP_Error();
	}

	// allow definition of default variables
	$filtered_results = apply_filters('signup_another_blog_init', array('blogname' => $blogname, 'blog_title' => $blog_title, 'errors' => $errors ));
	$blogname = $filtered_results['blogname'];
	$blog_title = $filtered_results['blog_title'];
	$errors = $filtered_results['errors'];

	?>
	<h3><?php _e( "You're already registered!", 'buddypress' )?></h3>
	<p><?php _e( 'You can still create another blog however. Fill in the form below to add another blog to your account.', 'buddypress' ) ?>
	 

	<p><?php _e( "There is no limit to the number of blogs you can have, so create to your heart's content, but blog responsibly. If you&#8217;re not going to use a great blog domain, leave it for a new user. Now have at it!", 'buddypress' ) ?></p>
	
	<form id="setupform" method="post" action="<?php echo site_url(REGISTER_SLUG) ?>">
		<input type="hidden" name="stage" value="gimmeanotherblog" />
		<?php do_action( "signup_hidden_fields" ); ?>
		<?php bp_core_signup_show_blog_form($blogname, $blog_title, $errors); ?>
		<p>
			<input id="submit" type="submit" name="submit" class="submit" value="<?php _e('Create Blog &raquo;') ?>"/>
		</p>
	</form>
	<?php
}

function bp_core_signup_validate_another_blog_signup() {
	global $wpdb, $current_user, $blogname, $blog_title, $errors, $domain, $path;
	$current_user = wp_get_current_user();
	if( !is_user_logged_in() )
		die();

	$result = bp_core_signup_validate_blog_form();
	extract($result);

	if ( $errors->get_error_code() ) {
		bp_core_signup_signup_another_blog($blogname, $blog_title, $errors);
		return false;
	}

	$public = (int) $_POST['blog_public'];
	$meta = apply_filters('signup_create_blog_meta', array ('lang_id' => 1, 'public' => $public)); // depreciated
	$meta = apply_filters( "add_signup_meta", $meta );

	wpmu_create_blog( $domain, $path, $blog_title, $current_user->id, $meta, $wpdb->siteid );
	bp_core_signup_confirm_another_blog_signup($domain, $path, $blog_title, $current_user->user_login, $current_user->user_email, $meta);
	return true;
}

function bp_core_signup_confirm_another_blog_signup($domain, $path, $blog_title, $user_name, $user_email = '', $meta = '') {
	?>
	<h2><?php printf( __( 'The blog %s is yours.', 'buddypress' ), "<a href='http://{$domain}{$path}'>{$blog_title}</a>" ) ?></h2>
	<p>
		<?php printf( __( '<a href="http://%1$s">http://%2$s</a> is your new blog.  <a href="%3$s">Login</a> as "%4$s" using your existing password.', 'buddypress' ), $domain.$path, $domain.$path, "http://" . $domain.$path . "/wp-login.php", $user_name) ?>
	</p>
	<?php
	do_action('signup_finished');
}

function bp_core_signup_signup_user($user_name = '', $user_email = '', $errors = '') {
	global $current_site, $active_signup;

	$active_signup = get_site_option( 'registration' );

	if ( !is_wp_error($errors) )
		$errors = new WP_Error();
	if( isset( $_POST[ 'signup_for' ] ) ) {
		$signup[ wp_specialchars( $_POST[ 'signup_for' ] ) ] = 'checked="checked"';
	} else {
		$signup[ 'blog' ] = 'checked="checked"';
	}

	// allow definition of default variables
	$filtered_results = apply_filters('signup_user_init', array('user_name' => $user_name, 'user_email' => $user_email, 'errors' => $errors ));
	$user_name = $filtered_results['user_name'];
	$user_email = $filtered_results['user_email'];
	$errors = $filtered_results['errors'];

	?>
	
	<form id="setupform" method="post" action="<?php echo site_url(REGISTER_SLUG) ?>">
		<p id="intro-text"><?php _e( 'Registering for a new account is easy, just fill in the form below and you\'ll be a new member in no time at all.', 'buddypress' ) ?></p>
		<input type="hidden" name="stage" value="validate-user-signup" />
		<?php do_action( "signup_hidden_fields" ); ?>
		
		<?php bp_core_signup_show_user_form($user_name, $user_email, $errors); ?>
		
		<?php if( 'blog' == $active_signup ) { ?>
			<input id="signupblog" type="hidden" name="signup_for" value="blog" />
		<?php } elseif( 'user' == $active_signup ) { ?>
			<input id="signupblog" type="hidden" name="signup_for" value="user" />
		<?php } else { ?>
			<div id="blog-or-username">
				<h3><?php _e( 'Create a Blog?', 'buddypress' ) ?></h3>
				<p id="blog-help-text"><?php _e( 'If you want to create your first blog, select the option below and you\'ll be asked for a few more details.', 'buddypress' ) ?></p>
				
				<div id="blog-or-username-fields">
					<p>
						<input id="signupblog" type="radio" name="signup_for" value="blog" <?php echo $signup['blog'] ?> />
						<label class="checkbox" for="signupblog"><?php _e( 'Gimme a blog!', 'buddypress' ) ?></label>			
					</p>
				
					<p>
						<input id="signupuser" type="radio" name="signup_for" value="user" <?php echo $signup['user'] ?> />			
						<label class="checkbox" for="signupuser"><?php _e( 'Just a username, please.', 'buddypress' ) ?></label>
					</p>
				</div>
			</div>
		<?php } ?>

		<input id="submit" type="submit" name="submit" class="submit" value="<?php _e('Next &raquo;') ?>"/>
	</form>
	<?php
}

function bp_core_signup_validate_user_signup() {
	$result = bp_core_signup_validate_user_form();
	extract($result);

	if ( $errors->get_error_code() ) {
		bp_core_signup_signup_user($user_name, $user_email, $errors);
		return false;
	}

	if ( 'blog' == $_POST['signup_for'] ) {
		bp_core_signup_signup_blog($user_name, $user_email);
		return false;
	}

	wpmu_signup_user($user_name, $user_email, apply_filters( "add_signup_meta", array() ) );

	bp_core_signup_confirm_user_signup($user_name, $user_email);
	return true;
}

function bp_core_signup_confirm_user_signup($user_name, $user_email) {
	?>
	<h3><?php _e( 'Congratulations, you are now registered!', 'buddypress' ) ?></h3>
	<p><?php printf(__('Your new username is: %s', 'buddypress' ), $user_name) ?></p>
	<p>&nbsp;</p>
	<p><?php printf(__('Before you can start using your new username, <strong>you must activate it</strong>. Check your inbox at <strong>%1$s</strong> and click the link given.', 'buddypress' ),  $user_email) ?></p>
	<p><?php _e('If you do not activate your username within two days, you will have to sign up again.', 'buddypress' ); ?></p>
	<?php
	do_action('signup_finished');
}

function bp_core_signup_signup_blog($user_name = '', $user_email = '', $blogname = '', $blog_title = '', $errors = '') {
	if ( !is_wp_error($errors) )
		$errors = new WP_Error();

	// allow definition of default variables
	$filtered_results = apply_filters('signup_blog_init', array('user_name' => $user_name, 'user_email' => $user_email, 'blogname' => $blogname, 'blog_title' => $blog_title, 'errors' => $errors ));
	$user_name = $filtered_results['user_name'];
	$user_email = $filtered_results['user_email'];
	$blogname = $filtered_results['blogname'];
	$blog_title = $filtered_results['blog_title'];
	$errors = $filtered_results['errors'];

	if ( empty($blogname) )
		$blogname = $user_name;
	?>
	<form id="setupform" method="post" action="<?php echo site_url(REGISTER_SLUG) ?>">
		<input type="hidden" name="stage" value="validate-blog-signup" />
		<input type="hidden" name="user_name" value="<?php echo $user_name ?>" />
		<input type="hidden" name="user_email" value="<?php echo $user_email ?>" />
		<?php do_action( "signup_hidden_fields" ); ?>
		<?php bp_core_signup_show_blog_form($blogname, $blog_title, $errors); ?>
		<p>
			<input id="submit" type="submit" name="submit" class="submit" value="<?php _e('Signup &raquo;') ?>"/></p>
	</form>
	<?php
}

function bp_core_signup_validate_blog_signup() {
	// Re-validate user info.
	$result = wpmu_validate_user_signup($_POST['user_name'], $_POST['user_email']);
	extract($result);

	if ( $errors->get_error_code() ) {
		bp_core_signup_signup_user($user_name, $user_email, $errors);
		return false;
	}

	$result = wpmu_validate_blog_signup($_POST['blogname'], $_POST['blog_title']);
	extract($result);

	if ( $errors->get_error_code() ) {
		bp_core_signup_signup_blog($user_name, $user_email, $blogname, $blog_title, $errors);
		return false;
	}

	$public = (int) $_POST['blog_public'];
	$meta = array ('lang_id' => 1, 'public' => $public);
	$meta = apply_filters( "add_signup_meta", $meta );

	wpmu_signup_blog($domain, $path, $blog_title, $user_name, $user_email, $meta);
	bp_core_signup_confirm_blog_signup($domain, $path, $blog_title, $user_name, $user_email, $meta);
	return true;
}

function bp_core_signup_confirm_blog_signup($domain, $path, $blog_title, $user_name = '', $user_email = '', $meta) {
	?>
	<h3><?php _e('Congratulations, You are now registered!', 'buddypress' ) ?></h3>
	
	<p><?php printf( __('But, before you can start using your blog, <strong>you must activate it</strong>. Check your inbox at <strong>%s</strong> and click the link given. It should arrive within 30 minutes.', 'buddypress' ),  $user_email) ?></p>
	<p>&nbsp;</p>
	
	<h3><?php _e('Still waiting for your email?'); ?></h3>
	<p>
		<?php _e("If you haven't received your email yet, there are a number of things you can do:") ?>
		<ul>
			<li><p><strong><?php _e('Wait a little longer.  Sometimes delivery of email can be delayed by processes outside of our control.', 'buddypress' ) ?></strong></p></li>
			<li><p><?php _e('Check the junk email or spam folder of your email client.  Sometime emails wind up there by mistake.', 'buddypress' ) ?></p></li>
			<li><?php printf(__("Have you entered your email correctly?  We think it's %s but if you've entered it incorrectly, you won't receive it.", 'buddypress' ), $user_email) ?></li>
		</ul>
	</p>
	<?php
	do_action('signup_finished');
}

function bp_core_signup_do_signup() {
	// Main
	$active_signup = get_site_option( 'registration' );
	if( !$active_signup )
		$active_signup = 'all';

	$active_signup = apply_filters( 'wpmu_active_signup', $active_signup ); // return "all", "none", "blog" or "user"

	if( is_site_admin() )
		echo '<div class="mu_alert">' . sprintf( __( "Greetings Site Administrator! You are currently allowing '%s' registrations. To change or disable registration go to your <a href='wp-admin/wpmu-options.php'>Options page</a>.", 'buddypress' ), $active_signup ) . '</div>';

	$newblogname = isset($_GET['new']) ? strtolower(preg_replace('/^-|-$|[^-a-zA-Z0-9]/', '', $_GET['new'])) : null;

	$current_user = wp_get_current_user();
	if( $active_signup == "none" ) {
		_e( "Registration has been disabled.", 'buddypress' );
	} elseif( $active_signup == 'blog' && !is_user_logged_in() ){
		if( is_ssl() ) {
			$proto = 'https://';
		} else {
			$proto = 'http://';
		}
		$login_url = site_url( 'wp-login.php?redirect_to=' . site_url(REGISTER_SLUG) );
		echo sprintf( __( "You must first <a href=\"%s\">login</a>, and then you can create a new blog.", 'buddypress' ), $login_url );
	} else {
		switch ($_POST['stage']) {
			case 'validate-user-signup' :
				if( $active_signup == 'all' || $_POST[ 'signup_for' ] == 'blog' && $active_signup == 'blog' || $_POST[ 'signup_for' ] == 'user' && $active_signup == 'user' )
					bp_core_signup_validate_user_signup();
				else
					_e( "User registration has been disabled.", 'buddypress' );
			break;
			case 'validate-blog-signup':
				if( $active_signup == 'all' || $active_signup == 'blog' )
					bp_core_signup_validate_blog_signup();
				else
					_e( "Blog registration has been disabled.", 'buddypress' );
				break;
			case 'gimmeanotherblog':
				bp_core_signup_validate_another_blog_signup();
				break;
			default :
				$user_email = $_POST[ 'user_email' ];
				do_action( "preprocess_signup_form" ); // populate the form from invites, elsewhere?
				if ( is_user_logged_in() && ( $active_signup == 'all' || $active_signup == 'blog' ) ) {
					bp_core_signup_signup_another_blog($newblogname);
				} elseif( is_user_logged_in() == false && ( $active_signup == 'all' || $active_signup == 'user' ) ) {
					bp_core_signup_signup_user( $newblogname, $user_email );
				} elseif( is_user_logged_in() == false && ( $active_signup == 'blog' ) ) {
					_e( "I'm sorry. We're not accepting new registrations at this time.", 'buddypress' );
				} else {
					_e( "You're logged in already. No need to register again!", 'buddypress' );
				}
				if ($newblogname) {
					if( constant( "VHOST" ) == 'no' )
						$newblog = 'http://' . $current_site->domain . $current_site->path . $newblogname . '/';
					else
						$newblog = 'http://' . $newblogname . '.' . $current_site->domain . $current_site->path;
					if ($active_signup == 'blog' || $active_signup == 'all')
						printf( __( "<p><em>The blog you were looking for, <strong>%s</strong> doesn't exist but you can create it now!</em></p>", 'buddypress' ), $newblog );
					else
						printf( __( "<p><em>The blog you were looking for, <strong>%s</strong> doesn't exist.</em></p>", 'buddypress' ), $newblog );
				}
				break;
		}
	}
}
