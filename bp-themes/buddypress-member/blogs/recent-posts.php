<?php get_header() ?>

<div class="content-header">
	<?php bp_blogs_blog_tabs() ?>
</div>

<div id="content">
	<h2><?php _e("Recent Posts", "buddypress"); ?></h2>
	<?php do_action( 'template_notices' ) // (error/success feedback) ?>

	<?php if ( bp_has_posts() ) : ?>
		<?php while ( bp_posts() ) : bp_the_post(); ?>
			<div class="post" id="post-<?php bp_post_id(); ?>">
				<h2><a href="<?php bp_post_permalink() ?>" rel="bookmark" title="<?php printf ( __( 'Permanent Link to %s', 'buddypress' ), bp_post_title( false ) ); ?>"><?php bp_post_title(); ?></a></h2>
				<p class="date"><?php printf( __( '%1$s <em>in %2$s by %3$s</em>', 'buddypress' ), bp_post_date(__('F jS, Y', 'buddypress'), false ), bp_post_category( ', ', '', null, false ), bp_post_author( false ) ); ?></p>
				<?php bp_post_content(__('Read the rest of this entry &raquo;')); ?>
				<p class="postmetadata"><?php bp_post_tags( '<span class="tags">', ', ', '</span>' ); ?>  <span class="comments"><?php bp_post_comments( __('No Comments'), __('1 Comment'), __('% Comments') ); ?></span></p>
				<hr />
			</div>
			<?php endwhile; ?>
	<?php else: ?>

		<div id="message" class="info">
			<p><?php bp_word_or_name( __( "You haven't made any posts yet.", 'buddypress' ), __( "%s hasn't made any posts yet.", 'buddypress' ) ) ?></p>
		</div>

	<?php endif;?>

</div>

<?php get_footer() ?>