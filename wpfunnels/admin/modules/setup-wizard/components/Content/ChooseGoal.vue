<template>
	<div class="wpfnl-mm-choose-goal">
		<!-- Heading Section -->
		<div class="wpfnl-mm-choose-goal-header">
			<h2 class="wpfnl-mm-choose-goal-title">
				What's Your Goal?
			</h2>
			<p class="wpfnl-mm-choose-goal-subtitle">
				Get your sales funnel up and running in minutes.
			</p>
		</div>

		<!-- Container with Cards -->
		<div class="wpfnl-mm-choose-goal-container">
			<div class="wpfnl-mm-choose-goal-cards">

				<!-- Increase Order Value Card -->
				<div 
					class="wpfnl-mm-goal-card" 
					:class="{ 'active': selectedGoal === 'order-value' }"
					@click="selectGoal('order-value')"
				>
					<div class="wpfnl-mm-goal-card-checkmark" v-if="selectedGoal === 'order-value'">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M13.3334 4L6.00002 11.3333L2.66669 8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</div>
					<div class="wpfnl-mm-goal-card-icon">
						<OrderValue />
					</div>
					<div class="wpfnl-mm-goal-card-content">
						<h3 class="wpfnl-mm-goal-card-title">Increase Order Value</h3>
						<p class="wpfnl-mm-goal-card-description">
							Offer relevant upsells and order bumps to increase revenue from every purchase.
						</p>
					</div>
				</div>

				<!-- Improve Checkout Card -->
				<div 
					class="wpfnl-mm-goal-card" 
					:class="{ 'active': selectedGoal === 'improve-checkout' }"
					@click="selectGoal('improve-checkout')"
				>
					<div class="wpfnl-mm-goal-card-checkmark" v-if="selectedGoal === 'improve-checkout'">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M13.3334 4L6.00002 11.3333L2.66669 8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</div>
					<div class="wpfnl-mm-goal-card-icon">
						<ImproveCheckout />
					</div>
					<div class="wpfnl-mm-goal-card-content">
						<h3 class="wpfnl-mm-goal-card-title">Store Checkout</h3>
						<p class="wpfnl-mm-goal-card-description">
							Replace your default checkout with a custom, high-converting checkout page.
						</p>
					</div>
				</div>

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
						<h3 class="wpfnl-mm-goal-card-title">Launch Sales Funnel</h3>
						<p class="wpfnl-mm-goal-card-description">
							Build a complete funnel from landing page to checkout — with offers, upsells, and follow-ups.
						</p>
					</div>
				</div>

			</div>

		</div>

		<!-- Buttons -->
		<div class="wpfnl-mm-choose-goal-buttons">
			<button class="wpfnl-mm-btn wpfnl-mm-btn-secondary" @click="goBack">
				Back
			</button>

			<div class="wpfnl-mm-btn-group">
				<button 
					class="wpfnl-mm-btn wpfnl-mm-btn-primary" 
					@click="handleContinue"
				>
					Continue
					<svg width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M1 6H16M16 6L11 1M16 6L11 11" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			</div>
		</div>
	</div>
</template>


<script>
	import Sales from './Icons/Sales.vue';
	import OrderValue from './Icons/OrderValue.vue';
	import ImproveCheckout from './Icons/ImproveCheckout.vue';
	
	export default {
		name: 'ChooseGoal',
		components: {
			Sales,
			OrderValue,
			ImproveCheckout,
		}, 
		data() {
			return {
				selectedGoal: 'improve-checkout', // Default to improve checkout as shown in design
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
