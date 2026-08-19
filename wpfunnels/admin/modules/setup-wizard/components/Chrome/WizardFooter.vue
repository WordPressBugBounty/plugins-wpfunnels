<template>
	<footer class="wpfnl-wz-footer">
		<button
			type="button"
			class="wpfnl-wz-btn wpfnl-wz-btn--ghost"
			:class="{ 'is-hidden': ! showBack }"
			:disabled="! showBack || busy"
			:tabindex="showBack ? 0 : -1"
			:aria-hidden="! showBack"
			@click="$emit('back')"
		>
			Back
		</button>

		<div class="wpfnl-wz-footer-actions">
			<button
				v-if="showSkip"
				type="button"
				class="wpfnl-wz-skip"
				:disabled="busy"
				@click="$emit('skip')"
			>
				Skip
			</button>

			<button
				v-if="showNext"
				type="button"
				class="wpfnl-wz-btn wpfnl-wz-btn--primary"
				:disabled="! canProceed || busy"
				@click="$emit('next')"
			>
				<span
					v-if="busy"
					class="wpfnl-wz-spinner"
					aria-hidden="true"
				></span>
				{{ label }}
				<svg
					v-if="! busy"
					width="15"
					height="11"
					viewBox="0 0 17 12"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					aria-hidden="true"
				>
					<path d="M1 6H16M16 6L11 1M16 6L11 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</button>
			<span v-else class="wpfnl-wz-btn wpfnl-wz-btn--placeholder" aria-hidden="true"></span>
		</div>
	</footer>
</template>

<script>
export default {
	name: 'WizardFooter',
	props: {
		label: {
			type: String,
			default: 'Next',
		},
		canProceed: {
			type: Boolean,
			default: true,
		},
		busy: {
			type: Boolean,
			default: false,
		},
		showBack: {
			type: Boolean,
			default: true,
		},
		showNext: {
			type: Boolean,
			default: true,
		},
		showSkip: {
			type: Boolean,
			default: false,
		},
	},
	emits: [ 'back', 'next', 'skip' ],
};
</script>
