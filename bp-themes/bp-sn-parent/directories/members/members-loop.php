<?php if ( bp_has_members( 'type=active&per_page=10' ) ) : ?>

	<div class="pagination">

		<div class="pag-count" id="member-dir-count">
			<?php bp_members_pagination_count() ?>
		</div>

		<div class="pagination-links" id="member-dir-pag">
			<?php bp_members_pagination_links() ?>
		</div>

	</div>

	<?php do_action( 'bp_before_directory_members_list' ) ?>

	<ul id="members-list" class="item-list">
	<?php while ( bp_members() ) : bp_the_member(); ?>

		<li>
			<div class="item-avatar">
				<a href="<?php bp_member_link() ?>"><?php bp_member_avatar() ?></a>
			</div>

			<div class="item">
				<div class="item-title"><a href="<?php bp_member_link() ?>"><?php bp_member_name() ?></a></div>
				<div class="item-meta"><span class="activity"><?php bp_member_last_active() ?></span></div>

				<?php do_action( 'bp_directory_members_item' ) ?>
			</div>

			<div class="action">
				<?php bp_member_add_friend_button() ?>

				<?php do_action( 'bp_directory_members_actions' ) ?>
			</div>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>
	</ul>

	<?php do_action( 'bp_after_directory_members_list' ) ?>

	<?php bp_member_hidden_fields() ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'No members found. Members must fill in at least one piece of profile data to show in member lists.', 'buddypress' ) ?></p>
	</div>

<?php endif; ?>