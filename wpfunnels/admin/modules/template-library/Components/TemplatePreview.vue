<template>
    <div class="wpfnl-template-preview-wrapper">
        <div class="wpfnl-template-steps-wrapper">
            <div v-for="step in displaySteps" class="wpfnl-template-step" :class="activeStep.ID === step.ID ? 'active' : ''" @click="setActiveStep(step)">
                <span>
                    <figure>
                        <img :src="step.featured_image" :alt="step.title">
                    </figure>
                </span>

                <p>{{ step.step_type }}</p>
            </div>
        </div>

        <div class="wpfnl-template-step-preview" :class="view">
            <div class="speaker-mike">
                <span class="large-speaker"></span>
                <span class="small-speaker"></span>
            </div>

            <div class="wpfnl-template-iframe-wrapper">
                <iframe v-if="!shouldShowImagePreview(activeStep)" :src="activeStep?.link" width="100%" height="600px" @load="iframeLoaded"></iframe>
                <div v-if="!shouldShowImagePreview(activeStep)" class="wpfnl-loader-wrapper" :class="isLoading ? 'active' : ''">
                    <span class="wpfnl-loader"></span>
                </div>

                <div v-if="shouldShowImagePreview(activeStep)" class="wpfnl-preview-checkout-placeholder">
                    <img :src="getStepImageForView(activeStep)" :alt="activeStep?.title">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TemplatePreview',
    props: {
        template:{
            type: Object,
            default: {
                steps: [],
            }
        },
        view: {
            type: String,
            default: 'desktop'
        }
    },
    data() {
        return {
            activeStep: {},
            isLoading: true,
            isStoreCheckout: window.template_library_object.isStoreCheckout || false,
        }
    },
    computed: {
        displaySteps() {
            if (!this.template || !this.template.steps) return [];
            
            // Filter for Store Checkout - only show one checkout and one thankyou
            if (this.isStoreCheckout) {
                let filtered = [];
                let hasCheckout = false;
                let hasThankyou = false;
                
                this.template.steps.forEach(step => {
                    if (step.step_type === 'checkout' && !hasCheckout) {
                        filtered.push(step);
                        hasCheckout = true;
                    } else if (step.step_type === 'thankyou' && !hasThankyou) {
                        filtered.push(step);
                        hasThankyou = true;
                    }
                });
                
                return filtered;
            }
            
            return this.template.steps;
        }
    },
    mounted() {
      if (this.displaySteps && this.displaySteps.length > 0) {
        this.activeStep = this.displaySteps[0];
      } else {
        this.activeStep = {};
      }
    },
    methods: {
        getStepImageForView(step) {
            if (!step) {
                return '';
            }

            if (this.view === 'desktop') {
                return step.desktop_view_image || '';
            }

            if (this.view === 'tablet') {
                return step.tablet_view_image || '';
            }

            if (this.view === 'mobile') {
                return step.mobile_view_image || '';
            }

            return '';
        },
        shouldShowImagePreview(step) {
            const stepType = step?.step_type;
            if (stepType !== 'checkout' && stepType !== 'thankyou') {
                return false;
            }

            return !!this.getStepImageForView(step);
        },
        setActiveStep(step) {
            this.activeStep = step;
            this.isLoading = true; // Show loader when a new step is selected
        },
        iframeLoaded() {
            this.isLoading = false; // Hide loader when iframe has loaded
        },
    }
}
</script>