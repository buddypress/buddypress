<?php

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
			    if( $signup->domain . $signup->path == '' ) {
			    	printf(__('<p class="lead-in">Your account has been activated. You may now <a href="%1$s">log in</a> to the site using your chosen username of "%2$s".  Please check your email inbox at %3$s for your password and login instructions. If you do not receive an email, please check your junk or spam folder. If you still do not receive an email within an hour, you can <a href="%4$s">reset your password</a>.</p>', 'buddypress' ), 'http://' . $current_site->domain . $current_site->path . '/wp-login.php?redirect_to=' . $current_site->domain, $signup->user_login, $signup->user_email, 'http://' . $current_site->domain . $current_site->path . '/wp-login.php?action=lostpassword');
			    } else {
			    	printf(__('<p class="lead-in">Your blog at <a href="%1$s">%2$s</a> is active. You may now login to your blog using your chosen username of "%3$s".  Please check your email inbox at %4$s for your password and login instructions.  If you do not receive an email, please check your junk or spam folder.  If you still do not receive an email within an hour, you can <a href="%5$s">reset your password</a>.</p>', 'buddypress' ), 'http://' . $signup->domain, $signup->domain, $signup->user_login, $signup->user_email, 'http://' . $current_site->domain . $current_site->path . '/wp-login.php?action=lostpassword');
			    }
			} else {
				?>
				<h2><?php _e('An error occurred during the activation', 'buddypress' ); ?></h2>
				<?php
			    echo '<p>'.$result->get_error_message().'</p>';
			}
		} else {
			extract($result);

			$url = get_blogaddress_by_id( (int) $blog_id);
			$user = new WP_User( (int) $user_id);
			
			?>
			
			<h3><?php _e('Your account is now active!', 'buddypress' ); ?></h3>
			
			<?php if( $url != site_url() ) : ?>
				<p class="view"><?php printf(__('Your account is now activated. <a href="%1$s">View your site</a> or <a href="%2$s">Login</a>', 'buddypress' ), $url, $url . 'wp-login.php?redirect_to=' . $current_site->domain ); ?></p>
			<?php else: ?>
				<p class="view"><?php printf( __( 'Your account is now activated. <a href="%1$s">Login</a> or go back to the <a href="%2$s">homepage</a>.', 'buddypress' ), 'http://' . $current_site->domain . $current_site->path . 'wp-login.php?redirect_to=' . $current_site->domain, 'http://' . $current_site->domain . $current_site->path ); ?></p>
			<?php endif; ?>
			
			<div class="field-box" id="signup-welcome">
				<p><span class="label"><?php _e( 'Username:', 'buddypress' ); ?></span> <?php echo $user->user_login ?></p>
				<p><span class="label"><?php _e( 'Password:', 'buddypress' ); ?></span> <?php echo $password; ?></p>
			</div>
			
			<?php 
			do_action( 'bp_activation_extras', $user_id, $meta );
		}
	}
}

// Notify user of signup success.
function bp_core_activation_signup_blog_notification( $domain, $path, $title, $user, $user_email, $key, $meta ) {
	global $current_site;

	// Send email with activation link.
	if ( 'no' == constant( "VHOST" ) ) {
		$activate_url = bp_activation_page( false ) . "?key=$key";
	} else {
		$activate_url = bp_activation_page( false ) ."?key=$key";
	}
	
	$activate_url = clean_url($activate_url);
	$admin_email = get_site_option( "admin_email" );
	
	if ( empty( $admin_email ) )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];
	
	$from_name = ( '' == get_site_option( "site_name" ) ) ? 'WordPress' : wp_specialchars( get_site_option( "site_name" ) );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
	$message = sprintf(__("To activate your blog, please click the following link:\n\n%s\n\nAfter you activate, you will receive *another email* with your login.\n\nAfter you activate, you can visit your blog here:\n\n%s", 'buddypress' ), $activate_url, clean_url("http://{$domain}{$path}" ) );
	$subject = '[' . $from_name . '] ' . sprintf(__('Activate %s', 'buddypress' ), clean_url('http://' . $domain . $path));
	
	wp_mail($user_email, $subject, $message, $message_headers);
	
	// Return false to stop the original WPMU function from continuing
	return false;
}
add_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1, 7 );

function bp_core_activation_signup_user_notification( $user, $user_email, $key, $meta ) {
	global $current_site;

	// Send email with activation link.
	$admin_email = get_site_option( "admin_email" );
	
	if ( empty( $admin_email ) )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];
	
	$from_name = ( '' == get_site_option( "site_name" ) ) ? 'WordPress' : wp_specialchars( get_site_option( "site_name" ) );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
	$message = apply_filters( 'wpmu_signup_user_notification_email', sprintf( __( "To activate your user, please click the following link:\n\n%s\n\nAfter you activate, you will receive *another email* with your login.\n\n", 'buddypress' ), clean_url( bp_activation_page( false ) . "?key=$key" ) ) );
	$subject = apply_filters( 'wpmu_signup_user_notification_subject', sprintf( __(  'Activate %s', 'buddypress' ), $user ) ); 

	wp_mail( $user_email, $subject, $message, $message_headers );
	
	// Return false to stop the original WPMU function from continuing
	return false;
}
add_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 );
