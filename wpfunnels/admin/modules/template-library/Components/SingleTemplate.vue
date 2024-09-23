<template>
	<div class="create-funnel__single-template">
		<span class="pro-tag freemium-tag" v-if=" 'freemium' === templateType">Freemium</span>

		<div class="templates-title-wrapper" v-if="showStepsPreview">
			<span class="back" @click="toggleStepsPreview">
				<svg width="30" height="30" fill="none" stroke="#363B4E" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" class="icon icon-tabler icon-tabler-arrow-narrow-left" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke="none" d="M0 0h24v24H0z"/><path d="M5 12h14M5 12l4 4m-4-4l4-4"/></svg>
			</span>
			<h2 class="title">
				{{ steps.length > 1 ? steps.length + ' Steps' : steps.length + ' Step' }}
			</h2>
		</div>

		<div class="wpfnl-single-remote-wrapper" :class="classNames" v-if="!showStepsPreview">
			<div class="wpfnl-single-remote-template">
				<div class="importar-loader" v-show="loader">
					<span class="title-wrapper">
						<span class="title">{{ loaderMessage }}</span>
						<span class="dot-wrapper">
							<span class="dot-one">.</span>
							<span class="dot-two">.</span>
							<span class="dot-three">.</span>
						</span>
					</span>
				</div>

				<div class="template-action" v-show="!loader">
					<a
						href="#"
						v-if="!isAddNewFunnelButtonDisabled"
						v-show="(isProActivated && isPro) || !isPro"
						class="btn-default import wpfnl-import-funnel"
						@click="startImportTemplate"
						v-bind:style="{ 'pointer-events': disabled ? 'none' : '' }"
					>
						Import
					</a>

					<a href="#" class="btn-default steps-preview" @click="toggleStepsPreview"> Preview </a>
				</div>

				<div class="template-image-wrapper" :style="{ backgroundImage: `url(${templatedata.featured_image})` }" >
				</div>
			</div>

			<div class="funnel-template-info">
				<span class="title">{{ templatedata.title }}</span>
				<span class="steps">{{ templatedata.steps.length }} steps</span>
			</div>
		</div>
	</div>
</template>

<script>
import apiFetch from '@wordpress/api-fetch'
const nonce = window.template_library_object.nonce
apiFetch.use(apiFetch.createNonceMiddleware(nonce))
var j = jQuery.noConflict()
export default {
	name: 'SingleTemplate',
	props: {
		templatedata: Object,
		activeCategory: String,
		templateType: String,
		freeProFilter: String,
		isPro: Boolean,
		type: String,
		showStepsPreview: Boolean,
		isAddNewFunnelButtonDisabled: Boolean,
	},
	data: function() {
		let freePro = 'free'
		if (this.isPro) {
			freePro = 'pro'
		} else {
			freePro = 'free'
		}
		return {
			classNames: this?.templatedata?.wpf_funnel_industry
				? 'slug' in this?.templatedata?.wpf_funnel_industry
					? this.templatedata.wpf_funnel_industry.slug
					: ''
				: '',
			proUrl: window.template_library_object.pro_url,
			freePro: freePro,
			freProSelector: this.isPro ? 'pro' : 'free',
			steps: this.templatedata.steps,
			loader: false,
			loaderMessage: '',
			showStepPreviewClass: '',
			showBackBtn: false,
			disabled: false,
			isProActivated: window.WPFunnelVars.isProActivated,
		}
	},
	mounted() {
		this.steps = this.templatedata.steps
	},
	watch: {
		data: function(newData) {
			this.steps = newtemplatedata.steps_order
		},
		templatedata: function() {
			this.steps = this.templatedata.steps
		},
	},
	methods: {
		startImportTemplate: function(e) {
			e.preventDefault()
			if (this.isAddNewFunnelButtonDisabled) return false
			j('.wpfnl-create-funnel__templates-wrapper .not-clickable-overlay').addClass('template-importing').show();

			this.disabled = true
			this.loader = true
			this.loaderMessage = 'Getting ready to import'

			let data = {
					action: 'wpfunnel_import_funnel',
					steps: this.steps,
					source: 'remote',
					name: this.templatedata.title,
					remoteID: this.templatedata.ID,
					type: this.type,
				},
				that = this,
				_steps = this.filterSteps(this.steps)


			wpAjaxHelperRequest('wpfunnel-import-funnel', data)
				.success(function(response) {
					let looper 			= j.Deferred().resolve(),
						stepCount 		= 0,
						importedSteps 	= [];
					j.when.apply(j, j.map( _steps, function(step, index) {
							if ( that.shouldImportStep( step.step_type ) ) {
								looper = looper.then(function() {
									return that.createStep( step, response.funnelID, index, that ).then(function( response ) {
										console.log(response)
										stepCount++;
										importedSteps.push( response.stepID )
									});
								});
							}
							return looper;
						}),
					)
						.then(function() {
							that.afterFunnelCreationRedirect(response.funnelID, importedSteps, that.templateType )
						})
				})
				.error(function(response) {
					console.log(response.statusText)
				})
		},
		shouldImportStep: function(stepType) {
			let isProActivated = window.WPFunnelVars.isProActivated == 1;
			if ( isProActivated ) {
				return true
			}
			return !['upsell', 'downsell'].includes(stepType);
		},
		filterSteps: function(steps) {
			let isProActivated = window.WPFunnelVars.isProActivated == 1;
			if (isProActivated) {
				return steps;
			} else {
				return steps.filter((step, index, self) => {
					return step.step_type !== 'upsell' && step.step_type !== 'downsell' &&
						index === self.findIndex(s => s.step_type === step.step_type);
				});
			}
		},
		createStep: function(step, funnelID, index, that) {
			let deferred = j.Deferred(),
				payload = {
					step: step,
					funnelID: funnelID,
					source: 'remote',
					importType: 'templates',
				}
			apiFetch({
                    path: `${window.WPFunnelVars.rest_api_url}wpfunnels/v1/steps/wpfunnel-import-step`,
                    method: 'POST',
                    data: payload
                })
				.then(function(response) {
					that.loaderMessage = `Importing Step: ` + (parseInt(index) + 1)
					deferred.resolve(response)
				})
				.catch(function(error) {
					deferred.reject(response)
			})
			return deferred.promise()
		},
		afterFunnelCreationRedirect: function( funnelId, importedSteps, templateType = 'free' ) {
			var payload = {
				funnelID			: funnelId,
				templateType		: templateType,
				importedSteps		: importedSteps,
				source				: 'remote',
			}
			wpAjaxHelperRequest('wpfunnel-after-funnel-creation', payload)
				.success(function(response) {
					window.location = response.redirectLink
				})
				.error(function(response) {
					console.log(response)
				})
		},
		toggleStepsPreview: function(e) {
			e.preventDefault()
			this.$emit('toggleStepsPreview')
			this.$emit('initSteps', this.steps)
			this.$emit('setActiveTemplate', this.templatedata)
		},
	},
}
</script>
