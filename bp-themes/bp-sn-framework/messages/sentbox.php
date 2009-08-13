<?php get_header() ?>

	<div class="content-header">
		<div class="messages-options">	
			<?php bp_messages_options() ?>
		</div>
	</div>

	<div id="content">	
		<h2><?php _e("Sent Messages", "buddypress"); ?></h2>
	
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<?php do_action( 'bp_before_messages_sentbox_content' ) ?>

		<?php if ( bp_has_message_threads() ) : ?>
			
			<div class="pagination">

				<div class="pagination-links">
					<?php bp_messages_pagination() ?>
				</div>
			
			</div>

			<?php do_action( 'bp_before_messages_sentbox_list' ) ?>
				
			<table id="message-threads">
				<?php while ( bp_message_threads() ) : bp_message_thread(); ?>
				
					<tr id="m-<?php bp_message_thread_id() ?>">
						<td width="1%">
						</td>
						<td width="1%"><?php bp_message_thread_avatar() ?></td>
						<td width="27%">
							<p><?php _e("To:", "buddypress"); ?> <?php bp_message_thread_to() ?></p>
							<p class="date"><?php bp_message_thread_last_post_date() ?></p>
						</td>
						<td width="40%">
							<p><a href="<?php bp_message_thread_view_link() ?>" title="<?php _e("View Message", "buddypress"); ?>"><?php bp_message_thread_subject() ?></a></p>
							<p><?php bp_message_thread_excerpt() ?></p>
						</td>

						<?php do_action( 'bp_messages_sentbox_list' ) ?>

						<td width="10%">
							<a href="<?php bp_message_thread_delete_link() ?>" title="<?php _e("Delete Message", "buddypress"); ?>" class="delete"><?php _e("Delete", "buddypress"); ?></a> &nbsp;  
							<input type="checkbox" name="message_ids[]" value="<?php bp_message_thread_id() ?>" />
						</td>
					</tr>
				
				<?php endwhile; ?>
			</table>
		
			<?php do_action( 'bp_after_messages_sentbox_list' ) ?>
			
		<?php else: ?>
		
			<div id="message" class="info">
				<p><?php _e("You have no sent messages.", "buddypress"); ?></p>
			</div>	

		<?php endif;?>

		<?php do_action( 'bp_after_messages_sentbox_content' ) ?>
		
	</div>

<?php get_footer() ?>