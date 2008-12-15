jQuery(document).ready( function() {
	jQuery("ul#letter-list li a").livequery('click',
		function() { 
			jQuery('#ajax-loader-groups').toggle();

			jQuery("div#groups-list-options a").removeClass("selected");
			jQuery(this).addClass('selected');

			var letter = jQuery(this).attr('id')
			letter = letter.split('-');

			jQuery.post( ajaxurl, {
				action: 'directory_groups',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-group-filter").val(),
				'letter': letter[1],
				'page': 1,
				'num': 10
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				response = response.split('[[SPLIT]]');

				if ( response[0] != "-1" ) {
					jQuery("#group-dir-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-groups').toggle();
							jQuery("#group-dir-list").html(response[1]);
							jQuery("#group-dir-list").fadeIn(200);
						}
					);

				} else {					
					jQuery("ul#groups-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-groups').toggle();
							var message = response[1];
							jQuery("#group-dir-list").html(message);
							jQuery("#group-dir-list").fadeIn(200);
						}
					);
				}
			});
		
			return false;
		}
	);
	
	jQuery("form#search-groups-form").submit( function() { 
			jQuery('#ajax-loader-groups').toggle();

			jQuery.post( ajaxurl, {
				action: 'directory_groups',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-group-filter").val(),
				'groups_search': jQuery("input#groups_search").val(),
				'page': 1,
				'num': 10
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				response = response.split('[[SPLIT]]');

				if ( response[0] != "-1" ) {
					
					jQuery("#group-dir-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-groups').toggle();
							jQuery("#group-dir-list").html(response[1]);
							jQuery("#group-dir-list").fadeIn(200);
						}
					);

				} else {
										
					jQuery("#group-dir-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-groups').toggle();
							var message = response[1];
							jQuery("#group-dir-list").html(message);
							jQuery("#group-dir-list").fadeIn(200);
						}
					);
					
				}
			});
		
			return false;
		}
	);
	
	jQuery("div#group-dir-pag a").livequery('click',
		function() { 
			jQuery('#ajax-loader-groups').toggle();

			var page = jQuery(this).attr('href');
			page = page.split('page=');
			
			if ( !jQuery("input#selected_letter").val() )
				var letter = '';
			else
				var letter = jQuery("input#selected_letter").val();
						
			if ( !jQuery("input#search_terms").val() )
				var search_terms = '';
			else
				var search_terms = jQuery("input#search_terms").val();
				
			jQuery.post( ajaxurl, {
				action: 'directory_groups',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'page': page[1],
				'num': 10,
				'_wpnonce': jQuery("input#_wpnonce-group-filter").val(),
				
				'letter': letter,
				'groups_search': search_terms
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				response = response.split('[[SPLIT]]');

				if ( response[0] != "-1" ) {
					
					jQuery("#group-dir-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-groups').toggle();
							jQuery("#group-dir-list").html(response[1]);
							jQuery("#group-dir-list").fadeIn(200);
						}
					);

				} else {
										
					jQuery("#group-dir-list").fadeOut(200, 
						function() {
							jQuery('#ajax-loader-groups').toggle();
							var message = response[1];
							jQuery("#group-dir-list").html(message);
							jQuery("#group-dir-list").fadeIn(200);
						}
					);
					
				}			
			});
			
			return false;
		}
	);
	
	jQuery("div.group-button a").livequery('click',
		function() {
			var gid = jQuery(this).parent().attr('id');
			gid = gid.split('-');
			gid = gid[1];

			var thelink = jQuery(this);

			jQuery.post( ajaxurl, {
				action: 'joinleave_group',
				'cookie': encodeURIComponent(document.cookie),
				'gid': gid
			},
			function(response)
			{
				response = response.substr(0, response.length-1);
				var parentdiv = thelink.parent();

				jQuery(parentdiv).fadeOut(200, 
					function() {
						parentdiv.fadeIn(200).html(response);
					}
				);
			});
			return false;
		}
	);
});
