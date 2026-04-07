<template>
	<div class="wpfnl-mm-complete">
		<!-- Header -->
		<div class="wpfnl-mm-complete-header">
			<h2 class="wpfnl-mm-complete-title">
				{{ titleText }}
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
					Go to Dashboard
				</button>

				<button class="wpfnl-mm-btn wpfnl-mm-btn-primary" @click="viewFunnel">
					{{ isStoreCheckout ? 'Go to Editor' : 'Test the Funnel' }}
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
		},
		agreeToShare: {
			type: Boolean,
			default: false
		}
	},
	data() {
		return {
			isVideoPlaying: false,
			posterImage: this.getPosterImage(),
			videoUrl: '',
			contactRequestInFlight: false,
			contactCreated: false,
		}
	},
	mounted() {
		this.maybeCreateContact();
	},
	computed: {
		isStoreCheckout() {
			return this.selectedGoal === 'improve-checkout';
		},
		titleText() {
			if (this.isStoreCheckout) {
				return `Your store checkout is ready! 🎉`;
			}
			return `Your funnel is live 🎉`;
		},
		subtitleText() {
			if (this.isStoreCheckout) {
				return `Your optimized checkout is set up. Customers will now experience a streamlined checkout flow designed to reduce cart abandonment and boost conversions.`;
			}
			if (this.selectedGoal === 'leads') {
				return `Your first funnel is set up. Visitors opting in here will now see your next offer. Once leads start coming in, we'll show you exactly how this funnel is growing your list.`;
			}
			return `Your first funnel is set up. Customers who purchase this product will now see your order bump and upsell offers. Once real orders start coming in, we'll show you exactly how much additional revenue this funnel generates.`;
		},
		infoText() {
			if (this.isStoreCheckout) {
				return `You can customize your checkout page further from the funnel editor.`;
			}
			if (this.selectedGoal === 'leads') {
				return `Once this funnel starts bringing in leads, you can clone it for your other offers in just a few clicks.`;
			}
			return `Once you see this funnel working, you can clone it for your other products in a few clicks.`;
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
					action: action,
					goal: this.selectedGoal
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
			this.maybeCreateContact();
			this.handleCompleteStep('viewFunnel')
			const wizardObj = window.setup_wizard_obj || {}
			// For store checkout, redirect to the funnel editor
			if (this.isStoreCheckout) {
				const adminUrl = wizardObj.admin_url || ''
				window.location.href = `${adminUrl}admin.php?page=edit_funnel&id=${this.funnelId}`
				return
			}
			// Open the first step preview in a new tab
			if (this.firstStepLink) {
				window.open(this.firstStepLink, '_blank')
			} else {
				console.error('No first step link available')
				alert('Unable to open the funnel preview. Please go to the dashboard to view your funnel.')
				window.location.href = wizardObj.dashboard_url || `${wizardObj.admin_url}admin.php?page=wpfunnels`
			}
		},
		goToDashboard() {
			this.maybeCreateContact();
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
