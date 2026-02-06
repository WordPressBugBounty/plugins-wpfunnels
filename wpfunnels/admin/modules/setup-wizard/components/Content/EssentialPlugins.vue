<template>
	<div class="wpfnl-mm-setup-section">
        <div class="wpfnl-mm-setup-section-content wpfnl-mm-essential-plugins">
            <h3 class="wpfnl-mm-setup-section-title">Recommended Integrations</h3>
            <div class="wpfnl-mm-woo-status">
                <div 
                    v-for="plugin in visiblePlugins" 
                    :key="plugin.id"
                    class="wpfnl-setup-plugins-card"
                    :class="{ 'selected': plugin.selected }"
                    @click="togglePluginSelection(plugin.id)"
                >
                    <span class="wpfnl-setup-plugin-selected" v-if="plugin.selected">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M12 5L6.5 10L4 7.72727" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>

                    <img 
                        :src="plugin.image"
                        :alt="plugin.name + ' Logo'"
                    >
                    <div class="wpfnl-setup-plugin-info">
                        <span class="wpfnl-setup-plugin-name">
                            {{ plugin.name }}
                        </span>
                        <span class="wpfnl-setup-plugin-description">
                            {{ plugin.description }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'EssentialPlugins',
    props: {
        selectedGoal: {
            type: String,
            default: 'sales'
        },
        pluginStatuses: {
            type: Object,
            default: () => ({})
        },
        selectedPlugins: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            plugins: []
        }
    },
    computed: {
        visiblePlugins() {
            return this.plugins.filter(plugin => {
                if (plugin.showForGoals.includes('all')) {
                    return true;
                }
                return plugin.showForGoals.includes(this.selectedGoal);
            });
        }
    },
    watch: {
        pluginStatuses: {
            handler(newStatuses) {
                this.initializePlugins();
            },
            immediate: true,
            deep: true
        },
        selectedGoal() {
            this.initializePlugins();
        }
    },
    methods: {
        initializePlugins() {
            const mailMintDescription = this.selectedGoal === 'sales' 
                ? 'Email Marketing and Abandoned Cart Recovery.'
                : 'Email Marketing and Automation.';

            this.plugins = [
                {
                    id: 'mail-mint',
                    name: 'Mail Mint',
                    description: mailMintDescription,
                    image: window?.setup_wizard_obj?.mail_mint_logo || '',
                    slug: 'mail-mint',
                    basename: 'mail-mint/mail-mint.php',
                    showForGoals: ['all'],
                    selected: this.isPluginSelected('mail-mint')
                },
                {
                    id: 'woocommerce-payments',
                    name: 'WooCommerce Payments',
                    description: 'Accept payments directly on your site with WooCommerce Payments.',
                    image: window?.setup_wizard_obj?.wc_logo || '',
                    slug: 'woocommerce-payments',
                    basename: 'woocommerce-payments/woocommerce-payments.php',
                    showForGoals: ['sales'],
                    selected: this.isPluginSelected('woocommerce-payments')
                },
                {
                    id: 'stripe',
                    name: 'Stripe For WooCommerce',
                    description: 'Accept credit cards, Apple Pay, and Google Pay on your store.',
                    image: window?.setup_wizard_obj?.wc_logo || '',
                    slug: 'woo-stripe-payment',
                    basename: 'woo-stripe-payment/stripe-payments.php',
                    showForGoals: ['sales'],
                    selected: this.isPluginSelected('stripe')
                }
            ];
        },
        isPluginSelected(pluginId) {
            // Check if plugin is already installed and active
            const status = this.pluginStatuses[pluginId];
            if (status && status.installed && status.active) {
                return true;
            }
            
            // Mail Mint should be selected by default
            if (pluginId === 'mail-mint') {
                return true;
            }
            
            // Check if it's in the selectedPlugins array
            return this.selectedPlugins.includes(pluginId);
        },
        togglePluginSelection(pluginId) {
            const plugin = this.plugins.find(p => p.id === pluginId);
            if (plugin) {
                plugin.selected = !plugin.selected;
                this.$emit('update:selectedPlugins', this.plugins.filter(p => p.selected).map(p => p.id));
            }
        }
    },
    mounted() {
        this.initializePlugins();
    }
}
</script>