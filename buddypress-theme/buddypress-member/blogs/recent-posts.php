<div class="content-header">
</div>

<div id="content">
	<h2>Recent Posts</h2>
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>

	<?php if ( bp_has_posts() ) : ?>
		<?php while ( bp_posts() ) : bp_the_post(); ?>
			<div class="post" id="post-<?php bp_post_id(); ?>">
				<h2><a href="<?php bp_post_permalink() ?>" rel="bookmark" title="Permanent Link to <?php bp_post_title(); ?>"><?php bp_post_title(); ?></a></h2>
				<p class="date"><?php bp_post_date('F jS, Y') ?> <em>in <?php bp_post_category(', ') ?> by <?php bp_post_author() ?></em></p>
				<?php bp_post_content('Read the rest of this entry &raquo;'); ?>
				<p class="postmetadata"><?php bp_post_tags('<span class="tags">', ', ', '</span>'); ?>  <span class="comments"><?php bp_post_comments('No Comments', '1 Comment', '% Comments'); ?></span></p>
				<hr />
			</div>
			<?php endwhile; ?>
	<?php else: ?>

		<div id="message" class="info">
			<p><?php bp_you_or_name() ?> <?php _e('made any posts yet!'); ?></p>
		</div>

	<?php endif;?>

</div>