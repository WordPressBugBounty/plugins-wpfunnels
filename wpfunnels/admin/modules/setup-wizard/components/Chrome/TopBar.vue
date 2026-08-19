<template>
	<header class="wpfnl-wz-top">
		<div class="wpfnl-wz-logo">
			<img :src="logoUrl" alt="" class="wpfnl-wz-logo-mark" />
			<span class="wpfnl-wz-logo-text">WPFunnels</span>
		</div>

		<p class="wpfnl-wz-step-label">Step {{ currentStep }} of {{ totalSteps }}</p>

		<!-- The slot is the flex spacer that balances the logo column and keeps the
		     step label centered; the button inside it is sized to its own content
		     so it sits flush right instead of stretching across the column. -->
		<div class="wpfnl-wz-exit-slot">
			<button
				type="button"
				class="wpfnl-wz-exit"
				@click="$emit('exit')"
			>
				<span class="wpfnl-wz-exit-text">{{ exitLabel }}</span>
				<svg width="10" height="10" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<path d="M1 1L13 13M13 1L1 13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
				</svg>
			</button>
		</div>
	</header>
</template>

<script>
export default {
	name: 'TopBar',
	props: {
		currentStep: {
			type: Number,
			default: 1,
		},
		totalSteps: {
			type: Number,
			default: 5,
		},
	},
	emits: [ 'exit' ],
	data() {
		return {
			logoUrl: window?.setup_wizard_obj?.logo_url || '',
		};
	},
	computed: {
		exitLabel() {
			return 'Exit Guided Setup';
		},
	},
};
</script>
