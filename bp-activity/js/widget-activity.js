jQuery(document).ready( function() {
	var j = jQuery;

	if ( j('div.widget_bp_activity_widget').length )
		bp_activity_widget_post( j.cookie('bp_atype'), j.cookie('bp_afilter') );

	/* New posts */
	j("input#aw-whats-new-submit").click( function() {
		var button = j(this);
		var form = button.parent().parent().parent().parent();

		form.children().each( function() {
			if ( j.nodeName(this, "textarea") || j.nodeName(this, "input") )
				j(this).attr( 'disabled', 'disabled' );
		});

		j( 'form#' + form.attr('id') + ' span.ajax-loader' ).show();

		/* Remove any errors */
		j('div.error').remove();
		button.attr('disabled','disabled');

		j.post( ajaxurl, {
			action: 'post_update',
			'cookie': encodeURIComponent(document.cookie),
			'_wpnonce_post_update': j("input#_wpnonce_post_update").val(),
			'content': j("textarea#whats-new").val(),
			'group': j("#whats-new-post-in").val()
		},
		function(response)
		{
			j( 'form#' + form.attr('id') + ' span.ajax-loader' ).hide();

			form.children().each( function() {
				if ( j.nodeName(this, "textarea") || j.nodeName(this, "input") )
					j(this).attr( 'disabled', '' );
			});

			/* Check for errors and append if found. */
			if ( response[0] + response[1] == '-1' ) {
				form.prepend( response.substr( 2, response.length ) );
				j( 'form#' + form.attr('id') + ' div.error').hide().fadeIn( 200 );
				button.attr("disabled", '');
			} else {
				if ( 0 == j("ul.activity-list").length ) {
					j("div.error").slideUp(100).remove();
					j("div.activity").append( '<ul id="site-wide-stream" class="activity-list item-list">' );
				}

				j("ul.activity-list").prepend(response);
				j("li.new-update").hide().slideDown( 300 );
				j("li.new-update").removeClass( 'new-update' );
				j("textarea#whats-new").val('');

				/* Re-enable the submit button after 8 seconds. */
				setTimeout( function() { button.attr("disabled", ''); }, 8000 );
			}
		});

		return false;
	});

	/* List tabs event delegation */
	j('div.item-list-tabs').click( function(event) {
		var target = j(event.target).parent();

		/* Activity Stream Tabs */
		if ( target.attr('id') == 'activity-all' ||
		 	 target.attr('id') == 'activity-friends' ||
			 target.attr('id') == 'activity-groups' ) {

			var type = target.attr('id').substr( 9, target.attr('id').length );
			var filter = j("#activity-filter-select select").val();

			bp_activity_widget_post(type, filter);

			return false;
		}
	});

	j('#activity-filter-select select').change( function() {
		var selected_tab = j( '.' + j(this).parent().parent().parent().attr('class') + ' li.selected');
		var type = selected_tab.attr('id').substr( 9, selected_tab.attr('id').length );
		var filter = j(this).val();

		bp_activity_widget_post(type, filter);

		return false;
	});

	/* Stream event delegation */
	j('div.widget_bp_activity_widget').click( function(event) {
		var target = j(event.target).parent();

		/* Load more updates at the end of the page */
		if ( target.attr('class') == 'load-more' ) {
			j("li.load-more span.ajax-loader").show();

			var oldest_page = ( j("input#aw-oldestpage").val() * 1 ) + 1;

			j.post( ajaxurl, {
				action: 'aw_get_older_updates',
				'cookie': encodeURIComponent(document.cookie),
				'query_string': j("input#aw-querystring").val(),
				'acpage': oldest_page
			},
			function(response)
			{
				j("li.load-more span.ajax-loader").hide();

				/* Check for errors and append if found. */
				if ( response[0] + response[1] != '-1' ) {
					var response = response.split('||');
					j("input#aw-querystring").val(response[0]);

					j("ul.activity-list").append(response[1]);
					j("input#aw-oldestpage").val( oldest_page );
				}

				target.hide();
			});

			return false;
		}
	});


	function bp_activity_widget_post(type, filter) {
		if ( null == type )
			var type = 'all';

		if ( null == filter )
			var filter = '-1';

		/* Save the type and filter to a session cookie */
		j.cookie( 'bp_atype', type, null );
		j.cookie( 'bp_afilter', filter, null );

		/* Set the correct selected nav and filter */
		j('.widget_bp_activity_widget div.item-list-tabs li').each( function() {
			j(this).removeClass('selected');
		});
		j('li#activity-' + type).addClass('selected');
		j('#activity-filter-select select option[value=' + filter + ']').attr( 'selected', 'selected' );

		/* Reload the activity stream based on the selection */
		j('.widget_bp_activity_widget h2 span.ajax-loader').show();

		j.post( ajaxurl, {
			action: 'activity_widget_filter',
			'cookie': encodeURIComponent(document.cookie),
			'_wpnonce_activity_filter': j("input#_wpnonce_activity_filter").val(),
			'type': type,
			'filter': filter
		},
		function(response)
		{
			j('.widget_bp_activity_widget h2 span.ajax-loader').hide();

			/* Check for errors and append if found. */
			if ( response[0] + response[1] == '-1' ) {
				j('div.activity').fadeOut( 100, function() {
					j(this).html( response.substr( 2, response.length ) ).hide().fadeIn( 200 );
					j(this).fadeIn(100);
				});
			} else {
				var response = response.split('||');
				j("input#aw-querystring").val(response[0]);

				j('div.activity').fadeOut( 100, function() {
					j(this).html(response[1]);
					j(this).fadeIn(100);
				});
			}
		});
	}
});

/* jQuery Cookie plugin */
eval(function(p,a,c,k,e,d){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--){d[e(c)]=k[c]||e(c)}k=[function(e){return d[e]}];e=function(){return'\\w+'};c=1};while(c--){if(k[c]){p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c])}}return p}('o.5=B(9,b,2){6(h b!=\'E\'){2=2||{};6(b===n){b=\'\';2.3=-1}4 3=\'\';6(2.3&&(h 2.3==\'j\'||2.3.k)){4 7;6(h 2.3==\'j\'){7=w u();7.t(7.q()+(2.3*r*l*l*x))}m{7=2.3}3=\'; 3=\'+7.k()}4 8=2.8?\'; 8=\'+(2.8):\'\';4 a=2.a?\'; a=\'+(2.a):\'\';4 c=2.c?\'; c\':\'\';d.5=[9,\'=\',C(b),3,8,a,c].y(\'\')}m{4 e=n;6(d.5&&d.5!=\'\'){4 g=d.5.A(\';\');s(4 i=0;i<g.f;i++){4 5=o.z(g[i]);6(5.p(0,9.f+1)==(9+\'=\')){e=D(5.p(9.f+1));v}}}F e}};',42,42,'||options|expires|var|cookie|if|date|path|name|domain|value|secure|document|cookieValue|length|cookies|typeof||number|toUTCString|60|else|null|jQuery|substring|getTime|24|for|setTime|Date|break|new|1000|join|trim|split|function|encodeURIComponent|decodeURIComponent|undefined|return'.split('|'),0,{}))
