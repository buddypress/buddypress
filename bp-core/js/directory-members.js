jQuery(document).ready( function() {
	jQuery("ul#letter-list li a").livequery('click',
		function() { 
			jQuery('#ajax-loader-members').toggle();

			jQuery("div#members-list-options a").removeClass("selected");
			jQuery(this).addClass('selected');

			var letter = jQuery(this).attr('id')
			letter = letter.split('-');

			jQuery.post( ajaxurl, {
				action: 'directory_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'letter': letter[1],
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				response = response.split('[[SPLIT]]');

				if ( response[0] != "-1" ) {
					jQuery("ul#members-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-members').toggle();
							jQuery("ul#members-list").html(response[1]);
							jQuery("ul#members-list").fadeIn(200);
						}
					);

				} else {					
					jQuery("ul#members-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-members').toggle();
							var message = '<p><div id="message" class="error"><p>' + response[1] + '</p></div></p>';
							jQuery("ul#members-list").html(message);
							jQuery("ul#members-list").fadeIn(200);
						}
					);
				}
			});
		
			return false;
		}
	);
});
