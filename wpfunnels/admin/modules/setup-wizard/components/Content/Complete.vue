<template>
	<div class="wpfnl-mm-complete">
		<!-- Header -->
		<div class="wpfnl-mm-complete-header">
			<h2 class="wpfnl-mm-complete-title">
				Your funnel is live 🎉
			</h2>
			<p class="wpfnl-mm-complete-subtitle">
				{{ subtitleText }}
			</p>
		</div>

		<!-- Video/Preview Section -->
		<div class="wpfnl-mm-complete-video-wrapper">
			<div class="wpfnl-mm-complete-video">
				<!-- Poster Image -->
				<div class="wpfnl-mm-complete-video-bg" v-show="!isVideoPlaying">
					<img
						:src="posterImage"
						alt="Video Poster"
						class="wpfnl-mm-complete-video-image"
					/>
					<div class="wpfnl-mm-complete-video-overlay"></div>
				</div>
				<!-- YouTube iframe -->
				<iframe
					v-show="isVideoPlaying"
					class="wpfnl-mm-complete-video-iframe"
					:src="videoUrl"
					title="WPFunnels Tutorial"
					frameborder="0"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
					allowfullscreen
				></iframe>
				<div class="wpfnl-mm-complete-video-player" v-show="!isVideoPlaying">
					<button class="wpfnl-mm-complete-play-btn" @click="playVideo">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M4 2L14 8L4 14V2Z" fill="white"/>
						</svg>
					</button>
					<div class="wpfnl-mm-complete-progress-bar">
						<div class="wpfnl-mm-complete-progress-fill"></div>
					</div>
				</div>
			</div>
		</div>

		<!-- Buttons Section -->
		<div class="wpfnl-mm-complete-actions">
			<div class="wpfnl-mm-complete-buttons">
				<button class="wpfnl-mm-btn wpfnl-mm-btn-secondary" @click="goToDashboard">
					Go to dashboard
				</button>

				<button class="wpfnl-mm-btn wpfnl-mm-btn-primary" @click="viewFunnel">
					Test the funnel
					<svg width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M1 6H16M16 6L11 1M16 6L11 11" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			</div>

			<div class="wpfnl-mm-complete-info">
				<p>{{ infoText }}</p>
			</div>
		</div>
	</div>
</template>

<script>
import apiFetch from '@wordpress/api-fetch';

export default {
	name: 'Complete',
	props: {
		funnelId: {
			type: [Number, String],
			default: null
		},
		firstStepLink: {
			type: String,
			default: ''
		},
		selectedGoal: {
			type: String,
			default: 'sales'
		}
	},
	data() {
		return {
			isVideoPlaying: false,
			posterImage: this.getPosterImage(),
			videoUrl: ''
		}
	},
	computed: {
		subtitleText() {
			if (this.selectedGoal === 'leads') {
				return 'Your first funnel is set up. Visitors opting in here will now see your next offer. Once leads start coming in, we’ll show you exactly how this funnel is growing your list.'
			}
			return "Your first funnel is set up. Customers buying this product will now see your order bump and upsell. Once real orders start coming in, we'll show you exactly how much extra revenue this funnel generates."		},
		infoText() {
			if (this.selectedGoal === 'leads') {
				return 'Once this funnel starts bringing in leads, you can clone it for your other offers in just a few clicks.'
			}
			return 'Once you see this funnel working, you can clone it for your other products in a few clicks.'		}
	},
	methods: {
		getPosterImage() {
			// Use the video poster from setup wizard object
			const wizardObj = window.setup_wizard_obj || {}
			return wizardObj.wizard_video_poster || ''
		},
		handleCompleteStep(action) {
			// Emit event to parent component
			const wizardObj = window.setup_wizard_obj || {}
			const restApiUrl = wizardObj.rest_api_url || wizardObj.rest_url || ''
			const requestArgs = {
				method: 'POST',
				data: {
					funnelId: this.funnelId,
					action: action
				}
			}

			if (restApiUrl) {
				const normalizedRestUrl = restApiUrl.endsWith('/') ? restApiUrl : `${restApiUrl}/`
				requestArgs.url = `${normalizedRestUrl}wpfunnels/v1/setup-wizard/complete-step`
			} else {
				requestArgs.path = '/wpfunnels/v1/setup-wizard/complete-step'
			}

			apiFetch(requestArgs)

		},
		viewFunnel() {
			this.handleCompleteStep('viewFunnel')
			// Open the first step preview in a new tab
			if (this.firstStepLink) {
				// Use the stored first step link
				window.open(this.firstStepLink, '_blank')
			} else {
				// Fallback to dashboard if no link is available
				const wizardObj = window.setup_wizard_obj || {}
				console.error('No first step link available')
				alert('Unable to open the funnel preview. Please go to the dashboard to view your funnel.')
				window.location.href = wizardObj.dashboard_url || `${wizardObj.admin_url}admin.php?page=wpfunnels`
			}
		},
		goToDashboard() {
			this.handleCompleteStep('goToDashboard')
			// Navigate to the funnels dashboard
			const wizardObj = window.setup_wizard_obj || {}
			window.location.href = wizardObj.dashboard_url || `${wizardObj.admin_url}admin.php?page=wpfunnels`
		},
		playVideo() {
			// Set video URL with autoplay and show the iframe
			this.videoUrl = 'https://www.youtube.com/embed/GrzIRl5jfBE?autoplay=1&rel=0'
			this.isVideoPlaying = true
		},
		showHelp() {
			window.open('https://getwpfunnels.com/docs/', '_blank')
		}
	}
}
</script>

<style scoped>
.wpfnl-mm-complete {
	width: 100%;
	max-width: 785px;
	display: flex;
	flex-direction: column;
	gap: 19px;
	align-items: center;
	position: relative;
}

.wpfnl-mm-complete-header {
	text-align: center;
	max-width: 631px;
}

.wpfnl-mm-complete-title {
	font-size: 24px;
	font-weight: 700;
	color: #363B4E;
	line-height: 35px;
	letter-spacing: -1px;
	margin: 0 0 8px 0;
}

.wpfnl-mm-complete-subtitle {
	font-size: 15px;
	font-weight: 400;
	color: #6E7A85;
	line-height: 22px;
	margin: 0;
	max-width: 534px;
	margin-left: auto;
	margin-right: auto;
}

/* Video/Preview Section */
.wpfnl-mm-complete-video-wrapper {
	width: 100%;
	padding: 10px;
	background: #FFF;
	border-radius: 16px;
	box-shadow:
		0px 2px 3px rgba(147, 130, 171, 0.05),
		0px 4px 5px rgba(85, 85, 85, 0.04),
		0px 4px 5px rgba(85, 85, 85, 0.03),
		0px 16px 16px rgba(85, 85, 85, 0.02);
}

.wpfnl-mm-complete-video {
	width: 100%;
	height: 382px;
	border-radius: 16px;
	background: #F6F5FA;
	position: relative;
	overflow: hidden;
	display: flex;
	flex-direction: column;
}

.wpfnl-mm-complete-video-iframe {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	border-radius: 16px;
	z-index: 2;
}

.wpfnl-mm-complete-video-bg {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
}

.wpfnl-mm-complete-video-image {
	width: 100%;
	height: 100%;
	object-fit: cover;
	object-position: center top;
}

.wpfnl-mm-complete-video-overlay {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: linear-gradient(
		180deg,
		rgba(60, 37, 111, 0) 37.658%,
		rgba(75, 52, 129, 0.3) 74.038%
	);
	border-radius: 16px;
}

.wpfnl-mm-complete-video-player {
	position: absolute;
	bottom: 18px;
	left: 20px;
	right: 20px;
	display: flex;
	align-items: center;
	gap: 12px;
}

.wpfnl-mm-complete-play-btn {
	width: 16px;
	height: 16px;
	background: transparent;
	border: none;
	cursor: pointer;
	padding: 0;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
}

.wpfnl-mm-complete-play-btn svg {
	width: 16px;
	height: 16px;
}

.wpfnl-mm-complete-progress-bar {
	flex: 1;
	height: 9px;
	background: linear-gradient(90deg, #FFFFFF 0%, rgba(255, 255, 255, 0) 100%);
	border-radius: 100px;
	overflow: hidden;
}

.wpfnl-mm-complete-progress-fill {
	width: 0%;
	height: 100%;
	background: #6E42D3;
	border-radius: 100px;
}

/* Actions Section */
.wpfnl-mm-complete-actions {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 6px;
	max-width: 592px;
	width: 100%;
}

.wpfnl-mm-complete-info {
	padding: 10px;
	text-align: center;
}

.wpfnl-mm-complete-info p {
	font-size: 14px;
	font-weight: 400;
	color: #7A8C9A;
	line-height: 22px;
	margin: 0;
}

/* Help Button */
.wpfnl-mm-help-btn {
	position: fixed;
	bottom: 40px;
	right: 54px;
	z-index: 100;
}

.wpfnl-mm-help-btn-circle {
	width: 34px;
	height: 34px;
	border-radius: 100px;
	background: #201F22;
	border: none;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0px 1px 2px rgba(190, 190, 215, 0.16);
	transition: all 0.3s ease;
}

.wpfnl-mm-help-btn-circle svg {
	width: 16px;
	height: 16px;
}

.wpfnl-mm-help-btn-circle:hover {
	background: #363B4E;
	transform: scale(1.05);
}
</style>
