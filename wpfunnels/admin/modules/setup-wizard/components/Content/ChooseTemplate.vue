<template>
	<div class="wpfnl-wz-center wpfnl-wz-templates">
		<h1 class="wpfnl-wz-h1">Setup your new store checkout</h1>
		<p class="wpfnl-wz-sub">After setup you can change the text and color or even choose an entirely new store checkout design.</p>

		<p v-if="errorMessage" class="wpfnl-wz-error" role="alert">{{ errorMessage }}</p>

		<!-- Loading skeletons -->
		<div v-if="loading" class="wpfnl-wz-template-grid" aria-busy="true">
			<div v-for="n in 3" :key="n" class="wpfnl-wz-template-card is-skeleton">
				<div class="wpfnl-wz-template-thumb"></div>
				<div class="wpfnl-wz-skeleton-line"></div>
				<div class="wpfnl-wz-skeleton-line is-short"></div>
			</div>
		</div>

		<p v-else-if="! templates.length" class="wpfnl-wz-empty">
			No templates are available for this builder yet. You can skip ahead and
			build your funnel from scratch in the editor.
		</p>

		<div
			v-else
			class="wpfnl-wz-template-grid"
			:class="{ 'is-busy': isGenerating }"
			:aria-busy="isGenerating"
			role="radiogroup"
			aria-label="Funnel template"
		>
			<div
				v-for="tpl in templates"
				:key="tpl.ID || tpl.id"
				class="wpfnl-wz-template-card"
				:class="{
					'is-active': selectedTemplateId === ( tpl.ID || tpl.id ),
					'is-suggested': isSuggested( tpl ),
				}"
				role="radio"
				:aria-checked="selectedTemplateId === ( tpl.ID || tpl.id )"
				tabindex="0"
				@click="selectTemplate( tpl )"
				@keydown.enter.prevent="selectTemplate( tpl )"
				@keydown.space.prevent="selectTemplate( tpl )"
			>
				<div class="wpfnl-wz-template-thumb">
					<img :src="cardImage( tpl )" :alt="tpl.title" loading="lazy" />
					<span v-if="isSuggested( tpl )" class="wpfnl-wz-template-badge">
						<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
							<path d="M5 1L6.18 3.4L8.8 3.78L6.9 5.64L7.36 8.25L5 6.99L2.64 8.25L3.1 5.64L1.2 3.78L3.82 3.4L5 1Z" fill="currentColor" stroke="currentColor" stroke-width="0.5" stroke-linejoin="round" />
						</svg>
						Suggested
					</span>
					<button type="button" class="wpfnl-wz-template-preview" @click.stop="showPreview( tpl )">
						Preview
					</button>
				</div>

				<h2 class="wpfnl-wz-template-title">{{ tpl.title }}</h2>
				<p class="wpfnl-wz-template-meta">{{ stepCount( tpl ) }} steps</p>
			</div>
		</div>

		<PreviewTemplate
			v-if="showPreviewModal && previewTemplate"
			:template="previewTemplate"
			:isStoreCheckout="true"
			:isSalesFunnel="false"
			@close="closePreview"
			@import="importFromPreview"
		/>
	</div>
</template>

<script>
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import PreviewTemplate from './PreviewTemplate.vue';
import { generateFunnel } from '../../js/funnel-generator';

/**
 * The checkout templates the wizard offers, in the order they appear.
 *
 * Matched as lowercase substrings of the template title, since the API returns
 * titles rather than stable slugs. Anything not listed here is hidden — the
 * wizard deliberately shows a short, curated set rather than the full library.
 */
const OFFERED_TEMPLATES = [
	'instant checkout',
	'express checkout',
	'store checkout',
];

export default {
	name: 'ChooseTemplate',
	components: {
		PreviewTemplate,
	},
	props: {
		builder: {
			type: String,
			default: 'gutenberg',
		},
		prefetchedTemplates: {
			type: Array,
			default: () => [],
		},
	},
	emits: [ 'nav', 'next-step' ],
	data() {
		return {
			loading: true,
			templates: [],
			selectedTemplateId: null,
			selectedTemplate: null,
			showPreviewModal: false,
			previewTemplate: null,
			phase: 'select',
			errorMessage: '',
		};
	},
	computed: {
		isGenerating() {
			return 'generating' === this.phase;
		},
	},
	watch: {
		selectedTemplateId() {
			this.publishNav();
		},
		templates( list ) {
			// Default to the first (suggested) template so the step opens ready
			// to proceed instead of with a disabled button.
			if ( ! this.selectedTemplateId && list?.length ) {
				this.selectTemplate( list[ 0 ] );
			}
		},
		prefetchedTemplates( value ) {
			// The prefetch may land after this step mounted.
			if ( ! this.loading ) {
				return;
			}
			if ( value?.length ) {
				this.templates = this.filterTemplates( value );
				this.loading = false;
				this.publishNav();
			}
		},
	},
	mounted() {
		if ( this.prefetchedTemplates?.length ) {
			this.templates = this.filterTemplates( this.prefetchedTemplates );
			this.loading = false;
		} else {
			this.fetchTemplates();
		}
		this.publishNav();
	},
	methods: {
		publishNav() {
			if ( 'generating' === this.phase ) {
				this.$emit( 'nav', {
					label: 'Building...',
					canProceed: false,
					busy: true,
					showBack: false,
					showNext: true,
				} );
				return;
			}

			this.$emit( 'nav', {
				label: 'Import & Continue',
				canProceed: !! this.selectedTemplateId,
				busy: false,
				showBack: true,
				showNext: true,
				showSkip: true,
			} );
		},

		/**
		 * Resolve the builder slug the template API expects. Divi ships its
		 * templates under `divi-builder`, and "Others" has no templates of its
		 * own so it borrows Gutenberg's.
		 *
		 * @return {string} Builder slug.
		 */
		builderParam() {
			if ( 'divi' === this.builder ) {
				return 'divi-builder';
			}
			if ( 'others' === this.builder ) {
				return 'gutenberg';
			}
			return this.builder || 'gutenberg';
		},

		fetchTemplates() {
			this.loading = true;
			const builder = this.builderParam();

			this.requestTemplates( builder )
				.then( ( templates ) => {
					if ( templates.length ) {
						this.templates = this.filterTemplates( templates );
						return null;
					}
					// Bricks and Oxygen have thin template libraries; fall back.
					if ( 'bricks' === builder || 'oxygen' === builder ) {
						return this.requestTemplates( 'gutenberg' );
					}
					return null;
				} )
				.then( ( fallback ) => {
					if ( fallback?.length ) {
						this.templates = this.filterTemplates( fallback );
					}
				} )
				.catch( ( error ) => {
					console.error( 'Error fetching templates:', error );
				} )
				.finally( () => {
					this.loading = false;
					this.publishNav();
				} );
		},

		/**
		 * @param {string} builder Builder slug.
		 * @return {Promise<Array>} Templates, or an empty array.
		 */
		requestTemplates( builder ) {
			const path = addQueryArgs(
				`${ window.setup_wizard_obj.rest_api_url }wpfunnels/v1/templates/get_templates`,
				{ builder, type: 'store_checkout' }
			);

			return apiFetch( { path } ).then( ( response ) =>
				response.success && response.templates ? response.templates : []
			);
		},

		/**
		 * Instant Checkout is the one we steer people towards, so it carries a
		 * badge and is pinned ahead of the ID-ordered rest.
		 *
		 * @param {Object} template Template to test.
		 * @return {boolean} Whether this is the suggested template.
		 */
		isSuggested( template ) {
			return ( template?.title || '' ).toLowerCase().includes( 'instant checkout' );
		},

		/**
		 * Position of a template in the curated list, or -1 if it isn't offered.
		 *
		 * @param {Object} template Template to look up.
		 * @return {number} Index in OFFERED_TEMPLATES.
		 */
		offeredIndex( template ) {
			const title = ( template?.title || '' ).toLowerCase();
			return OFFERED_TEMPLATES.findIndex( ( name ) => title.includes( name ) );
		},

		/**
		 * Reduce to the curated templates, trimmed to their checkout and
		 * thank-you steps, in the order OFFERED_TEMPLATES lists them.
		 *
		 * @param {Array} templates Raw templates from the API.
		 * @return {Array} Curated, ordered templates.
		 */
		filterTemplates( templates ) {
			return templates
				.filter( ( tpl ) => 'pro' !== tpl.templateType )
				.filter( ( tpl ) => {
					const types = ( tpl.steps || [] ).map( ( s ) => s.step_type );
					return types.includes( 'checkout' ) && types.includes( 'thankyou' );
				} )
				.filter( ( tpl ) => this.offeredIndex( tpl ) !== -1 )
				.map( ( tpl ) => ( {
					...tpl,
					steps: [
						tpl.steps.find( ( s ) => 'checkout' === s.step_type ),
						tpl.steps.find( ( s ) => 'thankyou' === s.step_type ),
					].filter( Boolean ),
				} ) )
				.sort( ( a, b ) => this.offeredIndex( a ) - this.offeredIndex( b ) );
		},

		cardImage( template ) {
			const checkout = ( template.steps || [] ).find( ( s ) => 'checkout' === s.step_type );
			return checkout?.featured_image || template.featured_image;
		},

		stepCount( template ) {
			return ( template.steps || [] ).length;
		},

		selectTemplate( template ) {
			if ( ! template?.ID && ! template?.id ) {
				return;
			}
			this.selectedTemplateId = template.ID || template.id;
			this.selectedTemplate = template;
		},

		showPreview( template ) {
			this.previewTemplate = template;
			this.showPreviewModal = true;
		},

		closePreview() {
			this.showPreviewModal = false;
			this.previewTemplate = null;
		},

		importFromPreview( template ) {
			this.closePreview();
			this.selectTemplate( template );
			this.submit();
		},

		/**
		 * Move on without importing a template.
		 *
		 * Reported as `skipped` rather than `abandoned` — the user saw the
		 * offer and declined it, which the telemetry treats as a distinct
		 * outcome from dropping out. No funnel is created, so the completion
		 * step adapts its copy off the absent funnel ID.
		 */
		skip() {
			const wizardObj = window.setup_wizard_obj || {};
			const restApiUrl = wizardObj.rest_api_url || '';

			if ( restApiUrl ) {
				const base = restApiUrl.endsWith( '/' ) ? restApiUrl : `${ restApiUrl }/`;
				apiFetch( {
					url: `${ base }wpfunnels/v1/setup-wizard/track-step`,
					method: 'POST',
					data: {
						event_type: 'skipped',
						step_name: 'choose_template',
						step_index: 3,
						goal: 'improve-checkout',
						time_on_step: 0,
						total_steps: 4,
					},
				} ).catch( () => {} );
			}

			this.$emit( 'next-step' );
		},

		/**
		 * Generate the funnel, then hand the wizard the created funnel so the
		 * completion step can link straight to it.
		 */
		async submit() {
			if ( ! this.selectedTemplate || 'generating' === this.phase ) {
				return;
			}

			this.phase = 'generating';
			this.errorMessage = '';
			this.publishNav();

			try {
				const { funnelId, firstStepLink } = await generateFunnel( {
					builder: this.builder,
					template: this.selectedTemplate,
				} );

				this.$emit( 'next-step', { funnelId, firstStepLink, template: this.selectedTemplate } );
			} catch ( error ) {
				this.phase = 'select';
				this.errorMessage = error?.message || 'Unable to generate the funnel. Please try again.';
				this.publishNav();
			}
		},
	},
};
</script>
