/* global bpSignupPreview */
/**
 * Opens a modal to preview a signup/membership request.
 *
 * @since 10.0.0
 */
( function( $ ) {
	// Bail if not set or if Thickbox is not available.
	if ( typeof bpSignupPreview === 'undefined' || 'function' !== typeof window.tb_show ) {
		return;
	}

	$( function() {
		$( '.bp-thickbox' ).on( 'click', function( e ) {
			e.preventDefault();

			var fragment = $( e.target ).prop( 'href' ).split( '#TB_inline&' )[1];

			window.tb_show( 'BuddyPress', '#TB_inline?' + fragment );
			window.bpAdjustThickbox( bpSignupPreview.modalLabel, '1em' );
		} );
	} );
}( jQuery ) );
