<template>
	<div class="wpfnl-mm-setup-wizard-exit">
        <a href="#" class="wpfnl-mm-setup-wizard-exit-link" @click.prevent="showExitModal">
            Skip for now
        </a>
	</div>
</template>

<script>
export default {
    name: 'Help',
    methods: {
		showExitModal() {
			this.$emit('show-exit-modal')
		}
    }
}
</script>

<style scoped>
/* Exit Modal */
.wpfnl-exit-modal-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 10000;
	animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
	from {
		opacity: 0;
	}
	to {
		opacity: 1;
	}
}

.wpfnl-exit-modal {
	background: #FFFFFF;
	border-radius: 16px;
	padding: 32px;
	max-width: 440px;
	width: 90%;
	box-shadow: 0px 20px 40px rgba(0, 0, 0, 0.15);
	animation: slideUp 0.3s ease;
}

@keyframes slideUp {
	from {
		transform: translateY(20px);
		opacity: 0;
	}
	to {
		transform: translateY(0);
		opacity: 1;
	}
}

.wpfnl-exit-modal-header {
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
	margin-bottom: 24px;
}

.wpfnl-exit-modal-header svg {
	margin-bottom: 16px;
}

.wpfnl-exit-modal-title {
	font-size: 20px;
	font-weight: 700;
	font-family: 'DM Sans', sans-serif;
	color: #363B4E;
	margin: 0 0 12px 0;
	letter-spacing: -0.5px;
}

.wpfnl-exit-modal-description {
	font-size: 14px;
	font-weight: 400;
	font-family: 'DM Sans', sans-serif;
	color: #6E7A85;
	line-height: 1.6;
	margin: 0;
}

.wpfnl-exit-modal-actions {
	display: flex;
	gap: 12px;
	justify-content: center;
}

.wpfnl-exit-modal-btn {
	padding: 12px 24px;
	border-radius: 8px;
	font-size: 14px;
	font-weight: 500;
	cursor: pointer;
	transition: all 0.3s ease;
	border: none;
	min-width: 140px;
}

.wpfnl-exit-modal-btn-secondary {
	background: #F6F5FA;
	color: #363B4E;
	border: 1px solid #ECEBF0;
}

.wpfnl-exit-modal-btn-secondary:hover {
	background: #ECEBF0;
	border-color: #6E42D3;
}

.wpfnl-exit-modal-btn-danger {
	background: #EF4444;
	color: #FFFFFF;
}

.wpfnl-exit-modal-btn-danger:hover {
	background: #DC2626;
}
</style>