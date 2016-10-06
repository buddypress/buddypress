(function($){
	$(document).ready(function() {
		$( '.bp-is-dismissible .notice-dismiss' ).click( function() {
			var $notice = $( this ).closest( '.notice' );
			var notice_id = $notice.data( 'noticeid' );
			$.post( {
				url: ajaxurl,
				data: {
					action: 'bp_dismiss_notice',
					nonce: $( '#bp-dismissible-nonce-' + notice_id ).val(),
					notice_id: $notice.data( 'noticeid' )
				}
			} );
		} );
	});
}(jQuery));
