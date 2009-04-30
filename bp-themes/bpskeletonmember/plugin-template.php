<?php
/*
 * /plugin-template.php
 * BuddyPress plugins can use this file to display extra content that doesn't need
 * seperate template files. It is basically a way for plugins to get content into your theme
 * without users having to modify the theme to support them.
 */
?>

<?php get_header() ?>

<div class="content-header">
	<?php do_action('bp_template_content_header') ?>
</div>

<div id="main">
	<h2><?php do_action('bp_template_title') ?></h2>
	
	<?php do_action('bp_template_content') ?>
</div>

<?php get_footer() ?>