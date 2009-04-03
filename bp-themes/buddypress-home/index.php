<?php get_header(); ?>

<div id="content" class="narrowcolumn">

	<div class="widget" id="latest-news">
		<h2 class="widgettitle"><?php _e( 'Blog', 'buddypress' ) ?></h2>
		<?php if (have_posts()) : ?>

			<?php while (have_posts()) : the_post(); ?>
				<div class="post" id="post-<?php the_ID(); ?>">
					<h3><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'buddypress' ) ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
					<p class="date"><?php the_time('F jS, Y') ?> <em><?php _e( 'in', 'buddypress' ) ?> <?php the_category(', ') ?> <?php printf( __( 'by %s', 'buddypress' ), bp_core_get_userlink($post->post_author) ) ?></em></p>

					<div class="entry">
						<?php the_content( __( 'Read the rest of this entry &raquo;', 'buddypress' ) ); ?>
					</div>

					<p class="postmetadata"><span class="tags"><?php the_tags('Tags: ', ', ', '<br />'); ?></span> <span class="comments"><?php comments_popup_link( __( 'No Comments &#187;', 'buddypress' ), __( '1 Comment &#187;', 'buddypress' ), __( '% Comments &#187;', 'buddypress' ) ); ?></span></p>
					
					<div class="clear"></div>
				</div>

			<?php endwhile; ?>

			<div class="navigation">
				<div class="alignleft"><?php next_posts_link( __( '&laquo; Previous Entries', 'buddypress' ) ) ?></div>
				<div class="alignright"><?php previous_posts_link( __( 'Next Entries &raquo;', 'buddypress' ) ) ?></div>
			</div>

		<?php else : ?>

			<h2 class="center"><?php _e( 'Not Found', 'buddypress' ) ?></h2>
			<p class="center"><?php _e( 'Sorry, but you are looking for something that isn\'t here.', 'buddypress' ) ?></p>
			<?php include (TEMPLATEPATH . "/searchform.php"); ?>

		<?php endif; ?>
	</div>

</div>

<?php get_sidebar(); ?>


<?php get_footer(); ?>
