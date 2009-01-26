<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="http://gmpg.org/xfn/11">

	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<title><?php bp_page_title() ?></title>
	<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" /> <!-- leave this for stats -->

	<?php bp_styles(); ?>
	
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
	
	<?php wp_head(); ?>
	
	<!--[if IE 6]>
	<link rel="stylesheet" href="<?php echo bloginfo('template_url') . '/css/ie/ie6.css' ?>" type="text/css" media="screen" />	
	<![endif]-->
	
	<!--[if IE 7]>
	<link rel="stylesheet" href="<?php echo bloginfo('template_url') . '/css/ie/ie7.css' ?>" type="text/css" media="screen" />	
	<![endif]-->
</head>

<body>

<div id="search-login-bar">
	<?php bp_search_form() ?>
	<?php bp_login_bar() ?>
	
	<div class="clear"></div>
</div>

<div id="header">
	<h1 id="logo"><a href="<?php echo get_option('home') ?>" title="<?php _e( 'Home', 'buddypress' ) ?>"><?php bp_site_name() ?></a></h1>
	
	<ul id="nav">
		<li<?php if(bp_is_page('home')) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>" title="<?php _e( 'Home', 'buddypress' ) ?>"><?php _e( 'Home', 'buddypress' ) ?></a></li>
		<li<?php if(bp_is_page('news')) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>/news" title="<?php _e( 'News', 'buddypress' ) ?>"><?php _e( 'News', 'buddypress' ) ?></a></li>
		<li<?php if(bp_is_page(MEMBERS_SLUG)) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>/<?php echo MEMBERS_SLUG ?>" title="<?php _e( 'Members', 'buddypress' ) ?>"><?php _e( 'Members', 'buddypress' ) ?></a></li>
		
		<?php if ( function_exists('groups_install') ) { ?>
			<li<?php if(bp_is_page('groups')) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>/groups" title="<?php _e( 'Groups', 'buddypress' ) ?>"><?php _e( 'Groups', 'buddypress' ) ?></a></li>
		<?php } ?>
		
		<?php if ( function_exists('bp_blogs_install') ) { ?>
			<li<?php if(bp_is_page('blogs')) {?> class="selected"<?php } ?>><a href="<?php echo get_option('home') ?>/blogs" title="<?php _e( 'Blogs', 'buddypress' ) ?>"><?php _e( 'Blogs', 'buddypress' ) ?></a></li>
		<?php } ?>

		<?php do_action( 'bp_nav_items' ) ?>
	</ul>
</div>

<?php include_once( TEMPLATEPATH . '/userbar.php' ) ?>
<?php include_once( TEMPLATEPATH . '/optionsbar.php' ) ?>

<div id="main">
