<template>
	<div class="wpfnl-mm-setup-wizard-content">
		<ChooseGoal
			v-if="currentStep === 1"
			@next-step="handleNextStep"
			@prev-step="handlePrevStep"
		/>
		<Setup
			v-if="currentStep === 2"
			:selectedGoal="selectedGoal"
			@next-step="handleNextStep"
			@prev-step="handlePrevStep"
		/>
		<ChooseTemplate
			v-if="currentStep === 3"
			:builder="selectedBuilder"
			:goal="selectedGoal"
			@next-step="handleNextStep"
			@prev-step="handlePrevStep"
		/>
		<BuildFunnel
			v-if="currentStep === 4"
			:template="selectedTemplate"
			:goal="selectedGoal"
			:builder="selectedBuilder"
			@next-step="handleNextStep"
			@prev-step="handlePrevStep"
		/>
		<Complete
			v-if="currentStep === 5"
			:funnelId="funnelId"
			:firstStepLink="firstStepLink"
			:selectedGoal="selectedGoal"
		/>

		<div class="wpfnl-mm-help-btn">
			<button class="wpfnl-mm-help-btn-circle" @click="showHelp">
				<svg xmlns="http://www.w3.org/2000/svg" width="10" height="15" viewBox="0 0 10 15" fill="none">
					<path d="M3.33203 0.235352C5.47393 -0.253449 7.61976 0.613333 8.55859 2.32812C9.33918 3.75474 9.12761 5.42966 7.99805 6.70312C7.6808 7.06053 7.34119 7.37444 7.00879 7.68066C6.23955 8.39045 5.70822 8.90509 5.56836 9.60938L5.54492 9.75293V9.76367L5.54004 10.2842C5.53577 10.8632 5.06526 11.328 4.48828 11.3281H4.47852C3.89713 11.3229 3.42931 10.8469 3.43457 10.2656L3.44043 9.69043V9.68945C3.44043 9.66013 3.44126 9.62985 3.44434 9.60059L3.44531 9.59961C3.60093 7.96652 4.68307 6.96278 5.58105 6.13477C5.8834 5.85561 6.1754 5.5871 6.42383 5.30664L6.42285 5.30566C6.72981 4.95955 7.19762 4.22567 6.71191 3.33887C6.43459 2.83155 5.95887 2.5097 5.43066 2.34473C4.90323 2.18009 4.31698 2.16973 3.80371 2.28711C2.7091 2.5372 2.30991 3.47128 2.1709 3.98926C2.02032 4.55057 1.44282 4.8834 0.880859 4.7334C0.319196 4.58278 -0.014292 4.00565 0.135742 3.44434C0.586504 1.76221 1.75249 0.596951 3.33203 0.235352Z" fill="white" stroke="white" stroke-width="0.2"/>
					<path d="M3.74121 12.9888C4.12882 12.6015 4.83802 12.6015 5.22559 12.9888L5.22656 12.9897C5.42546 13.1834 5.53605 13.4566 5.53613 13.73C5.53613 14.008 5.42666 14.2767 5.23242 14.4771C5.03181 14.6722 4.76248 14.7827 4.4834 14.7827C4.20964 14.7827 3.93533 14.6718 3.74121 14.478L3.74023 14.4771L3.66992 14.4009C3.51523 14.215 3.42969 13.9748 3.42969 13.73C3.42977 13.4566 3.54122 13.1834 3.74023 12.9897L3.74121 12.9888Z" fill="white" stroke="white" stroke-width="0.2"/>
				</svg>
			</button>
		</div>

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
import ChooseGoal from './ChooseGoal.vue'
import Setup from './Setup.vue'
import ChooseTemplate from './ChooseTemplate.vue'
import BuildFunnel from './BuildFunnel.vue'
import Complete from './Complete.vue'

export default {
    name: 'Content',
	components: {
		ChooseGoal,
		Setup,
		ChooseTemplate,
		BuildFunnel,
		Complete,
	},
	props: {
		currentStep: {
			type: Number,
			default: 1
		},
		selectedGoal: {
			type: String,
			default: ''
		},
		selectedBuilder: {
			type: String,
			default: ''
		},
		selectedTemplate: {
			type: Object,
			default: null
		},
		funnelId: {
			type: [Number, String],
			default: null
		},
		firstStepLink: {
			type: String,
			default: ''
		}
	},
	data() {
		return {
			isExitModalVisible: false
		}
	},
	mounted() {

	},
	methods: {
		handleNextStep(data) {
			this.$emit('next-step', data);
		},
		handlePrevStep() {
			this.$emit('prev-step');
		},
		showHelp() {
			window.open('https://getwpfunnels.com/docs/getting-started-with-wpfunnels/', '_blank');
		},
		showExitModal() {
			this.isExitModalVisible = true
		},
		closeExitModal() {
			this.isExitModalVisible = false
		},
		confirmExit() {
			window.location.href = window.setup_wizard_obj.dashboard_url || '/wp-admin/';
		}
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
	from {
		opacity: 0;
	}
	to {
		opacity: 1;
	}
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
	from {
		transform: translateY(20px);
		opacity: 0;
	}
	to {
		transform: translateY(0);
		opacity: 1;
	}
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