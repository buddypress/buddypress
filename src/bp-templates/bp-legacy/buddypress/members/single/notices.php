<?php
/**
 * BuddyPress - Users Notices
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 * @version 15.0.0
 */

?>

<?php if ( bp_is_current_component( 'notices' ) ) : ?>
	<div class="item-list-tabs no-ajax" id="subnav" aria-label="<?php esc_attr_e( 'Member secondary navigation', 'buddypress' ); ?>" role="navigation">
		<ul>
			<?php bp_get_options_nav(); ?>
		</ul>
	</div>
<?php endif; ?>

<div class="notices">

	<?php bp_output_notices(); ?>

</div><!-- .notices -->
