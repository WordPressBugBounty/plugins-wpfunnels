<template>
	<div class="wpfnl-mm-setup-wizard-sidebar">
        <Branding />
        <Steps :currentStep="currentStep" :selectedGoal="selectedGoal" :storeCheckoutPhase="storeCheckoutPhase" />
        <Help v-if="showSkipOption" @show-exit-modal="$emit('show-exit-modal')" />
	</div>
</template>

<script>
import Branding from './Branding.vue';
import Help from './Help.vue';
import Steps from './Steps.vue';

export default {
    name: 'Sidebar',
    components: {
        Branding,
        Help,
        Steps,
    },
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
        isLastStep() {
            return this.currentStep === this.getMaxSteps();
        },
        showSkipOption() {
            // Hide on step 2 (Required Installation) — no skipping plugin setup
            if (this.currentStep === 2) return false;
            // Hide on step 3 complete phase — setup is done, nothing to skip
            if (this.currentStep === 3 && this.storeCheckoutPhase === 'complete') return false;
            return true;
        }
    },
    methods: {
        getMaxSteps() {
            // Dynamic step calculation based on goal
            if (this.selectedGoal === 'order-value') {
                return 6; // Welcome, Setup, ChooseGoal, ChooseTemplate, BuildFunnel, Complete
            }
            if (this.selectedGoal === 'improve-checkout') {
                return 3; // Welcome, Setup, StoreCheckout
            }
            if (this.selectedGoal === 'sales') {
                return 7; // Welcome, Setup, ChooseGoal, ProductSync, ChooseTemplate, GenerateFunnel, Complete
            }
            // Default before goal selection
            return 5; // Welcome, Setup, ChooseGoal, ChooseTemplate, Complete (initial display)
        }
    }
}
</script>