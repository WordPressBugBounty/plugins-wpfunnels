<template>
	<div class="wpfnl wpfnl-mm-setup-wizard">
		<div class="wpfnl wpfnl-mm-setup-wizard__container">
			<div class="wpfnl-mm-setup-wizard-wrapper">
				<div class="wpfnl-mm-setup-wizard__wrapper">
					<div id="wizard-container"></div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import FunnelType from './FunnelType.vue'
import Automation from './Automation.vue'
import Builder from './Builder.vue'
import Permalink from './Permalink.vue'
import Thankyou from './Thankyou.vue'
import apiFetch from '@wordpress/api-fetch'
// import WizardNav from './WizardNav'
import rexWizard from 'rex-setup-wizard-manager'
import { doneButtonSection, doneSection, essentialPlugins, featuresSection, funnelType, pageBuilderSelection, settingsFooter, welcomeButtonSection, welcomeSection } from './html';

const nonce = window.setup_wizard_obj.nonce
apiFetch.use(apiFetch.createNonceMiddleware(nonce))
    export default {
        name: 'Wizard',
        components: {
            FunnelType,
			Automation,
            Builder,
            Permalink,
            Thankyou,
			// WizardNav
        },
		data() {
			return {
				plugins 			: window.setup_wizard_obj.getPlugins,
				pluginsInfo 		: window.setup_wizard_obj.getPlugins,
				defaultSettings 	: window.setup_wizard_obj.defaultSettings,
				isMMSelected        : false,
				isQubelySelected    : false,
				requiredPlugins     : [],
				isShowContinueBtn   : false,

				steps: [
					{
						// description: 'Im one',
						stepText: 'Welcome',
						html: `
							<section class="wpfnl-mm-setup-wizard__welcome">
								${welcomeSection}
								${featuresSection}
								${welcomeButtonSection}
							</section>
						`,
						isNextStep: true,
						isPreviousStep: false,
						isSkip: true,
					},
					{
						stepText: 'Funnel & Settings',
						html: `
							<section class="setup-wizard__body-content setup-wizard__funnel-settings">
								${funnelType}
								${pageBuilderSelection}
								${essentialPlugins}
								${settingsFooter}
							</section>
						`,
						isNextStep: true,
						isPreviousStep: true,
						isSkip: false,
					},
					{
						stepText: 'Done',
						html: `
							<section class="setup-wizard__body-content" style="display: block">
								${doneSection}
								${doneButtonSection}
							</section>
						`,

						isNextStep: false,
						isPreviousStep: true,
						isSkip: false,
					},
				],
				currentStep: 0, // Initial step index
				logoUrl: window.setup_wizard_obj.logo_url,
				logoClass: 'wpfnl-mm-setup-wizard__logo',
				
			}
		},

		mounted() {
			this.initializeWizard()
			window.previousStep 							= this.prev;
			window.handleFunnelType 						= this.handleFunnelType;	
			window.handlePageBuilderSelect 					= this.handlePageBuilderSelect;	
			window.handleEssentialPlugins 					= this.handleEssentialPlugins;	
			window.handleSelectMailMint 					= this.handleSelectMailMint;
			window.handleSelectQubely						= this.handleSelectQubely;
			window.handleLastStepButton 					= this.handleLastStepButton;
			window.skipStep  								= this.next;
			window.handleOpenVideo							= this.handleOpenVideo;
			window.handleWelcomeButton						= this.handleWelcomeButton;
			window.handlePrevious 							= this.handlePrevious;
			window.handleContinue							= this.handleContinue;

			let hash = window.location.hash;
			if(hash.includes('settings')){
				this.next();
			}else if(hash.includes('done')){
				this.next();
				this.next();
			}	
		},

		methods: {
			initializeWizard() {
				this.wizard = rexWizard({
					general: {
						title: 'Welcome to the Wizard',
						currentStep: this.currentStep,
						logo: this.logoUrl, // Pass the logo URL to the wizard
						targetElement: 'wizard-container',
						logoStyles: this?.logoClass,
						buttonStyles: this?.buttonClass,
					},
					steps: this.steps,
				});

				
			},

			prev() {
				this.wizard?.previousStep();
			},

			handleOpenVideo(){
				const yt_video = "https://www.youtube.com/embed/GrzIRl5jfBE?autoplay=1";

				// Show the video iframe
				document.getElementById("setup_video").style.display = "block";

				document.getElementById(
					"setup_video"
				).innerHTML = `<iframe id="recommendation-video_set" title="Video" src="${yt_video}" allow="autoplay"></iframe>`;

				// Hide the preview image and play button
				document.getElementById("recommendation-preview").style.display = "none";
				document.getElementById("video_play_button").style.display = "none";
			},

			next() {
				this.wizard?.nextStep();
			},



			handleGetStartWizard() {
				this.wizard?.nextStep();
			},
			selectedBuilder(){
				document.querySelectorAll('.setup-wizard__page-builder .setup-wizard__single-page-builder input').forEach(element => {
					if(element.checked){
						this.requiredPlugins.push(element.value);
						this.essentialPluginSetup();
					}
				})
			},

			handleFunnelType(event) {
				const funnelType = event?.target?.value ? event?.target?.value : 'sales';
				let payload = {
					'name': 'funnel_type',
					'value' : funnelType
				}

				if(funnelType === 'sales'){
					this.requiredPlugins = ['woocommerce']
				}else{
					this.requiredPlugins = []
				}
				this.selectedBuilder();
				this.saveSettings(payload)
			},
			handlePageBuilderSelect(event){
				const builder = event.target.value ? event.target.value : 'others';
				let payload = {
					'name' : 'builder',
					'value': builder
				}

				if(document.querySelector('#sales-funnel').checked){
					this.requiredPlugins = ['woocommerce']
					this.handleFunnelType(null);
				}else{
					this.handleFunnelType({target: {value: 'lead'}});
				}
				if(builder == 'others' && this.requiredPlugins.includes('woocommerce')){
					this.requiredPlugins = ['woocommerce'];	
				}else if(this.requiredPlugins.includes('woocommerce')){
					this.requiredPlugins = ['woocommerce', builder];	
				}else{
					this.requiredPlugins = [builder];	
				}
				this.saveSettings(payload);
				this.essentialPluginSetup(this.requiredPlugins);
			},


			modifyPluginsDataForInstall(){
				// Modify Plugin Data
				let pluginsData = this.pluginsInfo;
				let modifiedPluginsData = pluginsData.filter(plugin => {
					let isPluginInclude = this.requiredPlugins.includes(plugin.slug);
					if(plugin.slug === "oxygen" || plugin.slug === "divi-builder" || plugin.slug === "bricks"){
						if(!(plugin.status !== "uninstalled" && isPluginInclude)){
							let requiredPlugins = this.requiredPlugins;
							this.requiredPlugins = requiredPlugins.filter(item => item !== plugin.slug);
						}
						return plugin.status !== "uninstalled" && isPluginInclude;
					}else{
						return isPluginInclude;
					}
				})
				this.plugins = modifiedPluginsData;
			},

			toggleMailMint(){
				// Show MailMint Plugin
				let isShowMM = this.pluginsInfo.find(plugin => plugin.slug === 'mail-mint')?.status === 'activated';
				if(!isShowMM){
					let element = document.querySelector('#setup-wizard__mail-mint');
					if(element){
						element.classList.add('active');
					}
				}
			},

			toggleQubely(){
				let isShowqubely = this.pluginsInfo.find(plugin => plugin.slug === "qubely")?.status === 'activated';
				if(!isShowqubely && this.requiredPlugins.includes('gutenberg')){
					let element = document.querySelector('#setup-wizard__qubely');
					if(element){
						element.classList.add('active');
					}
				}else{
					let element = document.querySelector('#setup-wizard__qubely');
					if(element){
						element.classList.remove('active');
					}
				}
			},

			continueToNextStep(isContinue){
				const installBtn = document.querySelector('#setup-wizard__install-btn');
				const continueBtn = document.querySelector('#setup-wizard__continue-btn');
				if(isContinue){
					if(installBtn){
						installBtn.style.display = 'none';
					}
					if(continueBtn){
						continueBtn.style.display = 'block';
					}
					return;
				}else{
					if(installBtn){
						installBtn.style.display = 'block';
					}
					if(continueBtn){
						continueBtn.style.display = 'none';
					}
				}
			},
			makeRequiredPluginsVisible(){
				const essentialPluginWrapper = document.querySelector('.setup-wizard__essential-plugins');
				let isShowqubely = this.pluginsInfo.find(plugin => plugin.slug === "qubely")?.status !== 'activated';
				let isShowMM = this.pluginsInfo.find(plugin => plugin.slug === 'mail-mint')?.status !== 'activated';
				if(!isShowMM){
					if(this.requiredPlugins[0] === 'gutenberg' && !isShowqubely){
						this.continueToNextStep(true);
						if(essentialPluginWrapper){
							essentialPluginWrapper.classList.remove('active');
						}
						return;
					}else if(this.requiredPlugins.length === 0 || (this.requiredPlugins.length === 1 && this.requiredPlugins[0] === 'other')){
					
						this.continueToNextStep(true);
						if(essentialPluginWrapper){
							essentialPluginWrapper.classList.remove('active');
						}
						return;
					}
				}
				// Add active class to all plugin elements
				let isContinue = this.plugins.every(plugin => plugin.status === "activated");
				this.continueToNextStep(isContinue);

				const elements = document.querySelectorAll('.setup-wizard__essential-plugins .setup-wizard__single-page-builder');
				elements?.forEach(item =>{
					const pluginData = item.querySelector('input').value;
					if(this.requiredPlugins.includes(pluginData)){
						item.classList.add('active');

					}else{
						item.classList.remove('active');
					}
				})
				
				if(this.requiredPlugins.includes('woocommerce')){
					const woocommercePluginWrapper = document.querySelector('.setup-wizard__woocommerce-plugins');
					if(woocommercePluginWrapper){
						woocommercePluginWrapper.classList.add('active');
					}
				}

				if(essentialPluginWrapper){
					essentialPluginWrapper.classList.add('active');
				}
				this.continueToNextStep(false);
				const installBtn = document.querySelector('#setup-wizard__install-btn');
				if(installBtn){

					installBtn.removeAttribute('disabled');
				}
				this.isShowContinueBtn = isContinue;
			},

			toggleBtnShow(selector, isShow){
				let btn = document.querySelector(selector);
				if(btn){
					if(isShow){
						btn.style.display = 'inline-block';
					}else{
						btn.style.display = 'none';
					}
				}
			},

			essentialPluginSetup(){
				this.modifyPluginsDataForInstall();
				this.toggleMailMint();
				this.toggleQubely();
				this.makeRequiredPluginsVisible();
			},

			handleSelectMailMint(event){
				this.isMMSelected = event.target.checked;
				if(this.isMMSelected){
					let preData = this.plugins;
					let mmData = this.pluginsInfo.find(plugin => plugin.slug === 'mail-mint');
					this.plugins = [mmData, ...preData];
				}else{
					let preData = this.plugins;
					preData = this.pluginsInfo.find(plugin => plugin.slug !== 'mail-mint');
					this.plugins = [...preData];
				}
			},

			handleSelectQubely(event){
				let isShowQubely = event.target.checked;
				if(isShowQubely){
					let preData = this.plugins;
					let qubelyData = this.pluginsInfo.find(plugin => plugin.slug === 'qubely');
					this.plugins = [qubelyData, ...preData];
				}else{
					let preData = this.plugins;
					preData = this.pluginsInfo.find(plugin => plugin.slug !== 'qubely');
					this.plugins = [...preData];
				}
			},
			handleEssentialPlugins() {
				let loader = document.querySelector('.wpfnl-loader');
				if(loader){
					loader.style.display = 'inline-block';
				}
				
				let previousBtn = document.querySelector('#setup-wizard__settings-back-btn');
				const installBtn = document.querySelector('#setup-wizard__install-btn');
				if(previousBtn){
					previousBtn.setAttribute('disabled', true);
					installBtn.setAttribute('disabled', true);
				}

				this.installEssentialPlugins()
			},
			handleSubscribeStep(){
				let emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
				let email = document.querySelector('#wpfnl-mm-email').value;
				let name = document.querySelector('#wpfnl-mm-name').value;
				const requiredText = document.querySelector('.wpfnl-mm-required-text');

				if(email == ''){
					requiredText.innerHTML = 'Email address is required';
					requiredText.classList.add('active');
					return;
				}else if(!emailPattern.test(email)){
					requiredText.innerHTML = 'Invalid email address';
					requiredText.classList.add('active');
					return;
				}else{
					requiredText.classList.remove('active');
					let submitBtn = document.querySelector('#wpfnl-mm-subscribe-submit');
					if(submitBtn){
						submitBtn.setAttribute('disabled', true);
					}
					let loader = document.querySelector('.wpfnl-loader');
					if(loader){
						loader.style.display = 'inline-block';
					}

					let otherBtns = document.querySelectorAll('#wpfnl-mm-skip-plugin-step-btns button');
					if(otherBtns){
						otherBtns.forEach((btn) => {
							btn.setAttribute('disabled', true);
						})
					}
					this.createContact(email, name);
				}
			},

			createHash(hash){
				window.location.hash = hash;
			},

			handleWelcomeButton(){
				this.createHash('settings')
				this.next();
			},

			handleContinue(){
				this.createHash('done');
				this.next();
			},

			handlePrevious(){
				this.createHash('');
				this.prev();
			},
			

			installEssentialPlugins: async function () {
				let plugins = this.plugins;
				const installPlugin = (plugin) => {
					return new Promise((resolve, reject) => {
						if (plugin.status === 'uninstalled') {
							wp.updates.installPlugin({
								slug: plugin.slug,
							}).done(() => {
								plugin.status = 'installed';
								resolve(plugin);
							}).fail((error) => {
								if( "folder_exists" === error.errorCode){
									plugin.status = 'installed';
									resolve(plugin);
								}else{
									reject(error);
								}
							});
						} else {
							resolve(plugin);
						}
					});
				};

				try {
					for (let plugin of plugins) {
						await installPlugin(plugin);
					}
					// Update Plugin Status
					let previousData = this.plugins;
					previousData.map(plugin => plugin.status = 'activated');
					this.plugins = previousData;

					// Enable Previous Btn
					let previousBtn = document.querySelector('#wpfnl-mm-installation-pre-step');
					if(previousBtn){
						previousBtn.setAttribute('disabled', false);
					}
					// Next Step
					this.wizard?.nextStep();
					this.activatePlugins(plugins);
					this.createHash('done');
				} catch (error) {
					console.error("Error installing plugins:", error);
				}
			},

			activatePlugins: function(plugins) {
				let payload = {
					'plugins': plugins
				};
				apiFetch({
					path: window.setup_wizard_obj.rest_api_url + 'wpfunnels/v1/settings/activate-plugins/',
					method: 'POST',
					data: payload
				}).then(response => {
					
				}).catch(error => {
					console.error("Error activating plugins:", error);
				});
			},

			handleLastStepButton(url){
				const switcher = document.querySelector('#setup-wizard__switch-for-collect-email');
				if(switcher){
					if(switcher.checked){
						this.createContact();
					}

				}
				
				window.location.href = url;
			},

			createContact: function() {
				let payload = {
					'email': window.setup_wizard_obj.admin_email,
					'name': window.setup_wizard_obj.admin_name
				};

				apiFetch({
					path: window.setup_wizard_obj.rest_api_url + 'wpfunnels/v1/settings/create-contact/',
					method: 'POST',
					data: payload
				}).then(response => {
					let submitBtn = document.querySelector('#wpfnl-mm-subscribe-submit');
					if(submitBtn){
						submitBtn.setAttribute('disabled', false);
					}
					let loader = document.querySelector('.wpfnl-loader');
					if(loader){
						loader.style.display = 'none';
					}

					let otherBtns = document.querySelectorAll('#wpfnl-mm-skip-plugin-step-btns button');
					if(otherBtns){
						otherBtns.forEach((btn) => {
							btn.setAttribute('disabled', false);
						})
					}
					this.wizard?.nextStep();
				}).catch(error => {
					console.error("Error activating plugins:", error);
					let submitBtn = document.querySelector('#wpfnl-mm-subscribe-submit');
					if(submitBtn){
						submitBtn.setAttribute('disabled', false);
					}
					let loader = document.querySelector('.wpfnl-loader');
					if(loader){
						loader.style.display = 'none';
					}

					let otherBtns = document.querySelectorAll('#wpfnl-mm-skip-plugin-step-btns button');
					if(otherBtns){
						otherBtns.forEach((btn) => {
							btn.setAttribute('disabled', false);
						})
					}
					this.wizard?.nextStep();
				});
			},

			saveSettings: function (payload) {
				apiFetch({
					path: window.setup_wizard_obj.rest_api_url + 'wpfunnels/v1/settings/save-setup-wizard-settings/',
					method: 'POST',
					data: payload
				}).then(response => {

				}).catch(error => {
					console.error("Error saving settings:", error);
				});
			}
		},

		watch: {
			// React to changes in currentStep to reinitialize the wizard
			currentStep(newVal, oldVal) {
				if (newVal !== oldVal) {
					this.initializeWizard()
				}
			},

		},
}
</script>