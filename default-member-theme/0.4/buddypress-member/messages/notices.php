<div class="content-header">

</div>

<div id="content">
	<div class="pagination-links">
		<?php bp_messages_pagination() ?>
	</div>
	
	<h2>Sent Notices</h2>
	
	<?php do_action( 'template_notices' ) ?>

	<?php if ( bp_has_message_threads() ) : ?>
		
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
					<p class="date">Sent: <?php bp_message_notice_post_date() ?></p>
				</td>
				<td width="4%">
					<a href="<?php bp_message_activate_deactivate_link() ?>"><?php bp_message_activate_deactivate_text() ?></a> 
					<a href="<?php bp_message_notice_delete_link() ?>" title="Delete Message">Delete</a> 
				</td>
			</tr>
		<?php endwhile; ?>
		</table>
		
	<?php else: ?>
		
		<div id="message" class="info">
			<p>You have not sent any notices.</p>
		</div>	

	<?php endif;?>

</div>