<?php
/*
 * /messages/index.php
 * Displays all the message threads in the INBOX for a user.
 *
 * Loaded on URL:
 * 'http://example.org/members/[username]/messages/
 * 'http://example.org/members/[username]/messages/inbox/
 */
?>

<?php get_header() ?>

<form action="<?php bp_messages_form_action() ?>" method="post" id="messages-form">

	<div class="content-header">
		<div class="messages-options">	
			<?php bp_messages_options() ?>
		</div>
	</div>

	<div id="main">

		<?php do_action( 'template_notices' ) ?>
		
		<h2><?php _e( "Inbox", "buddypress" ); ?></h2>
	
		<?php bp_message_get_notices(); ?>

		<?php if ( bp_has_message_threads() ) : ?>
		
			<div class="pagination-links">
				<?php bp_messages_pagination() ?>
			</div>
		
			<table id="message-threads">
			<?php while ( bp_message_threads() ) : bp_message_thread(); ?>
			
				<tr id="m-<?php bp_message_thread_id() ?>"<?php if ( bp_message_thread_has_unread() ) : ?> class="unread"<?php else: ?> class="read"<?php endif; ?>>
					<td>
						<span class="unread-count"><?php bp_message_thread_unread_count() ?></span>
					</td>
				
					<td><?php bp_message_thread_avatar() ?></td>
				
					<td>
						<p><?php _e("From:", "buddypress"); ?> <?php bp_message_thread_from() ?></p>
						<p class="date"><?php bp_message_thread_last_post_date() ?></p>
					</td>
				
					<td>
						<p><a href="<?php bp_message_thread_view_link() ?>" title="<?php _e("View Message", "buddypress"); ?>"><?php bp_message_thread_subject() ?></a></p>
						<p><?php bp_message_thread_excerpt() ?></p>
					</td>
				
					<td>
						<a href="<?php bp_message_thread_delete_link() ?>" title="<?php _e("Delete Message", "buddypress"); ?>" class="delete"><?php _e("Delete", "buddypress"); ?></a> &nbsp; 
						<input type="checkbox" name="message_ids[]" value="<?php bp_message_thread_id() ?>" />
					</td>
				</tr>
			
			<?php endwhile; ?>
			</table>
		
		<?php else: ?>
		
			<div id="message" class="info">
				<p><?php _e( 'You have no messages in your inbox.', 'buddypress' ); ?></p>
			</div>	
		
		<?php endif;?>

	</div>

</form>

<?php get_footer() ?>