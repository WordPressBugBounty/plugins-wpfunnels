<template>
	<div class="wpfnl-mm-setup">
		<!-- Header -->
		<div class="wpfnl-mm-setup-header">
			<h2 class="wpfnl-mm-setup-title">Let’s Make Sure Your Store Is Ready</h2>
			<p class="wpfnl-mm-setup-subtitle">
				We're checking your {{ selectedGoal === 'sales' ? 'WooCommerce setup,' : '' }} page builder, and essential plugins.
			</p>
		</div>

		<WooCommerce 
			v-if="selectedGoal === 'sales'" 
			:isInstalled="wooCommerce.installed"
			:isActive="wooCommerce.active"
		/>
		<PageBuilders 
			:selectedBuilder="selectedBuilder"
			@update:selectedBuilder="updateSelectedBuilder"
		/>
		<EssentialPlugins 
			:selectedGoal="selectedGoal"
			:pluginStatuses="pluginStatuses"
			:selectedPlugins="selectedPlugins"
			@update:selectedPlugins="updateSelectedPlugins"
		/>

		<!-- Buttons -->
		<div class="wpfnl-mm-choose-goal-buttons wpfnl-mm-buttons-container">
			<button class="wpfnl-mm-btn wpfnl-mm-btn-secondary" @click="goBack" :disabled="isProcessing">
				<svg width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M16 6H1M1 6L6 1M1 6L6 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				Back
			</button>
			<div class="wpfnl-mm-btn-group">
				<button
					class="wpfnl-mm-btn wpfnl-mm-btn-primary"
					@click="handleContinue"
					:disabled="isProcessing"
				>
					<span v-if="!isProcessing">Continue</span>
					<span v-else>Processing...</span>
					<svg v-if="!isProcessing" width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M1 6H16M16 6L11 1M16 6L11 11" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<svg v-else class="wpfnl-spinner" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<circle cx="8" cy="8" r="6" stroke="white" stroke-width="2" stroke-dasharray="30 10" stroke-linecap="round"/>
					</svg>
				</button>
			</div>
		</div>

	</div>
</template>

<script>
import WooCommerce from './WooCommerce.vue';
import PageBuilders from './PageBuilders.vue';
import EssentialPlugins from './EssentialPlugins.vue';

export default {
	name: 'Setup',
	props: {
		selectedGoal: {
			type: String,
			default: 'sales'
		}
	},
	components: {
		WooCommerce,
		PageBuilders,
		EssentialPlugins,
	},
	data() {
		return {
			// WooCommerce states
			wooCommerce: {
				installed: false,
				active: false
			},

			// Builder states
			selectedBuilder: 'gutenberg',
			
			// Plugin states
			pluginStatuses: {},
			selectedPlugins: [],

			// Processing state
			isProcessing: false,
		}
	},
	mounted() {
		this.checkPluginStatuses();
	},
	watch: {
		selectedGoal() {
			this.checkPluginStatuses();
		}
	},
	methods: {
		async checkPluginStatuses() {
			// Check WooCommerce status if sales goal is selected
			if (this.selectedGoal === 'sales') {
				const isWooInstalled = window?.setup_wizard_obj?.is_woo_installed === 'yes';
				const isWooActive = window?.setup_wizard_obj?.is_woo_active === 'yes';
				
				this.wooCommerce.installed = isWooInstalled;
				this.wooCommerce.active = isWooActive;
			}

			// Check page builder statuses and auto-select
			this.detectAndSelectBuilder();

			// Check essential plugin statuses
			this.checkEssentialPlugins();
		},

		detectAndSelectBuilder() {
			const builderChecks = [
				{ id: 'elementor', installed: window?.setup_wizard_obj?.is_elementor_installed === 'yes', active: window?.setup_wizard_obj?.is_elementor_active === 'yes' },
				{ id: 'divi', installed: false, active: false }, // Add checks if available
				{ id: 'bricks', installed: false, active: false }, // Add checks if available
				{ id: 'oxygen', installed: false, active: false } // Add checks if available
			];

			// Find first installed and active builder
			const activeBuilder = builderChecks.find(b => b.installed && b.active);
			
			if (activeBuilder) {
				this.selectedBuilder = activeBuilder.id;
			} else {
				// Default to Gutenberg
				this.selectedBuilder = 'gutenberg';
			}
		},

		checkEssentialPlugins() {
			const statuses = {};
			
			// Check Mail Mint
			statuses['mail-mint'] = {
				installed: window?.setup_wizard_obj?.is_mrm_installed === 'yes',
				active: window?.setup_wizard_obj?.is_mrm_active === 'yes'
			};

			// Initialize selected plugins based on installation status
			this.selectedPlugins = ['mail-mint']; // Mail Mint always selected by default

			if (this.selectedGoal === 'sales') {
				// For sales goal, add payment plugins if installed
				// These would need to be checked from the server
				// For now, we'll add them to the list
			}

			this.pluginStatuses = statuses;
		},

		updateSelectedBuilder(builderId) {
			this.selectedBuilder = builderId;
		},

		updateSelectedPlugins(plugins) {
			this.selectedPlugins = plugins;
		},

		async installPlugin(slug) {
			return new Promise((resolve, reject) => {
				// Use WordPress native plugin installer
				wp.updates.ajax('install-plugin', {
					slug: slug,
					success: () => {
						resolve(true);
					},
					error: (error) => {
						console.error(`Error installing plugin ${slug}:`, error);
						resolve(false);
					}
				});
			});
		},

		async activatePlugin(basename) {
			return new Promise((resolve, reject) => {
				// Use WordPress AJAX to activate plugin
				const ajaxUrl = window.ajaxurl || window.location.origin + '/wp-admin/admin-ajax.php';
				
				// Use special handler for Mail Mint to ensure database initialization
				const action = basename === 'mail-mint/mail-mint.php' 
					? 'wpfnl_activate_mail_mint' 
					: 'wpfnl_activate_plugin';
				
				const data = {
					action: action
				};
				
				// Add plugin parameter for generic activation
				if (action === 'wpfnl_activate_plugin') {
					data.plugin = basename;
				}
				
				jQuery.ajax({
					url: ajaxUrl,
					type: 'POST',
					data: data,
					success: (response) => {

						if (response.success) {
							resolve(true);
						} else {
							console.error(`Plugin activation failed:`, response.data);
							resolve(false);
						}
					},
					error: (xhr, status, error) => {
						console.error(`AJAX Error activating plugin ${basename}:`, {
							status: xhr.status,
							statusText: xhr.statusText,
							responseText: xhr.responseText,
							error: error
						});
						// Try to continue even if activation fails (might already be active)
						resolve(false);
					}
				});
			});
		},

		async installAndActivatePlugin(slug, basename, isInstalled = false) {
			try {
				// If not installed, install it first
				if (!isInstalled) {
					const installed = await this.installPlugin(slug);
					
					if (!installed) {
						console.error(`Failed to install plugin: ${slug}`);
						return false;
					}

					// Small delay to ensure plugin files are ready
					await new Promise(resolve => setTimeout(resolve, 1500));
				}

				// Activate the plugin
				const activated = await this.activatePlugin(basename);
				
				return activated;
			} catch (error) {
				console.error('Error installing and activating plugin:', error);
				return false;
			}
		},

		async installWooCommerce() {
			if (this.selectedGoal !== 'sales') return true;

			// If WooCommerce is already installed and active, skip
			if (this.wooCommerce.installed && this.wooCommerce.active) {
				return true;
			}

			try {
				const success = await this.installAndActivatePlugin(
					'woocommerce', 
					'woocommerce/woocommerce.php',
					this.wooCommerce.installed
				);

				if (success) {
					this.wooCommerce.installed = true;
					this.wooCommerce.active = true;
				}

				return success;
			} catch (error) {
				console.error('Error installing WooCommerce:', error);
				return false;
			}
		},

		async installBuilder() {
			// If Gutenberg or Others selected, no installation needed
			if (this.selectedBuilder === 'gutenberg' || this.selectedBuilder === 'others') {
				return true;
			}

			const builderMap = {
				'elementor': { slug: 'elementor', basename: 'elementor/elementor.php', installedKey: 'is_elementor_installed', activeKey: 'is_elementor_active' },
				'divi': { slug: 'divi-builder', basename: 'divi-builder/divi-builder.php', installedKey: null, activeKey: null },
				'oxygen': { slug: 'oxygen', basename: 'oxygen/functions.php', installedKey: null, activeKey: null },
				'bricks': { slug: 'bricks', basename: 'bricks', isTheme: true, installedKey: null, activeKey: null }
			};

			const builder = builderMap[this.selectedBuilder];
			if (!builder) return true;

			// Check if already installed and active
			const isInstalled = builder.installedKey ? window?.setup_wizard_obj?.[builder.installedKey] === 'yes' : false;
			const isActive = builder.activeKey ? window?.setup_wizard_obj?.[builder.activeKey] === 'yes' : false;

			if (isInstalled && isActive) {
				return true;
			}

			try {
				if (builder.isTheme) {
					// For themes, we'd need a different approach
					return true;
				}

				const success = await this.installAndActivatePlugin(builder.slug, builder.basename, isInstalled);
				return success;
			} catch (error) {
				console.error('Error installing builder:', error);
				return false;
			}
		},

		async installSelectedPlugins() {
			const pluginMap = {
				'mail-mint': { 
					slug: 'mail-mint', 
					basename: 'mail-mint/mail-mint.php',
					installedKey: 'is_mrm_installed',
					activeKey: 'is_mrm_active'
				},
				'woocommerce-payments': { 
					slug: 'woocommerce-payments', 
					basename: 'woocommerce-payments/woocommerce-payments.php',
					installedKey: null,
					activeKey: null
				},
				'stripe': { 
					slug: 'woo-stripe-payment', 
					basename: 'woo-stripe-payment/stripe-payments.php',
					installedKey: null,
					activeKey: null
				}
			};

			for (const pluginId of this.selectedPlugins) {
				const plugin = pluginMap[pluginId];
				if (!plugin) continue;

				// Check if already installed and active
				const isInstalled = plugin.installedKey ? window?.setup_wizard_obj?.[plugin.installedKey] === 'yes' : false;
				const isActive = plugin.activeKey ? window?.setup_wizard_obj?.[plugin.activeKey] === 'yes' : false;

				if (isInstalled && isActive) {
					continue;
				}

				try {
					await this.installAndActivatePlugin(plugin.slug, plugin.basename, isInstalled);
				} catch (error) {
					console.error(`Error installing ${pluginId}:`, error);
				}
			}

			return true;
		},

		async handleContinue() {
			this.isProcessing = true;

			try {
				// Step 1: Install WooCommerce if needed (for sales goal)
				if (this.selectedGoal === 'sales') {
					await this.installWooCommerce();
				}

				// Step 2: Install selected page builder if needed
				await this.installBuilder();

				// Step 3: Install selected essential plugins
				await this.installSelectedPlugins();

				// Map "others" to "gutenberg" for template compatibility
				const builderForTemplates = this.selectedBuilder === 'others' ? 'gutenberg' : this.selectedBuilder;

				// Proceed to next step
				this.$emit('next-step', {
					builder: builderForTemplates,
					wooInstalled: this.wooCommerce.installed,
					wooActivated: this.wooCommerce.active
				});
			} catch (error) {
				console.error('Error during setup:', error);
				alert('An error occurred during setup. Please try again.');
			} finally {
				this.isProcessing = false;
			}
		},

		goBack() {
			this.$emit('prev-step');
		}
	}
}
</script>
