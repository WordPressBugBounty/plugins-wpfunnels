<template>
	<div class="wpfnl-mm-choose-goal">
		<!-- Heading Section -->
		<div class="wpfnl-mm-choose-goal-header">
			<h2 class="wpfnl-mm-choose-goal-title">
				Let’s Build Your First High-Converting Funnel
			</h2>
			<p class="wpfnl-mm-choose-goal-subtitle">
				Choose your goal, and we’ll guide you step by step to create a ready-to-use funnel. No coding required.
			</p>
		</div>

		<!-- Container with Cards -->
		<div class="wpfnl-mm-choose-goal-container">
			<div class="wpfnl-mm-choose-goal-cards">
				<!-- Sales Funnel Card -->
				<div 
					class="wpfnl-mm-goal-card" 
					:class="{ 'active': selectedGoal === 'sales' }"
					@click="selectGoal('sales')"
				>
					<div class="wpfnl-mm-goal-card-checkmark" v-if="selectedGoal === 'sales'">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M13.3334 4L6.00002 11.3333L2.66669 8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</div>
					<div class="wpfnl-mm-goal-card-icon">
						<Sales />
					</div>
					<div class="wpfnl-mm-goal-card-content">
						<h3 class="wpfnl-mm-goal-card-title">Sales Funnel</h3>
						<p class="wpfnl-mm-goal-card-description">
							Increase average order value (AOV) with upsells and order bumps.
						</p>
					</div>
				</div>

				<!-- Generate Leads Card -->
				<div 
					class="wpfnl-mm-goal-card" 
					:class="{ 'active': selectedGoal === 'leads' }"
					@click="selectGoal('leads')"
				>
					<div class="wpfnl-mm-goal-card-checkmark" v-if="selectedGoal === 'leads'">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M13.3334 4L6.00002 11.3333L2.66669 8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</div>
					<div class="wpfnl-mm-goal-card-icon">
						<Leads />
					</div>
					<div class="wpfnl-mm-goal-card-content">
						<h3 class="wpfnl-mm-goal-card-title">Generate Leads</h3>
						<p class="wpfnl-mm-goal-card-description">
							Grow your email list with proven lead magnets.
						</p>
					</div>
				</div>
			</div>

			<!-- Consent Checkbox -->
			<div class="wpfnl-mm-choose-goal-consent">
				<label class="wpfnl-mm-checkbox">
					<input type="checkbox" v-model="agreeToShare" />
					<span class="wpfnl-mm-checkbox-checkmark"></span>
					<span class="wpfnl-mm-checkbox-label">
						I agree to share usage data to personalize my experience and improve this product.
					</span>
				</label>
			</div>
		</div>

		<!-- Buttons -->
		<div class="wpfnl-mm-choose-goal-buttons">
			<button class="wpfnl-mm-btn wpfnl-mm-btn-secondary" @click="goBack" style="visibility: hidden;">
				Back
			</button>
			<div class="wpfnl-mm-btn-group">
				<button 
					class="wpfnl-mm-btn wpfnl-mm-btn-primary" 
					@click="handleContinue"
				>
					Start setup
					<svg width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M1 6H16M16 6L11 1M16 6L11 11" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			</div>
		</div>
	</div>
</template>

<script>
	import apiFetch from '@wordpress/api-fetch';
	import Sales from './Icons/Sales.vue';
	import Leads from './Icons/Leads.vue';
export default {
    name: 'ChooseGoal',
	components: {
		Sales,
		Leads,
	}, 
	data() {
		return {
			selectedGoal: 'sales', // Default to sales funnel as shown in design
			agreeToShare: true, // Default to checked as shown in design
		}
	},
	methods: {
		selectGoal(goal) {
			this.selectedGoal = goal;
		},
		goBack() {
			this.$emit('prev-step');
		},
		handleContinue() {
			if (!this.selectedGoal) {
				return;
			}

			// If user agrees to share, create contact
			if (this.agreeToShare) {
				let payload = {
					'email': window.setup_wizard_obj.admin_email,
					'name': window.setup_wizard_obj.admin_name
				};

				this.proceedToNextStep();

				apiFetch({
					path: window.setup_wizard_obj.rest_api_url + 'wpfunnels/v1/settings/create-contact/',
					method: 'POST',
					data: payload
				}).then(() => {
					this.proceedToNextStep();
				}).catch((error) => {
					console.error('Error creating contact:', error);
					this.proceedToNextStep();
				});
			} else {
				this.proceedToNextStep();
			}
		},
		proceedToNextStep() {
			// Emit event or navigate to next step
			this.$emit('next-step', {
				goal: this.selectedGoal,
				agreeToShare: this.agreeToShare
			});
		},
		showHelp() {
			// Handle help action
			window.open('https://getwpfunnels.com/docs/', '_blank');
		}
	}
}
</script>
