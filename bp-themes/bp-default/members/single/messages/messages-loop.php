<?php if ( bp_has_message_threads() ) : ?>

	<div class="pagination" id="user-pag">

		<div class="pag-count" id="messages-dir-count">
			<?php bp_messages_pagination_count() ?>
		</div>

		<div class="pagination-links" id="messages-dir-pag">
			<?php bp_messages_pagination() ?>
		</div>

	</div>

	<?php do_action( 'bp_before_messages_inbox_list' ) ?>

	<table id="message-threads">
		<?php while ( bp_message_threads() ) : bp_message_thread(); ?>

			<tr id="m-<?php bp_message_thread_id() ?>"<?php if ( bp_message_thread_has_unread() ) : ?> class="unread"<?php else: ?> class="read"<?php endif; ?>>
				<td width="1%" class="thread-count">
					<span class="unread-count"><?php bp_message_thread_unread_count() ?></span>
				</td>
				<td width="1%" class="thread-avatar"><?php bp_message_thread_avatar() ?></td>
				<td width="27%" class="thread-from">
					<?php _e("From:", "buddypress"); ?> <?php bp_message_thread_from() ?><br />
					<span class="activity"><?php bp_message_thread_last_post_date() ?></span>
				</td>
				<td width="40%" class="thread-info">
					<p><a href="<?php bp_message_thread_view_link() ?>" title="<?php _e( "View Message", "buddypress" ); ?>"><?php bp_message_thread_subject() ?></a></p>
					<p class="thread-excerpt"><?php bp_message_thread_excerpt() ?></p>
				</td>

				<?php do_action( 'bp_messages_inbox_list_item' ) ?>

				<td width="10%" class="thread-options">
					<a href="<?php bp_message_thread_delete_link() ?>" title="<?php _e("Delete Message", "buddypress"); ?>" class="delete confirm"><?php _e("Delete", "buddypress"); ?></a> &nbsp;
					<input type="checkbox" name="message_ids[]" value="<?php bp_message_thread_id() ?>" />
				</td>
			</tr>

		<?php endwhile; ?>
	</table>

	<ul id="messages-options-nav">
		<li>
			<?php bp_messages_options() ?>
		</li>
	</ul>

	<?php do_action( 'bp_after_messages_inbox_list' ) ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'Sorry, no messages were found.', 'buddypress' ); ?></p>
	</div>

<?php endif;?>
