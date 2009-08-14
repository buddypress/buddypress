jQuery(document).ready( function() {
	jQuery("div.friendship-button a").livequery('click',
		function() {
			jQuery(this).parent().addClass('loading');
			
			var fid = jQuery(this).attr('id');
			fid = fid.split('-');
			fid = fid[1];
			
			var nonce = jQuery(this).attr('href');
			nonce = nonce.split('?_wpnonce=');
			nonce = nonce[1].split('&');
			nonce = nonce[0];

			var thelink = jQuery(this);

			jQuery.post( ajaxurl, {
				action: 'addremove_friend',
				'cookie': encodeURIComponent(document.cookie),
				'fid': fid,
				'_wpnonce': nonce
			},
			function(response)
			{
				response = response.substr(0, response.length-1);

				var action = thelink.attr('rel');
				var parentdiv = thelink.parent();
				
				if ( action == 'add' ) {
					jQuery(parentdiv).fadeOut(200, 
						function() {
							parentdiv.removeClass('add_friend');
							parentdiv.removeClass('loading');
							parentdiv.addClass('pending');
							parentdiv.fadeIn(200).html(response);
						}
					);

				} else if ( action == 'remove' ) {
					jQuery(parentdiv).fadeOut(200, 
						function() {
							parentdiv.removeClass('remove_friend');
							parentdiv.removeClass('loading');
							parentdiv.addClass('add');
							parentdiv.fadeIn(200).html(response);
						}
					);				
				}
			});
			return false;
		}
	);
});

jQuery("div#wire-pagination a").livequery('click',
	function() { 
		jQuery('#ajax-loader').toggle();

		var fpage = jQuery(this).attr('href');
		fpage = fpage.split('=');

		jQuery.post( ajaxurl, {
			action: 'get_wire_posts',
			'cookie': encodeURIComponent(document.cookie),
			'_wpnonce': jQuery("input#_wpnonce").val(),
			'wpage': fpage[1],
			'bp_wire_item_id': jQuery("input#bp_wire_item_id").val()
		},
		function(response)
		{	
			jQuery('#ajax-loader').toggle();
			
			response = response.substr(0, response.length-1);

			jQuery("form#wire-post-list-form").fadeOut(200, 
				function() {
					jQuery("form#wire-post-list-form").html(response);
					jQuery("form#wire-post-list-form").fadeIn(200);
				}
			);

			return false;
		});
		
		return false;
	}
);

function clear(container) {
	if(!document.getElementById(container)) return false;
	
	var container = document.getElementById(container);
	
	radioButtons = container.getElementsByTagName('INPUT');

	for(var i=0; i<radioButtons.length; i++) {
		radioButtons[i].checked = false;
	}	
}
