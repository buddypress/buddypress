<?php
/**
 * Deprecated functions.
 *
 * @deprecated 2.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Print the generation time in the footer of the site.
 *
 * @since 1.0.0
 * @deprecated 2.6.0
 */
function bp_core_print_generation_time() {
?>

<!-- Generated in <?php timer_stop(1); ?> seconds. (<?php echo get_num_queries(); ?> q) -->

	<?php
}
