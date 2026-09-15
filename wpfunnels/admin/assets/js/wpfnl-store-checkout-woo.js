/* global jQuery, wp, wpfnlStoreCheckoutWoo */

/**
 * Store Checkout — WooCommerce dependency modal.
 *
 * Installs WooCommerce through the core updates queue and activates it through
 * the `plugin_install_success` ajax action, then reloads the Store Checkout
 * screen so the real listing takes over.
 *
 * @since 3.13.1
 */
( function( $ ) {
	'use strict';

	var WPFnlStoreCheckoutWoo = {

		/**
		 * Localized settings.
		 */
		settings: window.wpfnlStoreCheckoutWoo || {},

		/**
		 * Action button.
		 */
		$button: null,

		/**
		 * Get the action button, resolving it lazily.
		 */
		getButton: function() {
			if ( ! this.$button || ! this.$button.length ) {
				this.$button = $( '#wpfnl-wc-required-action' );
			}
			return this.$button;
		},

		/**
		 * Put the button in a busy state with the given label.
		 */
		setBusy: function( label ) {
			var $button = this.getButton();
			$button.addClass( 'is-busy' ).attr( 'aria-busy', 'true' );
			$button.find( '.wpfnl-wc-required-modal__label' ).text( label );
		},

		/**
		 * Restore the button to its idle state.
		 */
		resetButton: function() {
			var $button = this.getButton();
			$button.removeClass( 'is-busy' ).removeAttr( 'aria-busy' );
			$button.find( '.wpfnl-wc-required-modal__label' ).text( this.settings.buttonLabel || '' );
		},

		/**
		 * Show an error message inside the modal.
		 */
		showError: function( message ) {
			$( '.wpfnl-wc-required-modal__error' ).text( message ).show();
		},

		/**
		 * Hide any previously shown error message.
		 */
		hideError: function() {
			$( '.wpfnl-wc-required-modal__error' ).hide().text( '' );
		},

		/**
		 * Activate WooCommerce and reload the screen.
		 */
		activate: function() {
			var self = this,
				i18n = this.settings.i18n || {};

			self.setBusy( i18n.activating );

			$.ajax( {
				url: self.settings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'plugin_install_success',
					plugin_init: self.settings.plugin,
					security: self.settings.nonce
				}
			} ).done( function( response ) {
				if ( response && response.success ) {
					self.setBusy( i18n.redirecting );
					window.location.reload();
					return;
				}

				self.showError( ( response && response.data && response.data.message ) || i18n.error );
				self.resetButton();
			} ).fail( function() {
				self.showError( i18n.error );
				self.resetButton();
			} );
		},

		/**
		 * Queue the WooCommerce install through the core updates handler.
		 */
		install: function() {
			var i18n = this.settings.i18n || {};

			if ( ! window.wp || ! wp.updates || ! wp.updates.queue ) {
				this.showError( i18n.error );
				return;
			}

			this.setBusy( i18n.installing );

			wp.updates.queue.push( {
				action: 'install-plugin',
				data: {
					slug: this.settings.slug
				}
			} );
			wp.updates.queueChecker();
		},

		/**
		 * Handle a click on the modal action button.
		 */
		onAction: function( e ) {
			e.preventDefault();

			var self = WPFnlStoreCheckoutWoo;

			if ( self.getButton().hasClass( 'is-busy' ) ) {
				return;
			}

			self.hideError();

			if ( 'not-installed' === self.settings.status ) {
				self.install();
				return;
			}

			self.activate();
		},

		/**
		 * WooCommerce finished installing — activate it right away.
		 */
		onInstallSuccess: function() {
			WPFnlStoreCheckoutWoo.activate();
		},

		/**
		 * WooCommerce failed to install.
		 */
		onInstallError: function( e, response ) {
			var self = WPFnlStoreCheckoutWoo,
				i18n = self.settings.i18n || {};

			self.showError( ( response && response.errorMessage ) || i18n.error );
			self.resetButton();
		},

		init: function() {
			$( document )
				.on( 'click', '#wpfnl-wc-required-action', this.onAction )
				.on( 'wp-plugin-install-success', this.onInstallSuccess )
				.on( 'wp-plugin-install-error', this.onInstallError );
		}
	};

	$( function() {
		WPFnlStoreCheckoutWoo.init();
	} );

}( jQuery ) );
