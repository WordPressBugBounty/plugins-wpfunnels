<template>
	<div class="wpfnl-mm-setup-wizard-steps">
        <div
            v-for="(step, index) in steps"
            :key="index"
            class="wpfnl-mm-setup-wizard-step"
            :class="{
                'active': isActive(index),
                'completed': isCompleted(index)
            }"
        >
            <div class="wpfnl-mm-setup-wizard-step-number">
                <svg v-if="isCompleted(index)" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="8" cy="8" r="8" fill="#239654"/>
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
        },
        selectedGoal: {
            type: String,
            default: ''
        },
        storeCheckoutPhase: {
            type: String,
            default: 'select'
        }
    },
    computed: {
        steps() {
            return ['Welcome', 'Required Installation', 'Store Checkout'];
        }
    },
    methods: {
        isCompleted(index) {
            // Standard: earlier steps are completed when currentStep has moved past them
            if (this.currentStep > index + 1) return true;
            // Special case: step 3 (index 2) is completed when StoreCheckout phase is 'complete'
            if (index === 2 && this.currentStep === 3 && this.storeCheckoutPhase === 'complete') return true;
            return false;
        },
        isActive(index) {
            // Step 3 is no longer "active" once it's complete — it's "completed"
            if (index === 2 && this.storeCheckoutPhase === 'complete') return false;
            return this.currentStep === index + 1;
        }
    }
}
</script>
