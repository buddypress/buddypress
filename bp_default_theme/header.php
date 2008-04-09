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

<div id="page">

<div id="header">

	<p><img src="<?php echo get_template_directory_uri(); ?>/images/bp_logo.gif" alt="BuddyPress" /></p>
	<ul id="menu">
		<li><a href="<?php echo bp_get_homeurl(); ?>">Home</a></li>
		<li><a href="<?php echo bloginfo('home'); ?>">My Profile</a></li>
		<li><a href="<?php echo bloginfo('home'); ?>/wp-admin/admin.php?page=xprofile_Basic">- Edit</a></li>
		<li><a href="<?php echo bloginfo('home'); ?>/blog">My Blog</a></li>
		<li><a href="<?php echo bloginfo('home'); ?>/wp-admin/post-new.php">- Post</a></li>
		<?php if(is_site_admin()) { ?><li><a href="">Settings</a></li><?php } ?>
	</ul>

</div>
<hr />

<?php get_sidebar(); ?>