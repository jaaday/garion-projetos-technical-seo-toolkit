( function ( wp, data ) {
	'use strict';

	if ( ! wp || ! wp.apiFetch ) {
		return;
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var button = document.getElementById( 'gpseo-scan-now' );
		var message = document.getElementById( 'gpseo-scan-message' );

		if ( ! button ) {
			return;
		}

		function poll() {
			wp.apiFetch( { path: '/' + data.restNamespace + '/broken-links/status' } ).then( function ( status ) {
				if ( 'running' === status.status ) {
					message.textContent = data.i18n.scanning;
					setTimeout( poll, 3000 );
				} else {
					message.textContent = data.i18n.done;
					window.location.reload();
				}
			} );
		}

		if ( '1' === button.getAttribute( 'data-scanning' ) ) {
			poll();
		}

		button.addEventListener( 'click', function () {
			button.disabled = true;
			message.textContent = data.i18n.scanning;

			wp.apiFetch( {
				path: '/' + data.restNamespace + '/broken-links/scan',
				method: 'POST',
			} ).then( function () {
				poll();
			} );
		} );
	} );
} )( window.wp, window.gpseoData || {} );
