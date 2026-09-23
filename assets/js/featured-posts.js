/* global jQuery, inlineEditPost */
/**
 * Featured Posts on the Posts list screen: pre-tick Quick Edit's Featured box
 * from the row, and keep the title star in sync after an inline save.
 */
( function ( $ ) {
	var wpInlineEdit = inlineEditPost.edit;

	inlineEditPost.edit = function ( id ) {
		wpInlineEdit.apply( this, arguments );

		var postId = 0;
		if ( typeof id === 'object' ) {
			postId = parseInt( this.getId( id ), 10 );
		}

		if ( postId > 0 ) {
			var $row     = $( '#post-' + postId );
			var $editRow = $( '#edit-' + postId );
			var isFeatured = $row.find( '.column-pxtk_featured' ).text().trim() === '⭐';
			$editRow.find( 'input[name="pxtk_featured_post"]' ).prop( 'checked', isFeatured );
		}
	};

	$( document ).ajaxComplete( function ( event, xhr, settings ) {
		if ( ! settings.data || settings.data.indexOf( 'action=inline-save' ) === -1 ) {
			return;
		}

		var match = settings.data.match( /post_ID=(\d+)/ );
		if ( ! match ) {
			return;
		}

		var $row = $( '#post-' + match[1] );

		setTimeout( function () {
			var $title      = $row.find( '.row-title' );
			var isFeatured  = $row.find( '.column-pxtk_featured' ).text().trim() === '⭐';
			var titleText   = $title.text().replace( /\s*⭐\s*$/, '' ).trim();
			$title.text( isFeatured ? titleText + ' ⭐' : titleText );
		}, 100 );
	} );
} )( jQuery );

