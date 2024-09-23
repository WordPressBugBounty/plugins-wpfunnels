<template>
    <div class="wpfnl-template-preview-wrapper">
        <div class="wpfnl-template-steps-wrapper">
            <div v-for="step in this.template.steps" class="wpfnl-template-step" :class="activeStep.ID === step.ID ? 'active' : ''" @click="setActiveStep(step)">
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
                <iframe :src="activeStep?.link" width="100%" height="600px" @load="iframeLoaded"></iframe>
                <div class="wpfnl-loader-wrapper" :class="isLoading ? 'active' : ''">
                    <span class="wpfnl-loader"></span>
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
        }
    },
    mounted() {
      this.activeStep = this.template.steps[0];  
    },
    methods: {
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