<?php get_header() ?>

<div class="content-header">

</div>

<div id="content">	
	<h2><?php _e("Sent Messages", "buddypress"); ?></h2>
	<?php do_action( 'template_notices' ) ?>

	<?php if ( bp_has_message_threads() ) : ?>
		<div class="pagination-links">
			<?php bp_messages_pagination() ?>
		</div>
		
		<table id="message-threads">
		<?php while ( bp_message_threads() ) : bp_message_thread(); ?>
			<tr>
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
				<td width="4%">
						<a href="<?php bp_message_thread_delete_link() ?>" title="<?php _e("Delete Message", "buddypress"); ?>"><?php _e("Delete", "buddypress"); ?></a> 
						<input type="checkbox" name="" value="" />
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