<template>
	<div class="wpfnl-mm-setup">
		<!-- Heading Section -->
		<div class="wpfnl-mm-setup-header">
			<h2 class="wpfnl-mm-setup-title">
				{{ isStoreCheckout ? 'Setting Up Your Store Checkout' : 'Auto-generate Starter Funnel' }}
			</h2>
			<p class="wpfnl-mm-setup-subtitle">
				{{ isStoreCheckout ? 'Creating an optimized checkout experience for your store.' : 'Auto-generating your starter funnel based on your selections.' }}
			</p>
		</div>

		<!-- generate-funnel-container -->
		<div class="wpfnl-generate-funnel-container" :class="isGenerating ? 'generating' : ''">
			<figure class="wpfnl-generate-funnel-canvas">
                <img :src="isStoreCheckout ? generateStoreImage : generateFunnelImage" alt="Funnel canvas" width="728" height="250" />
            </figure>

			<div class="generate-funnel-overlay">
				<div class="generate-loader" v-if="isGenerating"></div>

				<span
					class="generated-notice"
					v-if="statusMessage || errorMessage"
					:class="{ 'is-error': !!errorMessage }"
				>
					<svg v-if="!errorMessage && !isGenerating" xmlns="http://www.w3.org/2000/svg" width="16" height="11" viewBox="0 0 16 11" fill="none"><path d="M14.7511 0.422913C14.287 -0.00764301 13.5333 -0.00737156 13.0686 0.422913L5.49641 7.4387L2.13089 4.32063C1.66617 3.89008 0.91287 3.89008 0.44815 4.32063C-0.0165708 4.75119 -0.0165708 5.44911 0.44815 5.87967L4.65486 9.77711C4.88708 9.99225 5.19157 10.1001 5.49609 10.1001C5.80061 10.1001 6.1054 9.99253 6.33761 9.77711L14.7511 1.98192C15.2158 1.55166 15.2158 0.853441 14.7511 0.422913Z" fill="#239654" stroke="#239654" stroke-width=".2"/></svg>
					<span>{{ errorMessage || statusMessage }}</span>
				</span>
			</div>
		</div>

		<!-- Buttons -->
		<div class="wpfnl-mm-choose-goal-buttons">
			<button class="wpfnl-mm-btn wpfnl-mm-btn-secondary" @click="goBack" :disabled="isGenerating">
				Back
			</button>

			<div class="wpfnl-mm-btn-group">
				<button 
					class="wpfnl-mm-btn wpfnl-mm-btn-primary" 
					@click="handleContinue"
					:disabled="isGenerating || !createdFunnelId"
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
import apiFetch from '@wordpress/api-fetch';

export default {
	name: 'GenerateFunnel',
	props: {
		selectedProduct: {
			type: Object,
			default: null
		},
		template: {
			type: Object,
			default: null
		},
		goal: {
			type: String,
			default: 'sales'
		},
		builder: {
			type: String,
			default: 'gutenberg'
		},
		mainProduct: {
			type: Object,
			default: null
		},
		orderBump: {
			type: Object,
			default: null
		},
		upsellProduct: {
			type: Object,
			default: null
		}
	},
	data() {
		return {
			generateFunnelImage: window?.setup_wizard_obj?.generate_funnel_image || '',
			generateStoreImage: window?.setup_wizard_obj?.generate_store_image || '',
			isGenerating: true,
			statusMessage: 'Preparing your funnel selections...',
			errorMessage: '',
			createdFunnelId: null,
			firstStepLink: ''
		};
	},
	computed: {
		isStoreCheckout() {
			return this.goal === 'improve-checkout';
		},
		filteredTemplate() {
			// Ensure template steps are filtered based on goal
			if (!this.template || !this.template.steps) return this.template;
			
			// For sales funnel, ensure only landing, checkout, thankyou
			if (this.goal === 'sales') {
				const allowedTypes = ['landing', 'checkout', 'thankyou'];
				const filteredSteps = this.template.steps.filter(step => {
					const stepType = step?.step_type || step?.stepType || step?.type;
					return allowedTypes.includes(stepType);
				});
				
				console.log('Filtered template for sales funnel. Original steps:', this.template.steps.length, 'Filtered:', filteredSteps.length);
				
				return {
					...this.template,
					steps: filteredSteps
				};
			}
			
			return this.template;
		}
	},
	mounted() {
		this.startFunnelGeneration();
	},
	methods: {
		async startFunnelGeneration() {
			if (!this.hasValidTemplate()) {
				this.handleError('Unable to generate a funnel because no template was selected.');
				return;
			}

			// Product is only required for non-store-checkout flows
			if (!this.isStoreCheckout && !this.hasValidProduct()) {
				this.handleError('Please select a product before generating the funnel.');
				return;
			}

			this.isGenerating = true;
			this.errorMessage = '';
			this.statusMessage = 'Configuring funnel settings...';

			try {
				const funnelType = this.getFunnelType();
				const templateType = this.getTemplateType(funnelType);

				await this.updateGeneralSettings(funnelType);

				this.statusMessage = this.isStoreCheckout ? 'Creating store checkout...' : 'Creating funnel shell...';
				const funnelResponse = await this.createFunnel(templateType);
				const funnelId = funnelResponse?.funnelID;

				if (!funnelId) {
					throw new Error('Funnel could not be created. Please try again.');
				}

				this.statusMessage = 'Importing template steps...';
				const { importedSteps, firstStepLink } = await this.importSteps(funnelId);

				// Assign products for non-store-checkout flows
				if (!this.isStoreCheckout) {
					this.statusMessage = 'Assigning products...';
					await this.assignProductsToSteps(importedSteps);
				}

				this.statusMessage = 'Finalizing funnel...';
				await this.afterFunnelCreation(funnelId, importedSteps);

				this.createdFunnelId = funnelId;
				this.firstStepLink = firstStepLink;
				this.isGenerating = false;
				this.statusMessage = this.isStoreCheckout ? 'Your store checkout is ready!' : 'The funnel is ready to go.';
			} catch (error) {
				console.error('Funnel generation failed:', error);
				this.handleError(error && error.message ? error.message : 'Unable to generate the funnel. Please try again.');
			}
		},
		hasValidTemplate() {
			return this.filteredTemplate && Array.isArray(this.filteredTemplate.steps) && this.filteredTemplate.steps.length > 0;
		},
		hasValidProduct() {
			// Check for mainProduct (from BuildFunnel) or selectedProduct (legacy)
			if (this.mainProduct && this.mainProduct.id) return true;
			return !!(this.selectedProduct && this.selectedProduct.id);
		},
		getFunnelType() {
			return this.goal === 'leads' ? 'lead' : 'sales';
		},
		getTemplateType(funnelType) {
			return funnelType === 'sales' ? 'wc' : 'lead';
		},
		updateGeneralSettings(funnelType) {
			return new Promise((resolve, reject) => {
				wpAjaxHelperRequest('update-general-settings', {
					funnel_type: funnelType,
					builder: this.builder || 'gutenberg'
				})
				.success(() => resolve())
				.error(error => reject(error));
			});
		},
		createFunnel(templateType) {
			const steps = this.filterStepsForImport(this.filteredTemplate?.steps || []);
			const data = {
				steps: steps,
				name: this.isStoreCheckout ? 'Store Checkout' : (this.filteredTemplate?.title || 'My First Funnel'),
				source: 'remote',
				type: templateType,
				status: 'draft'
			};
			
			// Always pass remoteID - backend needs it to create funnel
			data.remoteID = this.filteredTemplate?.ID || this.filteredTemplate?.id;
			
			if (this.isStoreCheckout) {
				data.is_store_checkout = true;
			}

			console.log('Creating funnel with data:', {
				...data,
				stepCount: steps.length,
				stepTypes: steps.map(s => s.step_type || s.stepType || s.type)
			});

			return new Promise((resolve, reject) => {
				wpAjaxHelperRequest('wpfunnel-import-funnel', data)
					.success(response => {
						console.log('Funnel creation response:', response);
						resolve(response || {});
					})
					.error(error => {
						console.error('Funnel creation error:', error);
						reject(error);
					});
			});
		},
		filterStepsForImport(steps) {
			const isProActive = window?.setup_wizard_obj?.is_pro_active === 'yes';
			return (steps || []).filter(step => {
				const stepType = step?.step_type || step?.stepType || step?.type;
				
				// For store checkout, only import checkout and thankyou steps
				if (this.isStoreCheckout) {
					return stepType === 'checkout' || stepType === 'thankyou';
				}
				
				// For sales funnel, only import landing, checkout, and thankyou steps
				if (this.goal === 'sales') {
					return stepType === 'landing' || stepType === 'checkout' || stepType === 'thankyou';
				}
				
				// For other goals, exclude downsell if pro is not active
				if (stepType === 'downsell' && !isProActive) {
					return false;
				}
				
				return true;
			});
		},
		async importSteps(funnelId) {
			const stepsToImport = this.filterStepsForImport(this.filteredTemplate?.steps || []);
			
			// Additional safety check for sales funnel - ensure no upsell/downsell
			const finalStepsToImport = this.goal === 'sales' 
				? stepsToImport.filter(step => {
					const stepType = step?.step_type || step?.stepType || step?.type;
					const isAllowed = stepType === 'landing' || stepType === 'checkout' || stepType === 'thankyou';
					if (!isAllowed) {
						console.warn(`Skipping step type "${stepType}" for sales funnel`);
					}
					return isAllowed;
				})
				: stepsToImport;
			
			console.log('Steps to import for goal "' + this.goal + '":', finalStepsToImport.map(s => s.step_type || s.stepType || s.type));
			
			const importedSteps = {};
			let firstStepLink = '';

			for (let index = 0; index < finalStepsToImport.length; index++) {
				const step = finalStepsToImport[index];
				const stepType = step?.step_type || step?.stepType || step?.type;
				
				console.log(`Importing step ${index + 1}/${finalStepsToImport.length}: ${stepType}`);
				
				try {
					const response = await apiFetch({
						path: `${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/steps/wpfunnel-import-step`,
						method: 'POST',
						data: {
							step,
							funnelID: funnelId,
							source: 'remote',
							importType: 'templates'
						}
					});

					if (response?.stepID && step?.step_type) {
						importedSteps[step.step_type] = response.stepID;
					}

					if (index === 0 && response?.stepViewLink) {
						firstStepLink = response.stepViewLink;
					}
				} catch (error) {
					console.error(`Error importing step ${step?.step_type || index}:`, error);
				}
			}

			if (!Object.keys(importedSteps).length) {
				throw new Error('Unable to import the selected template steps.');
			}

			console.log('Successfully imported steps:', Object.keys(importedSteps));

			return { importedSteps, firstStepLink };
		},
		async assignProductsToSteps(importedSteps) {
			const product = this.mainProduct || this.selectedProduct;

			// Assign main product to checkout step
			if (importedSteps.checkout && product && product.id) {
				try {
					await apiFetch({
						path: `${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/checkout/wpfnl-add-product`,
						method: 'POST',
						data: {
							id: product.id,
							step_id: importedSteps.checkout,
							quantity: 1
						}
					});
				} catch (error) {
					console.error('Error assigning main product:', error);
				}
			}

			// Assign order bump to checkout step
			if (importedSteps.checkout && this.orderBump && this.orderBump.id) {
				try {
					let productImage = {
						id: 0,
						url: window.setup_wizard_obj.plugin_url ? window.setup_wizard_obj.plugin_url + 'admin/assets/images/placeholder.png' : ''
					};

					try {
						const productData = await apiFetch({
							path: `${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/updateSelectedProduct?product=${this.orderBump.id}`,
							method: 'GET'
						});

						if (productData && productData.img) {
							const imgTag = productData.img;
							const srcMatch = imgTag.match(/src="([^"]+)"/);
							if (srcMatch) {
								productImage.url = srcMatch[1];
							}
							const idMatch = imgTag.match(/wp-image-(\d+)/);
							if (idMatch) {
								productImage.id = parseInt(idMatch[1]);
							}
						}
					} catch (imgError) {
						console.error('Could not fetch product image, using placeholder:', imgError);
					}

					const orderBumpData = [{
						name: 'Order Bump',
						selectedStyle: 'style1',
						isEnabled: true,
						position: 'after-order',
						product: this.orderBump.id,
						productName: this.orderBump.name,
						chooseVariantName: 'Choose an Option',
						productSearchName: this.orderBump.name,
						productType: '',
						price: '',
						salePrice: '',
						quantity: '1',
						htmlPrice: this.orderBump.priceHtml || '',
						numericRegularPrice: '',
						numericSalePrice: '',
						discountPrice: '',
						discountPriceHtml: '',
						productImage: productImage,
						highLightText: this.orderBump.name,
						checkBoxLabel: 'Yes, I want this!',
						productDescriptionText: 'Add this special offer to your order',
						discountOption: 'original',
						discountapply: 'regular',
						discountValue: '',
						couponName: '',
						obNextStep: 'default',
						isReplace: 'no',
						replaceSettings: {
							isAllReplace: 'yes',
							selectedProducts: []
						},
						replace: '',
						obArrowColor: '#EE8134',
						obPrimaryColor: '#6E42D2',
						obBgColor: '',
						obTitleColor: '#363B4E',
						obHighlightColor: '#6E42D3',
						obCheckboxTitleColor: '#d9d9d9',
						obDescriptionColor: '#7A8B9A',
						isRemoveObBgColor: '',
						obPriceColor: '#E86F2C',
						obChooseVariantColor: '#F34D01',
						prePurchaseUpsell: 'no'
					}];

					await apiFetch({
						path: `${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/order-bump`,
						method: 'POST',
						data: {
							value: orderBumpData,
							stepID: importedSteps.checkout
						}
					});
				} catch (error) {
					console.error('Error creating order bump:', error);
				}
			}

			// Assign upsell product to upsell step
			if (importedSteps.upsell && this.upsellProduct && this.upsellProduct.id) {
				try {
					const upsellData = {
						id: this.upsellProduct.id,
						quantity: 1
					};

					await apiFetch({
						path: `${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/offer/saveUpsellData/`,
						method: 'POST',
						data: {
							step_id: importedSteps.upsell,
							product: JSON.stringify(upsellData)
						}
					});
				} catch (error) {
					console.error('Error assigning upsell product:', error);
				}
			}
		},
		afterFunnelCreation(funnelId, importedSteps) {
			const payload = {
				funnelID: funnelId,
				source: 'remote',
				importedSteps: Object.values(importedSteps || {}),
				goal: this.goal
			};

			if (this.isStoreCheckout) {
				payload.is_store_checkout = true;
			}

			return new Promise(resolve => {
				wpAjaxHelperRequest('wpfunnel-after-funnel-creation', payload)
				.success(response => resolve(response))
				.error(error => {
					console.error('After funnel creation error:', error);
					resolve();
				});
			});
		},
		handleContinue() {
			if (this.isGenerating || !this.createdFunnelId) {
				return;
			}

			this.$emit('next-step', {
				funnelId: this.createdFunnelId,
				firstStepLink: this.firstStepLink
			});
		},
		goBack() {
			if (this.isGenerating) {
				return;
			}
			this.$emit('prev-step');
		},
		handleError(message) {
			this.errorMessage = message;
			this.isGenerating = false;
			this.statusMessage = '';
			this.createdFunnelId = null;
			this.firstStepLink = '';
		}
	}
};
</script>

<style scoped>
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
	color: #239654;
	font-size: 14px;
	font-weight: 600;
	order: 2;
}

.generated-notice.is-error {
	color: #d14343;
}
</style>
