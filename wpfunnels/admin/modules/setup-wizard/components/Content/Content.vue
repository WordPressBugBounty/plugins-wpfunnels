<template>
	<main class="wpfnl-wz-body">
		<Welcome
			v-if="1 === currentStep"
			ref="step"
			:agreeToShare="agreeToShare"
			@nav="bubbleNav"
			@next-step="bubbleNext"
		/>

		<Setup
			v-else-if="2 === currentStep"
			ref="step"
			@nav="bubbleNav"
			@next-step="bubbleNext"
		/>

		<ChooseTemplate
			v-else-if="3 === currentStep"
			ref="step"
			:builder="builder"
			:prefetchedTemplates="prefetchedTemplates"
			@nav="bubbleNav"
			@next-step="bubbleNext"
		/>

		<Complete
			v-else-if="4 === currentStep"
			ref="step"
			:funnelId="funnelId"
			:firstStepLink="firstStepLink"
			@nav="bubbleNav"
		/>

		<!-- Exit confirmation -->
		<div
			v-if="isExitModalVisible"
			class="wpfnl-wz-modal-overlay"
			role="dialog"
			aria-modal="true"
			aria-labelledby="wpfnl-wz-exit-title"
			@click.self="closeExitModal"
		>
			<div class="wpfnl-wz-modal">
				<div class="wpfnl-wz-modal-head">
					<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
						<circle cx="24" cy="24" r="24" fill="#FEF3C7" />
						<path d="M24 16V24M24 28H24.02M38 24C38 31.732 31.732 38 24 38C16.268 38 10 31.732 10 24C10 16.268 16.268 10 24 10C31.732 10 38 16.268 38 24Z" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
					</svg>
					<h2 id="wpfnl-wz-exit-title" class="wpfnl-wz-modal-title">Exit Setup Wizard?</h2>
					<p class="wpfnl-wz-modal-text">
						Are you sure you want to exit? Your progress won't be saved and you'll need to start over later.
					</p>
				</div>

				<div class="wpfnl-wz-modal-actions">
					<button type="button" class="wpfnl-wz-btn wpfnl-wz-btn--ghost" @click="closeExitModal">
						Continue Setup
					</button>
					<button type="button" class="wpfnl-wz-btn wpfnl-wz-btn--danger" @click="confirmExit">
						Yes, Exit
					</button>
				</div>
			</div>
		</div>
	</main>
</template>

<script>
import Setup from './Setup.vue';
import Welcome from './Welcome.vue';
import ChooseTemplate from './ChooseTemplate.vue';
import Complete from './Complete.vue';

/**
 * Telemetry step slugs, mirroring the map in Wizard.vue.
 */
const STEP_NAMES = {
	1: 'welcome',
	2: 'required_installation',
	3: 'choose_template',
	4: 'complete',
};

export default {
	name: 'Content',
	components: {
		Setup,
		Welcome,
		ChooseTemplate,
		Complete,
	},
	props: {
		currentStep: {
			type: Number,
			default: 1,
		},
		builder: {
			type: String,
			default: 'gutenberg',
		},
		funnelId: {
			type: [ Number, String ],
			default: null,
		},
		firstStepLink: {
			type: String,
			default: '',
		},
		agreeToShare: {
			type: Boolean,
			default: true,
		},
		prefetchedTemplates: {
			type: Array,
			default: () => [],
		},
	},
	emits: [ 'nav', 'next-step', 'prev-step', 'exit' ],
	data() {
		return {
			isExitModalVisible: false,
		};
	},
	methods: {
		/**
		 * Hand the currently mounted step component back to Wizard so the
		 * footer's Next button can delegate to it.
		 *
		 * @return {Object|null} Active step component instance.
		 */
		activeStep() {
			return this.$refs.step || null;
		},

		bubbleNav( state ) {
			this.$emit( 'nav', state );
		},

		bubbleNext( data ) {
			this.$emit( 'next-step', data );
		},

		showExitModal() {
			this.isExitModalVisible = true;
		},

		closeExitModal() {
			this.isExitModalVisible = false;
		},

		confirmExit() {
			this.trackAbandoned( () => {
				window.location.href = window.setup_wizard_obj?.dashboard_url || '/wp-admin/';
			} );
		},

		/**
		 * Report the exit before navigating away.
		 *
		 * Leaving from the template picker without generating is reported as
		 * `skipped` rather than `abandoned` — the user saw the offer and
		 * declined it, which is a different signal from dropping out earlier.
		 * Uses fetch + keepalive rather than apiFetch so the request survives
		 * the navigation that follows.
		 *
		 * @param {Function} callback Runs once the request settles.
		 */
		trackAbandoned( callback ) {
			const wizardObj = window.setup_wizard_obj || {};
			const restApiUrl = wizardObj.rest_api_url || '';

			if ( ! restApiUrl ) {
				callback();
				return;
			}

			const base = restApiUrl.endsWith( '/' ) ? restApiUrl : `${ restApiUrl }/`;
			const eventType = 3 === this.currentStep ? 'skipped' : 'abandoned';

			fetch( `${ base }wpfunnels/v1/setup-wizard/track-step`, {
				method: 'POST',
				keepalive: true,
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': wizardObj.nonce || '',
				},
				body: JSON.stringify( {
					event_type: eventType,
					step_name: STEP_NAMES[ this.currentStep ] || 'unknown',
					step_index: this.currentStep,
					goal: 'improve-checkout',
					time_on_step: 0,
					total_steps: 4,
				} ),
			} )
				.catch( () => {} )
				.finally( callback );
		},
	},
};
</script>
