<?php if ( bp_has_site_blogs( 'type=active&per_page=5' ) ) : ?>

<div class="pag-count" id="blog-dir-count">
<?php bp_site_blogs_pagination_count() ?>
</div>

<div class="pagination-links" id="blog-dir-pag">
<?php bp_site_blogs_pagination_links() ?>
</div>

<ul id="blogs-list" class="item-list">
<?php while ( bp_site_blogs() ) : bp_the_site_blog(); ?>

<li>
</li>

<?php endwhile; ?>
</ul>		

<?php else: ?>

<div id="message" class="info">
<p><?php _e( 'There were no blogs found.', 'buddypress' ) ?></p>
</div>

<?php endif; ?>

<?php bp_the_site_blog_hidden_fields() ?>