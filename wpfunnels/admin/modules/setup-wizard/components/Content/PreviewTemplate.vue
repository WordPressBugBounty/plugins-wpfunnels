<template>
	<div class="wpfnl-mm-preview-modal-overlay" @click.self="closeModal">
		<div class="wpfnl-mm-preview-modal">
			<!-- Modal Header -->
			<div class="wpfnl-mm-preview-modal-header">
				<h3 class="wpfnl-mm-preview-modal-title">{{ template.title }}</h3>
				<div class="wpfnl-mm-preview-device-switcher">
					<button 
						class="wpfnl-mm-device-btn" 
						:class="{ 'active': activeView === 'desktop' }"
						@click="activeView = 'desktop'"
						title="Desktop View"
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M14 2H2C1.44772 2 1 2.44772 1 3V11C1 11.5523 1.44772 12 2 12H14C14.5523 12 15 11.5523 15 11V3C15 2.44772 14.5523 2 14 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M5 14H11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M8 12V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</button>
					<button 
						class="wpfnl-mm-device-btn" 
						:class="{ 'active': activeView === 'tablet' }"
						@click="activeView = 'tablet'"
						title="Tablet View"
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<rect x="3" y="1" width="10" height="14" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
							<path d="M7 12H9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
						</svg>
					</button>
					<button 
						class="wpfnl-mm-device-btn" 
						:class="{ 'active': activeView === 'mobile' }"
						@click="activeView = 'mobile'"
						title="Mobile View"
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<rect x="4" y="1" width="8" height="14" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
							<path d="M7 12H9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
						</svg>
					</button>
				</div>

				<div class="wpfnl-mm-preview-modal-actions">
					<button class="wpfnl-mm-btn wpfnl-mm-btn-primary wpfnl-mm-btn-import-modal" @click="importTemplate">
						Import This Funnel
						<svg width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M1 6H16M16 6L11 1M16 6L11 11" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</button>

					<!-- Close Button -->
					<button class="wpfnl-mm-preview-close-btn" @click="closeModal" title="Close">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</button>
				</div>
			</div>

			<!-- Modal Body -->
			<div class="wpfnl-mm-preview-modal-body">
				<!-- Steps Navigation -->
				<div class="wpfnl-mm-preview-steps">
					<div 
						v-for="step in template.steps" 
						:key="step.ID"
						class="wpfnl-mm-preview-step-card" 
						:class="{ 'active': activeStep.ID === step.ID }"
						@click="setActiveStep(step)"
					>
						<div class="wpfnl-mm-preview-step-thumbnail">
							<img :src="step.featured_image" :alt="step.title">
						</div>
						<p class="wpfnl-mm-preview-step-type">{{ step.step_type }}</p>
					</div>
				</div>

				<!-- Preview Content -->
				<div class="wpfnl-mm-preview-content" :class="activeView">
					<div class="wpfnl-mm-preview-device-frame">
						<div class="wpfnl-mm-preview-iframe-wrapper">
							<iframe 
								:src="activeStep?.link" 
								width="100%" 
								height="100%" 
								frameborder="0"
								@load="iframeLoaded"
							></iframe>
							<div class="wpfnl-mm-preview-loader" v-if="isLoading">
								<span class="wpfnl-mm-loader"></span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
export default {
	name: 'PreviewTemplate',
	props: {
		template: {
			type: Object,
			required: true,
			default: () => ({
				steps: [],
				title: ''
			})
		}
	},
	data() {
		return {
			activeStep: {},
			activeView: 'desktop',
			isLoading: true
		}
	},
	mounted() {
		if (this.template.steps && this.template.steps.length > 0) {
			this.activeStep = this.template.steps[0];
		}
		// Prevent body scroll when modal is open
		document.body.style.overflow = 'hidden';
	},
	beforeUnmount() {
		// Restore body scroll when modal is closed
		document.body.style.overflow = '';
	},
	methods: {
		setActiveStep(step) {
			this.activeStep = step;
			this.isLoading = true;
		},
		iframeLoaded() {
			this.isLoading = false;
		},
		closeModal() {
			this.$emit('close');
		},
		importTemplate() {
			this.$emit('import', this.template);
		}
	}
}
</script>