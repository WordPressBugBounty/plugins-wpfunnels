/**
 * Send the block editor back button to the funnel canvas.
 *
 * The editor's own back button is built by @wordpress/edit-post and always
 * links to `edit.php?post_type=<step cpt>`, which drops the user on the step
 * list instead of the funnel they were editing. There is no public filter for
 * that href, so the link is rewritten here and the click is intercepted as a
 * fallback in case the button re-renders after the rewrite.
 *
 * @since 3.13.2
 */
( function () {
	'use strict';

	var settings = window.wpfnlGutenbergBack || {};

	if ( ! settings.funnelUrl || ! settings.postType ) {
		return;
	}

	var SELECTOR = 'a[href*="post_type=' + settings.postType + '"]';

	/**
	 * Whether an anchor is the editor back button pointing at the step list.
	 *
	 * @param {HTMLAnchorElement} link Anchor to test.
	 * @return {boolean} True when the anchor should go to the funnel canvas.
	 */
	function isStepListLink( link ) {
		var href = link.getAttribute( 'href' ) || '';
		return href.indexOf( 'edit.php' ) !== -1 &&
			href.indexOf( 'post_type=' + settings.postType ) !== -1;
	}

	/**
	 * Repoint an anchor at the funnel canvas.
	 *
	 * @param {HTMLAnchorElement} link Anchor to rewrite.
	 */
	function repoint( link ) {
		link.setAttribute( 'href', settings.funnelUrl );

		if ( settings.label ) {
			link.setAttribute( 'aria-label', settings.label );
		}
	}

	/**
	 * Rewrite every step list link currently in the document.
	 *
	 * @return {boolean} True when at least one link was rewritten.
	 */
	function rewrite() {
		var links = document.querySelectorAll( SELECTOR );
		var found = false;
		var i;

		for ( i = 0; i < links.length; i++ ) {
			if ( isStepListLink( links[ i ] ) ) {
				repoint( links[ i ] );
				found = true;
			}
		}

		return found;
	}

	/**
	 * Poll for the back button, which mounts after the editor boots.
	 */
	function watchForBackButton() {
		var attempts = 0;
		var timer = window.setInterval( function () {
			attempts++;

			if ( rewrite() || attempts >= 20 ) {
				window.clearInterval( timer );
			}
		}, 300 );
	}

	// Catch clicks on a button that mounted (or re-rendered) after the poll ended.
	document.addEventListener( 'click', function ( event ) {
		var link = event.target && event.target.closest ?
			event.target.closest( 'a[href]' ) :
			null;

		if ( ! link || ! isStepListLink( link ) ) {
			return;
		}

		repoint( link );

		// Let modified clicks (new tab, new window) follow the corrected href.
		if ( event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
			return;
		}

		event.preventDefault();
		window.location.href = settings.funnelUrl;
	}, true );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', watchForBackButton );
	} else {
		watchForBackButton();
	}
} )();
