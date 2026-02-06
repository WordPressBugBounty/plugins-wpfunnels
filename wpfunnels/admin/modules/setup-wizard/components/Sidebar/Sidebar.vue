<template>
	<div class="wpfnl-mm-setup-wizard-sidebar">
		<Branding />
        <Steps :currentStep="currentStep" />
        <Help v-if="currentStep !== 5" @show-exit-modal="$emit('show-exit-modal')" />
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
        }
    },
}
</script>