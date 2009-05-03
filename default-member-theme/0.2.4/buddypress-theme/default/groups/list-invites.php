<?php get_header(); ?>

	<div class="content-header">
	</div>

	<div id="content">
		<div class="pagination-links" id="pag">
			
		</div>
		
		<h2>Group Invites</h2>
		<?php do_action( 'template_notices' ) // (error/success feedback) ?>

		<?php if ( bp_has_groups() ) : ?>
			<ul id="group-list" class="invites">
			<?php while ( bp_groups() ) : bp_the_group(); ?>
				<li>
					<?php bp_group_avatar_thumb() ?>
					<h4><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a><span class="small"> - <?php bp_group_total_members() ?> members</span></h4>
					<p class="desc">
						<?php bp_group_description_excerpt() ?>
					</p>
					<div class="action">
						<a href="<?php bp_group_accept_invite_link() ?>" id="accept">Accept</a> 
						<a href="<?php bp_group_reject_invite_link() ?>" id="reject">Reject</a> 
					</div>
					<hr />
				</li>
			<?php endwhile; ?>
			</ul>
		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e('You have no outstanding group invites.'); ?></p>
			</div>

		<?php endif;?>
		
	</div>

<?php get_footer() ?>