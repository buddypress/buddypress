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

<body<?php if ( bp_is_blog() ) : ?> class="in-blog"<?php endif; ?>>

<div id="header">
	<h1><a href="<?php echo get_option('home'); ?>/">BuddyPress</a></h1>
	
	<div class="search">
		<?php include_once (TEMPLATEPATH . '/searchform.php'); ?>
	</div>
</div>

<?php include_once (TEMPLATEPATH . '/userbar.php'); ?>
<?php include_once (TEMPLATEPATH . '/optionsbar.php'); ?>

<?php if ( bp_is_blog() ) : ?>
	<?php get_sidebar() ?>
<?php endif; ?>

<div id="main">
