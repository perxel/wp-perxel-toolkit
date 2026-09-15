/*
 * Settings screen behaviour. Loaded only on the Settings page (see Admin::assets).
 * The unsaved-changes guard comes from the kit via `data-pxui-dirty-guard` on
 * the form - nothing to wire here. Add AJAX handlers (a "Test" button, a live
 * preview) below as the plugin grows.
 */
( function () {
	'use strict';

	// A module's on/off switch (.pxtk-row-toggle) can sit inside a
	// disclosure row's <summary>, alongside the chevron that expands its
	// "Configure" fields. <summary>'s own toggle-open behaviour runs
	// whenever a click bubbles through it uncancelled - including one that
	// started on a nested checkbox - so the switch must stop its click from
	// bubbling past itself. A listener on `document` fires too late for
	// this (document is the last stop in the bubble phase, well after
	// <summary> has already reacted), so this binds directly to each
	// switch instead.
	document.querySelectorAll( '.pxtk-row-toggle' ).forEach( function ( toggle ) {
		toggle.addEventListener( 'click', function ( event ) {
			event.stopPropagation();
		} );
	} );

	// const { __ } = wp.i18n;
	// Example: wire a "Test" button to an admin-ajax action here.
} )();
