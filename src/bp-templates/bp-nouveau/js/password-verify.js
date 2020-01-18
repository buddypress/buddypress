/* jshint undef: false */
/* Password Verify */
/* global pwsL10n */
/* @since 3.0.0 */
/* @version 5.0.0 */
( function( $ ){
	/**
	 * Function to inform the user about the strength of its password.
	 *
	 * @deprecated since version 5.0.0.
	 */
	function check_pass_strength() {
		var pass1 = $( '.password-entry' ).val(),
		    pass2 = $( '.password-entry-confirm' ).val(),
		    strength;

		// Reset classes and result text.
		$( '#pass-strength-result' ).removeClass( 'show mismatch short bad good strong' );
		if ( ! pass1 ) {
			$( '#pass-strength-result' ).html( pwsL10n.empty );
			return;
		}

		strength = wp.passwordStrength.meter( pass1, wp.passwordStrength.userInputBlacklist(), pass2 );

		switch ( strength ) {
			case 2:
				$( '#pass-strength-result' ).addClass( 'show bad' ).html( pwsL10n.bad );
				break;
			case 3:
				$( '#pass-strength-result' ).addClass( 'show good' ).html( pwsL10n.good );
				break;
			case 4:
				$( '#pass-strength-result' ).addClass( 'show strong' ).html( pwsL10n.strong );
				break;
			case 5:
				$( '#pass-strength-result' ).addClass( 'show mismatch' ).html( pwsL10n.mismatch );
				break;
			default:
				$( '#pass-strength-result' ).addClass( 'show short' ).html( pwsL10n['short'] );
				break;
		}
	}

	// Bind check_pass_strength to keyup events in the password fields.
	$( document ).ready( function() {
		$( '.password-entry' ).val( '' ).keyup( check_pass_strength );
		$( '.password-entry-confirm' ).val( '' ).keyup( check_pass_strength );

		// Display a deprecated warning.
		console.warn( 'The bp-nouveau/js/password-verify.js script is deprecated since 5.0.0 and will be deleted in version 6.0.0.' );
	} );

} )( jQuery );
