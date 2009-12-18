<?php

/* Stop the theme from killing WordPress if BuddyPress is not enabled. */
if ( !class_exists( 'BP_Core_User' ) )
	return false;

/* Register the widget columns */
register_sidebars( 1,
	array(
		'name' => 'Sidebar',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widgettitle">',
        'after_title' => '</h3>'
	)
);

/* Load the AJAX functions for the theme */
require_once( TEMPLATEPATH . '/_inc/ajax.php' );

/* Load the javascript for the theme */
wp_enqueue_script( 'dtheme-ajax-js', get_template_directory_uri() . '/_inc/global.js', array( 'jquery' ) );

/* Make sure the blog index page shows under /[HOME_BLOG_SLUG] if enabled */
function bp_dtheme_show_home_blog() {
	global $bp, $query_string, $paged;

	if ( $bp->current_component == BP_HOME_BLOG_SLUG && ( !$bp->current_action || 'page' == $bp->current_action ) ) {
		unset( $query_string );

		if ( ( 'page' == $bp->current_action && $bp->action_variables[0] ) && false === strpos( $query_string, 'paged' ) ) {
			$query_string .= '&paged=' . $bp->action_variables[0];
			$paged = $bp->action_variables[0];
		}

		query_posts($query_string);

		bp_core_load_template( 'index', true );
	}
}
add_action( 'wp', 'bp_dtheme_show_home_blog', 2 );

function bp_dtheme_firstname( $name = false, $echo = false ) {
	global $bp;

	if ( !$name )
		$name = $bp->loggedin_user->fullname;

	$fullname = (array)explode( ' ', $name );

	if ( $echo )
		echo $fullname[0];
	else
		return $fullname[0];
}

function bp_dtheme_add_blog_comments_js() {
	if ( is_singular() ) wp_enqueue_script( 'comment-reply' );
}
add_action( 'template_redirect', 'bp_dtheme_add_blog_comments_js' );

function bp_dtheme_comments( $comment, $args, $depth ) {
    $GLOBALS['comment'] = $comment;
	$comment_type = get_comment_type();

	if ( $comment->user_id )
		$userlink = bp_core_get_userurl( $comment->user_id );

	if ( $comment_type == 'comment' ) { ?>
        <li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">

			<div class="comment-avatar-box<?php if ( $comment->user_id ) : ?> extra<?php endif; ?>">
				<div class="avb">
					<a href="<?php if ( $userlink ) : echo $userlink; else : echo get_comment_author_url(); endif;?>">
						<?php echo get_avatar( $comment, 50 ); ?>
					</a>
				</div>
			</div>

			<div class="comment-content">

				<div class="comment-meta">
					<a href="<?php if ( $userlink ) : echo $userlink; else : echo get_comment_author_url(); endif;?>"><?php echo get_comment_author(); ?></a> <?php _e( 'said:', 'buddypress' ) ?>
					<em><?php _e( 'On', 'buddypress' ) ?> <a href="#comment-<?php comment_ID() ?>" title=""><?php comment_date() ?></a></em>
	            </div>

				<?php if ($comment->comment_approved == '0') : ?>
	            	<em class="moderate"><?php _e('Your comment is awaiting moderation.'); ?></em><br />
	            <?php endif; ?>

				<?php comment_text() ?>

				<div class="comment-options">
					<?php echo comment_reply_link( array('depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ?>
					<?php edit_comment_link( __( 'Edit' ),'','' ); ?>
				</div>

			</div>
        </li>
	<?php } ?>
<?php
}

function bp_dtheme_remove_redundant() {
	global $bp;

	/* Remove the redundant "My Posts and My Comments" options since we can use filters on the activity stream. */
	bp_core_remove_subnav_item( $bp->blogs->slug, 'recent-posts' );
	bp_core_remove_subnav_item( $bp->blogs->slug, 'recent-comments' );
}
add_action( 'init', 'bp_dtheme_remove_redundant' );

?>