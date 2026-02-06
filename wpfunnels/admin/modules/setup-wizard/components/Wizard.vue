<template>
	<div class="wpfnl-mm-setup-wizard">
		<Sidebar :currentStep="currentStep" @show-exit-modal="handleShowExitModal" />
		<Content
			ref="contentComponent"
			:currentStep="currentStep"
			:selectedGoal="selectedGoal"
			:selectedBuilder="selectedBuilder"
			:selectedTemplate="selectedTemplate"
			:funnelId="createdFunnelId"
			:firstStepLink="firstStepLink"
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
			if (data && data.funnelId) {
				this.createdFunnelId = data.funnelId;
			}
			if (data && data.firstStepLink) {
				this.firstStepLink = data.firstStepLink;
			}

			// Move to next step
			if (this.currentStep < 5) {
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
		}
	},
}
</script>