<template>
	<div class="wpfnl-mm-choose-template">
		<!-- Header -->
		<div class="wpfnl-mm-setup-header">
			<h2 class="wpfnl-mm-setup-title">Start With a High-Converting Template</h2>
			<p class="wpfnl-mm-setup-subtitle">
				Pick a ready-made funnel and customize it to match your store.
			</p>
		</div>

		<!-- Templates Grid -->
		<div class="wpfnl-mm-templates-grid" v-if="!loading">
			<div
				v-for="template in templates"
				:key="template.ID"
				class="wpfnl-mm-template-card"
				:class="{ 'active': selectedTemplateId === template.ID }"
				@click="selectTemplate(template)"
			>
				<div class="wpfnl-mm-template-card-preview">
					<img :src="template.featured_image" :alt="template.title" v-if="template.featured_image">
					<div class="wpfnl-mm-template-card-overlay">
						<button class="wpfnl-mm-btn-preview" @click.stop="showPreview(template)">Preview</button>
					</div>
					<div class="wpfnl-mm-template-card-checkmark" v-if="selectedTemplateId === template.ID">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<circle cx="8" cy="8" r="8" fill="#6E42D3"/>
							<path d="M11.3333 5.33334L6.66667 10L4.66667 8" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</div>
				</div>
				<div class="wpfnl-mm-template-card-divider"></div>
				<div class="wpfnl-mm-template-card-body">
					<h3 class="wpfnl-mm-template-card-title">{{ template.title }}</h3>
					<span class="wpfnl-mm-template-card-steps" v-if="template.steps">{{ template.steps.length }} Steps</span>
				</div>
			</div>
		</div>

        <div v-else class="wpfnl-mm-loader-wrapper">
            <span class="wpfnl-mm-loader"></span>
        </div>

		<!-- Buttons -->
		<div class="wpfnl-mm-choose-goal-buttons wpfnl-mm-buttons-container">
			<button class="wpfnl-mm-btn wpfnl-mm-btn-secondary" @click="goBack">
				<svg width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M16 6H1M1 6L6 1M1 6L6 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				Back
			</button>
			<div class="wpfnl-mm-btn-group">
				<button
					class="wpfnl-mm-btn wpfnl-mm-btn-primary"
					@click="handleContinue"
                    :disabled="!selectedTemplateId"
				>
					Continue
					<svg width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M1 6H16M16 6L11 1M16 6L11 11" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			</div>
		</div>

		<PreviewTemplate 
			v-if="showPreviewModal" 
			:template="previewTemplate" 
			@close="closePreview"
			@import="importFromPreview"
		/>
	</div>
</template>

<script>
import apiFetch from '@wordpress/api-fetch'
import { addQueryArgs } from '@wordpress/url'
import PreviewTemplate from './PreviewTemplate.vue'

export default {
	name: 'ChooseTemplate',
    components: {
        PreviewTemplate
    },
	props: {
		builder: {
			type: String,
			required: true
		},
		goal: {
			type: String,
			default: 'sales'
		}
	},
	data() {
		return {
			loading: true,
			templates: [],
			selectedTemplateId: null,
            selectedTemplate: null,
			showPreviewModal: false,
			previewTemplate: null,
			previewTab: 'listing'
		}
	},
	mounted() {
		this.fetchTemplates();
	},
	methods: {
		fetchTemplates() {
			this.loading = true;

            // Map builder ID if necessary (e.g. divi -> divi-builder)
            let builderParam = this.builder;
            if (builderParam === 'divi') {
                builderParam = 'divi-builder';
            }

            // Determine type based on goal
            // 'sales' -> 'wc', 'leads' -> 'lead'
            let typeParam = this.goal === 'sales' ? 'wc' : 'lead';

			const path = addQueryArgs(`${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/templates/get_templates`, {
				builder: builderParam,
				type: typeParam
			});

			apiFetch({ path: path })
				.then(response => {
					if (response.success && response.templates && response.templates.length > 0) {
						this.templates = response.templates;
					} else if (builderParam === 'bricks' || builderParam === 'oxygen') {
						// Fallback to Gutenberg templates if no templates found for Bricks or Oxygen
						this.fetchGutenbergFallback(typeParam);
						return;
					}
				})
				.catch(error => {
					console.error('Error fetching templates:', error);
					// Also fallback to Gutenberg on error for Bricks/Oxygen
					if (builderParam === 'bricks' || builderParam === 'oxygen') {
						this.fetchGutenbergFallback(typeParam);
						return;
					}
				})
				.finally(() => {
					this.loading = false;
				});
		},
		fetchGutenbergFallback(typeParam) {
			const fallbackPath = addQueryArgs(`${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/templates/get_templates`, {
				builder: 'gutenberg',
				type: typeParam
			});

			apiFetch({ path: fallbackPath })
				.then(response => {
					if (response.success && response.templates) {
						this.templates = response.templates;
					}
				})
				.catch(error => {
					console.error('Error fetching Gutenberg fallback templates:', error);
				})
				.finally(() => {
					this.loading = false;
				});
		},
		selectTemplate(template) {
			if (!template || !template.ID) {
				console.error('Invalid template:', template);
				return;
			}
			this.selectedTemplateId = template.ID;
			this.selectedTemplate = template;
		},
		showPreview(template) {
			this.previewTemplate = template;
			this.showPreviewModal = true;
			this.previewTab = 'listing';
		},
		closePreview() {
			this.showPreviewModal = false;
			this.previewTemplate = null;
		},
		importFromPreview(template) {
			// Close the preview modal
			this.closePreview();
			
			// Import the template
			this.importTemplate(template);
		},
		importTemplate(template) {
			// Select the template
			this.selectedTemplateId = template.id;
			this.selectedTemplate = template;

			// Close preview modal if open
			if (this.showPreviewModal) {
				this.closePreview();
			}

			// Immediately go to next step
			this.$emit('next-step', {
				template: this.selectedTemplate
			});
		},
		goBack() {
			this.$emit('prev-step');
		},
		handleContinue() {
			if (!this.selectedTemplateId) return;

			this.$emit('next-step', {
				template: this.selectedTemplate
			});
		}
	}
}
</script>
