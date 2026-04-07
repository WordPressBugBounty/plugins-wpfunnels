<template>
	<div class="create-funnel__single-template">
		<span class="pro-tag freemium-tag" v-if=" 'freemium' === templateType">Freemium</span>

		<div class="templates-title-wrapper" v-if="showStepsPreview">
			<span class="back" @click="toggleStepsPreview">
				<svg width="30" height="30" fill="none" stroke="#363B4E" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" class="icon icon-tabler icon-tabler-arrow-narrow-left" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke="none" d="M0 0h24v24H0z"/><path d="M5 12h14M5 12l4 4m-4-4l4-4"/></svg>
			</span>
			<h2 class="title">
				{{ stepCount > 1 ? stepCount + ' Steps' : stepCount + ' Step' }}
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
					
					<a 
						v-show="!isProActivated && isPro" 
						href="https://getwpfunnels.com/pricing/" 
						class="btn-default upgrade-to-pro"
						style="width:max-content;" 
						target="_blank"
					>
						Upgrade to Pro
					</a>

					<a href="#" class="btn-default steps-preview" @click="toggleStepsPreview"> Preview </a>
				</div>

				<div class="template-image-wrapper" :style="{ backgroundImage: `url(${previewImage})` }" >
				</div>
			</div>

			<div class="funnel-template-info">
				<span class="title">{{ templatedata.title }}</span>
				<span class="steps">{{ stepCount }} steps</span>
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
			isStoreCheckout: window.template_library_object.isStoreCheckout || false,
		}
	},
	computed: {
		previewImage: function() {
			if ( this.isStoreCheckout && this.templatedata.steps ) {
				const checkoutStep = this.templatedata.steps.find( s => s.step_type === 'checkout' );
				if ( checkoutStep && checkoutStep.featured_image ) {
					return checkoutStep.featured_image;
				}
			}
			return this.templatedata.featured_image;
		},
		displaySteps: function() {
			if (!this.steps) return [];
			
			// Filter for Store Checkout - only show one checkout and one thankyou
			if (this.isStoreCheckout) {
				let filtered = [];
				let hasCheckout = false;
				let hasThankyou = false;
				
				this.steps.forEach(step => {
					if (step.step_type === 'checkout' && !hasCheckout) {
						filtered.push(step);
						hasCheckout = true;
					} else if (step.step_type === 'thankyou' && !hasThankyou) {
						filtered.push(step);
						hasThankyou = true;
					}
				});
				
				return filtered;
			}
			
			return this.steps;
		},
		stepCount: function() {
			return this.displaySteps.length;
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
					is_store_checkout: this.isStoreCheckout ? true : false,
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
			return !['downsell'].includes(stepType);
		},
		filterSteps: function(steps) {
			let isProActivated = window.WPFunnelVars.isProActivated == 1;
			
			// Filter for Store Checkout - only checkout and thankyou (one of each)
			if (this.isStoreCheckout) {
				let filtered = [];
				let hasCheckout = false;
				let hasThankyou = false;
				
				steps.forEach(step => {
					if (step.step_type === 'checkout' && !hasCheckout) {
						filtered.push(step);
						hasCheckout = true;
					} else if (step.step_type === 'thankyou' && !hasThankyou) {
						filtered.push(step);
						hasThankyou = true;
					}
				});
				
				return filtered;
			}
			
			if (isProActivated) {
				return steps;
			} else {
				return steps.filter((step, index, self) => {
					return step.step_type !== 'downsell' &&
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
				is_store_checkout	: this.isStoreCheckout ? true : false,
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
			// Pass filtered steps to parent for Store Checkout
			this.$emit('initSteps', this.displaySteps)
			this.$emit('setActiveTemplate', this.templatedata)
		},
	},
}
</script>
