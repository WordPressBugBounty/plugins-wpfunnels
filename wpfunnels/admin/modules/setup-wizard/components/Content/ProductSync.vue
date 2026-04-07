<template>
	<div class="wpfnl-mm-setup">
		<!-- Heading Section -->
		<div class="wpfnl-mm-setup-header">
			<h2 class="wpfnl-mm-setup-title">
				Product Sync
			</h2>
			<p class="wpfnl-mm-setup-subtitle">
				Get your sales funnel up and running in minutes.
			</p>
		</div>

		<!-- product-sync-container -->
		<div class="wpfnl-product-sync-container">
			<!-- Search Bar -->
			<div class="wpfnl-product-sync-search">
				<div class="wpfnl-product-sync-search-wrapper">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M14.0008 14L11.1074 11.1067" stroke="#99a1af" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.33333 12.6667C10.2789 12.6667 12.6667 10.2789 12.6667 7.33333C12.6667 4.38781 10.2789 2 7.33333 2C4.38781 2 2 4.38781 2 7.33333C2 10.2789 4.38781 12.6667 7.33333 12.6667Z" stroke="#99a1af" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<input
						type="text"
						v-model="searchQuery"
						@input="handleSearchInput"
						placeholder="Type to search for a product..."
						class="wpfnl-product-sync-search-input"
					/>
				</div>

				<CreateProduct
					ref="createProduct"
					:isCreateProduct="isCreateProduct"
					:isShowModalLoader="isShowModalLoader"
					@removeCreateProductModal="removeCreateProductModal"
				/>
			</div>

			<!-- Suggested Product Label -->
			<div class="wpfnl-product-sync-label">
				Suggested Product
			</div>

			<!-- Skeleton Loading -->
			<div v-if="loading" class="wpfnl-product-sync-skeleton">
				<div v-for="n in 3" :key="n" class="wpfnl-product-sync-skeleton-card">
					<div class="wpfnl-skeleton-image wpfnl-skeleton-pulse"></div>
					<div class="wpfnl-skeleton-content">
						<div class="wpfnl-skeleton-title wpfnl-skeleton-pulse"></div>
						<div class="wpfnl-skeleton-description wpfnl-skeleton-pulse"></div>
						<div class="wpfnl-skeleton-price wpfnl-skeleton-pulse"></div>
					</div>
					<div class="wpfnl-skeleton-checkbox wpfnl-skeleton-pulse"></div>
				</div>
			</div>

			<!-- Product Cards -->
			<div v-else-if="visibleProducts.length" class="wpfnl-product-sync-cards">
				<label
					v-for="product in visibleProducts"
					:key="product.id"
					class="wpfnl-product-sync-card"
					:class="{ 'active': selectedProduct === product.id }"
				>
					<input
						type="radio"
						name="product-selection"
						:value="product.id"
						v-model.number="selectedProduct"
						class="wpfnl-product-sync-radio"
					/>
					<div class="wpfnl-product-image">
						<img
							v-if="product.image"
							:src="product.image"
							:alt="product.title"
							loading="lazy"
							style="width:32px;height:32px;border-radius:6px;object-fit:cover;"
						/>
						<svg v-else width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M16 2L19.5 12.5H30.5L21.5 19.5L25 30L16 23L7 30L10.5 19.5L1.5 12.5H12.5L16 2Z" fill="#E5E7EB" stroke="#D1D5DB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</div>

					<div class="wpfnl-product-sync-card-content">
						<h3 class="wpfnl-product-sync-card-title">{{ product.title }}</h3>
						<p class="wpfnl-product-sync-card-description">{{ product.description }}</p>
						<p class="wpfnl-product-sync-card-price">{{ product.price }}</p>
					</div>

					<div class="wpfnl-product-sync-card-checkbox">
						<div class="wpfnl-product-sync-checkbox-circle">
							<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M11.6667 3.5L5.25 9.91667L2.33334 7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</div>
					</div>
				</label>
			</div>

			<!-- Error State -->
			<div v-else-if="errorMessage" class="wpfnl-product-sync-state-error">
				<div class="wpfnl-product-sync-error-icon">
					<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M14 9.33337V14M14 18.6667H14.0117" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M14 25.6667C20.4434 25.6667 25.6667 20.4434 25.6667 14C25.6667 7.55672 20.4434 2.33337 14 2.33337C7.55672 2.33337 2.33337 7.55672 2.33337 14C2.33337 20.4434 7.55672 25.6667 14 25.6667Z" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</div>
				<p class="wpfnl-product-sync-error-text">{{ errorMessage }}</p>
				<button class="wpfnl-product-sync-retry-btn" @click="fetchProducts(searchQuery)">
					<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M1.16663 2.33337V5.83337H4.66663" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M2.44496 8.75C2.86584 9.89122 3.65241 10.8618 4.68197 11.5098C5.71152 12.1578 6.92457 12.4463 8.13396 12.3316C9.34336 12.2169 10.4816 11.7053 11.3725 10.8741C12.2633 10.0429 12.8544 8.94008 13.054 7.74004C13.2536 6.54 13.0506 5.30844 12.4766 4.23654C11.9027 3.16465 10.9903 2.31163 9.88294 1.81089C8.77557 1.31015 7.53476 1.19003 6.35363 1.46916C5.17249 1.7483 4.11847 2.41065 3.35996 3.35337L1.16663 5.83337" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					Try Again
				</button>
			</div>

			<!-- Empty State -->
			<div v-else class="wpfnl-product-sync-state-empty">
				<div class="wpfnl-product-sync-empty-icon">
					<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M33.3333 10L20 3.33337L6.66663 10V30L20 36.6667L33.3333 30V10Z" stroke="#D1D5DB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M20 36.6667V20" stroke="#D1D5DB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M33.3333 10L20 20" stroke="#D1D5DB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M20 20L6.66663 10" stroke="#D1D5DB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</div>
				<p class="wpfnl-product-sync-empty-title">No products found</p>
				<p class="wpfnl-product-sync-empty-subtitle">Create your first product to get started with your funnel.</p>
				<button class="wpfnl-product-sync-create-btn" @click="triggerCreateProduct">
					<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M7 2.91663V11.0833" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M2.91663 7H11.0833" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					Create Product
				</button>
			</div>

			<div class="wpfnl-product-sync-state-error wpfnl-product-sync-template-error" v-if="templateError">
				<div class="wpfnl-product-sync-error-icon">
					<svg width="20" height="20" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M14 9.33337V14M14 18.6667H14.0117" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M14 25.6667C20.4434 25.6667 25.6667 20.4434 25.6667 14C25.6667 7.55672 20.4434 2.33337 14 2.33337C7.55672 2.33337 2.33337 7.55672 2.33337 14C2.33337 20.4434 7.55672 25.6667 14 25.6667Z" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</div>
				<p class="wpfnl-product-sync-error-text">{{ templateError }}</p>
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
					:disabled="loading || templateLoading || !selectedProduct || !selectedTemplate"
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
import { addQueryArgs } from '@wordpress/url';
import CreateProduct from './CreateProduct.vue';

export default {
	name: 'ProductSync',
	components: {
		CreateProduct
	},
	isCreateProduct: false,
	isShowModalLoader: false,
	productUrl: window?.setup_wizard_obj?.product_url,
	props: {
		goal: {
			type: String,
			default: 'sales'
		},
		builder: {
			type: String,
			default: ''
		},
		selectedProductId: {
			type: [Number, String],
			default: null
		}
	},
	data() {
		return {
			selectedProduct: this.normalizeProductId(this.selectedProductId),
			searchQuery: '',
			products: [],
			loading: false,
			errorMessage: '',
			perPage: 3,
			searchPerPage: 10,
			debounceHandle: null,
			isCreateProduct: false,
			isShowModalLoader: false,
			productUrl: window && window.setup_wizard_obj ? window.setup_wizard_obj.product_url : '',
			templates: [],
			selectedTemplate: null,
			templateLoading: false,
			templateError: ''
		};
	},
	computed: {
		visibleProducts() {
			return this.products;
		}
	},
	watch: {
		selectedProductId(newValue) {
			this.selectedProduct = this.normalizeProductId(newValue);
		},
		goal: {
			handler() {
				this.fetchTemplates();
			},
			immediate: false
		},
		builder: {
			handler() {
				this.fetchTemplates();
			},
			immediate: false
		}
	},
	mounted() {
		this.fetchProducts();
		this.fetchTemplates();
	},
	beforeUnmount() {
		this.clearDebounce();
	},
	methods: {
		normalizeProductId(value) {
			if (value === null || value === undefined || value === '') {
				return null;
			}
			const parsed = parseInt(value, 10);
			return Number.isNaN(parsed) ? null : parsed;
		},
		clearDebounce() {
			if (this.debounceHandle) {
				clearTimeout(this.debounceHandle);
				this.debounceHandle = null;
			}
		},
		getTemplateType() {
			return this.goal === 'sales' ? 'wc' : 'lead';
		},
		normalizeBuilderParam(builder) {
			if (!builder) {
				return '';
			}
			if (builder === 'divi') {
				return 'divi-builder';
			}
			return builder;
		},
		shouldFallbackToGutenberg(builderParam) {
			return builderParam === 'bricks' || builderParam === 'oxygen';
		},
		requestTemplates(builderParam, typeParam) {
			const path = addQueryArgs(
				`${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/templates/get_templates`,
				{
					builder: builderParam,
					type: typeParam
				}
			);

			return apiFetch({ path }).then(response => {
				if (response && response.success && Array.isArray(response.templates) && response.templates.length) {
					this.templates = response.templates;
					const rotationIndex = this.getNextTemplateIndex(response.templates.length, builderParam, typeParam);
					this.selectedTemplate = response.templates[rotationIndex];
					return true;
				}
				const message = response && response.message ? response.message : 'No templates found for the selected goal.';
				throw new Error(message);
			});
		},
		getRotationStorageKey(builderParam, typeParam) {
			const goalKey = typeParam === 'wc' ? 'sales' : typeParam || 'lead';
			const builderKey = builderParam || 'gutenberg';
			return `wpfnl_template_rotation_${goalKey}_${builderKey}`;
		},
		getNextTemplateIndex(templateCount, builderParam, typeParam) {
			if (!templateCount) {
				return 0;
			}
			const key = this.getRotationStorageKey(builderParam, typeParam);
			try {
				const storedIndex = window?.sessionStorage ? parseInt(window.sessionStorage.getItem(key), 10) : NaN;
				const nextIndex = Number.isInteger(storedIndex) ? (storedIndex + 1) % templateCount : 0;
				if (window?.sessionStorage) {
					window.sessionStorage.setItem(key, nextIndex);
				}
				return nextIndex;
			} catch (error) {
				return 0;
			}
		},
		fetchTemplates() {
			this.templateLoading = true;
			this.templateError = '';

			const builderValue = this.builder ? this.builder : 'gutenberg';
			const builderParam = this.normalizeBuilderParam(builderValue);
			const typeParam = this.getTemplateType();

			return this.requestTemplates(builderParam, typeParam)
				.catch(error => {
					if (this.shouldFallbackToGutenberg(builderParam)) {
						return this.requestTemplates('gutenberg', typeParam);
					}
					throw error;
				})
				.catch(error => {
					this.templates = [];
					this.selectedTemplate = null;
					this.templateError = error && error.message ? error.message : 'Unable to retrieve templates.';
				})
				.finally(() => {
					this.templateLoading = false;
				});
		},
		triggerCreateProduct() {
			if (this.$refs.createProduct && this.$refs.createProduct.showCreateProductModal) {
				this.$refs.createProduct.showCreateProductModal();
			}
		},
		handleSearchInput() {
			this.clearDebounce();
			this.loading = true;
			this.errorMessage = '';
			this.debounceHandle = setTimeout(() => {
				this.fetchProducts(this.searchQuery);
			}, 400);
		},
		fetchProducts(searchTerm = '') {
			this.loading = true;
			this.errorMessage = '';

			const trimmedSearch = searchTerm ? searchTerm.trim() : '';
			const args = { limit: trimmedSearch ? this.searchPerPage : this.perPage };
			if (trimmedSearch) {
				args.search = trimmedSearch;
			}

			const path = addQueryArgs(
				`${window.setup_wizard_obj.rest_api_url}wpfunnels/v1/setup-wizard/products`,
				args
			);

			apiFetch({ path })
				.then(response => {
					if (response && response.success === false) {
						this.products = [];
						this.selectedProduct = null;
						this.errorMessage = response.message || 'Unable to load products.';
						return;
					}

					this.products = Array.isArray(response && response.products)
						? response.products
							.filter(product => !!product)
							.map(product => {
								const normalizedId = this.normalizeProductId(product.id);
								if (normalizedId === null) {
									return null;
								}
								return {
									...product,
									id: normalizedId
								};
							})
							.filter(product => !!product)
						: [];

					if (!this.products.length) {
						this.selectedProduct = null;
						return;
					}

					const hasExistingSelection = this.products.some(
						product => product.id === this.selectedProduct
					);

					if (!hasExistingSelection) {
						this.selectedProduct = this.products[0].id;
					}
				})
				.catch(error => {
					this.products = [];
					this.selectedProduct = null;
					this.errorMessage = error && error.message ? error.message : 'Unable to load products. Please try again.';
				})
				.finally(() => {
					this.loading = false;
				});
		},
		openCreateProductModal() {
			if (!this.productUrl) {
				return;
			}

			this.isCreateProduct = true;
			this.isShowModalLoader = true;

			this.$nextTick(() => {
				setTimeout(() => {
					const container = this.$refs.createProductContainer;
					if (!container) {
						this.isShowModalLoader = false;
						return;
					}

					container.innerHTML = '';

					const iframe = document.createElement('iframe');
					iframe.setAttribute('src', this.productUrl);
					iframe.setAttribute('width', '100%');
					iframe.setAttribute('height', '100%');
					iframe.setAttribute('frameborder', '0');
					iframe.addEventListener('load', () => {
						this.isShowModalLoader = false;
					});

					container.appendChild(iframe);
				}, 100);
			});
		},
		closeCreateProductModal() {
			this.isCreateProduct = false;
			this.isShowModalLoader = false;
			const container = this.$refs.createProductContainer;
			if (container) {
				container.innerHTML = '';
			}
		},
		goBack() {
			this.$emit('prev-step');
		},
		handleContinue() {
			if (!this.selectedProduct) {
				this.errorMessage = 'Please select a product to continue.';
				return;
			}

			if (this.templateLoading) {
				this.templateError = 'Please wait while we pick the best template for your goal.';
				return;
			}

			if (!this.selectedTemplate) {
				this.templateError = 'Unable to determine a funnel template for this goal. Please try again.';
				return;
			}

			this.errorMessage = '';
			this.templateError = '';

			const selectedProductData = this.products.find(
				product => product.id === this.selectedProduct
			) || null;

			this.$emit('next-step', {
				productId: this.selectedProduct,
				product: selectedProductData,
				template: this.selectedTemplate,
			});
		},
		showCreateProductModal: function () {
			this.isCreateProduct = true;
			this.isShowModalLoader = true;

			setTimeout(() => {
				var container = document.getElementById('wpfnl-create-product');

				// Create an iframe element
				var iframe = document.createElement('iframe');

				// Set the attributes of the iframe
				iframe.setAttribute('src', this.productUrl);
				iframe.setAttribute('width', '100%');
				iframe.setAttribute('height', '100%');
				iframe.setAttribute('frameborder', '0');

				iframe.addEventListener('load', () => {
					this.isShowModalLoader = false;
				});

				container.appendChild(iframe);
				// Append the iframe to the container div
			}, 100);
		},
		removeCreateProductModal: function () {
			this.isCreateProduct = false
			j('#wpfnl-create-product').empty()
		},
	}
}
</script>
