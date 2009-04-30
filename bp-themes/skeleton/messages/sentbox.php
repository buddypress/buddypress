<?php
/*
 * /messages/sentbox.php
 * Displays all the message threads in the "Sent Messages" box for a user.
 *
 * Loaded on URL:
 * 'http://example.org/members/[username]/messages/sentbox/
 */
?>

<?php get_header() ?>

<div class="content-header">
	<div class="messages-options">	
		<?php bp_messages_options() ?>
	</div>
</div>

<div id="main">	
	
	<?php do_action( 'template_notices' ) ?>
		
	<h2><?php _e("Sent Messages", "buddypress"); ?></h2>

	<?php if ( bp_has_message_threads() ) : ?>
		
		<div class="pagination-links">
			<?php bp_messages_pagination() ?>
		</div>
		
		<table id="message-threads">
		<?php while ( bp_message_threads() ) : bp_message_thread(); ?>
			
			<tr id="m-<?php bp_message_thread_id() ?>">
				
				<td></td>
				
				<td><?php bp_message_thread_avatar() ?></td>
				
				<td>
					<p><?php _e("To:", "buddypress"); ?> <?php bp_message_thread_to() ?></p>
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
			<p><?php _e("You have no sent messages.", "buddypress"); ?></p>
		</div>	

	<?php endif;?>

</div>

<?php get_footer() ?>