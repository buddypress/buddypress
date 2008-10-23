<div id="wire-post-new">
	<form action="<?php bp_wire_get_action() ?>" id="wire-post-new-form" method="post">
		<div id="wire-post-new-metadata">
			<?php bp_wire_poster_avatar() ?>
			On <?php bp_wire_poster_date() ?> 
			<?php bp_wire_poster_name() ?> said:
		</div>
	
		<div id="wire-post-new-input">
			<textarea name="wire-post-textarea" id="wire-post-textarea"></textarea>
			<input type="submit" name="wire-post-submit" id="wire-post-submit" value="Post &raquo;" />
		</div>
	</form>
</div>