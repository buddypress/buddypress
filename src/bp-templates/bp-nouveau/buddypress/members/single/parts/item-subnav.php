<?php
/**
 * BuddyPress Single Members item Sub Navigation
 *
 * @since 3.0.0
 * @version 3.0.0
 */
?>

<?php if ( bp_nouveau_has_nav( array( 'type' => 'secondary' ) ) ) : ?>

	<?php
	while ( bp_nouveau_nav_items() ) :
		bp_nouveau_nav_item();
	?>

		<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>" <?php bp_nouveau_nav_scope(); ?>>
			<a href="<?php bp_nouveau_nav_link(); ?>" id="<?php bp_nouveau_nav_link_id(); ?>">
				<?php bp_nouveau_nav_link_text(); ?>

				<?php if ( bp_nouveau_nav_has_count() ) : ?>
					<span class="count"><?php bp_nouveau_nav_count(); ?></span>
				<?php endif; ?>
			</a>
		</li>

	<?php endwhile; ?>

<?php endif; ?>
