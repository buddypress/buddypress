<?php get_header(); ?>

	<div id="content">

		<?php do_action( 'bp_before_blog_single_post' ) ?>

		<div class="page" id="blog-single">

			<h2 class="pagetitle"><?php _e( 'Blog', 'buddypress' ) ?></h2>

			<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

				<div class="item-options">

					<div class="alignleft"><?php next_posts_link( __( '&larr; Previous Entries', 'buddypress' ) ) ?></div>
					<div class="alignright"><?php previous_posts_link( __( 'Next Entries &raquo;', 'buddypress' ) ) ?></div>

				</div>

				<div class="post" id="post-<?php the_ID(); ?>">

					<?php do_action( 'bp_before_blog_post' ) ?>

					<h3><a href="<?php echo get_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent link to', 'buddypress' ) ?> <?php the_title(); ?>"><?php the_title(); ?></a></h3>

					<div class="entry">

						<?php the_content( __( '<p class="serif">Read the rest of this entry &raquo;</p>', 'buddypress' ) ); ?>

						<?php wp_link_pages(array('before' => __( '<p><strong>Pages:</strong> ', 'buddypress' ), 'after' => '</p>', 'next_or_number' => 'number')); ?>

					</div>

					<?php do_action( 'bp_after_blog_post' ) ?>

				</div>

			<?php comments_template(); ?>

			<?php endwhile; else: ?>

				<p><?php _e( 'Sorry, no posts matched your criteria.', 'buddypress' ) ?></p>

			<?php endif; ?>

		</div>

		<?php do_action( 'bp_after_blog_single_post' ) ?>

	</div>

	<?php get_sidebar(); ?>

<?php get_footer(); ?>
