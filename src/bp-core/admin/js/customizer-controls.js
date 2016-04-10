/**
 * Customizer controls implementation.
 *
 * If you're looking to add JS for a specific panel or control, don't add it here.
 * The file only implements generic Customizer control implementations.
 *
 * @since 2.5.0
 */

(function( $ ) {
	$( window ).on( 'load', function() {
		/**
		 * <range> element: update label when value changes.
		 *
		 * @since 2.5.0
		 */
		$( '.customize-control-range input' ).on( 'input', function() {
			var $this = $( this );
			$this.siblings( 'output' ).text( $this.val() );
		});
	});
})( jQuery );
