<?php
/***
 * Deprecated Core Functionality
 *
 * This file contains functions that are deprecated.
 * You should not under any circumstance use these functions as they are
 * either no longer valid, or have been replaced with something much more awesome.
 *
 * If you are using functions in this file you should slap the back of your head
 * and then use the functions or solutions that have replaced them.
 * Most functions contain a note telling you what you should be doing or using instead.
 *
 * Of course, things will still work if you use these functions but you will
 * be the laughing stock of the BuddyPress community. We will all point and laugh at
 * you. You'll also be making things harder for yourself in the long run,
 * and you will miss out on lovely performance and functionality improvements.
 *
 * If you've checked you are not using any deprecated functions and finished your little
 * dance, you can add the following line to your wp-config.php file to prevent any of
 * these old functions from being loaded:
 *
 * define( 'BP_IGNORE_DEPRECATED', true );
 */

function bp_core_deprecated_globals() {
	global $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;

	$bp->core->image_base = BP_PLUGIN_URL . '/bp-core/deprecated/images';
}
add_action( 'plugins_loaded', 'bp_core_deprecated_globals', 3 );
add_action( '_admin_menu', 'bp_core_deprecated_globals', 3 ); // must be _admin_menu hook.


/*** BEGIN DEPRECATED SIGNUP FUNCTIONS **********/

/***
 * Instead of duplicating the WPMU signup functions as in previous versions
 * of BuddyPress, all signup functionality is now in the template for easier
 * customization. Check out the default theme in the file 'register.php'.
 */

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

	<form id="setupform" method="post" action="<?php echo site_url(BP_REGISTER_SLUG) ?>">
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

	<form id="setupform" method="post" action="<?php echo site_url(BP_REGISTER_SLUG) ?>">
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
	<form id="setupform" method="post" action="<?php echo site_url(BP_REGISTER_SLUG) ?>">
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

	<h3><?php _e( 'Still waiting for your email?', 'buddypress' ); ?></h3>
	<p>
		<?php _e( "If you haven't received your email yet, there are a number of things you can do:", 'buddypress' ) ?>
		<ul>
			<li><p><strong><?php _e( 'Wait a little longer.  Sometimes delivery of email can be delayed by processes outside of our control.', 'buddypress' ) ?></strong></p></li>
			<li><p><?php _e( 'Check the junk email or spam folder of your email client.  Sometime emails wind up there by mistake.', 'buddypress' ) ?></p></li>
			<li><?php printf( __( "Have you entered your email correctly?  We think it's %s but if you've entered it incorrectly, you won't receive it.", 'buddypress' ), $user_email) ?></li>
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
		$login_url = site_url( 'wp-login.php?redirect_to=' . site_url(BP_REGISTER_SLUG) );
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

function bp_core_activation_set_headers() {
	global $wp_object_cache;

	define( "WP_INSTALLING", true );

	require_once( ABSPATH . WPINC . '/registration.php');

	if( is_object( $wp_object_cache ) )
		$wp_object_cache->cache_enabled = false;

	do_action("activate_header");
}

function bp_core_activation_do_activation() {
	global $current_site, $blog_id, $user_id; ?>

	<?php if ( empty( $_GET['key'] ) && empty( $_POST['key'] ) ) { ?>

		<h3><?php _e( 'Activation Key Required', 'buddypress' ) ?></h3>

		<p id="intro-text"><?php _e( 'This is the key contained in the email you were sent after registering for this site.', 'buddypress' ) ?></p>

		<div class="field-box">
			<form name="activateform" id="activateform" method="post" action="<?php echo 'http://' . $current_site->domain . $current_site->path ?>wp-activate.php">
				<p>
				    <label for="key"><?php _e('Activation Key:', 'buddypress' ) ?></label>
				    <br /><input type="text" name="key" id="key" value="" size="50" />
				</p>
				<p class="submit">
				    <input id="submit" type="submit" name="Submit" class="submit" value="<?php _e('Activate &raquo;', 'buddypress' ) ?>"/>
				</p>
			</form>
		</div>

	<?php } else {

		$key = !empty($_GET['key']) ? $_GET['key'] : $_POST['key'];
		$result = wpmu_activate_signup($key);

		if ( is_wp_error($result) ) {
			if ( 'already_active' == $result->get_error_code() || 'blog_taken' == $result->get_error_code() ) {
			    $signup = $result->get_error_data();
				?>

				<h3><?php _e('Your account is now active!', 'buddypress' ); ?></h3>

				<?php
			  	_e( 'Your account has already been activated. You can now log in with the account details that were emailed to you.' );

			} else {
				?>
				<h2><?php _e('An error occurred during the activation', 'buddypress' ); ?></h2>
				<?php
			    echo '<p>'.$result->get_error_message().'</p>';
			}
		} else {
			extract($result);

			$user = new WP_User( (int) $user_id);

			?>

			<h3><?php _e('Your account is now active!', 'buddypress' ); ?></h3>

			<p class="view"><?php printf( __( 'Your account is now activated. <a href="%1$s">Login</a> or go back to the <a href="%2$s">homepage</a>.', 'buddypress' ), site_url( 'wp-login.php?redirect_to=' . site_url() ), site_url() ); ?></p>

			<div class="field-box" id="signup-welcome">
				<p><span class="label"><?php _e( 'Username:', 'buddypress' ); ?></span> <?php echo $user->user_login ?></p>
				<p><span class="label"><?php _e( 'Password:', 'buddypress' ); ?></span> <?php echo $password; ?></p>
			</div>

			<?php
			do_action( 'bp_activation_extras', $user_id, $meta );
		}
	}
}

/*** END DEPRECATED SIGNUP FUNCTIONS **********/


/* DEPRECATED - use bp_core_new_nav_item() as it's more friendly and allows ordering */
function bp_core_add_nav_item( $name, $slug, $css_id = false, $show_for_displayed_user = true ) {
	bp_core_new_nav_item( array( 'name' => $name, 'slug' => $slug, 'item_css_id' => $css_id, 'show_for_displayed_user' => $show_for_displayed_user ) );
}

/* DEPRECATED - use bp_core_new_subnav_item() as it's more friendly and allows ordering. */
function bp_core_add_subnav_item( $parent_id, $slug, $name, $link, $function, $css_id = false, $user_has_access = true, $admin_only = false ) {
	bp_core_new_subnav_item( array( 'name' => $name, 'slug' => $slug, 'parent_slug' => $parent_id, 'parent_url' => $link, 'item_css_id' => $css_id, 'user_has_access' => $user_has_access, 'site_admin_only' => $admin_only, 'screen_function' => $function ) );
}

/* DEPRECATED - use bp_core_get_userid() */
function bp_core_get_userid_from_user_login( $deprecated ) {
	return bp_core_get_userid( $deprecated );
}

/* DEPRECATED - use bp_core_get_user_displayname() */
function bp_core_global_user_fullname( $user_id ) { return bp_core_get_user_displayname( $user_id ); }

/* DEPRECATED use bp_core_fetch_avatar() */
function bp_core_get_avatar( $user, $version = 1, $width = null, $height = null, $no_tag = false ) {
	$type = ( 2 == $version ) ? 'full' : 'thumb';
	return bp_core_fetch_avatar( array( 'item_id' => $user, 'type' => $type, 'width' => $width, 'height' => $height ) );
}

/* DEPRECATED - use bp_displayed_user_avatar( 'size=full' ) */
function bp_the_avatar() {
	global $bp;
	echo apply_filters( 'bp_the_avatar', bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'full' ) ) );
}

/* DEPRECATED - use bp_displayed_user_avatar( 'size=thumb' ) */
function bp_the_avatar_thumbnail() {
	global $bp;
	echo apply_filters( 'bp_the_avatar_thumbnail', bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb' ) ) );
}

/* DEPRECATED - use bp_loggedin_user_avatar( 'type=full' ); */
function bp_loggedinuser_avatar( $width = false, $height = false ) {
	global $bp;

	echo apply_filters( 'bp_loggedinuser_avatar', bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => 'thumb', 'width' => $width, 'height' => $height ) ) );
}

/* DEPRECATED - use bp_loggedin_user_avatar( 'type=thumb' ); */
function bp_loggedinuser_avatar_thumbnail( $width = false, $height = false ) {
	global $bp;

	echo apply_filters( 'bp_get_options_avatar', bp_core_fetch_avatar( array( 'item_id' => $bp->loggedin_user->id, 'type' => 'thumb', 'width' => $width, 'height' => $height ) ) );
}

/* DEPRECATED - use bp_core_get_user_displayname( $user_id ) */
function bp_fetch_user_fullname( $user_id, $echo = true ) {
	if ( $echo )
		echo apply_filters( 'bp_fetch_user_fullname', bp_core_get_user_displayname( $user_id ) );
	else
		return apply_filters( 'bp_fetch_user_fullname', bp_core_get_user_displayname( $user_id ) );
}

/*** BEGIN OLD AVATAR CROPPING SUPPORT ****************************************/

/* DEPRECATED - constant values that are no longer used. */
define( 'CORE_AVATAR_V1_W', apply_filters( 'bp_core_avatar_v1_w', 50 ) );
define( 'CORE_AVATAR_V1_H', apply_filters( 'bp_core_avatar_v1_h', 50 ) );
define( 'CORE_AVATAR_V2_W', apply_filters( 'bp_core_avatar_v2_w', 150 ) );
define( 'CORE_AVATAR_V2_H', apply_filters( 'bp_core_avatar_v2_h', 150 ) );
define( 'CORE_CROPPING_CANVAS_MAX', apply_filters( 'bp_core_avatar_cropping_canvas_max', 450 ) );
define( 'CORE_MAX_FILE_SIZE', get_site_option('fileupload_maxk') * 1024 );
define( 'CORE_DEFAULT_AVATAR', apply_filters( 'bp_core_avatar_default_src', BP_PLUGIN_URL . '/bp-xprofile/images/none.gif' ) );
define( 'CORE_DEFAULT_AVATAR_THUMB', apply_filters( 'bp_core_avatar_default_thumb_src', BP_PLUGIN_URL . '/bp-xprofile/images/none-thumbnail.gif' ) );

/* DEPRECATED - this is handled via a screen function. See xprofile_screen_change_avatar() */
function bp_core_avatar_admin( $message = null, $action, $delete_action) { ?>
	<p><?php _e('Your avatar will be used on your profile and throughout the site.', 'buddypress') ?></p>
	<p><?php _e( 'Click below to select a JPG, GIF or PNG format photo from your computer and then click \'Upload Photo\' to proceed.', 'buddypress' ) ?></p>

	<form action="" method="post" id="avatar-upload-form" enctype="multipart/form-data">

	<?php if ( 'upload-image' == bp_get_avatar_admin_step() ) : ?>

		<h3><?php _e( 'Your Current Avatar', 'buddypress' ) ?></h3>

		<?php bp_displayed_user_avatar( 'type=full') ?>
		<?php bp_displayed_user_avatar( 'type=thumb' ) ?>

		<p>
			<input type="file" name="file" id="file" />
			<input type="submit" name="upload" id="upload" value="<?php _e( 'Upload Image', 'buddypress' ) ?>" />
			<input type="hidden" name="action" id="action" value="bp_avatar_upload" />
		</p>

		<?php wp_nonce_field( 'bp_avatar_upload' ) ?>

	<?php endif; ?>

	<?php if ( 'crop-image' == bp_get_avatar_admin_step() ) : ?>

		<h3><?php _e( 'Crop Your New Avatar', 'buddypress' ) ?></h3>

		<img src="<?php bp_avatar_to_crop() ?>" id="avatar-to-crop" class="avatar" alt="<?php _e( 'Avatar to crop', 'buddypress' ) ?>" />

		<div id="avatar-crop-pane" style="width:100px;height:100px;overflow:hidden;">
			<img src="<?php bp_avatar_to_crop() ?>" id="avatar-crop-preview" class="avatar" alt="<?php _e( 'Avatar preview', 'buddypress' ) ?>" />
		</div>

		<input type="submit" name="avatar-crop-submit" id="avatar-crop-submit" value="<?php _e( 'Crop Image', 'buddypress' ) ?>" />

		<input type="hidden" name="image_src" id="image_src" value="<?php bp_avatar_to_crop_src() ?>" />
		<input type="hidden" id="x" name="x" />
		<input type="hidden" id="y" name="y" />
		<input type="hidden" id="w" name="w" />
		<input type="hidden" id="h" name="h" />

		<?php wp_nonce_field( 'bp_avatar_cropstore' ) ?>

	<?php endif; ?>

	</form> <?php
}

function bp_core_handle_avatar_upload($file) {
	global $wp_upload_error;

	require_once( ABSPATH . '/wp-admin/includes/file.php' );

	// Change the upload file location to /avatars/user_id
	add_filter( 'upload_dir', 'xprofile_avatar_upload_dir' );

	$res = wp_handle_upload( $file['file'], array('action'=>'slick_avatars') );

	if ( !in_array('error', array_keys($res) ) ) {
		return $res['file'];
	} else {
		$wp_upload_error = $res['error'];
		return false;
	}
}

function bp_core_resize_avatar( $file, $size = false ) {
	require_once( ABSPATH . '/wp-admin/includes/image.php' );

	if ( !$size )
		$size = CORE_CROPPING_CANVAS_MAX;

	$canvas = wp_create_thumbnail( $file, $size );

	if ( $canvas->errors )
		return false;

	return $canvas = str_replace( '//', '/', $canvas );
}

/*** END OLD AVATAR CROPPING SUPPORT **************************/


/*** BEGIN DEPRECATED OLD BUDDYPRESS THEME SUPPORT ************/

/***
 * In older versions of BuddyPress, BuddyPress templates were in a separate theme.
 * The child theme setup makes upgrades and extending themes much easier, so the
 * old method was deprecated.
 */

function bp_core_get_buddypress_themes() {
	global $wp_themes;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;

	/* Remove the cached WP themes first */
	$wp_existing_themes = &$wp_themes;
	$wp_themes = null;

	add_filter( 'theme_root', 'bp_core_filter_buddypress_theme_root' );
	$themes = get_themes();

	if ( $themes ) {
		foreach ( $themes as $name => $values ) {
			if ( $name == 'BuddyPress Default Home Theme' )
				continue;

			$member_themes[] = array(
				'name' => $name,
				'template' => $values['Template'],
				'version' => $values['Version']
			);
		}
	}

	/* Restore the cached WP themes */
	$wp_themes = $wp_existing_themes;

	return $member_themes;
}
function bp_core_get_member_themes() { return bp_core_get_buddypress_themes(); } // DEPRECATED

function bp_get_buddypress_theme_uri() {
	return apply_filters( 'bp_get_buddypress_theme_uri', WP_CONTENT_URL . '/bp-themes/' . get_site_option( 'active-member-theme' ) );
}

function bp_get_buddypress_theme_path() {
	return apply_filters( 'bp_get_buddypress_theme_path', WP_CONTENT_DIR . '/bp-themes/' . get_site_option( 'active-member-theme' ) );
}

function bp_core_filter_buddypress_theme_root() {
	return apply_filters( 'bp_core_filter_buddypress_theme_root', WP_CONTENT_DIR . "/bp-themes" );
}

function bp_core_filter_buddypress_theme_root_uri() {
	return apply_filters( 'bp_core_filter_buddypress_theme_root_uri', WP_CONTENT_URL . '/bp-themes' );
}

function bp_core_force_buddypress_theme( $template ) {
	global $is_member_page, $bp;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;

	if ( $is_member_page ) {
		$member_theme = get_site_option( 'active-member-theme' );

		if ( empty( $member_theme ) )
			$member_theme = 'bpmember';

		add_filter( 'theme_root', 'bp_core_filter_buddypress_theme_root' );
		add_filter( 'theme_root_uri', 'bp_core_filter_buddypress_theme_root_uri' );

		return $member_theme;
	} else {
		return $template;
	}
}
add_filter( 'template', 'bp_core_force_buddypress_theme' );

function bp_core_force_buddypress_stylesheet( $stylesheet ) {
	global $is_member_page;

	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $stylesheet;

	$member_theme = get_site_option( 'active-member-theme' );

	if ( empty( $member_theme ) )
		$member_theme = 'bpmember';

	if ( $is_member_page ) {
		add_filter( 'theme_root', 'bp_core_filter_buddypress_theme_root' );
		add_filter( 'theme_root_uri', 'bp_core_filter_buddypress_theme_root_uri' );

		return $member_theme;
	} else {
		return $stylesheet;
	}
}
add_filter( 'stylesheet', 'bp_core_force_buddypress_stylesheet' );


/* DEPRECATED - All CSS is in the theme in BP 1.1+ */
function bp_core_add_structure_css() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;

	/* Enqueue the structure CSS file to give basic positional formatting for components */
	wp_enqueue_style( 'bp-core-structure', BP_PLUGIN_URL . '/bp-core/deprecated/css/structure.css' );
}
add_action( 'bp_styles', 'bp_core_add_structure_css' );

/* DEPRECATED - All CSS is now in the theme */
function bp_core_add_css() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;

	// Enable a sitewide CSS file that will apply styles to both the home blog theme
	// and the member theme.
	if ( file_exists( WP_CONTENT_DIR . '/themes/' . get_blog_option( BP_ROOT_BLOG, 'stylesheet' ) . '/css/site-wide.css' ) )
		wp_enqueue_style( 'site-wide-styles', WP_CONTENT_URL . '/themes/' . get_blog_option( BP_ROOT_BLOG, 'stylesheet' ) . '/css/site-wide.css' );

	wp_print_styles();
}
add_action( 'wp_head', 'bp_core_add_css', 2 );

/* DEPRECATED - Admin bar CSS is now in the theme */
function bp_core_admin_bar_css() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;

	if ( defined( 'BP_DISABLE_ADMIN_BAR') )
		return false;

	if ( is_user_logged_in() || ( !(int)get_site_option( 'hide-loggedout-adminbar' ) && !is_user_logged_in() ) ) {
		wp_enqueue_style( 'bp-admin-bar', apply_filters( 'bp_core_admin_bar_css', BP_PLUGIN_URL . '/bp-core/deprecated/css/admin-bar.css' ) );

		if ( 'rtl' == get_bloginfo('text_direction') && file_exists( BP_PLUGIN_DIR . '/bp-core/deprecated/css/admin-bar-rtl.css' ) )
			wp_enqueue_style( 'bp-admin-bar-rtl', BP_PLUGIN_URL . '/bp-core/deprecated/css/admin-bar-rtl.css' );
	}
}
add_action( 'wp_head', 'bp_core_admin_bar_css' );
add_action( 'admin_menu', 'bp_core_admin_bar_css' );

/* DEPRECATED - Javascript is added by the theme on a per-theme basis. */
function bp_core_add_js() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-livequery-pack', BP_PLUGIN_URL . '/bp-core/deprecated/js/jquery/jquery.livequery.pack.js', 'jquery' );
	wp_enqueue_script( 'bp-general-js', BP_PLUGIN_URL . '/bp-core/deprecated/js/general.js' );
}
add_action( 'wp', 'bp_core_add_js' );
add_action( 'admin_menu', 'bp_core_add_js' );

function bp_core_directory_members_js() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return $template;

	wp_enqueue_script( 'bp-core-directory-members', BP_PLUGIN_URL . '/bp-core/deprecated/js/directory-members.js', array( 'jquery', 'jquery-livequery-pack' ) );
}
add_action( 'bp_core_action_directory_members', 'bp_core_directory_members_js' );

/*** END DEPRECATED OLD BUDDYPRESS THEME SUPPORT ************/

function bp_core_ajax_directory_members() {
	/* If we are using a BuddyPress 1.1+ theme ignore this. */
	if ( !file_exists( WP_CONTENT_DIR . '/bp-themes' ) )
		return false;

	check_ajax_referer('directory_members');

	locate_template( array( 'directories/members/members-loop.php' ), true );
}
add_action( 'wp_ajax_directory_members', 'bp_core_ajax_directory_members' );

/* DEPRECATED -- This should now be directly in the template */
function bp_login_bar() {
	global $bp;

	if ( !is_user_logged_in() ) : ?>

		<form name="login-form" id="login-form" action="<?php echo $bp->root_domain . '/wp-login.php' ?>" method="post">
			<input type="text" name="log" id="user_login" value="<?php _e( 'Username', 'buddypress' ) ?>" onfocus="if (this.value == '<?php _e( 'Username', 'buddypress' ) ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php _e( 'Username', 'buddypress' ) ?>';}" />
			<input type="password" name="pwd" id="user_pass" class="input" value="" />

			<input type="checkbox" name="rememberme" id="rememberme" value="forever" title="<?php _e( 'Remember Me', 'buddypress' ) ?>" />

			<input type="submit" name="wp-submit" id="wp-submit" value="<?php _e( 'Log In', 'buddypress' ) ?>"/>
			<input type="button" name="signup-submit" id="signup-submit" value="<?php _e( 'Sign Up', 'buddypress' ) ?>" onclick="location.href='<?php echo bp_signup_page() ?>'" />

			<input type="hidden" name="redirect_to" value="http://<?php echo $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] ?>" />
			<input type="hidden" name="testcookie" value="1" />

			<?php do_action( 'bp_login_bar_logged_out' ) ?>
		</form>

	<?php else : ?>

		<div id="logout-link">
			<?php bp_loggedinuser_avatar_thumbnail( 20, 20 ) ?> &nbsp;
			<?php bp_loggedinuser_link() ?>
			<?php
				if ( function_exists('wp_logout_url') ) {
					$logout_link = '/ <a href="' . wp_logout_url( $bp->root_domain ) . '">' . __( 'Log Out', 'buddypress' ) . '</a>';
				} else {
					$logout_link = '/ <a href="' . $bp->root_domain . '/wp-login.php?action=logout&amp;redirect_to=' . $bp->root_domain . '">' . __( 'Log Out', 'buddypress' ) . '</a>';
				}

				echo apply_filters( 'bp_logout_link', $logout_link );
			?>

			<?php do_action( 'bp_login_bar_logged_in' ) ?>
		</div>

	<?php endif;
}

/* DEPRECATED - use the param 'default_subnav_slug' in bp_core_new_nav_item() OR bp_core_new_nav_default() */
function bp_core_add_nav_default( $parent_id, $function, $slug = false, $user_has_access = true, $admin_only = false ) {
	global $bp;

	if ( !$user_has_access && !bp_is_home() )
		return false;

	if ( $admin_only && !is_site_admin() )
		return false;

	if ( $bp->current_component == $parent_id && !$bp->current_action ) {
		if ( function_exists($function) ) {
			add_action( 'wp', $function, 3 );
		}

		if ( $slug )
			$bp->current_action = $slug;
	}
}

/* DEPRECATED - use <?php locate_template( array( 'userbar.php' ), true ) ?> */
function bp_get_userbar( $hide_on_directory = true ) {
	global $bp;

	if ( $hide_on_directory && $bp->is_directory )
		return false;

	load_template( TEMPLATEPATH . '/userbar.php' );
}

/* DEPRECATED - use <?php locate_template( array( 'optionsbar.php' ), true ) ?> */
function bp_get_optionsbar( $hide_on_directory = true ) {
	global $bp;

	if ( $hide_on_directory && $bp->is_directory )
		return false;

	load_template( TEMPLATEPATH . '/optionsbar.php' );
}

