<template>
	<div class="wpfnl-wz-center wpfnl-wz-welcome">
		<div class="wpfnl-wz-welcome-main">
			<h1 class="wpfnl-wz-h1">
				Welcome to WPFunnels
			</h1>

			<p class="wpfnl-wz-sub">
				You're minutes away from a higher-converting WooCommerce store.
			</p>

			<figure class="wpfnl-wz-art">
				<img
					:src="welcomeImage"
					alt="A WPFunnels funnel showing landing, checkout, upsell, downsell and thank you steps"
					width="1450"
					height="832"
				/>
			</figure>

			<button type="button" class="wpfnl-wz-btn wpfnl-wz-btn--primary wpfnl-wz-btn--lg" @click="submit">
				Let's Start
				<svg width="15" height="11" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<path d="M1 6H16M16 6L11 1M16 6L11 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</button>
		</div>

		<label class="wpfnl-wz-consent">
			<input type="checkbox" v-model="consent" />
			<span class="wpfnl-wz-consent-box" aria-hidden="true"></span>
			<span class="wpfnl-wz-consent-text">
				Help improve WPFunnels by sharing non-sensitive usage data. We don't collect
				any personal information.
				<a
					:href="privacyUrl"
					class="wpfnl-wz-consent-link"
					target="_blank"
					rel="noopener noreferrer"
					@click.stop
				>Learn more</a>
			</span>
		</label>
	</div>
</template>

<script>
import apiFetch from '@wordpress/api-fetch';

export default {
	name: 'Welcome',
	props: {
		agreeToShare: {
			type: Boolean,
			default: true,
		},
	},
	emits: [ 'nav', 'next-step' ],
	data() {
		return {
			consent: this.agreeToShare,
			welcomeImage: window?.setup_wizard_obj?.welcome_image || '',
			// Static — the wizard doesn't otherwise depend on PHP for this, so keep the link on the frontend.
			privacyUrl: 'https://getwpfunnels.com/privacy-policy/',
			contactRequestInFlight: false,
			contactCreated: false,
		};
	},
	mounted() {
		this.publishNav();
	},
	methods: {
		publishNav() {
			this.$emit( 'nav', {
				label: 'Get started',
				canProceed: true,
				busy: false,
				showBack: false,
				showNext: true,
			} );
		},

		/**
		 * Register the admin as a Mail Mint contact.
		 *
		 * Only runs when the user has opted in, and only once — the wizard
		 * creates the contact here rather than on completion so a user who
		 * drops out later is still reachable.
		 */
		maybeCreateContact() {
			if ( ! this.consent || this.contactCreated || this.contactRequestInFlight ) {
				return;
			}

			const wizardObj = window.setup_wizard_obj || {};
			if ( ! wizardObj.rest_api_url ) {
				return;
			}

			this.contactRequestInFlight = true;
			apiFetch( {
				path: `${ wizardObj.rest_api_url }wpfunnels/v1/settings/create-contact/`,
				method: 'POST',
				data: {
					email: wizardObj.admin_email,
					name: wizardObj.admin_name,
				},
			} )
				.then( () => {
					this.contactCreated = true;
				} )
				.catch( ( error ) => {
					console.error( 'Error creating contact:', error );
				} )
				.finally( () => {
					this.contactRequestInFlight = false;
				} );
		},

		/**
		 * Called by the body CTA and by the footer's Next button.
		 */
		submit() {
			this.maybeCreateContact();
			this.$emit( 'next-step', { agreeToShare: this.consent } );
		},
	},
};
</script>
