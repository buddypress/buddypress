<?php get_header() ?>

<div id="directory-main">
	
	<form action="<?php echo site_url() . '/' ?>" method="post" id="blogs-directory-form">
		<h3><?php _e( 'Blog Directory', 'buddypress' ) ?></h3>
		
		<ul id="letter-list">
			<li><a href="#a" id="letter-a">A</a></li>
			<li><a href="#b" id="letter-b">B</a></li>
			<li><a href="#c" id="letter-c">C</a></li>
			<li><a href="#d" id="letter-d">D</a></li>
			<li><a href="#e" id="letter-e">E</a></li>
			<li><a href="#f" id="letter-f">F</a></li>
			<li><a href="#g" id="letter-g">G</a></li>
			<li><a href="#h" id="letter-h">H</a></li>
			<li><a href="#i" id="letter-i">I</a></li>
			<li><a href="#j" id="letter-j">J</a></li>
			<li><a href="#k" id="letter-k">K</a></li>
			<li><a href="#l" id="letter-l">L</a></li>
			<li><a href="#m" id="letter-m">M</a></li>
			<li><a href="#n" id="letter-n">N</a></li>
			<li><a href="#o" id="letter-o">O</a></li>
			<li><a href="#p" id="letter-p">P</a></li>
			<li><a href="#q" id="letter-q">Q</a></li>
			<li><a href="#r" id="letter-r">R</a></li>
			<li><a href="#s" id="letter-s">S</a></li>
			<li><a href="#t" id="letter-t">T</a></li>
			<li><a href="#u" id="letter-u">U</a></li>
			<li><a href="#v" id="letter-v">V</a></li>
			<li><a href="#w" id="letter-w">W</a></li>
			<li><a href="#x" id="letter-x">X</a></li>
			<li><a href="#y" id="letter-y">Y</a></li>
			<li><a href="#z" id="letter-z">Z</a></li>
		</ul>

		<div class="clear"></div>

		<div id="blogs-directory-listing" class="directory-listing">
			<h3><?php _e( 'Blog Listing', 'buddypress' ) ?></h3>
			
			<div id="blog-dir-list">
				<?php load_template( TEMPLATEPATH . '/directories/blogs/blogs-loop.php' ) ?>
			</div>

		</div>

		<?php do_action( 'bp_core_directory_blogs_content' ) ?>
		<?php wp_nonce_field( 'directory_blogs', '_wpnonce-blog-filter' ) ?>

	</form>
	
</div>

<div id="directory-sidebar">

	<div id="blogs-directory-search" class="directory-search">
		<h3><?php _e( 'Find Blogs', 'buddypress' ) ?></h3>

		<?php bp_directory_blogs_search_form() ?>

	</div>

	<div id="blogs-directory-featured" class="directory-featured">
		<h3><?php _e( 'Featured Blogs', 'buddypress' ) ?></h3>
		
		<?php if ( bp_has_site_blogs( 'type=random&max=3' ) ) : ?>

			<ul id="featured-blogs-list" class="item-list">
			<?php while ( bp_site_blogs() ) : bp_the_site_blog(); ?>

				<li>
					<div class="item-avatar">
						<a href="<?php bp_the_site_blog_link() ?>"><?php bp_the_site_blog_avatar_thumb() ?></a>
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php bp_the_site_blog_link() ?>"><?php bp_the_site_blog_name() ?></a></div>
						<div class="item-meta"><span class="activity"><?php bp_the_site_blog_last_active() ?></span></div>
						
						<div class="field-data">
							<div class="field-name">
								<strong><?php _e( 'Description: ', 'buddypress' ) ?></strong>
								<?php bp_the_site_blog_description() ?>
							</div>
						</div>
						<?php do_action( 'bp_core_directory_blogs_content' ) ?>
					</div>

					<div class="clear"></div>
				</li>

			<?php endwhile; ?>
			</ul>			
			
		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e( 'There are not enough blogs to feature.', 'buddypress' ) ?></p>
			</div>

		<?php endif; ?>
	
	</div>

</div>

<?php get_footer() ?>