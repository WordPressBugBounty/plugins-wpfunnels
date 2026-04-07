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
			@next-step="handleNextStep"
			@prev-step="handlePrevStep"
		/>
	</div>
</template>

<script>
import Content from './Content/Content.vue'
import Sidebar from './Sidebar/Sidebar.vue'
import apiFetch from '@wordpress/api-fetch'

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
		}
	},

	mounted() {
		// Initialization logic if needed
	},

	methods: {
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

			// Move to next step
			const maxSteps = this.getMaxSteps();
			if (this.currentStep < maxSteps) {
				this.currentStep++;
			}
		},
		handlePrevStep() {
			// Move to previous step
			if (this.currentStep > 1) {
				this.currentStep--;
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