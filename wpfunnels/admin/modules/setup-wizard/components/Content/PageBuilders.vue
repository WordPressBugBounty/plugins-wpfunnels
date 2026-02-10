<template>
	<div class="wpfnl-mm-setup-section">
        <div class="wpfnl-mm-setup-section-content wpfnl-mm-page-builders">
            <h3 class="wpfnl-mm-setup-section-title">What Page Builder Would You Like To Use To Design Funnel Pages?</h3>
            <div class="wpfnl-mm-woo-status">
                <div 
                    v-for="builder in builders" 
                    :key="builder.id"
                    class="wpfnl-setup-plugins-card"
                    :class="{ 
                        'selected': selectedBuilder === builder.id,
                        'disabled': isProBuilder(builder.id) && !isBuilderInstalled(builder.id)
                    }"
                    @click="selectBuilder(builder.id)"
                >
                    <span class="wpfnl-setup-plugin-selected" v-if="selectedBuilder === builder.id">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M12 5L6.5 10L4 7.72727" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>

                    <span class="wpfnl-setup-pro-badge" v-if="isProBuilder(builder.id) && !isBuilderInstalled(builder.id)">
                        Not Installed
                    </span>

                    <img 
                        :src="builder.image"
                        :alt="builder.name + ' Logo'"
                    >
                    <span class="wpfnl-setup-plugin-name">
                        {{ builder.name }}
                    </span>
                </div>
            </div>
        </div>
            
        <!-- Info Message About Page Builder Support -->
        <div class="wpfnl-mm-page-builder-info">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M7.9987 14.6673C11.6806 14.6673 14.6654 11.6825 14.6654 8.00065C14.6654 4.31875 11.6806 1.33398 7.9987 1.33398C4.3168 1.33398 1.33203 4.31875 1.33203 8.00065C1.33203 11.6825 4.3168 14.6673 7.9987 14.6673Z" stroke="#f68524" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 10.6667V8" stroke="#f68524" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 5.33301H8.00667" stroke="#f68524" stroke-width="1.33" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <p><strong>WPFunnels works with all page builders,</strong> so don't worry if your page builder is not in the list.</p>
        </div>
    </div>
</template>

<script>
export default {
    name: 'PageBuilders',
    props: {
        selectedBuilder: {
            type: String,
            default: ''
        },
        builderStatuses: {
            type: Object,
            default: () => ({})
        }
    },
    data() {
        return {
            // Pro builders that cannot be installed from the WordPress repo
            proBuilders: ['oxygen', 'bricks'],
            builders: [
                {
                    id: 'gutenberg',
                    name: 'Gutenberg',
                    image: window?.setup_wizard_obj?.gb_builder_img || '',
                    slug: '',
                    basename: '',
                    isBuiltIn: true
                },
                {
                    id: 'elementor',
                    name: 'Elementor',
                    image: window?.setup_wizard_obj?.elementor_img || '',
                    slug: 'elementor',
                    basename: 'elementor/elementor.php'
                },
                {
                    id: 'bricks',
                    name: 'Bricks',
                    image: window?.setup_wizard_obj?.bricks_img || '',
                    slug: 'bricks',
                    basename: 'bricks',
                    isTheme: true
                },
                {
                    id: 'oxygen',
                    name: 'Oxygen',
                    image: window?.setup_wizard_obj?.oxygen_img || '',
                    slug: 'oxygen',
                    basename: 'oxygen/functions.php'
                },
                {
                    id: 'others',
                    name: 'Others',
                    image: window?.setup_wizard_obj?.others_builder_img || '',
                    slug: '',
                    basename: '',
                    isBuiltIn: true
                }
            ]
        }
    },
    methods: {
        isProBuilder(builderId) {
            return this.proBuilders.includes(builderId);
        },

        isBuilderInstalled(builderId) {
            const status = this.builderStatuses[builderId];
            return status ? status.installed : false;
        },

        selectBuilder(builderId) {
            // Prevent selecting pro builders that are not installed
            if (this.isProBuilder(builderId) && !this.isBuilderInstalled(builderId)) {
                return;
            }
            this.$emit('update:selectedBuilder', builderId);
        }
    }
}
</script>