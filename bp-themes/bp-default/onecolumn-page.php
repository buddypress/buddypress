<?php
/*
 * Template Name: One column, no sidebar
 *
 * A custom page template without sidebar.
 *
 * @package BuddyPress
 * @subpackage BP_Default
 * @since 1.3
 */

get_header() ?>

	<div id="content">
		<div class="padder one-column">

		<?php do_action( 'bp_before_blog_page' ) ?>

		<div class="page" id="blog-page" role="main">

			<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

				<h2 class="pagetitle"><?php the_title(); ?></h2>

				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

					<div class="entry">

						<?php the_content( __( '<p class="serif">Read the rest of this page &rarr;</p>', 'buddypress' ) ); ?>

						<?php wp_link_pages( array( 'before' => '<div class="page-link"><p>' . __( '<strong>Pages:</strong> ', 'buddypress' ), 'after' => '</p></div>', 'next_or_number' => 'number')); ?>
						<?php edit_post_link( __( 'Edit this entry.', 'buddypress' ), '<p>', '</p>'); ?>

					</div>

				</div>

			<?php comments_template(); ?>

			<?php endwhile; endif; ?>

		</div><!-- .page -->

		<?php do_action( 'bp_after_blog_page' ) ?>

		</div><!-- .padder -->
	</div><!-- #content -->

<?php get_footer(); ?>