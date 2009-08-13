<?php get_header() ?>

	<div class="content-header">

	</div>

	<div id="content">
		
		<div class="pagination">
		
			<div class="pagination-links">
				<?php bp_messages_pagination() ?>
			</div>
		
		</div>
	
		<h2><?php _e( 'Sent Notices', 'buddypress' ); ?></h2>
	
		<?php do_action( 'template_notices' ) ?>

		<?php do_action( 'bp_before_messages_notices_content' ) ?>

		<?php if ( bp_has_message_threads() ) : ?>

			<?php do_action( 'bp_before_messages_notices_list' ) ?>
		
			<table id="message-threads" class="notices">
				<?php while ( bp_message_threads() ) : bp_message_thread(); ?>

					<tr>
						<td width="1%">
						</td>
						<td width="40%">
							<p><strong><?php bp_message_notice_subject() ?></strong></p>
							<p><?php bp_message_notice_text() ?></p>
						</td>
						<td width="27%">
							<p><?php bp_message_is_active_notice() ?></p>
							<p class="date"><?php _e("Sent:", "buddypress"); ?> <?php bp_message_notice_post_date() ?></p>
						</td>
							
						<?php do_action( 'bp_messages_notices_list_item' ) ?>

						<td width="4%">
							<a href="<?php bp_message_activate_deactivate_link() ?>"><?php bp_message_activate_deactivate_text() ?></a> 
							<a href="<?php bp_message_notice_delete_link() ?>" title="<?php _e("Delete Message", "buddypress"); ?>"><?php _e("Delete", "buddypress"); ?></a> 
						</td>
					</tr>
					
				<?php endwhile; ?>
			</table>

			<?php do_action( 'bp_after_messages_notices_list' ) ?>
		
		<?php else: ?>
		
			<div id="message" class="info">
				<p><?php _e("You have not sent any notices.", "buddypress"); ?></p>
			</div>	

		<?php endif; ?>

		<?php do_action( 'bp_after_messages_notices_content' ) ?>

	</div>

<?php get_footer() ?>