<template>
    <div class="wizard-single-content funnel-type">
        <div class="step-header">
            <h4 class="title">Email Automation</h4>
            <p class="description">You may choose to manage your leads and run email marketing automation flows using our own plugin, Mail Mint.</p>
        </div>

        <div class="wizard-single-content__body">
            

            <div class="wpfnl-field-wrapper">
                <div class="wpfnl-fields">
                    <span class="wpfnl-checkbox no-title" v-if="'yes' !== isMrmActive">
                        <input type="checkbox" name="install-mrm" v-model="isIstallMailMint" id="install-mrm" @change="isPermitted"/>
                        <label for="install-mrm"></label>
                    </span>
                    <label>
                        {{ getLabelMrm }}
                        <span class="wpfnl-tooltip">
                            
                            <p>Enable to install MailMint</p>
                        </span>
                    </label>
                </div>
                
                <p class="hints">
                    Effortless email marketing automation tool to collect & manage leads, run email campaigns, and initiate basic email automation.
                </p>
            </div>
        </div>

        <div class="wizard-single-content__footer">
            <a :href="prevStepLink" class="wizard-btn btn-default prev">previous</a>
            <a href="#" class="wizard-btn btn-default next" @click="processSettings">Next
                <span class="wpfnl-loader" v-if="showLoader"></span>
            </a>
        </div>

    </div>
</template>

<script>
    import apiFetch from '@wordpress/api-fetch'
    import TooltipIcon from '../../../src/components/icons/TooltipIcon.vue'
    // eslint-disable-next-line no-undef
    var j = jQuery.noConflict()
    const nonce = window.setup_wizard_obj.nonce
    apiFetch.use(apiFetch.createNonceMiddleware(nonce))

    export default {
        name: 'Automation',
        components: {
            TooltipIcon
        },
        props: {
            // eslint-disable-next-line vue/require-default-prop
            wizardSlug: String,
            prevStepLink: String,
            showLoader: Boolean,
            isMrmInstalled: String,
            isMrmActive: String,
        },
        data: function () {
            return {
                isIstallMailMint: 'yes' === this.isMrmActive ? false : true,
            }
    },
    computed: {
        getLabelMrm() {
            // Computed property
            if ('yes' === this.isMrmInstalled && 'no' === this.isMrmActive) {
                return 'Activate Mail Mint'
            } else if ('no' === this.isMrmInstalled && 'no' === this.isMrmActive) {
                return 'Install & Activate Mail Mint'
            } else {
                return 'Mail Mint is already activated'
            }
        },
    },
        mounted () {
            this.$emit('setPluginSlug', 'mail-mint', true)
            this.$emit('changeSetUpType', 'plugin')
        },
        methods: {

            processSettings: function (e) {
                e.preventDefault();
                this.$emit('processSettings')
            },
            isPermitted: function (e) {
                e.preventDefault();
                if( this.isIstallMailMint ){
                    this.$emit('setPluginSlug', 'mail-mint', true)
                }else{
                    this.$emit('setPluginSlug', '');
                }
                
            },
            
        }
    }
</script>
