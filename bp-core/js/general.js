jQuery(document).ready( function() {
	jQuery("div.friendship-button a").livequery('click',
		function(e) {
			jQuery(this).toggle();
			jQuery(this).before('<span id="working">Working...</span>');
		
			var fid = jQuery(this).attr('id');
			fid = fid.split('-');
			fid = fid[1];
			
			var link = jQuery(this);
		
			jQuery.post( ajaxurl, {
				action: 'addremove_friend',
				'cookie': encodeURIComponent(document.cookie),
				'fid': fid
			},
			function(response)
			{
				console.log(response);
				response = response.substr(0, response.length-1);
				response = response.split('[[SPLIT]]');
			
				if ( response[0] != "-1" ) {
					var action = link.attr('rel');
				
					if ( action == 'add' ) {
						jQuery("#working").html(response[0]);
					} else {
						jQuery("#working").toggle();
						link.html(response[0]);
						link.attr('rel', 'add');
						link.removeClass('remove');
						link.toggle();
					}
				}
			});
			return false;
		}
	);
});