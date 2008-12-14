<?php
function bp_blogs_directory_blogs_setup() {
	global $bp, $current_blog;
	
	if ( $bp['current_component'] == $bp['blogs']['slug'] && $bp['current_action'] == '' ) {
		add_action( 'bp_template_content', 'bp_blogs_directory_blogs_content' );
		add_action( 'bp_template_sidebar', 'bp_blogs_directory_blogs_sidebar' );
		
		wp_enqueue_script( 'bp-blogs-directory-blogs', site_url() . '/wp-content/mu-plugins/bp-blogs/js/directory-blogs.js', array( 'jquery', 'jquery-livequery-pack' ) );
		wp_enqueue_style( 'bp-blogs-directory-blogs', site_url() . '/wp-content/mu-plugins/bp-blogs/css/directory-blogs.css' );

		if ( file_exists( TEMPLATEPATH . '/plugin-template.php' ) )
			bp_catch_uri( 'plugin-template', true );
		else
			wp_die( __( 'To enable the blog directory you must drop the "plugin-template.php" file into your theme directory.', 'buddypress' ) );
	}
}
add_action( 'wp', 'bp_blogs_directory_blogs_setup', 5 );

function bp_blogs_directory_blogs_content() {
	global $bp;

	$pag_page = isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 1;
	$pag_num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : 10;
	
	$blogs = bp_blogs_get_all_blogs( $pag_num, $pag_page );

	$pag_links = paginate_links( array(
		'base' => add_query_arg( 'page', '%#%' ),
		'format' => '',
		'total' => ceil( $blogs['total'] / $pag_num ),
		'current' => $pag_page,
		'prev_text' => '&laquo;',
		'next_text' => '&raquo;',
		'mid_size' => 1
	));
	
	$from_num = intval( ( $pag_page - 1 ) * $pag_num ) + 1;
	$to_num = ( $from_num + ( $pag_num - 1  ) > $blogs['total'] ) ? $blogs['total'] : $from_num + ( $pag_num - 1 );
	
?>	
<div id="content" class="narrowcolumn">
	
	<form action="<?php echo site_url() . '/' ?>" method="post" id="blogs-directory-form">
	<div class="widget">
		<h2 class="widgettitle"><?php _e( 'Blog Directory', 'buddypress' ) ?></h2>
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
	</div>
	
	<div class="widget">
		<h2 class="widgettitle"><?php _e( 'Blog Listing', 'buddypress' ) ?></h2>
		
		<div id="blog-dir-list">
		<?php if ( $blogs['blogs'] ) : ?>
			<div id="blog-dir-count" class="pag-count">
				<?php echo sprintf( __( 'Viewing blog %d to %d (%d total active blogs)', 'buddypress' ), $from_num, $to_num, $blogs['total'] ); ?> &nbsp;
				<img id="ajax-loader-blogs" src="<?php echo $bp['core']['image_base'] ?>/ajax-loader.gif" height="7" alt="<?php _e( "Loading", "buddypress" ) ?>" style="display: none;" />
			</div>
			
			<div class="pagination-links" id="blog-dir-pag">
				<?php echo $pag_links ?>
			</div>

			<ul id="blogs-list" class="item-list">
			<?php foreach ( $blogs['blogs'] as $blog ) : ?>
				<li>
					<div class="item-avatar">
						<img src="<?php echo 'http://www.gravatar.com/avatar/' . md5( $blog->blog_id . '.blogs@' . site_url() ) . '?d=identicon&amp;s=50'; ?>" class="avatar" alt="Blog Identicon" />
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php echo get_blog_option( $blog->blog_id, 'siteurl' ) ?>" title="<?php echo get_blog_option( $blog->blog_id, 'blogname' ) ?>"><?php echo get_blog_option( $blog->blog_id, 'blogname' ) ?></a></div>
						<div class="item-meta"><span class="activity"><?php echo bp_core_get_last_activity( bp_blogs_get_blogmeta( $blog->blog_id, 'last_activity' ), __('active %s ago') ) ?></span></div>
					</div>
					
					<div class="action">
						<div class="blog-button visit">
							<a href="<?php echo get_blog_option( $blog->blog_id, 'siteurl' ) ?>" class="visit" title="Visit <?php echo get_blog_option( $blog->blog_id, 'blogname' ) ?>"><?php _e( 'Visit Blog', 'buddypress' ) ?></a>
						</div>
						<div class="meta">
							<?php 
								if ( $post = bp_blogs_get_latest_posts( $blog->blog_id, 1 ) ) {
									_e( sprintf( 'Latest Post: %s', '<a href="' . bp_post_get_permalink( $post[0], $blog->blog_id ) . '">' . apply_filters( 'the_title', $post[0]->post_title ) . '</a>' ), 'buddypress' );
								}
							?>
						</div>
					</div>
					
					<div class="clear"></div>
				</li>
			<?php endforeach; ?>
			</ul>	
		<?php else: ?>
			<div id="message" class="info">
				<p><?php _e( 'No blogs found.', 'buddypress' ) ?></p>
			</div>
		<?php endif; ?>
		</div>
		
	</div>
	<?php wp_nonce_field('directory_blogs', '_wpnonce-blog-filter' ) ?>
	</form>

</div>
<?php
}

function bp_blogs_directory_blogs_sidebar() {
	global $bp;
?>	
	<div class="widget">
		<h2 class="widgettitle"><?php _e( 'Find Blogs', 'buddypress' ) ?></h2>
		<form action="<?php echo site_url() . '/' . $bp['blogs']['slug']  . '/search/' ?>" method="post" id="search-blogs-form">
			<label><input type="text" name="blogs_search" id="blogs_search" value="<?php _e('Search anything...', 'buddypress' ) ?>"  onfocus="if (this.value == '<?php _e('Search anything...', 'buddypress' ) ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php _e('Search anything...', 'buddypress' ) ?>';}" /></label>
			<input type="submit" id="blogs_search_submit" name="blogs_search_submit" value="Search" />
		</form>
	</div>
	
	<div class="widget">
		<h2 class="widgettitle"><?php _e( 'Featured Blogs', 'buddypress' ) ?></h2>
		
		<?php $blogs = BP_Blogs_Blog::get_random( 3, 1 ) ?>

		<?php if ( $blogs['blogs'] ) { ?>
			<ul id="featured-blog-list" class="item-list">
				<?php foreach( $blogs['blogs'] as $blog ) : ?>
					<li>
						<div class="item-avatar">
							<img src="<?php echo 'http://www.gravatar.com/avatar/' . md5( $blog->blog_id . '.blogs@' . site_url() ) . '?d=identicon&amp;s=50'; ?>" class="avatar" alt="Blog Identicon" />
						</div>
						
						<div class="item">
							<div class="item-title"><a href="<?php echo get_blog_option( $blog->blog_id, 'siteurl' ) ?>" title="<?php echo get_blog_option( $blog->blog_id, 'blogname' ) ?>"><?php echo get_blog_option( $blog->blog_id, 'blogname' ) ?></a></div>
							<div class="item-meta"><span class="activity"><?php echo bp_core_get_last_activity( bp_blogs_get_blogmeta( $blog->blog_id, 'last_activity' ), __('active %s ago') ) ?></span></div>
						
							<div class="item-title blog-data">
								<p class="field-name"><?php _e( 'Description', 'buddypress' ) ?>:</p>
								<?php echo get_blog_option( $blog->blog_id, 'blogdescription' ) ?>
								
								<?php 
									if ( $post = bp_blogs_get_latest_posts( $blog->blog_id, 1 ) ) { ?>
										<p class="field-name"><?php _e( 'Latest Post', 'buddypress' ) ?>:</p>
										<?php echo '<a href="' . bp_post_get_permalink( $post[0], $blog->blog_id ) . '">' . apply_filters( 'the_title', $post[0]->post_title ) . '</a>';
									}
								?>
							</div>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php } else { ?>
			<div id="message" class="info">
				<p><?php _e( 'There are no blogs to feature.', 'buddypress' ) ?></p>
			</div>
		<?php } ?>
	</div>
<?php
}
