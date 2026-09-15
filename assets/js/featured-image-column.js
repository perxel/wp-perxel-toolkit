/* global jQuery, wp, pxtkFeaturedImageColumn, ajaxurl */
jQuery( function ( $ ) {
	'use strict';

	if ( typeof pxtkFeaturedImageColumn === 'undefined' ) {
		return;
	}

	var mediaFrame;

	$( document ).on( 'click', '.pxtk-featured-image-column', function ( e ) {
		e.preventDefault();

		var $wrapper = $( this );
		var postId   = $wrapper.data( 'post-id' );

		if ( mediaFrame ) {
			mediaFrame.off( 'select' );
		}

		mediaFrame = wp.media( {
			title: pxtkFeaturedImageColumn.i18nTitle || 'Set Featured Image',
			button: { text: pxtkFeaturedImageColumn.i18nButton || 'Set Featured Image' },
			multiple: false,
		} );

		mediaFrame.on( 'select', function () {
			var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();

			$.post( pxtkFeaturedImageColumn.ajaxUrl, {
				action: pxtkFeaturedImageColumn.action,
				post_id: postId,
				thumbnail_id: attachment.id,
				nonce: pxtkFeaturedImageColumn.nonce,
			} ).done( function ( response ) {
				if ( ! response.success ) {
					return;
				}

				var imageUrl = attachment.sizes && attachment.sizes.thumbnail
					? attachment.sizes.thumbnail.url
					: attachment.url;

				$wrapper
					.removeClass( 'pxtk-featured-image-column--empty' )
					.html( $( '<img>' ).attr( 'src', imageUrl ) )
					.data( 'thumbnail-id', attachment.id );
			} );
		} );

		mediaFrame.open();
	} );
} );
