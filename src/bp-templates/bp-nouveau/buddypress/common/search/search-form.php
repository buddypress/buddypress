<?php
/**
* BP Object search form
*
* @since 1.0.0
*/
?>

<div class="<?php bp_nouveau_search_container_class(); ?> bp-search">
	<form action="" method="get" class="bp-dir-search-form" id="<?php bp_nouveau_search_selector_id( 'search-form' ); ?>" role="search" data-bp-search="groups">

		<label for="<?php bp_nouveau_search_selector_id( 'search' ); ?>" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( '', false ); ?></label>

		<input id="<?php bp_nouveau_search_selector_id( 'search' ); ?>" name="<?php bp_nouveau_search_selector_name(); ?>" type="search"  placeholder="<?php bp_nouveau_search_default_text(); ?>" />

		<button type="submit" id="<?php bp_nouveau_search_selector_id( 'search-submit' ); ?>" class="nouveau-search-submit" name="<?php bp_nouveau_search_selector_name( 'search_submit' ); ?>">
			<span class="dashicons dashicons-search" aria-hidden="true"></span>
			<span id="button-text" class="bp-screen-reader-text"><?php _e( 'Search', 'buddypress' ); ?></span>
		</button>

	</form>
</div>
