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

					<tr id="m-<?php bp_message_thread_id() ?>"<?php if ( bp_message_thread_has_unread() ) : ?> class="unread"<?php else: ?> class="read"<?php endif; ?>>
						<td width="1%" class="thread-count">
							<span class="unread-count"><?php bp_message_thread_unread_count() ?></span>
						</td>
						<td width="1%" class="thread-avatar"><?php bp_message_thread_avatar() ?></td>
						<td width="30%" class="thread-from">
							<?php _e( 'From:', 'buddypress' ); ?> <?php bp_message_thread_from() ?><br />
							<span class="activity"><?php bp_message_thread_last_post_date() ?></span>
						</td>
						<td width="50%" class="thread-info">
							<p><a href="<?php bp_message_thread_view_link() ?>" title="<?php _e( "View Message", "buddypress" ); ?>"><?php bp_message_thread_subject() ?></a></p>
							<p class="thread-excerpt"><?php bp_message_thread_excerpt() ?></p>
						</td>

						<?php do_action( 'bp_messages_inbox_list_item' ) ?>

						<td width="13%" class="thread-options">
							<input type="checkbox" name="message_ids[]" value="<?php bp_message_thread_id() ?>" />
							<a class="button confirm" href="<?php bp_message_thread_delete_link() ?>" title="<?php _e( "Delete Message", "buddypress" ); ?>">x</a> &nbsp;
						</td>
					</tr>

				<?php endwhile; ?>
			</table><!-- #message-threads -->

			<?php do_action( 'bp_after_messages_sentbox_list' ) ?>

		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e("You have no sent messages.", "buddypress"); ?></p>
			</div>

		<?php endif;?>

		<?php do_action( 'bp_after_messages_sentbox_content' ) ?>

	</div>

<?php get_footer() ?>