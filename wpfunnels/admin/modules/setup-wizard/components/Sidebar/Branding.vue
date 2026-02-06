<template>
	<div class="wpfnl-mm-setup-wizard-branding">
        <img :src="logoUrl" alt="WPFunnels Logo" class="wpfnl-mm-setup-wizard-logo" />
        <h1 class="wpfnl-mm-setup-wizard-brand-name">WPFunnels</h1>
	</div>
</template>

<script>
export default {
    name: 'Branding',
    data() {
        return {
            logoUrl: window.setup_wizard_obj.logo_url,
        }
    },
}
</script>