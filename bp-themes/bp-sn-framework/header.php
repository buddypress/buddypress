<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

	<head profile="http://gmpg.org/xfn/11">

		<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />

		<title><?php bp_page_title() ?></title>

		<?php do_action( 'bp_head' ) ?>

		<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" /> <!-- leave this for stats -->

		<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />

		<?php if ( function_exists( 'bp_sitewide_activity_feed_link' ) ) : ?>
			<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> <?php _e('Site Wide Activity RSS Feed', 'buddypress' ) ?>" href="<?php bp_sitewide_activity_feed_link() ?>" />
		<?php endif; ?>

		<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> <?php _e( 'Blog Posts RSS Feed', 'buddypress' ) ?>" href="<?php bloginfo('rss2_url'); ?>" />
		<link rel="alternate" type="application/atom+xml" title="<?php bloginfo('name'); ?> <?php _e( 'Blog Posts Atom Feed', 'buddypress' ) ?>" href="<?php bloginfo('atom_url'); ?>" />

		<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

		<?php wp_head(); ?>

	</head>

	<body class="<?php do_action( 'bp_body_class' ) ?>">
		
		<div id="search-login-bar">
	
			<form action="<?php echo bp_search_form_action() ?>" method="post" id="search-form">
				<input type="text" id="search-terms" name="search-terms" value="" /> 
				<?php echo bp_search_form_type_select() ?>
	
				<input type="submit" name="search-submit" id="search-submit" value="<?php _e( 'Search', 'buddypress' ) ?>" />
				<?php wp_nonce_field( 'bp_search_form' ) ?>
			</form>
			
			<?php if ( !is_user_logged_in() ) : ?>
		
				<form name="login-form" id="login-form" action="<?php echo $bp->root_domain . '/wp-login.php' ?>" method="post">
					<input type="text" name="log" id="user_login" value="<?php _e( 'Username', 'buddypress' ) ?>" onfocus="if (this.value == '<?php _e( 'Username', 'buddypress' ) ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php _e( 'Username', 'buddypress' ) ?>';}" />
					<input type="password" name="pwd" id="user_pass" class="input" value="" />
			
					<input type="checkbox" name="rememberme" id="rememberme" value="forever" title="<?php _e( 'Remember Me', 'buddypress' ) ?>" />
			
					<input type="submit" name="wp-submit" id="wp-submit" value="<?php _e( 'Log In', 'buddypress' ) ?>"/>		
					
					<?php if ( 'none' != bp_get_signup_allowed() && 'blog' != bp_get_signup_allowed() ) : ?>		
						<input type="button" name="signup-submit" id="signup-submit" value="<?php _e( 'Sign Up', 'buddypress' ) ?>" onclick="location.href='<?php echo bp_signup_page() ?>'" />
					<?php endif; ?>
					
					<input type="hidden" name="redirect_to" value="<?php bp_root_domain() ?>" />
					<input type="hidden" name="testcookie" value="1" />
						
					<?php do_action( 'bp_login_bar_logged_out' ) ?>
				</form>
	
			<?php else : ?>
		
				<div id="logout-link">
					<?php bp_loggedin_user_avatar( 'width=20&height=20' ) ?> &nbsp; <?php bp_loggedinuser_link() ?> / <?php bp_log_out_link() ?>
					
					<?php do_action( 'bp_login_bar_logged_in' ) ?>
				</div>
		
			<?php endif; ?>

		</div>
		
		<?php do_action( 'bp_before_header' ) ?>		

		<div id="header">	
		
			<h1 id="logo"><a href="<?php echo get_option('home') ?>" title="<?php _e( 'Home', 'buddypress' ) ?>"><?php bp_site_name() ?></a></h1>
	
			<ul id="nav">
				<li<?php if ( bp_is_page( 'home' ) ) : ?> class="selected"<?php endif; ?>><a href="<?php echo get_option('home') ?>" title="<?php _e( 'Home', 'buddypress' ) ?>"><?php _e( 'Home', 'buddypress' ) ?></a></li>
				<li<?php if ( bp_is_page( BP_HOME_BLOG_SLUG ) ) : ?> class="selected"<?php endif; ?>><a href="<?php echo get_option('home') ?>/<?php echo BP_HOME_BLOG_SLUG ?>" title="<?php _e( 'Blog', 'buddypress' ) ?>"><?php _e( 'Blog', 'buddypress' ) ?></a></li>
				<li<?php if ( bp_is_page( BP_MEMBERS_SLUG ) ) : ?> class="selected"<?php endif; ?>><a href="<?php echo get_option('home') ?>/<?php echo BP_MEMBERS_SLUG ?>" title="<?php _e( 'Members', 'buddypress' ) ?>"><?php _e( 'Members', 'buddypress' ) ?></a></li>

				<?php if ( function_exists( 'groups_install' ) ) : ?>
					<li<?php if ( bp_is_page( BP_GROUPS_SLUG ) ) : ?> class="selected"<?php endif; ?>><a href="<?php echo get_option('home') ?>/<?php echo BP_GROUPS_SLUG ?>" title="<?php _e( 'Groups', 'buddypress' ) ?>"><?php _e( 'Groups', 'buddypress' ) ?></a></li>
				<?php endif; ?>

				<?php if ( function_exists( 'bp_blogs_install' ) ) : ?>
					<li<?php if ( bp_is_page( BP_BLOGS_SLUG ) ) : ?> class="selected"<?php endif; ?>><a href="<?php echo get_option('home') ?>/<?php echo BP_BLOGS_SLUG ?>" title="<?php _e( 'Blogs', 'buddypress' ) ?>"><?php _e( 'Blogs', 'buddypress' ) ?></a></li>
				<?php endif; ?>

				<?php do_action( 'bp_nav_items' ); ?>
			</ul>

			<?php do_action( 'bp_header' ) ?>
	
		</div>

		<?php do_action( 'bp_after_header' ) ?>
		<?php do_action( 'bp_before_container' ) ?>
		
		<div id="container">
	
			<?php if ( !bp_is_blog_page() && !bp_is_directory() && !bp_is_register_page() && !bp_is_activation_page() ) : ?>
		
				<?php load_template( TEMPLATEPATH . '/userbar.php' ) /* Load the user navigation */ ?>
				<?php load_template( TEMPLATEPATH . '/optionsbar.php' ) /* Load the currently displayed object navigation */ ?>			
		
			<?php endif; ?>
			