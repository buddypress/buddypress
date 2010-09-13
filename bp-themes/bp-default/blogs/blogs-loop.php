<?php /* Querystring is set via AJAX in _inc/ajax.php - bp_dtheme_object_filter() */ ?>

<?php do_action( 'bp_before_blogs_loop' ) ?>

<?php if ( bp_has_blogs( bp_ajax_querystring( 'blogs' ) ) ) : ?>

	<div class="pagination">

		<div class="pag-count" id="blog-dir-count">
			<?php bp_blogs_pagination_count() ?>
		</div>

		<div class="pagination-links" id="blog-dir-pag">
			<?php bp_blogs_pagination_links() ?>
		</div>

	</div>

	<ul id="blogs-list" class="item-list">
	<?php while ( bp_blogs() ) : bp_the_blog(); ?>

		<li>
			<div class="item-avatar">
				<a href="<?php bp_blog_permalink() ?>"><?php bp_blog_avatar('type=thumb') ?></a>
			</div>

			<div class="item">
				<div class="item-title"><a href="<?php bp_blog_permalink() ?>"><?php bp_blog_name() ?></a></div>
				<div class="item-meta"><span class="activity"><?php bp_blog_last_active() ?></span></div>

				<?php do_action( 'bp_directory_blogs_item' ) ?>
			</div>

			<div class="action">

				<?php do_action( 'bp_directory_blogs_actions' ) ?>

				<div class="meta">
					<?php bp_blog_latest_post() ?>
				</div>

			</div>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>
	</ul>

	<?php do_action( 'bp_after_directory_blogs_list' ) ?>

	<?php bp_blog_hidden_fields() ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'Sorry, there were no blogs found.', 'buddypress' ) ?></p>
	</div>

<?php endif; ?>

<?php do_action( 'bp_after_blogs_loop' ) ?>
