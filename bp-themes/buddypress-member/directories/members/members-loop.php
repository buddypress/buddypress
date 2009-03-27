<?php if ( bp_has_site_members( 'type=active&per_page=10' ) ) : ?>
	
	<div class="pag-count" id="member-dir-count">
		<?php bp_site_members_pagination_count() ?>
	</div>

	<div class="pagination-links" id="member-dir-pag">
		<?php bp_site_members_pagination_links() ?>
	</div>

	<ul id="members-list" class="item-list">
	<?php while ( bp_site_members() ) : bp_the_site_member(); ?>

		<li>
			<div class="item-avatar">
				<a href="<?php bp_the_site_member_link() ?>"><?php bp_the_site_member_avatar() ?></a>
			</div>

			<div class="item">
				<div class="item-title"><a href="<?php bp_the_site_member_link() ?>"><?php bp_the_site_member_name() ?></a></div>
				<div class="item-meta"><span class="activity"><?php bp_the_site_member_last_active() ?></span></div>

				<?php do_action( 'bp_core_directory_members_content' ) ?>
			</div>

			<div class="action">
				<?php bp_the_site_member_add_friend_button() ?>

				<?php do_action( 'bp_core_directory_members_actions' ) ?>
			</div>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>
	</ul>		
			
<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'No members found. Members must fill in at least one piece of profile data to show in member lists.', 'buddypress' ) ?></p>
	</div>

<?php endif; ?>

<?php bp_the_site_member_hidden_fields() ?>