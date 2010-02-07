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

	<?php if ( 'pingback' == $comment->comment_type ) return false; ?>

	<li id="comment-<?php comment_ID(); ?>">
		<div class="comment-avatar-box">
			<div class="avb">
				<a href="<?php echo get_comment_author_url() ?>">
					<?php if ( $comment->user_id ) : ?>
						<?php echo bp_core_fetch_avatar( array( 'item_id' => $comment->user_id, 'width' => 50, 'height' => 50, 'email' => $comment->comment_author_email ) ); ?>
					<?php else : ?>
						<?php echo get_avatar( $comment, 50 ) ?>
					<?php endif; ?>
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

function bp_dtheme_wp_pages_filter( $page_html ) {
	if ( 'page_on_front' != substr( $page_html, 14, 13 ) )
		return $page_html;

	$selected = false;
	$page_html = str_replace( '</select>', '', $page_html );

	if ( bp_dtheme_page_on_front() == 'activity' )
		$selected = ' selected="selected"';

	$page_html .= '<option class="level-0" value="activity"' . $selected . '>' . __( 'Activity Stream', 'buddypress' ) . '</option></select>';
	return $page_html;
}
add_filter( 'wp_dropdown_pages', 'bp_dtheme_wp_pages_filter' );

function bp_dtheme_page_on_front_update( $oldvalue, $newvalue ) {
	if ( !is_admin() || !is_site_admin() )
		return false;

	if ( 'activity' == $_POST['page_on_front'] )
		return 'activity';
	else
		return $oldvalue;
}
add_action( 'pre_update_option_page_on_front', 'bp_dtheme_page_on_front_update', 10, 2 );

function bp_dtheme_page_on_front_template( $template ) {
	global $wp_query;

	if ( empty( $wp_query->post->ID ) )
		return locate_template( array( 'activity/index.php' ), false );
	else
		return $template;
}
add_filter( 'page_template', 'bp_dtheme_page_on_front_template' );

/* Return the ID of a page set as the home page. */
function bp_dtheme_page_on_front() {
	return apply_filters( 'bp_dtheme_page_on_front', get_option( 'page_on_front' ) );
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

/* Adjust home page body class if activity stream is home */
function bp_dtheme_body_class_home( $classes, $bp_classes, $wp_classes, $custom_classes ) {
	if ( !is_home() )
		return apply_filters( 'bp_dtheme_body_class_home', $classes, $bp_classes, $wp_classes, $custom_classes );

	if ( bp_is_active( 'activity' ) ) {
		if ( 'activity' == bp_dtheme_page_on_front() ) {
			$blog = array_keys( $classes, 'blog-page' );
			$classes[$blog[0]] = 'activity';
			$classes[] = 'directory';
			$classes[] = 'internal-page';
			$classes[] = 'my-activity';
		}
	}
	return apply_filters( 'bp_dtheme_body_class_home', $classes, $bp_classes, $wp_classes, $custom_classes );
}
add_filter( 'bp_get_the_body_class', 'bp_dtheme_body_class_home', 10, 4 );

?>