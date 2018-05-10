<?php
/**
 * BuddyPress - Groups plugins
 *
 * @since 3.0.0
 * @version 3.0.0
 */

bp_nouveau_group_hook( 'before', 'plugin_template' );

bp_nouveau_plugin_hook( 'content' );

bp_nouveau_group_hook( 'after', 'plugin_template' );
