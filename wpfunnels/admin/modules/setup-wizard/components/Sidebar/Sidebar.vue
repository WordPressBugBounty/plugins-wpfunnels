<template>
	<div class="wpfnl-mm-setup-wizard-sidebar">
        <Branding />
        <Steps :currentStep="currentStep" :selectedGoal="selectedGoal" />
        <Help v-if="!isLastStep" @show-exit-modal="$emit('show-exit-modal')" />
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
        }
    },
    computed: {
        isLastStep() {
            return this.currentStep === this.getMaxSteps();
        }
    },
    methods: {
        getMaxSteps() {
            // Dynamic step calculation based on goal
            if (this.selectedGoal === 'order-value') {
                return 6; // Welcome, Setup, ChooseGoal, ChooseTemplate, BuildFunnel, Complete
            }
            if (this.selectedGoal === 'improve-checkout') {
                return 6; // Welcome, Setup, ChooseGoal, ChooseTemplate, GenerateFunnel, Complete
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