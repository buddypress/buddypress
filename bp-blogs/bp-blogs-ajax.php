<?php
function bp_blogs_ajax_directory_blogs() {
	global $bp;

	check_ajax_referer('directory_blogs');
	
	$pag_page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
	$pag_num = isset( $_POST['num'] ) ? intval( $_POST['num'] ) : 10;
	
	if ( isset( $_POST['letter'] ) && $_POST['letter'] != '' ) {
		$blogs = BP_Blogs_Blog::get_by_letter( $_POST['letter'], $pag_num, $pag_page );
	} else if ( isset ( $_POST['blogs_search'] ) && $_POST['blogs_search'] != '' ) {
		$blogs = BP_Blogs_Blog::search_blogs( $_POST['blogs_search'], $pag_num, $pag_page );
	} else {
		$blogs = BP_Blogs_Blog::get_all( $pag_num, $pag_page );
	}
	
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
	$to_num = ( $from_num + 9 > $blogs['total'] ) ? $blogs['total'] : $from_num + 9; 
	
	echo '<div id="blog-dir-list">';
	if ( $blogs['blogs'] ) {
		echo '0[[SPLIT]]'; // return valid result.
		
		?>
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
	<?php
	} else {
		echo "-1[[SPLIT]]<div id='message' class='error'><p>" . __("No blogs matched the current filter.", 'buddypress') . '</p></div>';
	}
	
	if ( isset( $_POST['letter'] ) ) {
		echo '<input type="hidden" id="selected_letter" value="' . $_POST['letter'] . '" name="selected_letter" />';
	}
	
	if ( isset( $_POST['blogs_search'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . $_POST['blogs_search'] . '" name="search_terms" />';
	}
	
	echo '</div>';
}
add_action( 'wp_ajax_directory_blogs', 'bp_blogs_ajax_directory_blogs' );


?>