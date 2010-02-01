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

function bp_dtheme_blog_comments( $comment, $args, $depth ) {
    $GLOBALS['comment'] = $comment; ?>

	<li id="comment-<?php comment_ID(); ?>">
		<div class="comment-avatar-box">
			<div class="avb">
				<a href="<?php echo get_comment_author_url() ?>">
					<?php echo bp_core_fetch_avatar( array( 'item_id' => $comment->user_id, 'width' => 50, 'height' => 50, 'email' => $comment->comment_author_email ) ); ?>
				</a>
			</div>
		</div>

		<div class="comment-content">

			<div class="comment-meta">
				<a href="<?php echo get_comment_author_url() ?>"><?php echo get_comment_author(); ?></a> <?php _e( 'said:', 'buddypress' ) ?>
				<em><?php _e( 'On', 'buddypress' ) ?> <a href="#comment-<?php comment_ID() ?>" title=""><?php comment_date() ?></a></em>
			</div>

			<?php if ( $comment->comment_approved == '0' ) : ?>
			 	<em class="moderate"><?php _e('Your comment is awaiting moderation.'); ?></em><br />
			<?php endif; ?>

			<?php comment_text() ?>

			<div class="comment-options">
				<?php echo comment_reply_link( array('depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ?>
				<?php edit_comment_link( __( 'Edit' ),'','' ); ?>
			</div>

		</div>
	</li>
<?php
}

function bp_dtheme_show_on_frontpage() {
	$settings = get_option( 'bp_dtheme_options' );

	if ( empty( $settings['show_on_frontpage'] ) || 'blog' == $settings['show_on_frontpage'] )
		return 'blog';

	return 'activity';
}

/* Set the defaults for the custom header image (http://ryan.boren.me/2007/01/07/custom-image-header-api/) */
define( 'HEADER_TEXTCOLOR', 'FFFFFF' );
define( 'HEADER_IMAGE', '%s/_inc/images/default_header.jpg' ); // %s is theme dir uri
define( 'HEADER_IMAGE_WIDTH', 1250 );
define( 'HEADER_IMAGE_HEIGHT', 125 );

function bp_dtheme_header_style() { ?>
	<style type="text/css">
		#header { background-image: url(<?php header_image() ?>); }
		<?php if ( 'blank' == get_header_textcolor() ) { ?>
		#header h1, #header #desc { display: none; }
		<?php } else { ?>
		#header h1 a, #desc { color:#<?php header_textcolor() ?>; }
		<?php } ?>
	</style>
<?php
}

function bp_dtheme_admin_header_style() { ?>
	<style type="text/css">
		#headimg {
			position: relative;
			color: #fff;
			background: url(<?php header_image() ?>);
			-moz-border-radius-bottomleft: 6px;
			-webkit-border-bottom-left-radius: 6px;
			-moz-border-radius-bottomright: 6px;
			-webkit-border-bottom-right-radius: 6px;
			margin-bottom: 20px;
			height: 100px;
			padding-top: 25px;
		}

		#headimg h1{
			position: absolute;
			bottom: 15px;
			left: 15px;
			width: 44%;
			margin: 0;
			font-family: Arial, Tahoma, sans-serif;
		}
		#headimg h1 a{
			color:#<?php header_textcolor() ?>;
			text-decoration: none;
			border-bottom: none;
		}
		#headimg #desc{
			color:#<?php header_textcolor() ?>;
			font-size:1em;
			margin-top:-0.5em;
		}

		#desc {
			display: none;
		}

		<?php if ( 'blank' == get_header_textcolor() ) { ?>
		#headimg h1, #headimg #desc {
			display: none;
		}
		#headimg h1 a, #headimg #desc {
			color:#<?php echo HEADER_TEXTCOLOR ?>;
		}
		<?php } ?>
	</style>
<?php
}
add_custom_image_header( 'bp_dtheme_header_style', 'bp_dtheme_admin_header_style' );

function bp_dtheme_remove_redundant() {
	global $bp;

	/* Remove the navigation options we do not need in this theme. */
	bp_core_remove_subnav_item( $bp->blogs->slug, 'my-blogs' );
	bp_core_remove_subnav_item( $bp->blogs->slug, 'recent-posts' );
	bp_core_remove_subnav_item( $bp->blogs->slug, 'recent-comments' );
}
add_action( 'init', 'bp_dtheme_remove_redundant' );

function bp_dtheme_show_notice() { ?>
	<div id="message" class="updated fade">
		<p><?php printf( __( 'Theme activated! This theme contains <a href="%s">customization options</a> and <a href="%s">custom header image</a> support. <a href="%s">Sidebar widgets</a> are also supported.', 'buddypress' ), admin_url( 'themes.php?page=theme-options-page' ), admin_url( 'themes.php?page=custom-header' ), admin_url( 'widgets.php' ) ) ?></p>
	</div>

	<style type="text/css">#message2, #message0 { display: none; }</style>
	<?php
}

/* Add words that we need to use in JS to the end of the page so they can be translated and still used. */
function bp_dtheme_js_terms() { ?>
<script type="text/javascript">
	var bp_terms_my_favs = '<?php _e( "My Favorites", "buddypress" ) ?>';
	var bp_terms_accepted = '<?php _e( "Accepted", "buddypress" ) ?>';
	var bp_terms_rejected = '<?php _e( "Rejected", "buddypress" ) ?>';
	var bp_terms_show_all_comments = '<?php _e( "Show all comments for this thread", "buddypress" ) ?>';
	var bp_terms_show_all = '<?php _e( "Show all", "buddypress" ) ?>';
	var bp_terms_comments = '<?php _e( "comments", "buddypress" ) ?>';
	var bp_terms_close = '<?php _e( "Close", "buddypress" ) ?>';
	var bp_terms_mention_explain = '<?php printf( __( "%s is a unique identifier for %s that you can type into any message on this site. %s will be sent a notification and a link to your message any time you use it.", "buddypress" ), '@' . bp_get_displayed_user_username(), bp_dtheme_firstname(bp_get_displayed_user_fullname()), bp_dtheme_firstname(bp_get_displayed_user_fullname()) ); ?>';
	</script>
<?php
}
add_action( 'wp_footer', 'bp_dtheme_js_terms' );


/* Show a notice when the theme is activated - workaround by Ozh (http://old.nabble.com/Activation-hook-exist-for-themes--td25211004.html) */
if ( is_admin() && isset($_GET['activated'] ) && $pagenow == "themes.php" ) {
	add_action( 'admin_notices', 'bp_dtheme_show_notice' );
}

/* Load the options page */
if ( is_admin() )
	require( TEMPLATEPATH . '/_inc/options.php' );

?>