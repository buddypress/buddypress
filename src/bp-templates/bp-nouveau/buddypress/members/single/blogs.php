<?php
/**
 * BuddyPress - Users Blogs
 *
 * @since 3.0.0
 * @version 12.0.0
 */
?>

<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Sites menu', 'buddypress' ); ?>">
	<ul id="member-secondary-nav" class="subnav bp-priority-subnav-nav-items">

		<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

	</ul>

	<?php bp_nouveau_member_hook( '', 'secondary_nav' ); ?>
</nav><!-- .bp-navs -->

<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

<?php
switch ( bp_current_action() ) :

	// Home/My Blogs
	case 'my-sites':
		bp_nouveau_member_hook( 'before', 'blogs_content' );
		?>

		<div class="blogs myblogs" data-bp-list="blogs">

			<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'member-blogs-loading' ); ?></div>

		</div><!-- .blogs.myblogs -->

		<?php
		bp_nouveau_member_hook( 'after', 'blogs_content' );
		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
