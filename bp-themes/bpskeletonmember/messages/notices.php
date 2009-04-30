<?php
/*
 * /messages/notices.php
 * Displays a list of all the created notices by a site admin. They can then 
 * activate/deactivate them.
 *
 * Loaded on URL:
 * 'http://example.org/members/[username]/messages/notices
 */
?>

<?php get_header() ?>

<div id="main">

	<?php do_action( 'template_notices' ) ?>
	
	<div class="pagination-links">
		<?php bp_messages_pagination() ?>
	</div>
	
	<h2><?php _e("Sent Notices", "buddypress"); ?></h2>

	<?php if ( bp_has_message_threads() ) : ?>
		
		<table id="message-threads" class="notices">
		<?php while ( bp_message_threads() ) : bp_message_thread(); ?>
			
			<tr>
				<td></td>
				
				<td>
					<p><strong><?php bp_message_notice_subject() ?></strong></p>
					<p><?php bp_message_notice_text() ?></p>
				</td>
				
				<td>
					<p><?php bp_message_is_active_notice() ?></p>
					<p class="date"><?php _e("Sent:", "buddypress"); ?> <?php bp_message_notice_post_date() ?></p>
				</td>
				
				<td>
					<a href="<?php bp_message_activate_deactivate_link() ?>"><?php bp_message_activate_deactivate_text() ?></a> 
					<a href="<?php bp_message_notice_delete_link() ?>" title="<?php _e("Delete Message", "buddypress"); ?>"><?php _e("Delete", "buddypress"); ?></a> 
				</td>
			</tr>
			
		<?php endwhile; ?>
		</table>
		
	<?php else: ?>
		
		<div id="message" class="info">
			<p><?php _e("You have not sent any notices.", "buddypress"); ?></p>
		</div>	

	<?php endif;?>

</div>

<?php get_footer() ?>