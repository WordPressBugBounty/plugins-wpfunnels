<template>
    <div>
        <div class="create-funnel-loader-overlay" id="create-funnel-loader" v-if="loader">
            <span class="wpfnl-loader"></span>
        </div>

        <div id="template-library-modal" class="template-library-modal" style="display: none">
            <div id="wpfnl-create-funnel__inner-content" class="wpfnl-create-funnel__inner-content">

                <div id="wpfnl-create-funnel__template-wrapper" class="wpfnl-create-funnel__templates-wrapper">
                    <div class="funnel-templates-header">
                        <div class="template-library-filter-wrapper">
                            <span class="back-form-modal wpfnl-modal-close" title="Back to Funnel list" v-show="!showStepsPreview && !isTemplatePage">
                                <DoubleAngleLeft/>
                            </span>
                            

                            <h1 class="header-title" v-if="!showStepsPreview">Find your templates</h1>
                            <div v-else class="wpfnl-template-header-title-wrapper">
                                <span class="back-form-modal back-to-templates" title="Back to Template" v-show="showStepsPreview" @click="backToTemplates">
                                    <DoubleAngleLeft/>
                                </span>
                                <h1 class="header-title">{{activeTemplate.title}}</h1>
                            </div>                            

                            <ResponsiveSwitcher v-if="showStepsPreview" :setResponsiveView="setResponsiveView"/>

                            <select class="template-type-filter" v-if="template_type.length" v-show="!showStepsPreview" v-model="type" @change="doTemplateCatFilter">
                                <option data-filter="woocommerce" v-for="(tempalateType, index) in template_type" :key="index" :value="tempalateType.slug" :selected="tempalateType.slug == type">
                                    {{tempalateType.label}}
                                </option>
                            </select>

                            <ul class="pro-free__filter" v-if="template_type.length > 0 && showProFilter" v-show="!showStepsPreview">
                                <li data-filter="all" :class="templatesType == 'all' ? 'active' : '' " @click="doFreeProFilter('all')">
                                    all
                                </li>

                                <li data-filter="free" :class="templatesType == 'free' ? 'active' : '' "  @click="doFreeProFilter('free')">
                                    free
                                </li>

                                <li data-filter="pro" :class="templatesType == 'freemium' ? 'active' : '' " @click="doFreeProFilter('freemium')">
                                    <svg width="20" height="14" fill="none" viewBox="0 0 20 14" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 1l4 6 5-4-2 10H3L1 3l5 4 4-6z" clip-rule="evenodd"/></svg>
                                    Freemium
                                </li>
                            </ul>

                            <!-- <div class="import-funnel-name" v-show="showStepsPreview" v-if="!isAddNewFunnelButtonDisabled">
                                <input type="text" name="import-funnel-name" :value="this.activeTemplate ? this.activeTemplate.title : '' " placeholder="Write Funnel Name">
                            </div> -->

                            <div class="funnel-global-import" v-show="showStepsPreview">
                                <a
                                    href="#"
                                    class="btn-default"
                                    id="funnel-global-import"
                                    @click="startImportTemplate"
                                    v-bind:style='{"pointer-events" : (disabled? "none" : "" )}'
                                    v-if="!isAddNewFunnelButtonDisabled"
                                    v-show="(isProActivated && activeTemplate.is_pro) || !activeTemplate.is_pro"
                                >
                                    <span class="global-import-progress"
                                        v-bind:style="{ width: globalImportProgress }"></span>
                                    <span class="btn-text">{{loaderMessage}}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- /.funnel-templates-header -->

                    <div class="funnel-templates-body" :class="showStepsPreview ? 'no-sidebar': '' ">
                        <div class="funnel-templates-sidebar" v-if="!showStepsPreview">
                            <div class="sidebar-tab-nav">
                                <span class="funnel-templates" :class="{ active: activeTab === 'templates' }" @click="onActivateTab('templates')">Templates</span>
                            </div>

                            <div class="sidebar-tab-content templates-content" v-if="activeTab === 'templates'">
                                <CategoryNav v-if="template_type.length > 0 && !showStepsPreview" :categories="categories" :activeCategory="activeCategory"  @doCatFilter="doCatFilter"/>
                            </div>

                        </div>
                        <!-- /.funnel-templates-sidebar -->

                        <div class="funnel-templates-content">
                            <div v-if="activeTab === 'templates'">
                                <div class="not-clickable-overlay"></div>

                                <!-- funnel name modal. this modal will show when create from scratch -->
                                <div class="create-funnel-name-modal wpfnl-create-funnel-layout">
                                    <span class="close" @click="closeFunnelNameModal" role="button">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg"><path stroke="#7A8B9A" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.5 1.5l-11 11m0-11l11 11"/></svg>
                                    </span>

                                    <div class="modal-body"> 
                                        <form action="">
                                            <div class="wpfnl-form-group">
                                                <label for="funnel-name">Name of your funnel</label>
                                                <input type="text" name="funnel-name" placeholder="Enter your funnel name" v-model="funnelName"/>
                                            </div>

                                            <p class="layout-title">Select Funnel layout</p>

                                            <div class="funnels-layout-wrapper">
                                                <div 
                                                    v-for="(layout, index) in layouts"
                                                    :class="[
                                                        selectedFunnelLayout?.value === layout.value ? 'active' : '',
                                                        layout.value,
                                                    ]"
                                                    class="single-layout layout1" 
                                                    @click="selectFunnelLayout(index, layout)" 
                                                    :key="index"
                                                >
                                                    <a href="https://getwpfunnels.com/pricing/" target="_blank" v-if="!isPro && 0 !==index && 1 !==index "><span class="pro-tag"><svg fill="none" width="22" height="22"  viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="11" fill="#2FCF5C"/><path fill="#fff" d="M15.209 9.236l-1.587.396-1.983-2.471a.926.926 0 00-1.445 0L8.21 9.63l-1.618-.395a.933.933 0 00-1.124 1.124l1.093 3.842a.618.618 0 00.617.451h7.412a.618.618 0 00.618-.45l1.1-3.843a.932.932 0 00-1.1-1.124zM5.172 8.57a.772.772 0 100-1.545.772.772 0 000 1.544zm11.427 0a.772.772 0 100-1.545.772.772 0 000 1.544zm-5.714-2.626a.772.772 0 100-1.544.772.772 0 000 1.544zm3.706 10.964H7.18a.618.618 0 010-1.236h7.411a.618.618 0 110 1.236z"/></svg></span></a>
                                                    <span class="single-layout-inner" :class="{ 'is-pro': !isPro && 0!==index && 1!==index }">
                                                        <span class="inner-box">
                                                            <FunnelLayout1 v-if="'layout1' == layout.value" />
                                                            <FunnelLayout2 v-if="'layout2' == layout.value" />
                                                            <FunnelLayout3 v-if="'layout3' == layout.value" />
                                                            <FunnelLayout4 v-if="'layout4' == layout.value" />
                                                            <FunnelLayout5 v-if="'layout5' == layout.value" />
                                                            <FunnelLayout6 v-if="'layout6' == layout.value" />
                                                        </span>
                                                    </span>
                                                </div>
                                                
                                            </div>

                                            <div class="button-area">
                                                <span class="wpfnl-alert box" style="display: inline-block;" :class="alertClass" v-if="isShowAlert">{{
                                                    this.alertMessage
                                                }}</span>
                                                <button type="submit" @click="createFunnel" :disabled='disabled'>
                                                    {{createFunnelTitle}}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="templates-title-wrapper" v-if="!showStepsPreview && 'other' !== builder">
                                    <h2 class="title">{{totalTemplates > 1 ? totalTemplates + ' Templates' : totalTemplates + ' Template'}}</h2>
                                </div>

                                <div class="wpfnl-create-funnel__templates">
                                    <div class="template-cat-filter-loader" :class="templateCatFilterLoader ? 'show-loader' : '' ">
                                        <span class="wpfnl-loader"></span>
                                    </div>

                                    <div v-show="loader" class="wpfnl-create-funnel__loader">
                                        <!-- <span class="wpfnl-loader" v-show="templateCatFilterLoader"></span> -->
                                    </div>

                                    <div class="create-funnel__single-template create__from-scratch"
                                        v-if="(showProFilter && !showStepsPreview && !isTemplatePage) || ( 'other' === builder )">
                                        <a id="wpfnl-create-funnel" href="#" class="btn-default" @click="showFunnelNameModal"
                                        v-if="!isAddNewFunnelButtonDisabled"> <PlusIcon /> Start From scratch </a>

                                        <div class="funnel-limit-notice" v-if="isAddNewFunnelButtonDisabled">
                                            <p><b>You have reached your limit and built 3/3 funnels!</b><br/><br/>
                                                To create unlimited funnels, please upgrade to Pro.
                                            </p>
                                        </div>

                                        <div class="wpfnl-single-remote-wrapper">
                                            <div class="wpfnl-single-remote-template">
                                                <div class="template-image-wrapper"></div>
                                            </div>
                                            <div class="funnel-template-info">
                                                <span class="title">title</span>
                                            </div>
                                        </div>
                                    </div>

                                    <template v-if="isAnyPluginMissing == 'no'">
                                        <SingleTemplate
                                            v-for="(data, index) in templates"
                                            :templatedata="data"
                                            :active-category="activeCategory"
                                            :templateType="data.templateType"
											:isPro="data.is_pro"
                                            :type="type"
                                            :key="index"
                                            :isAddNewFunnelButtonDisabled="isAddNewFunnelButtonDisabled"
                                            @toggleStepsPreview="toggleStepsPreview"
                                            @initSteps="initSteps"
                                            @setActiveTemplate="setActiveTemplate"
                                            v-if="!showStepsPreview"
                                        />
                                    </template>

                                    <div class="create-funnel__single-template wpfnl-missing-plugin-notice" v-else>
                                        <div v-if="builder === 'gutenberg'">
                                            <h4 class="wpfnl-notice-title">Oops! It looks like {{dependencyPlugins[builder].name}} is inactive.</h4>
                                            <div class="wpfnl-notice-notice-body">
                                                <p>
                                                    It seems like you have selected {{ builder.charAt(0).toUpperCase() + builder.slice(1) }} as your preferred page builder, but you
                                                    do not have {{dependencyPlugins[builder].name}} activated on your site. You see, we create funnel templates for
                                                    {{ builder.charAt(0).toUpperCase() + builder.slice(1) }} using {{dependencyPlugins[builder].name}}.
                                                    <br>Please install and activate {{dependencyPlugins[builder].name}} to import funnel templates for {{ builder.charAt(0).toUpperCase() + builder.slice(1) }}
                                                    <a href="#" @click="pluginInstallationAction">
                                                        Click here to install & activate {{dependencyPlugins[builder].name}}
                                                        <span class="dot-wrapper" v-if="pluginInstallLoader">
                                                            <span class="dot-one">.</span>
                                                            <span class="dot-two">.</span>
                                                            <span class="dot-three">.</span>
                                                        </span>
                                                    </a>
                                                </p>
                                                <p>If you want to create & design funnel pages without using {{dependencyPlugins[builder].name}}, then don't worry.
                                                    <b>You can go ahead and create funnels from scratch using any page builder/editor. It will work just fine.</b></p>

                                                <span class="wpfnl-plugin-installation-error"
                                                    v-html="pluginInstallationErrorMessage"></span>
                                            </div>
                                        </div>

                                        <div  v-else-if="builder === 'elementor'">
                                            <h4 class="wpfnl-notice-title">Oops! It looks like the page builder you selected is inactive.</h4>
                                            <div class="wpfnl-notice-notice-body">
                                                <p>
                                                    It seems like you have selected {{ builder.charAt(0).toUpperCase() + builder.slice(1) }} as your preferred page builder, but you do not have
                                                    the plugin {{dependencyPlugins[builder].name}}  activated on your site.  <br>

                                                    Please install and activate {{ builder.charAt(0).toUpperCase() + builder.slice(1) }} to import ready funnel templates.
                                                    <a href="#" @click="pluginInstallationAction">
                                                        Click here to install & activate {{ builder.charAt(0).toUpperCase() + builder.slice(1) }}
                                                        <span class="dot-wrapper" v-if="pluginInstallLoader">
                                                            <span class="dot-one">.</span>
                                                            <span class="dot-two">.</span>
                                                            <span class="dot-three">.</span>
                                                        </span>
                                                    </a>
                                                </p>

                                                <p>If you want to create & design funnel pages without using {{dependencyPlugins[builder].name}}, then
                                                    don't worry. <b>You can go ahead and create funnels from scratch using any page builder/editor. It will work just fine.</b></p>


                                                <span class="wpfnl-plugin-installation-error"
                                                    v-html="pluginInstallationErrorMessage"></span>
                                            </div>
                                        </div>

                                        <div  v-else-if="builder === 'divi-builder'">
                                            <h4 class="wpfnl-notice-title">Oops! It looks like the page builder you selected is inactive.</h4>
                                            <div class="wpfnl-notice-notice-body">
                                                <p>
                                                    It seems like you have selected {{ builder.charAt(0).toUpperCase() + builder.slice(1) }} as your preferred page builder, but you do not have
                                                    the plugin {{dependencyPlugins[builder].name}}  activated on your site.  <br>

                                                    Please install and activate {{ builder.charAt(0).toUpperCase() + builder.slice(1) }} to import ready funnel templates.
                                                </p>

                                                <p>If you want to create & design funnel pages without using {{dependencyPlugins[builder].name}}, then
                                                    don't worry. <b>You can go ahead and create funnels from scratch using any page builder/editor. It will work just fine.</b></p>


                                                <span class="wpfnl-plugin-installation-error"
                                                    v-html="pluginInstallationErrorMessage"></span>
                                            </div>
                                        </div>

                                        <div  v-else-if="builder === 'oxygen'">
                                            <h4 class="wpfnl-notice-title">Oops! It looks like the page builder you selected is inactive.</h4>
                                            <div class="wpfnl-notice-notice-body">
                                                <p>
                                                    It seems like you have selected Oxygen builder as your preferred page builder, but you do not have
                                                    the plugin Oxygen builder  activated on your site.  <br>

                                                    Please install and activate Oxygen builder to import ready funnel templates.
                                                </p>

                                                <p>If you want to create & design funnel pages without using Oxygen builder, then
                                                    don't worry. <b>You can go ahead and create funnels from scratch using any page builder/editor. It will work just fine.</b></p>


                                                <span class="wpfnl-plugin-installation-error"
                                                    v-html="pluginInstallationErrorMessage"></span>
                                            </div>
                                        </div>
                                        <div  v-else-if="builder === 'bricks'">
                                            <h4 class="wpfnl-notice-title">Oops! It looks like the page builder you selected is inactive.</h4>
                                            <div class="wpfnl-notice-notice-body">
                                                <p>
                                                    It seems like you have selected Bricks builder as your preferred page builder, but you do not have
                                                    the Bricks theme  activated on your site.  <br>

                                                    Please install and activate Bricks theme to import ready funnel templates.
                                                </p>

                                                <p>If you want to create & design funnel pages without using Bricks theme, then
                                                    don't worry. <b>You can go ahead and create funnels from scratch using any page builder/editor. It will work just fine.</b></p>


                                                <span class="wpfnl-plugin-installation-error"
                                                    v-html="pluginInstallationErrorMessage"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="create-funnel__single-template wpfnl-missing-plugin-notice" v-if="builder === 'other' ">
                                        <div>

                                            <h4 class="wpfnl-notice-title">Create your funnel with shortcodes!</h4>
                                            <div class="wpfnl-notice-notice-body">
                                                <p>
                                                    Hello there. We believe you are using a page builder other than Gutenberg, Elementor, Divi, Oxygen, or Bricks.
                                                </p>
                                                <p>
                                                    In this case, to make your funnel function, you should be using shortcodes on specific cases which include
                                                </p>

                                                <li>
                                                    The CTA buttons and opt-in form on the Landing page (or custom page)
                                                </li>
                                                <li>
                                                    The checkout form on the Checkout page
                                                </li>
                                                <li>
                                                    The order details on the Thank you page
                                                </li>
                                                <li>
                                                    Offer Accept/Reject button in the Upsell & Downsell pages.
                                                </li>
                                                <br>
                                                <p>
                                                    And it's really simple.
                                                </p>

                                                <ol style="padding-left: 15px;">
                                                    <li>
                                                        Find the list of shortcodes
                                                        <a href="https://getwpfunnels.com/docs/wpfunnels-shortcodes/" target="_blank">
                                                            here
                                                        </a>
                                                    </li>
                                                    <li>
                                                        Choose the one you want to use and copy it.
                                                    </li>
                                                    <li>
                                                        Paste it where you want to place the element when editing a funnel page.
                                                    </li>
                                                    <li>
                                                        Save and preview to see it in action.
                                                    </li>
                                                </ol>

                                                <br>
                                                <p>
                                                    You may utilize associated parameters to customize the elements.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="steps-preview-wrapper" v-if="showStepsPreview">
                                        <TemplatePreview :template="activeTemplate" :view="responsiveView"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.funnel-templates-content -->
                    </div>
                    <!-- /.funnel-templates-body -->

                </div>
            </div>

        </div>
    </div>
</template>

<script>
import SingleTemplate from './SingleTemplate.vue'
import CategoryNav from './CategoryNav.vue'
import SingleStep from './SingleStep.vue'
import SingleStepPreview from './SingleStepPreview.vue'
import StepImporter from './StepImporter.vue'
import apiFetch from '@wordpress/api-fetch'
import {addQueryArgs} from "@wordpress/url";
import DoubleAngleLeft from '../../../src/components/icons/DoubleAngleLeft.vue'
import PlusIcon from '../../../src/components/icons/PlusIcon.vue'

import FunnelLayout1 from '../../../src/components/icons/FunnelLayout1.vue'
import FunnelLayout2 from '../../../src/components/icons/FunnelLayout2.vue'
import FunnelLayout3 from '../../../src/components/icons/FunnelLayout3.vue'
import FunnelLayout4 from '../../../src/components/icons/FunnelLayout4.vue'
import FunnelLayout5 from '../../../src/components/icons/FunnelLayout5.vue'
import FunnelLayout6 from '../../../src/components/icons/FunnelLayout6.vue'
import ResponsiveSwitcher from './ResponsiveSwitcher.vue'
import TemplatePreview from './TemplatePreview.vue'

var j = jQuery.noConflict()
const nonce = window.template_library_object.nonce
apiFetch.use(apiFetch.createNonceMiddleware(nonce))

export default {
    name: 'TemplatesLibrary',
    components: {
        SingleTemplate,
        CategoryNav,
        StepImporter,
        SingleStepPreview,
        DoubleAngleLeft,
        SingleStep,
        PlusIcon,
        FunnelLayout1,
        FunnelLayout2,
        FunnelLayout3,
        FunnelLayout4,
        FunnelLayout5,
        FunnelLayout6,
        ResponsiveSwitcher,
        TemplatePreview
    },
    data: function () {
        return {
            showModal: j('#template-library-modal').attr('data-modal-visibility'),
            proUrl: window.template_library_object.pro_url,
            isRemoteFunnel : 'yes' === window.template_library_object.isRemote ? true : false,
            templates: [],
            activeTemplate: '',
            allTemplates: [],
            categories: [],
            stepCategories: [],
            steps: [],
            allSteps: [],
            totalTemplates: 0,
            loader: true,
            templateCatFilterLoader: false,
            message: '',
			isProTemplateSelected: false,
            loaderMessage: 'Import',
            activeCategory: 'all',
            activeStepCategory: 'all',
            activeStep: 'landing',
            templatesType: 'all',
            alertClass : 'wpfnl-success',
            isShowAlert : false,
            alertMessage : '',
            templatesCatType: 'woocommerce',
            selectedStep: 'landing',
            stepTemplateType: 'all',
            showProFilter: true,
            showStepsPreview: false,
            isTemplatePage: window.template_library_object.isTemplatePage,
            image_path: window.template_library_object.image_path,
            globalImportProgress: '',
            supportedSteps : window.template_library_object.supported_steps,
            disabled: false,
            templateNewName: '',
            funnelName: '',
            isPro  : window.template_library_object.is_pro,
            createFunnelTitle: 'Create Funnel',
            totalFunnels: window.WPFunnelVars.totalFunnels,
            countActiveFunnels: window.WPFunnelVars.count_active_funnels,
            totalAllowedFunnels: window.WPFunnelVars.totalAllowedFunnels,
            dependencyPlugins: window.WPFunnelVars.dependencyPlugins,
            isAnyPluginMissing: window.WPFunnelVars.isAnyPluginMissing,
			isProActivated: window.WPFunnelVars.isProActivated,
			isWcActivated: window.WPFunnelVars.is_wc_installed,
			isLmsActivated: window.WPFunnelVars.isLmsActivated,
            builder: window.WPFunnelVars.builder,
            pluginInstallLoader: false,
            pluginInstallationErrorMessage: '',
            funnelType: window.WPFunnelVars.global_funnel_type,
			type: 'lead' == window.WPFunnelVars.global_funnel_type ? 'lead' : window.WPFunnelVars.is_wc_installed === 'yes' ? 'wc' : 'lms',
			selectedType: 'lead' == window.WPFunnelVars.global_funnel_type ? 'lead' : window.WPFunnelVars.is_wc_installed === 'yes' ? 'wc' : 'lms',
            tempalateTypes : [],
            template_type : window.template_library_object.template_type,
            activeTab : 'templates',
            layouts : [
                {
                    name : 'Layout 1',
                    value:'layout1',
                    steps : [
                        {
                            name : 'Landing',
                            value:'landing',
                            pos_x: 156,
                            pos_y: 258
                        },
                        {
                            name : 'Thank You',
                            value:'thankyou',
                            pos_x: 399,
                            pos_y: 258
                        }
                    ]
                },
                {
                    name : 'Layout 2',
                    value:'layout2',
                    steps : [
                        {
                            name : 'Landing',
                            value:'landing',
                            pos_x: 156,
                            pos_y: 258
                        },
                        {
                            name : 'Checkout',
                            value:'checkout',
                            pos_x: 399,
                            pos_y: 258
                        },
                        {
                            name : 'Thank You',
                            value:'thankyou',
                            pos_x: 658,
                            pos_y: 258
                        }
                    ]
                },
                {
                    name : 'Layout 3',
                    value:'layout3',
                    steps : [
                        {
                            name : 'Landing',
                            value:'landing',
                            pos_x: 156,
                            pos_y: 258
                        },
                        {
                            name : 'Checkout',
                            value:'checkout',
                            pos_x: 399,
                            pos_y: 258
                        },
                        {
                            name : 'Upsell',
                            value:'upsell',
                            pos_x: 658,
                            pos_y: 258
                        },
                        {
                            name : 'Downsell',
                            value:'downsell',
                            pos_x: 917,
                            pos_y: 258
                        },
                        {
                            name : 'Thank You',
                            value:'thankyou',
                            pos_x: 1175,
                            pos_y: 258
                        }
                    ]
                },
                {
                    name : 'Layout 4',
                    value:'layout4',
                    steps : [
                        {
                            name : 'Landing',
                            value:'landing',
                            pos_x: 156,
                            pos_y: 258
                        },
                        {
                            name : 'Checkout',
                            value:'checkout',
                            pos_x: 399,
                            pos_y: 258
                        },
                        {
                            name : 'Upsell',
                            value:'upsell',
                            pos_x: 658,
                            pos_y: 258,
                            isCondition : true,
                            trueCondition : [
                                {
                                    name : 'Thank You',
                                    value:'thankyou',
                                    pos_x: 1059,
                                    pos_y: 111,
                                }
                            ],
                            falseCondition : [
                                {
                                    name : 'Downsell',
                                    value:'downsell',
                                    pos_x: 1059,
                                    pos_y: 447,
                                },
                                {
                                    name : 'Thank You',
                                    value:'thankyou',
                                    pos_x: 1317,
                                    pos_y: 447,
                                }
                            ]
                        }
                        
                    ]
                },
                {
                    name : 'Layout 5',
                    value:'layout5',
                    steps : [
                        {
                            name : 'Landing',
                            value:'landing',
                            pos_x: 156,
                            pos_y: 258
                        },
                        {
                            name : 'Checkout',
                            value:'checkout',
                            isCondition : true,
                            pos_x: 399,
                            pos_y: 258,
                            trueCondition : [
                                {
                                    name : 'Upsell',
                                    value: 'upsell',
                                    pos_x: 800,
                                    pos_y: 111,
                                },
                                {
                                    name : 'Thank You',
                                    value:'thankyou',
                                    pos_x: 1059,
                                    pos_y: 111,
                                }
                            ],
                            falseCondition : [
                                {
                                    name : 'Downsell',
                                    value:'downsell',
                                    pos_x: 800,
                                    pos_y: 447,
                                },
                                {
                                    name : 'Thank You',
                                    value:'thankyou',
                                    pos_x: 1059,
                                    pos_y: 447,
                                }
                            ]
                        }
                    ]
                },
                {
                    name : 'Layout 6',
                    value:'layout6',
                    steps : [
                        {
                            name : 'Landing',
                            value:'landing',
                            pos_x: 156,
                            pos_y: 258,
                            isCondition : true,
                            trueCondition : [
                                {
                                    name : 'Thank You',
                                    value:'thankyou',
                                    pos_x: 556,
                                    pos_y: 111
                                }
                            ],
                            falseCondition : [
                                {
                                    name : 'Custom',
                                    value:'custom',
                                    pos_x: 556,
                                    pos_y: 447
                                },
                                {
                                    name : 'Thank You',
                                    value:'thankyou',
                                    pos_x: 814,
                                    pos_y: 447
                                }
                            ]
                        },
                        
                        
                    ]
                },
            ],
            selectedFunnelLayout : {},
            responsiveView: 'desktop',
        }

    },

    computed: {
        isAddNewFunnelButtonDisabled: function () {
            if (!this.isProActivated) {
                if (parseInt(this.countActiveFunnels) >= parseInt(this.totalAllowedFunnels)) {
                    return true
                }
            }
            return false
        }
    },
    mounted() {
        j(document).on(
            'wp-plugin-install-success',
            this.pluginInstalledSuccess
        );

        j(document).on(
            'wp-plugin-install-error',
            this.pluginInstalledError
        );
        if(!this.isRemoteFunnel) {
            this.getTemplate();
		} else {
			this.loader = false
		}

    },
    
    methods: {

        selectFunnelLayout: function (index, value) {
            if(this.isPro || index === 0 || index === 1 ){
                if( this.selectedFunnelLayout == value){
                    this.selectedFunnelLayout = {};
                }else {
					if('lead' == this.selectedType ){
						if( (index === 0 && !this.isPro) || ((index === 0 || index === 5) && this.isPro)  ){
							this.selectedFunnelLayout = value;
						}else{
							this.isShowAlert = true;
							this.alertClass = 'wpfnl-error';
							this.alertMessage = 'This is only for sales funnel';

							setTimeout(() => {
								this.isShowAlert = false
							}, 3000);
							this.selectedFunnelLayout = {};
							return;
						}
					}else{
						this.isShowAlert = false;
						this.alertMessage = '';
						this.alertClass = '';
						this.selectedFunnelLayout = value;
					}
                }
            }
        },

        getTemplate: function (){

            if( this.selectedType ){
                apiFetch({
                    path: addQueryArgs( `${window.template_library_object.rest_api_url}wpfunnels/v1/templates/get_templates`, {
                        type: this.selectedType
                    } ),
                    method: 'GET'
                }).then(response => {
                    if (response.success) {
                        this.templates 		= response.templates
                        this.allTemplates 	= response.templates
						this.steps 			= response.steps
						this.allSteps 		= response.steps
                        this.templatesType 	= 'all'
                        this.activeCategory = 'all'
                        this.categories 	= response.categories
                        this.stepCategories = response.categories
						this.loader 		= false
						this.templateCatFilterLoader = false;
                        if (response.templates) {
                            this.totalTemplates = this.isAnyPluginMissing === 'yes' ? 0 : response.templates.length
                        }

                    }

                })
            }

        },

        createFunnel: function (e) {
            e.preventDefault();
            if( !this.funnelName ){
                this.isShowAlert = true;
                this.alertClass = 'wpfnl-error';
                this.alertMessage = 'Please enter funnel name';
                
                setTimeout(() => {
					this.isShowAlert = false
				}, 2500);

                return;
            }

            this.disabled = true;
            this.createFunnelTitle = "Creating Funnel..."
            var payload = {
                funnelName: this.funnelName,
                type      : this.type,
                selectedFunnelLayout : this.selectedFunnelLayout,
            };
            wpAjaxHelperRequest("create-funnel", payload)
                .success(function (response) {
                    window.location.href = response.redirectUrl;
                    this.disabled = false;
                })
                .error(function (response) {

                });
        },

        createFunnelWithSingleStep: function () {

            this.disabled = true;
            this.createFunnelTitle = "Creating Funnel..."
            var payload = {
                funnelName: this.funnelName,
                type      : this.type,
            };
            wpAjaxHelperRequest("create-funnel", payload)
                .success(function (response) {
                    window.location.href = response.redirectUrl;
                    this.disabled = false;
                })
                .error(function (response) {

                });
        },

        showFunnelNameModal: function (e) {
            e.preventDefault()

            j('.wpfnl-create-funnel__templates-wrapper .not-clickable-overlay').fadeIn();
            j('.wpfnl-create-funnel__templates-wrapper .create-funnel-name-modal').addClass('show');
        },

        closeFunnelNameModal: function (e) {
            e.preventDefault()
            j('.wpfnl-create-funnel__templates-wrapper .not-clickable-overlay').fadeOut();
            j('.wpfnl-create-funnel__templates-wrapper .create-funnel-name-modal').removeClass('show');
            this.selectedFunnelLayout = {};
        },

        startImportTemplate: function (e) {
            e.preventDefault();
            if (this.isAddNewFunnelButtonDisabled) return false;

            this.disabled = true;

            let data = {
                    action	: 'wpfunnel_import_funnel',
                    steps	: this.steps,
                    name	: j('.import-funnel-name input').val() ? j('.import-funnel-name input').val() : this.activeTemplate.title,
                    source	: 'remote',
                    remoteID: this.activeTemplate.ID,
					type    : this.type,
                },
                that = this;

            that.loaderMessage = 'Please Wait...',
                this.activeTemplate.title = data.name;

            wpAjaxHelperRequest("wpfunnel-import-funnel", data)
                .success(function (response) {
                    let looper = j.Deferred().resolve(),
                        first_step_id = 0;
                    j.when.apply(j, j.map(that.steps, function (step, index) {
                        looper = looper.then(function () {
                            return that.createStep(step, response.funnelID, index, that);
                        });
                        return looper;
                    })).then(function () {
                        that.afterFunnelCreationRedirect(response.funnelID);
                    });
                })
                .error(function (response) {
                    console.log("Uh, oh!");
                    console.log(response.statusText);
                });
        },

        createStep: function (step, funnelID, index, that, funnelData) {
            let deferred = j.Deferred(),
                payload = {
                    'step'		: step,
                    'funnelID'	: funnelID,
                    'source'	: 'remote',
                    'funnelData': JSON.stringify(funnelData),
                    'importType': 'templates',
                };
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
            return deferred.promise();
        },

        afterFunnelCreationRedirect: function (funnelId) {
            var payload = {
                'funnelID': funnelId,
                'source': 'remote'
            };
            wpAjaxHelperRequest("wpfunnel-after-funnel-creation", payload)
                .success(function (response) {
                    window.location = response.redirectLink
                })
                .error(function (response) {
                    console.log(response)
                });
        },

        doCatFilter: function (value) {
            this.activeCategory = value === '' ? 'all' : value

            if (value !== '') {
                this.templates = this.allTemplates.filter(function (template) {
                    if( null !== template.wpf_funnel_industry ) {
                        if( 'slug' in template.wpf_funnel_industry ){
                            if( 'all' === value ){
                                return true;
                            }else{
                                return template.wpf_funnel_industry.slug === value;
                            }
                        }
                    }

                });
            }else {
                this.templates = this.allTemplates
            }
            this.totalTemplates = this.templates.length
        },

        doStepCatFilter: function (value) {
            this.activeStepCategory = value === '' ? 'all' : value
            let activeStep = this.activeStep
            if (value !== '') {
                this.steps = this.allSteps.filter(function (step) {
                    return step.industry.slug === value && step.step_type === activeStep;
                });
            } else {
                this.steps = this.allSteps
            }
        },

        setActiveStep: function (value) {
            this.activeStep = value;
        },

        doStepFilter: function (value) {
            this.activeStep = value
            // this.steps = this.allSteps.filter(function (step) {
            //     return step.step_type === value;
            // });
        },

        doStepFreeProFilter: function (value) {
            this.stepTemplateType = value
            this.steps = this.allSteps.filter(function (step) {
                return value === 'pro' ? step.is_pro : !step.is_pro;
            });
        },

        doFreeProFilter: function (value) {
            this.templatesType = value
            let activeCategory = this.activeCategory
            this.templates = this.allTemplates.filter(function (template) {
				return (activeCategory === 'all' || template.wpf_funnel_industry?.slug === activeCategory) &&
					(
						value === 'all' ||
						(value === 'pro' && 'pro' === template.templateType) ||
						(value === 'free' && 'free' === template.templateType) ||
						(value === 'freemium' && 'freemium' === template.templateType )
					);
			});
            this.totalTemplates = this.templates.length
        },

        doTemplateCatFilter: function (e) {
            this.loader = true;
            this.templateCatFilterLoader = true;

            this.selectedType = this.type
            // this.doCatFilter('')
            if(!this.isRemoteFunnel) {
                this.getTemplate();
            }else{
                this.loader = false
                this.templateCatFilterLoader = false
            }

        },

        toggleLoader: function (e) {
            this.loader = !this.loader
        },

        showLoaderMessage: function (e, message) {
            this.message = message
        },

        getBackBtnValue: function (params) {
            this.showBackBtn = params;
        },

        hideProFilter: function () {
            this.showProFilter = !this.showProFilter
        },

        toggleStepsPreview: function () {
            this.showStepsPreview = !this.showStepsPreview;
        },

        initSteps: function (steps) {
            this.steps = steps;
        },

        backToTemplates: function (e) {
            this.showStepsPreview = false
        },

        funnelNewName: function (data) {
            this.templateNewName = j('.import-funnel-name input').val();
        },

        setActiveTemplate: function (data) {
            this.activeTemplate = data;
            // if(data.is_pro && this.isProActivated) {
			// 	this.isProTemplateSelected = true;
			// } else if(!data.isPro) {
			// 	this.isProTemplateSelected = true;
			// }else {
			// 	this.isProTemplateSelected = false;
			// }
        },

        activatePlugin: function (payload, that) {
            wpAjaxHelperRequest("wpfunnels-activate-plugin", payload)
                .success(function (response) {
                    that.isAnyPluginMissing = 'no'
                })
                .error(function (response) {

                });
        },

        pluginInstallationAction: function (e) {
            e.preventDefault();

            this.pluginInstallLoader = true;

            var plugin = this.dependencyPlugins[this.builder],
                pluginFile = plugin.plugin_file,
                payload = {
                    pluginFile: pluginFile
                },
                action = this.dependencyPlugins[this.builder].action,
                that = this;
            if (action == 'activate') {
                this.activatePlugin(payload, that);
            } else {
                wp.updates.queue.push({
                    action: 'install-plugin',
                    data: {
                        slug: plugin.slug,
                    },
                });
                wp.updates.queueChecker();
            }

        },

        pluginInstalledSuccess: function (e, response) {
            e.preventDefault();
            var plugin = this.dependencyPlugins[this.builder],
                pluginFile = plugin.plugin_file,
                payload = {
                    pluginFile: pluginFile
                },
                that = this;
            this.activatePlugin(payload, that);
        },

        pluginInstalledError: function (e, response) {
            e.preventDefault();
            this.pluginInstallLoader = false;
            this.pluginInstallationErrorMessage = response.errorMessage
        },

        onActivateTab(tabId) {
            this.activeTab = tabId;
        },
        setResponsiveView(view) {
            this.responsiveView = view;
        },

    }
}
</script>
