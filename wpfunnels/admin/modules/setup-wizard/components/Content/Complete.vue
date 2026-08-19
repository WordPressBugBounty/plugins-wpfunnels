<template>
	<div class="wpfnl-wz-success" role="main">
		<!-- Sits behind the content; purely decorative -->
		<canvas ref="confetti" class="wpfnl-wz-success__confetti" aria-hidden="true"></canvas>

		<div class="wpfnl-wz-success__body">
			<div class="wpfnl-wz-success__emoji" aria-hidden="true">🎉</div>

			<h1 class="wpfnl-wz-success__heading">Congratulations, You Did it!</h1>
			<p class="wpfnl-wz-success__sub">
				WPFunnels is set up on your website! You're now ready to build
				high-converting sales funnels.
			</p>

			<div class="wpfnl-wz-success__actions">
				<button
					type="button"
					class="wpfnl-wz-btn wpfnl-wz-btn--lg wpfnl-wz-btn--pill"
					:class="hasFunnel ? 'wpfnl-wz-btn--ghost' : 'wpfnl-wz-btn--primary'"
					@click="goToDashboard"
				>
					Explore dashboard
				</button>

				<button
					v-if="hasFunnel"
					type="button"
					class="wpfnl-wz-btn wpfnl-wz-btn--primary wpfnl-wz-btn--lg wpfnl-wz-btn--pill"
					@click="viewFunnel"
				>
					Customize your checkout
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
						<path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
					</svg>
				</button>
			</div>

			<div class="wpfnl-wz-success__resources">
				<p class="wpfnl-wz-success__resources-title">Want to get more out of WPFunnels?</p>

				<div class="wpfnl-wz-success__resources-grid">
					<a
						v-for="item in resources"
						:key="item.label"
						:href="item.url"
						target="_blank"
						rel="noreferrer noopener"
						class="wpfnl-wz-success__resource-card"
					>
						<span class="wpfnl-wz-success__resource-icon" aria-hidden="true" v-html="item.icon"></span>
						<span class="wpfnl-wz-success__resource-label">{{ item.label }}</span>
					</a>
				</div>

				<div class="wpfnl-wz-success__resources-footer">
					<p>These are always available from your dashboard.</p>
					<p>
						Want more power?
						<a
							href="https://getwpfunnels.com/pricing/"
							target="_blank"
							rel="noreferrer noopener"
							class="wpfnl-wz-success__pro-link"
						>See what Pro unlocks</a>
					</p>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import apiFetch from '@wordpress/api-fetch';

/**
 * Canvas confetti for the completion screen.
 *
 * Returns handles so the caller can fade it out and then stop the loop —
 * leaving a rAF running after the component unmounts would leak.
 *
 * @param {HTMLCanvasElement} canvas Target canvas.
 * @return {{ startFade: Function, stop: Function }} Animation controls.
 */
function runConfetti( canvas ) {
	const ctx = canvas.getContext( '2d' );
	const COLORS = [ '#6E42D3', '#a78bfa', '#fbbf24', '#34d399', '#f87171', '#60a5fa', '#f472b6' ];

	let globalAlpha = 1;
	let fading = false;
	let animId;

	const pieces = Array.from( { length: 150 }, () => ( {
		x: Math.random() * canvas.width,
		y: Math.random() * canvas.height - canvas.height,
		w: Math.random() * 10 + 5,
		h: Math.random() * 5 + 3,
		color: COLORS[ Math.floor( Math.random() * COLORS.length ) ],
		speed: Math.random() * 3 + 1.5,
		angle: Math.random() * Math.PI * 2,
		spin: ( Math.random() - 0.5 ) * 0.1,
		drift: ( Math.random() - 0.5 ) * 1.5,
	} ) );

	const draw = () => {
		ctx.clearRect( 0, 0, canvas.width, canvas.height );

		if ( fading ) {
			globalAlpha = Math.max( 0, globalAlpha - 0.018 );
		}

		if ( globalAlpha <= 0 ) {
			return;
		}

		pieces.forEach( ( p ) => {
			ctx.save();
			ctx.globalAlpha = globalAlpha;
			ctx.translate( p.x + p.w / 2, p.y + p.h / 2 );
			ctx.rotate( p.angle );
			ctx.fillStyle = p.color;
			ctx.fillRect( -p.w / 2, -p.h / 2, p.w, p.h );
			ctx.restore();

			p.y += p.speed;
			p.x += p.drift;
			p.angle += p.spin;

			if ( p.y > canvas.height + p.h ) {
				p.y = -p.h;
				p.x = Math.random() * canvas.width;
			}
		} );

		animId = requestAnimationFrame( draw );
	};

	draw();

	return {
		startFade: () => {
			fading = true;
		},
		stop: () => cancelAnimationFrame( animId ),
	};
}

export default {
	name: 'Complete',
	props: {
		funnelId: {
			type: [ Number, String ],
			default: null,
		},
		firstStepLink: {
			type: String,
			default: '',
		},
	},
	emits: [ 'nav' ],
	data() {
		return {
			fadeTimer: null,
			confetti: null,
			resources: [
				{
					label: 'Blog & tutorials',
					url: 'https://www.youtube.com/@WPFunnels',
					icon: '<svg width="28" height="28" viewBox="0 0 28 28" fill="none"><rect width="28" height="28" rx="8" fill="#fee2e2"/><path d="M11 10l7 4-7 4V10z" fill="#ef4444"/></svg>',
				},
				{
					label: 'Join the community',
					url: 'https://www.facebook.com/groups/wpfunnels',
					icon: '<svg width="28" height="28" viewBox="0 0 28 28" fill="none"><rect width="28" height="28" rx="8" fill="#dbeafe"/><circle cx="12" cy="12" r="3" stroke="#3b82f6" stroke-width="1.4"/><circle cx="18" cy="12" r="3" stroke="#3b82f6" stroke-width="1.4"/><path d="M7 22c0-2.8 2-4.5 5-4.5M21 22c0-2.8-2-4.5-5-4.5" stroke="#3b82f6" stroke-width="1.4" stroke-linecap="round"/></svg>',
				},
				{
					label: 'Get support',
					url: 'https://wordpress.org/support/plugin/wpfunnels/',
					icon: '<svg width="28" height="28" viewBox="0 0 28 28" fill="none"><rect width="28" height="28" rx="8" fill="#d1fae5"/><path d="M10 14v-2a4 4 0 018 0v2" stroke="#059669" stroke-width="1.4" stroke-linecap="round"/><rect x="8" y="14" width="3" height="4" rx="1.5" fill="#059669"/><rect x="17" y="14" width="3" height="4" rx="1.5" fill="#059669"/></svg>',
				},
				{
					label: 'Docs',
					url: 'https://getwpfunnels.com/docs/getting-started-with-wpfunnels/',
					icon: '<svg width="28" height="28" viewBox="0 0 28 28" fill="none"><rect width="28" height="28" rx="8" fill="#e0f2fe"/><rect x="8" y="7" width="12" height="14" rx="2" stroke="#0284c7" stroke-width="1.4"/><path d="M11 12h6M11 15.5h4" stroke="#0284c7" stroke-width="1.4" stroke-linecap="round"/></svg>',
				},
			],
		};
	},
	computed: {
		/**
		 * Whether the template step actually built something. False when the
		 * user skipped it, in which case there is no funnel to open and the
		 * editor CTA is hidden.
		 */
		hasFunnel() {
			return !! this.funnelId;
		},
	},
	mounted() {
		// The completion screen is terminal — the footer offers no way onward.
		this.$emit( 'nav', {
			showBack: false,
			showNext: false,
			showSkip: false,
			canProceed: false,
			busy: false,
			label: '',
		} );

		this.startConfetti();
	},
	beforeUnmount() {
		this.stopConfetti();
	},
	methods: {
		startConfetti() {
			const canvas = this.$refs.confetti;
			if ( ! canvas ) {
				return;
			}

			canvas.width = window.innerWidth;
			canvas.height = window.innerHeight;

			this.confetti = runConfetti( canvas );
			this.fadeTimer = setTimeout( this.confetti.startFade, 3200 );
		},

		stopConfetti() {
			clearTimeout( this.fadeTimer );
			this.confetti?.stop();
		},

		/**
		 * Report the outcome before leaving the wizard.
		 *
		 * @param {string} action Which exit the user took.
		 * @return {Promise} Always resolves.
		 */
		completeStep( action ) {
			const wizardObj = window.setup_wizard_obj || {};
			const restApiUrl = wizardObj.rest_api_url || '';

			if ( ! restApiUrl ) {
				return Promise.resolve();
			}

			const base = restApiUrl.endsWith( '/' ) ? restApiUrl : `${ restApiUrl }/`;

			return apiFetch( {
				url: `${ base }wpfunnels/v1/setup-wizard/complete-step`,
				method: 'POST',
				data: {
					funnelId: this.funnelId,
					action,
					goal: 'improve-checkout',
					total_steps: 4,
				},
			} ).catch( () => {} );
		},

		async viewFunnel() {
			await this.completeStep( 'viewFunnel' );
			const wizardObj = window.setup_wizard_obj || {};
			const adminUrl = wizardObj.admin_url || '';

			if ( this.funnelId ) {
				window.location.href = `${ adminUrl }admin.php?page=edit_funnel&id=${ this.funnelId }`;
				return;
			}
			if ( this.firstStepLink ) {
				window.open( this.firstStepLink, '_blank' );
				return;
			}
			window.location.href = wizardObj.dashboard_url || `${ adminUrl }admin.php?page=wpfunnels`;
		},

		async goToDashboard() {
			await this.completeStep( 'goToDashboard' );
			const wizardObj = window.setup_wizard_obj || {};
			window.location.href = wizardObj.dashboard_url || `${ wizardObj.admin_url }admin.php?page=wpfunnels`;
		},
	},
};
</script>
