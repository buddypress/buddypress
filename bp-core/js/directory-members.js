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
				'_wpnonce': jQuery("input#_wpnonce-member-filter").val(),
				'letter': letter[1],
				'page': 1,
				'num': 10
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				response = response.split('[[SPLIT]]');

				if ( response[0] != "-1" ) {
					jQuery("#member-dir-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-members').toggle();
							jQuery("#member-dir-list").html(response[1]);
							jQuery("#member-dir-list").fadeIn(200);
						}
					);

				} else {					
					jQuery("ul#members-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-members').toggle();
							var message = '<p><div id="message" class="error"><p>' + response[1] + '</p></div></p>';
							jQuery("#member-dir-list").html(message);
							jQuery("#member-dir-list").fadeIn(200);
						}
					);
				}
			});
		
			return false;
		}
	);
	
	jQuery("form#search-members-form").submit( function() { 
			jQuery('#ajax-loader-members').toggle();

			jQuery.post( ajaxurl, {
				action: 'directory_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-member-filter").val(),
				'members_search': jQuery("input#members_search").val(),
				'page': 1,
				'num': 10
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				response = response.split('[[SPLIT]]');

				if ( response[0] != "-1" ) {
					
					jQuery("#member-dir-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-members').toggle();
							jQuery("#member-dir-list").html(response[1]);
							jQuery("#member-dir-list").fadeIn(200);
						}
					);

				} else {
										
					jQuery("#member-dir-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-members').toggle();
							var message = '<p><div id="message" class="error"><p>' + response[1] + '</p></div></p>';
							jQuery("#member-dir-list").html(message);
							jQuery("#member-dir-list").fadeIn(200);
						}
					);
					
				}
			});
		
			return false;
		}
	);
	
	jQuery("div#member-dir-pag a").livequery('click',
		function() { 
			jQuery('#ajax-loader-members').toggle();

			var page = jQuery(this).attr('href');
			page = page.split('page=');
			page[1] = page[1].substr(0, 1);

			if ( !jQuery("input#selected_letter").val() )
				var letter = '';
			else
				var letter = jQuery("input#selected_letter").val();
						
			if ( !jQuery("input#search_terms").val() )
				var search_terms = '';
			else
				var search_terms = jQuery("input#search_terms").val();
				
			jQuery.post( ajaxurl, {
				action: 'directory_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'page': page[1],
				'num': 10,
				'_wpnonce': jQuery("input#_wpnonce-member-filter").val(),
				
				'letter': letter,
				'members_search': search_terms
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				response = response.split('[[SPLIT]]');

				if ( response[0] != "-1" ) {
					
					jQuery("#member-dir-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-members').toggle();
							jQuery("#member-dir-list").html(response[1]);
							jQuery("#member-dir-list").fadeIn(200);
						}
					);

				} else {
										
					jQuery("#member-dir-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-members').toggle();
							var message = '<p><div id="message" class="error"><p>' + response[1] + '</p></div></p>';
							jQuery("#member-dir-list").html(message);
							jQuery("#member-dir-list").fadeIn(200);
						}
					);
					
				}			
			});
			
			return false;
		}
	);
});
