<template>
	<div class="wpfnl-store-checkout-step">

		<!-- Phase: Select Template -->
		<div v-if="phase === 'select'" class="wpfnl-mm-choose-template">
			<div class="wpfnl-mm-setup-header">
				<h2 class="wpfnl-mm-setup-title">Choose Your Store Checkout Template</h2>
				<p class="wpfnl-mm-setup-subtitle">
					Pick a ready-made checkout designed to reduce cart abandonment and boost conversions.
				</p>
			</div>

			<div class="wpfnl-mm-templates-grid" v-if="!loading">
				<div
					v-for="template in templates"
					:key="template.ID"
					class="wpfnl-mm-template-card"
					:class="{ 'wpfnl-mm-template-card--suggested': isSuggestedTemplate(template) }"
					@click="showPreview(template)"
				>
					<div class="wpfnl-mm-template-card-preview">
						<img :src="getCardImage(template)" :alt="template.title" v-if="getCardImage(template)">
						<div class="wpfnl-mm-template-card-overlay">
							<button class="wpfnl-mm-btn-preview" @click.stop="showPreview(template)">Preview</button>
						</div>
						<!-- Suggested badge -->
						<div class="wpfnl-mm-template-card-suggested" v-if="isSuggestedTemplate(template)">
							<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M5 1L6.18 3.4L8.8 3.78L6.9 5.64L7.36 8.25L5 6.99L2.64 8.25L3.1 5.64L1.2 3.78L3.82 3.4L5 1Z" fill="white" stroke="white" stroke-width="0.5" stroke-linejoin="round"/>
							</svg>
							Suggested
						</div>
					</div>
					<div class="wpfnl-mm-template-card-divider"></div>
					<div class="wpfnl-mm-template-card-body">
						<h3 class="wpfnl-mm-template-card-title">{{ template.title }}</h3>
						<span class="wpfnl-mm-template-card-steps" v-if="template.steps">{{ template.steps.length }} Steps</span>
					</div>
				</div>
			</div>

			<div v-else class="wpfnl-mm-templates-grid wpfnl-mm-templates-skeleton">
				<div v-for="n in 6" :key="n" class="wpfnl-mm-template-card wpfnl-mm-template-card--skeleton">
					<div class="wpfnl-mm-template-card-preview wpfnl-mm-skeleton-block"></div>
					<div class="wpfnl-mm-template-card-divider"></div>
					<div class="wpfnl-mm-template-card-body">
						<div class="wpfnl-mm-skeleton-block wpfnl-mm-skeleton-title"></div>
						<div class="wpfnl-mm-skeleton-block wpfnl-mm-skeleton-steps"></div>
					</div>
				</div>
			</div>

			<div class="wpfnl-mm-choose-goal-buttons wpfnl-mm-buttons-container">
				<button class="wpfnl-mm-btn wpfnl-mm-btn-secondary" @click="goBack">
					<svg width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M16 6H1M1 6L6 1M1 6L6 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					Back
				</button>
			</div>

			<PreviewTemplate
				v-if="showPreviewModal"
				:template="previewTemplate"
				:isStoreCheckout="true"
				:isSalesFunnel="false"
				@close="closePreview"
				@import="importFromPreview"
			/>
		</div>

		<!-- Phase: Generating -->
		<div v-else-if="phase === 'generating'" class="wpfnl-mm-setup">
			<div class="wpfnl-mm-setup-header">
				<h2 class="wpfnl-mm-setup-title">Setting Up Your Store Checkout</h2>
				<p class="wpfnl-mm-setup-subtitle">Creating an optimized checkout experience for your store.</p>
			</div>

			<div class="wpfnl-generate-funnel-container generating">
				<figure class="wpfnl-generate-funnel-canvas">
					<img :src="generateStoreImage" alt="Store checkout setup" width="728" height="250" />
				</figure>
				<div class="generate-funnel-overlay">
					<div class="generate-loader"></div>
					<span class="generated-notice" v-if="statusMessage">
						<span>{{ statusMessage }}</span>
					</span>
					<span class="generated-notice is-error" v-if="errorMessage">
						{{ errorMessage }}
					</span>
				</div>
			</div>
		</div>

		<!-- Phase: Complete (Celebration) -->
		<div v-else-if="phase === 'complete'" class="wpfnl-store-celebration">
			<canvas ref="confettiCanvas" class="wpfnl-confetti-canvas"></canvas>

			<div class="wpfnl-celebration-content">
				<div class="wpfnl-celebration-icon">
					<svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
						<circle cx="36" cy="36" r="36" fill="#EDE5FF"/>
						<circle cx="36" cy="36" r="26" fill="#6E42D3"/>
						<path d="M26 36L33 43L46 29" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</div>

				<h2 class="wpfnl-celebration-title">Setup Complete! 🎉</h2>
				<p class="wpfnl-celebration-subtitle">
					Your store checkout is ready. Start converting visitors into customers.
				</p>

				<div class="wpfnl-celebration-actions">
					<button class="wpfnl-mm-btn wpfnl-mm-btn-secondary" @click="goToDashboard">
						Go to Dashboard
					</button>
					<button class="wpfnl-mm-btn wpfnl-mm-btn-primary" @click="goToEditor">
						Go to Editor
						<svg width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M1 6H16M16 6L11 1M16 6L11 11" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</button>
				</div>
			</div>
		</div>

	</div>
</template>

<script>
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import PreviewTemplate from './PreviewTemplate.vue';

export default {
	name: 'StoreCheckout',
	components: { PreviewTemplate },
	props: {
		builder: {
			type: String,
			default: 'gutenberg'
		},
		prefetchedTemplates: {
			type: Array,
			default: () => []
		},
		agreeToShare: {
			type: Boolean,
			default: false
		}
	},
	data() {
		return {
			phase: 'select',
			loading: true,
			templates: [],
			selectedTemplateId: null,
			selectedTemplate: null,
			showPreviewModal: false,
			previewTemplate: null,
			statusMessage: '',
			errorMessage: '',
			createdFunnelId: null,
			generateStoreImage: window?.setup_wizard_obj?.generate_store_image || '',
		};
	},
	mounted() {
		const cached = this.prefetchedTemplates;
		if (cached && cached.length > 0) {
			this.templates = this.filterTemplates(cached);
			this.loading = false;
		} else {
			this.fetchTemplates();
		}
	},
	watch: {
		prefetchedTemplates(val) {
			if (!this.loading || !val || !val.length) return;
			this.templates = this.filterTemplates(val);
			this.loading = false;
		},
	},
	methods: {
		fetchTemplates() {
			this.loading = true;
			let builderParam = this.builder === 'divi' ? 'divi-builder' : (this.builder || 'gutenberg');

			const path = addQueryArgs(
				`${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/templates/get_templates`,
				{ builder: builderParam, type: 'store_checkout' }
			);

			apiFetch({ path })
				.then(response => {
					if (response.success && response.templates && response.templates.length > 0) {
						this.templates = this.filterTemplates(response.templates);
					} else if (builderParam !== 'gutenberg') {
						this.fetchFallback();
						return;
					}
				})
				.catch(() => {
					if (this.builder !== 'gutenberg') this.fetchFallback();
				})
				.finally(() => { this.loading = false; });
		},

		fetchFallback() {
			const path = addQueryArgs(
				`${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/templates/get_templates`,
				{ builder: 'gutenberg', type: 'store_checkout' }
			);
			apiFetch({ path })
				.then(response => {
					if (response.success && response.templates) {
						this.templates = this.filterTemplates(response.templates);
					}
				})
				.catch(() => {})
				.finally(() => { this.loading = false; });
		},

		isSuggestedTemplate(template) {
			return (template.title || '').toLowerCase().includes('instant checkout');
		},

		filterTemplates(templates) {
			const filtered = templates
				.filter(t => t.templateType !== 'pro')
				.filter(t => {
					if (!t.steps || !Array.isArray(t.steps)) return false;
					const types = t.steps.map(s => s.step_type);
					return types.includes('checkout') && types.includes('thankyou');
				})
				.map(t => {
					const checkout = t.steps.find(s => s.step_type === 'checkout');
					const thankyou = t.steps.find(s => s.step_type === 'thankyou');
					return { ...t, steps: [checkout, thankyou].filter(Boolean) };
				})
				.sort((a, b) => a.ID - b.ID);

			// Pin the suggested template first
			const suggestedIdx = filtered.findIndex(t => this.isSuggestedTemplate(t));
			if (suggestedIdx > 0) {
				const [suggested] = filtered.splice(suggestedIdx, 1);
				filtered.unshift(suggested);
			}

			return filtered;
		},

		getCardImage(template) {
			if (template.steps) {
				const checkoutStep = template.steps.find(s => s.step_type === 'checkout');
				if (checkoutStep && checkoutStep.featured_image) return checkoutStep.featured_image;
			}
			return template.featured_image;
		},

		selectTemplate(template) {
			this.selectedTemplateId = template.ID;
			this.selectedTemplate = template;
		},

		showPreview(template) {
			this.previewTemplate = template;
			this.showPreviewModal = true;
		},

		closePreview() {
			this.showPreviewModal = false;
			this.previewTemplate = null;
		},

		importFromPreview(template) {
			this.closePreview();
			this.selectedTemplateId = template.ID || template.id;
			this.selectedTemplate = template;
			this.startImport();
		},

		startImport() {
			if (!this.selectedTemplateId) return;
			this.phase = 'generating';
			this.$emit('store-checkout-phase', 'generating');
			this.generateFunnel();
		},

		async generateFunnel() {
			this.statusMessage = 'Configuring settings...';
			this.errorMessage = '';

			try {
				await this.updateGeneralSettings('sales');

				this.statusMessage = 'Creating store checkout...';
				const funnelResponse = await this.createFunnel();
				const funnelId = funnelResponse?.funnelID;

				if (!funnelId) throw new Error('Funnel could not be created. Please try again.');

				this.statusMessage = 'Importing template steps...';
				const { importedSteps } = await this.importSteps(funnelId);

				this.statusMessage = 'Finalizing...';
				await this.afterFunnelCreation(funnelId, importedSteps);

				this.createdFunnelId = funnelId;
				this.maybeCreateContact();
				this.handleCompleteTracking();
				this.phase = 'complete';
				this.$emit('store-checkout-phase', 'complete');
				this.$nextTick(() => this.launchConfetti());
			} catch (error) {
				this.errorMessage = error?.message || 'Unable to generate the funnel. Please try again.';
				this.statusMessage = '';
			}
		},

		updateGeneralSettings(funnelType) {
			return new Promise((resolve, reject) => {
				wpAjaxHelperRequest('update-general-settings', {
					funnel_type: funnelType,
					builder: this.builder || 'gutenberg'
				})
				.success(() => resolve())
				.error(err => reject(err));
			});
		},

		createFunnel() {
			const steps = this.filterStepsForImport(this.selectedTemplate?.steps || []);
			const data = {
				steps,
				name: 'Store Checkout',
				source: 'remote',
				type: 'wc',
				status: 'publish',
				remoteID: this.selectedTemplate?.ID || this.selectedTemplate?.id,
				is_store_checkout: true,
			};

			return new Promise((resolve, reject) => {
				wpAjaxHelperRequest('wpfunnel-import-funnel', data)
					.success(response => resolve(response || {}))
					.error(err => reject(err));
			});
		},

		filterStepsForImport(steps) {
			return (steps || []).filter(step => {
				const type = step?.step_type || step?.stepType || step?.type;
				return type === 'checkout' || type === 'thankyou';
			});
		},

		async importSteps(funnelId) {
			const stepsToImport = this.filterStepsForImport(this.selectedTemplate?.steps || []);
			const importedSteps = {};

			for (const step of stepsToImport) {
				try {
					const response = await apiFetch({
						path: `${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/steps/wpfunnel-import-step`,
						method: 'POST',
						data: { step, funnelID: funnelId, source: 'remote', importType: 'templates' }
					});

					if (response?.stepID && step?.step_type) {
						importedSteps[step.step_type] = response.stepID;
					}
				} catch (err) {
					console.error(`Error importing step ${step?.step_type}:`, err);
				}
			}

			if (!Object.keys(importedSteps).length) {
				throw new Error('Unable to import the selected template steps.');
			}

			return { importedSteps };
		},

		afterFunnelCreation(funnelId, importedSteps) {
			return new Promise(resolve => {
				wpAjaxHelperRequest('wpfunnel-after-funnel-creation', {
					funnelID: funnelId,
					source: 'remote',
					importedSteps: Object.values(importedSteps || {}),
					goal: 'improve-checkout',
					is_store_checkout: true,
				})
				.success(response => resolve(response))
				.error(() => resolve());
			});
		},

		handleCompleteTracking() {
			const wizardObj = window.setup_wizard_obj || {};
			const restApiUrl = wizardObj.rest_api_url || '';
			if (!restApiUrl) return;

			const normalizedUrl = restApiUrl.endsWith('/') ? restApiUrl : `${restApiUrl}/`;
			apiFetch({
				url: `${normalizedUrl}wpfunnels/v1/setup-wizard/complete-step`,
				method: 'POST',
				data: { funnelId: this.createdFunnelId, action: 'completed', goal: 'improve-checkout', total_steps: 3 },
			}).catch(() => {});
		},

		maybeCreateContact() {
			if (!this.agreeToShare) return;
			const wizardObj = window.setup_wizard_obj || {};
			if (!wizardObj.rest_api_url) return;

			apiFetch({
				path: `${wizardObj.rest_api_url}wpfunnels/v1/settings/create-contact/`,
				method: 'POST',
				data: { email: wizardObj.admin_email, name: wizardObj.admin_name },
			}).catch(() => {});
		},

		async goToEditor() {
			const wizardObj = window.setup_wizard_obj || {};
			const adminUrl = wizardObj.admin_url || '';
			window.location.href = `${adminUrl}admin.php?page=edit_funnel&id=${this.createdFunnelId}`;
		},

		async goToDashboard() {
			const wizardObj = window.setup_wizard_obj || {};
			window.location.href = wizardObj.dashboard_url || `${wizardObj.admin_url}admin.php?page=wpfunnels`;
		},

		goBack() {
			this.$emit('prev-step');
		},

		launchConfetti() {
			const canvas = this.$refs.confettiCanvas;
			if (!canvas) return;

			const ctx = canvas.getContext('2d');
			canvas.width = window.innerWidth;
			canvas.height = window.innerHeight;

			const colors = ['#6E42D3', '#F59E0B', '#10B981', '#EF4444', '#3B82F6', '#EC4899', '#8B5CF6', '#F97316', '#06B6D4'];
			const pieces = Array.from({ length: 220 }, () => ({
				x: Math.random() * canvas.width,
				y: Math.random() * -canvas.height * 0.5,
				w: Math.random() * 12 + 5,
				h: Math.random() * 6 + 3,
				color: colors[Math.floor(Math.random() * colors.length)],
				vy: Math.random() * 3.5 + 1.5,
				vx: (Math.random() - 0.5) * 2.5,
				angle: Math.random() * Math.PI * 2,
				va: (Math.random() - 0.5) * 0.18,
				opacity: 1,
			}));

			const startTime = Date.now();
			const duration = 5000;

			const draw = () => {
				const elapsed = Date.now() - startTime;
				if (elapsed > duration) {
					ctx.clearRect(0, 0, canvas.width, canvas.height);
					return;
				}

				ctx.clearRect(0, 0, canvas.width, canvas.height);

				pieces.forEach(p => {
					p.y += p.vy;
					p.x += p.vx;
					p.angle += p.va;
					p.opacity = elapsed > 3500 ? Math.max(0, 1 - (elapsed - 3500) / 1500) : 1;

					ctx.save();
					ctx.globalAlpha = p.opacity;
					ctx.translate(p.x + p.w / 2, p.y + p.h / 2);
					ctx.rotate(p.angle);
					ctx.fillStyle = p.color;
					ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
					ctx.restore();
				});

				requestAnimationFrame(draw);
			};

			draw();
		},
	}
};
</script>

<style scoped>
.wpfnl-store-checkout-step {
	width: 100%;
	display: contents;
}

/* Celebration Phase */
.wpfnl-store-celebration {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 100%;
	min-height: 100%;
	position: relative;
}

.wpfnl-confetti-canvas {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	pointer-events: none;
	z-index: 50;
}

.wpfnl-celebration-content {
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
	gap: 20px;
	max-width: 520px;
	padding: 60px 40px;
	background: #FFFFFF;
	border-radius: 24px;
	box-shadow: 0 20px 60px rgba(110, 66, 211, 0.12), 0 8px 20px rgba(0, 0, 0, 0.06);
	position: relative;
	z-index: 100;
	animation: celebrationPop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}

@keyframes celebrationPop {
	0% { transform: scale(0.85); opacity: 0; }
	100% { transform: scale(1); opacity: 1; }
}

.wpfnl-celebration-icon {
	animation: iconBounce 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.3s both;
}

@keyframes iconBounce {
	0% { transform: scale(0) rotate(-20deg); opacity: 0; }
	60% { transform: scale(1.15) rotate(5deg); }
	100% { transform: scale(1) rotate(0deg); opacity: 1; }
}

.wpfnl-celebration-title {
	font-size: 28px;
	font-weight: 700;
	font-family: 'DM Sans', sans-serif;
	color: #363B4E;
	margin: 0;
	letter-spacing: -0.5px;
}

.wpfnl-celebration-subtitle {
	font-size: 15px;
	font-weight: 400;
	font-family: 'DM Sans', sans-serif;
	color: #6E7A85;
	line-height: 1.6;
	margin: 0;
}

.wpfnl-celebration-actions {
	display: flex;
	gap: 12px;
	margin-top: 8px;
}

.wpfnl-celebration-actions .wpfnl-mm-btn {
	padding: 12px 28px;
	border-radius: 10px;
	font-size: 15px;
	font-weight: 600;
	font-family: 'DM Sans', sans-serif;
	cursor: pointer;
	transition: all 0.3s ease;
	border: none;
	display: inline-flex;
	align-items: center;
	gap: 8px;
	height: 48px;
}

.wpfnl-celebration-actions .wpfnl-mm-btn-secondary {
	background: #F6F5FA;
	color: #363B4E;
	border: 1px solid #ECEBF0;
}

.wpfnl-celebration-actions .wpfnl-mm-btn-secondary:hover {
	border-color: #6E42D3;
	color: #6E42D3;
}

.wpfnl-celebration-actions .wpfnl-mm-btn-primary {
	background: #6E42D3;
	color: #FFFFFF;
}

.wpfnl-celebration-actions .wpfnl-mm-btn-primary:hover {
	background: #5c36b3;
}

/* Generating phase styles */
.generate-funnel-overlay {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 14px;
	text-align: center;
}

.generate-loader {
	order: 1;
}

.generated-notice {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	color: #6E42D3;
	font-size: 14px;
	font-weight: 600;
	order: 2;
}

.generated-notice.is-error {
	color: #d14343;
}
</style>
