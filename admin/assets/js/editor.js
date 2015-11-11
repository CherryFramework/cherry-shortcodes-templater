/* global activeAcc, macrosButtons, QTags */
jQuery( document ).ready(function() {
	jQuery( '#nav-container' ).accordion({
		collapsible: true,
		heightStyle: 'content',
		active: parseInt( activeAcc, 10 )
	});

	jQuery( '#edit-action' ).click(function() {
		jQuery( '#action-dropdown-list_' ).toggle();
		return false;
	});

	jQuery( this ).click(function() {
		jQuery( '#action-dropdown-list_' ).hide();
	});

	jQuery( '#message_' ).delay( 2000 ).fadeOut();
});

jQuery( window ).load(function() {
	var editor   = jQuery( '#wp-shortcode-template-editor-container' ),
		current  = jQuery( '#current-file' ).val(),
		newFile = jQuery( '#new-file-name' ),
		rename   = jQuery( '#rename' );

	jQuery.each( macrosButtons, function( index, value ) {
		addButton( value );
	});

	jQuery( '.ed_button' ).tooltip({
		tooltipClass: 'macros-tooltip',
		position: {
			at: 'center top-80'
		}
	});

	if ( 'default.tmpl' === current ) {
		editor
			.find( 'input,textarea' )
			.attr( 'disabled', true );
	}

	newFile.keyup(function() {
		jQuery( '#file-name-error' ).hide();
	});

	rename.on( 'click', function() {
		var filename = newFile.val(),
			filenameCheck = /([0-9a-z_-]+[\.][0-9a-z_-]{1,4})$/.test( filename );

		if ( false === filenameCheck ) {
			jQuery( '#file-name-error' ).show();

			return false;
		}
	});
});

function addButton( $obj ) {
	/* Adding Quicktag buttons to the editor WordPress
	 * - Button HTML ID (required)
	 * - Button display, value="" attribute (required)
	 * - Opening Tag (required)
	 * - Closing Tag (required)
	 * - Access key, accesskey="" attribute for the button (optional)
	 * - Title, title="" attribute (optional)
	 * - Priority/position on bar, 1-9 = first, 11-19 = second, 21-29 = third, etc. (optional)
	 */
	QTags.addButton(
		$obj.id,
		$obj.value,
		$obj.open,
		$obj.close,
		$obj.key,
		$obj.title
	);
}
