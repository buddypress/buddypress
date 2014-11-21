<?php do_action( 'bp_before_member_messages_loop' ); ?>

<?php if ( bp_has_message_threads( bp_ajax_querystring( 'messages' ) ) ) : ?>

	<div class="pagination no-ajax" id="user-pag">

		<div class="pag-count" id="messages-dir-count">
			<?php bp_messages_pagination_count(); ?>
		</div>

		<div class="pagination-links" id="messages-dir-pag">
			<?php bp_messages_pagination(); ?>
		</div>

	</div><!-- .pagination -->

	<?php do_action( 'bp_after_member_messages_pagination' ); ?>

	<?php do_action( 'bp_before_member_messages_threads' ); ?>

	<form action="<?php echo bp_loggedin_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() ?>/bulk-manage/" method="post" id="messages-bulk-management">

		<table id="message-threads" class="messages-notices">

			<thead>
				<tr>
					<th scope="col" class="thread-checkbox"><label class="bp-screen-reader-text" for="select-all-messages"><?php _e( 'Select all', 'buddypress' ); ?></label><input id="select-all-messages" type="checkbox"></th>
					<th scope="col" class="thread-from"><?php _e( 'From', 'buddypress' ); ?></th>
					<th scope="col" class="thread-info"><?php _e( 'Subject', 'buddypress' ); ?></th>
					<th scope="col" class="thread-options"><?php _e( 'Actions', 'buddypress' ); ?></th>
				</tr>
			</thead>

			<tbody>

				<?php while ( bp_message_threads() ) : bp_message_thread(); ?>

					<tr id="m-<?php bp_message_thread_id(); ?>" class="<?php bp_message_css_class(); ?><?php if ( bp_message_thread_has_unread() ) : ?> unread<?php else: ?> read<?php endif; ?>">
						<td>
							<input type="checkbox" name="message_ids[]" class="message-check" value="<?php bp_message_thread_id(); ?>" />
						</td>

						<?php if ( 'sentbox' != bp_current_action() ) : ?>
							<td class="thread-from">
								<?php bp_message_thread_avatar( array( 'width' => 25, 'height' => 25 ) ); ?>
								<span class="from"><?php _e( 'From:', 'buddypress' ); ?></span> <?php bp_message_thread_from(); ?>
								<?php bp_message_thread_total_and_unread_count(); ?>
								<span class="activity"><?php bp_message_thread_last_post_date(); ?></span>
							</td>
						<?php else: ?>
							<td class="thread-from">
								<?php bp_message_thread_avatar( array( 'width' => 25, 'height' => 25 ) ); ?>
								<span class="to"><?php _e( 'To:', 'buddypress' ); ?></span> <?php bp_message_thread_to(); ?>
								<?php bp_message_thread_total_and_unread_count(); ?>
								<span class="activity"><?php bp_message_thread_last_post_date(); ?></span>
							</td>
						<?php endif; ?>

						<td class="thread-info">
							<p><a href="<?php bp_message_thread_view_link(); ?>" title="<?php esc_attr_e( "View Message", "buddypress" ); ?>"><?php bp_message_thread_subject(); ?></a></p>
							<p class="thread-excerpt"><?php bp_message_thread_excerpt(); ?></p>
						</td>

						<?php do_action( 'bp_messages_inbox_list_item' ); ?>

						<td class="thread-options">
							<?php if ( bp_message_thread_has_unread() ) : ?>
								<a class="read" href="<?php bp_the_message_thread_mark_read_url();?>"><?php _e( 'Read', 'buddypress' ); ?></a>
							<?php else : ?>
								<a class="unread" href="<?php bp_the_message_thread_mark_unread_url();?>"><?php _e( 'Unread', 'buddypress' ); ?></a>
							<?php endif; ?>
							 |
							<a class="delete" href="<?php bp_message_thread_delete_link(); ?>"><?php _e( 'Delete', 'buddypress' ); ?></a>
						</td>
					</tr>

				<?php endwhile; ?>

			</tbody>

		</table><!-- #message-threads -->

		<div class="messages-options-nav">
			<?php bp_messages_bulk_management_dropdown(); ?>
		</div><!-- .messages-options-nav -->

		<?php wp_nonce_field( 'messages_bulk_nonce', 'messages_bulk_nonce' ); ?>
	</form>

	<?php do_action( 'bp_after_member_messages_threads' ); ?>

	<?php do_action( 'bp_after_member_messages_options' ); ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'Sorry, no messages were found.', 'buddypress' ); ?></p>
	</div>

<?php endif;?>

<?php do_action( 'bp_after_member_messages_loop' ); ?>
