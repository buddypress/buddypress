<?php if ( bp_has_blogs( 'type=active&per_page=10' ) ) : ?>

	<div class="pagination">

		<div class="pag-count" id="blog-dir-count">
			<?php bp_blogs_pagination_count() ?>
		</div>

		<div class="pagination-links" id="blog-dir-pag">
			<?php bp_blogs_pagination_links() ?>
		</div>

	</div>

	<?php do_action( 'bp_before_directory_blogs_list' ) ?>

	<ul id="blogs-list" class="item-list">
	<?php while ( bp_blogs() ) : bp_the_blog(); ?>

		<li>
			<div class="item-avatar">
				<a href="<?php bp_blog_permalink() ?>"><?php bp_blog_avatar_thumb() ?></a>
			</div>

			<div class="item">
				<div class="item-title"><a href="<?php bp_blog_permalink() ?>"><?php bp_blog_name() ?></a></div>
				<div class="item-meta"><span class="activity"><?php bp_blog_last_active() ?></span></div>

				<?php do_action( 'bp_core_directory_blogs_item' ) ?>
			</div>

			<div class="action">
				<div class="generic-button blog-button visit">
					<a href="<?php bp_blog_permalink() ?>" class="visit" title="<?php _e( 'Visit Blog', 'buddypress' ) ?>"><?php _e( 'Visit Blog', 'buddypress' ) ?></a>
				</div>

				<div class="meta">
					<?php bp_blog_latest_post() ?>
				</div>

				<?php do_action( 'bp_directory_blogs_actions' ) ?>
			</div>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>
	</ul>

	<?php do_action( 'bp_after_directory_blogs_list' ) ?>

	<?php bp_blog_hidden_fields() ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'No blogs found. Blogs must fill in at least one piece of profile data to show in blog lists.', 'buddypress' ) ?></p>
	</div>

<?php endif; ?>