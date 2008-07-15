jQuery(document).ready( function() {
	jQuery("a#addremove-friend").click(
		function(e) {
			jQuery.post( ajaxurl, {
				action: 'addremove_friend',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val()
			},
			function(response)
			{
				console.log(response);
				response = response.substr(0, response.length-1);
				response = response.split('[[SPLIT]]');
				
				if ( response[0] != "-1" ) {
					var action = jQuery("a#addremove-friend").attr('rel');
					
					if ( action == 'add' ) {
						jQuery("#friendship-button").html(response[0]);
					} else {
						jQuery("a#addremove-friend").html(response[0]);
						jQuery("a#addremove-friend").attr('rel', 'add');
						jQuery("a#addremove-friend").removeClass('remove');
					}
				}
				
				
			});

			return false;
		}
	);
});