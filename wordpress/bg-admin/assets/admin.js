/* Bio-Goods Admin – nyomtatás és „összes kijelölése”. */
( function () {
	document.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '[data-bg-print]' ) ) {
			e.preventDefault();
			window.print();
		}
	} );
	document.addEventListener( 'change', function ( e ) {
		var all = e.target.closest( '[data-bg-check-all]' );
		if ( ! all ) return;
		var table = all.closest( 'table' );
		table.querySelectorAll( 'tbody input[type=checkbox]' ).forEach( function ( cb ) { cb.checked = all.checked; } );
	} );
} )();
