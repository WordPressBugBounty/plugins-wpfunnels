<template>
    <div class="wpfnl-step__single-template" >
        <div class="template-preview-image">
            <div class="template-action">
                <a :href="step.link" target="_blank" class="btn-default step-btn"> preview </a>
            </div>

            <div class="step-image-wrapper" :style="{ backgroundImage: `url(${image})` }">
            </div>
        </div>

        <div class="step-template-info">
            <span class="title">{{step.title}}</span>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'SingleStepPreview',
        props: {
            step: Object,
            image: String,
            previewLink: String,
            isAddNewFunnelButtonDisabled: Boolean,
        },
        data: function () {
            return {
                showLoader : false
            }
        },
        methods: {
            importStep: function (e) {
                e.preventDefault();
                var that = this;
                this.showLoader = true;
                that.createStep( {'id' : this.step.id, 'type' : this.step.step_type, 'name' : this.step.step_type}, this.funnelID, that);
            },
        }
    }
</script>