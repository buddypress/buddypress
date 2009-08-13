<?php

function bp_get_options_class() {
	global $bp;

	if ( ( !bp_is_home() && $bp->current_component == $bp->profile->slug ) || ( !bp_is_home() && $bp->current_component == $bp->friends->slug ) || ( !bp_is_home() && $bp->current_component == $bp->blogs->slug ) ) {
		echo ' class="arrow"';
	}
	
	if ( ( $bp->current_component == $bp->groups->slug && $bp->is_single_item ) || ( $bp->current_component == $bp->groups->slug && !bp_is_home() ) )
		echo ' class="arrow"';	
}

function bp_has_icons() {
	global $bp;

	if ( ( !bp_is_home() ) )
		echo ' class="icons"';
}

?>