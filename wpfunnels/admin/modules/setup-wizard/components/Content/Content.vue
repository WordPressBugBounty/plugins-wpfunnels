<template>
	<div class="wpfnl-mm-setup-wizard-content">
		<Welcome
			v-if="currentStep === 1"
			@next-step="handleNextStep"
			@prev-step="handlePrevStep"
		/>

		<Setup
			v-if="currentStep === 2"
			@next-step="handleNextStep"
			@prev-step="handlePrevStep"
		/>

		<StoreCheckout
			v-if="currentStep === 3"
			:builder="selectedBuilder"
			:prefetchedTemplates="prefetchedTemplates"
			:agreeToShare="agreeToShare"
			@prev-step="handlePrevStep"
			@store-checkout-phase="onStoreCheckoutPhase"
		/>


		<!-- Exit Confirmation Modal -->
		<div class="wpfnl-exit-modal-overlay" v-if="isExitModalVisible" @click.self="closeExitModal">
			<div class="wpfnl-exit-modal">
				<div class="wpfnl-exit-modal-header">
					<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
						<circle cx="24" cy="24" r="24" fill="#FEF3C7"/>
						<path d="M24 16V24M24 28H24.02M38 24C38 31.732 31.732 38 24 38C16.268 38 10 31.732 10 24C10 16.268 16.268 10 24 10C31.732 10 38 16.268 38 24Z" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<h3 class="wpfnl-exit-modal-title">Exit Setup Wizard?</h3>
					<p class="wpfnl-exit-modal-description">
						Are you sure you want to exit? Your progress won't be saved and you'll need to start over later.
					</p>
				</div>

				<div class="wpfnl-exit-modal-actions">
					<button class="wpfnl-exit-modal-btn wpfnl-exit-modal-btn-secondary" @click="closeExitModal">
						Continue Setup
					</button>
					<button class="wpfnl-exit-modal-btn wpfnl-exit-modal-btn-danger" @click="confirmExit">
						Yes, Exit
					</button>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import Setup from './Setup.vue'
import Welcome from './Welcome.vue'
import StoreCheckout from './StoreCheckout.vue'

export default {
    name: 'Content',
	components: {
		Setup,
		Welcome,
		StoreCheckout,
	},
	props: {
		currentStep: {
			type: Number,
			default: 1
		},
		selectedBuilder: {
			type: String,
			default: ''
		},
		agreeToShare: {
			type: Boolean,
			default: false
		},
		prefetchedTemplates: {
			type: Array,
			default: () => []
		}
	},
	data() {
		return {
			isExitModalVisible: false,
			storeCheckoutPhase: 'select',
		}
	},
	methods: {
		handleNextStep(data) {
			this.$emit('next-step', data);
		},
		handlePrevStep() {
			this.$emit('prev-step');
		},
		onStoreCheckoutPhase(phase) {
			this.storeCheckoutPhase = phase;
			this.$emit('store-checkout-phase', phase);
		},
		showExitModal() {
			this.isExitModalVisible = true
		},
		closeExitModal() {
			this.isExitModalVisible = false
		},
		confirmExit() {
			this.trackAbandoned(() => {
				window.location.href = window.setup_wizard_obj.dashboard_url || '/wp-admin/';
			});
		},
		trackAbandoned(callback) {
			const wizardObj = window.setup_wizard_obj || {};
			const restApiUrl = wizardObj.rest_api_url || '';
			const base = restApiUrl ? (restApiUrl.endsWith('/') ? restApiUrl : restApiUrl + '/') : null;

			if (!base) {
				callback();
				return;
			}

			const url = base + 'wpfunnels/v1/setup-wizard/track-step';
			const nonce = wizardObj.nonce || '';

			// Use 'skipped' outcome when user reached step 3 template selection but didn't import
			// Use 'abandoned' (→ 'exited') for earlier exits
			const stepName = this.currentStep === 1 ? 'welcome'
				: this.currentStep === 2 ? 'required_installation'
				: 'store_checkout';

			const eventType = (this.currentStep === 3 && this.storeCheckoutPhase === 'select')
				? 'skipped'
				: 'abandoned';

			fetch(url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': nonce,
				},
				body: JSON.stringify({
					event_type: eventType,
					step_name: stepName,
					step_index: this.currentStep,
					goal: 'improve-checkout',
					time_on_step: 0,
					total_steps: 3,
				}),
			}).catch(() => {}).finally(callback);
		},
	}
}
</script>

<style scoped>
/* Exit Modal */
.wpfnl-exit-modal-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 10000;
	animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
	from { opacity: 0; }
	to { opacity: 1; }
}

.wpfnl-exit-modal {
	background: #FFFFFF;
	border-radius: 16px;
	padding: 32px;
	max-width: 440px;
	width: 90%;
	box-shadow: 0px 20px 40px rgba(0, 0, 0, 0.15);
	animation: slideUp 0.3s ease;
}

@keyframes slideUp {
	from { transform: translateY(20px); opacity: 0; }
	to { transform: translateY(0); opacity: 1; }
}

.wpfnl-exit-modal-header {
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
	margin-bottom: 24px;
}

.wpfnl-exit-modal-header svg {
	margin-bottom: 16px;
}

.wpfnl-exit-modal-title {
	font-size: 20px;
	font-weight: 700;
	font-family: 'DM Sans', sans-serif;
	color: #363B4E;
	margin: 0 0 12px 0;
	letter-spacing: -0.5px;
}

.wpfnl-exit-modal-description {
	font-size: 14px;
	font-weight: 400;
	font-family: 'DM Sans', sans-serif;
	color: #6E7A85;
	line-height: 1.6;
	margin: 0;
}

.wpfnl-exit-modal-actions {
	display: flex;
	gap: 12px;
	justify-content: center;
}

.wpfnl-exit-modal-btn {
	padding: 12px 24px;
	border-radius: 8px;
	font-size: 14px;
	font-weight: 600;
	font-family: 'DM Sans', sans-serif;
	cursor: pointer;
	transition: all 0.3s ease;
	border: none;
	min-width: 140px;
}

.wpfnl-exit-modal-btn-secondary {
	background: #F6F5FA;
	color: #363B4E;
	border: 1px solid #ECEBF0;
}

.wpfnl-exit-modal-btn-secondary:hover {
	background: #ECEBF0;
	border-color: #6E42D3;
}

.wpfnl-exit-modal-btn-danger {
	background: #EF4444;
	color: #FFFFFF;
}

.wpfnl-exit-modal-btn-danger:hover {
	background: #DC2626;
}
</style>
