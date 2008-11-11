<?php if ( bp_has_groups() ) : ?>
	<div class="pagination-links" id="<?php bp_group_pag_id() ?>">
		<?php bp_group_pagination() ?>
	</div>
	
	<ul id="group-list">
	<?php while ( bp_groups() ) : bp_the_group(); ?>
		<li>
			<?php bp_group_avatar_thumb() ?>
			<h4><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a><span class="small"> - <?php bp_group_total_members() ?> members</span></h4>
			
			<?php if ( bp_group_has_requested_membership() ) : ?>
				<p class="request-pending">Membership Pending Approval</p>
			<?php endif; ?>
			
			<p class="desc">
				<?php bp_group_description_excerpt() ?>
			</p>
		</li>
	<?php endwhile; ?>
	</ul>
<?php else: ?>

	<?php if ( bp_group_show_no_groups_message() ) : ?>
	<div id="message" class="info">
		<p><?php bp_word_or_name( __( "You haven't joined any groups yet.", 'buddypress' ), __( "%s hasn't joined any groups yet.", 'buddypress' ) ) ?></p>
	</div>
	<?php endif; ?>

<?php endif;?>