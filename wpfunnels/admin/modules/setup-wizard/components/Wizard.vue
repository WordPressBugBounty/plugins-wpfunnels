<template>
	<div class="wpfnl-mm-setup-wizard">
		<Sidebar :currentStep="currentStep" :selectedGoal="selectedGoal" @show-exit-modal="handleShowExitModal" />
		<Content
			ref="contentComponent"
			:currentStep="currentStep"
			:selectedGoal="selectedGoal"
			:selectedBuilder="selectedBuilder"
			:selectedTemplate="selectedTemplate"
			:funnelId="createdFunnelId"
			:firstStepLink="firstStepLink"
			:selectedProductId="selectedProductId"
			:selectedProduct="selectedProduct"
			:agreeToShare="agreeToShare"
			:mainProduct="mainProduct"
			:orderBump="orderBump"
			:upsellProduct="upsellProduct"
			:prefetchedTemplates="prefetchedTemplates"
			@next-step="handleNextStep"
			@prev-step="handlePrevStep"
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
			selectedGoal: '',
			agreeToShare: false,
			selectedBuilder: '',
			wooInstalled: false,
			wooActivated: false,
			selectedTemplate: null,
			createdFunnelId: null,
			firstStepLink: '',
			selectedProductId: null,
			selectedProduct: null,
			mainProduct: null,
			orderBump: null,
			upsellProduct: null,
			stepEnteredAt: Date.now(),
			consentSaved: false,
			prefetchedTemplates: {},
			prefetchedForBuilder: '',
		}
	},

	mounted() {
		this.trackStep('viewed', this.currentStep);
	},

	watch: {
		// Trigger prefetch when step 2 is completed and builder is confirmed.
		selectedBuilder(val) {
			if (val && val !== this.prefetchedForBuilder) {
				this.prefetchedTemplates = {};
				this.triggerTemplatePrefetch(val);
			}
		},
	},

	methods: {
		triggerTemplatePrefetch(builder) {
			const resolved = builder === 'divi' ? 'divi-builder'
				: builder === 'others' ? 'gutenberg'
				: (builder || 'gutenberg');

			this.prefetchedForBuilder = resolved;

			const baseUrl = (window.setup_wizard_obj?.rest_api_url || '').replace(/\/$/, '');
			const goalTypeMap = {
				'order-value':     'wc',
				'improve-checkout': 'store_checkout',
				'sales':            'wc',
			};

			Object.entries(goalTypeMap).forEach(([goal, type]) => {
				const path = addQueryArgs(`${baseUrl}/wpfunnels/v1/templates/get_templates`, {
					builder: resolved,
					type,
				});

				apiFetch({ path })
					.then(response => {
						if (response.success && response.templates && response.templates.length > 0) {
							this.prefetchedTemplates = { ...this.prefetchedTemplates, [goal]: response.templates };
						} else if (resolved === 'bricks' || resolved === 'oxygen') {
							// Gutenberg fallback for builders with no template coverage.
							const fallback = addQueryArgs(`${baseUrl}/wpfunnels/v1/templates/get_templates`, {
								builder: 'gutenberg',
								type,
							});
							apiFetch({ path: fallback })
								.then(fb => {
									if (fb.success && fb.templates) {
										this.prefetchedTemplates = { ...this.prefetchedTemplates, [goal]: fb.templates };
									}
								})
								.catch(() => {});
						}
					})
					.catch(() => {});
			});
		},

		getStepName(stepIndex, goal) {
			if (stepIndex === 1) return 'welcome';
			if (stepIndex === 2) return 'environment_check';
			if (stepIndex === 3) return 'choose_goal';
			if (goal === 'order-value') {
				return { 4: 'choose_template', 5: 'build_funnel', 6: 'complete' }[stepIndex] || 'unknown';
			}
			if (goal === 'improve-checkout') {
				return { 4: 'choose_template', 5: 'generate_funnel', 6: 'complete' }[stepIndex] || 'unknown';
			}
			if (goal === 'sales') {
				return { 4: 'product_sync', 5: 'choose_template', 6: 'generate_funnel', 7: 'complete' }[stepIndex] || 'unknown';
			}
			return 'unknown';
		},

		trackStep(eventType, stepIndex, goal) {
			const resolvedGoal = goal !== undefined ? goal : this.selectedGoal;
			const stepName = this.getStepName(stepIndex, resolvedGoal);
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
					goal: resolvedGoal,
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
			// Store data from current step
			if (data && data.goal) {
				this.selectedGoal = data.goal;
				this.agreeToShare = data.agreeToShare;
			}
			if (data && data.builder) {
				this.selectedBuilder = data.builder;
				this.wooInstalled = data.wooInstalled;
				this.wooActivated = data.wooActivated;
			}
			if (data && data.template) {
				this.selectedTemplate = data.template;
			}
			if (data && data.productId) {
				this.selectedProductId = data.productId;
				this.selectedProduct = data.product || null;
			}
			if (data && data.funnelId) {
				this.createdFunnelId = data.funnelId;
			}
			if (data && data.firstStepLink) {
				this.firstStepLink = data.firstStepLink;
			}
			if (data && data.mainProduct !== undefined) {
				this.mainProduct = data.mainProduct;
			}
			if (data && data.orderBump !== undefined) {
				this.orderBump = data.orderBump;
			}
			if (data && data.upsell !== undefined) {
				this.upsellProduct = data.upsell;
			}

			// Save consent once when leaving the Welcome step.
			if (this.currentStep === 1) {
				this.saveConsent(this.agreeToShare);
			}

			// Track step completed, then move forward and track step viewed.
			this.trackStep('completed', this.currentStep);
			const maxSteps = this.getMaxSteps();
			if (this.currentStep < maxSteps) {
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
			// Call the showExitModal method in Content component
			if (this.$refs.contentComponent) {
				this.$refs.contentComponent.showExitModal();
			}
		},
		getMaxSteps() {
			// Dynamic step calculation based on goal
			if (this.selectedGoal === 'order-value') {
				return 6; // Welcome, Setup, ChooseGoal, ChooseTemplate, BuildFunnel, Complete
			}
			if (this.selectedGoal === 'improve-checkout') {
				return 6; // Welcome, Setup, ChooseGoal, ChooseTemplate, GenerateFunnel, Complete
			}
			if (this.selectedGoal === 'sales') {
				return 7; // Welcome, Setup, ChooseGoal, ProductSync, ChooseTemplate, GenerateFunnel, Complete
			}
			// Default before goal selection
			return 5; // Welcome, Setup, ChooseGoal, ChooseTemplate, Complete (initial display)
		}
	},
}
</script>