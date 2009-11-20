<?php if ( bp_has_site_groups( 'type=active&per_page=10' ) ) : ?>

	<div class="pagination">

		<div class="pag-count" id="group-dir-count">
			<?php bp_site_groups_pagination_count() ?>
		</div>

		<div class="pagination-links" id="group-dir-pag">
			<?php bp_site_groups_pagination_links() ?>
		</div>

	</div>

	<?php do_action( 'bp_before_directory_groups_list' ) ?>

	<ul id="groups-list" class="item-list">
	<?php while ( bp_site_groups() ) : bp_the_site_group(); ?>

		<li>
			<div class="item-avatar">
				<a href="<?php bp_the_site_group_link() ?>"><?php bp_the_site_group_avatar_thumb() ?></a>
			</div>

			<div class="item">
				<div class="item-title"><a href="<?php bp_the_site_group_link() ?>"><?php bp_the_site_group_name() ?></a></div>
				<div class="item-meta"><span class="activity"><?php bp_the_site_group_last_active() ?></span></div>

				<div class="item-meta desc"><?php bp_the_site_group_description_excerpt() ?></div>

				<?php do_action( 'bp_directory_groups_item' ) ?>
			</div>

			<div class="action">
				<?php bp_the_site_group_join_button() ?>

				<div class="meta">
					<?php bp_the_site_group_type() ?> / <?php bp_the_site_group_member_count() ?>
				</div>

				<?php do_action( 'bp_directory_groups_actions' ) ?>
			</div>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>
	</ul>

	<?php do_action( 'bp_after_directory_groups_list' ) ?>

	<?php bp_the_site_group_hidden_fields() ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'There were no groups found.', 'buddypress' ) ?></p>
	</div>

<?php endif; ?>