<?php

function bp_forums_add_js() {
	global $bp;
?>
	<script type="text/javascript">
		jQuery(document).ready( function() {
			jQuery("a#topic-delete-link").click( function() {
				if ( confirm( '<?php _e( 'Are you sure you want to delete this topic?', 'buddypress' ) ?>' ) ) 
					return true;
				else
					return false;
			});
			
			jQuery("a#post-delete-link").click( function() {
				if ( confirm( '<?php _e( 'Are you sure you want to delete this post?', 'buddypress' ) ?>' ) ) 
					return true;
				else
					return false;
			});
	
			jQuery("a#topic-close-link").click( function() {
				if ( confirm( '<?php _e( 'Are you sure you want to close this topic?', 'buddypress' ) ?>' ) ) 
					return true;
				else
					return false;
			});
		});
	</script>
<?php
}

?>