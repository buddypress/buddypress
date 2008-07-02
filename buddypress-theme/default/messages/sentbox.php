<?php get_header(); ?>

	<div class="content-header">
		<div class="pagination-links">
			<?php bp_messages_pagination() ?>
		</div>
	</div>

	<div id="content">
		<?php do_action( 'template_notices' ) ?>
	
		<?php if ( bp_has_message_threads() ) : ?>
			
			<table id="message-threads">
			<?php while ( bp_message_threads() ) : bp_message_thread(); ?>
				<tr>
					<td width="1%">
					</td>
					<td width="1%"><?php bp_message_thread_avatar() ?></td>
					<td width="27%">
						<p>To: <?php bp_message_thread_to() ?></p>
						<p class="date"><?php bp_message_thread_last_post_date() ?></p>
					</td>
					<td width="40%">
						<p><a href="<?php bp_message_thread_view_link() ?>" title="View Message"><?php bp_message_thread_subject() ?></a></p>
						<p><?php bp_message_thread_excerpt() ?></p>
					</td>
					<td width="4%">
							<a href="<?php bp_message_thread_delete_link() ?>" title="Delete Message">Delete</a> 
							<input type="checkbox" name="" value="" />
					</td>
				</tr>
			<?php endwhile; ?>
			</table>
			
		<?php else: ?>
			
			<div id="message" class="error">
				<p>You have no sent messages.</p>
			</div>	

		<?php endif;?>
	
	</div>
	
<?php get_footer() ?>