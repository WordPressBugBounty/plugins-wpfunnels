<template>
	<div class="wpfnl-wz">
		<TopBar
			:currentStep="currentStep"
			:totalSteps="totalSteps"
			@exit="requestExit"
		/>

		<Content
			ref="content"
			:currentStep="currentStep"
			:builder="builder"
			:funnelId="funnelId"
			:firstStepLink="firstStepLink"
			:agreeToShare="agreeToShare"
			:prefetchedTemplates="prefetchedTemplates"
			@nav="onNav"
			@next-step="handleNextStep"
			@prev-step="handlePrevStep"
			@exit="requestExit"
		/>

		<!-- Welcome carries its own CTA; the footer takes over from step 2. -->
		<WizardFooter
			v-if="currentStep > 1"
			:label="nav.label"
			:canProceed="nav.canProceed"
			:busy="nav.busy"
			:showBack="nav.showBack && currentStep > 1"
			:showNext="nav.showNext"
			:showSkip="nav.showSkip"
			@back="handlePrevStep"
			@next="submitActiveStep"
			@skip="skipActiveStep"
		/>
	</div>
</template>

<script>
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import Content from './Content/Content.vue';
import TopBar from './Chrome/TopBar.vue';
import WizardFooter from './Chrome/WizardFooter.vue';

const nonce = window.setup_wizard_obj.nonce;
apiFetch.use( apiFetch.createNonceMiddleware( nonce ) );

const TOTAL_STEPS = 4;

/**
 * Step slugs, indexed by their 1-based position. Used for telemetry so the
 * reported step names stay stable even if the labels change.
 */
const STEP_NAMES = {
	1: 'welcome',
	2: 'required_installation',
	3: 'choose_template',
	4: 'complete',
};

const DEFAULT_NAV = {
	label: 'Next',
	canProceed: true,
	busy: false,
	showBack: true,
	showNext: true,
	showSkip: false,
};

export default {
	name: 'Wizard',
	components: {
		Content,
		TopBar,
		WizardFooter,
	},

	data() {
		return {
			currentStep: 1,
			totalSteps: TOTAL_STEPS,

			// Collected across the flow.
			agreeToShare: true,
			builder: 'gutenberg',
			funnelId: null,
			firstStepLink: '',

			// Footer state, published by whichever step is active.
			nav: { ...DEFAULT_NAV },

			prefetchedTemplates: [],
			stepEnteredAt: Date.now(),
			consentSaved: false,
		};
	},

	mounted() {
		this.trackStep( 'viewed', this.currentStep );
		this.prefetchTemplates();
	},

	methods: {
		/**
		 * Receive footer state from the active step.
		 *
		 * @param {Object} state Partial nav state.
		 */
		onNav( state ) {
			this.nav = { ...DEFAULT_NAV, ...( state || {} ) };
		},

		/**
		 * Ask the active step to advance. Steps own their own validation and
		 * side effects (installing plugins, generating a funnel), so the footer
		 * never advances the wizard directly.
		 */
		submitActiveStep() {
			const step = this.$refs.content?.activeStep();
			if ( step && 'function' === typeof step.submit ) {
				step.submit();
				return;
			}
			this.handleNextStep();
		},

		/**
		 * Let the active step handle being skipped. Falls through to a plain
		 * advance for steps that offer no skip of their own.
		 */
		skipActiveStep() {
			const step = this.$refs.content?.activeStep();
			if ( step && 'function' === typeof step.skip ) {
				step.skip();
				return;
			}
			this.handleNextStep();
		},

		requestExit() {
			this.$refs.content?.showExitModal();
		},

		/**
		 * Warm the store-checkout template grid so the template step renders
		 * instantly. Re-run whenever the builder choice changes, since the API
		 * result is builder-specific.
		 */
		prefetchTemplates() {
			const builder = 'divi' === this.builder ? 'divi-builder'
				: 'others' === this.builder ? 'gutenberg'
				: ( this.builder || 'gutenberg' );

			const baseUrl = ( window.setup_wizard_obj?.rest_api_url || '' ).replace( /\/$/, '' );
			const path = addQueryArgs( `${ baseUrl }/wpfunnels/v1/templates/get_templates`, {
				builder,
				type: 'store_checkout',
			} );

			apiFetch( { path } )
				.then( ( response ) => {
					if ( response.success && response.templates?.length ) {
						this.prefetchedTemplates = response.templates;
					}
				} )
				.catch( () => {} );
		},

		getStepName( stepIndex ) {
			return STEP_NAMES[ stepIndex ] || 'unknown';
		},

		trackStep( eventType, stepIndex ) {
			const restApiUrl = window.setup_wizard_obj?.rest_api_url || '';
			if ( ! restApiUrl ) {
				return;
			}

			const base = restApiUrl.endsWith( '/' ) ? restApiUrl : `${ restApiUrl }/`;

			apiFetch( {
				url: `${ base }wpfunnels/v1/setup-wizard/track-step`,
				method: 'POST',
				data: {
					event_type: eventType,
					step_name: this.getStepName( stepIndex ),
					step_index: stepIndex,
					goal: 'improve-checkout',
					time_on_step: 'viewed' === eventType
						? 0
						: Math.round( ( Date.now() - this.stepEnteredAt ) / 1000 ),
					total_steps: TOTAL_STEPS,
				},
			} ).catch( () => {} );
		},

		/**
		 * Persist the telemetry opt-in from the welcome step. Every later
		 * track-step call is gated on this, so it runs before step 2.
		 *
		 * @param {boolean} consented Whether the user opted in.
		 */
		saveConsent( consented ) {
			if ( this.consentSaved ) {
				return;
			}
			this.consentSaved = true;

			const restApiUrl = window.setup_wizard_obj?.rest_api_url || '';
			if ( ! restApiUrl ) {
				return;
			}

			const base = restApiUrl.endsWith( '/' ) ? restApiUrl : `${ restApiUrl }/`;

			apiFetch( {
				url: `${ base }wpfunnels/v1/setup-wizard/save-consent`,
				method: 'POST',
				data: { consented: !! consented },
			} ).catch( () => {} );
		},

		/**
		 * Advance, folding whatever the finished step collected into wizard state.
		 *
		 * @param {Object} data Payload emitted by the step.
		 */
		handleNextStep( data ) {
			if ( data?.agreeToShare !== undefined ) {
				this.agreeToShare = data.agreeToShare;
			}
			if ( data?.builder ) {
				const builderChanged = data.builder !== this.builder;
				this.builder = data.builder;
				if ( builderChanged ) {
					this.prefetchTemplates();
				}
			}
			if ( data?.funnelId ) {
				this.funnelId = data.funnelId;
			}
			if ( data?.firstStepLink ) {
				this.firstStepLink = data.firstStepLink;
			}

			if ( 1 === this.currentStep ) {
				this.saveConsent( this.agreeToShare );
			}

			this.trackStep( 'completed', this.currentStep );

			if ( this.currentStep < TOTAL_STEPS ) {
				this.currentStep++;
				this.stepEnteredAt = Date.now();
				this.nav = { ...DEFAULT_NAV };
				this.trackStep( 'viewed', this.currentStep );
			}
		},

		handlePrevStep() {
			if ( this.currentStep <= 1 ) {
				return;
			}
			this.trackStep( 'completed', this.currentStep );
			this.currentStep--;
			this.stepEnteredAt = Date.now();
			this.nav = { ...DEFAULT_NAV };
			this.trackStep( 'viewed', this.currentStep );
		},
	},
};
</script>
