<?php
/*
 * 404.php
 * Shown when a user requests an incorrect URL, or they try to access something they don't
 * have access to (and BP doesn't auto-redirect them back to their profile)
 */
?>

<?php get_header() ?>

<div class="content-header">
	<?php _e( 'Permission Denied', 'buddypress' ) ?>
</div>

<div id="main">
	<h2><?php _e( 'Not Found / No Access', 'buddypress' ) ?></h2>
	<p><?php _e( 'The page you are looking for either does not exist, or you do not have the permissions to access it.', 'buddypress' ) ?></p>
</div>

<?php get_footer() ?>