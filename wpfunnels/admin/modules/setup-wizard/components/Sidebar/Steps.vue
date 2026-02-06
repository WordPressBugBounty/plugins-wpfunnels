<template>
	<div class="wpfnl-mm-setup-wizard-steps">
        <div 
            v-for="(step, index) in steps" 
            :key="index"
            class="wpfnl-mm-setup-wizard-step"
            :class="{
                'active': currentStep === index + 1,
                'completed': currentStep > index + 1
            }"
        >
            <div class="wpfnl-mm-setup-wizard-step-number">
                <svg v-if="currentStep > index + 1" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="8" cy="8" r="8" fill="#2FCF5C"/>
                    <path d="M11.3333 5.33334L6.66667 10L4.66667 8" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span v-else>{{ index + 1 }}</span>
            </div>
            <span class="wpfnl-mm-setup-wizard-step-label">{{ step }}</span>
        </div>
	</div>
</template>

<script>
export default {
    name: 'Steps',
    props: {
        currentStep: {
            type: Number,
            default: 1
        }
    },
    data() {
        return {
            steps: [
                'Welcome',
                'Setup',
                'Choose Template',
                'Build Funnel',
                'Complete'
            ]
        }
    },
}
</script>