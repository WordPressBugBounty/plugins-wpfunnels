<template>
	<div class="wpfnl-mm-setup-wizard">
		<Sidebar
			:currentStep="currentStep"
			:selectedGoal="'improve-checkout'"
			:storeCheckoutPhase="storeCheckoutPhase"
			@show-exit-modal="handleShowExitModal"
		/>
		<Content
			ref="contentComponent"
			:currentStep="currentStep"
			:selectedBuilder="selectedBuilder"
			:agreeToShare="agreeToShare"
			:prefetchedTemplates="prefetchedTemplates"
			@next-step="handleNextStep"
			@prev-step="handlePrevStep"
			@store-checkout-phase="handleStoreCheckoutPhase"
		/>
	</div>
</template>

<script>
import Content from './Content/Content.vue'
import Sidebar from './Sidebar/Sidebar.vue'
import apiFetch from '@wordpress/api-fetch'
import { addQueryArgs } from '@wordpress/url'

const nonce = window.setup_wizard_obj.nonce
apiFetch.use(apiFetch.createNonceMiddleware(nonce))

export default {
	name: 'Wizard',
	components: {
		Content,
		Sidebar,
	},
	data() {
		return {
			currentStep: 1,
			agreeToShare: false,
			selectedBuilder: '',
			stepEnteredAt: Date.now(),
			consentSaved: false,
			prefetchedTemplates: [],
			storeCheckoutPhase: 'select',
		}
	},

	mounted() {
		this.trackStep('viewed', this.currentStep);
		this.prefetchTemplates('gutenberg');
	},

	methods: {
		prefetchTemplates(builder) {
			const resolved = builder === 'divi' ? 'divi-builder'
				: builder === 'others' ? 'gutenberg'
				: (builder || 'gutenberg');

			const baseUrl = (window.setup_wizard_obj?.rest_api_url || '').replace(/\/$/, '');
			const path = addQueryArgs(`${baseUrl}/wpfunnels/v1/templates/get_templates`, {
				builder: resolved,
				type: 'store_checkout',
			});

			apiFetch({ path })
				.then(response => {
					if (response.success && response.templates && response.templates.length > 0) {
						this.prefetchedTemplates = response.templates;
					} else if (resolved !== 'gutenberg') {
						this.prefetchTemplates('gutenberg');
					}
				})
				.catch(() => {});
		},

		getStepName(stepIndex) {
			return { 1: 'welcome', 2: 'required_installation', 3: 'store_checkout' }[stepIndex] || 'unknown';
		},

		trackStep(eventType, stepIndex) {
			const stepName = this.getStepName(stepIndex);
			const timeOnStep = Math.round((Date.now() - this.stepEnteredAt) / 1000);

			const wizardObj = window.setup_wizard_obj || {};
			const restApiUrl = wizardObj.rest_api_url || '';
			const url = restApiUrl
				? (restApiUrl.endsWith('/') ? restApiUrl : restApiUrl + '/') + 'wpfunnels/v1/setup-wizard/track-step'
				: null;

			if (!url) return;

			apiFetch({
				url,
				method: 'POST',
				data: {
					event_type: eventType,
					step_name: stepName,
					step_index: stepIndex,
					goal: 'improve-checkout',
					time_on_step: eventType === 'viewed' ? 0 : timeOnStep,
				},
			}).catch(() => {});
		},

		saveConsent(consented) {
			if (this.consentSaved) return;
			this.consentSaved = true;

			const wizardObj = window.setup_wizard_obj || {};
			const restApiUrl = wizardObj.rest_api_url || '';
			const url = restApiUrl
				? (restApiUrl.endsWith('/') ? restApiUrl : restApiUrl + '/') + 'wpfunnels/v1/setup-wizard/save-consent'
				: null;

			if (!url) return;

			apiFetch({
				url,
				method: 'POST',
				data: { consented: !!consented },
			}).catch(() => {});
		},

		handleNextStep(data) {
			if (data && data.agreeToShare !== undefined) {
				this.agreeToShare = data.agreeToShare;
			}
			if (data && data.builder) {
				this.selectedBuilder = data.builder;
				this.prefetchTemplates(data.builder);
			}

			if (this.currentStep === 1) {
				this.saveConsent(this.agreeToShare);
			}

			this.trackStep('completed', this.currentStep);
			if (this.currentStep < 3) {
				this.currentStep++;
				this.stepEnteredAt = Date.now();
				this.trackStep('viewed', this.currentStep);
			}
		},

		handlePrevStep() {
			if (this.currentStep > 1) {
				this.trackStep('completed', this.currentStep);
				this.currentStep--;
				this.stepEnteredAt = Date.now();
				this.trackStep('viewed', this.currentStep);
			}
		},

		handleShowExitModal() {
			if (this.$refs.contentComponent) {
				this.$refs.contentComponent.showExitModal();
			}
		},

		handleStoreCheckoutPhase(phase) {
			this.storeCheckoutPhase = phase;
		},
	},
}
</script>
