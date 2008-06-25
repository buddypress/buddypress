<?php get_header(); ?>

	<div class="content-header">
		X New Messages
	</div>

	<div id="content">
	
		<?php if ( bp_has_message_threads() ) : ?>
			
			<table id="message-threads">
			<?php while ( bp_message_threads() ) : bp_message_thread(); ?>
				<tr<?php if ( bp_message_thread_has_unread() ) : ?> class="unread"<?php endif; ?>>
					<td>
						<?php if ( bp_message_thread_has_unread() ) : ?>
							<?php bp_message_thread_unread_count() ?>
						<?php endif; ?>
					</td>
					<td><?php bp_message_thread_avatar() ?></td>
					<td>
						<p>From: <?php bp_message_thread_to() ?></p>
						<p class="date"><?php bp_message_thread_last_post_date() ?></p>
					</td>
					<td>
						<p><a href="<?php bp_message_thread_view_link() ?>" title="View Message"><?php bp_message_thread_subject() ?></a></p>
						<p><?php bp_message_thread_excerpt() ?></p>
					</td>
				</tr>
			<?php endwhile; ?>
			</table>
			
		<?php else: ?>
			
			<p><?php _e('No Messages!'); ?></p>
			
		<?php endif;?>
	
	</div>
	
<?php get_footer() ?>