<?php
/*
 * /messages/view.php
 * Displays an inline message thread. Currently the HTML is not editable in the theme.
 * Each message is wrapped in a div with class 'message-box'. The send reply box
 * is wrapped in a form with the ID 'send-reply'.
 *
 * Loaded on URL:
 * 'http://example.org/members/[username]/messages/view/[message-id]/
 */
?>

<?php get_header() ?>

<div id="main">
	<?php do_action( 'template_notices' ) ?>

	<?php bp_message_thread_view() ?>
</div>

<?php get_footer() ?>