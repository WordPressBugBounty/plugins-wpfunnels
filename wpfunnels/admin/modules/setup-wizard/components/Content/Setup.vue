<template>
	<div class="wpfnl-required-installation">

		<!-- Header — matches .wpfnl-mm-setup-header pattern from other steps -->
		<div class="wpfnl-mm-setup-header">
			<h2 class="wpfnl-mm-setup-title">{{ pluginsSectionTitle }}</h2>
			<p class="wpfnl-mm-setup-subtitle">{{ pluginsSectionSubtitle }}</p>
		</div>

		<!-- Single unified white card — matches .wpfnl-mm-setup-section pattern -->
		<div class="wpfnl-ri-card">

			<!-- Builder Selection -->
			<div class="wpfnl-ri-builder-section">
				<p class="wpfnl-ri-builder-label">What page builder are you using?</p>
				<div class="wpfnl-ri-builder-options">
					<button
						v-for="builder in builders"
						:key="builder.id"
						class="wpfnl-ri-builder-btn"
						:class="{
							'active': selectedBuilder === builder.id,
							'disabled': isProBuilder(builder.id) && !isBuilderInstalled(builder.id)
						}"
						@click="selectBuilder(builder.id)"
						:disabled="isProBuilder(builder.id) && !isBuilderInstalled(builder.id)"
					>
						<img :src="builder.image" :alt="builder.name" />
						<span>{{ builder.name }}</span>
						<span class="wpfnl-ri-not-installed" v-if="isProBuilder(builder.id) && !isBuilderInstalled(builder.id)">Not Installed</span>
					</button>
				</div>
			</div>

			<!-- Divider -->
			<div class="wpfnl-ri-divider"></div>

			<!-- Plugins Section -->
			<div class="wpfnl-ri-plugins-section">
				<div class="wpfnl-ri-plugin-cards">

					<!-- WooCommerce — required -->
					<div class="wpfnl-ri-plugin-card" :class="pluginCardClass('woocommerce')">
						<div class="wpfnl-ri-plugin-card-icon">
							<img :src="wooLogo" alt="WooCommerce" />
						</div>
						<div class="wpfnl-ri-plugin-card-body">
							<div class="wpfnl-ri-plugin-card-header">
								<span class="wpfnl-ri-plugin-card-name">WooCommerce</span>
								<span class="wpfnl-ri-plugin-card-required">Required</span>
							</div>
							<p class="wpfnl-ri-plugin-card-desc">Process payments and manage orders for your funnels.</p>
						</div>
						<div class="wpfnl-ri-plugin-card-status">
							<span v-if="isPluginActive('woocommerce')" class="wpfnl-ri-status wpfnl-ri-status--active">
								<svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M8.5 2.5L4 7L1.5 4.5" stroke="#059669" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
								Active
							</span>
							<span v-else-if="isPluginInstalled('woocommerce')" class="wpfnl-ri-status wpfnl-ri-status--installed">
								<svg width="8" height="8" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4" fill="#F59E0B"/></svg>
								Installed
							</span>
							<span v-else class="wpfnl-ri-status wpfnl-ri-status--missing">
								<svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M5 1v4M5 7.5v.5" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round"/></svg>
								Not Installed
							</span>
						</div>
					</div>

					<!-- Mail Mint — optional, toggleable. Hidden when already active. -->
					<div
						v-if="!isPluginActive('mail-mint')"
						class="wpfnl-ri-plugin-card wpfnl-ri-plugin-card--optional"
						:class="mailMintCardClass"
						@click="toggleMailMint"
						role="checkbox"
						:aria-checked="mailMintSelected"
						tabindex="0"
						@keydown.space.prevent="toggleMailMint"
						@keydown.enter.prevent="toggleMailMint"
					>
						<div class="wpfnl-ri-plugin-card-icon">
							<img :src="mailMintLogo" alt="Mail Mint" />
						</div>
						<div class="wpfnl-ri-plugin-card-body">
							<div class="wpfnl-ri-plugin-card-header">
								<span class="wpfnl-ri-plugin-card-name">Mail Mint</span>
								<span class="wpfnl-ri-plugin-card-optional">Optional</span>
							</div>
							<p class="wpfnl-ri-plugin-card-desc">Email marketing and abandoned cart recovery for your store.</p>
						</div>
						<div class="wpfnl-ri-plugin-card-status">
							<span v-if="mailMintSelected && isPluginActive('mail-mint')" class="wpfnl-ri-status wpfnl-ri-status--active">
								<svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M8.5 2.5L4 7L1.5 4.5" stroke="#059669" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
								Active
							</span>
							<span v-else-if="mailMintSelected && isPluginInstalled('mail-mint')" class="wpfnl-ri-status wpfnl-ri-status--installed">
								<svg width="8" height="8" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4" fill="#F59E0B"/></svg>
								Installed
							</span>
							<span v-else-if="mailMintSelected" class="wpfnl-ri-status wpfnl-ri-status--missing">
								<svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M5 1v4M5 7.5v.5" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round"/></svg>
								Not Installed
							</span>
						</div>
						<div class="wpfnl-ri-plugin-card-toggle">
							<div class="mint-toggle" :class="{ 'mint-toggle--on': mailMintSelected }">
								<div class="mint-toggle__knob"></div>
							</div>
						</div>
					</div>

				</div>
			</div>

		</div>

		<!-- Footer Buttons -->
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
					{{ installButtonLabel }}
					<svg v-if="!isProcessing" width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M1 6H16M16 6L11 1M16 6L11 11" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			</div>
		</div>

	</div>
</template>

<script>
export default {
	name: 'Setup',
	data() {
		return {
			selectedBuilder: 'gutenberg',
			builderStatuses: {},
			pluginStatuses: {},
			isProcessing: false,
			installStatusText: '',
			mailMintSelected: true,
			builders: [
				{ id: 'gutenberg', name: 'Gutenberg', image: window?.setup_wizard_obj?.gb_builder_img || '', isBuiltIn: true },
				{ id: 'elementor', name: 'Elementor', image: window?.setup_wizard_obj?.elementor_img || '' },
				{ id: 'bricks',    name: 'Bricks',    image: window?.setup_wizard_obj?.bricks_img || '', isTheme: true },
				{ id: 'divi',      name: 'Divi',      image: window?.setup_wizard_obj?.divi_img || '', isTheme: true },
				{ id: 'oxygen',    name: 'Oxygen',    image: window?.setup_wizard_obj?.oxygen_img || '' },
				{ id: 'others',    name: 'Others',    image: window?.setup_wizard_obj?.others_builder_img || '', isBuiltIn: true },
			],
			proBuilders: ['oxygen', 'bricks', 'divi'],
			plugins: [
				{ id: 'woocommerce', name: 'WooCommerce', slug: 'woocommerce', basename: 'woocommerce/woocommerce.php', installedKey: 'is_woo_installed', activeKey: 'is_woo_active' },
				{ id: 'mail-mint',   name: 'Mail Mint',   slug: 'mail-mint',   basename: 'mail-mint/mail-mint.php',     installedKey: 'is_mrm_installed', activeKey: 'is_mrm_active' },
			],
		}
	},
	computed: {
		wooLogo() {
			return window?.setup_wizard_obj?.wc_logo || '';
		},
		mailMintLogo() {
			return window?.setup_wizard_obj?.mail_mint_logo || '';
		},

		wooStatus() {
			const s = this.pluginStatuses['woocommerce'] || {};
			if (s.active)    return 'active';
			if (s.installed) return 'installed';
			return 'missing';
		},

		pluginsSectionTitle() {
			if (this.wooStatus === 'active') {
				return 'Get essential plugins installed with one-click';
			}
			if (this.wooStatus === 'installed') {
				return 'WooCommerce is installed but not active';
			}
			return 'WooCommerce is required to use WPFunnels';
		},

		pluginsSectionSubtitle() {
			const mintAlreadyActive = this.isPluginActive('mail-mint');
			const mintPart = !mintAlreadyActive
				? ' You can also add Mail Mint for email marketing and abandoned cart recovery.'
				: '';
			if (this.wooStatus === 'active') {
				return mintAlreadyActive
					? 'WooCommerce and Mail Mint are already active. You\'re all set!'
					: 'WooCommerce is already active. Optionally install Mail Mint for email marketing and abandoned cart recovery.';
			}
			if (this.wooStatus === 'installed') {
				return 'We will activate WooCommerce to process payments.' + mintPart;
			}
			return 'WPFunnels uses WooCommerce to process payments. We will install and activate it for you.' + mintPart;
		},

		installButtonLabel() {
			if (this.isProcessing) {
				return this.installStatusText || 'Installing and Activating...';
			}
			const wooActive     = this.isPluginActive('woocommerce');
			const wooInstalled  = this.isPluginInstalled('woocommerce');
			// If Mail Mint is already active, treat it as not needing action
			const mintAlreadyActive = this.isPluginActive('mail-mint');
			const mintActive    = mintAlreadyActive || !this.mailMintSelected || this.isPluginActive('mail-mint');
			const mintInstalled = mintAlreadyActive || !this.mailMintSelected || this.isPluginInstalled('mail-mint');

			if (wooActive && mintActive) return 'Continue';

			const needsInstall = (!wooInstalled && !wooActive)
				|| (!mintAlreadyActive && this.mailMintSelected && !mintInstalled && !mintActive);

			return needsInstall ? 'Install and Activate' : 'Activate';
		},

		// Mail Mint card class lives in computed so Vue tracks mailMintSelected reactively
		mailMintCardClass() {
			if (!this.mailMintSelected)              return 'wpfnl-ri-plugin-card--unchecked';
			if (this.isPluginActive('mail-mint'))    return 'wpfnl-ri-plugin-card--active';
			if (this.isPluginInstalled('mail-mint')) return 'wpfnl-ri-plugin-card--installed';
			return '';
		},
	},
	mounted() {
		this.detectBuilder();
		this.checkPluginStatuses();
	},
	methods: {
		detectBuilder() {
			const getPlugins = window?.setup_wizard_obj?.getPlugins || [];
			const oxygenData = getPlugins.find(p => p.name === 'oxygen');
			const bricksData = getPlugins.find(p => p.name === 'bricks');
			const diviData   = getPlugins.find(p => p.name === 'divi');

			const checks = [
				{ id: 'elementor', active: window?.setup_wizard_obj?.is_elementor_active === 'yes',   installed: window?.setup_wizard_obj?.is_elementor_installed === 'yes' },
				{ id: 'bricks',    active: bricksData?.status === 'activated',  installed: bricksData?.status === 'activated' || bricksData?.status === 'installed' },
				{ id: 'divi',      active: diviData?.status === 'activated',    installed: diviData?.status === 'activated'   || diviData?.status === 'installed' },
				{ id: 'oxygen',    active: oxygenData?.status === 'activated',  installed: oxygenData?.status === 'activated' || oxygenData?.status === 'installed' },
			];

			const statuses = { gutenberg: { installed: true, active: true }, others: { installed: true, active: true } };
			checks.forEach(b => { statuses[b.id] = { installed: b.installed, active: b.active }; });
			this.builderStatuses = statuses;

			// 1. Use previously saved builder setting if it exists
			const savedBuilder = window?.setup_wizard_obj?.defaultSettings?.builder;
			if (savedBuilder && savedBuilder !== 'gutenberg') {
				// Validate the saved builder is actually installed/active before trusting it
				const savedStatus = statuses[savedBuilder];
				if (savedStatus?.installed || savedStatus?.active) {
					this.selectedBuilder = savedBuilder;
					return;
				}
			}

			// 2. If saved is gutenberg (or no valid saved setting), default to gutenberg
			this.selectedBuilder = 'gutenberg';
		},

		checkPluginStatuses() {
			const statuses = {};
			this.plugins.forEach(p => {
				statuses[p.id] = {
					installed: p.installedKey ? window?.setup_wizard_obj?.[p.installedKey] === 'yes' : false,
					active:    p.activeKey    ? window?.setup_wizard_obj?.[p.activeKey]    === 'yes' : false,
				};
			});
			this.pluginStatuses = statuses;
		},

		isProBuilder(id)    { return this.proBuilders.includes(id); },
		isBuilderInstalled(id) { return !!this.builderStatuses[id]?.installed; },
		isPluginActive(id)  { return !!this.pluginStatuses[id]?.active; },
		isPluginInstalled(id) { return !!this.pluginStatuses[id]?.installed; },

		pluginCardClass(id) {
			if (this.isPluginActive(id))    return 'wpfnl-ri-plugin-card--active';
			if (this.isPluginInstalled(id)) return 'wpfnl-ri-plugin-card--installed';
			return '';
		},

		toggleMailMint() {
			this.mailMintSelected = !this.mailMintSelected;
		},

		selectBuilder(id) {
			if (this.isProBuilder(id) && !this.isBuilderInstalled(id)) return;
			this.selectedBuilder = id;
		},

		async installPlugin(slug) {
			return new Promise(resolve => {
				wp.updates.ajax('install-plugin', { slug, success: () => resolve(true), error: () => resolve(false) });
			});
		},

		async activatePlugin(basename) {
			return new Promise(resolve => {
				const ajaxUrl = window.ajaxurl || window.location.origin + '/wp-admin/admin-ajax.php';
				const action = basename === 'mail-mint/mail-mint.php' ? 'wpfnl_activate_mail_mint' : 'wpfnl_activate_plugin';
				const data = { action };
				if (action === 'wpfnl_activate_plugin') data.plugin = basename;
				jQuery.ajax({ url: ajaxUrl, type: 'POST', data, success: r => resolve(!!r.success), error: () => resolve(false) });
			});
		},

		async installAndActivatePlugin(slug, basename, isInstalled = false) {
			try {
				if (!isInstalled) {
					this.installStatusText = 'Installing and Activating...';
					const ok = await this.installPlugin(slug);
					if (!ok) return false;
					await new Promise(r => setTimeout(r, 1500));
				}
				this.installStatusText = 'Installing and Activating...';
				return await this.activatePlugin(basename);
			} catch { return false; }
		},

		async installBuilder() {
			if (this.selectedBuilder === 'gutenberg' || this.selectedBuilder === 'others') return true;
			const map = {
				elementor: { slug: 'elementor',     basename: 'elementor/elementor.php',           installedKey: 'is_elementor_installed', activeKey: 'is_elementor_active' },
				divi:      { slug: 'divi-builder',  basename: 'divi-builder/divi-builder.php',     installedKey: null, activeKey: null },
				oxygen:    { slug: 'oxygen',        basename: 'oxygen/functions.php',              installedKey: null, activeKey: null },
				bricks:    { slug: 'bricks',        basename: 'bricks', isTheme: true },
			};
			const b = map[this.selectedBuilder];
			if (!b || b.isTheme) return true;
			const isInstalled = b.installedKey ? window?.setup_wizard_obj?.[b.installedKey] === 'yes' : false;
			const isActive    = b.activeKey    ? window?.setup_wizard_obj?.[b.activeKey]    === 'yes' : false;
			if (isInstalled && isActive) return true;
			return this.installAndActivatePlugin(b.slug, b.basename, isInstalled);
		},

		async installPlugins() {
			for (const plugin of this.plugins) {
				if (plugin.id === 'mail-mint' && (!this.mailMintSelected || this.isPluginActive('mail-mint'))) continue;
				const isInstalled = plugin.installedKey ? window?.setup_wizard_obj?.[plugin.installedKey] === 'yes' : false;
				const isActive    = plugin.activeKey    ? window?.setup_wizard_obj?.[plugin.activeKey]    === 'yes' : false;
				if (isInstalled && isActive) continue;
				await this.installAndActivatePlugin(plugin.slug, plugin.basename, isInstalled);
			}
		},

		async handleContinue() {
			this.isProcessing = true;
			this.installStatusText = '';
			try {
				await this.installBuilder();
				await this.installPlugins();
				const builder = this.selectedBuilder === 'others' ? 'gutenberg' : this.selectedBuilder;
				this.$emit('next-step', { builder });
			} catch {
				alert('An error occurred during setup. Please try again.');
			} finally {
				this.isProcessing = false;
				this.installStatusText = '';
			}
		},

		goBack() { this.$emit('prev-step'); },
	}
}
</script>

<style scoped>
/* Toggle switch — scoped to this component, no specificity conflicts */
.mint-toggle {
	position: relative;
	display: inline-block;
	width: 36px;
	height: 20px;
	border-radius: 100px;
	background: #D1D5DB;
	transition: background 0.25s ease;
	cursor: pointer;
	flex-shrink: 0;
}

.mint-toggle--on {
	background: #6E42D3;
}

.mint-toggle__knob {
	position: absolute;
	top: 2px;
	left: 2px;
	width: 16px;
	height: 16px;
	border-radius: 50%;
	background: #FFF;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
	transition: transform 0.25s ease;
	transform: translateX(0);
}

.mint-toggle--on .mint-toggle__knob {
	transform: translateX(16px);
}
</style>
