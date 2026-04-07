<template>
	<div class="wpfnl-wizard-welcome">
		<!-- Heading Section -->
		<div class="wpfnl-wizard-welcome-content">
			<h2 class="wpfnl-wizard-welcome-title">
				Let’s boost your WooCommerce revenue
			</h2>
			<p class="wpfnl-wizard-welcome-subtitle">
				We’ll help you set up your first revenue-boosting funnel in just a few steps. No coding required.
			</p>

            <!-- Buttons -->
            <div class="wpfnl-mm-choose-goal-buttons">
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

            <!-- Consent Checkbox -->
            <div class="wpfnl-mm-choose-goal-consent">
                <label class="wpfnl-mm-checkbox">
                    <input type="checkbox" id="consent-checkbox" v-model="agreeToShare" />
                    <span class="wpfnl-mm-checkbox-checkmark"></span>
                    <span class="wpfnl-mm-checkbox-label">
                        I agree to share usage data to personalize my experience and improve this product.
                    </span>
                </label>
            </div>

            <figure class="welcome-image">
                <img :src="welcomeImage" alt="WPFunnels Setup" width="725" height="388" />
            </figure>
		</div>
		
	</div>
</template>

<script>
	import apiFetch from '@wordpress/api-fetch';
	import Sales from './Icons/Sales.vue';
	import Leads from './Icons/Leads.vue';
export default {
    name: 'Welcome',
	components: {
		Sales,
		Leads,
	}, 
	data() {
		return {
			selectedGoal: 'sales', // Default to sales funnel as shown in design
			agreeToShare: true, // Default to checked as shown in design
            welcomeImage: window.setup_wizard_obj.welcome_image,
			contactRequestInFlight: false,
			contactCreated: false,
		}
	},
	methods: {
		maybeCreateContact() {
			if (!this.agreeToShare || this.contactCreated || this.contactRequestInFlight) {
				return;
			}

			const wizardObj = window.setup_wizard_obj || {};
			const restApiUrl = wizardObj.rest_api_url;
			if (!restApiUrl) {
				return;
			}

			const payload = {
				email: wizardObj.admin_email,
				name: wizardObj.admin_name
			};

			this.contactRequestInFlight = true;
			apiFetch({
				path: `${restApiUrl}wpfunnels/v1/settings/create-contact/`,
				method: 'POST',
				data: payload
			})
				.then(() => {
					this.contactCreated = true;
				})
				.catch(error => {
					console.error('Error creating contact:', error);
				})
				.finally(() => {
					this.contactRequestInFlight = false;
				});
		},
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

			this.maybeCreateContact();
			this.proceedToNextStep();
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
