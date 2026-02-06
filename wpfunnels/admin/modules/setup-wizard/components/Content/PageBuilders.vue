<template>
	<div class="wpfnl-mm-setup-section">
        <div class="wpfnl-mm-setup-section-content wpfnl-mm-page-builders">
            <h3 class="wpfnl-mm-setup-section-title">What Page Builder Would You Like To Use To Design Funnel Pages?</h3>
            <div class="wpfnl-mm-woo-status">
                <div 
                    v-for="builder in builders" 
                    :key="builder.id"
                    class="wpfnl-setup-plugins-card"
                    :class="{ 'selected': selectedBuilder === builder.id }"
                    @click="selectBuilder(builder.id)"
                >
                    <span class="wpfnl-setup-plugin-selected" v-if="selectedBuilder === builder.id">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M12 5L6.5 10L4 7.72727" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
            
            <!-- Info Message About Page Builder Support -->
            <div class="wpfnl-mm-page-builder-info">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="10" cy="10" r="8" stroke="#3C434B" stroke-width="1.5"/>
                    <path d="M10 6V10M10 14H10.01" stroke="#3C434B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p><strong>WPFunnels works with all page builders,</strong> so don't worry if your page builder is not in the list.</p>
            </div>
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
        selectBuilder(builderId) {
            this.$emit('update:selectedBuilder', builderId);
        }
    }
}
</script>

<style scoped>
.wpfnl-mm-page-builder-info {
    display: flex;
    align-items: center;
    gap: 6px;
}

.wpfnl-mm-page-builder-info svg {
    flex-shrink: 0;
}

.wpfnl-mm-page-builder-info p {
    margin: 0;
    font-size: 12px;
    line-height: 1.5;
}

.wpfnl-mm-page-builder-info strong {
    font-weight: 600;
}
</style>