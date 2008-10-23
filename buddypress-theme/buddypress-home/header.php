<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />

<title><?php bloginfo('name'); ?> <?php if ( is_single() ) { ?> &raquo; Blog Archive <?php } ?> <?php wp_title(); ?></title>

<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" /> <!-- leave this for stats -->

<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

<?php wp_head(); ?>

</head>
<body>
	
	<div id="search-login-bar">
		<!--
		<form action="" method="post" id="search-form">
			<input type="text" id="search-terms" name="search-terms" value="Search everything" />
			<input type="submit" name="search-submit" id="search-submit" value="Search" />
		</form>
		-->
		
		<?php if ( !is_user_logged_in() ) : ?>
			<form name="login-form" id="login-form" action="<?php get_option('home') ?>/wp-login.php" method="post">
				<input type="text" name="log" id="user_login" value="Username" onfocus="if (this.value == 'Username') {this.value = '';}" onblur="if (this.value == '') {this.value = 'Username';}" />
				<input type="password" name="pwd" id="user_pass" class="input" value="" />
				<!--<input name="rememberme" type="checkbox" id="rememberme" value="forever" />-->
				<input type="submit" name="wp-submit" id="wp-submit" value="Log In" />				
				<input type="button" name="signup-submit" id="signup-submit" value="Signup" onclick="location.href='<?php echo site_url() . '/wp-signup.php' ?>'" />
				<input type="hidden" name="redirect_to" value="http://<?php echo $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] ?>" />
				<input type="hidden" name="testcookie" value="1" />
			</form>
		<?php else : ?>
			<div id="logout-link">
				<?php bp_loggedinuser_avatar_thumbnail( 20, 20 ) ?> &nbsp;
				<?php bp_loggedinuser_link() ?> 
				/ <a href="<?php echo site_url() . '/wp-login.php?action=logout' ?>">Log Out</a>
			</div>
		<?php endif; ?>
		<div class="clear"></div>
	</div>

	<div id="header">		
		<h1 id="logo">Social Network</h1>
		
		<ul id="nav">
			<li<?php if(bp_is_page('home')) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>" title="Home">Home</a></li>
			<li<?php if(bp_is_page('news')) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>/news" title="News">News</a></li>
			<li<?php if(bp_is_page('members')) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>/members" title="Members">Members</a></li>
			<!--
			<li><a href="<?php echo get_option('home') ?>/groups" title="Groups">Groups</a></li>
			<li><a href="<?php echo get_option('home') ?>/blogs" title="Blogs">Blogs</a></li>
			<li><a href="<?php echo get_option('home') ?>/forum" title="Forum">Forum</a></li>
			-->
		</ul>
		
		<div class="clear"></div>
	</div>
	
	<div class="clear"></div>